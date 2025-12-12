<?php
/**
 * IActionExecutor - 액션 실행 인터페이스
 *
 * 매칭된 규칙의 액션을 실행하는 인터페이스입니다.
 * 각 에이전트는 에이전트 특화 액션 핸들러를 등록할 수 있습니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Core;

interface IActionExecutor {

    /**
     * 액션 목록 실행
     *
     * @param array $actions 실행할 액션 목록
     * @param array &$context 현재 컨텍스트 (참조로 전달하여 수정 가능)
     * @return array 실행 결과 배열
     */
    public function execute(array $actions, array &$context): array;

    /**
     * 커스텀 액션 핸들러 등록
     *
     * @param string $actionName 액션 이름 (예: 'suggest_action')
     * @param callable $handler 핸들러 함수 function($value, &$context): string
     * @return void
     */
    public function registerHandler(string $actionName, callable $handler): void;

    /**
     * 등록된 핸들러 목록 조회
     *
     * @return array 핸들러 이름 목록
     */
    public function getRegisteredHandlers(): array;
}

/*
 * 기본 액션 타입 (BaseActionExecutor 기준):
 * - identify_persona: 페르소나 ID 설정
 * - set_tone: 응답 톤 설정 (Warm, Professional, Encouraging 등)
 * - set_pace: 응답 페이스 설정 (slow, normal, fast)
 * - prioritize_intervention: 개입 유형 우선순위 설정
 * - set_information_depth: 정보 깊이 설정
 * - add_flag: 컨텍스트에 플래그 추가
 * - set_risk_level: 위험 등급 설정 (agent13 전용)
 * - suggest_action: 개입 액션 제안 (agent13 전용)
 *
 * 파일 위치: ontology_engineering/persona_engine/core/IActionExecutor.php
 */
