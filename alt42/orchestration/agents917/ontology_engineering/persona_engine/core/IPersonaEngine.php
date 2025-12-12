<?php
/**
 * IPersonaEngine - 페르소나 엔진 핵심 인터페이스
 *
 * 모든 에이전트의 페르소나 엔진이 구현해야 하는 핵심 인터페이스입니다.
 * 규칙 로드, 페르소나 식별, 응답 생성의 표준 프로세스를 정의합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Core;

interface IPersonaEngine {

    /**
     * 규칙 파일 로드
     *
     * @param string $rulesPath rules.yaml 파일 경로
     * @return bool 로드 성공 여부
     * @throws \Exception 파일 로드 실패 시
     */
    public function loadRules(string $rulesPath): bool;

    /**
     * 학생 컨텍스트 로드
     *
     * @param int $userId Moodle 사용자 ID
     * @param array $sessionData 현재 세션 데이터 (선택)
     * @return array 학생 컨텍스트
     */
    public function loadStudentContext(int $userId, array $sessionData = []): array;

    /**
     * 메시지 분석 및 컨텍스트 업데이트
     *
     * @param array $context 현재 컨텍스트
     * @param string $message 사용자 메시지
     * @return array 업데이트된 컨텍스트
     */
    public function analyzeMessage(array $context, string $message): array;

    /**
     * 페르소나 식별
     *
     * @param array $context 학생 컨텍스트
     * @return array 식별 결과 [persona_id, persona_name, confidence, tone, intervention, ...]
     */
    public function identifyPersona(array $context): array;

    /**
     * 전체 프로세스 실행 (분석 → 식별 → 응답 생성)
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터 (선택)
     * @return array 처리 결과 [success, user_id, persona, response, context]
     */
    public function process(int $userId, string $message, array $sessionData = []): array;

    /**
     * 응답 생성
     *
     * @param array $identification 페르소나 식별 결과
     * @param array $context 컨텍스트
     * @param string $templateKey 템플릿 키 (선택)
     * @return array 생성된 응답 [text, template_key, tone, intervention, ...]
     */
    public function generateResponse(array $identification, array $context, string $templateKey = 'default'): array;

    /**
     * 에이전트 ID 반환
     *
     * @return int 에이전트 번호 (1-21)
     */
    public function getAgentId(): int;

    /**
     * 에이전트 이름 반환
     *
     * @return string 에이전트 이름
     */
    public function getAgentName(): string;

    /**
     * 설정 조회
     *
     * @param string|null $key 설정 키 (null이면 전체 설정)
     * @return mixed 설정 값
     */
    public function getConfig(?string $key = null);

    /**
     * 설정 업데이트
     *
     * @param array $config 새 설정
     * @return void
     */
    public function setConfig(array $config): void;
}

/*
 * 구현 가이드:
 *
 * 1. 각 에이전트는 IPersonaEngine을 직접 구현하거나 AbstractPersonaEngine을 상속
 * 2. 필수 메서드: loadRules, identifyPersona, process
 * 3. 에이전트별 특화:
 *    - getAgentId(): 에이전트 번호 반환
 *    - getAgentName(): 에이전트 이름 반환
 *    - 커스텀 조건/액션 핸들러 등록
 *
 * 표준 반환 구조:
 *
 * process() 반환값:
 * [
 *   'success' => true|false,
 *   'user_id' => 123,
 *   'persona' => [
 *     'persona_id' => 'R_High_M',
 *     'persona_name' => '고위험 동기저하형',
 *     'confidence' => 0.85,
 *     'tone' => 'Warm',
 *     'intervention' => 'Urgent'
 *   ],
 *   'response' => [
 *     'text' => '...',
 *     'template_key' => 'high_risk_intervention'
 *   ],
 *   'context' => [...]
 * ]
 *
 * 파일 위치: ontology_engineering/persona_engine/core/IPersonaEngine.php
 */
