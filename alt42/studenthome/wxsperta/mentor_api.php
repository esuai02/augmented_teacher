<?php
/**
 * 🎯 21개 에이전트 멘토링 시스템 API
 * 퀵테스트 → 에이전트 매칭 → 순차 대화 → 자동 연결
 *
 * 파일: mentor_api.php
 * 위치: /alt42/studenthome/wxsperta/
 */

include_once("/home/moodle/public_html/moodle/config.php");
include_once(__DIR__ . '/config.php'); // OpenAI API 설정 포함
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// 설정 - config.php의 OPENAI_API_KEY 및 OPENAI_MODEL 사용

// 에이전트 데이터 로드
include_once(__DIR__ . '/ai_agents/cards_data.php');

// ============================================================
// 21개 에이전트 목적함수 및 진입점 정의
// ============================================================
$agent_objectives = [
    1  => ['objective' => '미래 자아 시각화', 'entry' => '5년 뒤 너는 어디서 뭘 하고 있을 것 같아? 미래의 너에게 편지를 써볼까?', 'icon' => '📡', 'name' => '미래 통신'],
    2  => ['objective' => '계획 현실화', 'entry' => '이루고 싶은 목표가 있어? 거기까지 가는 길을 같이 그려볼까?', 'icon' => '🗺️', 'name' => '항로 설계'],
    3  => ['objective' => '성장 분석', 'entry' => '작년의 너와 지금의 너, 뭐가 달라졌어? 얼마나 성장했는지 확인해볼까?', 'icon' => '📊', 'name' => '비행 기록'],
    4  => ['objective' => '목표 수치화', 'entry' => '네 꿈을 숫자로 바꿔볼 수 있어? 예를 들면 "수학 90점" 같은 거!', 'icon' => '⭐', 'name' => '별자리 만들기'],
    5  => ['objective' => '동기 부여', 'entry' => '오늘 공부 의욕이 몇 퍼센트야? 뭘 하면 충전이 돼?', 'icon' => '⚡', 'name' => '에너지 충전'],
    6  => ['objective' => '전략 분석', 'entry' => '너의 숨겨진 강점이 뭔지 알아? 같이 찾아볼까?', 'icon' => '🔍', 'name' => '스캐너'],
    7  => ['objective' => '일정 관리', 'entry' => '오늘 딱 3가지만 해낸다면 뭘 하고 싶어?', 'icon' => '🎯', 'name' => '오늘의 미션'],
    8  => ['objective' => '정체성 구축', 'entry' => '너를 한 문장으로 소개한다면? "나는 ____한 사람이다"', 'icon' => '💎', 'name' => '정체성 코어'],
    9  => ['objective' => '본질 탐구', 'entry' => '왜 수학 공부해? 진짜 이유를 찾아볼까? (왜? 왜? 왜?)', 'icon' => '🔬', 'name' => '딥 스캔'],
    10 => ['objective' => '자료 정리', 'entry' => '좋은 공부 자료 발견하면 어디에 저장해? 보물창고 만들어볼까?', 'icon' => '📦', 'name' => '자원 수집'],
    11 => ['objective' => '작업 자동화', 'entry' => '매일 반복하는 귀찮은 일 있어? 자동으로 처리해볼까?', 'icon' => '🤖', 'name' => '자동 드론'],
    12 => ['objective' => '개인 브랜딩', 'entry' => '너를 모르는 사람에게 어떻게 소개하고 싶어? SNS 프로필 같이 만들어볼까?', 'icon' => '📢', 'name' => '신호 발사'],
    13 => ['objective' => '도전 과제', 'entry' => '30일 동안 매일 해보고 싶은 작은 도전이 있어? 뭐든 좋아!', 'icon' => '🏕️', 'name' => '30일 원정'],
    14 => ['objective' => '경쟁 전략', 'entry' => '다른 친구들한테 없는 나만의 특기가 뭐야? 없으면 같이 만들어볼까?', 'icon' => '🛡️', 'name' => '특수 장비'],
    15 => ['objective' => 'AI CEO 자문', 'entry' => '이번 주 어땠어? 잘한 거 1개, 아쉬운 거 1개 말해볼래?', 'icon' => '🗼', 'name' => '관제탑 리뷰'],
    16 => ['objective' => '지식 가꾸기', 'entry' => '오늘 배운 거 하나만 말해볼래? 그거 어디에 연결되는지 알아볼까', 'icon' => '🌱', 'name' => '지식 농장'],
    17 => ['objective' => '학습 설계', 'entry' => '수학 공부할 때 어떤 순서로 해? 더 효율적인 방법 찾아볼까?', 'icon' => '🔗', 'name' => '학습 회로'],
    18 => ['objective' => '정보 허브', 'entry' => '평소에 유용한 정보는 어디서 얻어? 자동으로 모아주면 어때?', 'icon' => '📡', 'name' => '정보 위성'],
    19 => ['objective' => '지식 연결', 'entry' => '수학이랑 과학이 연결된다는 거 알아? 네가 아는 것들을 연결해볼까?', 'icon' => '🌌', 'name' => '성운 지도'],
    20 => ['objective' => '지식 결정화', 'entry' => '시험 전에 한 페이지로 정리하는 거 해봤어? 핵심만 압축해볼까!', 'icon' => '💎', 'name' => '결정화'],
    21 => ['objective' => '자동화 시스템', 'entry' => '매번 똑같이 하는 공부 루틴 있어? 자동화할 수 있는 거 찾아볼까', 'icon' => '⚙️', 'name' => '자동 시스템']
];

