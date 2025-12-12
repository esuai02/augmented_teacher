# 버튼 클릭 무반응 디버깅 가이드

## 문제 증상
- Agent 01 카드 클릭 시 아무 반응 없음
- 패널이 슬라이드되지 않음
- 브라우저에 오류 메시지 없음

## 진단 절차

### 1단계: 브라우저 콘솔 확인

**테스트 페이지 접속**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/test_integration.php
```

**F12를 눌러 개발자 도구 열기** → **Console 탭 선택**

**예상되는 로그 순서**:
```javascript
📦 panel.js loading...                          // panel.js 로딩 시작
🔧 OnboardingPanel IIFE starting...             // IIFE 실행
✅ Assigning OnboardingPanel to window object   // 전역 객체 할당
📄 Document readyState: loading|complete         // DOM 상태
✅ DOM already ready - initializing panel...    // 초기화 시작
🚀 OnboardingPanel.init() called                // init 함수 호출
✅ OnboardingPanel initialized successfully     // 초기화 완료
🎉 panel.js IIFE complete                       // IIFE 완료
=== Agent01 Panel Test Page ===                 // 테스트 페이지 로드
Current user: 2
OnboardingPanel object: {panelElement: div#onboardingRightPanel.onboarding-right-panel, ...}
Test ready!
```

**버튼 클릭 시 예상 로그**:
```javascript
=== Test: Opening panel ===
User ID: 2
OnboardingPanel object: {panelElement: ..., currentUserId: null, ...}
Panel opened successfully
```

### 2단계: 오류 패턴별 해결 방법

#### 패턴 A: panel.js가 로딩되지 않음
```
(콘솔에 아무것도 없음)
```

**원인**:
- 파일 경로 오류
- 서버에 파일이 없음
- 권한 문제

**해결**:
1. 브라우저 Network 탭 확인
2. panel.js 요청이 404 오류인지 확인
3. 파일 존재 여부 확인:
   ```bash
   ls -la /mnt/c/1\ Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js
   ```
4. 서버 URL로 직접 접근 테스트:
   ```
   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js
   ```

#### 패턴 B: OnboardingPanel이 undefined
```javascript
📦 panel.js loading...
🔧 OnboardingPanel IIFE starting...
(이후 로그 없음)
```

**원인**: JavaScript 구문 오류

**해결**:
1. Console에서 구문 오류 메시지 확인
2. panel.js 파일의 구문 검증
3. 브라우저 호환성 확인 (화살표 함수, const/let 지원)

#### 패턴 C: init() 함수가 호출되지 않음
```javascript
📦 panel.js loading...
🔧 OnboardingPanel IIFE starting...
✅ Assigning OnboardingPanel to window object
📄 Document readyState: loading
⏳ Waiting for DOMContentLoaded...
(DOMContentLoaded fired 로그 없음)
```

**원인**: DOMContentLoaded 이벤트 미발생

**해결**:
1. 스크립트 위치 확인 (</body> 직전에 있어야 함)
2. 다른 스크립트의 오류로 페이지 로딩 중단 확인

#### 패턴 D: testPanelOpen() 함수 미실행
```javascript
(모든 초기화 로그는 정상)
(클릭해도 "=== Test: Opening panel ===" 로그 없음)
```

**원인**: onclick 이벤트 핸들러 바인딩 실패

**해결**:
1. Elements 탭에서 카드 요소 확인
2. onclick 속성이 제대로 있는지 확인:
   ```html
   <div class="test-card" ... onclick="testPanelOpen()">
   ```
3. 콘솔에서 직접 함수 호출 테스트:
   ```javascript
   testPanelOpen()
   ```

#### 패턴 E: OnboardingPanel.open() 실행 오류
```javascript
=== Test: Opening panel ===
User ID: 2
OnboardingPanel object: {...}
ERROR opening panel: [오류 메시지]
```

**원인**: open() 메서드 내부 오류

**해결**:
1. 오류 메시지 확인
2. panel.js의 open() 함수 검토
3. userid 값 확인

### 3단계: 수동 테스트

브라우저 콘솔에서 직접 실행:

```javascript
// 1. OnboardingPanel 객체 확인
console.log(window.OnboardingPanel);

// 2. panelElement 확인
console.log(OnboardingPanel.panelElement);

// 3. 패널 수동 열기 시도
OnboardingPanel.open(2);  // userid = 2

// 4. 패널 요소의 클래스 확인
console.log(OnboardingPanel.panelElement.className);

// 5. active 클래스 수동 추가
OnboardingPanel.panelElement.classList.add('active');
```

### 4단계: CSS 문제 확인

패널이 열리지만 보이지 않는 경우:

```javascript
// 패널 요소의 스타일 확인
const panel = document.getElementById('onboardingRightPanel');
console.log(window.getComputedStyle(panel).transform);
// 결과: "translateX(0px)" 이어야 함 (active 상태)
// 결과: "translateX(400px)" 이면 안 보임 (비활성 상태)
```

## 해결된 문제 목록

### ✅ 해결됨: onclick 이벤트 핸들러 this 바인딩 문제
- **증상**: 버튼 클릭 시 아무 반응 없음
- **원인**: HTML 문자열 내 `onclick="OnboardingPanel.method()"`에서 `this` 컨텍스트 손실
- **해결**: `addEventListener`로 변경, 화살표 함수로 `this` 바인딩

**수정 전**:
```javascript
actionsDiv.innerHTML = `
    <button onclick="OnboardingPanel.saveMBTI()">MBTI 저장</button>
`;
```

**수정 후**:
```javascript
actionsDiv.innerHTML = `
    <button id="btnSaveMbti">MBTI 저장</button>
`;

document.getElementById('btnSaveMbti').addEventListener('click', () => {
    this.saveMBTI();
});
```

### ✅ 해결됨: 파일 경로 문제
- **증상**: panel.js, panel.css 로딩 실패
- **원인**: 상대 경로 사용으로 인한 경로 해석 오류
- **해결**: 절대 경로로 변경

**수정 전**:
```html
<script src="ui/panel.js"></script>
<link rel="stylesheet" href="ui/panel.css">
```

**수정 후**:
```html
<script src="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js"></script>
<link rel="stylesheet" href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.css">
```

## 추가 디버깅 도구

### 네트워크 탭 확인
1. F12 → Network 탭
2. 페이지 새로고침 (Ctrl+F5)
3. panel.js, panel.css 요청 확인
4. 상태 코드 확인 (200 OK여야 함)

### Elements 탭 확인
1. F12 → Elements 탭
2. `<div id="onboardingRightPanel">` 요소 찾기
3. 클래스 확인: `onboarding-right-panel active` (열린 상태)
4. Styles 패널에서 CSS 규칙 확인

### 성능 문제 진단
```javascript
// panel.js 로딩 시간 측정
performance.getEntriesByName('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js');
```

## 일반적인 실수

### ❌ 실수 1: 전역 변수명 충돌
다른 스크립트에서 `OnboardingPanel`을 사용하는 경우

**해결**: 콘솔에서 확인
```javascript
console.log(typeof OnboardingPanel);  // "object"여야 함
```

### ❌ 실수 2: Moodle 권한 문제
로그인하지 않은 상태에서 접근

**해결**: require_login() 확인

### ❌ 실수 3: 브라우저 캐시
이전 버전의 panel.js가 캐시됨

**해결**: 하드 리프레시 (Ctrl+Shift+R 또는 Ctrl+F5)

## 긴급 임시 해결책

모든 디버깅 실패 시 패널을 수동으로 열기:

```javascript
// 브라우저 콘솔에서 실행
const panel = document.getElementById('onboardingRightPanel');
if (panel) {
    panel.classList.add('active');
    // 데이터 로딩
    fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_service.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=checkExistingReport&userid=2'
    })
    .then(r => r.json())
    .then(data => console.log(data));
}
```

## 지원 연락처

문제 해결이 안 될 경우:
1. 브라우저 콘솔 스크린샷 캡처
2. Network 탭 스크린샷 캡처
3. panel.js 파일의 존재 여부 확인
4. 서버 에러 로그 확인: `/var/log/apache2/error.log`
