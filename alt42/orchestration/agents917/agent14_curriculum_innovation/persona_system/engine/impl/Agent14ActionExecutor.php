<?php
/**
 * Agent14ActionExecutor - Agent14 액션 실행기
 *
 * Agent14 전용 액션 실행 구현
 *
 * @package AugmentedTeacher\Agent14\PersonaEngine\Impl
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

require_once(__DIR__ . '/../../../../../ontology_engineering/persona_engine/core/IActionExecutor.php');

class Agent14ActionExecutor implements IActionExecutor {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var array 등록된 핸들러 */
    protected $handlers = [];

    /** @var array 실행 로그 */
    protected $executionLog = [];

    /**
     * 생성자 - 기본 핸들러 등록
     */
    public function __construct() {
        $this->registerDefaultHandlers();
    }

    /**
     * 기본 핸들러 등록
     */
    protected function registerDefaultHandlers(): void {
        // 페르소나 식별
        $this->registerHandler('identify_persona', function($params, $context) {
            return ['persona' => $params['persona'] ?? $params];
        });

        // 톤 설정
        $this->registerHandler('set_tone', function($params, $context) {
            return ['tone' => $params['tone'] ?? $params];
        });

        // 페이스 설정
        $this->registerHandler('set_pace', function($params, $context) {
            return ['pace' => $params['pace'] ?? $params];
        });

        // 개입 우선순위
        $this->registerHandler('prioritize_intervention', function($params, $context) {
            return ['intervention' => $params['type'] ?? $params];
        });

        // 플래그 추가
        $this->registerHandler('add_flag', function($params, $context) {
            return ['flag' => $params['flag'] ?? $params];
        });

        // 컨텍스트 업데이트
        $this->registerHandler('update_context', function($params, $context) {
            return ['context_update' => $params];
        });

        // 로그 기록
        $this->registerHandler('log_action', function($params, $context) {
            error_log("[Agent14ActionExecutor] Action logged: " . json_encode($params));
            return ['logged' => true];
        });
    }

    /**
     * 액션 목록 실행
     *
     * @param array $actions 실행할 액션 목록
     * @param array $context 현재 컨텍스트
     * @return array 실행 결과
     */
    public function execute(array $actions, array $context = []): array {
        $results = [];
        $this->executionLog = [];

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
     * @param string|array $action 액션
     * @param array $context 컨텍스트
     * @return mixed 실행 결과
     */
    protected function executeAction($action, array $context) {
        $startTime = microtime(true);
        $actionName = '';
        $params = [];

        try {
            // 액션 파싱
            if (is_string($action)) {
                // 문자열 형태: "action_name:param1,param2" 또는 "action_name"
                if (strpos($action, ':') !== false) {
                    list($actionName, $paramStr) = explode(':', $action, 2);
                    $params = $this->parseParams($paramStr);
                } else {
                    $actionName = $action;
                }
            } elseif (is_array($action)) {
                $actionName = $action['action'] ?? $action['name'] ?? '';
                $params = $action['params'] ?? $action;
            }

            $actionName = trim($actionName);

            // 핸들러 실행
            if (isset($this->handlers[$actionName])) {
                $result = call_user_func($this->handlers[$actionName], $params, $context);
            } else {
                // 기본 처리 - 액션 이름을 그대로 반환
                $result = $actionName . (is_array($params) ? ':' . implode(',', $params) : '');
            }

            // 로그 기록
            $this->executionLog[] = [
                'action' => $actionName,
                'params' => $params,
                'result' => $result,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success' => true
            ];

            return $result;

        } catch (Exception $e) {
            error_log("[Agent14ActionExecutor] {$this->currentFile}:" . __LINE__ .
                " - 액션 실행 실패 ({$actionName}): " . $e->getMessage());

            $this->executionLog[] = [
                'action' => $actionName,
                'params' => $params,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success' => false
            ];

            return null;
        }
    }

    /**
     * 파라미터 문자열 파싱
     *
     * @param string $paramStr 파라미터 문자열
     * @return array 파싱된 파라미터
     */
    protected function parseParams(string $paramStr): array {
        $params = [];
        $parts = explode(',', $paramStr);

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $params[trim($key)] = trim($value);
            } else {
                $params[] = $part;
            }
        }

        return $params;
    }

    /**
     * 커스텀 액션 핸들러 등록
     *
     * @param string $actionName 액션 이름
     * @param callable $handler 핸들러 함수
     */
    public function registerHandler(string $actionName, callable $handler): void {
        $this->handlers[$actionName] = $handler;
    }

    /**
     * 핸들러 존재 여부 확인
     *
     * @param string $actionName 액션 이름
     * @return bool 존재 여부
     */
    public function hasHandler(string $actionName): bool {
        return isset($this->handlers[$actionName]);
    }

    /**
     * 등록된 핸들러 목록 반환
     *
     * @return array 핸들러 이름 목록
     */
    public function getRegisteredHandlers(): array {
        return array_keys($this->handlers);
    }

    /**
     * 실행 로그 반환
     *
     * @return array 실행 로그
     */
    public function getExecutionLog(): array {
        return $this->executionLog;
    }

    /**
     * 핸들러 제거
     *
     * @param string $actionName 액션 이름
     * @return bool 제거 성공 여부
     */
    public function removeHandler(string $actionName): bool {
        if (isset($this->handlers[$actionName])) {
            unset($this->handlers[$actionName]);
            return true;
        }
        return false;
    }
}
