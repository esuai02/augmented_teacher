<?php
/**
 * API 설정 파일
 * 보안상 중요한 정보를 별도 파일로 관리
 *
 * 주의: 이 파일은 버전 관리 시스템에 포함되지 않아야 합니다.
 * .gitignore에 추가하세요.
 *
 * 파일 위치: /books/api_config.php
 * 관련 DB: 없음 (설정 파일)
 */

// Moodle config.php 로드 (아직 로드되지 않은 경우에만)
if (!isset($CFG)) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $CFG;

// OpenAI API 키 - Moodle $CFG에서 가져오기 (보안 강화)
// Moodle config.php에 다음과 같이 설정:
// $CFG->openai_api_key = 'your-api-key-here';
$apiKey = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';

if (empty($apiKey)) {
    // 폴백: 환경변수에서 읽기 시도
    $apiKey = getenv('OPENAI_API_KEY');
}

if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', $apiKey);
}

// TTS 설정
define('TTS_MODEL', 'tts-1');
define('TTS_VOICE', 'alloy'); // alloy, echo, fable, onyx, nova, shimmer 중 선택

// GPT 모델 설정
define('GPT_MODEL', 'gpt-5.1-chat-latest');
define('GPT_MAX_TOKENS', 8000);
define('GPT_TEMPERATURE', 0.2);

// 오디오 파일 저장 경로
define('AUDIO_UPLOAD_PATH', '/home/moodle/public_html/Contents/audiofiles/pmemory/');
define('AUDIO_URL_BASE', 'https://mathking.kr/Contents/audiofiles/pmemory/');

// 디버그 모드 (개발 시에만 true로 설정)
define('DEBUG_MODE', true);

// API 타임아웃 설정
define('API_TIMEOUT', 60); // 초 단위
define('TTS_API_TIMEOUT', 90); // TTS는 더 긴 타임아웃

?>