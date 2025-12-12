<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;

// OpenAI API 키 설정 (config.php의 $CFG->openai_api_key 사용)
$secret_key = $CFG->openai_api_key;

$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
require_login();
$contentsid=$_GET["cid"];  
$contentstype=$_GET["ctype"];  
$type=$_GET["type"];  
$timecreated=time();

$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'pmemory' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 ");
$inputtext=$thiscnt->outputtext;
if($role!=='student') echo '';
else
    {
    echo '사용권한이 없습니다.';
    exit();
    }

if($type==NULL)$type='pmemory';
$thiscnt=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE 'pmemory' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND gid LIKE '71280'  ORDER BY id DESC LIMIT 1 ");
if($thiscnt->id==NULL)
    {
    $newrecord = new stdClass();
    $newrecord->type = "pmemory";
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
        echo '<table align=left><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$thisboard->url.'"target="_blank">WB</a><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/conversation.php?cnttype='.$contentstype.'&type=pmemory&cntid='.$contentsid.'&userid='.$USER->id.'&mode=restart">📝</a></td><td><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="q" title="수동 업로드">📁</button> <button id="save_button" class="custom-button green" onclick="saveText()">대본 저장</button> <button id="generate_narration_button" class="custom-button" style="background-color:#2196F3;color:white;" onclick="generateProceduralNarration()">🎓 절차기억 나레이션 생성</button></td></tr></table>';
    }
