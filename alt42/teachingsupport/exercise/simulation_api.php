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

$action = $input['action'] ?? '';
$problemText = $input['problemText'] ?? '';
$problemImage = $input['problemImage'] ?? '';
$teacherMessage = $input['message'] ?? '';
$studentPersona = $input['persona'] ?? 'mid';
$conversationHistory = $input['history'] ?? [];
$customDescription = $input['customDescription'] ?? '';

// 학생 페르소나 정의 (인지적 특성 강화)
$personaDescriptions = [
    'curious-high' => [
        'name' => '민준',
        'level' => '상위권',
        'description' => '수학적 직관이 뛰어나고 심화 개념에 관심이 많음. 증명과 일반화를 좋아하며, 개념의 본질을 파고들려 함.',
        'cognitive_traits' => [
            'confusion_tolerance' => '높음',
            'metacognition' => '활발함',
            'error_patterns' => ['과도한 일반화', '세부사항 간과'],
            'thinking_time' => '짧음',
            'question_types' => ['왜?', '만약에?', '일반적으로?']
        ]
    ],
    'careful-mid' => [
        'name' => '서연', 
        'level' => '중위권',
        'description' => '단계적 설명을 선호하며, 충분한 연습이 필요. 실수를 줄이려 노력하고, 이해했는지 확인하기를 원함.',
        'cognitive_traits' => [
            'confusion_tolerance' => '보통',
            'metacognition' => '가끔',
            'error_patterns' => ['계산 실수', '공식 혼동', '부호 오류'],
            'thinking_time' => '보통',
            'question_types' => ['맞나요?', '어떻게?', '다시 설명해주세요']
        ]
    ],
    'struggling-low' => [
        'name' => '지호',
        'level' => '하위권',
        'description' => '기본 개념 이해가 부족하고 자신감이 낮음. 격려와 반복 설명이 필요하며, 쉬운 예시부터 시작해야 함.',
        'cognitive_traits' => [
            'confusion_tolerance' => '낮음',
            'metacognition' => '드물음',
            'error_patterns' => ['개념 미이해', '용어 혼동', '포기'],
            'thinking_time' => '길거나 포기',
            'question_types' => ['모르겠어요', '어려워요', '이게 뭐예요?']
        ]
    ]
];

// OpenAI API 호출 함수
function generateStudentResponse($problemText, $problemImage, $teacherMessage, $persona, $history, $customDescription = '') {
    global $personaDescriptions;
    
    $apiKey = OPENAI_API_KEY;
    $model = 'gpt-4o';
    
    $personaInfo = $personaDescriptions[$persona];
    // 사용자가 편집한 설명이 있으면 사용
    if (!empty($customDescription)) {
        $personaInfo['description'] = $customDescription;
    }
    
    // 시스템 메시지 (인지적 특성 반영)
    $cognitiveState = analyzeCognitiveState($history);
    $traits = $personaInfo['cognitive_traits'] ?? [];
    
    $messages = [
        [
            'role' => 'system',
            'content' => "당신은 {$personaInfo['name']}이라는 {$personaInfo['level']} 수학 학생입니다.
특성: {$personaInfo['description']}

**인지적 특성:**
• 혼란 감내력: {$traits['confusion_tolerance']}
• 메타인지: {$traits['metacognition']}
• 주요 오류 패턴: " . implode(', ', $traits['error_patterns']) . "
• 사고 시간: {$traits['thinking_time']}
• 질문 유형: " . implode(', ', $traits['question_types']) . "

**현재 인지 상태:** {$cognitiveState}

**반응 지침:**
1. 3초 기다림 효과 반영
   - 복잡한 질문: \"음... (잠깐 생각 중)\" 또는 \"어... 잠깐만요\"
   - 쉬운 질문: 즉시 반응

2. 인지 상태별 반응
   - 혼란 상태: \"헷갈려요\", \"잘 모르겠어요\", \"어렵네요\"
   - 호기심 상태: \"아! 그러면...\", \"왜 그런거예요?\", \"신기하네요\"
   - 이해 시도: \"아, 그러니까...\", \"제가 이해한 게 맞나요?\"

3. 오류 패턴 반영
   - 페르소나의 전형적 실수를 자연스럽게 포함
   - 교사 피드백에 따라 점진적 수정

4. 메타인지적 표현
   - 상위권: \"제 풀이 과정을 설명하면...\"
   - 중위권: \"여기까지는 맞는 것 같은데...\"
   - 하위권: \"어디서부터 틀렸는지 모르겠어요\"

5. 절대 하지 말아야 할 것
   - 교사처럼 설명하기
   - 다른 학생에게 조언하기
   - 완벽한 답변 제시하기"
        ]
    ];
    
    // 문제 컨텍스트 추가
    if ($problemText || $problemImage) {
        $contextContent = [];
        
        if ($problemText) {
            $contextContent[] = [
                'type' => 'text',
                'text' => "우리가 공부하고 있는 문제: {$problemText}"
            ];
        }
        
        if ($problemImage) {
            $contextContent[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $problemImage]
            ];
            if (!$problemText) {
                array_unshift($contextContent, [
                    'type' => 'text', 
                    'text' => '우리가 공부하고 있는 문제 (이미지 참조):'
                ]);
            }
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $contextContent
        ];
    }
    
    // 대화 히스토리 추가
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'] === 'teacher' ? 'user' : 'assistant',
            'content' => $msg['content']
        ];
    }
    
    // 현재 교사 메시지 추가
    $messages[] = [
        'role' => 'user',
        'content' => $teacherMessage
    ];
    
    // cURL 초기화
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    // 요청 데이터
    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 300,
        'temperature' => 0.8
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

