<?php
/**
 * IDataContext - 데이터 컨텍스트 인터페이스
 *
 * Moodle DB에서 학생 데이터를 가져와 컨텍스트를 구성하는 인터페이스입니다.
 * 각 에이전트는 자신만의 데이터 소스와 지표를 정의할 수 있습니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Core;

interface IDataContext {

    /**
     * 사용자 ID로 기본 컨텍스트 로드
     *
     * @param int $userId Moodle 사용자 ID
     * @return array 학생 기본 컨텍스트 (이름, 역할, 최근 활동 등)
     */
    public function loadByUserId(int $userId): array;

    /**
     * 사용자 메시지 분석
     *
     * @param string $message 사용자 입력 메시지
     * @return array 분석 결과 (길이, 감정 키워드, 의도 등)
     */
    public function analyzeMessage(string $message): array;

    /**
     * 에이전트별 특화 데이터 로드 (확장 포인트)
     *
     * 각 에이전트가 오버라이드하여 자신만의 데이터를 추가합니다.
     * 예: agent13은 ninactive, npomodoro, tlaststroke 등
     *
     * @param int $userId Moodle 사용자 ID
     * @return array 에이전트 특화 데이터
     */
    public function getAgentSpecificData(int $userId): array;

    /**
     * 전체 컨텍스트 구성 (기본 + 에이전트 특화)
     *
     * @param int $userId Moodle 사용자 ID
     * @param array $sessionData 현재 세션 데이터 (선택)
     * @return array 완전한 컨텍스트
     */
    public function buildFullContext(int $userId, array $sessionData = []): array;
}

/*
 * 컨텍스트 데이터 구조 예시:
 * [
 *   // 기본 데이터 (모든 에이전트 공통)
 *   'user_id' => 123,
 *   'firstname' => '홍길동',
 *   'lastname' => '홍',
 *   'email' => 'user@example.com',
 *   'lastaccess' => 1701590400,
 *   'role' => 'student',
 *
 *   // 메시지 분석 데이터
 *   'user_message' => '오늘 공부하기 싫어요',
 *   'response_length' => 12,
 *   'emotional_keywords' => ['싫어요'],
 *
 *   // 에이전트 특화 데이터 (agent13 예시)
 *   'ninactive' => 3,
 *   'npomodoro' => 4,
 *   'tlaststroke_min' => 25,
 *   'nlazy_blocks' => 2,
 *   'risk_level' => 'Medium'
 * ]
 *
 * 파일 위치: ontology_engineering/persona_engine/core/IDataContext.php
 */
