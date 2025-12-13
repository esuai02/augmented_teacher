<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
require_once(__DIR__ . '/config.php');
global $DB, $USER;
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $solution = $input['solution'] ?? '';
    
    if (empty($solution)) {
        throw new Exception('해설 내용이 없습니다.');
    }

    // OpenAI API를 사용하여 나레이션 스크립트 생성
    $messages = [
        [
            'role' => 'system',
            'content' => '당신은 친근한 수학 선생님입니다. 주어진 수학 문제 해설을 학생에게 음성으로 설명하는 나레이션 스크립트를 작성해주세요.

다음 지침을 따라주세요:
1. 학생에게 직접 말하는 것처럼 친근하고 격려하는 톤 사용
2. 수식은 말로 풀어서 설명 (예: "x 제곱"은 "엑스의 제곱", "분수"는 구체적으로 설명)
3. 어려운 개념은 쉬운 예시로 설명
4. 중간중간 이해를 확인하는 문구 추가
5. 마지막에 격려의 말 추가
6. 자연스러운 구어체 사용'
        ],
        [
            'role' => 'user',
            'content' => "다음 해설을 학생에게 음성으로 설명하는 나레이션 스크립트로 변환해주세요:\n\n" . $solution
        ]
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => 1500,
        'temperature' => 0.8
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
        throw new Exception('OpenAI API 호출 실패: ' . $errorMessage);
    }

    $responseData = json_decode($response, true);
    $narration = $responseData['choices'][0]['message']['content'] ?? '';

    echo json_encode([
        'success' => true,
        'narration' => $narration
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>