// ============================================================
// 상황별 에이전트 매칭 매트릭스
// ============================================================
$situation_agent_matrix = [
    // 고민 기반 1차 매칭
    'worry' => [
        'exam' => [7, 20, 4, 17],      // 시험임박 → 오늘미션, 결정화, 별자리, 학습회로
        'no_motivation' => [5, 8, 13, 1], // 의욕없음 → 에너지충전, 정체성, 30일원정, 미래통신
        'vague_goal' => [1, 2, 4, 9],   // 목표막연 → 미래통신, 항로설계, 별자리, 딥스캔
        'study_method' => [17, 10, 20, 16], // 공부법 → 학습회로, 자원수집, 결정화, 지식농장
        'confidence' => [6, 3, 8, 14],  // 자신감 → 스캐너, 비행기록, 정체성, 특수장비
        'none' => [15, 7, 5, 3]         // 없음 → 관제탑, 오늘미션, 에너지충전, 비행기록
    ],
    // 목표 기반 2차 매칭
    'goal' => [
        'plan' => [2, 7, 11],           // 공부계획 → 항로설계, 오늘미션, 자동드론
        'motivation' => [5, 1, 13],      // 동기부여 → 에너지충전, 미래통신, 30일원정
        'grade' => [4, 17, 20],          // 성적향상 → 별자리, 학습회로, 결정화
        'career' => [1, 9, 8],           // 진로고민 → 미래통신, 딥스캔, 정체성
        'other' => [15, 6, 19]           // 기타 → 관제탑, 스캐너, 성운지도
    ],
    // 에너지 기반 톤 조정
    'energy' => [
        'tired' => ['tone' => 'gentle', 'depth' => 'light'],
        'normal' => ['tone' => 'balanced', 'depth' => 'medium'],
        'good' => ['tone' => 'encouraging', 'depth' => 'medium'],
        'great' => ['tone' => 'challenging', 'depth' => 'deep']
    ],
    // 시간 기반 깊이 조정
    'time' => [
        '30min' => ['max_turns' => 3, 'focus' => 'quick_win'],
        '1hour' => ['max_turns' => 5, 'focus' => 'balanced'],
        '2hour_plus' => ['max_turns' => 8, 'focus' => 'deep_dive'],
        'unknown' => ['max_turns' => 4, 'focus' => 'adaptive']
    ]
];

