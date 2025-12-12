<?php
/**
 * 발표 기록 저장/업데이트 API
 * - 신규 발표 레코드 생성(필수: userid, contentsid, contentstype, nrepeat)
 * - STT 텍스트/분석 결과/선택 페르소나 업데이트
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $table = 'at_student_presentations';
    $action = $input['action'] ?? 'create_or_update';

    // 공통 필드
    $presentationId = isset($input['presentation_id']) ? (int)$input['presentation_id'] : 0;
    $analysisId = $input['analysis_id'] ?? null;
    $contentsId = isset($input['contentsid']) ? (int)$input['contentsid'] : 0;
    $contentsType = $input['contentstype'] ?? '';

    // 업데이트용(선택)
    $durationSeconds = isset($input['duration_seconds']) ? (int)$input['duration_seconds'] : null;
    $presentationText = $input['presentation_text'] ?? null;
    $analysisJson = $input['analysis_json'] ?? null; // array|object|string 허용
    $weakPersonas = $input['weak_personas'] ?? null; // array 허용
    $selectedPersonaIds = $input['selected_persona_ids'] ?? null; // array 허용

    if ($action === 'create') {
        if ($contentsId <= 0) {
            throw new Exception('contentsid가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
        }
        if (empty($contentsType)) {
            throw new Exception('contentstype가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
        }

        // nrepeat 계산: 동일 user + content 기준 최대 nrepeat + 1
        $last = $DB->get_record_sql(
            "SELECT nrepeat FROM {at_student_presentations}
             WHERE userid = ? AND contentsid = ? AND contentstype = ?
             ORDER BY nrepeat DESC, id DESC LIMIT 1",
            [$USER->id, $contentsId, $contentsType]
        );
        $nrepeat = $last ? ((int)$last->nrepeat + 1) : 1;

        $record = new stdClass();
        $record->analysis_id = $analysisId;
        $record->userid = $USER->id;
        $record->contentsid = $contentsId;
        $record->contentstype = $contentsType;
        $record->nrepeat = $nrepeat;
        $record->duration_seconds = isset($durationSeconds) ? (int)$durationSeconds : 0;
        $record->presentation_text = null;
        $record->analysis_json = null;
        $record->weak_personas_json = null;
        $record->selected_persona_ids_json = null;
        $record->created_at = date('Y-m-d H:i:s');
        $record->updated_at = date('Y-m-d H:i:s');

        $newId = $DB->insert_record($table, $record);

        echo json_encode([
            'success' => true,
            'presentation_id' => (int)$newId,
            'nrepeat' => $nrepeat,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // update or create_or_update
    if ($presentationId > 0) {
        $existing = $DB->get_record($table, ['id' => $presentationId], '*', IGNORE_MISSING);
        if (!$existing) {
            throw new Exception('presentation_id 레코드를 찾을 수 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 404);
        }
        if ((int)$existing->userid !== (int)$USER->id) {
            throw new Exception('권한이 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 403);
        }

        $update = new stdClass();
        $update->id = $presentationId;
        $update->updated_at = date('Y-m-d H:i:s');

        if ($analysisId !== null) $update->analysis_id = $analysisId;
        if ($durationSeconds !== null) $update->duration_seconds = (int)$durationSeconds;
        if ($presentationText !== null) $update->presentation_text = $presentationText;

        if ($analysisJson !== null) {
            $update->analysis_json = is_string($analysisJson) ? $analysisJson : json_encode($analysisJson, JSON_UNESCAPED_UNICODE);
        }
        if ($weakPersonas !== null) {
            $update->weak_personas_json = is_string($weakPersonas) ? $weakPersonas : json_encode($weakPersonas, JSON_UNESCAPED_UNICODE);
        }
        if ($selectedPersonaIds !== null) {
            $update->selected_persona_ids_json = is_string($selectedPersonaIds) ? $selectedPersonaIds : json_encode($selectedPersonaIds, JSON_UNESCAPED_UNICODE);
        }

        $DB->update_record($table, $update);

        echo json_encode([
            'success' => true,
            'presentation_id' => $presentationId,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // create_or_update without presentation_id => create
    if ($contentsId <= 0) {
        throw new Exception('contentsid가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }
    if (empty($contentsType)) {
        throw new Exception('contentstype가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $last = $DB->get_record_sql(
        "SELECT nrepeat FROM {at_student_presentations}
         WHERE userid = ? AND contentsid = ? AND contentstype = ?
         ORDER BY nrepeat DESC, id DESC LIMIT 1",
        [$USER->id, $contentsId, $contentsType]
    );
    $nrepeat = $last ? ((int)$last->nrepeat + 1) : 1;

    $record = new stdClass();
    $record->analysis_id = $analysisId;
    $record->userid = $USER->id;
    $record->contentsid = $contentsId;
    $record->contentstype = $contentsType;
    $record->nrepeat = $nrepeat;
    $record->duration_seconds = isset($durationSeconds) ? (int)$durationSeconds : 0;
    $record->presentation_text = $presentationText;
    $record->analysis_json = ($analysisJson === null) ? null : (is_string($analysisJson) ? $analysisJson : json_encode($analysisJson, JSON_UNESCAPED_UNICODE));
    $record->weak_personas_json = ($weakPersonas === null) ? null : (is_string($weakPersonas) ? $weakPersonas : json_encode($weakPersonas, JSON_UNESCAPED_UNICODE));
    $record->selected_persona_ids_json = ($selectedPersonaIds === null) ? null : (is_string($selectedPersonaIds) ? $selectedPersonaIds : json_encode($selectedPersonaIds, JSON_UNESCAPED_UNICODE));
    $record->created_at = date('Y-m-d H:i:s');
    $record->updated_at = date('Y-m-d H:i:s');

    $newId = $DB->insert_record($table, $record);

    echo json_encode([
        'success' => true,
        'presentation_id' => (int)$newId,
        'nrepeat' => $nrepeat,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    error_log("Save Presentation Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());

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


