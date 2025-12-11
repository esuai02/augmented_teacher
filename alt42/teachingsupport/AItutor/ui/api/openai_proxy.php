<?php
/**
 * OpenAI API 프록시
 * 
 * Moodle config에서 API 키를 가져와 사용
 * DB에서 문제 정보를 불러와 프롬프트 동적 생성
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle config 로드 (API 키 포함)
global $CFG, $DB;
if (!isset($CFG) || !isset($DB)) {
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
    }
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=UTF-8');
}

// API 키를 $CFG에서 가져오기
define('OPENAI_API_KEY', $CFG->openai_api_key ?? '');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'generateSuggestion') {
    $result = generateSuggestion();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

function generateSuggestion() {
    global $DB;
    
    $requestType = $_POST['requestType'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $userInput = $_POST['userInput'] ?? null;
    
    if (!$requestType || !$contentId) {
        return ['success' => false, 'error' => 'requestType and contentId are required'];
    }
    
    // DB에서 문제 정보 가져오기
    $contentInfo = getContentInfo($contentId);
    
    // 기존 노드/엣지 정보 가져오기
    $existingNodes = getExistingNodes($contentId);
    $existingEdges = getExistingEdges($contentId);
    
    $prompt = buildPrompt($requestType, $userInput, $contentInfo, $existingNodes, $existingEdges);
    $response = callOpenAI($prompt);
    $suggestion = parseOpenAIResponse($response, $requestType);
    
    return [
        'success' => true,
        'suggestion' => $suggestion,
        'rawResponse' => $response,
        'tokensUsed' => $response['usage']['total_tokens'] ?? 0
    ];
}

/**
 * DB에서 컨텐츠 정보 조회
 */
function getContentInfo($contentId) {
    global $DB;
    
    $info = [
        'title' => '',
        'answer' => '',
        'questionText' => '',
        'stageNames' => []
    ];
    
    try {
        // at_quantum_contents 테이블에서 조회
        $content = $DB->get_record('at_quantum_contents', ['content_id' => $contentId]);
        if ($content) {
            $info['title'] = $content->title ?? '';
            $info['answer'] = $content->answer ?? '';
            $info['stageNames'] = json_decode($content->stage_names ?? '[]', true) ?: [];
        }
        
        // mdl_question에서 문제 텍스트 조회 (숫자형 contentId인 경우)
        if (is_numeric($contentId)) {
            $question = $DB->get_record('question', ['id' => $contentId], 'questiontext');
            if ($question && $question->questiontext) {
                // HTML 태그 제거하여 텍스트만 추출
                $info['questionText'] = strip_tags($question->questiontext);
            }
        }
    } catch (Exception $e) {
        error_log("[openai_proxy] 컨텐츠 정보 조회 실패: " . $e->getMessage());
    }
    
    return $info;
}

/**
 * DB에서 기존 노드 목록 조회
 */
function getExistingNodes($contentId) {
    global $DB;
    
    $nodes = [];
    try {
        $records = $DB->get_records('at_quantum_nodes', ['content_id' => $contentId, 'is_active' => 1]);
        foreach ($records as $r) {
            $nodes[] = [
                'node_id' => $r->node_id,
                'label' => $r->label,
                'type' => $r->type,
                'stage' => (int)$r->stage,
                'description' => $r->description ?? ''
            ];
        }
    } catch (Exception $e) {
        error_log("[openai_proxy] 노드 조회 실패: " . $e->getMessage());
    }
    
    return $nodes;
}

/**
 * DB에서 기존 엣지 목록 조회
 */
function getExistingEdges($contentId) {
    global $DB;
    
    $edges = [];
    try {
        $records = $DB->get_records('at_quantum_edges', ['content_id' => $contentId, 'is_active' => 1]);
        foreach ($records as $r) {
            $edges[] = [$r->source_node_id, $r->target_node_id];
        }
    } catch (Exception $e) {
        error_log("[openai_proxy] 엣지 조회 실패: " . $e->getMessage());
    }
    
    return $edges;
}

/**
 * 프롬프트 동적 생성 (DB 정보 기반)
 */
