<?php
/**
 * BaseActionExecutor - 기본 액션 실행기 구현
 *
 * IActionExecutor 인터페이스의 기본 구현체
 * 핵심 액션 타입 제공, 커스텀 핸들러 확장 지원
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @author Claude Code
 */

require_once(__DIR__ . '/../core/IActionExecutor.php');

class BaseActionExecutor implements IActionExecutor {

    /** @var array 액션 핸들러 */
    private $handlers = [];

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 생성자 - 기본 핸들러 등록
     */
    public function __construct(bool $debugMode = false) {
        $this->debugMode = $debugMode;
        $this->registerDefaultHandlers();
    }

    /**
     * 기본 핸들러 등록
     */
    private function registerDefaultHandlers(): void {
        // 페르소나 식별
        $this->handlers['identify_persona'] = function(array $params, array $context): array {
            return [
                'success' => true,
                'persona_id' => $params['persona_id'] ?? 'default',
                'persona_name' => $params['persona_name'] ?? 'Default'
            ];
        };

        // 톤 설정
        $this->handlers['set_tone'] = function(array $params, array $context): array {
            $validTones = ['Professional', 'Friendly', 'Encouraging', 'Empathetic', 
                           'Supportive', 'Directive', 'Informative', 'Casual'];
            $tone = $params['tone'] ?? 'Professional';
            
            return [
                'success' => true,
                'tone' => in_array($tone, $validTones) ? $tone : 'Professional'
            ];
        };

        // 속도 설정
        $this->handlers['set_pace'] = function(array $params, array $context): array {
            $validPaces = ['Slow', 'Normal', 'Fast', 'Adaptive'];
            $pace = $params['pace'] ?? 'Normal';
            
            return [
                'success' => true,
                'pace' => in_array($pace, $validPaces) ? $pace : 'Normal'
            ];
        };

        // 개입 전략 우선순위
        $this->handlers['prioritize_intervention'] = function(array $params, array $context): array {
            $interventions = $params['interventions'] ?? ['InformationProvision'];
            
            return [
                'success' => true,
                'intervention' => is_array($interventions) ? $interventions[0] : $interventions,
                'all_interventions' => (array)$interventions
            ];
        };

        // 상태 업데이트
        $this->handlers['update_state'] = function(array $params, array $context): array {
            $key = $params['key'] ?? null;
            $value = $params['value'] ?? null;
            
            if (!$key) {
                return ['success' => false, 'error' => 'key is required'];
            }
            
            return [
                'success' => true,
                'updated' => [$key => $value]
            ];
        };

        // 알림 발송
        $this->handlers['trigger_notification'] = function(array $params, array $context): array {
            return [
                'success' => true,
                'notification_type' => $params['type'] ?? 'info',
                'message' => $params['message'] ?? '',
                'recipient' => $params['recipient'] ?? 'system'
            ];
        };

        // 로깅
        $this->handlers['log_event'] = function(array $params, array $context): array {
            $event = $params['event'] ?? 'generic_event';
            $data = $params['data'] ?? [];
            
            error_log("[PersonaEngine Event] {$event}: " . json_encode($data));
            
            return [
                'success' => true,
                'logged' => true
            ];
        };

        // 컨텍스트 플래그 설정
        $this->handlers['set_flag'] = function(array $params, array $context): array {
            return [
                'success' => true,
                'flag' => $params['name'] ?? 'unknown',
                'value' => $params['value'] ?? true
            ];
        };
    }

    /**
     * @inheritDoc
     */
    public function execute(array $action, array $context): array {
        $type = $action['type'] ?? null;
        $params = $action['params'] ?? [];

        if (!$type) {
            throw new \RuntimeException(
                "[{$this->currentFile}:" . __LINE__ . "] 액션 type이 필요합니다"
            );
        }

        if (!isset($this->handlers[$type])) {
            if ($this->debugMode) {
                error_log("[BaseActionExecutor] 알 수 없는 액션 타입: {$type}");
            }
            return [
                'success' => false,
                'error' => "지원하지 않는 액션: {$type}"
            ];
        }

        try {
            $result = $this->handlers[$type]($params, $context);
            
            if ($this->debugMode) {
                error_log("[BaseActionExecutor DEBUG] {$type} => " . json_encode($result));
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("[BaseActionExecutor ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 액션 실행 실패: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function executeAll(array $actions, array $context): array {
        $results = [];
        $aggregated = [
            'tone' => 'Professional',
            'pace' => 'Normal',
            'intervention' => 'InformationProvision'
        ];

        foreach ($actions as $action) {
            $result = $this->execute($action, $context);
            $results[] = $result;

            // 결과 집계
            if ($result['success']) {
                if (isset($result['tone'])) {
                    $aggregated['tone'] = $result['tone'];
                }
                if (isset($result['pace'])) {
                    $aggregated['pace'] = $result['pace'];
                }
                if (isset($result['intervention'])) {
                    $aggregated['intervention'] = $result['intervention'];
                }
            }
        }

        return array_merge($aggregated, ['all_results' => $results]);
    }

    /**
     * @inheritDoc
     */
    public function registerHandler(string $actionType, callable $handler): void {
        $this->handlers[$actionType] = $handler;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedActions(): array {
        return array_keys($this->handlers);
    }

    /**
     * @inheritDoc
     */
    public function validateAction(array $action): bool {
        if (!isset($action['type'])) {
            return false;
        }
        return isset($this->handlers[$action['type']]);
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state (update_state 액션 시)
 * - at_agent_messages (trigger_notification 액션 시)
 *
 * 참조 파일:
 * - core/IActionExecutor.php (인터페이스)
 * - agents/agent01_onboarding/persona_system/engine/ActionExecutor.php (원본)
 */
