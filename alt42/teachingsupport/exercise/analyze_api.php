<?php
// 에러 보고 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS 헤더 설정
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// config.php 파일 포함
require_once '../../config.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 요청 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$textContent = $input['text'] ?? '';
$imageData = $input['image'] ?? '';
$studentLevel = $input['level'] ?? '중위권';

// 텍스트나 이미지 중 하나는 반드시 있어야 함
if (empty($textContent) && empty($imageData)) {
    http_response_code(400);
    echo json_encode(['error' => '텍스트나 이미지 중 하나는 반드시 입력해야 합니다']);
    exit;
}

// OpenAI API 호출 함수
function callOpenAIAPI($textContent, $imageData, $studentLevel) {
    $apiKey = OPENAI_API_KEY;
    // gpt-4o를 사용 (이미지와 텍스트 모두 처리 가능)
    $model = 'gpt-4o';
    
    // 시스템 메시지
    $messages = [
        [
            'role' => 'system',
            'content' => "당신은 MKT(Mathematical Knowledge for Teaching)와 PCK(Pedagogical Content Knowledge)를 갖춘 수학 교육 전문가입니다. 
최신 교육 연구에 기반하여 {$studentLevel} 학생을 위한 지도 전략을 제시합니다.

**핵심 원칙:**
• 오류 예측: 학생이 범할 가능성이 높은 구체적 오개념 예측
• 반응적 스캐폴딩: 컨틴전시(상태 맞춤 개입), 페이딩(점진적 감소), 책임 이양
• 인지적 긴장: 적절한 혼란 유발 후 반드시 해결 경로 제공
• 기다림 시간: 질문 후 3초, 응답 후 3초 명시
• 개념 갈등: 잘못된 선개념을 자극하여 올바른 이해로 전환

**전략 형식:**
1. 피드백 전략: [오류 유형]: \"[재구조화 질문]\" → 3초 기다림 → [추적 질문] → [기대 효과]
2. 개념 설명 전략: [핵심 접근]: [구체적 방법]. [시각적/언어적/기호적 표현 통합]
3. 인지적 도전 설계: [혼란 유발] → [호기심 전환] → [스캐폴딩] → [해결]

학생 수준별 특징:
• 상위권: 심화 개념과 일반화, 증명에 초점
• 중위권: 단계적 설명과 충분한 연습, 실수 방지
• 하위권: 기초 개념 강화, 격려, 작은 성공 경험"
        ]
    ];
    
    // 사용자 메시지 구성
    $userContent = [];
    
    if (!empty($textContent)) {
        $userContent[] = [
            'type' => 'text',
            'text' => "다음 수학 문제/개념에 대한 지도 전략을 제시해주세요: {$textContent}"
        ];
    }
    
    if (!empty($imageData)) {
        // base64 이미지 데이터 처리
        if (strpos($imageData, 'data:image') === 0) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageData
                ]
            ];
            
            if (empty($textContent)) {
                array_unshift($userContent, [
                    'type' => 'text',
                    'text' => '이미지에 있는 수학 문제/개념에 대한 지도 전략을 제시해주세요:'
                ]);
            }
        }
    }
    
    $messages[] = [
        'role' => 'user',
        'content' => $userContent
    ];
    
    // cURL 초기화
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    // 요청 데이터
    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 1000,
        'temperature' => 0.7
    ];
    
    // cURL 옵션 설정
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // API 호출
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception('OpenAI API Error: ' . ($errorData['error']['message'] ?? 'Unknown error'));
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response format');
    }
    
    return $result['choices'][0]['message']['content'];
}

try {
    // OpenAI API 호출
    $analysis = callOpenAIAPI($textContent, $imageData, $studentLevel);
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);
    
} catch (Exception $e) {
    // 에러 응답
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>