// 시뮬레이션 시작을 위한 초기 학생 메시지 생성
function generateInitialStudentMessage($problemText, $problemImage, $persona, $customDescription = '') {
    global $personaDescriptions;
    
    $apiKey = OPENAI_API_KEY;
    $model = 'gpt-4o';
    
    $personaInfo = $personaDescriptions[$persona];
    // 사용자가 편집한 설명이 있으면 사용
    if (!empty($customDescription)) {
        $personaInfo['description'] = $customDescription;
    }
    
    // 시스템 메시지 (인지적 특성 반영)
    $traits = $personaInfo['cognitive_traits'] ?? [];
    
    $messages = [
        [
            'role' => 'system',
            'content' => "당신은 {$personaInfo['name']}이라는 {$personaInfo['level']} 수학 학생입니다.
특성: {$personaInfo['description']}

**인지적 특성:**
• 혼란 감내력: {$traits['confusion_tolerance']}
• 사고 시간: {$traits['thinking_time']}
• 전형적 오류: " . implode(', ', $traits['error_patterns']) . "

**첫 반응 지침:**
1. 문제를 보고 즉각적 반응
   - 상위권: 패턴 인식, 접근법 고민 \"이거 판별식 사용하는 문제네요\"
   - 중위권: 익숙함 확인, 절차 회상 \"이런 거 배운 것 같은데...\"
   - 하위권: 두려움/회피 \"어... 이거 너무 복잡해 보여요\"

2. 인지적 특성 반영
   - 높은 혼란 감내력: 도전적 태도
   - 낮은 혼란 감내력: 불안함 표현

3. 전형적 첫 시도
   - 상위권: 즉시 풀이 시작하며 사고 과정 말하기
   - 중위권: 공식이나 예제 떠올리기
   - 하위권: 도움 요청이나 포기 신호

실제 학생처럼 자연스럽게 반응하세요."
        ]
    ];
    
    // 문제 추가
    $userContent = [];
    
    if ($problemText) {
        $userContent[] = [
            'type' => 'text',
            'text' => "다음 문제를 봐주세요: {$problemText}"
        ];
    }
    
    if ($problemImage) {
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $problemImage]
        ];
        if (!$problemText) {
            array_unshift($userContent, [
                'type' => 'text',
                'text' => '이 문제를 봐주세요:'
            ]);
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
        'max_tokens' => 200,
        'temperature' => 0.8
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

// 인지 상태 분석 함수
function analyzeCognitiveState($history) {
    if (empty($history)) {
        return "시작 단계";
    }
    
    $confusionKeywords = ['모르겠', '헷갈', '어렵', '이해가 안', '왜', '어떻게'];
    $curiosityKeywords = ['그러면', '만약', '왜', '어떻게', '신기'];
    $understandingKeywords = ['아', '알겠', '이해했', '그렇구나', '맞네요'];
    
    $confusionCount = 0;
    $curiosityCount = 0;
    $understandingCount = 0;
    
    // 최근 3개 메시지 분석
    $recentHistory = array_slice($history, -3);
    foreach ($recentHistory as $msg) {
        if ($msg['role'] === 'student') {
            $content = $msg['content'];
            foreach ($confusionKeywords as $keyword) {
                if (mb_strpos($content, $keyword) !== false) $confusionCount++;
            }
            foreach ($curiosityKeywords as $keyword) {
                if (mb_strpos($content, $keyword) !== false) $curiosityCount++;
            }
            foreach ($understandingKeywords as $keyword) {
                if (mb_strpos($content, $keyword) !== false) $understandingCount++;
            }
        }
    }
    
    // 상태 결정
    if ($confusionCount > $understandingCount * 2) {
        if ($curiosityCount > 0) {
            return "생산적 혼란 (호기심 있음)";
        } else {
            return "비생산적 혼란 (좌절 위험)";
        }
    } elseif ($understandingCount > $confusionCount) {
        return "이해 진행 중";
    } elseif ($curiosityCount > $confusionCount) {
        return "탐구 모드";
    } else {
        return "일반 학습 중";
    }
}

try {
    if ($action === 'start') {
        // 시뮬레이션 시작 - 초기 학생 메시지 생성
        if (empty($problemText) && empty($problemImage)) {
            // 기본 문제 설정
            $problemText = "이차방정식에 대해 공부하고 있습니다.";
        }
        
        $initialMessage = generateInitialStudentMessage($problemText, $problemImage, $studentPersona, $customDescription);
        
        echo json_encode([
            'success' => true,
            'message' => $initialMessage,
            'persona' => $personaDescriptions[$studentPersona]
        ]);
        
    } elseif ($action === 'respond') {
        // 교사 메시지에 대한 학생 응답 생성
        if (empty($teacherMessage)) {
            throw new Exception('메시지를 입력해주세요');
        }
        
        $studentResponse = generateStudentResponse($problemText, $problemImage, $teacherMessage, $studentPersona, $conversationHistory, $customDescription);
        
        echo json_encode([
            'success' => true,
            'message' => $studentResponse
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>