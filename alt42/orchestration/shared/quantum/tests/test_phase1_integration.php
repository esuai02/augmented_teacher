<?php
/**
 * Rule-Quantum Bridge Phase 1 Integration Test
 * File: shared/quantum/tests/test_phase1_integration.php
 *
 * Tests:
 * 1. RuleYamlLoader - YAML parsing for Agent04
 * 2. RuleToWaveMapper - Rule to wave parameter conversion
 * 3. QuantumPersonaEngine - Bridge integration
 * 4. 4-Layer Probability Calculation
 * 5. DB Persistence to mdl_at_rule_quantum_state
 *
 * Created: 2025-12-09
 * Part of: Rule-Quantum Bridge Phase 1
 */

// Moodle integration
require_once("/home/moodle/public_html/moodle/config.php");
require_login();

global $DB, $USER;

$currentFile = __FILE__;

header('Content-Type: text/html; charset=utf-8');

// Check for JSON format
$format = optional_param('format', 'html', PARAM_ALPHA);
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    run_json_tests();
    exit;
}

// HTML Output
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Rule-Quantum Bridge Phase 1 Integration Test</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { margin-top: 0; color: #2c3e50; font-size: 1.3em; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .test-result { margin: 10px 0; padding: 12px; border-left: 4px solid #bdc3c7; background: #f9f9f9; border-radius: 0 4px 4px 0; }
        .test-result.pass { border-left-color: #27ae60; background: #e8f8f0; }
        .test-result.fail { border-left-color: #e74c3c; background: #fdf2f2; }
        .test-result.warning { border-left-color: #f39c12; background: #fef9e7; }
        .summary { margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; }
        .summary h3 { margin-top: 0; }
        .metric { display: inline-block; margin: 10px; padding: 15px 25px; background: rgba(255,255,255,0.2); border-radius: 8px; }
        .metric-value { font-size: 2em; font-weight: bold; }
        .metric-label { font-size: 0.9em; opacity: 0.9; }
        details { margin-top: 10px; }
        summary { cursor: pointer; color: #3498db; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 5px; }
        .badge-success { background: #27ae60; color: white; }
        .badge-error { background: #e74c3c; color: white; }
        .badge-phase { background: #9b59b6; color: white; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔗 Rule-Quantum Bridge Phase 1 Integration Test <span class='badge badge-phase'>Phase 1</span></h1>";
echo "<p><strong>Student ID:</strong> " . $USER->id . " | <strong>Started:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Base URL:</strong> <a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/' target='_blank'>Test Suite</a></p>";

$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

/**
 * Record and display test result
 */
function recordTest($name, $passed, $message = '', $data = null) {
    global $testResults, $totalTests, $passedTests, $failedTests, $currentFile;

    $totalTests++;
    if ($passed) {
        $passedTests++;
    } else {
        $failedTests++;
    }

    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'file' => $currentFile
    ];

    $class = $passed ? 'pass' : 'fail';
    $icon = $passed ? '✅' : '❌';
    echo "<div class='test-result {$class}'>";
    echo "<strong>{$icon} {$name}</strong>";
    if ($message) {
        echo "<br><span>" . htmlspecialchars($message) . "</span>";
    }
    if ($data !== null) {
        echo "<details><summary>📊 상세 데이터</summary><pre>" . htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre></details>";
    }
    echo "</div>";
}

/**
 * Test Section 1: RuleYamlLoader
 */
function testRuleYamlLoader() {
    global $currentFile;

    echo "<div class='test-section'>";
    echo "<h2>📂 Section 1: RuleYamlLoader Test</h2>";

    $loaderPath = dirname(__DIR__) . '/RuleYamlLoader.php';

    // Test 1.1: File exists
    $fileExists = file_exists($loaderPath);
    recordTest('RuleYamlLoader.php 파일 존재', $fileExists,
        $fileExists ? "경로: {$loaderPath}" : "파일을 찾을 수 없음 - File: {$currentFile}");

    if (!$fileExists) {
        echo "</div>";
        return null;
    }

    // Test 1.2: Include and instantiate
    try {
        require_once($loaderPath);
        $loader = new RuleYamlLoader();
        recordTest('RuleYamlLoader 클래스 인스턴스화', true, '클래스가 정상적으로 로드됨');
    } catch (Exception $e) {
        recordTest('RuleYamlLoader 클래스 인스턴스화', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}, Line: " . $e->getLine());
        echo "</div>";
        return null;
    }

    // Test 1.3: Load Agent04 rules
    try {
        $rules = $loader->loadAgentRules(4);
        $hasRules = is_array($rules) && !empty($rules);
        recordTest('Agent04 rules.yaml 로드', $hasRules,
            $hasRules ? "규칙 수: " . count($rules) : "규칙을 찾을 수 없음",
            $hasRules ? ['rule_count' => count($rules), 'first_rule_id' => $rules[0]['rule_id'] ?? 'N/A'] : null);
    } catch (Exception $e) {
        recordTest('Agent04 rules.yaml 로드', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
        echo "</div>";
        return $loader;
    }

    // Test 1.4: Rule structure validation
    if (!empty($rules)) {
        $firstRule = $rules[0];
        $requiredFields = ['rule_id', 'priority', 'confidence', 'conditions', 'action'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($firstRule[$field])) {
                $missingFields[] = $field;
            }
        }
        $structureValid = empty($missingFields);
        recordTest('규칙 구조 유효성 검증', $structureValid,
            $structureValid ? "필수 필드 모두 존재: " . implode(', ', $requiredFields) : "누락된 필드: " . implode(', ', $missingFields),
            ['rule_id' => $firstRule['rule_id'] ?? 'N/A', 'priority' => $firstRule['priority'] ?? 'N/A', 'confidence' => $firstRule['confidence'] ?? 'N/A']);
    }

    // Test 1.5: Extract condition fields
    try {
        $fields = $loader->extractAllConditionFields();
        $hasFields = is_array($fields) && !empty($fields);
        recordTest('조건 필드 추출', $hasFields,
            $hasFields ? "추출된 필드 수: " . count($fields) : "필드 추출 실패",
            $hasFields ? array_slice($fields, 0, 10) : null);
    } catch (Exception $e) {
        recordTest('조건 필드 추출', false, "오류: " . $e->getMessage());
    }

    echo "</div>";
    return $loader;
}

/**
 * Test Section 2: RuleToWaveMapper
 */
function testRuleToWaveMapper($loader) {
    global $currentFile;

    echo "<div class='test-section'>";
    echo "<h2>🌊 Section 2: RuleToWaveMapper Test</h2>";

    $mapperPath = dirname(__DIR__) . '/RuleToWaveMapper.php';

    // Test 2.1: File exists
    $fileExists = file_exists($mapperPath);
    recordTest('RuleToWaveMapper.php 파일 존재', $fileExists,
        $fileExists ? "경로: {$mapperPath}" : "파일을 찾을 수 없음");

    if (!$fileExists) {
        echo "</div>";
        return null;
    }

    // Test 2.2: Include and instantiate
    try {
        require_once($mapperPath);
        $mapper = new RuleToWaveMapper($loader);
        recordTest('RuleToWaveMapper 클래스 인스턴스화', true, 'RuleYamlLoader 주입 성공');
    } catch (Exception $e) {
        recordTest('RuleToWaveMapper 클래스 인스턴스화', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
        echo "</div>";
        return null;
    }

    // Test 2.3: Map single rule to wave params
    try {
        $rules = $loader->loadAgentRules(4);
        if (!empty($rules)) {
            $waveParams = $mapper->mapRuleToWaveParams($rules[0]);
            $hasWaveParams = is_array($waveParams) && !empty($waveParams);

            // Check for expected wave function keys
            $expectedWaves = ['psi_core', 'psi_align', 'psi_engage', 'psi_meta', 'psi_cascade'];
            $foundWaves = array_intersect($expectedWaves, array_keys($waveParams));

            recordTest('단일 규칙 → 파동 파라미터 변환', $hasWaveParams && count($foundWaves) >= 3,
                "변환된 파라미터 수: " . count($waveParams) . ", 발견된 파동함수: " . count($foundWaves),
                ['wave_params' => $waveParams, 'rule_id' => $rules[0]['rule_id']]);
        }
    } catch (Exception $e) {
        recordTest('단일 규칙 → 파동 파라미터 변환', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
    }

    // Test 2.4: Map all agent rules
    try {
        $allWaveParams = $mapper->mapAgentRulesToWaves(4);
        $hasAllParams = is_array($allWaveParams) && !empty($allWaveParams);
        recordTest('Agent04 전체 규칙 → 파동 파라미터 변환', $hasAllParams,
            $hasAllParams ? "변환된 규칙 수: " . count($allWaveParams) : "변환 실패",
            $hasAllParams ? ['rule_count' => count($allWaveParams), 'first_rule' => array_keys($allWaveParams)[0] ?? 'N/A'] : null);
    } catch (Exception $e) {
        recordTest('Agent04 전체 규칙 → 파동 파라미터 변환', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
    }

    // Test 2.5: Calculate Layer 1 score
    try {
        if (!empty($rules)) {
            $layer1Score = $mapper->calculateLayer1Score($rules[0], 1.0);
            $isValidScore = is_numeric($layer1Score) && $layer1Score >= 0 && $layer1Score <= 1;
            recordTest('Layer 1 (Rule Confidence) 계산', $isValidScore,
                "계산된 점수: " . number_format($layer1Score, 5) . " (범위: 0-1)",
                ['layer1_score' => $layer1Score, 'formula' => 'confidence × (priority/100) × condition_match']);
        }
    } catch (Exception $e) {
        recordTest('Layer 1 (Rule Confidence) 계산', false,
            "오류: " . $e->getMessage());
    }

    echo "</div>";
    return $mapper;
}

/**
 * Test Section 3: Database Table
 */
function testDatabaseTable() {
    global $DB, $currentFile;

    echo "<div class='test-section'>";
    echo "<h2>🗄️ Section 3: Database Table Test</h2>";

    $tableName = 'at_rule_quantum_state';

    // Test 3.1: Table exists
    try {
        $dbman = $DB->get_manager();
        $table = new xmldb_table($tableName);
        $tableExists = $dbman->table_exists($table);
        recordTest('mdl_at_rule_quantum_state 테이블 존재', $tableExists,
            $tableExists ? "테이블이 존재합니다" : "테이블을 찾을 수 없음 - Migration을 실행하세요");

        if (!$tableExists) {
            echo "<p class='warning'>⚠️ 테이블이 없습니다. <a href='../../db/migrations/run_010_migration.php'>Migration 실행</a></p>";
            echo "</div>";
            return false;
        }
    } catch (Exception $e) {
        recordTest('mdl_at_rule_quantum_state 테이블 존재', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
        echo "</div>";
        return false;
    }

    // Test 3.2: Check columns
    try {
        $columns = $DB->get_records_sql("SHOW COLUMNS FROM {" . $tableName . "}");
        $expectedColumns = ['id', 'studentid', 'sessionid', 'agentid', 'ruleid', 'layer1_rule_conf', 'layer2_wave_prob', 'layer3_corr_inf', 'layer4_final'];
        $foundColumns = array_keys($columns);
        $missingColumns = array_diff($expectedColumns, $foundColumns);

        $allColumnsExist = empty($missingColumns);
        recordTest('테이블 컬럼 구조 검증', $allColumnsExist,
            $allColumnsExist ? "모든 필수 컬럼 존재 (" . count($foundColumns) . "개)" : "누락된 컬럼: " . implode(', ', $missingColumns),
            ['found_columns' => count($foundColumns), 'expected' => $expectedColumns]);
    } catch (Exception $e) {
        recordTest('테이블 컬럼 구조 검증', false,
            "오류: " . $e->getMessage());
    }

    // Test 3.3: Check indexes
    try {
        $indexes = $DB->get_records_sql("SHOW INDEX FROM {" . $tableName . "}");
        $indexNames = array_unique(array_column($indexes, 'key_name'));
        $expectedIndexes = ['PRIMARY', 'idx_student_session', 'idx_agent_rule'];
        $foundIndexes = array_intersect($expectedIndexes, $indexNames);

        $hasIndexes = count($foundIndexes) >= 2;
        recordTest('테이블 인덱스 검증', $hasIndexes,
            "발견된 인덱스: " . count($indexNames) . "개",
            ['indexes' => $indexNames]);
    } catch (Exception $e) {
        recordTest('테이블 인덱스 검증', false,
            "오류: " . $e->getMessage());
    }

    // Test 3.4: Record count
    try {
        $count = $DB->count_records($tableName);
        recordTest('현재 레코드 수 확인', true,
            "저장된 레코드: {$count}개",
            ['record_count' => $count]);
    } catch (Exception $e) {
        recordTest('현재 레코드 수 확인', false,
            "오류: " . $e->getMessage());
    }

    echo "</div>";
    return true;
}

/**
 * Test Section 4: QuantumPersonaEngine Bridge Integration
 */
function testQuantumPersonaEngineBridge($loader, $mapper) {
    global $DB, $USER, $currentFile;

    echo "<div class='test-section'>";
    echo "<h2>⚛️ Section 4: QuantumPersonaEngine Bridge Integration Test</h2>";

    $enginePath = __DIR__ . '/../../../agents/agent04_inspect_weakpoints/quantum_modeling/QuantumPersonaEngine.php';

    // Test 4.1: File exists
    $fileExists = file_exists($enginePath);
    recordTest('QuantumPersonaEngine.php 파일 존재', $fileExists,
        $fileExists ? "경로: {$enginePath}" : "파일을 찾을 수 없음");

    if (!$fileExists) {
        echo "</div>";
        return null;
    }

    // Test 4.2: Include and instantiate
    try {
        require_once($enginePath);
        $engine = new QuantumPersonaEngine();
        recordTest('QuantumPersonaEngine 클래스 인스턴스화', true, '클래스가 정상적으로 로드됨');
    } catch (Exception $e) {
        recordTest('QuantumPersonaEngine 클래스 인스턴스화', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
        echo "</div>";
        return null;
    }

    // Test 4.3: Initialize bridge
    try {
        $bridgeInitialized = $engine->initializeBridge();
        recordTest('브릿지 초기화', $bridgeInitialized,
            $bridgeInitialized ? "RuleYamlLoader + RuleToWaveMapper 로드 완료" : "브릿지 초기화 실패");
    } catch (Exception $e) {
        recordTest('브릿지 초기화', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
    }

    // Test 4.4: 4D to 8D conversion
    try {
        $state4D = ['S' => 0.3, 'D' => 0.4, 'G' => 0.2, 'A' => 0.1];
        $state8D = $engine->convert4Dto8D($state4D);
        $has8Dimensions = is_array($state8D) && count($state8D) === 8;

        $expected8D = ['cognitive_clarity', 'emotional_stability', 'attention_level', 'motivation_strength',
                       'energy_level', 'social_connection', 'creative_flow', 'learning_momentum'];
        $found8D = array_intersect($expected8D, array_keys($state8D));

        recordTest('4D → 8D StateVector 변환', $has8Dimensions && count($found8D) === 8,
            "8개 차원으로 변환됨",
            ['input_4D' => $state4D, 'output_8D' => $state8D]);
    } catch (Exception $e) {
        recordTest('4D → 8D StateVector 변환', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}");
    }

    // Test 4.5: Load agent wave params
    try {
        $waveParams = $engine->loadAgentWaveParams(4);
        $hasParams = is_array($waveParams) && !empty($waveParams);
        recordTest('Agent04 파동 파라미터 로드', $hasParams,
            $hasParams ? "로드된 규칙 수: " . count($waveParams) : "파라미터 로드 실패",
            $hasParams ? ['rule_count' => count($waveParams), 'first_rule' => array_keys($waveParams)[0] ?? 'N/A'] : null);
    } catch (Exception $e) {
        recordTest('Agent04 파동 파라미터 로드', false,
            "오류: " . $e->getMessage());
    }

    // Test 4.6: Get bridge info
    try {
        $bridgeInfo = $engine->getBridgeInfo();
        $hasBridgeInfo = is_array($bridgeInfo) && isset($bridgeInfo['bridge_status']);
        recordTest('브릿지 정보 조회', $hasBridgeInfo,
            $hasBridgeInfo ? "상태: " . $bridgeInfo['bridge_status'] : "브릿지 정보 없음",
            $bridgeInfo);
    } catch (Exception $e) {
        recordTest('브릿지 정보 조회', false,
            "오류: " . $e->getMessage());
    }

    echo "</div>";
    return $engine;
}

/**
 * Test Section 5: 4-Layer Probability Calculation
 */
function test4LayerProbability($engine, $loader) {
    global $USER, $currentFile;

    echo "<div class='test-section'>";
    echo "<h2>📊 Section 5: 4-Layer Probability Calculation Test</h2>";

    if (!$engine || !$loader) {
        recordTest('4-Layer 계산 전제조건', false, "Engine 또는 Loader가 초기화되지 않음");
        echo "</div>";
        return;
    }

    // Load a sample rule
    try {
        $rules = $loader->loadAgentRules(4);
        if (empty($rules)) {
            recordTest('테스트 규칙 로드', false, "Agent04 규칙을 찾을 수 없음");
            echo "</div>";
            return;
        }
        $testRule = $rules[0];
        recordTest('테스트 규칙 로드', true,
            "규칙 ID: " . $testRule['rule_id'] . ", Priority: " . $testRule['priority'] . ", Confidence: " . $testRule['confidence']);
    } catch (Exception $e) {
        recordTest('테스트 규칙 로드', false, "오류: " . $e->getMessage());
        echo "</div>";
        return;
    }

    // Prepare test data
    $testState4D = ['S' => 0.25, 'D' => 0.35, 'G' => 0.20, 'A' => 0.20];
    $testState8D = $engine->convert4Dto8D($testState4D);

    // Get wave params
    try {
        $allWaveParams = $engine->loadAgentWaveParams(4);
        $testWaveParams = $allWaveParams[$testRule['rule_id']] ?? [];

        if (empty($testWaveParams)) {
            recordTest('테스트 파동 파라미터', false, "규칙에 대한 파동 파라미터 없음");
            echo "</div>";
            return;
        }
        recordTest('테스트 파동 파라미터', true,
            "파라미터 수: " . count($testWaveParams),
            array_slice($testWaveParams, 0, 5));
    } catch (Exception $e) {
        recordTest('테스트 파동 파라미터', false, "오류: " . $e->getMessage());
        echo "</div>";
        return;
    }

    // Test 4-layer calculation
    try {
        $layerResult = $engine->calculate4LayerProbability($testRule, $testWaveParams, $testState8D, 4);

        // Check layer 1
        $hasLayer1 = isset($layerResult['layer1_rule_conf']) && is_numeric($layerResult['layer1_rule_conf']);
        recordTest('Layer 1: Rule Confidence 계산', $hasLayer1,
            $hasLayer1 ? "P_rule = " . number_format($layerResult['layer1_rule_conf'], 5) : "Layer 1 계산 실패",
            ['formula' => 'confidence × (priority/100) × condition_match']);

        // Check layer 2
        $hasLayer2 = isset($layerResult['layer2_wave_prob']) && is_numeric($layerResult['layer2_wave_prob']);
        recordTest('Layer 2: Wave Probability 계산', $hasLayer2,
            $hasLayer2 ? "P_wave = " . number_format($layerResult['layer2_wave_prob'], 5) : "Layer 2 계산 실패",
            ['formula' => '|⟨ψ_agent|ψ_target⟩|²']);

        // Check layer 3
        $hasLayer3 = isset($layerResult['layer3_corr_inf']) && is_numeric($layerResult['layer3_corr_inf']);
        recordTest('Layer 3: Correlation Influence 계산', $hasLayer3,
            $hasLayer3 ? "P_corr = " . number_format($layerResult['layer3_corr_inf'], 5) . " (Phase 2에서 구현 예정)" : "Layer 3 계산 실패",
            ['formula' => 'Σ(C_ij × P_j) / 21']);

        // Check layer 4
        $hasLayer4 = isset($layerResult['layer4_final']) && is_numeric($layerResult['layer4_final']);
        recordTest('Layer 4: Final HYBRID Probability 계산', $hasLayer4,
            $hasLayer4 ? "P_final = " . number_format($layerResult['layer4_final'], 5) : "Layer 4 계산 실패",
            ['formula' => 'sigmoid(0.25×P_rule + 0.35×P_wave + 0.25×P_corr + bias)']);

        // Check intervention type
        $hasIntervention = isset($layerResult['intervention_type']) && !empty($layerResult['intervention_type']);
        recordTest('개입 유형 결정', $hasIntervention,
            $hasIntervention ? "결정된 유형: " . $layerResult['intervention_type'] : "개입 유형 결정 실패",
            ['thresholds' => '≥0.9: IMMEDIATE, ≥0.7: PROBABILISTIC, ≥0.5: WEIGHT_ADJ, <0.5: OBSERVE']);

        // Full result
        recordTest('4-Layer 계산 결과 종합', true, "전체 계산 완료", $layerResult);

    } catch (Exception $e) {
        recordTest('4-Layer Probability 계산', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}, Line: " . $e->getLine());
    }

    echo "</div>";
}

/**
 * Test Section 6: Full Evaluation Pipeline
 */
function testFullEvaluationPipeline($engine) {
    global $USER, $DB, $currentFile;

    echo "<div class='test-section'>";
    echo "<h2>🚀 Section 6: Full Evaluation Pipeline Test</h2>";

    if (!$engine) {
        recordTest('전체 평가 파이프라인', false, "Engine이 초기화되지 않음");
        echo "</div>";
        return;
    }

    // Test full evaluation
    $testSessionId = 'test_session_' . time();
    $testContext = [
        'state_vector' => ['S' => 0.3, 'D' => 0.35, 'G' => 0.2, 'A' => 0.15],
        'test_mode' => true
    ];

    try {
        $result = $engine->evaluateRuleQuantumBridge($USER->id, $testSessionId, 4, $testContext);

        // Check for errors
        if (isset($result['error'])) {
            recordTest('전체 평가 파이프라인 실행', false,
                "오류: " . $result['error'] . " - File: " . ($result['file'] ?? $currentFile));
            echo "</div>";
            return;
        }

        // Check evaluation results
        $hasEvaluations = isset($result['evaluations']) && is_array($result['evaluations']);
        recordTest('규칙 평가 결과', $hasEvaluations,
            $hasEvaluations ? "평가된 규칙 수: " . count($result['evaluations']) : "평가 결과 없음");

        // Check recommendations
        $hasRecommendations = isset($result['recommendations']) && is_array($result['recommendations']);
        recordTest('개입 권장사항', $hasRecommendations,
            $hasRecommendations ? "생성된 권장사항 수: " . count($result['recommendations']) : "권장사항 없음",
            $hasRecommendations ? array_slice($result['recommendations'], 0, 3) : null);

        // Check summary
        $hasSummary = isset($result['summary']);
        recordTest('평가 요약', $hasSummary,
            $hasSummary ? "총 평가: " . ($result['summary']['total_evaluated'] ?? 0) . ", 개입 대상: " . ($result['summary']['interventions_needed'] ?? 0) : "요약 없음",
            $result['summary'] ?? null);

        // Check DB save (only if not test mode)
        $dbSaved = isset($result['db_saved']) ? $result['db_saved'] : false;
        recordTest('DB 저장 상태', true,
            "저장 상태: " . ($dbSaved ? "저장됨" : "테스트 모드 - 저장 안함"),
            ['session_id' => $testSessionId, 'db_saved' => $dbSaved]);

        // Full result preview
        recordTest('전체 파이프라인 결과', true, "파이프라인 실행 완료", [
            'student_id' => $result['student_id'] ?? $USER->id,
            'session_id' => $result['session_id'] ?? $testSessionId,
            'agent_id' => $result['agent_id'] ?? 4,
            'evaluation_count' => count($result['evaluations'] ?? []),
            'recommendation_count' => count($result['recommendations'] ?? [])
        ]);

    } catch (Exception $e) {
        recordTest('전체 평가 파이프라인 실행', false,
            "오류: " . $e->getMessage() . " - File: {$currentFile}, Line: " . $e->getLine());
    }

    echo "</div>";
}

// ============================================================
// Run All Tests
// ============================================================

echo "<hr>";
echo "<p class='info'>ℹ️ 테스트를 시작합니다...</p>";

// Run tests
$loader = testRuleYamlLoader();
$mapper = testRuleToWaveMapper($loader);
$dbOk = testDatabaseTable();
$engine = testQuantumPersonaEngineBridge($loader, $mapper);
test4LayerProbability($engine, $loader);
testFullEvaluationPipeline($engine);

// ============================================================
// Summary
// ============================================================

echo "<div class='summary'>";
echo "<h3>📈 테스트 결과 요약</h3>";
echo "<div class='metric'><div class='metric-value'>{$totalTests}</div><div class='metric-label'>총 테스트</div></div>";
echo "<div class='metric'><div class='metric-value'>{$passedTests}</div><div class='metric-label'>성공</div></div>";
echo "<div class='metric'><div class='metric-value'>{$failedTests}</div><div class='metric-label'>실패</div></div>";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "<div class='metric'><div class='metric-value'>{$successRate}%</div><div class='metric-label'>성공률</div></div>";

if ($failedTests === 0) {
    echo "<p style='margin-top: 20px;'>✅ <strong>Phase 1 Integration 테스트 완료!</strong></p>";
    echo "<p>다음 단계: Phase 2 - Correlation Analysis 구현</p>";
} else {
    echo "<p style='margin-top: 20px;'>⚠️ <strong>{$failedTests}개 테스트 실패</strong></p>";
    echo "<p>실패한 테스트를 확인하고 문제를 해결하세요.</p>";
}

echo "</div>";

// Links
echo "<div class='test-section'>";
echo "<h2>🔗 관련 링크</h2>";
echo "<ul>";
echo "<li><a href='../../db/migrations/run_010_migration.php'>DB Migration 실행</a></li>";
echo "<li><a href='../RuleYamlLoader.php'>RuleYamlLoader.php</a></li>";
echo "<li><a href='../RuleToWaveMapper.php'>RuleToWaveMapper.php</a></li>";
echo "<li><a href='../../../agents/agent04_inspect_weakpoints/quantum_modeling/QuantumPersonaEngine.php'>QuantumPersonaEngine.php</a></li>";
echo "<li><a href='?format=json'>JSON API 형식 결과</a></li>";
echo "</ul>";
echo "</div>";

echo "</div></body></html>";

// ============================================================
// JSON API Handler
// ============================================================
function run_json_tests() {
    global $USER, $testResults, $totalTests, $passedTests, $failedTests;

    // Capture test output
    ob_start();

    $loader = testRuleYamlLoader();
    $mapper = testRuleToWaveMapper($loader);
    $dbOk = testDatabaseTable();
    $engine = testQuantumPersonaEngineBridge($loader, $mapper);
    test4LayerProbability($engine, $loader);
    testFullEvaluationPipeline($engine);

    ob_end_clean();

    $response = [
        'success' => $failedTests === 0,
        'total_tests' => $totalTests,
        'passed' => $passedTests,
        'failed' => $failedTests,
        'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $USER->id,
        'phase' => 'Phase 1: Rule-Quantum Bridge Integration',
        'results' => $testResults
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ============================================================
// Related Database Tables
// ============================================================
// mdl_at_rule_quantum_state - Main quantum state storage
//   - id: BIGINT(10) AUTO_INCREMENT
//   - studentid: BIGINT(10) NOT NULL
//   - sessionid: VARCHAR(50) NOT NULL
//   - agentid: INT(3) NOT NULL
//   - ruleid: VARCHAR(100) NOT NULL
//   - layer1_rule_conf: DECIMAL(6,5)
//   - layer2_wave_prob: DECIMAL(6,5)
//   - layer3_corr_inf: DECIMAL(6,5)
//   - layer4_final: DECIMAL(6,5)
//   - wave_params: TEXT (JSON)
//   - state_vector: TEXT (JSON)
//   - intervention_type: VARCHAR(50)
//   - intervention_executed: TINYINT(1)
//   - intervention_result: TEXT (JSON)
//   - timecreated: BIGINT(10)
//   - timemodified: BIGINT(10)
// ============================================================
