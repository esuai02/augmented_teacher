<?php
/**
 * API 설정 파일
 * 보안상 중요한 정보를 별도 파일로 관리
 *
 * 주의: 이 파일은 버전 관리 시스템에 포함되지 않아야 합니다.
 * .gitignore에 추가하세요.
 */

// OpenAI API 키 - 환경변수에서 먼저 읽고, 없으면 하드코딩된 값 사용
// 실제 운영 환경에서는 환경변수 사용을 권장합니다.
// 서버에서: export OPENAI_API_KEY="your-api-key-here"

$apiKey = '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';
define('OPENAI_API_KEY', $apiKey);

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