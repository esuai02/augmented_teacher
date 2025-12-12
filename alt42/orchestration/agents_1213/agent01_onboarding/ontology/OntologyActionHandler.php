<?php
/**
 * 온톨로지 액션 핸들러
 * File: agent01_onboarding/ontology/OntologyActionHandler.php
 * 
 * 룰 엔진의 온톨로지 액션을 처리하는 핸들러
 */

// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/OntologyEngine.php');

class OntologyActionHandler {
    
    private $ontologyEngine;
    private $context;
    private $studentId;
    
    /**
     * Constructor
     * 
     * @param string|null $agentId 에이전트 ID (선택적, 호환성을 위해)
     * @param array $context 룰 엔진 컨텍스트
     * @param int|null $studentId 학생 ID
     */
    public function __construct($agentId = null, array $context = [], ?int $studentId = null) {
        // 첫 번째 파라미터가 배열이면 (이전 버전 호환) context로 처리
        if (is_array($agentId)) {
            $studentId = $context;
            $context = $agentId;
            $agentId = null;
        }
        
        $this->ontologyEngine = new OntologyEngine();
        $this->context = $context;
        
        if ($studentId === null) {
            global $USER;
            $this->studentId = $USER->id ?? null;
        } else {
            $this->studentId = $studentId;
        }
    }
    