function buildPrompt($requestType, $userInput, $contentInfo, $existingNodes, $existingEdges) {
    $systemPrompt = "당신은 수학 교육 전문가입니다. 인지맵에 추가할 새로운 노드와 엣지를 JSON 형식으로 제안해주세요.

응답 형식:
{
    \"title\": \"제안 제목\",
    \"description\": \"제안 설명\",
    \"type\": \"new_path 또는 misconception_path\",
    \"confidence\": 0.0~1.0,
    \"nodes\": [{\"node_id\": \"고유ID\", \"label\": \"라벨\", \"type\": \"correct/wrong/partial\", \"stage\": 숫자, \"x\": 숫자, \"y\": 숫자, \"description\": \"설명\", \"reasoning\": \"이유\"}],
    \"edges\": [{\"source\": \"소스노드ID\", \"target\": \"타겟노드ID\", \"reasoning\": \"이유\"}]
}

노드 타입:
- correct: 올바른 풀이 단계
- wrong: 오개념/실수 단계
- partial: 부분적으로 맞는 단계
- confused: 혼란 상태
- success: 최종 정답
- fail: 최종 오답
- start: 시작점

기존 노드와 연결될 수 있도록 source와 target을 설정해주세요.";

    // 문제 정보 (DB에서 가져온 정보 사용)
    $problemDesc = "";
    if (!empty($contentInfo['title'])) {
        $problemDesc .= "문제: " . $contentInfo['title'] . "\n";
    }
    if (!empty($contentInfo['questionText'])) {
        $problemDesc .= "문제 내용: " . substr($contentInfo['questionText'], 0, 500) . "\n";
    }
    if (!empty($contentInfo['answer'])) {
        $problemDesc .= "정답: " . $contentInfo['answer'] . "\n";
    }
    
    // 기존 노드 정보 추가
    if (!empty($existingNodes)) {
        $problemDesc .= "\n기존 인지맵 노드:\n";
        foreach ($existingNodes as $node) {
            $problemDesc .= "- [{$node['stage']}단계] {$node['label']} ({$node['type']}): {$node['description']}\n";
        }
    }
    
    // 기존 엣지 정보 추가
    if (!empty($existingEdges)) {
        $problemDesc .= "\n기존 연결:\n";
        foreach ($existingEdges as $edge) {
            $problemDesc .= "- {$edge[0]} → {$edge[1]}\n";
        }
    }
    
    $userPrompt = $problemDesc . "\n";
    
    switch ($requestType) {
        case 'new_solution':
            $userPrompt .= "기존 풀이와 다른 새로운 정답 경로를 제안해주세요. 기존 노드와 연결 가능한 형태로 제안해주세요.";
            break;
        case 'misconception':
            $userPrompt .= "학생들이 자주 하는 오개념/실수 경로를 제안해주세요. 어떤 노드에서 분기되어 오답으로 이어지는지 설명해주세요.";
            break;
        case 'custom_input':
            $userPrompt .= "사용자 입력 풀이:\n$userInput\n\n이 풀이를 분석하여 인지맵에 추가할 노드/엣지를 제안해주세요. 기존 노드와 연결 가능한 형태로 제안해주세요.";
            break;
    }
    
    return ['system' => $systemPrompt, 'user' => $userPrompt];
}

function callOpenAI($prompt) {
    $apiKey = OPENAI_API_KEY;
    
    if (empty($apiKey)) {
        error_log("[openai_proxy] API 키가 설정되지 않음 - Mock 응답 사용");
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
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("cURL 오류: $curlError");
    }
    
    if ($httpCode !== 200) {
        $errorBody = json_decode($response, true);
        $errorMsg = $errorBody['error']['message'] ?? "HTTP $httpCode";
        throw new Exception("OpenAI API 오류: $errorMsg");
    }
    
    return json_decode($response, true);
}

/**
 * API 키가 없을 때 사용하는 Mock 응답
 * (테스트/개발 환경용)
 */
function getMockResponse() {
    $mockSuggestion = [
        'title' => '[Mock] 새로운 풀이 제안',
        'description' => 'API 키가 설정되지 않아 테스트 데이터를 반환합니다. $CFG->openai_api_key를 설정해주세요.',
        'type' => 'new_path',
        'confidence' => 0.5,
        'nodes' => [
            [
                'node_id' => 'mock_node_' . time(),
                'label' => 'Mock 노드',
                'type' => 'partial',
                'stage' => 3,
                'x' => 620,
                'y' => 460,
                'description' => '테스트용 노드입니다',
                'reasoning' => 'API 키 미설정으로 인한 Mock 응답'
            ]
        ],
        'edges' => [
            [
                'source' => 'start',
                'target' => 'mock_node_' . time(),
                'reasoning' => '테스트 연결'
            ]
        ]
    ];
    
    return [
        'choices' => [['message' => ['content' => json_encode($mockSuggestion, JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['total_tokens' => 0]
    ];
}

function parseOpenAIResponse($response, $requestType) {
    $content = $response['choices'][0]['message']['content'] ?? '';
    $suggestion = json_decode($content, true);
    
    if (!$suggestion) {
        throw new Exception("제안 JSON 파싱 실패: " . $content);
    }
    
    if (empty($suggestion['type'])) {
        $suggestion['type'] = $requestType === 'misconception' ? 'misconception_path' : 'new_path';
    }
    
    return $suggestion;
}
