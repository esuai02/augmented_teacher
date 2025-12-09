<?php
/**
 * Response Generator for Agent07 Persona System
 *
 * 식별된 페르소나에 맞는 응답을 생성
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Related Files:
 * - PersonaRuleEngine.php: 페르소나 식별 결과 제공
 * - templates/: 응답 템플릿 파일들
 * - personas.md: 페르소나별 톤/접근법 정의
 *
 * DB Tables:
 * - mdl_agent07_response_log: 응답 로그
 */

class ResponseGenerator {

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var int 사용자 ID */
    private $userId;

    /** @var array 설정값 */
    private $config;

    /** @var string 템플릿 디렉토리 경로 */
    private $templateDir;

    /**
     * 생성자
     *
     * @param object $db Moodle $DB 객체
     * @param int $userId 사용자 ID
     * @param array $config 설정 배열
     */
    public function __construct($db, $userId, $config = array()) {
        $this->db = $db;
        $this->userId = $userId;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->templateDir = dirname(__FILE__) . '/../templates/';
    }

    /**
     * 기본 설정값 반환
     *
     * @return array
     */
    private function getDefaultConfig() {
        return array(
            'enable_logging' => true,
            'default_tone' => 'supportive, encouraging',
            'max_response_length' => 2000,
            'include_persona_info' => false,
            'language' => 'ko'
        );
    }

