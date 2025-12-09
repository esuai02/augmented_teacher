<?php
/**
 * 룰 매칭 테스트 파일
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_rule_matching.php
 * 
 * 룰 매칭이 올바르게 되는지 확인하기 위한 디버깅 파일
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$testUserId = isset($_GET['userid']) ? intval($_GET['userid']) : 810;

if (!isset($USER) || !$USER->id) {
    $USER = new stdClass();
    $USER->id = $testUserId;
    $USER->username = 'test_user_' . $testUserId;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $testRequest = isset($_GET['request']) ? $_GET['request'] : '첫 수업을 어떻게 시작해야 할지 알려주세요';
    
    // data_access.php 로드
    $dataAccessPath = __DIR__ . '/../../agent01_onboarding/rules/data_access.php';
    require_once($dataAccessPath);
    
    $context = prepareRuleContext($testUserId);
    if ($context === null) {
        $context = ['student_id' => $testUserId];
    }
    
    // user_message 추가
    $context['user_message'] = $testRequest;
    $context['conversation_timestamp'] = time();
    
    // 룰 평가기 로드
    $rulesFilePath = __DIR__ . '/../../agent01_onboarding/rules/rules.yaml';
    $ruleEvaluatorPath = __DIR__ . '/../../agent01_onboarding/rules/rule_evaluator.php';
    require_once($ruleEvaluatorPath);
    
    $evaluator = new OnboardingRuleEvaluator($rulesFilePath);
    $decision = $evaluator->evaluate($context);
    
    echo json_encode([
        'success' => true,
        'test_info' => [
            'request' => $testRequest,
            'user_id' => $testUserId,
            'user_message_in_context' => $context['user_message'] ?? 'NOT SET'
        ],
        'context_sample' => [
            'student_id' => $context['student_id'] ?? null,
            'user_message' => $context['user_message'] ?? null,
            'math_learning_style' => $context['math_learning_style'] ?? null,
            'academy_name' => $context['academy_name'] ?? null
        ],
        'decision' => [
            'rule_id' => $decision['rule_id'] ?? null,
            'matched_rule' => $decision['matched_rule'] ?? null,
            'confidence' => $decision['confidence'] ?? null,
            'actions_count' => isset($decision['actions']) ? count($decision['actions']) : 0
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

