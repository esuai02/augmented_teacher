<?php
/**
 * 범용 온톨로지 엔진
 * File: agent22_module_improvement/ontology/UniversalOntologyEngine.php
 * 
 * 모든 에이전트가 공통으로 사용할 수 있는 온톨로지 엔진
 * Agent01의 OntologyEngine을 기반으로 범용화
 */

// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/OntologyConfig.php');
require_once(__DIR__ . '/OntologyFileLoader.php');

class UniversalOntologyEngine {
    
    private $db;
    private $agentId;
    private $namespace;
    private $prefix;
    
    /**
     * Constructor
     * 
     * @param string $agentId 에이전트 ID (예: 'agent01')
     */
    public function __construct(string $agentId) {
        global $DB;
        $this->db = $DB;
        $this->agentId = OntologyConfig::normalizeAgentId($agentId);
        $this->namespace = OntologyConfig::getOntologyNamespace($this->agentId);
        $this->prefix = OntologyConfig::getOntologyPrefix($this->agentId);
        $this->ensureTableExists();
    }
    
    /**
     * 온톨로지 인스턴스 저장 테이블 생성 및 확장
     */
    private function ensureTableExists() {
        $dbman = $this->db->get_manager();
        
        $table = new xmldb_table('alt42_ontology_instances');
        
        if (!$dbman->table_exists($table)) {
            // 테이블 생성
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('instance_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('agent_id', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('class_type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('jsonld_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('stage', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('parent_instance_id', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('instance_id_idx', XMLDB_INDEX_UNIQUE, ['instance_id']);
            $table->add_index('student_id_idx', XMLDB_INDEX_NOTUNIQUE, ['student_id']);
            $table->add_index('agent_id_idx', XMLDB_INDEX_NOTUNIQUE, ['agent_id']);
            $table->add_index('class_type_idx', XMLDB_INDEX_NOTUNIQUE, ['class_type']);
            
            $dbman->create_table($table);
        } else {
            // 테이블이 존재하면 agent_id 컬럼이 있는지 확인하고 없으면 추가
            $field = new xmldb_field('agent_id');
            if (!$dbman->field_exists($table, $field)) {
                $field->set_attributes(XMLDB_TYPE_CHAR, '50', null, null, null, null);
                $dbman->add_field($table, $field);
                
                // 인덱스 추가
                $index = new xmldb_index('agent_id_idx', XMLDB_INDEX_NOTUNIQUE, ['agent_id']);
                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
                
                error_log("[UniversalOntologyEngine] Added agent_id column to alt42_ontology_instances table [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
        }
    }
    
    /**
     * 온톨로지 인스턴스 생성
     * 
     * @param string $class 클래스 URI (예: 'mk:OnboardingContext')
     * @param array $properties 프로퍼티 배열
     * @param int|null $studentId 학생 ID
     * @return string 생성된 인스턴스 ID
     * @throws Exception
     */
    public function createInstance(string $class, array $properties = [], ?int $studentId = null): string {
        try {
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
                $this->prefix . 'hasStage' => $stage
            ];
            
            // 프로퍼티 추가
            foreach ($properties as $key => $value) {
                // 변수 치환 (예: {gradeLevel} → 실제 값)
                if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                    // 변수는 나중에 setProperty로 설정
                    continue;
                }
                $jsonld[$key] = $value;
            }
            
            // 데이터베이스에 저장
            $record = new stdClass();
            $record->instance_id = $instanceId;
            $record->student_id = $studentId;
            $record->agent_id = $this->agentId;
            $record->class_type = $class;
            $record->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $record->stage = $stage;
            $record->parent_instance_id = null;
            $record->created_at = time();
            $record->updated_at = time();
            
            $this->db->insert_record('alt42_ontology_instances', $record);
            
            error_log("[UniversalOntologyEngine] Created instance: {$instanceId} for agent: {$this->agentId}, student: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $instanceId;
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error creating instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            // 인스턴스 조회 (에이전트 ID도 확인)
            $instance = $this->db->get_record('alt42_ontology_instances', [
                'instance_id' => $instanceId,
                'agent_id' => $this->agentId
            ]);
            if (!$instance) {
                throw new Exception("Instance not found: {$instanceId} for agent: {$this->agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // JSON-LD 파싱
            $jsonld = json_decode($instance->jsonld_data, true);
            if (!$jsonld) {
                throw new Exception("Invalid JSON-LD data for instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // 값 치환 (변수 처리)
            if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                $varName = $matches[1];
                if ($context && isset($context[$varName])) {
                    $value = $context[$varName];
                } else {
                    $value = $this->resolveVariable($varName, $context);
                }
            }
            
            // 프로퍼티 설정
            $jsonld[$property] = $value;
            
            // 업데이트
            $instance->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $instance->updated_at = time();
            $this->db->update_record('alt42_ontology_instances', $instance);
            
            error_log("[UniversalOntologyEngine] Set property {$property} = " . (is_array($value) ? json_encode($value) : $value) . " for instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error setting property: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            
            $conditions = [
                'class_type' => $class,
                'agent_id' => $this->agentId
            ];
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
            
            error_log("[UniversalOntologyEngine] Reasoned over {$class} for agent: {$this->agentId}, found " . count($results) . " instances [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $results;
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error reasoning: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            $contexts = $this->reasonOver($this->prefix . 'Context', null, $studentId);
            
            // 전략 생성 로직
            $strategy = $this->buildStrategy($strategyClass, $contexts, $context);
            
            // 인스턴스로 저장
            $instanceId = $this->createInstance($strategyClass, $strategy, $studentId);
            
            // 부모 관계 설정
            if (!empty($contexts)) {
                $parentId = $contexts[0]['instance_id'];
                $this->setParentRelation($instanceId, $parentId);
            }
            
            error_log("[UniversalOntologyEngine] Generated strategy: {$instanceId} for agent: {$this->agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'strategy' => $strategy
            ];
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error generating strategy: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            $strategyInstance = $this->db->get_record('alt42_ontology_instances', [
                'instance_id' => $strategyId,
                'agent_id' => $this->agentId
            ]);
            if (!$strategyInstance) {
                throw new Exception("Strategy instance not found: {$strategyId} for agent: {$this->agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            $strategyJsonld = json_decode($strategyInstance->jsonld_data, true);
            
            // 절차 단계 생성
            $procedureSteps = $this->buildProcedureSteps($strategyJsonld);
            
            // 절차 인스턴스 생성
            $procedureData = [
                $this->prefix . 'hasProcedureSteps' => $procedureSteps
            ];
            
            $instanceId = $this->createInstance($procedureClass, $procedureData, $studentId);
            
            // 부모 관계 설정
            $this->setParentRelation($instanceId, $strategyId);
            
            error_log("[UniversalOntologyEngine] Generated procedure: {$instanceId} for agent: {$this->agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'procedure_steps' => $procedureSteps
            ];
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error generating procedure: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            $instance = $this->db->get_record('alt42_ontology_instances', [
                'instance_id' => $instanceId,
                'agent_id' => $this->agentId
            ]);
            if (!$instance) {
                return null;
            }
            
            return json_decode($instance->jsonld_data, true);
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error getting instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 에이전트 ID 가져오기
     * 
     * @return string 에이전트 ID
     */
    public function getAgentId(): string {
        return $this->agentId;
    }
    
    // === Private Helper Methods ===
    
    /**
     * 클래스에 대한 Stage 반환
     */
    private function getStageForClass(string $class): string {
        // 기본 Stage 매핑 (에이전트별로 확장 가능)
        $stageMap = [
            $this->prefix . 'OnboardingContext' => 'Context',
            $this->prefix . 'LearningContextIntegration' => 'Context',
            $this->prefix . 'FirstClassDecisionModel' => 'Decision',
            $this->prefix . 'FirstClassStrategy' => 'Decision',
            $this->prefix . 'FirstClassExecutionPlan' => 'Execution',
            $this->prefix . 'LessonProcedure' => 'Execution'
        ];
        
        return $stageMap[$class] ?? 'Context';
    }
    
    /**
     * 변수 해석
     */
    private function resolveVariable(string $varName, ?array $context): string {
        if ($context && isset($context[$varName])) {
            return $context[$varName];
        }
        
        return '';
    }
    
    /**
     * 추론 규칙 적용 (기본 구현, 에이전트별로 확장 가능)
     */
    private function applyReasoningRules(string $class, array $jsonld, ?int $studentId): array {
        // 기본 추론 로직 (에이전트별로 확장 가능)
        $result = [];
        
        // Agent01 특화 추론 로직은 여기에 추가 가능
        // 다른 에이전트는 기본 추론만 수행
        
        return $result;
    }
    
    /**
     * 전략 구축
     */
    private function buildStrategy(string $strategyClass, array $contexts, array $context): array {
        $strategy = [];
        
        // 컨텍스트에서 데이터 추출
        if (!empty($contexts)) {
            $contextData = $contexts[0]['data'];
            foreach ($contextData as $key => $value) {
                if (strpos($key, $this->prefix) === 0) {
                    $strategy[$key] = $value;
                }
            }
        }
        
        // 컨텍스트에서 추가 데이터
        foreach ($context as $key => $value) {
            if (strpos($key, $this->prefix) === 0) {
                $strategy[$key] = $value;
            }
        }
        
        return $strategy;
    }
    
    /**
     * 절차 단계 구축
     */
    private function buildProcedureSteps(array $strategyJsonld): array {
        $steps = [];
        $stepOrder = 1;
        
        // 전략에서 절차 단계 생성 (기본 구현)
        $steps[] = [
            '@type' => $this->prefix . 'ProcedureStep',
            $this->prefix . 'stepOrder' => $stepOrder++,
            $this->prefix . 'stepType' => 'general',
            $this->prefix . 'stepDescription' => '일반 절차 단계'
        ];
        
        return $steps;
    }
    
    /**
     * 부모 관계 설정
     */
    public function setParentRelation(string $childId, string $parentId): void {
        try {
            $child = $this->db->get_record('alt42_ontology_instances', [
                'instance_id' => $childId,
                'agent_id' => $this->agentId
            ]);
            if (!$child) {
                return;
            }
            
            $jsonld = json_decode($child->jsonld_data, true);
            $jsonld[$this->prefix . 'hasParent'] = $parentId;
            
            $child->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $child->parent_instance_id = $parentId;
            $child->updated_at = time();
            
            $this->db->update_record('alt42_ontology_instances', $child);
            
        } catch (Exception $e) {
            error_log("[UniversalOntologyEngine] Error setting parent relation: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
}

