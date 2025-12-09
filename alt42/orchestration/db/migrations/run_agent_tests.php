<?php
/**
 * run_agent_tests.php
 *
 * TestRunner 웹 인터페이스
 * 21개 에이전트 테스트 일괄 실행
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/run_agent_tests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// MOODLE_INTERNAL 정의
define('MOODLE_INTERNAL', true);

// TestRunner 경로
$testRunnerPath = __DIR__ . '/../../agents/engine_core/testing/TestRunner.php';

// 파라미터 처리
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

echo "<pre style='background:#1e1e1e; color:#d4d4d4; padding:20px; font-family:monospace;'>";
echo "=== Agent Test Runner ===\n";
echo "시작: " . date('Y-m-d H:i:s') . "\n\n";

// TestRunner 파일 존재 확인
echo "[1] TestRunner 파일 확인...\n";
if (!file_exists($testRunnerPath)) {
    echo "✗ TestRunner.php 파일 없음: {$testRunnerPath}\n";
    echo "</pre>";
    exit(1);
}
echo "✓ TestRunner.php 발견\n\n";

// TestRunner 로드
echo "[2] TestRunner 로드...\n";
try {
    require_once($testRunnerPath);
    echo "✓ TestRunner 클래스 로드 성공\n\n";
} catch (Throwable $e) {
    echo "✗ 로드 실패: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}

// 테스트 실행
echo "[3] 테스트 발견...\n";
try {
    $runner = new TestRunner(null, [
        'verbose' => false,
        'output_format' => 'text',
    ]);

    $discovered = $runner->discoverTests();
    $testsFound = 0;
    $noTests = 0;

    foreach ($discovered as $nagent => $info) {
        if ($info['test_file'] !== null) {
            $testsFound++;
            echo "  ✓ Agent" . sprintf('%02d', $nagent) . " ({$info['agent_kr_name']})\n";
        } else {
            $noTests++;
            echo "  ○ Agent" . sprintf('%02d', $nagent) . " ({$info['agent_kr_name']}) - 테스트 없음\n";
        }
    }
    echo "\n발견된 테스트: {$testsFound} / 테스트 없음: {$noTests}\n\n";

    // 테스트 실행
    echo "[4] 테스트 실행...\n";
    $runner->runAll();

    // 요약
    $summary = $runner->getSummary();
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "  결과 요약\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "  전체 에이전트: {$summary['total_agents']}\n";
    echo "  테스트 있음: {$summary['agents_with_tests']}\n";
    echo "  테스트 없음: {$summary['agents_without_tests']}\n";
    echo "  통과: {$summary['agents_passed']} | 실패: {$summary['agents_failed']}\n";
    echo "  성공률: {$summary['success_rate']}%\n";
    echo "  실행 시간: {$summary['total_duration']}ms\n\n";

    // 개별 결과
    echo "───────────────────────────────────────────────────────────────\n";
    echo "  개별 결과\n";
    echo "───────────────────────────────────────────────────────────────\n\n";

    foreach ($runner->getResults() as $nagent => $result) {
        // PHP 7.1 호환 (match → switch)
        switch ($result['status']) {
            case 'passed': $icon = '✓'; break;
            case 'failed': $icon = '✗'; break;
            case 'skipped': $icon = '○'; break;
            case 'error': $icon = '!'; break;
            default: $icon = '?';
        }

        echo sprintf(
            "  %s Agent%02d (%s): %s - P:%d F:%d W:%d\n",
            $icon,
            $nagent,
            $result['agent_kr_name'],
            strtoupper($result['status']),
            $result['passed'],
            $result['failed'],
            $result['warnings']
        );

        if (!empty($result['error'])) {
            echo "     └─ Error: " . $result['error'] . "\n";
        }
    }

    echo "\n완료: " . date('Y-m-d H:i:s') . "\n";

} catch (Throwable $e) {
    echo "✗ 테스트 실행 실패: " . $e->getMessage() . "\n";
    echo "  파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
