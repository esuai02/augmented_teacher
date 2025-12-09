<?php
/**
 * WXSPERTA 업데이트 분석 엔드포인트
 * 채팅 대화를 분석하여 에이전트 속성 업데이트 제안
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");

class UpdateAnalyzer {
    private $db;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }
    
    /**
     * 대화 분석 및 업데이트 제안
     */
    public function analyzeConversation($agent_id, $user_input, $ai_response, $current_properties) {
        // LLM을 통한 대화 분석
        $analysis_prompt = $this->buildAnalysisPrompt($user_input, $ai_response, $current_properties);
        
        // OpenAI API 호출
        $suggested_updates = $this->callLLMForAnalysis($analysis_prompt);
        
        if (!$suggested_updates) {
            // 폴백: 키워드 기반 분석
            $suggested_updates = $this->fallbackAnalysis($user_input, $ai_response, $current_properties);
        }
        
        // 변경사항 필터링 (실제로 변경이 필요한 것만)
        $filtered_updates = $this->filterSignificantChanges($suggested_updates, $current_properties);
        
        return [
            'success' => true,
            'suggested_updates' => $filtered_updates,
            'reasoning' => $this->generateUpdateReasoning($filtered_updates, $user_input)
        ];
    }
    
    /**
     * 분석 프롬프트 구성
     */
    private function buildAnalysisPrompt($user_input, $ai_response, $current_properties) {
        return "당신은 교육 AI 시스템의 분석가입니다. 학생과 AI 에이전트 간의 대화를 분석하여 에이전트의 WXSPERTA 속성을 업데이트해야 할지 결정해주세요.

현재 에이전트 속성:
- 세계관(worldView): {$current_properties['worldView']}
- 문맥(context): {$current_properties['context']}
- 구조(structure): {$current_properties['structure']}
- 절차(process): {$current_properties['process']}
- 실행(execution): {$current_properties['execution']}
- 성찰(reflection): {$current_properties['reflection']}
- 전파(transfer): {$current_properties['transfer']}
- 추상화(abstraction): {$current_properties['abstraction']}

대화 내용:
학생: {$user_input}
AI: {$ai_response}

다음 기준으로 분석해주세요:
1. 학생의 학습 스타일이나 선호도가 드러났는가?
2. 현재 접근 방법이 효과적이지 않은 신호가 있는가?
3. 새로운 학습 목표나 요구사항이 나타났는가?
4. 감정적 지원이나 동기부여 전략 변경이 필요한가?

업데이트가 필요한 속성과 새로운 값을 JSON 형식으로 제공해주세요.
변경이 필요없다면 빈 객체 {}를 반환하세요.

예시:
{
    \"worldView\": \"학습은 개인의 속도에 맞춰 진행되어야 한다\",
    \"process\": \"1) 기초 개념 확인 2) 단계별 설명 3) 실습 문제 제공\"
}";
    }
    
    /**
     * LLM을 통한 분석
     */
    private function callLLMForAnalysis($prompt) {
        $response = call_openai_api([
            ['role' => 'system', 'content' => 'You are an educational AI analyst. Analyze conversations and suggest property updates in JSON format.'],
            ['role' => 'user', 'content' => $prompt]
        ], 0.7, 'gpt-4o');
        
        if (!$response) {
            return null;
        }
        
        // JSON 추출
        preg_match('/\{[^}]+\}/', $response, $matches);
        if (!empty($matches[0])) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        
        return null;
    }
    
    /**
     * 폴백 분석 (키워드 기반)
     */
    private function fallbackAnalysis($user_input, $ai_response, $current_properties) {
        $updates = [];
        $input_lower = mb_strtolower($user_input);
        
        // 학습 스타일 키워드
        if (strpos($input_lower, '그림') !== false || strpos($input_lower, '시각') !== false) {
            $updates['process'] = $current_properties['process'] . ' (시각적 자료 활용 강화)';
        }
        
        if (strpos($input_lower, '예시') !== false || strpos($input_lower, '예제') !== false) {
            $updates['execution'] = '구체적인 예시와 실습 문제 중심의 설명';
        }
        
        // 감정 키워드
        if (strpos($input_lower, '어려') !== false || strpos($input_lower, '힘들') !== false) {
            $updates['worldView'] = '모든 학생은 자신만의 속도로 성장할 수 있다';
            $updates['context'] = '격려와 단계별 접근이 필요한 상황';
        }
        
        // 목표 키워드
        if (strpos($input_lower, '시험') !== false || strpos($input_lower, '준비') !== false) {
            $updates['structure'] = '시험 대비 집중 학습 구조';
            $updates['abstraction'] = '효율적인 시험 준비와 실전 대비';
        }
        
        return $updates;
    }
    
    /**
     * 중요한 변경사항만 필터링
     */
    private function filterSignificantChanges($suggested, $current) {
        $filtered = [];
        
        foreach ($suggested as $key => $value) {
            // 현재 값과 다르고, 의미있는 변경인 경우만 포함
            if (!isset($current[$key]) || $current[$key] !== $value) {
                // 단순 추가가 아닌 실제 변경인지 확인
                if (!isset($current[$key]) || 
                    strlen($value) > strlen($current[$key]) * 0.5 || 
                    similar_text($current[$key], $value) < 80) {
                    $filtered[$key] = $value;
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * 업데이트 이유 생성
     */
    private function generateUpdateReasoning($updates, $user_input) {
        if (empty($updates)) {
            return "현재 설정이 적절합니다.";
        }
        
        $reasons = [];
        
        foreach ($updates as $key => $value) {
            switch ($key) {
                case 'worldView':
                    $reasons[] = "학생의 학습 철학에 맞춰 세계관 조정";
                    break;
                case 'context':
                    $reasons[] = "현재 학습 상황을 반영한 문맥 업데이트";
                    break;
                case 'process':
                    $reasons[] = "더 효과적인 학습 절차로 개선";
                    break;
                case 'execution':
                    $reasons[] = "실행 방법을 학생 선호도에 맞춤";
                    break;
                case 'reflection':
                    $reasons[] = "성찰 방식을 학습 스타일에 최적화";
                    break;
            }
        }
        
        return implode(", ", $reasons);
    }
}

// AJAX 핸들러
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['agent_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }
    
    $analyzer = new UpdateAnalyzer();
    
    $result = $analyzer->analyzeConversation(
        $input['agent_id'],
        $input['user_input'] ?? '',
        $input['ai_response'] ?? '',
        $input['current_properties'] ?? []
    );
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>