<?php
/**
 * AI 튜터 상호작용 엔진
 * 
 * 모든 상호작용의 중앙 처리 엔진
 * - 룰 평가 및 실행
 * - 페르소나 감지 및 적용
 * - 채팅 메시지 생성
 * - 온톨로지 활용
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class InteractionEngine {
    private $db;
    private $studentId;
    private $contentId;
    
    private $rules = [];
    private $personaMapping = [];
    private $problemOntology = [];
    
    private $currentContext = [];
    private $detectedPersona = null;
    private $sessionState = [];
    
    // 에러 위치 출력용
    private $errorFile = __FILE__;
    
    /**
     * 생성자
     */
    public function __construct($studentId, $contentId) {
        global $DB;
        $this->db = $DB;
        $this->studentId = $studentId;
        $this->contentId = $contentId;
        
        $this->loadRules();
        $this->loadPersonaMapping();
        $this->loadOntology();
        $this->loadSessionState();
    }
    
    /**
     * 룰 로드
     */
    private function loadRules() {
        $rulesPath = dirname(__DIR__) . '/rules/complete_rules.php';
        if (file_exists($rulesPath)) {
            $this->rules = include($rulesPath);
        } else {
            error_log("[{$this->errorFile}:Line" . __LINE__ . "] 룰 파일 없음: {$rulesPath}");
        }
    }
    
    /**
     * 페르소나 매핑 로드
     */
    private function loadPersonaMapping() {
        $mappingPath = dirname(__DIR__) . '/ontology/persona_situation_mapping.php';
        if (file_exists($mappingPath)) {
            $this->personaMapping = include($mappingPath);
        } else {
            error_log("[{$this->errorFile}:Line" . __LINE__ . "] 페르소나 매핑 파일 없음: {$mappingPath}");
        }
    }
    
    /**
     * 온톨로지 로드
     */
    private function loadOntology() {
        $ontologyPath = dirname(__DIR__) . '/ontology/problem_ontology.php';
        if (file_exists($ontologyPath)) {
            $this->problemOntology = include($ontologyPath);
        }
    }
    
    /**
     * 세션 상태 로드
     */
    private function loadSessionState() {
        try {
            // 테이블 존재 여부 확인
            $dbman = $this->db->get_manager();
            if (!$dbman->table_exists('alt42_sessions')) {
                error_log("[{$this->errorFile}:Line" . __LINE__ . "] alt42_sessions 테이블 없음 - 기본 상태 사용");
                $this->sessionState = ['session_id' => 0, 'current_step' => 1, 'progress_percent' => 0, 'status' => 'active'];
                return;
            }
            
            $session = $this->db->get_record_sql(
                "SELECT * FROM {alt42_sessions} 
                 WHERE student_id = ? AND content_id = ? 
                 AND status IN ('active', 'paused')
                 ORDER BY created_at DESC LIMIT 1",
                [$this->studentId, $this->contentId]
            );
            
            if ($session) {
                $this->sessionState = [
                    'session_id' => $session->id,
                    'current_step' => $session->current_step ?? 1,
                    'progress_percent' => $session->progress_percent ?? 0,
                    'detected_persona' => $session->detected_persona ?? null,
                    'status' => $session->status ?? 'active'
                ];
            } else {
                $this->sessionState = $this->createNewSession();
            }
        } catch (Exception $e) {
            error_log("[{$this->errorFile}:Line" . __LINE__ . "] 세션 로드 실패: " . $e->getMessage());
            $this->sessionState = ['session_id' => 0, 'current_step' => 1, 'progress_percent' => 0, 'status' => 'active'];
        }
    }
    
    /**
     * 새 세션 생성
     */
    private function createNewSession() {
        try {
            // 테이블 존재 여부 확인
            $dbman = $this->db->get_manager();
            if (!$dbman->table_exists('alt42_sessions')) {
                return ['session_id' => 0, 'current_step' => 1, 'progress_percent' => 0, 'status' => 'active'];
            }
            
            $sessionId = $this->db->insert_record('alt42_sessions', [
                'student_id' => $this->studentId,
                'content_id' => $this->contentId,
                'current_step' => 1,
                'progress_percent' => 0,
                'status' => 'active',
                'created_at' => time(),
                'updated_at' => time()
            ]);
            
            return [
                'session_id' => $sessionId,
                'current_step' => 1,
                'progress_percent' => 0,
                'status' => 'active'
            ];
        } catch (Exception $e) {
            error_log("[{$this->errorFile}:Line" . __LINE__ . "] 세션 생성 실패: " . $e->getMessage());
            return ['session_id' => 0, 'current_step' => 1, 'progress_percent' => 0, 'status' => 'active'];
        }
    }
    
    /**
     * 이벤트 처리 (메인 진입점)
     */
    public function processEvent($eventData) {
        // 컨텍스트 업데이트
        $this->updateContext($eventData);
        
        // 페르소나 감지
        $this->detectPersona($eventData);
        
        // 룰 평가 및 매칭
        $matchedRule = $this->evaluateRules($eventData);
        
        if (!$matchedRule) {
            return $this->getDefaultResponse();
        }
        
        // 액션 실행
        $response = $this->executeActions($matchedRule);
        
        // 로깅
        $this->logInteraction($eventData, $matchedRule, $response);
        
        return $response;
    }
    
    /**
     * 컨텍스트 업데이트
     */
    private function updateContext($eventData) {
        $this->currentContext = array_merge($this->currentContext, [
            'student_id' => $this->studentId,
            'content_id' => $this->contentId,
            'session_id' => $this->sessionState['session_id'],
            'current_step' => $this->sessionState['current_step'],
            'timestamp' => time()
        ], $eventData);
    }
    
    /**
     * 페르소나 감지
     */
    private function detectPersona($eventData) {
        if (empty($this->personaMapping['situations'])) {
            return null;
        }
        
        $scores = [];
        $detectedSituations = [];
        
        // 상황 매칭
        foreach ($this->personaMapping['situations'] as $situationId => $situation) {
            $matched = true;
            
            foreach ($situation['signals'] as $signal) {
                $field = $signal['field'];
                $eventValue = $eventData[$field] ?? null;
                
                if (isset($signal['value'])) {
                    if ($eventValue !== $signal['value']) {
                        $matched = false;
                        break;
                    }
                } elseif (isset($signal['range'])) {
                    $min = $signal['range'][0];
                    $max = $signal['range'][1];
                    
                    if ($min !== null && $eventValue < $min) {
                        $matched = false;
                        break;
                    }
                    if ($max !== null && $eventValue > $max) {
                        $matched = false;
                        break;
                    }
                }
            }
            
            if ($matched && !empty($situation['persona_scores'])) {
                $detectedSituations[] = $situationId;
                
                foreach ($situation['persona_scores'] as $personaId => $score) {
                    if (!isset($scores[$personaId])) {
                        $scores[$personaId] = 0;
                    }
                    $scores[$personaId] += $score;
                }
            }
        }
        
        // 최고 점수 페르소나 선택
        if (!empty($scores)) {
            arsort($scores);
            $topPersonaId = array_key_first($scores);
            
            $config = $this->personaMapping['detection_config'] ?? [];
            $threshold = $config['confidence_threshold'] ?? 0.6;
            
            if ($scores[$topPersonaId] >= $threshold) {
                $this->detectedPersona = $this->personaMapping['personas'][$topPersonaId] ?? null;
                
                // 세션에 페르소나 저장
                $this->updateSessionPersona($topPersonaId);
            }
        }
        
        return $this->detectedPersona;
    }
    
    /**
     * 세션 페르소나 업데이트
     */
    private function updateSessionPersona($personaId) {
        if (!empty($this->sessionState['session_id'])) {
            try {
                $dbman = $this->db->get_manager();
                if (!$dbman->table_exists('alt42_sessions')) {
                    return;
                }
                
                $this->db->execute(
                    "UPDATE {alt42_sessions} SET detected_persona = ?, updated_at = ? WHERE id = ?",
                    [$personaId, time(), $this->sessionState['session_id']]
                );
            } catch (Exception $e) {
                error_log("[{$this->errorFile}:Line" . __LINE__ . "] 페르소나 업데이트 실패: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 룰 평가
     */
    private function evaluateRules($eventData) {
        if (empty($this->rules)) {
            return null;
        }
        
        $matchedRules = [];
        
        foreach ($this->rules as $ruleId => $rule) {
            if ($this->matchConditions($rule['conditions'], $eventData)) {
                $matchedRules[] = [
                    'rule' => $rule,
                    'priority' => $rule['priority'] ?? 0,
                    'confidence' => $rule['confidence'] ?? 1.0
                ];
            }
        }
        
        if (empty($matchedRules)) {
            return null;
        }
        
        // 우선순위 정렬
        usort($matchedRules, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] - $a['priority'];
            }
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return $matchedRules[0]['rule'];
    }
    
    /**
     * 조건 매칭
     */
    private function matchConditions($conditions, $eventData) {
        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? '';
            // 'op' 또는 'operator' 둘 다 지원
            $op = $cond['op'] ?? $cond['operator'] ?? '==';
            $expectedValue = $cond['value'] ?? null;
            
            // 중첩 필드 접근
            $actualValue = $this->getNestedValue($eventData, $field);
            
            // 컨텍스트에서도 검색
            if ($actualValue === null) {
                $actualValue = $this->getNestedValue($this->currentContext, $field);
            }
            
            // 동적 값 처리
            if (is_string($expectedValue) && strpos($expectedValue, '*') !== false) {
                $expectedValue = $this->evaluateDynamicValue($expectedValue);
            }
            
            $matched = false;
            
            switch ($op) {
                case '==':
                    $matched = ($actualValue == $expectedValue);
                    break;
                case '!=':
                    $matched = ($actualValue != $expectedValue);
                    break;
                case '>':
                    $matched = ($actualValue > $expectedValue);
                    break;
                case '>=':
                    $matched = ($actualValue >= $expectedValue);
                    break;
                case '<':
                    $matched = ($actualValue < $expectedValue);
                    break;
                case '<=':
                    $matched = ($actualValue <= $expectedValue);
                    break;
                case 'in':
                    $matched = is_array($expectedValue) && in_array($actualValue, $expectedValue);
                    break;
                case 'contains':
                    $matched = is_string($actualValue) && strpos($actualValue, $expectedValue) !== false;
                    break;
            }
            
            if (!$matched) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 중첩 필드 값 가져오기
     */
    private function getNestedValue($data, $field) {
        $keys = explode('.', $field);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * 동적 값 평가
     */
    private function evaluateDynamicValue($expression) {
        // 예: "expected_time * 0.5"
        if (preg_match('/^(\w+)\s*\*\s*([\d.]+)$/', $expression, $matches)) {
            $field = $matches[1];
            $multiplier = floatval($matches[2]);
            
            $baseValue = $this->currentContext[$field] ?? 60; // 기본 60초
            return $baseValue * $multiplier;
        }
        
        return $expression;
    }
    
    /**
     * 액션 실행
     */
    private function executeActions($rule) {
        $response = [
            'rule_id' => $rule['rule_id'],
            'actions' => [],
            'chat_messages' => [],
            'options' => null,
            'system_actions' => []
        ];
        
        if (empty($rule['actions'])) {
            return $response;
        }
        
        foreach ($rule['actions'] as $action) {
            $actionType = $action['type'] ?? '';
            
            switch ($actionType) {
                case 'chat':
                    $message = $this->processMessage($action['message']);
                    $response['chat_messages'][] = [
                        'text' => $message,
                        'style' => $action['style'] ?? 'normal',
                        'delay' => $action['delay'] ?? 0
                    ];
                    break;
                    
                case 'question':
                    $options = $this->processOptions($action);
                    $response['options'] = [
                        'style' => $action['style'] ?? 'button',
                        'text' => $action['text'] ?? '',
                        'options' => $options,
                        'timeout' => $action['timeout'] ?? null,
                        'timeout_rule' => $action['timeout_rule'] ?? null
                    ];
                    break;
                    
                case 'system':
                    $response['system_actions'][] = [
                        'action' => $action['action'],
                        'params' => $action['params'] ?? []
                    ];
                    break;
                    
                case 'intervention':
                    $interventionId = $action['id'];
                    $interventionResult = $this->executeIntervention($interventionId);
                    $response['actions'][] = $interventionResult;
                    break;
                    
                case 'log':
                    $this->logEvent($action['event'], $action['data'] ?? []);
                    break;
            }
        }
        
        return $response;
    }
    
    /**
     * 메시지 처리 (변수 치환)
     */
    private function processMessage($message) {
        // 컨텍스트 변수 치환
        $message = preg_replace_callback('/\{(\w+)\}/', function($matches) {
            $key = $matches[1];
            return $this->currentContext[$key] ?? $matches[0];
        }, $message);
        
        // 페르소나 템플릿 적용
        if ($this->detectedPersona && isset($this->detectedPersona['response_templates'])) {
            // 페르소나별 어조 적용 가능
        }
        
        return $message;
    }
    
    /**
     * 옵션 처리
     */
    private function processOptions($action) {
        $options = $action['options'] ?? [];
        
        // 동적 옵션 처리
        if ($options === 'DYNAMIC_FROM_ONTOLOGY') {
            return $this->generateDynamicOptions();
        }
        
        return $options;
    }
    
    /**
     * 동적 옵션 생성 (온톨로지 기반)
     */
    private function generateDynamicOptions() {
        $options = [];
        
        // 현재 문제의 개념에서 옵션 생성
        if (!empty($this->problemOntology['concepts'])) {
            $concepts = array_slice($this->problemOntology['concepts'], 0, 3);
            foreach ($concepts as $concept) {
                $options[] = [
                    'label' => $concept['label'] ?? $concept['id'],
                    'value' => 'concept_' . $concept['id'],
                    'next_rule' => 'WP_019'
                ];
            }
        }
        
        if (empty($options)) {
            $options = [
                ['label' => '문제를 다시 읽어볼게', 'value' => 'reread'],
                ['label' => '힌트 줘', 'value' => 'hint', 'next_rule' => 'WP_012'],
                ['label' => '처음부터 설명해줘', 'value' => 'explain', 'next_rule' => 'EM_016']
            ];
        }
        
        return $options;
    }
    
    /**
     * 개입 활동 실행
     */
    private function executeIntervention($interventionId) {
        $mappingPath = dirname(__DIR__) . '/rules/intervention_mapping.php';
        if (file_exists($mappingPath)) {
            $mapping = include($mappingPath);
            
            if (isset($mapping['interventions'][$interventionId])) {
                $intervention = $mapping['interventions'][$interventionId];
                return [
                    'id' => $interventionId,
                    'name' => $intervention['name'],
                    'action' => $intervention['action'],
                    'description' => $intervention['description'] ?? ''
                ];
            }
        }
        
        return ['id' => $interventionId, 'action' => 'unknown'];
    }
    
    /**
     * 이벤트 로깅
     */
    private function logEvent($eventType, $data = []) {
        try {
            // 테이블 존재 여부 확인
            $dbman = $this->db->get_manager();
            if (!$dbman->table_exists('alt42_interaction_logs')) {
                return; // 테이블 없으면 로깅 건너뜀
            }
            
            $this->db->insert_record('alt42_interaction_logs', [
                'session_id' => $this->sessionState['session_id'] ?? 0,
                'student_id' => $this->studentId,
                'event_type' => $eventType,
                'event_data' => json_encode($data),
                'timestamp' => time()
            ]);
        } catch (Exception $e) {
            error_log("[{$this->errorFile}:Line" . __LINE__ . "] 이벤트 로깅 실패: " . $e->getMessage());
        }
    }
    
    /**
     * 상호작용 로깅
     */
    private function logInteraction($eventData, $matchedRule, $response) {
        try {
            // 테이블 존재 여부 확인
            $dbman = $this->db->get_manager();
            if (!$dbman->table_exists('alt42_interaction_logs')) {
                return; // 테이블 없으면 로깅 건너뜀
            }
            
            $this->db->insert_record('alt42_interaction_logs', [
                'session_id' => $this->sessionState['session_id'] ?? 0,
                'student_id' => $this->studentId,
                'event_type' => 'rule_executed',
                'event_data' => json_encode([
                    'input' => $eventData,
                    'matched_rule' => $matchedRule['rule_id'] ?? null,
                    'persona' => $this->detectedPersona['id'] ?? null,
                    'response_actions' => count($response['actions'] ?? [])
                ]),
                'timestamp' => time()
            ]);
        } catch (Exception $e) {
            error_log("[{$this->errorFile}:Line" . __LINE__ . "] 상호작용 로깅 실패: " . $e->getMessage());
        }
    }
    
    /**
     * 기본 응답
     */
    private function getDefaultResponse() {
        return [
            'rule_id' => 'DEFAULT',
            'chat_messages' => [
                ['text' => '계속 해봐! 잘하고 있어 👍', 'style' => 'normal']
            ],
            'options' => null,
            'system_actions' => []
        ];
    }
    
    /**
     * 현재 페르소나 가져오기
     */
    public function getCurrentPersona() {
        return $this->detectedPersona;
    }
    
    /**
     * 세션 상태 가져오기
     */
    public function getSessionState() {
        return $this->sessionState;
    }
    
    /**
     * 세션 진행률 업데이트
     */
    public function updateProgress($percent) {
        $this->sessionState['progress_percent'] = $percent;
        
        if (!empty($this->sessionState['session_id'])) {
            try {
                $dbman = $this->db->get_manager();
                if (!$dbman->table_exists('alt42_sessions')) {
                    return;
                }
                
                $this->db->execute(
                    "UPDATE {alt42_sessions} SET progress_percent = ?, updated_at = ? WHERE id = ?",
                    [$percent, time(), $this->sessionState['session_id']]
                );
            } catch (Exception $e) {
                error_log("[{$this->errorFile}:Line" . __LINE__ . "] 진행률 업데이트 실패: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 현재 단계 업데이트
     */
    public function updateStep($step) {
        $this->sessionState['current_step'] = $step;
        
        if (!empty($this->sessionState['session_id'])) {
            try {
                $dbman = $this->db->get_manager();
                if (!$dbman->table_exists('alt42_sessions')) {
                    return;
                }
                
                $this->db->execute(
                    "UPDATE {alt42_sessions} SET current_step = ?, updated_at = ? WHERE id = ?",
                    [$step, time(), $this->sessionState['session_id']]
                );
            } catch (Exception $e) {
                error_log("[{$this->errorFile}:Line" . __LINE__ . "] 단계 업데이트 실패: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 세션 종료
     */
    public function endSession($status = 'completed') {
        if (!empty($this->sessionState['session_id'])) {
            try {
                $dbman = $this->db->get_manager();
                if (!$dbman->table_exists('alt42_sessions')) {
                    return;
                }
                
                $this->db->execute(
                    "UPDATE {alt42_sessions} SET status = ?, ended_at = ?, updated_at = ? WHERE id = ?",
                    [$status, time(), time(), $this->sessionState['session_id']]
                );
            } catch (Exception $e) {
                error_log("[{$this->errorFile}:Line" . __LINE__ . "] 세션 종료 실패: " . $e->getMessage());
            }
        }
    }
}

