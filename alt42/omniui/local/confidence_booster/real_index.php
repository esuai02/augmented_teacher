<?php
/**
 * Confidence Booster - 실제 데이터 연동 버전
 * 이현선 학생 맞춤형 학습 지원 시스템
 * 
 * 실제 MathKing 데이터베이스와 Alt42t 시험 시스템 연동
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 설정 파일 로드
require_once('config.php');

// 로그인 체크
$userid = confidence_require_login();
if (!$userid) {
    header('Location: /moodle/login/index.php');
    exit;
}

// DB 연결
$pdo = get_confidence_db_connection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// ==========================
// 실제 데이터 조회 시작
// ==========================

// 1. 사용자 정보 (mdl_user)
$stmt = $pdo->prepare("
    SELECT id, username, firstname, lastname, email, phone1, phone2
    FROM mdl_user 
    WHERE id = ? AND deleted = 0
");
$stmt->execute([$userid]);
$user = $stmt->fetch();

if (!$user) {
    die('사용자 정보를 찾을 수 없습니다.');
}

$user_name = trim($user['firstname'] . ' ' . $user['lastname']);

// 2. 사용자 역할 확인 (mdl_user_info_data)
$stmt = $pdo->prepare("
    SELECT data 
    FROM mdl_user_info_data 
    WHERE userid = ? AND fieldid = 22
");
$stmt->execute([$userid]);
$role_data = $stmt->fetchColumn();
$is_student = ($role_data === 'student');

// 3. Alt42t 시험 정보 조회 (mdl_alt42t_users, mdl_alt42t_exams)
$stmt = $pdo->prepare("
    SELECT u.*, e.exam_type, e.exam_range, ed.math_date
    FROM mdl_alt42t_users u
    LEFT JOIN mdl_alt42t_exams e ON u.school_name = e.school_name AND u.grade = e.grade
    LEFT JOIN mdl_alt42t_exam_dates ed ON e.exam_id = ed.exam_id AND ed.user_id = u.id
    WHERE u.userid = ?
    ORDER BY e.exam_id DESC
    LIMIT 1
");
$stmt->execute([$userid]);
$exam_info = $stmt->fetch();

// D-Day 계산
$dday = null;
$exam_phase = 'prepare';
if ($exam_info && $exam_info['math_date']) {
    $exam_date = new DateTime($exam_info['math_date']);
    $today = new DateTime();
    $interval = $today->diff($exam_date);
    $dday = $interval->invert ? -$interval->days : $interval->days;
    
    if ($dday <= 0) {
        $exam_phase = 'finished';
    } elseif ($dday <= 7) {
        $exam_phase = 'finish';
    } elseif ($dday <= 21) {
        $exam_phase = 'intensive';
    } else {
        $exam_phase = 'prepare';
    }
}

// 4. 최근 학습 활동 (mdl_abessi_missionlog)
$stmt = $pdo->prepare("
    SELECT page, COUNT(*) as count, MAX(timecreated) as last_time
    FROM mdl_abessi_missionlog
    WHERE userid = ? AND timecreated > ?
    GROUP BY page
    ORDER BY count DESC
    LIMIT 5
");
$seven_days_ago = time() - (7 * 24 * 60 * 60);
$stmt->execute([$userid, $seven_days_ago]);
$recent_activities = $stmt->fetchAll();

// 5. 출결 기록 (mdl_abessi_attendance_record)
$stmt = $pdo->prepare("
    SELECT type, COUNT(*) as count, SUM(hours) as total_hours
    FROM mdl_abessi_attendance_record
    WHERE userid = ? AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY type
");
$stmt->execute([$userid]);
$attendance_stats = $stmt->fetchAll();

// 6. 학습 진행 상황 (mdl_abessi_progress)
$stmt = $pdo->prepare("
    SELECT progress_data, timemodified
    FROM mdl_abessi_progress
    WHERE userid = ?
");
$stmt->execute([$userid]);
$progress = $stmt->fetch();
$progress_data = $progress ? json_decode($progress['progress_data'], true) : [];

// 7. 최근 요약 (mdl_abessi_today)
$stmt = $pdo->prepare("
    SELECT goals, timecreated, timemodified
    FROM mdl_abessi_today
    WHERE userid = ?
    ORDER BY timemodified DESC
    LIMIT 5
");
$stmt->execute([$userid]);
$recent_summaries = $stmt->fetchAll();

// 8. 챕터별 학습 (mdl_abessi_chapterlog)
$stmt = $pdo->prepare("
    SELECT chapter, COUNT(*) as study_count, MAX(timecreated) as last_study
    FROM mdl_abessi_chapterlog
    WHERE userid = ?
    GROUP BY chapter
    ORDER BY last_study DESC
    LIMIT 10
");
$stmt->execute([$userid]);
$chapter_logs = $stmt->fetchAll();

// 9. 오답 분석 (mdl_abessi_mathtalk)
$stmt = $pdo->prepare("
    SELECT content, timecreated
    FROM mdl_abessi_mathtalk
    WHERE userid = ? 
    ORDER BY timecreated DESC
    LIMIT 20
");
$stmt->execute([$userid]);
$mathtalk_logs = $stmt->fetchAll();

// 오답 유형별 분류
$error_types = [];
$summaries_from_mathtalk = [];
foreach ($mathtalk_logs as $log) {
    $content = json_decode($log['content'], true);
    if ($content) {
        if (isset($content['error_type'])) {
            // 오답 분석
            $type = $content['error_type'];
            if (!isset($error_types[$type])) {
                $error_types[$type] = 0;
            }
            $error_types[$type]++;
        } elseif (isset($content['summary'])) {
            // 요약 데이터
            $summaries_from_mathtalk[] = $content;
        }
    }
}

// 10. 연속 학습 일수 계산
$streak = 0;
for ($i = 0; $i < 365; $i++) {
    $check_date = strtotime("-{$i} days");
    $start = strtotime(date('Y-m-d 00:00:00', $check_date));
    $end = strtotime(date('Y-m-d 23:59:59', $check_date));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM mdl_abessi_missionlog 
        WHERE userid = ? AND timecreated BETWEEN ? AND ?
    ");
    $stmt->execute([$userid, $start, $end]);
    
    if ($stmt->fetchColumn() > 0) {
        $streak++;
    } else {
        if ($i > 0) break;
    }
}

// 11. 오늘의 통계
$today_start = strtotime('today');
$today_end = strtotime('tomorrow') - 1;

$stmt = $pdo->prepare("
    SELECT COUNT(*) as activity_count
    FROM mdl_abessi_missionlog
    WHERE userid = ? AND timecreated BETWEEN ? AND ?
");
$stmt->execute([$userid, $today_start, $today_end]);
$today_activities = $stmt->fetchColumn();

// 12. 시험 자료 (mdl_alt42t_exam_resources)
$exam_resources = [];
if ($exam_info && isset($exam_info['exam_id'])) {
    $stmt = $pdo->prepare("
        SELECT resource_id, title, file_url, tip_text, uploaded_at
        FROM mdl_alt42t_exam_resources
        WHERE exam_id = ?
        ORDER BY uploaded_at DESC
        LIMIT 5
    ");
    $stmt->execute([$exam_info['exam_id']]);
    $exam_resources = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confidence Booster - <?php echo htmlspecialchars($user_name); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- 헤더 섹션 -->
        <div class="glass p-8 mb-8">
            <div class="flex flex-wrap items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold gradient-text mb-2">
                        안녕하세요, <?php echo htmlspecialchars($user_name); ?>님!
                    </h1>
                    <p class="text-gray-600">
                        <?php if ($exam_info): ?>
                            <span class="font-semibold"><?php echo htmlspecialchars($exam_info['school_name']); ?></span> 
                            <span class="mx-2">|</span>
                            <span><?php echo htmlspecialchars($exam_info['grade']); ?></span>
                            <?php if ($dday !== null && $dday > 0): ?>
                            <span class="mx-2">|</span>
                            <span class="text-purple-600 font-bold">시험 D-<?php echo $dday; ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-center mt-4 md:mt-0">
                    <div class="text-5xl font-bold text-purple-600"><?php echo $streak; ?></div>
                    <div class="text-gray-500">연속 학습일</div>
                </div>
            </div>
        </div>

        <?php if ($exam_info && $dday !== null && $dday > 0): ?>
        <!-- 시험 정보 섹션 -->
        <div class="glass p-6 mb-8 border-l-4 border-purple-600">
            <h2 class="text-2xl font-bold mb-4 gradient-text">
                📚 다가오는 시험 정보
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-gray-500 text-sm">시험 종류</p>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($exam_info['exam_type'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">수학 시험일</p>
                    <p class="font-bold text-lg">
                        <?php echo $exam_info['math_date'] ? date('Y년 m월 d일', strtotime($exam_info['math_date'])) : '미정'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">시험 범위</p>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($exam_info['exam_range'] ?? '전체'); ?></p>
                </div>
            </div>
            
            <?php if ($exam_phase === 'finish'): ?>
            <div class="mt-4 p-3 bg-red-50 rounded-lg">
                <p class="text-red-800">
                    <i class="fas fa-exclamation-triangle"></i> 
                    시험이 얼마 남지 않았습니다! 최종 점검에 집중하세요.
                </p>
            </div>
            <?php elseif ($exam_phase === 'intensive'): ?>
            <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                <p class="text-yellow-800">
                    <i class="fas fa-clock"></i> 
                    집중 학습 기간입니다. 약점 보완에 신경쓰세요.
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 실시간 통계 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- 오늘의 활동 -->
            <div class="glass p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">📊</div>
                </div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $today_activities; ?></div>
                <div class="text-gray-500 text-sm mt-1">오늘의 활동</div>
            </div>

            <!-- 총 요약 작성 -->
            <div class="glass p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">📝</div>
                </div>
                <div class="text-3xl font-bold text-gray-800">
                    <?php echo count($recent_summaries); ?>
                </div>
                <div class="text-gray-500 text-sm mt-1">최근 요약</div>
            </div>

            <!-- 챕터 학습 -->
            <div class="glass p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">📚</div>
                </div>
                <div class="text-3xl font-bold text-gray-800">
                    <?php echo count($chapter_logs); ?>
                </div>
                <div class="text-gray-500 text-sm mt-1">학습한 챕터</div>
            </div>

            <!-- 오답 분석 -->
            <div class="glass p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">🔍</div>
                </div>
                <div class="text-3xl font-bold text-gray-800">
                    <?php echo array_sum($error_types); ?>
                </div>
                <div class="text-gray-500 text-sm mt-1">오답 분석</div>
            </div>
        </div>

        <!-- 메인 컨텐츠 그리드 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 왼쪽: 학습 활동 -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- 최근 학습 활동 -->
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">최근 학습 활동</h2>
                    <div class="space-y-3">
                        <?php foreach($recent_activities as $activity): ?>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <div>
                                <span class="font-semibold text-gray-800">
                                    <?php 
                                    $activity_labels = [
                                        'summary_writing' => '요약 작성',
                                        'error_analysis' => '오답 분석',
                                        'chapter_study' => '챕터 학습',
                                        'quiz' => '퀴즈',
                                        'review' => '복습'
                                    ];
                                    echo $activity_labels[$activity['page']] ?? $activity['page'];
                                    ?>
                                </span>
                                <span class="text-sm text-gray-500 ml-2">
                                    <?php echo date('m/d H:i', $activity['last_time']); ?>
                                </span>
                            </div>
                            <span class="text-purple-600 font-bold"><?php echo $activity['count']; ?>회</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 챕터별 학습 현황 -->
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">챕터별 학습 현황</h2>
                    <div class="space-y-3">
                        <?php foreach($chapter_logs as $chapter): ?>
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-gray-800">
                                        <?php echo htmlspecialchars($chapter['chapter']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        학습 횟수: <?php echo $chapter['study_count']; ?>회
                                    </p>
                                </div>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('m/d', $chapter['last_study']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 최근 요약 -->
                <?php if (count($recent_summaries) > 0): ?>
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">최근 작성한 요약</h2>
                    <div class="space-y-3">
                        <?php foreach($recent_summaries as $summary): 
                            $goals = json_decode($summary['goals'], true);
                            if ($goals):
                        ?>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="flex justify-between mb-2">
                                <h3 class="font-bold text-gray-800">
                                    <?php echo htmlspecialchars($goals['chapter'] ?? $goals['title'] ?? '제목 없음'); ?>
                                </h3>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('m/d H:i', $summary['timemodified']); ?>
                                </span>
                            </div>
                            <?php if (isset($goals['summary'])): ?>
                            <p class="text-gray-600 text-sm">
                                <?php echo htmlspecialchars(mb_substr($goals['summary'], 0, 100)); ?>...
                            </p>
                            <?php endif; ?>
                            <div class="mt-2 flex gap-2">
                                <?php if (isset($goals['quality'])): ?>
                                <span class="px-2 py-1 bg-purple-100 text-purple-600 text-xs rounded">
                                    품질: <?php echo $goals['quality']; ?>%
                                </span>
                                <?php endif; ?>
                                <?php if (isset($goals['confidence'])): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-600 text-xs rounded">
                                    자신감: <?php echo $goals['confidence']; ?>%
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 시험 자료 -->
                <?php if (count($exam_resources) > 0): ?>
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">시험 자료</h2>
                    <div class="space-y-3">
                        <?php foreach($exam_resources as $resource): ?>
                        <div class="p-3 border rounded-lg hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold">
                                        <?php echo htmlspecialchars($resource['title']); ?>
                                    </h4>
                                    <?php if ($resource['tip_text']): ?>
                                    <p class="text-sm text-gray-600 mt-1">
                                        💡 <?php echo htmlspecialchars($resource['tip_text']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($resource['file_url']): ?>
                                <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                   target="_blank" 
                                   class="text-purple-600 hover:text-purple-800">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- 오른쪽: 통계 및 분석 -->
            <div class="space-y-6">
                <!-- 출결 통계 -->
                <?php if (count($attendance_stats) > 0): ?>
                <div class="glass p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text">출결 현황</h3>
                    <div class="space-y-3">
                        <?php foreach($attendance_stats as $stat): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-700">
                                <?php 
                                $type_labels = [
                                    'absence' => '결석',
                                    'makeup_complete' => '보충 완료',
                                    'add_absence' => '추가 결석'
                                ];
                                echo $type_labels[$stat['type']] ?? $stat['type'];
                                ?>
                            </span>
                            <span class="font-bold">
                                <?php echo $stat['count']; ?>회 
                                <?php if ($stat['total_hours']): ?>
                                (<?php echo round($stat['total_hours'], 1); ?>시간)
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 오답 유형 분석 -->
                <?php if (count($error_types) > 0): ?>
                <div class="glass p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text">오답 유형 분석</h3>
                    <div class="space-y-3">
                        <?php 
                        $type_labels = [
                            'calculation' => '계산 실수',
                            'concept' => '개념 이해 부족',
                            'application' => '문제 적용 오류',
                            'careless' => '부주의'
                        ];
                        foreach($error_types as $type => $count): 
                            $percentage = round(($count / array_sum($error_types)) * 100);
                        ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">
                                    <?php echo $type_labels[$type] ?? $type; ?>
                                </span>
                                <span class="font-bold text-purple-600"><?php echo $count; ?>개 (<?php echo $percentage; ?>%)</span>
                            </div>
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 p-3 bg-purple-50 rounded-lg">
                        <p class="text-sm text-purple-800">
                            💡 가장 많은 오류 유형에 집중하여 개선하세요!
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 학습 진행도 -->
                <?php if ($progress_data): ?>
                <div class="glass p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text">학습 진행 상황</h3>
                    <?php if (isset($progress_data['average_quality'])): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-1">평균 품질 점수</p>
                        <div class="text-3xl font-bold text-purple-600">
                            <?php echo round($progress_data['average_quality']); ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($progress_data['summaries']) && count($progress_data['summaries']) > 0): ?>
                    <div>
                        <p class="text-sm text-gray-600 mb-2">최근 7일 요약 작성</p>
                        <div class="flex space-x-1">
                            <?php 
                            $last_7_days = array_slice($progress_data['summaries'], -7);
                            foreach($last_7_days as $summary): 
                                $quality = $summary['quality'] ?? 0;
                                $color = $quality >= 80 ? 'bg-green-400' : ($quality >= 60 ? 'bg-yellow-400' : 'bg-red-400');
                            ?>
                            <div class="flex-1 h-8 <?php echo $color; ?> rounded" 
                                 title="품질: <?php echo $quality; ?>%"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- 동기부여 메시지 -->
                <div class="glass p-6 bg-gradient-to-br from-purple-100 to-pink-100">
                    <h3 class="text-xl font-bold mb-3 gradient-text">오늘의 격려</h3>
                    <blockquote class="italic text-gray-700">
                        <?php if ($streak >= 7): ?>
                        "일주일 연속 학습! 당신의 꾸준함이 실력으로 바뀌고 있어요! 💪"
                        <?php elseif ($streak >= 3): ?>
                        "3일 연속 학습 중! 좋은 습관이 만들어지고 있어요! ✨"
                        <?php else: ?>
                        "오늘도 한 걸음 더 성장했습니다. 내일도 함께해요! 🌟"
                        <?php endif; ?>
                    </blockquote>
                </div>
            </div>
        </div>

        <!-- 액션 버튼 -->
        <div class="glass p-6 mt-8">
            <div class="flex flex-wrap gap-4 justify-center">
                <button onclick="location.href='../../../save_summary.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-pen mr-2"></i>요약 작성하기
                </button>
                <button onclick="location.href='../../../save_error.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-search mr-2"></i>오답 분석하기
                </button>
                <button onclick="location.href='../../../learning_tracker.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-chart-line mr-2"></i>학습 추적기
                </button>
            </div>
        </div>
    </div>

    <script>
    // 페이지 로드 시 애니메이션
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.card-hover');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });
    });
    </script>
</body>
</html>