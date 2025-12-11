<?php
/**
 * File: get_additional_answer.php
 * Purpose: 추가 질문에 대한 AI 답변 생성
 * Location: /mnt/c/1 Project/augmented_teacher/books/get_additional_answer.php
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
        '[get_additional_answer.php] File: %s, Line: %d, Request received: %s',
        basename(__FILE__),
        __LINE__,
        substr($input, 0, 200)
    ));

    if (!isset($data['question']) || !isset($data['context'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $question = $data['question'];
    $context = $data['context'];
    $contentsid = isset($data['contentsid']) ? $data['contentsid'] : '';
    $contentstype = isset($data['contentstype']) ? $data['contentstype'] : '';
    $nstep = isset($data['nstep']) ? intval($data['nstep']) : 0;
    $questionNum = isset($data['questionNum']) ? intval($data['questionNum']) : 0;

    // API 키를 $CFG에서 가져오기
    $secret_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
    if (empty($secret_key)) {
        throw new Exception('API 키가 설정되지 않았습니다. (get_additional_answer.php)');
    }

    // OpenAI API 호출을 위한 프롬프트 구성
    $prompt = "다음은 수학 문제에 대한 설명입니다:\n\n{$context}\n\n학생의 질문: {$question}\n\n위 질문에 대해 친절하고 명확하게 답변해주세요. 학생이 이해하기 쉽게 설명해주세요.";

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
                'content' => '당신은 친절하고 명확한 설명을 제공하는 수학 선생님입니다. 학생들이 이해하기 쉽게 설명해주세요.

**수식 표기법 규칙 (반드시 준수):**
1. 인라인 수식: \\( 수식 \\) 형태만 사용 (예: \\(x^2 + 1\\))
2. 디스플레이 수식: \\[ 수식 \\] 형태만 사용 (예: \\[\\frac{a}{b}\\])
3. 절대 사용 금지: $, $$, \begin{}, \end{}
4. 분수: \\frac{분자}{분모}
5. 제곱/첨자: x^2, x_n
6. 그리스 문자: \\alpha, \\beta, \\theta 등
7. 연산자: \\times, \\div, \\pm, \\neq, \\leq, \\geq
8. 예시: "이차방정식 \\(ax^2 + bx + c = 0\\)의 해는 \\[x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}\\]입니다."'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        ),
        'max_tokens' => 500,
        'temperature' => 0.7
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    error_log(sprintf(
        '[get_additional_answer.php] File: %s, Line: %d, Calling OpenAI API',
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

    error_log(sprintf(
        '[get_additional_answer.php] File: %s, Line: %d, API Response Code: %d',
        basename(__FILE__),
        __LINE__,
        $httpCode
    ));

    if ($httpCode !== 200) {
        throw new Exception('API 응답 오류: HTTP ' . $httpCode);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        error_log(sprintf(
            '[get_additional_answer.php] File: %s, Line: %d, Invalid API response: %s',
            basename(__FILE__),
            __LINE__,
            substr($response, 0, 500)
        ));
        throw new Exception('유효하지 않은 API 응답');
    }

    $answer = trim($result['choices'][0]['message']['content']);

    // DB에 답변 저장 (mdl_abessi_tailoredcontents)
    if (!empty($contentsid) && !empty($contentstype) && $questionNum >= 0) {
        try {
            // 기존 레코드 확인
            $existingRecord = $DB->get_record('abessi_tailoredcontents', array(
                'contentsid' => $contentsid,
                'contentstype' => $contentstype,
                'nstep' => $nstep
            ));

            if ($existingRecord) {
                // 답변 필드 업데이트
                $ansField = 'ans' . $questionNum;
                $existingRecord->$ansField = $answer;
                $existingRecord->timemodified = time();

                $DB->update_record('abessi_tailoredcontents', $existingRecord);

                error_log(sprintf(
                    '[get_additional_answer.php] File: %s, Line: %d, Updated %s for record id=%d',
                    basename(__FILE__),
                    __LINE__,
                    $ansField,
                    $existingRecord->id
                ));
            } else {
                // 레코드가 없으면 에러 로그 (정상적인 상황에서는 발생하지 않아야 함)
                error_log(sprintf(
                    '[get_additional_answer.php] File: %s, Line: %d, No existing record found for contentsid=%s, nstep=%d',
                    basename(__FILE__),
                    __LINE__,
                    $contentsid,
                    $nstep
                ));
            }
        } catch (Exception $e) {
            // DB 저장 실패해도 답변은 반환
            error_log(sprintf(
                '[get_additional_answer.php] File: %s, Line: %d, DB save failed: %s',
                basename(__FILE__),
                __LINE__,
                $e->getMessage()
            ));
        }
    }

    // 성공 응답
    echo json_encode(array(
        'success' => true,
        'answer' => $answer
    ), JSON_UNESCAPED_UNICODE);

    error_log(sprintf(
        '[get_additional_answer.php] File: %s, Line: %d, Answer generated successfully',
        basename(__FILE__),
        __LINE__
    ));

} catch (Exception $e) {
    error_log(sprintf(
        '[get_additional_answer.php] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));

    // 에러 응답
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
