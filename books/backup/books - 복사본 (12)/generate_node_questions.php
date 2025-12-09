<?php
/**
 * 사고 흐름도 노드별 학생 질문 자동 생성 API
 *
 * @author AI Learning System
 * @created 2025-01-26
 * @file books/generate_node_questions.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // 입력 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('입력 데이터 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    $nodeContent = $input['nodeContent'] ?? '';
    $nodeType = $input['nodeType'] ?? 'premise'; // premise, step, conclusion
    $fullContext = $input['fullContext'] ?? '';
    $contentsid = intval($input['contentsid'] ?? 0);
    $contentstype = intval($input['contentstype'] ?? 0);
    $nstep = intval($input['nstep'] ?? 1);
    $nodeIndex = intval($input['nodeIndex'] ?? 0);

    if (empty($nodeContent)) {
        throw new Exception('노드 내용 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    error_log(sprintf(
        '[generate_node_questions.php] File: %s, Line: %d, Generating questions for node %d (type: %s)',
        basename(__FILE__),
        __LINE__,
        $nodeIndex,
        $nodeType
    ));

    // DB에서 기존 질문 확인
    $existing = null;
    try {
        $existing = $DB->get_record_sql(
            "SELECT questions_json
             FROM mdl_abrainalignment_node_questions
             WHERE contentsid = ? AND contentstype = ? AND nstep = ? AND node_index = ?
             ORDER BY id DESC LIMIT 1",
            [$contentsid, $contentstype, $nstep, $nodeIndex]
        );
    } catch (Exception $dbError) {
        error_log(sprintf(
            '[generate_node_questions.php] File: %s, Line: %d, DB read error (table may not exist): %s',
            basename(__FILE__),
            __LINE__,
            $dbError->getMessage()
        ));
        // 테이블이 없어도 계속 진행 (새로 생성)
        $existing = null;
    }

    if ($existing && !empty($existing->questions_json)) {
        // 기존 질문 사용
        $questionsData = json_decode($existing->questions_json, true);

        error_log(sprintf(
            '[generate_node_questions.php] File: %s, Line: %d, Using existing questions from DB',
            basename(__FILE__),
            __LINE__
        ));

        echo json_encode([
            'success' => true,
            'questions' => $questionsData['questions'] ?? [],
            'source' => 'db'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // AI로 새로 생성
    $secret_key = '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';

    // 노드 타입별 질문 유형 결정
    $questionTypes = [
        'premise' => '전제조건이나 개념에 대한 이해를 확인하는 질문',
        'step' => '계산 과정이나 적용 방법에 대한 실행 질문',
        'conclusion' => '결과 검증이나 확장 적용에 대한 질문'
    ];

    $questionGuide = $questionTypes[$nodeType] ?? '일반적인 이해 질문';

    $systemPrompt = <<<PROMPT
당신은 수학 교육 전문가입니다. 학생들이 특정 사고 단계에서 자연스럽게 가질 수 있는 질문 2~3개를 생성하세요.

**질문 생성 원칙:**
1. 학생 입장에서 자연스럽게 떠오르는 의문점
2. "왜 이렇게 하나요?", "이게 무슨 뜻인가요?", "다른 방법은 없나요?" 같은 실제 질문 형태
3. {$questionGuide}

**출력 형식 (JSON):**
{
    "questions": [
        "질문 1",
        "질문 2",
        "질문 3"
    ]
}

**중요:** 반드시 JSON 형식만 출력하고, 다른 설명은 추가하지 마세요.
PROMPT;

    $userPrompt = <<<PROMPT
**사고 흐름도 전체 맥락:**
{$fullContext}

**현재 노드 내용 (타입: {$nodeType}):**
{$nodeContent}

위 내용에서 학생들이 가질 수 있는 질문 2~3개를 생성해주세요.
PROMPT;

    $apiData = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 800
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret_key
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("OpenAI API 오류 (HTTP {$httpCode}) - File: " . basename(__FILE__) . ", Line: " . __LINE__);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('API 응답 형식 오류 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    $aiResponse = $result['choices'][0]['message']['content'];

    // JSON 추출 (코드 블록 제거)
    $aiResponse = preg_replace('/```json\s*|\s*```/', '', $aiResponse);
    $aiResponse = trim($aiResponse);

    $questionsData = json_decode($aiResponse, true);

    if (!$questionsData || !isset($questionsData['questions'])) {
        throw new Exception('질문 파싱 실패 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    // DB에 저장 (테이블이 없으면 스킵)
    try {
        $record = new stdClass();
        $record->contentsid = $contentsid;
        $record->contentstype = $contentstype;
        $record->nstep = $nstep;
        $record->node_index = $nodeIndex;
        $record->node_content = $nodeContent;
        $record->node_type = $nodeType;
        $record->questions_json = json_encode($questionsData, JSON_UNESCAPED_UNICODE);
        $record->timecreated = time();
        $record->timemodified = time();

        $DB->insert_record('abrainalignment_node_questions', $record);
    } catch (Exception $dbError) {
        error_log(sprintf(
            '[generate_node_questions.php] File: %s, Line: %d, DB save error (table may not exist): %s',
            basename(__FILE__),
            __LINE__,
            $dbError->getMessage()
        ));
        // 저장 실패해도 질문은 반환
    }

    error_log(sprintf(
        '[generate_node_questions.php] File: %s, Line: %d, Generated %d questions for node %d',
        basename(__FILE__),
        __LINE__,
        count($questionsData['questions']),
        $nodeIndex
    ));

    echo json_encode([
        'success' => true,
        'questions' => $questionsData['questions'],
        'source' => 'ai'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $errorDetails = sprintf(
        'Error: %s (File: %s, Line: %s)',
        $e->getMessage(),
        basename($e->getFile()),
        $e->getLine()
    );

    error_log(sprintf(
        '[generate_node_questions.php] File: %s, Line: %d, %s, Stack: %s',
        basename(__FILE__),
        __LINE__,
        $errorDetails,
        $e->getTraceAsString()
    ));

    echo json_encode([
        'success' => false,
        'error' => $errorDetails
    ], JSON_UNESCAPED_UNICODE);
}
