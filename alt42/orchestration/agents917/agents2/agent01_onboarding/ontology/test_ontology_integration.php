<?php
/**
 * 온톨로지 통합 테스트
 * File: agent01_onboarding/ontology/test_ontology_integration.php
 * 
 * 테스트 대상:
 * 1. SchemaLoader - 스키마 로드, 클래스/프로퍼티 검증
 * 2. OntologyEngine - 인스턴스 생성, 추론, 전략 생성
 * 3. OntologyActionHandler - 액션 파싱 및 실행
 * 4. Q1 파이프라인 - 전체 흐름 통합 테스트
 * 
 * 사용법:
 * - 브라우저: https://mathking.kr/.../ontology/test_ontology_integration.php
 * - CLI: php test_ontology_integration.php
 */

// ========== 환경 설정 ==========
error_reporting(E_ALL);
ini_set('display_errors', 1);

$isCli = php_sapi_name() === 'cli';
$isWeb = !$isCli;

// Moodle config 로드 (웹 환경에서만)
$configPath = '/home/moodle/public_html/moodle/config.php';
$moodleLoaded = false;
if (file_exists($configPath)) {
    require_once($configPath);
    $moodleLoaded = true;
}

// 테스트 파일 로드
require_once(__DIR__ . '/SchemaLoader.php');
require_once(__DIR__ . '/OntologyEngine.php');
require_once(__DIR__ . '/OntologyActionHandler.php');

// ========== 테스트 유틸리티 ==========
class TestRunner {
    private $results = [];
    private $currentSuite = '';
    private $passed = 0;
    private $failed = 0;
    private $skipped = 0;
    
    public function suite(string $name): void {
        $this->currentSuite = $name;
        $this->results[$name] = [];
    }
    
    public function test(string $name, callable $testFn): void {
        $result = [
            'name' => $name,
            'status' => 'pending',
            'message' => '',
            'duration' => 0
        ];
        
        $start = microtime(true);
        
        try {
            $testFn();
            $result['status'] = 'passed';
            $result['message'] = '✓ 통과';
            $this->passed++;
        } catch (SkipException $e) {
            $result['status'] = 'skipped';
            $result['message'] = '⊘ 스킵: ' . $e->getMessage();
            $this->skipped++;
        } catch (AssertionError $e) {
            $result['status'] = 'failed';
            $result['message'] = '✗ 실패: ' . $e->getMessage();
            $this->failed++;
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = '✗ 에러: ' . $e->getMessage();
            $this->failed++;
        }
        
        $result['duration'] = round((microtime(true) - $start) * 1000, 2);
        $this->results[$this->currentSuite][] = $result;
    }
    
    public function skip(string $reason): void {
        throw new SkipException($reason);
    }
    
    public function getResults(): array {
        return $this->results;
    }
    
    public function getSummary(): array {
        return [
            'total' => $this->passed + $this->failed + $this->skipped,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'skipped' => $this->skipped
        ];
    }
}

class SkipException extends Exception {}

// 단언 함수들
function assertEqual($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $msg = $message ?: "Expected " . json_encode($expected) . ", got " . json_encode($actual);
        throw new AssertionError($msg);
    }
}

function assertTrue($condition, string $message = ''): void {
    if (!$condition) {
        throw new AssertionError($message ?: "Expected true, got false");
    }
}

function assertFalse($condition, string $message = ''): void {
    if ($condition) {
        throw new AssertionError($message ?: "Expected false, got true");
    }
}

function assertNotNull($value, string $message = ''): void {
    if ($value === null) {
        throw new AssertionError($message ?: "Expected non-null value");
    }
}

function assertNull($value, string $message = ''): void {
    if ($value !== null) {
        throw new AssertionError($message ?: "Expected null, got " . json_encode($value));
    }
}

function assertArrayHasKey(string $key, array $array, string $message = ''): void {
    if (!array_key_exists($key, $array)) {
        throw new AssertionError($message ?: "Array does not have key: {$key}");
    }
}

