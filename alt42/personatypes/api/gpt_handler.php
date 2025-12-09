<?php
/**
 * GPT API 핸들러
 * 학생 응답을 분석하고 개인화된 피드백을 생성
 */

// Security flag for config file
define('SECURE_ACCESS', true);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    // Fallback configuration if config file doesn't exist
    define('OPENAI_API_KEY', 'YOUR_API_KEY_HERE');
    define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
    define('OPENAI_MODEL', 'gpt-4');
    define('ENABLE_GPT_API', false); // Disable if no config
    define('ENABLE_FALLBACK', true);
}

// 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nodeId = $input['nodeId'] ?? 0;
    $answer = $input['answer'] ?? '';
    $questionType = $input['questionType'] ?? 'reflection';
    $userId = $input['userId'] ?? 0;
    $detectedBiases = $input['detectedBiases'] ?? [];
    
    // GPT 프롬프트 생성
    $prompt = generatePrompt($nodeId, $answer, $questionType, $detectedBiases);
    
    // GPT API 호출
    $response = callGPTAPI($prompt);
    
    // 응답 파싱 및 처리
    $feedback = parseFeedback($response);
    
    // 데이터베이스 저장 (옵션)
    saveFeedback($userId, $nodeId, $answer, $feedback);
    
    // 결과 반환
    echo json_encode([
        'success' => true,
        'feedback' => $feedback,
        'nextActions' => generateNextActions($nodeId, $feedback),
        'biasAnalysis' => analyzeBiases($answer, $detectedBiases)
    ]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

/**
 * GPT 프롬프트 생성
 */
function generatePrompt($nodeId, $answer, $questionType, $detectedBiases) {
    $nodeNames = [
        0 => "수학 여정의 시작 (성찰적 사고)",
        1 => "계산과의 만남 (계산적 사고)",
        2 => "도형의 세계 (공간적 사고)",
        3 => "연산의 깊이 (연산적 사고)",
        4 => "문제 해결 전략 (전략적 사고)",
        5 => "패턴의 발견 (패턴 인식)",
        6 => "깨달음의 순간 (통찰적 사고)",
        7 => "미래 예측 (예측적 사고)",
        8 => "여정의 정점 (통합적 사고)"
    ];
    
    $nodeName = $nodeNames[$nodeId] ?? "수학 탐험";
    $biasInfo = !empty($detectedBiases) ? 
        "\n감지된 인지편향: " . implode(", ", $detectedBiases) : "";
    
    $systemPrompt = "당신은 따뜻하고 격려적인 수학 학습 멘토입니다. 
학생의 수학적 사고를 발전시키고 인지편향을 극복하도록 도와주세요.
응답은 다음 형식으로 제공해주세요:
1. 긍정적 피드백 (학생의 답변에서 좋은 점 찾기)
2. 개선 제안 (구체적이고 실행 가능한 조언)
3. 통찰 제공 (수학적 사고의 발전 방향)
4. 다음 도전 (다음 단계로 나아가기 위한 힌트)";

    $userPrompt = "노드: $nodeName
학생 답변: $answer
질문 유형: $questionType
$biasInfo

이 학생의 답변을 분석하고 개인화된 피드백을 제공해주세요.
학생의 수학적 사고 발전을 격려하고, 감지된 편향이 있다면 극복 방법을 안내해주세요.";

    return [
        'model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => defined('API_TEMPERATURE') ? API_TEMPERATURE : 0.7,
        'max_tokens' => defined('API_MAX_TOKENS') ? API_MAX_TOKENS : 500
    ];
}

/**
 * GPT API 호출
 */
function callGPTAPI($prompt) {
    // Check if GPT API is enabled
    if (!defined('ENABLE_GPT_API') || !ENABLE_GPT_API) {
        return generateFallbackResponse();
    }
    
    // Check if API key is configured
    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'YOUR_API_KEY_HERE') {
        error_log('GPT API Key not configured');
        return generateFallbackResponse();
    }
    
    $ch = curl_init(OPENAI_API_URL);
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($prompt));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('API_TIMEOUT') ? API_TIMEOUT : 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || $error) {
        error_log("GPT API Error: HTTP $httpCode, Error: $error");
        // 에러 처리 - 폴백 응답 사용
        return generateFallbackResponse();
    }
    
    return json_decode($response, true);
}

/**
 * 피드백 파싱
 */
function parseFeedback($response) {
    if (isset($response['choices'][0]['message']['content'])) {
        $content = $response['choices'][0]['message']['content'];
        
        // 섹션별로 분리
        $sections = explode("\n", $content);
        
        return [
            'positive' => extractSection($sections, '긍정적 피드백'),
            'improvement' => extractSection($sections, '개선 제안'),
            'insight' => extractSection($sections, '통찰 제공'),
            'nextChallenge' => extractSection($sections, '다음 도전'),
            'fullText' => $content
        ];
    }
    
    return generateFallbackFeedback();
}

