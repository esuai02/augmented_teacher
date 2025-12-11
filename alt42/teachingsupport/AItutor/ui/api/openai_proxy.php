<?php
/**
 * OpenAI API 프록시
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=UTF-8');
}

define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'your-api-key-here');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'generateSuggestion') {
    $result = generateSuggestion();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

function generateSuggestion() {
    $requestType = $_POST['requestType'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $userInput = $_POST['userInput'] ?? null;
    
    if (!$requestType || !$contentId) {
        return ['success' => false, 'error' => 'requestType and contentId are required'];
    }
    
    $prompt = buildPrompt($requestType, $userInput);
    $response = callOpenAI($prompt);
    $suggestion = parseOpenAIResponse($response, $requestType);
    
    return [
        'success' => true,
        'suggestion' => $suggestion,
        'rawResponse' => $response,
        'tokensUsed' => $response['usage']['total_tokens'] ?? 0
    ];
}

function buildPrompt($requestType, $userInput = null) {
    $systemPrompt = "당신은 수학 교육 전문가입니다. 인지맵에 추가할 새로운 노드와 엣지를 JSON 형식으로 제안해주세요.

응답 형식:
{
    \"title\": \"제안 제목\",
    \"description\": \"제안 설명\",
    \"type\": \"new_path 또는 misconception_path\",
    \"confidence\": 0.0~1.0,
    \"nodes\": [{\"node_id\": \"고유ID\", \"label\": \"라벨\", \"type\": \"correct/wrong/partial\", \"stage\": 숫자, \"x\": 숫자, \"y\": 숫자, \"description\": \"설명\", \"reasoning\": \"이유\"}],
    \"edges\": [{\"source\": \"소스노드ID\", \"target\": \"타겟노드ID\", \"reasoning\": \"이유\"}]
}";

    $userPrompt = "문제: y=x²-ax 이차함수와 x축의 두 교점 A, B와 꼭짓점 C가 정삼각형을 이루는 a 값 구하기 (정답: a=2√3)\n\n";
    
    switch ($requestType) {
        case 'new_solution':
            $userPrompt .= "기존 풀이와 다른 새로운 정답 경로를 제안해주세요.";
            break;
        case 'misconception':
            $userPrompt .= "학생들이 자주 하는 오개념/실수 경로를 제안해주세요.";
            break;
        case 'custom_input':
            $userPrompt .= "사용자 입력 풀이: $userInput\n\n이 풀이를 분석하여 노드/엣지를 제안해주세요.";
            break;
    }
    
    return ['system' => $systemPrompt, 'user' => $userPrompt];
}

function callOpenAI($prompt) {
    $apiKey = OPENAI_API_KEY;
    
    if ($apiKey === 'your-api-key-here' || empty($apiKey)) {
        return getMockResponse();
    }
    
    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $prompt['system']],
            ['role' => 'user', 'content' => $prompt['user']]
        ],
        'temperature' => 0.7,
        'max_tokens' => 4000,
        'response_format' => ['type' => 'json_object']
    ];
    
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("OpenAI API 오류 ($httpCode)");
    }
    
    return json_decode($response, true);
}

function getMockResponse() {
    $mockSuggestion = [
        'title' => '대입법을 이용한 새로운 풀이',
        'description' => '꼭짓점 좌표를 직접 대입하여 정삼각형 조건을 확인하는 방법',
        'type' => 'new_path',
        'confidence' => 0.85,
        'nodes' => [
            ['node_id' => 's3_substitute', 'label' => '직접 대입', 'type' => 'correct', 'stage' => 3, 'x' => 620, 'y' => 460, 'description' => 'C 좌표를 직접 대입', 'reasoning' => '직접 값을 대입하여 계산'],
            ['node_id' => 's5_direct_calc', 'label' => '직접 거리 계산', 'type' => 'correct', 'stage' => 5, 'x' => 480, 'y' => 760, 'description' => '세 점 사이의 거리를 직접 계산', 'reasoning' => '좌표 이용 거리 공식']
        ],
        'edges' => [
            ['source' => 's2_formula', 'target' => 's3_substitute', 'reasoning' => '근의 공식 후 직접 대입'],
            ['source' => 's3_substitute', 'target' => 's4_sides', 'reasoning' => '좌표 획득 후 세 변 비교'],
            ['source' => 's4_sides', 'target' => 's5_direct_calc', 'reasoning' => '세 변 조건에서 거리 계산'],
            ['source' => 's5_direct_calc', 'target' => 's6_eq_sides', 'reasoning' => '거리로 방정식 설정']
        ]
    ];
    
    return [
        'choices' => [['message' => ['content' => json_encode($mockSuggestion, JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['total_tokens' => 1500]
    ];
}

function parseOpenAIResponse($response, $requestType) {
    $content = $response['choices'][0]['message']['content'] ?? '';
    $suggestion = json_decode($content, true);
    
    if (!$suggestion) {
        throw new Exception("제안 JSON 파싱 실패");
    }
    
    if (empty($suggestion['type'])) {
        $suggestion['type'] = $requestType === 'misconception' ? 'misconception_path' : 'new_path';
    }
    
    return $suggestion;
}

