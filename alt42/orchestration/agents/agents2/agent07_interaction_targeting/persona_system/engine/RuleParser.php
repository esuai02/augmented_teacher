<?php
/**
 * Rule Parser for Agent07 Persona System
 *
 * YAML 형식의 규칙 파일을 파싱하여 PHP 배열로 변환
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Related Files:
 * - rules.yaml: 파싱 대상 규칙 파일
 * - PersonaRuleEngine.php: 파싱된 규칙 사용처
 */

class RuleParser {

    /** @var string YAML 파일 경로 */
    private $filePath;

    /** @var array 파싱된 데이터 캐시 */
    private $parsedData = null;

    /**
     * 생성자
     *
     * @param string $filePath YAML 파일 경로
     */
    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    /**
     * YAML 파일 파싱
     *
     * @return array 파싱된 규칙 데이터
     * @throws Exception 파일 읽기/파싱 실패 시
     */
    public function parse() {
        if ($this->parsedData !== null) {
            return $this->parsedData;
        }

        if (!file_exists($this->filePath)) {
            throw new Exception(sprintf(
                "Rules file not found: %s (File: %s, Line: %d)",
                $this->filePath,
                __FILE__,
                __LINE__
            ));
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new Exception(sprintf(
                "Failed to read rules file: %s (File: %s, Line: %d)",
                $this->filePath,
                __FILE__,
                __LINE__
            ));
        }

        // PHP yaml 확장이 있으면 사용, 없으면 간단한 파서 사용
        if (function_exists('yaml_parse')) {
            $this->parsedData = yaml_parse($content);
        } else {
            $this->parsedData = $this->simpleYamlParse($content);
        }

        if ($this->parsedData === false || $this->parsedData === null) {
            throw new Exception(sprintf(
                "Failed to parse YAML content (File: %s, Line: %d)",
                __FILE__,
                __LINE__
            ));
        }

        return $this->parsedData;
    }

    /**
     * 간단한 YAML 파서 (yaml 확장 없을 때 사용)
     *
     * @param string $content YAML 내용
     * @return array
     */
    private function simpleYamlParse($content) {
        $result = array();
        $lines = explode("\n", $content);
        $stack = array(&$result);
        $indentStack = array(-1);

        foreach ($lines as $lineNum => $line) {
            // 주석 및 빈 줄 스킵
            if (preg_match('/^\s*#/', $line) || trim($line) === '') {
                continue;
            }

            // 들여쓰기 계산
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $trimmed = trim($line);

            // 현재 깊이 결정
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($stack);
                array_pop($indentStack);
            }

            // 키: 값 파싱
            if (preg_match('/^([^:]+):\s*(.*)$/', $trimmed, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);

                // 문자열에서 따옴표 제거
                $value = $this->parseValue($value);

                $current = &$stack[count($stack) - 1];

                if ($value === '' || $value === null) {
                    // 하위 구조 시작
                    $current[$key] = array();
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    $current[$key] = $value;
                }
            }
            // 배열 항목 (- 로 시작)
            elseif (preg_match('/^-\s*(.*)$/', $trimmed, $matches)) {
                $value = $this->parseValue(trim($matches[1]));
                $current = &$stack[count($stack) - 1];

                if (!is_array($current)) {
                    $current = array();
                }
                $current[] = $value;
            }
        }

        return $result;
    }

    /**
     * 값 파싱 (타입 변환)
     *
     * @param string $value 원본 값
     * @return mixed 변환된 값
     */
    private function parseValue($value) {
        // 빈 값
        if ($value === '' || $value === '~' || $value === 'null') {
            return null;
        }

        // 따옴표 문자열
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
            return $matches[1];
        }

        // 불리언
        if ($value === 'true') return true;
        if ($value === 'false') return false;

        // 숫자
        if (is_numeric($value)) {
            return strpos($value, '.') !== false
                ? (float)$value
                : (int)$value;
        }

        // 배열 (인라인)
        if (preg_match('/^\[(.+)\]$/', $value, $matches)) {
            $items = array_map('trim', explode(',', $matches[1]));
            return array_map(array($this, 'parseValue'), $items);
        }

        return $value;
    }

    /**
     * 특정 섹션만 가져오기
     *
     * @param string $section 섹션 키
     * @return array|null
     */
    public function getSection($section) {
        $data = $this->parse();
        return isset($data[$section]) ? $data[$section] : null;
    }

    /**
     * 파일 경로 반환
     *
     * @return string
     */
    public function getFilePath() {
        return $this->filePath;
    }

    /**
     * 캐시 초기화
     */
    public function clearCache() {
        $this->parsedData = null;
    }
}
