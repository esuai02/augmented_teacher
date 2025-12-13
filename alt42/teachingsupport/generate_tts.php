<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
require_once(__DIR__ . '/config.php');
global $DB, $USER;
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // PCM 데이터를 WAV 파일로 변환하는 함수
    // OpenAI TTS API의 PCM 형식: 16-bit PCM, 24kHz, mono
    function pcmToWav($pcmData, $sampleRate = 24000, $channels = 1, $bitsPerSample = 16) {
        $dataSize = strlen($pcmData);
        $fileSize = 36 + $dataSize; // RIFF chunk size (전체 파일 크기 - 8 bytes)
        
        // WAV 헤더 생성 (리틀 엔디안)
        $header = '';
        $header .= 'RIFF';                                    // ChunkID (4 bytes)
        $header .= pack('V', $fileSize);                     // ChunkSize (전체 파일 크기 - 8) (4 bytes, 리틀 엔디안)
        $header .= 'WAVE';                                    // Format (4 bytes)
        $header .= 'fmt ';                                   // Subchunk1ID (4 bytes)
        $header .= pack('V', 16);                            // Subchunk1Size (16 for PCM) (4 bytes, 리틀 엔디안)
        $header .= pack('v', 1);                             // AudioFormat (1 = PCM) (2 bytes, 리틀 엔디안)
        $header .= pack('v', $channels);                     // NumChannels (2 bytes, 리틀 엔디안)
        $header .= pack('V', $sampleRate);                   // SampleRate (4 bytes, 리틀 엔디안)
        $header .= pack('V', $sampleRate * $channels * $bitsPerSample / 8); // ByteRate (4 bytes, 리틀 엔디안)
        $header .= pack('v', $channels * $bitsPerSample / 8); // BlockAlign (2 bytes, 리틀 엔디안)
        $header .= pack('v', $bitsPerSample);                // BitsPerSample (2 bytes, 리틀 엔디안)
        $header .= 'data';                                   // Subchunk2ID (4 bytes)
        $header .= pack('V', $dataSize);                     // Subchunk2Size (4 bytes, 리틀 엔디안)
        
        return $header . $pcmData;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $text = $input['text'] ?? '';
    $voice = $input['voice'] ?? 'nova'; // nova, alloy, echo, fable, onyx, shimmer
    
    if (empty($text)) {
        throw new Exception('텍스트가 없습니다.');
    }

    // OpenAI TTS API 호출 (MP3 형식으로 생성 - 브라우저 호환성)
    // 참고: WAV는 브라우저 호환성 문제가 있어 MP3 사용
    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'tts-1', // tts-1 또는 tts-1-hd
        'input' => $text,
        'voice' => $voice,
        'response_format' => 'mp3' // MP3 형식 (브라우저 호환성 최고)
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $audioData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($audioData, true);
        $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
        throw new Exception('OpenAI TTS API 호출 실패: HTTP ' . $httpCode . ' - ' . $errorMessage);
    }
    
    // 오디오 데이터가 비어있는지 확인
    if (empty($audioData)) {
        throw new Exception('OpenAI TTS API가 빈 응답을 반환했습니다');
    }

    // 오디오 파일 저장
    $uploadDir = __DIR__ . '/audio/';
    
    // 디렉토리가 없으면 생성
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('오디오 디렉토리 생성 실패: ' . $uploadDir);
        }
    }
    
    // 디렉토리 쓰기 권한 확인
    if (!is_writable($uploadDir)) {
        throw new Exception('오디오 디렉토리에 쓰기 권한이 없습니다: ' . $uploadDir);
    }
    
    $filename = 'tts_' . time() . '_' . uniqid() . '.mp3';
    $filepath = $uploadDir . $filename;
    
    // MP3 데이터 저장
    $bytesWritten = file_put_contents($filepath, $audioData);
    if ($bytesWritten === false) {
        throw new Exception('오디오 파일 저장 실패: ' . $filepath);
    }
    
    // 파일이 실제로 생성되었는지 확인
    if (!file_exists($filepath) || filesize($filepath) == 0) {
        throw new Exception('오디오 파일이 제대로 생성되지 않았습니다');
    }

    // 웹 접근 가능한 URL 생성
    $audioUrl = 'audio/' . $filename;

    echo json_encode([
        'success' => true,
        'audioUrl' => $audioUrl,
        'filename' => $filename
    ]);

} catch (Exception $e) {
    // 에러 로깅 (디버깅용)
    error_log('TTS Error: ' . $e->getMessage());
    error_log('TTS Error Trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>