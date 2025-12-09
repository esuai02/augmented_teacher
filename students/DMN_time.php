<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMN 휴식 타이머</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Malgun Gothic', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            margin: 0;
            overflow: hidden;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 980px;
            width: 100%;
            height: calc(100vh - 10px);
            overflow: hidden;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .header h1 {
            font-size: 20px;
            margin-bottom: 8px;
            line-height: 1.4;
            font-weight: bold;
        }

        .header-subtitle {
            font-size: 12px;
            color: #e0e0e0;
            line-height: 1.3;
        }

        .header-links {
            display: flex;
            flex-direction: row;
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }

        .header-link {
            color: #a0e9ff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .header-link:hover {
            color: white;
            text-decoration: underline;
        }

        .timer-container {
            padding: 30px 20px;
            text-align: center;
            background: #f8f9fa;
            flex-shrink: 0;
        }

        .timer-display {
            font-size: 72px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            font-family: 'Consolas', 'Monaco', monospace;
            letter-spacing: 2px;
        }

        .timer-status {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .overtime-display {
            font-size: 24px;
            color: #dc3545;
            margin-top: 20px;
            display: none;
        }

        .controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .control-btn {
            padding: 15px 30px;
            font-size: 16px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            text-transform: uppercase;
        }

        .start-btn {
            background: #28a745;
            color: white;
        }

        .start-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .pause-btn {
            background: #ffc107;
            color: #333;
        }

        .pause-btn:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .reset-btn {
            background: #6c757d;
            color: white;
        }

        .reset-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        .activity-section {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            gap: 20px;
        }

        .activity-left {
            flex: 1;
        }

        .activity-right {
            flex: 0 0 250px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding-top: 40px;
        }

        .activity-link-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
        }

        .activity-link-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .activity-link-item a {
            color: #333;
            text-decoration: none;
            font-size: 14px;
            display: block;
            line-height: 1.5;
        }

        .activity-link-item a:hover {
            color: #667eea;
        }

        .feedback-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .feedback-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .feedback-message {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .feedback-penalty {
            font-size: 18px;
            color: #dc3545;
            margin-top: 15px;
            font-weight: bold;
        }

        .activity-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .activity-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .activity-item:hover {
            background: #e9ecef;
        }

        .activity-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .activity-label {
            cursor: pointer;
            user-select: none;
            flex: 1;
        }

        .progress-bar {
            position: relative;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 20px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 1s linear;
            width: 0%;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            display: none;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>좋은 휴식은 정보입력이 멈춘 상태에서 이루어집니다 (DMN휴식).</h1>
            <div class="header-subtitle">가짜휴식 : 스마트폰 등 뇌를 지속적으로 사용하는 휴식.</div>
        </div>

        <div class="timer-container">
            <div class="timer-display" id="timerDisplay">10:00</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="overtime-display" id="overtimeDisplay">
                초과 시간: <span id="overtimeValue">00:00</span>
            </div>

            <div class="controls">
                <button class="control-btn reset-btn" id="resetBtn">휴식종료</button>
            </div>
        </div>

        <div class="activity-section">
            <div class="activity-left">
            <div class="activity-title">휴식 중 추천 활동</div>
            <div class="activity-list">
                <div class="activity-item">
                    <input type="checkbox" class="activity-checkbox" id="activity1">
                    <label for="activity1" class="activity-label">☕ 물 마시기</label>
                </div>
                <div class="activity-item">
                    <input type="checkbox" class="activity-checkbox" id="activity2">
                    <label for="activity2" class="activity-label">🚶 스트레칭</label>
                </div>
                <div class="activity-item">
                    <input type="checkbox" class="activity-checkbox" id="activity3">
                    <label for="activity3" class="activity-label">👀 눈 운동</label>
                </div>
                <div class="activity-item">
                    <input type="checkbox" class="activity-checkbox" id="activity4">
                    <label for="activity4" class="activity-label">🧘 심호흡</label>
                </div>
            </div>
            </div>
            <div class="activity-right">
                <div class="activity-link-item">
                    <a href="https://www.youtube.com/watch?v=uqK7ydiPEaI" target="_blank">
                        🎨 예술작품에 감동하는 뇌
                    </a>
                </div>
                <div class="activity-link-item">
                    <a href="https://brunch.co.kr/@kissfmdj/1" target="_blank">
                        📚 멍 때리기의 힘
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="notification" id="notification">
        휴식 시간이 종료되었습니다!
    </div>

    <div class="feedback-overlay" id="feedbackOverlay">
        <div class="feedback-content">
            <div class="feedback-message" id="feedbackMessage"></div>
            <div class="feedback-penalty" id="feedbackPenalty"></div>
        </div>
    </div>

    <script>
        class DMNTimer {
            constructor() {
                this.totalSeconds = 600; // 10분
                this.currentSeconds = 600;
                this.overtimeSeconds = 0;
                this.isRunning = false;
                this.isOvertime = false;
                this.interval = null;
                this.feedbackShown = false;
                this.feedbackSent = false;
                
                this.initializeElements();
                this.initializeEventListeners();
                this.updateDisplay();
                // 페이지 로드 시 자동 시작
                this.start();
            }

            initializeElements() {
                this.timerDisplay = document.getElementById('timerDisplay');
                this.overtimeDisplay = document.getElementById('overtimeDisplay');
                this.overtimeValue = document.getElementById('overtimeValue');
                this.resetBtn = document.getElementById('resetBtn');
                this.progressFill = document.getElementById('progressFill');
                this.notification = document.getElementById('notification');
                this.feedbackOverlay = document.getElementById('feedbackOverlay');
                this.feedbackMessage = document.getElementById('feedbackMessage');
                this.feedbackPenalty = document.getElementById('feedbackPenalty');
                this.startTime = Date.now();
            }

            initializeEventListeners() {
                this.resetBtn.addEventListener('click', () => this.reset());

                // 체크박스 저장
                document.querySelectorAll('.activity-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', () => this.saveActivities());
                });

                // 부모 창으로부터 강제 종료 메시지 수신
                window.addEventListener('message', (event) => {
                    if (event.data === 'forceEnd') {
                        this.reset();
                    }
                });
            }

            start() {
                if (!this.isRunning) {
                    this.isRunning = true;
                    this.startTime = Date.now(); // 휴식 시작 시간 기록
                    
                    this.interval = setInterval(() => {
                        if (!this.isOvertime) {
                            this.currentSeconds--;
                            
                            if (this.currentSeconds <= 0) {
                                this.isOvertime = true;
                                this.showNotification();
                                this.playSound();
                                this.overtimeDisplay.style.display = 'block';
                            }
                        } else {
                            this.overtimeSeconds++;
                        }
                        
                        this.updateDisplay();
                        this.updateProgress();
                    }, 1000);
                }
            }

            reset() {
                this.isRunning = false;
                clearInterval(this.interval);
                
                // 실제 휴식 시간 계산 (타이머가 실행된 총 시간)
                const totalRestSeconds = this.totalSeconds - this.currentSeconds + this.overtimeSeconds;
                
                // 즉시 부모 창에 종료 메시지 전달 (2초 후 팝업 닫힘)
                if (window.parent && window.parent !== window) {
                    try {
                        window.parent.postMessage('restEnd', '*');
                    } catch (e) {
                        console.log('Error in DMN_time.php:469 - restEnd postMessage failed: ' + e.message);
                    }
                }
                
                // 피드백 표시
                this.showFeedback(totalRestSeconds);
            }

            showFeedback(totalRestSeconds) {
                const normalMessages = [
                    "방금 뇌세포 두 명이 악수하고 다시 출근했대 🤝🧠",
                    "살짝 쉬고 왔지? 뇌가 지금 \"오~ 다시 시작이군?\" 하고 웃더라 😌",
                    "네 머릿속 엔진, 다시 부르릉— 출발 준비 완료! 🚗💨",
                    "잠깐 쉬더니 집중력 아이콘처럼 빤딱해졌네 ✨",
                    "지금 뇌가 \"자, 가볍게 하나만 더 해보자~\" 중 😎",
                    "리스프레쉬한 너는 옵션 풀로 달리는 버전이다 ⚡️",
                    "방금 들어온 소식: 너의 집중력, 재부팅 성공! 🔄",
                    "너 쉬는 사이 문제들이 \"어? 돌아왔어?\" 하고 쫄았대 😆",
                    "업그레이드된 너가 복귀했다. 문제들 긴장해라 🧩🔥",
                    "너의 뇌, 방금 '다음 스테이지' 눌렀다 🎮",
                    "재시동 느낌 완전 좋다— 지금 타이밍이 딱이야 😌",
                    "너 잠깐 쉬는 동안 뇌가 살짝 스트레칭했대 🧠🧘",
                    "지금 너의 집중력, 프레시 버전으로 리셋됨 🍃",
                    "아까보다 12% 더 귀여운 집중력 장착 완료 😄",
                    "뇌가 방금 \"렛츠기릿~\" 외쳤음 😎🎤",
                    "조금 쉬더니 왜 이렇게 프로 느낌 나냐? 🤭",
                    "무겁게 시작할 필요 없어— 그냥 가볍게 톡! 📘",
                    "너한테 지금 필요한 건 1cm의 움직임뿐 ✨",
                    "막 복귀한 너는 크리티컬 히트 확률이 높다 🎯",
                    "방금 돌아온 너에게 문제들이 움찔했다 😳",
                    "쉬는 사이에 뇌가 비밀버프 얻고 왔다 🍀",
                    "\"너 다시 왔어?\" 하고 수학이 설렜대 🙃",
                    "지금 페이스 그 자체가 깔끔하다. 고고! 😌",
                    "너 휴식 끝나자마자 집중력 스위치 '딸깍' 🔘",
                    "지금 딱 한 문제만 건드리면 흐름 살아난다 🌊",
                    "쉬고 온 넌 그냥 상쾌함 자체임 🍋✨",
                    "네 두뇌: \"오케이, 재밌게 가보자고!\" 😎",
                    "손가락만 까딱해도 진도 나갈 준비됨 👉📘",
                    "방금 뇌세포가 자리 정렬하고 반듯하게 섰대 🫡",
                    "쉬었다 와서인지 오늘 너 좀 멋있다? 🤭",
                    "지금 시작하면 오늘 기록 갱신할 기운이다 ⚡️",
                    "'적당히'가 아니라 '딱 좋게' 돌아왔다 😌",
                    "쉬는 동안 뇌가 비타민 먹고 왔냐? 👀",
                    "어이, 문제야. 영웅이 돌아왔대 😎🦸‍♂️",
                    "쉬고 오니까 네 뇌가 말랑말랑해졌어 🍡",
                    "지금 시작하면 기세가 매끈하게 이어진다 ✨",
                    "너 쉬는 동안 집중력 게이지 자동 충전됨 🔋",
                    "\"아 그 흐름!\" 바로 돌아왔다 😄",
                    "한 문제만 딱 눌러도 달릴 기세다 🏃‍♂️💨",
                    "잠깐 쉬니까 너, 컨디션이 맛집이네 🍽️",
                    "방금 너의 뇌가 와이파이 5G로 전환했다 📶",
                    "지금 너는 '조용한 자신감 모드' 켜져 있음 😌",
                    "살살 가도 돼— 이미 방향은 맞아 있었어 ➡️",
                    "쉬고 와서 뇌 감도 미쳤다 오늘 ⚡️",
                    "문제들이 너한테 말 걸까 말까 눈치 보는 중 🙄",
                    "지금은 '가벼운 시동'이 최고의 선택 🧘‍♂️",
                    "쉬고 오니 사고 회로가 반짝반짝 ✨",
                    "너 지금 집중력 맛 좀 난다 🔥",
                    "수학이 \"와, 오늘 분위기 괜찮네?\" 하고 감탄함 😏",
                    "머릿속에 부드러운 바람 살짝 분다 🍃",
                    "방금 복귀한 너는 생각보다 파워가 넘친다 ⚡️",
                    "긴장 말고, 그냥 돌아온 너 그대로 가면 돼 🙂",
                    "쉬고 오니 네 뇌가 텐션업+차분함 섞였어 😌🎧",
                    "오늘 너한테 흐름이 와 있다. 가볍게 타보자 🌊",
                    "뇌세포들이 지금 해맑게 줄 맞춰 서있음 😄",
                    "너 지금 '부드러운 집중 모드' 활성화됨 🌙",
                    "부담 없이 톡— 건드려보면 바로 이어질 거야 ✏️",
                    "쉬고 와서인지 사고 속도가 말도 안 된다 😎",
                    "지금 딱 한 줄만 펼치면 다시 감 올라온다 📖",
                    "이 템포… 아주 기분 좋은 출발이다 🙂✨"
                ];

                const penaltyMessages = [
                    "휴식이 널 너무 좋아해서 보내주기 싫었나봐… 패널티만 살짝 😌⏱️",
                    "쉬는 시간 구간에서 널 강제로 방출했대— 대신 패널티는 챙겨감 😆",
                    "복귀 딜레이 확인! 네 의자도 \"어? 이제 왔어?\" 하고 놀람 🤭",
                    "휴식의 늪에서 탈출 성공! 벌칙은 가볍게만 얹어둘게 🎯",
                    "네 쉬는 시간 텐션이 너무 좋아서 시간이 질투했음 🤨✨",
                    "방금 시간표가 \"흠… 늦었군?\" 하고 고개를 갸웃했다 😄",
                    "쉬는 시간 요정이 널 붙잡고 있어서 늦었다고 보고됨 🧚‍♂️",
                    "집중력 리셋은 좋았지만… 시간이 너를 기다리다 지침 😆⏰",
                    "복귀 딜레이! 시스템이 살짝 \"늦었네?\" 하고 미소 지음 🙂",
                    "네 휴식 모드 너무 맛있었나봐… 패널티는 토핑처럼 얹어줌 🍒",
                    "살짝 늦었지만 괜찮아—대신 패널티로 분위기 리셋 GO! ⚡️",
                    "휴식 구간 초과 감지! 그래도 돌아온 건 칭찬함 😌",
                    "오늘 시간 너한테 끌렸는지… 붙잡고 늘어졌대 😂",
                    "복귀가 늦었네—그래도 다시 온 너 좀 귀엽다 🤭",
                    "쉬는 시간에 감정 이입 많이 했지? 패널티는 상큼하게 ➕",
                    "시간표가 \"오늘 널 너무 기다렸잖아~\" 라고 함 😏",
                    "네가 안 와서 펜이 외로워하고 있었대 ✏️🥲",
                    "살짝만 늦었는데 왜 이렇게 분위기 좋냐 😌",
                    "휴식 구간이랑 너랑 너무 찰떡이라 시간이 질투함 😆",
                    "패널티는 부드럽게 추가… 하지만 오늘 텐션 좋아 👍",
                    "너 없는 동안 문제들이 단체로 하품함 😮‍💨",
                    "커피처럼 쉬다가… 복귀는 디카페인 느낌으로 😌☕",
                    "여유롭게 돌아왔네~ 대신 패널티는 정확히 얹을게 🤓",
                    "늦게 온 만큼 분위기 멋있는데… 그래도 벌칙은 벌칙 😎",
                    "쉬는 시간과 연애하나 했니? 패널티로 현실 복귀 😄",
                    "복귀 시간 지연! 뇌가 슬쩍 놀라서 움찔했대 🧠💥",
                    "휴식 존에서 VIP 대우 받고 왔지? 벌칙은 기본 패키지 😌",
                    "그래, 늦을 수도 있지… 대신 패널티로 균형 잡자 ⚖️",
                    "네가 늦는 사이 연필이 \"나 심심해…\"라고 투덜댐 ✏️😆",
                    "살짝 늦었지만 무드 좋아서 패널티도 귀여워짐 🙂",
                    "시간 초과 감지! 오늘 너 좀 자유로운 영혼이네 🌬️",
                    "쉬는 공간이 너한테 너무 후한데? 벌칙은 내가 챙김 😄",
                    "늦게 와도 환영인데… 패널티는 예외 없음 😌",
                    "너 없는 동안 교과서가 널 찾았다고 함 📘👀",
                    "쉬다 복귀할 때 이 묘한 텐션… 귀엽다 😌",
                    "패널티는 가볍게, 분위기는 무겁지 않게 ⛅️",
                    "휴식 존의 덫에서 빠져나오느라 고생했다 😂",
                    "살짝 늦게 복귀한 너에게 오늘의 벌칙 토큰 지급 🎟️",
                    "너 오는 동안 시계 두 번 돌아갔다 ⏱️😆",
                    "오늘 너, 시간과 밀당하는 중이네? 😏",
                    "패널티는 추가됐지만 집중력은 상승 중 📈",
                    "쉬는 시간과 네 느낌… 너무 잘 맞아서 늦었나봐 😄",
                    "복귀 딜레이! 그래도 기세가 좋아서 OK 👍",
                    "방금 시계가 \"드디어 왔다!\" 하며 박수쳤대 👏",
                    "쉬는 시간 길어진 대가로 패널티만 살짝~ 😌",
                    "휴식 존을 탈출하는데 파일럿 느낌 났다 🛫😂",
                    "늦게 왔지만 분위기는 프로페셔널 😎",
                    "패널티는 주지만 기분 좋은 텐션은 계속된다 ⚡️",
                    "기다리던 문제들이 단체로 눈 마주침 👀",
                    "쉬는 시간 너한테 너무 친절한데? 시간표가 질투함 😅",
                    "뇌가 방금 \"어? 돌아왔어?\" 하고 놀랐대 🧠😳",
                    "늦었지만 시작은 아름답게 하자 🌼",
                    "패널티는 딱 필요한 만큼만—기분은 상하지 않게 🙂",
                    "쉬는 시간에서 레벨업하고 왔네? 벌칙은 기본옵션 🎮",
                    "너를 기다리던 펜이 아주 기쁜 표정이더라 ✏️🥰",
                    "시간이 널 붙잡고 있었던 느낌임… 패널티는 현실 😆",
                    "집중 모드로 재진입! 대신 패널티는 살짝 추가 ⚡️",
                    "쉬는 시간 오버됐지만 너의 텐션은 좋다 😌✨",
                    "복귀 시간 조금 밀렸지만 분위기 말랑해서 괜찮아 🤍",
                    "패널티는 주지만… 솔직히 너 지금 귀여움+1이다 😄➕"
                ];

                // 시간을 60으로 나눈 나머지로 멘트 선택
                const timeIndex = Math.floor(totalRestSeconds) % 60;
                
                let message = '';
                let penaltyText = '';
                
                if (totalRestSeconds > 600) {
                    // 10분 초과: 패널티 멘트 + 지연 시간 표시
                    const overtimeSeconds = totalRestSeconds - 600;
                    const overtimeMinutes = Math.floor(overtimeSeconds / 60);
                    const overtimeSecs = overtimeSeconds % 60;
                    message = penaltyMessages[timeIndex];
                    penaltyText = `귀가시간 지연: ${overtimeMinutes}분 ${overtimeSecs}초`;
                } else {
                    // 정상 휴식: 일반 멘트
                    message = normalMessages[timeIndex];
                }

                this.feedbackMessage.textContent = message;
                if (penaltyText) {
                    this.feedbackPenalty.textContent = penaltyText;
                    this.feedbackPenalty.style.display = 'block';
                } else {
                    this.feedbackPenalty.style.display = 'none';
                }

                this.feedbackOverlay.style.display = 'flex';
                this.feedbackShown = true; // 피드백 표시 플래그

                // 피드백 표시 완료를 부모 창에 알림
                this.sendFeedbackToParent();

                // 5초 후 창 닫기
                setTimeout(() => {
                    this.closeFeedback();
                }, 5000);
            }

            sendFeedbackToParent() {
                // 부모 창에 피드백 표시 완료 메시지 전달
                if (window.parent && window.parent !== window) {
                    try {
                        window.parent.postMessage('feedbackShown', '*');
                        this.feedbackSent = true;
                    } catch (e) {
                        console.log('Error in DMN_time.php:625 - postMessage failed: ' + e.message);
                        this.feedbackSent = false;
                        // 전달 실패 시 1초 후 재시도
                        setTimeout(() => {
                            if (!this.feedbackSent) {
                                this.sendFeedbackToParent();
                            }
                        }, 1000);
                    }
                }
            }

            closeFeedback() {
                this.feedbackOverlay.style.display = 'none';
                
                // 타이머 초기화
                this.isOvertime = false;
                this.currentSeconds = this.totalSeconds;
                this.overtimeSeconds = 0;
                this.overtimeDisplay.style.display = 'none';
                this.updateDisplay();
                this.updateProgress();
            
                // 체크박스 초기화
                document.querySelectorAll('.activity-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // 휴식 종료 - 부모 창에 메시지 전달 (iframe에서 부모로)
                if (window.parent && window.parent !== window) {
                    try {
                        window.parent.postMessage('restEnd', '*');
                    } catch (e) {
                        console.log('Error in DMN_time.php:650 - restEnd postMessage failed: ' + e.message);
                    }
                }

                // 피드백이 전달되지 않았고 팝업이 닫혔다면 다시 표시 시도
                if (!this.feedbackSent && this.feedbackShown) {
                    setTimeout(() => {
                        // 부모 창이 여전히 존재하는지 확인
                        try {
                            if (window.parent && window.parent !== window) {
                                // 다시 피드백 표시
                                this.feedbackOverlay.style.display = 'flex';
                                this.sendFeedbackToParent();
                                // 3초 더 표시
                                setTimeout(() => {
                                    this.closeFeedback();
                                }, 3000);
                            }
                        } catch (e) {
                            // 부모 창이 닫혔거나 접근 불가
                            console.log('Error in DMN_time.php:665 - parent window closed: ' + e.message);
                        }
                    }, 500);
                }
            }

            updateDisplay() {
                if (!this.isOvertime) {
                    const minutes = Math.floor(this.currentSeconds / 60);
                    const seconds = this.currentSeconds % 60;
                    this.timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    // 남은 시간에 따라 색상 변경
                    if (this.currentSeconds <= 60) {
                        this.timerDisplay.style.color = '#dc3545';
                    } else if (this.currentSeconds <= 180) {
                        this.timerDisplay.style.color = '#ffc107';
                    } else {
                        this.timerDisplay.style.color = '#333';
                    }
                } else {
                    this.timerDisplay.textContent = '0:00';
                    this.timerDisplay.style.color = '#28a745';
                    
                    const minutes = Math.floor(this.overtimeSeconds / 60);
                    const seconds = this.overtimeSeconds % 60;
                    this.overtimeValue.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }

            updateProgress() {
                if (!this.isOvertime) {
                    const progress = ((this.totalSeconds - this.currentSeconds) / this.totalSeconds) * 100;
                    this.progressFill.style.width = `${progress}%`;
                } else {
                    this.progressFill.style.width = '100%';
                    this.progressFill.style.background = '#28a745';
                }
            }

            showNotification() {
                this.notification.style.display = 'block';
                setTimeout(() => {
                    this.notification.style.display = 'none';
                }, 5000);
            }

            playSound() {
                // 알림음 기능 제거됨
            }

            saveActivities() {
                const activities = [];
                document.querySelectorAll('.activity-checkbox:checked').forEach(checkbox => {
                    activities.push(checkbox.id);
                });
                localStorage.setItem('dmnActivities', JSON.stringify(activities));
            }
        }

        // 타이머 초기화
        const timer = new DMNTimer();
    </script>
</body>
</html>