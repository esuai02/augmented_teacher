<?php
/**
 * Agent04 온톨로지 통합 테스트
 * File: agent04_inspect_weakpoints/tests/test_agent04_integration.php
 * 
 * 전체 흐름 검증: 학습 활동 → 룰 평가 → 온톨로지 인스턴스 생성 → 추론 → 보강 방안 생성 → 응답 생성
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_login();

global $DB, $USER;

// Agent Garden Service 로드
require_once(__DIR__ . '/../../agent22_module_improvement/ui/agent_garden.service.php');

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Agent04 온톨로지 통합 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test-section h2 { margin-top: 0; color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .test-result { margin: 10px 0; padding: 10px; border-left: 4px solid #ddd; }
        .test-result.pass { border-left-color: green; }
        .test-result.fail { border-left-color: red; }
        .test-result.warning { border-left-color: orange; }
    </style>
</head>
<body>";

echo "<h1>Agent04 온톨로지 통합 테스트</h1>";
echo "<p><strong>학생 ID:</strong> " . $USER->id . "</p>";
echo "<p><strong>테스트 시작 시간:</strong> " . date('Y-m-d H:i:s') . "</p>";

$testResults = [];
$service = new AgentGardenService();

/**
 * 테스트 결과 기록
 */
function recordTest($name, $passed, $message = '', $data = null) {
    global $testResults;
    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $class = $passed ? 'pass' : 'fail';
    $icon = $passed ? '✅' : '❌';
    echo "<div class='test-result {$class}'>";
    echo "<strong>{$icon} {$name}</strong><br>";
    if ($message) {
        echo "<span>" . htmlspecialchars($message) . "</span><br>";
    }
    if ($data !== null) {
        echo "<details><summary>상세 데이터</summary><pre>" . htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre></details>";
    }
    echo "</div>";
}

/**
 * 시나리오 1: 개념이해 취약구간 탐지 (CU_A1)
 */
function testScenario1_WeakPointDetection() {
    global $service, $USER;
    
    echo "<div class='test-section'>";
    echo "<h2>시나리오 1: 개념이해 취약구간 탐지 (CU_A1)</h2>";
    
    $context = [
        'student_id' => $USER->id,
        'activity_type' => 'concept_understanding',
        'concept_stage' => 'understanding',
        'pause_frequency' => 5,
        'pause_stage' => '핵심 의미 파악',
        'timestamp' => date('Y-m-d\TH:i:s\Z')
    ];
    
    try {
        $result = $service->executeAgent('agent04', $context, $USER->id);
        
        // 1. 룰 평가 결과 확인
        $ruleMatched = isset($result['matched_rule']) && $result['matched_rule'] === 'CU_A1_weak_point_detection';
        recordTest('룰 평가 결과 확인', $ruleMatched, $ruleMatched ? 'CU_A1 룰이 정상적으로 매칭됨' : 'CU_A1 룰 매칭 실패', $result['matched_rule'] ?? null);
        
        // 2. 응답 생성 확인
        $hasResponse = isset($result['response']) && isset($result['response']['status']);
        recordTest('응답 생성 확인', $hasResponse, $hasResponse ? '응답이 정상적으로 생성됨' : '응답 생성 실패');
        
        // 3. 온톨로지 결과 확인 (디버깅 정보 포함)
        $hasOntologyResults = isset($result['response']['reinforcement_plan']) || isset($result['response']['reasoning_results']);
        $debugInfo = [
            'has_reinforcement_plan' => isset($result['response']['reinforcement_plan']),
            'has_reasoning_results' => isset($result['response']['reasoning_results']),
            'has_execution_plan' => isset($result['response']['execution_plan']),
            'response_keys' => array_keys($result['response'] ?? [])
        ];
        recordTest('온톨로지 결과 확인', $hasOntologyResults, $hasOntologyResults ? '온톨로지 결과가 포함됨' : '온톨로지 결과 없음', $debugInfo);
        
        // 4. 보강 방안 확인
        if (isset($result['response']['reinforcement_plan'])) {
            $plan = $result['response']['reinforcement_plan'];
            $hasWeakpointDesc = !empty($plan['weakpoint_description']);
            $hasStrategy = !empty($plan['reinforcement_strategy']);
            
            recordTest('보강 방안 확인', $hasWeakpointDesc && $hasStrategy, 
                $hasWeakpointDesc && $hasStrategy ? '보강 방안이 정상적으로 생성됨' : '보강 방안 필수 필드 누락', 
                $plan);
        } else {
            recordTest('보강 방안 확인', false, '보강 방안이 생성되지 않음');
        }
        
        // 5. 메시지 확인
        $hasMessage = !empty($result['response']['message']);
        recordTest('메시지 확인', $hasMessage, $hasMessage ? '메시지가 정상적으로 생성됨' : '메시지 생성 실패', substr($result['response']['message'] ?? '', 0, 200));
        
        echo "<h3>전체 결과</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
        
    } catch (Exception $e) {
        recordTest('시나리오 1 실행', false, '에러 발생: ' . $e->getMessage() . " [File: " . $e->getFile() . ", Line: " . $e->getLine() . "]");
    }
    
    echo "</div>";
}

