<?php
/**
 * 범용 온톨로지 액션 핸들러
 * File: agent22_module_improvement/ontology/OntologyActionHandler.php
 * 
 * 모든 에이전트가 공통으로 사용할 수 있는 온톨로지 액션 핸들러
 * Agent01의 OntologyActionHandler를 기반으로 범용화
 */

// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/UniversalOntologyEngine.php');

class OntologyActionHandler {
    
    private $ontologyEngine;
    private $context;
    private $studentId;
    private $agentId;
    
    /**
     * Constructor
     * 
     * @param string $agentId 에이전트 ID
     * @param array $context 룰 엔진 컨텍스트
     * @param int|null $studentId 학생 ID
     */
    public function __construct(string $agentId, array $context = [], ?int $studentId = null) {
        $this->agentId = $agentId;
        $this->ontologyEngine = new UniversalOntologyEngine($agentId);
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
            error_log("[OntologyActionHandler] Error executing action for agent {$this->agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
        // 배열인 경우
        if (is_array($action)) {
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
        
        // 컨텍스트에서 프로퍼티 추출
        $properties = $this->extractPropertiesFromContext($class);
        
        $instanceId = $this->ontologyEngine->createInstance($class, $properties, $this->studentId);
        
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
        
        // 마지막으로 생성된 인스턴스 ID 가져오기
        $instanceId = $this->getLastCreatedInstanceId();
        if (!$instanceId) {
            return ['success' => false, 'error' => 'No instance found to set property'];
        }
        
        $this->ontologyEngine->setProperty($instanceId, $property, $value, $this->context);
        
        return [
            'success' => true,
            'instance_id' => $instanceId,
            'property' => $property,
            'value' => $value
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
        
        // 기본 매핑 (에이전트별로 확장 가능)
        // Agent01 특화 매핑은 여기에 유지
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
                 WHERE student_id = ? AND agent_id = ?
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$this->studentId, $this->agentId]
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
                 WHERE student_id = ? AND agent_id = ?
                 AND (class_type LIKE '%Strategy%' OR class_type LIKE '%DecisionModel%')
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$this->studentId, $this->agentId]
            );
            
            return $record ? $record->instance_id : null;
            
        } catch (Exception $e) {
            error_log("[OntologyActionHandler] Error getting last strategy instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 컨텍스트 설정
     */
    public function setContext(array $context): void {
        $this->context = array_merge($this->context, $context);
    }
}

