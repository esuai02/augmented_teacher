<?php
// Shining Stars 설정 파일
// PHP 7.3 호환

// Moodle 설정 파일 포함
require_once('/home/moodle/public_html/moodle/config.php');

// 데이터베이스 설정 (Moodle DB 사용)
if (!defined('SS_DB_PREFIX')) {
    define('SS_DB_PREFIX', 'ss_');
}

// OpenAI API 설정
if (!defined('OPENAI_API_KEY')) {
    // 환경 변수에서 먼저 확인
    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) {
        // config.php의 설정 사용
        $apiKey = 'sk-proj-YOUR_API_KEY_HERE';
    }
    define('OPENAI_API_KEY', $apiKey);
}

if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', 'gpt-4');
}

// 애플리케이션 설정
define('SS_VERSION', '1.0.0');
define('SS_DEBUG', false);  // 프로덕션에서는 false
define('SS_TIMEZONE', 'Asia/Seoul');

// 보안 설정
define('SS_SESSION_TIMEOUT', 7200);  // 2시간
define('SS_MAX_UPLOAD_SIZE', 10485760);  // 10MB
define('SS_ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'gif'));

// API 제한 설정
define('SS_API_RATE_LIMIT', 60);  // 분당 요청 수
define('SS_AI_RATE_LIMIT', 10);   // AI API 분당 요청 수

// 경로 설정
define('SS_ROOT', dirname(dirname(__FILE__)));
define('SS_CLASSES', SS_ROOT . '/classes');
define('SS_INCLUDES', SS_ROOT . '/includes');
define('SS_API', SS_ROOT . '/api');
define('SS_ASSETS', SS_ROOT . '/assets');
define('SS_LOGS', SS_ROOT . '/logs');

// 로그 설정
define('SS_LOG_LEVEL', 'info');  // debug, info, warning, error
define('SS_LOG_FILE', SS_LOGS . '/app.log');
define('SS_ERROR_LOG', SS_LOGS . '/error.log');
define('SS_AI_LOG', SS_LOGS . '/ai_usage.log');

// 기능 플래그
define('SS_FEATURE_TEACHER_DASHBOARD', true);
define('SS_FEATURE_AI_INSIGHTS', true);
define('SS_FEATURE_ACHIEVEMENTS', true);

// 시간대 설정
date_default_timezone_set(SS_TIMEZONE);

// 에러 보고 설정
if (SS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

// 세션 설정
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // HTTPS 환경에서만
ini_set('session.gc_maxlifetime', SS_SESSION_TIMEOUT);

// 자동 로드 함수
spl_autoload_register(function ($class) {
    $file = SS_CLASSES . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 공통 함수 포함
require_once SS_INCLUDES . '/functions.php';

// Moodle 인증 확인
require_login();

// CSRF 보호
require_sesskey();