    /**
     * 응답 생성 메인 메서드
     *
     * @param array $identificationResult PersonaRuleEngine의 식별 결과
     * @param array $additionalContext 추가 컨텍스트 (메시지 등)
     * @return array 생성된 응답 데이터
     */
    public function generate($identificationResult, $additionalContext = array()) {
        try {
            if (!$identificationResult['success']) {
                return $this->generateErrorResponse($identificationResult);
            }

            $personaId = $identificationResult['persona']['persona_id'];
            $situationId = $identificationResult['situation']['situation_id'];
            $responseConfig = isset($identificationResult['persona']['response_config'])
                ? $identificationResult['persona']['response_config']
                : array();

            // 1. 템플릿 로드
            $template = $this->loadTemplate($personaId, $situationId);

            // 2. 응답 데이터 구성
            $responseData = $this->buildResponseData(
                $template,
                $identificationResult,
                $additionalContext
            );

            // 3. 응답 텍스트 생성
            $responseText = $this->renderResponse($template, $responseData);

            // 4. 결과 구성
            $result = array(
                'success' => true,
                'response_text' => $responseText,
                'persona_id' => $personaId,
                'situation_id' => $situationId,
                'tone' => isset($responseConfig['tone'])
                    ? $responseConfig['tone']
                    : $this->config['default_tone'],
                'confidence' => $identificationResult['persona']['confidence'],
                'template_used' => $template['name'],
                'timestamp' => date('Y-m-d H:i:s')
            );

            // 5. 로깅
            if ($this->config['enable_logging']) {
                $this->logResponse($result, $additionalContext);
            }

            return $result;

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'timestamp' => date('Y-m-d H:i:s')
            );
        }
    }

    /**
     * 템플릿 로드
     *
     * @param string $personaId 페르소나 ID
     * @param string $situationId 상황 ID
     * @return array 템플릿 데이터
     */
    private function loadTemplate($personaId, $situationId) {
        // 페르소나별 템플릿 시도
        $personaTemplate = $this->templateDir . $personaId . '.php';
        if (file_exists($personaTemplate)) {
            return $this->parseTemplate($personaTemplate, $personaId);
        }

        // 상황별 템플릿 시도
        $situationTemplate = $this->templateDir . $situationId . '.php';
        if (file_exists($situationTemplate)) {
            return $this->parseTemplate($situationTemplate, $situationId);
        }

        // 기본 템플릿 사용
        $defaultTemplate = $this->templateDir . 'default.php';
        if (file_exists($defaultTemplate)) {
            return $this->parseTemplate($defaultTemplate, 'default');
        }

        // 템플릿 없으면 인라인 기본값
        return $this->getInlineDefaultTemplate();
    }

    /**
     * 템플릿 파일 파싱
     *
     * @param string $filePath 템플릿 파일 경로
     * @param string $name 템플릿 이름
     * @return array
     */
    private function parseTemplate($filePath, $name) {
        $content = file_get_contents($filePath);

        return array(
            'name' => $name,
            'path' => $filePath,
            'content' => $content,
            'type' => 'file'
        );
    }

    /**
     * 인라인 기본 템플릿
     *
     * @return array
     */
    private function getInlineDefaultTemplate() {
        return array(
            'name' => 'inline_default',
            'path' => null,
            'content' => '안녕하세요! 무엇을 도와드릴까요?',
            'type' => 'inline'
        );
    }

    /**
     * 응답 데이터 구성
     *
     * @param array $template 템플릿 데이터
     * @param array $identificationResult 식별 결과
     * @param array $additionalContext 추가 컨텍스트
     * @return array
     */
    private function buildResponseData($template, $identificationResult, $additionalContext) {
        $data = array(
            'user_id' => $this->userId,
            'persona_id' => $identificationResult['persona']['persona_id'],
            'persona_name' => $identificationResult['persona']['name'],
            'situation_id' => $identificationResult['situation']['situation_id'],
            'situation_name' => $identificationResult['situation']['name'],
            'confidence' => $identificationResult['persona']['confidence'],
            'context' => $identificationResult['context_snapshot'],
            'message' => isset($additionalContext['message'])
                ? $additionalContext['message']
                : '',
            'timestamp' => time()
        );

        // 추가 컨텍스트 병합
        return array_merge($data, $additionalContext);
    }

    /**
     * 응답 렌더링
     *
     * @param array $template 템플릿 데이터
     * @param array $data 응답 데이터
     * @return string
     */
    private function renderResponse($template, $data) {
        if ($template['type'] === 'file' && $template['path']) {
            // PHP 템플릿 실행
            ob_start();
            extract($data);
            include $template['path'];
            $response = ob_get_clean();
        } else {
            // 인라인 템플릿 사용
            $response = $this->processInlineTemplate($template['content'], $data);
        }

        // 길이 제한 적용
        if (mb_strlen($response) > $this->config['max_response_length']) {
            $response = mb_substr($response, 0, $this->config['max_response_length']) . '...';
        }

        return trim($response);
    }

    /**
     * 인라인 템플릿 처리
     *
     * @param string $content 템플릿 내용
     * @param array $data 데이터
     * @return string
     */
    private function processInlineTemplate($content, $data) {
        // {{변수명}} 형태의 플레이스홀더 치환
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function($matches) use ($data) {
                $key = $matches[1];
                return isset($data[$key]) ? $data[$key] : '';
            },
            $content
        );
    }

    /**
     * 에러 응답 생성
     *
     * @param array $identificationResult 실패한 식별 결과
     * @return array
     */
    private function generateErrorResponse($identificationResult) {
        return array(
            'success' => false,
            'response_text' => '죄송합니다. 잠시 문제가 발생했습니다. 다시 시도해 주세요.',
            'error' => isset($identificationResult['error'])
                ? $identificationResult['error']
                : 'Unknown error',
            'timestamp' => date('Y-m-d H:i:s')
        );
    }

    /**
     * 응답 로깅
     *
     * @param array $result 응답 결과
     * @param array $context 컨텍스트
     */
    private function logResponse($result, $context) {
        try {
            $record = new stdClass();
            $record->userid = $this->userId;
            $record->persona_id = $result['persona_id'];
            $record->situation_id = $result['situation_id'];
            $record->response_text = mb_substr($result['response_text'], 0, 500);
            $record->confidence_score = $result['confidence'];
            $record->user_message = isset($context['message'])
                ? mb_substr($context['message'], 0, 500)
                : '';
            $record->created_at = time();

            $this->db->insert_record('agent07_response_log', $record);

        } catch (Exception $e) {
            error_log(sprintf(
                "[ResponseGenerator] Logging failed: %s (File: %s, Line: %d)",
                $e->getMessage(),
                __FILE__,
                __LINE__
            ));
        }
    }

    /**
     * 페르소나별 인사말 가져오기
     *
     * @param string $personaId 페르소나 ID
     * @return string
     */
    public function getGreeting($personaId) {
        $greetings = array(
            // S1: 실시간고민
            'S1_P1' => '어디서부터 막혔는지 차근차근 살펴볼게요.',
            'S1_P2' => '어떤 부분이 헷갈리시나요? 함께 정리해 봐요.',
            'S1_P3' => '좋은 질문이에요! 어떤 것이 궁금하세요?',

            // S2: 포모도로
            'S2_P1' => '집중이 흐트러진 것 같네요. 잠시 환기해 볼까요?',
            'S2_P2' => '힘드시죠? 무엇이 방해가 되고 있나요?',
            'S2_P3' => '열심히 하고 계시네요! 진행 상황을 체크해 볼까요?',

            // S3: 수업준비
            'S3_P1' => '수업 전에 확인해 볼 것들이 있어요.',
            'S3_P2' => '시간이 촉박하시네요. 핵심만 빠르게 정리해 드릴게요.',
            'S3_P3' => '질문을 미리 준비하시다니 좋은 습관이에요!',

            // S4: 목표설정
            'S4_P1' => '어떤 학습을 하고 싶으신가요?',
            'S4_P2' => '학습 목표를 함께 정리해 볼까요?',
            'S4_P3' => '장기적인 계획을 세우고 싶으시군요!',

            // S5: 귀가검사
            'S5_P1' => '오늘 하루 어떠셨어요?',
            'S5_P2' => '오늘 배운 것들을 정리해 볼까요?',
            'S5_P3' => '서둘러 끝내야 하시는군요. 핵심만 확인할게요.',

            // S6: 커리큘럼
            'S6_P1' => '학습 방향을 고민하고 계시군요.',
            'S6_P2' => '전체적인 로드맵을 함께 살펴볼까요?',
            'S6_P3' => '새로운 분야를 탐색하시는군요!'
        );

        return isset($greetings[$personaId])
            ? $greetings[$personaId]
            : '안녕하세요! 무엇을 도와드릴까요?';
    }

    /**
     * 설정 업데이트
     *
     * @param array $config 새 설정
     */
    public function setConfig($config) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 현재 설정 반환
     *
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }
}

/*
 * DB Table: mdl_agent07_response_log
 *
 * CREATE TABLE mdl_agent07_response_log (
 *     id BIGINT(10) NOT NULL AUTO_INCREMENT,
 *     userid BIGINT(10) NOT NULL,
 *     persona_id VARCHAR(10) NOT NULL,
 *     situation_id VARCHAR(10) NOT NULL,
 *     response_text TEXT,
 *     confidence_score DECIMAL(3,2),
 *     user_message TEXT,
 *     created_at BIGINT(10) NOT NULL,
 *     PRIMARY KEY (id),
 *     INDEX idx_userid (userid),
 *     INDEX idx_persona (persona_id),
 *     INDEX idx_created (created_at)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
