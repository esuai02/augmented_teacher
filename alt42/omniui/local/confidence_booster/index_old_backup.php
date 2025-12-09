<?php
/**
 * Confidence Booster - 실제 데이터 연동 버전
 * 이현선 학생 맞춤형 학습 지원 시스템
 * 
 * 실제 MathKing 데이터베이스와 Alt42t 시험 시스템 연동
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster/
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
// 실제 데이터 조회
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

// 2. Alt42t 시험 정보 조회
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
    }
}

// 3. 최근 학습 활동 (mdl_abessi_missionlog)
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

// 4. 출결 기록
$stmt = $pdo->prepare("
    SELECT type, COUNT(*) as count, SUM(hours) as total_hours
    FROM mdl_abessi_attendance_record
    WHERE userid = ? AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY type
");
$stmt->execute([$userid]);
$attendance_stats = $stmt->fetchAll();

// 5. 학습 진행 상황
$stmt = $pdo->prepare("
    SELECT progress_data, timemodified
    FROM mdl_abessi_progress
    WHERE userid = ?
");
$stmt->execute([$userid]);
$progress = $stmt->fetch();
$progress_data = $progress ? json_decode($progress['progress_data'], true) : [];

// 6. 최근 요약
$stmt = $pdo->prepare("
    SELECT goals, timecreated, timemodified
    FROM mdl_abessi_today
    WHERE userid = ?
    ORDER BY timemodified DESC
    LIMIT 5
");
$stmt->execute([$userid]);
$recent_summaries = $stmt->fetchAll();

// 7. 챕터별 학습
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

// 8. 오답 분석
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
foreach ($mathtalk_logs as $log) {
    $content = json_decode($log['content'], true);
    if ($content && isset($content['error_type'])) {
        $type = $content['error_type'];
        if (!isset($error_types[$type])) {
            $error_types[$type] = 0;
        }
        $error_types[$type]++;
    }
}

// 9. 연속 학습 일수
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

// 10. 오늘의 통계
$today_start = strtotime('today');
$today_end = strtotime('tomorrow') - 1;

$stmt = $pdo->prepare("
    SELECT COUNT(*) as activity_count
    FROM mdl_abessi_missionlog
    WHERE userid = ? AND timecreated BETWEEN ? AND ?
");
$stmt->execute([$userid, $today_start, $today_end]);
$today_activities = $stmt->fetchColumn();
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
                
                <!-- 요약 작성 폼 -->
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">학습 요약 작성</h2>
                    <form id="summaryForm" class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">챕터</label>
                            <input type="text" id="chapter" name="chapter" required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                   placeholder="예: 미적분 - 함수의 극한">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">요약</label>
                            <textarea id="summary" name="summary" rows="4" required
                                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                      placeholder="오늘 배운 내용을 정리하세요..."></textarea>
                            <div class="mt-2 text-sm text-gray-500">
                                <span id="charCount">0</span> 자
                            </div>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">자신감 레벨</label>
                            <input type="range" id="confidence" name="confidence" min="0" max="100" value="50"
                                   class="w-full">
                            <div class="text-center mt-1">
                                <span id="confidenceValue" class="font-bold text-purple-600">50%</span>
                            </div>
                        </div>
                        <button type="submit" 
                                class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-lg hover:opacity-90">
                            <i class="fas fa-save mr-2"></i>요약 저장하기
                        </button>
                    </form>
                </div>

                <!-- 오답 분석 폼 -->
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">오답 분석</h2>
                    <form id="errorForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">문제 번호</label>
                                <input type="text" id="problem" name="problem" required
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                       placeholder="예: 3-15">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">오류 유형</label>
                                <select id="errorType" name="errorType" required
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500">
                                    <option value="">선택하세요</option>
                                    <option value="calculation">계산 실수</option>
                                    <option value="concept">개념 이해 부족</option>
                                    <option value="application">문제 적용 오류</option>
                                    <option value="careless">부주의</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">챕터 (선택)</label>
                            <input type="text" id="errorChapter" name="chapter"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                   placeholder="예: 미적분">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">오답 원인</label>
                            <textarea id="errorDescription" name="description" rows="2"
                                      class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                      placeholder="왜 틀렸는지 설명하세요..."></textarea>
                        </div>
                        <button type="submit" 
                                class="w-full py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-lg hover:opacity-90">
                            <i class="fas fa-search mr-2"></i>오답 분석 저장
                        </button>
                    </form>
                </div>

                <!-- 최근 활동 -->
                <?php if (count($recent_activities) > 0): ?>
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
                                        'chapter_study' => '챕터 학습'
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
                <?php endif; ?>

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
                                    <?php echo htmlspecialchars($goals['chapter'] ?? '제목 없음'); ?>
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
                                <span class="font-bold text-purple-600"><?php echo $percentage; ?>%</span>
                            </div>
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 학습 진행도 -->
                <?php if ($progress_data && isset($progress_data['average_quality'])): ?>
                <div class="glass p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text">학습 진행 상황</h3>
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-2">평균 품질 점수</p>
                        <div class="text-4xl font-bold text-purple-600">
                            <?php echo round($progress_data['average_quality']); ?>%
                        </div>
                    </div>
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
    </div>

    <script>
    // 문자 수 카운트
    document.getElementById('summary').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    // 자신감 레벨
    document.getElementById('confidence').addEventListener('input', function() {
        document.getElementById('confidenceValue').textContent = this.value + '%';
    });

    // 요약 폼 제출
    document.getElementById('summaryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const data = {
            chapter: document.getElementById('chapter').value,
            summary: document.getElementById('summary').value,
            confidence: document.getElementById('confidence').value
        };

        fetch('ajax/save_summary.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(result.message || '요약이 저장되었습니다!');
                this.reset();
                document.getElementById('charCount').textContent = '0';
                document.getElementById('confidenceValue').textContent = '50%';
                location.reload();
            } else {
                alert('오류: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('저장 중 오류가 발생했습니다.');
        });
    });

    // 오답 폼 제출
    document.getElementById('errorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const data = {
            problem: document.getElementById('problem').value,
            error_type: document.getElementById('errorType').value,
            chapter: document.getElementById('errorChapter').value,
            description: document.getElementById('errorDescription').value
        };

        fetch('ajax/save_error.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(result.message || '오답이 분석되었습니다!');
                if (result.data && result.data.tip) {
                    alert('💡 팁: ' + result.data.tip);
                }
                this.reset();
                location.reload();
            } else {
                alert('오류: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('저장 중 오류가 발생했습니다.');
        });
    });
    </script>
</body>
</html>