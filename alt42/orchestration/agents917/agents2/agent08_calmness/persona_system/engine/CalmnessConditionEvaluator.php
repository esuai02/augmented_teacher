<?php
/**
 * CalmnessConditionEvaluator - Agent08 전용 조건 평가기
 *
 * BaseConditionEvaluator를 확장하여 침착성(Calmness) 관련 조건을 평가합니다.
 * 침착성 레벨, 불안 트리거, 위기 상태, 호흡/그라운딩 필요성 등을 지원합니다.
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 * @author Claude Code
 */

// 기본 조건 평가기 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php');

use AugmentedTeacher\PersonaEngine\Impl\BaseConditionEvaluator;

class CalmnessConditionEvaluator extends BaseConditionEvaluator {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 침착성 레벨 점수 범위 */
    private $calmnessLevelRanges = [
        'C95' => ['min' => 95, 'max' => 100],
        'C90' => ['min' => 90, 'max' => 94],
        'C85' => ['min' => 85, 'max' => 89],
        'C80' => ['min' => 80, 'max' => 84],
        'C75' => ['min' => 75, 'max' => 79],
        'C_crisis' => ['min' => 0, 'max' => 74]
    ];

    /** @var array 침착성 레벨 순서 (높은 침착성 → 낮은 침착성) */
    private $calmnessLevelOrder = ['C95', 'C90', 'C85', 'C80', 'C75', 'C_crisis'];

    /** @var array 불안 트리거 카테고리 */
    private $anxietyTriggerCategories = [
        'time_pressure',
        'performance',
        'social',
        'uncertainty',
        'physical'
    ];

    /** @var array 위기 심각도 순서 */
    private $crisisSeverityOrder = ['critical', 'high', 'moderate', 'low', 'none'];

    /**
     * 생성자 - 침착성 특화 연산자 등록
     *
     * @param bool $debugMode 디버그 모드
     */
    public function __construct(bool $debugMode = false) {
        parent::__construct($debugMode);
        $this->registerCalmnessOperators();
    }

