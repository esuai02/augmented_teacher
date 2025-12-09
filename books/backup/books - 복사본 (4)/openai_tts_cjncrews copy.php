<?php
// Moodle 환경 설정 파일 포함 및 DB, 사용자 변수 초기화
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$secret_key = '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';

$contentsid  = $_GET["cid"];
$contentstype = $_GET["ctype"];
$type = isset($_GET["type"]) ? $_GET["type"] : 'conversation';
$timecreated = time();

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid = '$USER->id' AND fieldid = '22' ORDER BY id DESC LIMIT 1");
$role = $userrole->data;
require_login();

$thiscnt = $DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1");
$inputtext = $thiscnt->outputtext;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TTS 서비스</title>
  <style>
    body {
      font-family: "Arial", sans-serif;
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
      background-image: url("https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chatgpt.png");
      background-size: cover;
      background-position: center;
    }
    .dropdown-container {
      margin-bottom: 10px;
      text-align: center;
    }
    .custom-button {
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      margin: 5px;
    }
    .green {
      background-color: #4CAF50;
      color: white;
    }
    .green:hover {
      background-color: #45a049;
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
  <br><br><br>
  <div class="container">
    <div class="avatar"></div>
    
    <!-- 추가된 화자명 선택 dropdown (화자명 A, 화자명 B) -->
    <div class="dropdown-container">
      <label for="speakerNameA">화자명 A:</label>
      <select id="speakerNameA">
          <option value="학생" selected>학생</option>
          <option value="선생님">선생님</option>
          <option value="학부모">학부모</option>
          <option value="전문가">전문가</option>
          <option value="진행자">진행자</option>
          <option value="친구">친구</option>
          <option value="과학자">과학자</option>
      </select>
      &nbsp;&nbsp;
      <label for="speakerNameB">화자명 B:</label>
      <select id="speakerNameB">
          <option value="학생">학생</option>
          <option value="선생님" selected>선생님</option>
          <option value="학부모">학부모</option>
          <option value="전문가">전문가</option>
          <option value="진행자">진행자</option>
          <option value="친구">친구</option>
          <option value="과학자">과학자</option>
      </select>
    </div>
    <!-- 기존 목소리 선택 dropdown (목소리 A, 목소리 B) -->
    <div class="dropdown-container">
      <label for="speakerA">목소리 A:</label>
      <select id="speakerA">
          <option value="nova" selected>Nova (여성)</option>
          <option value="shimmer">Shimmer (여성)</option>
          <option value="echo">Echo (남성)</option>
          <option value="fable">Fable (남성)</option>
          <option value="onyx">Onyx (남성)</option>
          <option value="alloy">Alloy (중성)</option>
      </select>
      &nbsp;&nbsp;
      <label for="speakerB">목소리 B:</label>
      <select id="speakerB">
          <option value="nova">Nova (여성)</option>
          <option value="shimmer">Shimmer (여성)</option>
          <option value="echo" selected>Echo (남성)</option>
          <option value="fable">Fable (남성)</option>
          <option value="onyx">Onyx (남성)</option>
          <option value="alloy">Alloy (중성)</option>
      </select>
    </div>
    
    
    <!-- WB, 📝, 오디오 업로드(⬆️) 및 저장 버튼 출력 -->
    <?php
        echo '<table align="left">
        <tr> 
          <td>
            <button id="audio_upload" type="button" data-toggle="collapse" data-target="#demo" accesskey="q">⬆️</button> 
          </td>
        </tr>
      </table>';
    ?>
    
    <!-- 텍스트 입력 영역 -->
    <textarea id="input-text" placeholder="여기에 텍스트를 입력하세요" rows="4"><?php echo $inputtext; ?></textarea>
    <table align="center">
      <tr>
        <td><button id="startTalk">음성 생성</button></td>
      </tr>
    </table>
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
    let audioBuffers = []; // 오디오 버퍼 저장 배열

    // TTS 음성 생성 함수
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
        })
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

    // 여러 오디오 버퍼를 하나로 결합하는 함수
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

    // 결합된 오디오 버퍼를 재생하는 함수 및 오디오 파일 링크를 클립보드에 복사
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

      // 오디오 파일 링크를 클립보드에 복사
      navigator.clipboard.writeText(audioUrl).then(() => {
        console.log("Audio URL copied to clipboard: " + audioUrl);
      }).catch(err => {
        console.error("Failed to copy audio URL: ", err);
      });
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
      let index = 0, inputIndex = 0;
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
      writeString(view, 0, "RIFF");
      view.setUint32(4, 36 + samples.length * bytesPerSample, true);
      writeString(view, 8, "WAVE");
      writeString(view, 12, "fmt ");
      view.setUint32(16, 16, true);
      view.setUint16(20, format, true);
      view.setUint16(22, numChannels, true);
      view.setUint32(24, sampleRate, true);
      view.setUint32(28, sampleRate * blockAlign, true);
      view.setUint16(32, blockAlign, true);
      view.setUint16(34, bitDepth, true);
      writeString(view, 36, "data");
      view.setUint32(40, samples.length * bytesPerSample, true);
      if (format === 1) {
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

    // 음성 생성 버튼 클릭 이벤트 (텍스트 줄 단위로 분리)
    document.querySelector("#startTalk").addEventListener("click", async () => {
      const text = document.querySelector("#input-text").value;
      const lines = text.split(/\r?\n/).filter(line => line.trim() !== '');
      const outputText = document.querySelector("#output-text");
      outputText.innerHTML = "";
      audioBuffers = [];

      // 각 줄을 개별적으로 처리 (":" 기준으로 화자와 대화 내용 분리)
      for (let line of lines) {
        if (!line.includes(": ")) continue;
        const parts = line.split(": ");
        if (parts.length < 2) continue;
        const speaker = parts[0];
        const cleanedLine = parts.slice(1).join(": ");
        outputText.innerHTML += `<p>${speaker}: "${cleanedLine}" 음성 생성 중...</p>`;
        
        // 추가된 화자명 dropdown 선택값과 비교하여 해당 목소리 선택
        const speakerNameA = document.getElementById("speakerNameA").value;
        const speakerNameB = document.getElementById("speakerNameB").value;
        let selectedVoice;
        if (speaker.trim() === speakerNameA) {
          selectedVoice = document.getElementById("speakerA").value;
        } else if (speaker.trim() === speakerNameB) {
          selectedVoice = document.getElementById("speakerB").value;
        } else {
          selectedVoice = document.getElementById("speakerA").value;
        }
        
        await generateSpeech(cleanedLine, selectedVoice);
        outputText.innerHTML += `<b style="color:orange;"> completed !</b>`;
      }

      if (audioBuffers.length > 0) {
        const combinedBuffer = combineAudioBuffers(audioBuffers);
        playAudio(combinedBuffer);
        playNotificationSound();
        outputText.innerHTML += `<p style="color:green;">모든 음성 생성이 완료되었습니다.</p>`;
      }
    });

    // 오디오 파일 업로드 버튼 기능 (제공된 코드 차용)
    document.getElementById("audio_upload").onclick = function () {
      var input = document.createElement("input");
      input.type = "file";
      input.accept = "audio/*";
      var object = null;
      var Contentsid = '<?php echo $contentsid; ?>';
      var Contentstype = '<?php echo $contentstype; ?>';
      input.onchange = e => {
        var file = e.target.files[0];
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
          success: function (data, status, xhr) {
            var parsed_data = JSON.parse(data);
            object = parsed_data;
            if (object) {
              // 오디오 객체 처리 로직 구현 (필요 시 추가)
            }
          }
        });
      }
      input.click();
    };

    // 알림음 재생 함수
    function playNotificationSound() {
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      oscillator.type = "sine";
      oscillator.frequency.setValueAtTime(440, audioContext.currentTime);
      oscillator.connect(audioContext.destination);
      oscillator.start();
      oscillator.stop(audioContext.currentTime + 0.1);
      document.getElementById("save_button").click();
    }
  </script>
</body>
</html>