// ============================================================
// 에이전트 간 연결 매트릭스 (목적 달성 후 다음 추천)
// ============================================================
$agent_flow_matrix = [
    1  => [2, 4, 8],    // 미래통신 → 항로설계, 별자리, 정체성
    2  => [7, 4, 11],   // 항로설계 → 오늘미션, 별자리, 자동드론
    3  => [4, 6, 15],   // 비행기록 → 별자리, 스캐너, 관제탑
    4  => [7, 2, 17],   // 별자리 → 오늘미션, 항로설계, 학습회로
    5  => [7, 13, 8],   // 에너지충전 → 오늘미션, 30일원정, 정체성
    6  => [14, 8, 9],   // 스캐너 → 특수장비, 정체성, 딥스캔
    7  => [11, 20, 15], // 오늘미션 → 자동드론, 결정화, 관제탑
    8  => [12, 1, 14],  // 정체성 → 신호발사, 미래통신, 특수장비
    9  => [1, 8, 19],   // 딥스캔 → 미래통신, 정체성, 성운지도
    10 => [16, 19, 18], // 자원수집 → 지식농장, 성운지도, 정보위성
    11 => [21, 7, 10],  // 자동드론 → 자동시스템, 오늘미션, 자원수집
    12 => [14, 8, 13],  // 신호발사 → 특수장비, 정체성, 30일원정
    13 => [3, 5, 15],   // 30일원정 → 비행기록, 에너지충전, 관제탑
    14 => [6, 12, 8],   // 특수장비 → 스캐너, 신호발사, 정체성
    15 => [3, 7, 1],    // 관제탑 → 비행기록, 오늘미션, 미래통신
    16 => [19, 20, 10], // 지식농장 → 성운지도, 결정화, 자원수집
    17 => [20, 10, 7],  // 학습회로 → 결정화, 자원수집, 오늘미션
    18 => [10, 16, 19], // 정보위성 → 자원수집, 지식농장, 성운지도
    19 => [20, 16, 9],  // 성운지도 → 결정화, 지식농장, 딥스캔
    20 => [7, 17, 4],   // 결정화 → 오늘미션, 학습회로, 별자리
    21 => [11, 7, 10]   // 자동시스템 → 자동드론, 오늘미션, 자원수집
];

// ============================================================
// API 라우팅
// ============================================================
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_quicktest':
        getQuickTest();
        break;
    case 'submit_quicktest':
        $answers = json_decode($_POST['answers'] ?? '{}', true);
        submitQuickTest($answers);
        break;
    case 'get_agent_entry':
        $agent_num = intval($_POST['agent_num'] ?? $_GET['agent_num'] ?? 0);
        getAgentEntry($agent_num);
        break;
    case 'send_message':
        $agent_num = intval($_POST['agent_num'] ?? 0);
        $message = $_POST['message'] ?? '';
        $history = json_decode($_POST['history'] ?? '[]', true);
        $session_data = json_decode($_POST['session_data'] ?? '{}', true);
        sendMentorMessage($agent_num, $message, $history, $session_data);
        break;
    case 'get_next_agent':
        $current_agent = intval($_POST['current_agent'] ?? 0);
        $session_data = json_decode($_POST['session_data'] ?? '{}', true);
        getNextAgent($current_agent, $session_data);
        break;
    case 'get_session_state':
        getSessionState();
        break;
    default:
        echo json_encode(['success' => false, 'error' => "Invalid action: $action - mentor_api.php"]);
}

