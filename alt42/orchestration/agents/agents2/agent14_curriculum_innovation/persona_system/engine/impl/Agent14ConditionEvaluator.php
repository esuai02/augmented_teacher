<?php
/**
 * Agent14ConditionEvaluator - Agent14 조건 평가기
 *
 * Agent14 전용 조건 평가 구현
 *
 * @package AugmentedTeacher\Agent14\PersonaEngine\Impl
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

require_once(__DIR__ . '/../../../../../ontology_engineering/persona_engine/core/IConditionEvaluator.php');

class Agent14ConditionEvaluator implements IConditionEvaluator {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var array Agent14 도메인 키워드 */
    protected $domainKeywords = [
        'curriculum' => ['교육과정', '커리큘럼', 'curriculum', '과정', '코스'],
        'design' => ['설계', '디자인', 'design', '구성', '계획'],
        'content' => ['콘텐츠', '내용', 'content', '자료', '교재'],
        'assessment' => ['평가', '시험', 'assessment', 'evaluation', '측정'],
        'innovation' => ['혁신', '개선', 'innovation', '변화', '발전'],
        'pedagogy' => ['교수법', '교육방법', 'pedagogy', '수업', '지도']
    ];

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 배열
     * @param array $context 컨텍스트
     * @return bool 평가 결과
     */
    public function evaluate(array $condition, array $context): bool {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        // 컨텍스트에서 값 추출
        $contextValue = $this->getContextValue($context, $field);

        return $this->compareValues($contextValue, $operator, $value);
    }

    /**
     * OR 조건 평가 (하나라도 참이면 참)
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트
     * @return bool 평가 결과
     */
    public function evaluateOr(array $conditions, array $context): bool {
        foreach ($conditions as $condition) {
            if ($this->evaluate($condition, $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * AND 조건 평가 (모두 참이어야 참)
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트
     * @return bool 평가 결과
     */
    public function evaluateAnd(array $conditions, array $context): bool {
        foreach ($conditions as $condition) {
            if (!$this->evaluate($condition, $context)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 컨텍스트에서 값 추출
     *
     * @param array $context 컨텍스트
     * @param string $field 필드명
     * @return mixed 값
     */
    protected function getContextValue(array $context, string $field) {
        // 점 표기법 지원 (예: user.grade)
        $parts = explode('.', $field);
        $value = $context;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 값 비교
     *
     * @param mixed $contextValue 컨텍스트 값
     * @param string $operator 연산자
     * @param mixed $value 비교 값
     * @return bool 비교 결과
     */
    protected function compareValues($contextValue, string $operator, $value): bool {
        switch ($operator) {
            case '==':
            case 'equals':
                return $contextValue == $value;

            case '!=':
            case 'not_equals':
                return $contextValue != $value;

            case '>':
            case 'greater_than':
                return $contextValue > $value;

            case '<':
            case 'less_than':
                return $contextValue < $value;

            case '>=':
            case 'greater_equal':
                return $contextValue >= $value;

            case '<=':
            case 'less_equal':
                return $contextValue <= $value;

            case 'contains':
                if (is_string($contextValue)) {
                    return strpos(strtolower($contextValue), strtolower($value)) !== false;
                }
                return false;

            case 'contains_any':
                if (is_string($contextValue) && is_array($value)) {
                    foreach ($value as $v) {
                        if (strpos(strtolower($contextValue), strtolower($v)) !== false) {
                            return true;
                        }
                    }
                }
                return false;

            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                return in_array($contextValue, $values);

            case 'not_in':
                $values = is_array($value) ? $value : explode(',', $value);
                return !in_array($contextValue, $values);

            case 'regex':
                return (bool)preg_match($value, $contextValue);

            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    return $contextValue >= $value[0] && $contextValue <= $value[1];
                }
                return false;

            case 'domain_match':
                return $this->matchDomainKeywords($contextValue, $value);

            default:
                error_log("[Agent14ConditionEvaluator] {$this->currentFile}:" . __LINE__ .
                    " - 알 수 없는 연산자: {$operator}");
                return false;
        }
    }

    /**
     * 도메인 키워드 매칭 (Agent14 특화)
     *
     * @param string $text 텍스트
     * @param string $domain 도메인
     * @return bool 매칭 여부
     */
    protected function matchDomainKeywords(string $text, string $domain): bool {
        $keywords = $this->domainKeywords[$domain] ?? [];
        $lowerText = strtolower($text);

        foreach ($keywords as $keyword) {
            if (strpos($lowerText, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 지원하는 연산자 목록
     *
     * @return array 연산자 목록
     */
    public function getSupportedOperators(): array {
        return [
            '==', '!=', '>', '<', '>=', '<=',
            'equals', 'not_equals', 'greater_than', 'less_than', 'greater_equal', 'less_equal',
            'contains', 'contains_any', 'in', 'not_in', 'regex', 'between', 'domain_match'
        ];
    }

    /**
     * 도메인 키워드 추가
     *
     * @param string $domain 도메인명
     * @param array $keywords 키워드 배열
     */
    public function addDomainKeywords(string $domain, array $keywords): void {
        $this->domainKeywords[$domain] = array_merge(
            $this->domainKeywords[$domain] ?? [],
            $keywords
        );
    }
}
