<?php
/**
 * Agent18 Signature Routine - Rule Parser
 *
 * YAML 규칙 파일을 파싱하여 PHP 객체로 변환.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/RuleParser.php
 */

class RuleParser {

    /** @var array 파싱된 규칙 목록 */
    private $rules = [];

    /** @var array 파싱 오류 목록 */
    private $errors = [];

    /**
     * YAML 규칙 파일 파싱
     *
     * @param string $filePath YAML 파일 경로
     * @return array 파싱된 규칙 목록
     * @throws Exception 파일을 찾을 수 없거나 파싱 오류 시
     */
    public function parse($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("규칙 파일을 찾을 수 없습니다: {$filePath} at " . __FILE__ . ":" . __LINE__);
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("규칙 파일 읽기 실패: {$filePath} at " . __FILE__ . ":" . __LINE__);
        }

        // YAML 파싱 (간단한 파서 사용)
        $parsed = $this->parseYaml($content);

        if (!isset($parsed['rules']) || !is_array($parsed['rules'])) {
            throw new Exception("규칙 파일에 'rules' 섹션이 없습니다 at " . __FILE__ . ":" . __LINE__);
        }

        $this->rules = [];
        foreach ($parsed['rules'] as $ruleData) {
            $rule = $this->parseRule($ruleData);
            if ($rule !== null) {
                $this->rules[] = $rule;
            }
        }

