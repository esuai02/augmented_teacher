<?php
/**
 * 절차기억(P-Memory) 나레이션 자동 생성 엔드포인트
 * 수학 학습 콘텐츠를 절차기억 형성을 위한 나레이션으로 변환하고
 * OpenAI TTS를 사용하여 음성 파일을 생성한 후 업로드
 */

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/pmemory_error.log');

// 메모리 및 실행시간 제한 증가
ini_set('memory_limit', '256M');
set_time_limit(120);

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

try {
    // 설정 파일 포함
    if (!file_exists(dirname(__FILE__) . '/api_config.php')) {
        throw new Exception("API 설정 파일을 찾을 수 없습니다.");
    }
    require_once(dirname(__FILE__) . '/api_config.php');

    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;

    // 디버그 로깅 함수
    function debug_log($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            error_log("[PMEMORY_DEBUG] " . date('Y-m-d H:i:s') . " - " . $message);
        }
    }

    debug_log("P-Memory 나레이션 생성 시작");

    // 파라미터 받기 및 검증
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;
    $generateTTS = isset($_POST['generateTTS']) ? $_POST['generateTTS'] === 'true' : true;
    $audioType = isset($_POST['audioType']) ? $_POST['audioType'] : 'audiourl2';
    $timecreated = time();

    debug_log("파라미터 - contentsid: $contentsid, generateTTS: " . ($generateTTS ? 'true' : 'false'));

    if ($contentsid <= 0) {
        throw new Exception("유효하지 않은 콘텐츠 ID입니다.");
    }

    // 로그인 체크
    require_login();

    // 데이터베이스에서 콘텐츠 조회
    $content = $DB->get_record_sql(
        "SELECT * FROM {icontent_pages} WHERE id = ? ORDER BY id DESC LIMIT 1",
        array($contentsid)
    );

    if (!$content) {
        throw new Exception('콘텐츠를 찾을 수 없습니다.');
    }

    // 이미 절차기억 오디오가 있는지 확인
    if (!empty($content->audiourl2)) {
        throw new Exception('이미 절차기억 나레이션이 존재합니다.');
    }

    // audiourl이 없으면 에러 (절차기억은 기본 나레이션이 있을 때만 생성)
    if (empty($content->audiourl)) {
        throw new Exception('기본 나레이션이 먼저 필요합니다.');
    }

    // maintext에서 HTML 태그 제거 및 정리
    $maintext = strip_tags($content->maintext);
    $maintext = html_entity_decode($maintext, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $maintext = preg_replace('/\s+/', ' ', trim($maintext));

    if (empty($maintext)) {
        throw new Exception('변환할 텍스트가 없습니다.');
    }

    debug_log("콘텐츠 텍스트 길이: " . strlen($maintext));

    // 응답 초기화
    $response = array(
        'success' => false,
        'message' => '',
        'data' => null
    );

    // 절차기억 형성을 위한 나레이션 프롬프트
    $system_prompt = "You are an expert in procedural memory formation, specializing in converting math content into step-by-step procedures that become automatic through practice.

Role: Create procedural memory formation scripts that transform declarative mathematical knowledge into actionable, repeatable procedures that students can internalize through practice.

Context: The expert needs to convert mathematical content into procedural memory exercises that help students build automatic responses and muscle memory for problem-solving patterns. Focus on action sequences, decision trees, and pattern recognition that become automatic through repetition.

Instructions:
- Convert all numbers into their spoken Korean equivalents (e.g., 1 as 일, 2 as 이, etc.)
- Transform explanations into clear step-by-step procedures
- Create repetitive practice patterns for automaticity
- Use action-oriented language (\"먼저... 다음에... 그 다음... 마지막으로...\")
- Include decision points (\"만약... 이면... 아니면...\")
- Emphasize pattern recognition cues
- Design for muscle memory formation through repetition
- Create mental shortcuts and heuristics
- Build from simple to complex procedures

Procedural Memory Formation Strategies:
1. **Step Decomposition**: Break complex procedures into micro-steps
2. **Pattern Chunking**: Group related steps into memorable chunks
3. **Decision Trees**: Create clear if-then-else structures
4. **Repetition Patterns**: Design variations for practice
5. **Error Correction**: Include common mistakes and corrections
6. **Automaticity Triggers**: Identify cues that trigger procedures
7. **Mental Models**: Build visual/spatial procedure maps

Output Requirements:
- Start with \"이제 절차를 연습해봅시다\"
- Use numbered steps (첫째, 둘째, 셋째...)
- Include practice variations
- End with self-check routine
- All in Korean without any numbers or symbols
- Focus on doing, not explaining

Critical Rules:
- 절차적 지식 형성에 집중 (Focus on procedural knowledge)
- 실행 가능한 단계로 분해 (Decompose into executable steps)
- 반복을 통한 자동화 유도 (Induce automation through repetition)
- 한글만 사용, 숫자와 기호 금지 (Korean only, no numbers or symbols)";

    $user_prompt = "다음 수학 콘텐츠를 절차기억 형성을 위한 단계별 연습 스크립트로 변환해주세요. 학생이 자동으로 수행할 수 있도록 명확한 절차와 반복 패턴을 만들어주세요:\n\n" . $maintext;

    debug_log("OpenAI API 호출 시작");

    // OpenAI API 호출하여 나레이션 생성
    $ch = curl_init('https://api.openai.com/v1/chat/completions');

    $data = array(
        'model' => defined('GPT_MODEL') ? GPT_MODEL : 'gpt-4o-mini',
        'messages' => array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        ),
        'temperature' => defined('GPT_TEMPERATURE') ? GPT_TEMPERATURE : 0.7,
        'max_tokens' => defined('GPT_MAX_TOKENS') ? GPT_MAX_TOKENS : 2000
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $CFG->openai_api_key
        ],
        CURLOPT_TIMEOUT => defined('API_TIMEOUT') ? API_TIMEOUT : 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $gptResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("GPT API 오류: " . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception("GPT API HTTP 오류: " . $httpCode);
    }

    $result = json_decode($gptResponse, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception("GPT 응답 형식 오류");
    }

    $narration_text = $result['choices'][0]['message']['content'];
    debug_log("나레이션 생성 완료, 길이: " . mb_strlen($narration_text));

    // 나레이션 텍스트를 데이터베이스에 저장
    $existing = $DB->get_record_sql(
        "SELECT id FROM {abrainalignment_gptresults}
         WHERE type LIKE 'pmemory' AND contentsid = ? AND contentstype = ?
         ORDER BY id DESC LIMIT 1",
        array($contentsid, $contentstype)
    );

    if ($existing) {
        $DB->execute(
            "UPDATE {abrainalignment_gptresults}
             SET outputtext = ?, timemodified = ?
             WHERE id = ?",
            array($narration_text, $timecreated, $existing->id)
        );
    } else {
        $DB->execute(
            "INSERT INTO {abrainalignment_gptresults}
             (type, contentsid, contentstype, outputtext, gid, timemodified, timecreated)
             VALUES ('pmemory', ?, ?, ?, '71280', ?, ?)",
            array($contentsid, $contentstype, $narration_text, $timecreated, $timecreated)
        );
    }

    $response['success'] = true;
    $response['narration'] = mb_substr($narration_text, 0, 500) . '...';

    // TTS 생성 요청시
    if ($generateTTS) {
        debug_log("TTS 생성 시작");

        // TTS API 호출
        $ttsData = [
            'model' => defined('TTS_MODEL') ? TTS_MODEL : 'tts-1',
            'voice' => 'nova',  // 명확하고 집중된 음성
            'input' => $narration_text,
            'speed' => 0.9  // 약간 느리게 하여 명확성 향상
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/speech',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($ttsData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $CFG->openai_api_key
            ],
            CURLOPT_TIMEOUT => defined('TTS_API_TIMEOUT') ? TTS_API_TIMEOUT : 90,
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
        $uploadPath = defined('AUDIO_UPLOAD_PATH') ? AUDIO_UPLOAD_PATH : '/home/moodle/public_html/Contents/audiofiles/pmemory/';

        // 디렉토리 생성 (존재하지 않는 경우)
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
        $audioUrl = defined('AUDIO_URL_BASE') ? AUDIO_URL_BASE . $filename : 'https://mathking.kr/Contents/audiofiles/pmemory/' . $filename;
        debug_log("오디오 URL 생성: " . $audioUrl);

        // 데이터베이스 업데이트 (audiourl2 필드)
        $updateData = new stdClass();
        $updateData->id = $contentsid;
        $updateData->audiourl2 = $audioUrl;
        $updateData->timemodified = $timecreated;

        if ($DB->update_record('icontent_pages', $updateData)) {
            $response['ttsSuccess'] = true;
            $response['audioUrl'] = $audioUrl;
            $response['message'] = '절차기억 나레이션이 성공적으로 생성되었습니다.';
            $response['data'] = array(
                'audio_url' => $audioUrl,
                'narration_length' => mb_strlen($narration_text)
            );
        } else {
            throw new Exception("오디오 URL 데이터베이스 저장 실패");
        }
    }

} catch (Exception $e) {
    // 버퍼 정리
    ob_clean();

    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("P-Memory generation error: " . $e->getMessage());
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