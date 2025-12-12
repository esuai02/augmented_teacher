<?php
/**
 * Agent 04 Infrastructure Test
 * DataValidator와 AgentErrorHandler 통합 테스트
 *
 * 테스트 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/tests/test_infrastructure.php
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04
 * @version     1.0.0
 * @created     2025-12-09
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 테스트할 인프라 로드
require_once(__DIR__ . '/../../engine_core/validation/DataValidator.php');
require_once(__DIR__ . '/../../engine_core/errors/AgentErrorHandler.php');
require_once(__DIR__ . '/../rules/data_access.php');

// 파라미터
$studentid = optional_param('studentid', $USER->id, PARAM_INT);
$action = optional_param('action', 'all', PARAM_TEXT);

// JSON 출력 헤더
header('Content-Type: application/json; charset=utf-8');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'agent_id' => 'agent04',
    'student_id' => $studentid,
    'tests' => []
];

try {
    // Test 1: DataValidator 인스턴스 생성
    if ($action === 'all' || $action === 'validator') {
        $testStart = microtime(true);
        $validator = new DataValidator($DB, $studentid, 'agent04');
        $results['tests']['validator_init'] = [
            'status' => 'pass',
            'message' => 'DataValidator 인스턴스 생성 성공',
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // Test 2: 데이터 소스 검증
    if ($action === 'all' || $action === 'validation') {
        $testStart = microtime(true);
        $validation = validateAgent04DataSources($studentid);
        $results['tests']['data_validation'] = [
            'status' => $validation['valid'] ? 'pass' : 'warning',
            'message' => $validation['valid'] ? '모든 데이터 소스 검증 성공' : '일부 데이터 소스 검증 실패',
            'checked' => $validation['checked'] ?? 0,
            'missing' => $validation['missing'] ?? [],
            'warnings' => $validation['warnings'] ?? [],
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // Test 3: 테이블 빠른 검증
    if ($action === 'all' || $action === 'tables') {
        $testStart = microtime(true);
        $validator = new DataValidator($DB, $studentid, 'agent04');
        $tableCheck = $validator->quickValidateTables([
            'mdl_alt42_student_activity',
            'mdl_user',
            'mdl_quiz_attempts'
        ]);
        $results['tests']['quick_table_check'] = [
            'status' => $tableCheck['valid'] ? 'pass' : 'warning',
            'message' => $tableCheck['valid'] ? '모든 테이블 존재' : '누락된 테이블: ' . implode(', ', $tableCheck['missing']),
            'missing_tables' => $tableCheck['missing'],
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // Test 4: 학생 데이터 존재 확인
    if ($action === 'all' || $action === 'student_data') {
        $testStart = microtime(true);
        $validator = new DataValidator($DB, $studentid, 'agent04');
        $hasData = $validator->hasStudentData('mdl_alt42_student_activity', 'userid');
        $dataCount = $validator->getStudentDataCount('mdl_alt42_student_activity', 'userid');
        $results['tests']['student_data_check'] = [
            'status' => 'pass',
            'message' => $hasData ? "학생 데이터 {$dataCount}건 존재" : '학생 데이터 없음',
            'has_data' => $hasData,
            'record_count' => $dataCount,
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // Test 5: AgentErrorHandler 테스트
    if ($action === 'all' || $action === 'error_handler') {
        $testStart = microtime(true);

        // 에러 코드 조회 테스트
        $testCodes = [
            'dml_exception' => AgentErrorHandler::getErrorCode('dml_exception'),
            'validation_error' => AgentErrorHandler::getErrorCode('validation_error'),
            'unknown' => AgentErrorHandler::getErrorCode('unknown')
        ];

        $results['tests']['error_handler'] = [
            'status' => 'pass',
            'message' => 'AgentErrorHandler 정상 작동',
            'error_codes' => $testCodes,
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // Test 6: getProblemActivityContext 통합 테스트
    if ($action === 'all' || $action === 'context') {
        $testStart = microtime(true);
        $context = getProblemActivityContext($studentid);
        $results['tests']['context_retrieval'] = [
            'status' => isset($context['error']) ? 'warning' : 'pass',
            'message' => isset($context['error']) ? '컨텍스트 조회 실패: ' . $context['error']['message'] : '컨텍스트 조회 성공',
            'activity_count' => count($context['recent_activities'] ?? []),
            'categories' => $context['main_categories'] ?? [],
            'validation_status' => $context['validation']['valid'] ?? null,
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // Test 7: prepareRuleContext 테스트
    if ($action === 'all' || $action === 'rule_context') {
        $testStart = microtime(true);
        $ruleContext = prepareRuleContext($studentid);
        $results['tests']['rule_context'] = [
            'status' => isset($ruleContext['error']) ? 'warning' : 'pass',
            'message' => isset($ruleContext['error']) ? '규칙 컨텍스트 준비 실패' : '규칙 컨텍스트 준비 성공',
            'has_timestamp' => isset($ruleContext['timestamp']),
            'has_agent_id' => isset($ruleContext['agent_id']),
            'duration_ms' => round((microtime(true) - $testStart) * 1000, 2)
        ];
    }

    // 전체 결과 요약
    $passCount = 0;
    $warningCount = 0;
    $failCount = 0;

    foreach ($results['tests'] as $test) {
        switch ($test['status']) {
            case 'pass':
                $passCount++;
                break;
            case 'warning':
                $warningCount++;
                break;
            case 'fail':
                $failCount++;
                break;
        }
    }

    $results['summary'] = [
        'total_tests' => count($results['tests']),
        'passed' => $passCount,
        'warnings' => $warningCount,
        'failed' => $failCount,
        'overall_status' => $failCount > 0 ? 'fail' : ($warningCount > 0 ? 'warning' : 'pass')
    ];

} catch (Exception $e) {
    $results['error'] = AgentErrorHandler::handle($e, 'agent04', 'test_infrastructure');
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * 관련 파일:
 * - agents/engine_core/validation/DataValidator.php
 * - agents/engine_core/errors/AgentErrorHandler.php
 * - agents/agent04_inspect_weakpoints/rules/data_access.php
 *
 * 테스트 실행 방법:
 * - 전체 테스트: ?action=all
 * - 개별 테스트: ?action=validator|validation|tables|student_data|error_handler|context|rule_context
 * - 학생 ID 지정: ?studentid=1603
 */
