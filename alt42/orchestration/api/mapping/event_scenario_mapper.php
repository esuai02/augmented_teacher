<?php
/**
 * Event-Scenario Mapper
 * 이벤트 타입을 시나리오 그룹으로 매핑
 * 하이브리드 룰 체크 시스템의 핵심 컴포넌트
 * 
 * @package ALT42\Mapping
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

namespace ALT42\Mapping;

class EventScenarioMapper {
    private $eventScenarioMap;
    private $scenarioRuleGroups;
    
    public function __construct() {
        $this->initializeMappings();
    }
    
    /**
     * Initialize event-scenario mappings
     * 이벤트 타입 → 시나리오 매핑 및 시나리오 → 룰 그룹 매핑 초기화
     */
    private function initializeMappings() {
        // 이벤트 타입 → 시나리오 매핑
        $this->eventScenarioMap = [
            // 학습 관련 이벤트
            'learning.answer_wrong' => [
                'scenarios' => ['S1', 'S2'], // 이탈 관련 시나리오, 학습 관리
                'priority' => 6,
                'evaluation_mode' => 'priority_first', // 첫 매칭 룰 선택
                'description' => '오답 발생 시 이탈 및 학습 관리 시나리오 평가',
                'workflow_group' => 'learning_activity', // 워크플로우 그룹
                'affected_fields' => ['answer_count', 'wrong_count', 'last_activity'] // 영향받는 필드
            ],
            'learning.answer_correct' => [
                'scenarios' => ['S2'], // 학습 관리 (긍정적 피드백)
                'priority' => 7,
                'evaluation_mode' => 'priority_first',
                'description' => '정답 시 학습 관리 시나리오 평가',
                'workflow_group' => 'learning_activity',
                'affected_fields' => ['answer_count', 'correct_count', 'last_activity']
            ],
            'learning.problem_submitted' => [
                'scenarios' => ['S1', 'S2'],
                'priority' => 5,
                'evaluation_mode' => 'priority_first',
                'description' => '문제 제출 시 이탈 및 학습 관리 시나리오 평가',
                'workflow_group' => 'learning_activity',
                'affected_fields' => ['problem_count', 'last_activity', 'session_duration']
            ],
            
            // 생체 신호 이벤트
            'bio.stress_spike' => [
                'scenarios' => ['S3', 'S4'], // 스트레스 관리, 감정 관리
                'priority' => 4,
                'evaluation_mode' => 'priority_first',
                'description' => '스트레스 급증 시 스트레스 및 감정 관리 시나리오 평가',
                'workflow_group' => 'bio_feedback',
                'affected_fields' => ['stress_level', 'emotion_state', 'bio_state']
            ],
            'bio.concentration_drop' => [
                'scenarios' => ['S1', 'S3'], // 이탈, 스트레스 관리
                'priority' => 5,
                'evaluation_mode' => 'priority_first',
                'description' => '집중도 하락 시 이탈 및 스트레스 관리 시나리오 평가',
                'workflow_group' => 'bio_feedback',
                'affected_fields' => ['concentration_level', 'bio_state', 'activity_state']
            ],
            
            // 주기적 이벤트
            'cron.heartbeat_30m' => [
                'scenarios' => ['S0', 'S1', 'S2', 'S3', 'S4'], // 전체 시나리오 평가
                'priority' => 2,
                'evaluation_mode' => 'all_matching', // 모든 매칭 룰 실행
                'description' => '30분 주기 전체 시나리오 재평가'
            ],
            'cron.daily_analysis' => [
                'scenarios' => ['S0', 'S1', 'S2', 'S3', 'S4'],
                'priority' => 1,
                'evaluation_mode' => 'all_matching',
                'description' => '일일 분석 전체 시나리오 평가'
            ],
            
            // 시스템 이벤트
            'system.new_student' => [
                'scenarios' => ['S0'], // 온보딩 시나리오
                'priority' => 2,
                'evaluation_mode' => 'priority_first',
                'description' => '신규 학생 등록 시 온보딩 시나리오 평가'
            ],
            'system.error' => [
                'scenarios' => [], // 에러는 별도 처리
                'priority' => 1,
                'evaluation_mode' => 'priority_first',
                'description' => '시스템 에러는 별도 처리'
            ],
            
            // 교사 개입 이벤트
            'teacher.manual_intervention' => [
                'scenarios' => ['S2', 'S4'], // 학습 관리, 감정 관리
                'priority' => 3,
                'evaluation_mode' => 'priority_first',
                'description' => '교사 수동 개입 시 학습 및 감정 관리 시나리오 평가'
            ],
            
            // 상태 변화 이벤트 (State Change Detector에서 발행)
            'state.change_detected' => [
                'scenarios' => ['S1', 'S3', 'S4'], // 이탈, 스트레스, 감정 관리
                'priority' => 5,
                'evaluation_mode' => 'priority_first',
                'description' => '상태 변화 감지 시 관련 시나리오 평가',
                'workflow_group' => 'state_change',
                'affected_fields' => [] // 동적으로 결정됨
            ],
            
            // 에이전트 응답 이벤트
            'system.agent_response' => [
                'scenarios' => [], // 에이전트 응답은 별도 처리
                'priority' => 9,
                'evaluation_mode' => 'priority_first',
                'description' => '에이전트 응답은 별도 처리'
            ]
        ];
        
        // 시나리오 → 룰 그룹 매핑
        // 실제 룰 ID는 ktm_rule_book.md 또는 rules.yaml에서 가져옴
        $this->scenarioRuleGroups = [
            'S0' => [
                'rules' => [
                    'S0_R1_math_learning_style_collection',
                    'S0_R2_academy_info_collection',
                    'S0_R3_math_performance_quantification',
                    'S0_R4_textbook_info_collection',
                    'S0_R5_math_unit_mastery_collection',
                    'S0_R6_comprehensive_math_profile_verification'
                ],
                'description' => '수학학원 시스템 특화 필수 정보 수집',
                'evaluation_mode' => 'priority_first',
                'fallback' => 'S0_default'
            ],
            'S1' => [
                'rules' => [
                    'S1R1_emotion_based_reentry',
                    'S1R2_low_immersion_reentry',
                    'S1R3_emotion_based_reentry'
                ],
                'description' => '이탈 관련 시나리오 (재진입 유도)',
                'evaluation_mode' => 'priority_first',
                'fallback' => 'S1_default'
            ],
            'S2' => [
                'rules' => [
                    'S2R1_learning_management',
                    'S2R2_progress_tracking'
                ],
                'description' => '학습 관리 시나리오',
                'evaluation_mode' => 'all_matching',
                'fallback' => 'S2_default'
            ],
            'S3' => [
                'rules' => [
                    'S3R1_stress_management',
                    'S3R2_stress_intervention',
                    'S3R3_stress_recovery'
                ],
                'description' => '스트레스 관리 시나리오',
                'evaluation_mode' => 'priority_first',
                'fallback' => 'S3_default'
            ],
            'S4' => [
                'rules' => [
                    'S4R1_emotion_management',
                    'S4R2_confidence_boost'
                ],
                'description' => '감정 관리 시나리오',
                'evaluation_mode' => 'priority_first',
                'fallback' => 'S4_default'
            ]
        ];
    }
    
    /**
     * Get scenarios for event type
     * 
     * @param string $eventType Event type (e.g., 'learning.answer_wrong')
     * @return array Scenario configuration with scenarios, priority, evaluation_mode
     */
    public function getScenariosForEvent($eventType) {
        if (!isset($this->eventScenarioMap[$eventType])) {
            error_log("Unknown event type: {$eventType} at " . __FILE__ . ":" . __LINE__);
            return array(
                'scenarios' => array(),
                'priority' => 3,
                'evaluation_mode' => 'priority_first',
                'description' => 'Unknown event type'
            );
        }
        
        return $this->eventScenarioMap[$eventType];
    }
    
    /**
     * Get rules for scenario
     * 
     * @param string $scenarioId Scenario ID (e.g., 'S1')
     * @return array Rule group configuration with rules, evaluation_mode, fallback
     */
    public function getRulesForScenario($scenarioId) {
        if (!isset($this->scenarioRuleGroups[$scenarioId])) {
            error_log("Unknown scenario: {$scenarioId} at " . __FILE__ . ":" . __LINE__);
            return array(
                'rules' => array(),
                'evaluation_mode' => 'priority_first',
                'description' => 'Unknown scenario'
            );
        }
        
        return $this->scenarioRuleGroups[$scenarioId];
    }
    
    /**
     * Map event to scenarios and return rule IDs
     * 이벤트 타입을 받아서 관련된 모든 룰 ID 리스트 반환
     * 
     * @param string $eventType Event type
     * @return array Rule IDs to evaluate
     */
    public function mapEventToRules($eventType) {
        $eventConfig = $this->getScenariosForEvent($eventType);
        $ruleIds = array();
        
        foreach ($eventConfig['scenarios'] as $scenarioId) {
            $scenarioConfig = $this->getRulesForScenario($scenarioId);
            $ruleIds = array_merge($ruleIds, $scenarioConfig['rules']);
        }
        
        return array_unique($ruleIds);
    }
    
    /**
     * Get all scenarios for an event type with full configuration
     * 
     * @param string $eventType Event type
     * @return array Full scenario configurations with rules
     */
    public function getFullScenarioConfig($eventType) {
        $eventConfig = $this->getScenariosForEvent($eventType);
        $scenarios = array();
        
        foreach ($eventConfig['scenarios'] as $scenarioId) {
            $scenarioConfig = $this->getRulesForScenario($scenarioId);
            $scenarios[$scenarioId] = $scenarioConfig;
        }
        
        return array(
            'event_type' => $eventType,
            'event_config' => $eventConfig,
            'scenarios' => $scenarios
        );
    }
    
    /**
     * Check if event type is supported
     * 
     * @param string $eventType Event type
     * @return bool True if supported
     */
    public function isEventTypeSupported($eventType) {
        return isset($this->eventScenarioMap[$eventType]);
    }
    
    /**
     * Get all supported event types
     * 
     * @return array List of supported event types
     */
    public function getSupportedEventTypes() {
        return array_keys($this->eventScenarioMap);
    }
    
    /**
     * Get all scenario IDs
     * 
     * @return array List of scenario IDs
     */
    public function getAllScenarioIds() {
        return array_keys($this->scenarioRuleGroups);
    }
}

