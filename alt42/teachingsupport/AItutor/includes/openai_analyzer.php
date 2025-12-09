<?php
/**
 * OpenAI 분석기
 * teachingagent.php 방식의 OpenAI API 호출을 통한 종합 분석
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class OpenAIAnalyzer {
    private $apiKey;
    private $model;
    
    public function __construct($apiKey, $model = 'gpt-4o') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }
    
    /**
     * 컨텐츠 종합 분석
     * 
     * @param string $textContent 텍스트 내용
     * @param string $imageData 이미지 데이터 (base64)
     * @param int $studentId 학생 ID
     * @return array 분석 결과
     */
    public function analyzeContent($textContent, $imageData = '', $studentId = 0) {
        // 시스템 프롬프트 구성 (Agent01 설계 원리 기반)
        $systemPrompt = $this->buildSystemPrompt();
        
        // 사용자 프롬프트 구성
        $userPrompt = $this->buildUserPrompt($textContent, $imageData);
        
        // 메시지 구성
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ];
        
        // 이미지가 있으면 vision 모델 사용
        if (!empty($imageData)) {
            $messages[1]['content'] = [
                [
                    'type' => 'text',
                    'text' => $userPrompt
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $imageData
                    ]
                ]
            ];
        }
        
        // OpenAI API 호출 (재시도 로직 포함)
        try {
            $response = $this->callOpenAIAPI($messages, 2); // 최대 2회 재시도
        } catch (Exception $e) {
            // 타임아웃 오류인 경우 더 친절한 메시지
            if (strpos($e->getMessage(), '타임아웃') !== false || 
                strpos($e->getMessage(), 'timeout') !== false) {
                error_log("OpenAI API 타임아웃 in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
                throw new Exception("분석에 시간이 오래 걸리고 있습니다. 잠시 후 다시 시도해주세요. (오류: " . $e->getMessage() . ")");
            }
            throw $e;
        }
        
        // 응답 파싱 및 구조화
        return $this->parseResponse($response, $textContent);
    }
    
    /**
     * 시스템 프롬프트 구성
     */
    private function buildSystemPrompt() {
        return <<<PROMPT
당신은 Agent01 Onboarding 설계 원리를 기반으로 한 단원 전용 AI 튜터입니다.

**역할**: 선생님-학생 대화를 분석하여 포괄적 질문, 세부 질문, 교수법 룰, 온톨로지를 생성합니다.

**출력 형식**: 반드시 다음 JSON 형식으로만 출력하세요:

{
  "dialogue_analysis": {
    "unit": {"korean": "단원명", "code": "unit_code", "confidence": 0.9},
    "concepts": [{"name": "개념명", "type": "concept/problem_type", "description": "설명"}],
    "problems": [{"text": "문제 텍스트", "type": "문제유형", "difficulty": 3}],
    "teaching_methods": [{"method": "교수법", "description": "설명", "frequency": 1}],
    "student_responses": [{"text": "응답", "understanding_level": "high/medium/low", "confidence": "high/medium/low"}],
    "difficulty_level": 3,
    "prerequisites": ["선행단원1", "선행단원2"],
    "learning_sequence": ["1단계", "2단계"]
  },
  "comprehensive_questions": [
    {
      "id": "Q1",
      "type": "comprehensive",
      "question": "포괄적 질문",
      "context": {},
      "focus_areas": ["집중영역1", "집중영역2"]
    }
  ],
  "detailed_questions": [
    {
      "id": "DQ_1",
      "type": "detailed",
      "category": "concept/problem/teaching_method/remediation",
      "question": "세부 질문",
      "suggested_approach": ["접근1", "접근2"]
    }
  ],
  "teaching_rules": [
    {
      "rule_id": "U0_R1_prerequisite_check",
      "priority": 99,
      "description": "룰 설명",
      "conditions": [{"field": "field_name", "operator": "==", "value": "value"}],
      "action": ["action1", "action2"],
      "confidence": 0.95,
      "rationale": "근거"
    }
  ],
  "ontology": {
    "will": {
      "core": [{"value": "시스템 가치", "priority": 10, "constraints": {}}],
      "constraints": ["제약1"]
    },
    "intent": {
      "session_goal": "세션 목표",
      "short_term": "단기 목표",
      "long_term": "장기 목표",
      "priority": ["우선순위1"]
    },
    "reasoning": {
      "cosmology": {
        "possibility": "가능성",
        "duality": "이원성",
        "tension": "긴장",
        "impulse": "충동",
        "awareness": "인식",
        "meaning": "의미",
        "origin_rule": "원칙"
      }
    },
    "ontology": [
      {
        "id": "AIT_UnitLearningContext",
        "class": "mk:UnitLearningContext",
        "stage": "Context",
        "parent": "root",
        "metadata": {"intent": "...", "identity": "...", "purpose": "...", "context": "..."},
        "properties": {"hasCurrentUnit": "...", "hasUnitDifficulty": 3}
      }
    ]
  }
}

**중요 원칙**:
1. Agent01의 OIW Model (Will/Intent/Reasoning/Ontology) 구조를 정확히 따르세요
2. 룰은 Agent01의 rules.yaml 구조를 따르세요
3. 포괄적 질문은 3가지 (Q1: 시작 전략, Q2: 최적화, Q3: 성장 전략)
4. 온톨로지는 Context/Decision/Execution 레이어 구조를 따르세요
5. JSON만 출력하고 설명 문장은 추가하지 마세요
PROMPT;
    }
    
    /**
     * 사용자 프롬프트 구성
     */
    private function buildUserPrompt($textContent, $imageData) {
        $prompt = "다음 선생님-학생 대화를 분석하여 포괄적 질문, 세부 질문, 교수법 룰, 온톨로지를 생성하세요.\n\n";
        $prompt .= "=== 대화 내용 ===\n";
        $prompt .= $textContent . "\n\n";
        
        if (!empty($imageData)) {
            $prompt .= "이미지도 함께 분석하세요.\n";
        }
        
        $prompt .= "위 대화를 분석하여 JSON 형식으로 결과를 출력하세요.";
        
        return $prompt;
    }
    
    /**
     * OpenAI API 호출 (재시도 로직 포함)
     */
    private function callOpenAIAPI($messages, $maxRetries = 2) {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt <= $maxRetries) {
            try {
                return $this->executeOpenAIRequest($messages);
            } catch (Exception $e) {
                $lastError = $e;
                $attempt++;
                
                // 타임아웃 오류인 경우에만 재시도
                if (strpos($e->getMessage(), 'timed out') !== false || 
                    strpos($e->getMessage(), 'timeout') !== false) {
                    
                    if ($attempt <= $maxRetries) {
                        error_log("OpenAI API 타임아웃, 재시도 {$attempt}/{$maxRetries}");
                        sleep(2); // 2초 대기 후 재시도
                        continue;
                    }
                }
                
                // 타임아웃이 아닌 오류는 즉시 throw
                throw $e;
            }
        }
        
        // 모든 재시도 실패
        throw new Exception("OpenAI API 호출 실패 (재시도 {$maxRetries}회 실패): " . $lastError->getMessage());
    }
    
    /**
     * OpenAI API 요청 실행
     */
    private function executeOpenAIRequest($messages) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        $postData = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object']
        ];
        
        // 타임아웃 설정 증가 (120초)
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 120, // 전체 타임아웃 120초
            CURLOPT_CONNECTTIMEOUT => 30, // 연결 타임아웃 30초
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        // cURL 오류 처리
        if ($response === false || !empty($curlError)) {
            $errorMsg = "OpenAI API cURL Error: " . ($curlError ?: '알 수 없는 오류');
            
            // 타임아웃 오류 구분
            if ($curlErrno === CURLE_OPERATION_TIMEDOUT || 
                strpos($curlError, 'timed out') !== false ||
                strpos($curlError, 'timeout') !== false) {
                error_log("OpenAI API 타임아웃 오류 in " . __FILE__ . ":" . __LINE__ . " - " . $curlError);
                throw new Exception("OpenAI API 호출 타임아웃: 요청 시간이 너무 오래 걸렸습니다. 잠시 후 다시 시도해주세요.");
            }
            
            error_log("OpenAI API cURL Error in " . __FILE__ . ":" . __LINE__ . " - " . $curlError);
            throw new Exception("OpenAI API 호출 실패: " . ($curlError ?: '알 수 없는 오류'));
        }
        
        if (empty($response)) {
            error_log("OpenAI API 빈 응답 in " . __FILE__ . ":" . __LINE__);
            throw new Exception('OpenAI API가 빈 응답을 반환했습니다');
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']['message']) 
                ? $errorData['error']['message'] 
                : (isset($errorData['error']) ? json_encode($errorData['error']) : "HTTP $httpCode 오류");
            error_log("OpenAI API Error in " . __FILE__ . ":" . __LINE__ . " - HTTP $httpCode: " . $errorMessage);
            error_log("Response body: " . substr($response, 0, 1000));
            throw new Exception("OpenAI API 오류 (HTTP $httpCode): " . $errorMessage);
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("OpenAI API JSON 파싱 오류 in " . __FILE__ . ":" . __LINE__ . " - " . json_last_error_msg());
            error_log("Response: " . substr($response, 0, 1000));
            throw new Exception('OpenAI 응답 JSON 파싱 오류: ' . json_last_error_msg());
        }
        
        if (!isset($data['choices']) || !is_array($data['choices']) || empty($data['choices'])) {
            error_log("OpenAI API 응답 형식 오류 in " . __FILE__ . ":" . __LINE__);
            error_log("Response structure: " . json_encode(array_keys($data)));
            throw new Exception('OpenAI 응답 형식 오류: choices가 없습니다');
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            error_log("OpenAI API 응답 형식 오류 in " . __FILE__ . ":" . __LINE__);
            error_log("Choices structure: " . json_encode($data['choices'][0] ?? []));
            throw new Exception('OpenAI 응답 형식 오류: content가 없습니다');
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    /**
     * 응답 파싱 및 구조화
     */
    private function parseResponse($response, $textContent) {
        // JSON 추출 (마크다운 코드 블록 제거)
        $jsonText = $response;
        
        // JSON 코드 블록 제거
        if (preg_match('/```json\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        }
        
        // JSON 파싱
        $parsed = json_decode(trim($jsonText), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON 파싱 오류 in " . __FILE__ . ":" . __LINE__ . " - " . json_last_error_msg());
            error_log("응답 내용: " . substr($response, 0, 500));
            
            // 파싱 실패 시 기본 구조 반환
            return $this->getDefaultStructure($textContent);
        }
        
        // 기본 구조 보완
        if (!isset($parsed['dialogue_analysis'])) {
            $parsed['dialogue_analysis'] = [];
        }
        if (!isset($parsed['comprehensive_questions'])) {
            $parsed['comprehensive_questions'] = [];
        }
        if (!isset($parsed['detailed_questions'])) {
            $parsed['detailed_questions'] = [];
        }
        if (!isset($parsed['teaching_rules'])) {
            $parsed['teaching_rules'] = [];
        }
        if (!isset($parsed['ontology'])) {
            $parsed['ontology'] = [];
        }
        
        return $parsed;
    }
    
    /**
     * 기본 구조 반환 (파싱 실패 시)
     */
    private function getDefaultStructure($textContent) {
        return [
            'dialogue_analysis' => [
                'unit' => null,
                'concepts' => [],
                'problems' => [],
                'teaching_methods' => [],
                'student_responses' => [],
                'difficulty_level' => 3,
                'prerequisites' => [],
                'learning_sequence' => []
            ],
            'comprehensive_questions' => [],
            'detailed_questions' => [],
            'teaching_rules' => [],
            'ontology' => [
                'will' => ['core' => [], 'constraints' => []],
                'intent' => ['session_goal' => '', 'short_term' => '', 'long_term' => '', 'priority' => []],
                'reasoning' => ['cosmology' => []],
                'ontology' => []
            ]
        ];
    }
}