/**
 * 시나리오 2: TTS 주의집중 패턴 분석 (CU_A2)
 */
function testScenario2_TTSAttentionPattern() {
    global $service, $USER;
    
    echo "<div class='test-section'>";
    echo "<h2>시나리오 2: TTS 주의집중 패턴 분석 (CU_A2)</h2>";
    
    $context = [
        'student_id' => $USER->id,
        'activity_type' => 'concept_understanding',
        'learning_method' => 'TTS',
        'gaze_attention_score' => 0.5,
        'note_taking_pattern_change' => true,
        'timestamp' => date('Y-m-d\TH:i:s\Z')
    ];
    
    try {
        $result = $service->executeAgent('agent04', $context, $USER->id);
        
        $ruleMatched = isset($result['matched_rule']) && $result['matched_rule'] === 'CU_A2_tts_attention_pattern';
        recordTest('룰 평가 결과 확인', $ruleMatched, $ruleMatched ? 'CU_A2 룰이 정상적으로 매칭됨' : 'CU_A2 룰 매칭 실패');
        
        $hasOntologyResults = isset($result['response']['reinforcement_plan']) || isset($result['response']['reasoning_results']);
        recordTest('온톨로지 결과 확인', $hasOntologyResults, $hasOntologyResults ? '온톨로지 결과가 포함됨' : '온톨로지 결과 없음');
        
        echo "<h3>전체 결과</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
        
    } catch (Exception $e) {
        recordTest('시나리오 2 실행', false, '에러 발생: ' . $e->getMessage() . " [File: " . $e->getFile() . ", Line: " . $e->getLine() . "]");
    }
    
    echo "</div>";
}

/**
 * 시나리오 3: 개념 혼동 탐지 (CU_A3)
 */
function testScenario3_ConceptConfusion() {
    global $service, $USER;
    
    echo "<div class='test-section'>";
    echo "<h2>시나리오 3: 개념 혼동 탐지 (CU_A3)</h2>";
    
    $context = [
        'student_id' => $USER->id,
        'activity_type' => 'concept_understanding',
        'concept_confusion_detected' => true,
        'confusion_type' => 'definition_vs_example',
        'timestamp' => date('Y-m-d\TH:i:s\Z')
    ];
    
    try {
        $result = $service->executeAgent('agent04', $context, $USER->id);
        
        $ruleMatched = isset($result['matched_rule']) && $result['matched_rule'] === 'CU_A3_concept_confusion_detection';
        recordTest('룰 평가 결과 확인', $ruleMatched, $ruleMatched ? 'CU_A3 룰이 정상적으로 매칭됨' : 'CU_A3 룰 매칭 실패');
        
        $hasOntologyResults = isset($result['response']['reinforcement_plan']) || isset($result['response']['reasoning_results']);
        recordTest('온톨로지 결과 확인', $hasOntologyResults, $hasOntologyResults ? '온톨로지 결과가 포함됨' : '온톨로지 결과 없음');
        
        echo "<h3>전체 결과</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
        
    } catch (Exception $e) {
        recordTest('시나리오 3 실행', false, '에러 발생: ' . $e->getMessage() . " [File: " . $e->getFile() . ", Line: " . $e->getLine() . "]");
    }
    
    echo "</div>";
}

/**
 * 에러 케이스 테스트: 변수 누락
 */
function testErrorCase_MissingVariables() {
    global $service, $USER;
    
    echo "<div class='test-section'>";
    echo "<h2>에러 케이스 테스트: 변수 누락</h2>";
    
    $context = [
        'student_id' => $USER->id,
        // activity_type 누락
        'timestamp' => date('Y-m-d\TH:i:s\Z')
    ];
    
    try {
        $result = $service->executeAgent('agent04', $context, $USER->id);
        
        // 에러가 발생해도 기본 응답은 반환되어야 함
        $hasResponse = isset($result['response']) && isset($result['response']['status']);
        recordTest('기본 응답 반환 확인', $hasResponse, $hasResponse ? '변수 누락 시에도 기본 응답 반환됨' : '기본 응답 반환 실패');
        
        $isError = isset($result['response']['status']) && $result['response']['status'] === 'error';
        recordTest('에러 상태 확인', $isError || $hasResponse, $isError ? '에러 상태로 정상 처리됨' : '정상 응답 반환됨');
        
    } catch (Exception $e) {
        recordTest('에러 케이스 테스트 실행', false, '예외 발생: ' . $e->getMessage() . " [File: " . $e->getFile() . ", Line: " . $e->getLine() . "]");
    }
    
    echo "</div>";
}

/**
 * 성능 테스트: 응답 시간 측정
 */
