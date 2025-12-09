// engagement.js

// 📹 Step 1: 웹캠 접근 허용
const video = document.getElementById('webcam');

// 웹캠 스트림 가져오기
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
        console.log('Webcam stream started');
    })
    .catch(error => {
        console.error('Webcam access denied:', error);
    });

// 📊 Step 2: 몰입도 점수 설정
let engagementScore = 100;
let timer;

// 📌 Step 3: 몰입도 점수를 지속적으로 감소시키는 타이머
function startEngagementTracking() {
    timer = setInterval(() => {
        engagementScore = Math.max(engagementScore - 1, 0);
        console.log('Engagement Score:', engagementScore);
    }, 1000);  // 1초마다 점수 감소
}

// 🧠 Step 4: 학생의 얼굴 움직임 감지
function detectFaceMovement() {
    let lastFrameTime = Date.now();

    // 주기적으로 얼굴 움직임 확인
    setInterval(() => {
        const currentTime = Date.now();
        const timeElapsed = currentTime - lastFrameTime;

        // 화면에 집중하지 않으면 몰입도 감소
        if (timeElapsed > 2000) {
            engagementScore = Math.max(engagementScore - 2, 0);
        } else {
            engagementScore = Math.min(engagementScore + 1, 100);
        }

        console.log('Engagement Score:', engagementScore);
    }, 1000);
}

// 📌 Step 5: 몰입도 트래커 시작
window.onload = function() {
    startEngagementTracking();
    detectFaceMovement();
};
