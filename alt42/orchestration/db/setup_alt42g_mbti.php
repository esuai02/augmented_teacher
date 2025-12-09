<?php
/**
 * ALT42G MBTI Database Setup Script
 * - Creates a dedicated database for MBTI logs (name starts with alt42g_)
 * - Does NOT modify, drop, or create any other databases
 */

// Diagnostic output for HTTP 500 cases
@ini_set('display_errors', '1');
@error_reporting(E_ALL);
header('Content-Type: text/plain; charset=UTF-8');

// Simple file logger (best-effort)
function alt42g_log($message) {
    try {
        $logdir = dirname(__DIR__) . '/tmp';
        if (!is_dir($logdir)) { @mkdir($logdir, 0775, true); }
        $logfile = $logdir . '/alt42g_setup.log';
        @file_put_contents($logfile, '[' . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
    } catch (Throwable $e) {
        // ignore
    }
}

// Load shared DB credentials from omniui/config.php
$rootDir = dirname(__DIR__, 2); // .../alt42
$omniConfig = $rootDir . '/omniui/config.php';
if (!file_exists($omniConfig)) {
    alt42g_log('Missing omniui/config.php at ' . $omniConfig);
    echo 'omniui/config.php not found at ' . htmlspecialchars($omniConfig);
    exit;
}
include_once($omniConfig);

// Required constants from config.php: DB_HOST, DB_USER, DB_PASS
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
    alt42g_log('DB constants missing');
    echo 'DB connection constants (DB_HOST/DB_USER/DB_PASS) are not defined.';
    exit;
}

// Target database name (must start with alt42g_)
$targetDb = 'alt42g_mbti';

try {
    // Connect without selecting a default database
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    alt42g_log('Connected to MySQL host=' . DB_HOST . ' user=' . DB_USER);

    // Try using DB first (in case it already exists)
    $dbReady = false;
    try {
        $pdo->exec("USE `{$targetDb}`");
        $dbReady = true;
    } catch (Throwable $eUse) {
        // Try to create then use
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$targetDb}`");
            $dbReady = true;
        } catch (Throwable $eCreate) {
            alt42g_log('DB create/use failed: ' . $eCreate->getMessage());
            echo "❌ alt42g 데이터베이스 생성/선택 실패: " . $eCreate->getMessage() . "\n\n";
            echo "가능한 원인:\n";
            echo " - DB 사용자('" . DB_USER . "')에게 CREATE/USAGE 권한이 없음\n";
            echo " - DB 호스트('" . DB_HOST . "') 권한 정책 또는 연결 제한\n\n";
            echo "해결 방법(관리자):\n";
            echo " 1) 아래 명령을 관리자 권한으로 MySQL에서 실행:\n";
            echo "    CREATE DATABASE `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
            echo "    GRANT ALL PRIVILEGES ON `{$targetDb}`.* TO '" . DB_USER . "'@'%';\n";
            echo "    FLUSH PRIVILEGES;\n\n";
            echo " 2) 이후 이 페이지를 새로고침하여 테이블 생성을 진행하세요.\n";
            exit;
        }
    }

    // Create tables (idempotent)
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `mbti_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` BIGINT UNSIGNED NOT NULL,
  `mbti` CHAR(4) NOT NULL,
  `timecreated` INT UNSIGNED NOT NULL,
  `source` VARCHAR(64) NOT NULL DEFAULT 'orchestration7',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_userid_time` (`userid`, `timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    alt42g_log('Tables ensured');
    echo '✅ alt42g MBTI database and tables are ready (DB: ' . htmlspecialchars($targetDb) . ').';
} catch (Throwable $e) {
    alt42g_log('Setup failed: ' . $e->getMessage());
    echo '❌ Setup failed: ' . htmlspecialchars($e->getMessage()) . "\n";
    echo 'DSN host=' . DB_HOST . ', user=' . DB_USER . "\n";
}

?>


