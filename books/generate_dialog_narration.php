<?php
/////////////////////////////// 대화형 나레이션 자동 생성 및 TTS 생성 ///////////////////////////////

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정 - 모든 에러 출력 차단
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/dialog_narration_error.log');

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
        $logMessage = "[DIALOG_NARRATION] " . date('Y-m-d H:i:s') . " - " . $message;
        error_log($logMessage);
        $response['debug'][] = $message; // 디버그 메시지를 응답에 포함
    }

    debug_log("대화형 나레이션 생성 시작");

    // 설정 파일 포함
    $configFile = dirname(__FILE__) . '/api_config.php';
    if (!file_exists($configFile)) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] API 설정 파일을 찾을 수 없습니다: " . $configFile);
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
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] Moodle 설정 파일을 찾을 수 없습니다");
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
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 유효하지 않은 콘텐츠 ID입니다: $contentsid");
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
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 콘텐츠를 찾을 수 없습니다. ID: $contentsid");
    }

    // 콘텐츠 텍스트 준비
    $maintext = strip_tags($cnttext->maintext); // HTML 태그 제거
    $title = $cnttext->title;
    debug_log("콘텐츠 로드 완료 - 제목: $title");

    // 절차기억 형성 나레이터 프롬프트
    $systemPrompt = "# Role: 
당신은 수학 문제와 풀이를 절차기억(procedural memory) 형성용 나레이션으로 변환하는 전문가입니다.
입력된 수학문제와 풀이 정보를 분석 후 한국어로 단계별 풀이를 안내하는 수학 듣기평가를 위한 대본을 작성합니다.

# 목적:
계산 등 자세한 내용보다 절차에 대한 구조를 강화시키는 것이 목적입니다.
서술한 내용을 선생님이 직접 채점하는 상황을 가정합니다.

# 전개 방식:
1. **문제 내용 정리로 시작**: 탐구를 유도하고 해소작용으로 답을 제시하는 방식
2. **도제학습 스타일**: 무엇을 생각해야 할지를 궁금하게 만들고, 답을 하며 실행사항을 제시
3. **자료 중심**: 제공된 컨텐츠 내용을 순서대로 읽으면서 공부한다고 가정하고 해당 내용을 자세히 설명하는 방식으로 진행.
4. **절차기억 형성 단계**: 설명이 끝난 뒤 반드시 **'절차기억 형성활동을 시작합니다'**라는 문장으로 전환
5. **강조 및 요약**: 전환 이후에는 앞서 설명한 내용을 다시 한 번 강조·요약하며, 유사한 방식으로 더 중요한 사실들을 정리
 
# Instructions:
- 모든 숫자, 기호, 알파벳은 반드시 한글 발음으로 변환
- 관찰을 지시할 때는 \"지금 그림의 오른쪽 위를 보세요\"와 같이 구체적 시각 지침을 제공
- 절차기억 형성 단계에서 반드시 다시 정리, 중요한 사실 강조, 스스로 풀어보기 유도가 포함되어야 함
- 각각의 단락별로 음성파일이 일시정지가 되게 하기 위해 마지막 부분에 @ 기호를 반드시 삽입

# Guidelines: 
- 반드시 한글만 사용 (숫자나 기호 절대 금지)
- 하나, 둘, 셋 같은 표현은 쓰지 말고 반드시 일, 이, 삼, 사… 와 같은 아라비아숫자 한글 발음 사용
- 소숫점은 영점으로 읽기 (예: 0.35 → 영점삼오)
- 분수는 \"사분의 삼\"과 같이 올바른 순서로 읽기
- 출력은 오직 지시·설명 대본 형식으로, 다른 목차나 목록, 불필요한 기호 사용 금지
- 각 단락 끝에 @ 기호 필수
# 이것은 매우 중요해
- 제공된 자료가 문제인 경우와 개념이 제공된 경우 각각 아래의 예시를 참고하여 예시와 유사한 방식으로 생성해줘
- 예시의 단계는 한가지 예시이므로 제공된 풀이나 개념 상의 자료를 기준으로 양과 내용에 단계의 수를 조절해줘. 절차기억 형성활동을 시작합니다@ 부분부터는 같은 형식으로 해줘.
- 질문 다음에는 무조건 @ 붙여서 일시정지 지점을 표시해줘.
- 문제나 개념의 제목부분은 다루지 말아줘.
# 샘플 예시 스타일:
\" 지금 자료의 첫 문장을 천천히 읽어보세요. “이차함수의 그래프가 주어지면 이차부등식의 해를 쉽게 구할 수 있다”라고 되어 있죠. 그래프를 이용하면 부등식의 해를 어떻게 알 수 있을까요?@

