<?php
/**
 * Quantum Bridge Integration Test Runner
 * =======================================
 * Phase 7.2: PHP-Python 브릿지 통합 테스트
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/quantum_bridge_test.php
 *
 * 테스트 항목:
 *   1. Python 환경 확인
 *   2. _quantum_data_interface.py import 테스트
 *   3. StandardFeatures 데이터 변환 테스트
 *   4. 8D StateVector 출력 검증
 *
 * @file    quantum_bridge_test.php
 * @package QuantumOrchestration
 * @phase   7.2
 * @version 1.0.0
 * @created 2025-12-09
 */

// =============================================================================
// Moodle 통합
// =============================================================================
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 표시 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =============================================================================
// 상수 정의
// =============================================================================
define('HOLONS_PATH', __DIR__);
define('PYTHON_INTERFACE', HOLONS_PATH . '/_quantum_data_interface.py');
define('PYTHON_CMD', 'python3');

// =============================================================================
// 테스트 클래스
// =============================================================================
class QuantumBridgeTest {

    private $results = [];
    private $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
    }

    /**
     * 모든 테스트 실행
     */
    public function runAllTests(): array {
        $this->results['timestamp'] = date('Y-m-d\TH:i:s\Z');
        $this->results['tests'] = [];

        // Test 1: Python 환경
        $this->results['tests']['python_environment'] = $this->testPythonEnvironment();

        // Test 2: 인터페이스 파일
        $this->results['tests']['interface_file'] = $this->testInterfaceFile();

        // Test 3: StandardFeatures import
        $this->results['tests']['standard_features_import'] = $this->testStandardFeaturesImport();

        // Test 4: DimensionReducer import
        $this->results['tests']['dimension_reducer_import'] = $this->testDimensionReducerImport();

        // Test 5: StateVector 생성
        $this->results['tests']['state_vector_generation'] = $this->testStateVectorGeneration();

        // Test 6: 전체 파이프라인
        $this->results['tests']['full_pipeline'] = $this->testFullPipeline();

        // 전체 요약
        $this->results['summary'] = $this->generateSummary();
        $this->results['execution_time_ms'] = round((microtime(true) - $this->startTime) * 1000, 2);

        return $this->results;
    }

    /**
     * Test 1: Python 환경 확인
     */
    private function testPythonEnvironment(): array {
        $test = [
            'name' => 'Python Environment Check',
            'status' => 'error',
            'details' => []
        ];

        // Python 버전
        $version = shell_exec(PYTHON_CMD . ' --version 2>&1');
        $test['details']['version'] = trim($version);

        // Python 3 확인
        if (strpos($version, 'Python 3') !== false) {
            $test['status'] = 'passed';
            $test['details']['message'] = 'Python 3.x detected';
        } else {
            $test['details']['message'] = 'Python 3.x required';
        }

        return $test;
    }

    /**
     * Test 2: 인터페이스 파일 존재 확인
     */
    private function testInterfaceFile(): array {
        $test = [
            'name' => 'Interface File Check',
            'status' => 'error',
            'details' => []
        ];

        $test['details']['path'] = PYTHON_INTERFACE;
        $test['details']['exists'] = file_exists(PYTHON_INTERFACE);

        if (file_exists(PYTHON_INTERFACE)) {
            $test['status'] = 'passed';
            $test['details']['size_bytes'] = filesize(PYTHON_INTERFACE);
            $test['details']['message'] = '_quantum_data_interface.py found';
        } else {
            $test['details']['message'] = 'Interface file not found';
        }

        return $test;
    }

    /**
     * Test 3: StandardFeatures import 테스트
     */
    private function testStandardFeaturesImport(): array {
        $test = [
            'name' => 'StandardFeatures Import',
            'status' => 'error',
            'details' => []
        ];

        $pythonCode = <<<PYTHON
import sys
sys.path.insert(0, '{$this->escapePath(HOLONS_PATH)}')
try:
    from _quantum_data_interface import StandardFeatures
    sf = StandardFeatures()
    print('SUCCESS')
    print(f'Fields: {len(sf.__dataclass_fields__)}')
except Exception as e:
    print(f'ERROR: {e}')
PYTHON;

        $output = $this->runPython($pythonCode);
        $test['details']['output'] = trim($output);

        if (strpos($output, 'SUCCESS') !== false) {
            $test['status'] = 'passed';
            $test['details']['message'] = 'StandardFeatures imported successfully';

            // 필드 수 파싱
            if (preg_match('/Fields: (\d+)/', $output, $matches)) {
                $test['details']['field_count'] = (int)$matches[1];
            }
        } else {
            $test['details']['message'] = 'Import failed';
        }

        return $test;
    }

    /**
     * Test 4: DimensionReducer import 테스트
     */
    private function testDimensionReducerImport(): array {
        $test = [
            'name' => 'DimensionReducer Import',
            'status' => 'error',
            'details' => []
        ];

        $pythonCode = <<<PYTHON
import sys
sys.path.insert(0, '{$this->escapePath(HOLONS_PATH)}')
try:
    from _quantum_data_interface import DimensionReducer
    # DimensionReducer는 classmethod를 사용
    output_dims = len(DimensionReducer.TRANSFORM_WEIGHTS)
    print('SUCCESS')
    print(f'Output dims: {output_dims}')
except Exception as e:
    print(f'ERROR: {e}')
PYTHON;

        $output = $this->runPython($pythonCode);
        $test['details']['output'] = trim($output);

        if (strpos($output, 'SUCCESS') !== false) {
            $test['status'] = 'passed';
            $test['details']['message'] = 'DimensionReducer imported successfully';

            if (preg_match('/Output dims: (\d+)/', $output, $matches)) {
                $test['details']['output_dimensions'] = (int)$matches[1];
            }
        } else {
            $test['details']['message'] = 'Import failed';
        }

        return $test;
    }

    /**
     * Test 5: StateVector 생성 테스트
     */
    private function testStateVectorGeneration(): array {
        $test = [
            'name' => 'StateVector Generation',
            'status' => 'error',
            'details' => []
        ];

        $pythonCode = <<<PYTHON
import sys
import json
sys.path.insert(0, '{$this->escapePath(HOLONS_PATH)}')
try:
    from _quantum_data_interface import StandardFeatures, DimensionReducer

    # 테스트용 StandardFeatures 생성
    sf = StandardFeatures(
        calmness_score=0.7,
        problem_accuracy=0.8,
        engagement_level=0.65,
        goal_progress=0.5,
        dropout_risk=0.2,
        student_id=99999
    )

    # DimensionReducer.transform_to_list()로 8D StateVector 생성 (classmethod)
    state_8d = DimensionReducer.transform_to_list(sf)

    result = {
        'success': True,
        'state_vector': state_8d,
        'dimensions': len(state_8d),
        'sum_check': round(sum(state_8d), 4)
    }
    print(json.dumps(result))
except Exception as e:
    print(json.dumps({'success': False, 'error': str(e)}))
PYTHON;

        $output = $this->runPython($pythonCode);
        $test['details']['raw_output'] = trim($output);

        try {
            $result = json_decode($output, true);
            if ($result && isset($result['success']) && $result['success']) {
                $test['status'] = 'passed';
                $test['details']['message'] = 'StateVector generated successfully';
                $test['details']['dimensions'] = $result['dimensions'];
                $test['details']['state_vector'] = $result['state_vector'];
                $test['details']['probability_sum'] = $result['sum_check'];

                // 각 차원이 0-1 범위인지 검증 (8D 독립 차원이므로 합계가 1.0일 필요 없음)
                $allInRange = true;
                foreach ($result['state_vector'] as $val) {
                    if ($val < 0 || $val > 1) {
                        $allInRange = false;
                        break;
                    }
                }
                $test['details']['values_in_range'] = $allInRange;
                if (!$allInRange) {
                    $test['details']['warning'] = 'Some values outside [0,1] range';
                }
            } else {
                $test['details']['message'] = 'Generation failed';
                $test['details']['error'] = $result['error'] ?? 'Unknown error';
            }
        } catch (Exception $e) {
            $test['details']['message'] = 'JSON parse error';
            $test['details']['error'] = $e->getMessage();
        }

        return $test;
    }

    /**
     * Test 6: 전체 파이프라인 테스트
     */
    private function testFullPipeline(): array {
        $test = [
            'name' => 'Full Pipeline Integration',
            'status' => 'error',
            'details' => []
        ];

        global $USER;
        $userid = $USER->id ?? 99999;

        $pythonCode = <<<PYTHON
import sys
import json
sys.path.insert(0, '{$this->escapePath(HOLONS_PATH)}')
try:
    from _quantum_data_interface import (
        StandardFeatures,
        DimensionReducer,
        QuantumDataCollector
    )

    # 1. 데이터 수집기 생성
    collector = QuantumDataCollector(student_id={$userid})

    # 2. 샘플 에이전트 데이터 (키는 정수형 agent_id)
    agent_contexts = {
        8: {'calm_score': 0.72, 'calmness_level': 3},
        11: {'accuracy_rate': 0.85, 'total_problems': 20},
        12: {'rest_count': 5, 'average_interval': 55},
        3: {'goal_progress': 0.6, 'goal_effectiveness': 0.7},
        9: {'pomodoro_completion': 0.8},
        4: {'engagement_level': 0.75, 'dropout_risk': 0.15}
    }

    # 3. StandardFeatures 생성 (collect_all 메서드 사용)
    features = collector.collect_all(agent_contexts)

    # 4. 8D StateVector 변환 (classmethod)
    state_8d = DimensionReducer.transform_to_list(features)

    result = {
        'success': True,
        'pipeline': 'complete',
        'input_agents': [f'agent_{k:02d}' for k in agent_contexts.keys()],
        'state_vector_8d': state_8d,
        'dimensions': len(state_8d),
        'probability_sum': round(sum(state_8d), 4)
    }
    print(json.dumps(result))
except Exception as e:
    import traceback
    print(json.dumps({
        'success': False,
        'error': str(e),
        'traceback': traceback.format_exc()
    }))
PYTHON;

        $output = $this->runPython($pythonCode);
        $test['details']['raw_output'] = trim($output);

        try {
            $result = json_decode($output, true);
            if ($result && isset($result['success']) && $result['success']) {
                $test['status'] = 'passed';
                $test['details']['message'] = 'Full pipeline executed successfully';
                $test['details']['pipeline'] = $result['pipeline'];
                $test['details']['input_agents'] = $result['input_agents'];
                $test['details']['state_vector_8d'] = $result['state_vector_8d'];
                $test['details']['dimensions'] = $result['dimensions'];
                // 8D 검증
                $test['details']['dimension_valid'] = ($result['dimensions'] === 8);

                // 각 차원이 0-1 범위인지 검증 (8D 독립 차원이므로 합계가 1.0일 필요 없음)
                $allInRange = true;
                foreach ($result['state_vector_8d'] as $val) {
                    if ($val < 0 || $val > 1) {
                        $allInRange = false;
                        break;
                    }
                }
                $test['details']['values_in_range'] = $allInRange;
                if (!$allInRange) {
                    $test['details']['warning'] = 'Some values outside [0,1] range';
                }
            } else {
                $test['details']['message'] = 'Pipeline failed';
                $test['details']['error'] = $result['error'] ?? 'Unknown error';
                if (isset($result['traceback'])) {
                    $test['details']['traceback'] = $result['traceback'];
                }
            }
        } catch (Exception $e) {
            $test['details']['message'] = 'JSON parse error';
            $test['details']['error'] = $e->getMessage();
        }

        return $test;
    }

    /**
     * 테스트 요약 생성
     */
    private function generateSummary(): array {
        $total = count($this->results['tests']);
        $passed = 0;
        $failed = 0;

        foreach ($this->results['tests'] as $test) {
            if ($test['status'] === 'passed') {
                $passed++;
            } else {
                $failed++;
            }
        }

        return [
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
            'overall_status' => $failed === 0 ? 'ALL_PASSED' : 'SOME_FAILED'
        ];
    }

    /**
     * Python 코드 실행
     */
    private function runPython(string $code): string {
        $tempFile = tempnam(sys_get_temp_dir(), 'qbt_');
        $tempFile .= '.py';
        file_put_contents($tempFile, $code);

        $cmd = 'PYTHONIOENCODING=utf-8 ' . PYTHON_CMD . ' ' . escapeshellarg($tempFile) . ' 2>&1';
        $output = shell_exec($cmd);

        @unlink($tempFile);
        return $output ?? '';
    }

    /**
     * 경로 이스케이프
     */
    private function escapePath(string $path): string {
        return addslashes($path);
    }
}