function testPerformance_ResponseTime() {
    global $service, $USER;
    
    echo "<div class='test-section'>";
    echo "<h2>성능 테스트: 응답 시간 측정</h2>";
    
    $context = [
        'student_id' => $USER->id,
        'activity_type' => 'concept_understanding',
        'concept_stage' => 'understanding',
        'pause_frequency' => 5,
        'pause_stage' => '핵심 의미 파악',
        'timestamp' => date('Y-m-d\TH:i:s\Z')
    ];
    
    $times = [];
    $iterations = 3;
    
    for ($i = 0; $i < $iterations; $i++) {
        $startTime = microtime(true);
        try {
            $result = $service->executeAgent('agent04', $context, $USER->id);
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // 밀리초
            $times[] = $executionTime;
        } catch (Exception $e) {
            recordTest("성능 테스트 반복 {$i}", false, '에러 발생: ' . $e->getMessage());
        }
    }
    
    if (!empty($times)) {
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        recordTest('평균 응답 시간', $avgTime < 2000, 
            "평균: {$avgTime}ms, 최대: {$maxTime}ms, 최소: {$minTime}ms", 
            ['times' => $times, 'average' => $avgTime, 'max' => $maxTime, 'min' => $minTime]);
        
        echo "<p><strong>응답 시간 통계:</strong></p>";
        echo "<ul>";
        echo "<li>평균: {$avgTime}ms</li>";
        echo "<li>최대: {$maxTime}ms</li>";
        echo "<li>최소: {$minTime}ms</li>";
        echo "</ul>";
    }
    
    echo "</div>";
}

/**
 * 데이터베이스 검증: 온톨로지 인스턴스 저장 확인
 */
function testDatabase_OntologyInstances() {
    global $DB, $USER;
    
    echo "<div class='test-section'>";
    echo "<h2>데이터베이스 검증: 온톨로지 인스턴스 저장 확인</h2>";
    
    try {
        // 학생의 온톨로지 인스턴스 조회
        $instances = $DB->get_records_sql(
            "SELECT * FROM {alt42_ontology_instances} WHERE student_id = ? ORDER BY created_at DESC LIMIT 10",
            [$USER->id]
        );
        
        $hasInstances = !empty($instances);
        recordTest('온톨로지 인스턴스 저장 확인', $hasInstances, 
            $hasInstances ? count($instances) . '개의 인스턴스가 저장되어 있음' : '저장된 인스턴스 없음');
        
        if ($hasInstances) {
            echo "<h3>최근 인스턴스 목록</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>인스턴스 ID</th><th>클래스</th><th>생성 시간</th></tr>";
            
            foreach ($instances as $instance) {
                $jsonld = json_decode($instance->jsonld_data, true);
                $class = $jsonld['@type'] ?? 'Unknown';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($instance->instance_id) . "</td>";
                echo "<td>" . htmlspecialchars($class) . "</td>";
                echo "<td>" . date('Y-m-d H:i:s', $instance->created_at) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
    } catch (Exception $e) {
        recordTest('데이터베이스 검증', false, '에러 발생: ' . $e->getMessage() . " [File: " . $e->getFile() . ", Line: " . $e->getLine() . "]");
    }
    
    echo "</div>";
}

// 테스트 실행
echo "<h2>테스트 시작</h2>";

testScenario1_WeakPointDetection();
testScenario2_TTSAttentionPattern();
testScenario3_ConceptConfusion();
testErrorCase_MissingVariables();
testPerformance_ResponseTime();
testDatabase_OntologyInstances();

// 테스트 결과 요약
echo "<div class='test-section'>";
echo "<h2>테스트 결과 요약</h2>";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($r) { return $r['passed']; }));
$failedTests = $totalTests - $passedTests;
$passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;

echo "<p><strong>총 테스트:</strong> {$totalTests}</p>";
echo "<p><strong>통과:</strong> <span class='success'>{$passedTests}</span></p>";
echo "<p><strong>실패:</strong> <span class='error'>{$failedTests}</span></p>";
echo "<p><strong>통과율:</strong> {$passRate}%</p>";

if ($passRate >= 95) {
    echo "<p class='success'>✅ 테스트 통과율이 95% 이상입니다. 통합이 성공적으로 완료되었습니다.</p>";
} elseif ($passRate >= 80) {
    echo "<p class='warning'>⚠️ 테스트 통과율이 80% 이상이지만 95% 미만입니다. 일부 개선이 필요합니다.</p>";
} else {
    echo "<p class='error'>❌ 테스트 통과율이 80% 미만입니다. 심각한 문제가 있습니다.</p>";
}

echo "<h3>상세 결과</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>테스트 이름</th><th>결과</th><th>메시지</th><th>시간</th></tr>";

foreach ($testResults as $result) {
    $status = $result['passed'] ? "<span class='success'>✅ 통과</span>" : "<span class='error'>❌ 실패</span>";
    echo "<tr>";
    echo "<td>" . htmlspecialchars($result['name']) . "</td>";
    echo "<td>{$status}</td>";
    echo "<td>" . htmlspecialchars($result['message']) . "</td>";
    echo "<td>" . htmlspecialchars($result['timestamp']) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<p><strong>테스트 종료 시간:</strong> " . date('Y-m-d H:i:s') . "</p>";

echo "</body></html>";

