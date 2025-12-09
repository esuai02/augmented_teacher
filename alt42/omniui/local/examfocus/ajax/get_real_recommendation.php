<?php
/**
 * ExamFocus 실제 DB 기반 추천 API
 * 하드코딩 없이 실제 데이터 기반 JSON 응답
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// CORS 및 JSON 헤더
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 에러 처리
error_reporting(0);
ini_set('display_errors', 0);

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 사용자 ID 가져오기
$userid = isset($_GET['user_id']) ? intval($_GET['user_id']) : 
          (isset($_POST['user_id']) ? intval($_POST['user_id']) :
          (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null));

// 응답 초기화
$response = [
    'success' => false,
    'has_recommendation' => false,
    'user_id' => $userid,
    'timestamp' => date('Y-m-d H:i:s'),
    'error' => null
];

if (!$userid) {
    $response['error'] = 'User ID is required';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 데이터베이스 연결
    define('MATHKING_DB_HOST', '58.180.27.46');
    define('MATHKING_DB_NAME', 'mathking');
    define('MATHKING_DB_USER', 'moodle');
    define('MATHKING_DB_PASS', '@MCtrigd7128');
    
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    // 1. 사용자 정보 조회
    $stmt = $pdo->prepare("
        SELECT id, username, firstname, lastname, email 
        FROM mdl_user 
        WHERE id = :userid AND deleted = 0
    ");
    $stmt->execute(['userid' => $userid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $response['error'] = 'User not found';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $response['user_info'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => trim($user['firstname'] . ' ' . $user['lastname']),
        'email' => $user['email']
    ];
    
    // 2. Alt42t DB에서 시험 정보 조회
    $exam_data = null;
    try {
        // 여러 연결 방법 시도
        $alt_hosts = ['127.0.0.1', '58.180.27.46', 'localhost'];
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
            // 가장 가까운 시험 조회
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
                $exam_timestamp = strtotime($exam['math_exam_date']);
                $days_until = floor(($exam_timestamp - time()) / 86400);
                
                $exam_data = [
                    'exam_date' => $exam['math_exam_date'],
                    'exam_type' => $exam['exam_type'] ?: '정기고사',
                    'exam_scope' => $exam['exam_scope'],
                    'school' => $exam['school'],
                    'grade' => $exam['grade'],
                    'semester' => $exam['semester'],
                    'study_level' => $exam['study_level'],
                    'days_until' => $days_until,
                    'exam_status' => $exam['exam_status'],
                    'source' => 'alt42t'
                ];
            }
        }
    } catch (PDOException $e) {
        // Alt42t 연결 실패는 로그만 남기고 계속
        error_log("Alt42t connection failed: " . $e->getMessage());
    }
    
    // 3. Alt42t에서 찾지 못하면 MathKing DB에서 시도 (폴백)
    if (!$exam_data) {
        try {
            $stmt = $pdo->prepare("
                SELECT schedule_data, pinned, timecreated, timemodified
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
                
                // JSON 구조에서 시험 정보 찾기
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        if (is_array($value) && isset($value['date'])) {
                            $exam_timestamp = strtotime($value['date']);
                            if ($exam_timestamp > time()) {
                                $days_until = floor(($exam_timestamp - time()) / 86400);
                                $exam_data = [
                                    'exam_date' => $value['date'],
                                    'exam_type' => $value['type'] ?? '시험',
                                    'exam_scope' => $value['scope'] ?? null,
                                    'school' => null,
                                    'grade' => null,
                                    'semester' => null,
                                    'study_level' => null,
                                    'days_until' => $days_until,
                                    'exam_status' => 'scheduled',
                                    'source' => 'mathking_schedule'
                                ];
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // 무시
        }
    }
    
    // 4. 학습 통계 조회
    $study_stats = [];
    try {
        // 주간 활동
        $week_ago = time() - (7 * 86400);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as mission_count, MAX(timecreated) as last_activity
            FROM mdl_abessi_missionlog
            WHERE userid = :userid
            AND timecreated > :weekago
        ");
        $stmt->execute(['userid' => $userid, 'weekago' => $week_ago]);
        $week_data = $stmt->fetch();
        
        // 오늘 활동
        $today_start = strtotime('today');
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as today_count
            FROM mdl_abessi_missionlog
            WHERE userid = :userid
            AND timecreated > :today
        ");
        $stmt->execute(['userid' => $userid, 'today' => $today_start]);
        $today_data = $stmt->fetch();
        
        // 총 활동
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_count, MIN(timecreated) as first_activity
            FROM mdl_abessi_missionlog
            WHERE userid = :userid
        ");
        $stmt->execute(['userid' => $userid]);
        $total_data = $stmt->fetch();
        
        $study_stats = [
            'week_missions' => intval($week_data['mission_count']),
            'today_missions' => intval($today_data['today_count']),
            'total_missions' => intval($total_data['total_count']),
            'week_hours' => round($week_data['mission_count'] * 0.5, 1),
            'today_hours' => round($today_data['today_count'] * 0.5, 1),
            'last_activity' => $week_data['last_activity'] ? date('Y-m-d H:i:s', $week_data['last_activity']) : null,
            'first_activity' => $total_data['first_activity'] ? date('Y-m-d H:i:s', $total_data['first_activity']) : null
        ];
    } catch (Exception $e) {
        $study_stats = [
            'week_missions' => 0,
            'today_missions' => 0,
            'total_missions' => 0,
            'week_hours' => 0,
            'today_hours' => 0,
            'last_activity' => null,
            'first_activity' => null
        ];
    }
    
    $response['study_stats'] = $study_stats;
    
    // 5. 추천 로직 실행
    if ($exam_data && $exam_data['days_until'] !== null) {
        $days = $exam_data['days_until'];
        
        // 추천 생성
        $recommendation = null;
        
        if ($days <= 0) {
            $recommendation = [
                'mode' => 'exam_day',
                'title' => '🎯 오늘이 시험날입니다!',
                'message' => '침착하게 그동안 공부한 내용을 정리하고 좋은 결과 있기를 바랍니다.',
                'priority' => 'info',
                'urgency' => 'immediate',
                'actions' => [
                    '마지막 개념 점검',
                    '실수하기 쉬운 부분 확인', 
                    '시험 준비물 체크',
                    '멘탈 관리'
                ]
            ];
        } elseif ($days <= 7) {
            $recommendation = [
                'mode' => 'concept_summary',
                'title' => '🚨 긴급! D-' . $days . ' 개념요약 집중',
                'message' => '시험이 ' . $days . '일 앞으로 다가왔습니다. 개념요약과 대표유형에 집중하세요.',
                'priority' => 'high',
                'urgency' => 'urgent',
                'actions' => [
                    '핵심 개념 총정리',
                    '대표 유형 문제 마스터',
                    '오답노트 최종 점검',
                    '실전 모의고사 풀이'
                ]
            ];
        } elseif ($days <= 30) {
            $recommendation = [
                'mode' => 'review_errors',
                'title' => '📚 D-' . $days . ' 오답 회독 모드',
                'message' => '시험 준비의 황금 시기입니다. 체계적인 오답 복습으로 실력을 다지세요.',
                'priority' => 'medium',
                'urgency' => 'normal',
                'actions' => [
                    '오답 문제 재풀이',
                    '취약 단원 집중 학습',
                    '심화 문제 도전',
                    '개념 노트 작성'
                ]
            ];
        } else {
            $recommendation = [
                'mode' => 'regular',
                'title' => '📖 꾸준한 학습 유지',
                'message' => '시험까지 ' . $days . '일 남았습니다. 현재 페이스를 유지하며 꾸준히 학습하세요.',
                'priority' => 'low',
                'urgency' => 'normal',
                'actions' => [
                    '일일 학습 목표 달성',
                    '새로운 단원 예습',
                    '기본 문제 풀이',
                    '개념 이해도 점검'
                ]
            ];
        }
        
        $response['success'] = true;
        $response['has_recommendation'] = true;
        $response['exam_info'] = $exam_data;
        $response['recommendation'] = $recommendation;
        
    } else {
        $response['success'] = true;
        $response['has_recommendation'] = false;
        $response['message'] = '등록된 시험 일정이 없습니다.';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
    error_log("ExamFocus API Error: " . $e->getMessage());
}

// JSON 응답
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;