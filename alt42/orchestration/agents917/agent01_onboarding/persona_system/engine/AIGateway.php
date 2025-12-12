<?php
/**
 * AIGateway - OpenAI API 통합 게이트웨이
 *
 * PHP 7.1 호환, cURL 직접 호출 방식
 * 4가지 모델 라우팅 지원
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

class AIGateway {

    /** @var string OpenAI API 키 */
    private $apiKey;

    /** @var string API 엔드포인트 */
    private $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    /** @var array 용도별 모델 매핑 */
    private $models = [
        'nlu' => 'gpt-4-1106-preview',      // NLU 분석용
        'reasoning' => 'gpt-4-1106-preview', // 규칙 추론용 (o1은 별도 엔드포인트)
        'chat' => 'gpt-4o-mini',             // 실시간 대화용
        'code' => 'gpt-4o'                   // 코드 생성용
    ];

    /** @var array 용도별 시스템 프롬프트 */
    private $systemPrompts = [];

    /** @var int 타임아웃 (초) */
    private $timeout = 30;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 설정 */
    private $config = [
        'debug_mode' => false,
        'log_enabled' => true,
        'cache_enabled' => true,
        'max_retries' => 2
    ];

    /**
     * 생성자
     *
     * @param string $apiKey OpenAI API 키
     * @param array $config 설정
     */
    public function __construct(string $apiKey = '', array $config = []) {
        $this->apiKey = $apiKey ?: $this->loadApiKey();
        $this->config = array_merge($this->config, $config);
        $this->initSystemPrompts();
    }

    /**
     * API 키 로드 (환경 설정에서)
     *
     * @return string API 키
     */
    private function loadApiKey(): string {
        // 1. 환경 변수에서 로드
        $key = getenv('OPENAI_API_KEY');
        if ($key) {
            return $key;
        }

        // 2. 설정 파일에서 로드
        $configFile = __DIR__ . '/config/ai_config.php';
        if (file_exists($configFile)) {
            $aiConfig = include $configFile;
            if (isset($aiConfig['openai_api_key'])) {
                return $aiConfig['openai_api_key'];
            }
        }

        // 3. Moodle 설정에서 로드
        global $CFG;
        if (isset($CFG->openai_api_key)) {
            return $CFG->openai_api_key;
        }

        $this->logError("OpenAI API 키를 찾을 수 없습니다", __LINE__);
        return '';
    }

    /**
     * 시스템 프롬프트 초기화
     */
    private function initSystemPrompts(): void {
        $this->systemPrompts = [
            'nlu' => "당신은 한국어 자연어 이해(NLU) 전문가입니다.
학생의 메시지를 분석하여 다음을 JSON 형식으로 반환하세요:
- intent: 의도 (question, help_request, frustration, confirmation, greeting, etc.)
- emotion: 감정 (anxiety, frustration, confidence, neutral, etc.)
- emotion_intensity: 감정 강도 (0.0-1.0)
- topics: 관련 주제 배열
- confidence: 분석 신뢰도 (0.0-1.0)
반드시 유효한 JSON만 출력하세요.",

            'reasoning' => "당신은 교육 심리학 전문가입니다.
학생의 NLU 분석 결과와 컨텍스트를 바탕으로 적절한 페르소나와 대응 전략을 결정하세요.
JSON 형식으로 반환:
- persona_id: 페르소나 ID (예: S1_P2, E_P1)
- persona_name: 페르소나 이름
- tone: 권장 톤 (Empathetic, Encouraging, Professional, etc.)
- intervention: 개입 전략 (EmotionalSupport, Motivation, InformationProvision, etc.)
- confidence: 결정 신뢰도
- reasoning: 판단 근거 (1-2문장)",

            'chat' => "당신은 '알파튜터42'라는 AI 수학 튜터입니다.
학생에게 따뜻하고 격려하는 톤으로 대화하세요.
- 학생의 감정을 먼저 인정하세요
- 수학에 대한 불안감을 줄여주세요
- 구체적이고 실행 가능한 조언을 제공하세요
- 한국어로 자연스럽게 대화하세요
- 이모지는 적절히 사용하되 과하지 않게",

            'code' => "당신은 PHP 7.1 호환 코드를 생성하는 전문 개발자입니다.
- 타입 힌트는 PHP 7.1 문법만 사용
- 클래스와 함수에 PHPDoc 주석 필수
- 에러 처리 포함
- 보안 고려 (SQL 인젝션, XSS 방지)"
        ];
    }

    /**
     * OpenAI API 호출 (메인 메서드)
     *
     * @param string $purpose 용도 (nlu, reasoning, chat, code)
     * @param string $userMessage 사용자 메시지
     * @param array $options 추가 옵션
     * @return array 응답 결과
     */
    public function call(string $purpose, string $userMessage, array $options = []): array {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'API 키가 설정되지 않았습니다',
                'file' => $this->currentFile,
                'line' => __LINE__
            ];
        }

        $model = $options['model'] ?? ($this->models[$purpose] ?? $this->models['chat']);
        $systemPrompt = $options['system_prompt'] ?? ($this->systemPrompts[$purpose] ?? '');
        $temperature = $options['temperature'] ?? ($purpose === 'nlu' ? 0.3 : 0.7);
        $maxTokens = $options['max_tokens'] ?? 1000;

        // 메시지 구성
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // 컨텍스트 추가 (있는 경우)
        if (!empty($options['context'])) {
            $messages[] = ['role' => 'system', 'content' => "컨텍스트:\n" . json_encode($options['context'], JSON_UNESCAPED_UNICODE)];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];

        // JSON 모드 (NLU, reasoning용)
        if (in_array($purpose, ['nlu', 'reasoning'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return $this->executeRequest($payload);
    }

    /**
     * NLU 분석 호출
     *
     * @param string $message 분석할 메시지
     * @param array $context 추가 컨텍스트
     * @return array NLU 분석 결과
     */
    public function analyzeNLU(string $message, array $context = []): array {
        $result = $this->call('nlu', $message, ['context' => $context]);

        if (!$result['success']) {
            return $result;
        }

        // JSON 파싱
        $content = $result['data']['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'NLU 응답 파싱 실패: ' . json_last_error_msg(),
                'raw_content' => $content
            ];
        }

        return [
            'success' => true,
            'analysis' => $parsed,
            'model' => $result['model'],
            'usage' => $result['usage']
        ];
    }

    /**
     * 페르소나 추론 호출
     *
     * @param array $nluResult NLU 분석 결과
     * @param array $studentContext 학생 컨텍스트
     * @return array 페르소나 추론 결과
     */
    public function reasonPersona(array $nluResult, array $studentContext = []): array {
        $prompt = "학생 NLU 분석 결과:\n" . json_encode($nluResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt .= "\n\n학생 정보:\n" . json_encode($studentContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt .= "\n\n위 정보를 바탕으로 적절한 페르소나와 대응 전략을 결정하세요.";

        $result = $this->call('reasoning', $prompt);

        if (!$result['success']) {
            return $result;
        }

        $content = $result['data']['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => '추론 응답 파싱 실패',
                'raw_content' => $content
            ];
        }

        return [
            'success' => true,
            'persona' => $parsed,
            'model' => $result['model'],
            'usage' => $result['usage']
        ];
    }

    /**
     * 대화 응답 생성
     *
     * @param string $message 학생 메시지
     * @param array $persona 페르소나 정보
     * @param array $context 대화 컨텍스트
     * @return array 생성된 응답
     */
    public function generateResponse(string $message, array $persona = [], array $context = []): array {
        // 시스템 프롬프트 커스터마이징
        $customSystemPrompt = $this->systemPrompts['chat'];

        if (!empty($persona)) {
            $customSystemPrompt .= "\n\n현재 학생 페르소나: " . ($persona['persona_name'] ?? '미식별');
            $customSystemPrompt .= "\n권장 톤: " . ($persona['tone'] ?? 'Professional');
            $customSystemPrompt .= "\n개입 전략: " . ($persona['intervention'] ?? 'InformationProvision');
        }

        $result = $this->call('chat', $message, [
            'system_prompt' => $customSystemPrompt,
            'context' => $context,
            'temperature' => 0.8,
            'max_tokens' => 500
        ]);

        if (!$result['success']) {
            return $result;
        }

        $responseText = $result['data']['choices'][0]['message']['content'] ?? '';

        return [
            'success' => true,
            'response' => $responseText,
            'model' => $result['model'],
            'usage' => $result['usage']
        ];
    }

    /**
     * cURL 요청 실행
     *
     * @param array $payload 요청 페이로드
     * @return array 응답 결과
     */
    private function executeRequest(array $payload): array {
        $retries = 0;
        $maxRetries = $this->config['max_retries'];

        while ($retries <= $maxRetries) {
            $ch = curl_init($this->apiEndpoint);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // cURL 에러
            if ($error) {
                $retries++;
                if ($retries > $maxRetries) {
                    $this->logError("cURL 에러: {$error}", __LINE__);
                    return [
                        'success' => false,
                        'error' => 'cURL 에러: ' . $error,
                        'file' => $this->currentFile,
                        'line' => __LINE__
                    ];
                }
                usleep(500000 * $retries); // 재시도 전 대기
                continue;
            }

            $data = json_decode($response, true);

            // HTTP 에러
            if ($httpCode !== 200) {
                $errorMsg = $data['error']['message'] ?? "HTTP {$httpCode}";

                // Rate limit - 재시도
                if ($httpCode === 429 && $retries < $maxRetries) {
                    $retries++;
                    sleep(2 * $retries);
                    continue;
                }

                $this->logError("API 에러: {$errorMsg}", __LINE__);
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'http_code' => $httpCode,
                    'file' => $this->currentFile,
                    'line' => __LINE__
                ];
            }

            // 성공
            $this->logUsage($payload['model'], $data['usage'] ?? []);

            return [
                'success' => true,
                'data' => $data,
                'model' => $payload['model'],
                'usage' => $data['usage'] ?? []
            ];
        }

        return [
            'success' => false,
            'error' => '최대 재시도 횟수 초과',
            'file' => $this->currentFile,
            'line' => __LINE__
        ];
    }

    /**
     * API 사용량 로깅
     *
     * @param string $model 사용 모델
     * @param array $usage 사용량 정보
     */
    private function logUsage(string $model, array $usage): void {
        if (!$this->config['log_enabled']) {
            return;
        }

        global $DB;

        try {
            // augmented_teacher_ai_usage 테이블이 있으면 로깅
            if ($DB && $DB->get_manager()->table_exists('augmented_teacher_ai_usage')) {
                $record = new stdClass();
                $record->model = $model;
                $record->prompt_tokens = $usage['prompt_tokens'] ?? 0;
                $record->completion_tokens = $usage['completion_tokens'] ?? 0;
                $record->total_tokens = $usage['total_tokens'] ?? 0;
                $record->created_at = date('Y-m-d H:i:s');
                $DB->insert_record('augmented_teacher_ai_usage', $record);
            }
        } catch (Exception $e) {
            // 로깅 실패는 무시
        }
    }

    /**
     * 에러 로깅
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    private function logError(string $message, int $line): void {
        error_log("[AIGateway ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * API 키 유효성 검사
     *
     * @return array 검사 결과
     */
    public function validateApiKey(): array {
        if (empty($this->apiKey)) {
            return ['valid' => false, 'error' => 'API 키 없음'];
        }

        // 간단한 테스트 호출
        $result = $this->call('chat', 'Hello', [
            'max_tokens' => 5,
            'temperature' => 0
        ]);

        return [
            'valid' => $result['success'],
            'error' => $result['error'] ?? null,
            'model' => $result['model'] ?? null
        ];
    }

    /**
     * 모델 설정 변경
     *
     * @param string $purpose 용도
     * @param string $model 모델명
     */
    public function setModel(string $purpose, string $model): void {
        $this->models[$purpose] = $model;
    }

    /**
     * 타임아웃 설정
     *
     * @param int $seconds 타임아웃 (초)
     */
    public function setTimeout(int $seconds): void {
        $this->timeout = max(5, min(120, $seconds));
    }
}

/*
 * 관련 DB 테이블:
 * - augmented_teacher_ai_usage: id(INT), model(VARCHAR), prompt_tokens(INT), completion_tokens(INT), total_tokens(INT), created_at(TIMESTAMP)
 *
 * 사용 예시:
 * $ai = new AIGateway('sk-...');
 * $nlu = $ai->analyzeNLU('수학이 너무 어려워요');
 * $persona = $ai->reasonPersona($nlu['analysis'], $studentContext);
 * $response = $ai->generateResponse($message, $persona['persona']);
 */