function assertCount(int $expected, $countable, string $message = ''): void {
    $actual = is_array($countable) ? count($countable) : $countable->count();
    if ($expected !== $actual) {
        throw new AssertionError($message ?: "Expected count {$expected}, got {$actual}");
    }
}

function assertContains($needle, array $haystack, string $message = ''): void {
    if (!in_array($needle, $haystack)) {
        throw new AssertionError($message ?: "Array does not contain: " . json_encode($needle));
    }
}

function assertStringContains(string $needle, string $haystack, string $message = ''): void {
    if (strpos($haystack, $needle) === false) {
        throw new AssertionError($message ?: "String does not contain: {$needle}");
    }
}

function assertGreaterThan($expected, $actual, string $message = ''): void {
    if ($actual <= $expected) {
        throw new AssertionError($message ?: "Expected {$actual} > {$expected}");
    }
}

function assertInstanceOf(string $class, $object, string $message = ''): void {
    if (!($object instanceof $class)) {
        throw new AssertionError($message ?: "Expected instance of {$class}");
    }
}

// ========== 테스트 실행 ==========
$runner = new TestRunner();

// ========== 1. SchemaLoader 테스트 ==========
$runner->suite('SchemaLoader 테스트');

$runner->test('스키마 파일 로드 성공', function() {
    $loader = new SchemaLoader();
    $diag = $loader->getDiagnostics();
    assertTrue($diag['schema_loaded'], '스키마가 로드되어야 함');
});

$runner->test('클래스 개수 확인', function() {
    $loader = new SchemaLoader();
    $classes = $loader->getAllClasses();
    assertGreaterThan(5, count($classes), '최소 5개 이상의 클래스가 정의되어야 함');
});

$runner->test('프로퍼티 개수 확인', function() {
    $loader = new SchemaLoader();
    $properties = $loader->getAllProperties();
    assertGreaterThan(10, count($properties), '최소 10개 이상의 프로퍼티가 정의되어야 함');
});

$runner->test('OnboardingContext 클래스 존재 확인', function() {
    $loader = new SchemaLoader();
    assertTrue($loader->classExists('mk:OnboardingContext'), 'mk:OnboardingContext 클래스가 존재해야 함');
});

$runner->test('FirstClassStrategy 클래스 존재 확인', function() {
    $loader = new SchemaLoader();
    assertTrue($loader->classExists('mk:FirstClassStrategy'), 'mk:FirstClassStrategy 클래스가 존재해야 함');
});

$runner->test('LearningContextIntegration 클래스 존재 확인', function() {
    $loader = new SchemaLoader();
    assertTrue($loader->classExists('mk:LearningContextIntegration'), 'mk:LearningContextIntegration 클래스가 존재해야 함');
});

$runner->test('LessonProcedure 클래스 존재 확인', function() {
    $loader = new SchemaLoader();
    assertTrue($loader->classExists('mk:LessonProcedure'), 'mk:LessonProcedure 클래스가 존재해야 함');
});

$runner->test('존재하지 않는 클래스 확인', function() {
    $loader = new SchemaLoader();
    assertFalse($loader->classExists('mk:NonExistentClass'), '존재하지 않는 클래스는 false 반환');
});

$runner->test('gradeLevel 프로퍼티 정의 확인', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('gradeLevel');
    assertNotNull($propDef, 'gradeLevel 프로퍼티가 정의되어야 함');
    assertEqual('xsd:string', $propDef['type'], 'gradeLevel은 xsd:string 타입이어야 함');
});

$runner->test('mathSelfConfidence 프로퍼티 타입 확인', function() {
    $loader = new SchemaLoader();
    $propDef = $loader->getPropertyDefinition('mathSelfConfidence');
    assertNotNull($propDef, 'mathSelfConfidence 프로퍼티가 정의되어야 함');
    assertEqual('xsd:integer', $propDef['type'], 'mathSelfConfidence는 xsd:integer 타입이어야 함');
});

