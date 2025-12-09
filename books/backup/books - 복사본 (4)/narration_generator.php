<?php
/**
 * 나레이션 자동 생성 엔드포인트
 *
 * 수학 콘텐츠를 한국어 대화형 나레이션으로 변환하고
 * OpenAI TTS를 사용하여 음성 파일을 생성한 후 업로드
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// OpenAI API 키 설정
define('OPENAI_API_KEY_SECURE', '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');

// 에러 리포팅 설정
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);

// JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 응답 데이터 초기화
$response = array(
    'success' => false,
    'message' => '',
    'data' => null
);

try {
    // POST 데이터 검증
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 필수 파라미터 확인
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $contentstype = isset($_POST['contentstype']) ? intval($_POST['contentstype']) : 1;

    if ($contentsid <= 0) {
        throw new Exception('유효하지 않은 콘텐츠 ID입니다.');
    }

    // 데이터베이스에서 콘텐츠 조회
    $content = $DB->get_record_sql(
        "SELECT * FROM mdl_icontent_pages WHERE id = ? ORDER BY id DESC LIMIT 1",
        array($contentsid)
    );

    if (!$content) {
        throw new Exception('콘텐츠를 찾을 수 없습니다.');
    }

    // 이미 오디오가 있는지 확인
    if (!empty($content->audiourl)) {
        throw new Exception('이미 나레이션이 존재합니다.');
    }

    // maintext에서 HTML 태그 제거 및 정리
    $maintext = strip_tags($content->maintext);
    $maintext = html_entity_decode($maintext, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $maintext = preg_replace('/\s+/', ' ', trim($maintext));

    if (empty($maintext)) {
        throw new Exception('변환할 텍스트가 없습니다.');
    }

    // 나레이션 프롬프트 정의
    $system_prompt = "You are a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for Korean students.

작은 의미단위로 완결성있게 설명 후 요약. 그리고 준비하는 시간을 가지고 준비가 되면 다음 소주제로 넘어가는 방식으로 잘게 잘게 쪼개서 진행해줘. 작은 예시들을 통하여 확실히 확인하는 방식으로 진행해줘.

Role: Act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.

Context: The expert needs to convert any mathematical content into a script that sounds natural when spoken in Korean, maintaining the sequence and coherence of the original content. The narrator is tasked with making the content understandable and engaging, using explanations, examples, and analogies, especially clarifying any potentially confusing parts.

Instructions:
- Convert all numbers into their spoken Korean equivalents (e.g., 1 as 일, 2 as 이, etc.). 최종 결과물에는 한글만 존재해야하며 다른 기호나 숫자는 존재하지 않아야 합니다.
- Ensure all symbols, mathematical expressions, and alphabets are converted into their phonetic Korean readings.
- Maintain the logical sequence and coherence of the original mathematical content while transforming it into a narration script.
- Add explanatory notes, examples, or analogies to aid understanding, particularly clarifying any complex or confusing parts.
- Summarize each topic unit clearly, ensuring the script is engaging and understandable for a broad audience.
- 대화식을 요청하면 입력된 내용을 선생님과 학생의 대화형식으로 구성해줘. 특히, 학생은 헷갈리는 부분을 질문하며 다른 학생들이 대화를 들었을 때 도움이 되도록 해줘.
- 학생들이 컨텐츠를 보며 대화를 듣도록 제공된 내용에 대해 순서대로 읽으며 진행해줘.

Guidelines:
- The language should be clear, professional, and accessible, suitable for a mathematics educator.
- 생성결과에 아무리 간단한 경우라고 해도 반드시 숫자, 기호 대신 한글만 사용되어야 해.
- 마지막에는 학생이 간단하게 내용을 요약하고 점검하는 멘트를 추가하고 후속학습을 추천해줘.

Output format: Plain text suitable for script reading in dialogue format.

중요한 규칙:
- 어떤 생성결과도 한글만 사용해줘, 특수문자나 숫자, 기호 등은 절대로 사용하지 말아줘
- 분수읽을 때 오류 발생 주의 3/4는 사분의 삼이야. 종종 삼 사분의 삼이라고 잘못읽는 경우가 있어 조심해.
- : (콜론)은 학생과 선생님 뒤에만 나타나게 해. 다른 상황에서 콜론을 사용하는 일은 절대 금지
- 결과 생성은 반드시 대화형식으로 자연스럽게 이어줘. 절대로 목록화하지 마.
- 숫자를 표현할 때 반드시 아라비아숫자 읽기 (일, 이, 삼, 사, .... ,이십, 이십일..)를 사용해줘
- 하나, 둘, 셋, 넷, 다섯, 여섯, 일곱, 여덟, 아홉, 열, 열하나 ... 스물 등과 같은 표현은 사용하지 말아줘.
- 소숫점을 잘 식별해서 읽어줘 0.35 (영점삼오)
- 선생님과 학생 사이의 대화 전환이 있을 때만 줄바꿈이 가능해. 그렇지 않은 경우 반드시 하나의 단락을 유지해줘.
- 소주제나 목차나 목록형으로 생성 금지. 주어진 예시처럼 전체결과가 대화식이어야 해. 단락 나누지 마.";

    $user_prompt = "다음 수학 콘텐츠를 한국어 대화형 나레이션으로 변환해주세요. 선생님과 학생의 자연스러운 대화로 구성하고, 모든 숫자와 기호는 한글로 표현해주세요:\n\n" . $maintext;

    // OpenAI API 호출하여 나레이션 생성
    $narration_text = generateNarration($system_prompt, $user_prompt);

    if (empty($narration_text)) {
        throw new Exception('나레이션 생성에 실패했습니다.');
    }

    // 나레이션 텍스트를 대화 단위로 분리
    $dialogues = parseDialogues($narration_text);

    if (empty($dialogues)) {
        throw new Exception('대화 파싱에 실패했습니다.');
    }

    // TTS 음성 생성 및 합성
    $audio_file_path = generateTTSAudio($dialogues, $contentsid, $contentstype);

    if (!$audio_file_path) {
        throw new Exception('음성 파일 생성에 실패했습니다.');
    }

    // 데이터베이스 업데이트
    $audio_url = 'https://mathking.kr/audiofiles/' . basename($audio_file_path);

    // mdl_icontent_pages 테이블 업데이트
    $DB->execute(
        "UPDATE mdl_icontent_pages SET audiourl = ? WHERE id = ?",
        array($audio_url, $contentsid)
    );

    // mdl_abrainalignment_gptresults에 나레이션 텍스트 저장
    $timecreated = time();
    $existing = $DB->get_record_sql(
        "SELECT id FROM mdl_abrainalignment_gptresults
         WHERE type LIKE 'conversation' AND contentsid = ? AND contentstype = ?
         ORDER BY id DESC LIMIT 1",
        array($contentsid, $contentstype)
    );

    if ($existing) {
        $DB->execute(
            "UPDATE mdl_abrainalignment_gptresults
             SET outputtext = ?, timemodified = ?
             WHERE id = ?",
            array($narration_text, $timecreated, $existing->id)
        );
    } else {
        $DB->execute(
            "INSERT INTO mdl_abrainalignment_gptresults
             (type, contentsid, contentstype, outputtext, gid, timemodified, timecreated)
             VALUES ('conversation', ?, ?, ?, '71280', ?, ?)",
            array($contentsid, $contentstype, $narration_text, $timecreated, $timecreated)
        );
    }

    // 성공 응답
    $response['success'] = true;
    $response['message'] = '나레이션이 성공적으로 생성되었습니다.';
    $response['data'] = array(
        'audio_url' => $audio_url,
        'narration_length' => mb_strlen($narration_text)
    );

} catch (Exception $e) {
    // 에러 로깅
    error_log('Narration Generator Error: ' . $e->getMessage());

    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// JSON 응답 출력
echo json_encode($response);
exit;

/**
 * OpenAI API를 사용하여 나레이션 생성
 */
