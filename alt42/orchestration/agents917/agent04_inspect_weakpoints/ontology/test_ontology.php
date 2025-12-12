<?php
/**
 * Agent04 온톨로지 테스트 파일
 * File: agent04_inspect_weakpoints/ontology/test_ontology.php
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_login();

global $DB, $USER;

require_once(__DIR__ . '/OntologyEngine.php');
require_once(__DIR__ . '/OntologyActionHandler.php');

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Agent04 온톨로지 엔진 테스트</h1>";
echo "<p>학생 ID: " . $USER->id . "</p>";

try {
    $engine = new OntologyEngine();
    
    // 1. WeakpointDetectionContext 생성 테스트
    echo "<h2>1. WeakpointDetectionContext 생성</h2>";
    $context1 = $engine->createInstance('mk-a04:WeakpointDetectionContext', [
        'mk-a04:hasStudentId' => $USER->id,
        'mk-a04:hasActivityType' => 'mk-a04:ConceptUnderstanding',
        'mk-a04:hasActivityCategory' => '개념이해',
        'mk-a04:hasSubActivity' => 'TTS 듣기',
        'mk-a04:hasDetectionTimestamp' => date('c'),
        'mk-a04:hasWeakpointSeverity' => 'mk-a04:High',
        'mk-a04:hasWeakpointPattern' => '개념 정독 단계에서 멈춤 빈번',
        'mk-a04:hasPerformanceMetrics' => ['정답률: 40%', '소요시간: 25분']
    ], $USER->id);
    
    echo "<p>생성된 인스턴스 ID: <strong>{$context1}</strong></p>";
    
    // 2. ActivityAnalysisContext 생성 테스트
    echo "<h2>2. ActivityAnalysisContext 생성</h2>";
    $context2 = $engine->createInstance('mk-a04:ActivityAnalysisContext', [
        'mk-a04:hasActivityStage' => '개념 정독',
        'mk-a04:hasPauseFrequency' => 5,
        'mk-a04:hasPauseStage' => '핵심 의미 파악',
        'mk-a04:hasAttentionScore' => 0.6,
        'mk-a04:hasGazeAttentionScore' => 0.55,
        'mk-a04:hasConceptConfusionDetected' => true,
        'mk-a04:hasConfusionType' => ['mk-a04:DefinitionVsExample']
    ], $USER->id);
    
    echo "<p>생성된 인스턴스 ID: <strong>{$context2}</strong></p>";
    
    // 부모 관계 설정
    $engine->setProperty($context2, 'mk:hasParent', $context1);
    
    // 3. 추론 테스트
    echo "<h2>3. 추론 테스트</h2>";
    $reasoningResults = $engine->reasonOver('mk-a04:ActivityAnalysisContext', null, $USER->id);
    echo "<pre>" . print_r($reasoningResults, true) . "</pre>";
    
    // 4. 보강 방안 생성 테스트
    echo "<h2>4. 보강 방안 생성</h2>";
    $reinforcementPlan = $engine->generateReinforcementPlan('mk-a04:WeakpointAnalysisDecisionModel', [
        'recommendedMethod' => '예제 중심 학습',
        'recommendedContent' => ['concept_comparison_definition_vs_example', 'example_focused_learning'],
        'interventionType' => 'mk-a04:ConceptWeakPointSupport',
        'feedbackMessage' => '핵심 의미 파악 단계에서 멈추는 패턴이 보입니다. 이 구간을 집중적으로 보강해볼까요?',
        'expectedImpact' => '개념 이해도 향상, 멈춤 빈도 감소, 학습 효율 20% 향상'
    ], $USER->id);
    
    echo "<pre>" . print_r($reinforcementPlan, true) . "</pre>";
    
    // 5. OntologyActionHandler 테스트
    echo "<h2>5. OntologyActionHandler 테스트</h2>";
    $handler = new OntologyActionHandler([
        'activityType' => 'mk-a04:ConceptUnderstanding',
        'activityCategory' => '개념이해',
        'pauseFrequency' => 5,
        'attentionScore' => 0.6
    ], $USER->id);
    
    $actionResult = $handler->executeAction("create_instance: 'mk-a04:WeakpointDetectionContext'");
    echo "<pre>" . print_r($actionResult, true) . "</pre>";
    
    echo "<h2>✅ 모든 테스트 완료</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ 에러 발생</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>파일: " . $e->getFile() . "</p>";
    echo "<p>라인: " . $e->getLine() . "</p>";
}