// ============================================================
// 퀵테스트 질문 반환
// ============================================================
function getQuickTest() {
    $questions = [
        [
            'id' => 'energy',
            'question' => '오늘 컨디션 어때? 🔋',
            'type' => 'single',
            'options' => [
                ['value' => 'tired', 'label' => '😴 좀 피곤해...', 'emoji' => '😴'],
                ['value' => 'normal', 'label' => '😐 그냥 보통이야', 'emoji' => '😐'],
                ['value' => 'good', 'label' => '😊 괜찮아!', 'emoji' => '😊'],
                ['value' => 'great', 'label' => '🔥 오늘 최고야!', 'emoji' => '🔥']
            ]
        ],
        [
            'id' => 'goal',
            'question' => '오늘 뭘 하고 싶어? 🎯',
            'type' => 'single',
            'options' => [
                ['value' => 'plan', 'label' => '📅 공부 계획 세우기', 'emoji' => '📅'],
                ['value' => 'motivation', 'label' => '⚡ 의욕 충전하기', 'emoji' => '⚡'],
                ['value' => 'grade', 'label' => '📈 성적 올리는 법', 'emoji' => '📈'],
                ['value' => 'career', 'label' => '🧭 진로 생각하기', 'emoji' => '🧭'],
                ['value' => 'other', 'label' => '💭 그냥 얘기하고 싶어', 'emoji' => '💭']
            ]
        ],
        [
            'id' => 'time',
            'question' => '오늘 공부할 시간 있어? ⏰',
            'type' => 'single',
            'options' => [
                ['value' => '30min', 'label' => '⏱️ 30분 정도', 'emoji' => '⏱️'],
                ['value' => '1hour', 'label' => '🕐 1시간 정도', 'emoji' => '🕐'],
                ['value' => '2hour_plus', 'label' => '📚 2시간 이상!', 'emoji' => '📚'],
                ['value' => 'unknown', 'label' => '🤷 아직 모르겠어', 'emoji' => '🤷']
            ]
        ],
        [
            'id' => 'worry',
            'question' => '요즘 제일 고민인 거 있어? 💭',
            'type' => 'single',
            'options' => [
                ['value' => 'exam', 'label' => '📝 시험이 다가와...', 'emoji' => '📝'],
                ['value' => 'no_motivation', 'label' => '😔 의욕이 없어', 'emoji' => '😔'],
                ['value' => 'vague_goal', 'label' => '🌫️ 목표가 막연해', 'emoji' => '🌫️'],
                ['value' => 'study_method', 'label' => '❓ 공부 방법을 모르겠어', 'emoji' => '❓'],
                ['value' => 'confidence', 'label' => '😰 자신감이 없어', 'emoji' => '😰'],
                ['value' => 'none', 'label' => '😄 딱히 없어!', 'emoji' => '😄']
            ]
        ]
    ];

    echo json_encode([
        'success' => true,
        'questions' => $questions,
        'intro_message' => "안녕! 👋 오늘 너에게 딱 맞는 멘토를 찾아줄게.\n4가지만 빠르게 물어볼게!"
    ]);
}

// ============================================================
// 퀵테스트 제출 및 에이전트 매칭
// ============================================================
function submitQuickTest($answers) {
    global $situation_agent_matrix, $agent_objectives;

    $energy = $answers['energy'] ?? 'normal';
    $goal = $answers['goal'] ?? 'other';
    $time = $answers['time'] ?? 'unknown';
    $worry = $answers['worry'] ?? 'none';

    // 1차: 고민 기반 에이전트 후보 선정
    $worry_agents = $situation_agent_matrix['worry'][$worry] ?? $situation_agent_matrix['worry']['none'];

    // 2차: 목표 기반 필터링 (교집합 우선, 없으면 고민 기반 유지)
    $goal_agents = $situation_agent_matrix['goal'][$goal] ?? [];
    $matched_agents = array_intersect($worry_agents, $goal_agents);

    if (empty($matched_agents)) {
        $matched_agents = $worry_agents;
    }

    // 최종 에이전트 선정 (첫 번째 또는 랜덤)
    $selected_agent = reset($matched_agents);

    // 에너지/시간 기반 세션 설정
    $energy_settings = $situation_agent_matrix['energy'][$energy] ?? $situation_agent_matrix['energy']['normal'];
    $time_settings = $situation_agent_matrix['time'][$time] ?? $situation_agent_matrix['time']['unknown'];

    // 세션 데이터 구성
    $session_data = [
        'quicktest_answers' => $answers,
        'selected_agent' => $selected_agent,
        'tone' => $energy_settings['tone'],
        'depth' => $energy_settings['depth'],
        'max_turns' => $time_settings['max_turns'],
        'focus' => $time_settings['focus'],
        'turn_count' => 0,
        'visited_agents' => [$selected_agent],
        'started_at' => date('Y-m-d H:i:s')
    ];

    // 세션에 저장
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mentor_session'] = $session_data;

    $agent_info = $agent_objectives[$selected_agent];

    echo json_encode([
        'success' => true,
        'selected_agent' => $selected_agent,
        'agent_info' => [
            'number' => $selected_agent,
            'name' => $agent_info['name'],
            'icon' => $agent_info['icon'],
            'objective' => $agent_info['objective'],
            'entry_message' => $agent_info['entry']
        ],
        'session_data' => $session_data,
        'match_reason' => getMatchReason($worry, $goal, $selected_agent)
    ]);
}

