<?php
/**
 * run_tests.php
 *
 * TestRunner 웹 인터페이스 (독립 실행)
 * Moodle 세션 없이 테스트 실행
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.2
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/testing/run_tests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// MOODLE_INTERNAL 정의 (engine_core 파일들을 위해)
define('MOODLE_INTERNAL', true);

// 파라미터 처리
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';
$verbose = isset($_GET['verbose']) ? (bool)$_GET['verbose'] : false;
$agentsParam = isset($_GET['agents']) ? $_GET['agents'] : null;
$skipParam = isset($_GET['skip']) ? $_GET['skip'] : null;

// 에이전트 목록 파싱
$agents = null;
if ($agentsParam) {
    $agents = array_map('intval', explode(',', $agentsParam));
    $agents = array_filter($agents, function($n) { return $n >= 1 && $n <= 21; });
}

$skipAgents = [];
if ($skipParam) {
    $skipAgents = array_map('intval', explode(',', $skipParam));
    $skipAgents = array_filter($skipAgents, function($n) { return $n >= 1 && $n <= 21; });
}

// TestRunner 로드 시도
try {
    require_once(__DIR__ . '/TestRunner.php');

    $options = [
        'verbose' => $verbose,
        'agents' => $agents,
        'skip_agents' => $skipAgents,
        'output_format' => in_array($format, ['html', 'json', 'text']) ? $format : 'html',
        'timeout' => 30,
        'stop_on_failure' => false,
    ];

    $runner = new TestRunner(null, $options);
    $runner->discoverTests();
    $runner->runAll();

    // 출력
    switch ($format) {
        case 'json':
            header('Content-Type: application/json; charset=utf-8');
            echo $runner->renderJson();
            break;

        case 'text':
            header('Content-Type: text/plain; charset=utf-8');
            echo $runner->renderText();
            break;

        default:
            header('Content-Type: text/html; charset=utf-8');
            echo $runner->renderHtml();
            break;
    }
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== TestRunner Error ===\n";
    echo "File: " . __FILE__ . ":" . __LINE__ . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
