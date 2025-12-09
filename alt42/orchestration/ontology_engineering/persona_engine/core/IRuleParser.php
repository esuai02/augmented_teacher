<?php
/**
 * IRuleParser - 규칙 파서 인터페이스
 *
 * YAML/JSON 형식의 페르소나 규칙 파일을 파싱하는 표준 인터페이스
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @author Claude Code
 *
 * 지원 형식:
 * - YAML (.yaml, .yml)
 * - JSON (.json)
 *
 * 규칙 구조:
 * personas:
 *   - id: string
 *     name: string
 *     description: string
 *     conditions: array
 *     actions: array
 *     priority: int
 */

interface IRuleParser {

    /**
     * 규칙 파일 로드 및 파싱
     *
     * @param string $filePath 규칙 파일 경로 (.yaml, .json)
     * @return array 파싱된 규칙 배열
     * @throws \InvalidArgumentException 지원하지 않는 파일 형식
     * @throws \RuntimeException 파일 읽기/파싱 실패
     */
    public function parse(string $filePath): array;

    /**
     * 규칙 문자열 파싱
     *
     * @param string $content 규칙 내용 문자열
     * @param string $format 형식 ('yaml' | 'json')
     * @return array 파싱된 규칙 배열
     */
    public function parseString(string $content, string $format = 'yaml'): array;

    /**
     * 규칙 유효성 검증
     *
     * @param array $rules 검증할 규칙 배열
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(array $rules): array;

    /**
     * 규칙을 파일로 저장
     *
     * @param array $rules 저장할 규칙
     * @param string $filePath 저장 경로
     * @param string $format 형식 ('yaml' | 'json')
     * @return bool 저장 성공 여부
     */
    public function save(array $rules, string $filePath, string $format = 'yaml'): bool;

    /**
     * 지원 형식 목록 반환
     *
     * @return array ['yaml', 'json']
     */
    public function getSupportedFormats(): array;
}

/*
 * 관련 DB 테이블: 없음 (파일 기반)
 *
 * 참조 파일:
 * - agents/agent01_onboarding/persona_system/rules/rules.yaml
 * - agents/agent01_onboarding/persona_system/engine/PersonaRuleEngine.php::loadRules()
 */