    /**
     * 액션 실행
     * 
     * @param string|array $action 액션 문자열 또는 배열
     * @return array 실행 결과
     */
    public function executeAction($action): array {
        try {
            // 액션 파싱
            $parsedAction = $this->parseAction($action);
            
            if (!$parsedAction) {
                return ['success' => false, 'error' => 'Invalid action format'];
            }
            
            $actionType = $parsedAction['type'];
            $actionParams = $parsedAction['params'];
            
            // 액션 타입별 처리
            switch ($actionType) {
                case 'create_instance':
                    return $this->handleCreateInstance($actionParams);
                    
                case 'set_property':
                    return $this->handleSetProperty($actionParams);
                    
                case 'reason_over':
                    return $this->handleReasonOver($actionParams);
                    
                case 'generate_strategy':
                    return $this->handleGenerateStrategy($actionParams);
                    
                case 'generate_procedure':
                    return $this->handleGenerateProcedure($actionParams);
                    
                default:
                    return ['success' => false, 'error' => "Unknown action type: {$actionType}"];
            }
            
        } catch (Exception $e) {
            error_log("[OntologyActionHandler] Error executing action: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ];
        }
    }
    
    /**
     * 액션 파싱
     * 
     * @param string|array $action 액션 문자열 또는 배열
     * @return array|null 파싱된 액션
     */
    private function parseAction($action): ?array {
        // 배열인 경우 직접 처리 (Python 엔진이 반환하는 형식)
        if (is_array($action)) {
            // {"create_instance": "mk:OnboardingContext"} 형식
            if (isset($action['create_instance'])) {
                return [
                    'type' => 'create_instance',
                    'params' => ['class' => $action['create_instance']]
                ];
            }
            
            // {"set_property": "('mk:hasStudentGrade', '{gradeLevel}')"} 형식
            if (isset($action['set_property'])) {
                $propertyStr = $action['set_property'];
                // 문자열에서 튜플 파싱: ('mk:hasStudentGrade', '{gradeLevel}')
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
            
            // {"reason_over": "mk:LearningContextIntegration"} 형식
            if (isset($action['reason_over'])) {
                return [
                    'type' => 'reason_over',
                    'params' => ['class' => $action['reason_over']]
                ];
            }
            
            // {"generate_strategy": "mk:FirstClassStrategy"} 형식
            if (isset($action['generate_strategy'])) {
                return [
                    'type' => 'generate_strategy',
                    'params' => ['class' => $action['generate_strategy']]
                ];
            }
            
            // {"generate_procedure": "mk:LessonProcedure"} 형식
            if (isset($action['generate_procedure'])) {
                return [
                    'type' => 'generate_procedure',
                    'params' => ['class' => $action['generate_procedure']]
                ];
            }
            
            // 배열을 JSON 문자열로 변환하여 문자열 파싱 시도
            $action = json_encode($action);
        }
        
        // 문자열인 경우
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
    
    /**
     * create_instance 액션 처리
     */
    private function handleCreateInstance(array $params): array {
        $class = $params['class'] ?? null;
        if (!$class) {
            return ['success' => false, 'error' => 'Missing class parameter'];
        }
        
        // 컨텍스트에서 프로퍼티 추출 (자동 매핑)
        $properties = $this->extractPropertiesFromContext($class);
        
        error_log("[OntologyActionHandler] Creating instance {$class} with " . count($properties) . " properties from context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // 컨텍스트를 전달하여 변수 치환 수행
        $instanceId = $this->ontologyEngine->createInstance($class, $properties, $this->studentId, $this->context);
        
        // LearningContextIntegration은 OnboardingContext를 부모로 가져야 함
        if ($class === 'mk:LearningContextIntegration') {
            $parentId = $this->getLastCreatedOnboardingContextId();
            if ($parentId) {
                $this->ontologyEngine->setParentRelation($instanceId, $parentId);
            }
        }
        
        return [
            'success' => true,
            'instance_id' => $instanceId,
            'class' => $class
        ];
    }
    
    /**
     * set_property 액션 처리
     */
    private function handleSetProperty(array $params): array {
        $property = $params['property'] ?? null;
        $value = $params['value'] ?? null;
        
        if (!$property || $value === null) {
            return ['success' => false, 'error' => 'Missing property or value'];
        }
        
        // 마지막으로 생성된 인스턴스 ID 가져오기 (간단한 구현)
        $instanceId = $this->getLastCreatedInstanceId();
        if (!$instanceId) {
            return ['success' => false, 'error' => 'No instance found to set property'];
        }
        
        // 컨텍스트를 전달하여 변수 치환 수행
        error_log("[OntologyActionHandler] Setting property {$property} = {$value} on instance {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        $this->ontologyEngine->setProperty($instanceId, $property, $value, $this->context);
        
        // 실제로 설정된 값을 확인하기 위해 인스턴스를 다시 읽어옴
        $instance = $this->ontologyEngine->getInstance($instanceId);
        $actualValue = $instance[$property] ?? $value;
        
        return [
            'success' => true,
            'instance_id' => $instanceId,
            'property' => $property,
            'value' => $actualValue, // 실제 설정된 값 반환
            'original_value' => $value // 원본 값도 포함
        ];
    }
    
    /**
     * reason_over 액션 처리
     */
    private function handleReasonOver(array $params): array {
        $class = $params['class'] ?? null;
        if (!$class) {
            return ['success' => false, 'error' => 'Missing class parameter'];
        }
        
        $results = $this->ontologyEngine->reasonOver($class, null, $this->studentId);
        
        return [
            'success' => true,
            'class' => $class,
            'results' => $results
        ];
    }
    
    /**
     * generate_strategy 액션 처리
     */
    private function handleGenerateStrategy(array $params): array {
        $class = $params['class'] ?? null;
        if (!$class) {
            return ['success' => false, 'error' => 'Missing class parameter'];
        }
        
        $result = $this->ontologyEngine->generateStrategy($class, $this->context, $this->studentId);
        
        return [
            'success' => true,
            'strategy' => $result
        ];
    }
    
    /**
     * generate_procedure 액션 처리
     */
    private function handleGenerateProcedure(array $params): array {
        $class = $params['class'] ?? null;
        if (!$class) {
            return ['success' => false, 'error' => 'Missing class parameter'];
        }
        
        // 마지막으로 생성된 전략 인스턴스 ID 가져오기
        $strategyId = $this->getLastCreatedStrategyInstanceId();
        if (!$strategyId) {
            return ['success' => false, 'error' => 'No strategy instance found'];
        }
        
        $result = $this->ontologyEngine->generateProcedure($class, $strategyId, $this->studentId);
        
        return [
            'success' => true,
            'procedure' => $result
        ];
    }
    
    /**
     * 컨텍스트에서 프로퍼티 추출
     */
    private function extractPropertiesFromContext(string $class): array {
        $properties = [];
        
        // 클래스별 매핑
        $classPropertyMap = [
            'mk:OnboardingContext' => [
                'mk:hasStudentGrade' => 'gradeLevel',
                'mk:hasSchool' => 'schoolName',
                'mk:hasAcademy' => 'academyName',
                'mk:hasAcademyGrade' => 'academyGrade',
                'mk:hasMathConfidence' => 'math_confidence',
                'mk:hasMathLevel' => 'math_level',
                'mk:hasMathStressLevel' => 'math_stress_level',
                'mk:hasMathLearningStyle' => 'math_learning_style',
                'mk:hasStudyStyle' => 'study_style',
                'mk:hasExamStyle' => 'exam_style'
            ],
            'mk:LearningContextIntegration' => [
                'mk:hasConceptProgress' => 'concept_progress',
                'mk:hasAdvancedProgress' => 'advanced_progress',
                'mk:hasUnitMastery' => 'math_unit_mastery',
                'mk:hasCurrentPosition' => 'current_progress_position',
                'mk:hasAcademyProgress' => 'academy_progress',
                'mk:hasCurriculumAlignment' => 'curriculum_alignment',
                'mk:hasAcademySchoolHomeAlignment' => 'academy_school_home_alignment'
            ]
        ];
        
        $mapping = $classPropertyMap[$class] ?? [];
        foreach ($mapping as $property => $contextKey) {
            if (isset($this->context[$contextKey])) {
                $properties[$property] = $this->context[$contextKey];
            }
        }
        
        return $properties;
    }
    
    /**
     * 마지막으로 생성된 인스턴스 ID 가져오기
     */
    private function getLastCreatedInstanceId(): ?string {
        global $DB;
        
        try {
            $record = $DB->get_record_sql(
                "SELECT instance_id FROM {alt42_ontology_instances} 
                 WHERE student_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$this->studentId]
            );
            
            return $record ? $record->instance_id : null;
            
        } catch (Exception $e) {
            error_log("[OntologyActionHandler] Error getting last instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 마지막으로 생성된 전략 인스턴스 ID 가져오기
     */
    private function getLastCreatedStrategyInstanceId(): ?string {
        global $DB;
        
        try {
            $record = $DB->get_record_sql(
                "SELECT instance_id FROM {alt42_ontology_instances} 
                 WHERE student_id = ? 
                 AND (class_type = 'mk:FirstClassStrategy' OR class_type = 'mk:FirstClassDecisionModel')
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$this->studentId]
            );
            
            return $record ? $record->instance_id : null;
            
        } catch (Exception $e) {
            error_log("[OntologyActionHandler] Error getting last strategy instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 마지막으로 생성된 OnboardingContext 인스턴스 ID 가져오기
     */
    private function getLastCreatedOnboardingContextId(): ?string {
        global $DB;
        
        try {
            $record = $DB->get_record_sql(
                "SELECT instance_id FROM {alt42_ontology_instances} 
                 WHERE student_id = ? 
                 AND class_type = 'mk:OnboardingContext'
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$this->studentId]
            );
            
            return $record ? $record->instance_id : null;
            
        } catch (Exception $e) {
            error_log("[OntologyActionHandler] Error getting last OnboardingContext instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 컨텍스트 설정
     */
    public function setContext(array $context): void {
        $this->context = array_merge($this->context, $context);
    }
    
    // ========== Q1 파이프라인 연동 메서드 ==========
    
    /**
     * Q1 첫수업 전략 전체 파이프라인 실행
     * 
     * 스키마 → 추론 → 응답 파이프라인을 한 번에 실행
     * 
     * @return array 파이프라인 실행 결과
     */
    public function executeQ1Pipeline(): array {
        return $this->ontologyEngine->executeQ1Pipeline($this->studentId, $this->context);
    }
    
    /**
     * Q2 커리큘럼과 루틴 최적화 파이프라인 실행
     */
    public function executeQ2Pipeline(): array {
        return $this->ontologyEngine->executeQ2Pipeline($this->studentId, $this->context);
    }
    
    /**
     * Q3 중장기 성장 전략 파이프라인 실행
     */
    public function executeQ3Pipeline(): array {
        return $this->ontologyEngine->executeQ3Pipeline($this->studentId, $this->context);
    }
     
    /**
     * OntologyEngine 인스턴스 반환 (진단용)
     */
    public function getOntologyEngine(): OntologyEngine {
        return $this->ontologyEngine;
    }
    
    /**
     * 핸들러 진단 정보 반환
     */
    public function getDiagnostics(): array {
        return [
            'student_id' => $this->studentId,
            'context_keys' => array_keys($this->context),
            'context_values_preview' => array_map(function($v) {
                if (is_string($v) && strlen($v) > 50) {
                    return substr($v, 0, 50) . '...';
                }
                return $v;
            }, $this->context),
            'engine_diagnostics' => $this->ontologyEngine->getDiagnostics()
        ];
    }
    
    /**
     * 여러 액션을 순차 실행
     * 
     * @param array $actions 액션 배열
     * @return array 실행 결과 배열
     */
    public function executeActions(array $actions): array {
        $results = [];
        $hasError = false;
        
        foreach ($actions as $index => $action) {
            $result = $this->executeAction($action);
            $results[] = [
                'index' => $index,
                'action' => $action,
                'result' => $result
            ];
            
            if (!$result['success']) {
                $hasError = true;
                error_log("[OntologyActionHandler] Action {$index} failed: " . ($result['error'] ?? 'Unknown error'));
            }
        }
        
        return [
            'success' => !$hasError,
            'total_actions' => count($actions),
            'results' => $results,
            'validation_log' => $this->ontologyEngine->getValidationLog()
        ];
    }
}
