<?php
/**
 * ExamFocus 실사용 버전 - DB 연동
 * 하드코딩 없이 실제 데이터 사용
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 처리
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// 세션 시작
session_start();

// 사용자 ID 가져오기 (로그인 시스템과 연동)
$userid = isset($_GET['user_id']) ? intval($_GET['user_id']) : 
          (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

// 사용자 ID가 없으면 로그인 페이지로
if (!$userid) {
    // 로그인 페이지가 있다면 리다이렉트
    if (file_exists(__DIR__ . '/../../login_exam.php')) {
        header('Location: ../../login_exam.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    // 없으면 기본값 사용 (테스트용)
    $userid = 2;
    $is_guest = true;
} else {
    $is_guest = false;
}

// 데이터베이스 연결 설정
define('MATHKING_DB_HOST', '58.180.27.46');
define('MATHKING_DB_NAME', 'mathking');
define('MATHKING_DB_USER', 'moodle');
define('MATHKING_DB_PASS', '@MCtrigd7128');

// 실제 데이터 조회 함수
function get_real_exam_data($userid) {
    $result = [
        'has_exam' => false,
        'exam_date' => null,
        'exam_type' => null,
        'exam_scope' => null,
        'days_until' => null,
        'school' => null,
        'grade' => null,
        'user_name' => null,
        'study_stats' => null
    ];
    
    try {
        // 1. MathKing DB에서 사용자 정보 조회
        $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        // 사용자 정보 조회 (상세 정보 포함)
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.phone1, u.timecreated,
                   uid.data as role_data
            FROM mdl_user u
            LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
            WHERE u.id = :userid AND u.deleted = 0
        ");
        $stmt->execute(['userid' => $userid]);
        $user = $stmt->fetch();
        
        if ($user) {
            $result['user_name'] = trim($user['firstname'] . ' ' . $user['lastname']);
            if (empty($result['user_name'])) {
                $result['user_name'] = $user['username'];
            }
            
            // 사용자 상세 정보 추가
            $result['user_info'] = [
                'id' => $user['id'],
                'name' => $result['user_name'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
                'username' => $user['username'],
                'phone' => $user['phone1'],
                'role' => $user['role_data'] === 'student' ? '학생' : '교사',
                'member_since' => date('Y-m-d', $user['timecreated']),
            ];
        }
        
        // 2. Alt42t DB에서 시험 정보 조회
        try {
            // 여러 연결 방법 시도
            $alt_hosts = ['127.0.0.1', 'localhost', '58.180.27.46'];
            $alt_connected = false;
            
            foreach ($alt_hosts as $host) {
                try {
                    $alt_dsn = "mysql:host={$host};port=3306;dbname=alt42t;charset=utf8mb4";
                    $alt_pdo = new PDO($alt_dsn, 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 3
                    ]);
                    $alt_connected = true;
                    break;
                } catch (PDOException $e) {
                    continue;
                }
            }
            
            if ($alt_connected) {
                // student_exam_settings 테이블에서 시험 정보 조회
                $stmt = $alt_pdo->prepare("
                    SELECT 
                        name,
                        school,
                        grade,
                        semester,
                        exam_type,
                        exam_start_date,
                        exam_end_date,
                        math_exam_date,
                        exam_scope,
                        exam_status,
                        study_level,
                        created_at,
                        updated_at
                    FROM student_exam_settings
                    WHERE user_id = :userid
                    AND exam_status IN ('confirmed', 'expected')
                    AND math_exam_date >= CURDATE()
                    ORDER BY math_exam_date ASC
                    LIMIT 1
                ");
                $stmt->execute(['userid' => $userid]);
                $exam = $stmt->fetch();
                
                if ($exam && $exam['math_exam_date']) {
                    $result['has_exam'] = true;
                    $result['exam_date'] = $exam['math_exam_date'];
                    $result['exam_type'] = $exam['exam_type'] ?: '정기고사';
                    $result['exam_scope'] = $exam['exam_scope'];
                    $result['school'] = $exam['school'];
                    $result['grade'] = $exam['grade'];
                    $result['days_until'] = floor((strtotime($exam['math_exam_date']) - time()) / 86400);
                    
                    // 이름이 Alt42t에도 있으면 그것을 사용
                    if (!empty($exam['name'])) {
                        $result['user_name'] = $exam['name'];
                    }
                }
            }
        } catch (PDOException $e) {
            // Alt42t 연결 실패는 무시 (선택사항)
            error_log("Alt42t connection failed: " . $e->getMessage());
        }
        
        // 3. MathKing DB에서 학습 통계 조회
        try {
            // 최근 7일 학습 활동
            $week_ago = time() - (7 * 86400);
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as mission_count
                FROM mdl_abessi_missionlog
                WHERE userid = :userid
                AND timecreated > :weekago
            ");
            $stmt->execute(['userid' => $userid, 'weekago' => $week_ago]);
            $missions = $stmt->fetch();
            
            // 오늘 학습 시간
            $today_start = strtotime('today');
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as today_count
                FROM mdl_abessi_missionlog
                WHERE userid = :userid
                AND timecreated > :today
            ");
            $stmt->execute(['userid' => $userid, 'today' => $today_start]);
            $today = $stmt->fetch();
            
            // 월간 통계
            $month_ago = time() - (30 * 86400);
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as month_count
                FROM mdl_abessi_missionlog
                WHERE userid = :userid AND timecreated > :monthago
            ");
            $stmt->execute(['userid' => $userid, 'monthago' => $month_ago]);
            $month = $stmt->fetch();
            
            // 총 통계
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_count, MIN(timecreated) as first_activity, MAX(timecreated) as last_activity
                FROM mdl_abessi_missionlog
                WHERE userid = :userid
            ");
            $stmt->execute(['userid' => $userid]);
            $total = $stmt->fetch();
            
            // 연속 학습일 계산 (간단한 방법)
            $stmt = $pdo->prepare("
                SELECT DATE(FROM_UNIXTIME(timecreated)) as study_date, COUNT(*) as daily_count
                FROM mdl_abessi_missionlog
                WHERE userid = :userid AND timecreated > :weekago
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY study_date DESC
            ");
            $stmt->execute(['userid' => $userid, 'weekago' => $week_ago]);
            $daily_activity = $stmt->fetchAll();
            
            $result['study_stats'] = [
                'week_missions' => $missions['mission_count'] ?: 0,
                'today_missions' => $today['today_count'] ?: 0,
                'month_missions' => $month['month_count'] ?: 0,
                'total_missions' => $total['total_count'] ?: 0,
                'week_hours' => round(($missions['mission_count'] ?: 0) * 0.3, 1), // 미션당 18분 가정
                'today_hours' => round(($today['today_count'] ?: 0) * 0.3, 1),
                'month_hours' => round(($month['month_count'] ?: 0) * 0.3, 1),
                'total_hours' => round(($total['total_count'] ?: 0) * 0.3, 1),
                'active_days_week' => count($daily_activity),
                'first_activity' => $total['first_activity'] ? date('Y-m-d', $total['first_activity']) : null,
                'last_activity' => $total['last_activity'] ? date('Y-m-d H:i', $total['last_activity']) : null,
                'avg_daily_missions' => $daily_activity ? round(array_sum(array_column($daily_activity, 'daily_count')) / count($daily_activity), 1) : 0
            ];
            
        } catch (PDOException $e) {
            $result['study_stats'] = [
                'week_missions' => 0,
                'today_missions' => 0,
                'week_hours' => 0,
                'today_hours' => 0
            ];
        }
        
        // 4. 시험이 없으면 mdl_abessi_schedule에서 찾기 (폴백)
        if (!$result['has_exam']) {
            try {
                $stmt = $pdo->prepare("
                    SELECT schedule_data, pinned
                    FROM mdl_abessi_schedule
                    WHERE userid = :userid
                    AND pinned = 1
                    ORDER BY timemodified DESC
                    LIMIT 1
                ");
                $stmt->execute(['userid' => $userid]);
                $schedule = $stmt->fetch();
                
                if ($schedule && $schedule['schedule_data']) {
                    $data = json_decode($schedule['schedule_data'], true);
                    
                    // JSON 데이터에서 시험 정보 추출 (구조에 따라 조정 필요)
                    if (isset($data['exams']) && is_array($data['exams'])) {
                        foreach ($data['exams'] as $exam) {
                            if (isset($exam['date']) && strtotime($exam['date']) > time()) {
                                $result['has_exam'] = true;
                                $result['exam_date'] = $exam['date'];
                                $result['exam_type'] = $exam['type'] ?? '시험';
                                $result['days_until'] = floor((strtotime($exam['date']) - time()) / 86400);
                                break;
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                // 무시
            }
        }
        
    } catch (Exception $e) {
        error_log("ExamFocus Error: " . $e->getMessage());
    }
    
    return $result;
}

// 추천 모드 결정 함수
function get_recommendation($data) {
    if (!$data['has_exam'] || $data['days_until'] === null) {
        return null;
    }
    
    $days = $data['days_until'];
    
    // 설정값 (실제로는 settings 테이블에서 가져와야 함)
    $d7_threshold = 7;
    $d30_threshold = 30;
    
    if ($days <= 0) {
        return [
            'mode' => 'exam_day',
            'title' => '🎯 오늘이 시험날!',
            'message' => '침착하게 그동안 공부한 내용을 정리하고 시험에 임하세요.',
            'priority' => 'info',
            'actions' => [
                '마지막 개념 점검',
                '실수하기 쉬운 부분 확인',
                '시험 준비물 체크'
            ]
        ];
    } elseif ($days <= $d7_threshold) {
        return [
            'mode' => 'concept_summary',
            'title' => '🚨 긴급! D-' . $days . ' 개념요약 집중',
            'message' => '시험이 ' . $days . '일 앞으로 다가왔습니다. 개념요약과 대표유형에 집중하세요.',
            'priority' => 'danger',
            'actions' => [
                '핵심 개념 총정리',
                '대표 유형 문제 마스터',
                '오답노트 최종 점검',
                '실전 모의고사 풀이'
            ]
        ];
    } elseif ($days <= $d30_threshold) {
        return [
            'mode' => 'review_errors',
            'title' => '📚 D-' . $days . ' 오답 회독 모드',
            'message' => '시험 준비의 황금 시기입니다. 체계적인 오답 복습으로 실력을 다지세요.',
            'priority' => 'warning',
            'actions' => [
                '오답 문제 재풀이',
                '취약 단원 집중 학습',
                '심화 문제 도전',
                '개념 노트 작성'
            ]
        ];
    } else {
        return [
            'mode' => 'regular',
            'title' => '📖 꾸준한 학습 유지',
            'message' => '시험까지 ' . $days . '일 남았습니다. 현재 페이스를 유지하며 꾸준히 학습하세요.',
            'priority' => 'success',
            'actions' => [
                '일일 학습 목표 달성',
                '새로운 단원 예습',
                '기본 문제 풀이',
                '개념 이해도 점검'
            ]
        ];
    }
}

// 실제 데이터 조회
$exam_data = get_real_exam_data($userid);
$recommendation = get_recommendation($exam_data);

// 디버그 모드
$debug = isset($_GET['debug']) ? true : false;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamFocus - <?php echo $exam_data['user_name'] ?: '학습 모드 추천'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .main-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .examfocus-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        .user-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .recommendation-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        .recommendation-box.danger {
            background: linear-gradient(135deg, #f5576c 0%, #fda085 100%);
        }
        .recommendation-box.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .recommendation-box.success {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        .recommendation-box.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        .mode-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .mode-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }
        .mode-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        .mode-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 12px;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
        }
        .exam-info-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        .no-exam-container {
            text-align: center;
            padding: 60px 20px;
        }
        .action-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .action-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .debug-info {
            background: #2d3436;
            color: #dfe6e9;
            padding: 20px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- 헤더 -->
        <div class="text-center text-white mb-4">
            <h1 class="display-4">📚 ExamFocus</h1>
            <p class="lead">맞춤형 시험 대비 학습 모드 추천 시스템</p>
        </div>
        
        <!-- 메인 카드 -->
        <div class="examfocus-card">
            <!-- 사용자 정보 -->
            <div class="user-info">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-2">
                            <i class="fas fa-user-circle text-primary"></i> 
                            <?php echo htmlspecialchars($exam_data['user_name'] ?: '학생'); ?>님
                        </h5>
                        <?php if (isset($exam_data['user_info'])): ?>
                            <p class="mb-1">
                                <span class="badge bg-<?php echo $exam_data['user_info']['role'] === '학생' ? 'primary' : 'success'; ?>">
                                    <?php echo $exam_data['user_info']['role']; ?>
                                </span>
                                <small class="text-muted ms-2">가입일: <?php echo $exam_data['user_info']['member_since']; ?></small>
                            </p>
                            <?php if ($exam_data['user_info']['email']): ?>
                            <small class="text-muted">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($exam_data['user_info']['email']); ?>
                            </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($exam_data['school']): ?>
                                <p class="mb-0 text-muted">
                                    🏫 <?php echo htmlspecialchars($exam_data['school']); ?>
                                    <?php if ($exam_data['grade']): ?>
                                        | <?php echo htmlspecialchars($exam_data['grade']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($is_guest): ?>
                                <small class="text-warning">⚠️ 게스트 모드 - <a href="../../login_exam.php">로그인</a></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if (isset($exam_data['study_stats'])): ?>
                        <div class="row">
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $exam_data['study_stats']['today_hours'] ?: '0'; ?>h</div>
                                    <div class="stat-label">오늘 학습</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $exam_data['study_stats']['week_hours'] ?: '0'; ?>h</div>
                                    <div class="stat-label">주간 학습</div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-value" style="font-size:1.5em"><?php echo $exam_data['study_stats']['total_missions'] ?: '0'; ?></div>
                                    <div class="stat-label">총 활동</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-value" style="font-size:1.5em"><?php echo $exam_data['study_stats']['active_days_week'] ?: '0'; ?>일</div>
                                    <div class="stat-label">주간 활동일</div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <p>학습 데이터를 불러오는 중...</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($exam_data['has_exam'] && $recommendation): ?>
                <!-- 시험 정보 -->
                <div class="exam-info-card">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>📅 시험 종류</strong><br>
                            <?php echo htmlspecialchars($exam_data['exam_type']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>📆 시험일</strong><br>
                            <?php echo $exam_data['exam_date']; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>⏰ 남은 기간</strong><br>
                            <span class="text-danger fw-bold">D-<?php echo $exam_data['days_until']; ?></span>
                        </div>
                    </div>
                    <?php if ($exam_data['exam_scope']): ?>
                        <hr>
                        <strong>📖 시험 범위</strong><br>
                        <?php echo nl2br(htmlspecialchars($exam_data['exam_scope'])); ?>
                    <?php endif; ?>
                </div>
                
                <!-- 추천 박스 -->
                <div class="recommendation-box <?php echo $recommendation['priority']; ?> pulse">
                    <h3 class="mb-3"><?php echo $recommendation['title']; ?></h3>
                    <p class="mb-4"><?php echo $recommendation['message']; ?></p>
                    
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <strong>시험까지</strong><br>
                                <span class="h3">D-<?php echo $exam_data['days_until']; ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <strong>추천 모드</strong><br>
                                <span class="h5"><?php echo $recommendation['mode']; ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <strong>우선순위</strong><br>
                                <span class="h5">
                                    <?php
                                    switch($recommendation['priority']) {
                                        case 'danger': echo '긴급'; break;
                                        case 'warning': echo '중요'; break;
                                        case 'success': echo '일반'; break;
                                        default: echo '확인'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 추천 학습 활동 -->
                <h5 class="mt-4 mb-3">✅ 오늘의 추천 학습 활동</h5>
                <?php foreach ($recommendation['actions'] as $action): ?>
                <div class="action-item">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="action_<?php echo md5($action); ?>">
                        <label class="form-check-label" for="action_<?php echo md5($action); ?>">
                            <?php echo $action; ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- 액션 버튼 -->
                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-success btn-lg" onclick="startStudyMode('<?php echo $recommendation['mode']; ?>')">
                        ✨ <?php echo $recommendation['mode']; ?> 모드로 학습 시작
                    </button>
                    <button class="btn btn-outline-secondary" onclick="refreshData()">
                        🔄 데이터 새로고침
                    </button>
                </div>
                
            <?php else: ?>
                <!-- 시험 없음 -->
                <div class="no-exam-container">
                    <h2 class="mb-4">📖 등록된 시험이 없습니다</h2>
                    <p class="text-muted mb-4">
                        시험 일정을 등록하면 맞춤형 학습 모드를 추천해 드립니다.<br>
                        D-30부터 D-Day까지 최적의 학습 전략을 제공합니다.
                    </p>
                    <button class="btn btn-primary btn-lg" onclick="registerExam()">
                        📅 시험 일정 등록하기
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($debug): ?>
            <!-- 디버그 정보 -->
            <div class="mt-4">
                <h5>🔧 디버그 정보</h5>
                <div class="debug-info">
                    <pre><?php print_r($exam_data); ?></pre>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 학습 모드 선택 카드 -->
        <div class="examfocus-card">
            <h4 class="mb-4"><i class="fas fa-graduation-cap"></i> 학습 모드 선택</h4>
            <p class="text-muted mb-4">원하는 학습 모드를 선택하여 바로 시작하세요</p>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="mode-card" onclick="startModeWithConfirm('concept_summary')">
                        <div class="mode-icon">🚨</div>
                        <h5>개념요약 모드</h5>
                        <p class="text-muted mb-2">D-7 긴급 개념 정리</p>
                        <small>핵심 개념과 대표 유형에 집중</small>
                        <div class="mode-badge bg-danger">긴급</div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="mode-card" onclick="startModeWithConfirm('review_errors')">
                        <div class="mode-icon">🔄</div>
                        <h5>오답 회독 모드</h5>
                        <p class="text-muted mb-2">D-30 체계적 오답 복습</p>
                        <small>틀린 문제와 취약점 보완</small>
                        <div class="mode-badge bg-warning">중요</div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="mode-card" onclick="startModeWithConfirm('practice')">
                        <div class="mode-icon">⚡</div>
                        <h5>실전 연습 모드</h5>
                        <p class="text-muted mb-2">모의고사 및 시간 연습</p>
                        <small>실제 시험 환경 시뮬레이션</small>
                        <div class="mode-badge bg-info">연습</div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="mode-card" onclick="startModeWithConfirm('study')">
                        <div class="mode-icon">📚</div>
                        <h5>일반 학습 모드</h5>
                        <p class="text-muted mb-2">규칙적인 학습 습관</p>
                        <small>꾸준한 진도 학습과 복습</small>
                        <div class="mode-badge bg-success">일반</div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="mode-card" onclick="startModeWithConfirm('exam_day')">
                        <div class="mode-icon">⭐</div>
                        <h5>시험 당일 모드</h5>
                        <p class="text-muted mb-2">D-Day 최종 점검</p>
                        <small>마지막 점검과 멘탈 관리</small>
                        <div class="mode-badge bg-warning">당일</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 푸터 -->
        <div class="text-center text-white">
            <p class="mb-2">
                User ID: <?php echo $userid; ?> | 
                <a href="?user_id=<?php echo $userid; ?>&debug=1" class="text-white">디버그 모드</a> | 
                <a href="ajax/get_recommendation.php?user_id=<?php echo $userid; ?>" class="text-white" target="_blank">API</a>
            </p>
            <small>
                <a href="index_safe.php" class="text-white-50">안전 모드</a> | 
                <a href="error_check.php" class="text-white-50">시스템 진단</a>
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 학습 모드 시작
        function startStudyMode(mode) {
            console.log('Starting study mode:', mode);
            
            // 로딩 상태 표시
            const button = event.target;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 시작 중...';
            
            // 서버에 모드 시작 요청
            fetch('actions/start_mode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    user_id: <?php echo $userid; ?>,
                    mode: mode
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 성공 메시지 표시
                    showNotification('success', data.message);
                    
                    // 세션 저장
                    sessionStorage.setItem('examfocus_mode', mode);
                    sessionStorage.setItem('examfocus_started', Date.now());
                    
                    // 완료된 액션 표시
                    if (data.actions_completed && data.actions_completed.length > 0) {
                        showActionProgress(data.actions_completed);
                    }
                    
                    // 리다이렉트 URL이 있으면 이동
                    if (data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    }
                } else {
                    showNotification('error', data.message || '모드 시작 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', '서버와의 통신 중 오류가 발생했습니다.');
            })
            .finally(() => {
                // 버튼 복원
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
        
        // 시험 등록
        function registerExam() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 처리 중...';
            
            // 간단한 시험 정보 입력 모달 표시
            const examData = promptExamData();
            
            if (examData) {
                // 서버에 시험 등록 요청
                fetch('actions/register_exam.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: <?php echo $userid; ?>,
                        exam_data: examData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message);
                        
                        // 페이지 새로고침하여 업데이트된 정보 표시
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification('error', data.message || '시험 등록 중 오류가 발생했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', '서버와의 통신 중 오류가 발생했습니다.');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            } else {
                // 데이터 없이 시험 시스템 페이지로 이동
                fetch('actions/register_exam.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: <?php echo $userid; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        // 기본 시험 시스템 페이지로 이동
                        window.location.href = '../../exam_system.php?user_id=<?php echo $userid; ?>';
                    }
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            }
        }
        
        // 데이터 새로고침
        function refreshData() {
            location.reload();
        }
        
        // 체크박스 상태 저장
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            // 로컬 스토리지에서 상태 복원
            const id = checkbox.id;
            if (localStorage.getItem(id) === 'checked') {
                checkbox.checked = true;
            }
            
            // 변경 시 저장
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    localStorage.setItem(id, 'checked');
                } else {
                    localStorage.removeItem(id);
                }
            });
        });
        
        // 시험 정보 입력 프롬프트
        function promptExamData() {
            // 간단한 프롬프트로 시험 정보 수집
            const examType = prompt('시험 종류를 입력하세요 (예: 1학기 중간고사):', '1학기 중간고사');
            if (!examType) return null;
            
            const mathExamDate = prompt('수학 시험 날짜를 입력하세요 (YYYY-MM-DD):', '');
            if (!mathExamDate) return null;
            
            // 날짜 형식 검증
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(mathExamDate)) {
                alert('날짜 형식이 올바르지 않습니다. YYYY-MM-DD 형식으로 입력해주세요.');
                return null;
            }
            
            const examScope = prompt('시험 범위를 입력하세요 (선택사항):', '') || '';
            const school = prompt('학교명을 입력하세요 (선택사항):', '') || '';
            const grade = prompt('학년을 입력하세요 (예: 고3, 중2):', '') || '';
            
            return {
                exam_type: examType,
                math_exam_date: mathExamDate,
                exam_start_date: mathExamDate, // 단순화를 위해 같은 날짜 사용
                exam_end_date: mathExamDate,
                exam_scope: examScope,
                school: school,
                grade: grade,
                semester: '1학기' // 기본값
            };
        }
        
        // 알림 표시 함수
        function showNotification(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            
            const alertHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show mt-3" role="alert">
                    <i class="${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // 기존 알림 제거
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // 새 알림 추가
            const container = document.querySelector('.container');
            container.insertAdjacentHTML('afterbegin', alertHTML);
            
            // 3초 후 자동 제거
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 3000);
        }
        
        // 액션 진행상황 표시 함수
        function showActionProgress(actions) {
            let progressHTML = '<div class="mt-3"><h6>완료된 작업:</h6><ul class="list-group list-group-flush">';
            actions.forEach(action => {
                progressHTML += `<li class="list-group-item"><i class="fas fa-check text-success"></i> ${action}</li>`;
            });
            progressHTML += '</ul></div>';
            
            const container = document.querySelector('.container');
            container.insertAdjacentHTML('beforeend', progressHTML);
            
            // 5초 후 제거
            setTimeout(() => {
                const progress = document.querySelector('.mt-3:last-child');
                if (progress) progress.remove();
            }, 5000);
        }
        
        // 학습 모드 확인 후 시작
        function startModeWithConfirm(mode) {
            const modeNames = {
                'concept_summary': '개념요약 모드 (D-7 긴급 개념 정리)',
                'review_errors': '오답회독 모드 (D-30 체계적 오답 복습)',
                'practice': '실전연습 모드 (모의고사 및 실전 연습)',
                'exam_day': '시험당일 모드 (시험 당일 최종 점검)',
                'study': '일반학습 모드 (평상시 학습)'
            };
            
            const modeName = modeNames[mode] || mode;
            const confirmMessage = `${modeName}를 시작하시겠습니까?`;
            
            if (confirm(confirmMessage)) {
                // 선택된 모드 표시
                showNotification('success', `${modeName} 시작 중...`);
                
                // startStudyMode 함수 호출 (기존 구현 사용)
                startStudyMode(mode);
            }
        }
        
        // 자동 새로고침 (5분마다)
        setTimeout(function() {
            console.log('Auto-refreshing data...');
            refreshData();
        }, 5 * 60 * 1000);
    </script>
</body>
</html>