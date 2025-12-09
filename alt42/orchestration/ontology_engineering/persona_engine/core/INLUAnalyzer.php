<?php
/**
 * INLUAnalyzer - 자연어 이해 분석 인터페이스
 *
 * 입력 텍스트의 의도, 감정, 주제 등을 분석하는 표준 인터페이스입니다.
 * 페르소나 식별을 위한 핵심 입력 데이터를 생성합니다.
 *
 * @package PersonaEngine\Core
 * @version 1.0.0
 * @author ALT42 Orchestration System
 *
 * 사용 예시:
 * ```php
 * $analyzer = new TeacherNLUAnalyzer();
 * $analysis = $analyzer->analyze("학생이 요즘 수학에 흥미를 잃은 것 같아요");
 * // Returns: ['intent' => 'concern_expression', 'emotion' => 'worry', 'topic' => 'motivation']
 * ```
 */

interface INLUAnalyzer
{
    /**
     * 텍스트를 종합적으로 분석합니다.
     *
     * @param string $text 분석할 텍스트
     * @param array $options 분석 옵션
     *        - 'include_keywords': bool 키워드 추출 포함
     *        - 'include_entities': bool 개체명 추출 포함
     *        - 'context': array 추가 컨텍스트
     * @return array 분석 결과
     *         - 'intent': string 주요 의도
     *         - 'intents': array 모든 감지된 의도와 신뢰도
     *         - 'emotion': string 주요 감정
     *         - 'emotions': array 모든 감지된 감정과 강도
     *         - 'topic': string 주요 주제
     *         - 'topics': array 모든 감지된 주제
     *         - 'keywords': array 추출된 키워드 (옵션)
     *         - 'entities': array 추출된 개체명 (옵션)
     *         - 'confidence': float 전체 분석 신뢰도
     */
    public function analyze(string $text, array $options = []): array;

    /**
     * 의도를 분석합니다.
     *
     * @param string $text 분석할 텍스트
     * @return array 의도 분석 결과
     *         - 'primary': string 주요 의도
     *         - 'all': array [의도 => 신뢰도]
     */
    public function analyzeIntent(string $text): array;

    /**
     * 감정을 분석합니다.
     *
     * @param string $text 분석할 텍스트
     * @return array 감정 분석 결과
     *         - 'primary': string 주요 감정
     *         - 'valence': float 감정 극성 (-1 to 1)
     *         - 'intensity': float 감정 강도 (0 to 1)
     *         - 'all': array [감정 => 강도]
     */
    public function analyzeEmotion(string $text): array;

    /**
     * 주제를 분석합니다.
     *
     * @param string $text 분석할 텍스트
     * @return array 주제 분석 결과
     *         - 'primary': string 주요 주제
     *         - 'all': array [주제 => 관련도]
     */
    public function analyzeTopic(string $text): array;

    /**
     * 키워드를 추출합니다.
     *
     * @param string $text 분석할 텍스트
     * @param int $limit 최대 키워드 수
     * @return array 추출된 키워드 배열
     */
    public function extractKeywords(string $text, int $limit = 10): array;

    /**
     * 의도 패턴을 등록합니다.
     *
     * @param string $intentName 의도 이름
     * @param array $patterns 패턴 배열 (정규식 또는 키워드)
     * @return void
     */
    public function registerIntentPattern(string $intentName, array $patterns): void;

    /**
     * 감정 어휘를 등록합니다.
     *
     * @param string $emotionName 감정 이름
     * @param array $lexicon 어휘 배열
     *        - 'words': array 감정 표현 단어
     *        - 'weight': float 기본 가중치
     * @return void
     */
    public function registerEmotionLexicon(string $emotionName, array $lexicon): void;

    /**
     * 주제 키워드를 등록합니다.
     *
     * @param string $topicName 주제 이름
     * @param array $keywords 키워드 배열
     * @return void
     */
    public function registerTopicKeywords(string $topicName, array $keywords): void;

    /**
     * 에이전트별 분석 규칙을 로드합니다.
     *
     * @param int $agentId 에이전트 번호 (1-21)
     * @return void
     */
    public function loadAgentRules(int $agentId): void;

    /**
     * 지원하는 의도 목록을 반환합니다.
     *
     * @return array 의도 목록과 설명
     */
    public function getSupportedIntents(): array;

    /**
     * 지원하는 감정 목록을 반환합니다.
     *
     * @return array 감정 목록과 설명
     */
    public function getSupportedEmotions(): array;

    /**
     * 지원하는 주제 목록을 반환합니다.
     *
     * @return array 주제 목록과 설명
     */
    public function getSupportedTopics(): array;
}
