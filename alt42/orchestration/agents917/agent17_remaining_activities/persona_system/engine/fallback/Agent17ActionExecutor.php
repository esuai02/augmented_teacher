<?php
/**
 * Agent17ActionExecutor - 액션 실행기 Fallback 구현체
 *
 * BaseActionExecutor가 없을 경우 사용되는 Agent17 전용 액션 실행기
 * 규칙에서 정의된 액션을 실행합니다.
 *
 * @package AugmentedTeacher\Agent17\PersonaEngine\Fallback
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

// 인터페이스 로드
$corePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/';
require_once($corePath . 'IActionExecutor.php');

use AugmentedTeacher\PersonaEngine\Core\IActionExecutor;

/**
 * Agent17 전용 액션 실행기
 */
class Agent17ActionExecutor implements IActionExecutor {
    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 커스텀 액션 핸들러 */
    protected $customHandlers = [];

    /** @var array 기본 지원 액션 타입 */
    protected $defaultActions = [
        'identify_persona', 'set_tone', 'set_pace',
        'prioritize_intervention', 'trigger_notification',
        'update_state', 'log', 'noop'
    ];

    /**
     * 단일 액션 실행
     *
     * @param array $action 액션 배열 ['type', 'params']
     * @param array $context 컨텍스트 데이터
     * @return array 실행 결과 ['success', 'data', 'error']
     */
    public function execute(array $action, array $context): array {
        if (!$this->validateAction($action)) {
            return [
                'success' => false,
                'data' => null,
                'error' => '잘못된 액션 형식'
            ];
        }

        $type = $action['type'] ?? 'noop';
        $params = $action['params'] ?? [];

        try {
            // 커스텀 핸들러 확인
            if (isset($this->customHandlers[$type])) {
                $result = call_user_func($this->customHandlers[$type], $params, $context);
                return [
                    'success' => true,
                    'data' => $result,
                    'type' => $type
                ];
            }

            // 기본 액션 처리
            $data = $this->executeDefaultAction($type, $params, $context);

            return [
                'success' => true,
                'data' => $data,
                'type' => $type
            ];

        } catch (Exception $e) {
            error_log("[Agent17ActionExecutor] {$this->currentFile}:" . __LINE__ .
                " - 액션 실행 실패: " . $e->getMessage());
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 복수 액션 실행
     *
     * @param array $actions 액션 배열
     * @param array $context 컨텍스트 데이터
     * @return array 각 액션의 실행 결과 배열
     */
    public function executeAll(array $actions, array $context): array {
        $results = [];
        foreach ($actions as $index => $action) {
            // 문자열 액션은 noop 타입으로 변환
            if (is_string($action)) {
                $action = ['type' => 'noop', 'params' => ['value' => $action]];
            }
            $results[$index] = $this->execute($action, $context);
        }
        return $results;
    }

    /**
     * 커스텀 액션 핸들러 등록
     *
     * @param string $actionType 액션 타입 이름
     * @param callable $handler 핸들러 함수
     */
    public function registerHandler(string $actionType, callable $handler): void {
        $this->customHandlers[$actionType] = $handler;
    }

    /**
     * 지원 액션 타입 목록 반환
     *
     * @return array 액션 타입 배열
     */
    public function getSupportedActions(): array {
        return array_merge($this->defaultActions, array_keys($this->customHandlers));
    }

    /**
     * 액션 유효성 검사
     *
     * @param array $action 검사할 액션
     * @return bool 유효 여부
     */
    public function validateAction(array $action): bool {
        // 최소한 type이 있어야 함
        if (!isset($action['type']) || !is_string($action['type'])) {
            return false;
        }

        // params가 있으면 배열이어야 함
        if (isset($action['params']) && !is_array($action['params'])) {
            return false;
        }

        return true;
    }

    /**
     * 기본 액션 실행
     *
     * @param string $type 액션 타입
     * @param array $params 파라미터
     * @param array $context 컨텍스트
     * @return mixed 실행 결과
     */
    protected function executeDefaultAction(string $type, array $params, array $context) {
        switch ($type) {
            case 'identify_persona':
                return [
                    'persona_id' => $params['persona_id'] ?? 'R2_P1',
                    'confidence' => $params['confidence'] ?? 0.8
                ];

            case 'set_tone':
                return ['tone' => $params['tone'] ?? 'Professional'];

            case 'set_pace':
                return ['pace' => $params['pace'] ?? 'normal'];

            case 'prioritize_intervention':
                return ['intervention' => $params['intervention'] ?? 'InformationProvision'];

            case 'trigger_notification':
                // 알림 발송 로직 (실제로는 DB에 기록)
                error_log("[Agent17ActionExecutor] {$this->currentFile}:" . __LINE__ .
                    " - 알림 발송: " . json_encode($params));
                return ['notified' => true, 'channel' => $params['channel'] ?? 'database'];

            case 'update_state':
                return ['updated' => true, 'fields' => array_keys($params)];

            case 'log':
                error_log("[Agent17ActionExecutor] {$this->currentFile}:" . __LINE__ .
                    " - Action log: " . ($params['message'] ?? 'no message'));
                return ['logged' => true];

            case 'noop':
            default:
                return ['value' => $params['value'] ?? null];
        }
    }
}

/*
 * 관련 인터페이스: IActionExecutor
 * 위치: /ontology_engineering/persona_engine/core/IActionExecutor.php
 *
 * 메서드:
 * - execute(array $action, array $context): array
 * - executeAll(array $actions, array $context): array
 * - registerHandler(string $actionType, callable $handler): void
 * - getSupportedActions(): array
 * - validateAction(array $action): bool
 *
 * 기본 지원 액션:
 * - identify_persona: 페르소나 식별
 * - set_tone: 응답 톤 설정
 * - set_pace: 진행 속도 설정
 * - prioritize_intervention: 개입 우선순위 설정
 * - trigger_notification: 알림 발송
 * - update_state: 상태 업데이트
 * - log: 로그 기록
 * - noop: 아무 동작 없음
 */
