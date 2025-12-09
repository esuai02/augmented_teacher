<?php
/**
 * YamlRuleParser - YAML 규칙 파서 구현
 *
 * IRuleParser 인터페이스의 YAML 기반 구현체
 * PHP Spyc 라이브러리 또는 yaml 확장 사용
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @author Claude Code
 */

require_once(__DIR__ . '/../core/IRuleParser.php');

class YamlRuleParser implements IRuleParser {

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 캐시된 규칙 */
    private $cache = [];

    /** @var bool 캐시 활성화 */
    private $cacheEnabled = true;

    /**
     * 생성자
     */
    public function __construct(bool $debugMode = false, bool $cacheEnabled = true) {
        $this->debugMode = $debugMode;
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $filePath): array {
        // 캐시 확인
        $cacheKey = md5($filePath);
        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                "[{$this->currentFile}:" . __LINE__ . "] 규칙 파일을 찾을 수 없습니다: {$filePath}"
            );
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException(
                "[{$this->currentFile}:" . __LINE__ . "] 파일 읽기 실패: {$filePath}"
            );
        }

        $rules = $this->parseString($content, $extension === 'json' ? 'json' : 'yaml');

        // 캐시 저장
        if ($this->cacheEnabled) {
            $this->cache[$cacheKey] = $rules;
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function parseString(string $content, string $format = 'yaml'): array {
        $format = strtolower($format);

        if ($format === 'json') {
            $rules = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    "[{$this->currentFile}:" . __LINE__ . "] JSON 파싱 실패: " . json_last_error_msg()
                );
            }
            return $rules;
        }

        // YAML 파싱
        return $this->parseYaml($content);
    }

    /**
     * YAML 문자열 파싱
     *
     * @param string $content YAML 내용
     * @return array 파싱된 배열
     */
    private function parseYaml(string $content): array {
        // yaml 확장이 있으면 사용
        if (function_exists('yaml_parse')) {
            $result = yaml_parse($content);
            if ($result === false) {
                throw new \RuntimeException(
                    "[{$this->currentFile}:" . __LINE__ . "] YAML 파싱 실패"
                );
            }
            return $result;
        }

        // 간단한 YAML 파서 (기본 지원)
        return $this->simpleYamlParse($content);
    }

    /**
     * 간단한 YAML 파서 (기본 구조만 지원)
     *
     * @param string $content YAML 내용
     * @return array 파싱된 배열
     */
    private function simpleYamlParse(string $content): array {
        $lines = explode("\n", $content);
        $result = [];
        $stack = [&$result];
        $indentStack = [-1];

        foreach ($lines as $lineNum => $line) {
            // 주석과 빈 줄 무시
            if (preg_match('/^\s*(#|$)/', $line)) {
                continue;
            }

            // 들여쓰기 레벨 계산
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $line = trim($line);

            // 리스트 아이템
            if (preg_match('/^-\s*(.*)$/', $line, $matches)) {
                $value = trim($matches[1]);
                $current = &$stack[count($stack) - 1];
                
                if (!is_array($current)) {
                    $current = [];
                }

                if (strpos($value, ':') !== false) {
                    // 리스트 내 객체
                    preg_match('/^([^:]+):\s*(.*)$/', $value, $kv);
                    $item = [];
                    $item[trim($kv[1])] = $this->parseValue(trim($kv[2] ?? ''));
                    $current[] = $item;
                    $stack[] = &$current[count($current) - 1];
                    $indentStack[] = $indent;
                } else {
                    $current[] = $this->parseValue($value);
                }
                continue;
            }

            // 키-값 쌍
            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);

                // 스택 조정
                while ($indent <= end($indentStack)) {
                    array_pop($stack);
                    array_pop($indentStack);
                }

                $current = &$stack[count($stack) - 1];

                if ($value === '' || $value === '|' || $value === '>') {
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    $current[$key] = $this->parseValue($value);
                }
            }
        }

        return $result;
    }

    /**
     * YAML 값 파싱
     *
     * @param string $value 값 문자열
     * @return mixed 파싱된 값
     */
    private function parseValue(string $value) {
        // 따옴표 제거
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
            return $matches[1];
        }

        // null
        if ($value === 'null' || $value === '~' || $value === '') {
            return null;
        }

        // boolean
        if (in_array(strtolower($value), ['true', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', 'no', 'off'])) {
            return false;
        }

        // 숫자
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // 인라인 배열
        if (preg_match('/^\[(.+)\]$/', $value, $matches)) {
            $items = array_map('trim', explode(',', $matches[1]));
            return array_map([$this, 'parseValue'], $items);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $rules): array {
        $errors = [];
        $required = ['personas'];

        foreach ($required as $field) {
            if (!isset($rules[$field])) {
                $errors[] = "필수 필드 누락: {$field}";
            }
        }

        // 페르소나 구조 검증
        if (isset($rules['personas']) && is_array($rules['personas'])) {
            foreach ($rules['personas'] as $index => $persona) {
                if (!isset($persona['id'])) {
                    $errors[] = "페르소나 #{$index}: id 필드 필요";
                }
                if (!isset($persona['name'])) {
                    $errors[] = "페르소나 #{$index}: name 필드 필요";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * @inheritDoc
     */
    public function save(array $rules, string $filePath, string $format = 'yaml'): bool {
        try {
            if ($format === 'json') {
                $content = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $content = $this->arrayToYaml($rules);
            }

            return file_put_contents($filePath, $content) !== false;
        } catch (\Exception $e) {
            error_log("[YamlRuleParser ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 배열을 YAML 문자열로 변환
     *
     * @param array $data 데이터 배열
     * @param int $indent 들여쓰기 레벨
     * @return string YAML 문자열
     */
    private function arrayToYaml(array $data, int $indent = 0): string {
        $yaml = '';
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_int($key)) {
                // 리스트 아이템
                if (is_array($value)) {
                    $yaml .= $prefix . "-\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
                    $yaml .= $prefix . "- " . $this->formatValue($value) . "\n";
                }
            } else {
                // 키-값 쌍
                if (is_array($value)) {
                    $yaml .= $prefix . $key . ":\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
                    $yaml .= $prefix . $key . ": " . $this->formatValue($value) . "\n";
                }
            }
        }

        return $yaml;
    }

    /**
     * 값을 YAML 형식으로 포맷
     *
     * @param mixed $value 값
     * @return string 포맷된 문자열
     */
    private function formatValue($value): string {
        if (is_null($value)) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_numeric($value)) return (string)$value;
        if (preg_match('/[\:\#\[\]\{\}\,\&\*\!\|\>\'\"\%\@\`]/', $value)) {
            return '"' . addslashes($value) . '"';
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedFormats(): array {
        return ['yaml', 'yml', 'json'];
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void {
        $this->cache = [];
    }
}

/*
 * 관련 DB 테이블: 없음
 *
 * 참조 파일:
 * - core/IRuleParser.php (인터페이스)
 * - agents/agent01_onboarding/persona_system/rules/rules.yaml (규칙 예시)
 */
