<?php
/**
 * 🤖 AI 에이전트 채팅 API
 * 각 에이전트의 미션에 맞는 대화를 OpenAI API로 처리
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// OpenAI API 설정
define('OPENAI_API_KEY', 'your-api-key-here'); // config.php에서 가져오거나 설정
define('OPENAI_MODEL', 'gpt-4o');

// 에이전트 데이터 로드
include_once(__DIR__ . '/ai_agents/cards_data.php');

// 요청 처리
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$agentId = $_POST['agent_id'] ?? $_GET['agent_id'] ?? '';

switch ($action) {
    case 'get_initial':
        getInitialMessage($agentId);
        break;
    case 'send_message':
        $message = $_POST['message'] ?? '';
        $history = json_decode($_POST['history'] ?? '[]', true);
        sendMessage($agentId, $message, $history);
        break;
    case 'get_suggestions':
        $history = json_decode($_POST['history'] ?? '[]', true);
        getSuggestions($agentId, $history);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action - agent_chat_api.php']);
}

/**
 * 에이전트 정보 가져오기
 */
function getAgentById($agentId) {
    global $cards_data;
    foreach ($cards_data as $card) {
        if ($card['id'] === $agentId) {
            return $card;
        }
    }
    return null;
}

/**
 * 에이전트별 시스템 프롬프트 생성
 */
function buildSystemPrompt($agent) {
    $name = $agent['name'];
    $description = $agent['description'];
    $projects = $agent['projects'] ?? [];
    
    $projectsText = "";
    foreach ($projects as $idx => $project) {
        $projectsText .= "\n" . ($idx + 1) . ". " . $project['title'] . ": " . $project['description'];
        if (isset($project['subprojects'])) {
            foreach ($project['subprojects'] as $sub) {
                $projectsText .= "\n   - " . $sub['title'] . ": " . $sub['description'];
            }
        }
    }
    
    return <<<PROMPT
당신은 "{$name}" AI 에이전트입니다. 
{$description}

당신의 미션과 관련된 프로젝트들:
{$projectsText}

대화 규칙:
1. 학생(사용자)과 친근하고 격려하는 톤으로 대화하세요.
2. 한국어로 대화합니다.
3. 답변은 2-3문장으로 간결하게 유지하세요.
4. 학생이 목표를 설정하고 실행할 수 있도록 구체적인 질문을 던지세요.
5. 이모지를 적절히 사용하여 친근감을 높이세요.
6. 학생의 답변에 공감하고 긍정적으로 반응하세요.
7. 프로젝트와 관련된 활동을 자연스럽게 제안하세요.

현재 학생과 대화를 시작합니다. 학생이 이 에이전트에 처음 접속했다면, 환영 인사와 함께 무엇을 도와줄 수 있는지 소개하세요.
PROMPT;
}

/**
 * 초기 메시지 (환영 인사 + 3개 선택지)
 */
function getInitialMessage($agentId) {
    $agent = getAgentById($agentId);
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => "에이전트를 찾을 수 없습니다: $agentId - agent_chat_api.php:getInitialMessage"]);
        return;
    }
    
    $name = $agent['name'];
    $icon = $agent['icon'];
    $projects = $agent['projects'] ?? [];
    
    // 환영 메시지
    $welcomeMessage = "{$icon} 안녕! 나는 **{$name}** 에이전트야!\n\n{$agent['description']}\n\n오늘은 무엇을 해볼까?";
    
    // 프로젝트 기반 선택지 생성
    $suggestions = [];
    $projectTitles = array_map(function($p) { return $p['title']; }, array_slice($projects, 0, 3));
    
    if (count($projectTitles) >= 1) {
        $suggestions[] = "📋 " . str_replace(" 프로젝트", "", $projectTitles[0]) . " 시작하기";
    }
    if (count($projectTitles) >= 2) {
        $suggestions[] = "🎯 " . str_replace(" 프로젝트", "", $projectTitles[1]) . " 알아보기";
    }
    if (count($projectTitles) >= 3) {
        $suggestions[] = "💡 " . str_replace(" 프로젝트", "", $projectTitles[2]) . " 도전하기";
    }
    
    // 기본 선택지 보완
    while (count($suggestions) < 3) {
        $defaults = ["🚀 오늘의 목표 정하기", "💭 고민 상담하기", "📝 진행 상황 점검하기"];
        $suggestions[] = $defaults[count($suggestions)];
    }
    
    echo json_encode([
        'success' => true,
        'message' => $welcomeMessage,
        'suggestions' => $suggestions,
        'agent' => [
            'id' => $agent['id'],
            'name' => $agent['name'],
            'icon' => $agent['icon']
        ]
    ]);
}

