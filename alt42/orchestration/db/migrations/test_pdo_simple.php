<?php
/**
 * PDO 연결 및 테이블 생성 테스트
 * 최소한의 진단 스크립트
 *
 * @created 2025-12-03
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/test_pdo_simple.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background:#1e1e1e; color:#d4d4d4; padding:20px; font-family:monospace;'>";
echo "=== PDO 테이블 생성 테스트 ===\n";
echo "시작: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Moodle config 로드
echo "[1] Moodle config 로드...\n";
$config_path = '/home/moodle/public_html/moodle/config.php';
if (!file_exists($config_path)) {
    echo "✗ config 파일 없음\n</pre>";
    exit(1);
}
require_once($config_path);
global $CFG;
echo "✓ dbhost: {$CFG->dbhost}, dbname: {$CFG->dbname}\n\n";

// Step 2: PDO 직접 연결
echo "[2] PDO 직접 연결...\n";
try {
    $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ PDO 연결 성공\n\n";
} catch (PDOException $e) {
    echo "✗ PDO 연결 실패: " . $e->getMessage() . "\n</pre>";
    exit(1);
}

// Step 3: 테이블 존재 확인 함수
function checkTable($pdo, $dbname, $table) {
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dbname, $table]);
    $row = $stmt->fetch();
    return (int)$row['cnt'] > 0;
}

// Step 4: 테스트 테이블 생성
$test_table = 'mdl_at_test_pdo_' . time();
echo "[3] 테스트 테이블 생성: {$test_table}\n";

$create_sql = "
    CREATE TABLE IF NOT EXISTS `{$test_table}` (
        `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `test_field` VARCHAR(100) NOT NULL,
        `created_at` INT(10) UNSIGNED NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    echo "  SQL 길이: " . strlen($create_sql) . " bytes\n";

    // 실행 전 확인
    $exists_before = checkTable($pdo, $CFG->dbname, $test_table);
    echo "  실행 전 존재: " . ($exists_before ? "예" : "아니오") . "\n";

    // SQL 실행
    $pdo->exec($create_sql);
    echo "  ✓ SQL 실행 완료\n";

    // 실행 후 확인
    $exists_after = checkTable($pdo, $CFG->dbname, $test_table);
    echo "  실행 후 존재: " . ($exists_after ? "예" : "아니오") . "\n";

    if ($exists_after) {
        echo "✓ 테스트 테이블 생성 성공!\n\n";

        // 데이터 삽입 테스트
        echo "[4] 데이터 삽입 테스트...\n";
        $insert_sql = "INSERT INTO `{$test_table}` (test_field, created_at) VALUES (?, ?)";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute(['test_value', time()]);
        echo "✓ 데이터 삽입 성공\n\n";

        // 테이블 삭제
        echo "[5] 테스트 테이블 삭제...\n";
        $pdo->exec("DROP TABLE IF EXISTS `{$test_table}`");
        echo "✓ 테스트 테이블 삭제 완료\n\n";
    } else {
        echo "✗ 테이블 생성 실패 (확인 불가)\n\n";
    }

} catch (PDOException $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
    echo "  오류 코드: " . $e->getCode() . "\n\n";
}

// Step 5: AgentDataLayer 테스트
echo "[6] AgentDataLayer 테스트...\n";
$adl_path = __DIR__ . '/../../api/database/agent_data_layer.php';
if (file_exists($adl_path)) {
    require_once($adl_path);

    try {
        $conn = ALT42\Database\AgentDataLayer::getConnection();
        echo "✓ AgentDataLayer 연결 성공\n\n";

        // 실제 테이블 생성 테스트
        $test_table2 = 'mdl_at_test_adl_' . time();
        echo "[7] AgentDataLayer로 테이블 생성: {$test_table2}\n";

        $create_sql2 = "
            CREATE TABLE IF NOT EXISTS `{$test_table2}` (
                `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `test_field` VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        ALT42\Database\AgentDataLayer::executeQuery($create_sql2);
        echo "  ✓ executeQuery 완료\n";

        $exists = checkTable($pdo, $CFG->dbname, $test_table2);
        echo "  테이블 존재: " . ($exists ? "예" : "아니오") . "\n";

        if ($exists) {
            echo "✓ AgentDataLayer 테이블 생성 성공!\n";
            $pdo->exec("DROP TABLE IF EXISTS `{$test_table2}`");
            echo "✓ 테스트 테이블 삭제 완료\n\n";
        } else {
            echo "✗ AgentDataLayer 테이블 생성 실패\n\n";
        }

    } catch (Exception $e) {
        echo "✗ AgentDataLayer 오류: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✗ AgentDataLayer 파일 없음: {$adl_path}\n\n";
}

// Step 6: 실제 마이그레이션 테이블 상태 확인
echo "[8] 마이그레이션 테이블 상태 확인...\n";
$migration_tables = [
    'mdl_at_agent_messages',
    'mdl_at_agent_persona_state',
    'mdl_at_agent_transitions',
    'mdl_at_agent_heartbeat',
    'mdl_at_agent_subscriptions',
    'mdl_at_agent_request_response',
    'mdl_at_agent_communication_log',
    'mdl_at_agent_collaboration'
];

$existing = 0;
foreach ($migration_tables as $table) {
    $exists = checkTable($pdo, $CFG->dbname, $table);
    $icon = $exists ? "✓" : "✗";
    echo "  {$icon} {$table}\n";
    if ($exists) $existing++;
}

echo "\n결과: {$existing}/" . count($migration_tables) . " 테이블 존재\n";
echo "\n완료: " . date('Y-m-d H:i:s') . "\n";
echo "</pre>";
