<?php
// Simple fallback chatbot API that works without OpenAI
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Set JSON response header
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$student_id = isset($input['student_id']) ? intval($input['student_id']) : $USER->id;
$message = isset($input['message']) ? trim($input['message']) : '';
$learning_mode = isset($input['learning_mode']) ? $input['learning_mode'] : 'curriculum';

// Get the actual selected mode from persona_modes table
try {
    $persona_mode = $DB->get_record_sql(
        "SELECT * FROM {persona_modes} WHERE student_id = :studentid ORDER BY timecreated DESC LIMIT 1",
        array('studentid' => $student_id)
    );
    
    if ($persona_mode && !empty($persona_mode->student_mode)) {
        $learning_mode = $persona_mode->student_mode;
    }
} catch (Exception $e) {
    // Continue with default mode
}

// Mode-specific responses based on worldview
$mode_responses = [
    'curriculum' => [
        'greeting' => "📚 안녕하세요! 체계적 진도형 학습 도우미입니다. '진도는 전략, 보정은 일상'이라는 마음으로 도와드리겠습니다.",
        'help' => "주간 진도 체크, 단원 마스터리 80% 달성, 7:3 복습 비율 유지를 도와드립니다. 무엇을 도와드릴까요?",
        'study' => "오늘의 학습 목표를 확인해봐요! 주간 진도표에 따라 체계적으로 진행하고, 매일 정시 학습을 유지하는 것이 중요합니다.",
        'default' => "체계적인 진도 관리가 학습의 핵심입니다. 오늘 목표한 단원을 함께 정복해봐요!"
    ],
    'exam' => [
        'greeting' => "✏️ 시험 대비 모드입니다! '시험은 전투, 출제자는 상대'라는 전략으로 접근합니다.",
        'help' => "기출 3회독, 오답노트 2회독, 일일 50문항 처리를 목표로 합니다. D-day까지 함께 달려요!",
        'study' => "오늘 50문항 목표 달성했나요? 시간압박 훈련과 함께 출제빈도 높은 문제부터 공략해봐요.",
        'default' => "시험은 전략입니다! 기출문제 분석과 시간 관리가 고득점의 열쇠예요."
    ],
    'custom' => [
        'greeting' => "🎯 맞춤학습 도우미입니다! '모든 학생은 고유한 학습 DNA를 가진다'는 믿음으로 도와드립니다.",
        'help' => "당신의 강점을 극대화하고 약점을 보완하는 맞춤형 학습 경로를 설계해드립니다.",
        'study' => "자신의 속도로, 자신만의 방법으로! MBTI 학습법과 개인 속도에 맞춰 진행해봐요.",
        'default' => "당신만의 학습 스타일을 찾아가는 여정, 함께 하겠습니다!"
    ],
    'mission' => [
        'greeting' => "⚡ 미션 달성형 도우미입니다! '작은 승리가 큰 성공을 만든다'는 원칙으로 동기부여하겠습니다.",
        'help' => "일일 5미션, 주간 보스전, 월간 레벨업! 게임처럼 재미있게 학습해요.",
        'study' => "오늘의 5개 미션 확인! 연속 달성 기록을 이어가며 포인트를 모아봐요.",
        'default' => "미션 클리어! 작은 성취가 모여 큰 성과가 됩니다. 다음 도전은?"
    ],
    'reflection' => [
        'greeting' => "🧠 성찰 중심 도우미입니다. '이해 없는 정답은 무의미하다'는 철학으로 깊이 있는 학습을 돕습니다.",
        'help' => "백지복습법, 개념맵 작성, '왜?'라는 질문을 통해 진짜 실력을 키워요.",
        'study' => "오늘 배운 내용을 설명할 수 있나요? 학습일지에 깨달음을 기록하고 메타인지를 발달시켜봐요.",
        'default' => "'왜?'라고 질문하세요. 근본적인 이해가 진정한 실력입니다."
    ],
    'selfled' => [
        'greeting' => "🚀 자율학습 도우미입니다. '스스로 설계한 길이 가장 빠른 길'이라는 신념으로 지원합니다.",
        'help' => "최소한의 가이드, 최대한의 자율성! 필요할 때만 도움을 요청하세요.",
        'study' => "이번 주 목표는 무엇인가요? 자율적으로 계획하고 실행하는 모습이 멋집니다!",
        'default' => "스스로의 페이스로, 스스로의 방향으로! 자기주도 학습을 응원합니다."
    ],
    'cognitive' => [
        'greeting' => "🔍 인지적 도제 도우미입니다. '마스터의 사고를 모방하며 성장한다'는 방식으로 안내합니다.",
        'help' => "전문가의 사고 과정을 시연하고, 단계별로 따라하며 독립적 문제해결 능력을 키워요.",
        'study' => "Think-aloud! 문제 해결 과정을 말로 표현하며 사고력을 기르세요.",
        'default' => "관찰 → 모방 → 연습 → 독립! 마스터의 길을 함께 걸어가요."
    ],
    'timecentered' => [
        'greeting' => "🕒 시간 관리 도우미입니다. '시간은 학습의 생명선'이라는 원칙으로 효율을 극대화합니다.",
        'help' => "25분 집중/5분 휴식, 1-3-7-14일 반복주기로 시간당 18문항 처리를 목표로!",
        'study' => "포모도로 타이머 준비! 시간 블록을 설계하고 밀도 높은 학습을 시작해봐요.",
        'default' => "시간 관리가 곧 성과 관리! 집중과 휴식의 리듬을 지켜요."
    ],
    'curiositycentered' => [
        'greeting' => "💡 호기심 중심 도우미입니다. '궁금증이 최고의 선생님'이라는 믿음으로 탐구를 돕습니다.",
        'help' => "'왜? 어떻게? 만약?' 질문법으로 호기심을 탐구 프로젝트로 발전시켜요.",
        'study' => "오늘의 궁금증은 무엇인가요? 가설을 세우고 실험하며 발견의 기쁨을 느껴봐요!",
        'default' => "질문하세요! 호기심이 깊은 학습으로 이어집니다."
    ]
];

