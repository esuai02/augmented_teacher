<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;

// 에러 표시 설정 (디버깅용, 운영 환경에서는 비활성화해야 합니다)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// API 키를 $CFG에서 가져오기
$secret_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($secret_key)) {
    error_log('[openai_realtime.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Error: API 키가 설정되지 않았습니다.');
}
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1"); 
$role = $userrole->data;
require_login();
$contentsid = $_GET["cid"];  
$contentstype = $_GET["ctype"];  
 $timecreated = time();
 
 

// OpenAI Realtime API와의 WebSocket 연결을 위한 설정
require './vendor/autoload.php';

use WebSocket\Client;

// 세션 시작 (필요한 경우에만)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// WebSocket Client 생성
$openai_client = new Client("wss://api.openai.com/v1/realtime/conversations", [
    'headers' => [
        'Authorization' => 'Bearer ' . $secret_key,
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
          
        try {
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

        try {
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

        // 임시 파일 삭제
        unlink($inputFile);
        unlink($outputFile);

        // 클라이언트로 응답 전송
        header('Content-Type: application/json');
        echo json_encode([
            'type' => 'text',
            'text' => $assistantText
        ]);
        exit();
    }
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
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'background',
                        text: backgroundKnowledge
                    })
                });
                const data = await response.json();
                if (data.type === 'text') {
                    appendMessage('Assistant', data.text);
                } else if (data.type === 'error') {
                    console.error('오류:', data.message);
                    alert('오류가 발생했습니다: ' + data.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('네트워크 오류가 발생했습니다.');
            }
        }

        startButton.style.display = 'none';
        stopButton.style.display = 'inline-block';
    }

    function stopConversation() {
        startButton.style.display = 'inline-block';
        stopButton.style.display = 'none';
    }
    function blobToBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => {
            const dataUrl = reader.result;
            const base64 = dataUrl.split(',')[1];
            resolve(base64);
        };
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
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
                    const audioBase64 = await blobToBase64(blob);
                  
                    // 서버로 전송
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'audio',
                                audio: audioBase64
                            })
                        });
                        const data = await response.json();
                        if (data.type === 'text') {
                            appendMessage('Assistant', data.text);
                        } else if (data.type === 'error') {
                            console.error('오류:', data.message);
                            alert('오류가 발생했습니다: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('네트워크 오류가 발생했습니다.');
                    }

                    chunks = []; // 다음 녹음을 위해 초기화
                };

                mediaRecorder.start();
                isRecording = true;
                recordButton.textContent = '녹음 중지';
            } catch (error) {
                console.error('마이크 접근 오류:', error);
                alert('마이크에 접근할 수 없습니다.');
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
