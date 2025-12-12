<?php
/**
 * Agent01 Onboarding - 온보딩 데이터 조회 API
 * GET params: userid (required)
 * Returns: JSON with onboarding context data
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
    if ($userid <= 0) {
        $userid = $USER->id;
    }
    
    // 권한 체크
    $isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());
    if (!$isTeacher) {
        $userid = $USER->id; // 학생은 자신의 데이터만 조회 가능
    }
    
    // data_access.php의 getOnboardingContext 함수 사용
    require_once(__DIR__ . '/../rules/data_access.php');
    
    $context = getOnboardingContext($userid);
    
    echo json_encode([
        'success' => true,
        'userid' => $userid,
        'context' => $context
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]"
    ], JSON_UNESCAPED_UNICODE);
}
?>

