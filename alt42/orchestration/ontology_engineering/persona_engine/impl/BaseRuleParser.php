<?php
/**
 * BaseRuleParser - 기본 YAML 규칙 파서 구현
 *
 * IRuleParser 인터페이스의 기본 구현체
 * 모든 에이전트에서 공통으로 사용하는 규칙 파싱 기능 제공
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 */

require_once(__DIR__ . '/../core/IRuleParser.php');

class BaseRuleParser implements IRuleParser {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var array 파싱된 규칙 캐시 */
    protected $ruleCache = [];

    /**
     * 규칙 파일 로드 및 파싱 (IRuleParser 인터페이스 구현)
     *
     * @param string $filePath rules.yaml 경로
     * @return array 파싱된 규칙
     * @throws Exception 파일 읽기 실패 시
     */
    public function parse(string $filePath): array {
        // 캐시 확인
        $cacheKey = md5($filePath);
        if (isset($this->ruleCache[$cacheKey])) {
            return $this->ruleCache[$cacheKey];
        }

        if (!file_exists($filePath)) {
            throw new Exception("[BaseRuleParser] {$this->currentFile}:" . __LINE__ .
                " - 파일을 찾을 수 없음: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("[BaseRuleParser] {$this->currentFile}:" . __LINE__ .
                " - 파일 읽기 실패: {$filePath}");
        }

        // YAML 파싱 (다양한 라이브러리 지원)
        if (function_exists('yaml_parse')) {
            $rules = yaml_parse($content);
        } elseif (class_exists('Symfony\Component\Yaml\Yaml')) {
            $rules = \Symfony\Component\Yaml\Yaml::parse($content);
        } else {
            $rules = $this->simpleYamlParse($content);
        }

        if ($rules === false || $rules === null) {
            throw new Exception("[BaseRuleParser] {$this->currentFile}:" . __LINE__ .
                " - YAML 파싱 실패");
        }

        // 캐시 저장
        $this->ruleCache[$cacheKey] = $rules;

        return $rules;
    }

    /**
     * 규칙을 우선순위로 정렬
     *
     * @param array $rules 규칙 배열
     * @return array 정렬된 규칙
     */
    public function sortByPriority(array $rules): array {
        usort($rules, function($a, $b) {
            $priorityA = $a['priority'] ?? 50;
            $priorityB = $b['priority'] ?? 50;
            return $priorityB - $priorityA; // 높은 우선순위 먼저
        });

        return $rules;
    }

    /**
     * 간단한 YAML 파서 (외부 라이브러리 없을 때 사용)
     *
     * @param string $content YAML 문자열
     * @return array 파싱된 배열
     */
    protected function simpleYamlParse(string $content): array {
        $result = [];
        $lines = explode("\n", $content);
        $stack = [&$result];
        $indentStack = [-1];
        $currentKey = null;

        foreach ($lines as $lineNum => $line) {
            // 주석과 빈 줄 스킵
            $trimmedLine = trim($line);
            if (empty($trimmedLine) || $trimmedLine[0] === '#') {
                continue;
            }

            // 들여쓰기 계산
            $indent = strlen($line) - strlen(ltrim($line));

            // 배열 항목 처리
            if (preg_match('/^(\s*)-\s*(.*)$/', $line, $matches)) {
                $content = trim($matches[2]);

                // 키-값 쌍인 경우
                if (preg_match('/^(\w+):\s*(.+)$/', $content, $kvMatches)) {
                    $key = $kvMatches[1];
                    $value = $this->parseValue($kvMatches[2]);

                    if ($currentKey !== null) {
                        if (!isset($stack[count($stack)-1][$currentKey])) {
                            $stack[count($stack)-1][$currentKey] = [];
                        }
                        $stack[count($stack)-1][$currentKey][] = [$key => $value];
                    }
                }
                // 단순 값인 경우
                elseif (!empty($content)) {
                    if ($currentKey !== null) {
                        if (!isset($stack[count($stack)-1][$currentKey])) {
                            $stack[count($stack)-1][$currentKey] = [];
                        }
                        $stack[count($stack)-1][$currentKey][] = $this->parseValue($content);
                    }
                }
                continue;
            }

            // 키-값 쌍 처리
            if (preg_match('/^(\s*)(\w+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[2];
                $value = trim($matches[3]);

                // 현재 스택 조정
                while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                    array_pop($stack);
                    array_pop($indentStack);
                }

                if (empty($value)) {
                    // 새로운 섹션
                    $stack[count($stack)-1][$key] = [];
                    $stack[] = &$stack[count($stack)-1][$key];
                    $indentStack[] = $indent;
                    $currentKey = $key;
                } else {
                    // 값이 있는 키
                    $stack[count($stack)-1][$key] = $this->parseValue($value);
                }
            }
        }

        return $result;
    }

    /**
     * YAML 값 파싱
     *
     * @param string $value 문자열 값
     * @return mixed 파싱된 값
     */
    protected function parseValue(string $value) {
        $value = trim($value);

        // 따옴표 제거
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }

        // 불리언
        if ($value === 'true') return true;
        if ($value === 'false') return false;

        // null
        if ($value === 'null' || $value === '~') return null;

        // 숫자
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // 인라인 배열
        if (substr($value, 0, 1) === '[' && substr($value, -1) === ']') {
            $inner = substr($value, 1, -1);
            $items = preg_split('/,\s*/', $inner);
            return array_map([$this, 'parseValue'], $items);
        }

        return $value;
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void {
        $this->ruleCache = [];
    }

