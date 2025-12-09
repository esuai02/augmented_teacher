<?php
/////////////////////////////// 수업 엿듣기 나레이션 자동 생성 API ///////////////////////////////
// 파일: /mnt/c/1 Project/augmented_teacher/books/generate_classroom_narration.php
// 설명: 수업 엿듣기 전용 나레이션 및 TTS 자동 생성 API
// 작성일: 2025-01-24

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/classroom_narration_error.log');

// 메모리 및 실행시간 제한 증가
ini_set('memory_limit', '512M');
set_time_limit(180); // 3분

// JSON 헤더 즉시 설정
header('Content-Type: application/json; charset=utf-8');

try {
    // 설정 파일 포함
    if (!file_exists(dirname(__FILE__) . '/api_config.php')) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 설정 파일을 찾을 수 없습니다.");
    }
    require_once(dirname(__FILE__) . '/api_config.php');

    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;

    // 디버그 로깅 함수
    function debug_log($message) {
        $logMessage = "[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] " . $message;
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            error_log("[CLASSROOM_NARRATION] " . date('Y-m-d H:i:s') . " - " . $logMessage);
        }
    }

    debug_log("수업 엿듣기 나레이션 생성 시작");

    // 파라미터 받기 및 검증
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;
    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;
    $regenerate = isset($_POST['regenerate']) ? $_POST['regenerate'] === 'true' : false;
    $timecreated = time();

    debug_log("파라미터 - contentsid: $contentsid, contentstype: $contentstype, regenerate: " . ($regenerate ? 'true' : 'false'));

    if ($contentsid <= 0) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] 유효하지 않은 콘텐츠 ID입니다.");
    }

    // 로그인 체크
    require_login();

    // 권한 체크
    $userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = ? ORDER BY id DESC LIMIT 1",
        array($USER->id, 22));
    $role = isset($userrole->data) ? $userrole->data : '';

    // 응답 초기화
    $response = [
        'success' => false,
        'message' => '',
        'step' => 'init',
        'narration' => '',
        'audioUrl' => ''
    ];

    // 콘텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM {icontent_pages} WHERE id = ? ORDER BY id DESC LIMIT 1", array($contentsid));

    if (!$cnttext) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] 콘텐츠를 찾을 수 없습니다.");
    }

    // 재생성 모드가 아닌 경우 기존 오디오 확인
    if (!$regenerate && !empty($cnttext->audiourl)) {
        debug_log("이미 audiourl이 존재하고 재생성 모드가 아니므로 기존 URL 반환");
        $response['success'] = true;
        $response['message'] = '이미 수업 엿듣기 컨텐츠가 존재합니다.';
        $response['audioUrl'] = $cnttext->audiourl;
        $response['alreadyExists'] = true;
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Step 1: 나레이션 생성
    $response['step'] = 'generating_narration';
    debug_log("Step 1: 나레이션 생성 시작");

    // 콘텐츠 텍스트 준비
    $maintext = strip_tags($cnttext->maintext); // HTML 태그 제거
    $title = $cnttext->title;

    // 수업 엿듣기 전용 프롬프트 (사용자 제공)
    $systemPrompt = "Role:
act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.

Context:
- The expert needs to convert any mathematical content into a script that sounds natural when spoken in Korean, maintaining the sequence and coherence of the original content.
- The narrator is tasked with making the content understandable and engaging, using explanations, examples, and analogies, especially clarifying any potentially confusing parts.

Input Values:
- Mathematical text containing numbers, symbols, etc.

Instructions:
- Convert all numbers into their spoken Korean equivalents (e.g., 1 as 일, 2 as 이, etc.) . 최종 결과물에는 한글만 존재해야하며 다른 기호나 숫자는 존재하지 않아야 합니다.
- Ensure all symbols, mathematical expressions, and alphabets are converted into their phonetic Korean readings.
- Maintain the logical sequence and coherence of the original mathematical content while transforming it into a narration script.
- Add explanatory notes, examples, or analogies to aid understanding, particularly clarifying any complex or confusing parts.
- Summarize each topic unit clearly, ensuring the script is engaging and understandable for a broad audience.
- Prepare the script for professional voice-over recording, ensuring it is suitable for educational video content.
- 그림이 추가되는 경우 그림을 대화의 뼈대로 하고 자세한 관찰과 연결을 토대로 단계별로 대화식으로 진행. 여러 단계에 걸쳐 자세하게 진행.. 디테일한 관찰과 묘사.
- 학생이 나레이션의 도움을 통하여 혼자 스스로 공부할 수 있도록 적절한 예시와 세밀한 표현을 통하여 학습을 유도해 주세요.
- 대화식을 요청하면 입력된 내용을 선생님과 학생의 대화형식으로 구성해줘. 특히, 학생은 헷갈리는 부분을 질문하며 다른 학생들이 대화를 들었을 때 도움이 되도록 해줘. 학생들이 컨텐츠를 보며 대화를 듣도록 제공된 내용에 대해 순서대로 읽으며 진행해줘

Guidelines:
- The script should be detailed enough for a professional voice actor to understand and perform without needing additional context.
- The language should be clear, professional, and accessible, suitable for a mathematics educator.
- Where necessary, include cues for intonation or emphasis to guide the voice-over artist.
- 생성결과에 아무리 간단한 경우라고 해도 반드시 숫자, 기호 대신 한글만 사용되어야 해.
- 마지막에는 학생 간단하게 내용을 요약하고 점검하는 멘트를 추가하고 후속학습을 추천해줘.

Output format:
- Plain text suitable for script reading.

Output fields:
- Detailed narration script including numbers, explanations, examples, and any additional notes for clarity

Output examples:
선생님: 자, 문제를 한번 자세히 봅시다.
엑스는 \"삼의 엔제곱 빼기 삼의 마이너스 엔제곱\"을 이로 나눈 값이라고 주어졌어요. 이걸 먼저 식으로 읽어 보면, 엑스는 \"삼의 엔제곱 마이너스 삼의 마이너스 엔제곱을 이로 나눈 값\"이 됩니다. 여기까지 괜찮나요?
학생: 네! 엑스에 대한 식은 이해했어요. 그런데 이걸 가지고 뭘 해야 하는 거죠?
선생님: 좋아요! 이제 문제에서 우리가 궁금해하는 건, 이 엑스를 이용해서 \"루트 투 엔 승의 엑스 플러스 루트 일 플러스 엑스의 제곱\"의 값을 구하는 거예요. 이게 복잡해 보이지만 차근차근 풀어 나가면 단순해집니다.

# 이것은 중요해 !
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

프롬프트 시작 부분에 추가할 필수 문장:
작은 의미단위로 완결성있게 설명 후 요약. 그리고 준비하는 시간을 가지고 준비가 되면 다음 소주제로 넘어 가는 방식으로 잘게 잘게 쪼개서 진행해줘. 작은 예시들을 통하여 확실히 확인하는 방식으로 진행해줘.";

    $userPrompt = "작은 의미단위로 완결성있게 설명 후 요약. 그리고 준비하는 시간을 가지고 준비가 되면 다음 소주제로 넘어 가는 방식으로 잘게 잘게 쪼개서 진행해줘. 작은 예시들을 통하여 확실히 확인하는 방식으로 진행해줘.

다음 수학 콘텐츠를 수업 엿듣기용 나레이션 대본으로 변환해주세요:

제목: $title

내용:
$maintext";

    // OpenAI API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 키가 설정되지 않았습니다.");
    }

    // API 요청 데이터
    $requestData = [
        'model' => GPT_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_tokens' => GPT_MAX_TOKENS,
        'temperature' => GPT_TEMPERATURE
    ];

    debug_log("GPT API 요청 준비 완료");

    // cURL 설정
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    // API 호출
    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 호출 오류: " . $curlError);
    }

    debug_log("GPT API 응답 코드: " . $httpCode);

    // HTTP 상태 코드별 상세 에러 처리
    if ($httpCode === 401) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 키가 유효하지 않습니다. 관리자에게 문의하세요.");
    } elseif ($httpCode === 429) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 사용량 한도를 초과했습니다. 잠시 후 다시 시도해주세요.");
    } elseif ($httpCode === 500) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] OpenAI 서버 오류입니다. 잠시 후 다시 시도해주세요.");
    } elseif ($httpCode !== 200) {
        $errorData = json_decode($apiResponse, true);
        $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : "알 수 없는 오류";
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 오류 (HTTP $httpCode): " . $errorMsg);
    }

    // API 응답 파싱
    $apiData = json_decode($apiResponse, true);

    if (!isset($apiData['choices'][0]['message']['content'])) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] API 응답 형식 오류");
    }

    $narration = $apiData['choices'][0]['message']['content'];
    debug_log("나레이션 생성 완료. 길이: " . strlen($narration));

    // DB에 나레이션 저장 (type='conversation', audiourl 필드용)
    $existingRecord = $DB->get_record_sql(
        "SELECT id FROM {abrainalignment_gptresults} WHERE type = ? AND contentsid = ? AND contentstype = ? ORDER BY id DESC LIMIT 1",
        array('conversation', $contentsid, $contentstype)
    );

    if ($existingRecord) {
        // 업데이트
        $record = new stdClass();
        $record->id = $existingRecord->id;
        $record->outputtext = $narration;
        $record->timemodified = $timecreated;
        $DB->update_record('abrainalignment_gptresults', $record);
        debug_log("기존 나레이션 레코드 업데이트 완료. ID: " . $existingRecord->id);
    } else {
        // 새 레코드 삽입
        $newrecord = new stdClass();
        $newrecord->type = "conversation";
        $newrecord->contentsid = $contentsid;
        $newrecord->contentstype = $contentstype;
        $newrecord->gid = '71280';
        $newrecord->outputtext = $narration;
        $newrecord->timemodified = $timecreated;
        $newrecord->timecreated = $timecreated;
        $insertedId = $DB->insert_record('abrainalignment_gptresults', $newrecord);
        debug_log("새 나레이션 레코드 생성 완료. ID: " . $insertedId);
    }

    $response['narration'] = substr($narration, 0, 100) . '...'; // 미리보기용

    // Step 2: TTS 생성
    $response['step'] = 'generating_tts';
    debug_log("Step 2: TTS 생성 시작");

    // TTS 생성 데이터
    $ttsData = [
        'model' => TTS_MODEL,
        'voice' => TTS_VOICE,
        'input' => $narration
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/audio/speech',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($ttsData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_TIMEOUT => TTS_API_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $audioResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] TTS 생성 오류: " . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] TTS API 오류: HTTP " . $httpCode);
    }

    debug_log("TTS 생성 완료. 응답 크기: " . strlen($audioResponse) . " bytes");

    // 오디오 파일 저장
    $uploadPath = '/home/moodle/public_html/audiofiles/';
    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] 오디오 디렉토리 생성 실패: " . $uploadPath);
        }
    }

    $filename = 'cid' . $contentsid . 'ct' . $contentstype . '_classroom.mp3';
    $filepath = $uploadPath . $filename;

    debug_log("오디오 파일 저장 경로: " . $filepath);

    if (file_put_contents($filepath, $audioResponse) === false) {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] 오디오 파일 저장 실패: " . $filepath);
    }

    // 파일 권한 설정
    chmod($filepath, 0644);
    debug_log("오디오 파일 저장 완료");

    // 오디오 URL 생성
    $audioUrl = 'https://mathking.kr/audiofiles/' . $filename;
    debug_log("오디오 URL 생성: " . $audioUrl);

    // Step 3: DB 업데이트 (audiourl 필드)
    $response['step'] = 'updating_db';
    debug_log("Step 3: DB 업데이트 시작");

    $updateData = new stdClass();
    $updateData->id = $contentsid;
    $updateData->audiourl = $audioUrl;
    $updateData->timemodified = $timecreated;

    if ($DB->update_record('icontent_pages', $updateData)) {
        debug_log("DB 업데이트 완료 (audiourl)");
        $response['success'] = true;
        $response['message'] = '수업 엿듣기 컨텐츠가 성공적으로 생성되었습니다.';
        $response['audioUrl'] = $audioUrl;
        $response['step'] = 'completed';
    } else {
        throw new Exception("[파일: " . basename(__FILE__) . ", 라인: " . __LINE__ . "] 오디오 URL 데이터베이스 저장 실패");
    }

} catch (Exception $e) {
    // 버퍼 정리
    ob_clean();

    $errorMessage = $e->getMessage();
    $response['success'] = false;
    $response['message'] = $errorMessage;
    error_log("Classroom narration generation error: " . $errorMessage);
    error_log("Stack trace: " . $e->getTraceAsString());

    // JSON 응답 출력
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 성공 시에도 버퍼 정리 후 출력
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