// ============================================================
// 매칭 이유 생성
// ============================================================
function getMatchReason($worry, $goal, $agent_num) {
    global $agent_objectives;

    $reasons = [
        'exam' => '시험이 가까워서',
        'no_motivation' => '의욕 충전이 필요해서',
        'vague_goal' => '목표를 명확히 하려고',
        'study_method' => '공부법을 찾으려고',
        'confidence' => '자신감을 키우려고',
        'none' => '오늘 하루를 알차게 보내려고'
    ];

    $agent = $agent_objectives[$agent_num];
    $worry_reason = $reasons[$worry] ?? '';

    return "{$worry_reason} {$agent['icon']} {$agent['name']}를 추천해!";
}

// ============================================================
// 에이전트 진입점 반환
// ============================================================
function getAgentEntry($agent_num) {
    global $agent_objectives, $cards_data;

    if (!isset($agent_objectives[$agent_num])) {
        echo json_encode(['success' => false, 'error' => "Invalid agent number: $agent_num - mentor_api.php:getAgentEntry"]);
        return;
    }

    $agent_info = $agent_objectives[$agent_num];

    // cards_data에서 추가 정보 가져오기
    $card_data = null;
    foreach ($cards_data as $card) {
        if ($card['number'] === $agent_num) {
            $card_data = $card;
            break;
        }
    }

    // 초기 선택지 생성
    $initial_suggestions = generateInitialSuggestions($agent_num, $card_data);

    echo json_encode([
        'success' => true,
        'agent' => [
            'number' => $agent_num,
            'name' => $agent_info['name'],
            'icon' => $agent_info['icon'],
            'objective' => $agent_info['objective']
        ],
        'entry_message' => $agent_info['entry'],
        'suggestions' => $initial_suggestions
    ]);
}

// ============================================================
// 초기 선택지 생성
// ============================================================
function generateInitialSuggestions($agent_num, $card_data) {
    $suggestions = [];

    // 에이전트별 맞춤 초기 응답
    $agent_responses = [
        1  => ['응, 해볼래!', '글쎄... 잘 모르겠어', '재밌겠다!'],
        2  => ['목표 있어!', '아직 없어...', '같이 찾아봐!'],
        3  => ['성장한 것 같아', '잘 모르겠어', '확인해보고 싶어'],
        4  => ['숫자로 해볼게!', '어려울 것 같아', '예시 보여줘'],
        5  => ['50% 정도?', '거의 0%야...', '충전 방법 알려줘!'],
        6  => ['내 강점 알고 싶어!', '강점이 없는 것 같아', '같이 찾아줘'],
        7  => ['3가지 있어!', '하나도 모르겠어', '정해줘!'],
        8  => ['생각해볼게', '어렵다...', '힌트 줘!'],
        9  => ['진짜 이유 모르겠어', '생각해본 적 없어', '같이 찾아봐!'],
        10 => ['여기저기 저장해', '안 해...', '보물창고 만들자!'],
        11 => ['있어!', '딱히 없어', '예시 보여줘'],
        12 => ['소개하고 싶어!', '부끄러워...', '같이 만들어줘'],
        13 => ['도전해볼래!', '30일은 길어...', '뭐가 좋을까?'],
        14 => ['특기 있어!', '없는 것 같아', '같이 만들어보자'],
        15 => ['이번 주 괜찮았어', '힘들었어...', '리뷰해줘'],
        16 => ['오늘 배운 거 있어!', '기억 안 나', '연결해봐!'],
        17 => ['내 방법 있어', '그냥 해...', '효율적인 방법 알려줘'],
        18 => ['여러 곳에서!', '잘 모르겠어', '자동으로 해줘!'],
        19 => ['연결해보고 싶어!', '어렵다...', '예시 보여줘'],
        20 => ['해봤어!', '안 해봤어', '같이 해보자!'],
        21 => ['루틴 있어!', '매번 달라', '자동화하고 싶어']
    ];

    $suggestions = $agent_responses[$agent_num] ?? ['응!', '글쎄...', '더 알려줘'];

    return $suggestions;
}

