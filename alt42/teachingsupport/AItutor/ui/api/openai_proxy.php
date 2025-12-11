<?php
/**
 * OpenAI API 프록시
 * 
 * OpenAI API를 호출하여 인지맵 성장 제안을 생성
 * 
 * @package AugmentedTeacher\TeachingSupport\AItutor\UI\API
 * @version 1.0.0
 * @since 2025-12-11
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 직접 호출된 경우에만 헤더 설정
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=UTF-8');
}

$currentFile = __FILE__;

// Moodle 통합 (직접 호출 시에만)
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    try {
        if (file_exists("/home/moodle/public_html/moodle/config.php")) {
            include_once("/home/moodle/public_html/moodle/config.php");
            global $DB, $USER;
            require_login();
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Moodle config.php not found',
                'error_location' => "$currentFile:28"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Moodle 로드 실패: ' . $e->getMessage(),
            'error_location' => "$currentFile:35"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// OpenAI API 키 (환경변수 또는 config에서 가져오기)
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'your-api-key-here');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 직접 호출된 경우에만 액션 처리
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    try {
        switch ($action) {
            case 'generateSuggestion':
                $result = generateSuggestion();
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
            default:
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid action: ' . $action
                ], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'error_location' => "$currentFile:" . $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    // 내부 include인 경우
    if ($action === 'generateSuggestion') {
        $result = generateSuggestion();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * AI 제안 생성
 */