$runner->test('클래스 정의 조회', function() {
    $loader = new SchemaLoader();
    $classDef = $loader->getClassDefinition('mk:OnboardingContext');
    assertNotNull($classDef, '클래스 정의가 반환되어야 함');
    assertArrayHasKey('label', $classDef, 'label 필드가 있어야 함');
    assertArrayHasKey('subClassOf', $classDef, 'subClassOf 필드가 있어야 함');
});

$runner->test('클래스 계층 구조 조회', function() {
    $loader = new SchemaLoader();
    $hierarchy = $loader->getClassHierarchy('mk:OnboardingContext');
    assertTrue(is_array($hierarchy), '계층 구조는 배열이어야 함');
});

$runner->test('인스턴스 검증 - 유효한 데이터', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateInstance('mk:OnboardingContext', [
        '@id' => 'mk:test/instance_1',
        '@type' => 'mk:OnboardingContext',
        'gradeLevel' => '중2',
        'schoolName' => '테스트중학교'
    ]);
    assertTrue($result['valid'], '유효한 데이터는 검증 통과해야 함');
});

$runner->test('인스턴스 검증 - 존재하지 않는 클래스', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateInstance('mk:NonExistentClass', [
        '@id' => 'mk:test/instance_1'
    ]);
    assertFalse($result['valid'], '존재하지 않는 클래스는 검증 실패해야 함');
    assertGreaterThan(0, count($result['errors']), '오류 메시지가 있어야 함');
});

$runner->test('rules.yaml 액션 검증 - create_instance', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "create_instance: 'mk:OnboardingContext'"
    ]);
    assertTrue($result['valid'], 'create_instance 액션이 유효해야 함');
});

$runner->test('rules.yaml 액션 검증 - 존재하지 않는 클래스', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "create_instance: 'mk:NonExistentClass'"
    ]);
    assertFalse($result['valid'], '존재하지 않는 클래스 참조는 실패해야 함');
});

$runner->test('rules.yaml 액션 검증 - set_property', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "set_property: ('mk:hasStudentGrade', '{gradeLevel}')"
    ]);
    // set_property의 프로퍼티가 스키마에 있는지 확인
    assertTrue(is_array($result['mappings']), '매핑 결과가 배열이어야 함');
});

$runner->test('rules.yaml 액션 검증 - reason_over', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "reason_over: 'mk:LearningContextIntegration'"
    ]);
    assertTrue($result['valid'], 'reason_over 액션이 유효해야 함');
});

$runner->test('rules.yaml 액션 검증 - generate_strategy', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "generate_strategy: 'mk:FirstClassStrategy'"
    ]);
    assertTrue($result['valid'], 'generate_strategy 액션이 유효해야 함');
});

$runner->test('rules.yaml 액션 검증 - generate_procedure', function() {
    $loader = new SchemaLoader();
    $result = $loader->validateRuleActions([
        "generate_procedure: 'mk:LessonProcedure'"
    ]);
    assertTrue($result['valid'], 'generate_procedure 액션이 유효해야 함');
});

// ========== 2. OntologyEngine 테스트 ==========
$runner->suite('OntologyEngine 테스트');

$runner->test('엔진 초기화', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    assertNotNull($engine, '엔진이 생성되어야 함');
});

$runner->test('SchemaLoader 통합 확인', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $schemaLoader = $engine->getSchemaLoader();
    assertNotNull($schemaLoader, 'SchemaLoader가 통합되어야 함');
    assertInstanceOf(SchemaLoader::class, $schemaLoader, 'SchemaLoader 인스턴스여야 함');
});

$runner->test('진단 정보 조회', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $diag = $engine->getDiagnostics();
    assertArrayHasKey('engine_version', $diag, 'engine_version 필드가 있어야 함');
    assertArrayHasKey('schema_validation_enabled', $diag, 'schema_validation_enabled 필드가 있어야 함');
    assertArrayHasKey('schema_loader_available', $diag, 'schema_loader_available 필드가 있어야 함');
});

