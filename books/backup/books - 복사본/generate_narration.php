<?php
/////////////////////////////// 나레이션 자동 생성 및 TTS 생성 ///////////////////////////////

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정 - 모든 에러 출력 차단
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/narration_error.log');

// 메모리 및 실행시간 제한 증가
ini_set('memory_limit', '256M');
set_time_limit(120);

// JSON 헤더 즉시 설정
header('Content-Type: application/json; charset=utf-8');

try {
    // 설정 파일 포함
    if (!file_exists(dirname(__FILE__) . '/api_config.php')) {
        throw new Exception("API 설정 파일을 찾을 수 없습니다.");
    }
    require_once(dirname(__FILE__) . '/api_config.php');

    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;

    // 디버그 로깅 함수
    function debug_log($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            error_log("[NARRATION_DEBUG] " . date('Y-m-d H:i:s') . " - " . $message);
        }
    }

    debug_log("나레이션 생성 시작");

    // 파라미터 받기 및 검증
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;
    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;
    $generateTTS = isset($_POST['generateTTS']) ? $_POST['generateTTS'] === 'true' : true;
    $audioType = isset($_POST['audioType']) ? $_POST['audioType'] : 'audiourl2';
    $regenerate = isset($_POST['regenerate']) ? $_POST['regenerate'] === 'true' : false;
    $timecreated = time();

    debug_log("파라미터 - contentsid: $contentsid, generateTTS: " . ($generateTTS ? 'true' : 'false') . ", regenerate: " . ($regenerate ? 'true' : 'false') . ", audioType: $audioType");

    if ($contentsid <= 0) {
        throw new Exception("유효하지 않은 콘텐츠 ID입니다.");
    }

    // 로그인 체크
    require_login();

    // 권한 체크
    $userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = ? ORDER BY id DESC LIMIT 1",
        array($USER->id, 22));
    $role = isset($userrole->data) ? $userrole->data : '';

    // 응답 초기화
    $response = ['success' => false, 'message' => ''];
    // 콘텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM {icontent_pages} WHERE id = ? ORDER BY id DESC LIMIT 1", array($contentsid));

    if (!$cnttext) {
        throw new Exception("콘텐츠를 찾을 수 없습니다.");
    }

    // 재생성 모드가 아닌 경우 기존 오디오 확인 (재생성 모드에서는 기존 오디오가 있어도 새로 생성)
    if (!$regenerate && $audioType === 'audiourl' && !empty($cnttext->audiourl)) {
        debug_log("이미 audiourl이 존재하고 재생성 모드가 아니므로 스킵");
        // 이미 오디오가 존재하고 재생성이 아닌 경우 알림만 반환
        $response['success'] = false;
        $response['message'] = '이미 수업 엿듣기 컨텐츠가 존재합니다. 재생성하려면 재생성 옵션을 사용하세요.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 콘텐츠 텍스트 준비
    $maintext = strip_tags($cnttext->maintext); // HTML 태그 제거
    $title = $cnttext->title;

    debug_log("나레이션 생성 진행 - 재생성 모드: " . ($regenerate ? 'true' : 'false'));

    // 수학 나레이터 프롬프트
    $systemPrompt = "Role:
act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.

Context:
입력된 수학 텍스트를 자연스러운 한국어 설명 대본으로 변환.
본 활동은 문제 풀이에 대한 자세한 설명을 들은 후 해당 내용을 숙달하기 위한 활동임
따라서 계산 등 자세한 내용은 생략하고 절차에 대한 구조를 강화시키는 것이 목적임
먼저, 문제 내용을 한 번더 정리하는 것을 시작.
대본은 지시와 설명 기반 도제학습 스타일로 작성.
학생이 문제나 이미지를 보고 있다고 가정하고, 관찰 지시를 통해 시각적 이해를 강화.
구체적인 계산 과정은 최소화하고, 풀이의 핵심 흐름과 구조를 간결하면서도 몰입감 있게 설명.
설명이 끝난 뒤에는 반드시 **'절차기억 형성활동을 시작합니다'**라는 문장으로 전환.
전환 이후에는 앞서 설명한 내용을 다시 한 번 강조·요약하며, 유사한 방식으로 더 중요한 사실들을 정리.
마지막에는 이전 설명을 요약해서 한 번 더 설명하고 \"이제 문제만 보고 풀 수 있는지 생각해 보세요. 스스로 머릿속으로 풀어 보세요.\" 라는 식으로 학생이 혼자 문제를 시도하도록 유도.

Instructions:
모든 숫자, 기호, 알파벳은 반드시 한글 발음으로 변환.
계산식의 디테일보다는 문제 구조, 조건, 풀이 절차의 흐름을 강조.
관찰을 지시할 때는 \"지금 그림의 오른쪽 위를 보세요\"와 같이 구체적 시각 지침을 제공.
설명은 단계마다 요약을 포함하여 기억 정착을 돕도록 구성.
절차기억 형성 단계에서 반드시 다시 정리, 중요한 사실 강조, 스스로 풀어보기 유도가 포함되어야 함.

Guidelines:
반드시 한글만 사용. 숫자나 기호 절대 금지.
하나, 둘, 셋 같은 표현은 쓰지 말고 반드시 일, 이, 삼, 사… 와 같은 아라비아숫자 한글 발음 사용.
소숫점은 영점으로 읽기. 예: 영점삼오.
분수는 \"사분의 삼\"과 같이 올바른 순서로 읽기.
출력은 오직 지시·설명 대본 형식으로, 다른 목차나 목록, 불필요한 기호 사용 금지.

Output format:
Plain text suitable for script reading

Output fields:
Detailed narration script including observation, explanations, step-by-step guidance, and memory-forming activity";

    $userPrompt = "다음 수학 콘텐츠를 나레이션 대본으로 변환해주세요:

제목: $title

내용:
$maintext";

    // OpenAI API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception("API 키가 설정되지 않았습니다.");
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

    debug_log("API 요청 준비 완료");

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
        throw new Exception("API 호출 오류: " . $curlError);
    }

    debug_log("API 응답 코드: " . $httpCode);

    // HTTP 상태 코드별 상세 에러 처리
    if ($httpCode === 401) {
        throw new Exception("API 키가 유효하지 않습니다. 관리자에게 문의하세요.");
    } elseif ($httpCode === 429) {
        throw new Exception("API 사용량 한도를 초과했습니다. 잠시 후 다시 시도해주세요.");
    } elseif ($httpCode === 500) {
        throw new Exception("OpenAI 서버 오류입니다. 잠시 후 다시 시도해주세요.");
    } elseif ($httpCode !== 200) {
        $errorData = json_decode($apiResponse, true);
        $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : "알 수 없는 오류";
        throw new Exception("API 오류 (HTTP $httpCode): " . $errorMsg);
    }

    // API 응답 파싱
    $apiData = json_decode($apiResponse, true);

    if (!isset($apiData['choices'][0]['message']['content'])) {
        throw new Exception("API 응답 형식 오류");
    }

    $narration = $apiData['choices'][0]['message']['content'];

    // 기존 레코드 확인
    $existingRecord = $DB->get_record_sql("SELECT id FROM {abrainalignment_gptresults} WHERE type = ? AND contentsid = ? AND contentstype = ? ORDER BY id DESC LIMIT 1",
        array('pmemory', $contentsid, $contentstype));

    if ($existingRecord) {
        // 업데이트
        $record = new stdClass();
        $record->id = $existingRecord->id;
        $record->outputtext = $narration;
        $record->timemodified = $timecreated;
        $DB->update_record('abrainalignment_gptresults', $record);
    } else {
        // 새 레코드 삽입
        $newrecord = new stdClass();
        $newrecord->type = "pmemory";
        $newrecord->contentsid = $contentsid;
        $newrecord->contentstype = $contentstype;
        $newrecord->gid = '71280';
        $newrecord->outputtext = $narration;
        $newrecord->timemodified = $timecreated;
        $newrecord->timecreated = $timecreated;
        $DB->insert_record('abrainalignment_gptresults', $newrecord);
    }

    $response['success'] = true;
    $response['message'] = '나레이션이 성공적으로 생성되었습니다.';
    $response['narration'] = substr($narration, 0, 100) . '...'; // 미리보기용
    $response['fullNarration'] = $narration;

    // TTS 생성 요청시
    if ($generateTTS) {
        try {
            debug_log("TTS 생성 시작");

            // TTS 생성
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
                throw new Exception("TTS 생성 오류: " . $curlError);
            }

            if ($httpCode !== 200) {
                throw new Exception("TTS API 오류: HTTP " . $httpCode);
            }

            // 오디오 파일 저장
            $uploadPath = AUDIO_UPLOAD_PATH;
            if (!file_exists($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    throw new Exception("오디오 디렉토리 생성 실패: " . $uploadPath);
                }
            }

            $filename = 'cid' . $contentsid . 'ct' . $contentstype . '_pmemory.mp3';
            $filepath = $uploadPath . $filename;

            debug_log("오디오 파일 저장 경로: " . $filepath);

            if (file_put_contents($filepath, $audioResponse) === false) {
                throw new Exception("오디오 파일 저장 실패: " . $filepath);
            }

            // 파일 권한 설정
            chmod($filepath, 0644);

            // 오디오 URL 생성
            $audioUrl = AUDIO_URL_BASE . $filename;
            debug_log("오디오 URL 생성: " . $audioUrl);

            // 콘텐츠 테이블 업데이트
            $updateData = new stdClass();
            $updateData->id = $contentsid;
            $updateData->{$audioType} = $audioUrl;
            $updateData->timemodified = $timecreated;

            if ($DB->update_record('icontent_pages', $updateData)) {
                $response['ttsSuccess'] = true;
                $response['audioUrl'] = $audioUrl;
                $response['message'] = '나레이션 및 TTS가 성공적으로 생성되었습니다.';
            } else {
                throw new Exception("오디오 URL 데이터베이스 저장 실패");
            }

        } catch (Exception $ttsError) {
            $response['ttsSuccess'] = false;
            $response['ttsError'] = $ttsError->getMessage();
            error_log("TTS generation error: " . $ttsError->getMessage());
        }
    }

} catch (Exception $e) {
    // 버퍼 정리
    ob_clean();

    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Narration generation error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // JSON 응답 출력
    echo json_encode($response);
    exit;
}

// 성공 시에도 버퍼 정리 후 출력
ob_clean();
echo json_encode($response);
exit;
?>