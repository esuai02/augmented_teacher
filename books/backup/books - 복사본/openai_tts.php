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

// 이미지 추출 함수
function extractContentImages($contentsid, $contentstype, $DB) {
    $images = array();

    if($contentstype == 1) {
        // icontent_pages 테이블에서 추출
        $content = $DB->get_record_sql("SELECT pageicontent FROM mdl_icontent_pages WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
        if($content && $content->pageicontent) {
            $htmlDom = new DOMDocument();
            @$htmlDom->loadHTML($content->pageicontent);
            $imageTags = $htmlDom->getElementsByTagName('img');
            foreach($imageTags as $imageTag) {
                $imgSrc = $imageTag->getAttribute('src');
                if(strpos($imgSrc, '.png') !== false || strpos($imgSrc, '.jpg') !== false) {
                    $images[] = array('type' => 'content', 'url' => $imgSrc);
                }
            }
        }
    } elseif($contentstype == 2) {
        // question 테이블에서 추출
        $question = $DB->get_record_sql("SELECT questiontext, generalfeedback, mathexpression FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
        if($question) {
            // questiontext에서 이미지 추출
            if($question->questiontext) {
                $htmlDom = new DOMDocument();
                @$htmlDom->loadHTML($question->questiontext);
                $imageTags = $htmlDom->getElementsByTagName('img');
                foreach($imageTags as $imageTag) {
                    $imgSrc = $imageTag->getAttribute('src');
                    if((strpos($imgSrc, '.png') !== false || strpos($imgSrc, '.jpg') !== false)
                       && strpos($imgSrc, 'hintimages') === false) {
                        $images[] = array('type' => 'question', 'url' => $imgSrc);
                    }
                }
            }

            // generalfeedback에서 이미지 추출
            if($question->generalfeedback) {
                $htmlDom2 = new DOMDocument();
                @$htmlDom2->loadHTML($question->generalfeedback);
                $imageTags2 = $htmlDom2->getElementsByTagName('img');
                foreach($imageTags2 as $imageTag2) {
                    $imgSrc2 = $imageTag2->getAttribute('src');
                    if(strpos($imgSrc2, 'MATRIX/MATH') !== false && strpos($imgSrc2, 'hintimages') === false) {
                        $images[] = array('type' => 'solution', 'url' => $imgSrc2);
                    }
                }
            }
        }
    }

    return $images;
}

// 콘텐츠 이미지 추출
$contentImages = extractContentImages($contentsid, $contentstype, $DB);
$contentImagesJson = json_encode($contentImages);

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
.upload-status {
    margin-top: 15px;
    padding: 10px;
    border-radius: 5px;
    display: none;
}
.upload-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.upload-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
.upload-progress {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
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
        <div id="upload-status" class="upload-status"></div>
        <div id="audio-player">
            <audio controls id="audio-control">
                <source id="audio-source" type="audio/wav">
                Your browser does not support the audio element.
            </audio>
        </div>
    </div>

    <script>
        const apikey = "<?php echo $secret_key; ?>";
        const contentImages = <?php echo $contentImagesJson; ?>; // 추출된 이미지 정보
        let audioBuffers = []; // 오디오 버퍼를 저장할 배열
        const imageDescriptionCache = {}; // 이미지 설명 캐시

        // OpenAI Vision API를 사용한 이미지 설명 생성
        const generateImageDescription = async (imageUrl) => {
            // 캐시 확인
            if (imageDescriptionCache[imageUrl]) {
                return imageDescriptionCache[imageUrl];
            }

            try {
                const response = await fetch("https://api.openai.com/v1/chat/completions", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Authorization": `Bearer ${apikey}`
                    },
                    body: JSON.stringify({
                        model: "gpt-4o",
                        messages: [{
                            role: "user",
                            content: [
                                {
                                    type: "text",
                                    text: "이 이미지의 교육적 내용을 한국어로 간단히 설명해주세요. 학생이 이해하기 쉽게 설명해주세요."
                                },
                                {
                                    type: "image_url",
                                    image_url: {
                                        url: imageUrl.startsWith('http') ? imageUrl : `https://mathking.kr${imageUrl}`
                                    }
                                }
                            ]
                        }],
                        max_tokens: 300
                    })
                });

                if (!response.ok) {
                    console.error("Vision API 에러:", response.status);
                    return null;
                }

                const data = await response.json();
                const description = data.choices[0].message.content;

                // 캐시 저장
                imageDescriptionCache[imageUrl] = description;
                return description;
            } catch (error) {
                console.error("이미지 설명 생성 실패:", error);
                return null;
            }
        };

        // 기본 TTS 생성 함수
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

        // 이미지 설명이 포함된 향상된 TTS 생성
        const generateEnhancedSpeech = async (text, voice) => {
            let enhancedText = text;

            // 이미지가 있으면 설명 추가
            if (contentImages && contentImages.length > 0) {
                for (const image of contentImages) {
                    const description = await generateImageDescription(image.url);
                    if (description) {
                        if (image.type === 'question' || image.type === 'content') {
                            // 문제나 콘텐츠 이미지는 텍스트 앞에 추가
                            enhancedText = `그림을 보면서 들어주세요. ${description} 이제 본문 내용입니다. ${enhancedText}`;
                        } else if (image.type === 'solution') {
                            // 해설 이미지는 텍스트 뒤에 추가
                            enhancedText += ` 해설 그림을 보면, ${description}`;
                        }
                    }
                }
            }

            // TTS 생성
            await generateSpeech(enhancedText, voice);
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

            // 자동으로 파일 업로드
            uploadAudioFile(audioBlob);
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

    // 자동 업로드 함수
    const uploadAudioFile = async (audioBlob) => {
        const outputText = document.querySelector("#output-text");
        const uploadStatus = document.querySelector("#upload-status");
        const contentsId = '<?php echo $contentsid; ?>';
        const contentsType = '<?php echo $contentstype; ?>';

        // 파일명 생성
        const filename = `cid${contentsId}ct${contentsType}_audio.wav`;
        const file = new File([audioBlob], filename, { type: 'audio/wav' });

        // FormData 생성
        const formData = new FormData();
        formData.append("audio", file);
        formData.append("contentsid", contentsId);
        formData.append("contentstype", contentsType);

        // 업로드 진행 중 표시
        uploadStatus.className = 'upload-status upload-progress';
        uploadStatus.innerHTML = '⏳ 음성 파일 자동 업로드 중...';
        uploadStatus.style.display = 'block';

        try {
            const response = await $.ajax({
                url: "../LLM/file.php",
                type: "POST",
                cache: false,
                contentType: false,
                processData: false,
                data: formData
            });

            const parsed_data = JSON.parse(response);

            if (parsed_data.success) {
                uploadStatus.className = 'upload-status upload-success';
                uploadStatus.innerHTML = `✅ 음성 파일이 자동으로 업로드되었습니다<br>파일명: ${parsed_data.url}<br>경로: https://mathking.kr/audiofiles/${parsed_data.url}`;

                outputText.innerHTML += `<p style="color:green;">✅ 업로드 완료!</p>`;

                // 저장 버튼도 자동으로 클릭하여 텍스트 저장
                setTimeout(() => {
                    document.getElementById("save_button").click();
                }, 500);
            } else {
                uploadStatus.className = 'upload-status upload-error';
                uploadStatus.innerHTML = `❌ 업로드 실패: ${parsed_data.error}`;
                outputText.innerHTML += `<p style="color:red;">❌ 업로드 실패</p>`;
            }
        } catch (error) {
            console.error("Upload error:", error);
            uploadStatus.className = 'upload-status upload-error';
            uploadStatus.innerHTML = `❌ 업로드 중 오류 발생: ${error}`;
            outputText.innerHTML += `<p style="color:red;">❌ 업로드 오류</p>`;
        }
    };

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

        await generateEnhancedSpeech(cleanedLine, voice);

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
    // 자동 업로드 함수에서 저장 버튼 클릭하므로 여기서는 제거
} 
    </script>
</body>
</html>