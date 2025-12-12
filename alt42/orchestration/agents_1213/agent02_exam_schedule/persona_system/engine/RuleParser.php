<?php
/**
 * RuleParser - YAML 규칙 파서
 *
 * rules.yaml 파일을 파싱하여 PHP 배열로 변환합니다.
 *
 * @package AugmentedTeacher\Agent02\PersonaSystem
 * @version 1.0
 */

class RuleParser {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * YAML 규칙 파일 파싱
     *
     * @param string $filePath rules.yaml 경로
     * @return array 파싱된 규칙
     * @throws Exception 파일 읽기 실패 시
     */
    public function parseRules(string $filePath): array {
        if (!file_exists($filePath)) {
            throw new Exception("[RuleParser] {$this->currentFile}:" . __LINE__ . " - 파일을 찾을 수 없음: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("[RuleParser] {$this->currentFile}:" . __LINE__ . " - 파일 읽기 실패: {$filePath}");
        }

        // YAML 파싱 (Symfony YAML 또는 spyc 사용)
        if (function_exists('yaml_parse')) {
            // PHP YAML 확장 사용
            $rules = yaml_parse($content);
        } elseif (class_exists('Symfony\Component\Yaml\Yaml')) {
            // Symfony YAML 사용
            $rules = \Symfony\Component\Yaml\Yaml::parse($content);
        } else {
            // 간단한 자체 파서 사용
            $rules = $this->simpleYamlParse($content);
        }

        if ($rules === false || $rules === null) {
            throw new Exception("[RuleParser] {$this->currentFile}:" . __LINE__ . " - YAML 파싱 실패");
        }

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
    private function simpleYamlParse(string $content): array {
        $result = [];
        $lines = explode("\n", $content);
        $stack = [&$result];
        $indentStack = [-1];
        $currentKey = null;
        $inArray = false;
        $currentArray = [];

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

                    if (!isset($currentArray)) {
                        $currentArray = [];
                    }
                    $currentArray[$key] = $value;
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
    private function parseValue(string $value) {
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
