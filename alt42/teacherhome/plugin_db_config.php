<?php
/**
 * 플러그인 데이터베이스 설정 파일
 * Moodle 설정을 사용하여 데이터베이스에 연결
 */

// Moodle config 파일 포함
require_once("/home/moodle/public_html/moodle/config.php");

// Moodle의 설정을 사용하여 상수 정의
define('DB_HOST', $CFG->dbhost);
define('DB_NAME', $CFG->dbname);
define('DB_USER', $CFG->dbuser);
define('DB_PASS', $CFG->dbpass);
define('DB_CHARSET', 'utf8mb4');

// 테이블 접두사
define('DB_PREFIX', $CFG->prefix);

// PDO DSN 설정 (마이그레이션 스크립트 호환성)
$dbtype = $CFG->dbtype;
if ($dbtype === 'mysqli' || $dbtype === 'mariadb') {
    $dbtype = 'mysql';
}
$dsn = $dbtype . ":host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$username = DB_USER;
$password = DB_PASS;

/**
 * PDO 데이터베이스 연결 함수
 */
function getDBConnection() {
    try {
        // Moodle DB 타입 확인 (mysqli 또는 mariadb)
        global $CFG;
        $dbtype = $CFG->dbtype;
        
        // mysqli나 mariadb는 모두 mysql PDO 드라이버 사용
        if ($dbtype === 'mysqli' || $dbtype === 'mariadb') {
            $dbtype = 'mysql';
        }
        
        $dsn = $dbtype . ":host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("데이터베이스 연결에 실패했습니다: " . $e->getMessage());
    }
}
?>