그래프에서 와이값이 엑스축보다 위쪽에 있으면 양수, 아래쪽에 있으면 음수라는 점을 떠올려 보세요. 그래서 식 에이 엑스 제곱 더하기 비 엑스 더하기 씨가 영보다 크다의 해는 그래프가 엑스축 위에 있는 엑스의 범위, 영보다 작다의 해는 그래프가 엑스축 아래에 있는 엑스의 범위예요. 이 관계를 눈으로 구분할 수 있다는 것이 바로 ‘그래프를 이용한 해석’의 핵심이에요. 그래프 위쪽은 양수, 아래쪽은 음수. 단순하지만 강력한 연결이죠. 그래프의 위아래 위치가 해의 부호를 결정합니다. 엑스축과 교차하는 점이 있다면, 그 엑스좌표들이 바로 경계가 됩니다. 이 교점이 왜 중요한 걸까요?@

그 이유는, 엑스축에서 와이가 영이 되기 때문이에요. 즉, 그 점에서는 이차함수가 영이 되어 등식 에이 엑스 제곱 더하기 비 엑스 더하기 씨가 영과 같다는 조건이 성립하죠. 그래서 그 엑스좌표들이 부등식의 해가 변하는 경계가 되는 거예요. 이제 이 경계를 기준으로 왼쪽, 가운데, 오른쪽 구간으로 나누어 그래프가 위에 있는지, 아래에 있는지를 판단하면 각각의 구간 부호가 결정됩니다. 그럼, 그래프가 엑스축 위쪽일 때의 부등식은 어떻게 달라질까요?@

그래프가 위쪽에 있으면 와이는 양수니까, 식 에이 엑스 제곱 더하기 비 엑스 더하기 씨가 영보다 크다의 해가 됩니다. 반대로 아래쪽이라면 와이가 음수니까 영보다 작다의 해가 되죠. 즉, 그래프의 위와 아래를 시각적으로 구분하면 바로 해의 부호가 보이는 거예요. 이 원리를 기억해 두면, 계산 없이도 부등식의 해를 빠르게 판단할 수 있습니다. 그런데, 부등호에 ‘같다’가 포함될 때는 어떤 변화가 생길까요?@

‘같다’가 포함될 때는 경계점도 해에 포함돼요. 즉, 부등식이 영보다 크거나 같다 또는 작거나 같다의 형태라면, 엑스축과 만나는 점의 엑스좌표도 해가 됩니다. 반면, 단순히 크다 혹은 작다만 있다면, 그 경계점은 포함되지 않아요. 그래서 그래프에서는 점을 채운 동그라미로 표시하면 포함, 비운 동그라미로 표시하면 제외를 뜻하게 됩니다. 이제 그래프가 엑스축과 만나지 않는 경우를 상상해 볼까요?@

그래프가 엑스축과 만나지 않으면, 포물선 전체가 위쪽에 있거나 아래쪽에 있게 됩니다. 위로 볼록하고 엑스축보다 위쪽이면 와이는 항상 양수, 즉 영보다 크다의 부등식은 모든 엑스에서 참이 됩니다. 반대로 아래로 볼록하고 엑스축보다 아래쪽이면 와이는 항상 음수, 즉 영보다 작다의 부등식은 모든 엑스에서 참이 되죠. 이런 경우를 “항상 참” 또는 “항상 거짓”의 부등식이라 부릅니다. 그럼 실제로 이차부등식을 풀 때는 어떤 순서로 접근해야 할까요?@

먼저, 에이의 부호를 보고 그래프가 위로 볼록인지 아래로 볼록인지 결정해야 합니다. 다음으로, 이차방정식 에이 엑스 제곱 더하기 비 엑스 더하기 씨가 영과 같다를 풀어 엑스절편, 즉 경계점을 찾습니다. 마지막으로, 그래프의 위아래 위치를 판단하여 각 구간의 부호를 읽고, 부등호의 방향에 따라 경계를 포함할지 말지를 결정하면 됩니다. 이 흐름이 바로 이차부등식 풀이의 기본 절차예요.@

절차기억 형성활동을 시작합니다.@

방금의 절차를 다시 정리해 봅시다. 일, 에이의 부호로 그래프의 개형을 결정하기. 이, 이차방정식을 풀어 엑스절편을 찾기. 삼, 그래프의 위아래 위치를 보고 구간의 부호를 판단하기. 사, 부등호에 따라 경계 포함 여부를 결정하기. 오, 구간별로 해를 문장으로 정리하기.@

