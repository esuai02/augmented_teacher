<?php
/**
 * BaseActionExecutor - 액션 실행 기본 구현체
 *
 * 매칭된 규칙의 액션을 실행하는 기본 구현체입니다.
 * 표준 액션 타입과 커스텀 액션 핸들러 등록을 지원합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Impl;

use AugmentedTeacher\PersonaEngine\Core\IActionExecutor;

class BaseActionExecutor implements IActionExecutor {

    /** @var array 커스텀 액션 핸들러 */
    protected $handlers = [];

    /** @var string 현재 파일 경로 (디버깅용) */
    protected $currentFile = __FILE__;

    /**
     * 생성자
     */
    public function __construct() {
        $this->registerDefaultHandlers();
    }

    /**
     * 기본 액션 핸들러 등록
     */
    protected function registerDefaultHandlers(): void {
        // identify_persona: 페르소나 ID 설정
        $this->registerHandler('identify_persona', function($value, &$context) {
            $context['persona_id'] = $value;
            return "identify_persona:{$value}";
        });

        // set_tone: 응답 톤 설정
        $this->registerHandler('set_tone', function($value, &$context) {
            $context['tone'] = $value;
            return "set_tone:{$value}";
        });

        // set_pace: 응답 페이스 설정
        $this->registerHandler('set_pace', function($value, &$context) {
            $context['pace'] = $value;
            return "set_pace:{$value}";
        });

        // prioritize_intervention: 개입 유형 설정
        $this->registerHandler('prioritize_intervention', function($value, &$context) {
            $context['intervention'] = $value;
            return "prioritize_intervention:{$value}";
        });

        // set_information_depth: 정보 깊이 설정
        $this->registerHandler('set_information_depth', function($value, &$context) {
            $context['information_depth'] = $value;
            return "set_information_depth:{$value}";
        });

        // add_flag: 컨텍스트에 플래그 추가
        $this->registerHandler('add_flag', function($value, &$context) {
            if (!isset($context['flags'])) {
                $context['flags'] = [];
            }
            $context['flags'][] = $value;
            return "add_flag:{$value}";
        });

        // set_risk_level: 위험 등급 설정 (agent13용)
        $this->registerHandler('set_risk_level', function($value, &$context) {
            $context['risk_level'] = $value;
            return "set_risk_level:{$value}";
        });

        // set_dropout_cause: 이탈 원인 설정 (agent13용)
        $this->registerHandler('set_dropout_cause', function($value, &$context) {
            $context['dropout_cause'] = $value;
            return "set_dropout_cause:{$value}";
        });

        // suggest_action: 개입 액션 제안 (agent13용)
        $this->registerHandler('suggest_action', function($value, &$context) {
            if (!isset($context['suggested_actions'])) {
                $context['suggested_actions'] = [];
            }
            $context['suggested_actions'][] = $value;
            return "suggest_action:{$value}";
        });

        // set_priority: 우선순위 설정
        $this->registerHandler('set_priority', function($value, &$context) {
            $context['priority'] = $value;
            return "set_priority:{$value}";
        });

        // log_event: 이벤트 로깅
        $this->registerHandler('log_event', function($value, &$context) {
            $context['logged_event'] = $value;
            return "log_event:{$value}";
        });

        // trigger_notification: 알림 트리거
        $this->registerHandler('trigger_notification', function($value, &$context) {
            $context['notification'] = $value;
            return "trigger_notification:{$value}";
        });

        // set_template: 응답 템플릿 설정
        $this->registerHandler('set_template', function($value, &$context) {
            $context['template_key'] = $value;
            return "set_template:{$value}";
        });

        // set_variable: 컨텍스트 변수 설정
        $this->registerHandler('set_variable', function($value, &$context) {
            if (is_array($value) && isset($value['key']) && isset($value['value'])) {
                $context[$value['key']] = $value['value'];
                return "set_variable:{$value['key']}={$value['value']}";
            }
            return "set_variable:invalid";
        });
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $actions, array &$context): array {
        $results = [];

        foreach ($actions as $action) {
            $result = $this->executeAction($action, $context);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * 단일 액션 실행
     *
     * @param mixed $action 액션 (문자열 또는 배열)
     * @param array &$context 컨텍스트 (참조)
     * @return string|null 실행 결과
     */
    protected function executeAction($action, array &$context): ?string {
        // 문자열 형식: "action_name: value"
        if (is_string($action)) {
            return $this->executeStringAction($action, $context);
        }

        // 배열 형식: ['action_name' => 'value']
        if (is_array($action)) {
            return $this->executeArrayAction($action, $context);
        }

        return null;
    }

    /**
     * 문자열 형식 액션 실행
     *
     * @param string $action 액션 문자열
     * @param array &$context 컨텍스트
     * @return string|null 실행 결과
     */
    protected function executeStringAction(string $action, array &$context): ?string {
        // "action_name: value" 형식 파싱
        $parts = explode(':', $action, 2);
        if (count($parts) < 2) {
            $this->logWarning("잘못된 액션 형식: {$action}", __LINE__);
            return null;
        }

        $actionName = trim($parts[0]);
        $value = trim($parts[1], " \t\n\r\0\x0B\"'");

        return $this->executeHandler($actionName, $value, $context);
    }

    /**
     * 배열 형식 액션 실행
     *
     * @param array $action 액션 배열
     * @param array &$context 컨텍스트
     * @return string|null 실행 결과
     */
    protected function executeArrayAction(array $action, array &$context): ?string {
        $results = [];

        foreach ($action as $actionName => $value) {
            $result = $this->executeHandler($actionName, $value, $context);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return !empty($results) ? implode('; ', $results) : null;
    }

    /**
     * 핸들러 실행
     *
     * @param string $actionName 액션 이름
     * @param mixed $value 값
     * @param array &$context 컨텍스트
     * @return string|null 실행 결과
     */
    protected function executeHandler(string $actionName, $value, array &$context): ?string {
        if (isset($this->handlers[$actionName])) {
            return call_user_func_array($this->handlers[$actionName], [$value, &$context]);
        }

        // 핸들러가 없으면 기본 동작 (컨텍스트에 직접 설정)
        $context[$actionName] = $value;
        $this->logWarning("등록되지 않은 액션, 기본 처리: {$actionName}", __LINE__);
        return "{$actionName}:{$value}";
    }

    /**
     * {@inheritdoc}
     */
    public function registerHandler(string $actionName, callable $handler): void {
        $this->handlers[$actionName] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegisteredHandlers(): array {
        return array_keys($this->handlers);
    }

    /**
     * 경고 로깅
     */
    protected function logWarning(string $message, int $line): void {
        error_log("[ActionExecutor WARN] {$this->currentFile}:{$line} - {$message}");
    }
}

/*
 * 사용 예시:
 *
 * $executor = new BaseActionExecutor();
 * $context = ['user_id' => 123];
 *
 * // 액션 실행
 * $actions = [
 *     'identify_persona: R_High_M',
 *     'set_tone: Warm',
 *     'set_risk_level: High',
 *     'suggest_action: urgent_intervention'
 * ];
 *
 * $results = $executor->execute($actions, $context);
 * // $context는 이제 persona_id, tone, risk_level, suggested_actions 포함
 *
 * // 커스텀 핸들러 등록
 * $executor->registerHandler('send_alert', function($value, &$context) {
 *     // 알림 전송 로직
 *     $context['alert_sent'] = true;
 *     return "send_alert:{$value}";
 * });
 *
 * 기본 액션 타입:
 * - identify_persona: 페르소나 ID 설정
 * - set_tone: 응답 톤 (Warm, Professional, Encouraging 등)
 * - set_pace: 응답 페이스 (slow, normal, fast)
 * - prioritize_intervention: 개입 유형
 * - set_risk_level: 위험 등급 (Low, Medium, High)
 * - set_dropout_cause: 이탈 원인 (M, R, S, E)
 * - suggest_action: 개입 액션 제안
 * - add_flag: 플래그 추가
 * - trigger_notification: 알림 트리거
 *
 * 파일 위치: ontology_engineering/persona_engine/impl/BaseActionExecutor.php
 */
