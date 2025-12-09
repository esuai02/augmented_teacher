<?php
/**
 * Chat Bridge for Holonic WXSPERTA
 * 기존 index1~4.php, indexm.php의 채팅 정보를 WXSPERTA 시스템과 연동
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");
require_once("event_bus.php");
require_once("llm_orchestrator.php");
require_once("approval_system.php");

class ChatBridge {
    private $db;
    private $eventBus;
    private $orchestrator;
    private $approvalSystem;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->eventBus = new EventBus();
        $this->orchestrator = new LLMOrchestrator();
        $this->approvalSystem = new ApprovalSystem();
    }
    
    /**
     * 채팅 메시지 수신 및 처리
     * 기존 페이지에서 AJAX로 호출
     */
    public function processMessage($data) {
        $user_id = $data['user_id'] ?? 0;
        $message = $data['message'] ?? '';
        $page_type = $data['page_type'] ?? ''; // index1, index2, etc.
        $context = $data['context'] ?? [];
        $session_id = $data['session_id'] ?? session_id();
        
        // 페이지 타입에 따른 에이전트 매핑
        $agent_id = $this->mapPageToAgent($page_type);
        
        // 채팅 컨텍스트 업데이트
        $this->updateChatContext($session_id, $user_id, $agent_id, $message, $context);
        
        // 이벤트 발행
        $event_id = $this->eventBus->emit('student_question', 'user', $user_id, [
            'message' => $message,
            'agent_id' => $agent_id,
            'page_type' => $page_type,
            'context' => $context,
            'session_id' => $session_id
        ]);
        
        // LLM 응답 생성
        $response = $this->generateResponse($agent_id, $user_id, $message, $context);
        
        // 인사이트 추출 및 자동 업데이트 트리거
        $insights = $this->extractInsights($message, $response);
        if ($insights['needs_update']) {
            $this->triggerAgentUpdate($agent_id, $user_id, $insights);
        }
        
        // 응답 저장
        $this->saveInteraction($user_id, $agent_id, $message, $response, $session_id);
        
        return [
            'success' => true,
            'response' => $response,
            'agent_id' => $agent_id,
            'insights' => $insights,
            'event_id' => $event_id
        ];
    }
    
    /**
     * 페이지 타입별 에이전트 매핑
     */
    private function mapPageToAgent($page_type) {
        $mapping = [
            'index1' => 1,  // 개념학습 → 시간 수정체
            'index2' => 2,  // 문제풀이 → 타임라인 합성기
            'index3' => 5,  // 복습 → 동기 엔진
            'index4' => 7,  // 시험대비 → 일일 사령부
            'indexm' => 15  // 종합 → 시간수정체 CEO
        ];
        
        return $mapping[$page_type] ?? 1;
    }
    
    /**
     * 채팅 컨텍스트 업데이트
     */
    private function updateChatContext($session_id, $user_id, $agent_id, $message, $context) {
        $chat_context = $this->db->get_record('wxsperta_chat_contexts', [
            'session_id' => $session_id
        ]);
        
        if (!$chat_context) {
            $chat_context = new stdClass();
            $chat_context->session_id = $session_id;
            $chat_context->user_id = $user_id;
            $chat_context->agent_id = $agent_id;
            $chat_context->context_summary = '';
            $chat_context->emotion_state = 'neutral';
            $chat_context->learning_progress = json_encode([]);
            $chat_context->last_updated = time();
            
            $this->db->insert_record('wxsperta_chat_contexts', $chat_context);
        } else {
            // 컨텍스트 요약 업데이트
            $summary = $this->summarizeContext($chat_context->context_summary, $message);
            $chat_context->context_summary = $summary;
            $chat_context->last_updated = time();
            
            $this->db->update_record('wxsperta_chat_contexts', $chat_context);
        }
    }
    
    /**
     * LLM 응답 생성
     */
    private function generateResponse($agent_id, $user_id, $message, $context) {
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        if (!$agent) {
            return "죄송합니다. 일시적인 오류가 발생했습니다.";
        }
        
        // 에이전트 속성 가져오기
        $props = $this->getAgentProperties($agent_id);
        
        // 최근 대화 이력
        $recent_interactions = $this->getRecentInteractions($user_id, $agent_id, 10);
        
        // 프롬프트 구성
        $system_prompt = $this->buildSystemPrompt($agent, $props, $context);
        $user_prompt = $this->buildUserPrompt($message, $recent_interactions);
        
        // OpenAI API 호출
        $response = call_openai_api([
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ], 0.8);
        
        if (!$response) {
            // 폴백 응답
            return $this->getFallbackResponse($agent_id, $message);
        }
        
        return $response;
    }
    
    /**
     * 인사이트 추출
     */
    private function extractInsights($message, $response) {
        $insights = [
            'needs_update' => false,
            'emotion' => 'neutral',
            'learning_gaps' => [],
            'suggested_actions' => []
        ];
        
        // 감정 분석
        $emotions = ['좌절', '힘들', '어려', '못하겠', '포기'];
        foreach ($emotions as $emotion) {
            if (strpos($message, $emotion) !== false) {
                $insights['emotion'] = 'frustrated';
                $insights['needs_update'] = true;
                break;
            }
        }
        
        // 학습 격차 감지
        $gaps = ['모르겠', '이해가 안', '설명해', '뭔지', '헷갈려'];
        foreach ($gaps as $gap) {
            if (strpos($message, $gap) !== false) {
                $insights['learning_gaps'][] = $gap;
                $insights['needs_update'] = true;
            }
        }
        
        // 목표 관련 키워드
        $goals = ['목표', '계획', '시험', '준비', '공부법'];
        foreach ($goals as $goal) {
            if (strpos($message, $goal) !== false) {
                $insights['suggested_actions'][] = 'create_study_plan';
                $insights['needs_update'] = true;
            }
        }
        
        return $insights;
    }
    
    /**
     * 에이전트 업데이트 트리거
     */
    private function triggerAgentUpdate($agent_id, $user_id, $insights) {
        // Holon Loop 실행
        $this->orchestrator->executeHolonLoop($agent_id, [
            'type' => 'user_insights',
            'insights' => $insights,
            'user_id' => $user_id
        ]);
        
        // 감정 상태에 따른 추가 에이전트 활성화
        if ($insights['emotion'] === 'frustrated') {
            // 동기 엔진 활성화
            $this->eventBus->emit('urgent_support_needed', 'system', 0, [
                'user_id' => $user_id,
                'emotion' => $insights['emotion'],
                'original_agent' => $agent_id
            ], ['type' => 'agent', 'id' => 5]);
        }
        
        // 학습 격차가 있으면 프로젝트 생성 제안
        if (!empty($insights['learning_gaps'])) {
            $this->suggestRemedialProject($agent_id, $user_id, $insights['learning_gaps']);
        }
    }
    
    /**
     * 보충 학습 프로젝트 제안
     */
    private function suggestRemedialProject($agent_id, $user_id, $learning_gaps) {
        $project_data = [
            'title' => '보충 학습: ' . implode(', ', $learning_gaps),
            'description' => '학습 격차를 해결하기 위한 맞춤형 보충 프로젝트',
            'priority' => 80,
            'wxsperta_layers' => [
                'worldView' => '모든 학습 격차는 체계적인 접근으로 해결할 수 있다',
                'context' => '현재 이해도와 목표 사이의 격차 분석',
                'structure' => '단계별 보충 학습 커리큘럼',
                'process' => '1) 기초 개념 복습 2) 응용 문제 연습 3) 심화 이해'
            ]
        ];
        
        // 승인 요청 생성
        $this->approvalSystem->createApprovalRequest(
            'project_create',
            'agent',
            $agent_id,
            $project_data,
            $agent_id,
            $user_id
        );
    }
    
    /**
     * 상호작용 저장
     */
    private function saveInteraction($user_id, $agent_id, $message, $response, $session_id) {
        $interaction = new stdClass();
        $interaction->user_id = $user_id;
        $interaction->agent_id = $agent_id;
        $interaction->interaction_type = 'chat';
        $interaction->user_input = $message;
        $interaction->agent_response = $response;
        $interaction->session_id = $session_id;
        $interaction->created_at = time();
        
        $this->db->insert_record('wxsperta_interactions', $interaction);
    }
    
    /**
     * 시스템 프롬프트 구성
     */
    private function buildSystemPrompt($agent, $props, $context) {
        $page_context = isset($context['page_type']) ? $context['page_type'] : '';
        $learning_mode = $this->getLearningMode($page_context);
        
        return "당신은 {$agent->name}입니다.
역할: {$agent->description}

현재 학습 모드: {$learning_mode}
세계관: {$props['worldView']}
문맥: {$props['context']}
절차: {$props['process']}

학생의 질문에 따뜻하고 격려하는 톤으로 응답하세요.
구체적이고 실용적인 조언을 제공하세요.
필요시 다음 학습 단계를 제안하세요.";
    }
    
    /**
     * 사용자 프롬프트 구성
     */
    private function buildUserPrompt($message, $recent_interactions) {
        $history = "";
        if (!empty($recent_interactions)) {
            $history = "\n\n최근 대화 맥락:\n";
            foreach (array_slice($recent_interactions, -3) as $interaction) {
                $history .= "학생: " . substr($interaction->user_input, 0, 100) . "...\n";
                $history .= "AI: " . substr($interaction->agent_response, 0, 100) . "...\n\n";
            }
        }
        
        return $history . "현재 질문: " . $message;
    }
    
    /**
     * 학습 모드 확인
     */
    private function getLearningMode($page_type) {
        $modes = [
            'index1' => '개념 학습',
            'index2' => '문제 풀이',
            'index3' => '복습',
            'index4' => '시험 대비',
            'indexm' => '종합 학습'
        ];
        
        return $modes[$page_type] ?? '일반 학습';
    }
    
    /**
     * 최근 상호작용 가져오기
     */
    private function getRecentInteractions($user_id, $agent_id, $limit) {
        return $this->db->get_records_sql("
            SELECT * FROM {wxsperta_interactions}
            WHERE user_id = ? AND agent_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ", [$user_id, $agent_id, $limit]);
    }
    
    /**
     * 에이전트 속성 가져오기
     */
    private function getAgentProperties($agent_id) {
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        return [
            'worldView' => $agent->world_view ?? '',
            'context' => $agent->context ?? '',
            'structure' => $agent->structure ?? '',
            'process' => $agent->process ?? '',
            'execution' => $agent->execution ?? '',
            'reflection' => $agent->reflection ?? '',
            'transfer' => $agent->transfer ?? '',
            'abstraction' => $agent->abstraction ?? ''
        ];
    }
    
    /**
     * 컨텍스트 요약
     */
    private function summarizeContext($existing_summary, $new_message) {
        // 간단한 요약 (실제로는 LLM 사용 가능)
        $summary = $existing_summary;
        if (strlen($summary) > 500) {
            $summary = substr($summary, -300);
        }
        
        $summary .= "\n[" . date('H:i') . "] " . substr($new_message, 0, 100);
        return $summary;
    }
    
    /**
     * 폴백 응답
     */
    private function getFallbackResponse($agent_id, $message) {
        $responses = [
            1 => "좋은 질문이네요! 미래의 목표와 연결해서 생각해볼까요?",
            2 => "단계별로 접근해보죠. 먼저 가장 작은 부분부터 시작해볼까요?",
            5 => "잘하고 있어요! 조금만 더 힘내면 목표에 도달할 수 있어요.",
            7 => "오늘의 학습 계획을 함께 세워볼까요?",
            15 => "전체적인 진행 상황을 보니 좋은 성과를 내고 있네요!"
        ];
        
        return $responses[$agent_id] ?? "네, 이해했습니다. 더 자세히 설명해주시겠어요?";
    }
}

// AJAX 엔드포인트
if (isset($_POST['action']) && $_POST['action'] === 'process_message') {
    require_login();
    
    $bridge = new ChatBridge();
    
    $data = [
        'user_id' => $USER->id,
        'message' => $_POST['message'] ?? '',
        'page_type' => $_POST['page_type'] ?? '',
        'context' => json_decode($_POST['context'] ?? '{}', true),
        'session_id' => session_id()
    ];
    
    $result = $bridge->processMessage($data);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>