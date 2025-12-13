<?php
/**
 * 문항 이미지 분석 API
 * 문제/해설 이미지를 OpenAI Vision으로 분석하여 맞춤형 페르소나 생성
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . '/../../config.php'); // OpenAI API 키 설정
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../includes/question_persona_generator.php');

// 요청 파라미터
$questionId = $_REQUEST['question_id'] ?? null;
$wboardId = $_REQUEST['wboard_id'] ?? null;
$studentId = $_REQUEST['student_id'] ?? $USER->id;
$questionImageUrl = $_REQUEST['question_image'] ?? null;
$solutionImageUrl = $_REQUEST['solution_image'] ?? null;
$forceRefresh = isset($_REQUEST['force_refresh']) && $_REQUEST['force_refresh'] === '1';

try {
    if (!$wboardId && !$questionId) {
        throw new Exception("[analyze_question.php] wboard_id 또는 question_id가 필요합니다.");
    }
    
    $generator = new QuestionPersonaGenerator();
    
    // 캐시된 결과 확인 (force_refresh가 아닌 경우)
    if (!$forceRefresh) {
        $cached = $generator->getCachedAnalysis($wboardId ?: $questionId, $studentId);
        if ($cached) {
            echo json_encode([
                'success' => true,
                'source' => 'cache',
                'data' => $cached
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // wboardId로 문제 정보 조회
    if ($wboardId && (!$questionImageUrl || !$solutionImageUrl)) {
        $thisboard = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_messages WHERE wboardid = ? ORDER BY tlaststroke DESC LIMIT 1",
            [$wboardId]
        );
        
        if ($thisboard && $thisboard->contentsid) {
            $questionId = $thisboard->contentsid;
            
            // 문제 텍스트/이미지 조회
            $qtext = $DB->get_record_sql(
                "SELECT questiontext, generalfeedback FROM mdl_question WHERE id = ? LIMIT 1",
                [$questionId]
            );
            
            if ($qtext) {
                // 문제 이미지 추출
                if (!$questionImageUrl) {
                    $questionImageUrl = extractImageFromHtml($qtext->questiontext);
                }
                // 해설 이미지 추출
                if (!$solutionImageUrl) {
                    $solutionImageUrl = extractImageFromHtml($qtext->generalfeedback);
                }
            }
        }
    }
    
    if (!$questionImageUrl) {
        throw new Exception("[analyze_question.php] 문제 이미지를 찾을 수 없습니다.");
    }
    
    // OpenAI Vision으로 분석 및 페르소나 생성
    $result = $generator->analyzeAndGeneratePersona([
        'question_id' => $questionId,
        'wboard_id' => $wboardId,
        'student_id' => $studentId,
        'question_image' => $questionImageUrl,
        'solution_image' => $solutionImageUrl
    ]);
    
    echo json_encode([
        'success' => true,
        'source' => 'analysis',
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("[analyze_question.php] 오류: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * HTML에서 이미지 URL 추출
 */
function extractImageFromHtml($html) {
    if (empty($html)) return null;
    
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $images = $dom->getElementsByTagName('img');
    
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        $src = str_replace(' ', '%20', $src);
        
        // 유효한 이미지 URL 판단
        if (strpos($src, 'hintimages') === false && 
            (strpos($src, '.png') !== false || strpos($src, '.jpg') !== false)) {
            return $src;
        }
    }
    
    return null;
}

