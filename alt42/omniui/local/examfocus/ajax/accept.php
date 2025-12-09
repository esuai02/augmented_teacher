<?php
/**
 * AJAX 핸들러 - 추천 수락
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

// Moodle 설정 로드
$config_paths = [
    __DIR__ . '/../../../../../config.php',
    __DIR__ . '/../../../../config.php',
    __DIR__ . '/../../../config.php',
    '/home/moodle/public_html/moodle/config.php'
];

foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        break;
    }
}

require_login();
require_sesskey();

header('Content-Type: application/json; charset=utf-8');

try {
    $userid = required_param('userid', PARAM_INT);
    $mode = required_param('mode', PARAM_TEXT);
    
    // 권한 체크
    if ($userid != $USER->id && !is_siteadmin()) {
        throw new moodle_exception('nopermissions');
    }
    
    // 서비스 로드
    require_once($CFG->dirroot . '/local/examfocus/classes/service/exam_focus_service.php');
    $service = new \local_examfocus\service\exam_focus_service();
    
    // 추천 수락 처리
    $result = $service->accept_recommendation($userid, $mode);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => get_string('recommendation_accepted', 'local_examfocus')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to accept recommendation'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}