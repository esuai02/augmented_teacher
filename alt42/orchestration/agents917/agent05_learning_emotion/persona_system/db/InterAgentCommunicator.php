<?php
/**
 * InterAgentCommunicator.php
 *
 * Agent05 Learning Emotion - 에이전트 간 통신 모듈
 * 감정 정보를 다른 에이전트들과 공유하고 조율합니다.
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem\DB
 * @version 1.0
 * @author Claude Code
 * @created 2024-12-02
 *
 * DB Tables:
 * - mdl_at_agent_emotion_share: 감정 공유 기록
 * - mdl_at_agent_messages: 공통 메시지 큐 (ontology_engineering)
 */

namespace AugmentedTeacher\Agent05\PersonaSystem\DB;

class InterAgentCommunicator {

    /** @var \moodle_database DB 인스턴스 */
    private $db;

    /** @var string 현재 에이전트 ID */
    private $agentId = 'agent05';

    /** @var string 현재 파일 경로 */
    private $currentFile;

    /** @var array 수신 대상 에이전트 목록 */
    private $targetAgents = [
        'agent06' => ['priority' => 'high', 'types' => ['emotion_alert', 'approach_recommendation']],
        'agent07' => ['priority' => 'medium', 'types' => ['learning_emotion', 'frustration_alert']],
        'agent08' => ['priority' => 'high', 'types' => ['fatigue_alert', 'calmness_trigger']],
        'agent09' => ['priority' => 'medium', 'types' => ['emotion_summary', 'intervention_needed']],
    ];

