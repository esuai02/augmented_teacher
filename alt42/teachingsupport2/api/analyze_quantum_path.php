<?php
/**
 * 양자 붕괴 학습 미로 - AI 분석 API
 * 
 * OpenAI API를 사용하여:
 * 1. 문제를 분석하고 핵심 개념 추출
 * 2. 풀이 경로(노드/엣지) 자동 생성
 * 3. 사용자가 추가한 새 경로 검증 및 생성
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php');
require_once(__DIR__ . '/../../keys.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// OpenAI API 설정
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-4o-mini');

// 입력 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => '입력 데이터가 없습니다.']);
    exit;
}

$action = $input['action'] ?? 'analyze';

try {
    switch ($action) {
        case 'analyze':
            // 문제 분석 및 노드/엣지 생성
            $result = analyzeQuestion($input);
            break;
            
        case 'create_node':
            // 사용자가 새 경로 추가
            $result = createUserNode($input);
            break;
            
        default:
            $result = ['success' => false, 'error' => '알 수 없는 액션'];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("[analyze_quantum_path.php] 오류: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * 문제 분석 및 노드/엣지 생성
 */
function analyzeQuestion($input) {
    $questionData = $input['questionData'] ?? null;
    $imageUrl = $input['imageUrl'] ?? null;
    
    if (!$questionData) {
        return ['success' => false, 'error' => '문제 데이터가 없습니다.'];
    }
    
    // 문제 텍스트 추출
    $questionText = $questionData['narration_text'] ?? $questionData['question_text'] ?? '';
    
    if (empty($questionText) && empty($imageUrl)) {
        return ['success' => false, 'error' => '분석할 문제가 없습니다.'];
    }
    
    // OpenAI API로 분석
    $prompt = buildAnalysisPrompt($questionText, $imageUrl);
    $response = callOpenAI($prompt, $imageUrl);
    
    if (!$response) {
        // 기본 데이터 반환
        return ['success' => true, 'data' => getDefaultMazeData()];
    }
    
    // JSON 파싱
    $mazeData = parseOpenAIResponse($response);
    
    return ['success' => true, 'data' => $mazeData];
}

/**
 * 사용자 노드 생성
 */
function createUserNode($input) {
    global $DB;
    
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $parentNodeId = $input['parentNodeId'] ?? 'start';
    $userId = $input['userId'] ?? 0;
    $contentsId = $input['contentsId'] ?? '';
    
    if (empty($title) || empty($description)) {
        return ['success' => false, 'error' => '제목과 설명이 필요합니다.'];
    }
    
    // AI로 노드 유형 및 개념 분석
    $prompt = buildNodeAnalysisPrompt($title, $description);
    $response = callOpenAI($prompt);
    
    $nodeData = parseNodeResponse($response, $title);
    
    // 고유 ID 생성
    $nodeId = 'user_' . uniqid();
    
    // 데이터베이스에 저장 (선택적)
    try {
        $record = new stdClass();
        $record->contentsid = $contentsId;
        $record->userid = $userId;
        $record->node_id = $nodeId;
        $record->parent_node_id = $parentNodeId;
        $record->title = $title;
        $record->description = $description;
        $record->node_type = $nodeData['type'];
        $record->concepts = json_encode($nodeData['concepts']);
        $record->status = 'pending'; // pending, verified, rejected
        $record->votes = 0;
        $record->timecreated = time();
        
        // 테이블이 있으면 저장
        if ($DB->get_manager()->table_exists('ktm_quantum_nodes')) {
            $DB->insert_record('ktm_quantum_nodes', $record);
        }
    } catch (Exception $e) {
        error_log("[analyze_quantum_path.php] DB 저장 오류: " . $e->getMessage());
    }
    
    return [
        'success' => true,
        'node' => [
            'id' => $nodeId,
            'label' => $title,
            'type' => $nodeData['type'],
            'concepts' => $nodeData['concepts'],
            'isUserNode' => true,
            'status' => 'pending'
        ]
    ];
}