function generateSuggestion() {
    global $currentFile;
    
    $requestType = $_POST['requestType'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $contextSnapshot = json_decode($_POST['contextSnapshot'] ?? '{}', true);
    $nodesSnapshot = json_decode($_POST['nodesSnapshot'] ?? '{}', true);
    $userInput = $_POST['userInput'] ?? null;
    
    if (!$requestType || !$contentId) {
        throw new Exception('requestType and contentId are required');
    }
    
    // 프롬프트 구성
    $prompt = buildPrompt($requestType, $contextSnapshot, $nodesSnapshot, $userInput);
    
    // OpenAI API 호출
    $response = callOpenAI($prompt);
    
    // 응답 파싱
    $suggestion = parseOpenAIResponse($response, $requestType);
    
    return [
        'success' => true,
        'suggestion' => $suggestion,
        'rawResponse' => $response,
        'tokensUsed' => $response['usage']['total_tokens'] ?? 0
    ];
}

/**
 * 프롬프트 구성
 */
function buildPrompt($requestType, $contextSnapshot, $nodesSnapshot, $userInput = null) {
    $systemPrompt = <<<PROMPT
당신은 수학 교육 전문가이며, 학생들의 문제 풀이 과정을 분석하여 인지맵(Cognitive Map)을 구성하는 AI 어시스턴트입니다.

인지맵은 학생이 문제를 풀 때 거칠 수 있는 다양한 사고 경로를 시각화한 것입니다.
각 노드는 특정 사고 단계나 풀이 방법을 나타내며, 다음과 같은 타입이 있습니다:
- start: 시작점
- correct: 올바른 풀이 단계
- partial: 부분적으로 올바른 풀이
- wrong: 잘못된 풀이 (오개념)
- confused: 혼란 상태
- success: 최종 정답 도달
- fail: 최종 오답

노드는 stage(단계)별로 구성되며, 각 노드는 관련된 개념(concepts)을 가질 수 있습니다.
엣지는 한 노드에서 다른 노드로의 전이를 나타냅니다.

응답은 반드시 다음 JSON 형식으로 제공해야 합니다:
{
    "title": "제안 제목",
    "description": "제안 설명",
    "type": "new_path 또는 misconception_path 또는 modification",
    "confidence": 0.0~1.0 사이의 신뢰도,
    "nodes": [
        {
            "node_id": "고유 노드 ID (예: s3_new_method)",
            "label": "노드 라벨",
            "type": "노드 타입",
            "stage": 단계 번호(정수),
            "x": x좌표(정수),
            "y": y좌표(정수),
            "description": "노드 설명",
            "reasoning": "이 노드를 제안한 이유"
        }
    ],
    "edges": [
        {
            "source": "소스 노드 ID",
            "target": "타겟 노드 ID",
            "reasoning": "이 연결을 제안한 이유"
        }
    ],
    "concepts": [
        {
            "node_id": "연결할 노드 ID",
            "concept_id": "개념 ID",
            "name": "새 개념인 경우 이름",
            "icon": "새 개념인 경우 아이콘 (이모지)",
            "color": "새 개념인 경우 색상 코드",
            "is_new": true/false,
            "reasoning": "이 개념 연결을 제안한 이유"
        }
    ]
}
PROMPT;

    // 현재 컨텐츠 정보
    $contentInfo = "";
    if (!empty($contextSnapshot['content'])) {
        $content = $contextSnapshot['content'];
        $contentInfo = "
## 현재 문제 정보
- 제목: {$content['title']}
- 정답: {$content['answer']}
";
    }
    
    // 기존 개념 목록
    $conceptsInfo = "";
    if (!empty($contextSnapshot['concepts'])) {
        $conceptsInfo = "\n## 기존 개념 목록\n";
        foreach ($contextSnapshot['concepts'] as $c) {
            $conceptsInfo .= "- {$c['concept_id']}: {$c['name']} ({$c['icon']})\n";
        }
    }
    
    // 기존 노드 목록
    $nodesInfo = "";
    if (!empty($nodesSnapshot['nodes'])) {
        $nodesInfo = "\n## 기존 노드 목록\n";
        foreach ($nodesSnapshot['nodes'] as $n) {
            $concepts = !empty($n['concepts']) ? implode(', ', $n['concepts']) : '없음';
            $nodesInfo .= "- Stage {$n['stage']}: {$n['node_id']} ({$n['type']}) - {$n['label']} [개념: {$concepts}]\n";
        }
    }
    
    // 기존 엣지 목록
    $edgesInfo = "";
    if (!empty($nodesSnapshot['edges'])) {
        $edgesInfo = "\n## 기존 엣지(연결) 목록\n";
        foreach ($nodesSnapshot['edges'] as $e) {
            $edgesInfo .= "- {$e['source']} → {$e['target']}\n";
        }
    }
    
    // 요청 타입별 지시사항
    $taskInstruction = "";
    switch ($requestType) {
        case 'new_solution':
            $taskInstruction = "
## 작업 요청
기존 풀이 경로와 다른 **새로운 정답 풀이 방법**을 제안해주세요.
- 기존에 없는 새로운 접근법이나 풀이 전략을 탐색
- 최종적으로 정답(success)에 도달하는 경로여야 함
- 기존 노드와 자연스럽게 연결될 수 있도록 구성
- 새 노드의 x, y 좌표는 기존 노드들과 겹치지 않도록 배치
";
            break;
            
        case 'misconception':
            $taskInstruction = "
## 작업 요청
학생들이 자주 하는 **오개념이나 실수 경로**를 제안해주세요.
- 학생들이 흔히 범하는 실수나 오해를 반영
- wrong 또는 confused 타입의 노드 포함
- 최종적으로 fail에 도달하거나, 오류를 인식하고 복귀하는 경로
- 교육적 가치가 있는 오개념 패턴
";
            break;
            
        case 'custom_input':
            $taskInstruction = "
## 작업 요청
사용자가 입력한 풀이를 분석하여 인지맵에 반영할 노드와 엣지를 제안해주세요.

### 사용자 입력 풀이
{$userInput}

- 입력된 풀이를 단계별로 분석
- 각 단계를 적절한 노드로 변환
- 기존 노드와의 연결점 찾기
- 올바른 풀이인지 오개념인지 판단
";
            break;
    }
    
    $userPrompt = $contentInfo . $conceptsInfo . $nodesInfo . $edgesInfo . $taskInstruction;
    
    return [
        'system' => $systemPrompt,
        'user' => $userPrompt
    ];
}

/**
 * OpenAI API 호출
 */
function callOpenAI($prompt) {
    global $currentFile;
    
    $apiKey = OPENAI_API_KEY;
    
    if ($apiKey === 'your-api-key-here' || empty($apiKey)) {
        // API 키가 없는 경우 모의 응답 반환 (개발/테스트용)
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
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("OpenAI API 호출 실패: $error [$currentFile]");
    }
    
    if ($httpCode !== 200) {
        $errorBody = json_decode($response, true);
        $errorMsg = $errorBody['error']['message'] ?? 'Unknown error';
        throw new Exception("OpenAI API 오류 ($httpCode): $errorMsg [$currentFile]");
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception("OpenAI 응답 파싱 실패 [$currentFile]");
    }
    
    return $result;
}

/**
 * 모의 응답 (테스트용)
 */
function getMockResponse() {
    $mockSuggestion = [
        'title' => '대입법을 이용한 새로운 풀이',
        'description' => '꼭짓점 좌표를 직접 대입하여 정삼각형 조건을 확인하는 방법',
        'type' => 'new_path',
        'confidence' => 0.85,
        'nodes' => [
            [
                'node_id' => 's3_substitute',
                'label' => '직접 대입',
                'type' => 'correct',
                'stage' => 3,
                'x' => 620,
                'y' => 460,
                'description' => 'C 좌표를 직접 계산하여 대입',
                'reasoning' => '완전제곱식이나 꼭짓점 공식 대신 직접 값을 대입하여 계산하는 방법'
            ],
            [
                'node_id' => 's5_direct_calc',
                'label' => '직접 거리 계산',
                'type' => 'correct',
                'stage' => 5,
                'x' => 480,
                'y' => 760,
                'description' => '세 점 사이의 거리를 직접 계산',
                'reasoning' => '좌표를 이용한 거리 공식 직접 적용'
            ]
        ],
        'edges' => [
            [
                'source' => 's2_formula',
                'target' => 's3_substitute',
                'reasoning' => '근의 공식으로 x절편을 구한 후 직접 대입 방법으로 진행'
            ],
            [
                'source' => 's3_substitute',
                'target' => 's4_sides',
                'reasoning' => '좌표를 얻은 후 세 변의 길이를 비교하는 방법으로 연결'
            ],
            [
                'source' => 's4_sides',
                'target' => 's5_direct_calc',
                'reasoning' => '세 변 같음 조건에서 직접 거리 계산으로 연결'
            ],
            [
                'source' => 's5_direct_calc',
                'target' => 's6_eq_sides',
                'reasoning' => '직접 계산된 거리로 방정식 설정'
            ]
        ],
        'concepts' => [
            [
                'node_id' => 's3_substitute',
                'concept_id' => 'vertex',
                'is_new' => false,
                'reasoning' => '꼭짓점 좌표 활용'
            ],
            [
                'node_id' => 's5_direct_calc',
                'concept_id' => 'distance',
                'is_new' => false,
                'reasoning' => '거리 계산 개념 적용'
            ]
        ]
    ];
    
    return [
        'choices' => [
            [
                'message' => [
                    'content' => json_encode($mockSuggestion, JSON_UNESCAPED_UNICODE)
                ]
            ]
        ],
        'usage' => [
            'total_tokens' => 1500
        ]
    ];
}

/**
 * OpenAI 응답 파싱
 */
function parseOpenAIResponse($response, $requestType) {
    global $currentFile;
    
    $content = $response['choices'][0]['message']['content'] ?? '';
    
    $suggestion = json_decode($content, true);
    
    if (!$suggestion) {
        throw new Exception("제안 JSON 파싱 실패 [$currentFile]");
    }
    
    // 필수 필드 검증
    if (empty($suggestion['nodes']) && empty($suggestion['edges'])) {
        throw new Exception("제안에 노드나 엣지가 없습니다 [$currentFile]");
    }
    
    // 타입 설정 (없는 경우)
    if (empty($suggestion['type'])) {
        switch ($requestType) {
            case 'new_solution':
                $suggestion['type'] = 'new_path';
                break;
            case 'misconception':
                $suggestion['type'] = 'misconception_path';
                break;
            default:
                $suggestion['type'] = 'modification';
        }
    }
    
    return $suggestion;
}


