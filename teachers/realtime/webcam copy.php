<?php
// 다른 PHP 로직(세션 체크, DB 연결 등)이 필요하다면 이 지점에 추가 가능합니다.

// 전체 HTML을 echo로 출력
echo '
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Student Engagement Tracker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        h1 {
            margin-bottom: 20px;
        }
        video {
            border: 2px solid #333;
            width: 640px;
            height: 480px;
            background-color: #000;
        }
        #scoreDisplay {
            margin-top: 1rem;
            font-size: 1.2rem;
            color: #333;
        }
    </style>
</head>
<body>

    <h1>Student Engagement Tracker</h1>
    <!-- 웹캠 영상 표시 -->
    <video id="webcam" autoplay playsinline></video>
    <!-- 몰입도 점수 표시 -->
    <div id="scoreDisplay">Engagement Score: 100</div>

    <script>
        // 1) 웹캠 접근 (HTTPS 또는 localhost 환경 필요)
        const videoElement = document.getElementById("webcam");

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                videoElement.srcObject = stream;
                console.log("Webcam stream started");
            })
            .catch(error => {
                console.error("Webcam access denied:", error);
                // 필요한 경우, 에러 표시 메시지를 표시할 수도 있음
            });

        // 2) 몰입도 점수 관련 변수
        let engagementScore = 100;
        const scoreDisplay = document.getElementById("scoreDisplay");

        // 3) 주기적으로 몰입도 -1 감소
        function startEngagementTracking() {
            setInterval(() => {
                // 최소 0 이상 유지
                engagementScore = Math.max(engagementScore - 1, 0);
                updateScoreDisplay();
            }, 1000);
        }

        // 4) 간단한 "얼굴 움직임 감지" 로직 (실제로는 face-api.js 등 사용 필요)
        function detectFaceMovement() {
            let lastFrameTime = Date.now();
            
            setInterval(() => {
                const currentTime = Date.now();
                const timeElapsed = currentTime - lastFrameTime;

                if (timeElapsed > 2000) {
                    // 2초 이상 움직임 없다고 가정 → 점수 -2
                    engagementScore = Math.max(engagementScore - 2, 0);
                } else {
                    // 움직임 있다고 가정 → 점수 +1
                    engagementScore = Math.min(engagementScore + 1, 100);
                }

                lastFrameTime = currentTime;
                updateScoreDisplay();
            }, 1000);
        }

        // 5) 점수 표시 갱신 함수
        function updateScoreDisplay() {
            scoreDisplay.textContent = "Engagement Score: " + engagementScore;
            console.log("Engagement Score:", engagementScore);
        }

        // 6) 페이지 로드 후 실행
        window.onload = function() {
        //    startEngagementTracking();
          //  detectFaceMovement();
        };
    </script>

</body>
</html>
';
?>