// Get appropriate response based on message content
function getResponse($message, $mode, $responses) {
    $message_lower = mb_strtolower($message);
    
    if (strpos($message_lower, '안녕') !== false || strpos($message_lower, 'hello') !== false) {
        return $responses['greeting'];
    }
    if (strpos($message_lower, '도움') !== false || strpos($message_lower, '도와') !== false) {
        return $responses['help'];
    }
    if (strpos($message_lower, '공부') !== false || strpos($message_lower, '학습') !== false) {
        return $responses['study'];
    }
    
    // Check for specific keywords per mode
    if ($mode === 'exam' && (strpos($message_lower, '시험') !== false || strpos($message_lower, '문제') !== false)) {
        return "시험 준비 팁: 기출문제를 분석하고, 자주 나오는 유형을 먼저 정복하세요. 시간 배분 연습도 중요합니다!";
    }
    if ($mode === 'mission' && strpos($message_lower, '미션') !== false) {
        return "오늘의 미션 리스트: 1) 개념 정리 10분, 2) 문제 5개 풀기, 3) 오답 분석, 4) 복습 노트 작성, 5) 내일 계획 세우기";
    }
    if ($mode === 'timecentered' && strpos($message_lower, '시간') !== false) {
        return "시간 관리 팁: 25분 타이머를 설정하고 집중! 5분 휴식 후 다시 시작. 이 리듬을 4번 반복하면 100분의 고밀도 학습!";
    }
    
    return $responses['default'];
}

// Handle the request
if ($action === 'send_message') {
    $responses = isset($mode_responses[$learning_mode]) ? $mode_responses[$learning_mode] : $mode_responses['curriculum'];
    $response_text = getResponse($message, $learning_mode, $responses);
    
    // Add some variation
    $endings = [
        " 화이팅! 💪",
        " 함께 해요! 🌟",
        " 할 수 있어요! ✨",
        " 응원합니다! 🎯",
        " 파이팅! 🚀"
    ];
    
    // Sometimes add an ending
    if (rand(1, 3) === 1) {
        $response_text .= $endings[array_rand($endings)];
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response_text,
        'mode' => $learning_mode
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Unknown action'
    ]);
}
?>