    /**
     * 침착성 특화 연산자 등록
     */
    private function registerCalmnessOperators(): void {
        // ========================================
        // 침착성 레벨 연산자
        // ========================================

        // 특정 침착성 레벨인지 확인
        $this->registerOperator('calmness_level_is', function($actual, $expected) {
            return $this->normalizeCalmnessLevel($actual) === $this->normalizeCalmnessLevel($expected);
        });

        // 침착성 레벨이 특정 레벨 이상인지 (더 침착한지)
        $this->registerOperator('calmness_level_above', function($actual, $threshold) {
            $actualIndex = array_search($this->normalizeCalmnessLevel($actual), $this->calmnessLevelOrder);
            $thresholdIndex = array_search($this->normalizeCalmnessLevel($threshold), $this->calmnessLevelOrder);
            return $actualIndex !== false && $thresholdIndex !== false && $actualIndex < $thresholdIndex;
        });

        // 침착성 레벨이 특정 레벨 이하인지 (더 불안한지)
        $this->registerOperator('calmness_level_below', function($actual, $threshold) {
            $actualIndex = array_search($this->normalizeCalmnessLevel($actual), $this->calmnessLevelOrder);
            $thresholdIndex = array_search($this->normalizeCalmnessLevel($threshold), $this->calmnessLevelOrder);
            return $actualIndex !== false && $thresholdIndex !== false && $actualIndex > $thresholdIndex;
        });

        // 침착성 점수가 특정 범위 내인지
        $this->registerOperator('calmness_score_between', function($score, $range) {
            if (!is_array($range) || count($range) < 2) return false;
            return $score >= $range[0] && $score <= $range[1];
        });

        // 침착성 점수로 레벨 매칭
        $this->registerOperator('calmness_score_at_level', function($score, $level) {
            $level = $this->normalizeCalmnessLevel($level);
            if (!isset($this->calmnessLevelRanges[$level])) return false;
            $range = $this->calmnessLevelRanges[$level];
            return $score >= $range['min'] && $score <= $range['max'];
        });

        // ========================================
        // 위기 상태 연산자
        // ========================================

        // 위기 상태 활성화 여부
        $this->registerOperator('crisis_active', function($context, $ignored) {
            if (is_array($context)) {
                return isset($context['is_crisis']) && $context['is_crisis'] === true;
            }
            return $context === true || $context === 'crisis' || $context === 'C_crisis';
        });

        // 위기 심각도 확인
        $this->registerOperator('crisis_severity_is', function($actual, $expected) {
            return strtolower($actual) === strtolower($expected);
        });

        // 위기 심각도가 특정 레벨 이상인지
        $this->registerOperator('crisis_severity_above', function($actual, $threshold) {
            $actualIndex = array_search(strtolower($actual), $this->crisisSeverityOrder);
            $thresholdIndex = array_search(strtolower($threshold), $this->crisisSeverityOrder);
            return $actualIndex !== false && $thresholdIndex !== false && $actualIndex < $thresholdIndex;
        });

        // 위기 지표 포함 여부
        $this->registerOperator('has_crisis_indicator', function($indicators, $type) {
            if (!is_array($indicators)) return false;
            foreach ($indicators as $indicator) {
                if (isset($indicator['type']) && $indicator['type'] === $type) {
                    return true;
                }
            }
            return false;
        });

        // ========================================
        // 불안 트리거 연산자
        // ========================================

        // 불안 트리거 존재 여부
        $this->registerOperator('has_anxiety_trigger', function($triggers, $category) {
            if (!is_array($triggers)) return false;
            if ($category === 'any') {
                return !empty($triggers);
            }
            return isset($triggers[$category]) && !empty($triggers[$category]);
        });

        // 불안 트리거 개수
        $this->registerOperator('anxiety_trigger_count', function($triggers, $threshold) {
            if (!is_array($triggers)) return false;
            $count = 0;
            foreach ($triggers as $category => $items) {
                if (is_array($items)) {
                    $count += count($items);
                }
            }
            return $count >= $threshold;
        });

        // 특정 불안 카테고리 활성화 여부
        $this->registerOperator('anxiety_category_active', function($triggers, $categories) {
            if (!is_array($triggers)) return false;
            $categories = is_array($categories) ? $categories : [$categories];
            foreach ($categories as $category) {
                if (isset($triggers[$category]) && !empty($triggers[$category])) {
                    return true;
                }
            }
            return false;
        });

        // ========================================
        // 감정 상태 연산자
        // ========================================

        // 감정 상태 확인
        $this->registerOperator('emotional_state_is', function($actual, $expected) {
            return strtolower($actual) === strtolower($expected);
        });

        // 부정적 감정 상태인지
        $this->registerOperator('is_negative_emotion', function($state, $ignored) {
            $negativeStates = ['anxious', 'stressed', 'frustrated', 'overwhelmed', 'panic', 'fearful', 'negative'];
            return in_array(strtolower($state), $negativeStates);
        });

        // 긍정적 감정 상태인지
        $this->registerOperator('is_positive_emotion', function($state, $ignored) {
            $positiveStates = ['calm', 'focused', 'relaxed', 'peaceful', 'confident', 'positive'];
            return in_array(strtolower($state), $positiveStates);
        });

        // 감정 강도 확인
        $this->registerOperator('emotion_intensity_above', function($intensity, $threshold) {
            return is_numeric($intensity) && is_numeric($threshold) && $intensity > $threshold;
        });

        // ========================================
        // 호흡/그라운딩 운동 필요성 연산자
        // ========================================

        // 호흡 운동 필요 여부
        $this->registerOperator('needs_breathing', function($context, $ignored) {
            if (is_array($context)) {
                if (isset($context['breathing_suggestion']) && $context['breathing_suggestion']) {
                    return true;
                }
                if (isset($context['calmness_level'])) {
                    return in_array($context['calmness_level'], ['C80', 'C75', 'C_crisis']);
                }
            }
            return $context === true;
        });

        // 그라운딩 운동 필요 여부
        $this->registerOperator('needs_grounding', function($context, $ignored) {
            if (is_array($context)) {
                if (isset($context['grounding_needed']) && $context['grounding_needed']) {
                    return true;
                }
                if (isset($context['emotional_state'])) {
                    return in_array($context['emotional_state'], ['overwhelmed', 'panic', 'dissociated']);
                }
            }
            return $context === true;
        });

        // 특정 운동 타입 추천 여부
        $this->registerOperator('exercise_recommended', function($recommendations, $exerciseType) {
            if (!is_array($recommendations)) return false;
            return in_array($exerciseType, $recommendations);
        });

        // ========================================
        // 트렌드 분석 연산자
        // ========================================

        // 침착성 트렌드 확인
        $this->registerOperator('calmness_trend_is', function($trend, $expected) {
            if (is_array($trend)) {
                $trendValue = $trend['trend'] ?? 'stable';
            } else {
                $trendValue = $trend;
            }
            return strtolower($trendValue) === strtolower($expected);
        });

        // 침착성이 개선되고 있는지
        $this->registerOperator('calmness_improving', function($trend, $ignored) {
            if (is_array($trend)) {
                return ($trend['trend'] ?? '') === 'improving' || ($trend['change'] ?? 0) > 0;
            }
            return $trend === 'improving';
        });

        // 침착성이 악화되고 있는지
        $this->registerOperator('calmness_declining', function($trend, $ignored) {
            if (is_array($trend)) {
                return ($trend['trend'] ?? '') === 'declining' || ($trend['change'] ?? 0) < 0;
            }
            return $trend === 'declining';
        });

        // 트렌드 변동성 확인
        $this->registerOperator('trend_volatility_is', function($trend, $level) {
            if (is_array($trend)) {
                return ($trend['volatility'] ?? 'low') === strtolower($level);
            }
            return false;
        });

        // ========================================
        // 개입 전략 연산자
        // ========================================

        // 특정 개입이 추천되는지
        $this->registerOperator('intervention_recommended', function($recommendations, $intervention) {
            if (is_array($recommendations)) {
                $primary = $recommendations['primary'] ?? null;
                $secondary = $recommendations['secondary'] ?? null;
                return $primary === $intervention || $secondary === $intervention;
            }
            return false;
        });

        // 개입 긴급도 확인
        $this->registerOperator('intervention_urgency_is', function($recommendations, $urgency) {
            if (is_array($recommendations)) {
                return ($recommendations['urgency'] ?? 'normal') === strtolower($urgency);
            }
            return false;
        });

        // ========================================
        // 메시지 분석 연산자
        // ========================================

        // 메시지 긴급도 확인
        $this->registerOperator('message_urgency_is', function($analysis, $urgency) {
            if (is_array($analysis)) {
                return ($analysis['urgency'] ?? 'normal') === strtolower($urgency);
            }
            return false;
        });

        // 메시지에 특정 의도 포함 여부
        $this->registerOperator('has_intent', function($analysis, $intent) {
            if (is_array($analysis) && isset($analysis['intent'])) {
                return $analysis['intent'] === $intent;
            }
            return false;
        });

        // 침착성 지표 점수 확인
        $this->registerOperator('calmness_indicator_score', function($indicators, $condition) {
            if (!is_array($indicators) || !is_array($condition)) return false;
            $field = $condition['field'] ?? null;
            $op = $condition['op'] ?? '>';
            $value = $condition['value'] ?? 0;

            if (!$field || !isset($indicators[$field])) return false;

            $actual = $indicators[$field];
            switch ($op) {
                case '>': return $actual > $value;
                case '>=': return $actual >= $value;
                case '<': return $actual < $value;
                case '<=': return $actual <= $value;
                case '==': return $actual == $value;
                default: return false;
            }
        });

        // ========================================
        // 세션/이력 연산자
        // ========================================

        // 이전 세션에서 특정 상태였는지
        $this->registerOperator('previous_state_was', function($history, $state) {
            if (!is_array($history) || empty($history)) return false;
            $previous = reset($history);
            if (is_object($previous)) {
                return ($previous->level ?? null) === $state;
            }
            return ($previous['level'] ?? null) === $state;
        });

        // 연속 위기 세션 수 확인
        $this->registerOperator('consecutive_crisis_sessions', function($history, $threshold) {
            if (!is_array($history)) return false;
            $count = 0;
            foreach ($history as $session) {
                $level = is_object($session) ? ($session->level ?? '') : ($session['level'] ?? '');
                if ($level === 'C_crisis') {
                    $count++;
                } else {
                    break;
                }
            }
            return $count >= $threshold;
        });

        // 평균 침착성 점수 확인
        $this->registerOperator('average_calmness_above', function($history, $threshold) {
            if (!is_array($history) || empty($history)) return false;
            $total = 0;
            $count = 0;
            foreach ($history as $session) {
                $score = is_object($session) ? ($session->score ?? null) : ($session['score'] ?? null);
                if (is_numeric($score)) {
                    $total += $score;
                    $count++;
                }
            }
            if ($count === 0) return false;
            return ($total / $count) > $threshold;
        });
    }