    /**
     * 규칙 문자열 파싱 (IRuleParser 인터페이스 구현)
     *
     * @param string $content 규칙 내용 문자열
     * @param string $format 형식 ('yaml' | 'json')
     * @return array 파싱된 규칙 배열
     */
    public function parseString(string $content, string $format = 'yaml'): array {
        $format = strtolower($format);

        if ($format === 'json') {
            $rules = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("[BaseRuleParser] {$this->currentFile}:" . __LINE__ .
                    " - JSON 파싱 실패: " . json_last_error_msg());
            }
            return $rules;
        }

        // YAML 파싱
        if (function_exists('yaml_parse')) {
            $rules = yaml_parse($content);
        } elseif (class_exists('Symfony\Component\Yaml\Yaml')) {
            $rules = \Symfony\Component\Yaml\Yaml::parse($content);
        } else {
            $rules = $this->simpleYamlParse($content);
        }

        if ($rules === false || $rules === null) {
            throw new Exception("[BaseRuleParser] {$this->currentFile}:" . __LINE__ .
                " - YAML 파싱 실패");
        }

        return $rules;
    }

    /**
     * 규칙 유효성 검증 (IRuleParser 인터페이스 구현)
     *
     * @param array $rules 검증할 규칙 배열
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(array $rules): array {
        $errors = [];

        // personas 필드 검증
        if (!isset($rules['personas']) || !is_array($rules['personas'])) {
            $errors[] = 'personas 필드가 누락되었거나 배열이 아닙니다';
            return ['valid' => false, 'errors' => $errors];
        }

        // 각 페르소나 검증
        foreach ($rules['personas'] as $index => $persona) {
            if (empty($persona['id'])) {
                $errors[] = "페르소나 #{$index}: ID가 누락됨";
            }
            if (empty($persona['name'])) {
                $errors[] = "페르소나 #{$index}: 이름이 누락됨";
            }
        }

        // 규칙 검증 (존재하는 경우)
        if (isset($rules['rules']) && is_array($rules['rules'])) {
            foreach ($rules['rules'] as $index => $rule) {
                $ruleValidation = $this->validateSingleRule($rule);
                if (!$ruleValidation['valid']) {
                    foreach ($ruleValidation['errors'] as $error) {
                        $errors[] = "규칙 #{$index}: {$error}";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 단일 규칙 유효성 검증
     *
     * @param array $rule 검증할 규칙
     * @return array 검증 결과 ['valid' => bool, 'errors' => array]
     */
    protected function validateSingleRule(array $rule): array {
        $errors = [];

        if (empty($rule['id'])) {
            $errors[] = '규칙 ID가 누락됨';
        }

        if (empty($rule['situation'])) {
            $errors[] = '상황 코드가 누락됨';
        }

        if (empty($rule['conditions'])) {
            $errors[] = '조건이 누락됨';
        }

        if (empty($rule['persona'])) {
            $errors[] = '페르소나 ID가 누락됨';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 규칙을 파일로 저장 (IRuleParser 인터페이스 구현)
     *
     * @param array $rules 저장할 규칙
     * @param string $filePath 저장 경로
     * @param string $format 형식 ('yaml' | 'json')
     * @return bool 저장 성공 여부
     */
    public function save(array $rules, string $filePath, string $format = 'yaml'): bool {
        try {
            $format = strtolower($format);

            if ($format === 'json') {
                $content = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                // 간단한 YAML 생성 (기본 지원)
                $content = $this->arrayToYaml($rules);
            }

            $result = file_put_contents($filePath, $content);

            // 캐시 무효화
            $cacheKey = md5($filePath);
            unset($this->ruleCache[$cacheKey]);

            return $result !== false;
        } catch (Exception $e) {
            error_log("[BaseRuleParser] {$this->currentFile}:" . __LINE__ .
                " - 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 배열을 YAML 문자열로 변환
     *
     * @param array $data 변환할 배열
     * @param int $indent 들여쓰기 레벨
     * @return string YAML 문자열
     */
    protected function arrayToYaml(array $data, int $indent = 0): string {
        $yaml = '';
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_int($key)) {
                if (is_array($value)) {
                    $yaml .= $prefix . "-\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
                    $yaml .= $prefix . "- " . $this->formatYamlValue($value) . "\n";
                }
            } else {
                if (is_array($value)) {
                    $yaml .= $prefix . $key . ":\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
                    $yaml .= $prefix . $key . ": " . $this->formatYamlValue($value) . "\n";
                }
            }
        }

        return $yaml;
    }

    /**
     * YAML 값 포맷팅
     *
     * @param mixed $value 값
     * @return string 포맷된 문자열
     */
    protected function formatYamlValue($value): string {
        if (is_null($value)) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_numeric($value)) return (string)$value;
        if (preg_match('/[:\#\[\]\{\},&\*!\|>\'\"%@`]/', $value)) {
            return '"' . addslashes($value) . '"';
        }
        return (string)$value;
    }

    /**
     * 지원 형식 목록 반환 (IRuleParser 인터페이스 구현)
     *
     * @return array ['yaml', 'json']
     */
    public function getSupportedFormats(): array {
        return ['yaml', 'yml', 'json'];
    }
}

/*
 * 지원 형식:
 * - 기본 키-값 쌍: key: value
 * - 중첩 구조: 들여쓰기 기반
 * - 배열: - item 형식
 * - 인라인 배열: [item1, item2]
 * - 불리언: true/false
 * - 숫자: 정수/실수
 * - 문자열: 따옴표 포함/미포함
 */
