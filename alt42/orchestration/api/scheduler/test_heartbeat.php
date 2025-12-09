<?php
/**
 * Heartbeat Scheduler 테스트 스크립트
 * 마이그레이션 후 heartbeat.php 동작 확인
 * 
 * @package ALT42\Scheduler\Test
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Moodle config 체크
$moodle_available = file_exists('/home/moodle/public_html/moodle/config.php');
if ($moodle_available) {
    require_once('/home/moodle/public_html/moodle/config.php');
    global $DB, $USER;
}

echo "=== Heartbeat Scheduler Test ===\n";
echo "Started at " . date('Y-m-d H:i:s') . "\n\n";

// 1. 데이터베이스 테이블 확인
echo "1. Checking database tables...\n";
require_once(__DIR__ . '/../database/agent_data_layer.php');

$required_tables = [
    'mdl_alt42_heartbeat_log',
    'mdl_alt42_scenario_evaluation_log',
    'mdl_alt42_student_activity'
];

$tables_exist = [];
foreach ($required_tables as $table) {
    try {
        // SHOW TABLES LIKE는 prepared statement를 지원하지 않으므로 직접 문자열 사용
        $sql = "SHOW TABLES LIKE '" . addslashes($table) . "'";
        $stmt = \ALT42\Database\AgentDataLayer::executeQuery($sql);
        $exists = $stmt->rowCount() > 0;
        $tables_exist[$table] = $exists;
        echo "   {$table}: " . ($exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
    } catch (\Exception $e) {
        $tables_exist[$table] = false;
        echo "   {$table}: ✗ ERROR - " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    }
}

// 2. 뷰 확인
echo "\n2. Checking views...\n";
try {
    // INFORMATION_SCHEMA를 사용하여 VIEW 확인 (더 안전함)
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.VIEWS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'mdl_alt42_v_student_state'";
    $stmt = \ALT42\Database\AgentDataLayer::executeQuery($sql);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    $view_exists = ($result && $result['cnt'] > 0);
    echo "   mdl_alt42_v_student_state: " . ($view_exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
} catch (\Exception $e) {
    $view_exists = false;
    echo "   mdl_alt42_v_student_state: ✗ ERROR - " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
}

// 3. 의존성 파일 확인
echo "\n3. Checking dependency files...\n";
$dependencies = [
    'event_bus.php' => __DIR__ . '/../events/event_bus.php',
    'agent_data_layer.php' => __DIR__ . '/../database/agent_data_layer.php',
    'event_scenario_mapper.php' => __DIR__ . '/../mapping/event_scenario_mapper.php',
    'route.php' => __DIR__ . '/../oa/route.php',
    'event_schemas.php' => __DIR__ . '/../config/event_schemas.php',
    'rule_evaluator.php' => __DIR__ . '/../rule_engine/rule_evaluator.php'
];

$deps_exist = [];
foreach ($dependencies as $name => $path) {
    $exists = file_exists($path);
    $deps_exist[$name] = $exists;
    echo "   {$name}: " . ($exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
}

// 4. Heartbeat 실행 테스트
echo "\n4. Testing Heartbeat execution...\n";

if (!file_exists(__DIR__ . '/heartbeat.php')) {
    echo "   ✗ heartbeat.php not found\n";
    exit(1);
}

try {
    // Heartbeat 클래스 로드
    require_once(__DIR__ . '/heartbeat.php');
    
    // 인스턴스 생성 테스트
    $scheduler = new HeartbeatScheduler();
    echo "   ✓ HeartbeatScheduler instance created\n";
    
    // execute() 메서드 호출 (실제 실행은 하지 않고 구조만 확인)
    if (method_exists($scheduler, 'execute')) {
        echo "   ✓ execute() method exists\n";
    } else {
        echo "   ✗ execute() method not found\n";
    }
    
    // 실제 실행 (빈 결과가 나와도 정상)
    echo "\n5. Running Heartbeat (dry run)...\n";
    echo "   Note: This will process active students if any exist.\n";
    
    $result = $scheduler->execute();
    
    echo "\n   Result:\n";
    echo "   - Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "   - Students processed: " . ($result['students_processed'] ?? 0) . "\n";
    echo "   - Errors: " . ($result['errors'] ?? 0) . "\n";
    echo "   - Duration: " . ($result['duration_ms'] ?? 0) . " ms\n";
    
    if (isset($result['errors']) && $result['errors'] > 0) {
        echo "\n   ⚠ Warnings:\n";
        foreach ($result['results'] ?? [] as $studentId => $studentResult) {
            if (isset($studentResult['success']) && !$studentResult['success']) {
                echo "   - Student {$studentId}: " . ($studentResult['error'] ?? 'Unknown error') . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "   Stack trace:\n";
    echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
    exit(1);
}

// 5. 요약
echo "\n=== Test Summary ===\n";
$all_tables_ok = !in_array(false, $tables_exist);
$all_deps_ok = !in_array(false, $deps_exist);

echo "Tables: " . ($all_tables_ok ? "✓ ALL OK" : "✗ SOME MISSING") . "\n";
echo "View: " . ($view_exists ? "✓ OK" : "✗ MISSING") . "\n";
echo "Dependencies: " . ($all_deps_ok ? "✓ ALL OK" : "✗ SOME MISSING") . "\n";
echo "Heartbeat execution: " . (isset($result['success']) && $result['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";

if (!$all_tables_ok) {
    echo "\n⚠ Please run migrations:\n";
    echo "   php db/migrations/run_005_migration.php\n";
    echo "   php db/migrations/run_006_migration.php\n";
}

if (!$view_exists) {
    echo "\n⚠ Please run migration 006:\n";
    echo "   php db/migrations/run_006_migration.php\n";
}

echo "\nCompleted at " . date('Y-m-d H:i:s') . "\n";

if ($all_tables_ok && $view_exists && $all_deps_ok && isset($result['success']) && $result['success']) {
    exit(0);
} else {
    exit(1);
}

