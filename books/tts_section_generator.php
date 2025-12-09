<?php
/**
 * TTS Section Generator - Splits @-separated text into individual TTS audio files
 *
 * @package    local_augmented_teacher
 * @copyright  2025 Hyperpeal Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../moodle/config.php');
global $DB, $USER;
require_login();

/**
 * Split text by @ separator and trim each section
 *
 * @param string $text Input text with @ separators
 * @return array Array of trimmed text sections (empty array if input is empty)
 * @throws InvalidArgumentException If input is not a string
 */
function splitTextBySeparator($text) {
    // Input validation with error context
    if (!is_string($text)) {
        $error_msg = "[tts_section_generator.php:" . __LINE__ . "] Input must be string, " . gettype($text) . " given";
        error_log($error_msg);
        throw new InvalidArgumentException($error_msg);
    }

    // Handle empty string
    if (empty($text)) {
        error_log("[tts_section_generator.php:" . __LINE__ . "] Empty input provided, returning empty array");
        return [];
    }

    try {
        $sections = explode('@', $text);
        $sections = array_filter(array_map('trim', $sections));
        $result = array_values($sections); // Re-index array

        error_log("[tts_section_generator.php:" . __LINE__ . "] Split text into " . count($result) . " sections");
        return $result;
    } catch (Exception $e) {
        $error_msg = "[tts_section_generator.php:" . __LINE__ . "] Error splitting text: " . $e->getMessage();
        error_log($error_msg);
        throw $e;
    }
}

/**
 * Validate section count is within acceptable range
 *
 * @param array $sections Array of text sections
 * @return array ['valid' => bool, 'error' => string|null]
 * @throws InvalidArgumentException If input is not an array
 */
function validateSectionCount($sections) {
    // Input validation
    if (!is_array($sections)) {
        $error_msg = "[tts_section_generator.php:" . __LINE__ . "] Input must be array, " . gettype($sections) . " given";
        error_log($error_msg);
        throw new InvalidArgumentException($error_msg);
    }

    $count = count($sections);
    error_log("[tts_section_generator.php:" . __LINE__ . "] Validating section count: {$count}");

    // Minimum validation (2 sections required)
    if ($count < 2) {
        $error_msg = "최소 2개 이상의 섹션이 필요합니다. (현재: {$count}개) [tts_section_generator.php:" . __LINE__ . "]";
        error_log($error_msg);
        return [
            'valid' => false,
            'error' => $error_msg
        ];
    }

    // Maximum validation (20 sections maximum)
    if ($count > 20) {
        $error_msg = "섹션이 너무 많습니다. 최대 20개까지 가능합니다. (현재: {$count}개) [tts_section_generator.php:" . __LINE__ . "]";
        error_log($error_msg);
        return [
            'valid' => false,
            'error' => $error_msg
        ];
    }

    // Valid count
    error_log("[tts_section_generator.php:" . __LINE__ . "] Section count validation passed: {$count} sections");
    return [
        'valid' => true,
        'error' => null
    ];
}

/**
 * Generate filename for TTS audio file
 *
 * @param int $contentsid Content ID (essay ID)
 * @param int $sectionNum Section number (1-based index)
 * @return string Filename without path (e.g., essay_instruction_12345_section_1_1737000000.mp3)
 * @throws InvalidArgumentException If parameters are invalid
 */
function generateTTSFilename($contentsid, $sectionNum) {
    // Validate inputs
    if (!is_int($contentsid) || $contentsid <= 0) {
        $error_msg = "[tts_section_generator.php:" . __LINE__ . "] Invalid contentsid: must be positive integer";
        error_log($error_msg);
        throw new InvalidArgumentException($error_msg);
    }

    if (!is_int($sectionNum) || $sectionNum <= 0) {
        $error_msg = "[tts_section_generator.php:" . __LINE__ . "] Invalid sectionNum: must be positive integer";
        error_log($error_msg);
        throw new InvalidArgumentException($error_msg);
    }

    $timestamp = time();
    $filename = "essay_instruction_{$contentsid}_section_{$sectionNum}_{$timestamp}.mp3";

    error_log("[tts_section_generator.php:" . __LINE__ . "] Generated filename: {$filename}");
    return $filename;
}

