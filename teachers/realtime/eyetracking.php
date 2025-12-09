<?php
// ---------------------------------------------------------
// index.php
// 웹 브라우저에 로드되어 Tobii Eyetracker + GPT 연동을 시도하는 예시
// ---------------------------------------------------------

// 만약 세션이나 사용자 식별 로직이 필요하다면 이곳에서 처리
// session_start(); 등

echo '
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Tobii Eyetracker 5 + GPT Realtime Feedback (Demo)</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f0f0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: start;
      min-height: 100vh;
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
    .coords {
      margin-top: 10px;
      font-size: 0.9rem;
      color: #555;
    }
  </style>

  <!-- (예시) html2canvas 라이브러리: 전체화면 캡처용 -->
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

  <!-- (예시) Tobii Web SDK/Stream Engine -->
  <!-- 실제 사용 시, Tobii에서 제공하는 JS 라이브러리를 다운로드 혹은 CDN 경로를 사용 -->
  <!-- <script src="path/to/TobiiWebSDK.js"></script> -->

  <script>
    let latestGazeX = 0;
    let latestGazeY = 0;

    /**********************************************************
     * 1) Tobii Eyetracker 초기화 (가상 시뮬레이션 예시)
     *    실제 구현 시에는 Tobii 웹 SDK 문서 참고
     **********************************************************/
    function initTobiiEyetracker() {
      console.log("Tobii Eyetracker 초기화 시도...");

      /*
      // 실제 예시 (가상 코드)
      const eyeTrackerManager = new TobiiEyeTrackerManager();
      eyeTrackerManager.init().then(async () => {
        const devices = await eyeTrackerManager.scanDevices();
        if (devices.length > 0) {
          await eyeTrackerManager.connect(devices[0]);
          console.log("Tobii Eyetracker 연결 완료");

          // 실시간 시선 데이터
          eyeTrackerManager.onGazeData = (gazeData) => {
            latestGazeX = gazeData.gazeX;
            latestGazeY = gazeData.gazeY;
            document.getElementById("coordsDisplay").textContent
              = `Gaze: (${latestGazeX}, ${latestGazeY})`;
          };
        } else {
          console.error("Tobii Eyetracker를 찾을 수 없습니다.");
        }
      }).catch(err => {
        console.error("Tobii 초기화 오류:", err);
      });
      */

      // 데모용: 1초마다 가상 좌표 변경
      setInterval(() => {
        latestGazeX = Math.floor(Math.random() * window.innerWidth);
        latestGazeY = Math.floor(Math.random() * window.innerHeight);
        document.getElementById("coordsDisplay").textContent
          = `Gaze(가상): (${latestGazeX}, ${latestGazeY})`;
      }, 1000);

      console.log("Tobii Eyetracker 가상 좌표 시뮬레이션 시작");
    }

    /**********************************************************
     * 2) 전체 화면 캡처 + 시선 주변 영역 캡처
     **********************************************************/
    async function captureScreenAndGazeArea(cnttext) {
      // 1) 전체 화면 캡처
      const body = document.body;
      const fullCanvas = await html2canvas(body);
      const fullDataURL = fullCanvas.toDataURL("image/png");

      // 2) 시선 주변 영역 추출 (가령 200 x 200)
      const width = 200;
      const height = 200;
      const gazeX = Math.max(0, latestGazeX - width / 2);
      const gazeY = Math.max(0, latestGazeY - height / 2);

      // 별도 canvas 생성
      const partialCanvas = document.createElement("canvas");
      partialCanvas.width = width;
      partialCanvas.height = height;
      const ctx = partialCanvas.getContext("2d");

      // 전체 이미지를 임시 Image로 불러와 잘라 그림
      return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
          ctx.drawImage(
            img,
            gazeX,
            gazeY,
            width,
            height,
            0,
            0,
            width,
            height
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

    /**********************************************************
     * 3) 서버로 전송 -> 서버에서 GPT API 호출
     **********************************************************/
    async function sendToGPT(payload) {
      try {
        // sendToGPT.php에 POST
        const response = await fetch("sendToGPT.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        const result = await response.json();
        console.log("GPT 응답:", result);

        // 결과 표시
        const feedbackElem = document.getElementById("feedbackDisplay");
        feedbackElem.textContent = result.feedback || "No feedback received";
      } catch (error) {
        console.error("Error in sending data to GPT:", error);
      }
    }

    /**********************************************************
     * 4) 페이지 로드 후 실행
     **********************************************************/
    window.onload = () => {
      // (1) Tobii Eyetracker 연결 시도
      initTobiiEyetracker();

      // (2) 클릭 이벤트 -> 화면/시선영역 캡처 + GPT 전송
      document.getElementById("captureButton").addEventListener("click", async () => {
        // 사용자가 전송하고자 하는 콘텐츠 정보
        const cnttext = "이 부분에 실제 컨텐츠 정보를 담을 수 있음";

        // 화면 및 시선 주변 이미지 캡처
        const captureData = await captureScreenAndGazeArea(cnttext);

        // 서버 측 GPT 호출
        await sendToGPT(captureData);
      });
    };
  </script>
</head>
<body>
  <h1>Tobii Eyetracker 5 + GPT Realtime Feedback (Demo)</h1>

  <!-- 시선 주변 이미지 전송 버튼 -->
  <button id="captureButton">시선 주변 이미지 전송하기</button>

  <!-- 시선 좌표 표시 -->
  <div id="coordsDisplay" class="coords">Gaze: (0, 0)</div>

  <!-- GPT 피드백 영역 -->
  <div id="feedbackDisplay">여기에 GPT 피드백이 표시됩니다</div>
</body>
</html>
';
?>
