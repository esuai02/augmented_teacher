<?php
/**
 * ExamFocus 오류 진단 페이지
 * 500 오류 원인 파악용
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 모든 에러 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ExamFocus 오류 진단</h1>";
echo "<pre>";

// 1. PHP 버전 체크
echo "1. PHP 버전: " . PHP_VERSION . "\n";
echo "   - Required: 7.4+ (권장), 7.1+ (최소)\n";
echo "   - Status: " . (version_compare(PHP_VERSION, '7.1.0', '>=') ? '✅ OK' : '❌ FAIL') . "\n\n";

// 2. 필수 확장 모듈 체크
echo "2. PHP 확장 모듈:\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    echo "   - $ext: " . (extension_loaded($ext) ? '✅ Loaded' : '❌ Missing') . "\n";
}
echo "\n";

// 3. 파일 권한 체크
echo "3. 파일 권한:\n";
$files_to_check = [
    'index.php',
    'index_safe.php',
    'test.php',
    'version.php',
    'classes/service/exam_focus_service.php'
];

foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        $perms = fileperms($filepath);
        $perms_str = substr(sprintf('%o', $perms), -4);
        echo "   - $file: $perms_str " . (is_readable($filepath) ? '✅ Readable' : '❌ Not readable') . "\n";
    } else {
        echo "   - $file: ❌ Not found\n";
    }
}
echo "\n";

// 4. Moodle config.php 찾기
echo "4. Moodle Integration:\n";
$config_paths = [
    __DIR__ . '/../../../../config.php',
    __DIR__ . '/../../../config.php',
    __DIR__ . '/../../config.php',
    '/home/moodle/public_html/moodle/config.php'
];

$moodle_found = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        echo "   - Moodle config.php: ✅ Found at $path\n";
        $moodle_found = true;
        
        // config.php를 안전하게 포함
        try {
            // MOODLE_INTERNAL 정의
            if (!defined('MOODLE_INTERNAL')) {
                define('MOODLE_INTERNAL', true);
            }
            
            // CLI_SCRIPT 정의 (웹 요청이지만 에러 방지용)
            if (!defined('CLI_SCRIPT')) {
                define('CLI_SCRIPT', false);
            }
            
            // config.php 포함
            @include_once($path);
            
            if (isset($CFG)) {
                echo "   - CFG object: ✅ Loaded\n";
                echo "   - wwwroot: " . (isset($CFG->wwwroot) ? $CFG->wwwroot : 'Not set') . "\n";
                echo "   - dirroot: " . (isset($CFG->dirroot) ? $CFG->dirroot : 'Not set') . "\n";
            } else {
                echo "   - CFG object: ❌ Not loaded\n";
            }
        } catch (Exception $e) {
            echo "   - Error loading config.php: " . $e->getMessage() . "\n";
        }
        break;
    }
}

if (!$moodle_found) {
    echo "   - Moodle config.php: ❌ Not found\n";
}
echo "\n";

// 5. 데이터베이스 연결 테스트
echo "5. Database Connection:\n";

// MathKing DB
try {
    $dsn = "mysql:host=58.180.27.46;dbname=mathking;charset=utf8mb4";
    $pdo = new PDO($dsn, 'moodle', '@MCtrigd7128', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "   - MathKing DB: ✅ Connected\n";
    
    // 테이블 존재 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'mdl_user'");
    if ($stmt->fetch()) {
        echo "   - mdl_user table: ✅ Exists\n";
    }
} catch (PDOException $e) {
    echo "   - MathKing DB: ❌ " . $e->getMessage() . "\n";
}

// Alt42t DB
try {
    $alt42t = new PDO("mysql:host=127.0.0.1;dbname=alt42t;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    echo "   - Alt42t DB: ✅ Connected\n";
} catch (PDOException $e) {
    echo "   - Alt42t DB: ⚠️ " . $e->getMessage() . " (Optional)\n";
}
echo "\n";

// 6. 클래스 로딩 테스트
echo "6. Class Loading:\n";
$class_file = __DIR__ . '/classes/service/exam_focus_service.php';
if (file_exists($class_file)) {
    echo "   - exam_focus_service.php: ✅ Found\n";
    
    // 네임스페이스 체크
    $content = file_get_contents($class_file);
    if (strpos($content, 'namespace local_examfocus\\service;') !== false) {
        echo "   - Namespace: ✅ Correct\n";
    } else {
        echo "   - Namespace: ❌ Incorrect or missing\n";
    }
    
    // 클래스 로드 시도
    try {
        require_once($class_file);
        if (class_exists('\\local_examfocus\\service\\exam_focus_service')) {
            echo "   - Class loading: ✅ Success\n";
        } else {
            echo "   - Class loading: ❌ Class not found\n";
        }
    } catch (Exception $e) {
        echo "   - Class loading: ❌ " . $e->getMessage() . "\n";
    }
} else {
    echo "   - exam_focus_service.php: ❌ Not found\n";
}
echo "\n";

// 7. 세션 체크
echo "7. Session:\n";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "   - Session status: " . (session_status() == PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Not active') . "\n";
echo "   - Session ID: " . session_id() . "\n";
echo "\n";

// 8. 메모리 및 실행 시간
echo "8. PHP Limits:\n";
echo "   - Memory limit: " . ini_get('memory_limit') . "\n";
echo "   - Max execution time: " . ini_get('max_execution_time') . " seconds\n";
echo "   - Post max size: " . ini_get('post_max_size') . "\n";
echo "   - Upload max filesize: " . ini_get('upload_max_filesize') . "\n";
echo "\n";

// 9. 오류 로그 위치
echo "9. Error Logs:\n";
echo "   - Error log: " . ini_get('error_log') . "\n";
echo "   - Display errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
echo "   - Error reporting: " . error_reporting() . "\n";
echo "\n";

// 10. 제안사항
echo "10. Recommendations:\n";
if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    echo "   ❗ PHP 버전을 7.1 이상으로 업그레이드하세요.\n";
}
if (!$moodle_found) {
    echo "   ❗ Moodle과 독립적으로 실행하려면 index_safe.php를 사용하세요.\n";
}
echo "   ℹ️ 500 오류가 계속되면 error_log 파일을 확인하세요.\n";
echo "   ℹ️ 안전 모드 페이지: index_safe.php\n";
echo "   ℹ️ AJAX 엔드포인트: ajax/get_recommendation.php\n";

echo "</pre>";

// 마지막 PHP 오류 확인
$last_error = error_get_last();
if ($last_error) {
    echo "<h3>Last PHP Error:</h3>";
    echo "<pre>";
    print_r($last_error);
    echo "</pre>";
}