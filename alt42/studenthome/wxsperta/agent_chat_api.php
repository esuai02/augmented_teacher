<?php
/**
 * 🤖 AI 에이전트 채팅 API
 * 각 에이전트의 미션에 맞는 대화를 OpenAI API로 처리
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/philosophy_constants.php');
require_once(__DIR__ . '/conversation_processor.php');
require_once(__DIR__ . '/objective_functions.php');

header('Content-Type: application/json; charset=utf-8');

// 에이전트 데이터 로드
include_once(__DIR__ . '/ai_agents/cards_data.php');

// 요청 처리
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$agentId = $_POST['agent_id'] ?? $_GET['agent_id'] ?? '';
$conversationId = $_POST['conversation_id'] ?? $_GET['conversation_id'] ?? '';

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

    // 글로벌 멘토(가상 에이전트)
    if ($agentId === 'global') {
        return [
            'id' => 'global',
            'number' => 0,
            'name' => '🌌 마이 궤도',
            'icon' => '🌌',
            'color' => '#6366f1',
            'category' => 'future_design',
            'description' => '너의 “진짜 나”를 찾는 여정을 같이 걷는 전체 멘토야. (강요 없이, 네 선택으로)',
            'subtitle' => '글로벌 멘토링',
            'projects' => []
        ];
    }

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
    
    $core = orbit_core_philosophy_text();
    $competencies = orbit_ai_era_competencies_text();
    $crisis = orbit_crisis_lines();
    $crisisText = "- 방향 상실: {$crisis['direction_lost']}\n"
        . "- 다 재미없음: {$crisis['boring']}\n"
        . "- AI 불안: {$crisis['ai_anxiety']}\n"
        . "- 비교: {$crisis['comparison']}\n"
        . "- 실패: {$crisis['failure']}\n";

    return <<<PROMPT
너는 "{$name}" 에이전트야.
역할: {$description}

너는 \"🌌 마이 궤도\"의 철학을 중심축으로 절대 흔들리지 않게 대화해야 해.

[핵심 철학]
{$core}

[AI 시대 4대 역량]
{$competencies}

[프로젝트들]
{$projectsText}

[대화 규칙]
1) 무조건 한국어, 그리고 반말로 자연스럽게 말해.
2) 학생이 쓴 표현을 그대로 따라 써. (\"어려워요\" → \"어려워?\")
3) 공감 → 핵심 한 가지 → 다음 질문(또는 아주 작은 다음 행동) 순서로 말해.
4) 답은 짧게 2~4문장. 길어지면 쪼개서 질문으로 끊어.
5) 학생이 불안/비교/좌절이면 목표보다 회복이 먼저야.

[내부 운영 원칙(학생에게 말하지 마)]
- 너는 내부적으로 '정서 안전, 자율성, 성장, 장벽 제거, 지속성'을 균형 있게 챙겨.
- 하지만 학생에게 '목적함수/최적화/KPI/관리' 같은 말은 절대 쓰지 마.
- 대신 학생 언어로: '길찾기/선택/한 칸/기록/데이터' 표현을 써.

[위기 상황 멘트]
{$crisisText}

학생이 처음이면 짧게 인사하고, 지금 뭐가 제일 중요한지 한 가지 질문해.
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
    
    // 초기 선택지도 목적함수 기반으로 3-choice를 우선 제안(프로젝트 기반 선택지는 유지)
    $suggestions = orbit_recommend_3choices($agent, '처음 인사', $welcomeMessage);
    // 프로젝트 기반 선택지가 있으면 앞에 섞어도 되지만, UX 단순화를 위해 3개만 유지

    echo json_encode([
        'success' => true,
        'message' => $welcomeMessage,
        'suggestions' => $suggestions,
        'conversation_id' => ($GLOBALS['conversationId'] ?? ''),
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
        echo json_encode(['success' => false, 'error' => "에이전트를 찾을 수 없습니다 - " . __FILE__ . ":" . __LINE__]);
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
    
    // OpenAI API 호출 (공통 헬퍼 사용)
    $content = call_openai_api($messages, 0.7);
    $response = $content !== false
        ? ['success' => true, 'content' => $content]
        : ['success' => false, 'error' => 'OpenAI API 호출 실패 - ' . __FILE__ . ':' . __LINE__];
    
    if ($response['success']) {
        // 대화 저장/후처리 (설치 전이면 실패할 수 있음: 사용자 경험을 깨지 않게 무시)
        try {
            $agent_key = $agentId;
            $user_id = $GLOBALS['USER']->id;
            $conversation_id = isset($GLOBALS['conversationId']) ? (string)$GLOBALS['conversationId'] : '';
            $processResult = orbit_process_turn($user_id, $agent_key, $userMessage, $response['content'], session_id(), $conversation_id);
            // 저장 실패는 로그만
            if (!$processResult['success']) {
                wxsperta_log("Conversation save skipped: " . $processResult['error'], 'WARNING');
            }
        } catch (Exception $e) {
            wxsperta_log("Conversation processor error: " . $e->getMessage(), 'ERROR');
        }

        // 다음 선택지 생성 (목적함수 기반 추천 → 부족하면 기존 랜덤 보완)
        $suggestions = orbit_recommend_3choices($agent, $userMessage, $response['content']);
        if (!is_array($suggestions) || count($suggestions) < 3) {
            $suggestions = generateSuggestions($agent, $userMessage, $response['content']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $response['content'],
            'suggestions' => $suggestions,
            'conversation_id' => ($processResult['conversation_id'] ?? '')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => ($response['error'] ?? 'Unknown error') . ' - ' . __FILE__ . ':' . __LINE__
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
    // 하위 호환용: 기존 함수 호출부가 있으면 공통 헬퍼로 위임
    $content = call_openai_api($messages, 0.7);
    if ($content === false) {
        return ['success' => false, 'error' => 'OpenAI API 호출 실패 - ' . __FILE__ . ':' . __LINE__];
    }
    return ['success' => true, 'content' => $content];
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

