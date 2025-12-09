<?php
/**
 * 강화된 GPT API 핸들러
 * 추천 대화 제시 및 사고 자극 피드백 생성
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 설정
$API_KEY = 'YOUR_OPENAI_API_KEY'; // 실제 API 키로 교체 필요
$API_URL = 'https://api.openai.com/v1/chat/completions';

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$generateType = $input['generateType'] ?? 'feedback'; // text, feedback
$context = $input['context'] ?? '';
$nodeId = $input['nodeId'] ?? 0;
$answer = $input['answer'] ?? '';
$questionType = $input['questionType'] ?? 'reflection';
$detectedInertias = $input['detectedInertias'] ?? [];
$userId = $input['userId'] ?? 0;

// 노드별 인지관성 카드 매핑
$nodeToCardMapping = [
    0 => ['확증관성', '선택적주의'],
    1 => ['앵커링관성', '가용성휴리스틱'],
    2 => ['프레이밍효과', '대표성휴리스틱'],
    3 => ['과신관성', '계획오류'],
    4 => ['자기과소평가', '완벽주의'],
    5 => ['패턴인식관성', '과일반화'],
    6 => ['확실성효과', '통제착각'],
    7 => ['재앙화사고', '흑백사고'],
    8 => ['더닝크루거효과', '관성맹점']
];

// 피드백 타입 결정
function determineFeedbackType($answer, $detectedInertias) {
    $answerLength = mb_strlen($answer);
    $hasSpecificExample = preg_match('/예를 들어|예시|경험|했을 때/u', $answer);
    $hasReflection = preg_match('/생각해보니|깨달았|느꼈|알게 되었/u', $answer);
    
    if (count($detectedInertias) > 2) {
        return 'metacognitive'; // 인지관성이 많이 감지되면 메타인지 피드백
    } elseif ($hasSpecificExample && $hasReflection) {
        return 'encouraging'; // 구체적이고 성찰적이면 격려
    } elseif ($answerLength < 50) {
        return 'challenging'; // 짧은 답변이면 도전적 질문
    } else {
        return 'connecting'; // 일반적인 경우 연결 피드백
    }
}

// GPT 프롬프트 생성
function generatePrompt($nodeId, $answer, $questionType, $detectedInertias, $feedbackType) {
    global $nodeToCardMapping;
    
    $relatedInertias = $nodeToCardMapping[$nodeId] ?? [];
    $inertiaContext = !empty($detectedInertias) ? 
        "학생의 답변에서 다음 인지관성이 감지되었습니다: " . implode(', ', $detectedInertias) : 
        "이 단계에서 주의할 인지관성: " . implode(', ', $relatedInertias);
    
    $basePrompt = "당신은 따뜻하고 지혜로운 수학 교육 멘토입니다. 
학생의 답변을 분석하고 성장을 도울 피드백을 제공하세요.

학생의 답변: \"$answer\"
질문 유형: $questionType
$inertiaContext

피드백 스타일: $feedbackType
";

    $specificInstructions = [
        'encouraging' => "
학생의 강점을 구체적으로 칭찬하고, 다음 단계로 나아갈 방향을 제시하세요.
긍정적 강화를 통해 자신감을 높여주세요.",
        
        'challenging' => "
학생의 사고를 확장시킬 수 있는 도전적이지만 흥미로운 질문 3개를 제시하세요.
다른 관점에서 생각해볼 기회를 제공하세요.",
        
        'connecting' => "
이 개념이 다른 수학 영역이나 실생활과 어떻게 연결되는지 설명하세요.
구체적인 예시와 함께 연결점을 제시하세요.",
        
        'metacognitive' => "
학생이 자신의 사고 과정을 돌아볼 수 있도록 도와주세요.
감지된 인지관성을 부드럽게 인식시키고, 극복 방법을 제안하세요."
    ];
    
    $prompt = $basePrompt . ($specificInstructions[$feedbackType] ?? '');
    
    $prompt .= "

응답 형식:
{
    \"positive\": \"잘한 점과 강점 (1-2문장)\",
    \"improvement\": \"개선 제안 (1-2문장)\",
    \"insight\": \"깊은 통찰이나 연결점 (1-2문장)\",
    \"questions\": [\"사고를 자극하는 질문1\", \"질문2\", \"질문3\"],
    \"inertiaOvercome\": \"인지관성 극복 가이드 (인지관성이 감지된 경우)\",
    \"nextChallenge\": \"다음 단계 도전 과제\"
}";
    
    return $prompt;
}

// GPT API 호출
function callGPTAPI($prompt) {
    global $API_KEY, $API_URL;
    
    $data = [
        'model' => 'gpt-4',
        'messages' => [
            [
                'role' => 'system',
                'content' => '당신은 교육 심리학과 수학 교육에 전문성을 가진 AI 멘토입니다.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 800,
        'response_format' => ['type' => 'json_object']
    ];
    
    $options = [
        'http' => [
            'header' => [
                "Content-Type: application/json",
                "Authorization: Bearer $API_KEY"
            ],
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($API_URL, false, $context);
    
    if ($result === FALSE) {
        return null;
    }
    
    $response = json_decode($result, true);
    return json_decode($response['choices'][0]['message']['content'], true);
}

// 인지관성 카드 획득 판정
function determineCardAcquisition($nodeId, $answer, $detectedInertias) {
    global $nodeToCardMapping;
    
    $potentialCards = $nodeToCardMapping[$nodeId] ?? [];
    $acquiredCards = [];
    
    // 답변 품질 점수 계산
    $qualityScore = 0;
    $qualityScore += min(mb_strlen($answer) / 100, 3); // 길이 점수 (최대 3점)
    $qualityScore += preg_match_all('/예|경험|느|생각/u', $answer) * 0.5; // 키워드 점수
    $qualityScore += count($detectedInertias) > 0 ? 1 : 0; // 인지관성 인식 점수
    
    // 점수에 따라 카드 획득
    if ($qualityScore >= 3) {
        $acquiredCards = $potentialCards; // 모든 카드 획득
    } elseif ($qualityScore >= 2) {
        $acquiredCards[] = $potentialCards[0] ?? null; // 첫 번째 카드만
    }
    
    return array_filter($acquiredCards);
}

// 동적 텍스트 생성 함수
function generateDynamicText($context, $params = []) {
    global $API_KEY, $API_URL;
    
    $prompts = [
        'welcome' => "사용자를 따뜻하게 환영하는 인사말을 만들어주세요. 수학 학습 여정의 시작을 축하하고 격려하는 메시지여야 합니다.",
        'nodeIntro' => "노드 {$params['nodeId']}번의 학습을 시작하는 안내 메시지를 만들어주세요. 호기심을 자극하고 동기를 부여하는 내용이어야 합니다.",
        'encouragement' => "학습자를 격려하는 짧은 메시지를 만들어주세요. 긍정적이고 동기부여가 되는 내용이어야 합니다.",
        'completion' => "단계를 완료한 것을 축하하는 메시지를 만들어주세요. 성취감을 느끼게 하는 내용이어야 합니다.",
        'hint' => "문제 해결을 위한 부드러운 힌트를 제공해주세요. 직접적인 답이 아닌 사고를 자극하는 내용이어야 합니다."
    ];
    
    $prompt = $prompts[$context] ?? "학습자에게 도움이 되는 교육적 메시지를 만들어주세요.";
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => '당신은 친근하고 격려적인 수학 교육 멘토입니다. 이모지를 적절히 사용하여 친근감을 표현하세요.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.8,
        'max_tokens' => 150
    ];
    
    $options = [
        'http' => [
            'header' => [
                "Content-Type: application/json",
                "Authorization: Bearer $API_KEY"
            ],
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($API_URL, false, $context);
    
    if ($result === FALSE) {
        return null;
    }
    
    $response = json_decode($result, true);
    return $response['choices'][0]['message']['content'] ?? null;
}

// 메인 처리
try {
    // 텍스트 생성 요청 처리
    if ($generateType === 'text') {
        $text = generateDynamicText($context, $input);
        if (!$text) {
            // 폴백 텍스트
            $fallbackTexts = [
                'welcome' => "반가워요! 🌟 함께 수학의 세계를 탐험해볼까요?",
                'nodeIntro' => "이 단계에서는 새로운 관점으로 문제를 바라보게 될 거예요.",
                'encouragement' => "훌륭해요! 계속 이렇게 생각해보세요.",
                'completion' => "대단해요! 한 단계를 완성했네요! 🎉",
                'hint' => "다른 각도에서 접근해보는 것은 어떨까요?"
            ];
            $text = $fallbackTexts[$context] ?? "계속 탐험해보세요! ✨";
        }
        
        $response = [
            'success' => true,
            'text' => $text,
            'context' => $context
        ];
    } 
    // 피드백 생성 요청 처리
    else {
        // 피드백 타입 결정
        $feedbackType = determineFeedbackType($answer, $detectedInertias);
        
        // 프롬프트 생성
        $prompt = generatePrompt($nodeId, $answer, $questionType, $detectedInertias, $feedbackType);
        
        // GPT API 호출
        $gptResponse = callGPTAPI($prompt);
        
        // 폴백 응답
        if (!$gptResponse) {
            $gptResponse = [
                'positive' => '깊이 있는 성찰이 돋보입니다! 🌟',
                'improvement' => '구체적인 예시를 더 들어보면 좋겠어요.',
                'insight' => '수학적 사고는 일상생활 곳곳에 숨어있답니다.',
                'questions' => [
                    '오늘 배운 것을 다른 상황에 적용한다면?',
                    '이 개념이 왜 중요하다고 생각하나요?',
                    '비슷한 패턴을 어디서 본 적이 있나요?'
                ],
                'inertiaOvercome' => !empty($detectedInertias) ? 
                    "인지관성을 인식하는 것이 극복의 첫걸음입니다." : "",
                'nextChallenge' => '다음 노드에서 더 흥미로운 발견이 기다리고 있어요!'
            ];
        }
        
        // 카드 획득 판정
        $acquiredCards = determineCardAcquisition($nodeId, $answer, $detectedInertias);
        
        // 응답 생성
        $response = [
            'success' => true,
            'feedback' => $gptResponse,
            'feedbackType' => $feedbackType,
            'acquiredCards' => $acquiredCards,
            'nodeCompleted' => true,
            'nextNodes' => getNextNodes($nodeId)
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// 다음 노드 결정
function getNextNodes($nodeId) {
    $connections = [
        0 => [1, 7],
        1 => [2, 3],
        2 => [3, 4],
        3 => [4, 5],
        4 => [5, 6],
        5 => [6],
        6 => [7],
        7 => [1],
        8 => []
    ];
    
    return $connections[$nodeId] ?? [];
}

// 데이터베이스 저장 (옵션)
if ($response['success'] && $userId > 0) {
    // TODO: 데이터베이스 연결 및 저장 로직
    // saveToDatabase($userId, $nodeId, $answer, $response);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>