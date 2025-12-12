<?php
/**
 * RuleParser - YAML 규칙 파서
 *
 * YAML 형식의 페르소나 규칙 파일을 PHP 배열로 변환
 * Agent15 문제 재정의 도메인 특화 파싱 지원
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

class RuleParser {

    /** @var array 파싱된 규칙 */
    private $parsedRules = [];

    /** @var bool YAML 확장 사용 가능 여부 */
    private $yamlAvailable = false;

    /**
     * 생성자
     */
    public function __construct() {
        // YAML 확장 확인
        $this->yamlAvailable = function_exists('yaml_parse_file');
    }

    /**
     * YAML 파일 파싱
     *
     * @param string $filePath 파일 경로
     * @return array 파싱된 규칙 배열
     * @throws Exception 파싱 실패 시
     */
    public function parseFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Rule file not found: $filePath [" . __FILE__ . ":" . __LINE__ . "]");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Failed to read rule file: $filePath [" . __FILE__ . ":" . __LINE__ . "]");
        }

        return $this->parseContent($content);
    }

    /**
     * YAML 내용 파싱
     *
     * @param string $content YAML 내용
     * @return array 파싱된 규칙 배열
     * @throws Exception 파싱 실패 시
     */
    public function parseContent($content) {
        try {
            // YAML 확장 사용 가능 시
            if ($this->yamlAvailable) {
                $data = yaml_parse($content);
            } else {
                // 간단한 YAML 파서 사용
                $data = $this->simpleYamlParse($content);
            }

            if ($data === false || !is_array($data)) {
                throw new Exception("Invalid YAML content [" . __FILE__ . ":" . __LINE__ . "]");
            }

            // 규칙 구조 검증 및 정규화
            $this->parsedRules = $this->normalizeRules($data);

            return $this->parsedRules;

        } catch (Exception $e) {
            throw new Exception("YAML parsing failed: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
        }
    }

    /**
     * 간단한 YAML 파서 (확장 미사용 시)
     *
     * @param string $content YAML 내용
     * @return array 파싱된 데이터
     */
    private function simpleYamlParse($content) {
        $result = [];
        $lines = explode("\n", $content);
        $stack = [&$result];
        $indentStack = [-1];
        $currentKey = null;
        $inMultiline = false;
        $multilineContent = '';
        $multilineKey = null;
        $multilineIndent = 0;

        foreach ($lines as $lineNum => $line) {
            // 주석 및 빈 줄 스킵
            if (empty(trim($line)) || trim($line)[0] === '#') {
                continue;
            }

            // 들여쓰기 계산
            $indent = strlen($line) - strlen(ltrim($line));
            $trimmedLine = trim($line);

            // 여러 줄 문자열 처리
            if ($inMultiline) {
                if ($indent > $multilineIndent) {
                    $multilineContent .= substr($line, $multilineIndent) . "\n";
                    continue;
                } else {
                    // 여러 줄 종료
                    $inMultiline = false;
                    $stack[count($stack) - 1][$multilineKey] = trim($multilineContent);
                }
            }

            // 스택 조정
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($stack);
                array_pop($indentStack);
            }

            // 리스트 항목
            if (strpos($trimmedLine, '- ') === 0) {
                $value = trim(substr($trimmedLine, 2));

                // 키:값 형태의 리스트 항목
                if (strpos($value, ':') !== false) {
                    $parts = explode(':', $value, 2);
                    $itemKey = trim($parts[0]);
                    $itemValue = isset($parts[1]) ? trim($parts[1]) : null;

                    if (!isset($stack[count($stack) - 1][$currentKey])) {
                        $stack[count($stack) - 1][$currentKey] = [];
                    }

                    $newItem = [$itemKey => $this->parseValue($itemValue)];
                    $stack[count($stack) - 1][$currentKey][] = $newItem;
                } else {
                    // 단순 리스트 항목
                    if (!isset($stack[count($stack) - 1][$currentKey])) {
                        $stack[count($stack) - 1][$currentKey] = [];
                    }
                    $stack[count($stack) - 1][$currentKey][] = $this->parseValue($value);
                }
                continue;
            }

            // 키:값 쌍
            if (strpos($trimmedLine, ':') !== false) {
                $colonPos = strpos($trimmedLine, ':');
                $key = trim(substr($trimmedLine, 0, $colonPos));
                $value = trim(substr($trimmedLine, $colonPos + 1));

                // 여러 줄 문자열 시작
                if ($value === '|' || $value === '>') {
                    $inMultiline = true;
                    $multilineContent = '';
                    $multilineKey = $key;
                    $multilineIndent = $indent + 2;
                    $currentKey = $key;
                    continue;
                }

                // 빈 값 (하위 맵 또는 리스트)
                if (empty($value)) {
                    $stack[count($stack) - 1][$key] = [];
                    $stack[] = &$stack[count($stack) - 1][$key];
                    $indentStack[] = $indent;
                    $currentKey = $key;
                } else {
                    $stack[count($stack) - 1][$key] = $this->parseValue($value);
                    $currentKey = $key;
                }
            }
        }

        return $result;
    }

    /**
     * 값 파싱 (타입 변환)
     *
     * @param mixed $value 원본 값
     * @return mixed 변환된 값
     */
    private function parseValue($value) {
        if ($value === null || $value === '') {
            return null;
        }

        // 따옴표 제거
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }

        // 불리언
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes' || $lower === 'on') {
            return true;
        }
        if ($lower === 'false' || $lower === 'no' || $lower === 'off') {
            return false;
        }
        if ($lower === 'null' || $lower === '~') {
            return null;
        }

        // 숫자
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                return floatval($value);
            }
            return intval($value);
        }

        return $value;
    }

    /**
     * 규칙 정규화
     *
     * @param array $data 원본 데이터
     * @return array 정규화된 규칙
     */
    private function normalizeRules($data) {
        $rules = [];

        // 규칙 목록 추출
        if (isset($data['rules'])) {
            $ruleList = $data['rules'];
        } elseif (isset($data['personas'])) {
            $ruleList = $data['personas'];
        } else {
            // 최상위가 규칙 배열인 경우
            $ruleList = $data;
        }

        foreach ($ruleList as $key => $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $normalizedRule = $this->normalizeRule($rule, $key);
            if ($normalizedRule) {
                $rules[] = $normalizedRule;
            }
        }

        return $rules;
    }

    /**
     * 개별 규칙 정규화
     *
     * @param array $rule 원본 규칙
     * @param string $key 규칙 키
     * @return array|null 정규화된 규칙
     */
    private function normalizeRule($rule, $key) {
        $normalized = [
            'id' => $rule['id'] ?? $key,
            'persona_id' => $rule['persona_id'] ?? $rule['id'] ?? $key,
            'persona_name' => $rule['persona_name'] ?? $rule['name'] ?? '미정의 페르소나',
            'category' => $rule['category'] ?? $this->inferCategory($key),
            'trigger_scenarios' => $rule['trigger_scenarios'] ?? $rule['triggers'] ?? [],
            'conditions' => $this->normalizeConditions($rule['conditions'] ?? $rule['when'] ?? []),
            'characteristics' => $rule['characteristics'] ?? $rule['traits'] ?? [],
            'priority' => $rule['priority'] ?? 50,
            'response_template' => $rule['response_template'] ?? $rule['template'] ?? null,
            'actions' => $rule['actions'] ?? []
        ];

        return $normalized;
    }

    /**
     * 조건 정규화
     *
     * @param array $conditions 원본 조건
     * @return array 정규화된 조건
     */
    private function normalizeConditions($conditions) {
        if (empty($conditions)) {
            return [];
        }

        $normalized = [];

        foreach ($conditions as $key => $condition) {
            if (is_string($condition)) {
                // 문자열 조건 파싱
                $normalized[] = $this->parseConditionString($condition);
            } elseif (is_array($condition)) {
                // 배열 조건
                if (isset($condition['field']) || isset($condition['type'])) {
                    $normalized[] = $condition;
                } else {
                    // 중첩 조건
                    $normalized[] = [
                        'type' => 'compound',
                        'operator' => $key, // AND, OR 등
                        'conditions' => $this->normalizeConditions($condition)
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * 조건 문자열 파싱
     *
     * @param string $conditionStr 조건 문자열
     * @return array 파싱된 조건
     */
    private function parseConditionString($conditionStr) {
        // 연산자 패턴 매칭
        $patterns = [
            '/^(.+?)\s*>=\s*(.+)$/' => '>=',
            '/^(.+?)\s*<=\s*(.+)$/' => '<=',
            '/^(.+?)\s*!=\s*(.+)$/' => '!=',
            '/^(.+?)\s*==\s*(.+)$/' => '==',
            '/^(.+?)\s*>\s*(.+)$/' => '>',
            '/^(.+?)\s*<\s*(.+)$/' => '<',
            '/^(.+?)\s+contains\s+(.+)$/i' => 'contains',
            '/^(.+?)\s+in\s+\[(.+)\]$/i' => 'in',
            '/^(.+?)\s+matches\s+(.+)$/i' => 'matches'
        ];

        foreach ($patterns as $pattern => $operator) {
            if (preg_match($pattern, $conditionStr, $matches)) {
                return [
                    'field' => trim($matches[1]),
                    'operator' => $operator,
                    'value' => $this->parseValue(trim($matches[2]))
                ];
            }
        }

        // 기본: 필드 존재 여부 체크
        return [
            'field' => trim($conditionStr),
            'operator' => 'exists',
            'value' => true
        ];
    }

    /**
     * 카테고리 추론
     *
     * @param string $key 규칙 키
     * @return string 추론된 카테고리
     */
    private function inferCategory($key) {
        $key = strtoupper($key);

        if (strpos($key, 'R') === 0) return 'R'; // Recognition
        if (strpos($key, 'A') === 0) return 'A'; // Attribution
        if (strpos($key, 'V') === 0) return 'V'; // Validation
        if (strpos($key, 'S') === 0) return 'S'; // Solution
        if (strpos($key, 'E') === 0) return 'E'; // Emotional

        return 'R'; // 기본값
    }

    /**
     * 파싱된 규칙 반환
     *
     * @return array 파싱된 규칙
     */
    public function getRules() {
        return $this->parsedRules;
    }

    /**
     * 특정 페르소나 ID의 규칙 검색
     *
     * @param string $personaId 페르소나 ID
     * @return array|null 해당 규칙
     */
    public function getRuleByPersonaId($personaId) {
        foreach ($this->parsedRules as $rule) {
            if ($rule['persona_id'] === $personaId) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * 카테고리별 규칙 검색
     *
     * @param string $category 카테고리 코드
     * @return array 해당 카테고리 규칙 목록
     */
    public function getRulesByCategory($category) {
        $filtered = [];
        foreach ($this->parsedRules as $rule) {
            if ($rule['category'] === $category) {
                $filtered[] = $rule;
            }
        }
        return $filtered;
    }

    /**
     * 트리거 시나리오별 규칙 검색
     *
     * @param string $scenario 트리거 시나리오 코드
     * @return array 해당 시나리오 규칙 목록
     */
    public function getRulesByTrigger($scenario) {
        $filtered = [];
        foreach ($this->parsedRules as $rule) {
            if (in_array($scenario, $rule['trigger_scenarios'])) {
                $filtered[] = $rule;
            }
        }
        return $filtered;
    }
}
