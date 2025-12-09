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
// 구간 정보 받기 (mynote2.php에서 dot 더블클릭 시 전달)
$section = isset($_GET["section"]) ? intval($_GET["section"]) : null;
$subtitle = isset($_GET["subtitle"]) ? $_GET["subtitle"] : '';
$timecreated=time();

$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 ");
$inputtext=$thiscnt->outputtext;

// 구간 자막이 전달된 경우 자막 텍스트로 대체
if(!empty($subtitle)) {
    $inputtext = $subtitle;
    error_log(sprintf(
        '[drillingmath.php] File: %s, Line: %d, Section: %d, Subtitle received: %s',
        basename(__FILE__),
        __LINE__,
        $section,
        substr($subtitle, 0, 100)
    ));
}  
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

// 컨텐츠 정보 가져오기
$maintext = '';
$imgSrc1 = '';
$imgSrc2 = '';

if($contentstype==1) {
    // icontent_pages 테이블에서 컨텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid' ORDER BY id DESC LIMIT 1");
    $maintext = $cnttext->maintext;

    // 이미지 추출
    $getimgbk = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ORDER BY id DESC LIMIT 1");
    $ctextbk = $getimgbk->pageicontent;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctextbk);
    $imageTags2 = $htmlDom->getElementsByTagName('img');
    foreach($imageTags2 as $imageTag2) {
        $imgSrc1 = $imageTag2->getAttribute('src');
        if(strpos($imgSrc1, '.png')!= false || strpos($imgSrc1, '.jpg')!= false) break;
    }
} elseif($contentstype==2) {
    // question 테이블에서 컨텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question where id='$contentsid' ORDER BY id DESC LIMIT 1");
    $maintext = $cnttext->mathexpression;

    // 이미지 추출
    $qtext0 = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1 ");

    // generalfeedback에서 이미지 추출
    $htmlDom1 = new DOMDocument;
    @$htmlDom1->loadHTML($qtext0->generalfeedback);
    $imageTags1 = $htmlDom1->getElementsByTagName('img');
    foreach($imageTags1 as $imageTag1) {
        $imgSrc1 = $imageTag1->getAttribute('src');
        $imgSrc1 = str_replace(' ', '%20', $imgSrc1);
        if(strpos($imgSrc1, 'MATRIX/MATH')!= false && strpos($imgSrc1, 'hintimages')==false) break;
    }

    // questiontext에서 이미지 추출
    $htmlDom2 = new DOMDocument;
    @$htmlDom2->loadHTML($qtext0->questiontext);
    $imageTags2 = $htmlDom2->getElementsByTagName('img');
    foreach($imageTags2 as $imageTag2) {
        $imgSrc2 = $imageTag2->getAttribute('src');
        $imgSrc2 = str_replace(' ', '%20', $imgSrc2);
        if(strpos($imgSrc2, 'hintimages')!= true && (strpos($imgSrc2, '.png')!= false || strpos($imgSrc2, '.jpg')!= false)) break;
    }
}

// 대화생성 URL 및 노트 URL 설정
$conversationUrl = 'https://chatgpt.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci';
$noteUrl = '';

if($contentstype==1)
    {
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype' AND url IS NOT NULL ORDER BY id DESC LIMIT 1 ");
        $noteUrl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$thisboard->url;
    }
else
    {
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype'  ORDER BY id DESC LIMIT 1 ");
        $noteUrl = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$thisboard->wboardid;
    }

echo '<script>


