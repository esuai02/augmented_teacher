<?php
/**
 * Agent05 학습감정 분석 API
 * 활동 선택 및 감정 유형 데이터 관리
 *
 * File: alt42/orchestration/agents/agent05_learning_emotion/api/activity_emotion_api.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS 헤더
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? 'student';

// 입력 데이터
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'saveActivitySelection':
            // 활동 선택 저장 (추후 구현)
            $activity_key = $input['activity_key'] ?? '';
            $activity_name = $input['activity_name'] ?? '';
            $sub_item = $input['sub_item'] ?? '';
            $userid = $input['userid'] ?? $USER->id;

            if (empty($activity_key) || empty($sub_item)) {
                throw new Exception('활동 및 세부 항목을 선택해주세요 [activity_emotion_api.php:52]');
            }

            // TODO: 활동 선택 데이터 DB 저장 로직 추가
            // 현재는 로깅만 수행
            error_log("Agent05: Activity selected - User: $userid, Activity: $activity_name, SubItem: $sub_item");

            echo json_encode([
                'success' => true,
                'message' => '활동 선택이 저장되었습니다 (임시)',
                'data' => [
                    'userid' => $userid,
                    'activity_key' => $activity_key,
                    'activity_name' => $activity_name,
                    'sub_item' => $sub_item,
                    'timestamp' => time()
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'getActivitySelections':
            // 활동 선택 이력 조회 (추후 구현)
            $userid = $input['userid'] ?? $USER->id;

            // TODO: DB에서 활동 선택 이력 조회
            echo json_encode([
                'success' => true,
                'data' => [],
                'message' => '활동 선택 이력 조회 (추후 구현) [activity_emotion_api.php:81]'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'getEmotionSurveyQuestions':
            // 감정 설문 문항 조회 (추후 구현)
            $activity_key = $input['activity_key'] ?? '';

            // TODO: 활동별 감정 설문 문항 반환
            echo json_encode([
                'success' => true,
                'data' => [
                    'activity_key' => $activity_key,
                    'questions' => []
                ],
                'message' => '감정 설문 문항 조회 (추후 구현) [activity_emotion_api.php:98]'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'saveEmotionSurvey':
            // 감정 설문 응답 저장 (추후 구현)
            $userid = $input['userid'] ?? $USER->id;
            $activity_key = $input['activity_key'] ?? '';
            $responses = $input['responses'] ?? [];

            if (empty($activity_key) || empty($responses)) {
                throw new Exception('설문 응답 데이터가 없습니다 [activity_emotion_api.php:110]');
            }

            // TODO: 설문 응답 DB 저장
            error_log("Agent05: Survey saved - User: $userid, Activity: $activity_key");

            echo json_encode([
                'success' => true,
                'message' => '설문 응답이 저장되었습니다 (임시)',
                'data' => [
                    'userid' => $userid,
                    'activity_key' => $activity_key,
                    'response_count' => count($responses),
                    'timestamp' => time()
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception('알 수 없는 액션: ' . $action . ' [activity_emotion_api.php:131]');
    }

} catch (Exception $e) {
    error_log("Agent05 API Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