/**
 * Call OpenAI TTS API to generate audio for a section
 *
 * @param string $text Text to convert to speech (10-4000 characters)
 * @param int $contentsid Content ID for file naming
 * @param int $sectionNum Section number for file naming
 * @return array ['success' => bool, 'url' => string|null, 'error' => string|null]
 * @throws Exception On validation or API errors (caught internally)
 */
function generateTTSForSection($text, $contentsid, $sectionNum) {
    $maxRetries = 3;
    $retryDelay = 1; // seconds

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            error_log("[tts_section_generator.php:" . __LINE__ . "] TTS generation attempt {$attempt}/{$maxRetries} for section {$sectionNum}");

            // Validate text length
            if (!is_string($text) || strlen($text) < 10) {
                throw new Exception("텍스트가 너무 짧습니다 (최소 10자) [tts_section_generator.php:" . __LINE__ . "]");
            }

            if (strlen($text) > 4000) {
                throw new Exception("텍스트가 너무 깁니다 (최대 4000자) [tts_section_generator.php:" . __LINE__ . "]");
            }

            // Generate filename
            $filename = generateTTSFilename($contentsid, $sectionNum);

            // Get API key from environment (SECURITY: never use hardcoded fallback)
            $apiKey = getenv('OPENAI_API_KEY');
            if (!$apiKey) {
                $error_msg = "서버 설정 오류: OPENAI_API_KEY 환경 변수가 설정되지 않았습니다 [tts_section_generator.php:" . __LINE__ . "]";
                error_log($error_msg);
                throw new Exception($error_msg);
            }

            // Validate API key format (OpenAI keys start with sk-)
            if (!preg_match('/^sk-[a-zA-Z0-9]{20,}$/', $apiKey)) {
                $error_msg = "서버 설정 오류: 잘못된 API 키 형식 [tts_section_generator.php:" . __LINE__ . "]";
                error_log($error_msg);
                throw new Exception($error_msg);
            }

            // Prepare API request
            $postData = json_encode([
                'model' => 'tts-1',
                'voice' => 'alloy',
                'input' => $text,
                'response_format' => 'mp3',
                'speed' => 1.0
            ]);

            error_log("[tts_section_generator.php:" . __LINE__ . "] Calling OpenAI TTS API for section {$sectionNum}");

            // Initialize cURL
            $ch = curl_init('https://api.openai.com/v1/audio/speech');
            if ($ch === false) {
                throw new Exception("cURL 초기화 실패 [tts_section_generator.php:" . __LINE__ . "]");
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);

            // Execute request
            $audioData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Handle cURL errors
            if ($audioData === false) {
                throw new Exception("네트워크 오류: {$curlError} [tts_section_generator.php:" . __LINE__ . "]");
            }

            // Handle HTTP errors
            if ($httpCode !== 200) {
                // Try to parse error response
                $errorResponse = json_decode($audioData, true);
                $errorMessage = $errorResponse['error']['message'] ?? "Unknown error";

                // Check if error is transient (rate limit, server error)
                if (in_array($httpCode, [429, 500, 502, 503, 504]) && $attempt < $maxRetries) {
                    error_log("[tts_section_generator.php:" . __LINE__ . "] Transient error (HTTP {$httpCode}), retrying in {$retryDelay}s");
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
                    continue;
                }

                throw new Exception("OpenAI API 호출 실패 (HTTP {$httpCode}): {$errorMessage} [tts_section_generator.php:" . __LINE__ . "]");
            }

            // Validate audio data
            if (empty($audioData)) {
                throw new Exception("빈 오디오 데이터 반환됨 [tts_section_generator.php:" . __LINE__ . "]");
            }

            error_log("[tts_section_generator.php:" . __LINE__ . "] Audio data received, size: " . strlen($audioData) . " bytes");

            // Save audio file to server
            $uploadResult = uploadAudioFile($audioData, $filename, $contentsid, $sectionNum);

            if (!$uploadResult['success']) {
                throw new Exception("파일 업로드 실패: " . $uploadResult['error']);
            }

            error_log("[tts_section_generator.php:" . __LINE__ . "] TTS generation successful for section {$sectionNum}: {$uploadResult['url']}");

            return [
                'success' => true,
                'url' => $uploadResult['url'],
                'error' => null
            ];

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("[tts_section_generator.php:" . __LINE__ . "] TTS 생성 실패 (attempt {$attempt}/{$maxRetries}): {$errorMsg}");

            // If final attempt, return error
            if ($attempt >= $maxRetries) {
                return [
                    'success' => false,
                    'url' => null,
                    'error' => $errorMsg
                ];
            }

            // Otherwise, retry after delay
            if ($attempt < $maxRetries) {
                sleep($retryDelay);
                $retryDelay *= 2;
            }
        }
    }

    // Should never reach here, but just in case
    return [
        'success' => false,
        'url' => null,
        'error' => "최대 재시도 횟수 초과 [tts_section_generator.php:" . __LINE__ . "]"
    ];
}