    /** @var int 메시지 기본 만료 시간 (초) */
    private $defaultExpirySeconds = 3600; // 1시간

    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->currentFile = __FILE__;
    }

    // =========================================================================
    // 감정 정보 공유
    // =========================================================================

    /**
     * 감정 정보를 다른 에이전트들에게 공유
     *
     * @param int $userId 사용자 ID
     * @param array $emotionData 감정 데이터
     * @return array 공유 결과
     */
    public function shareEmotionInfo(int $userId, array $emotionData): array {
        $results = [];
        $shareRecords = [];

        // 각 대상 에이전트에게 공유
        foreach ($this->targetAgents as $targetAgent => $config) {
            $summary = $this->prepareEmotionSummary($emotionData, $config['types']);

            if (empty($summary)) {
                continue;
            }

            $urgency = $this->calculateUrgency($emotionData);

            $record = [
                'user_id' => $userId,
                'from_agent' => $this->agentId,
                'to_agent' => $targetAgent,
                'emotion_summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
                'recommended_approach' => $this->getRecommendedApproach($emotionData),
                'urgency_level' => $urgency,
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', time() + $this->defaultExpirySeconds),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            try {
                $id = $this->db->insert_record('at_agent_emotion_share', (object)$record);
                $shareRecords[] = [
                    'id' => $id,
                    'to_agent' => $targetAgent,
                    'urgency' => $urgency,
                    'status' => 'created'
                ];
                $results[$targetAgent] = ['success' => true, 'share_id' => $id];
            } catch (\Exception $e) {
                $results[$targetAgent] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'location' => $this->currentFile . ':' . __LINE__
                ];
                error_log("[Agent05 InterAgentCommunicator] Share failed for {$targetAgent}: " . $e->getMessage());
            }
        }

        return [
            'shared_count' => count(array_filter($results, fn($r) => $r['success'])),
            'results' => $results,
            'records' => $shareRecords
        ];
    }

    /**
     * 특정 에이전트에게 긴급 알림 전송
     *
     * @param int $userId 사용자 ID
     * @param string $targetAgent 대상 에이전트
     * @param string $alertType 알림 유형
     * @param array $data 알림 데이터
     * @return array 전송 결과
     */
    public function sendUrgentAlert(int $userId, string $targetAgent, string $alertType, array $data): array {
        // 긴급 알림은 at_agent_messages 테이블 사용 (공통 메시지 큐)
        $messageId = $this->generateMessageId();

        $payload = [
            'alert_type' => $alertType,
            'emotion_data' => $data,
            'timestamp' => time(),
            'source' => $this->agentId
        ];

        $message = [
            'message_id' => $messageId,
            'from_agent' => $this->agentId,
            'to_agent' => $targetAgent,
            'message_type' => 'urgent_alert',
            'priority' => 1, // 최고 우선순위
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'checksum' => hash('sha256', json_encode($payload)),
            'protocol_version' => '1.0.0',
            'retry_count' => 0,
            'timecreated' => time()
        ];

        try {
            $id = $this->db->insert_record('at_agent_messages', (object)$message);
            return [
                'success' => true,
                'message_id' => $messageId,
                'record_id' => $id,
                'target' => $targetAgent,
                'alert_type' => $alertType
            ];
        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Urgent alert failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'location' => $this->currentFile . ':' . __LINE__
            ];
        }
    }

    /**
     * 수신된 메시지 조회
     *
     * @param string $status 상태 필터
     * @param int $limit 제한
     * @return array 메시지 목록
     */
    public function getReceivedMessages(string $status = 'pending', int $limit = 50): array {
        try {
            $sql = "SELECT * FROM {at_agent_messages}
                    WHERE to_agent = ?
                    AND status = ?
                    ORDER BY priority ASC, timecreated ASC
                    LIMIT ?";

            $messages = $this->db->get_records_sql($sql, [$this->agentId, $status, $limit]);

            return array_map(function($msg) {
                $msg->payload = json_decode($msg->payload, true);
                return $msg;
            }, $messages);

        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Get messages failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 메시지 처리 완료 표시
     *
     * @param string $messageId 메시지 ID
     * @param string $status 최종 상태
     * @return bool 성공 여부
     */
    public function markMessageProcessed(string $messageId, string $status = 'processed'): bool {
        try {
            $this->db->execute(
                "UPDATE {at_agent_messages}
                 SET status = ?, timeprocessed = ?
                 WHERE message_id = ?",
                [$status, time(), $messageId]
            );
            return true;
        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Mark processed failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 감정 공유 조회
    // =========================================================================

    /**
     * 특정 사용자의 최근 감정 공유 기록 조회
     *
     * @param int $userId 사용자 ID
     * @param int $hours 최근 N시간
     * @return array 공유 기록
     */
    public function getRecentShares(int $userId, int $hours = 24): array {
        try {
            $since = date('Y-m-d H:i:s', time() - ($hours * 3600));

            $sql = "SELECT * FROM {at_agent_emotion_share}
                    WHERE user_id = ?
                    AND from_agent = ?
                    AND created_at >= ?
                    ORDER BY created_at DESC";

            $shares = $this->db->get_records_sql($sql, [$userId, $this->agentId, $since]);

            return array_map(function($share) {
                $share->emotion_summary = json_decode($share->emotion_summary, true);
                return $share;
            }, $shares);

        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Get recent shares failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 다른 에이전트로부터 받은 감정 관련 정보 조회
     *
     * @param int $userId 사용자 ID
     * @return array 수신된 정보
     */
    public function getReceivedEmotionInfo(int $userId): array {
        try {
            $sql = "SELECT * FROM {at_agent_emotion_share}
                    WHERE user_id = ?
                    AND to_agent = ?
                    AND status IN ('pending', 'delivered')
                    AND (expires_at IS NULL OR expires_at > NOW())
                    ORDER BY urgency_level DESC, created_at DESC";

            $shares = $this->db->get_records_sql($sql, [$userId, $this->agentId]);

            // 수신 확인 처리
            foreach ($shares as $share) {
                $this->acknowledgeShare($share->id);
            }

            return array_map(function($share) {
                $share->emotion_summary = json_decode($share->emotion_summary, true);
                return $share;
            }, $shares);

        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Get received emotion info failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 공유 수신 확인
     *
     * @param int $shareId 공유 ID
     * @return bool 성공 여부
     */
    public function acknowledgeShare(int $shareId): bool {
        try {
            $this->db->execute(
                "UPDATE {at_agent_emotion_share}
                 SET status = 'acknowledged', acknowledged_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND status != 'acknowledged'",
                [$shareId]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // 특수 알림 메서드
    // =========================================================================

    /**
     * 좌절 에스컬레이션 알림
     * 연속 실패 시 관련 에이전트들에게 알림
     *
     * @param int $userId 사용자 ID
     * @param array $frustrationData 좌절 데이터
     * @return array 알림 결과
     */
    public function notifyFrustrationEscalation(int $userId, array $frustrationData): array {
        $results = [];

        // Agent08 (calmness)에게 긴급 알림
        $results['agent08'] = $this->sendUrgentAlert(
            $userId,
            'agent08',
            'frustration_escalation',
            [
                'intensity' => $frustrationData['intensity'] ?? 'high',
                'consecutive_failures' => $frustrationData['consecutive_failures'] ?? 0,
                'current_emotion' => 'frustration',
                'recommended_action' => 'activate_calming_mode'
            ]
        );

        // Agent07 (math_tutor)에게 알림 - 난이도 조절 권장
        $results['agent07'] = $this->sendUrgentAlert(
            $userId,
            'agent07',
            'difficulty_adjustment_needed',
            [
                'reason' => 'frustration_escalation',
                'current_difficulty' => $frustrationData['current_difficulty'] ?? 'unknown',
                'recommended_action' => 'reduce_difficulty'
            ]
        );

        return $results;
    }

    /**
     * 피로 알림
     * 장시간 학습 시 관련 에이전트들에게 알림
     *
     * @param int $userId 사용자 ID
     * @param array $fatigueData 피로 데이터
     * @return array 알림 결과
     */
    public function notifyFatigue(int $userId, array $fatigueData): array {
        $results = [];

        // Agent08에게 휴식 권유 알림
        $results['agent08'] = $this->sendUrgentAlert(
            $userId,
            'agent08',
            'fatigue_detected',
            [
                'intensity' => $fatigueData['intensity'] ?? 'medium',
                'study_duration_minutes' => $fatigueData['duration'] ?? 0,
                'recommended_action' => 'suggest_break'
            ]
        );

        // Agent09에게 세션 종료 고려 알림
        if (($fatigueData['intensity'] ?? '') === 'high') {
            $results['agent09'] = $this->sendUrgentAlert(
                $userId,
                'agent09',
                'session_end_consideration',
                [
                    'reason' => 'high_fatigue',
                    'study_duration_minutes' => $fatigueData['duration'] ?? 0,
                    'recommended_action' => 'wrap_up_session'
                ]
            );
        }

        return $results;
    }

    /**
     * 성취 축하 동기화
     * 주요 성취 시 다른 에이전트들과 축하 동기화
     *
     * @param int $userId 사용자 ID
     * @param array $achievementData 성취 데이터
     * @return array 동기화 결과
     */
    public function syncCelebration(int $userId, array $achievementData): array {
        $payload = [
            'achievement_type' => $achievementData['type'] ?? 'general',
            'achievement_detail' => $achievementData['detail'] ?? '',
            'emotion_state' => 'achievement',
            'intensity' => $achievementData['intensity'] ?? 'medium',
            'sync_action' => 'join_celebration'
        ];

        $results = [];
        $celebrationAgents = ['agent06', 'agent07', 'agent08', 'agent09'];

        foreach ($celebrationAgents as $agent) {
            $results[$agent] = $this->sendUrgentAlert(
                $userId,
                $agent,
                'celebration_sync',
                $payload
            );
        }

        return $results;
    }

    // =========================================================================
    // 내부 헬퍼 메서드
    // =========================================================================

    /**
     * 감정 요약 준비
     *
     * @param array $emotionData 감정 데이터
     * @param array $types 포함할 타입
     * @return array 요약
     */
    private function prepareEmotionSummary(array $emotionData, array $types): array {
        $summary = [
            'primary_emotion' => $emotionData['type'] ?? null,
            'intensity' => $emotionData['intensity'] ?? 'medium',
            'score' => $emotionData['score'] ?? 0.5,
            'timestamp' => time()
        ];

        // 2차 감정 포함
        if (!empty($emotionData['secondary_emotions'])) {
            $summary['secondary_emotions'] = $emotionData['secondary_emotions'];
        }

        // 활동 정보 포함
        if (!empty($emotionData['activity_type'])) {
            $summary['activity_context'] = $emotionData['activity_type'];
        }

        // 권장 페르소나
        if (!empty($emotionData['recommended_persona'])) {
            $summary['recommended_persona'] = $emotionData['recommended_persona'];
        }

        return $summary;
    }

    /**
     * 긴급도 계산
     *
     * @param array $emotionData 감정 데이터
     * @return string 긴급도
     */
    private function calculateUrgency(array $emotionData): string {
        $intensity = $emotionData['intensity'] ?? 'medium';
        $type = $emotionData['type'] ?? '';

        // 높은 강도의 부정적 감정
        if ($intensity === 'high') {
            if (in_array($type, ['frustration', 'anxiety', 'fatigue'])) {
                return 'critical';
            }
            return 'high';
        }

        // 중간 강도의 부정적 감정
        if ($intensity === 'medium') {
            if (in_array($type, ['frustration', 'anxiety'])) {
                return 'high';
            }
            if (in_array($type, ['boredom', 'fatigue', 'confusion'])) {
                return 'medium';
            }
        }

        return 'low';
    }

    /**
     * 권장 접근법 결정
     *
     * @param array $emotionData 감정 데이터
     * @return string 권장 접근법
     */
    private function getRecommendedApproach(array $emotionData): string {
        $type = $emotionData['type'] ?? '';
        $intensity = $emotionData['intensity'] ?? 'medium';

        $approaches = [
            'anxiety' => [
                'high' => '차분하고 안정적인 접근, 명확한 단계별 안내',
                'medium' => '격려와 함께 작은 성공 경험 제공',
                'low' => '자신감 부여, 도전적 과제 점진적 제시'
            ],
            'frustration' => [
                'high' => '먼저 감정 수용, 휴식 권유, 다른 접근법 제안',
                'medium' => '문제 분석 도움, 힌트 제공',
                'low' => '격려하며 지속 유도'
            ],
            'confidence' => [
                'high' => '도전적 과제 제시, 성취 인정',
                'medium' => '현재 수준 유지하며 점진적 확장',
                'low' => '작은 성공 축하, 자신감 구축'
            ],
            'curiosity' => [
                'high' => '깊이 있는 탐구 유도, 관련 개념 연결',
                'medium' => '질문 기반 학습 촉진',
                'low' => '흥미로운 사실로 호기심 자극'
            ],
            'boredom' => [
                'high' => '활동 변경 필요, 게임적 요소 도입',
                'medium' => '새로운 도전 제시',
                'low' => '목표 상기시키며 동기 부여'
            ],
            'fatigue' => [
                'high' => '휴식 강력 권유, 세션 종료 고려',
                'medium' => '페이스 조절, 쉬운 문제로 전환',
                'low' => '짧은 휴식 후 계속'
            ],
            'achievement' => [
                'high' => '충분한 축하와 인정, 다음 목표 제시',
                'medium' => '성취 인정하며 모멘텀 유지',
                'low' => '작은 진전도 인정'
            ],
            'confusion' => [
                'high' => '처음부터 차근차근 재설명, 비유 활용',
                'medium' => '핵심 포인트 정리, 예시 추가',
                'low' => '명확화 질문으로 혼란 지점 파악'
            ]
        ];

        return $approaches[$type][$intensity] ?? '상황에 맞는 적절한 지원 제공';
    }

    /**
     * 고유 메시지 ID 생성
     *
     * @return string 메시지 ID
     */
    private function generateMessageId(): string {
        return sprintf(
            '%s_%s_%s',
            $this->agentId,
            date('YmdHis'),
            bin2hex(random_bytes(8))
        );
    }

    // =========================================================================
    // 정리 메서드
    // =========================================================================

    /**
     * 만료된 공유 정리
     *
     * @return int 정리된 레코드 수
     */
    public function cleanupExpiredShares(): int {
        try {
            $result = $this->db->execute(
                "UPDATE {at_agent_emotion_share}
                 SET status = 'expired', updated_at = NOW()
                 WHERE expires_at < NOW()
                 AND status NOT IN ('expired', 'acknowledged')"
            );

            return $this->db->count_records_sql(
                "SELECT COUNT(*) FROM {at_agent_emotion_share} WHERE status = 'expired'"
            );

        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Cleanup failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 통계 조회
     *
     * @return array 통계
     */
    public function getStatistics(): array {
        try {
            $stats = [
                'sent_today' => $this->db->count_records_sql(
                    "SELECT COUNT(*) FROM {at_agent_emotion_share}
                     WHERE from_agent = ? AND DATE(created_at) = CURDATE()",
                    [$this->agentId]
                ),
                'pending_count' => $this->db->count_records_sql(
                    "SELECT COUNT(*) FROM {at_agent_emotion_share}
                     WHERE from_agent = ? AND status = 'pending'",
                    [$this->agentId]
                ),
                'acknowledged_count' => $this->db->count_records_sql(
                    "SELECT COUNT(*) FROM {at_agent_emotion_share}
                     WHERE from_agent = ? AND status = 'acknowledged'
                     AND DATE(created_at) = CURDATE()",
                    [$this->agentId]
                ),
                'received_pending' => $this->db->count_records_sql(
                    "SELECT COUNT(*) FROM {at_agent_emotion_share}
                     WHERE to_agent = ? AND status = 'pending'",
                    [$this->agentId]
                )
            ];

            return $stats;

        } catch (\Exception $e) {
            error_log("[Agent05 InterAgentCommunicator] Get statistics failed: " . $e->getMessage());
            return [];
        }
    }
}

/*
 * =====================================
 * 사용 예시
 * =====================================
 *
 * // 초기화
 * $communicator = new InterAgentCommunicator();
 *
 * // 감정 정보 공유
 * $result = $communicator->shareEmotionInfo($userId, [
 *     'type' => 'frustration',
 *     'intensity' => 'high',
 *     'score' => 0.85,
 *     'activity_type' => 'problem_solving'
 * ]);
 *
 * // 좌절 에스컬레이션 알림
 * $result = $communicator->notifyFrustrationEscalation($userId, [
 *     'intensity' => 'high',
 *     'consecutive_failures' => 5
 * ]);
 *
 * // 수신된 정보 조회
 * $received = $communicator->getReceivedEmotionInfo($userId);
 *
 * // 성취 축하 동기화
 * $result = $communicator->syncCelebration($userId, [
 *     'type' => 'problem_solved',
 *     'detail' => '어려운 문제 해결',
 *     'intensity' => 'high'
 * ]);
 *
 * =====================================
 * DB Tables
 * =====================================
 *
 * mdl_at_agent_emotion_share:
 * - user_id, from_agent, to_agent
 * - emotion_summary (JSON)
 * - recommended_approach, urgency_level
 * - status, expires_at
 *
 * mdl_at_agent_messages (공통):
 * - message_id, from_agent, to_agent
 * - message_type, priority, payload
 * - status, checksum, retry_count
 */
