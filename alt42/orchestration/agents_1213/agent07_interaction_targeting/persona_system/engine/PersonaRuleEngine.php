<?php
/**
 * Agent07 Persona Rule Engine
 *
 * 페르소나 식별을 위한 핵심 엔진 클래스
 * 상황(Situation) 식별 → 페르소나(Persona) 식별 2단계 프로세스
 *
 * @version 1.0
 * @author Agent07 Interaction Targeting
 * @requires PHP 7.1.9+
 *
 * Related Files:
 * - rules.yaml: 페르소나 식별 규칙
 * - DataContext.php: 컨텍스트 데이터 수집
 * - RuleParser.php: YAML 규칙 파싱
 * - ConditionEvaluator.php: 조건 평가
 *
 * DB Tables:
 * - mdl_agent07_persona_log: 페르소나 식별 로그
 * - mdl_agent07_context_log: 컨텍스트 로그
 */

// Error reporting with file and line info
error_reporting(E_ALL);
ini_set('display_errors', 1);

class PersonaRuleEngine {

    /** @var array 파싱된 규칙 데이터 */
    private $rules;

    /** @var DataContext 컨텍스트 데이터 객체 */
    private $context;

    /** @var RuleParser 규칙 파서 */
    private $ruleParser;

    /** @var ConditionEvaluator 조건 평가기 */
    private $conditionEvaluator;

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var int 현재 사용자 ID */
    private $userId;

    /** @var array 설정값 */
    private $config;

    /** @var string 규칙 파일 경로 */
    private $rulesPath;

    /** @var array 캐시된 규칙 */
    private static $rulesCache = null;

    /**
     * 생성자
     *
     * @param object $db Moodle $DB 객체
     * @param int $userId 사용자 ID
     * @param array $config 설정 배열 (선택)
     */
    public function __construct($db, $userId, $config = array()) {
        $this->db = $db;
        $this->userId = $userId;
        $this->config = array_merge($this->getDefaultConfig(), $config);

        $this->rulesPath = dirname(__FILE__) . '/../rules.yaml';

        $this->initializeComponents();
    }

    /**
     * 기본 설정값 반환
     *
     * @return array
     */
    private function getDefaultConfig() {
        return array(
            'base_confidence' => 0.5,
            'condition_match_boost' => 0.1,
            'all_conditions_bonus' => 0.2,
            'keyword_match_boost' => 0.05,
            'min_confidence' => 0.3,
            'max_confidence' => 1.0,
            'enable_logging' => true,
            'cache_rules' => true
        );
    }

    /**
     * 컴포넌트 초기화
     */
    private function initializeComponents() {
        require_once dirname(__FILE__) . '/RuleParser.php';
        require_once dirname(__FILE__) . '/ConditionEvaluator.php';
        require_once dirname(__FILE__) . '/DataContext.php';

        $this->ruleParser = new RuleParser($this->rulesPath);
        $this->conditionEvaluator = new ConditionEvaluator();
        $this->context = new DataContext($this->db, $this->userId);

        $this->loadRules();
    }

    /**
     * 규칙 로드
     */
    private function loadRules() {
        if ($this->config['cache_rules'] && self::$rulesCache !== null) {
            $this->rules = self::$rulesCache;
            return;
        }

        $this->rules = $this->ruleParser->parse();

        if ($this->config['cache_rules']) {
            self::$rulesCache = $this->rules;
        }
    }

