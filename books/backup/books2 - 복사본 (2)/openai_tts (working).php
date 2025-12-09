<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$secret_key = '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';
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
require_login();

if($role!=='student') echo '';
else echo '사용권한이 없습니다.';
/*
선생님: 오늘은 이차방정식과 이차함수의 관계에 대해 알아볼 거야. 이차방정식과 이차함수는 서로 아주 깊은 관련이 있는데, 특히 그래프와 해의 관계를 살펴보면 쉽게 이해할 수 있어. 준비됐니?

학생: 네, 선생님! 그런데 이차방정식과 이차함수가 어떻게 연결되는지 좀 헷갈려요. 더 자세히 설명해주실 수 있나요?

선생님: 물론이지. 먼저, 이차함수의 일반적인 형태는 와이 이퀄 에이 엑스 제곱 플러스 비 엑스 플러스 씨야. 그리고 이차방정식의 일반적인 형태는 에이 엑스 제곱 플러스 비 엑스 플러스 씨 이퀄 영이지. 여기서 중요한 점은 이차함수의 그래프, 즉 포물선이 엑스축과 만나는 지점들이 이차방정식의 해와 같다는 거야.

학생: 아, 엑스축과 만나는 지점이 방정식의 해랑 같다고요? 어떻게 그게 가능하죠?

선생님: 좋은 질문이야! 이차함수의 그래프에서 와이 값이 영일 때, 엑스좌표가 바로 이차방정식의 해가 돼. 즉, 함수에서 와이가 영인 순간이 이차방정식에서 방정식을 만족하는 엑스값과 같은 거야. 간단하게 말하면, 이차방정식은 이차함수의 그래프가 엑스축을 만나는 점에서 해가 생긴다는 거지.

학생: 그럼, 이차함수가 엑스축과 만나는 지점이 몇 개인지에 따라 이차방정식의 해가 몇 개인지도 알 수 있나요?
*/

?>  

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS 서비스</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: block;
            background-color: #4CAF50;
            background-image: url('https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chatgpt.png');
            background-size: cover;
            background-position: center;
        }
        #input-text {
            width: 100%;
            padding: 15px;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        #startTalk {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        #startTalk:hover {
            background-color: #45a049;
        }
        #audio-player {
            margin-top: 20px;
            width: 100%;
        }
        #audio-control {
            width: 100%;
        }
        #progress-container {
    width: 100%;
    background-color: #f0f0f0;
    border-radius: 5px;
    margin-top: 20px;
    display: none;
}
#progress-bar {
    width: 0;
    height: 20px;
    background-color: #4CAF50;
    border-radius: 5px;
    transition: width 0.3s;
}
    </style>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body><br><br><br>
<div class="container">
        <div class="avatar"></div>
        <textarea id="input-text" placeholder="여기에 텍스트를 입력하세요" rows="4"></textarea>
        <button id="startTalk">음성 생성</button>
        <div id="output-text"></div>
        <div id="progress-container">
    <div id="progress-bar"></div>
