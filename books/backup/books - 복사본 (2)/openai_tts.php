<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$secret_key = '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';
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
require_login();
$contentsid=$_GET["cid"];  
$contentstype=$_GET["ctype"];  
$type=$_GET["type"];  
$timecreated=time();

$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 ");
$inputtext=$thiscnt->outputtext;  
if($role!=='student') echo '';
else 
    {
    echo '사용권한이 없습니다.'; 
    exit();
    }

if($type==NULL)$type='conversation';
$thiscnt=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE '$type' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND gid LIKE '71280'  ORDER BY id DESC LIMIT 1 ");
if($thiscnt->id==NULL)
    {
    $newrecord = new stdClass();
    $newrecord->type = $type;
    $newrecord->contentsid = $contentsid;
    $newrecord->contentstype = $contentstype;
    $newrecord->gid ='71280'; 
    $newrecord->timemodified = $timecreated;
    $newrecord->timecreated = $timecreated; // $timecreated 변수의 값 설정이 필요합니다.
    // 새 레코드를 mdl_abessi_messages 테이블에 삽입
    $DB->insert_record('abrainalignment_gptresults', $newrecord);
    }

$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype' AND url IS NOT NULL ORDER BY id DESC LIMIT 1 ");

if($contentstype==1)
    {
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype' AND url IS NOT NULL ORDER BY id DESC LIMIT 1 ");
        echo '<table align=left><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$thisboard->url.'"target="_blank">WB</a><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/conversation.php?cnttype='.$contentstype.'&type=conversation&cntid='.$contentsid.'&userid='.$USER->id.'&mode=restart">📝</a></td><td><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="q">⬆️</button> <button id="save_button" class="custom-button green" onclick="saveText()">저장</button></td></tr></table>';
    }
else 
    {   
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype'  ORDER BY id DESC LIMIT 1 ");
        echo '<table align=left><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$thisboard->wboardid.'"target="_blank">WB</a><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/conversation.php?cnttype='.$contentstype.'&type=conversation&cntid='.$contentsid.'&userid='.$USER->id.'&mode=restart">📝</a></td><td><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="q">⬆️</button> <button id="save_button" class="custom-button green" onclick="saveText()">저장</button></td></tr></table>';
    }

echo '<script>


function saveText()
  {  
    var Contentsid= \''.$contentsid.'\'; 
    var Contentstype= \''.$contentstype.'\'; 
    //var Resulttext =document.getElementById("input-text").textContent; 
    var Resulttext = document.getElementById("input-text").value;
    alert("대본이 업데이트 되었습니다.");
    //swal("","대본이 업데이트 되었습니다.", {buttons: false,timer:10000000});
    $.ajax({
      url:"check_status.php",
      type: "POST", 
      dataType:"json",
      data : {
      "eventid":5, 
      "inputtext":Resulttext,
      "contentsid":Contentsid,
      "contentstype":Contentstype,
      },
      success:function(data){	
      var Thisuserid=data.thisuserid;	
       }
    })   
    //setTimeout(function(){location.reload();},2000);
  }
