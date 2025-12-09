<?php
/**
 * Agent14RuleParser - Agent14 규칙 파서
 *
 * Agent14 전용 YAML 규칙 파싱 구현
 *
 * @package AugmentedTeacher\Agent14\PersonaEngine\Impl
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

require_once(__DIR__ . '/../../../../../ontology_engineering/persona_engine/core/IRuleParser.php');

class Agent14RuleParser implements IRuleParser {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /**
     * 규칙 파일 파싱
     *
     * @param string $filePath 규칙 파일 경로
     * @return array 파싱된 규칙 배열
     */
    public function parseRules(string $filePath): array {
        if (!file_exists($filePath)) {
            error_log("[Agent14RuleParser] {$this->currentFile}:" . __LINE__ .
                " - 규칙 파일을 찾을 수 없음: {$filePath}");
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            error_log("[Agent14RuleParser] {$this->currentFile}:" . __LINE__ .
                " - 파일 읽기 실패: {$filePath}");
            return [];
        }

        // YAML 파싱 시도
        if (function_exists('yaml_parse')) {
            $parsed = @yaml_parse($content);
            if ($parsed !== false && is_array($parsed)) {
                return $parsed['rules'] ?? [];
            }
        }

        // 간단한 파서 폴백
        return $this->simpleYamlParse($content);
    }

    /**
     * 간단한 YAML 파서 (폴백)
     *
     * @param string $content YAML 내용
     * @return array 파싱된 규칙
     */
    protected function simpleYamlParse(string $content): array {
        $rules = [];
        $lines = explode("\n", $content);
        $currentRule = null;
        $inConditions = false;
        $inActions = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }

            // 규칙 시작
            if (preg_match('/^-\s*id:\s*(.+)$/', $trimmed, $m)) {
                if ($currentRule) {
                    $rules[] = $currentRule;
                }
                $currentRule = ['id' => trim($m[1])];
                $inConditions = false;
                $inActions = false;
            } elseif ($currentRule) {
                // 규칙 속성 파싱
                if (preg_match('/^situation:\s*(.+)$/', $trimmed, $m)) {
                    $currentRule['situation'] = trim($m[1]);
                } elseif (preg_match('/^priority:\s*(\d+)$/', $trimmed, $m)) {
                    $currentRule['priority'] = (int)$m[1];
                } elseif (preg_match('/^persona:\s*(.+)$/', $trimmed, $m)) {
                    $currentRule['persona'] = trim($m[1]);
                } elseif (preg_match('/^confidence:\s*([\d.]+)$/', $trimmed, $m)) {
                    $currentRule['confidence'] = (float)$m[1];
                } elseif (strpos($trimmed, 'conditions:') === 0) {
                    $inConditions = true;
                    $inActions = false;
                    $currentRule['conditions'] = [];
                } elseif (strpos($trimmed, 'actions:') === 0) {
                    $inActions = true;
                    $inConditions = false;
                    $currentRule['actions'] = [];
                } elseif ($inConditions && preg_match('/^-\s*(.+)$/', $trimmed, $m)) {
                    $this->parseCondition($currentRule['conditions'], trim($m[1]));
                } elseif ($inActions && preg_match('/^-\s*(.+)$/', $trimmed, $m)) {
                    $currentRule['actions'][] = trim($m[1]);
                }
            }
        }

        if ($currentRule) {
            $rules[] = $currentRule;
        }

        return $rules;
    }

    /**
     * 조건 파싱
     */
    protected function parseCondition(array &$conditions, string $condition): void {
        if (strpos($condition, 'OR:') === 0) {
            $conditions['OR'] = [];
        } elseif (strpos($condition, 'AND:') === 0) {
            $conditions['AND'] = [];
        } else {
            // 간단한 조건 파싱
            if (preg_match('/^(\w+)\s*(==|!=|>|<|>=|<=|contains|in)\s*(.+)$/', $condition, $m)) {
                $cond = [
                    'field' => $m[1],
                    'operator' => $m[2],
                    'value' => trim($m[3], '"\'')
                ];

                if (isset($conditions['OR'])) {
                    $conditions['OR'][] = $cond;
                } elseif (isset($conditions['AND'])) {
                    $conditions['AND'][] = $cond;
                } else {
                    $conditions['AND'][] = $cond;
                }
            }
        }
    }

    /**
     * 우선순위로 정렬
     *
     * @param array $rules 규칙 배열
     * @return array 정렬된 규칙
     */
    public function sortByPriority(array $rules): array {
        usort($rules, function($a, $b) {
            $pa = $a['priority'] ?? 100;
            $pb = $b['priority'] ?? 100;
            return $pa - $pb;
        });
        return $rules;
    }
}

/*
 * 관련 파일:
 * - ../config/rules.yaml (Agent14 규칙 정의)
 */
