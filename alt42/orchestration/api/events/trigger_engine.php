<?php
/**
 * trigger_engine.php
 *
 * 이벤트를 규칙(trigger_rules.yaml)에 따라 평가하고,
 * 매칭된 경우 AgentOrchestrator(Lib)를 통해 에이전트를 실행한다.
 *
 * @package ALT42\Events
 * @version 1.0.0
 */

require_once(__DIR__ . '/../../agents_1204/engine_core/orchestration/AgentOrchestratorLib.php');

class TriggerRuleEngine {
    /** @var int */
    private $studentId;

    /** @var array */
    private $rules = [];

    /** @var AgentOrchestrator */
    private $orchestrator;

    public function __construct(int $studentId) {
        $this->studentId = $studentId;
        $this->orchestrator = new AgentOrchestrator($studentId);
        $this->rules = $this->loadRules();
    }

    /**
     * 이벤트 평가: 매칭된 트리거 목록 반환
     * @param array $event normalized event
     * @return array matched triggers
     */
    public function evaluate(array $event): array {
        $matched = [];

        foreach ($this->rules as $rule) {
            if (!$this->matchRule($rule, $event)) continue;

            $matched[] = [
                'rule_id' => $rule['id'],
                'agent_id' => (int)$rule['agent_id'],
                'priority' => (int)($rule['priority'] ?? 5),
                'event' => $event
            ];
        }

        usort($matched, function($a, $b) {
            return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
        });

        return $matched;
    }

    /**
     * 트리거 실행 (단일 에이전트)
     * @param array $trigger
     * @return array execution result
     */
    public function execute(array $trigger): array {
        $agentId = (int)($trigger['agent_id'] ?? 0);
        if ($agentId <= 0 || $agentId > 22) {
            return [
                'success' => false,
                'error' => "Invalid agent_id {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]"
            ];
        }

        $options = [
            'trigger_rule' => $trigger['rule_id'] ?? null,
            'trigger_event' => $trigger['event'] ?? null
        ];

        $result = $this->orchestrator->executeAgent($agentId, $options);
        return $result;
    }

    // =========================================================================
    // Rule loading / parsing
    // =========================================================================

    private function loadRules(): array {
        $path = __DIR__ . '/trigger_rules.yaml';
        if (!file_exists($path)) {
            return $this->defaultRules();
        }

        $parsed = $this->parseSimpleYamlRules($path);
        if (!empty($parsed)) return $parsed;

        // yaml extension fallback
        if (function_exists('yaml_parse_file')) {
            $y = @yaml_parse_file($path);
            if (is_array($y)) {
                $rules = $y['rules'] ?? $y;
                if (is_array($rules)) return $rules;
            }
        }

        return $this->defaultRules();
    }

    /**
     * 매우 단순한 YAML 파서 (trigger_rules.yaml 전용)
     * - rules: 아래에 "- id: ..." 형태만 지원
     * - conditions는 "conditions:" 아래에 "- field/operator/value"만 지원
     */
    private function parseSimpleYamlRules(string $path): array {
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) return [];

        $rules = [];
        $cur = null;
        $inRules = false;
        $inConditions = false;

        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || strpos($trim, '#') === 0) continue;

            if ($trim === 'rules:' || $trim === 'rules:') {
                $inRules = true;
                continue;
            }

            if (!$inRules) continue;

            if (strpos($trim, '- ') === 0 && strpos($trim, '- id:') === 0) {
                if (is_array($cur)) $rules[] = $cur;
                $cur = ['conditions' => []];
                $inConditions = false;
                $cur['id'] = trim(substr($trim, strlen('- id:')));
                continue;
            }

            if (!is_array($cur)) continue;

            if ($trim === 'conditions:' ) {
                $inConditions = true;
                continue;
            }

            // key: value
            if (!$inConditions) {
                if (preg_match('/^([a-zA-Z0-9_]+):\s*(.+)$/', $trim, $m)) {
                    $k = $m[1];
                    $v = trim($m[2], " \t\"");
                    if ($k === 'agent_id' || $k === 'priority') {
                        $cur[$k] = (int)$v;
                    } else {
                        $cur[$k] = $v;
                    }
                }
                continue;
            }

            // condition item start
            if (strpos($trim, '- ') === 0) {
                $cur['conditions'][] = [];
                // possibly inline field
                $rest = trim(substr($trim, 2));
                if (preg_match('/^field:\s*(.+)$/', $rest, $m)) {
                    $cur['conditions'][count($cur['conditions']) - 1]['field'] = trim($m[1], " \t\"");
                }
                continue;
            }

            // condition properties (indented in YAML)
            if (preg_match('/^(field|operator|value):\s*(.+)$/', $trim, $m)) {
                $idx = count($cur['conditions']) - 1;
                if ($idx >= 0) {
                    $key = $m[1];
                    $val = trim($m[2], " \t\"");
                    if ($key === 'value' && is_numeric($val)) $val = $val + 0;
                    $cur['conditions'][$idx][$key] = $val;
                }
            }
        }

        if (is_array($cur)) $rules[] = $cur;

        // 최소 필수 필드 검증
        $out = [];
        foreach ($rules as $r) {
            if (empty($r['id']) || empty($r['event_type']) || empty($r['agent_id'])) continue;
            if (!isset($r['conditions']) || !is_array($r['conditions'])) $r['conditions'] = [];
            $out[] = $r;
        }
        return $out;
    }

    private function defaultRules(): array {
        return [
            [
                'id' => 'rule_problem_wrong_agent11',
                'event_type' => 'problem_wrong',
                'agent_id' => 11,
                'priority' => 9,
                'conditions' => []
            ],
            [
                'id' => 'rule_consecutive_wrong_3_agent05',
                'event_type' => 'problem_wrong',
                'agent_id' => 5,
                'priority' => 9,
                'conditions' => [
                    ['field' => 'data.consecutive_wrong_count', 'operator' => '>=', 'value' => 3]
                ]
            ],
            [
                'id' => 'rule_consecutive_wrong_5_agent13',
                'event_type' => 'problem_wrong',
                'agent_id' => 13,
                'priority' => 10,
                'conditions' => [
                    ['field' => 'data.consecutive_wrong_count', 'operator' => '>=', 'value' => 5]
                ]
            ],
        ];
    }

    private function matchRule(array $rule, array $event): bool {
        if (($rule['event_type'] ?? null) !== ($event['event_type'] ?? null)) return false;

        $conds = $rule['conditions'] ?? [];
        if (!is_array($conds) || empty($conds)) return true;

        foreach ($conds as $c) {
            if (!$this->evalCondition($c, $event)) return false;
        }
        return true;
    }

    private function evalCondition(array $cond, array $event): bool {
        $field = $cond['field'] ?? null;
        $op = $cond['operator'] ?? '==';
        $val = $cond['value'] ?? null;
        if (!$field) return true;

        $fv = $this->getFieldValue($event, $field);

        switch ($op) {
            case '==': return $fv == $val;
            case '!=': return $fv != $val;
            case '>': return $fv > $val;
            case '>=': return $fv >= $val;
            case '<': return $fv < $val;
            case '<=': return $fv <= $val;
            case 'in':
                if (!is_array($val)) $val = [$val];
                return in_array($fv, $val, true);
            default:
                return false;
        }
    }

    private function getFieldValue(array $event, string $path) {
        $parts = explode('.', $path);
        $cur = $event;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
            } else {
                return null;
            }
        }
        return $cur;
    }
}