document.getElementById("audio_upload").onclick = function ()
{  
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "audio/*"
    var object = null;
    var Contentsid= \''.$contentsid.'\'; 
    var Contentstype= \''.$contentstype.'\'; 


    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
        var formData = new FormData();
        formData.append("audio", file);
        formData.append("contentsid", Contentsid); 
        formData.append("contentstype", Contentstype); 
        $.ajax({
            url: "../LLM/file.php",
            type: "POST",
            cache: false,
            contentType: false,
            processData: false,
            data: formData,
            success: function (data, status, xhr) 
            {
                var parsed_data = JSON.parse(data);
                // View.createAudioObject와 같은 오디오 객체를 생성하는 새 함수가 필요합니다.
                // 이 예에서는 object 변수의 할당을 단순화했습니다.
                object = parsed_data; // 오디오 객체 생성 로직에 맞게 수정 필요
                if (object)
                {
                    // 오디오 객체 처리 로직
                }
            }
        })
    }
    input.click();

} 
</script>';
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
        <style>
        .custom-button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        .green {
            background-color: #4CAF50; /* 녹색 배경 */
            color: white; /* 텍스트 색상 */
        }

        .green:hover {
            background-color: #45a049; /* 호버 시 조금 더 어두운 녹색 */
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
        <textarea id="input-text" placeholder="여기에 텍스트를 입력하세요" rows="4"><?php echo $inputtext; ?></textarea>
        <table align="center"><tr><td><button id="startTalk">음성 생성</button></td></tr></table>
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

    <?php
    // 기존 오디오 URL 확인 - contentstype에 따라 적절한 테이블에서 조회
    $existingAudio = null;

    if ($contentstype == 2) {
        // question 테이블에서 조회
        $existingAudio = $DB->get_record_sql(
            "SELECT audiourl FROM {question} WHERE id = ?",
            array($contentsid)
        );
    } else {
        // icontent_pages 테이블에서 조회
        $existingAudio = $DB->get_record_sql(
            "SELECT audiourl FROM {icontent_pages} WHERE id = ?",
            array($contentsid)
        );
    }

    if ($existingAudio && !empty($existingAudio->audiourl)) {
        echo '<script>
        // 페이지 로드 시 기존 오디오 표시
        window.addEventListener("DOMContentLoaded", function() {
            const audioSource = document.getElementById("audio-source");
            const audioControl = document.getElementById("audio-control");
            const audioPlayer = document.getElementById("audio-player");

            audioSource.src = "' . $existingAudio->audiourl . '";
            audioControl.load();
            audioPlayer.style.display = "block";

            console.log("기존 오디오 파일 로드됨: ' . $existingAudio->audiourl . '");
        });
        </script>';
    }
    ?>

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

            // 서버에 자동 업로드 및 DB 업데이트
            uploadAudioToServer(audioData);
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
   //const text = document.querySelector("#input-text").value;
    const text = document.querySelector("#input-text").value.replace(/\n(?!학생:|선생님:)/g, '');

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
        if (["학생", "아빠", "A"].includes(speaker)) {
            voice = "onyx"; // 예: 남성 목소리
        }
        else  {
            voice = "alloy"; // 예: 여성 목소리
        }   //else {
        //    voice = "nova"; // 기본 목소리
        //}

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
    document.getElementById("save_button").click();
}

// 서버에 오디오 파일 업로드 및 DB 업데이트 함수
function uploadAudioToServer(audioData) {
    const contentsid = "<?php echo $contentsid; ?>";
    const contentstype = "<?php echo $contentstype; ?>";
    const type = "<?php echo $type; ?>";

    // ArrayBuffer를 Base64로 변환
    const base64Audio = arrayBufferToBase64(audioData);

    $.ajax({
        url: 'save_tts_audio.php',
        type: 'POST',
        data: {
            audioData: base64Audio,
            contentsid: contentsid,
            contentstype: contentstype,
            type: type
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('오디오 업로드 성공:', response.message);
                console.log('오디오 URL:', response.audioUrl);

                // 업로드된 파일로 오디오 소스 업데이트
                const audioControl = document.getElementById('audio-control');
                const audioSource = document.getElementById('audio-source');
                audioSource.src = response.audioUrl;
                audioControl.load();

                alert('오디오가 성공적으로 저장되고 재생 가능합니다!');
            } else {
                console.error('오디오 업로드 실패:', response.error);
                alert('오디오 업로드 실패: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX 오류:', error);
            console.error('응답:', xhr.responseText);
            alert('서버 통신 오류가 발생했습니다.');
        }
    });
}

// ArrayBuffer를 Base64로 변환하는 헬퍼 함수
function arrayBufferToBase64(buffer) {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return 'data:audio/wav;base64,' + window.btoa(binary);
}
    </script>
</body>
</html>