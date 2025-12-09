<?php
/**
 * File: regenerate_single_answer.php
 * Purpose: 개별 답변 다시 생성
 * Location: /mnt/c/1 Project/augmented_teacher/books/regenerate_single_answer.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // POST 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log(sprintf(
        '[regenerate_single_answer.php] File: %s, Line: %d, Request received',
        basename(__FILE__),
        __LINE__
    ));

    if (!isset($data['context']) || !isset($data['answerIndex'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $context = $data['context'];
    $subtitle = isset($data['subtitle']) ? $data['subtitle'] : '';
    $contentsid = isset($data['contentsid']) ? $data['contentsid'] : '';
    $contentstype = isset($data['contentstype']) ? $data['contentstype'] : '';
    $nstep = isset($data['nstep']) ? intval($data['nstep']) : 0;
    $answerIndex = intval($data['answerIndex']);

    // DB에서 기존 레코드 확인하여 질문 가져오기
    $existingRecord = $DB->get_record('abessi_tailoredcontents', array(
        'contentsid' => $contentsid,
        'contentstype' => $contentstype,
        'nstep' => $nstep
    ));

    if (!$existingRecord) {
        throw new Exception('해당 레코드를 찾을 수 없습니다.');
    }

    // 질문 가져오기
    $questionField = 'qstn' . ($answerIndex + 1);
    $question = $existingRecord->$questionField;

    // OpenAI API 키
    $secret_key = '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';

    // 프롬프트 구성
    $prompt = "전체 대본 내용:\n{$context}\n\n";

    if (!empty($subtitle)) {
        $prompt .= "현재 구간 내용:\n{$subtitle}\n\n";
    }

    $prompt .= "질문: {$question}\n\n";
    $prompt .= "**사고 추적 형식 (반드시 준수):**\n";
    $prompt .= "1. 결론 먼저: ∴ [핵심 결과]\n";
    $prompt .= "2. 사고 흐름: 기호와 화살표로 단계별 추론\n";
    $prompt .= "   - 조건/가정 → 추론 → 결과\n";
    $prompt .= "   - ∵ (왜냐하면), ∴ (따라서), ⇒ (이면), ⇔ (필요충분)\n";
    $prompt .= "   - 예: 조건 A ∵ [이유] ⇒ 과정 B ⇒ 과정 C ∴ 결론 D\n";
    $prompt .= "3. 한글 최소화, 수식·기호 최대화\n";
    $prompt .= "4. 각 단계마다 화살표(→, ⇒, ⇔)로 논리 흐름 명시";

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
                'content' => '수학적 사고 추적 전문가. 논리 흐름을 기호와 화살표로 시각화.

**표기법:**
- 인라인: \\(수식\\) | 디스플레이: \\[수식\\]
- 금지: $, $$, \\begin{}, \\end{}
- 논리: ∵(이유) ∴(결론) ⇒(함의) ⇔(동치) →(흐름)
- 연산: \\times, \\div, \\pm, \\neq, \\leq, \\geq, \\in, \\subset
- 분수: \\frac{a}{b} | 첨자: x^2, a_n | 그리스: \\alpha, \\beta, \\theta

**응답 구조:**
∴ 결론
├─ 조건1 ∵ [이유]
├─ 조건2 ⇒ 중간결과
└─ 최종 ∴ [증명]

**예시:**
\\(ax^2 + bx + c = 0\\) ⇒ 완전제곱 변형
⇒ \\(a(x + \\frac{b}{2a})^2 = \\frac{b^2-4ac}{4a}\\)
∴ \\[x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}\\]'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        ),
        'max_tokens' => 1000,
        'temperature' => 0.7
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    error_log(sprintf(
        '[regenerate_single_answer.php] File: %s, Line: %d, Calling OpenAI API for answer %d',
        basename(__FILE__),
        __LINE__,
        $answerIndex
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

    $newAnswer = trim($result['choices'][0]['message']['content']);

    // DB 업데이트
    $answerField = 'ans' . ($answerIndex + 1);

    $record = new stdClass();
    $record->id = $existingRecord->id;
    $record->$answerField = $newAnswer;
    $record->timemodified = time();

    $DB->update_record('abessi_tailoredcontents', $record);

    error_log(sprintf(
        '[regenerate_single_answer.php] File: %s, Line: %d, Answer %d regenerated successfully',
        basename(__FILE__),
        __LINE__,
        $answerIndex
    ));

    // 성공 응답
    echo json_encode(array(
        'success' => true,
        'answer' => $newAnswer
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log(sprintf(
        '[regenerate_single_answer.php] File: %s, Line: %d, Error: %s',
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
