// engagement.js

// 1. 웹캠 접근 허용
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

// 2. 몰입도 점수 설정
let engagementScore = 100;
let timer;

// 3. 몰입도 점수를 지속적으로 감소시키는 타이머
function startEngagementTracking() {
    timer = setInterval(() => {
        engagementScore = Math.max(engagementScore - 1, 0);
        console.log('Engagement Score:', engagementScore);
    }, 1000);  // 1초마다 점수 1씩 감소
}

// 4. 학생의 얼굴 움직임 감지
function detectFaceMovement() {
    let lastFrameTime = Date.now();

    // 주기적으로 얼굴 움직임 확인 (실제로는 얼굴인식 라이브러리가 필요함)
    setInterval(() => {
        const currentTime = Date.now();
        const timeElapsed = currentTime - lastFrameTime;

        // 만약 2초 이상 움직임(혹은 인식)이 없다고 가정하면 -2점
        if (timeElapsed > 2000) {
            engagementScore = Math.max(engagementScore - 2, 0);
        } else {
            // 반대로 얼굴이 인식되거나 움직임이 있다고 가정하면 +1점
            engagementScore = Math.min(engagementScore + 1, 100);
        }

        // ★ 포인트: 마지막 시간 갱신
        lastFrameTime = currentTime;

        console.log('Engagement Score:', engagementScore);
    }, 1000);
}

// 5. 몰입도 트래커 시작
window.onload = function() {
    startEngagementTracking();
    detectFaceMovement();
};
