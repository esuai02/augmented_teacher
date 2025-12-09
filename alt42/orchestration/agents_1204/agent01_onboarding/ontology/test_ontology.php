<?php
/**
 * 온톨로지 엔진 테스트
 * File: agent01_onboarding/ontology/test_ontology.php
 * 
 * 온톨로지 엔진 동작 테스트
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/OntologyEngine.php');
require_once(__DIR__ . '/OntologyActionHandler.php');

header('Content-Type: application/json; charset=utf-8');

$testResults = [];

try {
    // 테스트 컨텍스트
    $testContext = [
        'student_id' => $USER->id,
        'gradeLevel' => '중2',
        'schoolName' => '테스트중학교',
        'academyName' => '테스트학원',
        'academyGrade' => '중2 상위반',
        'math_confidence' => 4,
        'math_level' => '중위권',
        'math_learning_style' => '계산형',
        'study_style' => '개념 정리 위주',
        'exam_style' => '벼락치기',
        'concept_progress' => '중2-1 일차방정식까지',
        'advanced_progress' => '중2-1 심화 전반',
        'math_unit_mastery' => '방정식 보통, 함수 미이수'
    ];
    
    $handler = new OntologyActionHandler($testContext, $USER->id);
    
    // 테스트 1: create_instance - OnboardingContext
    $testResults['test1_create_onboarding_context'] = [
        'action' => "create_instance: 'mk:OnboardingContext'",
        'result' => $handler->executeAction("create_instance: 'mk:OnboardingContext'")
    ];
    
    // 테스트 1-2: create_instance - LearningContextIntegration
    $testResults['test1_2_create_learning_context'] = [
        'action' => "create_instance: 'mk:LearningContextIntegration'",
        'result' => $handler->executeAction("create_instance: 'mk:LearningContextIntegration'")
    ];
    
    // 테스트 2: set_property
    $testResults['test2_set_property'] = [
        'action' => "set_property: ('mk:hasStudentGrade', '중2')",
        'result' => $handler->executeAction("set_property: ('mk:hasStudentGrade', '중2')")
    ];
    
    // 테스트 3: reason_over
    $testResults['test3_reason_over'] = [
        'action' => "reason_over: 'mk:LearningContextIntegration'",
        'result' => $handler->executeAction("reason_over: 'mk:LearningContextIntegration'")
    ];
    
    // 테스트 4: generate_strategy
    $testResults['test4_generate_strategy'] = [
        'action' => "generate_strategy: 'mk:FirstClassStrategy'",
        'result' => $handler->executeAction("generate_strategy: 'mk:FirstClassStrategy'")
    ];
    
    // 테스트 5: generate_procedure
    $testResults['test5_generate_procedure'] = [
        'action' => "generate_procedure: 'mk:LessonProcedure'",
        'result' => $handler->executeAction("generate_procedure: 'mk:LessonProcedure'")
    ];
    
    // 테스트 6: 인스턴스 조회 (마지막 생성된 인스턴스)
    $engine = new OntologyEngine();
    // 직접 DB에서 조회
    try {
        $lastInstance = $DB->get_record_sql(
            "SELECT instance_id FROM {alt42_ontology_instances} 
             WHERE student_id = ? 
             ORDER BY created_at DESC 
             LIMIT 1",
            [$USER->id]
        );
        
        if ($lastInstance) {
            $testResults['test6_get_instance'] = [
                'instance_id' => $lastInstance->instance_id,
                'data' => $engine->getInstance($lastInstance->instance_id)
            ];
        }
    } catch (Exception $e) {
        $testResults['test6_get_instance'] = [
            'error' => $e->getMessage()
        ];
    }
    
    $response = [
        'success' => true,
        'message' => '온톨로지 엔진 테스트 완료',
        'student_id' => $USER->id,
        'test_results' => $testResults,
        'timestamp' => time()
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

