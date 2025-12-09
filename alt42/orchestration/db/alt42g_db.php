<?php
/**
 * ALT42G DB helper: returns PDO for the dedicated alt42g_ database
 */

// Load shared DB credentials from omniui/config.php
$rootDir = dirname(__DIR__, 2);
$omniConfig = $rootDir . '/omniui/config.php';
if (!file_exists($omniConfig)) {
    throw new RuntimeException('omniui/config.php not found');
}
include_once($omniConfig);

if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
    throw new RuntimeException('DB_HOST/DB_USER/DB_PASS not defined');
}

/**
 * Get PDO for alt42g_ database
 * @param string $dbname database name starting with alt42g_
 * @return PDO
 */
function alt42g_get_pdo($dbname = 'alt42g_mbti') {
    if (strpos($dbname, 'alt42g_') !== 0) {
        throw new InvalidArgumentException('Target DB name must start with alt42g_');
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . $dbname . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

?>


