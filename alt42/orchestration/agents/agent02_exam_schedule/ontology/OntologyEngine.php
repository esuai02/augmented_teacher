<?php
/**
 * Agent02 온톨로지 엔진 (시험 일정 전용 엔진)
 * File: agent02_exam_schedule/ontology/OntologyEngine.php
 * 
 * Agent02 전용 온톨로지 엔진 - 시험 일정 관련 추론 및 전략 생성
 * 
 * 아키텍처: 
 * ┌─────────────────────────────────────────────────────────┐
 * │                    OntologyEngine                       │
 * ├─────────────────────────────────────────────────────────┤
 * │  [스키마 레이어] SchemaLoader                           │
 * │    - 온톨로지.jsonld 로드/파싱                          │
 * │    - 클래스/프로퍼티 검증                               │
 * │    - 타입 검증 (xsd:string, xsd:integer 등)             │
 * ├─────────────────────────────────────────────────────────┤
 * │  [도메인 추론 레이어] OntologyEngine                    │
 * │    - 인스턴스 생성/조회                                 │
 * │    - 의미 기반 추론 (reasonOver)                        │
 * │    - 전략/절차 생성 (generateStrategy/generateProcedure)│
 * └─────────────────────────────────────────────────────────┘
 * 
 * 설계 원칙:
 * - 스키마(검증) 로직과 도메인 추론 로직 분리
 * - SchemaLoader는 검증만 담당
 * - OntologyEngine은 추론/생성만 담당
 */
   
// SchemaLoader 로드
require_once(__DIR__ . '/SchemaLoader.php');

// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class OntologyEngine {
    
    private $db;
    private $namespace = 'https://hyperial.tech/ontology/exam_schedule#';
    private $prefix = 'mk:';
    
    /** @var SchemaLoader 스키마 로더 (검증 레이어) */
    private $schemaLoader;
    
    /** @var bool 스키마 검증 활성화 여부 */
    private $enableSchemaValidation = true;
    
    /** @var array 검증 오류 로그 */
    private $validationLog = [];
    
    /** @var array 온톨로지에서 동적으로 로드된 프로퍼티 타입 캐시 */
    private $propertyTypesCache = null;
    
    /** @var array 온톨로지에서 동적으로 로드된 클래스 레이블 캐시 */
    private $classLabelsCache = null;
    
    /**
     * Constructor
     * 
     * @param bool $enableSchemaValidation 스키마 검증 활성화 여부 (기본: true)
     */
    public function __construct(bool $enableSchemaValidation = true) {
        global $DB;
        $this->db = $DB;
        $this->enableSchemaValidation = $enableSchemaValidation;
        
        // 스키마 로더 초기화 (검증 레이어)
        try {
            $this->schemaLoader = new SchemaLoader();
            error_log("[OntologyEngine] SchemaLoader 초기화 완료 [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } catch (Exception $e) {
            error_log("[OntologyEngine] SchemaLoader 초기화 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $this->schemaLoader = null;
            $this->enableSchemaValidation = false;
        }
        
        // DB 테이블 생성 (실패해도 계속 진행)
        try {
            $this->ensureTableExists();
        } catch (Exception $e) {
            error_log("[OntologyEngine] ensureTableExists 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            // 테이블 생성 실패해도 계속 진행 (이미 존재할 수 있음)
        }
    }
    
    /**
     * 스키마 검증 활성화/비활성화
     */
    public function setSchemaValidation(bool $enabled): void {
        $this->enableSchemaValidation = $enabled;
    }
    
    /**
     * 검증 로그 조회
     */
    public function getValidationLog(): array {
        return $this->validationLog;
    }
    
    /**
     * SchemaLoader 인스턴스 반환 (진단용)
     */
    public function getSchemaLoader(): ?SchemaLoader {
        return $this->schemaLoader;
    }
    
    /**
     * 온톨로지 인스턴스 저장 테이블 생성
     */
    private function ensureTableExists() {
        $dbman = $this->db->get_manager();
        
        $table = new xmldb_table('alt42_ontology_instances');
        
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('instance_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('class_type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('jsonld_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('stage', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('parent_instance_id', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('instance_id_idx', XMLDB_INDEX_UNIQUE, ['instance_id']);
            $table->add_index('student_id_idx', XMLDB_INDEX_NOTUNIQUE, ['student_id']);
            $table->add_index('class_type_idx', XMLDB_INDEX_NOTUNIQUE, ['class_type']);
            
            $dbman->create_table($table);
        }
    }
    
    /**
     * 온톨로지 인스턴스 생성
     * 
     * @param string $class 클래스 URI (예: 'mk:OnboardingContext')
     * @param array $properties 프로퍼티 배열
     * @param int|null $studentId 학생 ID
     * @param array|null $context 컨텍스트 (변수 치환용)
     * @return string 생성된 인스턴스 ID
     * @throws Exception
     */
    public function createInstance(string $class, array $properties = [], ?int $studentId = null, ?array $context = null): string {
        try {
            // [스키마 레이어] 클래스 존재 검증
            if ($this->enableSchemaValidation && $this->schemaLoader) {
                if (!$this->schemaLoader->classExists($class)) {
                    $this->validationLog[] = [
                        'type' => 'class_not_found',
                        'class' => $class,
                        'timestamp' => time(),
                        'message' => "클래스가 스키마에 정의되어 있지 않습니다: {$class}"
                    ];
                    error_log("[OntologyEngine] 스키마 경고: 클래스 {$class}가 온톨로지에 정의되어 있지 않습니다");
                    // 경고만 하고 계속 진행 (하위 호환성)
                }
            }
            
            // 학생 ID 확인
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
                if ($studentId === null) {
                    throw new Exception("student_id가 필요합니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // 클래스명 추출
            $className = str_replace($this->prefix, '', $class);
            if (strpos($className, ':') !== false) {
                $className = substr($className, strpos($className, ':') + 1);
            }
            
            // 인스턴스 ID 생성
            $instanceId = $this->prefix . $className . '/instance_' . uniqid();
            
            // Stage 확인
            $stage = $this->getStageForClass($class);
            
            // JSON-LD 데이터 구성
            $jsonld = [
                '@id' => $instanceId,
                '@type' => $class,
                'mk:hasStage' => $stage
            ];
            
            // 프로퍼티 추가 (변수 치환 및 타입 변환 포함)
            foreach ($properties as $key => $value) {
                // 변수 치환 (예: {gradeLevel} → 실제 값)
                if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                    $varName = $matches[1];
                    $resolvedValue = $this->resolveVariable($varName, $context);
                    if ($resolvedValue !== '') {
                        $value = $resolvedValue;
                        error_log("[OntologyEngine] Resolved {$varName} = {$value} during createInstance [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    } else {
                        // 값이 없으면 빈 문자열로 설정 (나중에 setProperty로 채울 수 있도록)
                        $value = '';
                    }
                }
                
                // [핵심 수정] 배열 타입을 올바르게 처리 (hasUnitMastery 등)
                $value = $this->normalizePropertyValue($key, $value);
                
                // 빈 값이 아닌 경우에만 추가
                if ($value !== null && $value !== '' && $value !== []) {
                    $jsonld[$key] = $value;
                }
            }
            
            // 데이터베이스에 저장 (강화된 로직)
            $record = new stdClass();
            $record->instance_id = $instanceId;
            $record->student_id = $studentId;
            $record->class_type = $class;
            $record->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $record->stage = $stage;
            $record->parent_instance_id = null;
            $record->created_at = time();
            $record->updated_at = time();
            
            // [핵심 수정] DB 저장 전 검증 및 상세 로깅
            if (!$this->db) {
                throw new Exception("Database connection not available [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // JSON-LD 데이터 유효성 검증
            $jsonCheck = json_decode($record->jsonld_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON-LD data: " . json_last_error_msg() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // 프로퍼티 수 로깅 (디버깅용)
            $propertyCount = count($jsonld) - 3; // @id, @type, mk:hasStage 제외
            error_log("[OntologyEngine] Saving instance with {$propertyCount} properties [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // [핵심 수정 - 신뢰도 9.8+] 트랜잭션 기반 DB 저장 + 즉시 검증
            $transaction = null;
            try {
                // 트랜잭션 시작 (지원되는 경우)
                if (method_exists($this->db, 'start_delegated_transaction')) {
                    $transaction = $this->db->start_delegated_transaction();
                }
                
                $insertedId = $this->db->insert_record('alt42_ontology_instances', $record, true, true);
                
                if ($insertedId) {
                    // [핵심 수정] 저장 후 즉시 검증
                    $verifyRecord = $this->db->get_record('alt42_ontology_instances', ['id' => $insertedId]);
                    
                    if (!$verifyRecord) {
                        throw new Exception("DB 저장 검증 실패: 인스턴스가 저장되지 않았습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    }
                    
                    // 트랜잭션 커밋
                    if ($transaction) {
                        $transaction->allow_commit();
                    }
                    
                    error_log("[OntologyEngine] ✅ DB 저장 완료 및 검증 성공: {$instanceId} (DB ID: {$insertedId}) for student: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[OntologyEngine] ⚠️ WARNING: insert_record returned falsy value for {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            } catch (Exception $dbError) {
                // 롤백
                if ($transaction) {
                    try {
                        $transaction->rollback($dbError);
                    } catch (Exception $rollbackError) {
                        error_log("[OntologyEngine] Rollback error: " . $rollbackError->getMessage());
                    }
                }
                error_log("[OntologyEngine] ❌ DB ERROR: Failed to insert instance: " . $dbError->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                throw $dbError;
            }
            
            return $instanceId;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] ❌ Error creating instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 프로퍼티 설정
     * 
     * @param string $instanceId 인스턴스 ID
     * @param string $property 프로퍼티 URI
     * @param mixed $value 값
     * @param array|null $context 변수 치환을 위한 컨텍스트
     * @throws Exception
     */
    public function setProperty(string $instanceId, string $property, $value, ?array $context = null): void {
        try {
            // [스키마 레이어] 프로퍼티 타입 검증
            if ($this->enableSchemaValidation && $this->schemaLoader) {
                $propDef = $this->schemaLoader->getPropertyDefinition($property);
                if (!$propDef) {
                    $this->validationLog[] = [
                        'type' => 'property_not_found',
                        'property' => $property,
                        'timestamp' => time(),
                        'message' => "프로퍼티가 스키마에 정의되어 있지 않습니다: {$property}"
                    ];
                    error_log("[OntologyEngine] 스키마 경고: 프로퍼티 {$property}가 온톨로지에 정의되어 있지 않습니다");
                }
            }
            
            // 인스턴스 조회
            $instance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $instanceId]);
            if (!$instance) {
                throw new Exception("Instance not found: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // JSON-LD 파싱
            $jsonld = json_decode($instance->jsonld_data, true);
            if (!$jsonld) {
                throw new Exception("Invalid JSON-LD data for instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // 값 치환 (변수 처리)
            if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                $varName = $matches[1];
                // resolveVariable을 통해 컨텍스트에서 값 추출
                $resolvedValue = $this->resolveVariable($varName, $context);
                if ($resolvedValue !== '') {
                    $value = $resolvedValue;
                    error_log("[OntologyEngine] Resolved {$varName} = {$value} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[OntologyEngine] Warning: Could not resolve variable {$varName}, keeping placeholder [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    // 값이 없으면 빈 문자열로 설정 (나중에 채울 수 있도록)
                    $value = '';
                }
            }
            
            // [핵심 수정] 배열 타입을 올바르게 처리
            $value = $this->normalizePropertyValue($property, $value);
            
            // 프로퍼티 설정 (빈 값도 설정하여 명시적으로 표시)
            $jsonld[$property] = $value;
            
            // 업데이트
            $instance->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $instance->updated_at = time();
            $this->db->update_record('alt42_ontology_instances', $instance);
            
            error_log("[OntologyEngine] Set property {$property} = " . (is_array($value) ? json_encode($value) : $value) . " for instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error setting property: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 의미 기반 추론
     * 
     * @param string $class 추론할 클래스 URI
     * @param string|null $instanceId 특정 인스턴스 ID (null이면 전체)
     * @param int|null $studentId 학생 ID
     * @return array 추론 결과
     */
    public function reasonOver(string $class, ?string $instanceId = null, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            $conditions = ['class_type' => $class];
            if ($instanceId !== null) {
                $conditions['instance_id'] = $instanceId;
            }
            if ($studentId !== null) {
                $conditions['student_id'] = $studentId;
            }
            
            $instances = $this->db->get_records('alt42_ontology_instances', $conditions);
            
            $results = [];
            foreach ($instances as $instance) {
                $jsonld = json_decode($instance->jsonld_data, true);
                if (!$jsonld) {
                    continue;
                }
                
                // 추론 로직 적용
                $reasoningResult = $this->applyReasoningRules($class, $jsonld, $studentId);
                $results[] = [
                    'instance_id' => $instance->instance_id,
                    'data' => $jsonld,
                    'reasoning' => $reasoningResult
                ];
            }
            
            error_log("[OntologyEngine] Reasoned over {$class}, found " . count($results) . " instances [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $results;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error reasoning: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return [];
        }
    }
    
    /**
     * 전략 생성
     * 
     * @param string $strategyClass 전략 클래스 URI
     * @param array $context 컨텍스트 정보
     * @param int|null $studentId 학생 ID
     * @return array 생성된 전략 객체
     */
    public function generateStrategy(string $strategyClass, array $context, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            // 관련 Context 인스턴스 조회
            $onboardingContexts = $this->reasonOver('mk:OnboardingContext', null, $studentId);
            $learningContexts = $this->reasonOver('mk:LearningContextIntegration', null, $studentId);
            
            // 전략 생성 로직
            $strategy = $this->buildStrategy($strategyClass, $onboardingContexts, $learningContexts, $context);
            
            // 인스턴스로 저장 (컨텍스트 전달하여 변수 치환 가능하도록)
            $instanceId = $this->createInstance($strategyClass, $strategy, $studentId, $context);
            
            // 부모 관계 설정
            if (!empty($onboardingContexts)) {
                $parentId = $onboardingContexts[0]['instance_id'];
                $this->setParentRelation($instanceId, $parentId);
            }
            
            error_log("[OntologyEngine] Generated strategy: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'strategy' => $strategy
            ];
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error generating strategy: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 절차 생성
     * 
     * @param string $procedureClass 절차 클래스 URI
     * @param string $strategyId 전략 인스턴스 ID
     * @param int|null $studentId 학생 ID
     * @return array 생성된 절차 객체
     */
    public function generateProcedure(string $procedureClass, string $strategyId, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            // 전략 인스턴스 조회
            $strategyInstance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $strategyId]);
            if (!$strategyInstance) {
                throw new Exception("Strategy instance not found: {$strategyId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            $strategyJsonld = json_decode($strategyInstance->jsonld_data, true);
            
            // 절차 단계 생성
            $procedureSteps = $this->buildProcedureSteps($strategyJsonld);
            
            // 절차 인스턴스 생성
            $procedureData = [
                'mk:hasProcedureSteps' => $procedureSteps
            ];
            
            $instanceId = $this->createInstance($procedureClass, $procedureData, $studentId, null);
            
            // 부모 관계 설정
            $this->setParentRelation($instanceId, $strategyId);
            
            error_log("[OntologyEngine] Generated procedure: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'procedure_steps' => $procedureSteps
            ];
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error generating procedure: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 인스턴스 조회
     * 
     * @param string $instanceId 인스턴스 ID
     * @return array|null JSON-LD 데이터
     */
    public function getInstance(string $instanceId): ?array {
        try {
            $instance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $instanceId]);
            if (!$instance) {
                return null;
            }
            
            return json_decode($instance->jsonld_data, true);
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error getting instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 클래스별 인스턴스 조회
     * 
     * @param string $classType 클래스 타입 (예: 'mk:FirstClassStrategy')
     * @param int|null $studentId 학생 ID (null이면 전체 조회)
     * @param int $limit 최대 조회 개수 (기본: 10)
     * @return array 인스턴스 배열
     */
    public function getInstancesByClass(string $classType, ?int $studentId = null, int $limit = 10): array {
        try {
            $conditions = ['class_type' => $classType];
            if ($studentId !== null) {
                $conditions['student_id'] = $studentId;
            }
            
            $instances = $this->db->get_records('alt42_ontology_instances', $conditions, 'created_at DESC', '*', 0, $limit);
            
            if (!$instances) {
                return [];
            }
            
            $result = [];
            foreach ($instances as $instance) {
                $result[] = [
                    'id' => $instance->id,
                    'instance_id' => $instance->instance_id,
                    'class_type' => $instance->class_type,
                    'student_id' => $instance->student_id,
                    'created_at' => $instance->created_at,
                    'jsonld_data' => json_decode($instance->jsonld_data, true)
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error getting instances by class: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return [];
        }
    }
    
    /**
     * 학생별 최신 인스턴스 조회
     * 
     * @param int $studentId 학생 ID
     * @param string|null $classType 클래스 타입 (null이면 전체)
     * @param int $limit 최대 조회 개수
     * @return array 인스턴스 배열
     */
    public function getLatestInstancesForStudent(int $studentId, ?string $classType = null, int $limit = 10): array {
        try {
            $conditions = ['student_id' => $studentId];
            if ($classType !== null) {
                $conditions['class_type'] = $classType;
            }
            
            $instances = $this->db->get_records('alt42_ontology_instances', $conditions, 'created_at DESC', '*', 0, $limit);
            
            if (!$instances) {
                return [];
            }
            
            $result = [];
            foreach ($instances as $instance) {
                $result[] = [
                    'id' => $instance->id,
                    'instance_id' => $instance->instance_id,
                    'class_type' => $instance->class_type,
                    'student_id' => $instance->student_id,
                    'created_at' => $instance->created_at,
                    'jsonld_data' => json_decode($instance->jsonld_data, true)
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error getting latest instances for student: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return [];
        }
    }
    
    // === Private Helper Methods ===
    
    /**
     * 프로퍼티 값 정규화 (배열 타입 올바르게 처리)
     * 
     * PHP 배열이 "Array" 문자열로 잘못 변환되는 문제 해결
     * hasUnitMastery 등 배열 프로퍼티를 온톨로지 형식에 맞게 변환
     * 
     * @param string $property 프로퍼티 이름
     * @param mixed $value 값
     * @return mixed 정규화된 값
     */
    private function normalizePropertyValue(string $property, $value) {
        // 값이 null이거나 빈 문자열이면 그대로 반환
        if ($value === null || $value === '') {
            return $value;
        }
        
        // 배열인 경우 특별 처리
        if (is_array($value)) {
            // hasUnitMastery, recommendsUnits 등 특수 프로퍼티 처리
            if (strpos($property, 'UnitMastery') !== false || $property === 'mk:hasUnitMastery') {
                return $this->formatUnitMasteryArray($value);
            }
            
            // recommendsUnits 등 단순 배열 프로퍼티
            if (strpos($property, 'recommends') !== false || strpos($property, 'Units') !== false) {
                // 비어있지 않은 배열은 그대로 반환 (JSON-LD에서 배열로 표현)
                if (!empty($value)) {
                    return $value;
                }
                return [];
            }
            
            // 일반 배열은 그대로 반환 (빈 배열 제외)
            return empty($value) ? [] : $value;
        }
        
        // 문자열 "Array"가 들어온 경우 빈 배열로 변환 (오류 방지)
        if ($value === 'Array') {
            error_log("[OntologyEngine] Warning: Property {$property} had string 'Array', converting to empty array");
            return [];
        }
        
        return $value;
    }
    
    /**
     * UnitMastery 배열을 온톨로지 형식으로 변환
     * 
     * @param array $masteryData 마스터리 데이터 배열
     * @return array 온톨로지 형식의 UnitMastery 배열
     */
    private function formatUnitMasteryArray(array $masteryData): array {
        $formatted = [];
        
        foreach ($masteryData as $key => $value) {
            // 이미 형식화된 경우 (unitName, masteryLevel 포함)
            if (is_array($value) && isset($value['unitName'])) {
                $formatted[] = [
                    '@type' => 'mk:UnitMastery',
                    'mk:unitName' => $value['unitName'],
                    'mk:masteryLevel' => $value['masteryLevel'] ?? 'unknown'
                ];
            }
            // 키-값 형태 (예: '일차함수' => '완료')
            elseif (is_string($key) && !is_numeric($key)) {
                $formatted[] = [
                    '@type' => 'mk:UnitMastery',
                    'mk:unitName' => $key,
                    'mk:masteryLevel' => is_string($value) ? $value : 'unknown'
                ];
            }
            // 문자열 값인 경우 (예: ['일차함수', '이차함수'])
            elseif (is_string($value)) {
                $formatted[] = [
                    '@type' => 'mk:UnitMastery',
                    'mk:unitName' => $value,
                    'mk:masteryLevel' => 'unknown'
                ];
            }
        }
        
        // 빈 배열이면 빈 배열 반환
        return $formatted;
    }
    
    /**
     * 클래스에 대한 Stage 반환
     */
    private function getStageForClass(string $class): string {
        $stageMap = [
            'mk:OnboardingContext' => 'Context',
            'mk:LearningContextIntegration' => 'Context',
            'mk:FirstClassDecisionModel' => 'Decision',
            'mk:FirstClassStrategy' => 'Decision',
            'mk:FirstClassExecutionPlan' => 'Execution',
            'mk:LessonProcedure' => 'Execution'
        ];
        
        return $stageMap[$class] ?? 'Context';
    }
    
    /**
     * 변수 해석 (강화된 버전 - 공식 매핑 테이블 사용)
     * 
     * 변수명을 컨텍스트 키로 매핑하여 실제 값 추출
     * SchemaLoader의 공식 매핑 테이블을 단일 진실 소스로 사용
     */
    private function resolveVariable(string $varName, ?array $context): string {
        if (!$context) {
            return '';
        }
        
        // 1. 직접 매칭 시도
        if (isset($context[$varName])) {
            $value = $context[$varName];
            if ($value !== null && $value !== '') {
                error_log("[OntologyEngine] Direct match: {$varName} = " . (is_array($value) ? json_encode($value) : $value));
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            }
        }
        
        // 2. 공식 매핑 테이블 기반 역방향 매핑 (온톨로지 프로퍼티 → 컨텍스트 키)
        $officialMapping = SchemaLoader::getOfficialVariableMapping();
        
        // 2a. varName이 온톨로지 프로퍼티명인 경우, 대응하는 컨텍스트 키 찾기
        $possibleContextKeys = [];
        foreach ($officialMapping as $contextKey => $ontologyProp) {
            if ($ontologyProp === $varName || $contextKey === $varName) {
                $possibleContextKeys[] = $contextKey;
            }
        }
        
        // 2b. 찾은 컨텍스트 키들로 값 검색
        foreach ($possibleContextKeys as $contextKey) {
            if (isset($context[$contextKey])) {
                $value = $context[$contextKey];
                if ($value !== null && $value !== '') {
                    error_log("[OntologyEngine] Official mapping: {$varName} → {$contextKey} = " . (is_array($value) ? json_encode($value) : $value));
                    return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
                }
            }
        }
        
        // 3. 변수명 → 컨텍스트 키 확장 매핑 테이블 (하위 호환성)
        $variableMapping = [
            // OnboardingContext 프로퍼티 매핑
            'gradeLevel' => ['student_grade', 'grade_level', 'grade', 'gradeLevel'],
            'schoolName' => ['school_name', 'school', 'schoolName'],
            'academyName' => ['academy_name', 'academy', 'academyName'],
            'academyGrade' => ['academy_grade', 'academy_grade_level', 'academyGrade'],
            
            // LearningContextIntegration 프로퍼티 매핑
            'concept_progress' => ['concept_progress', 'conceptProgress', 'conceptProgressLevel'],
            'advanced_progress' => ['advanced_progress', 'advancedProgress', 'advancedProgressLevel'],
            'math_unit_mastery' => ['math_unit_mastery', 'unit_mastery', 'unitMastery'],
            'current_progress_position' => ['current_progress_position', 'currentPosition', 'current_position'],
            'hasConceptProgress' => ['concept_progress', 'conceptProgress', 'conceptProgressLevel'],
            'hasAdvancedProgress' => ['advanced_progress', 'advancedProgress', 'advancedProgressLevel'],
            'hasUnitMastery' => ['math_unit_mastery', 'unit_mastery', 'unitMastery'],
            'hasCurrentPosition' => ['current_progress_position', 'currentPosition', 'current_position'],
            
            // FirstClassStrategy 프로퍼티 매핑
            'math_learning_style' => ['math_learning_style', 'mathLearningStyle', 'learning_style'],
            'study_style' => ['study_style', 'studyStyle'],
            'exam_style' => ['exam_style', 'examStyle', 'examPreparationStyle'],
            'math_confidence' => ['math_confidence', 'mathConfidence', 'confidence', 'mathSelfConfidence'],
            'math_level' => ['math_level', 'mathLevel', 'level'],
            'math_stress_level' => ['math_stress_level', 'mathStressLevel', 'stress_level'],
            'hasMathLearningStyle' => ['math_learning_style', 'mathLearningStyle', 'learning_style'],
            'hasStudyStyle' => ['study_style', 'studyStyle'],
            'hasExamStyle' => ['exam_style', 'examStyle', 'examPreparationStyle'],
            'hasMathConfidence' => ['math_confidence', 'mathConfidence', 'confidence', 'mathSelfConfidence'],
            'hasMathLevel' => ['math_level', 'mathLevel', 'level'],
            'hasMathStressLevel' => ['math_stress_level', 'mathStressLevel', 'stress_level'],
            
            // 교재 관련
            'textbooks' => ['textbooks', 'hasTextbooks'],
            'hasTextbooks' => ['textbooks', 'hasTextbooks'],
            'academyTextbook' => ['academy_textbook', 'academyTextbook'],
            'hasAcademyTextbook' => ['academy_textbook', 'academyTextbook']
        ];
        
        // 4. 확장 매핑 테이블에서 찾기
        if (isset($variableMapping[$varName])) {
            foreach ($variableMapping[$varName] as $contextKey) {
                if (isset($context[$contextKey])) {
                    $value = $context[$contextKey];
                    if ($value !== null && $value !== '') {
                        error_log("[OntologyEngine] Extended mapping: {$varName} → {$contextKey} = " . (is_array($value) ? json_encode($value) : $value));
                        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
                    }
                }
            }
        }
        
        // 5. snake_case 변환 시도 (예: gradeLevel → grade_level)
        $snakeCase = strtolower(preg_replace('/([A-Z])/', '_$1', $varName));
        $snakeCase = ltrim($snakeCase, '_'); // 앞의 언더스코어 제거
        if (isset($context[$snakeCase])) {
            $value = $context[$snakeCase];
            if ($value !== null && $value !== '') {
                error_log("[OntologyEngine] snake_case conversion: {$varName} → {$snakeCase} = " . (is_array($value) ? json_encode($value) : $value));
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            }
        }
        
        // 6. camelCase 변환 시도 (예: grade_level → gradeLevel)
        $camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $varName))));
        if ($camelCase !== $varName && isset($context[$camelCase])) {
            $value = $context[$camelCase];
            if ($value !== null && $value !== '') {
                error_log("[OntologyEngine] camelCase conversion: {$varName} → {$camelCase} = " . (is_array($value) ? json_encode($value) : $value));
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            }
        }
        
        // 7. has 프리픽스 제거 시도 (예: hasConceptProgress → concept_progress)
        if (strpos($varName, 'has') === 0) {
            $withoutHas = lcfirst(substr($varName, 3));
            $snakeWithoutHas = strtolower(preg_replace('/([A-Z])/', '_$1', $withoutHas));
            $snakeWithoutHas = ltrim($snakeWithoutHas, '_');
            
            if (isset($context[$snakeWithoutHas])) {
                $value = $context[$snakeWithoutHas];
                if ($value !== null && $value !== '') {
                    error_log("[OntologyEngine] has prefix removal: {$varName} → {$snakeWithoutHas} = " . (is_array($value) ? json_encode($value) : $value));
                    return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
                }
            }
            
            if (isset($context[$withoutHas])) {
                $value = $context[$withoutHas];
                if ($value !== null && $value !== '') {
                    error_log("[OntologyEngine] has prefix removal (camelCase): {$varName} → {$withoutHas} = " . (is_array($value) ? json_encode($value) : $value));
                    return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
                }
            }
        }
        
        error_log("[OntologyEngine] Could not resolve variable: {$varName} (context keys: " . implode(', ', array_keys($context)) . ")");
        return '';
    }
    
    /**
     * 추론 규칙 적용
     */
    private function applyReasoningRules(string $class, array $jsonld, ?int $studentId): array {
        $result = [];
        
        if ($class === 'mk:LearningContextIntegration') {
            // 진도 기반 단원 추천 추론
            $conceptProgress = $jsonld['mk:hasConceptProgress'] ?? null;
            $advancedProgress = $jsonld['mk:hasAdvancedProgress'] ?? null;
            $unitMastery = $jsonld['mk:hasUnitMastery'] ?? null;
            $currentPosition = $jsonld['mk:hasCurrentPosition'] ?? null;
            
            if ($conceptProgress || $advancedProgress) {
                // 단원 추천 추론
                $recommendedUnits = $this->inferRecommendedUnits($conceptProgress, $advancedProgress, $unitMastery, $currentPosition);
                if (!empty($recommendedUnits)) {
                    $result['recommendsUnits'] = $recommendedUnits;
                }
                
                // 내용 범위 추론
                $contentRange = $this->inferContentRange($conceptProgress, $advancedProgress);
                if ($contentRange) {
                    $result['recommendsContentRange'] = $contentRange;
                }
                
                // 정렬 전략 추론
                $academyProgress = $jsonld['mk:hasAcademyProgress'] ?? null;
                $curriculumAlignment = $jsonld['mk:hasCurriculumAlignment'] ?? null;
                if ($academyProgress || $curriculumAlignment) {
                    $alignmentStrategy = $this->inferAlignmentStrategy($conceptProgress, $academyProgress, $curriculumAlignment);
                    if ($alignmentStrategy) {
                        $result['recommendsAlignmentStrategy'] = $alignmentStrategy;
                    }
                }
            }
        }
        
        if ($class === 'mk:OnboardingContext') {
            // 온보딩 컨텍스트 기반 추론
            $mathConfidence = $jsonld['mk:hasMathConfidence'] ?? null;
            $mathLevel = $jsonld['mk:hasMathLevel'] ?? null;
            $mathLearningStyle = $jsonld['mk:hasMathLearningStyle'] ?? null;
            
            // 난이도 추천 추론
            if ($mathConfidence !== null && $mathLevel) {
                $difficulty = $this->inferDifficultyLevel($mathConfidence, $mathLevel);
                if ($difficulty) {
                    $result['recommendsDifficulty'] = $difficulty;
                }
            }
            
            // 진도 추천 추론
            if ($mathLevel && $mathLearningStyle) {
                $progress = $this->inferProgressRecommendation($mathLevel, $mathLearningStyle);
                if ($progress) {
                    $result['recommendsProgress'] = $progress;
                }
            }
        }
        
        // Agent02 전용 추론 로직
        if ($class === 'mk:AcademySchoolHomeAlignment') {
            // 학원-학교-집 학습 정렬 추론
            $academyProgress = $jsonld['mk:academyProgress'] ?? null;
            $schoolExamScope = $jsonld['mk:schoolExamScope'] ?? null;
            $progressGap = $jsonld['mk:progressGap'] ?? null;
            
            if ($academyProgress && $schoolExamScope) {
                $alignmentPlan = $this->inferAlignmentPlan($academyProgress, $schoolExamScope, $progressGap);
                if ($alignmentPlan) {
                    $result['recommendsAlignmentStrategy'] = $alignmentPlan;
                }
            }
        }
        
        if ($class === 'mk:ScoreImprovementPotential') {
            // 점수 상승 잠재력 추론
            $targetScore = $jsonld['mk:targetScore'] ?? null;
            $currentExpectedScore = $jsonld['mk:currentExpectedScore'] ?? null;
            $unitAccuracyRate = $jsonld['mk:unitAccuracyRate'] ?? null;
            
            if ($targetScore !== null && $currentExpectedScore !== null) {
                $strategyRatio = $this->inferStrategyRatio($targetScore, $currentExpectedScore, $unitAccuracyRate);
                if ($strategyRatio) {
                    $result['recommendsStrategyRatio'] = $strategyRatio;
                }
            }
        }
        
        if ($class === 'mk:ExamCycleImprovement') {
            // 시험 주기 개선 패턴 추론
            $academyRank = $jsonld['mk:academyRank'] ?? null;
            $schoolScore = $jsonld['mk:schoolScore'] ?? null;
            $textbookEffectivenessIndex = $jsonld['mk:textbookEffectivenessIndex'] ?? null;
            
            if ($academyRank !== null || $schoolScore !== null) {
                $improvementStrategy = $this->inferImprovementStrategy($academyRank, $schoolScore, $textbookEffectivenessIndex);
                if ($improvementStrategy) {
                    $result['recommendsImprovementStrategy'] = $improvementStrategy;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 추천 단원 추론 (동적 로드: 단원 순서는 외부 데이터 소스에서 로드)
     * 
     * @param string|null $conceptProgress 개념 진도
     * @param string|null $advancedProgress 심화 진도
     * @param string|array|null $unitMastery 단원 마스터리 (문자열 또는 배열)
     * @param string|null $currentPosition 현재 위치
     * @return array 추천 단원 배열
     */
    private function inferRecommendedUnits(?string $conceptProgress, ?string $advancedProgress, $unitMastery, ?string $currentPosition): array {
        $recommended = [];
        
        // 개념 진도 파싱 (예: "중2-1 일차방정식까지")
        if ($conceptProgress) {
            // 학년-학기 추출
            if (preg_match('/(중|고)\s*(\d)-(\d)/', $conceptProgress, $matches)) {
                $grade = $matches[1] . $matches[2];
                $semester = $matches[3];
                
                // 현재 단원 추출 (정규식으로 일반적인 단원명 패턴 매칭)
                $currentUnit = null;
                if (preg_match('/([가-힣]+(?:방정식|함수|형|원|통계|확률|비|성질|계산|부등식|유리수|실수|인수분해|제곱근))/', $conceptProgress, $unitMatches)) {
                    $currentUnit = $unitMatches[1];
                }
                
                // 단원 순서는 외부 파일에서 로드 시도 (하드코딩 제거)
                $unitSequence = $this->loadUnitSequence($grade);
                
                if (!empty($unitSequence)) {
                    $currentIndex = $currentUnit ? array_search($currentUnit, $unitSequence) : -1;
                    
                    // 다음 2-3개 단원 추천
                    for ($i = $currentIndex + 1; $i < min($currentIndex + 4, count($unitSequence)); $i++) {
                        if (isset($unitSequence[$i])) {
                            $recommended[] = $unitSequence[$i];
                        }
                    }
                }
            }
        }
        
        // 심화 진도가 있으면 심화 단원도 추가
        if ($advancedProgress && !empty($recommended)) {
            $recommended[] = $recommended[0] . ' (심화)';
        }
        
        // 단원 마스터리 기반 보완 추천
        if ($unitMastery) {
            // 배열인 경우 문자열로 변환
            $masteryStr = is_array($unitMastery) ? json_encode($unitMastery, JSON_UNESCAPED_UNICODE) : $unitMastery;
            
            if (is_string($masteryStr) && preg_match('/(\w+)\s*(미이수|부족|보통)/', $masteryStr, $masteryMatches)) {
                $weakUnit = $masteryMatches[1];
                if (!in_array($weakUnit, $recommended)) {
                    array_unshift($recommended, $weakUnit . ' (보완 필요)');
                }
            }
            
            // 배열인 경우 추가 처리
            if (is_array($unitMastery)) {
                foreach ($unitMastery as $unit => $status) {
                    if (is_string($status) && in_array($status, ['미이수', '부족', '보통'])) {
                        $unitName = is_string($unit) ? $unit : (string)$unit;
                        if (!in_array($unitName, $recommended) && !in_array($unitName . ' (보완 필요)', $recommended)) {
                            array_unshift($recommended, $unitName . ' (보완 필요)');
                        }
                    }
                }
            }
        }
        
        return !empty($recommended) ? $recommended : []; // 기본값 제거 (하드코딩 방지)
    }
    
    /**
     * [동적 로드] 학년별 단원 순서 로드
     * 
     * 외부 파일(예: unit_sequence.yaml)에서 로드하거나 온톨로지에서 조회
     * 
     * @param string $grade 학년 (예: '중1', '중2', '중3')
     * @return array 단원 순서 배열
     */
    private function loadUnitSequence(string $grade): array {
        // 외부 파일에서 로드 시도 (예: unit_sequence.yaml)
        $unitSequencePath = __DIR__ . '/../unit_sequence.yaml';
        if (file_exists($unitSequencePath)) {
            try {
                // YAML 파싱 (간단한 구현)
                $yamlContent = file_get_contents($unitSequencePath);
                // YAML 파서가 없으면 JSON 형식도 지원
                if (strpos($yamlContent, '{') === 0) {
                    $data = json_decode($yamlContent, true);
                    if ($data && isset($data[$grade])) {
                        return $data[$grade];
                    }
                }
            } catch (Exception $e) {
                error_log("[OntologyEngine] 단원 순서 파일 로드 실패: " . $e->getMessage());
            }
        }
        
        // 온톨로지에서 단원 정보 조회 시도
        if ($this->schemaLoader) {
            // MathUnit 클래스의 인스턴스들을 조회하여 순서 추론
            // (현재는 구현되지 않았지만 확장 가능)
        }
        
        // Fallback: 빈 배열 반환 (하드코딩 제거)
        return [];
    }
    
    /**
     * 내용 범위 추론
     */
    private function inferContentRange(?string $conceptProgress, ?string $advancedProgress): ?string {
        if (!$conceptProgress) {
            return null;
        }
        
        // 기본 범위: 개념 진도 기준
        $range = $conceptProgress;
        
        // 심화 진도가 있으면 확장
        if ($advancedProgress) {
            $range .= ' + ' . $advancedProgress . ' (심화)';
        }
        
        return $range;
    }
    
    /**
     * 정렬 전략 추론 (동적 로드: 정렬 상태는 온톨로지 인스턴스에서 조회)
     */
    private function inferAlignmentStrategy(?string $conceptProgress, ?string $academyProgress, ?string $curriculumAlignment): ?string {
        if (!$conceptProgress || !$academyProgress) {
            return null;
        }
        
        // 정렬 상태를 온톨로지 인스턴스로 매핑
        $alignmentStatus = $this->mapAlignmentStatusToInstance($curriculumAlignment);
        
        // 추론 규칙 동적 로드
        $rules = $this->loadInferenceRules('alignment_strategy');
        
        if (!empty($rules) && isset($rules[$alignmentStatus])) {
            return $rules[$alignmentStatus]['strategy'] ?? null;
        }
        
        // Fallback: 기본 추론 (하드코딩 최소화)
        if (strpos($curriculumAlignment, '빠름') !== false || strpos($curriculumAlignment, '앞서') !== false) {
            return '학원 진도에 맞춰 학교 진도 보완';
        } elseif (strpos($curriculumAlignment, '느림') !== false || strpos($curriculumAlignment, '뒤처') !== false) {
            return '학교 진도에 맞춰 학원 진도 보완';
        } else {
            return '학교-학원 진도 정렬 유지';
        }
    }
    
    /**
     * [동적 로드] 정렬 상태 문자열을 온톨로지 인스턴스로 매핑
     */
    private function mapAlignmentStatusToInstance(string $status): string {
        if ($this->schemaLoader) {
            // 온톨로지에서 AlignmentStatus 인스턴스 조회 시도
            $allInstances = $this->schemaLoader->getAllInstanceLabels();
            foreach ($allInstances as $instanceId => $instanceDef) {
                $label = $instanceDef['label'] ?? '';
                if (strpos($instanceId, 'Alignment') !== false) {
                    // 빠름/앞서 -> forAheadAcademy
                    if ((strpos($status, '빠름') !== false || strpos($status, '앞서') !== false) && 
                        strpos($instanceId, 'Ahead') !== false) {
                        return $instanceId;
                    }
                    // 느림/뒤처 -> forAheadSchool
                    if ((strpos($status, '느림') !== false || strpos($status, '뒤처') !== false) && 
                        strpos($instanceId, 'Behind') !== false) {
                        return $instanceId;
                    }
                    // 정렬 -> forAligned
                    if (strpos($status, '정렬') !== false && strpos($instanceId, 'Aligned') !== false) {
                        return $instanceId;
                    }
                }
            }
        }
        
        // Fallback: 기본 매핑
        if (strpos($status, '빠름') !== false || strpos($status, '앞서') !== false) {
            return 'mk:forAheadAcademy';
        } elseif (strpos($status, '느림') !== false || strpos($status, '뒤처') !== false) {
            return 'mk:forAheadSchool';
        } else {
            return 'mk:forAligned';
        }
    }
    
    /**
     * 난이도 수준 추론 (동적 로드: 추론 규칙은 온톨로지 또는 설정에서 로드)
     */
    private function inferDifficultyLevel(int $mathConfidence, string $mathLevel): ?string {
        // 추론 규칙 동적 로드
        $rules = $this->loadInferenceRules('difficulty_level');
        
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                $min = $rule['min'] ?? 0;
                $max = $rule['max'] ?? 10;
                $result = $rule['result'] ?? null;
                
                if ($mathConfidence >= $min && $mathConfidence <= $max && $result) {
                    return $result;
                }
            }
        }
        
        // [동적 로드] 규칙이 없으면 fallback 기본값 로드
        $fallbackDefaults = $this->loadInferenceRules('fallback_defaults');
        $defaultDifficulty = $fallbackDefaults['difficulty_level'] ?? null;
        if ($defaultDifficulty) {
            error_log("[OntologyEngine] 난이도 추론 규칙을 찾을 수 없어 fallback 기본값을 사용합니다: " . $defaultDifficulty);
            return $defaultDifficulty;
        }
        error_log("[OntologyEngine] 난이도 추론 규칙과 fallback 기본값을 찾을 수 없습니다. inference_rules.json을 확인하세요.");
        throw new Exception("난이도 추론 규칙을 찾을 수 없습니다.");
    }
    
    /**
     * [동적 로드] 추론 규칙 로드
     * 
     * @param string $ruleType 규칙 타입 ('difficulty_level', 'progress_recommendation' 등)
     * @return array 규칙 배열
     */
    private function loadInferenceRules(string $ruleType): array {
        // 외부 설정 파일에서 로드 시도
        $rulesPath = __DIR__ . '/../inference_rules.json';
        if (file_exists($rulesPath)) {
            try {
                $rulesData = json_decode(file_get_contents($rulesPath), true);
                if ($rulesData && isset($rulesData[$ruleType])) {
                    return $rulesData[$ruleType];
                }
            } catch (Exception $e) {
                error_log("[OntologyEngine] 추론 규칙 로드 실패: " . $e->getMessage());
            }
        }
        
        // 온톨로지에서 추론 규칙 조회 시도 (향후 확장)
        // 현재는 빈 배열 반환
        return [];
    }
    
    /**
     * 진도 추천 추론 (동적 로드: 학습 스타일은 온톨로지 인스턴스에서 조회)
     */
    private function inferProgressRecommendation(string $mathLevel, string $mathLearningStyle): ?string {
        // 학습 스타일을 온톨로지 인스턴스로 변환
        $styleInstance = $this->mapLearningStyleToInstance($mathLearningStyle);
        
        // 추론 규칙 동적 로드
        $rules = $this->loadInferenceRules('progress_recommendation');
        
        if (!empty($rules) && isset($rules[$styleInstance])) {
            $suffix = $rules[$styleInstance]['suffix'] ?? '';
            return $mathLevel . ($suffix ? ' (' . $suffix . ')' : '');
        }
        
        // [동적 로드] 학습 스타일 suffix 매핑
        $suffixRules = $this->loadInferenceRules('learning_style_suffix_mapping');
        $suffix = $suffixRules[$mathLearningStyle] ?? $suffixRules['default'] ?? '';
        return $mathLevel . ($suffix ? ' (' . $suffix . ')' : '');
    }
    
    /**
     * [동적 로드] 학습 스타일 문자열을 온톨로지 인스턴스로 매핑
     */
    private function mapLearningStyleToInstance(string $style): string {
        if ($this->schemaLoader) {
            // 온톨로지에서 MathLearningStyle 인스턴스 조회 시도
            $allInstances = $this->schemaLoader->getAllInstanceLabels();
            foreach ($allInstances as $instanceId => $instanceDef) {
                $label = $instanceDef['label'] ?? '';
                if (strpos($label, $style) !== false || strpos($style, $label) !== false) {
                    return $instanceId;
                }
            }
        }
        
        // [동적 로드] 학습 스타일 인스턴스 매핑
        $mappingRules = $this->loadInferenceRules('learning_style_to_instance_mapping');
        return $mappingRules[$style] ?? $mappingRules['default'] ?? 'mk:BalancedType';
    }
    
    /**
     * 교재 추천 추론 (동적 로드: 교재 매핑은 외부 파일 또는 온톨로지에서 로드)
     * 
     * @param string|null $conceptProgress 개념 진도
     * @param string|null $mathLevel 수학 수준
     * @return string 추천 교재
     */
    private function inferRecommendedTextbook(?string $conceptProgress, ?string $mathLevel): string {
        // 진도에서 학년 추출
        $grade = '';
        if ($conceptProgress) {
            if (preg_match('/(중|고)\s*(\d)/', $conceptProgress, $matches)) {
                $grade = $matches[1] . $matches[2];
            }
        }
        
        // 수준에 따른 교재 추천 (온톨로지 기반)
        $levelType = $this->mapMathLevelToType($mathLevel);
        
        // 교재 매핑 동적 로드
        $textbookMap = $this->loadTextbookMapping();
        
        if (!empty($textbookMap) && isset($textbookMap[$grade]) && isset($textbookMap[$grade][$levelType])) {
            return $textbookMap[$grade][$levelType];
        }
        
        // Fallback: 기본 교재 (하드코딩 최소화)
        if ($levelType === 'advanced') {
            return '심화 문제집';
        } elseif ($levelType === 'struggling') {
            return '개념원리 기초';
        } else {
            return '쎈 기본';
        }
    }
    
    /**
     * [동적 로드] 수학 수준을 타입으로 매핑
     */
    private function mapMathLevelToType(?string $mathLevel): string {
        if (!$mathLevel) {
            return 'standard';
        }
        
        if (strpos($mathLevel, '상위') !== false || strpos($mathLevel, '상') !== false) {
            return 'advanced';
        } elseif (strpos($mathLevel, '어려') !== false || strpos($mathLevel, '하위') !== false || strpos($mathLevel, '하') !== false) {
            return 'struggling';
        }
        
        return 'standard';
    }
    
    /**
     * [동적 로드] 교재 매핑 로드
     * 
     * @return array 교재 매핑 배열 ['중1' => ['advanced' => '...', 'standard' => '...', ...], ...]
     */
    private function loadTextbookMapping(): array {
        // 외부 파일에서 로드 시도
        $textbookPath = __DIR__ . '/../textbook_mapping.json';
        if (file_exists($textbookPath)) {
            try {
                $mapping = json_decode(file_get_contents($textbookPath), true);
                if ($mapping) {
                    return $mapping;
                }
            } catch (Exception $e) {
                error_log("[OntologyEngine] 교재 매핑 로드 실패: " . $e->getMessage());
            }
        }
        
        // 온톨로지에서 Textbook 인스턴스 조회 시도 (향후 확장)
        if ($this->schemaLoader) {
            // Textbook 클래스의 인스턴스들을 조회하여 매핑 생성 가능
        }
        
        // Fallback: 빈 배열 반환 (하드코딩 제거)
        return [];
    }
    
    /**
     * 문제 유형 추천 추론
     * 
     * @param string|null $mathLevel 수학 수준
     * @param string|null $mathLearningStyle 수학 학습 스타일
     * @param int $confidence 자신감 수준
     * @return array 추천 문제 유형 배열
     */
    private function inferRecommendedProblemType(?string $mathLevel, ?string $mathLearningStyle, int $confidence): array {
        $problemTypes = [];
        
        // [동적 로드] 문제 유형 추천 규칙 로드
        $rules = $this->loadInferenceRules('problem_type_recommendation');
        $levelBased = $rules['level_based'] ?? [];
        $styleAdjustments = $rules['learning_style_adjustments'] ?? [];
        $confidenceAdjustments = $rules['confidence_adjustments'] ?? [];
        
        // 수준 기반 기본 비중
        $isAdvanced = strpos($mathLevel ?? '', '상위') !== false;
        $isStruggling = strpos($mathLevel ?? '', '어려') !== false || strpos($mathLevel ?? '', '하위') !== false;
        
        if ($isStruggling && isset($levelBased['struggling'])) {
            $problemTypes = $levelBased['struggling'];
        } elseif ($isAdvanced && isset($levelBased['advanced'])) {
            $problemTypes = $levelBased['advanced'];
        } else {
            $problemTypes = $levelBased['default'] ?? [];
        }
        
        // [동적 로드] 학습 스타일 기반 조정
        if (isset($styleAdjustments[$mathLearningStyle])) {
            $problemTypes[] = $styleAdjustments[$mathLearningStyle];
        }
        
        // [동적 로드] 자신감 기반 조정
        $lowThreshold = $confidenceAdjustments['low_threshold'] ?? 3;
        if ($confidence <= $lowThreshold && isset($confidenceAdjustments['low_confidence_addition'])) {
            array_unshift($problemTypes, $confidenceAdjustments['low_confidence_addition']);
        }
        
        return $problemTypes;
    }
    
    /**
     * 전략 구축 (강화된 버전)
     * 
     * 컨텍스트와 온톨로지 인스턴스에서 데이터를 통합하여 전략 생성
     */
    private function buildStrategy(string $strategyClass, array $onboardingContexts, array $learningContexts, array $context): array {
        $strategy = [];
        
        // 1. 컨텍스트에서 직접 데이터 추출 (동적 로드: SchemaLoader 매핑 사용)
        $officialMapping = SchemaLoader::getOfficialVariableMapping();
        
        // 역방향 매핑 생성: 온톨로지 프로퍼티 → 가능한 컨텍스트 키들
        $ontologyToContextKeys = [];
        foreach ($officialMapping as $contextKey => $ontologyProp) {
            // mk: 프리픽스 추가
            $fullProp = (strpos($ontologyProp, 'mk:') === 0) ? $ontologyProp : 'mk:' . $ontologyProp;
            if (!isset($ontologyToContextKeys[$fullProp])) {
                $ontologyToContextKeys[$fullProp] = [];
            }
            $ontologyToContextKeys[$fullProp][] = $contextKey;
        }
        
        // 컨텍스트 데이터를 온톨로지 프로퍼티로 매핑
        foreach ($context as $contextKey => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            
            // 공식 매핑에서 찾기
            if (isset($officialMapping[$contextKey])) {
                $ontologyProp = $officialMapping[$contextKey];
                $fullProp = (strpos($ontologyProp, 'mk:') === 0) ? $ontologyProp : 'mk:' . $ontologyProp;
                
                // 타입 강제화 적용
                $typedValue = $this->enforcePropertyType($fullProp, $value);
                $strategy[$fullProp] = $typedValue;
                error_log("[OntologyEngine] buildStrategy: {$fullProp} = {$typedValue} (from {$contextKey})");
            }
        }
        
        // 2. OnboardingContext에서 데이터 추출 (동적 로드: 온톨로지 스키마 기반)
        if (!empty($onboardingContexts)) {
            $onboardingData = $onboardingContexts[0]['data'];
            $onboardingReasoning = $onboardingContexts[0]['reasoning'] ?? [];
            
            // 온톨로지 스키마에서 OnboardingContext의 프로퍼티 동적 조회
            $onboardingProps = $this->getClassProperties('mk:OnboardingContext');
            
            foreach ($onboardingProps as $prop) {
                if (!isset($strategy[$prop]) && isset($onboardingData[$prop])) {
                    $strategy[$prop] = $onboardingData[$prop];
                }
            }
            
            // 추론 결과 반영 (모든 recommends* 프로퍼티 동적 반영)
            foreach ($onboardingReasoning as $key => $value) {
                if (strpos($key, 'recommends') === 0) {
                    $fullProp = (strpos($key, 'mk:') === 0) ? $key : 'mk:' . $key;
                    $strategy[$fullProp] = $value;
                }
            }
        }
        
        // 3. LearningContextIntegration에서 데이터 추출 (동적 로드)
        if (!empty($learningContexts)) {
            $learningData = $learningContexts[0]['data'];
            $reasoning = $learningContexts[0]['reasoning'] ?? [];
            
            // 온톨로지 스키마에서 LearningContextIntegration의 프로퍼티 동적 조회
            $learningProps = $this->getClassProperties('mk:LearningContextIntegration');
            
            foreach ($learningProps as $prop) {
                if (!isset($strategy[$prop]) && isset($learningData[$prop])) {
                    $strategy[$prop] = $learningData[$prop];
                }
            }
            
            // 추론 결과 반영 (모든 recommends* 프로퍼티 동적 반영)
            foreach ($reasoning as $key => $value) {
                if (strpos($key, 'recommends') === 0) {
                    $fullProp = (strpos($key, 'mk:') === 0) ? $key : 'mk:' . $key;
                    $strategy[$fullProp] = $value;
                }
            }
        }
        
        // 4. [동적 로드] 자신감 기반 도입 루틴 추천
        $confidence = $strategy['mk:hasMathConfidence'] ?? 5;
        if (is_numeric($confidence)) {
            $confidence = (int)$confidence;
            $rules = $this->loadInferenceRules('confidence_based_recommendation');
            foreach ($rules as $rule) {
                if ($confidence >= $rule['min'] && $confidence <= $rule['max']) {
                    $strategy['mk:recommendsIntroductionRoutine'] = $rule['recommendsIntroductionRoutine'];
                    $strategy['mk:recommendsInteractionStyle'] = $rule['recommendsInteractionStyle'];
                    $strategy['mk:recommendsFeedbackTone'] = $rule['recommendsFeedbackTone'];
                    break;
                }
            }
        }
        
        // 5. [동적 로드] 학습 스타일 기반 설명 전략 추천
        $learningStyle = $strategy['mk:hasMathLearningStyle'] ?? '';
        $rules = $this->loadInferenceRules('learning_style_explanation_strategy');
        if (isset($rules[$learningStyle])) {
            $strategy['mk:recommendsExplanationStrategy'] = $rules[$learningStyle];
        } else {
            // [동적 로드] default가 없으면 fallback 기본값 사용
            $fallbackDefaults = $this->loadInferenceRules('fallback_defaults');
            $strategy['mk:recommendsExplanationStrategy'] = $rules['default'] ?? ($fallbackDefaults['explanation_strategy'] ?? null);
            if (!$strategy['mk:recommendsExplanationStrategy']) {
                error_log("[OntologyEngine] 설명 전략 규칙을 찾을 수 없습니다.");
            }
        }
        
        // 6. [동적 로드] 진도 기반 자료 유형 추천
        $conceptProgress = $strategy['mk:hasConceptProgress'] ?? '';
        $advancedProgress = $strategy['mk:hasAdvancedProgress'] ?? '';
        
        $materialRules = $this->loadInferenceRules('material_type_recommendation');
        $fallbackDefaults = $this->loadInferenceRules('fallback_defaults');
        if ($advancedProgress && $advancedProgress !== '') {
            $strategy['mk:recommendsMaterialType'] = $materialRules['hasAdvancedProgress'] ?? ($fallbackDefaults['material_type_advanced'] ?? null);
        } elseif ($conceptProgress && $conceptProgress !== '') {
            $strategy['mk:recommendsMaterialType'] = $materialRules['hasConceptProgress'] ?? ($fallbackDefaults['material_type_standard'] ?? null);
        } else {
            $strategy['mk:recommendsMaterialType'] = $materialRules['default'] ?? ($fallbackDefaults['material_type_basic'] ?? null);
        }
        if (!$strategy['mk:recommendsMaterialType']) {
            error_log("[OntologyEngine] 자료 유형 추천 규칙을 찾을 수 없습니다.");
        }
        
        // 7. [핵심 추가] 교재 추천 로직 (recommendsTextbook)
        $textbooks = $context['textbooks'] ?? $context['hasTextbooks'] ?? null;
        if (!empty($textbooks)) {
            if (is_array($textbooks)) {
                // 첫 번째 교재를 주 교재로 추천
                $strategy['mk:recommendsTextbook'] = is_array($textbooks[0]) ? ($textbooks[0]['name'] ?? $textbooks[0]) : $textbooks[0];
                $strategy['mk:hasTextbooks'] = $textbooks;
            } else {
                $strategy['mk:recommendsTextbook'] = $textbooks;
            }
        } else {
            // 기본 교재 추천 (진도 기반)
            $strategy['mk:recommendsTextbook'] = $this->inferRecommendedTextbook($conceptProgress, $strategy['mk:hasMathLevel'] ?? '');
        }
        
        // 8. [핵심 추가] 단원 추천 로직 (recommendsUnit)
        if (!isset($strategy['mk:recommendsUnits']) || empty($strategy['mk:recommendsUnits'])) {
            $unitMastery = $strategy['mk:hasUnitMastery'] ?? $context['math_unit_mastery'] ?? null;
            $recommendedUnits = $this->inferRecommendedUnits($conceptProgress, $advancedProgress, $unitMastery, null);
            if (!empty($recommendedUnits)) {
                $strategy['mk:recommendsUnits'] = $recommendedUnits;
                $strategy['mk:recommendsUnit'] = $recommendedUnits[0]; // 첫 번째 단원을 주 추천
            }
        }
        
        // 9. [핵심 추가] 문제 유형 추천 (recommendsProblemType)
        $mathLevel = $strategy['mk:hasMathLevel'] ?? '';
        $mathLearningStyle = $strategy['mk:hasMathLearningStyle'] ?? '';
        $strategy['mk:recommendsProblemType'] = $this->inferRecommendedProblemType($mathLevel, $mathLearningStyle, $confidence);
        
        // 10. 컨텍스트의 mk: 프로퍼티 직접 추가 (기존 값 덮어쓰지 않음)
        foreach ($context as $key => $value) {
            if (strpos($key, 'mk:') === 0 && !isset($strategy[$key])) {
                $strategy[$key] = $value;
            }
        }
        
        // 11. [동적 로드] 전략 완성도 검증 로그
        $rules = $this->loadInferenceRules('required_properties');
        if (empty($rules)) {
            error_log("[OntologyEngine] 필수 프로퍼티 규칙을 찾을 수 없습니다. inference_rules.json의 required_properties를 확인하세요.");
            $requiredProps = [];
        } else {
            $requiredProps = $rules;
        }
        $filledProps = array_filter($requiredProps, function($prop) use ($strategy) {
            return isset($strategy[$prop]) && !empty($strategy[$prop]);
        });
        error_log("[OntologyEngine] buildStrategy completed with " . count($strategy) . " properties, required filled: " . count($filledProps) . "/" . count($requiredProps));
        
        return $strategy;
    }
    
    /**
     * [동적 로드] 절차 단계 구축 (procedure_template.json 사용)
     * 
     * 첫 수업 30분 진행안을 상세하게 생성
     */
    private function buildProcedureSteps(array $strategyJsonld): array {
        $steps = [];
        $stepOrder = 1;
        
        // [동적 로드] 절차 템플릿 로드
        $templatePath = __DIR__ . '/../procedure_template.json';
        $template = [];
        if (file_exists($templatePath)) {
            try {
                $template = json_decode(file_get_contents($templatePath), true);
            } catch (Exception $e) {
                error_log("[OntologyEngine] 절차 템플릿 로드 실패: " . $e->getMessage());
            }
        }
        
        // [동적 로드] 자신감 수준 확인
        $confidence = $strategyJsonld['mk:hasMathConfidence'] ?? 5;
        $defaultValues = $this->loadInferenceRules('default_values');
        $learningStyle = $strategyJsonld['mk:hasMathLearningStyle'] ?? ($defaultValues['learningStyle'] ?? null);
        $studyStyle = $strategyJsonld['mk:hasStudyStyle'] ?? ($defaultValues['studyStyle'] ?? null);
        if (!$learningStyle || !$studyStyle) {
            error_log("[OntologyEngine] 학습 스타일 기본값을 찾을 수 없습니다. inference_rules.json의 default_values를 확인하세요.");
        }
        
        // 자신감 수준 판단
        $confidenceLevel = 'high';
        if (!empty($template['confidence_thresholds'])) {
            if ($confidence <= $template['confidence_thresholds']['low']) {
                $confidenceLevel = 'low';
            } elseif ($confidence <= $template['confidence_thresholds']['medium']) {
                $confidenceLevel = 'medium';
            }
        } else {
            // 템플릿이 없으면 기본값 사용 (하드코딩 제거)
            error_log("[OntologyEngine] 절차 템플릿을 찾을 수 없습니다. procedure_template.json을 확인하세요.");
            $confidenceLevel = 'medium';
        }
        
        // [동적 로드] 템플릿에서 절차 단계 생성
        $procedureSteps = $template['procedure_steps'] ?? [];
        foreach ($procedureSteps as $stepTemplate) {
            $stepType = $stepTemplate['stepType'] ?? '';
            $stepDuration = $stepTemplate['stepDuration'] ?? '';
            $descriptions = $stepTemplate['descriptions'] ?? [];
            
            // 설명 선택 로직
            $description = '';
            if ($stepType === 'introduction') {
                $description = $descriptions[$confidenceLevel . '_confidence'] ?? $descriptions['default'] ?? '';
            } elseif ($stepType === 'explanation') {
                $description = $descriptions[$learningStyle] ?? $descriptions['default'] ?? '';
            } elseif ($stepType === 'practice') {
                $description = $descriptions[$studyStyle] ?? $descriptions['default'] ?? '';
            } elseif ($stepType === 'closing') {
                $description = $descriptions[$confidenceLevel . '_confidence'] ?? $descriptions['default'] ?? '';
            } else {
                $description = $descriptions['default'] ?? '';
            }
            
            $steps[] = [
                '@type' => 'mk:ProcedureStep',
                'mk:stepOrder' => $stepOrder++,
                'mk:stepType' => $stepType,
                'mk:stepDescription' => $description,
                'mk:stepDuration' => $stepDuration
            ];
        }
        
        // 추가: 추천 정보가 있으면 반영
        if (isset($strategyJsonld['mk:recommendsIntroductionRoutine']) && !empty($steps[0])) {
            $steps[0]['mk:stepDescription'] = $strategyJsonld['mk:recommendsIntroductionRoutine'];
        }
        
        if (isset($strategyJsonld['mk:recommendsExplanationStrategy']) && !empty($steps[2])) {
            $steps[2]['mk:stepDescription'] = $strategyJsonld['mk:recommendsExplanationStrategy'];
        }
        
        if (isset($strategyJsonld['mk:recommendsFeedbackTone']) && !empty($steps[4])) {
            $steps[4]['mk:stepDescription'] = '피드백: ' . $strategyJsonld['mk:recommendsFeedbackTone'];
        }
        
        return $steps;
    }
    
    /**
     * 부모 관계 설정
     */
    public function setParentRelation(string $childId, string $parentId): void {
        try {
            $child = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $childId]);
            if (!$child) {
                return;
            }
            
            $jsonld = json_decode($child->jsonld_data, true);
            $jsonld['mk:hasParent'] = $parentId;
            
            $child->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $child->parent_instance_id = $parentId;
            $child->updated_at = time();
            
            $this->db->update_record('alt42_ontology_instances', $child);
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error setting parent relation: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // ========== 진단 및 디버깅 메서드 (Q1 진단 뷰 연동) ==========
    
    /**
     * 엔진 진단 정보 반환
     * 
     * @return array 진단 정보
     */
    public function getDiagnostics(): array {
        $diagnostics = [
            'engine_version' => '2.0.0-profile-based',
            'schema_validation_enabled' => $this->enableSchemaValidation,
            'schema_loader_available' => $this->schemaLoader !== null,
            'validation_log_count' => count($this->validationLog),
            'validation_log' => $this->validationLog
        ];
        
        // 스키마 로더 진단 정보 추가
        if ($this->schemaLoader) {
            $diagnostics['schema'] = $this->schemaLoader->getDiagnostics();
        }
        
        // DB 상태 확인
        try {
            if ($this->db) {
                $diagnostics['db_connected'] = true;
                $diagnostics['instance_count'] = $this->db->count_records('alt42_ontology_instances');
            }
        } catch (Exception $e) {
            $diagnostics['db_connected'] = false;
            $diagnostics['db_error'] = $e->getMessage();
        }
        
        return $diagnostics;
    }
    
    /**
     * 인스턴스 데이터 스키마 검증
     * 
     * @param string $instanceId 인스턴스 ID
     * @return array 검증 결과
     */
    public function validateInstanceSchema(string $instanceId): array {
        if (!$this->schemaLoader) {
            return ['valid' => true, 'message' => 'SchemaLoader가 비활성화되어 있습니다'];
        }
        
        try {
            $instance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $instanceId]);
            if (!$instance) {
                return ['valid' => false, 'error' => "인스턴스를 찾을 수 없습니다: {$instanceId}"];
            }
            
            $jsonld = json_decode($instance->jsonld_data, true);
            $classType = $instance->class_type;
            
            return $this->schemaLoader->validateInstance($classType, $jsonld);
            
        } catch (Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Q1 첫수업 전략 파이프라인 실행 (스키마→추론→응답)
     * 
     * @param int $studentId 학생 ID
     * @param array $context 컨텍스트 데이터
     * @return array 파이프라인 실행 결과
     */
    public function executeQ1Pipeline(int $studentId, array $context): array {
        $result = [
            'success' => false,
            'stages' => [],
            'errors' => [],
            'strategy' => null,
            'procedure' => null
        ];
        
        try {
            // Stage 1: OnboardingContext 인스턴스 생성
            $result['stages']['context_creation'] = ['status' => 'started'];
            $onboardingContextId = $this->createInstance(
                'mk:OnboardingContext',
                [
                    'mk:hasStudentGrade' => $context['student_grade'] ?? '',
                    'mk:hasSchool' => $context['school_name'] ?? '',
                    'mk:hasAcademy' => $context['academy_name'] ?? '',
                    'mk:hasAcademyGrade' => $context['academy_grade'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['context_creation'] = ['status' => 'completed', 'instance_id' => $onboardingContextId];
            
            // Stage 2: LearningContextIntegration 인스턴스 생성
            $result['stages']['learning_context'] = ['status' => 'started'];
            $learningContextId = $this->createInstance(
                'mk:LearningContextIntegration',
                [
                    'mk:hasConceptProgress' => $context['concept_progress'] ?? '',
                    'mk:hasAdvancedProgress' => $context['advanced_progress'] ?? '',
                    'mk:hasUnitMastery' => $context['math_unit_mastery'] ?? '',
                    'mk:hasCurrentPosition' => $context['current_progress_position'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['learning_context'] = ['status' => 'completed', 'instance_id' => $learningContextId];
            
            // Stage 3: 의미 기반 추론
            $result['stages']['reasoning'] = ['status' => 'started'];
            $learningReasoning = $this->reasonOver('mk:LearningContextIntegration', null, $studentId);
            $onboardingReasoning = $this->reasonOver('mk:OnboardingContext', null, $studentId);
            $result['stages']['reasoning'] = [
                'status' => 'completed',
                'learning_context_results' => count($learningReasoning),
                'onboarding_context_results' => count($onboardingReasoning)
            ];
            
            // Stage 4: 전략 생성
            $result['stages']['strategy_generation'] = ['status' => 'started'];
            $strategyResult = $this->generateStrategy('mk:FirstClassStrategy', $context, $studentId);
            $result['stages']['strategy_generation'] = ['status' => 'completed', 'instance_id' => $strategyResult['instance_id']];
            $result['strategy'] = $strategyResult;
            
            // Stage 5: 절차 생성
            $result['stages']['procedure_generation'] = ['status' => 'started'];
            $procedureResult = $this->generateProcedure('mk:LessonProcedure', $strategyResult['instance_id'], $studentId);
            $result['stages']['procedure_generation'] = ['status' => 'completed', 'instance_id' => $procedureResult['instance_id']];
            $result['procedure'] = $procedureResult;
            
            $result['success'] = true;
            $result['errors'] = $this->validationLog;
            
        } catch (Exception $e) {
            $result['errors'][] = [
                'type' => 'pipeline_error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        return $result;
    }
    
    /**
     * Q2 커리큘럼과 루틴 최적화 파이프라인 실행
     */
    public function executeQ2Pipeline(int $studentId, array $context): array {
        $result = [
            'success' => false,
            'stages' => [],
            'errors' => [],
            'strategy' => null,
            'procedure' => null
        ];
        
        try {
            // Stage 1: CurriculumOptimization 인스턴스 생성
            $result['stages']['curriculum_optimization'] = ['status' => 'started'];
            $curriculumOptimizationId = $this->createInstance(
                'mk-a01-mod:CurriculumOptimization',
                [
                    'mk-a01-mod:hasGoalAnalysis' => $context['goal_analysis'] ?? '',
                    'mk-a01-mod:hasLearningStyle' => $context['math_learning_style'] ?? '',
                    'mk-a01-mod:hasStressLevel' => $context['stress_level'] ?? '',
                    'mk-a01-mod:hasParentInvolvement' => $context['parent_style'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['curriculum_optimization'] = ['status' => 'completed', 'instance_id' => $curriculumOptimizationId];
            
            // Stage 2: GoalBasedCurriculum 인스턴스 생성
            $result['stages']['goal_based_curriculum'] = ['status' => 'started'];
            $goalBasedCurriculumId = $this->createInstance(
                'mk-a01-mod:GoalBasedCurriculum',
                [
                    'mk-a01-mod:hasShortTermGoal' => $context['short_term_goal'] ?? '',
                    'mk-a01-mod:hasMidTermGoal' => $context['mid_term_goal'] ?? '',
                    'mk-a01-mod:hasLongTermGoal' => $context['long_term_goal'] ?? '',
                    'mk-a01-mod:hasConceptProgress' => $context['concept_progress'] ?? '',
                    'mk-a01-mod:hasAdvancedProgress' => $context['advanced_progress'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['goal_based_curriculum'] = ['status' => 'completed', 'instance_id' => $goalBasedCurriculumId];
            
            // Stage 3: LearningFlow 인스턴스 생성
            $result['stages']['learning_flow'] = ['status' => 'started'];
            $learningFlowId = $this->createInstance(
                'mk-a01-mod:LearningFlow',
                [
                    'mk-a01-mod:hasStudyStyle' => $context['study_style'] ?? '',
                    'mk-a01-mod:hasMathLearningStyle' => $context['math_learning_style'] ?? '',
                    'mk-a01-mod:hasExamStyle' => $context['exam_style'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['learning_flow'] = ['status' => 'completed', 'instance_id' => $learningFlowId];
            
            // Stage 4: 의미 기반 추론
            $result['stages']['reasoning'] = ['status' => 'started'];
            $curriculumReasoning = $this->reasonOver('mk-a01-mod:CurriculumOptimization', null, $studentId);
            $goalReasoning = $this->reasonOver('mk-a01-mod:GoalBasedCurriculum', null, $studentId);
            $flowReasoning = $this->reasonOver('mk-a01-mod:LearningFlow', null, $studentId);
            $result['stages']['reasoning'] = [
                'status' => 'completed',
                'curriculum_optimization_results' => count($curriculumReasoning),
                'goal_based_curriculum_results' => count($goalReasoning),
                'learning_flow_results' => count($flowReasoning)
            ];
            // 실제 추론 결과 저장
            $result['reasoning'] = array_merge(
                $curriculumReasoning,
                $goalReasoning,
                $flowReasoning
            );
            
            // Stage 5: 전략 생성
            $result['stages']['strategy_generation'] = ['status' => 'started'];
            $strategyResult = $this->generateStrategy('mk-a01-mod:CurriculumOptimization', $context, $studentId);
            $result['stages']['strategy_generation'] = ['status' => 'completed', 'instance_id' => $strategyResult['instance_id']];
            $result['strategy'] = $strategyResult;
            
            $result['success'] = true;
            $result['errors'] = $this->validationLog;
            
        } catch (Exception $e) {
            $result['errors'][] = [
                'type' => 'pipeline_error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        return $result;
    }
    
    /**
     * Q3 중장기 성장 전략 파이프라인 실행
     */
    public function executeQ3Pipeline(int $studentId, array $context): array {
        $result = [
            'success' => false,
            'stages' => [],
            'errors' => [],
            'strategy' => null,
            'procedure' => null
        ];
        
        try {
            // Stage 1: LongTermGrowthStrategy 인스턴스 생성
            $result['stages']['growth_strategy'] = ['status' => 'started'];
            $growthStrategyId = $this->createInstance(
                'mk-a01-mod:LongTermGrowthStrategy',
                [
                    'mk-a01-mod:hasLongTermGoal' => $context['long_term_goal'] ?? '',
                    'mk-a01-mod:hasMathConfidence' => $context['math_confidence'] ?? '',
                    'mk-a01-mod:hasFatiguePattern' => $context['fatigue_pattern'] ?? '',
                    'mk-a01-mod:hasRoutineStability' => $context['routine_stability'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['growth_strategy'] = ['status' => 'completed', 'instance_id' => $growthStrategyId];
            
            // Stage 2: RiskPrediction 인스턴스 생성
            $result['stages']['risk_prediction'] = ['status' => 'started'];
            $riskPredictionId = $this->createInstance(
                'mk-a01-mod:RiskPrediction',
                [
                    'mk-a01-mod:hasMathConfidence' => $context['math_confidence'] ?? '',
                    'mk-a01-mod:hasStressLevel' => $context['stress_level'] ?? '',
                    'mk-a01-mod:hasStudyHoursPerWeek' => $context['weekly_hours'] ?? '',
                    'mk-a01-mod:hasStudyStyle' => $context['study_style'] ?? '',
                    'mk-a01-mod:hasRiskLevel' => $context['risk_level'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['risk_prediction'] = ['status' => 'completed', 'instance_id' => $riskPredictionId];
            
            // Stage 3: TrackingPriority 인스턴스 생성
            $result['stages']['tracking_priority'] = ['status' => 'started'];
            $trackingPriorityId = $this->createInstance(
                'mk-a01-mod:TrackingPriority',
                [
                    'mk-a01-mod:hasPriorityList' => $context['priority_list'] ?? ''
                ],
                $studentId,
                $context
            );
            $result['stages']['tracking_priority'] = ['status' => 'completed', 'instance_id' => $trackingPriorityId];
            
            // Stage 4: 의미 기반 추론
            $result['stages']['reasoning'] = ['status' => 'started'];
            $growthReasoning = $this->reasonOver('mk-a01-mod:LongTermGrowthStrategy', null, $studentId);
            $riskReasoning = $this->reasonOver('mk-a01-mod:RiskPrediction', null, $studentId);
            $trackingReasoning = $this->reasonOver('mk-a01-mod:TrackingPriority', null, $studentId);
            $result['stages']['reasoning'] = [
                'status' => 'completed',
                'growth_strategy_results' => count($growthReasoning),
                'risk_prediction_results' => count($riskReasoning),
                'tracking_priority_results' => count($trackingReasoning)
            ];
            // 실제 추론 결과 저장
            $result['reasoning'] = array_merge(
                $growthReasoning,
                $riskReasoning,
                $trackingReasoning
            );
            
            // Stage 5: 전략 생성
            $result['stages']['strategy_generation'] = ['status' => 'started'];
            $strategyResult = $this->generateStrategy('mk-a01-mod:LongTermGrowthStrategy', $context, $studentId);
            $result['stages']['strategy_generation'] = ['status' => 'completed', 'instance_id' => $strategyResult['instance_id']];
            $result['strategy'] = $strategyResult;
            
            $result['success'] = true;
            $result['errors'] = $this->validationLog;
            
        } catch (Exception $e) {
            $result['errors'][] = [
                'type' => 'pipeline_error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        return $result;
    }
    
    // ============================================================
    // [신규 함수들 - 신뢰도 9.8~10/10 달성용]
    // ============================================================
    
    /**
     * [신규 함수] 프로퍼티 값 타입 강제화
     * int/string/array 형태를 강제로 변환
     * 
     * 🔧 동적 로드: SchemaLoader를 통해 온톨로지.jsonld에서 타입 정보를 읽음
     * 
     * @param string $property 온톨로지 프로퍼티 (mk:hasMathConfidence 등)
     * @param mixed $value 원본 값
     * @return mixed 타입이 강제화된 값
     */
    public function enforcePropertyType(string $property, $value) {
        // [동적 로드] SchemaLoader에서 XSD 타입 조회
        $xsdType = null;
        if ($this->schemaLoader) {
            $xsdType = $this->schemaLoader->getPropertyType($property);
        }
        
        // XSD 타입을 PHP 타입으로 변환
        $type = $this->xsdToPhpType($xsdType);
        
        if ($type === null) {
            return $value; // 타입 정의 없으면 그대로 반환
        }
        
        switch ($type) {
            case 'int':
                if (is_numeric($value)) {
                    return intval($value);
                }
                // 문자열에서 숫자 추출 시도
                if (is_string($value) && preg_match('/(\d+)/', $value, $m)) {
                    return intval($m[1]);
                }
                return 0;
                
            case 'string':
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                return strval($value);
                
            case 'array':
                if (is_string($value)) {
                    // JSON 문자열인 경우 파싱
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                    // 쉼표로 구분된 문자열인 경우
                    if (strpos($value, ',') !== false) {
                        return array_map('trim', explode(',', $value));
                    }
                    return [$value];
                }
                if (!is_array($value)) {
                    return [$value];
                }
                return $value;
                
            case 'id':
                // @id 타입은 문자열로 처리
                return strval($value);
                
            default:
                return $value;
        }
    }
    
    /**
     * [동적 로드] XSD 타입을 PHP 타입으로 변환
     * 
     * @param string|null $xsdType XSD 타입 (xsd:integer, xsd:string, @id 등)
     * @return string|null PHP 타입 (int, string, array, id)
     */
    private function xsdToPhpType(?string $xsdType): ?string {
        if ($xsdType === null) {
            return null;
        }
        
        $mapping = [
            'xsd:integer' => 'int',
            'xsd:int' => 'int',
            'xsd:string' => 'string',
            'xsd:boolean' => 'bool',
            'xsd:decimal' => 'float',
            'xsd:float' => 'float',
            'xsd:double' => 'float',
            '@id' => 'id',  // 참조 타입
        ];
        
        // @type가 없는 프로퍼티는 array로 간주 (예: textbooks, recommendsUnits)
        if (!isset($mapping[$xsdType])) {
            // @id가 아닌 경우 배열일 가능성이 높음
            return $xsdType === null ? 'array' : 'string';
        }
        
        return $mapping[$xsdType] ?? 'string';
    }
    
    /**
     * [동적 로드] 컨텍스트 데이터를 온톨로지 프로퍼티로 자동 변환
     * 
     * SchemaLoader::getOfficialVariableMapping()을 활용하여 동적 매핑
     * 
     * @param array $context 컨텍스트 데이터
     * @return array ['mapped' => 온톨로지 데이터, 'unmapped_keys' => 미매핑 키 배열]
     */
    public function mapContextToOntology(array $context): array {
        $ontologyData = [];
        $unmappedKeys = [];
        
        // [동적 로드] SchemaLoader에서 공식 매핑 테이블 가져오기
        $officialMapping = SchemaLoader::getOfficialVariableMapping();
        
        foreach ($context as $key => $value) {
            // 1. 공식 매핑 테이블에서 찾기
            $ontologyProp = null;
            if (isset($officialMapping[$key])) {
                $ontologyProp = $officialMapping[$key];
                // mk: 프리픽스 추가 (없는 경우)
                if (strpos($ontologyProp, 'mk:') !== 0 && strpos($ontologyProp, 'has') === 0) {
                    $ontologyProp = 'mk:' . $ontologyProp;
                }
            }
            
            // 2. SchemaLoader의 mapContextToOntology 사용
            if ($ontologyProp === null && $this->schemaLoader) {
                $ontologyProp = $this->schemaLoader->mapContextToOntology($key);
                if ($ontologyProp) {
                    $ontologyProp = 'mk:' . $ontologyProp;
                }
            }
            
            if ($ontologyProp) {
                $typedValue = $this->enforcePropertyType($ontologyProp, $value);
                $ontologyData[$ontologyProp] = $typedValue;
            } else {
                // 매핑되지 않은 키 기록 (시스템 키 제외)
                $systemKeys = ['student_id', 'user_id', 'timestamp', 'session_id', 'request_id', 'user_message', 'conversation_timestamp'];
                if (!in_array($key, $systemKeys)) {
                    $unmappedKeys[] = $key;
                }
            }
        }
        
        // 미매핑 키 로깅 (디버그용)
        if (!empty($unmappedKeys)) {
            error_log("[OntologyEngine] 미매핑 컨텍스트 키 (" . count($unmappedKeys) . "개): " . implode(', ', array_slice($unmappedKeys, 0, 10)));
        }
        
        return [
            'mapped' => $ontologyData,
            'unmapped_keys' => $unmappedKeys,
            'mapped_count' => count($ontologyData),
            'unmapped_count' => count($unmappedKeys)
        ];
    }
    
    /**
     * [신규 함수] DB 저장 상태 진단
     * recordInstance 또는 insert_record 경로 점검용
     * 
     * @param int $studentId 학생 ID
     * @return array 진단 결과
     */
    public function diagnoseSaveStatus(int $studentId): array {
        $diagnosis = [
            'table_exists' => false,
            'record_count' => 0,
            'recent_records' => [],
            'db_connection' => false,
            'student_id' => $studentId
        ];
        
        try {
            // DB 연결 확인
            $diagnosis['db_connection'] = ($this->db !== null);
            
            if (!$this->db) {
                $diagnosis['error'] = 'DB 연결 없음';
                return $diagnosis;
            }
            
            // 테이블 존재 확인
            $dbman = $this->db->get_manager();
            $table = new xmldb_table('alt42_ontology_instances');
            $diagnosis['table_exists'] = $dbman->table_exists($table);
            
            if (!$diagnosis['table_exists']) {
                $diagnosis['error'] = '테이블 없음: alt42_ontology_instances';
                return $diagnosis;
            }
            
            // 전체 레코드 수 확인
            $diagnosis['total_record_count'] = $this->db->count_records('alt42_ontology_instances');
            
            // 해당 학생 레코드 수 확인
            $diagnosis['record_count'] = $this->db->count_records('alt42_ontology_instances', ['student_id' => $studentId]);
            
            // 최근 레코드 조회 (최대 5개)
            $records = $this->db->get_records('alt42_ontology_instances', 
                ['student_id' => $studentId], 
                'created_at DESC', 
                'id, instance_id, class_type, stage, created_at', 
                0, 5);
            
            $diagnosis['recent_records'] = [];
            foreach ($records as $r) {
                $diagnosis['recent_records'][] = [
                    'id' => $r->id,
                    'instance_id' => substr($r->instance_id, 0, 60) . '...',
                    'class_type' => $r->class_type,
                    'stage' => $r->stage,
                    'created_at' => date('Y-m-d H:i:s', $r->created_at)
                ];
            }
            
            $diagnosis['status'] = $diagnosis['record_count'] > 0 ? 'OK' : 'NO_RECORDS';
            
        } catch (Exception $e) {
            $diagnosis['error'] = $e->getMessage();
            $diagnosis['status'] = 'ERROR';
        }
        
        return $diagnosis;
    }
    
    /**
     * [동적 로드] 프로퍼티 타입 정보 반환
     * 온톨로지.jsonld의 @context에서 동적으로 로드
     */
    public function getPropertyTypes(): array {
        if ($this->propertyTypesCache !== null) {
            return $this->propertyTypesCache;
        }
        
        $types = [];
        if ($this->schemaLoader) {
            $allProps = $this->schemaLoader->getAllProperties();
            foreach ($allProps as $propName => $propDef) {
                $xsdType = $propDef['type'] ?? null;
                $phpType = $this->xsdToPhpType($xsdType);
                if ($phpType) {
                    $types['mk:' . $propName] = $phpType;
                }
            }
        }
        
        $this->propertyTypesCache = $types;
        return $types;
    }
    
    /**
     * [동적 로드] 컨텍스트-온톨로지 매핑 정보 반환
     */
    public function getContextMappings(): array {
        return SchemaLoader::getOfficialVariableMapping();
    }
    
    /**
     * [동적 로드] 클래스의 프로퍼티 목록 조회
     * 온톨로지.jsonld의 @context에서 해당 클래스와 관련된 프로퍼티 추출
     * 
     * @param string $classUri 클래스 URI (예: 'mk:OnboardingContext')
     * @return array 프로퍼티 URI 배열 (예: ['mk:hasMathLevel', 'mk:hasMathConfidence', ...])
     */
    private function getClassProperties(string $classUri): array {
        $properties = [];
        
        if (!$this->schemaLoader) {
            return $properties;
        }
        
        // SchemaLoader에서 모든 프로퍼티 가져오기
        $allProps = $this->schemaLoader->getAllProperties();
        
        // 클래스명에서 프리픽스 제거 (예: mk:OnboardingContext -> OnboardingContext)
        $className = str_replace('mk:', '', $classUri);
        
        // 프로퍼티명에서 클래스명 추론
        // 예: OnboardingContext -> hasStudentGrade, hasSchool, hasAcademy 등
        foreach ($allProps as $propName => $propDef) {
            $propId = $propDef['id'] ?? 'mk:' . $propName;
            
            // has* 또는 recommends* 프로퍼티는 해당 클래스와 연관될 가능성이 높음
            // 더 정확한 매칭을 위해 온톨로지 스키마의 도메인(domain) 정보가 있으면 사용
            // 현재는 프로퍼티명 패턴으로 추론
            if (strpos($propName, 'has') === 0 || strpos($propName, 'recommends') === 0) {
                $properties[] = $propId;
            }
        }
        
        return $properties;
    }
    
    /**
     * [동적 로드] 온톨로지 클래스의 레이블 가져오기
     * 온톨로지.jsonld의 @graph에서 rdfs:label 조회
     * 
     * @param string $classUri 클래스 URI (예: 'mk:OnboardingContext')
     * @return string 레이블 또는 클래스명
     */
    public function getClassLabel(string $classUri): string {
        if ($this->classLabelsCache === null) {
            $this->classLabelsCache = [];
            if ($this->schemaLoader) {
                $allClasses = $this->schemaLoader->getAllClasses();
                foreach ($allClasses as $classId => $classDef) {
                    $this->classLabelsCache[$classId] = $classDef['label'] ?? $classId;
                }
            }
        }
        
        return $this->classLabelsCache[$classUri] ?? $classUri;
    }
    
    /**
     * [동적 로드] 온톨로지 프로퍼티의 레이블 가져오기
     * 
     * SchemaLoader를 활용하여 프로퍼티의 타입에서 클래스 레이블 조회,
     * 또는 CamelCase를 한글로 자동 변환
     * 
     * @param string $property 프로퍼티 (예: 'mk:hasMathLevel')
     * @return string 레이블 또는 프로퍼티명
     */
    public function getPropertyLabel(string $property): string {
        // mk: 프리픽스 제거
        $shortName = str_replace('mk:', '', $property);
        
        // [동적 로드] 프로퍼티 타입에서 연결된 클래스의 rdfs:label 조회 시도
        if ($this->schemaLoader) {
            // 프로퍼티의 @type이 @id인 경우 연결된 클래스 레이블 사용
            $propDef = $this->schemaLoader->getPropertyDefinition($shortName);
            if ($propDef && isset($propDef['type']) && $propDef['type'] === '@id') {
                // 프로퍼티명에서 연결된 클래스 추론 (예: hasMathLevel → MathLevel)
                $className = preg_replace('/^(has|recommends)/', '', $shortName);
                $classUri = 'mk:' . $className;
                $classLabel = $this->getClassLabel($classUri);
                if ($classLabel && $classLabel !== $classUri) {
                    return $classLabel;
                }
            }
        }
        
        // [동적 생성] CamelCase를 한글로 자동 변환
        return $this->camelCaseToKorean($shortName);
    }
    
    /**
     * Agent02 전용: 정렬 플랜 추론
     */
    private function inferAlignmentPlan(?string $academyProgress, ?string $schoolExamScope, ?int $progressGap): ?string {
        if (!$academyProgress || !$schoolExamScope) {
            return null;
        }
        
        // 진도 격차에 따른 정렬 전략 추론
        if ($progressGap !== null) {
            if ($progressGap > 2) {
                return '학원 진도에 맞춰 학교 진도 보완';
            } elseif ($progressGap < -2) {
                return '학교 진도에 맞춰 학원 진도 보완';
            } else {
                return '학교-학원 진도 정렬 유지';
            }
        }
        
        return '학원-학교-집 3축 정렬 플랜 수립';
    }
    
    /**
     * Agent02 전용: 전략 비율 추론
     */
    private function inferStrategyRatio(?int $targetScore, ?int $currentExpectedScore, ?float $unitAccuracyRate): ?array {
        if ($targetScore === null || $currentExpectedScore === null) {
            return null;
        }
        
        $scoreGap = $targetScore - $currentExpectedScore;
        
        // 점수 차이에 따른 전략 비율 조정
        if ($scoreGap > 20) {
            // 큰 차이: 개념 중심
            return ['concept' => 0.5, 'type' => 0.3, 'advanced' => 0.15, 'pastExam' => 0.05];
        } elseif ($scoreGap > 10) {
            // 중간 차이: 균형
            return ['concept' => 0.4, 'type' => 0.35, 'advanced' => 0.15, 'pastExam' => 0.1];
        } else {
            // 작은 차이: 유형/기출 중심
            return ['concept' => 0.3, 'type' => 0.4, 'advanced' => 0.2, 'pastExam' => 0.1];
        }
    }
    
    /**
     * Agent02 전용: 개선 전략 추론
     */
    private function inferImprovementStrategy(?int $academyRank, ?int $schoolScore, ?float $textbookEffectivenessIndex): ?string {
        $improvements = [];
        
        if ($textbookEffectivenessIndex !== null && $textbookEffectivenessIndex < 0.7) {
            $improvements[] = '교재 효과 지표 개선 필요';
        }
        
        if ($academyRank !== null && $academyRank > 20) {
            $improvements[] = '학원 등수 향상을 위한 전략 수정';
        }
        
        if ($schoolScore !== null && $schoolScore < 80) {
            $improvements[] = '학교 성적 향상을 위한 보완 단원 학습';
        }
        
        return !empty($improvements) ? implode(', ', $improvements) : '현재 전략 유지';
    }
    
    /**
     * [동적 생성] CamelCase 프로퍼티명을 한글로 변환
     * 
     * @param string $camelCase 프로퍼티명 (예: 'hasMathLevel')
     * @return string 한글 레이블 (예: '수학 수준')
     */
    private function camelCaseToKorean(string $camelCase): string {
        // 접두사 제거
        $name = preg_replace('/^(has|recommends|get|set)/', '', $camelCase);
        
        // [동적 로드] 영어 키워드 → 한글 매핑 (procedure_template.json에서 로드)
        $templatePath = __DIR__ . '/../procedure_template.json';
        $keywords = [];
        if (file_exists($templatePath)) {
            try {
                $template = json_decode(file_get_contents($templatePath), true);
                $keywords = $template['property_keyword_mapping'] ?? [];
            } catch (Exception $e) {
                error_log("[OntologyEngine] 키워드 매핑 로드 실패: " . $e->getMessage());
            }
        }
        
        // 키워드가 없으면 에러 로그만 남기고 빈 배열 사용
        if (empty($keywords)) {
            error_log("[OntologyEngine] 키워드 매핑을 찾을 수 없습니다. procedure_template.json의 property_keyword_mapping을 확인하세요.");
        }
        
        // CamelCase를 단어로 분리
        $words = preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);
        
        // 각 단어를 한글로 변환
        $koreanWords = [];
        foreach ($words as $word) {
            $koreanWords[] = $keywords[$word] ?? $word;
        }
        
        return implode(' ', $koreanWords);
    }
}