function generateNarration($system_prompt, $user_prompt) {
    $max_retries = 3;
    $retry_count = 0;

    while ($retry_count < $max_retries) {
        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');

            $data = array(
                'model' => 'gpt-4',
                'messages' => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user', 'content' => $user_prompt)
                ),
                'temperature' => 0.7,
                'max_tokens' => 4000
            );

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY_SECURE
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return $result['choices'][0]['message']['content'];
                }
            }

            $retry_count++;
            if ($retry_count < $max_retries) {
                sleep(2); // 재시도 전 대기
            }

        } catch (Exception $e) {
            error_log('OpenAI API Error: ' . $e->getMessage());
            $retry_count++;
        }
    }

    return null;
}

/**
 * 나레이션 텍스트를 대화 단위로 파싱
 */
function parseDialogues($narration_text) {
    $dialogues = array();

    // 선생님: 또는 학생: 패턴으로 분리
    $lines = preg_split('/(?=선생님:|학생:)/', $narration_text, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (strpos($line, '선생님:') === 0) {
            $dialogues[] = array(
                'speaker' => 'teacher',
                'voice' => 'alloy',
                'text' => trim(str_replace('선생님:', '', $line))
            );
        } elseif (strpos($line, '학생:') === 0) {
            $dialogues[] = array(
                'speaker' => 'student',
                'voice' => 'onyx',
                'text' => trim(str_replace('학생:', '', $line))
            );
        }
    }

    return $dialogues;
}

