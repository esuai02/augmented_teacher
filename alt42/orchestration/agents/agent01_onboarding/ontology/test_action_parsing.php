<?php
/**
 * OntologyActionHandler 액션 파싱 테스트
 * File: agent01_onboarding/ontology/test_action_parsing.php
 * 
 * 액션 파싱 로직만 테스트 (DB 연결 불필요)
 * 
 * 사용법:
 * - 브라우저: https://mathking.kr/.../ontology/test_action_parsing.php
 * - CLI: php test_action_parsing.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$isCli = php_sapi_name() === 'cli';
$isWeb = !$isCli;

// 테스트 결과 저장
$tests = [];
$passed = 0;
$failed = 0;

function runTest(string $name, callable $fn): array {
    global $passed, $failed;
    
    $result = [
        'name' => $name,
        'status' => 'pending',
        'message' => '',
        'duration' => 0
    ];
    
    $start = microtime(true);
    
    try {
        $fn();
        $result['status'] = 'passed';
        $result['message'] = '✓ 통과';
        $passed++;
    } catch (Exception $e) {
        $result['status'] = 'failed';
        $result['message'] = '✗ ' . $e->getMessage();
        $failed++;
    }
    
    $result['duration'] = round((microtime(true) - $start) * 1000, 2);
    return $result;
}

function assert_eq($expected, $actual, $msg = '') {
    if ($expected !== $actual) {
        throw new Exception($msg ?: "Expected " . json_encode($expected) . ", got " . json_encode($actual));
    }
}

function assert_not_null($value, $msg = '') {
    if ($value === null) throw new Exception($msg ?: 'Expected non-null');
}

function assert_null($value, $msg = '') {
    if ($value !== null) throw new Exception($msg ?: 'Expected null, got ' . json_encode($value));
}

// ========== 액션 파싱 함수 (OntologyActionHandler에서 추출) ==========
function parseAction($action): ?array {
    // 배열인 경우 직접 처리
    if (is_array($action)) {
        if (isset($action['create_instance'])) {
            return [
                'type' => 'create_instance',
                'params' => ['class' => $action['create_instance']]
            ];
        }
        
        if (isset($action['set_property'])) {
            $propertyStr = $action['set_property'];
            if (preg_match("/\(['\"](.+?)['\"],\s*['\"](.+?)['\"]\)/", $propertyStr, $matches)) {
                return [
                    'type' => 'set_property',
                    'params' => [
                        'property' => $matches[1],
                        'value' => $matches[2]
                    ]
                ];
            }
        }
        
        if (isset($action['reason_over'])) {
            return [
                'type' => 'reason_over',
                'params' => ['class' => $action['reason_over']]
            ];
        }
        
        if (isset($action['generate_strategy'])) {
            return [
                'type' => 'generate_strategy',
                'params' => ['class' => $action['generate_strategy']]
            ];
        }
        
        if (isset($action['generate_procedure'])) {
            return [
                'type' => 'generate_procedure',
                'params' => ['class' => $action['generate_procedure']]
            ];
        }
        
        $action = json_encode($action);
    }
    
    if (!is_string($action)) {
        return null;
    }
    
    // create_instance: 'mk:OnboardingContext'
    if (preg_match("/^create_instance:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
        return [
            'type' => 'create_instance',
            'params' => ['class' => $matches[1]]
        ];
    }
    
    // set_property: ('mk:hasStudentGrade', '{gradeLevel}')
    if (preg_match("/^set_property:\s*\(['\"](.+?)['\"],\s*['\"](.+?)['\"]\)$/", trim($action), $matches)) {
        return [
            'type' => 'set_property',
            'params' => [
                'property' => $matches[1],
                'value' => $matches[2]
            ]
        ];
    }
    
    // reason_over: 'mk:LearningContextIntegration'
    if (preg_match("/^reason_over:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
        return [
            'type' => 'reason_over',
            'params' => ['class' => $matches[1]]
        ];
    }
    
    // generate_strategy: 'mk:FirstClassStrategy'
    if (preg_match("/^generate_strategy:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
        return [
            'type' => 'generate_strategy',
            'params' => ['class' => $matches[1]]
        ];
    }
    
    // generate_procedure: 'mk:LessonProcedure'
    if (preg_match("/^generate_procedure:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
        return [
            'type' => 'generate_procedure',
            'params' => ['class' => $matches[1]]
        ];
    }
    
    return null;
}

// ========== 테스트 케이스 ==========

// 1. create_instance 파싱 테스트
$tests[] = runTest('create_instance 문자열 (작은따옴표)', function() {
    $result = parseAction("create_instance: 'mk:OnboardingContext'");
    assert_not_null($result);
    assert_eq('create_instance', $result['type']);
    assert_eq('mk:OnboardingContext', $result['params']['class']);
});

$tests[] = runTest('create_instance 문자열 (큰따옴표)', function() {
    $result = parseAction('create_instance: "mk:OnboardingContext"');
    assert_not_null($result);
    assert_eq('create_instance', $result['type']);
    assert_eq('mk:OnboardingContext', $result['params']['class']);
});

$tests[] = runTest('create_instance 배열', function() {
    $result = parseAction(['create_instance' => 'mk:OnboardingContext']);
    assert_not_null($result);
    assert_eq('create_instance', $result['type']);
    assert_eq('mk:OnboardingContext', $result['params']['class']);
});

$tests[] = runTest('create_instance - LearningContextIntegration', function() {
    $result = parseAction("create_instance: 'mk:LearningContextIntegration'");
    assert_not_null($result);
    assert_eq('mk:LearningContextIntegration', $result['params']['class']);
});

$tests[] = runTest('create_instance - FirstClassStrategy', function() {
    $result = parseAction("create_instance: 'mk:FirstClassStrategy'");
    assert_not_null($result);
    assert_eq('mk:FirstClassStrategy', $result['params']['class']);
});

// 2. set_property 파싱 테스트
$tests[] = runTest('set_property 문자열 (작은따옴표)', function() {
    $result = parseAction("set_property: ('mk:hasStudentGrade', '{gradeLevel}')");
    assert_not_null($result);
    assert_eq('set_property', $result['type']);
    assert_eq('mk:hasStudentGrade', $result['params']['property']);
    assert_eq('{gradeLevel}', $result['params']['value']);
});

$tests[] = runTest('set_property 문자열 (큰따옴표)', function() {
    $result = parseAction('set_property: ("mk:hasSchool", "{schoolName}")');
    assert_not_null($result);
    assert_eq('set_property', $result['type']);
    assert_eq('mk:hasSchool', $result['params']['property']);
    assert_eq('{schoolName}', $result['params']['value']);
});

$tests[] = runTest('set_property 배열', function() {
    $result = parseAction(['set_property' => "('mk:hasStudentGrade', '{gradeLevel}')"]);
    assert_not_null($result);
    assert_eq('set_property', $result['type']);
    assert_eq('mk:hasStudentGrade', $result['params']['property']);
});

$tests[] = runTest('set_property - 다양한 프로퍼티', function() {
    $properties = [
        ['mk:hasConceptProgress', '{concept_progress}'],
        ['mk:hasAdvancedProgress', '{advanced_progress}'],
        ['mk:hasMathConfidence', '{math_confidence}'],
        ['mk:hasMathLearningStyle', '{math_learning_style}']
    ];
    
    foreach ($properties as [$prop, $val]) {
        $result = parseAction("set_property: ('{$prop}', '{$val}')");
        assert_not_null($result);
        assert_eq($prop, $result['params']['property']);
        assert_eq($val, $result['params']['value']);
    }
});

// 3. reason_over 파싱 테스트
$tests[] = runTest('reason_over 문자열', function() {
    $result = parseAction("reason_over: 'mk:LearningContextIntegration'");
    assert_not_null($result);
    assert_eq('reason_over', $result['type']);
    assert_eq('mk:LearningContextIntegration', $result['params']['class']);
});

$tests[] = runTest('reason_over 배열', function() {
    $result = parseAction(['reason_over' => 'mk:OnboardingContext']);
    assert_not_null($result);
    assert_eq('reason_over', $result['type']);
    assert_eq('mk:OnboardingContext', $result['params']['class']);
});

// 4. generate_strategy 파싱 테스트
$tests[] = runTest('generate_strategy 문자열', function() {
    $result = parseAction("generate_strategy: 'mk:FirstClassStrategy'");
    assert_not_null($result);
    assert_eq('generate_strategy', $result['type']);
    assert_eq('mk:FirstClassStrategy', $result['params']['class']);
});

$tests[] = runTest('generate_strategy 배열', function() {
    $result = parseAction(['generate_strategy' => 'mk:FirstClassStrategy']);
    assert_not_null($result);
    assert_eq('generate_strategy', $result['type']);
});

// 5. generate_procedure 파싱 테스트
$tests[] = runTest('generate_procedure 문자열', function() {
    $result = parseAction("generate_procedure: 'mk:LessonProcedure'");
    assert_not_null($result);
    assert_eq('generate_procedure', $result['type']);
    assert_eq('mk:LessonProcedure', $result['params']['class']);
});

$tests[] = runTest('generate_procedure 배열', function() {
    $result = parseAction(['generate_procedure' => 'mk:LessonProcedure']);
    assert_not_null($result);
    assert_eq('generate_procedure', $result['type']);
});

// 6. 잘못된 액션 테스트
$tests[] = runTest('잘못된 액션 - null 반환', function() {
    $result = parseAction("invalid_action: 'test'");
    assert_null($result);
});

$tests[] = runTest('잘못된 액션 - 빈 문자열', function() {
    $result = parseAction("");
    assert_null($result);
});

$tests[] = runTest('잘못된 액션 - 숫자', function() {
    $result = parseAction(123);
    assert_null($result);
});

$tests[] = runTest('잘못된 액션 - 불완전한 형식', function() {
    $result = parseAction("create_instance:");
    assert_null($result);
});

// 7. 공백 처리 테스트
$tests[] = runTest('앞뒤 공백 처리', function() {
    $result = parseAction("  create_instance: 'mk:OnboardingContext'  ");
    assert_not_null($result);
    assert_eq('create_instance', $result['type']);
});

$tests[] = runTest('콜론 뒤 공백', function() {
    $result = parseAction("create_instance:   'mk:OnboardingContext'");
    assert_not_null($result);
    assert_eq('mk:OnboardingContext', $result['params']['class']);
});

// 8. rules.yaml에서 추출한 실제 액션 테스트
$tests[] = runTest('rules.yaml 실제 액션 1', function() {
    $result = parseAction("create_instance: 'mk:MathLearningStyle'");
    assert_not_null($result);
    assert_eq('mk:MathLearningStyle', $result['params']['class']);
});

$tests[] = runTest('rules.yaml 실제 액션 2', function() {
    $result = parseAction("set_property: ('mk:mathLearningStyle', '{math_learning_style}')");
    assert_not_null($result);
    assert_eq('mk:mathLearningStyle', $result['params']['property']);
});

$tests[] = runTest('rules.yaml 실제 액션 3', function() {
    $result = parseAction("reason_over: 'mk:LearningContextIntegration'");
    assert_not_null($result);
    assert_eq('mk:LearningContextIntegration', $result['params']['class']);
});

$tests[] = runTest('rules.yaml 실제 액션 4', function() {
    $result = parseAction("generate_strategy: 'mk:FirstClassStrategy'");
    assert_not_null($result);
    assert_eq('mk:FirstClassStrategy', $result['params']['class']);
});

$tests[] = runTest('rules.yaml 실제 액션 5', function() {
    $result = parseAction("generate_procedure: 'mk:LessonProcedure'");
    assert_not_null($result);
    assert_eq('mk:LessonProcedure', $result['params']['class']);
});

// 9. Q1 룰의 전체 액션 시퀀스 테스트
$tests[] = runTest('Q1 룰 액션 시퀀스 파싱', function() {
    $actions = [
        "create_instance: 'mk:OnboardingContext'",
        "set_property: ('mk:hasStudentGrade', '{gradeLevel}')",
        "set_property: ('mk:hasSchool', '{schoolName}')",
        "set_property: ('mk:hasAcademy', '{academyName}')",
        "create_instance: 'mk:LearningContextIntegration'",
        "set_property: ('mk:hasConceptProgress', '{concept_progress}')",
        "reason_over: 'mk:LearningContextIntegration'",
        "reason_over: 'mk:OnboardingContext'",
        "generate_strategy: 'mk:FirstClassStrategy'",
        "generate_procedure: 'mk:LessonProcedure'"
    ];
    
    foreach ($actions as $action) {
        $result = parseAction($action);
        assert_not_null($result, "Failed to parse: {$action}");
    }
});

// 10. 특수 문자 처리 테스트
$tests[] = runTest('프로퍼티명에 콜론 포함', function() {
    $result = parseAction("set_property: ('mk:hasStudentGrade', '{value}')");
    assert_not_null($result);
    assert_eq('mk:hasStudentGrade', $result['params']['property']);
});

$tests[] = runTest('값에 중괄호 포함', function() {
    $result = parseAction("set_property: ('mk:test', '{variable_name}')");
    assert_not_null($result);
    assert_eq('{variable_name}', $result['params']['value']);
});

// ========== 결과 출력 ==========
$total = count($tests);

if ($isWeb) {
    echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>액션 파싱 테스트 결과</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Pretendard', -apple-system, sans-serif; 
            background: #0f0f23; 
            color: #e0e0e0; 
            padding: 20px;
            line-height: 1.6;
        }
        h1 { 
            color: #00d9ff; 
            margin-bottom: 20px; 
            font-size: 1.8em;
            border-bottom: 2px solid #00d9ff;
            padding-bottom: 10px;
        }
        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-item {
            background: #1a1a2e;
            border-radius: 10px;
            padding: 20px 30px;
            text-align: center;
            border: 1px solid #333;
        }
        .summary-item .number {
            font-size: 2.5em;
            font-weight: bold;
        }
        .summary-item .label {
            color: #888;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .summary-item.passed .number { color: #00c853; }
        .summary-item.failed .number { color: #ff5252; }
        
        .tests {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #333;
        }
        .test {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #333;
        }
        .test:last-child { border-bottom: none; }
        .test-name { flex: 1; font-size: 0.95em; }
        .test-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .test-status.passed { background: #00c853; color: #000; }
        .test-status.failed { background: #ff5252; color: #fff; }
        .test-duration {
            color: #666;
            font-size: 0.75em;
            margin-left: 10px;
            min-width: 50px;
            text-align: right;
        }
        .test-message {
            color: #ff5252;
            font-size: 0.8em;
            margin-top: 3px;
        }
        .timestamp {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 20px;
        }
        .progress-bar {
            height: 8px;
            background: #333;
            border-radius: 4px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .progress-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, #00c853, #00d9ff);
        }
    </style>
</head>
<body>
    <h1>🔧 액션 파싱 테스트</h1>
    <p class='timestamp'>실행 시간: " . date('Y-m-d H:i:s') . " | DB 연결 불필요</p>
    
    <div class='progress-bar'>
        <div class='fill' style='width: " . ($total > 0 ? round($passed / $total * 100) : 0) . "%;'></div>
    </div>
    
    <div class='summary'>
        <div class='summary-item'>
            <div class='number'>{$total}</div>
            <div class='label'>전체</div>
        </div>
        <div class='summary-item passed'>
            <div class='number'>{$passed}</div>
            <div class='label'>통과</div>
        </div>
        <div class='summary-item failed'>
            <div class='number'>{$failed}</div>
            <div class='label'>실패</div>
        </div>
    </div>
    
    <div class='tests'>";
    
    foreach ($tests as $test) {
        $statusClass = $test['status'];
        $message = $test['status'] === 'failed' ? "<div class='test-message'>{$test['message']}</div>" : '';
        echo "<div class='test'>
            <div class='test-name'>
                {$test['name']}
                {$message}
            </div>
            <span class='test-status {$statusClass}'>{$test['status']}</span>
            <span class='test-duration'>{$test['duration']}ms</span>
        </div>";
    }
    
    echo "</div>
</body>
</html>";

} else {
    echo "\n========================================\n";
    echo "   액션 파싱 테스트\n";
    echo "========================================\n\n";
    
    echo "요약: 전체 {$total} | 통과 {$passed} | 실패 {$failed}\n\n";
    
    foreach ($tests as $test) {
        $icon = $test['status'] === 'passed' ? '✓' : '✗';
        echo "  {$icon} {$test['name']} ({$test['duration']}ms)\n";
        if ($test['status'] !== 'passed') {
            echo "    {$test['message']}\n";
        }
    }
    
    echo "\n";
    
    if ($failed > 0) {
        exit(1);
    }
}

