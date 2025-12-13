<?php
// OpenAI API 설정
// 실제 운영 환경에서는 이 파일을 .gitignore에 추가하고
// 환경변수나 보안된 설정 파일에서 읽어오도록 수정하세요

include_once("/home/moodle/public_html/moodle/config.php");
global $CFG;

/**
 * teachingsupport 공통 OpenAI 키 로더
 * - 요구사항: $CFG->openai_api_key를 단일 진실 소스로 사용
 * - 운영: OPENAI_API_KEY 환경변수 값을 $CFG에 주입(또는 보정)
 */

if (!function_exists('is_valid_openai_api_key')) {
    function is_valid_openai_api_key($key) {
        if (!is_string($key)) return false;
        $key = trim($key);
        if ($key === '') return false;
        $upper = strtoupper($key);
        if ($upper === 'OPENAI_API_KEY' || $upper === 'YOUR_OPENAI_API_KEY' || $upper === 'YOUR_API_KEY_HERE') {
            return false;
        }
        return (strpos($key, 'sk-') === 0) && (strlen($key) >= 20);
    }
}

if (!function_exists('get_openai_api_key')) {
    function get_openai_api_key() {
        global $CFG;
        $cfgKey = (isset($CFG) && is_object($CFG) && isset($CFG->openai_api_key)) ? $CFG->openai_api_key : '';
        if (is_valid_openai_api_key($cfgKey)) {
            return trim($cfgKey);
        }

        $envKey = getenv('OPENAI_API_KEY');
        if (is_valid_openai_api_key($envKey)) {
            if (!isset($CFG) || !is_object($CFG)) {
                $CFG = new stdClass();
            }
            $CFG->openai_api_key = trim($envKey);
            return $CFG->openai_api_key;
        }

        return '';
    }
}

// $CFG 준비 및 env_only 보정 주입
if (!isset($CFG) || !is_object($CFG)) {
    $CFG = new stdClass();
}
if (!is_valid_openai_api_key($CFG->openai_api_key ?? '')) {
    $CFG->openai_api_key = get_openai_api_key();
}

// 호출부 호환: 일부 파일은 OPENAI_API_KEY 상수를 사용
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', $CFG->openai_api_key);
}
if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', 'gpt-4o'); // o3 모델 출시 시 'o3'로 변경
}

$secret_key = $CFG->openai_api_key;

// 데이터베이스 테이블 이름 (필요시)
define('TABLE_TEACHING_SOLUTIONS', 'teaching_solutions');
?> 