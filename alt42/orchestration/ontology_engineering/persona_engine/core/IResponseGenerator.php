<?php
/**
 * IResponseGenerator - 응답 생성기 인터페이스
 *
 * 페르소나 기반 응답을 생성하는 표준 인터페이스
 * 템플릿 기반 또는 AI 기반 응답 생성 지원
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @author Claude Code
 *
 * 응답 생성 모드:
 * - template: 미리 정의된 템플릿 사용
 * - ai: OpenAI API 활용 동적 생성
 * - hybrid: 템플릿 + AI 보강
 */

interface IResponseGenerator {

    /**
     * 응답 생성
     *
     * @param array $persona 식별된 페르소나 정보
     * @param array $context 응답 생성 컨텍스트
     * @param string $mode 생성 모드 ('template' | 'ai' | 'hybrid')
     * @return array ['text' => string, 'tone' => string, 'source' => string]
     */
    public function generate(array $persona, array $context, string $mode = 'template'): array;

    /**
     * 템플릿 로드
     *
     * @param string $templatePath 템플릿 파일 경로
     * @return bool 로드 성공 여부
     */
    public function loadTemplates(string $templatePath): bool;

    /**
     * 템플릿 변수 치환
     *
     * @param string $template 템플릿 문자열
     * @param array $variables 변수 배열 ['name' => 'value']
     * @return string 치환된 문자열
     */
    public function renderTemplate(string $template, array $variables): string;

    /**
     * AI 응답 생성 (OpenAI API)
     *
     * @param string $message 사용자 메시지
     * @param array $persona 페르소나 정보
     * @param array $context 컨텍스트
     * @return array ['text' => string, 'model' => string, 'tokens' => int]
     */
    public function generateAIResponse(string $message, array $persona, array $context): array;

    /**
     * 응답 톤 조정
     *
     * @param string $text 원본 텍스트
     * @param string $tone 목표 톤 (Professional, Friendly, Encouraging 등)
     * @return string 톤 조정된 텍스트
     */
    public function adjustTone(string $text, string $tone): string;

    /**
     * 기본 응답 반환 (폴백)
     *
     * @param string $emotion 감지된 감정
     * @return string 기본 응답 텍스트
     */
    public function getDefaultResponse(string $emotion = 'neutral'): string;
}

/*
 * 관련 DB 테이블: 없음 (메모리 기반)
 *
 * 참조 파일:
 * - agents/agent01_onboarding/persona_system/engine/ResponseGenerator.php
 * - agents/agent01_onboarding/persona_system/templates/
 */
