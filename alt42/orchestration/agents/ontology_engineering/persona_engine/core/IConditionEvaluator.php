<?php
/**
 * IConditionEvaluator - 조건 평가 인터페이스
 *
 * 규칙의 조건을 학생 데이터와 비교하여 평가하는 인터페이스입니다.
 * 각 에이전트는 이 인터페이스를 구현하여 커스텀 조건 평가 로직을 추가할 수 있습니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Core;

interface IConditionEvaluator {

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 배열 ['field' => 'name', 'operator' => '==', 'value' => 'x']
     * @param array $context 학생 컨텍스트 데이터
     * @return bool 조건 충족 여부
     */
    public function evaluate(array $condition, array $context): bool;

    /**
     * OR 조건 평가 (하나라도 true면 true)
     *
     * @param array $conditions OR로 연결된 조건 배열
     * @param array $context 학생 컨텍스트 데이터
     * @return bool 하나 이상 충족 여부
     */
    public function evaluateOr(array $conditions, array $context): bool;

    /**
     * AND 조건 평가 (모두 true여야 true)
     *
     * @param array $conditions AND로 연결된 조건 배열
     * @param array $context 학생 컨텍스트 데이터
     * @return bool 모두 충족 여부
     */
    public function evaluateAnd(array $conditions, array $context): bool;

    /**
     * 커스텀 연산자 등록
     *
     * @param string $operator 연산자 이름 (예: 'contains_any')
     * @param callable $handler 평가 함수 function($fieldValue, $conditionValue): bool
     * @return void
     */
    public function registerOperator(string $operator, callable $handler): void;
}

/*
 * 지원 연산자 목록 (BaseConditionEvaluator 기준):
 * - == : 동등 비교
 * - != : 불일치
 * - >  : 초과
 * - >= : 이상
 * - <  : 미만
 * - <= : 이하
 * - contains : 부분 문자열 포함
 * - contains_any : 배열 중 하나 포함
 * - in : 배열 내 포함
 * - regex : 정규식 매칭
 *
 * 파일 위치: ontology_engineering/persona_engine/core/IConditionEvaluator.php
 */
