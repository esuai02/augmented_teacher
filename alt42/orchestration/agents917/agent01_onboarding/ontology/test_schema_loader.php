<?php
/**
 * SchemaLoader 단위 테스트
 * File: agent01_onboarding/ontology/test_schema_loader.php
 * 
 * Moodle 환경 없이 실행 가능한 경량 테스트
 * 
 * 사용법:
 * - 브라우저: https://mathking.kr/.../ontology/test_schema_loader.php
 * - CLI: php test_schema_loader.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/SchemaLoader.php');

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
    } catch (Error $e) {
        $result['status'] = 'failed';
        $result['message'] = '✗ Error: ' . $e->getMessage();
        $failed++;
    }
    
    $result['duration'] = round((microtime(true) - $start) * 1000, 2);
    return $result;
}

function assert_true($condition, $msg = '') {
    if (!$condition) throw new Exception($msg ?: 'Expected true');
}

function assert_false($condition, $msg = '') {
    if ($condition) throw new Exception($msg ?: 'Expected false');
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

function assert_gt($expected, $actual, $msg = '') {
    if ($actual <= $expected) throw new Exception($msg ?: "Expected {$actual} > {$expected}");
}

function assert_contains($needle, $haystack, $msg = '') {
    if (!in_array($needle, $haystack)) {
        throw new Exception($msg ?: "Array does not contain: " . json_encode($needle));
    }
}

function assert_has_key($key, $array, $msg = '') {
    if (!array_key_exists($key, $array)) {
        throw new Exception($msg ?: "Array does not have key: {$key}");
    }
}

// ========== 테스트 실행 ==========

$tests[] = runTest('스키마 파일 로드', function() {
    $loader = new SchemaLoader();
    $diag = $loader->getDiagnostics();
    assert_true($diag['schema_loaded'], '스키마가 로드되어야 함');
});

$tests[] = runTest('클래스 개수 확인 (5개 이상)', function() {
    $loader = new SchemaLoader();
    $classes = $loader->getAllClasses();
    assert_gt(4, count($classes), '최소 5개 이상의 클래스');
});

$tests[] = runTest('프로퍼티 개수 확인 (10개 이상)', function() {
    $loader = new SchemaLoader();
    $properties = $loader->getAllProperties();
    assert_gt(9, count($properties), '최소 10개 이상의 프로퍼티');
});

$tests[] = runTest('mk:OnboardingContext 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:OnboardingContext'));
});

$tests[] = runTest('mk:FirstClassStrategy 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:FirstClassStrategy'));
});

$tests[] = runTest('mk:LearningContextIntegration 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:LearningContextIntegration'));
});

$tests[] = runTest('mk:LessonProcedure 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:LessonProcedure'));
});

$tests[] = runTest('mk:ExplanationStrategy 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:ExplanationStrategy'));
});

$tests[] = runTest('mk:IntroductionRoutine 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:IntroductionRoutine'));
});

$tests[] = runTest('mk:MaterialType 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:MaterialType'));
});

$tests[] = runTest('mk:DifficultyLevel 클래스 존재', function() {
    $loader = new SchemaLoader();
    assert_true($loader->classExists('mk:DifficultyLevel'));
});

$tests[] = runTest('존재하지 않는 클래스 확인', function() {
    $loader = new SchemaLoader();
    assert_false($loader->classExists('mk:NonExistentClass'));
});

$tests[] = runTest('gradeLevel 프로퍼티 정의', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('gradeLevel');
    assert_not_null($propDef);
    assert_eq('xsd:string', $propDef['type']);
});

$tests[] = runTest('schoolName 프로퍼티 정의', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('schoolName');
    assert_not_null($propDef);
    assert_eq('xsd:string', $propDef['type']);
});

$tests[] = runTest('academyName 프로퍼티 정의', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('academyName');
    assert_not_null($propDef);
});

$tests[] = runTest('mathSelfConfidence 프로퍼티 (integer)', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('mathSelfConfidence');
    assert_not_null($propDef);
    assert_eq('xsd:integer', $propDef['type']);
});

$tests[] = runTest('studentId 프로퍼티 (integer)', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('studentId');
    assert_not_null($propDef);
    assert_eq('xsd:integer', $propDef['type']);
});

$tests[] = runTest('stepOrder 프로퍼티 (integer)', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('stepOrder');
    assert_not_null($propDef);
    assert_eq('xsd:integer', $propDef['type']);
});

$tests[] = runTest('hasConceptProgress 프로퍼티 (@id)', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('hasConceptProgress');
    assert_not_null($propDef);
    assert_eq('@id', $propDef['type']);
});

$tests[] = runTest('recommendsDifficulty 프로퍼티 (@id)', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('recommendsDifficulty');
    assert_not_null($propDef);
    assert_eq('@id', $propDef['type']);
});

$tests[] = runTest('존재하지 않는 프로퍼티', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('nonExistentProperty');
    assert_eq(null, $propDef);
});

$tests[] = runTest('getPropertyType 메서드', function() {
    $loader = new SchemaLoader();
    $type = $loader->getPropertyType('gradeLevel');
    assert_eq('xsd:string', $type);
});

$tests[] = runTest('클래스 정의 조회', function() {
    $loader = new SchemaLoader();
    $classDef = $loader->getClassDefinition('mk:OnboardingContext');
    assert_not_null($classDef);
    assert_has_key('id', $classDef);
    assert_has_key('label', $classDef);
});

$tests[] = runTest('클래스 상위 클래스 조회', function() {
    $loader = new SchemaLoader();
    $superClass = $loader->getSuperClass('mk:OnboardingContext');
    assert_eq('mk:Context', $superClass);
});

$tests[] = runTest('FirstClassStrategy 상위 클래스', function() {
    $loader = new SchemaLoader();
    $superClass = $loader->getSuperClass('mk:FirstClassStrategy');
    assert_eq('mk:Strategy', $superClass);
});

$tests[] = runTest('클래스 계층 구조', function() {
    $loader = new SchemaLoader();
    $hierarchy = $loader->getClassHierarchy('mk:OnboardingContext');
    assert_true(is_array($hierarchy));
});

$tests[] = runTest('인스턴스 검증 - 유효한 데이터', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateInstance('mk:OnboardingContext', [
        '@id' => 'mk:test/instance_1',
        '@type' => 'mk:OnboardingContext',
        'gradeLevel' => '중2'
    ]);
    assert_true($result['valid']);
});

$tests[] = runTest('인스턴스 검증 - 존재하지 않는 클래스', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateInstance('mk:NonExistentClass', [
        '@id' => 'mk:test/instance_1'
    ]);
    assert_false($result['valid']);
    assert_gt(0, count($result['errors']));
});

$tests[] = runTest('인스턴스 검증 - 정의되지 않은 프로퍼티 경고', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateInstance('mk:OnboardingContext', [
        '@id' => 'mk:test/instance_1',
        '@type' => 'mk:OnboardingContext',
        'undefinedProperty' => 'value'
    ]);
    // 경고는 있지만 유효함 (하위 호환성)
    assert_true(is_array($result['errors']));
});

$tests[] = runTest('rules.yaml 액션 검증 - create_instance 유효', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "create_instance: 'mk:OnboardingContext'"
    ]);
    assert_true($result['valid']);
    assert_gt(0, count($result['mappings']));
});

$tests[] = runTest('rules.yaml 액션 검증 - create_instance 무효', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "create_instance: 'mk:NonExistentClass'"
    ]);
    assert_false($result['valid']);
});

$tests[] = runTest('rules.yaml 액션 검증 - reason_over', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "reason_over: 'mk:LearningContextIntegration'"
    ]);
    assert_true($result['valid']);
});

$tests[] = runTest('rules.yaml 액션 검증 - generate_strategy', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "generate_strategy: 'mk:FirstClassStrategy'"
    ]);
    assert_true($result['valid']);
});

$tests[] = runTest('rules.yaml 액션 검증 - generate_procedure', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "generate_procedure: 'mk:LessonProcedure'"
    ]);
    assert_true($result['valid']);
});

$tests[] = runTest('rules.yaml 액션 검증 - set_property', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "set_property: ('mk:hasStudentGrade', '{gradeLevel}')"
    ]);
    assert_true(is_array($result['mappings']));
});

$tests[] = runTest('rules.yaml 액션 검증 - 복합 액션', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "create_instance: 'mk:OnboardingContext'",
        "set_property: ('mk:hasStudentGrade', '{gradeLevel}')",
        "reason_over: 'mk:OnboardingContext'",
        "generate_strategy: 'mk:FirstClassStrategy'",
        "generate_procedure: 'mk:LessonProcedure'"
    ]);
    assert_true($result['valid']);
    assert_eq(5, count($result['mappings']));
});

$tests[] = runTest('진단 정보 반환', function() {
    $loader = new SchemaLoader();
    $diag = $loader->getDiagnostics();
    assert_has_key('schema_loaded', $diag);
    assert_has_key('schema_path', $diag);
    assert_has_key('class_count', $diag);
    assert_has_key('property_count', $diag);
    assert_has_key('classes', $diag);
    assert_has_key('properties', $diag);
});

$tests[] = runTest('getAllClasses 반환 형식', function() {
    $loader = new SchemaLoader();
    $classes = $loader->getAllClasses();
    assert_true(is_array($classes));
    foreach ($classes as $id => $def) {
        assert_has_key('id', $def);
        assert_has_key('label', $def);
    }
});

$tests[] = runTest('getAllProperties 반환 형식', function() {
    $loader = new SchemaLoader();
    $properties = $loader->getAllProperties();
    assert_true(is_array($properties));
    foreach ($properties as $name => $def) {
        assert_has_key('id', $def);
        assert_has_key('shortName', $def);
    }
});

$tests[] = runTest('변수 매핑 검증 - 유효', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateVariableMappings([
        'gradeLevel' => ['student_grade', 'grade_level']
    ]);
    assert_gt(0, count($result['matched']));
});

$tests[] = runTest('변수 매핑 검증 - 무효', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateVariableMappings([
        'nonExistentVar' => ['some_key']
    ]);
    assert_gt(0, count($result['unmatched']));
});

// ========== 공식 매핑 테이블 테스트 ==========
$tests[] = runTest('공식 매핑 테이블 존재', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_true(is_array($mapping));
    assert_gt(20, count($mapping));
});

$tests[] = runTest('공식 매핑 - concept_progress', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_has_key('concept_progress', $mapping);
    assert_eq('conceptProgressLevel', $mapping['concept_progress']);
});

$tests[] = runTest('공식 매핑 - advanced_progress', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_has_key('advanced_progress', $mapping);
    assert_eq('advancedProgressLevel', $mapping['advanced_progress']);
});

$tests[] = runTest('공식 매핑 - math_learning_style', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_has_key('math_learning_style', $mapping);
    assert_eq('mathLearningStyle', $mapping['math_learning_style']);
});

$tests[] = runTest('공식 매핑 - math_confidence', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_has_key('math_confidence', $mapping);
    assert_eq('mathSelfConfidence', $mapping['math_confidence']);
});

$tests[] = runTest('공식 매핑 - exam_style', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_has_key('exam_style', $mapping);
    assert_eq('examPreparationStyle', $mapping['exam_style']);
});

$tests[] = runTest('공식 매핑 - 관계 프로퍼티 (hasConceptProgress)', function() {
    $mapping = SchemaLoader::getOfficialVariableMapping();
    assert_has_key('hasConceptProgress', $mapping);
});

$tests[] = runTest('mapContextToOntology 메서드', function() {
    $loader = new SchemaLoader();
    $result = $loader->mapContextToOntology('concept_progress');
    assert_eq('conceptProgressLevel', $result);
});

$tests[] = runTest('mapContextToOntology - 존재하지 않는 키', function() {
    $loader = new SchemaLoader();
    $result = $loader->mapContextToOntology('nonExistentKey');
    assert_null($result);
});

$tests[] = runTest('mapOntologyToContext 메서드', function() {
    $loader = new SchemaLoader();
    $result = $loader->mapOntologyToContext('conceptProgressLevel');
    assert_not_null($result);
});

$tests[] = runTest('변수 매핑 검증 - concept_progress 공식 매핑', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateVariableMappings([
        'concept_progress' => ['concept_progress', 'conceptProgress']
    ]);
    // 공식 매핑 테이블에 있으므로 매칭되어야 함
    assert_gt(0, count($result['matched']));
});

$tests[] = runTest('변수 매핑 검증 - math_learning_style 공식 매핑', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateVariableMappings([
        'math_learning_style' => ['math_learning_style', 'mathLearningStyle']
    ]);
    assert_gt(0, count($result['matched']));
});

$tests[] = runTest('변수 매핑 검증 - 모든 미매칭 변수 해결', function() {
    $loader = new SchemaLoader();
    // 기존에 미매칭이었던 10개 변수
    $previouslyUnmatched = [
        'concept_progress' => ['concept_progress', 'conceptProgress'],
        'advanced_progress' => ['advanced_progress', 'advancedProgress'],
        'math_unit_mastery' => ['math_unit_mastery', 'unitMastery'],
        'current_progress_position' => ['current_progress_position', 'currentPosition'],
        'math_learning_style' => ['math_learning_style', 'mathLearningStyle'],
        'study_style' => ['study_style', 'studyStyle'],
        'exam_style' => ['exam_style', 'examStyle'],
        'math_confidence' => ['math_confidence', 'mathConfidence'],
        'math_level' => ['math_level', 'mathLevel'],
        'math_stress_level' => ['math_stress_level', 'mathStressLevel']
    ];
    
    $result = $loader->validateVariableMappings($previouslyUnmatched);
    
    // 모두 매칭되어야 함 (unmatched가 0이어야 함)
    assert_eq(0, count($result['unmatched']), 
        "미매칭 변수가 있음: " . implode(', ', array_keys($result['unmatched'])));
});

// ========== 결과 출력 ==========
$total = count($tests);

if ($isWeb) {
    echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>SchemaLoader 테스트 결과</title>
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
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <h1>🔬 SchemaLoader 단위 테스트</h1>
    <p class='timestamp'>실행 시간: " . date('Y-m-d H:i:s') . " | Moodle 환경 불필요</p>
    
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
    // CLI 출력
    echo "\n========================================\n";
    echo "   SchemaLoader 단위 테스트\n";
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

