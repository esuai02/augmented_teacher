<?php
/**
 * 유기적 뉴런 배양 시스템 - AI 분석 API
 * 학생의 풀이를 분석하여 새로운 노드 데이터 생성
 *
 * DB: ktm_user_neurons
 * Fields: id, question_id, parent_node_id, title, summary, description,
 *         path_type (alternative/misconception/shortcut), tags (JSON),
 *         creator_id, creator_name, status (pending/verified/public),
 *         votes_count, created_at, verified_at
 */

require_once '/home/moodle/public_html/moodle/config.php';

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// 입력 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$parentNodeId = $input['parentNodeId'] ?? '';
$pathType = $input['pathType'] ?? 'alternative';
$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$questionId = $input['questionId'] ?? '';
$existingNodes = $input['existingNodes'] ?? [];
$userId = $USER->id ?? 0;
$userName = $USER->firstname ?? 'Guest';

// 유효성 검사
if (empty($title) || empty($description)) {
    echo json_encode(['success' => false, 'error' => '제목과 설명을 모두 입력해주세요.']);
    exit;
}

if (mb_strlen($title) < 3 || mb_strlen($title) > 50) {
    echo json_encode(['success' => false, 'error' => '제목은 3~50자 사이로 입력해주세요.']);
    exit;
}

if (mb_strlen($description) < 10) {
    echo json_encode(['success' => false, 'error' => '설명을 10자 이상 입력해주세요.']);
    exit;
}

// OpenAI API 호출
$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    // 환경변수 없으면 config에서 가져오기 시도
    global $CFG;
    $apiKey = $CFG->openai_api_key ?? '';
}

// 기존 노드들 문자열로 변환
$existingNodesText = '';
foreach ($existingNodes as $node) {
    $existingNodesText .= "- {$node['label']}: {$node['desc']}\n";
}

// AI 프롬프트
$systemPrompt = <<<PROMPT
당신은 수학 교육 전문가입니다. 학생이 제출한 풀이 방법을 분석하여 다음을 수행하세요:

1. **유사도 분석**: 기존 노드들과 비교하여 유사한 경로가 있는지 판단
2. **노드 데이터 생성**: 새로운 노드의 제목, 요약, 태그를 생성
3. **유형 분류**: 풀이 유형을 세분화 (정석형/직관형/계산형/분석형)

반드시 JSON 형식으로 응답하세요:
{
  "isSimilar": false,
  "similarNodeId": null,
  "similarityScore": 0.0,
  "node": {
    "title": "노드 제목 (간결하게)",
    "summary": "1-2문장 요약",
    "concepts": ["concept1", "concept2"],
    "learnerType": "직관형|정석형|계산형|분석형",
    "difficulty": 1-5,
    "isValid": true,
    "validationNote": "검증 코멘트"
  }
}
PROMPT;

$userPrompt = <<<PROMPT
## 기존 노드들
{$existingNodesText}

## 학생 제출 풀이
- 유형: {$pathType}
- 제목: {$title}
- 설명: {$description}

위 풀이를 분석하여 JSON으로 응답하세요.
PROMPT;

// OpenAI API 호출
$response = callOpenAI($apiKey, $systemPrompt, $userPrompt);

if ($response['success']) {
    $analysisResult = $response['data'];

    // DB에 저장
    try {
        global $DB, $CFG;

        // 직접 SQL INSERT 사용 (Moodle DB API 호환성 문제 우회)
        $nodeTitle = $analysisResult['node']['title'] ?? $title;
        $nodeSummary = $analysisResult['node']['summary'] ?? '';
        $tagsJson = json_encode([
            'concepts' => $analysisResult['node']['concepts'] ?? [],
            'learnerType' => $analysisResult['node']['learnerType'] ?? 'general',
            'difficulty' => $analysisResult['node']['difficulty'] ?? 3
        ]);
        $createdAt = time();

        $sql = "INSERT INTO {$CFG->prefix}ktm_user_neurons
                (question_id, parent_node_id, title, summary, description, path_type, tags, creator_id, creator_name, status, votes_count, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, ?)";

        $params = [
            $questionId,
            $parentNodeId,
            $nodeTitle,
            $nodeSummary,
            $description,
            $pathType,
            $tagsJson,
            $userId,
            $userName,
            $createdAt
        ];

        $DB->execute($sql, $params);
        $insertId = $DB->get_field_sql("SELECT LAST_INSERT_ID()");

        // 성공 응답
        echo json_encode([
            'success' => true,
            'isSimilar' => $analysisResult['isSimilar'] ?? false,
            'similarNode' => $analysisResult['similarNodeId'] ?? null,
            'node' => [
                'id' => 'user_' . $insertId,
                'dbId' => $insertId,
                'label' => $analysisResult['node']['title'] ?? $title,
                'desc' => $analysisResult['node']['summary'] ?? $description,
                'concepts' => $analysisResult['node']['concepts'] ?? [],
                'learnerType' => $analysisResult['node']['learnerType'] ?? 'general',
                'status' => 'pending',
                'creator' => $userName,
                'creatorId' => $userId,
                'isUserNode' => true
            ]
        ]);

    } catch (Exception $e) {
        error_log("[analyze_neuron_path.php] DB 오류: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'DB 저장 실패: ' . $e->getMessage()]);
    }

} else {
    // API 실패 시 기본값으로 처리
    echo json_encode([
        'success' => true,
        'isSimilar' => false,
        'node' => [
            'id' => 'user_' . time(),
            'label' => $title,
            'desc' => $description,
            'concepts' => [],
            'learnerType' => 'general',
            'status' => 'pending',
            'creator' => $userName,
            'creatorId' => $userId,
            'isUserNode' => true
        ],
        'warning' => 'AI 분석을 건너뛰었습니다.'
    ]);
}

/**
 * OpenAI API 호출
 */
function callOpenAI($apiKey, $systemPrompt, $userPrompt) {
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'API key not configured'];
    }

    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.7,
        'response_format' => ['type' => 'json_object']
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("[analyze_neuron_path.php] OpenAI API 오류: HTTP $httpCode - $response");
        return ['success' => false, 'error' => 'API call failed'];
    }

    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($content, true);

    if ($parsed) {
        return ['success' => true, 'data' => $parsed];
    }

    return ['success' => false, 'error' => 'Failed to parse response'];
}

/**
 * 테이블 존재 확인 (ktm_user_neurons)
 * 테이블은 create_user_neurons_table.php로 미리 생성되어야 함
 */
function ensureTableExists($DB) {
    global $CFG;
    $tableName = 'ktm_user_neurons';

    // 테이블 존재 여부 확인
    $checkSql = "SHOW TABLES LIKE '{$CFG->prefix}{$tableName}'";
    $exists = $DB->get_records_sql($checkSql);

    return !empty($exists);
}