        return $this->rules;
    }

    /**
     * 간단한 YAML 파서
     *
     * @param string $content YAML 내용
     * @return array 파싱된 배열
     */
    private function parseYaml($content) {
        // PHP의 yaml 확장이 있으면 사용
        if (function_exists('yaml_parse')) {
            return yaml_parse($content);
        }

        // 간단한 YAML 파서 (기본 구조만 지원)
        return $this->simpleYamlParse($content);
    }

    /**
     * 간단한 YAML 파서 구현
     *
     * @param string $content YAML 내용
     * @return array 파싱된 배열
     */
    private function simpleYamlParse($content) {
        $lines = explode("\n", $content);
        $result = [];
        $currentRules = [];
        $currentRule = null;
        $currentSection = null;
        $currentConditions = [];
        $currentActions = [];
        $inRules = false;

        foreach ($lines as $lineNum => $line) {
            // 주석 및 빈 줄 스킵
            $trimmedLine = trim($line);
            if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
                continue;
            }

            // 들여쓰기 레벨 계산
            $indent = strlen($line) - strlen(ltrim($line));

            // rules 섹션 시작
            if ($trimmedLine === 'rules:') {
                $inRules = true;
                continue;
            }

            if (!$inRules) {
                // 메타 정보 파싱
                if (strpos($trimmedLine, ':') !== false) {
                    list($key, $value) = explode(':', $trimmedLine, 2);
                    $result[trim($key)] = trim($value);
                }
                continue;
            }

            // 새 규칙 시작 (- id:)
            if (preg_match('/^-\s+id:\s*(.+)$/', $trimmedLine, $matches)) {
                // 이전 규칙 저장
                if ($currentRule !== null) {
                    $currentRule['conditions'] = $currentConditions;
                    $currentRule['actions'] = $currentActions;
                    $currentRules[] = $currentRule;
                }

                $currentRule = ['id' => trim($matches[1])];
                $currentConditions = [];
                $currentActions = [];
                $currentSection = null;
                continue;
            }

            if ($currentRule === null) {
                continue;
            }

            // 규칙 속성 파싱
            if (preg_match('/^\s+(\w+):\s*(.*)$/', $line, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);

                if ($key === 'conditions') {
                    $currentSection = 'conditions';
                    continue;
                } elseif ($key === 'actions') {
                    $currentSection = 'actions';
                    continue;
                } elseif ($key === 'priority') {
                    $currentRule['priority'] = (int)$value;
                } elseif ($key === 'name') {
                    $currentRule['name'] = $value;
                } elseif ($key === 'description') {
                    $currentRule['description'] = $value;
                }
            }

            // 조건 파싱
            if ($currentSection === 'conditions' && preg_match('/^\s+-\s+(.+)$/', $line, $matches)) {
                $conditionStr = trim($matches[1]);
                $condition = $this->parseCondition($conditionStr);
                if ($condition) {
                    $currentConditions[] = $condition;
                }
            }

            // 액션 파싱
            if ($currentSection === 'actions' && preg_match('/^\s+-\s+(.+)$/', $line, $matches)) {
                $actionStr = trim($matches[1]);
                $action = $this->parseAction($actionStr);
                if ($action) {
                    $currentActions[] = $action;
                }
            }
        }

        // 마지막 규칙 저장
        if ($currentRule !== null) {
            $currentRule['conditions'] = $currentConditions;
            $currentRule['actions'] = $currentActions;
            $currentRules[] = $currentRule;
        }

        $result['rules'] = $currentRules;
        return $result;
    }

    /**
     * 단일 규칙 파싱
     *
     * @param array $ruleData 규칙 데이터
     * @return array|null 파싱된 규칙 또는 null
     */
    private function parseRule($ruleData) {
        if (!isset($ruleData['id'])) {
            $this->errors[] = "규칙 ID가 없습니다";
            return null;
        }

        $rule = [
            'id' => $ruleData['id'],
            'name' => $ruleData['name'] ?? $ruleData['id'],
            'description' => $ruleData['description'] ?? '',
            'priority' => (int)($ruleData['priority'] ?? 50),
            'conditions' => [],
            'actions' => []
        ];

        // 조건 파싱
        if (isset($ruleData['conditions']) && is_array($ruleData['conditions'])) {
            foreach ($ruleData['conditions'] as $conditionData) {
                if (is_string($conditionData)) {
                    $condition = $this->parseCondition($conditionData);
                } else {
                    $condition = $conditionData;
                }
                if ($condition) {
                    $rule['conditions'][] = $condition;
                }
            }
        }

        // 액션 파싱
        if (isset($ruleData['actions']) && is_array($ruleData['actions'])) {
            foreach ($ruleData['actions'] as $actionData) {
                if (is_string($actionData)) {
                    $action = $this->parseAction($actionData);
                } else {
                    $action = $actionData;
                }
                if ($action) {
                    $rule['actions'][] = $action;
                }
            }
        }

        return $rule;
    }

    /**
     * 조건 문자열 파싱
     *
     * @param string $conditionStr 조건 문자열
     * @return array|null 파싱된 조건
     */
    private function parseCondition($conditionStr) {
        // 연산자 패턴
        $operators = ['>=', '<=', '!=', '==', '>', '<', 'contains_any', 'contains', 'in', 'is_empty'];

        foreach ($operators as $op) {
            if (strpos($conditionStr, $op) !== false) {
                $parts = explode($op, $conditionStr, 2);
                if (count($parts) === 2) {
                    return [
                        'field' => trim($parts[0]),
                        'operator' => $op,
                        'value' => $this->parseValue(trim($parts[1]))
                    ];
                }
            }
        }

        // 단순 필드 체크 (boolean)
        if (preg_match('/^[\w\.]+$/', $conditionStr)) {
            return [
                'field' => $conditionStr,
                'operator' => '==',
                'value' => true
            ];
        }

        return null;
    }

    /**
     * 값 파싱 (타입 변환)
     *
     * @param string $value 값 문자열
     * @return mixed 변환된 값
     */
    private function parseValue($value) {
        // 배열 형식 [a, b, c]
        if (preg_match('/^\[(.+)\]$/', $value, $matches)) {
            $items = explode(',', $matches[1]);
            return array_map(function($item) {
                return $this->parseValue(trim($item));
            }, $items);
        }

        // 숫자
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // 불리언
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;

        // 따옴표 제거
        if (preg_match('/^["\'](.+)["\']$/', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    /**
     * 액션 문자열 파싱
     *
     * @param string $actionStr 액션 문자열
     * @return array|null 파싱된 액션
     */
    private function parseAction($actionStr) {
        // type: value 형식
        if (preg_match('/^(\w+):\s*(.+)$/', $actionStr, $matches)) {
            $type = trim($matches[1]);
            $value = trim($matches[2]);

            switch ($type) {
                case 'identify_persona':
                    // identify_persona: PERSONA_ID (confidence: 0.8)
                    if (preg_match('/^([\w_]+)(?:\s*\(confidence:\s*([\d.]+)\))?$/', $value, $pMatches)) {
                        return [
                            'type' => 'identify_persona',
                            'persona' => $pMatches[1],
                            'confidence' => isset($pMatches[2]) ? (float)$pMatches[2] : 0.5
                        ];
                    }
                    return [
                        'type' => 'identify_persona',
                        'persona' => $value,
                        'confidence' => 0.5
                    ];

                case 'set_context':
                    return [
                        'type' => 'set_context',
                        'context' => $value
                    ];

                case 'set_tone':
                    return [
                        'type' => 'set_tone',
                        'value' => $value
                    ];

                case 'set_recommendation':
                    return [
                        'type' => 'set_recommendation',
                        'value' => $value
                    ];

                case 'flag':
                    return [
                        'type' => 'flag',
                        'flag' => $value
                    ];

                default:
                    return [
                        'type' => $type,
                        'value' => $this->parseValue($value)
                    ];
            }
        }

        return null;
    }

    /**
     * 파싱 오류 반환
     *
     * @return array 오류 목록
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * 규칙 유효성 검사
     *
     * @param array $rule 규칙
     * @return bool 유효 여부
     */
    public function validateRule($rule) {
        if (!isset($rule['id']) || empty($rule['id'])) {
            $this->errors[] = "규칙 ID가 필요합니다";
            return false;
        }

        if (empty($rule['conditions'])) {
            $this->errors[] = "규칙 '{$rule['id']}'에 조건이 없습니다";
            return false;
        }

        if (empty($rule['actions'])) {
            $this->errors[] = "규칙 '{$rule['id']}'에 액션이 없습니다";
            return false;
        }

        return true;
    }
}
