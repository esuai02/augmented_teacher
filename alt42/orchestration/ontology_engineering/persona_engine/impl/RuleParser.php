<?php
/**
 * RuleParser - YAML/JSON 규칙 파서 구현
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 */

require_once(__DIR__ . '/../core/IRuleParser.php');

class RuleParser implements IRuleParser {

    /** @var array 파싱된 규칙 */
    private $rules = [];

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 규칙 파일 로드
     */
    public function load(string $filePath): array {
        if (!file_exists($filePath)) {
            throw new Exception("규칙 파일을 찾을 수 없습니다: {$filePath} [{$this->currentFile}:" . __LINE__ . "]");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("규칙 파일 읽기 실패: {$filePath} [{$this->currentFile}:" . __LINE__ . "]");
        }

        $this->rules = $this->parse($content);
        return $this->rules;
    }

    /**
     * 규칙 문자열 파싱
     */
    public function parse(string $content): array {
        // YAML 파싱 시도
        if (function_exists('yaml_parse')) {
            $parsed = @yaml_parse($content);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        // JSON 파싱 시도
        $json = @json_decode($content, true);
        if ($json !== null) {
            return $json;
        }

        // 간단 텍스트 파서 (폴백)
        return $this->parseSimpleFormat($content);
    }

    /**
     * 간단 텍스트 형식 파싱 (YAML 라이브러리 없는 환경용)
     */
    private function parseSimpleFormat(string $content): array {
        $lines = explode("\n", $content);
        $result = ['personas' => []];
        $currentPersona = null;
        $currentSection = null;
        $indentLevel = 0;

        foreach ($lines as $lineNum => $line) {
            // 빈 줄과 주석 건너뛰기
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }

            // 들여쓰기 레벨 계산
            $spaces = strlen($line) - strlen(ltrim($line));
            $newIndentLevel = floor($spaces / 2);

            // personas: 섹션 시작
            if ($trimmed === 'personas:') {
                $currentSection = 'personas';
                continue;
            }

            // 새 페르소나 시작 (- id: xxx 형식)
            if (preg_match('/^-\s*id:\s*(.+)$/', $trimmed, $matches)) {
                if ($currentPersona !== null) {
                    $result['personas'][] = $currentPersona;
                }
                $currentPersona = [
                    'id' => trim($matches[1]),
                    'conditions' => [],
                    'actions' => [],
                    'responses' => []
                ];
                continue;
            }

            // 키-값 파싱
            if ($currentPersona !== null && preg_match('/^(\w+):\s*(.*)$/', $trimmed, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2]);

                // 배열 값 처리
                if (empty($value)) {
                    // 다음 라인에서 배열 값 수집
                    continue;
                }

                // 숫자 변환
                if (is_numeric($value)) {
                    $value = $value + 0;
                }
                // 불리언 변환
                elseif ($value === 'true') {
                    $value = true;
                }
                elseif ($value === 'false') {
                    $value = false;
                }

                $currentPersona[$key] = $value;
            }
        }

        // 마지막 페르소나 추가
        if ($currentPersona !== null) {
            $result['personas'][] = $currentPersona;
        }

        return $result;
    }

    /**
     * 규칙 유효성 검증
     */
    public function validate(array $rules): array {
        $errors = [];

        if (!isset($rules['personas']) || !is_array($rules['personas'])) {
            $errors[] = "personas 배열이 필요합니다";
            return ['valid' => false, 'errors' => $errors];
        }

        foreach ($rules['personas'] as $idx => $persona) {
            if (!isset($persona['id'])) {
                $errors[] = "페르소나 #{$idx}: id가 필요합니다";
            }
            if (!isset($persona['name'])) {
                $errors[] = "페르소나 #{$idx}: name이 필요합니다";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 특정 페르소나의 규칙 추출
     */
    public function getRulesForPersona(string $personaId): array {
        foreach ($this->rules['personas'] ?? [] as $persona) {
            if (($persona['id'] ?? '') === $personaId) {
                return $persona;
            }
        }
        return [];
    }

    /**
     * 전체 규칙 반환
     */
    public function getAllRules(): array {
        return $this->rules;
    }
}

/*
 * 관련 DB 테이블:
 * - 직접적인 DB 연동 없음 (파일 기반)
 */
