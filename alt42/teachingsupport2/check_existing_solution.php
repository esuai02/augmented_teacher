<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$contentsid = $input['contentsid'] ?? 0;
$contentstype = $input['contentstype'] ?? 0;
$studentid = $input['studentid'] ?? $USER->id;

if (!$contentsid || !$contentstype) {
    echo json_encode([
        'success' => false, 
        'error' => 'contentsid와 contentstype이 필요합니다.',
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    exit;
}

try {
    // abessi_messages 테이블에서 contentsid와 contentstype으로 조회
    // contentsid가 ktm_teaching_interactions의 id인 경우
    $sql = "SELECT i.* 
            FROM {$CFG->prefix}ktm_teaching_interactions i
            WHERE i.id = ? 
            AND i.userid = ?
            ORDER BY i.timemodified DESC 
            LIMIT 1";
    
    $existingSolution = $DB->get_record_sql($sql, [$contentsid, $studentid]);
    
    if ($existingSolution) {
        echo json_encode([
            'success' => true,
            'exists' => true,
            'interaction' => [
                'id' => $existingSolution->id,
                'problem_type' => $existingSolution->problem_type ?? '',
                'problem_image' => $existingSolution->problem_image ?? '',
                'problem_text' => $existingSolution->problem_text ?? '',
                'solution_text' => $existingSolution->solution_text ?? '',
                'narration_text' => $existingSolution->narration_text ?? '',
                'audio_url' => $existingSolution->audio_url ?? '',
                'faqtext' => $existingSolution->faqtext ?? '',
                'modification_prompt' => $existingSolution->modification_prompt ?? '',
                'status' => $existingSolution->status ?? '',
                'teacherid' => $existingSolution->teacherid ?? 0,
                'wboardid' => $existingSolution->wboardid ?? '',
                'type' => $existingSolution->type ?? ''
            ],
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    } else {
        // abessi_messages에서 직접 조회 시도 (contentsid가 interaction id인 경우)
        $directSql = "SELECT * 
                      FROM {$CFG->prefix}ktm_teaching_interactions 
                      WHERE id = ? 
                      AND userid = ?
                      ORDER BY timemodified DESC 
                      LIMIT 1";
        
        $directSolution = $DB->get_record_sql($directSql, [$contentsid, $studentid]);
        
        if ($directSolution) {
            echo json_encode([
                'success' => true,
                'exists' => true,
                'interaction' => [
                    'id' => $directSolution->id,
                    'problem_type' => $directSolution->problem_type ?? '',
                    'problem_image' => $directSolution->problem_image ?? '',
                    'problem_text' => $directSolution->problem_text ?? '',
                    'solution_text' => $directSolution->solution_text ?? '',
                    'narration_text' => $directSolution->narration_text ?? '',
                    'audio_url' => $directSolution->audio_url ?? '',
                    'faqtext' => $directSolution->faqtext ?? '',
                    'modification_prompt' => $directSolution->modification_prompt ?? '',
                    'status' => $directSolution->status ?? '',
                    'teacherid' => $directSolution->teacherid ?? 0,
                    'wboardid' => $directSolution->wboardid ?? '',
                    'type' => $directSolution->type ?? ''
                ],
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'exists' => false,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    }
} catch (Exception $e) {
    error_log(sprintf(
        '[check_existing_solution.php] File: %s, Line: %d, Exception 발생: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>

