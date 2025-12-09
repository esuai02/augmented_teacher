<?php
// File: verify_task4.php
// Verification script for Task 4 implementation

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Task 4 Verification Script ===\n\n";

// Check if files exist
$files = [
    'lib/MvpDatabase.php' => 'Core Database class',
    'tests/unit/MvpDatabaseQueryTest.php' => 'Query test file'
];

echo "1. File Existence Check:\n";
foreach ($files as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   ✓ {$description}: EXISTS\n";
    } else {
        echo "   ✗ {$description}: MISSING\n";
    }
}

echo "\n2. Method Verification:\n";
require_once(__DIR__ . '/lib/MvpDatabase.php');

$db = MvpDatabase::getInstance();
$requiredMethods = [
    'execute',
    'fetchOne',
    'fetchAll',
    'lastInsertId',
    'affectedRows',
    'escape'
];

foreach ($requiredMethods as $method) {
    if (method_exists($db, $method)) {
        echo "   ✓ Method {$method}() exists\n";
    } else {
        echo "   ✗ Method {$method}() MISSING\n";
    }
}

// Check for private method using reflection
$reflection = new ReflectionClass('MvpDatabase');
$methods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);
$hasGetParamTypes = false;
foreach ($methods as $method) {
    if ($method->getName() === 'getParamTypes') {
        $hasGetParamTypes = true;
        break;
    }
}

if ($hasGetParamTypes) {
    echo "   ✓ Private method getParamTypes() exists\n";
} else {
    echo "   ✗ Private method getParamTypes() MISSING\n";
}

echo "\n3. Error Format Verification:\n";
$content = file_get_contents(__DIR__ . '/lib/MvpDatabase.php');
if (strpos($content, '__FILE__') !== false && strpos($content, '__LINE__') !== false) {
    echo "   ✓ Error messages include [file:line] format\n";
} else {
    echo "   ✗ Error messages missing [file:line] format\n";
}

echo "\n4. Test Structure Verification:\n";
$testContent = file_get_contents(__DIR__ . '/tests/unit/MvpDatabaseQueryTest.php');
$testMethods = [
    'testExecuteInsert',
    'testFetchOne',
    'testFetchAll',
    'testExecuteUpdate',
    'testExecuteDelete',
    'testEscape'
];

foreach ($testMethods as $testMethod) {
    if (strpos($testContent, "public function {$testMethod}()") !== false) {
        echo "   ✓ Test {$testMethod}() exists\n";
    } else {
        echo "   ✗ Test {$testMethod}() MISSING\n";
    }
}

echo "\n=== Verification Complete ===\n";
echo "\nTo run actual tests, execute:\n";
echo "php tests/unit/MvpDatabaseQueryTest.php\n";
echo "\nOr via web:\n";
echo "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/run_query_test.php\n";
?>
