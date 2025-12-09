<?php
/**
 * ActionExecutor - 액션 실행기 구현
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 */

require_once(__DIR__ . '/../core/IActionExecutor.php');

class ActionExecutor implements IActionExecutor {

    /** @var array 액션 핸들러 */
    private $handlers = [];

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var bool 로그 활성화 */
    private $logEnabled = true;

    /**
     * 생성자 - 기본 핸들러 등록
     */
    public function __construct() {
        $this->registerDefaultHandlers();
    }

    /**
     * 기본 핸들러 등록
     */
    private function registerDefaultHandlers(): void {
        // 로그 액션
        $this->handlers['log'] = function($action, $context) {
            $message = $action['message'] ?? 'Action executed';
            $level = $action['level'] ?? 'info';
            error_log("[PersonaAction:{$level}] {$message}");
            return ['success' => true, 'type' => 'log'];
        };

        // 컨텍스트 업데이트 액션
        $this->handlers['update_context'] = function($action, $context) {
            global $DB;
            $updates = $action['data'] ?? [];
            $userId = $context['userid'] ?? $context['user_id'] ?? null;
            
            if ($userId && !empty($updates)) {
                // 실제 DB 업데이트는 에이전트별로 구현
                return ['success' => true, 'type' => 'update_context', 'updates' => $updates];
            }
            return ['success' => false, 'type' => 'update_context', 'error' => 'No user or data'];
        };

        // 이벤트 트리거 액션
        $this->handlers['trigger_event'] = function($action, $context) {
            $eventName = $action['event'] ?? 'unknown';
            $eventData = $action['data'] ?? [];
            // 이벤트 시스템 연동 (에이전트별 구현)
            return ['success' => true, 'type' => 'trigger_event', 'event' => $eventName];
        };

        // 알림 액션
        $this->handlers['notify'] = function($action, $context) {
            $target = $action['target'] ?? 'user';
            $message = $action['message'] ?? '';
            $channel = $action['channel'] ?? 'internal';
            // 알림 시스템 연동 (에이전트별 구현)
            return ['success' => true, 'type' => 'notify', 'target' => $target, 'channel' => $channel];
        };

        // 메시지 전송 액션
        $this->handlers['send_message'] = function($action, $context) {
            $recipient = $action['recipient'] ?? null;
            $message = $action['message'] ?? '';
            // 메시지 시스템 연동 (에이전트별 구현)
            return ['success' => true, 'type' => 'send_message', 'recipient' => $recipient];
        };

        // 예약 작업 액션
        $this->handlers['schedule'] = function($action, $context) {
            $task = $action['task'] ?? '';
            $delay = $action['delay'] ?? 0;
            $time = $action['time'] ?? null;
            // 스케줄러 연동 (에이전트별 구현)
            return ['success' => true, 'type' => 'schedule', 'task' => $task];
        };

        // 에이전트 간 통신 액션
        $this->handlers['agent_message'] = function($action, $context) {
            $targetAgent = $action['target_agent'] ?? null;
            $message = $action['message'] ?? [];
            // 에이전트 통신 시스템 연동
            return ['success' => true, 'type' => 'agent_message', 'target' => $targetAgent];
        };

        // 페르소나 전환 액션
        $this->handlers['switch_persona'] = function($action, $context) {
            $newPersona = $action['persona_id'] ?? null;
            // 페르소나 전환 로직
            return ['success' => true, 'type' => 'switch_persona', 'new_persona' => $newPersona];
        };
    }

    /**
     * 단일 액션 실행
     */
    public function execute(array $action, array $context): array {
        $actionType = $action['type'] ?? 'unknown';

        if (!$this->canExecute($action, $context)) {
            return [
                'success' => false,
                'type' => $actionType,
                'error' => "액션 실행 불가: 조건 미충족 또는 핸들러 없음 [{$this->currentFile}:" . __LINE__ . "]"
            ];
        }

        try {
            $handler = $this->handlers[$actionType];
            $result = $handler($action, $context);

            // 로그 기록
            if ($this->logEnabled) {
                $this->logAction($actionType, $action, $result, $context);
            }

            return $result;

        } catch (Exception $e) {
            error_log("[ActionExecutor ERROR] {$actionType}: {$e->getMessage()} [{$this->currentFile}:" . __LINE__ . "]");
            return [
                'success' => false,
                'type' => $actionType,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 다중 액션 실행
     */
    public function executeAll(array $actions, array $context): array {
        $results = [];

        foreach ($actions as $idx => $action) {
            $results[$idx] = $this->execute($action, $context);

            // 실패시 중단 옵션
            if (($action['stop_on_error'] ?? false) && !$results[$idx]['success']) {
                break;
            }
        }

        return $results;
    }

    /**
     * 액션 실행 가능 여부 확인
     */
    public function canExecute(array $action, array $context): bool {
        $actionType = $action['type'] ?? 'unknown';

        // 핸들러 존재 확인
        if (!isset($this->handlers[$actionType])) {
            return false;
        }

        // 조건 확인 (액션에 condition이 있는 경우)
        if (isset($action['condition'])) {
            // 간단 조건 평가
            $conditionField = $action['condition']['field'] ?? null;
            $conditionValue = $action['condition']['value'] ?? null;
            
            if ($conditionField) {
                $actualValue = $this->getContextValue($conditionField, $context);
                if ($actualValue !== $conditionValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 액션 핸들러 등록
     */
    public function registerHandler(string $actionType, callable $handler): void {
        $this->handlers[$actionType] = $handler;
    }

    /**
     * 컨텍스트에서 필드 값 추출
     */
    private function getContextValue(string $field, array $context) {
        $keys = explode('.', $field);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 액션 로그 기록
     */
    private function logAction(string $type, array $action, array $result, array $context): void {
        global $DB;

        try {
            $logData = new stdClass();
            $logData->action_type = $type;
            $logData->action_data = json_encode($action);
            $logData->result = json_encode($result);
            $logData->user_id = $context['userid'] ?? $context['user_id'] ?? 0;
            $logData->created_at = time();

            // mdl_at_persona_action_log 테이블이 있으면 기록
            // $DB->insert_record('at_persona_action_log', $logData);

        } catch (Exception $e) {
            error_log("[ActionExecutor] 로그 기록 실패: {$e->getMessage()}");
        }
    }

    /**
     * 로그 활성화 설정
     */
    public function setLogEnabled(bool $enabled): void {
        $this->logEnabled = $enabled;
    }
}

/*
 * 지원 액션 타입:
 * - log : 로그 기록
 * - update_context : 컨텍스트 업데이트
 * - trigger_event : 이벤트 트리거
 * - notify : 알림 발송
 * - send_message : 메시지 전송
 * - schedule : 작업 예약
 * - agent_message : 에이전트 간 통신
 * - switch_persona : 페르소나 전환
 *
 * 관련 DB 테이블:
 * - mdl_at_persona_action_log (액션 실행 로그)
 */