    /**
     * 페르소나 식별 메인 메서드
     *
     * @param array $inputData 추가 입력 데이터 (메시지 등)
     * @return array 식별 결과
     */
    public function identifyPersona($inputData = array()) {
        try {
            // 1. 컨텍스트 데이터 수집
            $contextData = $this->context->collect($inputData);

            // 2. 상황(Situation) 식별
            $situationResult = $this->identifySituation($contextData);

            // 3. 페르소나(Persona) 식별
            $personaResult = $this->identifyPersonaForSituation(
                $situationResult['situation_id'],
                $contextData
            );

            // 4. 결과 조합
            $result = array(
                'success' => true,
                'situation' => $situationResult,
                'persona' => $personaResult,
                'context_snapshot' => $this->getContextSnapshot($contextData),
                'timestamp' => date('Y-m-d H:i:s')
            );

            // 5. 로깅
            if ($this->config['enable_logging']) {
                $this->logIdentification($result);
            }

            return $result;

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'timestamp' => date('Y-m-d H:i:s')
            );
        }
    }

    /**
     * 상황(Situation) 식별
     *
     * @param array $contextData 컨텍스트 데이터
     * @return array
     */
    private function identifySituation($contextData) {
        $situationRules = isset($this->rules['situation_rules'])
            ? $this->rules['situation_rules']
            : array();

        $matches = array();

        foreach ($situationRules as $ruleKey => $rule) {
            $evaluation = $this->conditionEvaluator->evaluate(
                isset($rule['conditions']) ? $rule['conditions'] : array(),
                $contextData
            );

            if ($evaluation['matched']) {
                $matches[] = array(
                    'rule_key' => $ruleKey,
                    'situation_id' => $rule['id'],
                    'name' => $rule['name'],
                    'priority' => isset($rule['priority']) ? $rule['priority'] : 50,
                    'confidence' => $evaluation['confidence'],
                    'matched_conditions' => $evaluation['matched_conditions']
                );
            }
        }

        // 우선순위와 신뢰도로 정렬
        usort($matches, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] - $a['priority'];
            }
            return $b['confidence'] <=> $a['confidence'];
        });

        if (!empty($matches)) {
            return $matches[0];
        }

        // Fallback
        return $this->getSituationFallback();
    }

    /**
     * 특정 상황에 대한 페르소나 식별
     *
     * @param string $situationId 상황 ID
     * @param array $contextData 컨텍스트 데이터
     * @return array
     */
    private function identifyPersonaForSituation($situationId, $contextData) {
        $personaRules = isset($this->rules['persona_rules'])
            ? $this->rules['persona_rules']
            : array();

        $matches = array();

        foreach ($personaRules as $ruleKey => $rule) {
            // 해당 상황의 페르소나 규칙만 평가
            if (!isset($rule['situation']) || $rule['situation'] !== $situationId) {
                continue;
            }

            $evaluation = $this->conditionEvaluator->evaluate(
                isset($rule['conditions']) ? $rule['conditions'] : array(),
                $contextData
            );

            if ($evaluation['matched']) {
                $confidence = $evaluation['confidence'];

                // 신뢰도 부스트 적용
                if (isset($rule['confidence_boost'])) {
                    $confidence += $rule['confidence_boost'];
                }

                // 최대/최소 제한
                $confidence = max(
                    $this->config['min_confidence'],
                    min($this->config['max_confidence'], $confidence)
                );

                $matches[] = array(
                    'rule_key' => $ruleKey,
                    'persona_id' => $rule['id'],
                    'name' => $rule['name'],
                    'priority' => isset($rule['priority']) ? $rule['priority'] : 50,
                    'confidence' => $confidence,
                    'matched_conditions' => $evaluation['matched_conditions'],
                    'is_default' => isset($rule['default_for_situation'])
                        ? $rule['default_for_situation']
                        : false
                );
            }
        }

        // 우선순위와 신뢰도로 정렬
        usort($matches, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] - $a['priority'];
            }
            return $b['confidence'] <=> $a['confidence'];
        });

        if (!empty($matches)) {
            $selected = $matches[0];
            $selected['response_config'] = $this->getResponseConfig($selected['persona_id']);
            return $selected;
        }

        // Fallback
        return $this->getPersonaFallback($situationId);
    }

    /**
     * 상황 Fallback 반환
     *
     * @return array
     */
    private function getSituationFallback() {
        $fallback = isset($this->rules['fallback_rules']['situation_fallback'])
            ? $this->rules['fallback_rules']['situation_fallback']
            : array();

        return array(
            'situation_id' => isset($fallback['default_situation'])
                ? $fallback['default_situation']
                : 'S4',
            'name' => '목표설정 (기본값)',
            'priority' => 0,
            'confidence' => isset($fallback['confidence'])
                ? $fallback['confidence']
                : 0.3,
            'matched_conditions' => array(),
            'is_fallback' => true
        );
    }

    /**
     * 페르소나 Fallback 반환
     *
     * @param string $situationId 상황 ID
     * @return array
     */
    private function getPersonaFallback($situationId) {
        $fallback = isset($this->rules['fallback_rules']['persona_fallback'])
            ? $this->rules['fallback_rules']['persona_fallback']
            : array();

        $defaults = isset($fallback['defaults']) ? $fallback['defaults'] : array();
        $personaId = isset($defaults[$situationId])
            ? $defaults[$situationId]
            : $situationId . '_P2';

        $result = array(
            'persona_id' => $personaId,
            'name' => $personaId . ' (기본값)',
            'priority' => 0,
            'confidence' => isset($fallback['confidence'])
                ? $fallback['confidence']
                : 0.4,
            'matched_conditions' => array(),
            'is_fallback' => true
        );

        $result['response_config'] = $this->getResponseConfig($personaId);

        return $result;
    }

    /**
     * 응답 설정 가져오기
     *
     * @param string $personaId 페르소나 ID
     * @return array
     */
    private function getResponseConfig($personaId) {
        $mapping = isset($this->rules['response_mapping'][$personaId])
            ? $this->rules['response_mapping'][$personaId]
            : array();

        return array(
            'template' => isset($mapping['template'])
                ? $mapping['template']
                : 'templates/default.php',
            'tone' => isset($mapping['tone'])
                ? $mapping['tone']
                : 'neutral, supportive'
        );
    }

    /**
     * 컨텍스트 스냅샷 생성
     *
     * @param array $contextData 전체 컨텍스트 데이터
     * @return array 주요 컨텍스트만 포함
     */
    private function getContextSnapshot($contextData) {
        $keyFields = array(
            'current_activity',
            'pomodoro_active',
            'focus_score',
            'motivation_score',
            'anxiety_signals',
            'goal_clarity'
        );

        $snapshot = array();
        foreach ($keyFields as $field) {
            if (isset($contextData[$field])) {
                $snapshot[$field] = $contextData[$field];
            }
        }

        return $snapshot;
    }

    /**
     * 식별 결과 로깅
     *
     * @param array $result 식별 결과
     */
    private function logIdentification($result) {
        try {
            $record = new stdClass();
            $record->userid = $this->userId;
            $record->situation_id = $result['situation']['situation_id'];
            $record->persona_id = $result['persona']['persona_id'];
            $record->confidence_score = $result['persona']['confidence'];
            $record->context_data = json_encode($result['context_snapshot']);
            $record->created_at = time();

            $this->db->insert_record('agent07_persona_log', $record);

        } catch (Exception $e) {
            // 로깅 실패는 무시 (메인 프로세스 중단 방지)
            error_log(sprintf(
                "[PersonaRuleEngine] Logging failed: %s (File: %s, Line: %d)",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }

    /**
     * 규칙 캐시 초기화
     */
    public static function clearCache() {
        self::$rulesCache = null;
    }

    /**
     * 디버그 정보 반환
     *
     * @return array
     */
    public function getDebugInfo() {
        return array(
            'rules_loaded' => !empty($this->rules),
            'rules_path' => $this->rulesPath,
            'situation_rules_count' => isset($this->rules['situation_rules'])
                ? count($this->rules['situation_rules'])
                : 0,
            'persona_rules_count' => isset($this->rules['persona_rules'])
                ? count($this->rules['persona_rules'])
                : 0,
            'config' => $this->config,
            'user_id' => $this->userId
        );
    }
}

/*
 * DB Table: mdl_agent07_persona_log
 *
 * CREATE TABLE mdl_agent07_persona_log (
 *     id BIGINT(10) NOT NULL AUTO_INCREMENT,
 *     userid BIGINT(10) NOT NULL,
 *     situation_id VARCHAR(10) NOT NULL,
 *     persona_id VARCHAR(10) NOT NULL,
 *     confidence_score DECIMAL(3,2) NOT NULL,
 *     context_data LONGTEXT,
 *     created_at BIGINT(10) NOT NULL,
 *     PRIMARY KEY (id),
 *     INDEX idx_userid (userid),
 *     INDEX idx_persona (persona_id),
 *     INDEX idx_created (created_at)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
