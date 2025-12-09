<?php
// --------------- 추가: 세션이나 DB 연결, 사용자 식별 로직이 필요하면 여기서 처리 ---------------
// 예: $_SESSION['user_id'] = 123; (로그인 여부 등)

// 전체 HTML을 echo로 출력
echo '
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Student Engagement Tracker + face-api.js</title>
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

  <!-- face-api.js 라이브러리 (CDN) -->
  <!-- 버전은 예시로 0.22.2를 사용. 필요시 최신 버전 사용 가능. -->
  <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body>
  <h1>Student Engagement Tracker (with face-api.js)</h1>

  <!-- 웹캠 영상 표시 -->
  <video id="webcam" autoplay playsinline></video>
  
  <!-- 몰입도 점수 표시 -->
  <div id="scoreDisplay">Engagement Score: 100</div>

  <script>
    /**********************************************************
     * 1) 전역 변수 및 요소 가져오기
     **********************************************************/
    const videoElement = document.getElementById("webcam");
    const scoreDisplay = document.getElementById("scoreDisplay");

    // 초기 몰입도 점수
    let engagementScore = 100;

    /**********************************************************
     * 2) face-api.js 모델 로드 함수
     *    - Tiny Face Detector 모델을 로드한다.
     *    - 실제로는 /models 폴더에 모델 파일들이 있어야 함
     **********************************************************/
    async function loadFaceApiModels() {
      const MODEL_URL = "/models"; // tiny_face_detector_model-weights_manifest.json 등 존재해야 함

      // TinyFaceDetector 로드
      await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);

      // 필요에 따라 추가 모델:
      // await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
      // await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

      console.log("face-api.js 모델 로드 완료");
    }

    /**********************************************************
     * 3) 웹캠 시작
     **********************************************************/
    async function startWebcam() {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        videoElement.srcObject = stream;
        console.log("Webcam stream started");
      } catch (err) {
        console.error("Webcam access denied:", err);
      }
    }

    /**********************************************************
     * 4) 점수 표시 갱신
     **********************************************************/
    function updateScoreDisplay() {
      scoreDisplay.textContent = "Engagement Score: " + engagementScore;
      console.log("Engagement Score:", engagementScore);
    }

    /**********************************************************
     * 5) 얼굴 움직임 감지 (face-api.js 적용)
     *    - 1초마다 비디오 프레임에서 얼굴 감지
     *    - 감지 성공: lastFrameTime 갱신 & 점수 +1
     *    - 감지 실패: 2초 이상 미검출 시 점수 -2
     **********************************************************/
    async function detectFaceMovement() {
      // 먼저 모델 로드
      await loadFaceApiModels();

      let lastFrameTime = Date.now();

      // 1초 간격으로 얼굴 감지
      setInterval(async () => {
        // 1) 비디오에서 얼굴 감지
        const detections = await faceapi.detectAllFaces(
          videoElement,
          new faceapi.TinyFaceDetectorOptions({
            inputSize: 224,       // 성능/정확도 트레이드오프
            scoreThreshold: 0.5   // 0~1 사이 값, 높을수록 "엄격"하게 얼굴로 판정
          })
        );

        if (detections.length > 0) {
          // 얼굴이 1개 이상 감지됨 → 움직임 있다고 판단
          lastFrameTime = Date.now();
          // 점수 상승
          engagementScore = Math.min(engagementScore + 1, 100);
        } else {
          // 얼굴 미검출
          const currentTime = Date.now();
          const timeElapsed = currentTime - lastFrameTime;
          if (timeElapsed > 2000) {
            // 2초 이상 얼굴 미검출 → 점수 감소
            engagementScore = Math.max(engagementScore - 2, 0);
          }
        }

        updateScoreDisplay();
      }, 1000);
    }

    /**********************************************************
     * 6) 페이지 로드 후 실행
     **********************************************************/
    window.onload = async function() {
      // (1) 웹캠 권한 요청 및 시작
      await startWebcam();

      // (2) face-api.js 모델 로드 & 얼굴 감지 시작
      detectFaceMovement();
    };
  </script>
</body>
</html>

';
?>
