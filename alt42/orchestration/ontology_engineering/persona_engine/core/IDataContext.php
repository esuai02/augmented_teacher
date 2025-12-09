<?php
/**
 * IDataContext - 데이터 컨텍스트 인터페이스
 *
 * 페르소나 엔진에서 사용하는 학생/사용자 데이터 컨텍스트 표준 인터페이스
 * Moodle DB와 연동하여 필요한 데이터를 제공
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @author Claude Code
 *
 * 컨텍스트 데이터 종류:
 * - 사용자 기본 정보 (이름, 학년 등)
 * - 학습 상태 (현재 문제, 진행률 등)
 * - 감정 상태 (NLU 분석 결과)
 * - 세션 히스토리
 * - 에이전트별 맞춤 데이터
 */

interface IDataContext {

    /**
     * 사용자 ID로 컨텍스트 로드
     *
     * @param int $userId Moodle 사용자 ID
     * @param array $sessionData 현재 세션 데이터 (선택)
     * @return array 통합 컨텍스트 데이터
     */
    public function loadContext(int $userId, array $sessionData = []): array;

    /**
     * 컨텍스트 필드 값 가져오기
     *
     * @param string $field 필드명 (점 표기법 지원: 'user.firstname')
     * @param mixed $default 기본값
     * @return mixed 필드 값
     */
    public function get(string $field, $default = null);

    /**
     * 컨텍스트 필드 값 설정
     *
     * @param string $field 필드명
     * @param mixed $value 설정할 값
     * @return void
     */
    public function set(string $field, $value): void;

    /**
     * 컨텍스트 데이터 전체 반환
     *
     * @return array 전체 컨텍스트 데이터
     */
    public function toArray(): array;

    /**
     * 컨텍스트 갱신 (DB에서 최신 데이터 로드)
     *
     * @return void
     */
    public function refresh(): void;

    /**
     * 에이전트별 맞춤 데이터 로드
     *
     * @param string $agentId 에이전트 ID (예: 'agent11')
     * @return array 에이전트 맞춤 데이터
     */
    public function loadAgentData(string $agentId): array;

    /**
     * 세션 히스토리 추가
     *
     * @param string $event 이벤트 타입
     * @param array $data 이벤트 데이터
     * @return void
     */
    public function addToHistory(string $event, array $data): void;

    /**
     * 감정 상태 분석 결과 설정
     *
     * @param string $emotion 감정 (anxiety, frustration, confidence 등)
     * @param float $intensity 강도 (0.0-1.0)
     * @return void
     */
    public function setEmotionalState(string $emotion, float $intensity): void;
}

/*
 * 관련 DB 테이블:
 * - mdl_user (사용자 기본 정보)
 * - mdl_user_info_data (사용자 추가 정보, fieldid=22: 역할)
 * - at_agent_persona_state (에이전트별 페르소나 상태)
 *
 * 참조 파일:
 * - agents/agent01_onboarding/persona_system/engine/DataContext.php (원본 구현)
 */
