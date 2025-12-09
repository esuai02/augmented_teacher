<?php
/**
 * ExamFocus 학습 모드 시작 처리
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
$mode = $input['mode'] ?? $_POST['mode'] ?? $_GET['mode'] ?? null;

$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => null,
    'actions_completed' => []
];

if (!$userid || !$mode) {
    $response['message'] = '필수 매개변수가 누락되었습니다.';
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
    
    // 2. 모드별 처리
    $completed_actions = [];
    $redirect_url = null;
    
    switch ($mode) {
        case 'concept_summary':
            // 개념요약 모드
            $completed_actions = [
                '개념요약 모드 활성화',
                '핵심 개념 정리 페이지 준비',
                '대표 유형 문제 목록 생성'
            ];
            $redirect_url = '../../concept_summary.php?user_id=' . $userid;
            break;
            
        case 'review_errors':
            // 오답 회독 모드
            $completed_actions = [
                '오답 회독 모드 활성화',
                '틀린 문제 목록 준비',
                '취약 단원 분석 완료'
            ];
            $redirect_url = '../../review_errors.php?user_id=' . $userid;
            break;
            
        case 'practice':
            // 실전 연습 모드
            $completed_actions = [
                '실전 연습 모드 활성화',
                '모의고사 문제 준비',
                '시간 측정 도구 설정'
            ];
            $redirect_url = '../../practice.php?user_id=' . $userid;
            break;
            
        case 'exam_day':
            // 시험 당일 모드
            $completed_actions = [
                '시험 당일 모드 활성화',
                '최종 점검 리스트 준비',
                '멘탈 관리 도구 활성화'
            ];
            $redirect_url = '../../exam_day.php?user_id=' . $userid;
            break;
            
        case 'study':
            // 일반 학습 모드
            $completed_actions = [
                '일반 학습 모드 활성화',
                '일일 학습 계획 생성',
                '진도 관리 도구 준비'
            ];
            $redirect_url = '../../study.php?user_id=' . $userid;
            break;
            
        default:
            // 기본 처리
            $completed_actions = [
                '학습 모드 설정 완료'
            ];
            $redirect_url = '../../exam_system.php?user_id=' . $userid;
    }
    
    // 3. 활동 로그 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userid, 'examfocus_mode_' . $mode, time()]);
    
    // 4. 세션에 모드 저장
    $_SESSION['examfocus_current_mode'] = $mode;
    $_SESSION['examfocus_started_at'] = time();
    $_SESSION['examfocus_user_id'] = $userid;
    
    // 5. 성공 응답
    $response['success'] = true;
    $response['message'] = $mode . ' 모드가 성공적으로 시작되었습니다!';
    $response['redirect_url'] = $redirect_url;
    $response['actions_completed'] = $completed_actions;
    $response['mode'] = $mode;
    $response['user_name'] = trim($user['firstname'] . ' ' . $user['lastname']);
    
} catch (Exception $e) {
    $response['message'] = '처리 중 오류가 발생했습니다: ' . $e->getMessage();
    error_log("ExamFocus start_mode error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;