/**
 * 분석 프롬프트 생성
 */
function buildAnalysisPrompt($questionText, $imageUrl) {
    $prompt = <<<PROMPT
수학 문제를 분석하여 학습자의 풀이 경로를 "양자 붕괴 학습 미로" 형태로 생성해주세요.

## 문제
{$questionText}

## 출력 형식
다음 JSON 형식으로 정확히 응답해주세요:

```json
{
  "concepts": {
    "concept_id": {"id": "concept_id", "name": "개념명", "icon": "이모지", "color": "#색상코드"},
    ...
  },
  "nodes": {
    "start": {"id": "start", "x": 350, "y": 40, "label": "문제 인식", "type": "start", "stage": 0, "concepts": []},
    "s1_c": {"id": "s1_c", "x": 180, "y": 120, "label": "올바른 접근", "type": "correct", "stage": 1, "concepts": ["concept_id1"]},
    "s1_m": {"id": "s1_m", "x": 350, "y": 120, "label": "부분적 접근", "type": "partial", "stage": 1, "concepts": ["concept_id2"]},
    "s1_x": {"id": "s1_x", "x": 520, "y": 120, "label": "혼란", "type": "confused", "stage": 1, "concepts": []},
    ...
    "success": {"id": "success", "x": 180, "y": 510, "label": "💥 정답", "type": "success", "stage": 5, "concepts": ["final_concept"]},
    "fail": {"id": "fail", "x": 450, "y": 510, "label": "❌ 오답", "type": "fail", "stage": 5, "concepts": []}
  },
  "edges": [
    ["start", "s1_c"],
    ["start", "s1_m"],
    ...
  ]
}
```

## 규칙
1. **노드 유형**: start, correct, partial, wrong, confused, success, fail
2. **stage**: 0(시작)~5(결과)까지 6단계
3. **concepts**: 해당 노드에서 활성화되는 개념 ID 배열
4. **좌표**: x는 100~600, y는 stage에 따라 40, 120, 210, 310, 410, 510
5. **다양한 경로**: 정답 경로 뿐만 아니라 흔한 오개념, 부분적 이해, 혼란 경로도 포함
6. **실제 문제 기반**: 해당 문제의 실제 풀이 과정과 흔한 실수를 반영

JSON만 출력하세요. 다른 설명은 필요 없습니다.
PROMPT;

    return $prompt;
}

/**
 * 노드 분석 프롬프트
 */
function buildNodeAnalysisPrompt($title, $description) {
    return <<<PROMPT
학생이 제출한 수학 풀이 방법을 분석해주세요.

## 풀이 제목
{$title}

## 풀이 설명
{$description}

## 출력 형식 (JSON만)
```json
{
  "type": "correct|partial|wrong",
  "concepts": ["관련개념1", "관련개념2"],
  "validity": "valid|invalid|needs_review",
  "feedback": "간단한 피드백"
}
```

- type: correct(올바른 풀이), partial(부분적으로 맞음), wrong(틀린 접근)
- concepts: 이 풀이에서 사용된 수학 개념들
- validity: 이 풀이의 유효성
- feedback: 학생에게 줄 짧은 피드백

JSON만 출력하세요.
PROMPT;
}

/**
 * OpenAI API 호출
 */
function callOpenAI($prompt, $imageUrl = null) {
    $apiKey = MATHKING_OPENAI_KEY ?? '';
    
    if (empty($apiKey)) {
        error_log("[analyze_quantum_path.php] OpenAI API 키가 없습니다.");
        return null;
    }
    
    $messages = [];
    
    if ($imageUrl) {
        // 이미지 포함 메시지
        $messages[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ]
        ];
    } else {
        $messages[] = ['role' => 'user', 'content' => $prompt];
    }
    
    $payload = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => 4096,
        'temperature' => 0.7
    ];
    
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("[analyze_quantum_path.php] OpenAI API 오류: HTTP {$httpCode}");
        return null;
    }
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? null;
}