    /**
     * 침착성 레벨 정규화
     *
     * @param mixed $level 침착성 레벨
     * @return string 정규화된 레벨
     */
    private function normalizeCalmnessLevel($level): string {
        if (is_numeric($level)) {
            $score = (int)$level;
            foreach ($this->calmnessLevelRanges as $levelName => $range) {
                if ($score >= $range['min'] && $score <= $range['max']) {
                    return $levelName;
                }
            }
            return 'C85';
        }

        $level = strtoupper(trim((string)$level));

        // C 접두사 없으면 추가
        if (!str_starts_with($level, 'C') && !str_starts_with($level, 'C_')) {
            $level = 'C' . $level;
        }

        return in_array($level, $this->calmnessLevelOrder) ? $level : 'C85';
    }

    /**
     * 침착성 컨텍스트 기반 조건 평가
     *
     * @param array $condition 조건
     * @param array $context 컨텍스트
     * @return bool 평가 결과
     */
    public function evaluateCalmnessCondition(array $condition, array $context): bool {
        // 특수 침착성 조건 처리
        if (isset($condition['calmness_condition'])) {
            return $this->evaluateSpecialCalmnessCondition($condition['calmness_condition'], $context);
        }

        // 기본 조건 평가
        return $this->evaluate($condition, $context);
    }

