<?php
/**
 * PersonaTransitionManager - 페르소나 전환 관리자
 *
 * 페르소나 간 전환 관계를 관리하고 전환 이벤트를 기록합니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
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

    /** @var array 상황별 허용 전환 매트릭스 */
    private $situationTransitions = [];

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
     */
    private function initTransitionRules(): void {
        // ============================================
        // 상황(Situation) 간 전환 규칙
        // ============================================

        $this->situationTransitions = [
            // S0 → 정보 수집 완료 후 가능한 전환
            'S0' => [
                'allowed' => ['S1', 'S2', 'S3', 'Q', 'E'],
                'conditions' => [
                    'S1' => ['type' => 'new_registration', 'min_confidence' => 0.6],
                    'S2' => ['type' => 'ready_for_planning', 'min_confidence' => 0.7],
                    'S3' => ['type' => 'progress_assessment', 'min_confidence' => 0.65],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5],
                    'E' => ['type' => 'emotional_trigger', 'min_confidence' => 0.7]
                ]
            ],

            // S1 → 신규 등록 후
            'S1' => [
                'allowed' => ['S2', 'S0', 'Q', 'E', 'C'],
                'conditions' => [
                    'S2' => ['type' => 'registration_complete', 'min_confidence' => 0.7],
                    'S0' => ['type' => 'need_more_info', 'min_confidence' => 0.6],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5],
                    'E' => ['type' => 'emotional_trigger', 'min_confidence' => 0.7],
                    'C' => ['type' => 'complex_situation', 'min_confidence' => 0.8]
                ]
            ],

            // S2 → 학습 설계 후
            'S2' => [
                'allowed' => ['S3', 'S1', 'Q', 'E', 'C'],
                'conditions' => [
                    'S3' => ['type' => 'plan_created', 'min_confidence' => 0.7],
                    'S1' => ['type' => 'need_reassessment', 'min_confidence' => 0.6],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5],
                    'E' => ['type' => 'emotional_trigger', 'min_confidence' => 0.7],
                    'C' => ['type' => 'complex_situation', 'min_confidence' => 0.8]
                ]
            ],

            // S3 → 진도 판단 후
            'S3' => [
                'allowed' => ['S2', 'S4', 'S5', 'Q', 'E', 'C'],
                'conditions' => [
                    'S2' => ['type' => 'need_plan_adjustment', 'min_confidence' => 0.65],
                    'S4' => ['type' => 'parent_consultation_needed', 'min_confidence' => 0.7],
                    'S5' => ['type' => 'goal_discussion', 'min_confidence' => 0.65],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5],
                    'E' => ['type' => 'emotional_trigger', 'min_confidence' => 0.7],
                    'C' => ['type' => 'complex_situation', 'min_confidence' => 0.8]
                ]
            ],

            // S4 → 학부모 상담 후
            'S4' => [
                'allowed' => ['S2', 'S3', 'S5', 'Q', 'C'],
                'conditions' => [
                    'S2' => ['type' => 'plan_revision_needed', 'min_confidence' => 0.65],
                    'S3' => ['type' => 'progress_review', 'min_confidence' => 0.6],
                    'S5' => ['type' => 'goal_alignment', 'min_confidence' => 0.65],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5],
                    'C' => ['type' => 'complex_situation', 'min_confidence' => 0.8]
                ]
            ],

            // S5 → 장기 목표 후
            'S5' => [
                'allowed' => ['S2', 'S3', 'Q', 'E', 'C'],
                'conditions' => [
                    'S2' => ['type' => 'implement_goals', 'min_confidence' => 0.7],
                    'S3' => ['type' => 'track_progress', 'min_confidence' => 0.65],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5],
                    'E' => ['type' => 'emotional_trigger', 'min_confidence' => 0.7],
                    'C' => ['type' => 'complex_situation', 'min_confidence' => 0.8]
                ]
            ],

            // C → 복합 상황 후
            'C' => [
                'allowed' => ['S0', 'S1', 'S2', 'S3', 'E', 'Q'],
                'conditions' => [
                    'S0' => ['type' => 'need_fresh_assessment', 'min_confidence' => 0.6],
                    'S1' => ['type' => 'restart_process', 'min_confidence' => 0.65],
                    'S2' => ['type' => 'focus_on_planning', 'min_confidence' => 0.7],
                    'S3' => ['type' => 'evaluate_progress', 'min_confidence' => 0.65],
                    'E' => ['type' => 'emotional_priority', 'min_confidence' => 0.8],
                    'Q' => ['type' => 'general_question', 'min_confidence' => 0.5]
                ]
            ],

            // Q → 일반 질문 후
            'Q' => [
                'allowed' => ['S0', 'S1', 'S2', 'S3', 'S4', 'S5', 'E', 'C'],
                'conditions' => [
                    'S0' => ['type' => 'need_assessment', 'min_confidence' => 0.6],
                    'S1' => ['type' => 'new_student', 'min_confidence' => 0.65],
                    'S2' => ['type' => 'planning_needed', 'min_confidence' => 0.65],
                    'S3' => ['type' => 'progress_check', 'min_confidence' => 0.6],
                    'S4' => ['type' => 'parent_involvement', 'min_confidence' => 0.7],
                    'S5' => ['type' => 'goal_discussion', 'min_confidence' => 0.65],
                    'E' => ['type' => 'emotional_trigger', 'min_confidence' => 0.7],
                    'C' => ['type' => 'complex_situation', 'min_confidence' => 0.8]
                ]
            ],

            // E → 정서적 상황 후
            'E' => [
                'allowed' => ['S0', 'S2', 'S3', 'Q', 'C'],
                'conditions' => [
                    'S0' => ['type' => 'emotional_resolved', 'min_confidence' => 0.6],
                    'S2' => ['type' => 'ready_for_learning', 'min_confidence' => 0.65],
                    'S3' => ['type' => 'gentle_progress', 'min_confidence' => 0.6],
                    'Q' => ['type' => 'general_inquiry', 'min_confidence' => 0.5],
                    'C' => ['type' => 'compound_issues', 'min_confidence' => 0.75]
                ]
            ]
        ];

        // ============================================
        // 페르소나 전환 트리거 규칙
        // ============================================

        $this->transitionRules = [
            // 메시지 기반 트리거
            'message_triggers' => [
                'anxiety_increase' => [
                    'keywords' => ['불안', '걱정', '무서워', '두려워', '긴장'],
                    'target_situation' => 'E',
                    'confidence_boost' => 0.15
                ],
                'frustration_increase' => [
                    'keywords' => ['짜증', '화나', '답답', '포기', '못하겠어'],
                    'target_situation' => 'E',
                    'confidence_boost' => 0.20
                ],
                'confidence_gain' => [
                    'keywords' => ['할 수 있', '해볼게', '이해했', '알겠'],
                    'confidence_boost' => 0.10,
                    'flag' => 'positive_progress'
                ],
                'help_request' => [
                    'keywords' => ['도와주세요', '모르겠어요', '어떻게'],
                    'flag' => 'needs_support'
                ]
            ],

            // 행동 기반 트리거
            'behavior_triggers' => [
                'repeated_short_response' => [
                    'condition' => 'consecutive_short_responses >= 3',
                    'action' => 'increase_engagement',
                    'confidence_penalty' => -0.10
                ],
                'long_silence' => [
                    'condition' => 'no_response_minutes >= 5',
                    'action' => 'prompt_engagement',
                    'flag' => 'disengagement_risk'
                ],
                'rapid_improvement' => [
                    'condition' => 'positive_responses >= 5',
                    'target_situation' => 'S3',
                    'confidence_boost' => 0.15
                ]
            ],

            // 신뢰도 기반 트리거
            'confidence_triggers' => [
                'high_confidence_stable' => [
                    'threshold' => 0.85,
                    'consecutive_matches' => 3,
                    'action' => 'confirm_persona'
                ],
                'low_confidence_drop' => [
                    'threshold' => 0.50,
                    'action' => 're_evaluate',
                    'target_situation' => 'S0'
                ],
                'confidence_oscillation' => [
                    'variance' => 0.20,
                    'window' => 5,
                    'action' => 'complex_assessment',
                    'target_situation' => 'C'
                ]
            ]
        ];
    }

    /**
     * 전환 가능 여부 확인
     *
     * @param string $fromSituation 현재 상황
     * @param string $toSituation 목표 상황
     * @param float $confidence 신뢰도
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function canTransition(string $fromSituation, string $toSituation, float $confidence): array {
        // 같은 상황이면 항상 허용
        if ($fromSituation === $toSituation) {
            return ['allowed' => true, 'reason' => 'same_situation'];
        }

        // 전환 규칙 확인
        if (!isset($this->situationTransitions[$fromSituation])) {
            return ['allowed' => false, 'reason' => 'unknown_source_situation'];
        }

        $transitions = $this->situationTransitions[$fromSituation];

        // 허용된 전환인지 확인
        if (!in_array($toSituation, $transitions['allowed'])) {
            return ['allowed' => false, 'reason' => 'transition_not_allowed'];
        }

        // 신뢰도 조건 확인
        $condition = $transitions['conditions'][$toSituation] ?? null;
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
            'transition_type' => $condition['type'] ?? 'unknown'
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

            // 상황 코드 추출
            $fromSituation = $fromPersona ? substr($fromPersona, 0, 2) : null;
            $toSituation = substr($toPersona, 0, 2);

            // 전환 가능 여부 확인 (강제 전환이 아닌 경우)
            if ($fromSituation && !($details['force'] ?? false)) {
                $canTransit = $this->canTransition(
                    $fromSituation,
                    $toSituation,
                    $details['confidence'] ?? 0.5
                );
                if (!$canTransit['allowed']) {
                    error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
                        " - 전환 거부: {$fromSituation} → {$toSituation}, 이유: {$canTransit['reason']}");
                    return false;
                }
            }

            // 전환 기록 저장
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = 'agent01';
            $record->session_key = $sessionKey;
            $record->from_persona = $fromPersona;
            $record->to_persona = $toPersona;
            $record->from_situation = $fromSituation;
            $record->to_situation = $toSituation;
            $record->trigger_type = $triggerType;
            $record->trigger_detail = json_encode($details);
            $record->confidence_change = $details['confidence_change'] ?? null;
            $record->transition_time = date('Y-m-d H:i:s');

            $this->db->insert_record('augmented_teacher_persona_transitions', $record);

            return true;

        } catch (Exception $e) {
            error_log("[PersonaTransitionManager] {$this->currentFile}:" . __LINE__ .
                " - 전환 기록 실패: " . $e->getMessage());
            return false;
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
        foreach ($this->transitionRules['message_triggers'] as $triggerName => $trigger) {
            foreach ($trigger['keywords'] as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return [
                        'trigger_name' => $triggerName,
                        'matched_keyword' => $keyword,
                        'target_situation' => $trigger['target_situation'] ?? null,
                        'confidence_boost' => $trigger['confidence_boost'] ?? 0,
                        'flag' => $trigger['flag'] ?? null
                    ];
                }
            }
        }
        return null;
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
                    WHERE user_id = ?
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
     * 페르소나 전환 패턴 분석
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
            'situation_frequency' => [],
            'persona_frequency' => [],
            'common_transitions' => [],
            'trigger_distribution' => [],
            'avg_confidence_change' => 0,
            'stability_score' => 0
        ];

        $confidenceChanges = [];
        $transitionPairs = [];

        foreach ($history as $transition) {
            // 상황 빈도
            $toSit = $transition['to_situation'];
            $analysis['situation_frequency'][$toSit] = ($analysis['situation_frequency'][$toSit] ?? 0) + 1;

            // 페르소나 빈도
            $toPersona = $transition['to_persona'];
            $analysis['persona_frequency'][$toPersona] = ($analysis['persona_frequency'][$toPersona] ?? 0) + 1;

            // 전환 쌍 빈도
            if ($transition['from_persona']) {
                $pair = $transition['from_persona'] . ' → ' . $toPersona;
                $transitionPairs[$pair] = ($transitionPairs[$pair] ?? 0) + 1;
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

        // 가장 빈번한 전환
        arsort($transitionPairs);
        $analysis['common_transitions'] = array_slice($transitionPairs, 0, 5, true);

        // 안정성 점수 계산 (전환이 적을수록 높음)
        $uniquePersonas = count($analysis['persona_frequency']);
        $transitionsPerPersona = count($history) / max($uniquePersonas, 1);
        $analysis['stability_score'] = round(max(0, 100 - ($transitionsPerPersona * 10)), 1);

        return $analysis;
    }

    /**
     * 권장 전환 제안
     *
     * @param string $currentSituation 현재 상황
     * @param array $context 컨텍스트
     * @return array 권장 전환 목록
     */
    public function suggestTransitions(string $currentSituation, array $context): array {
        $suggestions = [];

        if (!isset($this->situationTransitions[$currentSituation])) {
            return $suggestions;
        }

        $allowed = $this->situationTransitions[$currentSituation]['allowed'];
        $conditions = $this->situationTransitions[$currentSituation]['conditions'];
        $currentConfidence = $context['confidence'] ?? 0.5;

        foreach ($allowed as $targetSituation) {
            $condition = $conditions[$targetSituation];
            $suggestions[] = [
                'target' => $targetSituation,
                'type' => $condition['type'],
                'min_confidence' => $condition['min_confidence'],
                'current_meets_requirement' => $currentConfidence >= $condition['min_confidence'],
                'confidence_gap' => max(0, $condition['min_confidence'] - $currentConfidence)
            ];
        }

        // 현재 조건을 충족하는 것 먼저
        usort($suggestions, function($a, $b) {
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
            'situation_transitions' => $this->situationTransitions,
            'transition_rules' => $this->transitionRules
        ];
    }
}

/*
 * 지원 전환 트리거 유형:
 * - message_analysis: 메시지 분석 기반
 * - behavior_pattern: 행동 패턴 감지
 * - explicit_request: 명시적 요청
 * - situation_change: 상황 변화
 * - confidence_threshold: 신뢰도 임계값
 * - time_based: 시간 기반
 *
 * 관련 DB 테이블:
 * - augmented_teacher_persona_transitions
 */
