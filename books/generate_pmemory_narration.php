<?php
/////////////////////////////// 절차기억 나레이션 자동 생성 및 TTS 생성 (improveprompt.php 프롬프트 사용) ///////////////////////////////

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정 - 모든 에러 출력 차단
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/pmemory_narration_error.log');

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
        $logMessage = "[PMEMORY_NARRATION] " . date('Y-m-d H:i:s') . " - " . $message;
        error_log($logMessage);
        $response['debug'][] = $message;
    }

    debug_log("절차기억 나레이션 생성 시작");

    // Moodle 설정 포함 (openai_tts.php와 동일)
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    debug_log("Moodle 설정 로드 완료");
    $secret_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';

    // 파라미터 받기 및 검증
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;
    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;
    $generateTTS = isset($_POST['generateTTS']) ? $_POST['generateTTS'] === 'true' : true;
    $audioType = isset($_POST['audioType']) ? $_POST['audioType'] : 'audiourl2';
    $timecreated = time();

    debug_log("파라미터 - contentsid: $contentsid, generateTTS: " . ($generateTTS ? 'true' : 'false') . ", audioType: $audioType");

    if ($contentsid <= 0) {
        throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] 유효하지 않은 콘텐츠 ID입니다: $contentsid");
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
        throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] 콘텐츠를 찾을 수 없습니다. ID: $contentsid");
    }

    // 콘텐츠 텍스트 준비
    $maintext = strip_tags($cnttext->maintext); // HTML 태그 제거
    $title = $cnttext->title;
    debug_log("콘텐츠 로드 완료 - 제목: $title");

    // ============================================================
    // 절차기억 형성 나레이터 프롬프트 (improveprompt.php에서 동적 로드)
    // ============================================================
    
    // 1. 사용자 커스텀 프롬프트 불러오기 (improveprompt.php와 동일한 방식)
    $customPrompt = $DB->get_record_sql("SELECT * FROM {gptprompts} 
        WHERE userid = ? AND type = 'pmemory' 
        ORDER BY timemodified DESC LIMIT 1", array($USER->id));
    
    // 2. 기본 프롬프트 정의 (단계별 설명 방식 - 절차기억 형성용)
    $defaultPrompt = <<<PROMPT
# Role: act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for step by step instructions

입력된 수학문제와 풀이 정보를 분석 후 한국어로 단계별 풀이를 안내하는 수학 듣기평가를 위한 지시어로 변경해줘.

계산 등 자세한 내용 보다 절차에 대한 구조를 강화시키는 것이 목적임. 서술한 내용을 선생님이 직접채점하는 상황.

먼저, 문제 내용을 한 번 정리하는 것으로 시작하는데 이것도 탐구를 유도하고 해소작용으로 답을 제시하는 방식으로 해줘.

다음으로 무엇을 생각해야할지를 궁금하게 만들고 답을하며 실행사항을 제시하는 도제학습 스타일로 작성.

학생이 문제나 이미지를 보고 있다고 가정하고, 관찰 지시를 통해 시각적 이해를 강화.

구체적인 계산 과정은 최소화하고, 풀이의 핵심 흐름과 구조를 간결하면서도 몰입감 있게 설명.

설명이 끝난 뒤에는 반드시 **'절차기억 형성활동을 시작합니다'**라는 문장으로 전환.

전환 이후에는 앞서 설명한 내용을 다시 한 번 강조·요약하며, 유사한 방식으로 더 중요한 사실들을 정리.

마지막에는 이전 설명을 요약해서 한 번 더 설명하고 "이제 문제만 보고 풀 수 있는지 생각해 보세요. 스스로 머릿속으로 풀어 보세요." 라는 식으로 학생이 혼자 문제를 시도하도록 유도.

# Instructions:
- 모든 숫자, 기호, 알파벳은 반드시 한글 발음으로 변환.
- 계산식의 디테일보다는 문제 구조, 조건, 풀이 절차의 흐름을 강조.
- 관찰을 지시할 때는 "지금 그림의 오른쪽 위를 보세요"와 같이 구체적 시각 지침을 제공.
- 설명은 단계마다 요약을 포함하여 기억 정착을 돕도록 구성.
- 절차기억 형성 단계에서 반드시 다시 정리, 중요한 사실 강조, 스스로 풀어보기 유도가 포함되어야 함.
- **각각의 단락별로 @ 기호를 마지막 부분에 반드시 추가해야 함. 이는 음성파일 일시정지 지점을 표시하는 것임.**

# Guidelines:
- 반드시 한글만 사용. 숫자나 기호 절대 금지.
- 하나, 둘, 셋 같은 표현은 쓰지 말고 반드시 일, 이, 삼, 사… 와 같은 아라비아숫자 한글 발음 사용.
- 소숫점은 영점으로 읽기. 예: 0.35 → 영점삼오
- 분수는 "사분의 삼"과 같이 올바른 순서로 읽기.
- 출력은 오직 지시·설명 대본 형식으로, 다른 목차나 목록, 불필요한 기호 사용 금지.
- 각 단락 끝에는 반드시 @ 기호를 추가.

중요: 응답은 오직 나레이션 대본만 출력하세요. 다른 설명이나 서론, 부연 설명 없이 즉시 나레이션으로 시작하세요.
PROMPT;

    // 3. 커스텀 프롬프트가 있으면 우선 사용, 없으면 기본 프롬프트 사용
    // (@ 기호 검증 없이 사용자 프롬프트 존중)
    if ($customPrompt && !empty($customPrompt->prompttext)) {
        $systemPrompt = $customPrompt->prompttext;
        debug_log("사용자 커스텀 프롬프트 사용 (userid: " . $USER->id . ", modified: " . date('Y-m-d H:i:s', $customPrompt->timemodified) . ")");
    } else {
        $systemPrompt = $defaultPrompt;
        debug_log("기본 프롬프트 사용 (단계별 설명 방식)");
    }
    
    // 응답에 사용된 프롬프트 정보 추가 (디버깅용)
    $response['prompt_source'] = ($systemPrompt === $defaultPrompt) ? 'default' : 'custom';
    $response['prompt_preview'] = mb_substr($systemPrompt, 0, 200) . '...';
 
    // 사용자 프롬프트 (콘텐츠 내용)
    $userPrompt = "다음 수학 콘텐츠를 절차기억 형성 방식으로 설명해주세요:\n\n제목: $title\n\n내용:\n$maintext";

    debug_log("OpenAI API 호출 준비");

    // OpenAI GPT API 호출
    $apiUrl = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret_key
    ];

    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_completion_tokens' => 4000,
        'temperature' => 0.7
    ]; 

    debug_log("OpenAI API 호출 시작 (모델: gpt-4o)");

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
        throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] CURL 오류: " . $curlError);
    }

    debug_log("API 응답 코드: $httpCode");

    if ($httpCode !== 200) {
        $errorData = json_decode($gptResponse, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $gptResponse;
        throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] OpenAI API 오류 (HTTP $httpCode): " . $errorMessage);
    }

    $gptData = json_decode($gptResponse, true);
    if (!isset($gptData['choices'][0]['message']['content'])) {
        throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] 나레이션 텍스트 생성 실패: 응답 형식 오류");
    }

    $narrationText = $gptData['choices'][0]['message']['content'];
    debug_log("나레이션 텍스트 생성 완료 (길이: " . mb_strlen($narrationText) . "자)");

    // 나레이션 텍스트 저장 (reflections0 필드 사용)
    try {
        $DB->execute("UPDATE {icontent_pages} SET reflections0 = ? WHERE id = ?",
            array($narrationText, $contentsid));
        debug_log("나레이션 텍스트 icontent_pages.reflections0 저장 완료");
    } catch (Exception $e) {
        debug_log("icontent_pages 저장 실패 (무시): " . $e->getMessage());
    }

    // ============================================================
    // openai_tts_pmemory.php에서 사용하도록 mdl_abrainalignment_gptresults 테이블에도 저장
    // ============================================================
    try {
        // 기존 레코드 확인
        $existingRecord = $DB->get_record_sql(
            "SELECT id FROM {abrainalignment_gptresults} 
             WHERE type = 'pmemory' AND contentsid = ? AND contentstype = ? 
             ORDER BY id DESC LIMIT 1",
            array($contentsid, $contentstype)
        );

        if ($existingRecord && $existingRecord->id) {
            // 기존 레코드 업데이트
            $DB->execute(
                "UPDATE {abrainalignment_gptresults} SET outputtext = ?, timemodified = ? WHERE id = ?",
                array($narrationText, $timecreated, $existingRecord->id)
            );
            debug_log("abrainalignment_gptresults 업데이트 완료 (id: " . $existingRecord->id . ")");
        } else {
            // 새 레코드 삽입
            $newRecord = new stdClass();
            $newRecord->type = 'pmemory';
            $newRecord->contentsid = $contentsid;
            $newRecord->contentstype = $contentstype;
            $newRecord->gid = '71280';
            $newRecord->outputtext = $narrationText;
            $newRecord->timemodified = $timecreated;
            $newRecord->timecreated = $timecreated;
            
            $newId = $DB->insert_record('abrainalignment_gptresults', $newRecord);
            debug_log("abrainalignment_gptresults 신규 삽입 완료 (id: " . $newId . ")");
        }
        
        $response['saved_to_tts_db'] = true;
        debug_log("openai_tts_pmemory.php 연동용 DB 저장 완료");
    } catch (Exception $e) {
        debug_log("abrainalignment_gptresults 저장 실패: " . $e->getMessage());
        $response['saved_to_tts_db'] = false;
        $response['tts_db_error'] = $e->getMessage();
    }

    if (!$generateTTS) {
        // TTS 생성 없이 텍스트만 반환
        $response['success'] = true;
        $response['narrationText'] = $narrationText;
        $response['message'] = '절차기억 나레이션 텍스트가 생성되었습니다.';
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
                $fileName = 'cid' . $contentsid . 'ct' . $contentstype . '_pmemory_section' . $sectionNum . '.mp3';
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
            'mode' => 'pmemory_listening_test',
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
            throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] TTS CURL 오류: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($audioData, true);
            $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : "HTTP $httpCode";
            throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] TTS 생성 실패: " . $errorMessage);
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
        
        $finalFileName = 'cid' . $contentsid . 'ct' . $contentstype . '_pmemory.mp3';
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
                throw new Exception("[generate_pmemory_narration.php:" . __LINE__ . "] 오디오 파일 저장 실패");
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
    $response['message'] = '절차기억 나레이션과 음성이 성공적으로 생성되었습니다.';
    
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

