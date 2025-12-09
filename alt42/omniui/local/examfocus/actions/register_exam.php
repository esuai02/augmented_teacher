<?php
/**
 * ExamFocus 시험 등록 처리
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 처리
error_reporting(0);
ini_set('display_errors', 0);

// CORS 및 JSON 헤더
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 세션 시작
session_start();

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$userid = $input['user_id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? null;
$exam_data = $input['exam_data'] ?? $_POST['exam_data'] ?? null;

$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => null
];

if (!$userid) {
    $response['message'] = '사용자 ID가 필요합니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 데이터베이스 연결
    $dsn = "mysql:host=58.180.27.46;dbname=mathking;charset=utf8mb4";
    $pdo = new PDO($dsn, 'moodle', '@MCtrigd7128', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // 1. 사용자 검증
    $stmt = $pdo->prepare("SELECT id, username, firstname, lastname FROM mdl_user WHERE id = ? AND deleted = 0");
    $stmt->execute([$userid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $response['message'] = '사용자를 찾을 수 없습니다.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 2. 시험 등록 데이터 처리
    if ($exam_data) {
        // Alt42t DB에 시험 정보 저장
        try {
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
                // 기존 시험 정보 업데이트 또는 새로 추가
                $stmt = $alt_pdo->prepare("
                    INSERT INTO student_exam_settings 
                    (user_id, name, school, grade, semester, exam_type, exam_start_date, exam_end_date, 
                     math_exam_date, exam_scope, exam_status, study_level, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'expected', 'concept', NOW())
                    ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    school = VALUES(school),
                    grade = VALUES(grade),
                    semester = VALUES(semester),
                    exam_type = VALUES(exam_type),
                    exam_start_date = VALUES(exam_start_date),
                    exam_end_date = VALUES(exam_end_date),
                    math_exam_date = VALUES(math_exam_date),
                    exam_scope = VALUES(exam_scope),
                    exam_status = VALUES(exam_status),
                    study_level = VALUES(study_level),
                    updated_at = NOW()
                ");
                
                $stmt->execute([
                    $userid,
                    trim($user['firstname'] . ' ' . $user['lastname']),
                    $exam_data['school'] ?? '',
                    $exam_data['grade'] ?? '',
                    $exam_data['semester'] ?? '',
                    $exam_data['exam_type'] ?? '정기고사',
                    $exam_data['exam_start_date'] ?? null,
                    $exam_data['exam_end_date'] ?? null,
                    $exam_data['math_exam_date'] ?? null,
                    $exam_data['exam_scope'] ?? ''
                ]);
                
                $response['message'] = '시험 정보가 성공적으로 등록되었습니다!';
                $response['success'] = true;
            } else {
                throw new Exception("Alt42t 데이터베이스에 연결할 수 없습니다.");
            }
        } catch (Exception $e) {
            // Alt42t 실패 시 MathKing DB에 스케줄로 저장
            $schedule_data = [
                'exam_registration' => [
                    'exam_type' => $exam_data['exam_type'] ?? '정기고사',
                    'math_exam_date' => $exam_data['math_exam_date'] ?? null,
                    'exam_scope' => $exam_data['exam_scope'] ?? '',
                    'registered_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO mdl_abessi_schedule (userid, schedule_data, pinned, timecreated, timemodified)
                VALUES (?, ?, 1, ?, ?)
                ON DUPLICATE KEY UPDATE
                schedule_data = VALUES(schedule_data),
                timemodified = VALUES(timemodified)
            ");
            
            $now = time();
            $stmt->execute([
                $userid, 
                json_encode($schedule_data, JSON_UNESCAPED_UNICODE), 
                $now, 
                $now
            ]);
            
            $response['message'] = '시험 정보가 로컬에 저장되었습니다.';
            $response['success'] = true;
        }
    } else {
        // 시험 등록 페이지로 리다이렉트
        $response['redirect_url'] = '../../exam_system.php?user_id=' . $userid;
        $response['message'] = '시험 등록 페이지로 이동합니다.';
        $response['success'] = true;
    }
    
    // 3. 활동 로그 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userid, 'examfocus_exam_register', time()]);
    
} catch (Exception $e) {
    $response['message'] = '처리 중 오류가 발생했습니다: ' . $e->getMessage();
    error_log("ExamFocus register_exam error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;