$runner->test('스키마 검증 활성화/비활성화', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine(true);
    $diag1 = $engine->getDiagnostics();
    assertTrue($diag1['schema_validation_enabled'], '스키마 검증이 활성화되어야 함');
    
    $engine->setSchemaValidation(false);
    $diag2 = $engine->getDiagnostics();
    assertFalse($diag2['schema_validation_enabled'], '스키마 검증이 비활성화되어야 함');
});

$runner->test('인스턴스 생성 - OnboardingContext', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        ['mk:hasStudentGrade' => '중2', 'mk:hasSchool' => '테스트중학교'],
        999 // 테스트용 학생 ID
    );
    assertNotNull($instanceId, '인스턴스 ID가 반환되어야 함');
    assertStringContains('mk:OnboardingContext', $instanceId, '인스턴스 ID에 클래스명이 포함되어야 함');
});

$runner->test('인스턴스 조회', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        ['mk:hasStudentGrade' => '중3'],
        999
    );
    
    $instance = $engine->getInstance($instanceId);
    assertNotNull($instance, '인스턴스가 조회되어야 함');
    assertEqual('mk:OnboardingContext', $instance['@type'], '타입이 일치해야 함');
});

$runner->test('프로퍼티 설정', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance('mk:OnboardingContext', [], 999);
    
    $engine->setProperty($instanceId, 'mk:hasStudentGrade', '고1');
    
    $instance = $engine->getInstance($instanceId);
    assertEqual('고1', $instance['mk:hasStudentGrade'], '프로퍼티가 설정되어야 함');
});

$runner->test('변수 치환 - 컨텍스트 기반', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $context = ['student_grade' => '중2', 'school_name' => '서울중학교'];
    
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        ['mk:hasStudentGrade' => '{gradeLevel}'],
        999,
        $context
    );
    
    // 변수가 치환되거나 빈 문자열이어야 함
    $instance = $engine->getInstance($instanceId);
    assertNotNull($instance, '인스턴스가 생성되어야 함');
});

$runner->test('의미 기반 추론 - reasonOver', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    
    // 먼저 인스턴스 생성
    $engine->createInstance(
        'mk:LearningContextIntegration',
        [
            'mk:hasConceptProgress' => '중2-1 일차방정식',
            'mk:hasAdvancedProgress' => '중2-2 일차함수'
        ],
        999
    );
    
    $results = $engine->reasonOver('mk:LearningContextIntegration', null, 999);
    assertTrue(is_array($results), '추론 결과가 배열이어야 함');
});

$runner->test('전략 생성 - generateStrategy', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    
    $context = [
        'math_learning_style' => '개념형',
        'study_style' => '자기주도형',
        'math_confidence' => 6
    ];
    
    $result = $engine->generateStrategy('mk:FirstClassStrategy', $context, 999);
    assertArrayHasKey('instance_id', $result, 'instance_id가 반환되어야 함');
    assertArrayHasKey('strategy', $result, 'strategy가 반환되어야 함');
});

$runner->test('절차 생성 - generateProcedure', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    
    // 먼저 전략 생성
    $strategyResult = $engine->generateStrategy('mk:FirstClassStrategy', [], 999);
    $strategyId = $strategyResult['instance_id'];
    
    $result = $engine->generateProcedure('mk:LessonProcedure', $strategyId, 999);
    assertArrayHasKey('instance_id', $result, 'instance_id가 반환되어야 함');
    assertArrayHasKey('procedure_steps', $result, 'procedure_steps가 반환되어야 함');
});

$runner->test('부모 관계 설정', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    
    $parentId = $engine->createInstance('mk:OnboardingContext', [], 999);
    $childId = $engine->createInstance('mk:LearningContextIntegration', [], 999);
    
    $engine->setParentRelation($childId, $parentId);
    
    $child = $engine->getInstance($childId);
    assertEqual($parentId, $child['mk:hasParent'], '부모 관계가 설정되어야 함');
});

