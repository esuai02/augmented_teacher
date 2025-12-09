<?php
/**
 * AJAX 엔드포인트 - 추천 정보 조회
 * 500 오류 방지 버전
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 출력 방지
error_reporting(0);
ini_set('display_errors', 0);

// CORS 헤더 (필요한 경우)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 응답 초기화
$response = [
    'success' => false,
    'has_recommendation' => false,
    'error' => null
];

try {
    // 사용자 ID 가져오기
    $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 
               (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2);
    
    // DB 연결
    if (!defined('MATHKING_DB_HOST')) {
        define('MATHKING_DB_HOST', '58.180.27.46');
        define('MATHKING_DB_NAME', 'mathking');
        define('MATHKING_DB_USER', 'moodle');
        define('MATHKING_DB_PASS', '@MCtrigd7128');
    }
    
    // 시험 날짜 조회
    $exam_date = null;
    $days_until = null;
    
    // Alt42t DB 시도
    try {
        $alt42t = new PDO("mysql:host=127.0.0.1;dbname=alt42t;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_TIMEOUT => 2
        ]);
        
        $stmt = $alt42t->prepare("
            SELECT math_exam_date, exam_type
            FROM student_exam_settings 
            WHERE user_id = :userid 
            AND exam_status = 'confirmed' 
            AND math_exam_date >= CURDATE() 
            ORDER BY math_exam_date ASC 
            LIMIT 1
        ");
        $stmt->execute(['userid' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['math_exam_date']) {
            $exam_date = $result['math_exam_date'];
            $days_until = floor((strtotime($exam_date) - time()) / 86400);
        }
    } catch (Exception $e) {
        // Alt42t 실패 무시
    }
    
    // MathKing DB에서도 시도 (fallback)
    if (!$exam_date) {
        try {
            $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            ]);
            
            // mdl_abessi_schedule 조회 (JSON 데이터 파싱 필요)
            $stmt = $pdo->prepare("
                SELECT schedule_data 
                FROM mdl_abessi_schedule 
                WHERE userid = :userid 
                AND pinned = 1 
                ORDER BY timemodified DESC 
                LIMIT 1
            ");
            $stmt->execute(['userid' => $user_id]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($schedule && $schedule['schedule_data']) {
                $data = json_decode($schedule['schedule_data'], true);
                // schedule_data 구조에 따라 조정 필요
                if (isset($data['exam_date'])) {
                    $exam_date = $data['exam_date'];
                    $days_until = floor((strtotime($exam_date) - time()) / 86400);
                }
            }
        } catch (Exception $e) {
            // MathKing DB 실패도 무시
        }
    }
    
    // 추천 생성
    if ($days_until !== null && $days_until > 0) {
        $response['success'] = true;
        $response['has_recommendation'] = true;
        $response['days_until'] = $days_until;
        $response['exam_date'] = $exam_date;
        
        if ($days_until <= 7) {
            $response['mode'] = 'concept_summary';
            $response['message'] = '시험 D-' . $days_until . '! 개념요약과 대표유형에 집중하세요.';
            $response['priority'] = 'high';
        } elseif ($days_until <= 30) {
            $response['mode'] = 'review_errors';
            $response['message'] = '시험 D-' . $days_until . '! 오답 회독을 시작하세요.';
            $response['priority'] = 'medium';
        } else {
            $response['has_recommendation'] = false;
        }
    } else {
        $response['success'] = true;
        $response['has_recommendation'] = false;
        $response['message'] = '예정된 시험이 없습니다.';
    }
    
} catch (Exception $e) {
    $response['error'] = 'System error occurred';
}

// JSON 출력
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;