function saveText()
  {
    var Contentsid= \''.$contentsid.'\';
    var Contentstype= \''.$contentstype.'\';
    //var Resulttext =document.getElementById("input-text").textContent;
    var Resulttext = document.getElementById("input-text").value;
     
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

// DOM이 완전히 로드된 후 이벤트 리스너 등록
document.addEventListener("DOMContentLoaded", function() {
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
}); // DOMContentLoaded 이벤트 리스너 종료
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
        .title-bar {
            width: 80%;
            max-width: 600px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            margin: 10px auto;
            border-radius: 10px;
        }
        .title-bar h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .upload-buttons {
            display: flex;
            gap: 10px;
        }
        #audio_upload {
            background-color: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        #audio_upload:hover {
            background-color: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        #save_button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        #save_button:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }
        .content-info {
            width: 80%;
            max-width: 600px;
            margin: 10px auto 10px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            gap: 20px;
        }
        .content-text {
            flex: 1;
            overflow-wrap: break-word;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.6;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .content-text:hover {
            background-color: #D5F3FE;
        }
        .content-images {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .content-images:hover {
            background-color: #D5F3FE;
        }
        .content-images img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            pointer-events: none;
        }
        .copy-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .copy-notification.show {
            opacity: 1;
        }
        .action-bar {
            width: 80%;
            max-width: 600px;
            margin: 10px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .action-bar a {
            flex: 1;
            text-decoration: none;
        }
        .action-button {
            width: 100%;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .action-button.primary {
            background-color: #4CAF50;
            color: white;
        }
        .action-button.primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .action-button.secondary {
            background-color: #2196F3;
            color: white;
        }
        .action-button.secondary:hover {
            background-color: #0b7dda;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
<body>

<!-- 타이틀 바 -->
<div class="title-bar">
    <h1>💬 대화기반 컨텐츠 생성기<?php if($section !== null) echo ' - 구간 '.($section + 1); ?></h1>
    <div class="upload-buttons">
        <button id="audio_upload" type="button" title="오디오 파일 업로드">⬆️ 업로드</button>
        <button id="save_button" onclick="saveText()" title="대본 저장">저장</button>
    </div>
</div>

<!-- 복사 알림 -->
<div id="copy-notification" class="copy-notification">복사되었습니다!</div>

<!-- 컨텐츠 정보 표시 -->
<div class="content-info">
    <div class="content-text" id="content-text-area" onclick="copyTextContent()" title="클릭하여 텍스트 복사">
        <h3 style="margin-top:0; color:#4CAF50;">📝 컨텐츠 내용 (클릭하여 복사)</h3>
        <div id="text-content"><?php echo $maintext; ?></div>
    </div>
    <div class="content-images" id="content-images-area" onclick="copyImageContent()" title="클릭하여 이미지 복사">
        <h3 style="margin-top:0; color:#4CAF50;">🖼️ 이미지 (클릭하여 복사)</h3>
        <?php
        if(!empty($imgSrc2)) {
            // 이미지를 base64로 인코딩
            $imgSrc2_full = $imgSrc2;
            if(strpos($imgSrc2, 'http') === false) {
                $imgSrc2_full = 'https://mathking.kr' . $imgSrc2;
            }
            echo '<img id="content-img2" src="'.$imgSrc2.'" data-original-src="'.$imgSrc2_full.'" alt="문제 이미지" crossorigin="anonymous">';
        }
        if(!empty($imgSrc1)) {
            // 이미지를 base64로 인코딩
            $imgSrc1_full = $imgSrc1;
            if(strpos($imgSrc1, 'http') === false) {
                $imgSrc1_full = 'https://mathking.kr' . $imgSrc1;
            }
            echo '<img id="content-img1" src="'.$imgSrc1.'" data-original-src="'.$imgSrc1_full.'" alt="해설 이미지" crossorigin="anonymous">';
        }
        if(empty($imgSrc1) && empty($imgSrc2)) {
            echo '<p style="color:#999;">이미지 없음</p>';
        }
        ?>
    </div>
</div>

<!-- Action Bar -->
<div class="action-bar">
    <?php if(!empty($noteUrl)): ?>
    <a href="<?php echo $noteUrl; ?>" target="_blank" style="flex:1;">
        <button class="action-button secondary">📖 노트보기</button>
    </a>
    <?php endif; ?>
    <a href="<?php echo $conversationUrl; ?>" target="_blank" style="flex:1;">
        <button class="action-button primary">💬 대화생성</button>
    </a>
</div>

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

// 텍스트 복사 함수
function copyTextContent() {
    const textElement = document.getElementById('text-content');
    const text = textElement.innerText || textElement.textContent;

    // 클립보드에 복사
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopyNotification();
            console.log('[openai_tts.php:copyTextContent] 텍스트가 클립보드에 복사되었습니다.');
        }).catch(function(err) {
            console.error('[openai_tts.php:copyTextContent] 복사 실패:', err);
            // 폴백 방식
            fallbackCopyText(text);
        });
    } else {
        // 폴백 방식
        fallbackCopyText(text);
    }
}

// 폴백 텍스트 복사 함수 (구형 브라우저용)
function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyNotification();
            console.log('[openai_tts.php:fallbackCopyText] 텍스트가 클립보드에 복사되었습니다 (폴백 방식).');
        } else {
            alert('복사에 실패했습니다.');
        }
    } catch (err) {
        console.error('[openai_tts.php:fallbackCopyText] 복사 실패:', err);
        alert('복사에 실패했습니다.');
    }

    document.body.removeChild(textArea);
}