// ============================================================
// 멘토 메시지 전송 및 AI 응답
// ============================================================
function sendMentorMessage($agent_num, $user_message, $history, $session_data) {
    global $agent_objectives, $cards_data;

    if (!isset($agent_objectives[$agent_num])) {
        echo json_encode(['success' => false, 'error' => "Invalid agent: $agent_num - mentor_api.php:sendMentorMessage"]);
        return;
    }

    $agent_info = $agent_objectives[$agent_num];

    // cards_data에서 상세 정보 가져오기
    $card_data = null;
    foreach ($cards_data as $card) {
        if ($card['number'] === $agent_num) {
            $card_data = $card;
            break;
        }
    }

    // 시스템 프롬프트 구성
    $system_prompt = buildMentorSystemPrompt($agent_info, $card_data, $session_data);

    // 메시지 구성
    $messages = [['role' => 'system', 'content' => $system_prompt]];

    foreach ($history as $msg) {
        $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
    }
    $messages[] = ['role' => 'user', 'content' => $user_message];

    // OpenAI API 호출
    $response = callMentorOpenAI($messages);

    if (!$response['success']) {
        echo json_encode(['success' => false, 'error' => $response['error']]);
        return;
    }

    // 턴 카운트 증가
    $turn_count = ($session_data['turn_count'] ?? 0) + 1;
    $max_turns = $session_data['max_turns'] ?? 5;

    // 목적 달성 여부 체크
    $objective_completed = checkObjectiveCompletion($agent_num, $history, $user_message, $response['content']);

    // 다음 선택지 생성
    $suggestions = generateContextSuggestions($agent_num, $user_message, $response['content'], $objective_completed);

    // 다음 에이전트 전환 여부
    $should_transition = $objective_completed || $turn_count >= $max_turns;
    $next_agent_info = null;

    if ($should_transition) {
        $next_agent_info = getNextAgentRecommendation($agent_num, $session_data);
    }

    echo json_encode([
        'success' => true,
        'message' => $response['content'],
        'suggestions' => $suggestions,
        'turn_count' => $turn_count,
        'objective_completed' => $objective_completed,
        'should_transition' => $should_transition,
        'next_agent' => $next_agent_info,
        'session_update' => ['turn_count' => $turn_count]
    ]);
}

// ============================================================
// 멘토 시스템 프롬프트 생성
// ============================================================
function buildMentorSystemPrompt($agent_info, $card_data, $session_data) {
    $name = $agent_info['name'];
    $icon = $agent_info['icon'];
    $objective = $agent_info['objective'];

    $tone_guide = [
        'gentle' => '부드럽고 위로하는 톤으로, 학생이 편안하게 느끼도록',
        'balanced' => '친근하고 격려하는 톤으로, 적절한 동기부여와 함께',
        'encouraging' => '적극적으로 칭찬하고 격려하는 톤으로, 학생의 자신감을 높이도록',
        'challenging' => '도전적이고 자극적인 톤으로, 학생이 더 높은 목표를 향하도록'
    ];

    $depth_guide = [
        'light' => '간단하고 가벼운 질문으로, 부담 없이 대화할 수 있도록',
        'medium' => '적당한 깊이의 질문으로, 생각해볼 거리를 주되 너무 무겁지 않게',
        'deep' => '깊이 있는 질문으로, 학생이 진지하게 성찰할 수 있도록'
    ];

    $tone = $session_data['tone'] ?? 'balanced';
    $depth = $session_data['depth'] ?? 'medium';

    $description = $card_data['description'] ?? '';
    $projects_text = '';
    if (isset($card_data['projects'])) {
        foreach ($card_data['projects'] as $p) {
            $projects_text .= "\n- " . $p['title'];
        }
    }

    $quicktest = $session_data['quicktest_answers'] ?? [];
    $context = '';
    if (!empty($quicktest)) {
        $energy_labels = ['tired' => '피곤함', 'normal' => '보통', 'good' => '좋음', 'great' => '최고'];
        $worry_labels = ['exam' => '시험 임박', 'no_motivation' => '의욕 없음', 'vague_goal' => '목표 막연', 'study_method' => '공부법 고민', 'confidence' => '자신감 부족', 'none' => '특별한 고민 없음'];

        $context = "\n\n[학생 현재 상태]
- 컨디션: " . ($energy_labels[$quicktest['energy'] ?? 'normal'] ?? '보통') . "
- 고민: " . ($worry_labels[$quicktest['worry'] ?? 'none'] ?? '없음');
    }

    return <<<PROMPT
당신은 "{$icon} {$name}" AI 멘토입니다.

[미션]
{$objective}

[설명]
{$description}

[관련 프로젝트]{$projects_text}
{$context}

[대화 스타일]
- 톤: {$tone_guide[$tone]}
- 깊이: {$depth_guide[$depth]}

[핵심 규칙]
1. 한국어로 친근한 반말체로 대화 (예: "~해볼까?", "~어때?")
2. 답변은 2-3문장으로 간결하게
3. 매 답변 끝에 자연스러운 질문이나 제안 포함
4. 이모지 적절히 사용
5. 학생의 답변에 공감하고 긍정적 피드백 제공
6. 미션({$objective})을 자연스럽게 달성하도록 대화 유도
7. 학생이 스스로 생각하고 성찰하도록 질문형 대화

[금지사항]
- 긴 설명이나 강의식 답변
- 부정적이거나 비판적인 표현
- 학생을 평가하거나 판단하는 표현
PROMPT;
}

