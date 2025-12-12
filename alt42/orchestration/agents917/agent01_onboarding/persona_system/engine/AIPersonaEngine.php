<?php
/**
 * AIPersonaEngine - AI 통합 페르소나 엔진
 *
 * 로컬 NLU + OpenAI API 하이브리드 방식
 * 비용 최적화: 로컬 분석 우선, 낮은 신뢰도시 AI 보강
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/PersonaRuleEngine.php');
require_once(__DIR__ . '/AIGateway.php');

class AIPersonaEngine {

    /** @var PersonaRuleEngine 로컬 페르소나 엔진 */
    private $localEngine;

    /** @var AIGateway AI 게이트웨이 */
    private $aiGateway;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 설정 */
    private $config = [
        'ai_enabled' => true,
        'ai_threshold' => 0.7,          // 이 신뢰도 이하면 AI 호출
        'ai_response_enabled' => true,   // AI 응답 생성 사용
        'fallback_to_local' => true,     // AI 실패시 로컬 사용
        'debug_mode' => false,
        'log_enabled' => true
    ];

    /**
     * 생성자
     *
     * @param array $config 설정
     * @param string $apiKey OpenAI API 키 (옵션)
     */
    public function __construct(array $config = [], string $apiKey = '') {
        $this->config = array_merge($this->config, $config);

        // 로컬 엔진 초기화
        $this->localEngine = new PersonaRuleEngine([
            'debug_mode' => $this->config['debug_mode'],
            'nlu_enabled' => true,
            'transition_enabled' => true
        ]);

        // AI 게이트웨이 초기화
        $this->aiGateway = new AIGateway($apiKey, [
            'debug_mode' => $this->config['debug_mode'],
            'log_enabled' => $this->config['log_enabled']
        ]);
    }

    /**
     * 규칙 로드
     *
     * @param string $rulesPath 규칙 파일 경로
     * @return bool 성공 여부
     */
    public function loadRules(string $rulesPath): bool {
        return $this->localEngine->loadRules($rulesPath);
    }

    /**
     * 메인 프로세스 - 메시지 분석부터 응답까지
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        $startTime = microtime(true);
        $aiUsed = false;

        try {
            // 1. 학생 컨텍스트 로드
            $context = $this->localEngine->loadStudentContext($userId, $sessionData);

            // 2. 로컬 NLU 분석 먼저 실행
            $localAnalysis = $this->localEngine->analyzeMessage($context, $message);
            $localConfidence = $this->calculateLocalConfidence($localAnalysis);

            // 3. 신뢰도 기반 AI 보강 결정
            $finalAnalysis = $localAnalysis;
            if ($this->config['ai_enabled'] && $localConfidence < $this->config['ai_threshold']) {
                $aiAnalysis = $this->enhanceWithAI($message, $localAnalysis);
                if ($aiAnalysis['success']) {
                    $finalAnalysis = $this->mergeAnalysis($localAnalysis, $aiAnalysis);
                    $aiUsed = true;
                }
            }

            // 4. 페르소나 식별
            $identification = $this->localEngine->identifyPersona($finalAnalysis);

            // AI로 페르소나 추론 보강 (신뢰도 낮은 경우)
            if ($this->config['ai_enabled'] && ($identification['confidence'] ?? 0) < $this->config['ai_threshold']) {
                $aiPersona = $this->enhancePersonaWithAI($finalAnalysis, $context);
                if ($aiPersona['success']) {
                    $identification = $this->mergePersona($identification, $aiPersona);
                    $aiUsed = true;
                }
            }

            // 5. 응답 생성
            $response = $this->generateResponse($message, $identification, $finalAnalysis);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'user_id' => $userId,
                'persona' => $identification,
                'response' => $response,
                'context' => [
                    'intent' => $finalAnalysis['detected_intent'] ?? null,
                    'emotion' => $finalAnalysis['detected_emotion'] ?? null,
                    'emotion_intensity' => $finalAnalysis['emotion_intensity'] ?? 0,
                    'topics' => $finalAnalysis['detected_topics'] ?? []
                ],
                'meta' => [
                    'ai_used' => $aiUsed,
                    'local_confidence' => $localConfidence,
                    'processing_time_ms' => $processingTime
                ]
            ];

        } catch (Exception $e) {
            $this->logError("프로세스 실행 실패: " . $e->getMessage(), __LINE__);

            // 폴백: 로컬 엔진만 사용
            if ($this->config['fallback_to_local']) {
                return $this->processLocalOnly($userId, $message, $sessionData);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ];
        }
    }

    /**
     * 로컬 분석 신뢰도 계산
     *
     * @param array $analysis 로컬 분석 결과
     * @return float 신뢰도 (0.0-1.0)
     */
    private function calculateLocalConfidence(array $analysis): float {
        $score = 0.5; // 기본값

        // 의도 감지됨
        if (!empty($analysis['detected_intent']) && $analysis['detected_intent'] !== 'unknown') {
            $score += 0.2;
        }

        // 감정 감지됨
        if (!empty($analysis['detected_emotion']) && $analysis['detected_emotion'] !== 'neutral') {
            $score += 0.15;
        }

        // 주제 감지됨
        if (!empty($analysis['detected_topics'])) {
            $score += min(0.15, count($analysis['detected_topics']) * 0.05);
        }

        // NLU 분석 결과 신뢰도
        if (isset($analysis['nlu_analysis']['overall_confidence'])) {
            $nluConf = $analysis['nlu_analysis']['overall_confidence'];
            $score = ($score + $nluConf) / 2;
        }

        return min(1.0, max(0.0, $score));
    }

    /**
     * AI로 NLU 분석 보강
     *
     * @param string $message 원본 메시지
     * @param array $localAnalysis 로컬 분석 결과
     * @return array AI 분석 결과
     */
    private function enhanceWithAI(string $message, array $localAnalysis): array {
        $context = [
            'local_intent' => $localAnalysis['detected_intent'] ?? 'unknown',
            'local_emotion' => $localAnalysis['detected_emotion'] ?? 'neutral',
            'local_topics' => $localAnalysis['detected_topics'] ?? []
        ];

        return $this->aiGateway->analyzeNLU($message, $context);
    }

    /**
     * AI로 페르소나 추론 보강
     *
     * @param array $analysis NLU 분석 결과
     * @param array $context 학생 컨텍스트
     * @return array AI 페르소나 추론 결과
     */
    private function enhancePersonaWithAI(array $analysis, array $context): array {
        $nluForAI = [
            'intent' => $analysis['detected_intent'] ?? 'unknown',
            'emotion' => $analysis['detected_emotion'] ?? 'neutral',
            'emotion_intensity' => $analysis['emotion_intensity'] ?? 0,
            'topics' => $analysis['detected_topics'] ?? [],
            'message' => $analysis['user_message'] ?? ''
        ];

        $studentContext = [
            'name' => $context['firstname'] ?? '학생',
            'grade' => $context['grade'] ?? null,
            'current_situation' => $context['current_situation'] ?? 'S1',
            'previous_persona' => $context['current_persona'] ?? null
        ];

        return $this->aiGateway->reasonPersona($nluForAI, $studentContext);
    }

    /**
     * 로컬 + AI 분석 병합
     *
     * @param array $local 로컬 분석
     * @param array $ai AI 분석
     * @return array 병합 결과
     */
    private function mergeAnalysis(array $local, array $ai): array {
        $aiData = $ai['analysis'] ?? [];

        // AI 결과로 보강 (로컬 우선, AI 보완)
        $merged = $local;

        if (!empty($aiData['intent']) && ($local['detected_intent'] ?? 'unknown') === 'unknown') {
            $merged['detected_intent'] = $aiData['intent'];
        }

        if (!empty($aiData['emotion']) && ($local['detected_emotion'] ?? 'neutral') === 'neutral') {
            $merged['detected_emotion'] = $aiData['emotion'];
            $merged['emotion_intensity'] = $aiData['emotion_intensity'] ?? 0.5;
        }

        if (!empty($aiData['topics'])) {
            $merged['detected_topics'] = array_unique(array_merge(
                $local['detected_topics'] ?? [],
                $aiData['topics']
            ));
        }

        $merged['ai_enhanced'] = true;
        $merged['ai_confidence'] = $aiData['confidence'] ?? 0.8;

        return $merged;
    }

    /**
     * 로컬 + AI 페르소나 병합
     *
     * @param array $local 로컬 페르소나
     * @param array $ai AI 페르소나
     * @return array 병합 결과
     */
    private function mergePersona(array $local, array $ai): array {
        $aiData = $ai['persona'] ?? [];

        // AI 신뢰도가 더 높으면 AI 결과 사용
        $localConf = $local['confidence'] ?? 0.5;
        $aiConf = $aiData['confidence'] ?? 0.7;

        if ($aiConf > $localConf && !empty($aiData['persona_id'])) {
            return [
                'persona_id' => $aiData['persona_id'],
                'persona_name' => $aiData['persona_name'] ?? $local['persona_name'],
                'confidence' => $aiConf,
                'tone' => $aiData['tone'] ?? $local['tone'],
                'intervention' => $aiData['intervention'] ?? $local['intervention'],
                'matched_rule' => 'ai_reasoning',
                'actions' => $local['actions'] ?? [],
                'ai_reasoning' => $aiData['reasoning'] ?? null,
                'ai_enhanced' => true
            ];
        }

        // 로컬 유지, 톤/개입만 AI로 보강
        $local['tone'] = $aiData['tone'] ?? $local['tone'];
        $local['intervention'] = $aiData['intervention'] ?? $local['intervention'];

        return $local;
    }

    /**
     * 응답 생성 (하이브리드)
     *
     * @param string $message 원본 메시지
     * @param array $identification 페르소나 식별 결과
     * @param array $context 컨텍스트
     * @return array 응답
     */
    private function generateResponse(string $message, array $identification, array $context): array {
        // AI 응답 생성 활성화된 경우
        if ($this->config['ai_response_enabled'] && $this->config['ai_enabled']) {
            $aiResponse = $this->aiGateway->generateResponse($message, $identification, [
                'student_name' => $context['firstname'] ?? '학생',
                'situation' => $context['current_situation'] ?? 'S1'
            ]);

            if ($aiResponse['success']) {
                return [
                    'text' => $aiResponse['response'],
                    'source' => 'ai',
                    'model' => $aiResponse['model'],
                    'tone' => $identification['tone'] ?? 'Professional',
                    'intervention' => $identification['intervention'] ?? 'InformationProvision',
                    'persona_id' => $identification['persona_id'],
                    'confidence' => $identification['confidence']
                ];
            }
        }

        // 폴백: 로컬 템플릿 응답
        $localResponse = $this->localEngine->generateResponse($identification, $context);

        return [
            'text' => $localResponse['text'] ?? $this->getDefaultResponse($identification),
            'source' => 'local',
            'tone' => $localResponse['tone'] ?? 'Professional',
            'intervention' => $localResponse['intervention'] ?? 'InformationProvision',
            'persona_id' => $identification['persona_id'],
            'confidence' => $identification['confidence']
        ];
    }

    /**
     * 기본 응답 생성
     *
     * @param array $identification 페르소나 식별 결과
     * @return string 기본 응답
     */
    private function getDefaultResponse(array $identification): string {
        $emotion = $identification['detected_emotion'] ?? 'neutral';

        if (in_array($emotion, ['anxiety', 'frustration', 'fear'])) {
            return "괜찮아요, 천천히 함께 해결해 나가요. 어떤 부분이 가장 어렵게 느껴지시나요?";
        }

        return "네, 말씀해 주세요. 어떻게 도와드릴까요?";
    }

    /**
     * 로컬 전용 프로세스 (폴백)
     *
     * @param int $userId 사용자 ID
     * @param string $message 메시지
     * @param array $sessionData 세션 데이터
     * @return array 결과
     */
    private function processLocalOnly(int $userId, string $message, array $sessionData = []): array {
        $result = $this->localEngine->process($userId, $message, $sessionData);
        $result['meta'] = [
            'ai_used' => false,
            'fallback' => true
        ];
        return $result;
    }

    /**
     * AI API 상태 확인
     *
     * @return array 상태 정보
     */
    public function checkAIStatus(): array {
        return $this->aiGateway->validateApiKey();
    }

    /**
     * 설정 변경
     *
     * @param string $key 설정 키
     * @param mixed $value 설정 값
     */
    public function setConfig(string $key, $value): void {
        $this->config[$key] = $value;
    }

    /**
     * AI 활성화/비활성화
     *
     * @param bool $enabled 활성화 여부
     */
    public function setAIEnabled(bool $enabled): void {
        $this->config['ai_enabled'] = $enabled;
    }

    /**
     * 디버그 정보
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        return [
            'config' => $this->config,
            'local_engine' => $this->localEngine->getDebugInfo(),
            'ai_status' => $this->config['ai_enabled'] ? $this->checkAIStatus() : ['enabled' => false]
        ];
    }

    /**
     * 에러 로깅
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    private function logError(string $message, int $line): void {
        error_log("[AIPersonaEngine ERROR] {$this->currentFile}:{$line} - {$message}");
    }
}

/*
 * 사용 예시:
 *
 * $engine = new AIPersonaEngine([
 *     'ai_enabled' => true,
 *     'ai_threshold' => 0.7,
 *     'debug_mode' => true
 * ]);
 *
 * $engine->loadRules(__DIR__ . '/../rules/rules.yaml');
 *
 * $result = $engine->process($userId, '수학이 너무 어려워서 포기하고 싶어요');
 *
 * if ($result['success']) {
 *     echo $result['response']['text'];
 *     // AI 사용 여부: $result['meta']['ai_used']
 * }
 */
