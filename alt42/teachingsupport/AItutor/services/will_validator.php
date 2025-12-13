<?php
/**
 * Will Layer 검증 시스템 (Will Validator)
 * 
 * Phase 4: Will Layer 검증 시스템
 * - 룰 액션 전 Will 위배 검사
 * - 온톨로지 기반 override 로직
 * - 장기 목표 vs 즉각 반응 균형
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class WillValidator {
    
    /**
     * Will Layer 우선순위 정의
     * Agent01 OIW Model 기반
     */
    private $willPriorities = [
        [
            'id' => 'WILL_1',
            'value' => '학생이 좌절하지 않도록 한다',
            'priority' => 10,
            'constraints' => [
                'difficulty_progression' => ['allowed' => ['gradual'], 'forbidden' => ['sudden_increase']],
                'emotional_state' => ['forbidden' => ['frustrated', 'anxious', 'giving_up']]
            ]
        ],
        [
            'id' => 'WILL_2',
            'value' => '핵심 개념을 확실히 이해하도록 한다',
            'priority' => 9,
            'constraints' => [
                'concept_mastery' => ['threshold' => 0.7, 'measurement' => 'understanding_level'],
                'prerequisite_check' => ['required' => true]
            ]
        ],
        [
            'id' => 'WILL_3',
            'value' => '단원 간 연결성을 이해하도록 한다',
            'priority' => 8,
            'constraints' => [
                'unit_relations' => ['required' => true],
                'concept_connections' => ['show' => true]
            ]
        ]
    ];
    
    /**
     * 제약 조건 (Constraints)
     */
    private $constraints = [
        '학부모 불신을 유발하지 않는다',
        '학원 진도와 완전히 어긋나지 않는다',
        '시험 대비를 완전히 무시하지 않는다'
    ];
    
    /**
     * 금지 액션 매핑 (감정 상태별)
     */
    private $forbiddenActions = [
        'frustrated' => [
            'forbidden' => ['SUGGEST_CHALLENGE', 'increase_difficulty'],
            'override_to' => 'INT_7_4' // 작은 성공 만들기
        ],
        'anxious' => [
            'forbidden' => ['INT_4_2', 'INT_6_1', 'timed_challenge'],
            'override_to' => 'INT_7_3' // 난이도 조정 예고
        ],
        'giving_up' => [
            'forbidden' => ['ITEM_ADVANCE', 'skip'],
            'override_to' => 'INT_7_1' // 노력 인정
        ],
        'stuck' => [
            'forbidden' => ['INT_1_3'], // 긴 사고 여백은 더 막히게 함
            'override_to' => 'INT_5_5' // 힌트 질문
        ]
    ];
    
    /**
     * 생성자
     */
    public function __construct() {
        // 추가 초기화 필요시
    }
    
    /**
     * 액션 검증 (메인 메서드)
     * 
     * @param array $proposedAction 제안된 액션
     * @param array $studentContext 학생 컨텍스트
     * @return array 검증 결과
     */
    public function validateAction($proposedAction, $studentContext) {
        $result = [
            'approved' => true,
            'original_action' => $proposedAction,
            'override_action' => null,
            'violations' => [],
            'warnings' => [],
            'applied_wills' => []
        ];
        
        // 1. Will 우선순위별 검증
        foreach ($this->willPriorities as $will) {
            $validation = $this->validateAgainstWill($proposedAction, $studentContext, $will);
            
            if (!$validation['passed']) {
                $result['approved'] = false;
                $result['violations'][] = [
                    'will_id' => $will['id'],
                    'will_value' => $will['value'],
                    'reason' => $validation['reason']
                ];
                
                if ($validation['override_action']) {
                    $result['override_action'] = $validation['override_action'];
                }
            }
            
            if ($validation['warning']) {
                $result['warnings'][] = $validation['warning'];
            }
            
            $result['applied_wills'][] = $will['id'];
        }
        
        // 2. 감정 상태 기반 검증
        $emotionValidation = $this->validateEmotionalState($proposedAction, $studentContext);
        if (!$emotionValidation['passed']) {
            $result['approved'] = false;
            $result['violations'][] = $emotionValidation['violation'];
            $result['override_action'] = $emotionValidation['override_action'];
        }
        
        // 3. 페르소나 제약 검증
        $personaValidation = $this->validatePersonaConstraints($proposedAction, $studentContext);
        if (!$personaValidation['passed']) {
            $result['warnings'][] = $personaValidation['warning'];
            // 페르소나 제약은 경고만, 금지하지 않음
        }
        
        return $result;
    }
    
    /**
     * Will에 대한 검증
     * 
     * @param array $proposedAction 제안된 액션
     * @param array $studentContext 학생 컨텍스트
     * @param array $will Will 정의
     * @return array 검증 결과
     */
    private function validateAgainstWill($proposedAction, $studentContext, $will) {
        $result = [
            'passed' => true,
            'reason' => null,
            'override_action' => null,
            'warning' => null
        ];
        
        $actionId = $proposedAction['activity_id'] ?? $proposedAction['action'] ?? null;
        $actionType = $proposedAction['action_type'] ?? null;
        
        switch ($will['id']) {
            case 'WILL_1': // 좌절 방지
                $emotionalState = $studentContext['emotional_state'] ?? 'neutral';
                $consecutiveWrong = $studentContext['consecutive_wrong'] ?? 0;
                
                // 좌절 상태에서 난이도 상승 금지
                if (in_array($emotionalState, ['frustrated', 'anxious', 'stuck'])) {
                    if ($actionType === 'increase_difficulty' || $actionId === 'SUGGEST_CHALLENGE') {
                        $result['passed'] = false;
                        $result['reason'] = "Will 1 위배: 좌절 상태에서 난이도 상승 시도";
                        $result['override_action'] = 'INT_7_4'; // 작은 성공 만들기
                    }
                }
                
                // 연속 오답 3회 이상이면 경고
                if ($consecutiveWrong >= 3) {
                    $result['warning'] = "연속 오답 {$consecutiveWrong}회: 정서 개입 권장";
                }
                break;
                
            case 'WILL_2': // 핵심 개념 이해
                $understandingLevel = $studentContext['understanding_level'] ?? 'medium';
                $prerequisitesComplete = $studentContext['prerequisites_complete'] ?? true;
                
                // 이해도 낮은데 다음으로 진행 시도
                if ($understandingLevel === 'low' || $understandingLevel === 'very_low') {
                    if ($actionId === 'STEP_ADVANCE' || $actionId === 'ITEM_ADVANCE') {
                        $result['warning'] = "Will 2 주의: 이해도 낮은 상태에서 진행";
                        // 금지하지는 않지만 경고
                    }
                }
                
                // 선행 개념 미완료
                if (!$prerequisitesComplete) {
                    $result['warning'] = "선행 개념 확인 필요";
                }
                break;
                
            case 'WILL_3': // 단원 간 연결성
                // 현재는 경고만
                break;
        }
        
        return $result;
    }
    
    /**
     * 감정 상태 기반 검증
     * 
     * @param array $proposedAction 제안된 액션
     * @param array $studentContext 학생 컨텍스트
     * @return array 검증 결과
     */
    private function validateEmotionalState($proposedAction, $studentContext) {
        $result = [
            'passed' => true,
            'violation' => null,
            'override_action' => null
        ];
        
        $emotionalState = $studentContext['emotional_state'] ?? 'neutral';
        $actionId = $proposedAction['activity_id'] ?? $proposedAction['action'] ?? null;
        
        // 감정 상태별 금지 액션 확인
        if (isset($this->forbiddenActions[$emotionalState])) {
            $forbidden = $this->forbiddenActions[$emotionalState]['forbidden'];
            
            if (in_array($actionId, $forbidden)) {
                $result['passed'] = false;
                $result['violation'] = [
                    'type' => 'emotional_state_constraint',
                    'emotional_state' => $emotionalState,
                    'forbidden_action' => $actionId,
                    'reason' => "감정 상태 '{$emotionalState}'에서 '{$actionId}' 액션 금지"
                ];
                $result['override_action'] = $this->forbiddenActions[$emotionalState]['override_to'];
            }
        }
        
        return $result;
    }
    
    /**
     * 페르소나 제약 검증
     * 
     * @param array $proposedAction 제안된 액션
     * @param array $studentContext 학생 컨텍스트
     * @return array 검증 결과
     */
    private function validatePersonaConstraints($proposedAction, $studentContext) {
        $result = [
            'passed' => true,
            'warning' => null
        ];
        
        $personaId = $studentContext['persona_id'] ?? null;
        $actionId = $proposedAction['activity_id'] ?? $proposedAction['action'] ?? null;
        
        // 페르소나별 회피 액션 (persona_rules.php에서 정의)
        $personaAvoidActions = [
            'P001' => ['INT_4_5'], // 막힘-회피형: 예고 신호 회피
            'P002' => ['INT_1_5'], // 확인요구형: 자기 수정 대기 회피
            'P003' => ['INT_6_1'], // 감정출렁형: 즉시 교정 회피
            'P004' => ['INT_2_6'], // 빠른데허술형: 요약 압축 회피
            'P005' => ['INT_3_4'], // 집중튐형: 극단적 예시 회피
            'P006' => ['INT_2_3'], // 패턴추론형: 단계 분해 회피
            'P007' => ['INT_2_1'], // 쉬운길형: 동일 반복 회피
            'P008' => ['INT_4_2'], // 불안과몰입형: 대비 강조 회피
            'P009' => ['INT_2_4'], // 추상약함형: 역순 재구성 회피
            'P010' => ['INT_1_3'], // 상호작용의존형: 긴 사고 여백 회피
            'P011' => ['INT_1_3'], // 무기력형: 긴 대기 회피
            'P012' => ['INT_2_1', 'INT_5_4'] // 메타인지고수형: 단순 반복, 이지선다 회피
        ];
        
        if ($personaId && isset($personaAvoidActions[$personaId])) {
            $avoidList = $personaAvoidActions[$personaId];
            
            if (in_array($actionId, $avoidList)) {
                $result['passed'] = false;
                $result['warning'] = "페르소나 {$personaId}에 적합하지 않은 액션: {$actionId}";
            }
        }
        
        return $result;
    }
    
    /**
     * 장기 목표와 즉각 반응의 균형 점검
     * 
     * @param array $shortTermAction 즉각 반응 액션
     * @param array $longTermGoals 장기 목표
     * @param array $studentContext 학생 컨텍스트
     * @return array 균형 분석 결과
     */
    public function balanceShortLongTerm($shortTermAction, $longTermGoals, $studentContext) {
        $result = [
            'balanced' => true,
            'short_term_priority' => false,
            'long_term_priority' => false,
            'recommendation' => null
        ];
        
        $emotionalState = $studentContext['emotional_state'] ?? 'neutral';
        $understandingLevel = $studentContext['understanding_level'] ?? 'medium';
        
        // 긴급 상황: 즉각 반응 우선
        $emergencyEmotions = ['frustrated', 'anxious', 'giving_up'];
        if (in_array($emotionalState, $emergencyEmotions)) {
            $result['short_term_priority'] = true;
            $result['recommendation'] = '정서 안정 우선: ' . $shortTermAction['activity_id'];
            return $result;
        }
        
        // 이해도 매우 낮음: 장기 목표 양보
        if ($understandingLevel === 'very_low') {
            $result['short_term_priority'] = true;
            $result['recommendation'] = '기초 보완 우선';
            return $result;
        }
        
        // 이해도 높음: 장기 목표 추구 가능
        if ($understandingLevel === 'high' || $understandingLevel === 'very_high') {
            $result['long_term_priority'] = true;
            $result['recommendation'] = '도전 및 심화 학습 가능';
            return $result;
        }
        
        // 기본: 균형 유지
        $result['recommendation'] = '현재 페이스 유지';
        return $result;
    }
    
    /**
     * 개입 활동 최종 선택
     * 
     * @param array $candidateActions 후보 액션 목록
     * @param array $studentContext 학생 컨텍스트
     * @return array 최종 선택된 액션
     */
    public function selectFinalAction($candidateActions, $studentContext) {
        $validActions = [];
        
        foreach ($candidateActions as $action) {
            $validation = $this->validateAction($action, $studentContext);
            
            if ($validation['approved']) {
                $validActions[] = [
                    'action' => $action,
                    'validation' => $validation
                ];
            } else {
                // Override 액션이 있으면 그것을 후보에 추가
                if ($validation['override_action']) {
                    $validActions[] = [
                        'action' => ['activity_id' => $validation['override_action']],
                        'validation' => $validation,
                        'is_override' => true
                    ];
                }
            }
        }
        
        if (empty($validActions)) {
            // 모든 액션이 거부되면 기본 정서 조절 액션
            return [
                'activity_id' => 'INT_7_1', // 노력 인정
                'reason' => 'Will Layer default fallback'
            ];
        }
        
        // 우선순위별 정렬 (override가 아닌 것 우선)
        usort($validActions, function($a, $b) {
            $aIsOverride = $a['is_override'] ?? false;
            $bIsOverride = $b['is_override'] ?? false;
            
            if ($aIsOverride && !$bIsOverride) return 1;
            if (!$aIsOverride && $bIsOverride) return -1;
            
            return 0;
        });
        
        return $validActions[0]['action'];
    }
    
    /**
     * Will 상태 리포트 생성
     * 
     * @param array $studentContext 학생 컨텍스트
     * @return array Will 상태 리포트
     */
    public function generateWillReport($studentContext) {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'student_id' => $studentContext['student_id'] ?? null,
            'will_status' => [],
            'overall_health' => 'good',
            'recommendations' => []
        ];
        
        // Will 1: 좌절 방지
        $emotionalState = $studentContext['emotional_state'] ?? 'neutral';
        $consecutiveWrong = $studentContext['consecutive_wrong'] ?? 0;
        
        $will1Status = 'green';
        if (in_array($emotionalState, ['frustrated', 'giving_up'])) {
            $will1Status = 'red';
            $report['recommendations'][] = '즉시 정서 개입 필요';
        } elseif (in_array($emotionalState, ['anxious', 'stuck']) || $consecutiveWrong >= 2) {
            $will1Status = 'yellow';
            $report['recommendations'][] = '정서 모니터링 강화';
        }
        $report['will_status']['WILL_1'] = $will1Status;
        
        // Will 2: 핵심 개념 이해
        $understandingLevel = $studentContext['understanding_level'] ?? 'medium';
        
        $will2Status = 'green';
        if ($understandingLevel === 'very_low') {
            $will2Status = 'red';
            $report['recommendations'][] = '기초 개념 재학습 필요';
        } elseif ($understandingLevel === 'low') {
            $will2Status = 'yellow';
            $report['recommendations'][] = '추가 설명 및 연습 권장';
        }
        $report['will_status']['WILL_2'] = $will2Status;
        
        // Will 3: 연결성 이해
        $report['will_status']['WILL_3'] = 'green'; // 기본값
        
        // 전체 상태 결정
        if (in_array('red', $report['will_status'])) {
            $report['overall_health'] = 'critical';
        } elseif (in_array('yellow', $report['will_status'])) {
            $report['overall_health'] = 'warning';
        }
        
        return $report;
    }
}

