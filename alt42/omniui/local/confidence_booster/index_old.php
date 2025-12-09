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

// 3. Alt42t 시험 정보 조회 - student_exam_settings 테이블 사용
try {
    $stmt = $pdo->prepare("
        SELECT ses.*, es.exam_scope
        FROM student_exam_settings ses
        LEFT JOIN exam_settings es ON ses.school = es.school 
            AND ses.exam_type = es.exam_type
        WHERE ses.user_id = ?
        ORDER BY ses.updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userid]);
    $exam_info = $stmt->fetch();
} catch (PDOException $e) {
    // 테이블이 없거나 오류 발생 시 null 처리
    $exam_info = null;
}

// D-Day 계산
$dday = null;
$exam_phase = 'prepare';
if ($exam_info && isset($exam_info['math_exam_date']) && $exam_info['math_exam_date']) {
    $exam_date = new DateTime($exam_info['math_exam_date']);
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

// 4. 최근 학습 활동 - 실제 테이블 확인 필요
// mdl_logstore_standard_log 테이블 사용 (실제 Moodle 로그)
try {
    $stmt = $pdo->prepare("
        SELECT eventname, COUNT(*) as count, MAX(timecreated) as last_time
        FROM mdl_logstore_standard_log
        WHERE userid = ? AND timecreated > ?
        GROUP BY eventname
        ORDER BY count DESC
        LIMIT 5
    ");
    $seven_days_ago = time() - (7 * 24 * 60 * 60);
    $stmt->execute([$userid, $seven_days_ago]);
    $recent_activities = $stmt->fetchAll();
    
    // 이벤트 이름을 한글로 변환
    foreach ($recent_activities as &$activity) {
        // 이벤트 이름에서 마지막 부분 추출
        $event = substr($activity['eventname'], strrpos($activity['eventname'], '\\') + 1);
        
        // 이벤트 이름을 사용자 친화적인 한글로 매핑
        $event_labels = [
            'course_viewed' => '코스 접속',
            'course_module_viewed' => '학습 자료 열람',
            'course_module_created' => '학습 활동 생성',
            'edit_page_viewed' => '편집 페이지 열람',
            'user_loggedin' => '로그인',
            'user_loggedout' => '로그아웃',
            'quiz_attempt_started' => '퀴즈 시작',
            'quiz_attempt_submitted' => '퀴즈 제출',
            'quiz_attempt_viewed' => '퀴즈 결과 확인',
            'assignment_submitted' => '과제 제출',
            'assignment_viewed' => '과제 확인',
            'forum_post_created' => '포럼 글 작성',
            'forum_discussion_viewed' => '포럼 토론 열람',
            'resource_viewed' => '자료 열람',
            'page_viewed' => '페이지 열람',
            'url_viewed' => '링크 방문',
            'book_viewed' => '책 열람',
            'lesson_started' => '레슨 시작',
            'lesson_ended' => '레슨 완료',
            'workshop_submission_created' => '워크샵 제출',
            'grade_viewed' => '성적 확인',
            'user_profile_viewed' => '프로필 조회',
            'message_sent' => '메시지 전송',
            'badge_earned' => '배지 획득',
            'competency_viewed' => '역량 확인',
            'h5pactivity_viewed' => 'H5P 활동 참여',
            'scorm_launched' => 'SCORM 학습 시작',
            'wiki_page_viewed' => '위키 페이지 열람',
            'glossary_entry_viewed' => '용어집 조회',
            'calendar_event_created' => '일정 추가',
            'dashboard_viewed' => '대시보드 접속'
        ];
        
        // 매핑된 한글 이름이 있으면 사용, 없으면 원본 이벤트 이름 정리
        if (isset($event_labels[$event])) {
            $activity['page'] = $event_labels[$event];
        } else {
            // 언더스코어를 공백으로, viewed/created 등을 한글로
            $activity['page'] = str_replace('_', ' ', $event);
            $activity['page'] = str_replace('viewed', '열람', $activity['page']);
            $activity['page'] = str_replace('created', '생성', $activity['page']);
            $activity['page'] = str_replace('submitted', '제출', $activity['page']);
            $activity['page'] = str_replace('updated', '수정', $activity['page']);
            $activity['page'] = str_replace('deleted', '삭제', $activity['page']);
        }
    }
} catch (PDOException $e) {
    // 테이블이 없으면 기본값
    $recent_activities = [];
}

// 5. 최근 활동 통계 - 세션 데이터로 대체
$attendance_stats = [];

// 대신 세션 활동 확인
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timecreated))) as active_days
        FROM mdl_logstore_standard_log
        WHERE userid = ? AND timecreated > ?
    ");
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    $stmt->execute([$userid, $thirty_days_ago]);
    $active_days = $stmt->fetchColumn();
} catch (PDOException $e) {
    $active_days = 0;
}

