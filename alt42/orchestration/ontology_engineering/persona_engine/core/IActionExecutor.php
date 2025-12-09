<?php
/**
 * IActionExecutor - 액션 실행기 인터페이스
 *
 * 페르소나 규칙의 액션을 실행하는 표준 인터페이스
 * 각 에이전트는 이 인터페이스를 구현하여 맞춤형 액션 로직 제공
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @author Claude Code
 *
 * 기본 액션 타입:
 * - identify_persona: 페르소나 식별
 * - set_tone: 응답 톤 설정
 * - set_pace: 진행 속도 설정
 * - prioritize_intervention: 개입 전략 우선순위 설정
 * - trigger_notification: 알림 발송
 * - update_state: 상태 업데이트
 */

interface IActionExecutor {

    /**
     * 단일 액션 실행
     *
     * @param array $action 액션 배열 ['type' => string, 'params' => array]
     * @param array $context 실행 컨텍스트
     * @return array 실행 결과 ['success' => bool, 'data' => mixed]
     * @throws \RuntimeException 액션 실행 실패
     */
    public function execute(array $action, array $context): array;

    /**
     * 복수 액션 실행
     *
     * @param array $actions 액션 배열 목록
     * @param array $context 실행 컨텍스트
     * @return array 각 액션별 실행 결과
     */
    public function executeAll(array $actions, array $context): array;

    /**
     * 커스텀 액션 핸들러 등록
     *
     * @param string $actionType 액션 타입 이름
     * @param callable $handler 핸들러 function(array $params, array $context): array
     * @return void
     */
    public function registerHandler(string $actionType, callable $handler): void;

    /**
     * 지원하는 액션 타입 목록 반환
     *
     * @return array 액션 타입 이름 배열
     */
    public function getSupportedActions(): array;

    /**
     * 액션 실행 전 유효성 검사
     *
     * @param array $action 검증할 액션
     * @return bool 유효 여부
     */
    public function validateAction(array $action): bool;
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state (상태 업데이트 시)
 * - at_agent_messages (알림 발송 시)
 *
 * 참조 파일:
 * - agents/agent01_onboarding/persona_system/engine/ActionExecutor.php (원본 구현)
 */