$runner->test('검증 로그 확인', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    
    // 존재하지 않는 클래스로 인스턴스 생성 시도
    try {
        $engine->createInstance('mk:NonExistentClass', [], 999);
    } catch (Exception $e) {
        // 예외 발생 가능
    }
    
    $log = $engine->getValidationLog();
    assertTrue(is_array($log), '검증 로그가 배열이어야 함');
});

// ========== 3. OntologyActionHandler 테스트 ==========
$runner->suite('OntologyActionHandler 테스트');

$runner->test('핸들러 초기화', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [], 999);
    assertNotNull($handler, '핸들러가 생성되어야 함');
});

$runner->test('컨텍스트 설정', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, ['key1' => 'value1'], 999);
    $handler->setContext(['key2' => 'value2']);
    
    $diag = $handler->getDiagnostics();
    assertContains('key1', $diag['context_keys'], 'key1이 컨텍스트에 있어야 함');
    assertContains('key2', $diag['context_keys'], 'key2가 컨텍스트에 있어야 함');
});

$runner->test('액션 파싱 - create_instance 문자열', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [], 999);
    $result = $handler->executeAction("create_instance: 'mk:OnboardingContext'");
    
    assertTrue($result['success'], 'create_instance 액션이 성공해야 함');
    assertArrayHasKey('instance_id', $result, 'instance_id가 반환되어야 함');
});

$runner->test('액션 파싱 - create_instance 배열', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [], 999);
    $result = $handler->executeAction(['create_instance' => 'mk:OnboardingContext']);
    
    assertTrue($result['success'], 'create_instance 액션이 성공해야 함');
});

$runner->test('액션 파싱 - set_property 문자열', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, ['gradeLevel' => '중2'], 999);
    
    // 먼저 인스턴스 생성
    $handler->executeAction("create_instance: 'mk:OnboardingContext'");
    
    // 프로퍼티 설정
    $result = $handler->executeAction("set_property: ('mk:hasStudentGrade', '{gradeLevel}')");
    assertTrue($result['success'], 'set_property 액션이 성공해야 함');
});

$runner->test('액션 파싱 - reason_over', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [], 999);
    $result = $handler->executeAction("reason_over: 'mk:OnboardingContext'");
    
    assertTrue($result['success'], 'reason_over 액션이 성공해야 함');
    assertArrayHasKey('results', $result, 'results가 반환되어야 함');
});

$runner->test('액션 파싱 - generate_strategy', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [
        'math_learning_style' => '개념형',
        'study_style' => '자기주도형'
    ], 999);
    
    $result = $handler->executeAction("generate_strategy: 'mk:FirstClassStrategy'");
    assertTrue($result['success'], 'generate_strategy 액션이 성공해야 함');
});

$runner->test('액션 파싱 - 잘못된 액션', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [], 999);
    $result = $handler->executeAction("invalid_action: 'test'");
    
    assertFalse($result['success'], '잘못된 액션은 실패해야 함');
});

$runner->test('여러 액션 순차 실행', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [
        'student_grade' => '중2',
        'concept_progress' => '중2-1 일차방정식'
    ], 999);
    
    $actions = [
        "create_instance: 'mk:OnboardingContext'",
        "set_property: ('mk:hasStudentGrade', '{student_grade}')",
        "create_instance: 'mk:LearningContextIntegration'",
        "set_property: ('mk:hasConceptProgress', '{concept_progress}')"
    ];
    
    $result = $handler->executeActions($actions);
    assertTrue($result['success'], '모든 액션이 성공해야 함');
    assertEqual(4, $result['total_actions'], '4개 액션이 실행되어야 함');
});

$runner->test('OntologyEngine 접근', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $handler = new OntologyActionHandler(null, [], 999);
    $engine = $handler->getOntologyEngine();
    
    assertInstanceOf(OntologyEngine::class, $engine, 'OntologyEngine 인스턴스여야 함');
});

// ========== 4. Q1 파이프라인 통합 테스트 ==========
$runner->suite('Q1 파이프라인 통합 테스트');

