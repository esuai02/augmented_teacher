<?php
/**
 * Agent17RuleParser - 규칙 파서 Fallback 구현체
 *
 * BaseRuleParser가 없을 경우 사용되는 Agent17 전용 규칙 파서
 * YAML 및 JSON 형식의 규칙 파일을 파싱하고 검증합니다.
 *
 * @package AugmentedTeacher\Agent17\PersonaEngine\Fallback
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

// 인터페이스 로드
$corePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/';
require_once($corePath . 'IRuleParser.php');

use AugmentedTeacher\PersonaEngine\Core\IRuleParser;

/**
 * Agent17 전용 규칙 파서 (BaseRuleParser 없을 경우 사용)
 */
class Agent17RuleParser implements IRuleParser {
    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /**
     * 파일에서 규칙 파싱
     *
     * @param string $filePath 규칙 파일 경로
     * @return array 파싱된 규칙 배열
     */
    public function parse(string $filePath): array {
        if (!file_exists($filePath)) {
            error_log("[Agent17RuleParser] {$this->currentFile}:" . __LINE__ .
                " - 규칙 파일을 찾을 수 없음: {$filePath}");
            return [];
        }

        $content = file_get_contents($filePath);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($ext, ['yaml', 'yml'])) {
            if (function_exists('yaml_parse')) {
                $rules = yaml_parse($content);
            } else {
                // yaml 확장이 없는 경우 간단한 파싱 시도
                $rules = $this->simpleYamlParse($content);
            }
        } else {
            $rules = json_decode($content, true);
        }

        if ($rules === false || $rules === null) {
            error_log("[Agent17RuleParser] {$this->currentFile}:" . __LINE__ .
                " - 파싱 실패: {$filePath}");
            return [];
        }

        return $rules;
    }

    /**
     * 문자열에서 규칙 파싱
     *
     * @param string $content 규칙 내용
     * @param string $format 포맷 (yaml 또는 json)
     * @return array 파싱된 규칙 배열
     */
    public function parseString(string $content, string $format = 'yaml'): array {
        if ($format === 'yaml') {
            if (function_exists('yaml_parse')) {
                return yaml_parse($content) ?: [];
            }
            return $this->simpleYamlParse($content);
        }
        return json_decode($content, true) ?: [];
    }

    /**
     * 규칙 유효성 검사
     *
     * @param array $rules 검사할 규칙 배열
     * @return array 검사 결과 ['valid' => bool, 'errors' => array]
     */
    public function validate(array $rules): array {
        $errors = [];
        if (!isset($rules['rules']) && !isset($rules['personas'])) {
            $errors[] = '규칙 배열에 rules 또는 personas 키가 필요합니다';
        }
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * 규칙을 파일로 저장
     *
     * @param array $rules 저장할 규칙 배열
     * @param string $filePath 저장 경로
     * @param string $format 포맷 (yaml 또는 json)
     * @return bool 성공 여부
     */
    public function save(array $rules, string $filePath, string $format = 'yaml'): bool {
        if ($format === 'yaml' && function_exists('yaml_emit')) {
            $content = yaml_emit($rules);
        } else {
            $content = json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        return file_put_contents($filePath, $content) !== false;
    }

    /**
     * 지원 포맷 목록 반환
     *
     * @return array 지원 포맷 배열
     */
    public function getSupportedFormats(): array {
        return ['yaml', 'json'];
    }

    /**
     * 간단한 YAML 파서 (yaml 확장이 없을 경우)
     *
     * @param string $content YAML 내용
     * @return array 파싱된 배열
     */
    private function simpleYamlParse(string $content): array {
        // 매우 간단한 YAML 파서 (실제로는 yaml 확장 필요)
        $lines = explode("\n", $content);
        $result = [];
        foreach ($lines as $line) {
            if (preg_match('/^(\s*)(\w+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[2];
                $value = trim($matches[3]);
                if ($value !== '') {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}

/*
 * 관련 인터페이스: IRuleParser
 * 위치: /ontology_engineering/persona_engine/core/IRuleParser.php
 *
 * 메서드:
 * - parse(string $filePath): array
 * - parseString(string $content, string $format): array
 * - validate(array $rules): array
 * - save(array $rules, string $filePath, string $format): bool
 * - getSupportedFormats(): array
 */
