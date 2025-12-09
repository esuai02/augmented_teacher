<?php
/**
 * SimpleYamlParser - 경량 YAML 파서
 *
 * PHP yaml 확장이 없는 환경에서 사용할 수 있는 간단한 YAML 파서
 * 기본적인 YAML 구문만 지원 (배열, 객체, 문자열, 숫자, 불린)
 *
 * @package AugmentedTeacher\PersonaEngine\Lib
 * @version 1.0
 */

class SimpleYamlParser {

    /**
     * YAML 문자열을 PHP 배열로 파싱
     *
     * @param string $yaml YAML 형식 문자열
     * @return array 파싱된 배열
     */
    public static function parse($yaml) {
        // 줄 단위로 분리
        $lines = explode("\n", $yaml);
        $result = [];
        $stack = [&$result];
        $indentStack = [-1];
        $currentIndent = 0;
        $inMultiline = false;
        $multilineKey = '';
        $multilineValue = '';
        $multilineIndent = 0;

        foreach ($lines as $lineNum => $line) {
            // 빈 줄 무시
            if (trim($line) === '') {
                if ($inMultiline) {
                    $multilineValue .= "\n";
                }
                continue;
            }

            // 주석 무시
            $trimmed = ltrim($line);
            if (strpos($trimmed, '#') === 0) {
                continue;
            }

            // 현재 들여쓰기 계산
            $indent = strlen($line) - strlen($trimmed);

            // 멀티라인 처리
            if ($inMultiline) {
                if ($indent > $multilineIndent) {
                    $multilineValue .= substr($line, $multilineIndent) . "\n";
                    continue;
                } else {
                    // 멀티라인 종료
                    $inMultiline = false;
                    $current = &$stack[count($stack) - 1];
                    $current[$multilineKey] = rtrim($multilineValue);
                }
            }

            // --- 구분자 무시
            if (trim($line) === '---') {
                continue;
            }

            // 들여쓰기에 따라 스택 조정
            while (count($indentStack) > 1 && $indent <= $indentStack[count($indentStack) - 1]) {
                array_pop($stack);
                array_pop($indentStack);
            }

            $current = &$stack[count($stack) - 1];

            // 리스트 항목 (- 로 시작)
            if (preg_match('/^-\s+(.*)$/', $trimmed, $matches)) {
                $value = $matches[1];

                // 리스트 항목이 객체인 경우
                if (preg_match('/^([^:]+):\s*(.*)$/', $value, $kvMatch)) {
                    $item = [];
                    $item[trim($kvMatch[1])] = self::parseValue(trim($kvMatch[2]));
                    $current[] = $item;

                    // 다음 항목들을 위해 스택 업데이트
                    $lastIndex = count($current) - 1;
                    $stack[] = &$current[$lastIndex];
                    $indentStack[] = $indent + 2;
                } else {
                    $current[] = self::parseValue($value);
                }
                continue;
            }

            // 키: 값 쌍
            if (preg_match('/^([^:]+):\s*(.*)$/', $trimmed, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);

                // 멀티라인 시작 (| 또는 >)
                if ($value === '|' || $value === '>') {
                    $inMultiline = true;
                    $multilineKey = $key;
                    $multilineValue = '';
                    $multilineIndent = $indent + 2;
                    continue;
                }

                // 값이 없으면 하위 객체/배열
                if ($value === '') {
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    $current[$key] = self::parseValue($value);
                }
                continue;
            }
        }

        return $result;
    }

    /**
     * 값 파싱 (타입 변환)
     *
     * @param string $value 원시 값
     * @return mixed 변환된 값
     */
    private static function parseValue($value) {
        // 빈 값
        if ($value === '' || $value === '~' || strtolower($value) === 'null') {
            return null;
        }

        // 불린
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes' || $lower === 'on') {
            return true;
        }
        if ($lower === 'false' || $lower === 'no' || $lower === 'off') {
            return false;
        }

        // 숫자
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                return (float) $value;
            }
            return (int) $value;
        }

        // 소수점 (0.xx 형식)
        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            return (float) $value;
        }

        // 인라인 배열 [a, b, c]
        if (preg_match('/^\[(.+)\]$/', $value, $matches)) {
            $items = array_map('trim', explode(',', $matches[1]));
            return array_map([self::class, 'parseValue'], $items);
        }

        // 인라인 객체 {a: 1, b: 2}
        if (preg_match('/^\{(.+)\}$/', $value, $matches)) {
            $result = [];
            $pairs = explode(',', $matches[1]);
            foreach ($pairs as $pair) {
                if (strpos($pair, ':') !== false) {
                    list($k, $v) = explode(':', $pair, 2);
                    $result[trim($k)] = self::parseValue(trim($v));
                }
            }
            return $result;
        }

        // 따옴표 제거
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }

        // 문자열 그대로 반환
        return $value;
    }

    /**
     * YAML 파일 로드
     *
     * @param string $filePath 파일 경로
     * @return array|false 파싱된 배열 또는 실패 시 false
     */
    public static function parseFile($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        return self::parse($content);
    }
}