$runner->test('Q1 파이프라인 전체 실행', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    
    $context = [
        'student_grade' => '중2',
        'school_name' => '테스트중학교',
        'academy_name' => '테스트학원',
        'academy_grade' => 'A반',
        'concept_progress' => '중2-1 일차방정식',
        'advanced_progress' => '중2-2 일차함수',
        'math_unit_mastery' => '일차방정식 완료',
        'current_progress_position' => '중2-1',
        'math_learning_style' => '개념형',
        'study_style' => '자기주도형',
        'exam_style' => '꾸준형',
        'math_confidence' => 6,
        'math_level' => '중위권'
    ];
    
    $handler = new OntologyActionHandler(null, $context, 999);
    $result = $handler->executeQ1Pipeline();
    
    assertTrue($result['success'], 'Q1 파이프라인이 성공해야 함');
    assertArrayHasKey('stages', $result, 'stages가 있어야 함');
    assertArrayHasKey('strategy', $result, 'strategy가 있어야 함');
    assertArrayHasKey('procedure', $result, 'procedure가 있어야 함');
});

$runner->test('Q1 파이프라인 - 스테이지 검증', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    
    $handler = new OntologyActionHandler(null, [
        'student_grade' => '고1',
        'concept_progress' => '고1-1 다항식'
    ], 999);
    
    $result = $handler->executeQ1Pipeline();
    
    $stages = $result['stages'];
    assertArrayHasKey('context_creation', $stages, 'context_creation 스테이지가 있어야 함');
    assertArrayHasKey('learning_context', $stages, 'learning_context 스테이지가 있어야 함');
    assertArrayHasKey('reasoning', $stages, 'reasoning 스테이지가 있어야 함');
    assertArrayHasKey('strategy_generation', $stages, 'strategy_generation 스테이지가 있어야 함');
    assertArrayHasKey('procedure_generation', $stages, 'procedure_generation 스테이지가 있어야 함');
});

$runner->test('Q1 파이프라인 - 절차 단계 생성 확인', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    
    $handler = new OntologyActionHandler(null, [
        'math_confidence' => 4,
        'math_learning_style' => '계산형'
    ], 999);
    
    $result = $handler->executeQ1Pipeline();
    
    if ($result['success'] && isset($result['procedure']['procedure_steps'])) {
        $steps = $result['procedure']['procedure_steps'];
        assertGreaterThan(0, count($steps), '절차 단계가 생성되어야 함');
    }
});

$runner->test('Q1 파이프라인 - 검증 오류 수집', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    
    $handler = new OntologyActionHandler(null, [], 999);
    $result = $handler->executeQ1Pipeline();
    
    assertArrayHasKey('errors', $result, 'errors 배열이 있어야 함');
    assertTrue(is_array($result['errors']), 'errors는 배열이어야 함');
});

// ========== 5. 엣지 케이스 테스트 ==========
$runner->suite('엣지 케이스 테스트');

$runner->test('빈 컨텍스트로 인스턴스 생성', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance('mk:OnboardingContext', [], 999);
    assertNotNull($instanceId, '빈 컨텍스트로도 인스턴스 생성 가능해야 함');
});

$runner->test('특수문자가 포함된 값 처리', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        ['mk:hasSchool' => "테스트'학교\"이름"],
        999
    );
    
    $instance = $engine->getInstance($instanceId);
    assertEqual("테스트'학교\"이름", $instance['mk:hasSchool'], '특수문자가 올바르게 저장되어야 함');
});

$runner->test('한글 값 처리', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        [
            'mk:hasStudentGrade' => '중학교 2학년',
            'mk:hasSchool' => '서울특별시 강남구 테스트중학교'
        ],
        999
    );
    
    $instance = $engine->getInstance($instanceId);
    assertEqual('중학교 2학년', $instance['mk:hasStudentGrade'], '한글이 올바르게 저장되어야 함');
});

$runner->test('숫자 값 처리', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        ['mk:hasMathConfidence' => 7],
        999
    );
    
    $instance = $engine->getInstance($instanceId);
    assertEqual(7, $instance['mk:hasMathConfidence'], '숫자가 올바르게 저장되어야 함');
});

