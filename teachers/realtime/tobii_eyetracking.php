<?php
// --------------- 추가: 세션이나 DB 연결, 사용자 식별 로직이 필요하면 여기서 처리 ---------------
// 예: $_SESSION['user_id'] = 123; (로그인 여부 등)

echo '
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Tobii Eyetracker + GPT Realtime Feedback</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f0f0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: start;
      height: 100vh;
      margin: 0;
      padding: 2rem;
    }
    h1 {
      margin-bottom: 20px;
    }
    #captureButton {
      margin: 20px 0;
      padding: 10px 20px;
      font-size: 1rem;
      cursor: pointer;
    }
    #feedbackDisplay {
      margin-top: 1rem;
      width: 640px;
      min-height: 100px;
      border: 1px solid #ccc;
      background: #fff;
      padding: 1rem;
      box-sizing: border-box;
    }
  </style>

  <!-- html2canvas 라이브러리 (전체화면 캡처용) -->
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

  <!-- (예시) Tobii Eyetracker와 통신하기 위한 JS/SDK 삽입 -->
  <!-- 실제로는 Tobii 측의 Web SDK 또는 Stream Engine 브라우저 연동 코드를 사용해야 함 -->
  <script>
    let latestGazeX = 0;
    let latestGazeY = 0;

    // (가정) Tobii Eyetracker와 연결되는 WebSocket/SDK 초기화 예시
    // 실제 사용시 라이브러리 문서 참조
    function initTobiiEyetracker() {
      console.log("Tobii Eyetracker 초기화 시도...");

      // 예시: WebSocket 이용
      // var socket = new WebSocket("ws://localhost:8080");
      // socket.onopen = function() {
      //   console.log("Tobii WebSocket connected");
      //   // 필요 시 인증/구독 메시지 전송
      // };
      // socket.onmessage = function(event) {
      //   const data = JSON.parse(event.data);
      //   // data 예시: { gazeX: 123, gazeY: 456, ... }
      //   if (data.gazeX !== undefined && data.gazeY !== undefined) {
      //     latestGazeX = data.gazeX;
      //     latestGazeY = data.gazeY;
      //   }
      // };

      // 실제 동작 없이 데모용으로 가상 좌표 변경 (1초 간격)
      setInterval(() => {
        latestGazeX = Math.floor(Math.random() * window.innerWidth);
        latestGazeY = Math.floor(Math.random() * window.innerHeight);
      }, 1000);

      console.log("Tobii Eyetracker 가상 좌표 시뮬레이션 시작");
    }

    // 화면 캡처 및 주변 이미지 추출
    async function captureScreenAndGazeArea(cnttext) {
      // 1) 전체 화면 캡처
      const body = document.body;
      const fullCanvas = await html2canvas(body);
      const fullDataURL = fullCanvas.toDataURL("image/png");

      // 2) 시선 주변 영역 추출 (예: 200x200 크기)
      // 시선 좌표를 기준으로 캡처하므로, 좌표 범위 체크 필요
      const width = 200;
      const height = 200;
      const gazeX = Math.max(0, latestGazeX - width / 2);
      const gazeY = Math.max(0, latestGazeY - height / 2);

      // 별도 canvas 생성
      const partialCanvas = document.createElement("canvas");
      partialCanvas.width = width;
      partialCanvas.height = height;
      const ctx = partialCanvas.getContext("2d");

      // 전체 캡처 이미지를 임시 Image로 불러와 특정 영역만 잘라 그림
      return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
          ctx.drawImage(
            img, 
            gazeX, gazeY, width, height, 
            0, 0, width, height
          );
          const partialDataURL = partialCanvas.toDataURL("image/png");
          resolve({
            fullImage: fullDataURL,
            partialImage: partialDataURL,
            gazeCoords: { x: latestGazeX, y: latestGazeY },
            cnttext: cnttext
          });
        };
        img.src = fullDataURL;
      });
    }

    // GPT로 전송 (fetch 예시)
    async function sendToGPT(payload) {
      // payload 내에 fullImage, partialImage, cnttext, gazeCoords 등이 존재
      // 여기서는 서버에 POST → 서버 측에서 GPT API 호출 처리
      // 백엔드 endpoint 예: /sendToGPT.php
      try {
        const response = await fetch("/sendToGPT.php", {
          method: "POST",
          headers: {"Content-Type": "application/json"},
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        console.log("GPT 응답:", result);

        // 결과 표시 (가정: result.feedback 에 피드백이 담겨 있음)
        document.getElementById("feedbackDisplay").textContent = result.feedback || "No feedback received";
      } catch (error) {
        console.error("Error in sending data to GPT:", error);
      }
    }

    window.onload = () => {
      // (1) Tobii Eyetracker 초기화
      initTobiiEyetracker();

      // (2) 클릭 시 캡처 + GPT 전송
      document.getElementById("captureButton").addEventListener("click", async () => {
        const cnttext = "이 부분에 실제 컨텐츠 정보를 담아 보낼 수 있음";
        const captureData = await captureScreenAndGazeArea(cnttext);
        await sendToGPT(captureData);
      });
    };
  </script>
</head>
<body>
  <h1>Tobii Eyetracker 5 + GPT Realtime Feedback</h1>
  <button id="captureButton">시선 주변 이미지 전송하기</button>
  <div id="feedbackDisplay">여기에 GPT 피드백이 표시됩니다</div>
</body>
</html>
';
?>
