<?php
/**
 * TTS API 프록시
 * 서버 측에서 OpenAI TTS API를 호출하여 CORS 문제 해결 및 API 키 보안 유지
 *
 * 파일 위치: /books/tts_api_proxy.php
 * 호출 방법: POST 요청으로 text, voice 파라미터 전송
 *
 * 관련 DB: 없음 (API 프록시 전용)
 */

// Moodle config.php 로드하여 $CFG->openai_api_key 사용
include_once("/home/moodle/public_html/moodle/config.php");
global $CFG, $USER;

// 로그인 필수
require_login();

// CORS 헤더 (같은 도메인이라도 명시적으로 설정)
header('Content-Type: application/json; charset=utf-8');

// 에러 핸들러 설정
function sendError($message, $code = 400, $details = '') {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'details' => $details,
        'file' => 'tts_api_proxy.php',
        'line' => debug_backtrace()[0]['line'] ?? 0
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('POST 요청만 허용됩니다.', 405);
}

// 필수 파라미터 확인
$text = isset($_POST['text']) ? trim($_POST['text']) : '';
$voice = isset($_POST['voice']) ? trim($_POST['voice']) : 'alloy';

if (empty($text)) {
    sendError('텍스트가 비어있습니다.', 400);
}

// 텍스트 길이 제한 (OpenAI TTS API 제한: 4096자)
if (mb_strlen($text) > 4096) {
    $text = mb_substr($text, 0, 4096);
}

// API 키 가져오기 ($CFG에서)
$apiKey = '';
if (isset($CFG->openai_api_key) && !empty($CFG->openai_api_key)) {
    $apiKey = $CFG->openai_api_key;
} else {
    sendError('API 키가 설정되지 않았습니다. Moodle config.php에서 $CFG->openai_api_key를 확인하세요.', 500);
}

// 음성 옵션 검증
$validVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
if (!in_array($voice, $validVoices)) {
    $voice = 'alloy';
}

// OpenAI TTS API 호출
$apiUrl = 'https://api.openai.com/v1/audio/speech';
$requestData = json_encode([
    'model' => 'tts-1',
    'voice' => $voice,
    'input' => $text
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $requestData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 90, // TTS는 긴 타임아웃 필요
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// cURL 오류 확인
if ($curlError) {
    sendError('API 요청 실패: ' . $curlError, 500, 'cURL 에러');
}

// HTTP 응답 코드 확인
if ($httpCode !== 200) {
    // 에러 응답 파싱 시도
    $errorData = json_decode($response, true);
    $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : '알 수 없는 오류';
    sendError("OpenAI API 오류 (HTTP $httpCode): $errorMessage", $httpCode, $response);
}

// 성공: 오디오 데이터를 Base64로 인코딩하여 반환
$audioBase64 = base64_encode($response);

echo json_encode([
    'success' => true,
    'audio' => $audioBase64,
    'format' => 'mp3',
    'voice' => $voice,
    'text_length' => mb_strlen($text)
], JSON_UNESCAPED_UNICODE);
?>