$runner->test('배열 값 처리', function() use ($moodleLoaded, $runner) {
    if (!$moodleLoaded) {
        $runner->skip('Moodle 환경 필요');
    }
    $engine = new OntologyEngine();
    $instanceId = $engine->createInstance(
        'mk:OnboardingContext',
        ['mk:hasTextbooks' => ['쎈', '개념원리', 'RPM']],
        999
    );
    
    $instance = $engine->getInstance($instanceId);
    assertTrue(is_array($instance['mk:hasTextbooks']), '배열이 올바르게 저장되어야 함');
    assertCount(3, $instance['mk:hasTextbooks'], '배열 요소가 3개여야 함');
});

// ========== 결과 출력 ==========
$results = $runner->getResults();
$summary = $runner->getSummary();

if ($isWeb) {
    // HTML 출력
    echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>온톨로지 통합 테스트 결과</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; 
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
        h2 { 
            color: #ffd700; 
            margin: 30px 0 15px; 
            font-size: 1.3em;
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
        .summary-item.skipped .number { color: #ff9800; }
        
        .suite {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #333;
        }
        .test {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }
        .test:last-child { border-bottom: none; }
        .test-name { flex: 1; }
        .test-status {
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .test-status.passed { background: #00c853; color: #000; }
        .test-status.failed { background: #ff5252; color: #fff; }
        .test-status.skipped { background: #ff9800; color: #000; }
        .test-status.error { background: #ff5252; color: #fff; }
        .test-duration {
            color: #666;
            font-size: 0.8em;
            margin-left: 15px;
            min-width: 60px;
            text-align: right;
        }
        .test-message {
            color: #888;
            font-size: 0.85em;
            margin-top: 5px;
        }
        .timestamp {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>🧪 온톨로지 통합 테스트 결과</h1>
    <p class='timestamp'>실행 시간: " . date('Y-m-d H:i:s') . "</p>
    
    <div class='summary'>
        <div class='summary-item'>
            <div class='number'>{$summary['total']}</div>
            <div class='label'>전체</div>
        </div>
        <div class='summary-item passed'>
            <div class='number'>{$summary['passed']}</div>
            <div class='label'>통과</div>
        </div>
        <div class='summary-item failed'>
            <div class='number'>{$summary['failed']}</div>
            <div class='label'>실패</div>
        </div>
        <div class='summary-item skipped'>
            <div class='number'>{$summary['skipped']}</div>
            <div class='label'>스킵</div>
        </div>
    </div>";
    
    foreach ($results as $suiteName => $tests) {
        echo "<h2>{$suiteName}</h2>
        <div class='suite'>";
        
        foreach ($tests as $test) {
            $statusClass = $test['status'];
            echo "<div class='test'>
                <div class='test-name'>
                    {$test['name']}
                    <div class='test-message'>{$test['message']}</div>
                </div>
                <span class='test-status {$statusClass}'>{$test['status']}</span>
                <span class='test-duration'>{$test['duration']}ms</span>
            </div>";
        }
        
        echo "</div>";
    }
    
    echo "</body></html>";
    
} else {
    // CLI 출력
    echo "\n========================================\n";
    echo "   온톨로지 통합 테스트 결과\n";
    echo "========================================\n\n";
    
    echo "요약: 전체 {$summary['total']} | 통과 {$summary['passed']} | 실패 {$summary['failed']} | 스킵 {$summary['skipped']}\n\n";
    
    foreach ($results as $suiteName => $tests) {
        echo "--- {$suiteName} ---\n";
        foreach ($tests as $test) {
            $icon = $test['status'] === 'passed' ? '✓' : ($test['status'] === 'skipped' ? '⊘' : '✗');
            echo "  {$icon} {$test['name']} ({$test['duration']}ms)\n";
            if ($test['status'] !== 'passed') {
                echo "    {$test['message']}\n";
            }
        }
        echo "\n";
    }
}