// =============================================================================
// 실행
// =============================================================================
$format = $_GET['format'] ?? 'html';
$tester = new QuantumBridgeTest();
$results = $tester->runAllTests();

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML 출력
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Bridge Integration Test</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            margin: 0;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 {
            color: #58a6ff;
            border-bottom: 1px solid #30363d;
            padding-bottom: 10px;
        }
        .summary-box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .value {
            font-size: 36px;
            font-weight: bold;
        }
        .summary-item .label {
            color: #8b949e;
            font-size: 14px;
        }
        .passed { color: #7ee787; }
        .failed { color: #f85149; }
        .test-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .test-header {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            border-bottom: 1px solid #30363d;
        }
        .test-header:hover { background: #21262d; }
        .test-name { font-weight: 600; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-passed { background: #238636; color: white; }
        .status-error { background: #da3633; color: white; }
        .test-details {
            padding: 15px;
            background: #0d1117;
            font-family: monospace;
            font-size: 13px;
        }
        .detail-item {
            margin: 5px 0;
            padding: 5px 10px;
            background: #161b22;
            border-radius: 4px;
        }
        .detail-key { color: #79c0ff; }
        .detail-value { color: #a5d6ff; }
        .vector-display {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .vector-dim {
            background: #21262d;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
        }
        .vector-dim .dim-name {
            color: #8b949e;
            font-size: 11px;
        }
        .vector-dim .dim-value {
            color: #7ee787;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background: #238636;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
            margin-right: 10px;
        }
        .btn:hover { background: #2ea043; }
        .btn-secondary {
            background: #30363d;
        }
        .btn-secondary:hover { background: #484f58; }
        pre {
            background: #161b22;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 Quantum Bridge Integration Test</h1>

        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-item">
                <div class="value"><?php echo $results['summary']['total_tests']; ?></div>
                <div class="label">Total Tests</div>
            </div>
            <div class="summary-item">
                <div class="value passed"><?php echo $results['summary']['passed']; ?></div>
                <div class="label">Passed</div>
            </div>
            <div class="summary-item">
                <div class="value failed"><?php echo $results['summary']['failed']; ?></div>
                <div class="label">Failed</div>
            </div>
            <div class="summary-item">
                <div class="value" style="color: <?php echo $results['summary']['success_rate'] >= 80 ? '#7ee787' : '#f85149'; ?>">
                    <?php echo $results['summary']['success_rate']; ?>%
                </div>
                <div class="label">Success Rate</div>
            </div>
            <div class="summary-item">
                <div class="value" style="font-size: 24px;"><?php echo $results['execution_time_ms']; ?>ms</div>
                <div class="label">Execution Time</div>
            </div>
        </div>

        <!-- Test Results -->
        <h2>Test Results</h2>
        <?php foreach ($results['tests'] as $testKey => $test): ?>
        <div class="test-card">
            <div class="test-header" onclick="toggleDetails('<?php echo $testKey; ?>')">
                <span class="test-name"><?php echo htmlspecialchars($test['name']); ?></span>
                <span class="status-badge status-<?php echo $test['status']; ?>">
                    <?php echo strtoupper($test['status']); ?>
                </span>
            </div>
            <div class="test-details" id="details-<?php echo $testKey; ?>" style="display: none;">
                <?php foreach ($test['details'] as $key => $value): ?>
                    <?php if ($key === 'state_vector_8d' || $key === 'state_vector'): ?>
                        <div class="detail-item">
                            <span class="detail-key"><?php echo $key; ?>:</span>
                            <div class="vector-display">
                                <?php
                                // 8D StateVector 차원 이름 (Python DimensionReducer.transform_to_list() 순서와 일치)
                                $dimNames = ['cognitive_clarity', 'emotional_stability', 'engagement_level', 'concept_mastery', 'routine_strength', 'metacognitive_awareness', 'dropout_risk', 'intervention_readiness'];
                                foreach ($value as $i => $v):
                                ?>
                                <div class="vector-dim">
                                    <div class="dim-name"><?php echo $dimNames[$i] ?? "D$i"; ?></div>
                                    <div class="dim-value"><?php echo number_format($v, 4); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif (is_array($value)): ?>
                        <div class="detail-item">
                            <span class="detail-key"><?php echo $key; ?>:</span>
                            <span class="detail-value"><?php echo htmlspecialchars(json_encode($value)); ?></span>
                        </div>
                    <?php elseif (is_bool($value)): ?>
                        <div class="detail-item">
                            <span class="detail-key"><?php echo $key; ?>:</span>
                            <span class="detail-value" style="color: <?php echo $value ? '#7ee787' : '#f85149'; ?>">
                                <?php echo $value ? 'true' : 'false'; ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="detail-item">
                            <span class="detail-key"><?php echo $key; ?>:</span>
                            <span class="detail-value"><?php echo htmlspecialchars((string)$value); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Actions -->
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Re-run Tests</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?format=json" class="btn btn-secondary">View JSON</a>
        <a href="quantum_data_bridge.php?action=test" class="btn btn-secondary">Test Bridge</a>
    </div>

    <script>
        function toggleDetails(testKey) {
            const details = document.getElementById('details-' + testKey);
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
        }
        // Auto-expand failed tests
        document.querySelectorAll('.status-error').forEach(badge => {
            const card = badge.closest('.test-card');
            const details = card.querySelector('.test-details');
            if (details) details.style.display = 'block';
        });
    </script>
</body>
</html>
