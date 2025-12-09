<?php
/**
 * Agent04 액션 디버깅 테스트
 * File: agent04_inspect_weakpoints/tests/test_debug_actions.php
 * 
 * decision['actions']의 실제 형식을 확인하는 디버깅 스크립트
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
    <title>Agent04 액션 디버깅</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .action-item { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .ontology-action { border-left-color: #28a745; }
        .non-ontology-action { border-left-color: #ffc107; }
    </style>
</head>
<body>";

echo "<h1>Agent04 액션 디버깅</h1>";
echo "<p><strong>학생 ID:</strong> " . $USER->id . "</p>";

$service = new AgentGardenService();

$context = [
    'student_id' => $USER->id,
    'activity_type' => 'concept_understanding',
    'concept_stage' => 'understanding',
    'pause_frequency' => 5,
    'pause_stage' => '핵심 의미 파악',
    'timestamp' => date('Y-m-d\TH:i:s\Z')
];

try {
    // 룰 평가기 직접 호출하여 decision 확인
    $agent04RulesPath = __DIR__ . '/../rules';
    $rulesFilePath = $agent04RulesPath . '/rules.yaml';
    $ruleEvaluatorPath = $agent04RulesPath . '/rule_evaluator.php';
    
    require_once($ruleEvaluatorPath);
    $evaluator = new InspectWeakpointsRuleEvaluator($rulesFilePath);
    
    echo "<div class='section'>";
    echo "<h2>1. 룰 평가 결과 (Decision)</h2>";
    $decision = $evaluator->evaluate($context);
    echo "<pre>" . htmlspecialchars(json_encode($decision, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Actions 분석</h2>";
    
    if (isset($decision['actions']) && is_array($decision['actions'])) {
        echo "<p><strong>총 액션 수:</strong> " . count($decision['actions']) . "</p>";
        
        $ontologyActions = [];
        $nonOntologyActions = [];
        
        foreach ($decision['actions'] as $idx => $action) {
            $actionStr = is_array($action) ? json_encode($action, JSON_UNESCAPED_UNICODE) : (string)$action;
            $isOntology = false;
            
            // 온톨로지 액션 감지
            if (is_array($action)) {
                $ontologyKeys = ['create_instance', 'set_property', 'reason_over', 'generate_reinforcement_plan'];
                foreach ($ontologyKeys as $key) {
                    if (isset($action[$key])) {
                        $isOntology = true;
                        break;
                    }
                }
            } else {
                if (preg_match('/create_instance|reason_over|generate_reinforcement_plan|set_property.*(mk:|mk-a04:)/i', $actionStr)) {
                    $isOntology = true;
                }
            }
            
            $class = $isOntology ? 'ontology-action' : 'non-ontology-action';
            echo "<div class='action-item {$class}'>";
            echo "<strong>액션 #{$idx}</strong> (" . ($isOntology ? '온톨로지 액션' : '일반 액션') . ")<br>";
            echo "<pre>" . htmlspecialchars($actionStr) . "</pre>";
            echo "</div>";
            
            if ($isOntology) {
                $ontologyActions[] = $action;
            } else {
                $nonOntologyActions[] = $action;
            }
        }
        
        echo "<h3>온톨로지 액션 요약</h3>";
        echo "<p><strong>온톨로지 액션 수:</strong> " . count($ontologyActions) . "</p>";
        echo "<p><strong>일반 액션 수:</strong> " . count($nonOntologyActions) . "</p>";
        
        if (!empty($ontologyActions)) {
            echo "<h3>온톨로지 액션 상세</h3>";
            echo "<pre>" . htmlspecialchars(json_encode($ontologyActions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Actions가 없거나 배열이 아닙니다.</p>";
    }
    
    echo "</div>";
    
    // processOntologyActions 호출 후 확인
    echo "<div class='section'>";
    echo "<h2>3. processOntologyActions 호출 후</h2>";
    
    // OntologyActionHandler 직접 테스트
    echo "<h3>3.1 OntologyActionHandler 직접 테스트</h3>";
    $ontologyHandlerPath = __DIR__ . '/../ontology/OntologyActionHandler.php';
    if (file_exists($ontologyHandlerPath)) {
        require_once($ontologyHandlerPath);
        echo "<p style='color: green;'>✅ OntologyActionHandler 로드 성공</p>";
        
        try {
            $handler = new OntologyActionHandler($context, $USER->id);
            echo "<p style='color: green;'>✅ OntologyActionHandler 생성 성공</p>";
            
            // 첫 번째 온톨로지 액션 테스트
            $testAction = "create_instance: 'mk-a04:WeakpointDetectionContext'";
            echo "<p><strong>테스트 액션:</strong> {$testAction}</p>";
            
            $result = $handler->executeAction($testAction);
            echo "<p><strong>실행 결과:</strong></p>";
            echo "<pre>" . htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ OntologyActionHandler 생성 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>파일:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>라인:</strong> " . $e->getLine() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ OntologyActionHandler 파일을 찾을 수 없습니다: {$ontologyHandlerPath}</p>";
    }
    
    // Reflection을 사용하여 private 메서드 호출
    echo "<h3>3.2 processOntologyActions 메서드 호출</h3>";
    try {
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('processOntologyActions');
        $method->setAccessible(true);
        
        echo "<p style='color: green;'>✅ processOntologyActions 메서드 접근 성공</p>";
        
        $decisionAfter = $method->invoke($service, 'agent04', $decision, $context, $USER->id);
        
        echo "<h4>Ontology Results</h4>";
        if (isset($decisionAfter['ontology_results'])) {
            echo "<p style='color: green;'>✅ ontology_results 있음 (" . count($decisionAfter['ontology_results']) . "개)</p>";
            echo "<pre>" . htmlspecialchars(json_encode($decisionAfter['ontology_results'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ ontology_results가 없습니다.</p>";
            echo "<p><strong>Decision keys:</strong> " . implode(', ', array_keys($decisionAfter)) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ processOntologyActions 호출 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>파일:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>라인:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    echo "</div>";
    
    // 전체 서비스 실행 결과
    echo "<div class='section'>";
    echo "<h2>4. 전체 서비스 실행 결과</h2>";
    
    $result = $service->executeAgent('agent04', $context, $USER->id);
    
    echo "<h3>Response</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
    
    echo "<h3>Ontology Results in Response</h3>";
    if (isset($result['response']['reinforcement_plan'])) {
        echo "<p style='color: green;'>✅ reinforcement_plan 있음</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result['response']['reinforcement_plan'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ reinforcement_plan 없음</p>";
    }
    
    if (isset($result['response']['reasoning_results'])) {
        echo "<p style='color: green;'>✅ reasoning_results 있음</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result['response']['reasoning_results'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ reasoning_results 없음</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section' style='border-color: red;'>";
    echo "<h2 style='color: red;'>❌ 에러 발생</h2>";
    echo "<p><strong>메시지:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>파일:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>라인:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";