// 이미지 복사 함수 (fetch 프록시 방식)
async function copyImageContent() {
    const img1 = document.getElementById('content-img1');
    const img2 = document.getElementById('content-img2');

    // 우선순위: img2 -> img1
    const targetImg = img2 || img1;

    if (!targetImg) {
        alert('복사할 이미지가 없습니다.');
        console.log('[openai_tts.php:copyImageContent] 복사할 이미지가 없습니다.');
        return;
    }

    console.log('[openai_tts.php:copyImageContent] 이미지 복사 시작:', targetImg.src);

    try {
        // 방법 1: 프록시를 통해 이미지 가져오기 (CORS 문제 해결)
        let blob;

        try {
            console.log('[openai_tts.php:copyImageContent] 방법 1: 프록시를 통해 이미지 가져오기 시도');

            // 프록시 URL 생성
            const proxyUrl = 'image_proxy.php?url=' + encodeURIComponent(targetImg.src);
            console.log('[openai_tts.php:copyImageContent] 프록시 URL:', proxyUrl);

            const response = await fetch(proxyUrl);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('[openai_tts.php:copyImageContent] 프록시 응답 에러:', errorText);
                throw new Error('프록시 fetch 실패: ' + response.status);
            }

            blob = await response.blob();

            // blob이 이미지인지 확인
            if (!blob.type.startsWith('image/')) {
                throw new Error('이미지 타입이 아님: ' + blob.type);
            }

            console.log('[openai_tts.php:copyImageContent] 프록시 fetch 성공, blob 타입:', blob.type, 'blob 크기:', blob.size);
        } catch (fetchErr) {
            console.log('[openai_tts.php:copyImageContent] 프록시 fetch 실패, Canvas 방식으로 전환:', fetchErr.message);

            // 방법 2: Canvas 방식 (CORS가 허용된 경우에만 작동)
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // 이미지가 이미 로드되어 있으므로 바로 사용
            if (targetImg.complete && targetImg.naturalWidth > 0) {
                canvas.width = targetImg.naturalWidth;
                canvas.height = targetImg.naturalHeight;
                ctx.drawImage(targetImg, 0, 0);
            } else {
                // 이미지가 로드되지 않았으면 새로 로드
                const img = new Image();
                img.crossOrigin = 'anonymous';

                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = targetImg.src;
                });

                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
            }

            // Canvas를 Blob으로 변환
            blob = await new Promise((resolve, reject) => {
                canvas.toBlob((b) => {
                    if (b) resolve(b);
                    else reject(new Error('Canvas toBlob 실패'));
                }, 'image/png');
            });

            console.log('[openai_tts.php:copyImageContent] Canvas 방식 성공');
        }

        if (!blob) {
            throw new Error('Blob 생성 실패');
        }

        // ClipboardItem으로 클립보드에 복사
        const item = new ClipboardItem({ [blob.type]: blob });
        await navigator.clipboard.write([item]);

        showCopyNotification();
        console.log('[openai_tts.php:copyImageContent] 이미지가 클립보드에 복사되었습니다. 타입:', blob.type);

    } catch (err) {
        console.error('[openai_tts.php:copyImageContent] 이미지 복사 실패:', err);
        console.error('[openai_tts.php:copyImageContent] 에러 상세:', err.message);

        // 디버깅 정보 출력
        console.log('[openai_tts.php:copyImageContent] 디버깅 정보:');
        console.log('  - 이미지 src:', targetImg.src);
        console.log('  - 이미지 naturalWidth:', targetImg.naturalWidth);
        console.log('  - 이미지 naturalHeight:', targetImg.naturalHeight);
        console.log('  - 이미지 complete:', targetImg.complete);
        console.log('  - navigator.clipboard:', !!navigator.clipboard);
        console.log('  - navigator.clipboard.write:', !!navigator.clipboard?.write);

        // 폴백: 이미지 URL을 텍스트로 복사
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(targetImg.src);
                alert('이미지 복사에 실패했습니다.\n이미지 URL이 클립보드에 복사되었습니다.\n\n원인:\n- 브라우저가 이미지 복사를 지원하지 않음\n- CORS 정책으로 인한 제한\n- HTTPS 연결이 필요함\n\n콘솔(F12)에서 자세한 오류를 확인하세요.');
                console.log('[openai_tts.php:copyImageContent] 폴백: 이미지 URL 복사 완료');
            } else {
                throw new Error('Clipboard API를 사용할 수 없습니다.');
            }
        } catch (err2) {
            console.error('[openai_tts.php:copyImageContent] 폴백 복사도 실패:', err2);
            alert('이미지 복사에 실패했습니다.\n\n수동으로 이미지를 우클릭하여 "이미지 복사"를 선택해주세요.');
        }
    }
}

// 복사 알림 표시 함수
function showCopyNotification() {
    const notification = document.getElementById('copy-notification');
    notification.classList.add('show');

    setTimeout(function() {
        notification.classList.remove('show');
    }, 1500);
}
    </script> 
</body>
</html>