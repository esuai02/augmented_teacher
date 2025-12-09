<?php
/**
 * PersonaTransitionManager - 페르소나 전환 관리자
 *
 * 페르소나 간 전환 관계를 관리하고 전환 이벤트를 기록합니다.
 * Agent21 개입 실행에서 반응 유형(수용/저항/무응답/지연) 간
 * 전환을 추적하고 개입 효과를 분석합니다.
 *
 * @package AugmentedTeacher\Agent21\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class PersonaTransitionManager {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var object Moodle DB */
    private $db;

    /** @var array 전환 규칙 */
    private $transitionRules = [];

    /** @var array 반응 유형별 허용 전환 매트릭스 */
    private $responseTypeTransitions = [];

    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->initTransitionRules();
    }

    /**
     * 전환 규칙 초기화
     * Agent21: 반응 유형(A/R/N/D) 간 전환 매트릭스
     */
    private function initTransitionRules(): void {
        // ============================================
        // 반응 유형(Response Type) 간 전환 규칙
        // A: Acceptance (수용), R: Resistance (저항)
        // N: No Response (무응답), D: Delayed (지연)
        // ============================================

        $this->responseTypeTransitions = [
            // A (수용) → 다른 반응으로 전환
            'A' => [
                'allowed' => ['A', 'R', 'N', 'D'],
                'conditions' => [
                    // 수용 유지 (긍정적 지속)
                    'A' => [
                        'type' => 'continued_engagement',
                        'min_confidence' => 0.6,
                        'interpretation' => 'positive_continuation'
                    ],
                    // 수용 → 저항 (관계 악화 가능성)
                    'R' => [
                        'type' => 'resistance_emergence',
                        'min_confidence' => 0.7,  // 높은 신뢰도 필요 (신중한 판단)
                        'interpretation' => 'relationship_concern',
                        'alert_level' => 'warning'
                    ],
                    // 수용 → 무응답 (참여도 감소)
                    'N' => [
                        'type' => 'engagement_drop',
                        'min_confidence' => 0.65,
                        'interpretation' => 'disengagement_risk'
                    ],
                    // 수용 → 지연 (일정 조정)
                    'D' => [
                        'type' => 'timing_adjustment',
                        'min_confidence' => 0.6,
                        'interpretation' => 'schedule_flexibility'
                    ]
                ]
            ],

            // R (저항) → 다른 반응으로 전환
            'R' => [
                'allowed' => ['A', 'R', 'N', 'D'],
                'conditions' => [
                    // 저항 → 수용 (개입 성공!)
                    'A' => [
                        'type' => 'resistance_overcome',
                        'min_confidence' => 0.55,  // 낮은 임계값 (긍정적 변화 포착)
                        'interpretation' => 'intervention_success',
                        'celebration' => true
                    ],
                    // 저항 유지 (지속적 저항)
                    'R' => [
                        'type' => 'persistent_resistance',
                        'min_confidence' => 0.6,
                        'interpretation' => 'needs_different_approach',
                        'escalation_check' => true
                    ],
                    // 저항 → 무응답 (철수/회피)
                    'N' => [
                        'type' => 'withdrawal',
                        'min_confidence' => 0.65,
                        'interpretation' => 'avoidance_behavior',
                        'alert_level' => 'caution'
                    ],
                    // 저항 → 지연 (협상 시작)
                    'D' => [
                        'type' => 'negotiation_opening',
                        'min_confidence' => 0.6,
                        'interpretation' => 'partial_acceptance'
                    ]
                ]
            ],

            // N (무응답) → 다른 반응으로 전환
            'N' => [
                'allowed' => ['A', 'R', 'N', 'D'],
                'conditions' => [
                    // 무응답 → 수용 (재참여 성공!)
                    'A' => [
                        'type' => 'reengagement_success',
                        'min_confidence' => 0.55,
                        'interpretation' => 'engagement_recovered',
                        'celebration' => true
                    ],
                    // 무응답 → 저항 (숨은 저항 표출)
                    'R' => [
                        'type' => 'hidden_resistance_surfaced',
                        'min_confidence' => 0.65,
                        'interpretation' => 'underlying_issue_revealed'
                    ],
                    // 무응답 유지 (지속적 무반응)
                    'N' => [
                        'type' => 'continued_silence',
                        'min_confidence' => 0.6,
                        'interpretation' => 'disengagement_deepening',
                        'escalation_check' => true,
                        'parent_notify_check' => true
                    ],
                    // 무응답 → 지연 (시간 필요 표현)
                    'D' => [
                        'type' => 'time_request',
                        'min_confidence' => 0.6,
                        'interpretation' => 'processing_time_needed'
                    ]
                ]
            ],

            // D (지연) → 다른 반응으로 전환
            'D' => [
                'allowed' => ['A', 'R', 'N', 'D'],
                'conditions' => [
                    // 지연 → 수용 (약속 이행!)
                    'A' => [
                        'type' => 'commitment_fulfilled',
                        'min_confidence' => 0.55,
                        'interpretation' => 'follow_through_success',
                        'celebration' => true
                    ],
                    // 지연 → 저항 (약속 철회)
                    'R' => [
                        'type' => 'commitment_withdrawal',
                        'min_confidence' => 0.7,
                        'interpretation' => 'broken_commitment',
                        'alert_level' => 'warning'
                    ],
                    // 지연 → 무응답 (회피로 전환)
                    'N' => [
                        'type' => 'avoidance_shift',
                        'min_confidence' => 0.65,
                        'interpretation' => 'stalling_behavior',
                        'alert_level' => 'caution'
                    ],
                    // 지연 유지 (추가 지연 요청)
                    'D' => [
                        'type' => 'repeated_delay',
                        'min_confidence' => 0.6,
                        'interpretation' => 'pattern_forming',
                        'escalation_check' => true
                    ]
                ]
            ]
        ];

        // ============================================
        // 개입 반응 전환 트리거 규칙
        // ============================================

        $this->transitionRules = [
            // 메시지 기반 트리거 (개입 반응 감지)
            'message_triggers' => [
                // 수용 신호
                'acceptance_signal' => [
                    'keywords' => ['알겠습니다', '해볼게요', '네', '좋아요', '그렇게 할게요', '이해했어요'],
                    'target_response_type' => 'A',
                    'confidence_boost' => 0.15,
                    'flag' => 'positive_engagement'
                ],
                // 저항 신호
                'resistance_signal' => [
                    'keywords' => ['싫어요', '안 해요', '안 할래요', '왜요', '필요 없어요', '하기 싫어'],
                    'target_response_type' => 'R',
                    'confidence_boost' => 0.20,
                    'flag' => 'resistance_detected'
                ],
                // 무응답 신호
                'minimal_response' => [
                    'keywords' => ['네', '음', '...', '그래요', '몰라요'],
                    'max_length' => 5,  // 짧은 응답
                    'target_response_type' => 'N',
                    'confidence_boost' => 0.10,
                    'flag' => 'minimal_engagement'
                ],
                // 지연 신호
                'delay_signal' => [
                    'keywords' => ['나중에', '다음에', '시간 없어', '바빠서', '있다가', '이따가'],
                    'target_response_type' => 'D',
                    'confidence_boost' => 0.15,
                    'flag' => 'postponement_requested'
                ],
                // 감정적 저항
                'emotional_resistance' => [
                    'keywords' => ['짜증나', '화나', '답답해', '힘들어', '포기'],
                    'target_response_type' => 'R',
                    'confidence_boost' => 0.20,
                    'flag' => 'emotional_resistance',
                    'emotional_support_needed' => true
                ]
            ],

            // 행동 기반 트리거
            'behavior_triggers' => [
                'repeated_short_response' => [
                    'condition' => 'consecutive_short_responses >= 3',
                    'target_response_type' => 'N',
                    'action' => 'engagement_recovery',
                    'confidence_penalty' => -0.10
                ],
                'long_silence' => [
                    'condition' => 'no_response_minutes >= 10',
                    'target_response_type' => 'N',
                    'action' => 'gentle_prompt',
                    'flag' => 'timeout_risk'
                ],
                'positive_streak' => [
                    'condition' => 'acceptance_responses >= 3',
                    'target_response_type' => 'A',
                    'confidence_boost' => 0.15,
                    'action' => 'reinforce_success'
                ],
                'repeated_delay' => [
                    'condition' => 'delay_requests >= 2',
                    'target_response_type' => 'D',
                    'action' => 'address_barrier',
                    'flag' => 'pattern_delay'
                ]
            ],

            // 개입 효과 기반 트리거
            'intervention_triggers' => [
                'resistance_to_acceptance' => [
                    'from' => 'R',
                    'to' => 'A',
                    'action' => 'celebrate_breakthrough',
                    'log_success' => true,
                    'confidence_boost' => 0.20
                ],
                'no_response_to_engagement' => [
                    'from' => 'N',
                    'to' => 'A',
                    'action' => 'celebrate_reengagement',
                    'log_success' => true,
                    'confidence_boost' => 0.15
                ],
                'acceptance_to_resistance' => [
                    'from' => 'A',
                    'to' => 'R',
                    'action' => 'investigate_cause',
                    'alert_level' => 'warning',
                    'confidence_penalty' => -0.15
                ],
                'persistent_no_response' => [
                    'from' => 'N',
                    'to' => 'N',
                    'consecutive' => 3,
                    'action' => 'escalate_to_parent',
                    'alert_level' => 'high'
                ]
            ],

            // 신뢰도 기반 트리거
            'confidence_triggers' => [
                'high_confidence_acceptance' => [
                    'threshold' => 0.85,
                    'response_type' => 'A',
                    'action' => 'confirm_engagement'
                ],
                'low_confidence_any' => [
                    'threshold' => 0.50,
                    'action' => 're_evaluate_response',
                    'flag' => 'uncertain_classification'
                ],
                'confidence_drop' => [
                    'change_threshold' => -0.20,
                    'action' => 'reassess_approach'
                ]
            ]
        ];
    }

    /**
     * 전환 가능 여부 확인
     *
     * @param string $fromResponseType 현재 반응 유형
     * @param string $toResponseType 목표 반응 유형
     * @param float $confidence 신뢰도
     * @return array ['allowed' => bool, 'reason' => string, ...]
     */
    public function canTransition(string $fromResponseType, string $toResponseType, float $confidence): array {
        // 같은 반응 유형이면 항상 허용
        if ($fromResponseType === $toResponseType) {
            return ['allowed' => true, 'reason' => 'same_response_type'];
        }

        // 전환 규칙 확인
        if (!isset($this->responseTypeTransitions[$fromResponseType])) {
            return ['allowed' => false, 'reason' => 'unknown_source_response_type'];
        }

        $transitions = $this->responseTypeTransitions[$fromResponseType];

        // 허용된 전환인지 확인
        if (!in_array($toResponseType, $transitions['allowed'])) {
            return ['allowed' => false, 'reason' => 'transition_not_allowed'];
        }

        // 신뢰도 조건 확인
        $condition = $transitions['conditions'][$toResponseType] ?? null;
        if ($condition && isset($condition['min_confidence'])) {
            if ($confidence < $condition['min_confidence']) {
                return [
                    'allowed' => false,
                    'reason' => 'confidence_too_low',
                    'required_confidence' => $condition['min_confidence'],
                    'current_confidence' => $confidence
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'transition_valid',
            'transition_type' => $condition['type'] ?? 'unknown',
            'interpretation' => $condition['interpretation'] ?? null,
            'alert_level' => $condition['alert_level'] ?? null,
            'celebration' => $condition['celebration'] ?? false,
            'escalation_check' => $condition['escalation_check'] ?? false
        ];
    }

    /**
     * 전환 실행 및 기록
     *
     * @param int $userId 사용자 ID
     * @param string $sessionKey 세션 키
     * @param string $fromPersona 이전 페르소나
     * @param string $toPersona 새 페르소나
     * @param string $triggerType 트리거 유형
     * @param array $details 상세 정보
     * @return bool 성공 여부
     */
    public function executeTransition(
        int $userId,
        string $sessionKey,
        ?string $fromPersona,
        string $toPersona,
        string $triggerType,
        array $details = []
    ): bool {
        try {
            // 테이블 존재 확인
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_persona_transitions');
            if (!$tableExists) {
                error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ . " - transitions 테이블이 존재하지 않음");
                return false;
            }

            // 반응 유형 코드 추출 (A, R, N, D)
            $fromResponseType = $fromPersona ? strtoupper(substr($fromPersona, 0, 1)) : null;
            $toResponseType = strtoupper(substr($toPersona, 0, 1));

            // 전환 가능 여부 확인 (강제 전환이 아닌 경우)
            $transitionInfo = null;
            if ($fromResponseType && !($details['force'] ?? false)) {
                $transitionInfo = $this->canTransition(
                    $fromResponseType,
                    $toResponseType,
                    $details['confidence'] ?? 0.5
                );
                if (!$transitionInfo['allowed']) {
                    error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
                        " - 전환 거부: {$fromResponseType} → {$toResponseType}, 이유: {$transitionInfo['reason']}");
                    return false;
                }
            }

            // 전환 기록 저장
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = 'agent21';
            $record->session_key = $sessionKey;
            $record->from_persona = $fromPersona;
            $record->to_persona = $toPersona;
            $record->from_situation = $fromResponseType;  // Agent21: 반응 유형 코드
            $record->to_situation = $toResponseType;       // Agent21: 반응 유형 코드
            $record->trigger_type = $triggerType;
            $record->trigger_detail = json_encode(array_merge($details, [
                'transition_info' => $transitionInfo,
                'response_type_transition' => "{$fromResponseType}→{$toResponseType}"
            ]));
            $record->confidence_change = $details['confidence_change'] ?? null;
            $record->transition_time = date('Y-m-d H:i:s');

            $this->db->insert_record('augmented_teacher_persona_transitions', $record);

            // 특별 이벤트 처리
            if ($transitionInfo) {
                // 성공 축하
                if (!empty($transitionInfo['celebration'])) {
                    $this->logInterventionSuccess($userId, $fromResponseType, $toResponseType, $details);
                }
                // 에스컬레이션 체크
                if (!empty($transitionInfo['escalation_check'])) {
                    $this->checkEscalationNeeded($userId, $toResponseType, $details);
                }
            }

            return true;

        } catch (Exception $e) {
            error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
                " - 전환 기록 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 개입 성공 기록
     *
     * @param int $userId 사용자 ID
     * @param string $fromType 이전 반응 유형
     * @param string $toType 새 반응 유형
     * @param array $details 상세 정보
     */
    private function logInterventionSuccess(int $userId, string $fromType, string $toType, array $details): void {
        error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
            " - 개입 성공! User {$userId}: {$fromType} → {$toType}");

        // 성공 기록 (별도 테이블 또는 로그)
        // TODO: augmented_teacher_intervention_successes 테이블에 기록
    }

    /**
     * 에스컬레이션 필요 여부 확인
     *
     * @param int $userId 사용자 ID
     * @param string $responseType 현재 반응 유형
     * @param array $details 상세 정보
     */
    private function checkEscalationNeeded(int $userId, string $responseType, array $details): void {
        // 연속 무응답/저항 체크
        $history = $this->getRecentTransitions($userId, 5);

        $consecutiveCount = 0;
        foreach ($history as $transition) {
            if ($transition['to_situation'] === $responseType) {
                $consecutiveCount++;
            } else {
                break;
            }
        }

        if ($consecutiveCount >= 3) {
            error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
                " - 에스컬레이션 권고: User {$userId}, 연속 {$responseType} {$consecutiveCount}회");
            // TODO: 부모 알림 또는 담당자 알림 트리거
        }
    }

    /**
     * 메시지 기반 전환 트리거 감지
     *
     * @param string $message 메시지
     * @param array $currentContext 현재 컨텍스트
     * @return array|null 트리거 정보 또는 null
     */
    public function detectMessageTrigger(string $message, array $currentContext): ?array {
        $messageLength = mb_strlen($message);

        foreach ($this->transitionRules['message_triggers'] as $triggerName => $trigger) {
            // 최대 길이 조건 체크 (무응답 감지용)
            if (isset($trigger['max_length']) && $messageLength > $trigger['max_length']) {
                continue;
            }

            foreach ($trigger['keywords'] as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return [
                        'trigger_name' => $triggerName,
                        'matched_keyword' => $keyword,
                        'target_response_type' => $trigger['target_response_type'] ?? null,
                        'confidence_boost' => $trigger['confidence_boost'] ?? 0,
                        'flag' => $trigger['flag'] ?? null,
                        'emotional_support_needed' => $trigger['emotional_support_needed'] ?? false
                    ];
                }
            }
        }
        return null;
    }

    /**
     * 최근 전환 기록 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 전환 기록
     */
    private function getRecentTransitions(int $userId, int $limit = 5): array {
        try {
            $sql = "SELECT *
                    FROM {augmented_teacher_persona_transitions}
                    WHERE user_id = ? AND agent_id = 'agent21'
                    ORDER BY transition_time DESC
                    LIMIT ?";

            $records = $this->db->get_records_sql($sql, [$userId, $limit]);

            $result = [];
            foreach ($records as $record) {
                $result[] = (array) $record;
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 전환 히스토리 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 전환 히스토리
     */
    public function getTransitionHistory(int $userId, int $limit = 20): array {
        try {
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_persona_transitions');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT *
                    FROM {augmented_teacher_persona_transitions}
                    WHERE user_id = ? AND agent_id = 'agent21'
                    ORDER BY transition_time DESC
                    LIMIT ?";

            $records = $this->db->get_records_sql($sql, [$userId, $limit]);

            $result = [];
            foreach ($records as $record) {
                $item = (array) $record;
                $item['trigger_detail'] = json_decode($item['trigger_detail'], true);
                $result[] = $item;
            }

            return $result;

        } catch (Exception $e) {
            error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
                " - 히스토리 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 개입 반응 전환 패턴 분석
     *
     * @param int $userId 사용자 ID
     * @return array 패턴 분석 결과
     */
    public function analyzeTransitionPatterns(int $userId): array {
        $history = $this->getTransitionHistory($userId, 50);

        if (empty($history)) {
            return ['status' => 'no_data'];
        }

        $analysis = [
            'total_transitions' => count($history),
            'response_type_frequency' => [
                'A' => 0, 'R' => 0, 'N' => 0, 'D' => 0
            ],
            'persona_frequency' => [],
            'transition_pairs' => [],
            'trigger_distribution' => [],
            'intervention_success_rate' => 0,
            'engagement_trend' => null,
            'avg_confidence_change' => 0,
            'resistance_to_acceptance_count' => 0,
            'alerts' => []
        ];

        $confidenceChanges = [];
        $transitionPairs = [];
        $successTransitions = 0;

        foreach ($history as $transition) {
            // 반응 유형 빈도
            $toType = $transition['to_situation'];
            if (isset($analysis['response_type_frequency'][$toType])) {
                $analysis['response_type_frequency'][$toType]++;
            }

            // 페르소나 빈도
            $toPersona = $transition['to_persona'];
            $analysis['persona_frequency'][$toPersona] = ($analysis['persona_frequency'][$toPersona] ?? 0) + 1;

            // 전환 쌍 빈도
            if ($transition['from_persona']) {
                $fromType = $transition['from_situation'];
                $pair = "{$fromType} → {$toType}";
                $transitionPairs[$pair] = ($transitionPairs[$pair] ?? 0) + 1;

                // 성공 전환 카운트 (R→A, N→A)
                if (($fromType === 'R' || $fromType === 'N') && $toType === 'A') {
                    $successTransitions++;
                    $analysis['resistance_to_acceptance_count']++;
                }
            }

            // 트리거 분포
            $trigger = $transition['trigger_type'];
            $analysis['trigger_distribution'][$trigger] = ($analysis['trigger_distribution'][$trigger] ?? 0) + 1;

            // 신뢰도 변화
            if ($transition['confidence_change'] !== null) {
                $confidenceChanges[] = (float) $transition['confidence_change'];
            }
        }

        // 평균 신뢰도 변화
        if (!empty($confidenceChanges)) {
            $analysis['avg_confidence_change'] = round(array_sum($confidenceChanges) / count($confidenceChanges), 3);
        }

        // 개입 성공률 계산
        $totalOpportunities = $analysis['response_type_frequency']['R'] + $analysis['response_type_frequency']['N'];
        if ($totalOpportunities > 0) {
            $analysis['intervention_success_rate'] = round(($successTransitions / $totalOpportunities) * 100, 1);
        }

        // 가장 빈번한 전환
        arsort($transitionPairs);
        $analysis['transition_pairs'] = array_slice($transitionPairs, 0, 5, true);

        // 참여 트렌드 분석
        $recentHistory = array_slice($history, 0, 10);
        $acceptanceCount = 0;
        foreach ($recentHistory as $h) {
            if ($h['to_situation'] === 'A') $acceptanceCount++;
        }
        $analysis['engagement_trend'] = $acceptanceCount >= 5 ? 'positive' : ($acceptanceCount <= 2 ? 'concerning' : 'neutral');

        // 알림 생성
        if ($analysis['response_type_frequency']['R'] > 5) {
            $analysis['alerts'][] = [
                'type' => 'high_resistance',
                'message' => '저항 반응이 빈번합니다. 접근 방식 재검토가 필요할 수 있습니다.'
            ];
        }
        if ($analysis['response_type_frequency']['N'] > 5) {
            $analysis['alerts'][] = [
                'type' => 'engagement_concern',
                'message' => '무응답이 빈번합니다. 참여도 회복 전략이 필요합니다.'
            ];
        }

        return $analysis;
    }

    /**
     * 권장 전환 제안
     *
     * @param string $currentResponseType 현재 반응 유형
     * @param array $context 컨텍스트
     * @return array 권장 전환 목록
     */
    public function suggestTransitions(string $currentResponseType, array $context): array {
        $suggestions = [];

        if (!isset($this->responseTypeTransitions[$currentResponseType])) {
            return $suggestions;
        }

        $allowed = $this->responseTypeTransitions[$currentResponseType]['allowed'];
        $conditions = $this->responseTypeTransitions[$currentResponseType]['conditions'];
        $currentConfidence = $context['confidence'] ?? 0.5;

        foreach ($allowed as $targetType) {
            $condition = $conditions[$targetType];
            $suggestions[] = [
                'target' => $targetType,
                'type' => $condition['type'],
                'interpretation' => $condition['interpretation'],
                'min_confidence' => $condition['min_confidence'],
                'current_meets_requirement' => $currentConfidence >= $condition['min_confidence'],
                'confidence_gap' => max(0, $condition['min_confidence'] - $currentConfidence),
                'is_positive_change' => in_array($targetType, ['A']) && $currentResponseType !== 'A'
            ];
        }

        // 긍정적 변화 우선, 그 다음 조건 충족 순
        usort($suggestions, function($a, $b) {
            if ($a['is_positive_change'] !== $b['is_positive_change']) {
                return $b['is_positive_change'] - $a['is_positive_change'];
            }
            if ($a['current_meets_requirement'] !== $b['current_meets_requirement']) {
                return $b['current_meets_requirement'] - $a['current_meets_requirement'];
            }
            return $a['confidence_gap'] <=> $b['confidence_gap'];
        });

        return $suggestions;
    }

    /**
     * 전환 규칙 반환 (디버깅용)
     *
     * @return array 전환 규칙
     */
    public function getTransitionRules(): array {
        return [
            'response_type_transitions' => $this->responseTypeTransitions,
            'transition_rules' => $this->transitionRules
        ];
    }

    /**
     * 반응 유형 코드 해석
     *
     * @param string $code 반응 유형 코드 (A, R, N, D)
     * @return array 반응 유형 정보
     */
    public function interpretResponseTypeCode(string $code): array {
        $interpretations = [
            'A' => [
                'code' => 'A',
                'name' => 'Acceptance',
                'korean' => '수용',
                'description' => '학생이 개입을 수용하고 협조적으로 반응',
                'positive' => true
            ],
            'R' => [
                'code' => 'R',
                'name' => 'Resistance',
                'korean' => '저항',
                'description' => '학생이 개입에 저항하거나 거부 반응',
                'positive' => false
            ],
            'N' => [
                'code' => 'N',
                'name' => 'No Response',
                'korean' => '무응답',
                'description' => '학생이 최소한의 반응만 보이거나 무반응',
                'positive' => false
            ],
            'D' => [
                'code' => 'D',
                'name' => 'Delayed',
                'korean' => '지연',
                'description' => '학생이 나중에 하겠다고 미루는 반응',
                'positive' => false
            ]
        ];

        return $interpretations[strtoupper($code)] ?? [
            'code' => $code,
            'name' => 'Unknown',
            'korean' => '알 수 없음',
            'description' => '알 수 없는 반응 유형',
            'positive' => null
        ];
    }
}

/*
 * Agent21 개입 실행 반응 유형 전환 규칙:
 *
 * A (수용) → 긍정적 상태, 개입 효과 좋음
 *   → A: 지속적 참여 (좋음)
 *   → R: 관계 악화 가능성 (경고)
 *   → N: 참여도 감소 (주의)
 *   → D: 일정 조정 (보통)
 *
 * R (저항) → 개입 필요 상태
 *   → A: 개입 성공! (축하)
 *   → R: 지속적 저항 (전략 변경 필요)
 *   → N: 회피로 전환 (주의)
 *   → D: 협상 시작 (긍정적)
 *
 * N (무응답) → 참여 회복 필요
 *   → A: 재참여 성공! (축하)
 *   → R: 숨은 저항 표출 (원인 파악)
 *   → N: 심화 (에스컬레이션 검토)
 *   → D: 시간 필요 (보통)
 *
 * D (지연) → 후속 관리 필요
 *   → A: 약속 이행 (축하)
 *   → R: 약속 철회 (경고)
 *   → N: 회피 전환 (주의)
 *   → D: 반복 지연 (패턴 형성)
 *
 * 관련 DB 테이블:
 * - augmented_teacher_persona_transitions
 */
