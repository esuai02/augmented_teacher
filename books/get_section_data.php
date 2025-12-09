<?php
/**
 * Section Data Retrieval API - Fetches TTS section data from database
 *
 * @package    local_augmented_teacher
 * @copyright  2025 Hyperpeal Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../moodle/config.php');
global $DB, $USER;
require_login();

/**
 * Validate and sanitize contentsid parameter
 *
 * @param mixed $value Raw input value
 * @return int Validated positive integer
 * @throws Exception If validation fails
 */
function validateContentsId($value) {
    // Check if value exists
    if ($value === null || $value === '') {
        $error_msg = "contentsid 파라미터가 필요합니다 [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    // Convert to integer
    $contentsid = intval($value);

    // Validate positive integer
    if ($contentsid <= 0) {
        $error_msg = "유효하지 않은 contentsid입니다. 양의 정수가 필요합니다. (입력값: {$value}) [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    error_log("[get_section_data.php:" . __LINE__ . "] Validated contentsid: {$contentsid}");
    return $contentsid;
}

/**
 * Fetch section data from database
 *
 * @param object $DB Moodle database object
 * @param int $contentsid Content ID
 * @return object Database record
 * @throws Exception If record not found
 */
function fetchSectionRecord($DB, $contentsid) {
    error_log("[get_section_data.php:" . __LINE__ . "] Fetching record for contentsid: {$contentsid}");

    try {
        // 먼저 abrainalignment_gptresults 테이블에서 조회 (mynote.php용)
        $record = $DB->get_record('abrainalignment_gptresults',
            ['contentsid' => $contentsid],
            'id, contentsid, contentstype, reflections1'
        );

        if ($record) {
            error_log("[get_section_data.php:" . __LINE__ . "] Record found in abrainalignment_gptresults: id={$record->id}, contentstype={$record->contentstype}");
            return $record;
        }

        // 없으면 ktm_teaching_interactions 테이블에서 조회 (teachingagent.php용)
        error_log("[get_section_data.php:" . __LINE__ . "] Not found in abrainalignment_gptresults, trying ktm_teaching_interactions");
        $interaction = $DB->get_record('ktm_teaching_interactions',
            ['id' => $contentsid],
            'id, audio_url, narration_text'
        );

        if ($interaction) {
            // ktm_teaching_interactions의 경우 audio_url과 narration_text를 사용하여 구조 생성
            error_log("[get_section_data.php:" . __LINE__ . "] Record found in ktm_teaching_interactions: id={$interaction->id}");
            
            // audio_url이 JSON 배열 형식인지 확인
            $audioUrl = $interaction->audio_url;
            $narrationText = $interaction->narration_text ?? '';
            
            if (empty($audioUrl)) {
                $error_msg = "오디오 데이터가 없습니다. 먼저 TTS 섹션을 생성하세요. [get_section_data.php:" . __LINE__ . "]";
                error_log($error_msg);
                throw new Exception($error_msg);
            }
            
            // JSON 배열 파싱 시도
            $sectionFiles = json_decode($audioUrl, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($sectionFiles)) {
                // JSON이 아니면 단일 URL로 처리
                $sectionFiles = [$audioUrl];
            }
            
            // narration_text에서 @ 기호로 분리하여 text_sections 생성
            $textSections = [];
            if (!empty($narrationText)) {
                $textSections = array_filter(array_map('trim', explode('@', $narrationText)));
            }
            
            // sections와 text_sections 개수가 다르면 sections 개수에 맞춤
            if (count($textSections) !== count($sectionFiles)) {
                // sections 개수에 맞춰 text_sections 조정
                $textSections = array_slice($textSections, 0, count($sectionFiles));
                // 부족하면 빈 문자열로 채움
                while (count($textSections) < count($sectionFiles)) {
                    $textSections[] = '';
                }
            }
            
            // 가상 레코드 객체 생성 (reflections1 필드에 JSON 저장)
            $record = new stdClass();
            $record->id = $interaction->id;
            $record->contentsid = $interaction->id;
            $record->contentstype = 2; // teachingagent.php는 contentstype 2 사용
            $record->reflections1 = json_encode([
                'mode' => 'listening_test',
                'sections' => $sectionFiles,
                'text_sections' => $textSections
            ], JSON_UNESCAPED_UNICODE);
            
            error_log("[get_section_data.php:" . __LINE__ . "] Converted ktm_teaching_interactions record: " . count($sectionFiles) . " sections");
            return $record;
        }

        // 둘 다 없으면 에러
        $error_msg = "데이터를 찾을 수 없습니다. contentsid={$contentsid}에 해당하는 레코드가 없습니다. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);

    } catch (Exception $e) {
        $error_msg = "데이터베이스 조회 실패: " . $e->getMessage() . " [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }
}

/**
 * Parse and validate JSON from reflections1 field
 *
 * @param string $jsonString JSON string from database
 * @return array Parsed JSON data
 * @throws Exception If JSON is invalid or missing required fields
 */
function parseAndValidateJSON($jsonString) {
    // Check if reflections1 field is empty
    if (empty($jsonString)) {
        $error_msg = "섹션 데이터가 없습니다. 먼저 TTS 섹션을 생성하세요. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    error_log("[get_section_data.php:" . __LINE__ . "] Parsing JSON data (length: " . strlen($jsonString) . " bytes)");

    // Decode JSON
    $jsonData = json_decode($jsonString, true);

    // Check for JSON parsing errors
    if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
        $error_msg = "JSON 파싱 실패: " . json_last_error_msg() . " [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    // Validate JSON is an array
    if (!is_array($jsonData)) {
        $error_msg = "잘못된 JSON 형식: 배열이 아닙니다. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    error_log("[get_section_data.php:" . __LINE__ . "] JSON parsed successfully");

    // Validate required fields: sections
    if (!isset($jsonData['sections']) || !is_array($jsonData['sections'])) {
        $error_msg = "필수 필드 누락: 'sections' 배열이 없습니다. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    // Validate required fields: text_sections
    if (!isset($jsonData['text_sections']) || !is_array($jsonData['text_sections'])) {
        $error_msg = "필수 필드 누락: 'text_sections' 배열이 없습니다. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    // Validate arrays have same length
    $sectionsCount = count($jsonData['sections']);
    $textSectionsCount = count($jsonData['text_sections']);

    if ($sectionsCount !== $textSectionsCount) {
        $error_msg = "데이터 불일치: sections({$sectionsCount})와 text_sections({$textSectionsCount}) 개수가 다릅니다. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    // Validate at least one section exists
    if ($sectionsCount === 0) {
        $error_msg = "섹션이 없습니다. TTS 섹션을 다시 생성하세요. [get_section_data.php:" . __LINE__ . "]";
        error_log($error_msg);
        throw new Exception($error_msg);
    }

    error_log("[get_section_data.php:" . __LINE__ . "] JSON validation passed: {$sectionsCount} sections found");

    return $jsonData;
}

/**
 * Build response data for frontend modal
 *
 * @param array $jsonData Parsed JSON data from database
 * @return array Response data structure
 */
function buildResponseData($jsonData) {
    $responseData = [
        'sections' => $jsonData['sections'],
        'text_sections' => $jsonData['text_sections'],
        'total_sections' => count($jsonData['sections']),
        'current_index' => 0
    ];

    // Include optional metadata if available
    if (isset($jsonData['mode'])) {
        $responseData['mode'] = $jsonData['mode'];
    }

    if (isset($jsonData['created_at'])) {
        $responseData['created_at'] = $jsonData['created_at'];
    }

    if (isset($jsonData['success_count'])) {
        $responseData['success_count'] = $jsonData['success_count'];
    }

    if (isset($jsonData['failed_sections']) && !empty($jsonData['failed_sections'])) {
        $responseData['failed_sections'] = $jsonData['failed_sections'];
        $responseData['warning'] = '일부 섹션 생성 실패: ' . implode(', ', $jsonData['failed_sections']);
    }

    error_log("[get_section_data.php:" . __LINE__ . "] Response data built: {$responseData['total_sections']} sections");

    return $responseData;
}

// ============================================================================
// MAIN ENDPOINT: GET Request Handler
// ============================================================================

/**
 * Main section data retrieval endpoint handler
 *
 * Handles GET requests to fetch TTS section data from database.
 *
 * @route GET /books/get_section_data.php?contentsid=123
 * @param int contentsid Content ID (required, positive integer)
 * @return JSON Response with section data
 *
 * Response Structure:
 * {
 *   "success": bool,
 *   "data": {
 *     "sections": ["url1.mp3", "url2.mp3"],
 *     "text_sections": ["text1", "text2"],
 *     "total_sections": 2,
 *     "current_index": 0,
 *     "mode": "listening_test",
 *     "created_at": 1234567890,
 *     "success_count": 2,
 *     "failed_sections": []
 *   },
 *   "error": null
 * }
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');

    // Initialize response tracking
    $startTime = microtime(true);

    try {
        error_log("[get_section_data.php:" . __LINE__ . "] GET request received");

        // ==================================================================
        // STEP 1: Validate input parameters
        // ==================================================================

        // Extract contentsid from GET parameters
        $contentsidRaw = $_GET['contentsid'] ?? null;

        // Validate contentsid
        $contentsid = validateContentsId($contentsidRaw);

        error_log("[get_section_data.php:" . __LINE__ . "] Processing request for contentsid: {$contentsid}");

        // ==================================================================
        // STEP 2: Fetch record from database
        // ==================================================================

        $record = fetchSectionRecord($DB, $contentsid);

        // ==================================================================
        // STEP 3: Parse and validate JSON from reflections1 field
        // ==================================================================

        $jsonData = parseAndValidateJSON($record->reflections1);

        // ==================================================================
        // STEP 4: Build response data
        // ==================================================================

        $responseData = buildResponseData($jsonData);

        // ==================================================================
        // STEP 5: Build success response
        // ==================================================================

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $response = [
            'success' => true,
            'data' => $responseData,
            'error' => null
        ];

        error_log("[get_section_data.php:" . __LINE__ . "] Request completed successfully in {$executionTime}ms");

        // Return JSON response
        http_response_code(200);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        // ==================================================================
        // ERROR HANDLING
        // ==================================================================

        $errorMsg = $e->getMessage();
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        error_log("[get_section_data.php:" . __LINE__ . "] Request failed after {$executionTime}ms: {$errorMsg}");

        // Build error response
        $errorResponse = [
            'success' => false,
            'data' => null,
            'error' => $errorMsg
        ];

        // Determine appropriate HTTP status code
        $httpCode = 500; // Default: Internal Server Error

        if (strpos($errorMsg, 'contentsid 파라미터가 필요합니다') !== false ||
            strpos($errorMsg, '유효하지 않은 contentsid') !== false) {
            $httpCode = 400; // Bad Request
        } elseif (strpos($errorMsg, '데이터를 찾을 수 없습니다') !== false) {
            $httpCode = 404; // Not Found
        } elseif (strpos($errorMsg, '섹션 데이터가 없습니다') !== false) {
            $httpCode = 404; // Not Found
        }

        // Return error response
        http_response_code($httpCode);
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Terminate after handling GET request
    exit;
}

// ============================================================================
// Invalid Request Method Handler
// ============================================================================

// If not GET request, return method not allowed
header('Content-Type: application/json; charset=utf-8');
http_response_code(405);
echo json_encode([
    'success' => false,
    'data' => null,
    'error' => 'Method Not Allowed. 이 엔드포인트는 GET 요청만 지원합니다. [get_section_data.php:' . __LINE__ . ']'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

// ============================================================================
// Database Schema Reference (for documentation)
// ============================================================================
// Table: mdl_abrainalignment_gptresults
// Fields used:
// - contentsid (INT): Content identifier (WHERE clause)
// - reflections1 (TEXT): JSON structure with TTS section data (READ)
//
// Expected JSON Structure in reflections1:
// {
//   "mode": "listening_test",
//   "sections": ["url1.mp3", "url2.mp3"],
//   "text_sections": ["section text 1", "section text 2"],
//   "created_at": 1234567890,
//   "total_sections": 2,
//   "success_count": 2,
//   "failed_sections": []
// }
// ============================================================================