</div>
        <div id="audio-player">
            <audio controls id="audio-control">
                <source id="audio-source" type="audio/wav">
                Your browser does not support the audio element.
            </audio>
        </div>
    </div>

    <script>
        const apikey = "<?php echo $secret_key; ?>";
        let audioBuffers = []; // 오디오 버퍼를 저장할 배열

        const generateSpeech = async (text, voice) => {
            const fetchOptions = {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${apikey}`
                },
                body: JSON.stringify({
                    model: "tts-1",
                    voice: voice,
                    input: text
                }),
            };

            try {
                const response = await fetch("https://api.openai.com/v1/audio/speech", fetchOptions);
                if (!response.ok) throw new Error("음성 생성 실패");
                const audioData = await response.arrayBuffer();
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const audioBuffer = await audioContext.decodeAudioData(audioData);
                audioBuffers.push(audioBuffer);
            } catch (error) {
                console.error(error);
            }
        };

        const combineAudioBuffers = (audioBuffers) => {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const totalLength = audioBuffers.reduce((acc, buffer) => acc + buffer.length, 0);
            const combinedBuffer = audioContext.createBuffer(
                audioBuffers[0].numberOfChannels,
                totalLength,
                audioBuffers[0].sampleRate
            );

            let offset = 0;
            for (const buffer of audioBuffers) {
                for (let channel = 0; channel < buffer.numberOfChannels; channel++) {
                    combinedBuffer.copyToChannel(buffer.getChannelData(channel), channel, offset);
                }
                offset += buffer.length;
            }

            return combinedBuffer;
        };

        const playAudio = (audioBuffer) => {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const source = audioContext.createBufferSource();
            source.buffer = audioBuffer;
            source.connect(audioContext.destination);
            source.start();

            const audioPlayer = document.getElementById("audio-player");
            const audioControl = document.getElementById("audio-control");
            const audioSource = document.getElementById("audio-source");

            // AudioBuffer를 Blob으로 변환
            const audioData = audioBufferToWav(audioBuffer);
            const audioBlob = new Blob([audioData], { type: 'audio/wav' });
            const audioUrl = URL.createObjectURL(audioBlob);

            audioSource.src = audioUrl;
            audioControl.load();
            audioPlayer.style.display = 'block';
        };

        // AudioBuffer를 WAV 형식으로 변환하는 함수
        function audioBufferToWav(buffer, opt) {
            opt = opt || {};
            const numChannels = buffer.numberOfChannels;
            const sampleRate = buffer.sampleRate;
            const format = opt.float32 ? 3 : 1;
            const bitDepth = format === 3 ? 32 : 16;

            let result;
            if (numChannels === 2) {
                result = interleave(buffer.getChannelData(0), buffer.getChannelData(1));
            } else {
                result = buffer.getChannelData(0);
            }

            return encodeWAV(result, format, sampleRate, numChannels, bitDepth);
        }

        function interleave(inputL, inputR) {
            const length = inputL.length + inputR.length;
            const result = new Float32Array(length);

            let index = 0;
            let inputIndex = 0;

            while (index < length) {
                result[index++] = inputL[inputIndex];
                result[index++] = inputR[inputIndex];
                inputIndex++;
            }
            return result;
        }

        function encodeWAV(samples, format, sampleRate, numChannels, bitDepth) {
            const bytesPerSample = bitDepth / 8;
            const blockAlign = numChannels * bytesPerSample;

            const buffer = new ArrayBuffer(44 + samples.length * bytesPerSample);
            const view = new DataView(buffer);

            /* RIFF identifier */
            writeString(view, 0, 'RIFF');
            /* RIFF chunk length */
            view.setUint32(4, 36 + samples.length * bytesPerSample, true);
            /* RIFF type */
            writeString(view, 8, 'WAVE');
            /* format chunk identifier */
            writeString(view, 12, 'fmt ');
            /* format chunk length */
            view.setUint32(16, 16, true);
            /* sample format (raw) */
            view.setUint16(20, format, true);
            /* channel count */
            view.setUint16(22, numChannels, true);
            /* sample rate */
            view.setUint32(24, sampleRate, true);
            /* byte rate (sample rate * block align) */
            view.setUint32(28, sampleRate * blockAlign, true);
            /* block align (channel count * bytes per sample) */
            view.setUint16(32, blockAlign, true);
            /* bits per sample */
            view.setUint16(34, bitDepth, true);
            /* data chunk identifier */
            writeString(view, 36, 'data');
            /* data chunk length */
            view.setUint32(40, samples.length * bytesPerSample, true);
            if (format === 1) { // Raw PCM
                floatTo16BitPCM(view, 44, samples);
            } else {
                writeFloat32(view, 44, samples);
            }

            return buffer;
        }

        function writeString(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        }

        function floatTo16BitPCM(output, offset, input) {
            for (let i = 0; i < input.length; i++, offset += 2) {
                const s = Math.max(-1, Math.min(1, input[i]));
                output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
            }
        }

        function writeFloat32(output, offset, input) {
            for (let i = 0; i < input.length; i++, offset += 4) {
                output.setFloat32(offset, input[i], true);
            }
        }

    document.querySelector("#startTalk").addEventListener("click", async () => {
    const text = document.querySelector("#input-text").value;
    const outputText = document.querySelector("#output-text");
    outputText.innerHTML = ""; // 출력 내용 초기화
    audioBuffers = []; // 오디오 버퍼 초기화

    const lines = text.split('\n');
    for (let line of lines) {
        let speaker = line.split(': ')[0]; // 화자 이름
        let cleanedLine = line.split(': ')[1]; // 실제 대화 내용
        if (!cleanedLine) continue;

        // 화자에 따른 성별 음성 선택 로직
        let voice;
        if (["선생님", "엄마", "A"].includes(speaker)) {
            voice = "alloy"; // 예: 여성 목소리
        } else if (["학생", "아빠", "B"].includes(speaker)) {
            voice = "onyx"; // 예: 남성 목소리
        } else {
            voice = "nova"; // 기본 목소리
        }

        // 진행 상황을 표시
        outputText.innerHTML += `<p>${speaker}: "${cleanedLine}" 음성 생성 중...</p>`;

        await generateSpeech(cleanedLine, voice);

        // 음성 생성 완료 후 상태 업데이트
        outputText.innerHTML += `<b style="color:orange;">completed !</b>`;     
    }

    if (audioBuffers.length > 0) {
        const combinedBuffer = combineAudioBuffers(audioBuffers);
        playAudio(combinedBuffer);
        playNotificationSound(); // 전체 완료 후 알림음 재생
        outputText.innerHTML += `<p style="color:green;">모든 음성 생성이 완료되었습니다.</p>`;
    }
 
});


// 알림음을 재생하는 함수
function playNotificationSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    oscillator.type = 'sine'; // 사인파
    oscillator.frequency.setValueAtTime(440, audioContext.currentTime); // 440Hz (A4음)
    oscillator.connect(audioContext.destination);
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.1); // 0.1초 동안 재생
}
    </script>
</body>
</html>