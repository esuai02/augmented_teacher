<?php
/**
 * 발표 음성 STT 변환 API (Whisper)
 * - 브라우저에서 녹음한 음성(webm)을 base64(DataURL)로 받아 Whisper로 텍스트 변환
 * - 음성 파일은 영구 저장하지 않고, 임시 파일 생성 후 즉시 삭제
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

/**
 * DataURL(base64)에서 바이너리로 디코딩
 */
function decode_data_url($dataUrl) {
    if (!is_string($dataUrl) || $dataUrl === '') return null;
    if (strpos($dataUrl, 'base64,') === false) return null;
    $parts = explode('base64,', $dataUrl, 2);
    if (count($parts) !== 2) return null;
    $b64 = $parts[1];
    $bin = base64_decode($b64, true);
    return $bin === false ? null : $bin;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $audioDataUrl = $input['audio_data'] ?? null; // data:audio/webm;base64,...
    if (empty($audioDataUrl)) {
        throw new Exception('audio_data가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $audioBin = decode_data_url($audioDataUrl);
    if ($audioBin === null) {
        throw new Exception('audio_data 형식 오류(DataURL base64) - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    // OpenAI API 키 로드
    $apiKey = null;
    $configPath = __DIR__ . '/../../config.php';
    if (file_exists($configPath)) {
        require_once($configPath);
        if (defined('OPENAI_API_KEY')) {
            $apiKey = OPENAI_API_KEY;
        }
    }
    if (!$apiKey) {
        $apiKey = get_config('local_augmented_teacher', 'openai_api_key');
    }
    if (!$apiKey) {
        throw new Exception('OpenAI API 키가 설정되지 않았습니다 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    // 임시 파일 생성 (영구 저장 금지)
    $tmpBase = tempnam(sys_get_temp_dir(), 'at_pres_');
    if ($tmpBase === false) {
        throw new Exception('임시 파일 생성 실패 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }
    $tmpFile = $tmpBase . '.webm';
    @rename($tmpBase, $tmpFile);

    $written = file_put_contents($tmpFile, $audioBin);
    if ($written === false || $written <= 0) {
        @unlink($tmpFile);
        throw new Exception('임시 파일 쓰기 실패 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    // Whisper STT 호출
    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    $postFields = [
        'model' => 'whisper-1',
        'file' => new CURLFile($tmpFile, 'audio/webm', 'presentation.webm'),
        'language' => 'ko',
        'response_format' => 'json'
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 임시 파일 삭제(필수)
    @unlink($tmpFile);

    if ($response === false || !empty($curlError)) {
        error_log("Whisper cURL Error in " . __FILE__ . ":" . __LINE__ . " - " . $curlError);
        throw new Exception('Whisper API 호출 실패: ' . $curlError . ' - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
        error_log("Whisper API Error in " . __FILE__ . ":" . __LINE__ . " - " . $errorMessage);
        throw new Exception('Whisper API 오류: ' . $errorMessage . ' - ' . basename(__FILE__) . ':' . __LINE__, $httpCode);
    }

    $data = json_decode($response, true);
    $text = $data['text'] ?? null;
    if (!is_string($text) || trim($text) === '') {
        throw new Exception('Whisper 응답에서 text를 찾을 수 없습니다 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    echo json_encode([
        'success' => true,
        'text' => $text,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    error_log("Transcribe Presentation Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());

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