    /**
     * 특수 침착성 조건 평가
     *
     * @param string $conditionType 조건 타입
     * @param array $context 컨텍스트
     * @return bool 평가 결과
     */
    private function evaluateSpecialCalmnessCondition(string $conditionType, array $context): bool {
        switch ($conditionType) {
            case 'is_in_crisis':
                $level = $context['calmness_level'] ?? 'C85';
                return $level === 'C_crisis' || ($context['is_crisis'] ?? false);

            case 'needs_immediate_support':
                return ($context['calmness_level'] ?? 'C85') === 'C_crisis' ||
                       ($context['crisis_severity'] ?? 'none') !== 'none';

            case 'can_handle_exercises':
                $level = $context['calmness_level'] ?? 'C85';
                return in_array($level, ['C95', 'C90', 'C85', 'C80']);

            case 'requires_professional':
                $severity = $context['crisis_severity'] ?? 'none';
                return in_array($severity, ['critical', 'high']);

            case 'stable_enough_for_skill_building':
                $level = $context['calmness_level'] ?? 'C85';
                $trend = $context['calmness_trend']['trend'] ?? 'stable';
                return in_array($level, ['C95', 'C90']) && $trend !== 'declining';

            case 'showing_improvement':
                $trend = $context['calmness_trend'] ?? [];
                return ($trend['trend'] ?? 'stable') === 'improving' && ($trend['change'] ?? 0) > 3;

            case 'at_risk_of_decline':
                $trend = $context['calmness_trend'] ?? [];
                return ($trend['trend'] ?? 'stable') === 'declining' ||
                       ($trend['volatility'] ?? 'low') === 'high';

            default:
                error_log("[CalmnessConditionEvaluator] {$this->currentFile}:" . __LINE__ .
                    " - 알 수 없는 특수 조건: {$conditionType}");
                return false;
        }
    }

    /**
     * 복합 침착성 조건 평가
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트
     * @param string $logic 논리 연산 (AND/OR)
     * @return bool 평가 결과
     */
    public function evaluateCalmnessConditions(array $conditions, array $context, string $logic = 'AND'): bool {
        // 침착성 특화 전처리
        $enrichedContext = $this->enrichCalmnessContext($context);

        // 기본 평가 실행
        return $this->evaluateAll($conditions, $enrichedContext, $logic);
    }

    /**
     * 침착성 컨텍스트 보강
     *
     * @param array $context 기본 컨텍스트
     * @return array 보강된 컨텍스트
     */
    private function enrichCalmnessContext(array $context): array {
        // 침착성 레벨 자동 계산 (점수가 있고 레벨이 없는 경우)
        if (isset($context['calmness_score']) && !isset($context['calmness_level'])) {
            $context['calmness_level'] = $this->normalizeCalmnessLevel($context['calmness_score']);
        }

        // 위기 상태 자동 설정
        if (!isset($context['is_crisis'])) {
            $context['is_crisis'] = ($context['calmness_level'] ?? 'C85') === 'C_crisis';
        }

        // 개입 긴급도 자동 계산
        if (!isset($context['intervention_urgency'])) {
            $level = $context['calmness_level'] ?? 'C85';
            $context['intervention_urgency'] = $this->calculateInterventionUrgency($level, $context);
        }

        return $context;
    }