이제 중요한 사실을 다시 강조합니다. 일, 그래프 위쪽은 와이 양수, 아래쪽은 와이 음수. 이, 엑스축과 만나는 점의 엑스좌표가 해의 경계. 삼, ‘같다’가 포함된 부등식은 경계점 포함. 사, 포물선이 엑스축과 만나지 않으면 항상 참 또는 항상 거짓의 형태가 된다.@

이제 스스로 문제를 떠올려 보세요. 위로 볼록한 포물선이 엑스축을 두 점에서 만난다면, 가운데 구간의 부호는 어떤가요? 그 부호에 따라 어느 구간이 해가 되는지 스스로 판단해 보세요. 그리고 마지막으로 “이제 문제만 보고 풀 수 있는지 생각해 보세요. 스스로 머릿속으로 풀어 보세요.”라고 적고, 조용히 마음속에서 절차를 다시 떠올려 보세요.@ \"

이와 같은 방식과 내용 스타일로 작성하세요.";
 
    // 사용자 프롬프트 (콘텐츠 내용)
    $userPrompt = "다음 수학 콘텐츠를 절차기억 형성 방식으로 설명해주세요:\n\n제목: $title\n\n내용:\n$maintext";

    debug_log("OpenAI API 호출 준비");

    // API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] OpenAI API 키가 설정되지 않았습니다");
    }

    // API 키를 $CFG에서 가져오기
    $apiKey = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
    if (empty($apiKey)) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] API 키가 설정되지 않았습니다");
    }

    // OpenAI GPT API 호출
    $apiUrl = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $data = [
        'model' => 'gpt-5.1',
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
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] CURL 오류: " . $curlError);
    }

    debug_log("API 응답 코드: $httpCode");

    if ($httpCode !== 200) {
        $errorData = json_decode($gptResponse, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $gptResponse;
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] OpenAI API 오류 (HTTP $httpCode): " . $errorMessage);
    }

    $gptData = json_decode($gptResponse, true);
    if (!isset($gptData['choices'][0]['message']['content'])) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 나레이션 텍스트 생성 실패: 응답 형식 오류");
    }

    $narrationText = $gptData['choices'][0]['message']['content'];
    debug_log("나레이션 텍스트 생성 완료 (길이: " . mb_strlen($narrationText) . "자)");

    // 나레이션 텍스트 저장 (narration_text 컬럼이 없을 수 있으므로 reflections0 필드 사용)
    try {
        $DB->execute("UPDATE {icontent_pages} SET reflections0 = ? WHERE id = ?",
            array($narrationText, $contentsid));
        debug_log("나레이션 텍스트 DB 저장 완료");
    } catch (Exception $e) {
        debug_log("나레이션 텍스트 저장 실패 (무시): " . $e->getMessage());
    }

    if (!$generateTTS) {
        // TTS 생성 없이 텍스트만 반환
        $response['success'] = true;
        $response['narrationText'] = $narrationText;
        $response['message'] = '대화형 나레이션 텍스트가 생성되었습니다.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // @ 기호로 분리된 듣기평가 모드 확인
    $isListeningTest = (strpos($narrationText, '@') !== false);
    
    if ($isListeningTest) {
        debug_log("듣기평가 모드 감지됨");
        
        // @ 기호로 구간 분리
        $sections = array_filter(array_map('trim', explode('@', $narrationText)));
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
            debug_log("구간 {$sectionNum}/{$sectionCount} TTS 생성 중");
            
            $ttsData = [
                'model' => 'tts-1',
                'voice' => 'alloy',
                'input' => $sectionText,
                'speed' => 1.0
            ];
            
            $ch = curl_init($ttsApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ttsData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $audioData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $fileName = 'cid' . $contentsid . 'ct' . $contentstype . '_section' . $sectionNum . '.mp3';
                $filePath = $audioDir . $fileName;
                file_put_contents($filePath, $audioData);
                $sectionFiles[] = 'https://mathking.kr/audiofiles/pmemory/sections/' . $fileName;
                debug_log("구간 {$sectionNum} 저장 완료: {$fileName}");
            } else {
                debug_log("구간 {$sectionNum} TTS 생성 실패");
            }
        }
        
        // 전체 구간 정보를 JSON으로 저장
        $sectionsInfo = json_encode([
            'mode' => 'listening_test',
            'sections' => $sectionFiles,
            'text_sections' => $sections
        ], JSON_UNESCAPED_UNICODE);
        
        // reflections1 필드에 구간 정보 저장
        $DB->execute("UPDATE {icontent_pages} SET reflections1 = ? WHERE id = ?",
            array($sectionsInfo, $contentsid));
        debug_log("구간 정보 DB 저장 완료");
        
        // 첫 번째 구간 파일을 audiourl2로 설정
        if (!empty($sectionFiles)) {
            $audioUrl = $sectionFiles[0];
            $DB->execute("UPDATE {icontent_pages} SET audiourl2 = ? WHERE id = ?",
                array($audioUrl, $contentsid));
            debug_log("DB audiourl2 업데이트 완료 (첫 구간)");
        }
        
        $response['listeningTest'] = true;
        $response['sectionCount'] = $sectionCount;
        $response['sectionFiles'] = $sectionFiles;
        
    } else {
        // 기존 방식: 전체를 하나의 음성으로
        debug_log("TTS 생성 시작");
        
        $ttsText = str_replace(['선생님:', '학생:'], '', $narrationText);
        $ttsText = trim($ttsText);
        
        $ttsApiUrl = 'https://api.openai.com/v1/audio/speech';
        $ttsData = [
            'model' => 'tts-1',
            'voice' => 'alloy',
            'input' => $ttsText,
            'speed' => 1.0
        ];
        
        debug_log("TTS API 호출");
        
        $ch = curl_init($ttsApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ttsData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] TTS CURL 오류: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($audioData, true);
            $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : "HTTP $httpCode";
            throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] TTS 생성 실패: " . $errorMessage);
        }
        
        debug_log("TTS 생성 완료 (크기: " . strlen($audioData) . " bytes)");
        
        // 파일 저장
        $audioDir = '/home/moodle/public_html/audiofiles/';
        
        if (!is_dir($audioDir)) {
            debug_log("오디오 디렉토리가 없음. 생성 시도: $audioDir");
            if (!@mkdir($audioDir, 0755, true)) {
                $audioDir = dirname(__FILE__) . '/audiofiles/';
                if (!is_dir($audioDir)) {
                    @mkdir($audioDir, 0755, true);
                }
                debug_log("대체 경로 사용: $audioDir");
            }
        }
        
        $finalFileName = 'cid' . $contentsid . 'ct' . $contentstype . '_dialog.mp3';
        $finalFilePath = $audioDir . $finalFileName;
        
        $bytesWritten = @file_put_contents($finalFilePath, $audioData);
        
        if ($bytesWritten === false) {
            $tempPath = sys_get_temp_dir() . '/' . $finalFileName;
            $bytesWritten = file_put_contents($tempPath, $audioData);
            
            if ($bytesWritten !== false) {
                if (@rename($tempPath, $finalFilePath)) {
                    debug_log("임시 파일에서 이동 완료");
                } else {
                    $finalFilePath = $tempPath;
                    debug_log("임시 경로 사용: $tempPath");
                }
            } else {
                throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 오디오 파일 저장 실패");
            }
        }
        
        debug_log("오디오 파일 저장 완료: $finalFilePath ($bytesWritten bytes)");
        
        // DB 업데이트
        $audioUrl = 'https://mathking.kr/audiofiles/' . $finalFileName;
        
        if ($audioType === 'audiourl2') {
            $DB->execute("UPDATE {icontent_pages} SET audiourl2 = ? WHERE id = ?",
                array($audioUrl, $contentsid));
            debug_log("DB audiourl2 업데이트 완료");
        } else {
            $DB->execute("UPDATE {icontent_pages} SET audiourl = ? WHERE id = ?",
                array($audioUrl, $contentsid));
            debug_log("DB audiourl 업데이트 완료");
        }
    }

    // 성공 응답
    $response['success'] = true;
    $response['narrationText'] = $narrationText;
    $response['message'] = '대화형 나레이션과 음성이 성공적으로 생성되었습니다.';
    
    // 일반 모드일 때도 audioUrl 포함
    if (!isset($response['audioUrl']) && isset($audioUrl)) {
        $response['audioUrl'] = $audioUrl;
    }
    
    // 듣기평가 모드가 아닐 때 기본값 설정
    if (!isset($response['listeningTest'])) {
        $response['listeningTest'] = false;
        $response['sectionCount'] = 0;
        $response['sectionFiles'] = [];
    }

} catch (Exception $e) {
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    $errorMessage = $e->getMessage();
    $fullErrorMessage = "[{$errorFile}:{$errorLine}] {$errorMessage}";
    
    debug_log("오류 발생: " . $fullErrorMessage);
    $response['success'] = false;
    $response['message'] = $fullErrorMessage;
    $response['errorDetails'] = [
        'file' => $errorFile,
        'line' => $errorLine,
        'message' => $errorMessage
    ];
}

// 출력 버퍼 정리
ob_clean();

// JSON 응답 출력
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>