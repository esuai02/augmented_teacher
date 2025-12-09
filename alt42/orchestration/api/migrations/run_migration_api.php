<?php
/**
 * 마이그레이션 API 엔드포인트
 * AJAX 요청 처리
 * 
 * @package ALT42\Migrations
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Moodle config 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

if ($role !== 'admin' && $role !== 'manager') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => '권한이 없습니다. 관리자만 실행할 수 있습니다. at ' . __FILE__ . ':' . __LINE__
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_migration') {
    $migration = $_POST['migration'] ?? '';
    $output = '';
    $success = false;
    
    try {
        if ($migration === '005' || $migration === 'all') {
            ob_start();
            include(__DIR__ . '/../../db/migrations/run_005_migration.php');
            $output .= ob_get_clean() . "\n";
        }
        
        if ($migration === '006' || $migration === 'all') {
            ob_start();
            include(__DIR__ . '/../../db/migrations/run_006_migration.php');
            $output .= ob_get_clean() . "\n";
        }
        
        $success = true;
    } catch (Exception $e) {
        $output .= "\n오류: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
        $success = false;
    }
    
    echo json_encode([
        'success' => $success,
        'output' => $output
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// GET 요청 처리 (테스트)
if (isset($_GET['action']) && $_GET['action'] === 'test') {
    $output = '';
    $success = false;
    
    try {
        ob_start();
        include(__DIR__ . '/../../api/scheduler/test_heartbeat.php');
        $output = ob_get_clean();
        $success = true;
    } catch (Exception $e) {
        $output = "오류: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
        $success = false;
    }
    
    echo json_encode([
        'success' => $success,
        'output' => $output
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 잘못된 요청
http_response_code(400);
echo json_encode([
    'success' => false,
    'error' => '잘못된 요청입니다. at ' . __FILE__ . ':' . __LINE__
]);
?>

