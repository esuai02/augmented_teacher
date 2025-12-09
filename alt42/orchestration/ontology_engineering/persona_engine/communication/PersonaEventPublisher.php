<?php
/**
 * PersonaEventPublisher - 페르소나 이벤트 발행기
 * 
 * 페르소나 관련 이벤트를 발행하고 구독자에게 전달하는 이벤트 시스템
 * Observer 패턴 기반 구현
 * 
 * @package AugmentedTeacher\PersonaEngine\Communication
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/AgentCommunicator.php');

class PersonaEventPublisher {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var string 에이전트 ID */
    protected $agentId;

    /** @var AgentCommunicator 통신 모듈 */
    protected $communicator;

    /** @var array 로컬 이벤트 핸들러 */
    protected $handlers = [];

    /** @var array 이벤트 타입 정의 */
    protected $eventTypes = [
        'persona.identified' => '페르소나 식별 완료',
        'persona.changed' => '페르소나 변경',
        'persona.transition' => '페르소나 전환 시작',
        'emotion.detected' => '감정 감지',
        'intent.detected' => '의도 감지',
        'situation.changed' => '상황 변경',
        'action.executed' => '액션 실행 완료',
        'crisis.detected' => '위기 상황 감지',
        'escalation.triggered' => '에스컬레이션 발생'
    ];

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID
     */
    public function __construct(string $agentId) {
        $this->agentId = $agentId;
        $this->communicator = new AgentCommunicator($agentId);
    }

    /**
     * 이벤트 발행
     *
     * @param string $eventName 이벤트 이름
     * @param array $eventData 이벤트 데이터
     * @param array $options 옵션 (broadcast, priority, user_id 등)
     * @return bool 성공 여부
     */
    public function publish(string $eventName, array $eventData, array $options = []): bool {
        try {
            // 이벤트 데이터 표준화
            $event = [
                'event_name' => $eventName,
                'source_agent' => $this->agentId,
                'timestamp' => time(),
                'data' => $eventData
            ];

            // 1. 로컬 핸들러 실행
            $this->executeLocalHandlers($eventName, $event);

            // 2. 브로드캐스트 옵션이 있으면 다른 에이전트에 전달
            if ($options['broadcast'] ?? false) {
                $this->communicator->broadcast($eventName, $event, [
                    'user_id' => $options['user_id'] ?? null,
                    'priority' => $options['priority'] ?? 5
                ]);
            }

            // 3. 로그 기록
            $this->logEvent($eventName, $event, $options);

            return true;

        } catch (Exception $e) {
            $this->logError("이벤트 발행 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 이벤트 핸들러 등록
     *
     * @param string $eventName 이벤트 이름 (와일드카드 * 지원)
     * @param callable $handler 핸들러 함수
     * @param int $priority 우선순위 (낮을수록 먼저 실행)
     * @return void
     */
    public function subscribe(string $eventName, callable $handler, int $priority = 10): void {
        if (!isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = [];
        }
        
        $this->handlers[$eventName][] = [
            'handler' => $handler,
            'priority' => $priority
        ];

        // 우선순위별 정렬
        usort($this->handlers[$eventName], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }

    /**
     * 이벤트 핸들러 제거
     *
     * @param string $eventName 이벤트 이름
     * @param callable|null $handler 특정 핸들러 (null=모두 제거)
     * @return void
     */
    public function unsubscribe(string $eventName, ?callable $handler = null): void {
        if (!isset($this->handlers[$eventName])) {
            return;
        }

        if ($handler === null) {
            unset($this->handlers[$eventName]);
        } else {
            $this->handlers[$eventName] = array_filter(
                $this->handlers[$eventName],
                function($item) use ($handler) {
                    return $item['handler'] !== $handler;
                }
            );
        }
    }

    /**
     * 페르소나 식별 이벤트 발행
     *
     * @param int $userId 사용자 ID
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return bool
     */
    public function publishPersonaIdentified(int $userId, array $identification, array $context = []): bool {
        return $this->publish('persona.identified', [
            'user_id' => $userId,
            'persona_id' => $identification['persona_id'] ?? 'default',
            'persona_name' => $identification['persona_name'] ?? '',
            'confidence' => $identification['confidence'] ?? 0.5,
            'tone' => $identification['tone'] ?? 'Professional',
            'intervention' => $identification['intervention'] ?? 'InformationProvision',
            'matched_rule' => $identification['matched_rule'] ?? '',
            'context' => $context
        ], ['user_id' => $userId, 'broadcast' => true, 'priority' => 5]);
    }

    /**
     * 페르소나 변경 이벤트 발행
     *
     * @param int $userId 사용자 ID
     * @param string $oldPersona 이전 페르소나
     * @param string $newPersona 새 페르소나
     * @param string $reason 변경 사유
     * @return bool
     */
    public function publishPersonaChanged(int $userId, string $oldPersona, string $newPersona, string $reason = ''): bool {
        return $this->publish('persona.changed', [
            'user_id' => $userId,
            'old_persona' => $oldPersona,
            'new_persona' => $newPersona,
            'reason' => $reason
        ], ['user_id' => $userId, 'broadcast' => true, 'priority' => 3]);
    }

    /**
     * 감정 감지 이벤트 발행
     *
     * @param int $userId 사용자 ID
     * @param string $emotion 감지된 감정
     * @param float $intensity 강도 (0-1)
     * @param array $keywords 감지된 키워드
     * @return bool
     */
    public function publishEmotionDetected(int $userId, string $emotion, float $intensity = 0.5, array $keywords = []): bool {
        return $this->publish('emotion.detected', [
            'user_id' => $userId,
            'emotion' => $emotion,
            'intensity' => $intensity,
            'keywords' => $keywords
        ], ['user_id' => $userId, 'priority' => 5]);
    }

    /**
     * 위기 상황 감지 이벤트 발행 (높은 우선순위)
     *
     * @param int $userId 사용자 ID
     * @param string $crisisType 위기 유형
     * @param string $description 설명
     * @param array $indicators 지표
     * @return bool
     */
    public function publishCrisisDetected(int $userId, string $crisisType, string $description, array $indicators = []): bool {
        return $this->publish('crisis.detected', [
            'user_id' => $userId,
            'crisis_type' => $crisisType,
            'description' => $description,
            'indicators' => $indicators,
            'severity' => 'high'
        ], ['user_id' => $userId, 'broadcast' => true, 'priority' => 0]); // 최고 우선순위
    }

    /**
     * 로컬 핸들러 실행
     *
     * @param string $eventName 이벤트 이름
     * @param array $event 이벤트 데이터
     * @return void
     */
    protected function executeLocalHandlers(string $eventName, array $event): void {
        // 정확히 일치하는 핸들러
        if (isset($this->handlers[$eventName])) {
            foreach ($this->handlers[$eventName] as $item) {
                try {
                    call_user_func($item['handler'], $event);
                } catch (Exception $e) {
                    $this->logError("핸들러 실행 실패 ({$eventName}): " . $e->getMessage(), __LINE__);
                }
            }
        }

        // 와일드카드 핸들러 (예: 'persona.*')
        foreach ($this->handlers as $pattern => $handlerList) {
            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace(['*', '.'], ['[^.]+', '\\.'], $pattern) . '$/';
                if (preg_match($regex, $eventName)) {
                    foreach ($handlerList as $item) {
                        try {
                            call_user_func($item['handler'], $event);
                        } catch (Exception $e) {
                            $this->logError("와일드카드 핸들러 실행 실패 ({$pattern}): " . $e->getMessage(), __LINE__);
                        }
                    }
                }
            }
        }
    }

    /**
     * 이벤트 로그 기록
     *
     * @param string $eventName 이벤트 이름
     * @param array $event 이벤트 데이터
     * @param array $options 옵션
     * @return void
     */
    protected function logEvent(string $eventName, array $event, array $options): void {
        global $DB;

        try {
            $record = new stdClass();
            $record->user_id = $options['user_id'] ?? 0;
            $record->agent_id = $this->agentId;
            $record->session_id = $options['session_id'] ?? null;
            $record->request_type = 'event_publish';
            $record->input_data = json_encode(['event_name' => $eventName, 'options' => $options], JSON_UNESCAPED_UNICODE);
            $record->persona_identified = $event['data']['persona_id'] ?? null;
            $record->confidence = $event['data']['confidence'] ?? null;
            $record->output_data = json_encode($event, JSON_UNESCAPED_UNICODE);
            $record->success = 1;
            $record->created_at = date('Y-m-d H:i:s');

            $DB->insert_record('at_persona_log', $record);

        } catch (Exception $e) {
            // 로깅 실패는 무시
            error_log("[{$this->agentId}:EventPublisher] 이벤트 로그 실패: " . $e->getMessage());
        }
    }

    /**
     * 에러 로깅
     */
    protected function logError(string $message, int $line): void {
        error_log("[{$this->agentId}:PersonaEventPublisher ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 이벤트 타입 목록 반환
     */
    public function getEventTypes(): array {
        return $this->eventTypes;
    }

    /**
     * 통신 모듈 반환
     */
    public function getCommunicator(): AgentCommunicator {
        return $this->communicator;
    }
}

/**
 * 관련 DB 테이블:
 * - mdl_at_persona_log: 이벤트 로그 기록
 *   - id: BIGINT PK
 *   - user_id: BIGINT
 *   - agent_id: VARCHAR(50)
 *   - request_type: VARCHAR(50) - 'event_publish' 등
 *   - input_data: JSON - 이벤트 입력 데이터
 *   - output_data: JSON - 이벤트 출력 데이터
 *   - created_at: DATETIME
 * 
 * - mdl_at_agent_messages: 브로드캐스트 메시지 저장
 */
