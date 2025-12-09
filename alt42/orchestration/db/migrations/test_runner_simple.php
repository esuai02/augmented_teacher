<?php
/**
 * test_runner_simple.php
 *
 * TestRunner 단순 테스트
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/test_runner_simple.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background:#1e1e1e; color:#d4d4d4; padding:20px; font-family:monospace;'>";
echo "=== TestRunner 단순 테스트 ===\n";
echo "시작: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: MOODLE_INTERNAL 정의
echo "[1] MOODLE_INTERNAL 정의...\n";
define('MOODLE_INTERNAL', true);
echo "✓ MOODLE_INTERNAL = " . (defined('MOODLE_INTERNAL') ? 'true' : 'false') . "\n\n";

// Step 2: engine_config.php 로드
echo "[2] engine_config.php 로드...\n";
$configPath = __DIR__ . '/../../agents/engine_core/config/engine_config.php';
echo "  경로: {$configPath}\n";
echo "  존재: " . (file_exists($configPath) ? '예' : '아니오') . "\n";

if (file_exists($configPath)) {
    try {
        require_once($configPath);
        echo "✓ engine_config.php 로드 성공\n";

        // AGENT_INFO 확인
        if (defined('AGENT_INFO')) {
            $agentInfo = AGENT_INFO;
            echo "  에이전트 정보: " . count($agentInfo) . "개 정의됨\n";
        }
    } catch (Throwable $e) {
        echo "✗ engine_config.php 로드 실패: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ engine_config.php 파일 없음\n";
}
echo "\n";

// Step 3: BasePersonaTest.php 로드
echo "[3] BasePersonaTest.php 로드...\n";
$basePath = __DIR__ . '/../../agents/engine_core/testing/BasePersonaTest.php';
echo "  경로: {$basePath}\n";
echo "  존재: " . (file_exists($basePath) ? '예' : '아니오') . "\n";

if (file_exists($basePath)) {
    try {
        require_once($basePath);
        echo "✓ BasePersonaTest.php 로드 성공\n";
        echo "  클래스 존재: " . (class_exists('BasePersonaTest') ? '예' : '아니오') . "\n";
    } catch (Throwable $e) {
        echo "✗ BasePersonaTest.php 로드 실패: " . $e->getMessage() . "\n";
        echo "  파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
echo "\n";

// Step 4: TestRunner.php 로드
echo "[4] TestRunner.php 로드...\n";
$runnerPath = __DIR__ . '/../../agents/engine_core/testing/TestRunner.php';
echo "  경로: {$runnerPath}\n";
echo "  존재: " . (file_exists($runnerPath) ? '예' : '아니오') . "\n";

if (file_exists($runnerPath)) {
    try {
        require_once($runnerPath);
        echo "✓ TestRunner.php 로드 성공\n";
        echo "  클래스 존재: " . (class_exists('TestRunner') ? '예' : '아니오') . "\n";
    } catch (Throwable $e) {
        echo "✗ TestRunner.php 로드 실패: " . $e->getMessage() . "\n";
        echo "  파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
echo "\n";

// Step 5: TestRunner 인스턴스 생성
echo "[5] TestRunner 인스턴스 생성...\n";
if (class_exists('TestRunner')) {
    try {
        $runner = new TestRunner();
        echo "✓ TestRunner 인스턴스 생성 성공\n";
    } catch (Throwable $e) {
        echo "✗ 인스턴스 생성 실패: " . $e->getMessage() . "\n";
        echo "  파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} else {
    echo "○ TestRunner 클래스 없음 - 건너뜀\n";
}
echo "\n";

// Step 6: 테스트 발견
echo "[6] 테스트 발견...\n";
if (isset($runner)) {
    try {
        $discovered = $runner->discoverTests();
        echo "✓ 발견된 에이전트: " . count($discovered) . "개\n";

        $withTests = 0;
        $withoutTests = 0;
        foreach ($discovered as $info) {
            if ($info['test_file'] !== null) {
                $withTests++;
            } else {
                $withoutTests++;
            }
        }
        echo "  테스트 있음: {$withTests}개\n";
        echo "  테스트 없음: {$withoutTests}개\n";
    } catch (Throwable $e) {
        echo "✗ 테스트 발견 실패: " . $e->getMessage() . "\n";
        echo "  파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} else {
    echo "○ TestRunner 없음 - 건너뜀\n";
}

echo "\n완료: " . date('Y-m-d H:i:s') . "\n";
echo "</pre>";
