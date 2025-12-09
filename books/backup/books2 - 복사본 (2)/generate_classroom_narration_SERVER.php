<?php
/////////////////////////////// 수업 엿듣기 나레이션 자동 생성 API ///////////////////////////////
// 파일: generate_classroom_narration.php
// 경로: /home/moodle/public_html/moodle/local/augmented_teacher/books/
// 설명: 수업 엿듣기 전용 나레이션 및 TTS 자동 생성 API
// 작성일: 2025-01-24

// 출력 버퍼링 시작
ob_start();

// 에러 핸들링 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/moodle/public_html/moodle/local/augmented_teacher/books/classroom_narration_error.log');

// 메모리 및 실행시간 제한
ini_set('memory_limit', '512M');
set_time_limit(180);

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

try {
    // Moodle 설정 포함
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;

    // API 설정 파일 포함
    $configFile = dirname(__FILE__) . '/api_config.php';
    if (!file_exists($configFile)) {
        throw new Exception("API 설정 파일을 찾을 수 없습니다: " . $configFile);
    }
    require_once($configFile);

    // 디버그 로깅 함수
    function debug_log($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            error_log("[CLASSROOM_NARRATION] " . date('Y-m-d H:i:s') . " - " . $message);
        }
    }

    debug_log("=== 수업 엿듣기 나레이션 생성 시작 ===");

    // 파라미터 받기
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;
    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;
    $regenerate = isset($_POST['regenerate']) && $_POST['regenerate'] === 'true';
    $timecreated = time();

    debug_log("파라미터: contentsid=$contentsid, regenerate=" . ($regenerate ? 'true' : 'false'));

    if ($contentsid <= 0) {
        throw new Exception("유효하지 않은 콘텐츠 ID");
    }

    // 로그인 체크
    require_login();

    // 응답 초기화
    $response = [
        'success' => false,
        'message' => '',
        'step' => 'init'
    ];

    // 콘텐츠 가져오기
    $cnttext = $DB->get_record('icontent_pages', ['id' => $contentsid]);

    if (!$cnttext) {
        throw new Exception("콘텐츠를 찾을 수 없습니다");
    }

    // 재생성 모드가 아닌 경우 기존 오디오 확인
    if (!$regenerate && !empty($cnttext->audiourl)) {
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

    $maintext = strip_tags($cnttext->maintext);
    $title = $cnttext->title;

    // 시스템 프롬프트
    $systemPrompt = "Role:
act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.

Context:
- Convert mathematical content into natural Korean narration scripts
- Make content understandable and engaging with explanations, examples, and analogies
- Clarify confusing parts

Instructions:
- Convert ALL numbers to Korean (1→일, 2→이)
- Convert ALL symbols and alphabets to phonetic Korean
- Maintain logical sequence
- Use teacher-student dialogue format
- Add step-by-step explanations

Critical Rules:
- ONLY use Korean characters, NO numbers or symbols in output
- Use Arabic number reading (일,이,삼,사...) NOT (하나,둘,셋,넷...)
- Decimals: 0.35 → 영점삼오
- Fractions: correct order (사분의 삼)
- Colon(:) ONLY after 선생님: or 학생:
- NO lists, ONLY dialogue format
- Line breaks ONLY between speaker changes

Output format:
선생님: [자연스러운 설명]
학생: [질문]
선생님: [답변]
...";

    $userPrompt = "작은 의미단위로 완결성있게 설명 후 요약. 준비하는 시간을 가지고 다음 소주제로 넘어가는 방식으로 진행해줘.

제목: $title

내용:
$maintext";

    // OpenAI API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception("API 키가 설정되지 않았습니다");
    }

    // GPT API 요청
    $requestData = [
        'model' => GPT_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_tokens' => GPT_MAX_TOKENS,
        'temperature' => GPT_TEMPERATURE
    ];

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

    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("API 호출 오류: " . $curlError);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($apiResponse, true);
        $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : "HTTP $httpCode";
        throw new Exception("GPT API 오류: " . $errorMsg);
    }

    $apiData = json_decode($apiResponse, true);
    if (!isset($apiData['choices'][0]['message']['content'])) {
        throw new Exception("API 응답 형식 오류");
    }

    $narration = $apiData['choices'][0]['message']['content'];
    debug_log("나레이션 생성 완료: " . strlen($narration) . " bytes");

    // DB에 나레이션 저장
    $existingRecord = $DB->get_record('abrainalignment_gptresults', [
        'type' => 'conversation',
        'contentsid' => $contentsid,
        'contentstype' => $contentstype
    ]);

    if ($existingRecord) {
        $record = new stdClass();
        $record->id = $existingRecord->id;
        $record->outputtext = $narration;
        $record->timemodified = $timecreated;
        $DB->update_record('abrainalignment_gptresults', $record);
    } else {
        $newrecord = new stdClass();
        $newrecord->type = "conversation";
        $newrecord->contentsid = $contentsid;
        $newrecord->contentstype = $contentstype;
        $newrecord->gid = '71280';
        $newrecord->outputtext = $narration;
        $newrecord->timemodified = $timecreated;
        $newrecord->timecreated = $timecreated;
        $DB->insert_record('abrainalignment_gptresults', $newrecord);
    }

    // Step 2: TTS 생성
    $response['step'] = 'generating_tts';
    debug_log("Step 2: TTS 생성 시작");

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
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("TTS API 오류: HTTP " . $httpCode);
    }

    // 오디오 파일 저장
    $uploadPath = '/home/moodle/public_html/audiofiles/';
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $filename = 'cid' . $contentsid . 'ct' . $contentstype . '_classroom.mp3';
    $filepath = $uploadPath . $filename;

    if (file_put_contents($filepath, $audioResponse) === false) {
        throw new Exception("오디오 파일 저장 실패");
    }

    chmod($filepath, 0644);
    debug_log("오디오 파일 저장 완료: " . $filepath);

    // 오디오 URL 생성
    $audioUrl = 'https://mathking.kr/audiofiles/' . $filename;

    // Step 3: DB 업데이트
    $response['step'] = 'updating_db';
    debug_log("Step 3: DB 업데이트");

    $updateData = new stdClass();
    $updateData->id = $contentsid;
    $updateData->audiourl = $audioUrl;
    $updateData->timemodified = $timecreated;

    if ($DB->update_record('icontent_pages', $updateData)) {
        $response['success'] = true;
        $response['message'] = '수업 엿듣기 컨텐츠가 성공적으로 생성되었습니다.';
        $response['audioUrl'] = $audioUrl;
        $response['step'] = 'completed';
        debug_log("=== 처리 완료 ===");
    } else {
        throw new Exception("DB 업데이트 실패");
    }

} catch (Exception $e) {
    ob_clean();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Classroom narration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
