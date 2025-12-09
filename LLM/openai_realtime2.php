<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 표시 설정 (디버깅용, 운영 환경에서는 비활성화해야 합니다)
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$secret_key = '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';
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1"); 
$role = $userrole->data;
require_login();
$contentsid = $_GET["cid"];  
$contentstype = $_GET["ctype"];  
 $timecreated = time();

if ($role !== 'student') {
    echo '';
} else {
    echo '사용권한이 없습니다.'; 
    exit();
}

// OpenAI Realtime API와의 WebSocket 연결을 위한 설정
require './vendor/autoload.php';

use WebSocket\Client;
// 세션 시작 (더 이상 필요 없다면 이 부분도 제거 가능)
// 세션 시작 (필요한 경우에만)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 클래스 존재 여부 확인
if (class_exists('WebSocket\Client')) {
    echo "WebSocket\Client 클래스가 로드되었습니다.";
} else {
    echo "WebSocket\Client 클래스를 찾을 수 없습니다.";
    exit();
}

// 세션에 저장하지 않고 각 요청마다 새로운 클라이언트 생성
$openai_client = new Client("wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01", [
    'headers' => [
        'Authorization' => 'Bearer' . $secret_key,
        'OpenAI-Beta' => 'realtime=v1'
    ]
]);

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST 데이터 읽기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data['action'] === 'background') {
        // 배경 지식 처리
        $backgroundText = $data['text'];
          
        // 배경 지식을 시스템 메시지로 전송
        $openai_client->send(json_encode([
            'type' => 'conversation.item.create',
            'item' => [
                'type' => 'message',
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $backgroundText
                    ]
                ]
            ]
        ]));

        // 응답 생성 요청
        $openai_client->send(json_encode([
            'type' => 'response.create',
            'response' => [
                'modalities' => ['text'],
                'instructions' => '사용자를 도와주세요.'
            ]
        ]));

        try {
            // OpenAI로부터의 응답 수신
            while ($response = $openai_client->receive()) {
                $responseData = json_decode($response, true);
                if ($responseData['type'] === 'conversation.item.created' && $responseData['item']['role'] === 'assistant') {
                    $assistantText = $responseData['item']['content'][0]['text'];
                    break;
                } elseif (isset($responseData['error'])) {
                    throw new Exception($responseData['error']['message']);
                }
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'type' => 'error',
                'message' => 'OpenAI API 오류: ' . $e->getMessage()
            ]);
            exit();
        } 
            // 테스트 응답을 클라이언트로 전송
    $assistantText = '테스트 응답입니다. 서버에서 OpenAI API 호출을 생략했습니다.';

    // 클라이언트로 응답 전송
    header('Content-Type: application/json');
    echo json_encode([
        'type' => 'text',
        'text' => $assistantText
    ]);
    exit();
    
 
    } elseif ($data['action'] === 'audio') {
        // 오디오 데이터 처리
        $audioBase64 = $data['audio'];
        $audioData = base64_decode($audioBase64);

        // OpenAI가 요구하는 포맷으로 오디오 변환 필요
        // PHP에서 오디오 변환을 위해 exec를 사용하여 ffmpeg 호출
        $inputFile = tempnam(sys_get_temp_dir(), 'input_') . '.webm';
        $outputFile = tempnam(sys_get_temp_dir(), 'output_') . '.wav';

        file_put_contents($inputFile, $audioData);

        // ffmpeg를 사용하여 오디오 변환
        exec("ffmpeg -y -i $inputFile -acodec pcm_s16le -ac 1 -ar 24000 $outputFile 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            // 오류 처리
            header('Content-Type: application/json');
            echo json_encode([
                'type' => 'error',
                'message' => '오디오 처리 중 오류가 발생했습니다.'
            ]);
            unlink($inputFile);
            unlink($outputFile);
            exit();
        }

        // 변환된 오디오 파일을 읽어 Base64로 인코딩
        $processedAudio = base64_encode(file_get_contents($outputFile));

        // OpenAI로 오디오 메시지 전송 (누락된 부분 추가)
        $openai_client->send(json_encode([
            'type' => 'conversation.item.create',
            'item' => [
                'type' => 'message',
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_audio',
                        'audio' => $processedAudio
                    ]
                ]
            ]
        ]));

        // 응답 생성 요청
        $openai_client->send(json_encode([
            "type" => "response.create",
            "response" => [
                "modalities" => ["text"],
                "instructions" => "사용자를 도와주세요."
            ]
        ]));

        try {
            // OpenAI로부터의 응답 수신
            while ($response = $openai_client->receive()) {
                $responseData = json_decode($response, true);
                if ($responseData['type'] === 'conversation.item.created' && $responseData['item']['role'] === 'assistant') {
                    $assistantText = $responseData['item']['content'][0]['text'];
                    break;
                } elseif (isset($responseData['error'])) {
                    throw new Exception($responseData['error']['message']);
                }
            }
        } catch (Exception $e) {
            // 임시 파일 삭제
            unlink($inputFile);
            unlink($outputFile);
        
            header('Content-Type: application/json');
            echo json_encode([
                'type' => 'error',
                'message' => 'OpenAI API 오류: ' . $e->getMessage()
            ]);
            exit();
        }
        
    }  
    // 임시 파일 삭제
    unlink($inputFile);
    unlink($outputFile);

    // OpenAI API 호출 부분을 주석 처리합니다.
  
    // 변환된 오디오 파일을 읽어 Base64로 인코딩
    $processedAudio = base64_encode(file_get_contents($outputFile));

    // OpenAI로 오디오 메시지 전송
    $openai_client->send(json_encode([
        'type' => 'conversation.item.create',
        'item' => [
            'type' => 'message',
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_audio',
                    'audio' => $processedAudio
                ]
            ]
        ]
    ]));

    // 응답 생성 요청
    $openai_client->send(json_encode([
        "type" => "response.create",
        "response" => [
            "modalities" => ["text"],
            "instructions" => "사용자를 도와주세요."
        ]
    ]));

    // OpenAI로부터의 응답 수신
    try {
        while ($response = $openai_client->receive()) {
            $responseData = json_decode($response, true);
            if ($responseData['type'] === 'conversation.item.created' && $responseData['item']['role'] === 'assistant') {
                $assistantText = $responseData['item']['content'][0]['text'];
                break;
            } elseif (isset($responseData['error'])) {
                throw new Exception($responseData['error']['message']);
            }
        }
    } catch (Exception $e) {
        // 임시 파일 삭제
        unlink($inputFile);
        unlink($outputFile);

        header('Content-Type: application/json');
        echo json_encode([
            'type' => 'error',
            'message' => 'OpenAI API 오류: ' . $e->getMessage()
        ]);
        exit();
    }
    

    // 테스트 응답을 클라이언트로 전송
    $assistantText = '테스트 응답입니다. 서버에서 OpenAI API 호출을 생략했습니다.';

    // 클라이언트로 응답 전송
    header('Content-Type: application/json');
    echo json_encode([
        'type' => 'text',
        'text' => $assistantText
    ]);
    exit();
}


