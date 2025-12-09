<?php
// 응답 헤더 설정
header('Content-Type: application/json');

// 오류 보고 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 응답 결과 초기화
$response = [
    'success' => false,
    'text' => '',
    'questions' => [],
    'error' => '',
    'timestamp' => time()
];

// OpenAI API 키 설정 - 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    $response['error'] = 'OPENAI_API_KEY 환경변수가 설정되지 않았습니다. 파일: ' . __FILE__ . ':' . __LINE__;
    echo json_encode($response);
    exit;
}

try {
    // 음성 파일이 업로드되었는지 확인
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('음성 파일 업로드에 실패했습니다.');
    }

    // 임시 파일 경로
    $tempFile = $_FILES['audio']['tmp_name'];
    
    // 파일 확장자 확인 (WebM, WAV, MP3 등 지원)
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($tempFile);
    
    if (strpos($mimeType, 'audio/') !== 0) {
        throw new Exception('유효한 오디오 파일이 아닙니다.');
    }
    
    // 음성 파일을 저장할 디렉토리
    $uploadDir = __DIR__ . '/uploads/';
    
    // 디렉토리가 없으면 생성
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // 고유한 파일명 생성
    $fileName = uniqid('audio_') . '.webm';
    $uploadFile = $uploadDir . $fileName;
    
    // 파일 이동
    if (!move_uploaded_file($tempFile, $uploadFile)) {
        throw new Exception('파일 저장에 실패했습니다.');
    }
    
    // Whisper API 사용을 위한 파일 변환 (WebM을 MP3로 변환)
    // 이는 FFmpeg가 서버에 설치되어 있어야 합니다
    $mp3File = $uploadDir . uniqid('audio_') . '.mp3';
    $command = "ffmpeg -i " . escapeshellarg($uploadFile) . " -vn -ar 44100 -ac 2 -ab 192k -f mp3 " . escapeshellarg($mp3File);
    
    exec($command, $output, $returnVar);
    
    if ($returnVar !== 0) {
        throw new Exception('오디오 파일 변환에 실패했습니다. FFmpeg가 설치되어 있는지 확인하세요.');
    }
    
    // OpenAI Whisper API 호출
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // 헤더 설정
    $headers = [
        'Authorization: Bearer ' . $openai_api_key,
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // POST 데이터 설정
    $postFields = [
        'file' => new CURLFile($mp3File),
        'model' => 'whisper-1',
        'language' => 'ko',  // 한국어로 설정
        'response_format' => 'json'
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    
    // API 요청 실행
    $result = curl_exec($ch);
    
    // 오류 확인
    if (curl_errno($ch)) {
        throw new Exception('Whisper API 호출 중 오류 발생: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // HTTP 응답 코드 확인
    if ($httpCode !== 200) {
        throw new Exception('Whisper API가 오류를 반환했습니다. HTTP 코드: ' . $httpCode . ', 응답: ' . $result);
    }
    
    // JSON 응답 파싱
    $resultData = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('API 응답 파싱 오류: ' . json_last_error_msg());
    }
    
    if (!isset($resultData['text'])) {
        throw new Exception('API 응답에 텍스트가 포함되어 있지 않습니다.');
    }
    
    // 변환된 텍스트 저장
    $transcribedText = $resultData['text'];
    $response['text'] = $transcribedText;
    
    // GPT API를 사용하여 발표 내용 분석 및 질문 생성
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // 헤더 설정
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key,
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // 시스템 메시지와 프롬프트 설정
    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => '당신은 학생의 발표를 평가하고 누락되거나 부족한 부분을 찾아내어 추가 질문을 생성하는 교육 AI입니다. 발표 내용에서 더 자세히 설명이 필요한 부분, 논리적 흐름이 부족한 부분, 또는 완전히 생략된 중요한 내용에 대해 3~5개의 질문을 생성해 주세요. 질문은 간결하고 명확해야 하며, 학생의 답변을 통해 발표 내용을 보완할 수 있도록 해야 합니다. 응답은 JSON 형식으로 질문 배열만 포함해야 합니다.'
            ],
            [
                'role' => 'user',
                'content' => '다음은 학생의 발표 내용을 텍스트로 변환한 것입니다. 이 발표에서 누락되거나 부족한 부분을 찾아 질문을 생성해 주세요: ' . $transcribedText
            ]
        ],
        'temperature' => 0.7,
        'response_format' => ['type' => 'json_object']
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // API 요청 실행
    $gptResult = curl_exec($ch);
    
    // 오류 확인
    if (curl_errno($ch)) {
        throw new Exception('GPT API 호출 중 오류 발생: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // HTTP 응답 코드 확인
    if ($httpCode !== 200) {
        throw new Exception('GPT API가 오류를 반환했습니다. HTTP 코드: ' . $httpCode . ', 응답: ' . $gptResult);
    }
    
    // JSON 응답 파싱
    $gptData = json_decode($gptResult, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('GPT API 응답 파싱 오류: ' . json_last_error_msg());
    }
    
    if (isset($gptData['choices'][0]['message']['content'])) {
        // GPT의 JSON 응답에서 질문 배열 추출
        $questionsJson = json_decode($gptData['choices'][0]['message']['content'], true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($questionsJson['questions'])) {
            $response['questions'] = $questionsJson['questions'];
        } else {
            // 예상 형식이 아닌 경우, 텍스트 처리 시도
            $textResponse = $gptData['choices'][0]['message']['content'];
            preg_match_all('/\d+\.\s*(.*?)(?=\d+\.|$)/s', $textResponse, $matches);
            
            if (!empty($matches[1])) {
                $response['questions'] = array_map('trim', $matches[1]);
            } else {
                $response['questions'] = ['발표 내용을 더 자세히 설명해 주세요.'];
            }
        }
    } else {
        $response['questions'] = ['발표 내용을 더 자세히 설명해 주세요.'];
    }
    
    $response['success'] = true;
    
    // 임시 파일 삭제
    unlink($uploadFile);
    unlink($mp3File);
    
    // 녹음 기록 저장 (옵션)
    $recordingsDir = __DIR__ . '/recordings/';
    if (!file_exists($recordingsDir)) {
        mkdir($recordingsDir, 0777, true);
    }
    
    $recordingData = [
        'timestamp' => time(),
        'text' => $transcribedText,
        'questions' => $response['questions']
    ];
    
    file_put_contents(
        $recordingsDir . 'recording_' . time() . '.json',
        json_encode($recordingData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    
    // API 호출 실패 시 폴백: 임시 텍스트 반환
    if (empty($response['text'])) {
        $response['text'] = '음성 인식 서비스에 연결할 수 없습니다. API 키를 확인하거나 네트워크 연결을 확인하세요.';
        $response['questions'] = ['발표 내용에 대해 더 자세히 설명해 주세요.'];
        $response['success'] = true; // 클라이언트에 오류 대신 메시지 표시
    }
}

// JSON 응답 출력
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 