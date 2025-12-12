# Agent 15 - iframe 통합 가이드

## 🎯 개요

Agent 15는 이제 **메인 페이지 + iframe 구조**로 동작합니다:
- **메인 페이지**: 좌측 카드, 우측 iframe 패널
- **iframe 내부**: 문제 재정의 & 개선방안 UI

## 📁 파일 구조

```
/agent15_problem_redefinition/
├── index.php                          # 메인 페이지 (좌측 카드 + 우측 iframe)
└── ui/
    ├── index.php                      # iframe 내부 페이지 (자동 레이아웃 조정)
    ├── problem_redefinition_panel.php # UI 컴포넌트
    ├── problem_redefinition_functions.js # 핵심 기능
    ├── test.html                      # 테스트 페이지
    ├── README.md                      # 기술 문서
    └── USAGE.md                       # 사용자 가이드
```

## 🌐 접속 URL

### 메인 페이지 (권장)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/index.php
```

**동작:**
1. 좌측에 "문제 재정의 & 개선방안" 카드 표시
2. 카드 클릭 시 우측에 iframe 패널 표시
3. iframe에 `ui/index.php` 로드

### iframe 내부 페이지 (직접 접속 가능)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/index.php
```

**동작:**
- iframe에서 열릴 때: 우측 패널만 전체 화면으로 표시
- 직접 접속 시: 좌측 카드 + 우측 패널 모두 표시

## 🎨 레이아웃 구조

### 메인 페이지 (index.php)

```
┌─────────────────────────────────────────────┐
│          Header (Agent 15 타이틀)           │
├──────────────┬──────────────────────────────┤
│  Left Panel  │      Right Panel (iframe)    │
│              │                              │
│  ┌────────┐  │  ┌────────────────────────┐ │
│  │ Card 1 │  │  │                        │ │
│  └────────┘  │  │   iframe 내부 페이지    │ │
│              │  │                        │ │
│  Info Box    │  │   (ui/index.php)       │ │
│              │  │                        │ │
└──────────────┴──────────────────────────────┘
```

### iframe 내부 (ui/index.php)

**직접 접속 시:**
```
┌─────────────────────────────────────────────┐
│  Left Panel  │      Right Panel             │
│              │                              │
│  ┌────────┐  │  ┌────────────────────────┐ │
│  │ Card   │  │  │  문제 재정의           │ │
│  └────────┘  │  │  텍스트박스            │ │
│              │  │  버튼들                │ │
└──────────────┴──────────────────────────────┘
```

**iframe 내부에서 로드될 때:**
```
┌─────────────────────────────────────────────┐
│          문제 재정의 & 개선방안              │
│                                             │
│  ┌────────────────────────────────────────┐ │
│  │  자동 생성된 내용 텍스트박스            │ │
│  │                                        │ │
│  └────────────────────────────────────────┘ │
│                                             │
│  [📊 문제 재정의 가져오기] [💾 저장]        │
└─────────────────────────────────────────────┘
```

## ⚙️ 기술 구현

### 1. iframe 감지 및 레이아웃 조정

ui/index.php에서 자동으로 iframe 여부를 감지:

```javascript
// iframe에서 실행 중인지 감지
const isInIframe = window.self !== window.top;

if (isInIframe) {
    // iframe 스타일 적용
    document.body.classList.add('in-iframe');

    // 좌측 패널 숨기기
    leftPanel.style.display = 'none';

    // 우측 패널을 전체 화면으로
    rightPanel.style.display = 'block';
    rightPanel.style.width = '100%';
}
```

### 2. postMessage를 통한 데이터 전달

**부모 → iframe (사용자 ID 전달):**

```javascript
// 메인 페이지 (index.php)
iframe.contentWindow.postMessage({
    type: 'setUserId',
    userId: studentId
}, '*');
```

**iframe에서 메시지 수신:**

```javascript
// ui/index.php
window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'setUserId') {
        window.currentUserId = event.data.userId;
    }
});
```

### 3. ESC 키로 패널 닫기

```javascript
// 메인 페이지 (index.php)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePanel();
    }
});
```

## 🚀 사용 방법

### 일반 사용자

1. 메인 페이지 접속:
   ```
   https://mathking.kr/.../agent15_problem_redefinition/index.php?userid=2
   ```

