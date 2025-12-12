<?php
/**
 * Agent19 템플릿 관리자
 *
 * 상황 및 페르소나 기반 응답 템플릿 로드 및 선택
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

class Agent19_TemplateManager {

    /** @var string 템플릿 기본 경로 */
    private $templatePath;

    /** @var array 캐시된 템플릿 */
    private $templateCache = [];

    /** @var array 설정 */
    private $config;

    /**
     * 생성자
     */
    public function __construct() {
        $this->templatePath = __DIR__ . '/situations/';
        $this->config = include(__DIR__ . '/../engine/config/config.php');
    }

    /**
     * 상황에 맞는 템플릿 로드
     *
     * @param string $situationCode S1-S7
     * @return array|null 템플릿 데이터
     */
    public function loadSituationTemplate($situationCode) {
        $cacheKey = 'situation_' . $situationCode;

        if (isset($this->templateCache[$cacheKey])) {
            return $this->templateCache[$cacheKey];
        }

        $fileMap = [
            'S1' => 'S1_dropout.php',
            'S2' => 'S2_delay.php',
            'S3' => 'S3_rest.php',
            'S4' => 'S4_error.php',
            'S5' => 'S5_emotional.php',
            'S6' => 'S6_imbalance.php',
            'S7' => 'S7_routine.php'
        ];

        if (!isset($fileMap[$situationCode])) {
            error_log("[Agent19_TemplateManager:loadSituationTemplate] Invalid situation code: $situationCode");
            return null;
        }

        $filePath = $this->templatePath . $fileMap[$situationCode];

        if (!file_exists($filePath)) {
            error_log("[Agent19_TemplateManager:loadSituationTemplate] Template file not found: $filePath");
            return null;
        }

        $template = include($filePath);
        $this->templateCache[$cacheKey] = $template;

        return $template;
    }

    /**
     * 페르소나 기반 응답 선택
     *
     * @param string $situationCode 상황 코드 (S1-S7)
     * @param array $persona 페르소나 정보 ['cognitive' => 'C1', 'behavioral' => 'B1', 'emotional' => 'E1']
     * @param array $contextData 추가 컨텍스트 데이터
     * @return array 선택된 응답 템플릿
     */
    public function selectResponse($situationCode, $persona, $contextData = []) {
        $template = $this->loadSituationTemplate($situationCode);

        if (!$template) {
            return $this->getDefaultResponse($situationCode);
        }

        $response = [
            'message' => $template['default']['message'] ?? '',
            'cta' => $template['default']['cta'] ?? '',
            'tone' => $template['default']['tone'] ?? 'neutral',
            'source' => 'default'
        ];

        // 1. 복합 조건 체크 (가장 우선순위 높음)
        if (isset($template['composite'])) {
            $compositeMatch = $this->matchCompositeCondition($template['composite'], $persona, $contextData);
            if ($compositeMatch) {
                $response = array_merge($response, $compositeMatch);
                $response['source'] = 'composite';
            }
        }

        // 2. 정서적 페르소나 기반 응답 (우선순위 높음)
        if (isset($persona['emotional']) && isset($template['emotional'][$persona['emotional']])) {
            $emotionalTemplate = $template['emotional'][$persona['emotional']];
            $response = $this->mergeTemplate($response, $emotionalTemplate, 'emotional');
        }

        // 3. 인지적 페르소나 기반 응답
        if (isset($persona['cognitive']) && isset($template['cognitive'][$persona['cognitive']])) {
            $cognitiveTemplate = $template['cognitive'][$persona['cognitive']];
            $response = $this->mergeTemplate($response, $cognitiveTemplate, 'cognitive');
        }

        // 4. 행동유형 기반 응답
        if (isset($persona['behavioral']) && isset($template['behavioral'][$persona['behavioral']])) {
            $behavioralTemplate = $template['behavioral'][$persona['behavioral']];
            $response = $this->mergeTemplate($response, $behavioralTemplate, 'behavioral');
        }

        // 5. 시간대별 변형 적용
        if (isset($template['temporal_variants']) && isset($contextData['temporal'])) {
            $response = $this->applyTemporalVariant($response, $template['temporal_variants'], $contextData['temporal']);
        }

        // 6. 변수 치환
        $response = $this->replaceVariables($response, $contextData);

        return $response;
    }

    /**
     * 복합 조건 매칭
     *
     * @param array $compositeTemplates 복합 템플릿 배열
     * @param array $persona 페르소나
     * @param array $contextData 컨텍스트
     * @return array|null
     */
    private function matchCompositeCondition($compositeTemplates, $persona, $contextData) {
        foreach ($compositeTemplates as $key => $composite) {
            if (!isset($composite['condition'])) {
                continue;
            }

            $condition = $composite['condition'];
            $matches = true;

            // 인지적 조건 체크
            if (isset($condition['cognitive'])) {
                if (!in_array($persona['cognitive'] ?? '', $condition['cognitive'])) {
                    $matches = false;
                }
            }

            // 행동유형 조건 체크
            if (isset($condition['behavioral'])) {
                if (!in_array($persona['behavioral'] ?? '', $condition['behavioral'])) {
                    $matches = false;
                }
            }

            // 정서적 조건 체크
            if (isset($condition['emotional'])) {
                if (!in_array($persona['emotional'] ?? '', $condition['emotional'])) {
                    $matches = false;
                }
            }

            // 에러 유형 조건 체크
            if (isset($condition['error_type'])) {
                if (($contextData['error_type'] ?? '') !== $condition['error_type']) {
                    $matches = false;
                }
            }

            if ($matches) {
                return [
                    'message' => $composite['message'] ?? '',
                    'cta' => $composite['cta'] ?? '',
                    'tone' => $composite['tone'] ?? 'adaptive',
                    'intervention' => $composite['intervention'] ?? null,
                    'composite_key' => $key
                ];
            }
        }

        return null;
    }

    /**
     * 템플릿 병합
     *
     * @param array $base 기본 응답
     * @param array $override 오버라이드 템플릿
     * @param string $source 소스 타입
     * @return array
     */
    private function mergeTemplate($base, $override, $source) {
        // 메시지가 있으면 오버라이드
        if (!empty($override['message'])) {
            $base['message'] = $override['message'];
            $base['source'] = $source;
        }

        // CTA 병합
        if (!empty($override['cta'])) {
            $base['cta'] = $override['cta'];
        }

        // 추가 필드 병합
        foreach (['encouragement', 'follow_up', 'strategy', 'support', 'intervention'] as $field) {
            if (isset($override[$field])) {
                $base[$field] = $override[$field];
            }
        }

        return $base;
    }

    /**
     * 시간대별 변형 적용
     *
     * @param array $response 응답
     * @param array $temporalVariants 시간대별 변형
     * @param string $temporalCode 현재 시간대 코드
     * @return array
     */
    private function applyTemporalVariant($response, $temporalVariants, $temporalCode) {
        $timeMapping = [
            'T1_CTX' => 'morning',
            'T2_CTX' => 'afternoon',
            'T3_CTX' => 'evening',
            'T4_CTX' => 'night',
            'T5_CTX' => 'weekend'
        ];

        $timeKey = $timeMapping[$temporalCode] ?? null;

        if ($timeKey && isset($temporalVariants[$timeKey])) {
            $variant = $temporalVariants[$timeKey];

            // 인사말 추가
            if (isset($variant['greeting'])) {
                $response['message'] = $variant['greeting'] . ' ' . $response['message'];
            }

            // 제안 추가
            if (isset($variant['suggestion'])) {
                $response['temporal_suggestion'] = $variant['suggestion'];
            }
        }

        return $response;
    }

    /**
     * 변수 치환
     *
     * @param array $response 응답
     * @param array $contextData 컨텍스트 데이터
     * @return array
     */
    private function replaceVariables($response, $contextData) {
        $replacements = [
            '{duration}' => $contextData['session_duration'] ?? '?',
            '{remaining_count}' => $contextData['remaining_items'] ?? '?',
            '{streak_count}' => $contextData['correct_streak'] ?? '?',
            '{improvement_percent}' => $contextData['improvement'] ?? '?',
            '{activity_name}' => $contextData['dominant_activity'] ?? '현재 활동',
            '{use_case}' => $contextData['practical_use'] ?? '실제 상황',
            '{review_count}' => $contextData['review_count'] ?? '?',
            '{time}' => $contextData['best_time'] ?? '?',
            '{percent}' => $contextData['percentage'] ?? '?',
            '{hint_content}' => $contextData['hint'] ?? '',
            '{first_step}' => $contextData['first_step'] ?? ''
        ];

        foreach ($response as $key => $value) {
            if (is_string($value)) {
                $response[$key] = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $value
                );
            }
        }

        return $response;
    }

    /**
     * 기본 응답 생성
     *
     * @param string $situationCode 상황 코드
     * @return array
     */
    private function getDefaultResponse($situationCode) {
        $defaults = [
            'S1' => ['message' => '잠시 쉬셨네요. 준비되시면 다시 시작해요!', 'cta' => '이어서 하기'],
            'S2' => ['message' => '천천히 생각해보세요.', 'cta' => '힌트 보기'],
            'S3' => ['message' => '휴식이 필요해 보여요.', 'cta' => '휴식하기'],
            'S4' => ['message' => '어려운 부분이 있나요?', 'cta' => '도움 받기'],
            'S5' => ['message' => '학습하시면서 기분이 어떠세요?', 'cta' => '계속하기'],
            'S6' => ['message' => '다양한 활동을 해보세요.', 'cta' => '다른 활동'],
            'S7' => ['message' => '좋은 학습 패턴이에요!', 'cta' => '계속하기']
        ];

        return array_merge(
            $defaults[$situationCode] ?? ['message' => '계속 진행할까요?', 'cta' => '확인'],
            ['tone' => 'neutral', 'source' => 'fallback']
        );
    }

    /**
     * 이모지 포함 여부에 따른 응답 정제
     *
     * @param array $response 응답
     * @param bool $includeEmoji 이모지 포함 여부
     * @return array
     */
    public function sanitizeResponse($response, $includeEmoji = true) {
        if (!$includeEmoji) {
            // 이모지 제거
            $emojiPattern = '/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}]/u';

            foreach ($response as $key => $value) {
                if (is_string($value)) {
                    $response[$key] = trim(preg_replace($emojiPattern, '', $value));
                }
            }
        }

        // 최대 길이 제한
        $maxLength = $this->config['response']['max_length'] ?? 500;
        if (isset($response['message']) && mb_strlen($response['message']) > $maxLength) {
            $response['message'] = mb_substr($response['message'], 0, $maxLength - 3) . '...';
        }

        return $response;
    }

    /**
     * 캐시 초기화
     */
    public function clearCache() {
        $this->templateCache = [];
    }

    /**
     * 사용 가능한 템플릿 목록 반환
     *
     * @return array
     */
    public function getAvailableTemplates() {
        return [
            'situations' => ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7'],
            'cognitive' => ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'],
            'behavioral' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6'],
            'emotional' => ['E1', 'E2', 'E3', 'E4', 'E5', 'E6']
        ];
    }
}

/*
 * 관련 데이터베이스 테이블:
 * - mdl_agent19_response_templates (id, situation_code, persona_code, template_data, is_active, created_at)
 * - mdl_agent19_response_log (id, user_id, template_id, response_text, context_data, created_at)
 *
 * 사용 예시:
 * $manager = new Agent19_TemplateManager();
 * $response = $manager->selectResponse('S4', [
 *     'cognitive' => 'C2',
 *     'behavioral' => 'B1',
 *     'emotional' => 'E2'
 * ], ['error_type' => 'consecutive']);
 */
