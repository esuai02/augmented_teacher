<?php
/**
 * 발표 기록 조회 API
 * - presentation_id로 발표 텍스트/분석/선택 값을 조회
 *
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $presentationId = isset($_GET['presentation_id']) ? (int)$_GET['presentation_id'] : 0;
    if ($presentationId <= 0) {
        throw new Exception('presentation_id가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $table = 'at_student_presentations';
    $record = $DB->get_record($table, ['id' => $presentationId], '*', IGNORE_MISSING);
    if (!$record) {
        throw new Exception('presentation_id 레코드를 찾을 수 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 404);
    }

    // 기본은 본인 데이터만 조회 허용
    if ((int)$record->userid !== (int)$USER->id) {
        throw new Exception('권한이 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 403);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$record->id,
            'analysis_id' => $record->analysis_id,
            'userid' => (int)$record->userid,
            'contentsid' => (int)$record->contentsid,
            'contentstype' => $record->contentstype,
            'nrepeat' => (int)$record->nrepeat,
            'duration_seconds' => (int)($record->duration_seconds ?? 0),
            'presentation_text' => $record->presentation_text,
            'analysis_json' => $record->analysis_json,
            'weak_personas_json' => $record->weak_personas_json,
            'selected_persona_ids_json' => $record->selected_persona_ids_json,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    error_log("Get Presentation Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());

    $code = $e->getCode() ?: 500;
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();


