<?php
/**
 * Agent04 온톨로지 엔진
 * File: agent04_inspect_weakpoints/ontology/OntologyEngine.php
 * 
 * 온톨로지 인스턴스 생성, 추론, 보강 방안 생성 기능 제공
 */

// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class OntologyEngine {
    
    private $db;
    private $namespace = 'https://mathking.kr/ontology/agent04/';
    private $prefix = 'mk-a04:';
    
    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->ensureTableExists();
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
     * @param string $class 클래스 URI (예: 'mk-a04:WeakpointDetectionContext')
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
                'mk:hasStage' => $stage
            ];
            
            // 프로퍼티 추가
            foreach ($properties as $key => $value) {
                if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                    // 변수는 나중에 setProperty로 설정
                    continue;
                }
                $jsonld[$key] = $value;
            }
            
            // DB 저장
            $record = new stdClass();
            $record->instance_id = $instanceId;
            $record->student_id = $studentId;
            $record->class_type = $class;
            $record->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE);
            $record->stage = $stage;
            $record->parent_instance_id = null;
            $record->created_at = time();
            $record->updated_at = time();
            
            $this->db->insert_record('alt42_ontology_instances', $record);
            
            error_log("[OntologyEngine] Created instance: {$instanceId} for student: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $instanceId;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error creating instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 프로퍼티 설정
     * 
     * @param string $instanceId 인스턴스 ID
     * @param string $property 프로퍼티 URI
     * @param mixed $value 값
     * @return bool 성공 여부
     */
    public function setProperty(string $instanceId, string $property, $value): bool {
        try {
            $instance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $instanceId]);
            if (!$instance) {
                throw new Exception("Instance not found: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            $jsonld = json_decode($instance->jsonld_data, true);
            if (!$jsonld) {
                throw new Exception("Invalid JSON-LD data [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            $jsonld[$property] = $value;
            
            $instance->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE);
            $instance->updated_at = time();
            
            $this->db->update_record('alt42_ontology_instances', $instance);
            
            error_log("[OntologyEngine] Set property {$property} on {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return true;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error setting property: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return false;
        }
    }
    
    /**
     * 추론 수행
     * 
     * @param string $class 클래스 URI
     * @param array|null $conditions 검색 조건
     * @param int|null $studentId 학생 ID
     * @return array 추론 결과
     */
    public function reasonOver(string $class, ?array $conditions = null, ?int $studentId = null): array {
        try {
            $conditions = $conditions ?? [];
            $conditions['class_type'] = $class;
            
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
     * 보강 방안 생성
     * 
     * @param string $decisionClass 결정 모델 클래스 URI
     * @param array $context 컨텍스트 정보
     * @param int|null $studentId 학생 ID
     * @return array 생성된 보강 방안 객체
     */
    public function generateReinforcementPlan(string $decisionClass, array $context, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            // 관련 Context 인스턴스 조회
            $weakpointContexts = $this->reasonOver('mk-a04:WeakpointDetectionContext', null, $studentId);
            $activityContexts = $this->reasonOver('mk-a04:ActivityAnalysisContext', null, $studentId);
            
            // 보강 방안 생성 로직
            $reinforcementPlan = $this->buildReinforcementPlan($decisionClass, $weakpointContexts, $activityContexts, $context);
            
            // 인스턴스로 저장
            $instanceId = $this->createInstance($decisionClass, $reinforcementPlan, $studentId);
            
            // 부모 관계 설정
            if (!empty($weakpointContexts)) {
                $parentId = $weakpointContexts[0]['instance_id'];
                $this->setParentRelation($instanceId, $parentId);
            }
            
            error_log("[OntologyEngine] Generated reinforcement plan: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'reinforcement_plan' => $reinforcementPlan
            ];
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error generating reinforcement plan: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
    
    // === Private Helper Methods ===
    
    /**
     * 클래스에 대한 Stage 반환
     */
    private function getStageForClass(string $class): string {
        $stageMap = [
            'mk-a04:WeakpointDetectionContext' => 'Context',
            'mk-a04:ActivityAnalysisContext' => 'Context',
            'mk-a04:WeakpointAnalysisDecisionModel' => 'Decision',
            'mk-a04:ReinforcementPlanExecutionPlan' => 'Execution'
        ];
        
        return $stageMap[$class] ?? 'Context';
    }
    
    /**
     * 부모 관계 설정
     */
    private function setParentRelation(string $childId, string $parentId): bool {
        try {
            $child = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $childId]);
            if (!$child) {
                return false;
            }
            
            $jsonld = json_decode($child->jsonld_data, true);
            $jsonld['mk:hasParent'] = $parentId;
            
            $child->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE);
            $child->parent_instance_id = $parentId;
            $child->updated_at = time();
            
            $this->db->update_record('alt42_ontology_instances', $child);
            
            return true;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error setting parent relation: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return false;
        }
    }
    
    /**
     * 추론 규칙 적용
     */
    private function applyReasoningRules(string $class, array $jsonld, ?int $studentId): array {
        $result = [];
        
        if ($class === 'mk-a04:ActivityAnalysisContext') {
            // 활동 분석 기반 취약점 심각도 추론
            $pauseFrequency = $jsonld['mk-a04:hasPauseFrequency'] ?? 0;
            $attentionScore = $jsonld['mk-a04:hasAttentionScore'] ?? 1.0;
            
            // 심각도 추론
            $severity = $this->inferSeverityLevel($pauseFrequency, $attentionScore);
            if ($severity) {
                $result['inferredSeverity'] = $severity;
            }
            
            // 보강 전략 추론
            $confusionDetected = $jsonld['mk-a04:hasConceptConfusionDetected'] ?? false;
            $methodMatchScore = $jsonld['mk-a04:hasMethodPersonaMatchScore'] ?? 1.0;
            $boredomDetected = $jsonld['mk-a04:hasBoredomDetected'] ?? false;
            
            $strategy = $this->inferReinforcementStrategy($confusionDetected, $methodMatchScore, $boredomDetected, $attentionScore);
            if ($strategy) {
                $result['inferredStrategy'] = $strategy;
            }
        }
        
        if ($class === 'mk-a04:WeakpointDetectionContext') {
            // 취약점 탐지 컨텍스트 기반 추론
            $activityType = $jsonld['mk-a04:hasActivityType'] ?? null;
            $severity = $jsonld['mk-a04:hasWeakpointSeverity'] ?? null;
            
            // 우선순위 추론
            if ($severity) {
                $priority = $this->inferPriorityLevel($severity);
                if ($priority) {
                    $result['inferredPriority'] = $priority;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 심각도 수준 추론
     */
    private function inferSeverityLevel(int $pauseFrequency, float $attentionScore): ?string {
        // 멈춤 빈도 기반
        if ($pauseFrequency >= 10) {
            return 'mk-a04:Critical';
        } elseif ($pauseFrequency >= 5) {
            return 'mk-a04:High';
        } elseif ($pauseFrequency >= 3) {
            return 'mk-a04:Medium';
        }
        
        // 주의집중도 기반
        if ($attentionScore <= 0.3) {
            return 'mk-a04:Critical';
        } elseif ($attentionScore <= 0.5) {
            return 'mk-a04:High';
        } elseif ($attentionScore <= 0.7) {
            return 'mk-a04:Medium';
        }
        
        return 'mk-a04:Low';
    }
    
    /**
     * 보강 전략 추론
     */
    private function inferReinforcementStrategy(bool $confusionDetected, float $methodMatchScore, bool $boredomDetected, float $attentionScore): ?string {
        if ($confusionDetected) {
            return 'mk-a04:ConceptClarificationStrategy';
        }
        
        if ($methodMatchScore < 0.7) {
            return 'mk-a04:MethodOptimizationStrategy';
        }
        
        if ($attentionScore < 0.5) {
            return 'mk-a04:AttentionRecoveryStrategy';
        }
        
        if ($boredomDetected) {
            return 'mk-a04:BoredomInterventionStrategy';
        }
        
        return 'mk-a04:CombinationOptimizationStrategy';
    }
    
    /**
     * 우선순위 수준 추론
     */
    private function inferPriorityLevel(string $severity): ?string {
        $priorityMap = [
            'mk-a04:Critical' => 'mk-a04:Urgent',
            'mk-a04:High' => 'mk-a04:High',
            'mk-a04:Medium' => 'mk-a04:Medium',
            'mk-a04:Low' => 'mk-a04:Low'
        ];
        
        return $priorityMap[$severity] ?? null;
    }
    
    /**
     * 보강 방안 구축
     */
    private function buildReinforcementPlan(string $decisionClass, array $weakpointContexts, array $activityContexts, array $context): array {
        $plan = [];
        
        // 기본 보강 방안 구성
        if (!empty($weakpointContexts)) {
            $weakpointData = $weakpointContexts[0]['data'];
            $plan['mk-a04:hasWeakpointDescription'] = $weakpointData['mk-a04:hasWeakpointPattern'] ?? '취약점 탐지됨';
            $plan['mk-a04:hasRootCause'] = $this->inferRootCause($weakpointData, $activityContexts);
        }
        
        if (!empty($activityContexts)) {
            $activityData = $activityContexts[0]['data'];
            $reasoning = $activityContexts[0]['reasoning'] ?? [];
            
            if (isset($reasoning['inferredStrategy'])) {
                $plan['mk-a04:hasReinforcementStrategy'] = $reasoning['inferredStrategy'];
            }
            
            if (isset($reasoning['inferredSeverity'])) {
                $plan['mk-a04:hasReinforcementPriority'] = $this->inferPriorityLevel($reasoning['inferredSeverity']);
            }
        }
        
        // 권장 방법 및 콘텐츠
        $plan['mk-a04:hasRecommendedMethod'] = $context['recommendedMethod'] ?? '예제 중심 학습';
        $plan['mk-a04:hasRecommendedContent'] = $context['recommendedContent'] ?? ['concept_comparison_definition_vs_example'];
        $plan['mk-a04:hasInterventionType'] = $context['interventionType'] ?? 'mk-a04:ConceptWeakPointSupport';
        $plan['mk-a04:hasFeedbackMessage'] = $context['feedbackMessage'] ?? '취약점이 탐지되었습니다. 보강이 필요합니다.';
        $plan['mk-a04:hasExpectedImpact'] = $context['expectedImpact'] ?? '학습 효율 향상 예상';
        
        return $plan;
    }
    
    /**
     * 근본 원인 추론
     */
    private function inferRootCause(array $weakpointData, array $activityContexts): string {
        $causes = [];
        
        if (!empty($activityContexts)) {
            $activityData = $activityContexts[0]['data'];
            
            if ($activityData['mk-a04:hasConceptConfusionDetected'] ?? false) {
                $confusionType = $activityData['mk-a04:hasConfusionType'] ?? [];
                if (!empty($confusionType)) {
                    $causes[] = '개념 혼동: ' . implode(', ', $confusionType);
                }
            }
            
            if (($activityData['mk-a04:hasMethodPersonaMatchScore'] ?? 1.0) < 0.7) {
                $causes[] = '학습 방법과 페르소나 불일치';
            }
            
            if (($activityData['mk-a04:hasAttentionScore'] ?? 1.0) < 0.5) {
                $causes[] = '주의집중도 저하';
            }
        }
        
        return !empty($causes) ? implode(', ', $causes) : '취약점 원인 분석 필요';
    }
}