else
    {
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype'  ORDER BY id DESC LIMIT 1 ");
        echo '<table align=left><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$thisboard->wboardid.'"target="_blank">WB</a><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/conversation.php?cnttype='.$contentstype.'&type=pmemory&cntid='.$contentsid.'&userid='.$USER->id.'&mode=restart">📝</a></td><td><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="q" title="수동 업로드">📁</button> <button id="save_button" class="custom-button green" onclick="saveText()">대본 저장</button> <button id="generate_narration_button" class="custom-button" style="background-color:#2196F3;color:white;" onclick="generateProceduralNarration()">🎓 절차기억 나레이션 생성</button></td></tr></table>';
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
      url:"../check_status.php",
      type: "POST", 
      dataType:"json",
      data : {
      "eventid":51, 
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

function generateProceduralNarration()
  {
    var Contentsid= \''.$contentsid.'\'; 
    var Contentstype= \''.$contentstype.'\'; 
    var inputText = document.getElementById("input-text").value;
    var generateBtn = document.getElementById("generate_narration_button");
    
    if(!inputText || inputText.trim() === "") {
      alert("변환할 문제와 풀이를 입력하세요.");
      return;
    }
    
    // 버튼 비활성화 및 로딩 표시
    generateBtn.disabled = true;
    generateBtn.innerHTML = "⏳ GPT 나레이션 생성 중...";
    generateBtn.style.backgroundColor = "#ccc";
    
    $.ajax({
      url: "generate_procedural_narration.php",
      type: "POST",
      dataType: "json",
      data: {
        "inputText": inputText,
        "contentsid": Contentsid,
        "contentstype": Contentstype
      },
      success: function(data) {
        generateBtn.disabled = false;
        generateBtn.innerHTML = "🎓 절차기억 나레이션 생성";
        generateBtn.style.backgroundColor = "#2196F3";
        
        if(data.success) {
          document.getElementById("input-text").value = data.narration;
          
          // DB 저장 상태 확인
          var dbStatus = data.saved_to_db ? "✅ DB 저장 완료" : "⚠️ DB 저장 실패";
          var dbStatusColor = data.saved_to_db ? "#28a745" : "#ffc107";
          
          // 성공 메시지를 페이지 상단에 표시
          var successMsg = document.createElement("div");
          successMsg.style.cssText = "background:#d4edda;border:2px solid " + dbStatusColor + ";padding:15px;margin:10px 0;border-radius:8px;";
          successMsg.innerHTML = "<strong>✅ " + data.message + "</strong><br>" +
                                "<small>@ 기호로 " + data.sectionCount + "개 구간이 구분되었습니다.</small><br>" +
                                "<small style=\'color:" + dbStatusColor + ";font-weight:bold;\'>" + dbStatus + "</small><br>" +
                                "<small>이제 \'🎵 음성 생성\' 버튼을 클릭하면 듣기평가 모드로 음성이 생성됩니다!</small>";
          
          var container = document.querySelector(".container");
          container.insertBefore(successMsg, document.getElementById("input-text"));
          
          // 5초 후 메시지 제거
          setTimeout(function() {
            successMsg.remove();
          }, 5000);
          
          var alertMsg = "✅ 절차기억 나레이션이 생성되었습니다!\\n\\n총 " + data.sectionCount + "개 구간으로 분리되었습니다.\\n\\n" + dbStatus;
          if(data.saved_to_db) {
            alertMsg += "\\n\\n이제 \'음성 생성\' 버튼을 클릭하세요.";
          } else {
            alertMsg += "\\n\\n⚠️ DB 저장 실패: " + (data.db_error || "알 수 없는 오류");
          }
          alert(alertMsg);
        } else {
          alert("❌ 나레이션 생성 실패: " + data.error);
          console.error("GPT API 오류:", data);
        }
      },
      error: function(xhr, status, error) {
        generateBtn.disabled = false;
        generateBtn.innerHTML = "🎓 절차기억 나레이션 생성";
        generateBtn.style.backgroundColor = "#2196F3";
        
        alert("❌ 서버 오류: " + error);
        console.error("AJAX 오류:", xhr.responseText);
      }
    });
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
            url: "../LLM/file_pmemory.php",
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
        
        #next-section-btn {
            background-color: #ccc;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: not-allowed;
            margin: 20px auto;
            display: block;
            transition: all 0.3s ease;
        }
        
        #next-section-btn:not(:disabled) {
            background-color: #4CAF50;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        #next-section-btn:not(:disabled):hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }
        
        #next-section-btn:not(:disabled):active {
            transform: translateY(0);
        }
        
        .section-text-display {
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        
        .progress-indicator {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
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
        <div style="background:#fff3cd;border:2px solid #ffc107;padding:15px;margin-bottom:15px;border-radius:8px;">
            <strong>🎧 듣기평가 모드 사용법:</strong><br>
            <small>1. 아래 텍스트 영역에 <strong>@</strong> 기호로 구간을 구분하세요</small><br>
            <small>2. 예: "첫 번째 구간 내용@두 번째 구간 내용@세 번째 구간 내용"</small><br>
            <small>3. "음성 생성" 버튼 클릭</small><br>
            <small>4. <strong>✅ 듣기평가 정보 DB 저장 완료!</strong> 메시지 확인 (중요!)</small>
        </div>
        <textarea id="input-text" placeholder="듣기평가 테스트: @ 기호로 구간을 나누세요. 예) 첫번째@두번째@세번째" rows="6"><?php echo $inputtext; ?></textarea>
        <table align="center"><tr><td><button id="startTalk" style="font-size:18px;padding:12px 24px;">🎵 음성 생성 (듣기평가 모드)</button></td></tr></table>
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
        const contentsid = "<?php echo $contentsid; ?>"; // PHP에서 JavaScript로 전달
        const contentstype = "<?php echo $contentstype; ?>"; // PHP에서 JavaScript로 전달
        let audioBuffers = []; // 오디오 버퍼를 저장할 배열
        let sectionBuffers = []; // 각 구간별 오디오 버퍼 (@ 구분)
        let currentSection = 0; // 현재 재생 중인 구간
        let totalSections = 0; // 전체 구간 수
        let sections = []; // @ 로 분리된 텍스트 구간들
        let currentAudioSource = null; // 현재 재생 중인 오디오 소스
        let combinedUploaded = false; // 병합 파일 업로드 여부 플래그

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
                return audioBuffer;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        // 절차기억 학습용 향상된 TTS 생성
        const generateEnhancedSpeech = async (text, voice, isPracticeSection = false) => {
            // 원본 텍스트를 그대로 사용 (추가 내용 제거)
            return await generateSpeech(text, voice);
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

        // 특정 구간 재생 함수
        const playSection = (sectionIndex) => {
            if (sectionIndex >= sectionBuffers.length) {
                // 모든 구간 재생 완료
                onAllSectionsComplete();
                return;
            }

            const audioBuffer = sectionBuffers[sectionIndex];
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // 이전 오디오 소스가 있으면 정지
            if (currentAudioSource) {
                currentAudioSource.stop();
            }

            currentAudioSource = audioContext.createBufferSource();
            currentAudioSource.buffer = audioBuffer;
            currentAudioSource.connect(audioContext.destination);

            // 재생 완료 이벤트
            currentAudioSource.onended = () => {
                onSectionEnded(sectionIndex);
            };

            currentAudioSource.start();

            // 진행 상황 업데이트
            updateProgressUI(sectionIndex);
            
            // "다음" 버튼 비활성화
            const nextButton = document.getElementById("next-section-btn");
            if (nextButton) {
                nextButton.disabled = true;
            }
        };

        // 구간 재생 완료 시 호출
        const onSectionEnded = (sectionIndex) => {
            const outputText = document.querySelector("#output-text");
            outputText.innerHTML += `<p style="color:green;">✅ 구간 ${sectionIndex + 1} 재생 완료</p>`;

            if (sectionIndex < totalSections - 1) {
                // 다음 구간이 있으면 "다음" 버튼 활성화
                const nextButton = document.getElementById("next-section-btn");
                if (nextButton) {
                    nextButton.disabled = false;
                    nextButton.textContent = `다음 구간 재생 (${sectionIndex + 2}/${totalSections})`;
                }
            } else {
                // 마지막 구간이면 완료 처리
                onAllSectionsComplete();
            }
        };

        // 모든 구간 재생 완료
        const onAllSectionsComplete = () => {
            const outputText = document.querySelector("#output-text");
            outputText.innerHTML += `<p style="color:blue;font-weight:bold;">🎉 모든 구간 재생 완료!</p>`;
            
            // "다음" 버튼 숨기기
            const nextButton = document.getElementById("next-section-btn");
            if (nextButton) {
                nextButton.style.display = "none";
            }

            // 전체 오디오 병합 및 업로드
            if (sectionBuffers.length > 0) {
                const combinedBuffer = combineAudioBuffers(sectionBuffers);
                uploadCombinedAudio(combinedBuffer);
            }
        };

        // 진행 상황 UI 업데이트
        const updateProgressUI = (sectionIndex) => {
            const outputText = document.querySelector("#output-text");
            
            // 기존 진행 상황 메시지 제거
            const existingProgress = outputText.querySelector(".progress-indicator");
            if (existingProgress) {
                existingProgress.remove();
            }
            
            const existingSection = outputText.querySelector(".section-text-display");
            if (existingSection) {
                existingSection.remove();
            }
            
            // 진행 상황 표시
            const progressDiv = document.createElement("div");
            progressDiv.className = "progress-indicator";
            progressDiv.innerHTML = `▶️ 구간 ${sectionIndex + 1}/${totalSections} 재생 중...`;
            outputText.appendChild(progressDiv);
            
            // 현재 구간 텍스트 표시
            if (sections[sectionIndex]) {
                const sectionDiv = document.createElement("div");
                sectionDiv.className = "section-text-display";
                const displayText = sections[sectionIndex].trim();
                sectionDiv.innerHTML = `<strong>현재 재생 중:</strong><br>${displayText.substring(0, 200)}${displayText.length > 200 ? '...' : ''}`;
                outputText.appendChild(sectionDiv);
            }
        };

        // 병합된 오디오 업로드
        const uploadCombinedAudio = (audioBuffer) => {
            if (combinedUploaded) {
                return; // 중복 업로드 방지
            }
            const audioData = audioBufferToWav(audioBuffer);
            const audioBlob = new Blob([audioData], { type: 'audio/wav' });
            
            const audioPlayer = document.getElementById("audio-player");
            const audioControl = document.getElementById("audio-control");
            const audioSource = document.getElementById("audio-source");

            // 오디오 플레이어에 전체 병합 파일 설정
            const audioUrl = URL.createObjectURL(audioBlob);
            audioSource.src = audioUrl;
            audioControl.load();
            audioPlayer.style.display = 'block';

            // 서버에 병합 파일 업로드 (section 없이)
            const audioFile = new File([audioBlob], `tts_${contentsid}_${contentstype}_combined.wav`, {
                type: 'audio/wav',
                lastModified: Date.now()
            });
            
            const formData = new FormData();
            formData.append('audio', audioFile);
            formData.append('contentsid', contentsid);
            formData.append('contentstype', contentstype);
            // section 없음 = DB 업데이트 함
            
            const outputText = document.querySelector("#output-text");
            outputText.innerHTML += '<p style="color:blue;">🔄 전체 병합 파일을 서버에 업로드 중...</p>';
            
            $.ajax({
                url: "../LLM/file_pmemory.php",
                type: "POST",
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json', // JSON 자동 파싱 설정
                data: formData,
                success: function (data, status, xhr) {
                    // data는 이미 파싱된 객체이므로 JSON.parse() 불필요
                    if (data.success) {
                        outputText.innerHTML += '<p style="color:green;">✅ 전체 병합 파일 업로드 완료!</p>';
                        outputText.innerHTML += `<p style=\"color:gray;\">DB 업데이트됨: ${data.audiourl}</p>`;
                        playUploadNotification();
                        combinedUploaded = true; // DB 업데이트 완료 표시
                        // DB 반영 완료 → 아이콘 갱신 신호 발송
                        notifyPmemoryUploadComplete();
                    } else {
                        outputText.innerHTML += `<p style=\"color:red;\">❌ 병합 파일 업로드 실패: ${data.error || '알 수 없는 오류'}</p>`;
                    }
                },
                error: function(xhr, status, error) {
                    outputText.innerHTML += `<p style="color:red;">❌ 업로드 요청 실패: ${error}</p>`;
                    console.error('Upload request failed:', error);
                }
            });
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

            // 자동으로 파일 업로드 실행
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

    // 무음 구간을 생성하는 함수
    const createSilentBuffer = (duration) => {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const sampleRate = audioContext.sampleRate;
        const length = sampleRate * duration;
        const buffer = audioContext.createBuffer(1, length, sampleRate);
        // 버퍼는 이미 0으로 초기화되어 있으므로 별도 작업 불필요
        return buffer;
    };

    // 생성된 오디오 파일을 서버에 자동 업로드하는 함수
    const uploadAudioFile = (audioBlob) => {
        // Blob을 File 객체로 변환 (파일명 지정)
        const audioFile = new File([audioBlob], `tts_${contentsid}_${contentstype}.wav`, {
            type: 'audio/wav',
            lastModified: Date.now()
        });

        // FormData 생성
        const formData = new FormData();
        formData.append('audio', audioFile);
        formData.append('contentsid', contentsid);
        formData.append('contentstype', contentstype);

        // 업로드 상태 표시
        const outputText = document.querySelector("#output-text");
        outputText.innerHTML += '<p style="color:blue;">🔄 오디오 파일을 서버에 업로드 중...</p>';

        // AJAX로 파일 업로드
        $.ajax({
            url: "../LLM/file_pmemory.php",
            type: "POST",
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json', // JSON 자동 파싱 설정
            data: formData,
            success: function (data, status, xhr) {
                // data는 이미 파싱된 객체이므로 JSON.parse() 불필요
                if (data.success) {
                    outputText.innerHTML += '<p style="color:green;">✅ 오디오 파일이 성공적으로 업로드되었습니다!</p>';
                    outputText.innerHTML += `<p style="color:gray;">파일명: ${data.url}</p>`;

                    // 업로드 성공 알림
                    playUploadNotification();
                    // 섹션 파라미터가 없는 경우(일반 모드) DB에 audiourl2가 반영되므로 신호 발송
                    if (!formData.has('section')) {
                        notifyPmemoryUploadComplete();
                    }
                } else {
                    outputText.innerHTML += `<p style="color:red;">❌ 업로드 실패: ${data.error || '알 수 없는 오류'}</p>`;
                }
            },
            error: function(xhr, status, error) {
                outputText.innerHTML += `<p style="color:red;">❌ 업로드 요청 실패: ${error}</p>`;
                console.error('Upload request failed:', error);
            }
        });
    };

    // 업로드 완료 알림음
        const playUploadNotification = () => {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        oscillator.type = 'sine';
        // 두 번의 짧은 비프음
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.1);
        oscillator.connect(audioContext.destination);
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.2);
    };

        // 업로드 완료 브로드캐스트: mynote.php 등에서 받아 새로고침/아이콘 갱신
        const notifyPmemoryUploadComplete = () => {
            try {
                const payload = { type: 'pmemory_upload_complete', cid: contentsid, ctype: contentstype, ts: Date.now() };
                if (typeof BroadcastChannel !== 'undefined') {
                    const ch = new BroadcastChannel('pmemory_updates');
                    ch.postMessage(payload);
                }
                try {
                    localStorage.setItem('pmemory_upload_complete', JSON.stringify(payload));
                    setTimeout(() => { localStorage.removeItem('pmemory_upload_complete'); }, 1000);
                } catch (e) {
                    console.warn('localStorage notify failed', e);
                }
            } catch (e) {
                console.warn('notifyPmemoryUploadComplete error', e);
            }
        };

    document.querySelector("#startTalk").addEventListener("click", async () => {
        const inputText = document.querySelector("#input-text").value;
        const outputText = document.querySelector("#output-text");
        outputText.innerHTML = ""; // 출력 내용 초기화
        audioBuffers = []; // 오디오 버퍼 초기화
        sectionBuffers = []; // 구간별 버퍼 초기화
        currentSection = 0;
        combinedUploaded = false; // 재업로드 허용을 위한 플래그 초기화

        // 먼저 대본 내용 확인
        outputText.innerHTML += `<div style="background:#e3f2fd;border-left:4px solid #2196F3;padding:15px;margin:10px 0;border-radius:5px;">
            <strong>📋 대본 분석 중...</strong><br>
            <small>대본 길이: ${inputText.length}자</small><br>
            <small>@ 기호 개수: ${(inputText.match(/@/g) || []).length}개</small>
        </div>`;

        // @ 기호 확인
        if (inputText.includes('@')) {
            // @ 기호로 구분된 듣기평가 모드
            outputText.innerHTML += `<p style="color:purple;font-weight:bold;">🎯 듣기평가 모드: @ 기호로 구간을 분리합니다.</p>`;
            
            // @ 기호로 텍스트 분리
            sections = inputText.split('@').filter(s => s.trim());
            totalSections = sections.length;
            
            outputText.innerHTML += `<p style="color:blue;">총 ${totalSections}개 구간으로 분리되었습니다.</p>`;

            // 서버에 업로드된 구간 파일 URL 저장
            let uploadedSectionUrls = [];

            // 각 구간별로 TTS 생성 및 서버 업로드
            for (let i = 0; i < sections.length; i++) {
                const sectionText = sections[i].trim();
                const sectionNum = i + 1;
                
                outputText.innerHTML += `<p style="color:gray;">[구간 ${sectionNum}/${totalSections}] 음성 생성 중...</p>`;
                outputText.innerHTML += `<p style="color:lightgray;font-size:0.9em;">"${sectionText.substring(0, 100)}${sectionText.length > 100 ? '...' : ''}"</p>`;

                try {
                    // TTS 생성
                    const audioBuffer = await generateEnhancedSpeech(sectionText, "alloy", false);
                    sectionBuffers.push(audioBuffer);
                    outputText.innerHTML += `<p style="color:green;">✅ 구간 ${sectionNum} 음성 생성 완료</p>`;
                    
                    // WAV로 변환
                    const audioData = audioBufferToWav(audioBuffer);
                    const audioBlob = new Blob([audioData], { type: 'audio/wav' });
                    
                    // 파일명 생성
                    const fileName = `tts_${contentsid}_${contentstype}_section${sectionNum}.wav`;
                    const audioFile = new File([audioBlob], fileName, {
                        type: 'audio/wav',
                        lastModified: Date.now()
                    });
                    
                    // FormData 생성
                    const formData = new FormData();
                    formData.append('audio', audioFile);
                    formData.append('contentsid', contentsid);
                    formData.append('contentstype', contentstype);
                    formData.append('section', sectionNum); // 구간 번호 추가
                    
                    outputText.innerHTML += `<p style="color:blue;">🔄 구간 ${sectionNum} 서버 업로드 중...</p>`;
                    
                    // 서버 업로드 (Promise로 대기)
                    await new Promise((resolve, reject) => {
                        $.ajax({
                            url: "../LLM/file_pmemory.php",
                            type: "POST",
                            cache: false,
                            contentType: false,
                            processData: false,
                            dataType: 'json', // JSON 자동 파싱 설정
                            data: formData,
                            success: function (data, status, xhr) {
                                // data는 이미 파싱된 객체이므로 JSON.parse() 불필요
                                if (data.success) {
                                    uploadedSectionUrls.push(data.audiourl || data.url);
                                    outputText.innerHTML += `<p style="color:green;">✅ 구간 ${sectionNum} 업로드 완료!</p>`;
                                    resolve(data);
                                } else {
                                    outputText.innerHTML += `<p style=\"color:red;\">❌ 구간 ${sectionNum} 업로드 실패: ${data.error || '알 수 없는 오류'}</p>`;
                                    reject(new Error(data.error || '업로드 실패'));
                                }
                            },
                            error: function(xhr, status, error) {
                                outputText.innerHTML += `<p style=\"color:red;\">❌ 구간 ${sectionNum} 업로드 요청 실패: ${error}</p>`;
                                reject(new Error(error));
                            }
                        });
                    });
                    
                } catch (error) {
                    outputText.innerHTML += `<p style=\"color:red;\">❌ 구간 ${sectionNum} 처리 실패: ${error.message}</p>`;
                    // 일부 구간 실패해도 병합 업로드를 시도하여 DB에 audiourl2 반영되도록 함
                    if (sectionBuffers.length > 0 && !combinedUploaded) {
                        const combinedBuffer = combineAudioBuffers(sectionBuffers);
                        uploadCombinedAudio(combinedBuffer);
                    }
                    return;
                }
            }

            outputText.innerHTML += `<p style="color:green;font-weight:bold;">🎉 모든 구간의 음성 생성 및 업로드 완료!</p>`;
            outputText.innerHTML += `<p style="color:blue;">✅ 총 ${uploadedSectionUrls.length}개 파일이 서버에 업로드되었습니다.</p>`;
            
            // 업로드된 URL 콘솔 출력
            console.log('업로드된 구간 파일 URLs:', uploadedSectionUrls);

            // 듣기평가 정보를 DB에 저장 (reflections1 필드)
            const listeningTestData = {
                mode: 'listening_test',
                sections: uploadedSectionUrls,
                text_sections: sections.map(s => s.trim())
            };
            
            outputText.innerHTML += `<p style="color:blue;">🔄 듣기평가 정보를 DB에 저장 중...</p>`;
            
            // DB 업데이트 AJAX 호출
            await new Promise((resolve, reject) => {
                $.ajax({
                    url: "../check_status.php", // 상위 디렉토리의 check_status.php
                    type: "POST",
                    dataType: "json",
                    data: {
                        eventid: 52, // 듣기평가 정보 저장용 이벤트 ID
                        contentsid: contentsid,
                        contentstype: contentstype,
                        listeningdata: JSON.stringify(listeningTestData)
                    },
                    success: function(data) {
                        if(data.success) {
                            outputText.innerHTML += `<p style="color:green;">✅ 듣기평가 정보 DB 저장 완료!</p>`;
                            outputText.innerHTML += `<p style="color:gray;">→ mynotepause.php 페이지에서 듣기평가 인터페이스를 사용할 수 있습니다.</p>`;
                        } else {
                            outputText.innerHTML += `<p style="color:orange;">⚠️ DB 저장 응답: ${data.message || '알 수 없음'}</p>`;
                        }
                        outputText.innerHTML += `<p style="color:blue;">첫 번째 구간 재생을 시작합니다...</p>`;
                        resolve(data);
                    },
                    error: function(xhr, status, error) {
                        outputText.innerHTML += `<p style="color:red;">❌ DB 저장 실패: ${error}</p>`;
                        outputText.innerHTML += `<p style="color:orange;">⚠️ 재생은 가능하지만 mynotepause.php에서는 표시되지 않을 수 있습니다.</p>`;
                        console.error('DB 저장 실패:', xhr.responseText);
                        resolve(); // 실패해도 계속 진행
                    }
                });
            });

            // "다음" 버튼 생성
            const nextButton = document.createElement("button");
            nextButton.id = "next-section-btn";
            nextButton.textContent = totalSections > 1 ? `다음 구간 재생 (2/${totalSections})` : '완료';
            nextButton.disabled = true;
            nextButton.onclick = () => {
                currentSection++;
                playSection(currentSection);
            };

            // 버튼을 output-text 다음에 추가
            const container = document.querySelector(".container");
            const existingButton = document.getElementById("next-section-btn");
            if (existingButton) {
                existingButton.remove();
            }
            container.insertBefore(nextButton, document.getElementById("audio-player"));

            // 첫 구간 재생 시작
            playSection(0);
            playNotificationSound();

        } else {
            // 기존 모드 (@ 없을 때)
            outputText.innerHTML += `<p style="color:blue;">일반 모드: 연속 재생합니다.</p>`;
            
            // 텍스트를 문단이나 문장 단위로 분리
            // 학생:, 선생님: 등의 화자 표시를 모두 제거
            let processedText = inputText.replace(/^(학생|선생님|아빠|엄마|A|B):\s*/gm, '');

            // 실습 키워드 감지를 위한 패턴
            const practiceKeywords = /(실습|연습|해보|해 보|시도|따라|문제를 풀|계산해|작성해|그려보|실행해|적용해|활용해|정리해)/;
            const pauseKeywords = /(\[pause\]|\[실습\]|\[break\]|\.\.\.|…)/g;

            // 문장 단위로 분리 (마침표, 느낌표, 물음표 기준)
            let sentences = processedText.split(/(?<=[.!?])\s+/);

            let totalSentences = sentences.length;
            let currentSentence = 0;

            for (let sentence of sentences) {
                // 빈 문장 스킵
                if (!sentence.trim()) continue;

                currentSentence++;

                // pause 마커 처리
                let cleanedSentence = sentence.replace(pauseKeywords, '');
                let hasPauseMarker = pauseKeywords.test(sentence);

                // 진행 상황 표시
                outputText.innerHTML += `<p>[${currentSentence}/${totalSentences}] 선생님: "${cleanedSentence.substring(0, 50)}${cleanedSentence.length > 50 ? '...' : ''}" 음성 생성 중...</p>`;

                // 실습 섹션 여부 확인
                const isPractice = practiceKeywords.test(sentence) || hasPauseMarker;

                // 선생님 음성으로 생성 (이미지 설명 포함)
                const buffer = await generateEnhancedSpeech(cleanedSentence, "alloy", isPractice);
                audioBuffers.push(buffer);

                // 실습 키워드가 있거나 pause 마커가 있으면 무음 구간 추가
                if (isPractice) {
                    // 실습 시간: 5초
                    const silentDuration = 5;
                    const silentBuffer = createSilentBuffer(silentDuration);
                    audioBuffers.push(silentBuffer);
                    outputText.innerHTML += `<b style="color:blue;"> [${silentDuration}초 실습 시간 제공]</b>`;
                } else if (sentence.match(/[.!?]$/)) {
                    // 일반 문장 끝: 2초 pause
                    const silentDuration = 2;
                    const silentBuffer = createSilentBuffer(silentDuration);
                    audioBuffers.push(silentBuffer);
                }

                outputText.innerHTML += `<b style="color:orange;"> ✓</b><br>`;
            }

            if (audioBuffers.length > 0) {
                const combinedBuffer = combineAudioBuffers(audioBuffers);
                playAudio(combinedBuffer);
                playNotificationSound(); // 전체 완료 후 알림음 재생
                outputText.innerHTML += `<p style="color:green;"><b>📢 강의 음성 생성이 완료되었습니다. 총 ${currentSentence}개 문장 처리</b></p>`;
                outputText.innerHTML += `<p style="color:orange;">🔄 생성된 음성을 자동으로 서버에 업로드합니다...</p>`;
            }
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
    </script>
</body>
</html>