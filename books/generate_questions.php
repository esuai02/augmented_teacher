<?php
/**
 * File: generate_questions.php
 * Purpose: 2단계 - 보충질문 3개 생성
 * Location: /mnt/c/1 Project/augmented_teacher/books/generate_questions.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

header('Content-Type: application/json');

try {
    // POST 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log(sprintf(
        '[generate_questions.php] File: %s, Line: %d, Request received',
        basename(__FILE__),
        __LINE__
    ));

    if (!isset($data['context'])) {
        throw new Exception('컨텍스트가 누락되었습니다.');
    }

    $context = $data['context'];
    $subtitle = isset($data['subtitle']) ? $data['subtitle'] : '';
    $thinking = isset($data['thinking']) ? $data['thinking'] : '';
    $contentsid = isset($data['contentsid']) ? $data['contentsid'] : '';
    $contentstype = isset($data['contentstype']) ? $data['contentstype'] : '';
    $nstep = isset($data['nstep']) ? intval($data['nstep']) : 0;

    // API 키를 $CFG에서 가져오기
    $secret_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
    if (empty($secret_key)) {
        throw new Exception('API 키가 설정되지 않았습니다. (generate_questions.php)');
    }

    // 프롬프트 구성 (2단계: 보충질문 3개 생성)
    $prompt = "전체 대본 내용:\n{$context}\n\n";

    if (!empty($subtitle)) {
        $prompt .= "현재 구간 내용:\n{$subtitle}\n\n";
    }

    if (!empty($thinking)) {
        $prompt .= "자세히 생각하기 내용:\n{$thinking}\n\n";
    }

    $prompt .= "위 내용을 바탕으로 학생들이 더 깊이 생각해볼 수 있는 보충질문 3개를 제시해줘.\n\n";
    $prompt .= "**중요 요구사항:**\n";
    $prompt .= "- 현재 내용의 절차와 구조를 파고드는 질문\n";
    $prompt .= "- 다른 영역으로 확장하지 말고 현재 부분에만 집중\n";
    $prompt .= "- 각 질문은 한 줄로 간결하게\n";
    $prompt .= "- 응답 형식: 각 줄에 하나씩, 번호 없이 질문만 작성";

    // OpenAI API 호출
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret_key
    ));

    $requestData = array(
        'model' => 'gpt-4o-mini',
        'messages' => array(
            array(
                'role' => 'system',
                'content' => '당신은 수학 교육 전문가입니다. 학생들이 현재 학습하고 있는 내용을 깊이 있게 이해할 수 있도록 보충질문을 제시합니다.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        ),
        'max_tokens' => 300,
        'temperature' => 0.7
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    error_log(sprintf(
        '[generate_questions.php] File: %s, Line: %d, Calling OpenAI API',
        basename(__FILE__),
        __LINE__
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('API 호출 실패: ' . $error);
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('API 응답 오류: HTTP ' . $httpCode);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('유효하지 않은 API 응답');
    }

    $fullContent = trim($result['choices'][0]['message']['content']);

    // 질문 추출 (줄 단위로 분리)
    $questionLines = array_filter(
        array_map('trim', explode("\n", $fullContent)),
        function($line) { return !empty($line); }
    );

    $questions = array();
    foreach ($questionLines as $line) {
        // 번호 제거 (1., 2., 3. 등)
        $cleaned = preg_replace('/^\d+[\.\)]\s*/', '', $line);
        if (!empty($cleaned)) {
            $questions[] = $cleaned;
        }
    }

    // 정확히 3개만 사용
    $questions = array_slice($questions, 0, 3);

    // 3개 미만이면 기본 질문으로 채우기
    $defaultQuestions = array(
        '이 내용의 핵심 개념은 무엇인가요?',
        '이 절차를 단계별로 설명하면 어떻게 되나요?',
        '이 구조를 다르게 접근할 수 있는 방법은 무엇인가요?'
    );

    while (count($questions) < 3) {
        $questions[] = $defaultQuestions[count($questions)];
    }

    // DB 업데이트 (qstn1-3 저장)
    if (!empty($contentsid) && !empty($contentstype)) {
        try {
            $existingRecord = $DB->get_record('abessi_tailoredcontents', array(
                'contentsid' => $contentsid,
                'contentstype' => $contentstype,
                'nstep' => $nstep
            ));

            if ($existingRecord) {
                $record = new stdClass();
                $record->id = $existingRecord->id;
                $record->qstn1 = $questions[0];
                $record->qstn2 = $questions[1];
                $record->qstn3 = $questions[2];
                $record->timemodified = time();

                $DB->update_record('abessi_tailoredcontents', $record);

                error_log(sprintf(
                    '[generate_questions.php] File: %s, Line: %d, Questions saved to DB',
                    basename(__FILE__),
                    __LINE__
                ));
            }
        } catch (Exception $e) {
            error_log(sprintf(
                '[generate_questions.php] File: %s, Line: %d, DB save failed: %s',
                basename(__FILE__),
                __LINE__,
                $e->getMessage()
            ));
        }
    }

    // 성공 응답
    echo json_encode(array(
        'success' => true,
        'questions' => $questions
    ), JSON_UNESCAPED_UNICODE);

    error_log(sprintf(
        '[generate_questions.php] File: %s, Line: %d, Questions generated successfully',
        basename(__FILE__),
        __LINE__
    ));

} catch (Exception $e) {
    error_log(sprintf(
        '[generate_questions.php] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
