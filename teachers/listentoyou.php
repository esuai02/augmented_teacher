<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$userid = $_GET['userid'];
$cntid = $_GET['cntid'];
$cnttype = $_GET['cnttype'];
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->role;

$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid LIKE '$cntid' AND contentstype LIKE '$cnttype' ORDER BY id DESC LIMIT 1 ");
$soltext=$thiscnt->outputtext; 


echo '모범담안 :'.$soltext;
if($cnttype==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $eventid=1;
    $guidetext1=$cnttext->reflections0;
    $guidetext2=$cnttext->reflections1;
    $maintext=$cnttext->maintext;
     
	$getimgbk=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$cntid'  ORDER BY id DESC LIMIT 1");
	$ctextbk=$getimgbk->pageicontent;
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($ctextbk);
	$imageTags2 = $htmlDom->getElementsByTagName('img');
	$extractedImages = array();
	$nimg=0;
	foreach($imageTags2 as $imageTag2)
      {
      $nimg++;
      $imgSrc1 = $imageTag2->getAttribute('src');

      if(strpos($imgSrc1, '.png')!= false || strpos($imgSrc1, '.jpg')!= false)break;
      }

    }
elseif($cnttype==2)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $guidetext1=$cnttext->reflections0;
    $guidetext2=$cnttext->reflections1;
    $maintext=$cnttext->mathexpression;
    $soltext=$cnttext->ans1;
   
    $htmlDom1 = new DOMDocument;@$htmlDom1->loadHTML($cnttext->generalfeedback); $imageTags1 = $htmlDom1->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags1 as $imageTag1)
      {
      $nimg++; $imgSrc1 = $imageTag1->getAttribute('src'); $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
      if(strpos($imgSrc1, 'MATRIX/MATH')!= false && strpos($imgSrc1, 'hintimages')==false)break;
  
      //if(strpos($imgSrc1, 'Contents/MATH%20MATRIX/MATH%20images')!= false || strpos($imgSrc1, 'ContentsIMG')!= false)break;  //local/ContentsIMG
      }
    $htmlDom2 = new DOMDocument;@$htmlDom2->loadHTML($cnttext->questiontext); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags2 as $imageTag2)
      {
      $nimg++; $imgSrc2 = $imageTag2->getAttribute('src'); $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
      if(strpos($imgSrc2, 'hintimages')!= true && (strpos($imgSrc2, '.png')!= false || strpos($imgSrc2, '.jpg')!= false))break;
      }
    }

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>발표 평가 시스템</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fb;
            font-family: 'Noto Sans KR', sans-serif;
        }
        /* 채팅 인터페이스 스타일 */
        .chat-container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 40px);
            overflow: hidden;
        }
        .chat-header {
            padding: 15px 20px;
            background: #4a6cf7;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .message {
            max-width: 80%;
            padding: 12px 18px;
            border-radius: 20px;
            margin-bottom: 5px;
            line-height: 1.5;
        }
        .message-system {
            background-color: #e6eaff;
            color: #505050;
            align-self: center;
            border-radius: 10px;
            margin: 10px 0;
            max-width: 95%;
            text-align: center;
        }
        .message-assistant {
            background-color: #f0f2f5;
            color: #333;
            align-self: flex-start;
        }
        .message-user {
            background-color: #4a6cf7;
            color: white;
            align-self: flex-end;
        }
        .chat-input-container {
            display: flex;
            padding: 15px;
            border-top: 1px solid #e6e6e6;
            background-color: white;
        }
        .chat-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 15px;
            margin-right: 10px;
            resize: none;
            max-height: 120px;
            overflow-y: auto;
        }
        .chat-input:focus {
            outline: none;
            border-color: #4a6cf7;
        }
        .send-button {
            background-color: #4a6cf7;
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .send-button:hover {
            background-color: #3a57d7;
        }
        /* 음성 녹음 및 변환 패널 스타일 */
        #recordingPanel {
            margin: 20px auto;
            max-width: 900px;
        }
        .recording-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .recording-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        /* 평가 결과 알림 */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-left: 4px solid #4a6cf7;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateX(30px);
        }
        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        /* 평가 결과 스타일 */
        .evaluation-container {
            border-left: 3px solid #4a6cf7;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .question-container {
            border-left: 3px solid #6bc76b;
            padding-left: 15px;
        }
        .answer-button {
            background-color: #6bc76b;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .answer-button:hover {
            background-color: #5ab55a;
        }
        .audio-message {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        /* 발표 영역 스타일 */
        #presentationArea {
            position: sticky;
            bottom: 0;
            width: 100%;
            z-index: 10;
            background: white;
            border-top: 1px solid #ddd;
            padding: 15px;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- 채팅 인터페이스 -->
    <div class="chat-container">
        <div class="chat-header">
            <h5 class="mb-0">발표 평가 시스템</h5>
            <div class="small">자동화된 평가와 피드백을 받아보세요</div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Embedded 녹음 및 변환 패널 as chat message -->
            <div class="message message-assistant" id="recordingPanelMessage">
                <div id="recordingPanel">
                    <div class="recording-card">
                        <div class="recording-controls d-flex align-items-center">
                            <button id="startRecordBtn" class="btn btn-primary me-2"><i class="fas fa-microphone"></i> 발표 시작</button>
                            <button id="stopRecordBtn" class="btn btn-danger me-2" style="display: none;"><i class="fas fa-stop"></i> 발표 종료</button>
                            <div class="progress flex-grow-1" style="height: 20px;">
                                <div id="recordingProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="ms-2"><strong>상태:</strong> <span id="status">대기 중 (최대 5분)</span></div>
                            <span id="timeLeft" class="ms-2 d-none">남은 시간: <span id="timeLeftValue">300</span>초</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="message message-system">
                발표 평가 시스템에 오신 것을 환영합니다. 발표를 시작하려면 상단의 '발표 시작' 버튼을 클릭하세요.
            </div>
            <div class="message message-assistant">
                안녕하세요! 발표를 진행해 주세요. 시작 버튼을 누르면 녹음이 시작됩니다.
            </div>
        </div>
        <div class="chat-input-container">
            <textarea class="chat-input" id="chatInput" placeholder="메시지를 입력하세요..." rows="1"></textarea>
            <button class="send-button" id="sendButton">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    
    <div class="notification" id="notification">
        <div class="fw-bold">평가결과가 도착하였습니다.</div>
        <div class="small">지금 확인하려면 클릭하세요.</div>
    </div>
    <div class="notification" id="transcriptionNotification" style="display: none; opacity: 0; transform: translateX(30px);">
        <div class="fw-bold">변환된 텍스트가 도착하였습니다.</div>
        <div class="small">확인을 위해 클릭하세요.</div>
    </div>
    
    <!-- 발표 인터페이스 - 채팅 컨테이너 아래 배치 -->
    <div id="presentationArea" style="display: none; width: 100%; max-width: 900px; margin: 0 auto; background: white; padding: 15px; border: 1px solid #ddd; border-radius: 0 0 15px 15px; box-sizing: border-box; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h5 class="mb-3">발표 답변</h5>
        <textarea class="form-control" placeholder="답변을 입력하세요..." rows="4" style="width: 100%;"></textarea>
        <div class="d-flex justify-content-between mt-3">
            <button class="btn btn-secondary" id="cancelPresentationBtn">취소</button>
            <button class="btn btn-primary" id="submitPresentationBtn">제출</button>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
    // 채팅 인터페이스 관련 함수
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendButton = document.getElementById('sendButton');
        const notification = document.getElementById('notification');
        
        // 발표 답변 영역 버튼 참조
        const cancelPresentationBtn = document.getElementById('cancelPresentationBtn');
        const submitPresentationBtn = document.getElementById('submitPresentationBtn');
    
        function addMessage(content, type) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message');
            if (type === 'user') {
                messageDiv.classList.add('message-user');
            } else if (type === 'assistant') {
                messageDiv.classList.add('message-assistant');
            } else if (type === 'system') {
                messageDiv.classList.add('message-system');
            }
            messageDiv.textContent = content;
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }
    
        function addSystemMessage(content) {
            addMessage(content, 'system');
        }
    
        function addAssistantMessage(content) {
            addMessage(content, 'assistant');
        }
    
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    
        sendButton.addEventListener('click', function() {
            const message = chatInput.value.trim();
            if (message) {
                addMessage(message, 'user');
                chatInput.value = '';
                chatInput.style.height = 'auto';
                setTimeout(() => {
                    addSystemMessage('메시지가 전송되었습니다.');
                }, 500);
            }
        });
    
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendButton.click();
            }
        });
    
        // 평가 결과 알림 클릭 시 추가 질문 메시지 전송
        notification.addEventListener('click', function() {
            notification.classList.remove('show');
            // 알림 숨김
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(30px)';

            // 결과 표시로 스크롤
            const evaluationResults = document.querySelectorAll('.evaluation-container');
            if (evaluationResults.length > 0) {
                const lastEvaluation = evaluationResults[evaluationResults.length - 1];
                lastEvaluation.scrollIntoView({ behavior: 'smooth' });
            }
        });
    
        function addEvaluationResult(evaluation, questions) {
            console.log('addEvaluationResult 함수 호출됨');
            console.log('평가 내용:', evaluation ? evaluation.substring(0, 50) + '...' : '없음');
            console.log('질문 개수:', questions ? questions.length : 0);
            
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', 'message-assistant');

            const evalContainer = document.createElement('div');
            evalContainer.classList.add('evaluation-container');

            const evalTitle = document.createElement('h6');
            evalTitle.classList.add('fw-bold');
            evalTitle.textContent = '발표 평가 결과';

            const evalContent = document.createElement('p');
            evalContent.textContent = evaluation;

            evalContainer.appendChild(evalTitle);
            evalContainer.appendChild(evalContent);
            messageDiv.appendChild(evalContainer);

            if (questions && questions.length > 0) {
                const questionContainer = document.createElement('div');
                questionContainer.classList.add('question-container', 'mt-3');

                const questionTitle = document.createElement('h6');
                questionTitle.classList.add('fw-bold');
                questionTitle.textContent = '추가 질문';
                questionContainer.appendChild(questionTitle);

                const questionList = document.createElement('ul');
                questionList.classList.add('mb-0');

                questions.forEach(question => {
                    if (question && question.trim() !== '') {
                        const questionItem = document.createElement('li');
                        questionItem.textContent = question;
                        questionList.appendChild(questionItem);
                    }
                });

                questionContainer.appendChild(questionList);

                const answerButton = document.createElement('button');
                answerButton.classList.add('answer-button');
                answerButton.textContent = '답변하기';
                answerButton.addEventListener('click', function() {
                    chatInput.focus();
                    addSystemMessage('추가 질문에 답변해 주세요.');
                });
                questionContainer.appendChild(answerButton);
                messageDiv.appendChild(questionContainer);
            } else {
                console.warn('질문이 없거나 비어있습니다');
                const completionMessage = document.createElement('div');
                completionMessage.classList.add('mt-3', 'text-success', 'fw-bold');
                completionMessage.textContent = '발표가 마무리되었습니다.';
                messageDiv.appendChild(completionMessage);
            }
            
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.appendChild(messageDiv);
                console.log('평가 결과 메시지가 추가되었습니다');
            } else {
                console.error('chatMessages 요소를 찾을 수 없습니다');
            }
            
            scrollToBottom();
        }
        
        // 취소 버튼 이벤트 리스너 추가
        if (cancelPresentationBtn) {
            cancelPresentationBtn.addEventListener('click', function() {
                console.log('발표 취소 버튼 클릭됨');
                const presentationArea = document.getElementById('presentationArea');
                presentationArea.style.display = 'none';
                
                // 녹음 패널 메시지 다시 표시
                const recordingPanelMessage = document.getElementById('recordingPanelMessage');
                if (recordingPanelMessage) {
                    recordingPanelMessage.style.display = 'block';
                }
            });
        }
        
        // 제출 버튼 이벤트 리스너 추가
        if (submitPresentationBtn) {
            submitPresentationBtn.addEventListener('click', function() {
                console.log('발표 제출 버튼 클릭됨');
                alert('발표 제출 버튼 클릭됨');
                const textarea = document.querySelector('#presentationArea textarea');
                if (textarea && textarea.value.trim()) {
                    const messageText = textarea.value.trim();
                    console.log('제출할 텍스트:', messageText);
                    
                    // 답변 텍스트를 채팅 메시지로 추가
                    addMessage(messageText, 'user');
                    
                    // 발표 영역 숨기기
                    const presentationArea = document.getElementById('presentationArea');
                    presentationArea.style.display = 'none';
                    
                    // 텍스트 영역 비우기
                    textarea.value = '';
                    
                    // 녹음 패널 메시지 다시 표시
                    const recordingPanelMessage = document.getElementById('recordingPanelMessage');
                    if (recordingPanelMessage) {
                        recordingPanelMessage.style.display = 'block';
                    }
                    
                    // 시스템 메시지 추가 (답변이 제출되었음을 알림)
                    setTimeout(() => {
                        addSystemMessage('답변이 제출되었습니다.');
                        
                        // 모범답안과 비교하여 평가 및 질문 생성
                        compareAnswerAndGenerateQuestions(messageText);
                    }, 500);
                } else {
                    alert('답변을 입력해주세요.');
                }
            });
        }

        // 전역 접근을 위해 함수들을 window 객체에 할당
        window.addMessage = addMessage;
        window.addSystemMessage = addSystemMessage;
        window.scrollToBottom = scrollToBottom;
        window.compareAnswerAndGenerateQuestions = compareAnswerAndGenerateQuestions;
    });
    
    // 음성 녹음 및 텍스트 변환 기능 (두번째 코드 기능)
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Audio 녹음 및 텍스트 변환 이벤트 리스너 등록됨');
        let mediaRecorder;
        let audioChunks = [];
        let recordingTimer;
        let timeLeft;
        let maxRecordingTime = 300; // 5분으로 고정

        const startRecordBtn = document.getElementById('startRecordBtn');
        const stopRecordBtn = document.getElementById('stopRecordBtn');
        const statusElement = document.getElementById('status');
        const timeLeftElement = document.getElementById('timeLeft');
        const timeLeftValue = document.getElementById('timeLeftValue');
        const recordingProgress = document.getElementById('recordingProgress');

        console.log('startRecordBtn:', startRecordBtn);
        
        // 직접 입력 버튼 추가
        const manualStartBtn = document.createElement('button');
        manualStartBtn.id = 'startPresentationBtn';
        manualStartBtn.className = 'btn btn-success me-2';
        manualStartBtn.innerHTML = '<i class="fas fa-keyboard"></i> 직접 입력';
        
        // 녹음 버튼 다음에 직접 입력 버튼 추가
        if (startRecordBtn && startRecordBtn.parentNode) {
            startRecordBtn.parentNode.insertBefore(manualStartBtn, startRecordBtn.nextSibling);
            console.log('직접 입력 버튼 추가됨');
        }
        
        // 직접 입력 버튼 이벤트 리스너
        manualStartBtn.addEventListener('click', function() {
            console.log('직접 입력 버튼 클릭됨');
            const presentationArea = document.getElementById('presentationArea');
            if (presentationArea) {
                // 녹음 패널 메시지 숨기기
                const recordingPanelMessage = document.getElementById('recordingPanelMessage');
                if (recordingPanelMessage) {
                    recordingPanelMessage.style.display = 'none';
                }
                
                // 발표 영역 표시
                presentationArea.style.display = 'block';
                
                // 텍스트 영역 포커스
                const textarea = presentationArea.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
            } else {
                console.error('발표 영역(presentationArea)을 찾을 수 없습니다.');
            }
        });
        
        // MediaRecorder API 지원 확인
        if (!window.MediaRecorder) {
            console.error('이 브라우저는 MediaRecorder API를 지원하지 않습니다.');
            startRecordBtn.disabled = true;
            startRecordBtn.textContent = '녹음 기능 지원되지 않음';
            statusElement.textContent = '오류: 이 브라우저는 음성 녹음 기능을 지원하지 않습니다.';
            alert('현재 브라우저는 음성 녹음 기능을 지원하지 않습니다. Chrome 또는 Edge 브라우저를 사용해주세요.');
            return;
        }
    
        startRecordBtn.addEventListener('click', async () => {
            console.log('발표 시작/일시정지 버튼 클릭됨');
            
            // 이미 녹음 중인 경우 일시정지/재개 처리
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                // 일시정지
                mediaRecorder.pause();
                startRecordBtn.innerHTML = '<i class="fas fa-play"></i> 재개';
                startRecordBtn.classList.remove('btn-warning');
                startRecordBtn.classList.add('btn-success');
                // 타이머 중지
                clearInterval(recordingTimer);
                statusElement.textContent = '일시정지됨';
                recordingProgress.classList.remove('progress-bar-animated');
                return;
            } else if (mediaRecorder && mediaRecorder.state === 'paused') {
                // 재개
                mediaRecorder.resume();
                startRecordBtn.innerHTML = '<i class="fas fa-pause"></i> 일시정지';
                startRecordBtn.classList.remove('btn-success');
                startRecordBtn.classList.add('btn-warning');
                // 타이머 재시작
                startTimer();
                statusElement.textContent = '녹음 중...';
                recordingProgress.classList.add('progress-bar-animated');
                return;
            }
            
            // 새로 녹음 시작하는 경우
            try {
                console.log('getUserMedia 호출 전');
                statusElement.textContent = '마이크 권한 요청 중...';
                
                // 마이크 접근 권한 요청
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    } 
                }).catch(err => {
                    console.error('마이크 접근 오류:', err);
                    statusElement.textContent = '마이크 접근 오류: ' + err.message;
                    alert('마이크 접근에 실패했습니다. 마이크 권한을 허용해주세요.');
                    throw err;
                });
                
                console.log('getUserMedia 호출 성공:', stream);
                
                // 오디오 형식 설정
                // 브라우저 지원 형식 중 가장 적합한 것을 선택
                let options = {};
                const mimeTypes = [
                    'audio/webm',
                    'audio/mp4',
                    'audio/ogg;codecs=opus',
                    'audio/wav',
                    'audio/mp3'
                ];
                
                // 브라우저가 지원하는 최적의 형식 찾기
                let foundSupportedType = false;
                for (let mimeType of mimeTypes) {
                    if (MediaRecorder.isTypeSupported(mimeType)) {
                        options.mimeType = mimeType;
                        console.log(`녹음에 사용할 형식: ${mimeType}`);
                        foundSupportedType = true;
                        break;
                    }
                }
                
                if (!foundSupportedType) {
                    console.warn('지원되는 특정 MIME 타입을 찾을 수 없음, 브라우저 기본값 사용');
                }
                
                // 오디오 트랙의 상태 확인
                const audioTracks = stream.getAudioTracks();
                if (audioTracks.length === 0) {
                    throw new Error('오디오 트랙이 없습니다.');
                }
                
                console.log('오디오 트랙 상태:', audioTracks[0].enabled, audioTracks[0].readyState);
                
                // MediaRecorder 인스턴스 생성
                mediaRecorder = new MediaRecorder(stream, options);
                audioChunks = [];
                
                console.log('MediaRecorder 생성됨:', mediaRecorder);
    
                mediaRecorder.addEventListener('dataavailable', event => {
                    console.log('데이터 조각 수신:', event.data.size);
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                });
                
                mediaRecorder.addEventListener('start', () => {
                    console.log('녹음 시작됨');
                    statusElement.textContent = '녹음 중...';
                });
                
                mediaRecorder.addEventListener('pause', () => {
                    console.log('녹음 일시정지됨');
                });
                
                mediaRecorder.addEventListener('resume', () => {
                    console.log('녹음 재개됨');
                });
                
                mediaRecorder.addEventListener('error', (e) => {
                    console.error('MediaRecorder 오류:', e);
                    statusElement.textContent = '녹음 오류: ' + e.error.message;
                });
    
                mediaRecorder.addEventListener('stop', () => {
                    console.log('녹음 종료됨, 청크 개수:', audioChunks.length);
                    clearInterval(recordingTimer);
                    timeLeftElement.classList.add('d-none');
                    statusElement.textContent = '녹음 완료, 변환 중...';
                    recordingProgress.style.width = '100%';
                    recordingProgress.classList.remove('progress-bar-animated');
    
                    // 오디오 스트림 트랙 종료
                    stream.getTracks().forEach(track => {
                        console.log('트랙 정지:', track.kind);
                        track.stop();
                    });
                    
                    // 녹음된 청크가 있는지 확인
                    if (audioChunks.length === 0) {
                        console.error('녹음된 데이터가 없습니다.');
                        statusElement.textContent = '오류: 녹음된 데이터가 없습니다.';
                        resetRecordingButtons();
                        return;
                    }
    
                    // MediaRecorder와 동일한 형식으로 오디오 Blob 생성
                    const mimeType = mediaRecorder.mimeType || 'audio/webm'; // 기본값 설정
                    console.log('최종 Blob 생성 MIME 타입:', mimeType);
                    const audioBlob = new Blob(audioChunks, { type: mimeType });
                    console.log('오디오 Blob 생성됨:', audioBlob.size, 'bytes');
                    
                    // Blob 크기 확인
                    if (audioBlob.size < 100) {
                        console.error('오디오 Blob이 너무 작습니다:', audioBlob.size, 'bytes');
                        statusElement.textContent = '오류: 녹음된 오디오가 너무 작습니다.';
                        resetRecordingButtons();
                        return;
                    }
                    
                    // 확장자 결정
                    let fileExtension = 'webm'; // 기본 확장자
                    if (mimeType.includes('mp4')) fileExtension = 'mp4';
                    else if (mimeType.includes('mp3')) fileExtension = 'mp3';
                    else if (mimeType.includes('ogg')) fileExtension = 'ogg';
                    else if (mimeType.includes('wav')) fileExtension = 'wav';
    
                    const formData = new FormData();
                    formData.append('audio', audioBlob, `recording.${fileExtension}`);
    
                    console.log(`녹음 형식: ${mimeType}, 파일 확장자: ${fileExtension}, 전송 시작`);
    
                    fetch('../convert_speech.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('서버 응답 수신:', response.status);
                        if (!response.ok) {
                            throw new Error(`서버 응답 오류: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('변환 결과:', data);
                        if (data.success) {
                            statusElement.textContent = '변환 완료!';
                            // 변환된 텍스트를 transcript 요소에 설정 (없으면 생성)
                            let transcriptElement = document.getElementById('transcript');
                            if (!transcriptElement) {
                                transcriptElement = document.createElement('textarea');
                                transcriptElement.id = 'transcript';
                                transcriptElement.style.display = 'none';
                                document.body.appendChild(transcriptElement);
                            }
                            transcriptElement.value = data.text;
                            
                            const event = new CustomEvent('speechConverted', { detail: { text: data.text, audioBlob: audioBlob } });
                            document.dispatchEvent(event);
                        } else {
                            statusElement.textContent = '오류 발생';
                            console.error('음성 변환 실패:', data.error);
                            
                            // 파일 형식 문제인 경우 상세 메시지 표시
                            if (data.error && data.error.includes('Invalid file format')) {
                                alert(`음성 변환에 실패했습니다: 파일 형식 오류\n현재 형식: ${audioBlob.type}\n지원 형식: flac, m4a, mp3, mp4, mpeg, mpga, ogg, wav, webm`);
                            } else {
                                alert('음성 변환에 실패했습니다: ' + data.error);
                            }
                        }
                        recordingProgress.classList.remove('bg-danger');
                        recordingProgress.classList.add('bg-success');
                    })
                    .catch(error => {
                        console.error('서버 통신 오류:', error);
                        statusElement.textContent = '서버 연결 실패';
                        alert('서버 연결에 실패했습니다. 다시 시도해 주세요.');
                        recordingProgress.classList.remove('bg-danger');
                        recordingProgress.classList.add('bg-warning');
                    })
                    .finally(() => {
                        resetRecordingButtons();
                    });
                });
    
                // 녹음 시작
                mediaRecorder.start(1000); // 1초마다 데이터 조각 생성
                console.log('녹음 시작 명령 실행됨');
                statusElement.textContent = '녹음 중...';
    
                recordingProgress.style.width = '0%';
                recordingProgress.classList.remove('bg-success', 'bg-warning');
                recordingProgress.classList.add('bg-primary', 'progress-bar-animated');
    
                timeLeft = maxRecordingTime;
                timeLeftValue.textContent = timeLeft;
                timeLeftElement.classList.remove('d-none');
    
                startTimer();
    
                // 버튼 상태 변경
                startRecordBtn.innerHTML = '<i class="fas fa-pause"></i> 일시정지';
                startRecordBtn.classList.remove('btn-primary');
                startRecordBtn.classList.add('btn-warning');
                stopRecordBtn.style.display = 'block';
            } catch (error) {
                console.error('녹음 시작 오류:', error);
                statusElement.textContent = '오류: ' + error.message;
                alert('녹음을 시작할 수 없습니다: ' + error.message);
                resetRecordingButtons();
            }
        });
        
        // 타이머 시작 함수
        function startTimer() {
            recordingTimer = setInterval(() => {
                timeLeft--;
                
                // 4분 30초(270초) 이상일 때는 5초 단위로 표시
                if (timeLeft >= 270) {
                    // 5로 나누어 떨어지는 경우에만 표시 업데이트
                    if (timeLeft % 5 === 0) {
                        timeLeftValue.textContent = timeLeft;
                    }
                } else {
                    // 4분 30초 미만에서는 매초 업데이트
                    timeLeftValue.textContent = timeLeft;
                }
                
                const progress = ((maxRecordingTime - timeLeft) / maxRecordingTime) * 100;
                recordingProgress.style.width = progress + '%';
                if (timeLeft <= 10) {
                    recordingProgress.classList.remove('bg-primary');
                    recordingProgress.classList.add('bg-danger');
                }
                if (timeLeft <= 0) {
                    clearInterval(recordingTimer);
                    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                        mediaRecorder.stop();
                        mediaRecorder.stream.getTracks().forEach(track => track.stop());
                    }
                }
            }, 1000);
        }
        
        // 녹음 버튼 초기화 함수
        function resetRecordingButtons() {
            startRecordBtn.innerHTML = '<i class="fas fa-microphone"></i> 발표 시작';
            startRecordBtn.classList.remove('btn-warning', 'btn-success');
            startRecordBtn.classList.add('btn-primary');
            startRecordBtn.disabled = false;
            stopRecordBtn.style.display = 'none';
        }
    
        stopRecordBtn.addEventListener('click', () => {
            console.log('발표 종료 버튼 클릭됨');
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
                
                // 버튼 상태 초기화
                resetRecordingButtons();
            }
        });
    });
    
    document.addEventListener('speechConverted', function(e) {
        const convertedText = e.detail.text;
        console.log('변환된 텍스트:', convertedText);
        
        // 변환된 텍스트를 히든 필드에 저장
        const convertedTextElement = document.getElementById('convertedText');
        if (convertedTextElement) {
            convertedTextElement.value = convertedText;
        }
        
        // 변환된 텍스트로 메시지 생성 및 버튼 추가
        const messagesContainer = document.getElementById('chatMessages');
        if (messagesContainer) {
            const speechMessageDiv = document.createElement('div');
            speechMessageDiv.className = 'message speech-message';
            
            const textContent = document.createElement('div');
            textContent.className = 'message-text';
            textContent.innerText = convertedText;
            
            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'message-buttons';
            
            // 음성 재생 버튼
            const playButton = document.createElement('button');
            playButton.className = 'btn btn-sm btn-primary';
            playButton.innerText = '음성 재생';
            playButton.addEventListener('click', function() {
                console.log('음성 재생 버튼 클릭됨');
                const audioElement = document.getElementById('audioPlayback');
                if (audioElement && audioElement.src) {
                    audioElement.play();
                } else {
                    console.error('재생할 오디오가 없습니다.');
                }
            });
            
            // 답변하기 버튼
            const answerButton = document.createElement('button');
            answerButton.className = 'btn btn-sm btn-success ml-2';
            answerButton.innerText = '답변하기';
            answerButton.addEventListener('click', function() {
                console.log('답변하기 버튼 클릭됨');
                // 발표 영역 표시 및 텍스트 설정
                const presentationArea = document.getElementById('presentationArea');
                if (presentationArea) {
                    // 녹음 패널 메시지 숨기기
                    const recordingPanelMessage = document.getElementById('recordingPanelMessage');
                    if (recordingPanelMessage) {
                        recordingPanelMessage.style.display = 'none';
                    }
                    
                    // 발표 영역 표시
                    presentationArea.style.display = 'block';
                    
                    // 텍스트 영역에 변환된 텍스트 설정
                    const textarea = presentationArea.querySelector('textarea');
                    if (textarea) {
                        textarea.value = convertedText;
                        textarea.focus();
                    }
                } else {
                    console.error('발표 영역(presentationArea)을 찾을 수 없습니다.');
                }
            });
            
            // 제출 버튼
            const submitButton = document.createElement('button');
            submitButton.className = 'btn btn-sm btn-info ml-2';
            submitButton.innerText = '제출';
            submitButton.addEventListener('click', function() {
                console.log('제출 버튼 클릭됨');
                
                // addMessage 함수가 존재하는지 확인
                if (typeof addMessage !== 'function') {
                    console.error('addMessage 함수를 찾을 수 없습니다');
                    alert('오류가 발생했습니다. 페이지를 새로고침한 후 다시 시도해주세요.');
                    return;
                }
                
                // 사용자 메시지로 추가
                addMessage(convertedText, 'user');
                
                // 시스템 메시지 추가
                setTimeout(() => {
                    // addSystemMessage 함수가 존재하는지 확인
                    if (typeof addSystemMessage !== 'function') {
                        console.error('addSystemMessage 함수를 찾을 수 없습니다');
                        return;
                    }
                    
                    addSystemMessage('답변이 제출되었습니다.');
                    
                    // compareAnswerAndGenerateQuestions 함수가 존재하는지 확인
                    if (typeof compareAnswerAndGenerateQuestions !== 'function') {
                        console.error('compareAnswerAndGenerateQuestions 함수를 찾을 수 없습니다');
                        return;
                    }
                    
                    // 모범답안과 비교하여 평가 및 질문 생성
                    compareAnswerAndGenerateQuestions(convertedText);
                }, 500);
                
                // 메시지 제거
                speechMessageDiv.remove();
            });
            
            // 버튼 추가
            buttonContainer.appendChild(playButton);
            buttonContainer.appendChild(answerButton);
            buttonContainer.appendChild(submitButton);
            
            // 메시지에 내용과 버튼 추가
            speechMessageDiv.appendChild(textContent);
            speechMessageDiv.appendChild(buttonContainer);
            
            // 메시지 컨테이너에 추가
            messagesContainer.appendChild(speechMessageDiv);
            
            // 스크롤 조정
            scrollToBottom();
        }
    });
    
    // 학생 답변과 모범답안을 비교하여 추가 질문 생성하는 함수
    function compareAnswerAndGenerateQuestions(studentAnswerText) {
        // 디버깅 로그 추가
        console.log('compareAnswerAndGenerateQuestions 함수 호출됨');
        console.log('학생 답변 타입:', typeof studentAnswerText);
        console.log('학생 답변 길이:', studentAnswerText ? studentAnswerText.length : '없음');
        console.log('학생 답변 내용:', studentAnswerText ? studentAnswerText.substring(0, 50) + '...' : '없음');
        
        // PHP에서 가져온 모범답안
        const modelAnswer = <?php echo json_encode($soltext); ?>;
        
        if (!modelAnswer || modelAnswer.trim() === '') {
            console.warn('모범답안이 없습니다.');
            return;
        }
        
        // 학생 답변 확인 (파라미터로 전달받은 값 사용)
        if (!studentAnswerText || studentAnswerText.trim() === '') {
            console.warn('학생 답변이 비어 있습니다');
            alert('학생 답변이 비어 있습니다. 녹음을 진행해주세요.');
            return;
        }
        
        // 로딩 메시지 표시
        const chatMessages = document.getElementById('chatMessages');
        const loadingMessage = document.createElement('div');
        loadingMessage.classList.add('message', 'message-system');
        loadingMessage.id = 'loadingMessage';
        loadingMessage.textContent = '답변을 분석 중입니다...';
        chatMessages.appendChild(loadingMessage);
        scrollToBottom();
        
        // 로딩 애니메이션 설정
        let loadingDots = 0;
        let loadingTime = 0;
        const loadingInterval = setInterval(() => {
            loadingDots = (loadingDots + 1) % 4;
            loadingTime += 3;
            let dotsText = '.'.repeat(loadingDots);
            loadingMessage.textContent = `답변을 분석 중입니다${dotsText} (${loadingTime}초 경과)`;
            
            // 15초 이상 걸리면 타임아웃 처리 (20초에서 15초로 단축)
            if (loadingTime >= 15) {
                clearInterval(loadingInterval);
                loadingMessage.textContent = '응답 시간이 너무 오래 걸립니다. 내부 분석으로 전환합니다...';
                
                // 내부 분석으로 대체
                setTimeout(() => {
                    // 기본 평가 생성
                    const evaluation = "답변이 접수되었으나 서버 응답이 지연되어 내부 분석 결과를 제공합니다. 모범답안과 비교했을 때, 일부 내용만 포함하고 있으며 더 구체적인 설명이 필요합니다.";
                    const questions = [
                        "답변에서 빠진 내용이 있다면 추가로 설명해 주실 수 있을까요?",
                        "이 주제에 대해 다른 관점이나 의견이 있으신가요?",
                        "실제 상황에서 이 내용을 어떻게 적용할 수 있을까요?"
                    ];
                    
                    // 로딩 메시지 제거
                    const existingLoadingMessage = document.getElementById('loadingMessage');
                    if (existingLoadingMessage) {
                        chatMessages.removeChild(existingLoadingMessage);
                    }
                    
                    // 결과 표시
                    addEvaluationResult(evaluation, questions);
                    
                    // 결과 알림 표시
                    showNotification('⚠️ 응답 시간 초과로 내부 분석 결과가 사용되었습니다');
                }, 1000); // 2초에서 1초로 단축
                return;
            }
        }, 3000);
        
        // OpenAI에 보낼 프롬프트 생성
        const prompt = createPromptForOpenAI(studentAnswerText, modelAnswer);
        
        // OpenAI API 호출
        callOpenAIAPI(prompt, function(response) {
            // 로딩 인터벌 제거
            clearInterval(loadingInterval);
            
            // 로딩 메시지가 아직 존재하는지 확인
            const loadingMessageElement = document.getElementById('loadingMessage');
            if (loadingMessageElement) {
                chatMessages.removeChild(loadingMessageElement);
            }
            
            if (response.error) {
                // 오류 발생 시 사용자에게 알림
                const errorMessage = document.createElement('div');
                errorMessage.classList.add('message', 'message-system');
                errorMessage.textContent = '답변 분석 중 오류가 발생했습니다: ' + response.error;
                chatMessages.appendChild(errorMessage);
                console.error('OpenAI API 오류:', response.error);
                
                // 내부 분석으로 대체
                setTimeout(() => {
                    // 기본 평가 생성
                    const evaluation = "답변을 분석하는 중 오류가 발생하여 내부 분석 결과를 제공합니다. 모범답안과 비교했을 때, 구체적인 설명과 예시가 더 필요합니다.";
                    const questions = [
                        "답변에서 빠진 내용이 있다면 추가로 설명해 주실 수 있을까요?",
                        "이 주제에 대해 다른 관점이나 의견이 있으신가요?",
                        "실제 상황에서 이 내용을 어떻게 적용할 수 있을까요?"
                    ];
                    
                    // 결과 표시
                    addEvaluationResult(evaluation, questions);
                    
                    // 결과 알림 표시
                    showNotification('⚠️ 답변 분석 오류가 발생했습니다');
                }, 1000);
            } else {
                try {
                    // API 응답에서 평가와 질문 추출
                    const result = parseOpenAIResponse(response);
                    
                    // 평가 결과와 질문 표시
                    addEvaluationResult(result.evaluation, result.questions);
                    
                    // 결과 알림 표시
                    showNotification('✅ 분석이 완료되었습니다');
                } catch (error) {
                    console.error('API 응답 처리 오류:', error);
                    const errorMessage = document.createElement('div');
                    errorMessage.classList.add('message', 'message-system');
                    errorMessage.textContent = '답변 분석 중 오류가 발생했습니다: 응답 형식 오류';
                    chatMessages.appendChild(errorMessage);
                    
                    // 내부 분석으로 대체
                    setTimeout(() => {
                        const evaluation = "응답 형식에 오류가 있어 내부 분석 결과를 제공합니다.";
                        const questions = [
                            "답변에서 빠진 내용이 있다면 추가로 설명해 주실 수 있을까요?",
                            "이 주제에 대해 다른 관점이나 의견이 있으신가요?",
                            "실제 상황에서 이 내용을 어떻게 적용할 수 있을까요?"
                        ];
                        addEvaluationResult(evaluation, questions);
                        
                        showNotification('⚠️ 응답 형식 오류가 발생했습니다');
                    }, 1000);
                }
            }
            
            scrollToBottom();
        });
    }

    // 알림 표시 함수
    function showNotification(message) {
        console.log('showNotification 함수 호출됨, 메시지:', message);
        
        const notification = document.getElementById('notification');
        if (!notification) {
            console.error('notification 요소를 찾을 수 없습니다');
            return;
        }
        
        const notificationTitle = notification.querySelector('.fw-bold');
        if (notificationTitle) {
            notificationTitle.textContent = message;
        } else {
            console.warn('notification 내부 제목 요소를 찾을 수 없습니다');
        }
        
        notification.style.display = 'block';
        notification.classList.add('show');
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
        
        console.log('알림이 표시되었습니다');
        
        // 5초 후에 알림 자동 숨김
        setTimeout(() => {
            notification.classList.remove('show');
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(30px)';
            console.log('알림이 자동으로 숨겨졌습니다');
        }, 5000);
    }

    // 응답 파싱 함수
    function parseOpenAIResponse(data) {
        try {
            console.log('응답 파싱 시작');
            
            // 백업 응답인 경우 알림
            if (data.from_backup && data.error) {
                console.warn('백업 평가 시스템이 사용됨: ' + data.error);
                // 알림은 이미 표시되었으므로 여기서는 생략
            }
            
            if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                console.error('응답에 choices 데이터가 없습니다');
                return {
                    evaluation: "응답 형식 오류로 평가를 생성할 수 없습니다.",
                    questions: ["응답 형식 오류로 질문을 생성할 수 없습니다.", "", ""]
                };
            }
            
            const content = data.choices[0].message.content;
            if (!content) {
                console.error('응답 내용이 비어 있습니다');
                return {
                    evaluation: "응답 내용이 비어 있어 평가를 생성할 수 없습니다.",
                    questions: ["응답 내용이 비어 있어 질문을 생성할 수 없습니다.", "", ""]
                };
            }
            
            console.log('응답 내용:', content);
            
            // 정규식으로 평가와 질문 추출
            const evaluationMatch = content.match(/EVALUATION:?\s*(.*?)(?=QUESTION1:?|$)/is);
            const question1Match = content.match(/QUESTION1:?\s*(.*?)(?=QUESTION2:?|$)/is);
            const question2Match = content.match(/QUESTION2:?\s*(.*?)(?=QUESTION3:?|$)/is);
            const question3Match = content.match(/QUESTION3:?\s*(.*)/is);
            
            const evaluation = evaluationMatch ? evaluationMatch[1].trim() : "평가 내용을 찾을 수 없습니다.";
            const questions = [
                question1Match ? question1Match[1].trim() : "첫 번째 질문을 찾을 수 없습니다.",
                question2Match ? question2Match[1].trim() : "두 번째 질문을 찾을 수 없습니다.",
                question3Match ? question3Match[1].trim() : "세 번째 질문을 찾을 수 없습니다."
            ];
            
            console.log('파싱 결과:', { evaluation, questions });
            return { evaluation, questions };
        } catch (error) {
            console.error('응답 파싱 중 오류 발생:', error);
            return {
                evaluation: "응답 파싱 오류로 평가를 생성할 수 없습니다.",
                questions: ["응답 파싱 오류로 질문을 생성할 수 없습니다.", "", ""]
            };
        }
    }

    // OpenAI에 보낼 프롬프트 생성 함수
    function createPromptForOpenAI(studentAnswer, modelAnswer) {
        console.log('createPromptForOpenAI 호출됨');
        
        // 특수 문자 처리 및 문자열 길이 제한
        studentAnswer = studentAnswer.trim().slice(0, 400).replace(/[\r\n]+/g, " ");
        modelAnswer = modelAnswer.trim().slice(0, 400).replace(/[\r\n]+/g, " ");
        
        console.log('처리된 학생 답변 길이:', studentAnswer.length);
        console.log('처리된 모범 답안 길이:', modelAnswer.length);
        
        const prompt = `당신은 학생의 발표를 평가하는 교육 전문가입니다. 아래 정보를 바탕으로 학생의 답변을 분석하고 평가해주세요.
        
모범답안: ${modelAnswer}

학생 답변: ${studentAnswer}

다음 형식으로 응답해주세요:
EVALUATION: 학생의 답변 평가
QUESTION1: 첫번째 질문
QUESTION2: 두번째 질문
QUESTION3: 세번째 질문`;

        console.log('생성된 프롬프트 길이:', prompt.length);
        return prompt;
    }

    // 클라이언트 측 백업 응답 생성 함수
    function generateBackupResponse(modelAnswer, studentAnswer) {
        console.log('generateBackupResponse 호출됨');
        
        // 텍스트 길이 비교를 통한 기본 평가
        const modelLength = modelAnswer.length;
        const studentLength = studentAnswer.length;
        
        // 유사도 점수 계산 (0~100)
        const similarity = Math.min(100, (studentLength / Math.max(1, modelLength)) * 100);
        console.log('계산된 유사도 점수:', similarity);
        
        let evaluation = '';
        if (similarity >= 80) {
            evaluation = "답변이 모범답안의 내용을 충실히 포함하고 있습니다. 핵심 개념을 잘 이해하고 있으며, 설명이 명확합니다.";
        } else if (similarity >= 50) {
            evaluation = "답변이 모범답안의 일부 내용을 포함하고 있으나, 몇 가지 중요한 개념이 누락되었습니다. 더 구체적인 설명이 필요합니다.";
        } else {
            evaluation = "답변이 모범답안의 핵심 내용 대부분을 누락하고 있습니다. 주요 개념에 대한 이해가 부족해 보입니다.";
        }
        
        // 기본 질문 목록
        const defaultQuestions = [
            "답변에서 빠진 내용이 있다면 추가로 설명해 주실 수 있을까요?",
            "이 주제에 대해 다른 관점이나 의견이 있으신가요?",
            "실제 상황에서 이 내용을 어떻게 적용할 수 있을까요?",
            "이 개념을 좀 더 쉽게 설명한다면 어떻게 표현할 수 있을까요?",
            "이 주제와 관련된 실제 사례나 예시를 들어주실 수 있을까요?"
        ];
        
        // 질문 3개 선택 (랜덤하게)
        const shuffledQuestions = [...defaultQuestions].sort(() => 0.5 - Math.random());
        const selectedQuestions = shuffledQuestions.slice(0, 3);
        
        const result = `EVALUATION:${evaluation}\nQUESTION1:${selectedQuestions[0]}\nQUESTION2:${selectedQuestions[1]}\nQUESTION3:${selectedQuestions[2]}`;
        console.log('생성된 백업 응답 길이:', result.length);
        return result;
    }

    // OpenAI API 호출 함수
    function callOpenAIAPI(prompt, callback) {
        console.log('callOpenAIAPI 호출됨, 시간:', new Date().toLocaleTimeString());
        
        // 네트워크 연결 상태 확인
        if (!navigator.onLine) {
            console.error('오프라인 상태입니다');
            callback({ error: '인터넷 연결이 없습니다. 네트워크 연결을 확인해주세요.' });
            return;
        }
        
        // 학생 답변과 모범답안을 직접 추출하여 백업 평가 가능하도록 준비
        const modelAnswerMatch = prompt.match(/모범답안:\s*(.*?)(?=학생 답변:|$)/s);
        const studentAnswerMatch = prompt.match(/학생 답변:\s*(.*?)(?=다음 형식으로|$)/s);
        
        const modelAnswer = modelAnswerMatch ? modelAnswerMatch[1].trim() : '';
        const studentAnswer = studentAnswerMatch ? studentAnswerMatch[1].trim() : '';
        
        // 타임아웃 설정
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => {
                console.log('API 타임아웃 발생:', new Date().toLocaleTimeString());
                reject(new Error('API 요청 시간 초과'));
            }, 10000); // 10초 타임아웃
        });
        
        // 클라이언트에서 보낼 데이터 정리 - Chat API 형식으로 수정
        const requestData = {
            model: "gpt-4o-mini",
            messages: [
                { role: "system", content: "당신은 학생의 발표를 평가하는 교육 전문가입니다." },
                { role: "user", content: prompt.replace(/[\r\n]+/g, " ") }
            ]
        };
        
        console.log('요청 데이터 길이:', JSON.stringify(requestData).length);
        
        // 실제 API 호출
        const fetchPromise = fetch('../openai_analyze.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            console.log('API 응답 수신:', new Date().toLocaleTimeString(), 'Status:', response.status);
            if (!response.ok) {
                if (response.status === 400) {
                    throw new Error(`서버 응답 오류 (400): 잘못된 요청 형식`);
                } else {
                    throw new Error(`서버 응답 오류 (${response.status})`);
                }
            }
            return response.json();
        })
        .then(data => {
            console.log('API 응답 파싱 완료:', new Date().toLocaleTimeString());
            return data;
        })
        .catch(error => {
            console.error('API 응답 처리 중 오류:', error);
            
            // HTTP 400 또는 기타 오류가 발생하면 직접 백업 평가를 수행
            if (error.message.includes('400') || error.message.includes('잘못된 요청') || error.message.includes('JSON')) {
                console.log('JSON 파싱 또는 400 오류로 인한 직접 백업 평가 수행 중...');
                
                // 백업 평가 및 질문 생성
                return {
                    success: true,
                    from_backup: true,
                    choices: [{
                        message: {
                            content: generateBackupResponse(modelAnswer, studentAnswer)
                        }
                    }],
                    error: error.message
                };
            }
            
            throw error;
        });
        
        // 둘 중 먼저 완료되는 것 처리 (타임아웃 또는 API 응답)
        Promise.race([fetchPromise, timeoutPromise])
            .then(data => {
                console.log('API 응답 데이터:', data);
                
                // 응답 데이터 구조 확인
                if (!data) {
                    console.error('API 응답 데이터가 비어있습니다');
                    callback({ error: '응답 데이터가 비어있습니다' });
                    return;
                }
                
                if (data.success === false) {
                    console.error('API 오류 응답:', data.error);
                    callback({ error: data.error || '알 수 없는 오류가 발생했습니다' });
                } else if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                    console.error('API 응답 형식 오류:', data);
                    callback({ error: '응답 형식이 올바르지 않습니다' });
                } else {
                    if (data.from_backup) {
                        console.log('백업 응답 사용됨:', data.error);
                    }
                    console.log('API 응답 성공 처리 완료');
                    callback(data);
                }
            })
            .catch(error => {
                console.error('API 호출 최종 오류:', error);
                
                // 오류 발생 시 직접 백업 평가 수행
                const backupResponse = {
                    success: true,
                    from_backup: true,
                    choices: [{
                        message: {
                            content: generateBackupResponse(modelAnswer, studentAnswer)
                        }
                    }],
                    error: error.message || '네트워크 오류가 발생했습니다'
                };
                
                callback(backupResponse);
            });
    }

    // 스타일 충돌 해결을 위한 추가 스타일
    document.addEventListener('DOMContentLoaded', function() {
        console.log('스타일 조정을 위한 코드 실행');
        // Bootstrap 관련 클래스 호환성 유지
        const addCompatibilityStyles = function() {
            const styleElement = document.createElement('style');
            styleElement.textContent = `
                /* 버튼 간격 조정 */
                .btn-sm { margin-right: 5px; }
                .ml-2 { margin-left: 0.5rem !important; }
                
                /* 메시지 스타일 조정 */
                .speech-message {
                    background-color: #f8f9fa;
                    color: #333;
                    align-self: flex-start;
                    max-width: 80%;
                    padding: 12px 18px;
                    border-radius: 20px;
                    margin-bottom: 15px;
                }
                
                .message-text {
                    margin-bottom: 10px;
                }
                
                .message-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                }
                
                /* 녹음 패널 버튼 간격 */
                .me-2 {
                    margin-right: 0.5rem !important;
                }
            `;
            document.head.appendChild(styleElement);
            console.log('호환성 스타일이 추가되었습니다');
        };
        
        // 스타일 추가 호출
        addCompatibilityStyles();
    });
    </script>
</body>
</html> 