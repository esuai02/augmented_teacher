<?php
/**
 * IConditionEvaluator - 조건 평가기 인터페이스
 *
 * 페르소나 규칙의 조건을 평가하는 표준 인터페이스
 * 각 에이전트는 이 인터페이스를 구현하여 맞춤형 조건 평가 로직 제공
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @author Claude Code
 *
 * 사용 예시:
 * $evaluator = new ConcreteConditionEvaluator();
 * $result = $evaluator->evaluate($condition, $context);
 *
 * 지원 연산자:
 * - 비교: ==, !=, >, <, >=, <=
 * - 문자열: contains, starts_with, ends_with, regex
 * - 범위: in, not_in, between
 * - 논리: AND, OR (중첩 가능)
 */

interface IConditionEvaluator {

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 배열 ['field' => string, 'operator' => string, 'value' => mixed]
     * @param array $context 평가 컨텍스트 데이터
     * @return bool 조건 만족 여부
     * @throws \InvalidArgumentException 잘못된 조건 형식
     */
    public function evaluate(array $condition, array $context): bool;

    /**
     * 복합 조건 평가 (AND/OR 중첩)
     *
     * @param array $conditions 조건 배열 (중첩 가능)
     * @param array $context 평가 컨텍스트 데이터
     * @param string $logic 논리 연산자 ('AND' | 'OR')
     * @return bool 모든/일부 조건 만족 여부
     */
    public function evaluateAll(array $conditions, array $context, string $logic = 'AND'): bool;

    /**
     * 연산자 등록
     *
     * @param string $operator 연산자 이름
     * @param callable $handler 연산자 처리 함수 function($fieldValue, $compareValue): bool
     * @return void
     */
    public function registerOperator(string $operator, callable $handler): void;

    /**
     * 지원하는 연산자 목록 반환
     *
     * @return array 연산자 이름 배열
     */
    public function getSupportedOperators(): array;
}

/*
 * 관련 DB 테이블: 없음 (메모리 기반 평가)
 *
 * 참조 파일:
 * - agents/agent01_onboarding/persona_system/engine/ConditionEvaluator.php (원본 구현)
 */
