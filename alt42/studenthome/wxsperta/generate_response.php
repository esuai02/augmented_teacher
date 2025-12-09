<?php
require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");

global $DB, $USER;
require_login();

header('Content-Type: application/json');

// POST 데이터 읽기
$input = json_decode(file_get_contents('php://input'), true);

$system_prompt = $input['system_prompt'] ?? '';
$user_input = $input['user_input'] ?? '';
$agent_name = $input['agent_name'] ?? '';

if (empty($user_input)) {
    echo json_encode(['success' => false, 'error' => 'No input provided']);
    exit;
}

try {
    // OpenAI API 호출
    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_input]
    ];
    
    $response = call_openai_api($messages, 0.8);
    
    if ($response === false) {
        // 폴백 응답
        $fallback_responses = [
            '시간 수정체' => [
                '좋은 생각이네요! 미래의 당신은 이 순간을 어떻게 기억할까요?',
                '그 목표를 달성한 미래의 모습을 구체적으로 상상해보세요.',
                '5년 후의 당신에게 지금 이 선택이 어떤 의미일까요?'
            ],
            '타임라인 합성기' => [
                '이 목표를 작은 단계로 나눠볼까요? 첫 번째 단계는 무엇일까요?',
                '현실적인 타임라인을 만들어보죠. 언제까지 달성하고 싶나요?',
                '중간 점검 포인트를 설정해보면 어떨까요?'
            ],
            '성장 엘리베이터' => [
                '오늘의 성장을 측정해보죠. 어제와 비교해서 무엇이 달라졌나요?',
                '작은 진전도 성장입니다. 오늘의 작은 승리는 무엇이었나요?',
                '성장 곡선을 가속화할 수 있는 한 가지 행동은 무엇일까요?'
            ]
        ];
        
        $agent_responses = $fallback_responses[$agent_name] ?? ['네, 이해했습니다. 더 자세히 말씀해 주세요.'];
        $response = $agent_responses[array_rand($agent_responses)];
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);
    
    wxsperta_log("AI response generated for agent: $agent_name", 'INFO');
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'response' => '죄송합니다. 일시적인 오류가 발생했습니다. 다시 시도해 주세요.'
    ]);
}
?>