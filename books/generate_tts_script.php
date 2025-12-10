<?php
/**
 * GPT API를 사용하여 TTS 대사 생성
 * File: generate_tts_script.php
 * Location: /books/generate_tts_script.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // 입력 파라미터 확인
    if (!isset($_POST['contentsid']) || !isset($_POST['contentstype'])) {
        throw new Exception('필수 파라미터가 누락되었습니다. [파일: generate_tts_script.php, 위치: 입력 검증]');
    }

    $contentsid = $_POST['contentsid'];
    $contentstype = $_POST['contentstype'];

    // 디버깅 로그 추가
    error_log("[generate_tts_script.php] contentsid: " . $contentsid . ", contentstype: " . $contentstype);

    // 콘텐츠 내용 가져오기
    if ($contentstype == 2) {
        $content = $DB->get_record_sql(
            "SELECT questiontext FROM {question} WHERE id = ?",
            array($contentsid)
        );
        error_log("[generate_tts_script.php] Question 조회 결과: " . ($content ? "성공" : "실패"));
        $contentText = $content ? $content->questiontext : '';
    } else {
        $content = $DB->get_record_sql(
            "SELECT pagetext FROM {icontent_pages} WHERE id = ?",
            array($contentsid)
        );
        error_log("[generate_tts_script.php] iContent 조회 결과: " . ($content ? "성공" : "실패"));
        $contentText = $content ? $content->pagetext : '';
    }

    if (empty($contentText)) {
        $errorMsg = '콘텐츠 내용을 찾을 수 없습니다. ';
        $errorMsg .= 'contentsid=' . $contentsid . ', contentstype=' . $contentstype;
        $errorMsg .= ', 테이블=' . ($contentstype == 2 ? 'question' : 'icontent_pages');
        $errorMsg .= ' [파일: generate_tts_script.php, 위치: 콘텐츠 조회]';
        error_log("[generate_tts_script.php] " . $errorMsg);
        throw new Exception($errorMsg);
    }

    error_log("[generate_tts_script.php] 콘텐츠 길이: " . strlen($contentText));

    // HTML 태그 제거
    $contentText = strip_tags($contentText);

    // OpenAI API 키
    $apiKey = $CFG->openai_api_key;

    // GPT 프롬프트 구성
    $systemPrompt = "작은 의미단위로 완결성있게 설명 후 요약. 그리고 준비하는 시간을 가지고 준비가 되면 다음 소주제로 넘어 가는 방식으로 잘게 잘게 쪼개서 진행해줘. 작은 예시들을 통하여 확실히 확인하는 방식으로 진행해줘.

Role: act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.

Context:
- The expert needs to convert any mathematical content into a script that sounds natural when spoken in Korean, maintaining the sequence and coherence of the original content.
- The narrator is tasked with making the content understandable and engaging, using explanations, examples, and analogies, especially clarifying any potentially confusing parts.

Input Values:
- Mathematical text containing numbers, symbols, etc.

Instructions:
- Convert all numbers into their spoken Korean equivalents (e.g., 1 as 일, 2 as 이, etc.). 최종 결과물에는 한글만 존재해야하며 다른 기호나 숫자는 존재하지 않아야 합니다.
- Ensure all symbols, mathematical expressions, and alphabets are converted into their phonetic Korean readings.
- Maintain the logical sequence and coherence of the original mathematical content while transforming it into a narration script.
- Add explanatory notes, examples, or analogies to aid understanding, particularly clarifying any complex or confusing parts.
- Summarize each topic unit clearly, ensuring the script is engaging and understandable for a broad audience.
- Prepare the script for professional voice-over recording, ensuring it is suitable for educational video content.
- 그림이 추가되는 경우 그림을 대화의 뼈대로 하고 자세한 관찰과 연결을 토대로 단계별로 대화식으로 진행. 여러 단계에 걸쳐 자세하게 진행.. 디테일한 관찰과 묘사.
- 학생이 나레이션의 도움을 통하여 혼자 스스로 공부할 수 있도록 적절한 예시와 세밀한 표현을 통하여 학습을 유도해 주세요.
- 입력된 내용을 선생님과 학생의 대화형식으로 구성해줘. 특히, 학생은 헷갈리는 부분을 질문하며 다른 학생들이 대화를 들었을 때 도움이 되도록 해줘. 학생들이 컨텐츠를 보며 대화를 듣도록 제공된 내용에 대해 순서대로 읽으며 진행해줘

Guidelines:
- The script should be detailed enough for a professional voice actor to understand and perform without needing additional context.
- The language should be clear, professional, and accessible, suitable for a mathematics educator.
- Where necessary, include cues for intonation or emphasis to guide the voice-over artist.
- 생성결과에 아무리 간단한 경우라고 해도 반드시 숫자, 기호 대신 한글만 사용되어야 해.
- 마지막에는 학생 간단하게 내용을 요약하고 점검하는 멘트를 추가하고 후속학습을 추천해줘.

# 이것은 중요해!
- 어떤 생성결과도 한글만 사용해줘, 특수문자나 숫자, 기호 등은 절대로 사용하지 말아줘
- 분수읽을 때 오류 발생 주의 \$\\frac{3}{4} 는 사분의 삼이야. 그런데 종종 삼 사분의 삼이라고 잘못읽는 경우가 있어 조심해.
- : (콜론)은 학생과 선생님 뒤에만 나타나게해. 다른 상황에서 콜론을 사용하는 일은 절대 금지
- 결과 생성은 반드시 대화형식으로 자연스럽게 이어줘. 절대로 목록화(예시. - 목록1, - 목록2, - 목록3)를 하지마.

# 이것은 매우 중요해
- 숫자를 표현할 때 반드시 아라비아숫자 읽기 (일, 이, 삼, 사, .... ,이십, 이십일..)를 사용해줘
- 하나, 둘, 셋, 넷, 다섯, 여섯, 일곱, 여덟, 아홉, 열, 열하나 ... 스물 등과 같은 표현은 사용하지말아줘.
- 소숫점을 잘 식별해서 읽어줘 0.35 (영점삼오)
- 선생님과 학생 사이의 대화 전환이 있을 때만 줄바꿈이 가능해. 그렇지 않은 경우 반드시 하나의 단락을 유지해줘.
- 소주제나 목차나 목록형으로 생성 금지. 주어진 예시처럼 전체결과가 대화식이어야해. 단락 나누지마.

Output format: Plain text suitable for script reading.
 
Output examples:선생님: 오늘은 다항식의 인수정리와 이를 활용한 문제를 풀어볼 거예요. 준비됐나요?
학생: 네, 준비됐어요! 어떤 내용인지 궁금해요.
선생님: 좋아요. 먼저 이런 질문을 해볼게요. 다항식 이엑스 삼승 플러스 오엑스 제곱 플러스 에이엑스 플러스 비라는 다항식이 있어요. 그런데 이 다항식이 엑스 플러스 일과 엑스 플러스 삼이라는 인수를 가진다고 해요. 여기에서 상수 에이와 비의 값을 구하는 게 문제입니다.
학생: 음… 엑스 플러스 일과 엑스 플러스 삼을 인수로 가진다는 게 무슨 뜻이에요?
선생님: 좋은 질문이에요! 여기서 인수정리를 사용해요. 인수정리에 따르면, 다항식 에프엑스가 엑스 마이너스 알파라는 인수를 가진다면, 에프알파는 영이 됩니다. 즉, 이 인수를 넣으면 다항식 값이 영이 된다는 뜻이에요.
학생: 아, 그러니까 엑스 플러스 일이 인수라는 건 엑스에 마이너스 일을 넣으면 영이 된다는 뜻이군요!
선생님: 맞아요! 똑똑하네요. 이번 문제에서는 엑스 플러스 일과 엑스 플러스 삼이라는 두 개의 인수가 주어졌으니, 엑스에 마이너스 일과 마이너스 삼을 각각 대입했을 때 다항식 값이 영이 될 거예요.
학생: 아하, 그럼 마이너스 일을 넣었을 때와 마이너스 삼을 넣었을 때 각각 계산하면 되는 거군요?
선생님: 네, 정확해요. 이제 하나씩 계산해봅시다.
선생님: 다항식 에프엑스는 이엑스 삼승 플러스 오엑스 제곱 플러스 에이엑스 플러스 비라고 했죠? 먼저 엑스에 마이너스 일을 넣어볼게요. 에프 마이너스 일은 이렇게 계산됩니다.";

    $userPrompt = "다음 수학 콘텐츠를 선생님과 학생의 대화 형식으로 변환해주세요:\n\n" . $contentText;

    // OpenAI API 호출
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions'); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ));

    $data = array(
        'model' => 'gpt-4o',
        'messages' => array(
            array('role' => 'system', 'content' => $systemPrompt),
            array('role' => 'user', 'content' => $userPrompt)
        ),
        'temperature' => 0.0,
        'max_tokens' => 2000
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('CURL 오류: ' . curl_error($ch) . ' [파일: generate_tts_script.php, 위치: API 호출]');
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('OpenAI API 오류: HTTP ' . $httpCode . ' [파일: generate_tts_script.php, 위치: API 응답]');
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('API 응답 형식 오류 [파일: generate_tts_script.php, 위치: 응답 파싱]');
    }

    $generatedScript = $result['choices'][0]['message']['content'];

    // abrainalignment_gptresults 테이블에 저장
    $existingRecord = $DB->get_record_sql(
        "SELECT id FROM {abrainalignment_gptresults}
         WHERE type = 'conversation' AND contentsid = ? AND contentstype = ?
         ORDER BY id DESC LIMIT 1",
        array($contentsid, $contentstype)
    );

    $timecreated = time();

    if ($existingRecord && $existingRecord->id) {
        // 기존 레코드 업데이트
        $updateRecord = new stdClass();
        $updateRecord->id = $existingRecord->id;
        $updateRecord->outputtext = $generatedScript;
        $updateRecord->timemodified = $timecreated;

        $DB->update_record('abrainalignment_gptresults', $updateRecord);
    } else {
        // 새 레코드 생성
        $newRecord = new stdClass();
        $newRecord->type = 'conversation';
        $newRecord->contentsid = $contentsid;
        $newRecord->contentstype = $contentstype;
        $newRecord->gid = '71280';
        $newRecord->outputtext = $generatedScript;
        $newRecord->timecreated = $timecreated;
        $newRecord->timemodified = $timecreated;

        $DB->insert_record('abrainalignment_gptresults', $newRecord);
    }

    // 성공 응답
    echo json_encode(array(
        'success' => true,
        'script' => $generatedScript,
        'message' => 'TTS 대사가 성공적으로 생성되었습니다.'
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // 오류 응답
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
?>