/**
 * TTS 음성 생성 및 파일 저장
 */
function generateTTSAudio($dialogues, $contentsid, $contentstype) {
    $audio_files = array();
    $temp_dir = '/tmp/tts_' . uniqid();
    mkdir($temp_dir, 0777, true);

    try {
        // 각 대화를 개별 TTS로 생성
        foreach ($dialogues as $index => $dialogue) {
            $audio_file = generateSingleTTS(
                $dialogue['text'],
                $dialogue['voice'],
                $temp_dir . '/part_' . $index . '.mp3'
            );

            if ($audio_file) {
                $audio_files[] = $audio_file;
            }
        }

        if (empty($audio_files)) {
            throw new Exception('No audio files generated');
        }

        // 오디오 파일 합성
        $output_filename = 'cid' . $contentsid . 'ct' . $contentstype . '_audio.mp3';
        $output_path = '/home/moodle/public_html/audiofiles/' . $output_filename;

        // FFmpeg를 사용하여 오디오 파일 합성
        if (count($audio_files) > 1) {
            $concat_list = $temp_dir . '/concat.txt';
            $list_content = '';
            foreach ($audio_files as $file) {
                $list_content .= "file '" . $file . "'\n";
            }
            file_put_contents($concat_list, $list_content);

            $cmd = "ffmpeg -f concat -safe 0 -i " . escapeshellarg($concat_list) .
                   " -c copy " . escapeshellarg($output_path) . " 2>&1";
            exec($cmd, $output, $return_var);

            if ($return_var !== 0) {
                throw new Exception('Audio concatenation failed');
            }
        } else {
            // 단일 파일인 경우 그대로 복사
            copy($audio_files[0], $output_path);
        }

        // 임시 파일 정리
        foreach ($audio_files as $file) {
            @unlink($file);
        }
        @unlink($concat_list);
        @rmdir($temp_dir);

        return $output_path;

    } catch (Exception $e) {
        // 에러 발생 시 임시 파일 정리
        foreach ($audio_files as $file) {
            @unlink($file);
        }
        @rmdir($temp_dir);

        error_log('TTS Generation Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 단일 TTS 생성
 */
function generateSingleTTS($text, $voice, $output_file) {
    try {
        $ch = curl_init('https://api.openai.com/v1/audio/speech');

        $data = array(
            'model' => 'tts-1',
            'voice' => $voice,
            'input' => $text
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY_SECURE
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $audio_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $audio_data) {
            file_put_contents($output_file, $audio_data);
            return $output_file;
        }

        return null;

    } catch (Exception $e) {
        error_log('Single TTS Error: ' . $e->getMessage());
        return null;
    }
}

?>