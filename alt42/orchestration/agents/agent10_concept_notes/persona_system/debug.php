<?php
/**
 * Agent10 디버그 스크립트
 * 파일: agent10_concept_notes/persona_system/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== Agent10 디버그 시작 ===\n\n";

// 경로 정보
echo "1. 경로 정보:\n";
$corePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/';
$implPath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/impl/';
echo "   Core Path: {$corePath}\n";
echo "   Impl Path: {$implPath}\n";
echo "   Current Dir: " . __DIR__ . "\n\n";

// 파일 존재 확인
echo "2. 파일 존재 확인:\n";
$files = [
    'IRuleParser.php' => $corePath . 'IRuleParser.php',
    'IConditionEvaluator.php' => $corePath . 'IConditionEvaluator.php',
    'IActionExecutor.php' => $corePath . 'IActionExecutor.php',
    'IDataContext.php' => $corePath . 'IDataContext.php',
    'IResponseGenerator.php' => $corePath . 'IResponseGenerator.php',
    'AbstractPersonaEngine.php' => $corePath . 'AbstractPersonaEngine.php'
];

foreach ($files as $name => $path) {
    $exists = file_exists($path) ? 'EXISTS' : 'NOT FOUND';
    echo "   {$name}: {$exists}\n";
}
echo "\n";

// 하나씩 로드 테스트
echo "3. 파일 로드 테스트:\n";
try {
    echo "   - Loading IRuleParser.php... ";
    require_once($corePath . 'IRuleParser.php');
    echo "OK\n";

    echo "   - Loading IConditionEvaluator.php... ";
    require_once($corePath . 'IConditionEvaluator.php');
    echo "OK\n";

    echo "   - Loading IActionExecutor.php... ";
    require_once($corePath . 'IActionExecutor.php');
    echo "OK\n";

    echo "   - Loading IDataContext.php... ";
    require_once($corePath . 'IDataContext.php');
    echo "OK\n";

    echo "   - Loading IResponseGenerator.php... ";
    require_once($corePath . 'IResponseGenerator.php');
    echo "OK\n";

    echo "   - Loading AbstractPersonaEngine.php... ";
    require_once($corePath . 'AbstractPersonaEngine.php');
    echo "OK\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// 클래스 확인
echo "4. 클래스 존재 확인:\n";
$classes = [
    'AugmentedTeacher\\PersonaEngine\\Core\\IRuleParser',
    'AugmentedTeacher\\PersonaEngine\\Core\\IConditionEvaluator',
    'AugmentedTeacher\\PersonaEngine\\Core\\IActionExecutor',
    'AugmentedTeacher\\PersonaEngine\\Core\\IDataContext',
    'AugmentedTeacher\\PersonaEngine\\Core\\IResponseGenerator',
    'AugmentedTeacher\\PersonaEngine\\Core\\AbstractPersonaEngine'
];

foreach ($classes as $class) {
    $exists = interface_exists($class) || class_exists($class) ? 'EXISTS' : 'NOT FOUND';
    echo "   {$class}: {$exists}\n";
}
echo "\n";

// class_alias 테스트
echo "5. Class Alias 테스트:\n";
try {
    if (class_exists('AugmentedTeacher\\PersonaEngine\\Core\\AbstractPersonaEngine')) {
        class_alias('AugmentedTeacher\\PersonaEngine\\Core\\AbstractPersonaEngine', 'TestAbstractEngine');
        echo "   AbstractPersonaEngine alias: OK\n";
    }

    if (interface_exists('AugmentedTeacher\\PersonaEngine\\Core\\IRuleParser')) {
        class_alias('AugmentedTeacher\\PersonaEngine\\Core\\IRuleParser', 'TestIRuleParser');
        echo "   IRuleParser alias: OK\n";
    }
} catch (Exception $e) {
    echo "   FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

// 엔진 로드 테스트
echo "6. Agent10PersonaEngine 로드 테스트:\n";
try {
    require_once(__DIR__ . '/engine/Agent10PersonaEngine.php');
    echo "   Engine loaded: OK\n";

    if (class_exists('Agent10PersonaEngine')) {
        echo "   Class exists: OK\n";
    } else {
        echo "   Class exists: NOT FOUND\n";
    }
} catch (Exception $e) {
    echo "   FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

echo "\n=== 디버그 완료 ===\n";
