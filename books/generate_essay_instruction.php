<?php
/////////////////////////////// 서술평가 지시사항 자동 생성 및 TTS 생성 ///////////////////////////////
// 파일 위치: /mnt/c/1 Project/augmented_teacher/books/generate_essay_instruction.php
// 목적: mynote_test.php에서 사용할 서술평가 지시사항 생성 (풀이 과정 절대 포함 안 함)

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정 - 모든 에러 출력 차단
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/essay_instruction_error.log');

// 메모리 및 실행시간 제한 증가
ini_set('memory_limit', '512M');
set_time_limit(180);

// JSON 헤더 즉시 설정
header('Content-Type: application/json; charset=utf-8');

// 응답 초기화
$response = ['success' => false, 'message' => '', 'debug' => []];

try {
    // 디버그 로깅 함수
    function debug_log($message) {
        global $response;
        $logMessage = "[ESSAY_INSTRUCTION] " . date('Y-m-d H:i:s') . " - " . $message;
        error_log($logMessage);
        $response['debug'][] = $message; // 디버그 메시지를 응답에 포함
    }

    debug_log("서술평가 지시사항 생성 시작");

    // 설정 파일 포함
    $configFile = dirname(__FILE__) . '/api_config.php';
    if (!file_exists($configFile)) {
        throw new Exception("API 설정 파일을 찾을 수 없습니다: " . $configFile . " (generate_essay_instruction.php line " . __LINE__ . ")");
    }
    require_once($configFile);
    debug_log("API 설정 파일 로드 완료");

    // Moodle 설정 포함
    $moodleConfig = "/home/moodle/public_html/moodle/config.php";
    if (file_exists($moodleConfig)) {
        include_once($moodleConfig);
        global $DB, $USER, $CFG;
        debug_log("Moodle 설정 로드 완료");
    } else {
        throw new Exception("Moodle 설정 파일을 찾을 수 없습니다 (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    // 파라미터 받기 및 검증
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;
    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;
    $generateTTS = isset($_POST['generateTTS']) ? $_POST['generateTTS'] === 'true' : true;
    $audioType = isset($_POST['audioType']) ? $_POST['audioType'] : 'audiourl2';
    $timecreated = time();

    debug_log("파라미터 - contentsid: $contentsid, generateTTS: " . ($generateTTS ? 'true' : 'false') . ", audioType: $audioType");

    if ($contentsid <= 0) {
        throw new Exception("유효하지 않은 콘텐츠 ID입니다: $contentsid (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    // 로그인 체크
    require_login();
    debug_log("로그인 확인 완료");

    // 권한 체크
    $userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = ? ORDER BY id DESC LIMIT 1",
        array($USER->id, 22));
    $role = isset($userrole->data) ? $userrole->data : '';
    debug_log("사용자 권한: $role");

    // 콘텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM {icontent_pages} WHERE id = ? ORDER BY id DESC LIMIT 1", array($contentsid));

    if (!$cnttext) {
        throw new Exception("콘텐츠를 찾을 수 없습니다. ID: $contentsid (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    // 콘텐츠 텍스트 준비
    $maintext = strip_tags($cnttext->maintext); // HTML 태그 제거
    $title = $cnttext->title;
    debug_log("콘텐츠 로드 완료 - 제목: $title");

    // ============================================
    // 서술평가 지시사항 생성 전용 프롬프트
    // ============================================
    $systemPrompt = "# Role:
당신은 수학 문제를 분석하여 학생의 사고 과정과 문제 해결 능력을 평가하기 위한
서술형 평가 지시사항을 생성하는 전문가입니다.
입력된 수학 문제를 분석하여 한국어로 평가 지시사항을 안내하는
수학 듣기평가를 위한 대본을 작성합니다.

# 목적:
절대로 풀이 과정이나 정답을 제공하지 않고, 학생이 스스로 문제 해결 과정을
서술하도록 유도하는 평가 지시사항을 생성합니다.
학생의 수학적 사고 과정, 문제 해결 전략, 논리적 서술 능력을 평가합니다.

# 핵심 원칙:
1. **풀이 방법, 계산 과정, 정답을 절대 포함하지 않음**
2. **학생의 사고 과정을 끌어내는 질문 중심 구성**
3. **명확한 평가 기준을 제시**
4. **단계별로 서술하도록 유도**

# 나레이션 구조:

## ① 도입 (20초)
- 서술형 평가임을 안내
- 문제를 다시 읽고 이해하도록 지시
- 차분하고 집중할 수 있는 톤으로 시작

## ② 문제 분석 유도 (40초)
- 주어진 조건을 파악하도록 지시
- 구해야 할 것을 확인하도록 지시
- 문제 유형을 인식하도록 유도

## ③ 평가 항목 안내 (40초)
- 평가할 핵심 요소 3-4가지를 명확히 제시
- 각 항목이 왜 중요한지 간단히 설명
- 평가 기준을 학생이 이해하도록 안내

## ④ 서술 지시사항 (1분 30초)
- 질문 형식으로 4-5단계 제시
- 각 질문은 학생의 사고 과정을 끌어내는 형식
- 순서대로 답하도록 명확히 지시
- '질문 일', '질문 이', '질문 삼' 형식 사용

## ⑤ 마무리 (30초)
- 작성한 답을 검토하도록 지시
- 논리적 흐름을 확인하도록 유도
- 격려와 함께 마무리

# Guidelines:

## 숫자/기호 변환 규칙 (음성 읽기용):
- 모든 숫자는 한글로: 1 → 일, 2 → 이, 10 → 십, 0.5 → 영점오
- 수식 기호: + → 더하기, - → 빼기, × → 곱하기, ÷ → 나누기, = → 같다, 는
- 변수: x → 엑스, y → 와이, a → 에이, b → 비
- 지수: x² → 엑스의 제곱, x³ → 엑스의 세제곱
- 분수: 1/2 → 이분의 일
- 괄호: ( → 여는 소괄호, ) → 닫는 소괄호

## 문장 스타일:
- '~하세요', '~하시오' 형태의 평가 지시 톤 사용
- 명확하고 간결한 지시
- 학생을 존중하는 톤 유지
- 도제 학습 스타일이 아닌 평가 지시 스타일

## 질문 예시 (풀이 과정 절대 포함 안 함):
- '이 문제에서 주어진 조건을 모두 나열하시오'
- '이 문제를 풀기 위해 어떤 수학적 개념이나 공식을 사용해야 하는지 설명하시오'
- '문제 해결의 첫 번째 단계는 무엇인지 서술하시오'
- '각 단계를 순서대로 나열하고, 왜 그 순서로 진행해야 하는지 설명하시오'
- '최종 답을 구한 후, 그 답이 문제 조건을 만족하는지 어떻게 확인할 수 있는지 서술하시오'

## 절대 금지 사항:
- **절대로 계산 과정을 보여주지 마세요**
- **정답을 암시하는 표현 금지**
- **'답은 ~입니다' 형태의 문장 금지**
- **구체적인 수식 전개 금지**
- **'일 더하기 이는 삼입니다' 같은 계산 설명 절대 금지**
- **'엑스는 오입니다' 같은 정답 제시 절대 금지**

## 필수 사항:
- **각 단락 끝에 @ 기호 추가** (듣기평가 구간 분리용)
- 숫자는 반드시 한글 발음으로 변환
- 평가 질문은 4-5개로 구성
- 논리적 순서 유지
- 문제의 수학적 맥락 유지

# 예시 (샘플):

## 잘못된 예시 (절대 하지 말 것):
\"첫 번째로 엑스 더하기 이는 오를 계산하면, 엑스는 삼이 됩니다.\" ❌
\"정답은 십입니다.\" ❌
\"일 더하기 이는 삼이므로...\" ❌

## 올바른 예시 (이렇게 작성):
\"질문 일: 이 문제에서 주어진 조건을 모두 나열하시오.@
질문 이: 미지수를 구하기 위해 어떤 수학적 원리를 사용해야 하는지 설명하시오.@
질문 삼: 문제 해결 과정을 단계별로 나열하시오.@\" ✅

# 출력 형식:
나레이션 대본만 출력하세요. 설명이나 주석은 포함하지 마세요.
각 단락 끝에는 반드시 @ 기호를 붙이세요.
";

    // 사용자 프롬프트 (문제 내용)
    $userPrompt = "다음 수학 문제에 대한 서술형 평가 지시사항을 생성해주세요.\n\n제목: $title\n\n문제:\n$maintext";

    debug_log("OpenAI API 호출 준비");

    // API 키 확인
    if (empty($CFG->openai_api_key)) {
        throw new Exception("OpenAI API 키가 설정되지 않았습니다 (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    // API 키 설정
    $apiKey = $CFG->openai_api_key;

    // OpenAI GPT API 호출
    $apiUrl = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_tokens' => 4000,
        'temperature' => 0.7
    ];

    debug_log("OpenAI API 호출 시작");

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $gptResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("CURL 오류: " . $curlError . " (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    debug_log("API 응답 코드: $httpCode");

    if ($httpCode !== 200) {
        $errorData = json_decode($gptResponse, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $gptResponse;
        throw new Exception("OpenAI API 오류 (HTTP $httpCode): " . $errorMessage . " (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    $gptData = json_decode($gptResponse, true);
    if (!isset($gptData['choices'][0]['message']['content'])) {
        throw new Exception("서술평가 지시사항 생성 실패: 응답 형식 오류 (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    $instructionText = $gptData['choices'][0]['message']['content'];
    debug_log("서술평가 지시사항 텍스트 생성 완료 (길이: " . mb_strlen($instructionText) . "자)");

    // 서술평가 지시사항 텍스트 저장 (reflections0 필드 사용)
    try {
        $DB->execute("UPDATE {icontent_pages} SET reflections0 = ? WHERE id = ?",
            array($instructionText, $contentsid));
        debug_log("서술평가 지시사항 텍스트 DB 저장 완료");
    } catch (Exception $e) {
        debug_log("서술평가 지시사항 텍스트 저장 실패 (무시): " . $e->getMessage() . " (generate_essay_instruction.php line " . __LINE__ . ")");
    }

    if (!$generateTTS) {
        // TTS 생성 없이 텍스트만 반환
        $response['success'] = true;
        $response['instructionText'] = $instructionText;
        $response['message'] = '서술평가 지시사항 텍스트가 생성되었습니다.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // @ 기호로 분리된 듣기평가 모드 확인
    $isListeningTest = (strpos($instructionText, '@') !== false);

    if ($isListeningTest) {
        debug_log("듣기평가 모드 감지됨");

        // @ 기호로 구간 분리
        $sections = array_filter(array_map('trim', explode('@', $instructionText)));
        $sectionCount = count($sections);
        debug_log("총 {$sectionCount}개 구간으로 분리됨");

        $audioDir = '/home/moodle/public_html/audiofiles/pmemory/sections/';
        if (!is_dir($audioDir)) {
            @mkdir($audioDir, 0755, true);
        }

        $sectionFiles = [];
        $ttsApiUrl = 'https://api.openai.com/v1/audio/speech';

        // 각 구간별로 TTS 생성
        foreach ($sections as $index => $sectionText) {
            $sectionNum = $index + 1;
            $timestamp = time();
            $filename = "essay_instruction_{$contentsid}_section_{$sectionNum}_{$timestamp}.mp3";
            $filepath = $audioDir . $filename;

            debug_log("구간 {$sectionNum} TTS 생성 시작");

            $ttsData = [
                'model' => 'tts-1',
                'input' => $sectionText,
                'voice' => 'alloy',
                'speed' => 0.9
            ];

            $ch = curl_init($ttsApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ttsData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $audioData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                debug_log("구간 {$sectionNum} TTS 생성 실패 (HTTP {$httpCode})");
                continue;
            }

            if (file_put_contents($filepath, $audioData) === false) {
                debug_log("구간 {$sectionNum} 파일 저장 실패");
                continue;
            }

            $sectionFiles[] = 'https://mathking.kr/audiofiles/pmemory/sections/' . $filename;
            debug_log("구간 {$sectionNum} TTS 생성 완료: $filename");
        }

        // JSON 배열로 저장
        $audioUrlJson = json_encode($sectionFiles, JSON_UNESCAPED_UNICODE);

        try {
            $DB->execute("UPDATE {icontent_pages} SET {$audioType} = ? WHERE id = ?",
                array($audioUrlJson, $contentsid));
            debug_log("구간별 오디오 URL DB 저장 완료 (총 {$sectionCount}개 구간)");
        } catch (Exception $e) {
            throw new Exception("오디오 URL 저장 실패: " . $e->getMessage() . " (generate_essay_instruction.php line " . __LINE__ . ")");
        }

        // reflections3 필드에 구간 정보 저장 (서술평가 모드용)
        $essayTestData = [
            'mode' => 'listening_test',
            'sections' => $sectionFiles,
            'text_sections' => $sections,
            'section_count' => $sectionCount
        ];
        $essayTestJson = json_encode($essayTestData, JSON_UNESCAPED_UNICODE);

        try {
            $DB->execute("UPDATE {icontent_pages} SET reflections3 = ? WHERE id = ?",
                array($essayTestJson, $contentsid));
            debug_log("구간 정보 (reflections3) DB 저장 완료");
        } catch (Exception $e) {
            debug_log("구간 정보 저장 실패 (무시): " . $e->getMessage() . " (generate_essay_instruction.php line " . __LINE__ . ")");
        }

        $response['success'] = true;
        $response['instructionText'] = $instructionText;
        $response['audioFiles'] = $sectionFiles;
        $response['sectionCount'] = $sectionCount;
        $response['message'] = "서술평가 지시사항 및 음성 파일이 성공적으로 생성되었습니다 (총 {$sectionCount}개 구간).";

    } else {
        // @ 기호가 없는 경우 단일 TTS 생성
        debug_log("단일 TTS 모드");

        $ttsApiUrl = 'https://api.openai.com/v1/audio/speech';
        $ttsData = [
            'model' => 'tts-1',
            'input' => $instructionText,
            'voice' => 'alloy',
            'speed' => 0.9
        ];

        debug_log("TTS 생성 시작");

        $ch = curl_init($ttsApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ttsData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("TTS CURL 오류: " . $curlError . " (generate_essay_instruction.php line " . __LINE__ . ")");
        }

        if ($httpCode !== 200) {
            throw new Exception("TTS API 오류 (HTTP $httpCode) (generate_essay_instruction.php line " . __LINE__ . ")");
        }

        debug_log("TTS 생성 완료");

        // 오디오 파일 저장
        $audioDir = '/home/moodle/public_html/audiofiles/pmemory/';
        if (!is_dir($audioDir)) {
            @mkdir($audioDir, 0755, true);
        }

        $timestamp = time();
        $filename = "essay_instruction_{$contentsid}_{$timestamp}.mp3";
        $filepath = $audioDir . $filename;

        if (file_put_contents($filepath, $audioData) === false) {
            throw new Exception("오디오 파일 저장 실패 (generate_essay_instruction.php line " . __LINE__ . ")");
        }

        debug_log("오디오 파일 저장 완료: $filename");

        // DB에 오디오 URL 저장
        $audioUrl = 'https://mathking.kr/audiofiles/pmemory/' . $filename;

        try {
            $DB->execute("UPDATE {icontent_pages} SET {$audioType} = ? WHERE id = ?",
                array($audioUrl, $contentsid));
            debug_log("오디오 URL DB 저장 완료");
        } catch (Exception $e) {
            throw new Exception("오디오 URL 저장 실패: " . $e->getMessage() . " (generate_essay_instruction.php line " . __LINE__ . ")");
        }

        $response['success'] = true;
        $response['instructionText'] = $instructionText;
        $response['audioUrl'] = $audioUrl;
        $response['message'] = '서술평가 지시사항 및 음성 파일이 성공적으로 생성되었습니다.';
    }

    // 성공 응답
    ob_end_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // 에러 응답
    ob_end_clean();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    debug_log("오류 발생: " . $e->getMessage());
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