/**
 * OpenAI 응답 파싱
 */
function parseOpenAIResponse($response) {
    // JSON 블록 추출
    if (preg_match('/```json\s*([\s\S]*?)\s*```/', $response, $matches)) {
        $jsonStr = $matches[1];
    } else {
        $jsonStr = $response;
    }
    
    $data = json_decode($jsonStr, true);
    
    if (!$data || !isset($data['nodes']) || !isset($data['edges'])) {
        return getDefaultMazeData();
    }
    
    return $data;
}

/**
 * 노드 응답 파싱
 */
function parseNodeResponse($response, $fallbackTitle) {
    if (!$response) {
        return ['type' => 'partial', 'concepts' => []];
    }
    
    // JSON 블록 추출
    if (preg_match('/```json\s*([\s\S]*?)\s*```/', $response, $matches)) {
        $jsonStr = $matches[1];
    } else {
        $jsonStr = $response;
    }
    
    $data = json_decode($jsonStr, true);
    
    if (!$data) {
        return ['type' => 'partial', 'concepts' => []];
    }
    
    return [
        'type' => $data['type'] ?? 'partial',
        'concepts' => $data['concepts'] ?? [],
        'validity' => $data['validity'] ?? 'needs_review',
        'feedback' => $data['feedback'] ?? ''
    ];
}

/**
 * 기본 미로 데이터
 */
