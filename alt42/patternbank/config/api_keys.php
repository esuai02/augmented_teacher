<?php
/**
 * API 키 설정 - Moodle $CFG에서 가져오기
 * $CFG->openai_api_key 설정 필요
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $CFG;

// OpenAI API 키 설정 - $CFG에서 가져오기
$openai_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($openai_key)) {
    error_log('[api_keys.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Error: API 키가 설정되지 않았습니다.');
}
define('OPENAI_API_KEY_SECURE', $openai_key);
?>