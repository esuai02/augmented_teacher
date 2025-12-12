<?php
/**
 * 파일 로드 진단 스크립트
 * 실제로 필요한 파일들을 로드하면서 발생하는 에러를 캡처합니다.
 */

// 에러 리포팅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$results = [];
$errors = [];

// 에러 핸들러 설정
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = [
        'errno' => $errno,
        'errstr' => $errstr,
        'errfile' => $errfile,
        'errline' => $errline
    ];
    return true;
});

// Exception 핸들러 설정
set_exception_handler(function($e) use (&$errors) {
    $errors[] = [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
});

try {
    $results['step_1'] = 'Starting Moodle config load...';

    // Moodle 환경 로드
    if (!defined('MOODLE_INTERNAL')) {
        include_once("/home/moodle/public_html/moodle/config.php");
    }
    $results['step_1'] = 'Moodle config loaded successfully';

    $results['step_2'] = 'Starting engine base path calculation...';

    // 경로 계산
    $engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';
    $results['step_2'] = 'Engine base path: ' . $engineBasePath;

    // 각 파일 순차적 로드
    $requiredFiles = [
        '/core/AbstractPersonaEngine.php',
        '/impl/BaseRuleParser.php',
        '/impl/BaseConditionEvaluator.php',
        '/impl/BaseActionExecutor.php',
        '/impl/BaseDataContext.php',
        '/impl/BaseResponseGenerator.php',
        '/config/persona_engine.config.php'
    ];

    $step = 3;
    foreach ($requiredFiles as $file) {
        $fullPath = $engineBasePath . $file;
        $results['step_' . $step] = "Loading: {$file}...";

        if (!file_exists($fullPath)) {
            throw new Exception("File not found: {$fullPath}");
        }

        require_once($fullPath);
        $results['step_' . $step] = "Loaded successfully: {$file}";
        $step++;
    }

    // Agent03PersonaEngine 로드
    $results['step_' . $step] = 'Loading Agent03PersonaEngine.php...';
    require_once(__DIR__ . '/../engine/Agent03PersonaEngine.php');
    $results['step_' . $step] = 'Agent03PersonaEngine.php loaded successfully';
    $step++;

    // 클래스 존재 확인
    $results['step_' . $step] = 'Checking class existence...';
    $classChecks = [
        'AbstractPersonaEngine' => class_exists('AbstractPersonaEngine'),
        'BaseRuleParser' => class_exists('BaseRuleParser'),
        'BaseConditionEvaluator' => class_exists('BaseConditionEvaluator'),
        'BaseActionExecutor' => class_exists('BaseActionExecutor'),
        'BaseDataContext' => class_exists('BaseDataContext'),
        'BaseResponseGenerator' => class_exists('BaseResponseGenerator'),
        'Agent03PersonaEngine' => class_exists('Agent03PersonaEngine'),
        'Agent03DataContext' => class_exists('Agent03DataContext')
    ];
    $results['step_' . $step] = $classChecks;
    $step++;

    // 엔진 인스턴스화 시도
    $results['step_' . $step] = 'Attempting to instantiate Agent03PersonaEngine...';
    $engine = new Agent03PersonaEngine('agent03');
    $results['step_' . $step] = 'Engine instantiated successfully!';
    $step++;

    // 간단한 메시지 처리 시도
    $results['step_' . $step] = 'Attempting to process a simple message...';
    $testResult = $engine->process(1, '목표를 세우고 싶어요', ['source' => 'diagnostic']);
    $results['step_' . $step] = [
        'message' => 'Process completed',
        'success' => $testResult['success'] ?? false,
        'has_response' => isset($testResult['response'])
    ];

    echo json_encode([
        'success' => true,
        'results' => $results,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'results' => $results,
        'exception' => [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'results' => $results,
        'fatal_error' => [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