/**
 * 섹션 추출
 */
function extractSection($sections, $keyword) {
    $result = [];
    $capturing = false;
    
    foreach ($sections as $line) {
        if (strpos($line, $keyword) !== false) {
            $capturing = true;
            continue;
        }
        
        if ($capturing) {
            if (preg_match('/^\d\./', $line)) {
                // 다음 섹션 시작
                break;
            }
            $result[] = $line;
        }
    }
    
    return implode(" ", array_filter($result));
}

/**
 * 폴백 응답 생성
 */
function generateFallbackResponse() {
    return [
        'choices' => [[
            'message' => [
                'content' => "훌륭한 답변입니다! 
1. 긍정적 피드백: 당신의 생각을 잘 표현했어요.
2. 개선 제안: 더 구체적인 예시를 들어보면 좋겠어요.
3. 통찰 제공: 이런 사고 과정은 문제 해결에 큰 도움이 됩니다.
4. 다음 도전: 다음에는 다른 관점에서도 생각해보세요."
            ]
        ]]
    ];
}

/**
 * 폴백 피드백
 */
function generateFallbackFeedback() {
    $encouragements = [
        "훌륭한 시도예요! 계속해서 탐구해보세요.",
        "좋은 관찰력을 보여주었어요!",
        "창의적인 접근이 돋보입니다!",
        "논리적인 사고 과정이 인상적이에요!",
        "수학적 직관이 발전하고 있어요!"
    ];
    
    return [
        'positive' => $encouragements[array_rand($encouragements)],
        'improvement' => "다양한 방법으로 접근해보면 더 깊은 이해를 얻을 수 있을 거예요.",
        'insight' => "이 문제를 통해 수학적 사고의 새로운 면을 발견했네요.",
        'nextChallenge' => "다음 노드에서 더 흥미로운 도전이 기다리고 있어요!",
        'fullText' => "AI 분석 중... 계속 진행해주세요!"
    ];
}

/**
 * 다음 액션 생성
 */
function generateNextActions($nodeId, $feedback) {
    $actions = [];
    
    // 노드별 추천 액션
    $nodeActions = [
        0 => ["자기 성찰 일지 작성", "학습 목표 설정"],
        1 => ["계산 연습 문제", "암산 게임"],
        2 => ["도형 그리기", "3D 시각화"],
        3 => ["연산 규칙 탐구", "계산기 없이 도전"],
        4 => ["다양한 해결법 시도", "전략 비교"],
        5 => ["패턴 찾기 게임", "수열 만들기"],
        6 => ["아하 모멘트 기록", "통찰 공유"],
        7 => ["예측과 검증", "가설 세우기"],
        8 => ["학습 여정 정리", "포트폴리오 작성"]
    ];
    
    $actions = $nodeActions[$nodeId] ?? ["다음 단계 준비", "복습하기"];
    
    // 피드백 기반 추가 액션
    if (strpos($feedback['improvement'], '구체적') !== false) {
        $actions[] = "구체적인 예시 찾기";
    }
    
    if (strpos($feedback['insight'], '패턴') !== false) {
        $actions[] = "패턴 노트 작성";
    }
    
    return $actions;
}

/**
 * 편향 분석
 */
function analyzeBiases($answer, $detectedBiases) {
    $analysis = [];
    
    foreach ($detectedBiases as $bias) {
        $analysis[$bias] = [
            'detected' => true,
            'severity' => rand(30, 70) / 100, // 실제로는 더 정교한 분석 필요
            'overcomeStrategy' => getBiasStrategy($bias)
        ];
    }
    
    return $analysis;
}

/**
 * 편향 극복 전략
 */
function getBiasStrategy($bias) {
    $strategies = [
        'ConfirmationBias' => '다른 관점에서 문제를 다시 살펴보세요',
        'AnchoringBias' => '첫 번째 접근법을 잠시 잊고 새롭게 시작해보세요',
        'OverconfidenceBias' => '답을 다시 한 번 검증해보세요',
        'AvailabilityHeuristic' => '더 다양한 예시를 고려해보세요'
    ];
    
    return $strategies[$bias] ?? '여러 각도에서 문제를 바라보세요';
}

/**
 * 피드백 저장
 */
function saveFeedback($userId, $nodeId, $answer, $feedback) {
    // 데이터베이스 연결 (실제 구현 필요)
    global $DB;
    
    if ($DB) {
        try {
            $record = new stdClass();
            $record->userid = $userId;
            $record->nodeid = $nodeId;
            $record->answer = $answer;
            $record->feedback = json_encode($feedback);
            $record->timecreated = time();
            
            // $DB->insert_record('shiningstars_feedback', $record);
        } catch (Exception $e) {
            error_log("Failed to save feedback: " . $e->getMessage());
        }
    }
}
?>