?> 

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>실시간 대화 서비스</title>
    <style>
        /* 기존 스타일 코드 유지 */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
        }
        #messages {
            height: 400px;
            overflow-y: scroll;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
        }
        #input-text {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        #startButton {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        #startButton:hover {
            background-color: #45a049;
        }
        #stopButton {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            display: none;
        }
        #stopButton:hover {
            background-color: #e53935;
        }
        #recordButton {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #2196F3;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        #recordButton:hover {
            background-color: #1976D2;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>실시간 대화 서비스</h2>
    <div id="messages"></div>
    <textarea id="input-text" placeholder="여기에 배경 지식을 입력하세요" rows="4"></textarea>
    <button id="startButton">대화 시작</button>
    <button id="stopButton">대화 종료</button>
    <button id="recordButton">마이크 입력</button>
</div>

<script>
    let isRecording = false;
    let mediaRecorder;

    const startButton = document.getElementById('startButton');
    const stopButton = document.getElementById('stopButton');
    const recordButton = document.getElementById('recordButton');
    const messagesDiv = document.getElementById('messages');
    const inputText = document.getElementById('input-text');

    startButton.addEventListener('click', startConversation);
    stopButton.addEventListener('click', stopConversation);
    recordButton.addEventListener('click', toggleRecording);

    function appendMessage(sender, text) {
        const messageElement = document.createElement('div');
        messageElement.innerHTML = `<strong>${sender}:</strong> ${text}`;
        messagesDiv.appendChild(messageElement);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    async function startConversation() {
        // 배경 지식 전송
        const backgroundKnowledge = inputText.value;

        if (backgroundKnowledge) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'background',
                    text: backgroundKnowledge
                })
            })
            .then(response => response.json())
            .then(data => {
    if (data.type === 'text') {
        appendMessage('Assistant', data.text);
    } else if (data.type === 'error') {
        console.error('오류:', data.message);
        alert('오류가 발생했습니다: ' + data.message);
    }
});
        }

        startButton.style.display = 'none';
        stopButton.style.display = 'inline-block';
    }

    function stopConversation() {
        startButton.style.display = 'inline-block';
        stopButton.style.display = 'none';
    }

    async function toggleRecording() {
    if (isRecording) {
        mediaRecorder.stop();
        isRecording = false;
        recordButton.textContent = '마이크 입력';
    } else {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);

            let chunks = [];

            mediaRecorder.ondataavailable = function(event) {
                if (event.data.size > 0) {
                    chunks.push(event.data);
                }
            };

            mediaRecorder.onstop = async function() {
                const blob = new Blob(chunks, { type: 'audio/webm' });
                const arrayBuffer = await blob.arrayBuffer();
                const audioBase64 = arrayBufferToBase64(arrayBuffer);

                // 서버로 전송
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'audio',
                        audio: audioBase64
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.type === 'text') {
                        appendMessage('Assistant', data.text);
                    } else if (data.type === 'error') {
                        console.error('오류:', data.message);
                    }
                });

                chunks = []; // 다음 녹음을 위해 초기화
            };

            mediaRecorder.start();
            isRecording = true;
            recordButton.textContent = '녹음 중지';
        } catch (error) {
            console.error('마이크 접근 오류:', error);
        }
    }
}


    function arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
</script>

</body>
</html>