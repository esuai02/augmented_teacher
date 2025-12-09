<?php
/**
 * 30초 루틴 학습 페이지
 */

session_start();

// 사용자 정보
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>30초 사고 마스터 루틴 | Mathking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .routine-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }

        .routine-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .routine-header h1 {
            color: #2d3748;
            font-size: 2em;
            margin-bottom: 15px;
        }

        .routine-header p {
            color: #718096;
            font-size: 1.1em;
        }

        .timer-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }

        .timer-value {
            font-size: 4em;
            font-weight: bold;
            margin: 10px 0;
        }

        .timer-label {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .routine-steps {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .step-bubble {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #718096;
            transition: all 0.3s;
        }

        .step-bubble.active {
            background: #667eea;
            color: white;
            transform: scale(1.2);
        }

        .step-bubble.completed {
            background: #48bb78;
            color: white;
        }

        .current-step {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .current-step h2 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .current-step p {
            color: #718096;
            font-size: 1.2em;
            line-height: 1.6;
        }

        .control-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-start {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-pause {
            background: #f6ad55;
            color: white;
        }

        .btn-reset {
            background: #fc8181;
            color: white;
        }

        .btn-next {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
        }

        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(66, 153, 225, 0.3);
        }

        .practice-problem {
            background: #fff5f5;
            border: 2px solid #feb2b2;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .problem-content {
            font-size: 1.3em;
            color: #2d3748;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .highlight {
            background: #fef5e7;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulsing {
            animation: pulse 1s infinite;
        }

        .success-message {
            background: #c6f6d5;
            border: 2px solid #9ae6b4;
            color: #22543d;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }

        .success-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="routine-container">
        <div class="routine-header">
            <h1>🏆 30초 사고 마스터 루틴</h1>
            <p>차분하게 문제를 분석하고, 전략적으로 접근하는 습관을 만들어봐요</p>
        </div>

        <div class="timer-display">
            <div class="timer-label">남은 시간</div>
            <div class="timer-value" id="timer">00:00</div>
            <div class="timer-label" id="step-name">준비</div>
        </div>

        <div class="routine-steps">
            <div class="step-indicator">
                <div class="step-bubble active" id="step-1">1</div>
                <div class="step-bubble" id="step-2">2</div>
                <div class="step-bubble" id="step-3">3</div>
                <div class="step-bubble" id="step-4">4</div>
            </div>

            <div class="current-step" id="current-step-display">
                <h2>시작할 준비가 되셨나요?</h2>
                <p>아래 문제를 천천히 읽고, 30초 루틴을 적용해보세요.<br>
                서두르지 마세요. 충분한 시간이 있습니다!</p>
            </div>
        </div>

        <div class="practice-problem">
            <h3>📝 연습 문제</h3>
            <div class="problem-content">
                한 변의 길이가 <span class="highlight">6cm</span>인 정사각형과
                한 변의 길이가 <span class="highlight">8cm</span>인 정사각형이 있습니다.
                두 정사각형의 넓이의 합은 몇 cm²입니까?
            </div>
        </div>

        <div class="control-buttons">
            <button class="btn btn-start" onclick="startRoutine()">루틴 시작</button>
            <button class="btn btn-pause" onclick="pauseRoutine()" style="display: none;">일시정지</button>
            <button class="btn btn-reset" onclick="resetRoutine()" style="display: none;">처음부터</button>
            <button class="btn btn-next" onclick="nextStep()" style="display: none;">다음 단계</button>
        </div>

        <div class="success-message" id="success-message">
            <h3>🎉 훌륭해요! 30초 루틴을 완벽하게 수행했습니다!</h3>
            <p>이제 답을 구해보셨나요? 정답은 100cm²입니다. (36 + 64 = 100)</p>
        </div>
    </div>

    <script>
        let currentStep = 0;
        let timer = null;
        let timeRemaining = 0;
        let isPaused = false;

        const steps = [
            {
                name: "문제 읽기",
                duration: 10,
                instruction: "천천히 문제를 읽으며 핵심 단어를 찾아보세요",
                detail: "정사각형, 6cm, 8cm, 넓이의 합 - 이런 핵심어를 주목하세요!"
            },
            {
                name: "멈추고 생각하기",
                duration: 30,
                instruction: "잠깐! 관련된 개념 3가지를 떠올려보세요",
                detail: "정사각형 넓이 공식, 제곱, 덧셈... 어떤 개념이 필요할까요?"
            },
            {
                name: "전략 선택",
                duration: 10,
                instruction: "어떤 방법으로 풀지 결정하세요",
                detail: "각 정사각형의 넓이를 구한 후 더하기!"
            },
            {
                name: "실행하기",
                duration: 60,
                instruction: "선택한 전략으로 차근차근 풀어보세요",
                detail: "6×6=36, 8×8=64, 36+64=100"
            }
        ];

        function startRoutine() {
            currentStep = 0;
            document.querySelector('.btn-start').style.display = 'none';
            document.querySelector('.btn-pause').style.display = 'inline-block';
            document.querySelector('.btn-reset').style.display = 'inline-block';
            nextStep();
        }

        function nextStep() {
            if (currentStep >= steps.length) {
                completeRoutine();
                return;
            }

            // 단계 표시 업데이트
            document.querySelectorAll('.step-bubble').forEach((bubble, index) => {
                bubble.classList.remove('active');
                if (index < currentStep) {
                    bubble.classList.add('completed');
                } else if (index === currentStep) {
                    bubble.classList.add('active', 'pulsing');
                }
            });

            // 현재 단계 정보 표시
            const step = steps[currentStep];
            document.getElementById('step-name').textContent = step.name;
            document.getElementById('current-step-display').innerHTML = `
                <h2>${step.name}</h2>
                <p>${step.instruction}</p>
                <p style="margin-top: 15px; font-size: 1em; color: #a0aec0;">${step.detail}</p>
            `;

            // 타이머 시작
            timeRemaining = step.duration;
            startTimer();

            currentStep++;
        }

        function startTimer() {
            clearInterval(timer);
            timer = setInterval(() => {
                if (!isPaused) {
                    timeRemaining--;
                    updateTimerDisplay();

                    if (timeRemaining <= 0) {
                        clearInterval(timer);
                        if (currentStep < steps.length) {
                            nextStep();
                        } else {
                            completeRoutine();
                        }
                    }
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('timer').textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        function pauseRoutine() {
            isPaused = !isPaused;
            const pauseBtn = document.querySelector('.btn-pause');
            pauseBtn.textContent = isPaused ? '재개' : '일시정지';
        }

        function resetRoutine() {
            clearInterval(timer);
            currentStep = 0;
            isPaused = false;
            document.getElementById('timer').textContent = '00:00';
            document.getElementById('step-name').textContent = '준비';
            document.querySelector('.btn-start').style.display = 'inline-block';
            document.querySelector('.btn-pause').style.display = 'none';
            document.querySelector('.btn-reset').style.display = 'none';
            document.getElementById('success-message').classList.remove('show');

            document.querySelectorAll('.step-bubble').forEach(bubble => {
                bubble.classList.remove('active', 'completed', 'pulsing');
            });
            document.querySelector('#step-1').classList.add('active');

            document.getElementById('current-step-display').innerHTML = `
                <h2>시작할 준비가 되셨나요?</h2>
                <p>아래 문제를 천천히 읽고, 30초 루틴을 적용해보세요.<br>
                서두르지 마세요. 충분한 시간이 있습니다!</p>
            `;
        }

        function completeRoutine() {
            clearInterval(timer);
            document.getElementById('success-message').classList.add('show');
            document.querySelector('.btn-pause').style.display = 'none';

            // 완료 기록
            if (typeof parent !== 'undefined' && parent.trackProgress) {
                parent.trackProgress();
            }
        }
    </script>
</body>
</html>