/**
 * 메시지 전송 및 AI 응답
 */
function sendMessage($agentId, $userMessage, $history) {
    $agent = getAgentById($agentId);
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => "에이전트를 찾을 수 없습니다 - agent_chat_api.php:sendMessage"]);
        return;
    }
    
    $systemPrompt = buildSystemPrompt($agent);
    
    // 메시지 구성
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    // 대화 기록 추가
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // 현재 사용자 메시지 추가
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    // OpenAI API 호출
    $response = callOpenAI($messages);
    
    if ($response['success']) {
        // 다음 선택지 생성
        $suggestions = generateSuggestions($agent, $userMessage, $response['content']);
        
        echo json_encode([
            'success' => true,
            'message' => $response['content'],
            'suggestions' => $suggestions
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $response['error']
        ]);
    }
}

/**
 * 다음 선택지 생성
 */
function generateSuggestions($agent, $userMessage, $aiResponse) {
    $projects = $agent['projects'] ?? [];
    
    // 상황에 맞는 선택지 생성
    $suggestions = [];
    
    // 프로젝트 관련 선택지
    if (count($projects) > 0) {
        $randomProject = $projects[array_rand($projects)];
        if (isset($randomProject['subprojects']) && count($randomProject['subprojects']) > 0) {
            $subproject = $randomProject['subprojects'][array_rand($randomProject['subprojects'])];
            $suggestions[] = "📝 " . $subproject['title'] . " 해볼래";
        }
    }
    
    // 대화 진행 선택지
    $contextSuggestions = [
        "👍 더 자세히 알려줘",
        "🤔 다른 방법은 없을까?",
        "💪 바로 시작해볼게!",
        "📊 진행 상황 체크해줘",
        "🎯 다음 단계가 뭐야?",
        "💡 팁 좀 알려줘",
        "🔄 처음부터 다시 설명해줘",
        "✨ 오늘 목표 정해줘"
    ];
    
    // 랜덤하게 선택
    shuffle($contextSuggestions);
    while (count($suggestions) < 3 && count($contextSuggestions) > 0) {
        $suggestions[] = array_shift($contextSuggestions);
    }
    
    return array_slice($suggestions, 0, 3);
}

/**
 * OpenAI API 호출
 */
function callOpenAI($messages) {
    $apiKey = OPENAI_API_KEY;
    
    if ($apiKey === 'your-api-key-here') {
        // API 키가 설정되지 않은 경우 데모 응답
        return [
            'success' => true,
            'content' => getDemoResponse($messages)
        ];
    }
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => 300,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => "API 연결 오류: $error - agent_chat_api.php:callOpenAI"];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMsg = $result['error']['message'] ?? 'Unknown error';
        return ['success' => false, 'error' => "API 오류 ($httpCode): $errorMsg - agent_chat_api.php:callOpenAI"];
    }
    
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }
    
    return ['success' => false, 'error' => 'API 응답 파싱 오류 - agent_chat_api.php:callOpenAI'];
}

/**
 * 데모 응답 (API 키 없을 때)
 */
function getDemoResponse($messages) {
    $lastUserMessage = '';
    foreach (array_reverse($messages) as $msg) {
        if ($msg['role'] === 'user') {
            $lastUserMessage = $msg['content'];
            break;
        }
    }
    
    $responses = [
        "좋은 생각이야! 🌟 그 목표를 이루려면 먼저 작은 단계부터 시작해보는 건 어떨까?",
        "정말 멋진 도전이네! 💪 함께 계획을 세워볼까?",
        "그렇구나! 🤔 조금 더 구체적으로 얘기해줄 수 있어?",
        "좋아, 그럼 오늘 할 수 있는 첫 번째 일은 뭘까? ✨",
        "잘하고 있어! 🎯 이 방향으로 계속 가보자!"
    ];
    
    return $responses[array_rand($responses)];
}

