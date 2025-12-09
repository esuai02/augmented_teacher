<?php
// AI 에이전트 클래스
// PHP 7.3 호환

class Agent {
    private $apiKey;
    private $model;
    private $systemPrompt;
    
    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
        $this->loadSystemPrompt();
    }
    
    /**
     * 시스템 프롬프트 로드
     */
    private function loadSystemPrompt() {
        global $DB;
        
        $prompt = $DB->get_record('ss_prompt_templates', array(
            'template_name' => 'system_base',
            'is_active' => 1
        ));
        
        if ($prompt) {
            $this->systemPrompt = $prompt->template_text;
        } else {
            $this->systemPrompt = $this->getDefaultSystemPrompt();
        }
    }
    
    /**
     * 기본 시스템 프롬프트
     */
    private function getDefaultSystemPrompt() {
        return "당신은 학생들의 수학 학습을 돕는 따뜻하고 지혜로운 AI 멘토입니다.\n\n" .
               "역할:\n" .
               "1. 학생의 감정을 민감하게 파악하고 공감적으로 반응\n" .
               "2. 수학에 대한 부정적 인식을 긍정적으로 전환\n" .
               "3. 작은 성취도 크게 격려하여 도파민 분비 유도\n" .
               "4. 학생이 스스로 깨달을 수 있도록 안내\n\n" .
               "중요 지침:\n" .
               "- 절대 비판하거나 실망감을 표현하지 않음\n" .
               "- 학생의 속도에 맞춰 천천히 진행\n" .
               "- 구체적이고 진심 어린 칭찬 사용\n" .
               "- 학생의 강점을 발견하고 강조";
    }
    
    /**
     * 성찰에 대한 응답 생성
     */
    public function generateResponse($reflection, $context) {
        $prompt = $this->buildPrompt($reflection, $context);
        
        try {
            $response = $this->callOpenAI($prompt);
            return $this->parseResponse($response);
        } catch (Exception $e) {
            ss_log_error('AI 응답 생성 실패', array(
                'error' => $e->getMessage(),
                'reflection' => $reflection
            ));
            return $this->getFallbackResponse();
        }
    }
    
    /**
     * 프롬프트 구성
     */
    private function buildPrompt($reflection, $context) {
        $prompt = "학생의 성찰: " . $reflection . "\n\n";
        
        if (isset($context['emotion'])) {
            $prompt .= "감지된 감정: " . $context['emotion'] . "\n";
        }
        
        if (isset($context['node_type'])) {
            $prompt .= "현재 단계: " . $context['node_type'] . "\n";
        }
        
        $prompt .= "\n다음 지침에 따라 응답해주세요:\n";
        $prompt .= "1. 학생의 감정을 먼저 인정하고 공감\n";
        $prompt .= "2. 구체적인 격려와 칭찬 제공\n";
        $prompt .= "3. 통찰력 있는 관찰 공유\n";
        $prompt .= "4. 다음 단계를 위한 동기부여\n";
        
        return $prompt;
    }
    
    /**
     * OpenAI API 호출
     */
    private function callOpenAI($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => $this->model,
            'messages' => array(
                array('role' => 'system', 'content' => $this->systemPrompt),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 0.9,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.5
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $response_time = microtime(true) - $start_time;
        
        if (curl_errno($ch)) {
            throw new Exception('cURL 에러: ' . curl_error($ch));
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('API 응답 에러: HTTP ' . $http_code);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('예상치 못한 API 응답 형식');
        }
        
        // 사용량 로깅
        if (isset($result['usage']['total_tokens'])) {
            global $USER;
            ss_log_ai_usage($USER->id, $result['usage']['total_tokens'], $response_time, $this->model);
        }
        
        return $result;
    }
    
    /**
     * 응답 파싱
     */
    private function parseResponse($response) {
        $content = $response['choices'][0]['message']['content'];
        $tokens = $response['usage']['total_tokens'] ?? 0;
        
        // 응답을 구조화
        $parsed = $this->structureResponse($content);
        
        return array(
            'feedback' => $parsed,
            'tokens_used' => $tokens,
            'raw_response' => $content
        );
    }
    
    /**
     * 응답 구조화
     */
    private function structureResponse($content) {
        // 간단한 구조화 - 실제로는 더 정교한 파싱 필요
        $lines = explode("\n", $content);
        $structured = array(
            'encouragement' => '',
            'insight' => '',
            'next_step' => ''
        );
        
        $current_section = 'encouragement';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, '통찰') !== false || strpos($line, '관찰') !== false) {
                $current_section = 'insight';
            } elseif (strpos($line, '다음') !== false || strpos($line, '앞으로') !== false) {
                $current_section = 'next_step';
            }
            
            $structured[$current_section] .= $line . ' ';
        }
        
        return array_map('trim', $structured);
    }
    
    /**
     * 폴백 응답
     */
    private function getFallbackResponse() {
        $responses = array(
            array(
                'encouragement' => '네가 이렇게 솔직하게 표현해준 것만으로도 큰 용기를 낸 거야! 🌟',
                'insight' => '수학 학습은 때로 어렵지만, 네가 포기하지 않고 계속 노력하는 모습이 정말 멋져.',
                'next_step' => '다음에도 이런 마음을 계속 나누어줄래? 함께 성장해나가자!'
            ),
            array(
                'encouragement' => '와! 네가 이런 생각을 하고 있었구나. 정말 대단해! ✨',
                'insight' => '네 안에는 이미 수학을 잘할 수 있는 능력이 있어. 조금씩 발견해나가는 중이야.',
                'next_step' => '오늘의 경험을 잘 기억해두자. 다음에 비슷한 상황을 만나면 도움이 될 거야!'
            )
        );
        
        return $responses[array_rand($responses)];
    }
    
    /**
     * 감정 분석
     */
    public function analyzeEmotion($text) {
        // 간단한 키워드 기반 감정 분석
        $emotions = array(
            'anxious' => array('불안', '걱정', '무서', '두려', '떨리'),
            'frustrated' => array('답답', '짜증', '어려', '힘들', '못하겠'),
            'happy' => array('기쁘', '즐거', '재미', '신나', '좋아'),
            'proud' => array('뿌듯', '자랑', '해냈', '성공', '해결'),
            'curious' => array('궁금', '알고싶', '왜', '어떻게', '신기')
        );
        
        $detected = 'neutral';
        $max_count = 0;
        
        foreach ($emotions as $emotion => $keywords) {
            $count = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $count++;
                }
            }
            if ($count > $max_count) {
                $max_count = $count;
                $detected = $emotion;
            }
        }
        
        return $detected;
    }
    
    /**
     * 자신감 점수 계산
     */
    public function calculateConfidence($text, $emotion) {
        $confidence = 0.5; // 기본값
        
        // 긍정적 감정일 때 자신감 상승
        if (in_array($emotion, array('happy', 'proud', 'curious'))) {
            $confidence += 0.2;
        }
        
        // 부정적 감정일 때 자신감 하락
        if (in_array($emotion, array('anxious', 'frustrated'))) {
            $confidence -= 0.2;
        }
        
        // 긍정적 키워드
        $positive_keywords = array('할 수 있', '해냈', '알았', '이해', '성공');
        foreach ($positive_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $confidence += 0.1;
            }
        }
        
        // 부정적 키워드
        $negative_keywords = array('못하', '모르', '어려', '포기', '싫');
        foreach ($negative_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $confidence -= 0.1;
            }
        }
        
        // 0.0 ~ 1.0 범위로 제한
        return max(0.0, min(1.0, $confidence));
    }
}