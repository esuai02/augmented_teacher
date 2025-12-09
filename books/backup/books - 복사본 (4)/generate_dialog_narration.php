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
        throw new Exception("API 설정 파일을 찾을 수 없습니다: " . $configFile);
    }
    require_once($configFile);
    debug_log("API 설정 파일 로드 완료");

    // Moodle 설정 포함
    $moodleConfig = "/home/moodle/public_html/moodle/config.php";
    if (file_exists($moodleConfig)) {
        include_once($moodleConfig);
        global $DB, $USER;
        debug_log("Moodle 설정 로드 완료");
    } else {
        throw new Exception("Moodle 설정 파일을 찾을 수 없습니다");
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
        throw new Exception("유효하지 않은 콘텐츠 ID입니다: $contentsid");
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
        throw new Exception("콘텐츠를 찾을 수 없습니다. ID: $contentsid");
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
3. **관찰 지시**: 학생이 문제나 이미지를 보고 있다고 가정하고, \"지금 그림의 오른쪽 위를 보세요\"와 같이 구체적 시각 지침 제공
4. **구조 중심 설명**: 구체적인 계산 과정은 최소화하고, 풀이의 핵심 흐름과 구조를 간결하면서도 몰입감 있게 설명
5. **절차기억 형성 단계**: 설명이 끝난 뒤 반드시 **'절차기억 형성활동을 시작합니다'**라는 문장으로 전환
6. **강조 및 요약**: 전환 이후에는 앞서 설명한 내용을 다시 한 번 강조·요약하며, 유사한 방식으로 더 중요한 사실들을 정리
7. **자가 학습 유도**: 마지막에는 이전 설명을 요약해서 한 번 더 설명하고 \"이제 문제만 보고 풀 수 있는지 생각해 보세요. 스스로 머릿속으로 풀어 보세요.\"로 마무리

# Instructions:
- 모든 숫자, 기호, 알파벳은 반드시 한글 발음으로 변환
- 계산식의 디테일보다는 문제 구조, 조건, 풀이 절차의 흐름을 강조
- 관찰을 지시할 때는 \"지금 그림의 오른쪽 위를 보세요\"와 같이 구체적 시각 지침을 제공
- 설명은 단계마다 요약을 포함하여 기억 정착을 돕도록 구성
- 절차기억 형성 단계에서 반드시 다시 정리, 중요한 사실 강조, 스스로 풀어보기 유도가 포함되어야 함
- 각각의 단락별로 음성파일이 일시정지가 되게 하기 위해 마지막 부분에 @ 기호를 반드시 삽입

# Guidelines:
- 반드시 한글만 사용 (숫자나 기호 절대 금지)
- 하나, 둘, 셋 같은 표현은 쓰지 말고 반드시 일, 이, 삼, 사… 와 같은 아라비아숫자 한글 발음 사용
- 소숫점은 영점으로 읽기 (예: 0.35 → 영점삼오)
- 분수는 \"사분의 삼\"과 같이 올바른 순서로 읽기
- 출력은 오직 지시·설명 대본 형식으로, 다른 목차나 목록, 불필요한 기호 사용 금지
- 각 단락 끝에 @ 기호 필수

# 샘플 예시 스타일:
\"지금 문제의 전체 그림을 천천히 훑어보세요. 무엇을 구하라는지 본문에서 찾아서 한 줄로 직접 써보세요. 맞아요! 정육면체를 엑스 길이로 오 개 쌓아 만든 입체에서, 부피를 에이, 겉넓이를 비로 두고, 에이가 이 비 빼기 삼백 이십과 같을 때의 엑스를 찾는 문제예요. 그럼 이제, 왜 에이와 비를 엑스로 표현해야 하는지 이유를 짧게 적어보세요.@

지금 그림의 오른쪽 위를 보세요. 가장 위에 있는 정육면체 하나를 손가락으로 가리키듯 마음속으로 표시하고, 정육면체 하나의 부피는 엑스의 세제곱이라고 화이트보드 위에 적어보세요. 맞아요! 그럼 이제, 입체 전체의 부피가 왜 정육면체 오 개의 합으로 자연스럽게 연결되는지, 문장 한 줄로 써보세요.@

절차기억 형성활동을 시작합니다.@

방금의 절차를 다시 써보세요. 문제 재정리, 부피를 엑스로 표현, 겉넓이를 엑스로 표현, 조건에 대입하여 엑스만 남기는 방정식 구성, 양수 해 선별. 맞아요! 이 다섯 제목을 한 줄씩 크게 적고, 각 줄 옆에 핵심 단서를 짧게 덧붙여 보세요.@

이제 스스로 풀어보는 연습을 하는 시간입니다. 지금 만든 구조만 보고 본문을 가리지 말고 한 번 더 천천히 읽은 뒤, 화이트보드를 지우거나 빈 빈공간에 새로 동일한 절차를 직접 한 번 다시 써보세요. 이것을 마무리 한 다음 문제만 보고 풀 수 있는지 스스로 검토하는 시간을 가져보세요. 눈을 감거나 허공을 바라보면 전체 구조를 순서대로 떠올려보는 것도 효과적입니다.@\"

이와 같은 방식과 내용 스타일로 작성하세요.";

    // 사용자 프롬프트 (콘텐츠 내용)
    $userPrompt = "다음 수학 콘텐츠를 절차기억 형성 방식으로 설명해주세요:\n\n제목: $title\n\n내용:\n$maintext";

    debug_log("OpenAI API 호출 준비");

    // API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception("OpenAI API 키가 설정되지 않았습니다");
    }

    // 사용자가 제공한 새 API 키 사용
    $apiKey = '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';

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
        throw new Exception("CURL 오류: " . $curlError);
    }

    debug_log("API 응답 코드: $httpCode");

    if ($httpCode !== 200) {
        $errorData = json_decode($gptResponse, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $gptResponse;
        throw new Exception("OpenAI API 오류 (HTTP $httpCode): " . $errorMessage);
    }

    $gptData = json_decode($gptResponse, true);
    if (!isset($gptData['choices'][0]['message']['content'])) {
        throw new Exception("나레이션 텍스트 생성 실패: 응답 형식 오류");
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
            throw new Exception("TTS CURL 오류: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($audioData, true);
            $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : "HTTP $httpCode";
            throw new Exception("TTS 생성 실패: " . $errorMessage);
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
                throw new Exception("오디오 파일 저장 실패");
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
    $response['audioUrl'] = $audioUrl;
    $response['narrationText'] = $narrationText;
    $response['message'] = '대화형 나레이션과 음성이 성공적으로 생성되었습니다.';

} catch (Exception $e) {
    debug_log("오류 발생: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['errorDetails'] = [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// 출력 버퍼 정리
ob_clean();

// JSON 응답 출력
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>