// 6. 학습 진행 상황 - 사용자 정보 확장 필드에서 가져오기
try {
    $stmt = $pdo->prepare("
        SELECT data
        FROM mdl_user_info_data
        WHERE userid = ? AND fieldid IN (SELECT id FROM mdl_user_info_field WHERE shortname = 'attendance_stats')
    ");
    $stmt->execute([$userid]);
    $result = $stmt->fetch();
    $progress_data = $result ? json_decode($result['data'], true) : [];
} catch (PDOException $e) {
    $progress_data = [];
}

// 기본값 설정
if (empty($progress_data)) {
    $progress_data = [
        'average_quality' => 0,
        'summaries' => []
    ];
}

// 7. 최근 코스 참여 - Moodle 코스 수강 정보
try {
    $stmt = $pdo->prepare("
        SELECT c.fullname, c.shortname, ue.timemodified
        FROM mdl_user_enrolments ue
        JOIN mdl_enrol e ON e.id = ue.enrolid
        JOIN mdl_course c ON c.id = e.courseid
        WHERE ue.userid = ?
        ORDER BY ue.timemodified DESC
        LIMIT 5
    ");
    $stmt->execute([$userid]);
    $recent_summaries = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_summaries = [];
}

// 8. 코스별 학습 현황 - 실제 코스 활동 로그
try {
    $stmt = $pdo->prepare("
        SELECT c.fullname as chapter, COUNT(*) as study_count, MAX(l.timecreated) as last_study
        FROM mdl_logstore_standard_log l
        JOIN mdl_course c ON c.id = l.courseid
        WHERE l.userid = ? AND l.courseid > 1
        GROUP BY l.courseid, c.fullname
        ORDER BY last_study DESC
        LIMIT 10
    ");
    $stmt->execute([$userid]);
    $chapter_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $chapter_logs = [];
}

// 9. 퀴즈 및 평가 결과 - Moodle 퀴즈 기록
$error_types = [];
try {
    $stmt = $pdo->prepare("
        SELECT q.name, qa.sumgrades, qa.timemodified
        FROM mdl_quiz_attempts qa
        JOIN mdl_quiz q ON q.id = qa.quiz
        WHERE qa.userid = ?
        ORDER BY qa.timemodified DESC
        LIMIT 10
    ");
    $stmt->execute([$userid]);
    $quiz_results = $stmt->fetchAll();
    
    // 퀴즈 결과를 바탕으로 오답 패턴 분석
    foreach ($quiz_results as $quiz) {
        if ($quiz['sumgrades'] < 70) {
            $error_types['concept'] = ($error_types['concept'] ?? 0) + 1;
        } elseif ($quiz['sumgrades'] < 85) {
            $error_types['application'] = ($error_types['application'] ?? 0) + 1;
        } else {
            $error_types['calculation'] = ($error_types['calculation'] ?? 0) + 1;
        }
    }
} catch (PDOException $e) {
    $quiz_results = [];
}

if (empty($error_types)) {
    $error_types = ['calculation' => 0, 'concept' => 0, 'application' => 0, 'careless' => 0];
}
$summaries_from_mathtalk = [];

// 10. 연속 학습 일수 - 실제 로그 기반 계산 (최적화)
$streak = 0;
try {
    // 최근 30일간의 활동 일자 가져오기
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE(FROM_UNIXTIME(timecreated)) as activity_date
        FROM mdl_logstore_standard_log
        WHERE userid = ? AND timecreated > ?
        ORDER BY activity_date DESC
    ");
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    $stmt->execute([$userid, $thirty_days_ago]);
    $activity_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 연속 일수 계산
    if (!empty($activity_dates)) {
        $last_date = new DateTime($activity_dates[0]);
        $today = new DateTime();
        
        // 오늘 활동이 있으면 streak 시작
        if ($last_date->format('Y-m-d') == $today->format('Y-m-d')) {
            $streak = 1;
            for ($i = 1; $i < count($activity_dates); $i++) {
                $current = new DateTime($activity_dates[$i]);
                $expected = clone $last_date;
                $expected->modify('-1 day');
                
                if ($current->format('Y-m-d') == $expected->format('Y-m-d')) {
                    $streak++;
                    $last_date = $current;
                } else {
                    break;
                }
            }
        }
    }
} catch (PDOException $e) {
    $streak = 0;
}

// 11. 오늘의 통계 - 실제 로그 기반
$today_start = strtotime('today');
$today_end = strtotime('tomorrow') - 1;

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as activity_count
        FROM mdl_logstore_standard_log
        WHERE userid = ? AND timecreated BETWEEN ? AND ?
    ");
    $stmt->execute([$userid, $today_start, $today_end]);
    $today_activities = $stmt->fetchColumn();
} catch (PDOException $e) {
    $today_activities = 0;
}

// 12. 시험 자료 - 일단 빈 배열로 설정 (테이블 구조 확인 필요)
$exam_resources = [];
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
                        <?php echo isset($exam_info['math_exam_date']) && $exam_info['math_exam_date'] ? date('Y년 m월 d일', strtotime($exam_info['math_exam_date'])) : '미정'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">시험 범위</p>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($exam_info['exam_scope'] ?? '전체'); ?></p>
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
                                    <?php echo htmlspecialchars($activity['page']); ?>
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

                <!-- 최근 코스 -->
                <?php if (count($recent_summaries) > 0): ?>
                <div class="glass p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">수강 중인 코스</h2>
                    <div class="space-y-3">
                        <?php foreach($recent_summaries as $course): ?>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="flex justify-between mb-2">
                                <h3 class="font-bold text-gray-800">
                                    <?php echo htmlspecialchars($course['fullname'] ?? '코스 이름 없음'); ?>
                                </h3>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('m/d', $course['timemodified']); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm">
                                <?php echo htmlspecialchars($course['shortname'] ?? ''); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
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
                <button onclick="location.href='save_summary.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-pen mr-2"></i>요약 작성하기
                </button>
                <button onclick="location.href='save_error.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-search mr-2"></i>오답 분석하기
                </button>
                <button onclick="location.href='../../learning_tracker.php'" 
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