// ============================================================
// 목적 달성 여부 체크
// ============================================================
function checkObjectiveCompletion($agent_num, $history, $user_message, $ai_response) {
    // 간단한 휴리스틱: 대화 턴이 3회 이상이고 긍정적 답변이 있으면 달성으로 간주
    $turn_count = count($history) / 2 + 1;

    if ($turn_count < 2) {
        return false;
    }

    // 긍정적 키워드 체크
    $positive_keywords = ['알겠어', '해볼게', '좋아', '그래', '응', '해봤어', '했어', '정했어', '찾았어', '알 것 같아'];
    $completion_keywords = ['완료', '끝', '다음', '다른 것', '충분해'];

    foreach ($positive_keywords as $keyword) {
        if (mb_strpos($user_message, $keyword) !== false) {
            return $turn_count >= 3;
        }
    }

    foreach ($completion_keywords as $keyword) {
        if (mb_strpos($user_message, $keyword) !== false) {
            return true;
        }
    }

    return $turn_count >= 5;
}

// ============================================================
// 컨텍스트 기반 선택지 생성
// ============================================================
function generateContextSuggestions($agent_num, $user_message, $ai_response, $objective_completed) {
    if ($objective_completed) {
        return [
            '👍 좋았어! 다음 멘토 만나볼래?',
            '🔄 조금 더 얘기하고 싶어',
            '✨ 오늘은 여기까지 할게'
        ];
    }

    // 에이전트별 맞춤 응답 선택지
    $context_suggestions = [
        ['응, 맞아!', '글쎄, 잘 모르겠어', '더 설명해줘'],
        ['해볼게!', '어려울 것 같아...', '다른 방법은?'],
        ['좋은 생각이야!', '생각해볼게', '예시 보여줘'],
        ['그렇게 할게', '다른 거 해보고 싶어', '왜 그래야 해?']
    ];

    // AI 응답에 질문이 있으면 긍정/부정/중립 선택지
    if (mb_strpos($ai_response, '?') !== false) {
        return $context_suggestions[0];
    }

    // 제안이 있으면 수락/거절/대안 선택지
    if (mb_strpos($ai_response, '해볼') !== false || mb_strpos($ai_response, '어때') !== false) {
        return $context_suggestions[1];
    }

    return $context_suggestions[array_rand($context_suggestions)];
}