function getDefaultMazeData() {
    return [
        'concepts' => [
            'inequality' => ['id' => 'inequality', 'name' => '부등식 설정', 'icon' => '📐', 'color' => '#06b6d4'],
            'comparison' => ['id' => 'comparison', 'name' => '대소 비교', 'icon' => '⚖️', 'color' => '#8b5cf6'],
            'transpose' => ['id' => 'transpose', 'name' => '이항 정리', 'icon' => '↔️', 'color' => '#f59e0b'],
            'factorize' => ['id' => 'factorize', 'name' => '인수분해', 'icon' => '🧩', 'color' => '#10b981'],
            'roots' => ['id' => 'roots', 'name' => '근 찾기', 'icon' => '🎯', 'color' => '#ec4899'],
            'sign' => ['id' => 'sign', 'name' => '부호 판단', 'icon' => '±', 'color' => '#ef4444'],
            'interval' => ['id' => 'interval', 'name' => '구간 해석', 'icon' => '📊', 'color' => '#3b82f6'],
            'graph' => ['id' => 'graph', 'name' => '그래프 해석', 'icon' => '📈', 'color' => '#14b8a6'],
        ],
        'nodes' => [
            'start' => ['id' => 'start', 'x' => 350, 'y' => 40, 'label' => '문제 인식', 'type' => 'start', 'stage' => 0, 'concepts' => []],
            's1_c' => ['id' => 's1_c', 'x' => 180, 'y' => 120, 'label' => '핵심 파악', 'type' => 'correct', 'stage' => 1, 'concepts' => ['inequality', 'comparison']],
            's1_m' => ['id' => 's1_m', 'x' => 350, 'y' => 120, 'label' => '부분 이해', 'type' => 'partial', 'stage' => 1, 'concepts' => ['graph']],
            's1_x' => ['id' => 's1_x', 'x' => 520, 'y' => 120, 'label' => '혼란', 'type' => 'confused', 'stage' => 1, 'concepts' => []],
            's2_c1' => ['id' => 's2_c1', 'x' => 100, 'y' => 210, 'label' => '올바른 식', 'type' => 'correct', 'stage' => 2, 'concepts' => ['inequality', 'comparison']],
            's2_c2' => ['id' => 's2_c2', 'x' => 230, 'y' => 210, 'label' => '그래프 접근', 'type' => 'partial', 'stage' => 2, 'concepts' => ['graph', 'comparison']],
            's2_m1' => ['id' => 's2_m1', 'x' => 350, 'y' => 210, 'label' => '부호 착오', 'type' => 'wrong', 'stage' => 2, 'concepts' => ['inequality']],
            's2_m2' => ['id' => 's2_m2', 'x' => 470, 'y' => 210, 'label' => '개념 혼동', 'type' => 'wrong', 'stage' => 2, 'concepts' => ['roots']],
            's2_x1' => ['id' => 's2_x1', 'x' => 580, 'y' => 210, 'label' => '막막함', 'type' => 'confused', 'stage' => 2, 'concepts' => []],
            's3_c' => ['id' => 's3_c', 'x' => 120, 'y' => 310, 'label' => '정리 완료', 'type' => 'correct', 'stage' => 3, 'concepts' => ['transpose', 'inequality']],
            's3_p' => ['id' => 's3_p', 'x' => 260, 'y' => 310, 'label' => '시각적 정리', 'type' => 'partial', 'stage' => 3, 'concepts' => ['graph', 'transpose']],
            's3_m1' => ['id' => 's3_m1', 'x' => 400, 'y' => 310, 'label' => '계산 오류', 'type' => 'wrong', 'stage' => 3, 'concepts' => ['transpose']],
            's3_m2' => ['id' => 's3_m2', 'x' => 530, 'y' => 310, 'label' => '방향 착오', 'type' => 'wrong', 'stage' => 3, 'concepts' => ['factorize', 'roots']],
            's4_c' => ['id' => 's4_c', 'x' => 140, 'y' => 410, 'label' => '해 도출', 'type' => 'correct', 'stage' => 4, 'concepts' => ['factorize', 'roots']],
            's4_p' => ['id' => 's4_p', 'x' => 280, 'y' => 410, 'label' => '추정 해', 'type' => 'partial', 'stage' => 4, 'concepts' => ['graph', 'roots']],
            's4_m' => ['id' => 's4_m', 'x' => 420, 'y' => 410, 'label' => '불완전 해', 'type' => 'wrong', 'stage' => 4, 'concepts' => ['factorize', 'roots']],
            's4_m2' => ['id' => 's4_m2', 'x' => 550, 'y' => 410, 'label' => '잘못된 해', 'type' => 'wrong', 'stage' => 4, 'concepts' => ['roots']],
            'success' => ['id' => 'success', 'x' => 180, 'y' => 510, 'label' => '💥 정답!', 'type' => 'success', 'stage' => 5, 'concepts' => ['sign', 'interval']],
            'partial_s' => ['id' => 'partial_s', 'x' => 320, 'y' => 510, 'label' => '✨ 정답', 'type' => 'success', 'stage' => 5, 'concepts' => ['graph', 'interval']],
            'fail_m1' => ['id' => 'fail_m1', 'x' => 450, 'y' => 510, 'label' => '❌ 오답', 'type' => 'fail', 'stage' => 5, 'concepts' => ['sign', 'interval']],
            'fail_m2' => ['id' => 'fail_m2', 'x' => 570, 'y' => 510, 'label' => '❌ 오답', 'type' => 'fail', 'stage' => 5, 'concepts' => ['interval']],
        ],
        'edges' => [
            ['start', 's1_c'], ['start', 's1_m'], ['start', 's1_x'],
            ['s1_c', 's2_c1'], ['s1_c', 's2_c2'], ['s1_m', 's2_m1'], ['s1_m', 's2_m2'], ['s1_x', 's2_x1'],
            ['s2_c1', 's3_c'], ['s2_c2', 's3_p'], ['s2_m1', 's3_m1'], ['s2_m2', 's3_m2'], ['s2_x1', 's3_p'],
            ['s3_c', 's4_c'], ['s3_p', 's4_p'], ['s3_m1', 's4_m'], ['s3_m2', 's4_m2'],
            ['s4_c', 'success'], ['s4_p', 'partial_s'], ['s4_m', 'fail_m1'], ['s4_m2', 'fail_m2'],
        ]
    ];
}