/**
 * Upload audio file to server storage
 *
 * @param string $audioData Binary audio data from TTS API
 * @param string $filename Target filename (e.g., essay_instruction_12345_section_1_1737000000.mp3)
 * @param int $contentsid Content ID
 * @param int $sectionNum Section number
 * @return array ['success' => bool, 'url' => string|null, 'error' => string|null]
 */
function uploadAudioFile($audioData, $filename, $contentsid, $sectionNum) {
    try {
        error_log("[tts_section_generator.php:" . __LINE__ . "] Uploading audio file: {$filename}");

        // Validate inputs
        if (empty($audioData)) {
            throw new Exception("빈 오디오 데이터 [tts_section_generator.php:" . __LINE__ . "]");
        }

        // Validate filename to prevent directory traversal
        $safeFilename = basename($filename);
        if ($safeFilename !== $filename) {
            $error_msg = "보안 위반: 경로 침입 시도 [tts_section_generator.php:" . __LINE__ . "]";
            error_log($error_msg);
            throw new Exception($error_msg);
        }

        // Strict whitelist validation: only allow .mp3 extension
        if (!preg_match('/^[a-zA-Z0-9_-]+\.mp3$/', $safeFilename)) {
            $error_msg = "보안 위반: 허용되지 않은 파일명 형식 [tts_section_generator.php:" . __LINE__ . "]";
            error_log($error_msg);
            throw new Exception($error_msg);
        }

        // Ensure filename matches expected pattern from generateTTSFilename()
        if (!preg_match('/^essay_instruction_\d+_section_\d+_\d+\.mp3$/', $safeFilename)) {
            $error_msg = "[tts_section_generator.php:" . __LINE__ . "] Warning: Unexpected filename pattern: {$safeFilename}";
            error_log($error_msg);
        }

        // Define target directory (from requirements)
        $baseUrl = 'https://mathking.kr/audiofiles/pmemory/sections/';
        $basePath = $_SERVER['DOCUMENT_ROOT'] . '/audiofiles/pmemory/sections/';

        // Create directory if it doesn't exist (SECURITY: use 0750 for restricted access)
        if (!is_dir($basePath)) {
            if (!mkdir($basePath, 0750, true)) {
                throw new Exception("디렉토리 생성 실패: {$basePath} [tts_section_generator.php:" . __LINE__ . "]");
            }
            error_log("[tts_section_generator.php:" . __LINE__ . "] Created directory: {$basePath} with permissions 0750");
        }

        // Full file path
        $filePath = $basePath . $safeFilename;

        // Check if file already exists (timestamp should make this rare)
        if (file_exists($filePath)) {
            error_log("[tts_section_generator.php:" . __LINE__ . "] Warning: File already exists, overwriting: {$filePath}");
        }

        // Write audio data to file
        $bytesWritten = file_put_contents($filePath, $audioData);
        if ($bytesWritten === false) {
            throw new Exception("파일 쓰기 실패: {$filePath} [tts_section_generator.php:" . __LINE__ . "]");
        }

        // Set secure file permissions (SECURITY: 0640 = owner write, group read)
        if (!chmod($filePath, 0640)) {
            error_log("[tts_section_generator.php:" . __LINE__ . "] Warning: Failed to set file permissions on {$filePath}");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Audio file saved: {$filePath} ({$bytesWritten} bytes, permissions: 0640)");

        // Verify file was written correctly (clear cache first)
        clearstatcache(true, $filePath);
        if (!file_exists($filePath)) {
            throw new Exception("파일 생성 실패: 파일이 존재하지 않음 [tts_section_generator.php:" . __LINE__ . "]");
        }

        $actualSize = filesize($filePath);
        if ($actualSize !== $bytesWritten) {
            throw new Exception("파일 검증 실패: 예상 {$bytesWritten}바이트, 실제 {$actualSize}바이트 [tts_section_generator.php:" . __LINE__ . "]");
        }

        // Construct public URL
        $publicUrl = $baseUrl . $safeFilename;

        error_log("[tts_section_generator.php:" . __LINE__ . "] Audio file uploaded successfully: {$publicUrl}");

        return [
            'success' => true,
            'url' => $publicUrl,
            'error' => null
        ];

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("[tts_section_generator.php:" . __LINE__ . "] 파일 업로드 실패: {$errorMsg}");

        return [
            'success' => false,
            'url' => null,
            'error' => $errorMsg
        ];
    }
}

// ============================================================================
// MAIN ENDPOINT: POST Request Handler
// ============================================================================

/**
 * Main TTS generation endpoint handler
 *
 * Handles POST requests to generate sectioned TTS audio files from database narration.
 *
 * @route POST /books/tts_section_generator.php
 * @param int contentsid Content ID (required, positive integer)
 * @return JSON Response with success status and generation details
 *
 * Response Structure:
 * {
 *   "success": bool,
 *   "data": {
 *     "sections": ["url1.mp3", "url2.mp3"],
 *     "text_sections": ["text1", "text2"],
 *     "total_sections": 2,
 *     "success_count": 2,
 *     "failed_sections": []
 *   },
 *   "error": string|null
 * }
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    // Initialize response tracking
    $startTime = microtime(true);
    $transactionData = null; // For rollback capability

    try {
        error_log("[tts_section_generator.php:" . __LINE__ . "] POST request received");

        // ==================================================================
        // STEP 1: Parse and validate input parameters
        // ==================================================================

        // Get POST data (support both JSON and form-encoded)
        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);

        if (!$postData) {
            // Fallback to $_POST for form-encoded data
            $postData = $_POST;
        }

        // Extract and validate contentsid (REQUIRED)
        $contentsid = isset($postData['contentsid']) ? intval($postData['contentsid']) : 0;

        if ($contentsid <= 0) {
            throw new Exception("유효하지 않은 contentsid입니다. 양의 정수가 필요합니다. (입력값: {$contentsid}) [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Processing TTS generation for contentsid: {$contentsid}");

        // ==================================================================
        // STEP 2: Fetch narration from database
        // ==================================================================

        // Query database for narration1 field
        $record = $DB->get_record('abrainalignment_gptresults',
            ['contentsid' => $contentsid],
            'id, contentsid, contentstype, narration1, reflections1'
        );

        if (!$record) {
            throw new Exception("데이터를 찾을 수 없습니다. contentsid={$contentsid}에 해당하는 레코드가 없습니다. [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Database record found: id={$record->id}, contentstype={$record->contentstype}");

        // Validate narration1 field
        if (empty($record->narration1)) {
            throw new Exception("나레이션이 비어있습니다. 먼저 GPT 나레이션을 생성하세요. (contentsid={$contentsid}) [tts_section_generator.php:" . __LINE__ . "]");
        }

        $narrationText = trim($record->narration1);

        // Check for @ separator
        if (strpos($narrationText, '@') === false) {
            throw new Exception("@ 구분자가 없습니다. 나레이션에 @ 기호를 포함해야 합니다. (contentsid={$contentsid}) [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Narration text length: " . strlen($narrationText) . " characters");

        // ==================================================================
        // STEP 3: Split text into sections
        // ==================================================================

        $sections = splitTextBySeparator($narrationText);

        if (empty($sections)) {
            throw new Exception("텍스트 분할 실패: 유효한 섹션이 없습니다. [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Split narration into " . count($sections) . " sections");

        // ==================================================================
        // STEP 4: Validate section count (2-20 sections)
        // ==================================================================

        $validation = validateSectionCount($sections);

        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Section count validation passed");

        // ==================================================================
        // STEP 5: Generate TTS for each section
        // ==================================================================

        $sectionUrls = [];
        $textSections = [];
        $successCount = 0;
        $failedSections = [];
        $totalSections = count($sections);

        error_log("[tts_section_generator.php:" . __LINE__ . "] Starting TTS generation for {$totalSections} sections");

        foreach ($sections as $index => $sectionText) {
            $sectionNum = $index + 1;

            error_log("[tts_section_generator.php:" . __LINE__ . "] Processing section {$sectionNum}/{$totalSections}");

            try {
                $result = generateTTSForSection($sectionText, $contentsid, $sectionNum);

                if ($result['success']) {
                    $sectionUrls[] = $result['url'];
                    $textSections[] = $sectionText;
                    $successCount++;

                    error_log("[tts_section_generator.php:" . __LINE__ . "] Section {$sectionNum} generated successfully");
                } else {
                    $errorMsg = $result['error'] ?? 'Unknown error';
                    error_log("[tts_section_generator.php:" . __LINE__ . "] Section {$sectionNum} generation failed: {$errorMsg}");
                    $failedSections[] = $sectionNum;
                }

            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                error_log("[tts_section_generator.php:" . __LINE__ . "] Section {$sectionNum} exception: {$errorMsg}");
                $failedSections[] = $sectionNum;
            }
        }

        // ==================================================================
        // STEP 6: Check if at least one section succeeded
        // ==================================================================

        if ($successCount === 0) {
            throw new Exception("모든 섹션 생성 실패: {$totalSections}개 섹션 중 성공한 섹션이 없습니다. [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] TTS generation completed: {$successCount}/{$totalSections} sections successful");

        // ==================================================================
        // STEP 7: Build JSON data structure
        // ==================================================================

        $jsonData = [
            'mode' => 'listening_test',
            'sections' => $sectionUrls,
            'text_sections' => $textSections,
            'created_at' => time(),
            'total_sections' => $totalSections,
            'success_count' => $successCount,
            'failed_sections' => $failedSections
        ];

        $transactionData = $jsonData; // Store for potential rollback

        // ==================================================================
        // STEP 8: Save JSON to database (reflections1 field)
        // ==================================================================

        $jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($jsonString === false) {
            throw new Exception("JSON 인코딩 실패: " . json_last_error_msg() . " [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Saving JSON to database (reflections1 field)");

        // Update database record
        $record->reflections1 = $jsonString;

        $updateResult = $DB->update_record('abrainalignment_gptresults', $record);

        if (!$updateResult) {
            throw new Exception("데이터베이스 업데이트 실패: reflections1 필드 저장 실패 (contentsid={$contentsid}) [tts_section_generator.php:" . __LINE__ . "]");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] Database updated successfully: id={$record->id}");

        // ==================================================================
        // STEP 9: Build success response
        // ==================================================================

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $response = [
            'success' => true,
            'data' => $jsonData,
            'error' => null
        ];

        // Add warning if partial failure
        if (!empty($failedSections)) {
            $response['warning'] = '일부 섹션 실패: ' . implode(', ', $failedSections);
            error_log("[tts_section_generator.php:" . __LINE__ . "] Warning: Partial failure - sections " . implode(', ', $failedSections) . " failed");
        }

        error_log("[tts_section_generator.php:" . __LINE__ . "] TTS generation completed successfully in {$executionTime}ms");

        // Return JSON response
        http_response_code(200);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        // ==================================================================
        // ERROR HANDLING: Transaction-like rollback
        // ==================================================================

        $errorMsg = $e->getMessage();
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        error_log("[tts_section_generator.php:" . __LINE__ . "] TTS generation failed after {$executionTime}ms: {$errorMsg}");

        // Build error response
        $errorResponse = [
            'success' => false,
            'data' => null,
            'error' => $errorMsg
        ];

        // Return error response
        http_response_code(400);
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Terminate after handling POST request
    exit;
}

// ============================================================================
// Database Schema Reference (for documentation)
// ============================================================================
// Table: mdl_abrainalignment_gptresults
// Fields used:
// - contentsid (INT): Content identifier (WHERE clause)
// - narration1 (TEXT): GPT-generated narration with @ separators (READ)
// - reflections1 (TEXT): JSON structure with TTS section data (WRITE)
// ============================================================================
