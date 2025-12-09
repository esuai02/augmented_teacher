<?php
/**
 * AbstractPersonaEngine - 페르소나 엔진 추상 기본 클래스
 *
 * 모든 에이전트의 페르소나 엔진이 상속하는 기본 구현체입니다.
 * 공통 기능을 구현하고, 에이전트별 확장 포인트를 제공합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Impl;

use AugmentedTeacher\PersonaEngine\Core\IPersonaEngine;
use AugmentedTeacher\PersonaEngine\Core\IConditionEvaluator;
use AugmentedTeacher\PersonaEngine\Core\IActionExecutor;
use AugmentedTeacher\PersonaEngine\Core\IDataContext;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

abstract class AbstractPersonaEngine implements IPersonaEngine {

    /** @var array 로드된 규칙 */
    protected $rules = [];

    /** @var IConditionEvaluator 조건 평가기 */
    protected $evaluator;

    /** @var IActionExecutor 액션 실행기 */
    protected $executor;

    /** @var IDataContext 데이터 컨텍스트 */
    protected $dataContext;

    /** @var string 현재 파일 경로 (디버깅용) */
    protected $currentFile = __FILE__;

    /** @var array 기본 설정 */
    protected $config = [
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'debug_mode' => false,
        'log_enabled' => true
    ];

    /** @var array 페르소나 이름 매핑 (하위 클래스에서 정의) */
    protected $personaNames = [];

    /**
     * 생성자
     *
     * @param array $config 설정 옵션
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);
        $this->initializeComponents();
    }

    /**
     * 컴포넌트 초기화 (하위 클래스에서 오버라이드 가능)
     */
    protected function initializeComponents(): void {
        $this->evaluator = $this->createConditionEvaluator();
        $this->executor = $this->createActionExecutor();
        $this->dataContext = $this->createDataContext();

        // 에이전트별 커스텀 핸들러 등록
        $this->registerCustomHandlers();
    }

    /**
     * 조건 평가기 생성 (팩토리 메서드)
     * @return IConditionEvaluator
     */
    abstract protected function createConditionEvaluator(): IConditionEvaluator;

    /**
     * 액션 실행기 생성 (팩토리 메서드)
     * @return IActionExecutor
     */
    abstract protected function createActionExecutor(): IActionExecutor;

    /**
     * 데이터 컨텍스트 생성 (팩토리 메서드)
     * @return IDataContext
     */
    abstract protected function createDataContext(): IDataContext;

    /**
     * 커스텀 핸들러 등록 (확장 포인트)
     */
    protected function registerCustomHandlers(): void {
        // 하위 클래스에서 오버라이드하여 커스텀 핸들러 등록
    }

    /**
     * {@inheritdoc}
     */
    public function loadRules(string $rulesPath): bool {
        try {
            if (!file_exists($rulesPath)) {
                throw new \Exception("규칙 파일을 찾을 수 없습니다: {$rulesPath}");
            }

            $content = file_get_contents($rulesPath);
            if ($content === false) {
                throw new \Exception("규칙 파일 읽기 실패: {$rulesPath}");
            }

            // YAML 파싱 (PHP 7.1 호환)
            $this->rules = $this->parseYaml($content);

            // 우선순위 정렬
            if (isset($this->rules['persona_identification_rules'])) {
                usort($this->rules['persona_identification_rules'], function($a, $b) {
                    $priorityA = $a['priority'] ?? 100;
                    $priorityB = $b['priority'] ?? 100;
                    return $priorityA - $priorityB;
                });
            }

            return true;

        } catch (\Exception $e) {
            $this->logError("규칙 로드 실패: " . $e->getMessage(), __LINE__);
            throw $e;
        }
    }

    /**
     * YAML 문자열 파싱 (간단한 구현)
     *
     * @param string $content YAML 문자열
     * @return array 파싱된 배열
     */
    protected function parseYaml(string $content): array {
        // yaml_parse 사용 가능 시 (PECL yaml)
        if (function_exists('yaml_parse')) {
            return yaml_parse($content) ?: [];
        }

        // Spyc 라이브러리 시도
        $spycPath = __DIR__ . '/../../lib/Spyc.php';
        if (file_exists($spycPath)) {
            require_once($spycPath);
            return \Spyc::YAMLLoadString($content);
        }

        // 간단한 YAML 파싱 (기본)
        return $this->simpleYamlParse($content);
    }

    /**
     * 간단한 YAML 파싱 구현
     *
     * @param string $content YAML 문자열
     * @return array
     */
    protected function simpleYamlParse(string $content): array {
        $lines = explode("\n", $content);
        $result = [];
        $stack = [&$result];
        $indentStack = [-1];

        foreach ($lines as $line) {
            // 주석 및 빈 줄 스킵
            if (preg_match('/^\s*#/', $line) || trim($line) === '') {
                continue;
            }

            // 들여쓰기 레벨 계산
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $trimmed = trim($line);

            // 스택 조정
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($stack);
                array_pop($indentStack);
            }

            // 리스트 아이템
            if (preg_match('/^-\s*(.*)/', $trimmed, $matches)) {
                $value = $this->parseYamlValue(trim($matches[1]));
                $current = &$stack[count($stack) - 1];
                if (!is_array($current)) {
                    $current = [];
                }
                $current[] = $value;
            }
            // 키: 값
            elseif (preg_match('/^([^:]+):\s*(.*)/', $trimmed, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);
                $current = &$stack[count($stack) - 1];

                if ($value === '' || $value === '|' || $value === '>') {
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    $current[$key] = $this->parseYamlValue($value);
                }
            }
        }

        return $result;
    }

    /**
     * YAML 값 파싱
     *
     * @param string $value
     * @return mixed
     */
    protected function parseYamlValue(string $value) {
        // 불리언
        if (in_array(strtolower($value), ['true', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', 'no', 'off'])) {
            return false;
        }
        // null
        if (in_array(strtolower($value), ['null', '~', ''])) {
            return null;
        }
        // 숫자
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        // 문자열 (따옴표 제거)
        return trim($value, '"\'');
    }

    /**
     * {@inheritdoc}
     */
    public function loadStudentContext(int $userId, array $sessionData = []): array {
        return $this->dataContext->buildFullContext($userId, $sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeMessage(array $context, string $message): array {
        $analysis = $this->dataContext->analyzeMessage($message);
        return array_merge($context, $analysis, ['user_message' => $message]);
    }

    /**
     * {@inheritdoc}
     */
    public function identifyPersona(array $context): array {
        $result = $this->getDefaultResult();

        if (empty($this->rules['persona_identification_rules'])) {
            $this->logWarning("페르소나 식별 규칙이 로드되지 않음", __LINE__);
            return $result;
        }

        // 우선순위 순서로 규칙 매칭
        foreach ($this->rules['persona_identification_rules'] as $rule) {
            if ($this->evaluateRule($rule, $context)) {
                $result = $this->applyRule($rule, $context);
                $this->logPersonaMatch($context['user_id'] ?? 0, $result);
                return $result;
            }
        }

        return $result;
    }

    /**
     * 규칙 평가
     *
     * @param array $rule 규칙
     * @param array $context 컨텍스트
     * @return bool 매칭 여부
     */
    protected function evaluateRule(array $rule, array $context): bool {
        if (!isset($rule['conditions'])) {
            return false;
        }

        foreach ($rule['conditions'] as $condition) {
            // OR 조건
            if (isset($condition['OR'])) {
                if (!$this->evaluator->evaluateOr($condition['OR'], $context)) {
                    return false;
                }
            }
            // AND 조건
            elseif (isset($condition['AND'])) {
                if (!$this->evaluator->evaluateAnd($condition['AND'], $context)) {
                    return false;
                }
            }
            // 단일 조건
            else {
                if (!$this->evaluator->evaluate($condition, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 규칙 적용
     *
     * @param array $rule 매칭된 규칙
     * @param array $context 컨텍스트
     * @return array 적용 결과
     */
    protected function applyRule(array $rule, array &$context): array {
        $result = [
            'matched_rule' => $rule['rule_id'] ?? 'unknown',
            'confidence' => $rule['confidence'] ?? 0.5,
            'actions' => []
        ];

        // 액션 실행
        if (isset($rule['action'])) {
            $actionResults = $this->executor->execute($rule['action'], $context);
            $result['actions'] = $actionResults;

            // 컨텍스트에서 결과 추출
            $result['persona_id'] = $context['persona_id'] ?? null;
            $result['persona_name'] = $this->getPersonaName($result['persona_id']);
            $result['tone'] = $context['tone'] ?? 'Professional';
            $result['pace'] = $context['pace'] ?? 'normal';
            $result['intervention'] = $context['intervention'] ?? null;
            $result['risk_level'] = $context['risk_level'] ?? null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        try {
            // 1. 학생 컨텍스트 로드
            $context = $this->loadStudentContext($userId, $sessionData);

            // 2. 메시지 분석
            $context = $this->analyzeMessage($context, $message);

            // 3. 페르소나 식별
            $identification = $this->identifyPersona($context);

            // 4. 응답 생성
            $response = $this->generateResponse($identification, $context);

            return [
                'success' => true,
                'user_id' => $userId,
                'persona' => $identification,
                'response' => $response,
                'context' => [
                    'risk_level' => $identification['risk_level'] ?? null,
                    'tone' => $identification['tone'] ?? 'Professional',
                    'intervention' => $identification['intervention'] ?? null
                ]
            ];

        } catch (\Exception $e) {
            $this->logError("프로세스 실행 실패: " . $e->getMessage(), __LINE__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_location' => $this->currentFile . ':' . __LINE__,
                'user_id' => $userId
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateResponse(array $identification, array $context, string $templateKey = 'default'): array {
        // 템플릿 키 자동 결정
        if ($templateKey === 'default') {
            $templateKey = $this->determineTemplateKey($identification, $context);
        }

        return [
            'text' => $this->buildResponseText($identification, $context, $templateKey),
            'template_key' => $templateKey,
            'tone' => $identification['tone'] ?? 'Professional',
            'intervention' => $identification['intervention'] ?? null,
            'persona_id' => $identification['persona_id'] ?? null,
            'confidence' => $identification['confidence'] ?? 0.5
        ];
    }

    /**
     * 템플릿 키 자동 결정 (하위 클래스에서 오버라이드)
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return string 템플릿 키
     */
    protected function determineTemplateKey(array $identification, array $context): string {
        return 'default';
    }

    /**
     * 응답 텍스트 빌드 (하위 클래스에서 오버라이드)
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @param string $templateKey 템플릿 키
     * @return string 응답 텍스트
     */
    protected function buildResponseText(array $identification, array $context, string $templateKey): string {
        $personaName = $identification['persona_name'] ?? '미식별';
        $studentName = $context['firstname'] ?? '학생';

        return "{$studentName}님, 현재 상태를 파악했습니다. (페르소나: {$personaName})";
    }

    /**
     * 페르소나 이름 조회
     *
     * @param string|null $personaId 페르소나 ID
     * @return string 페르소나 이름
     */
    protected function getPersonaName(?string $personaId): string {
        if ($personaId === null) {
            return '미식별';
        }
        return $this->personaNames[$personaId] ?? '미식별';
    }

    /**
     * 기본 결과 반환
     *
     * @return array
     */
    protected function getDefaultResult(): array {
        return [
            'persona_id' => null,
            'persona_name' => '미식별',
            'confidence' => 0,
            'matched_rule' => null,
            'tone' => 'Professional',
            'pace' => 'normal',
            'intervention' => null,
            'risk_level' => null,
            'actions' => []
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(?string $key = null) {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config): void {
        $this->config = array_merge($this->config, $config);
    }

    // ===== 로깅 메서드 =====

    /**
     * 에러 로깅
     */
    protected function logError(string $message, int $line): void {
        if ($this->config['log_enabled']) {
            error_log("[PersonaEngine ERROR] {$this->currentFile}:{$line} - {$message}");
        }
    }

    /**
     * 경고 로깅
     */
    protected function logWarning(string $message, int $line): void {
        if ($this->config['log_enabled']) {
            error_log("[PersonaEngine WARN] {$this->currentFile}:{$line} - {$message}");
        }
    }

    /**
     * 페르소나 매칭 로깅
     */
    protected function logPersonaMatch(int $userId, array $result): void {
        if ($this->config['debug_mode']) {
            $personaId = $result['persona_id'] ?? 'null';
            $confidence = $result['confidence'] ?? 0;
            error_log("[PersonaEngine MATCH] User:{$userId} -> Persona:{$personaId} (conf:{$confidence})");
        }
    }
}

/*
 * 구현 가이드:
 *
 * 1. 에이전트별 엔진은 AbstractPersonaEngine을 상속
 * 2. 필수 구현:
 *    - createConditionEvaluator(): 조건 평가기 생성
 *    - createActionExecutor(): 액션 실행기 생성
 *    - createDataContext(): 데이터 컨텍스트 생성
 *    - getAgentId(): 에이전트 번호 반환
 *    - getAgentName(): 에이전트 이름 반환
 *
 * 3. 선택적 오버라이드:
 *    - registerCustomHandlers(): 커스텀 핸들러 등록
 *    - determineTemplateKey(): 템플릿 키 결정 로직
 *    - buildResponseText(): 응답 텍스트 생성 로직
 *
 * 4. 페르소나 이름 매핑:
 *    protected $personaNames = [
 *        'R_High_M' => '고위험 동기저하형',
 *        'R_Med_R' => '중위험 루틴붕괴형',
 *        ...
 *    ];
 *
 * 파일 위치: ontology_engineering/persona_engine/impl/AbstractPersonaEngine.php
 */