// ============================================================
// 다음 에이전트 추천
// ============================================================
function getNextAgentRecommendation($current_agent, $session_data) {
    global $agent_flow_matrix, $agent_objectives;

    $visited = $session_data['visited_agents'] ?? [];
    $next_candidates = $agent_flow_matrix[$current_agent] ?? [15, 7, 5]; // 기본: 관제탑, 오늘미션, 에너지충전

    // 이미 방문한 에이전트 제외
    $available = array_diff($next_candidates, $visited);

    if (empty($available)) {
        // 모든 추천을 방문했으면 전체에서 미방문 선택
        $all_agents = range(1, 21);
        $available = array_diff($all_agents, $visited);

        if (empty($available)) {
            $available = $next_candidates; // 모두 방문했으면 다시 시작
        }
    }

    $next_agent = reset($available);
    $agent_info = $agent_objectives[$next_agent];

    return [
        'number' => $next_agent,
        'name' => $agent_info['name'],
        'icon' => $agent_info['icon'],
        'objective' => $agent_info['objective'],
        'entry' => $agent_info['entry'],
        'transition_message' => "좋아! 이제 {$agent_info['icon']} {$agent_info['name']}를 만나볼까?"
    ];
}

// ============================================================
// 다음 에이전트로 전환
// ============================================================
function getNextAgent($current_agent, $session_data) {
    $next_info = getNextAgentRecommendation($current_agent, $session_data);

    // 세션 업데이트
    $visited = $session_data['visited_agents'] ?? [];
    $visited[] = $next_info['number'];

    $updated_session = array_merge($session_data, [
        'selected_agent' => $next_info['number'],
        'visited_agents' => $visited,
        'turn_count' => 0
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mentor_session'] = $updated_session;

    echo json_encode([
        'success' => true,
        'next_agent' => $next_info,
        'session_data' => $updated_session,
        'suggestions' => generateInitialSuggestions($next_info['number'], null)
    ]);
}

// ============================================================
// 세션 상태 반환
// ============================================================
function getSessionState() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $session = $_SESSION['mentor_session'] ?? null;

    if ($session) {
        echo json_encode([
            'success' => true,
            'has_session' => true,
            'session_data' => $session
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_session' => false
        ]);
    }
}

// ============================================================
// OpenAI API 호출
// ============================================================
function callMentorOpenAI($messages) {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : 'your-api-key-here';
    $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';

    if ($apiKey === 'your-api-key-here' || $apiKey === 'your-openai-api-key-here') {
        // 데모 모드
        return ['success' => true, 'content' => getDemoMentorResponse($messages)];
    }

    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 300,
        'temperature' => 0.8
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => "API 연결 오류: $error - mentor_api.php:callMentorOpenAI"];
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMsg = $result['error']['message'] ?? 'Unknown error';
        return ['success' => false, 'error' => "API 오류 ($httpCode): $errorMsg - mentor_api.php:callMentorOpenAI"];
    }

    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }

    return ['success' => false, 'error' => 'API 응답 파싱 오류 - mentor_api.php:callMentorOpenAI'];
}

// ============================================================
// 데모 응답 (API 키 없을 때)
// ============================================================
function getDemoMentorResponse($messages) {
    $lastUserMessage = '';
    foreach (array_reverse($messages) as $msg) {
        if ($msg['role'] === 'user') {
            $lastUserMessage = $msg['content'];
            break;
        }
    }

    $responses = [
        "오! 좋은 생각이야! 🌟 그럼 조금 더 구체적으로 얘기해볼까?",
        "그렇구나~ 😊 그래서 지금 제일 하고 싶은 건 뭐야?",
        "완전 공감해! 💪 그럼 오늘 딱 하나만 해본다면 뭘 할래?",
        "재밌겠다! ✨ 그거 같이 해보자! 뭐부터 시작할까?",
        "아~ 그런 거구나! 🎯 그럼 이렇게 해보는 건 어때?"
    ];

    return $responses[array_rand($responses)];
}

/*
============================================================
DB 관련 테이블 (필요시)
============================================================
테이블: mdl_mentor_sessions
- id (INT, PK, AUTO_INCREMENT)
- userid (INT, FK -> mdl_user.id)
- session_data (JSON)
- current_agent (INT)
- visited_agents (JSON)
- created_at (DATETIME)
- updated_at (DATETIME)

테이블: mdl_mentor_conversations
- id (INT, PK, AUTO_INCREMENT)
- session_id (INT, FK -> mdl_mentor_sessions.id)
- agent_num (INT)
- role (ENUM: 'user', 'assistant')
- content (TEXT)
- created_at (DATETIME)
============================================================
*/
