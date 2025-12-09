<?php
/**
 * 노드별 질문에 대한 답변 자동 생성 API
 *
 * @author AI Learning System
 * @created 2025-01-26
 * @file books/generate_node_answer.php
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

    $question = $input['question'] ?? '';
    $nodeContent = $input['nodeContent'] ?? '';
    $fullContext = $input['fullContext'] ?? '';
    $contentsid = intval($input['contentsid'] ?? 0);
    $contentstype = intval($input['contentstype'] ?? 0);
    $nstep = intval($input['nstep'] ?? 1);
    $nodeIndex = intval($input['nodeIndex'] ?? 0);
    $questionIndex = intval($input['questionIndex'] ?? 0);

    if (empty($question)) {
        throw new Exception('질문 내용 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    error_log(sprintf(
        '[generate_node_answer.php] File: %s, Line: %d, Generating answer for question: %s',
        basename(__FILE__),
        __LINE__,
        substr($question, 0, 50)
    ));

    // DB에서 기존 답변 확인
    $existing = $DB->get_record_sql(
        "SELECT answer
         FROM mdl_abrainalignment_node_answers
         WHERE contentsid = ? AND contentstype = ? AND nstep = ? AND node_index = ? AND question_index = ?
         ORDER BY id DESC LIMIT 1",
        [$contentsid, $contentstype, $nstep, $nodeIndex, $questionIndex]
    );

    if ($existing && !empty($existing->answer)) {
        // 기존 답변 사용
        error_log(sprintf(
            '[generate_node_answer.php] File: %s, Line: %d, Using existing answer from DB',
            basename(__FILE__),
            __LINE__
        ));

        echo json_encode([
            'success' => true,
            'answer' => $existing->answer,
            'source' => 'db'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // AI로 새로 생성
    $secret_key = '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';

    $systemPrompt = <<<PROMPT
당신은 친절한 수학 선생님입니다. 학생의 질문에 대해 명확하고 이해하기 쉽게 답변하세요.

**답변 원칙:**
1. **두괄식 구조**: 핵심 답을 먼저 제시
2. **구체적 설명**: 예시나 구체적 계산 과정 포함
3. **연결 고리**: 전체 문제 해결 과정과의 관계 설명
4. **수학 표기**: LaTeX 사용 (예: \\(x^2 + 1\\))

**길이**: 3~5문장 (100~200자)
PROMPT;

    $userPrompt = <<<PROMPT
**전체 사고 흐름:**
{$fullContext}

**현재 단계:**
{$nodeContent}

**학생 질문:**
{$question}

위 질문에 답변해주세요.
PROMPT;

    $apiData = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500
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

    $answer = trim($result['choices'][0]['message']['content']);

    // DB에 저장
    $record = new stdClass();
    $record->contentsid = $contentsid;
    $record->contentstype = $contentstype;
    $record->nstep = $nstep;
    $record->node_index = $nodeIndex;
    $record->question_index = $questionIndex;
    $record->question = $question;
    $record->answer = $answer;
    $record->timecreated = time();
    $record->timemodified = time();

    $DB->insert_record('abrainalignment_node_answers', $record);

    error_log(sprintf(
        '[generate_node_answer.php] File: %s, Line: %d, Generated answer length: %d',
        basename(__FILE__),
        __LINE__,
        strlen($answer)
    ));

    echo json_encode([
        'success' => true,
        'answer' => $answer,
        'source' => 'ai'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log(sprintf(
        '[generate_node_answer.php] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