    /**
     * 개입 긴급도 계산
     *
     * @param string $level 침착성 레벨
     * @param array $context 컨텍스트
     * @return string 긴급도 (critical, high, normal, low)
     */
    private function calculateInterventionUrgency(string $level, array $context): string {
        if ($level === 'C_crisis') {
            $severity = $context['crisis_severity'] ?? 'moderate';
            return in_array($severity, ['critical', 'high']) ? 'critical' : 'high';
        }

        if ($level === 'C75') {
            $trend = $context['calmness_trend']['trend'] ?? 'stable';
            return $trend === 'declining' ? 'high' : 'normal';
        }

        if (in_array($level, ['C80'])) {
            return 'normal';
        }

        return 'low';
    }

    /**
     * 침착성 규칙 매칭 점수 계산
     *
     * @param array $rule 규칙
     * @param array $context 컨텍스트
     * @return float 매칭 점수 (0.0 ~ 1.0)
     */
    public function calculateRuleMatchScore(array $rule, array $context): float {
        $score = 0.0;
        $maxScore = 0.0;

        // 침착성 레벨 매칭 (40% 가중치)
        if (isset($rule['calmness_level'])) {
            $maxScore += 0.4;
            if (($context['calmness_level'] ?? '') === $rule['calmness_level']) {
                $score += 0.4;
            }
        }

        // 상황 코드 매칭 (30% 가중치)
        if (isset($rule['situation'])) {
            $maxScore += 0.3;
            if (($context['situation'] ?? '') === $rule['situation']) {
                $score += 0.3;
            }
        }

        // 조건 매칭 (30% 가중치)
        if (isset($rule['conditions']) && is_array($rule['conditions'])) {
            $maxScore += 0.3;
            $conditionResults = [];
            foreach ($rule['conditions'] as $condition) {
                try {
                    $conditionResults[] = $this->evaluate($condition, $context) ? 1 : 0;
                } catch (Exception $e) {
                    $conditionResults[] = 0;
                }
            }
            if (!empty($conditionResults)) {
                $score += 0.3 * (array_sum($conditionResults) / count($conditionResults));
            }
        }

        return $maxScore > 0 ? $score / $maxScore : 0.0;
    }

    /**
     * 지원되는 침착성 연산자 목록 반환
     *
     * @return array 연산자 목록
     */
    public function getCalmnessOperators(): array {
        return [
            // 침착성 레벨
            'calmness_level_is',
            'calmness_level_above',
            'calmness_level_below',
            'calmness_score_between',
            'calmness_score_at_level',
            // 위기 상태
            'crisis_active',
            'crisis_severity_is',
            'crisis_severity_above',
            'has_crisis_indicator',
            // 불안 트리거
            'has_anxiety_trigger',
            'anxiety_trigger_count',
            'anxiety_category_active',
            // 감정 상태
            'emotional_state_is',
            'is_negative_emotion',
            'is_positive_emotion',
            'emotion_intensity_above',
            // 호흡/그라운딩
            'needs_breathing',
            'needs_grounding',
            'exercise_recommended',
            // 트렌드
            'calmness_trend_is',
            'calmness_improving',
            'calmness_declining',
            'trend_volatility_is',
            // 개입
            'intervention_recommended',
            'intervention_urgency_is',
            // 메시지 분석
            'message_urgency_is',
            'has_intent',
            'calmness_indicator_score',
            // 세션/이력
            'previous_state_was',
            'consecutive_crisis_sessions',
            'average_calmness_above'
        ];
    }
}

// PHP 7.1 호환성: str_starts_with 폴리필
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/*
 * 관련 DB 테이블: 없음 (런타임 평가)
 *
 * 참조 파일:
 * - ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php (부모 클래스)
 * - ontology_engineering/persona_engine/core/IConditionEvaluator.php (인터페이스)
 *
 * 지원하는 침착성 연산자:
 * - 레벨 연산자: calmness_level_is, calmness_level_above, calmness_level_below
 * - 점수 연산자: calmness_score_between, calmness_score_at_level
 * - 위기 연산자: crisis_active, crisis_severity_is, crisis_severity_above
 * - 트리거 연산자: has_anxiety_trigger, anxiety_trigger_count
 * - 감정 연산자: emotional_state_is, is_negative_emotion, is_positive_emotion
 * - 운동 연산자: needs_breathing, needs_grounding, exercise_recommended
 * - 트렌드 연산자: calmness_trend_is, calmness_improving, calmness_declining
 * - 이력 연산자: previous_state_was, consecutive_crisis_sessions
 */
