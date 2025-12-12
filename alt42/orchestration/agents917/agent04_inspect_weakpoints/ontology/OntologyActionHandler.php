<?php
/**
 * 온톨로지 액션 핸들러
 * File: agent04_inspect_weakpoints/ontology/OntologyActionHandler.php
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
    private $lastInstanceId;
    
    /**
     * Constructor
     * 
     * @param array $context 룰 엔진 컨텍스트
     * @param int|null $studentId 학생 ID
     */
    public function __construct(array $context = [], ?int $studentId = null) {
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
                return ['success' => false, 'error' => 'Invalid action format [File: ' . __FILE__ . ', Line: ' . __LINE__ . ']'];
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
                    
                case 'generate_reinforcement_plan':
                    return $this->handleGenerateReinforcementPlan($actionParams);
                    
                default:
                    return ['success' => false, 'error' => "Unknown action type: {$actionType} [File: " . __FILE__ . ", Line: " . __LINE__ . "]"];
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
     */
    private function parseAction($action): ?array {
        // Python 룰 엔진이 반환하는 배열 형식 처리
        if (is_array($action)) {
            // {"create_instance": "mk-a04:WeakpointDetectionContext"} 형식
            if (isset($action['create_instance'])) {
                return [
                    'type' => 'create_instance',
                    'params' => ['class' => $action['create_instance']]
                ];
            }
            
            // {"set_property": "('mk-a04:hasStudentId', '{student_id}')"} 형식
            if (isset($action['set_property'])) {
                $propertyStr = $action['set_property'];
                // 문자열에서 튜플 파싱: ('mk-a04:hasStudentId', '{student_id}')
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
            
            // {"reason_over": "mk-a04:ActivityAnalysisContext"} 형식
            if (isset($action['reason_over'])) {
                return [
                    'type' => 'reason_over',
                    'params' => ['class' => $action['reason_over']]
                ];
            }
            
            // {"generate_reinforcement_plan": "mk-a04:WeakpointAnalysisDecisionModel"} 형식
            if (isset($action['generate_reinforcement_plan'])) {
                return [
                    'type' => 'generate_reinforcement_plan',
                    'params' => ['class' => $action['generate_reinforcement_plan']]
                ];
            }
            
            // 배열을 JSON 문자열로 변환하여 문자열 파싱 시도
            $action = json_encode($action, JSON_UNESCAPED_UNICODE);
        }
        
        // 문자열인 경우
        if (!is_string($action)) {
            return null;
        }
        
        // create_instance: 'mk-a04:WeakpointDetectionContext'
        if (preg_match("/^create_instance:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
            return [
                'type' => 'create_instance',
                'params' => ['class' => $matches[1]]
            ];
        }
        
        // set_property: ('mk-a04:hasStudentId', '{studentId}')
        if (preg_match("/^set_property:\s*\(['\"](.+?)['\"],\s*['\"](.+?)['\"]\)$/", trim($action), $matches)) {
            return [
                'type' => 'set_property',
                'params' => [
                    'property' => $matches[1],
                    'value' => $matches[2]
                ]
            ];
        }
        
        // reason_over: 'mk-a04:ActivityAnalysisContext'
        if (preg_match("/^reason_over:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
            return [
                'type' => 'reason_over',
                'params' => ['class' => $matches[1]]
            ];
        }
        
        // generate_reinforcement_plan: 'mk-a04:WeakpointAnalysisDecisionModel'
        if (preg_match("/^generate_reinforcement_plan:\s*['\"](.+?)['\"]$/", trim($action), $matches)) {
            return [
                'type' => 'generate_reinforcement_plan',
                'params' => ['class' => $matches[1]]
            ];
        }
        
        return null;
    }
    
    /**
     * create_instance 액션 처리
     */
    private function handleCreateInstance(array $params): array {
        $class = $params['class'];
        
        error_log("[OntologyActionHandler] Creating instance of class: {$class} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // 컨텍스트에서 프로퍼티 추출
        $properties = $this->extractPropertiesFromContext($class);
        
        error_log("[OntologyActionHandler] Extracted properties: " . json_encode($properties, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        $instanceId = $this->ontologyEngine->createInstance($class, $properties, $this->studentId);
        $this->lastInstanceId = $instanceId;
        
        error_log("[OntologyActionHandler] Created instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        return [
            'success' => true,
            'instance_id' => $instanceId,
            'class' => $class,
            'data' => $this->ontologyEngine->getInstance($instanceId) // 인스턴스 데이터도 반환
        ];
    }
    
    /**
     * set_property 액션 처리
     */
    private function handleSetProperty(array $params): array {
        if (!$this->lastInstanceId) {
            return ['success' => false, 'error' => 'No instance created yet [File: ' . __FILE__ . ', Line: ' . __LINE__ . ']'];
        }
        
        $property = $params['property'];
        $value = $params['value'];
        
        // 변수 치환
        $resolvedValue = $this->resolveVariable($value);
        
        $success = $this->ontologyEngine->setProperty($this->lastInstanceId, $property, $resolvedValue);
        
        return [
            'success' => $success,
            'instance_id' => $this->lastInstanceId,
            'property' => $property,
            'value' => $resolvedValue
        ];
    }
    
    /**
     * reason_over 액션 처리
     */
    private function handleReasonOver(array $params): array {
        $class = $params['class'];
        
        error_log("[OntologyActionHandler] Reasoning over class: {$class} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        $results = $this->ontologyEngine->reasonOver($class, null, $this->studentId);
        
        error_log("[OntologyActionHandler] Reasoning results: " . json_encode($results, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        return [
            'success' => true,
            'class' => $class,
            'results' => $results,
            'reasoning' => !empty($results) && isset($results[0]['reasoning']) ? $results[0]['reasoning'] : [] // 추론 결과도 반환
        ];
    }
    
    /**
     * generate_reinforcement_plan 액션 처리
     */
    private function handleGenerateReinforcementPlan(array $params): array {
        $class = $params['class'];
        
        error_log("[OntologyActionHandler] Generating reinforcement plan for class: {$class} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        $result = $this->ontologyEngine->generateReinforcementPlan($class, $this->context, $this->studentId);
        
        error_log("[OntologyActionHandler] Generated reinforcement plan: " . json_encode($result, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // 인스턴스 데이터도 가져오기
        $instanceData = $this->ontologyEngine->getInstance($result['instance_id']);
        
        return [
            'success' => true,
            'instance_id' => $result['instance_id'],
            'reinforcement_plan' => $result['reinforcement_plan'],
            'data' => $instanceData // 인스턴스 데이터도 반환
        ];
    }
    
    /**
     * 컨텍스트에서 프로퍼티 추출
     */
    private function extractPropertiesFromContext(string $class): array {
        $properties = [];
        
        // 클래스별 기본 프로퍼티 설정
        if ($class === 'mk-a04:WeakpointDetectionContext') {
            $properties['mk-a04:hasStudentId'] = $this->studentId ?? 0;
            // snake_case와 camelCase 모두 지원
            $properties['mk-a04:hasActivityType'] = $this->context['activity_type'] ?? $this->context['activityType'] ?? 'mk-a04:ConceptUnderstanding';
            $properties['mk-a04:hasActivityCategory'] = $this->context['activity_category'] ?? $this->context['activityCategory'] ?? '개념이해';
            $properties['mk-a04:hasDetectionTimestamp'] = date('c');
            $properties['mk-a04:hasWeakpointSeverity'] = $this->context['weakpoint_severity'] ?? $this->context['weakpointSeverity'] ?? 'mk-a04:Medium';
        } elseif ($class === 'mk-a04:ActivityAnalysisContext') {
            $properties['mk-a04:hasActivityStage'] = $this->context['activity_stage'] ?? $this->context['activityStage'] ?? '';
            $properties['mk-a04:hasPauseFrequency'] = $this->context['pause_frequency'] ?? $this->context['pauseFrequency'] ?? 0;
            $properties['mk-a04:hasPauseStage'] = $this->context['pause_stage'] ?? $this->context['pauseStage'] ?? '';
            $properties['mk-a04:hasAttentionScore'] = $this->context['attention_score'] ?? $this->context['attentionScore'] ?? 1.0;
            $properties['mk-a04:hasGazeAttentionScore'] = $this->context['gaze_attention_score'] ?? $this->context['gazeAttentionScore'] ?? null;
            $properties['mk-a04:hasNoteTakingPatternChange'] = $this->context['note_taking_pattern_change'] ?? $this->context['noteTakingPatternChange'] ?? false;
            $properties['mk-a04:hasConceptConfusionDetected'] = $this->context['concept_confusion_detected'] ?? $this->context['conceptConfusionDetected'] ?? false;
            $properties['mk-a04:hasConfusionType'] = $this->context['confusion_type'] ?? $this->context['confusionType'] ?? null;
            $properties['mk-a04:hasCurrentMethod'] = $this->context['learning_method'] ?? $this->context['current_method'] ?? $this->context['currentMethod'] ?? null;
            $properties['mk-a04:hasMethodPersonaMatchScore'] = $this->context['method_persona_match_score'] ?? $this->context['methodPersonaMatchScore'] ?? null;
            $properties['mk-a04:hasPersonaType'] = $this->context['persona_type'] ?? $this->context['personaType'] ?? null;
            $properties['mk-a04:hasBoredomDetected'] = $this->context['boredom_detected'] ?? $this->context['boredomDetected'] ?? false;
            $properties['mk-a04:hasAttentionDropTime'] = $this->context['attention_drop_time'] ?? $this->context['attentionDropTime'] ?? null;
            $properties['mk-a04:hasEmotionState'] = $this->context['emotion_state'] ?? $this->context['emotionState'] ?? null;
        }
        
        return $properties;
    }
    
    /**
     * 변수 해석
     */
    private function resolveVariable(string $value): string {
        // {변수명} 또는 {student_id} 형식의 변수 치환
        $resolvedValue = $value;
        
        // 모든 {변수명} 패턴 찾기
        if (preg_match_all('/\{(\w+)\}/', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $varName = $match[1];
                $replacement = null;
                
                // 컨텍스트에서 변수 찾기 (snake_case와 camelCase 모두 지원)
                if (isset($this->context[$varName])) {
                    $replacement = $this->context[$varName];
                } else {
                    // camelCase 변환 시도 (student_id -> studentId)
                    $camelCase = str_replace('_', '', ucwords($varName, '_'));
                    $camelCase = lcfirst($camelCase);
                    if (isset($this->context[$camelCase])) {
                        $replacement = $this->context[$camelCase];
                    }
                }
                
                // 특수 변수 처리
                if ($replacement === null) {
                    switch ($varName) {
                        case 'student_id':
                        case 'studentId':
                            $replacement = (string)($this->studentId ?? 0);
                            break;
                        case 'timestamp':
                            $replacement = date('c');
                            break;
                        default:
                            // 변수를 찾지 못한 경우 원본 유지
                            error_log("[OntologyActionHandler] Variable '{$varName}' not found in context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                            continue 2; // 다음 매치로
                    }
                }
                
                // 변수 치환
                $resolvedValue = str_replace($match[0], $replacement, $resolvedValue);
            }
        }
        
        return $resolvedValue;
    }
}