2. 좌측 패널에서 "문제 재정의 & 개선방안" 카드 클릭

3. 우측 iframe 패널에서:
   - "📊 문제 재정의 가져오기" 버튼 클릭
   - GPT API가 자동으로 데이터 수집 및 분석
   - 생성된 내용 확인 및 수정
   - "💾 저장" 버튼으로 저장

4. ESC 키 또는 × 버튼으로 패널 닫기

### 개발자

#### 다른 페이지에 iframe으로 통합

```html
<iframe
    src="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/index.php?userid=2"
    width="100%"
    height="600px"
    frameborder="0">
</iframe>
```

#### JavaScript로 동적 로드

```javascript
function loadAgent15(userId) {
    const iframe = document.createElement('iframe');
    iframe.src = `agent15_problem_redefinition/ui/index.php?userid=${userId}`;
    iframe.style.width = '100%';
    iframe.style.height = '600px';
    iframe.style.border = 'none';

    document.getElementById('container').appendChild(iframe);

    // 사용자 ID 전달
    iframe.onload = function() {
        iframe.contentWindow.postMessage({
            type: 'setUserId',
            userId: userId
        }, '*');
    };
}
```

## 🔍 디버깅

### 콘솔 로그

**메인 페이지:**
```
[index.php:229] Student ID: 2
[index.php:238] showPanel called: problem-redefinition
[index.php:272] Panel shown: {...}
[index.php:313] Posted userId to iframe: 2
```

**iframe 내부:**
```
[ui/index.php:139] currentUserId: 2
[ui/index.php:144] Running in iframe: true
[ui/index.php:171] Iframe layout adjusted
[ui/index.php:176] Received postMessage: {type: 'setUserId', userId: 2}
```

### 문제 해결

#### 문제: iframe이 표시되지 않음

**원인:**
- 카드를 클릭하지 않음
- JavaScript 에러

**해결:**
1. 브라우저 콘솔에서 에러 확인
2. `showPanel()` 함수가 정상 호출되었는지 확인
3. `rightPanel.classList.contains('active')` 확인

#### 문제: iframe 내부 레이아웃이 이상함

**원인:**
- iframe 감지 로직 실패
- CSS 충돌

**해결:**
1. 콘솔에서 `isInIframe` 값 확인
2. `in-iframe` 클래스가 body에 추가되었는지 확인
3. 좌측 패널이 숨겨졌는지 확인

#### 문제: currentUserId가 전달되지 않음

**원인:**
- postMessage 실패
- 메시지 리스너 미등록

**해결:**
1. iframe onload 이벤트 확인
2. postMessage 콘솔 로그 확인
3. 메시지 수신 로그 확인

## 📊 성능 최적화

### iframe 리소스 관리

```javascript
function closePanel() {
    // 패널 숨기기
    rightPanel.classList.remove('active');

    // iframe src 초기화 (메모리 절약)
    iframe.src = '';
}
```

### 느린 로딩 처리

```javascript
// iframe 로딩 표시
const loadingIndicator = document.createElement('div');
loadingIndicator.className = 'loading';
loadingIndicator.textContent = '로딩 중...';

iframe.addEventListener('load', function() {
    loadingIndicator.remove();
});
```

## 🔐 보안 고려사항

### 1. postMessage 출처 검증

```javascript
window.addEventListener('message', function(event) {
    // 출처 검증 (프로덕션에서 필수)
    if (event.origin !== 'https://mathking.kr') {
        return;
    }

    if (event.data && event.data.type === 'setUserId') {
        window.currentUserId = event.data.userId;
    }
});
```

### 2. iframe sandbox (선택적)

```html
<iframe
    src="ui/index.php"
    sandbox="allow-same-origin allow-scripts allow-forms">
</iframe>
```

**주의:** `allow-same-origin` 없이는 postMessage가 작동하지 않을 수 있음

## 📝 향후 개선사항

- [ ] iframe 로딩 진행 표시
- [ ] iframe 통신 에러 처리 강화
- [ ] 다중 iframe 지원
- [ ] iframe 크기 자동 조절
- [ ] 출처 검증 강화

---

**Last Updated:** 2025-10-21
**Version:** 2.0
**Author:** Claude Code
