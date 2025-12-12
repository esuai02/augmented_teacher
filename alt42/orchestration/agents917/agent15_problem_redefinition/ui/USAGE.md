# Agent 15 사용 가이드

## 🚀 빠른 시작

### 1단계: 메인 페이지 접속

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/index.php
```

### 2단계: 카드 클릭

좌측 패널에서 "🔄 문제 재정의 & 개선방안" 카드를 클릭하면 우측 패널이 나타납니다.

### 3단계: 문제 재정의 가져오기

우측 패널의 "📊 문제 재정의 가져오기" 버튼을 클릭하면:
1. 자동으로 Step 2~14 데이터 수집
2. GPT API로 문제 재정의 생성
3. 텍스트박스에 결과 표시

### 4단계: 저장

생성된 내용을 수정한 후 "💾 저장" 버튼으로 로컬 스토리지에 저장합니다.

---

## 🧪 테스트 페이지

기능을 테스트하려면 다음 페이지를 사용하세요:

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/test.html
```

### 테스트 항목

1. **파일 존재 확인**: 모든 컴포넌트 파일이 정상적으로 접근 가능한지 확인
2. **JavaScript 로드 확인**: 필요한 함수들이 정상적으로 로드되었는지 확인
3. **API 엔드포인트 확인**: orchestration의 API가 정상 동작하는지 확인
4. **사용자 ID 설정**: currentUserId가 올바르게 설정되었는지 확인
5. **로컬 스토리지 테스트**: 데이터 저장/불러오기 기능 테스트

---

## 📁 파일 구조

```
/ui/
├── index.php                          # 메인 페이지 (좌측 카드 + 우측 패널)
├── problem_redefinition_panel.php     # 우측 패널 UI 컴포넌트
├── problem_redefinition_functions.js  # JavaScript 기능
├── test.html                          # 테스트 페이지
├── README.md                          # 상세 문서
└── USAGE.md                           # 이 사용 가이드
```

---

## 🔧 다른 페이지에 통합하기

### 방법 1: PHP include 사용

```php
<?php
// 페이지 상단에 Moodle 설정 포함
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();
?>

<!-- HTML 본문 -->
<div class="your-page-content">
    <!-- 우측 패널 영역 -->
    <div id="right-panel">
        <?php include 'problem_redefinition_panel.php'; ?>
    </div>
</div>

<!-- JavaScript 포함 -->
<script>
    window.currentUserId = <?php echo $USER->id; ?>;
</script>
<script src="problem_redefinition_functions.js"></script>
```

### 방법 2: AJAX 로드

```javascript
// 동적으로 컴포넌트 로드
fetch('problem_redefinition_panel.php')
    .then(response => response.text())
    .then(html => {
        document.getElementById('right-panel').innerHTML = html;

        // JavaScript 파일 로드
        const script = document.createElement('script');
        script.src = 'problem_redefinition_functions.js';
        document.head.appendChild(script);
    });
```

---

## 🎯 주요 함수

### JavaScript 함수

```javascript
// 문제 재정의 가져오기
agent15FetchProblemRedefinition()

// 문제 재정의 저장
agent15SaveProblemRedefinition()

// 저장된 내용 불러오기
agent15LoadSavedRedefinition()

// 초기화
initializeAgent15ProblemRedefinition()
```

### 사용 예제

```javascript
// 수동으로 문제 재정의 가져오기
document.getElementById('custom-button').addEventListener('click', function() {
    agent15FetchProblemRedefinition();
});

// 페이지 로드 시 저장된 내용 불러오기
window.addEventListener('load', function() {
    agent15LoadSavedRedefinition();
});
```

---

## 🔍 디버깅

### 브라우저 콘솔 로그

```javascript
// 초기화 로그
Agent 15: 문제 재정의 패널 초기화 시작...
Agent 15: 초기화 완료

// 데이터 가져오기 로그
agent15FetchProblemRedefinition 시작... (userId: 2)
📊 Agent 15: 데이터 수집 시작...
✅ 데이터 수집 완료
✅ GPT 분석 완료
✅ agent15-problem-redefinition-text에 설정 완료

// 저장 로그
agent15SaveProblemRedefinition 시작... (userId: 2)
✅ 로컬 스토리지 저장 완료
```

### 에러 메시지

모든 에러는 파일명과 라인 번호를 포함합니다:

```
❌ 오류: 데이터 수집 실패 (file: problem_redefinition_functions.js, line: 48)
```

---

## 💾 데이터 저장

### 로컬 스토리지 키

```javascript
agent15_redefinition_{userId}
```

예시:
- 사용자 ID 2: `agent15_redefinition_2`
- 사용자 ID 123: `agent15_redefinition_123`

### 저장 형식

```json
{
    "userId": 2,
    "content": "문제 재정의 내용...",
    "timestamp": "2025-10-21T23:51:00.000Z"
}
```

### 데이터 확인

브라우저 콘솔에서:

```javascript
// 저장된 데이터 확인
const userId = 2;
const data = localStorage.getItem(`agent15_redefinition_${userId}`);
console.log(JSON.parse(data));

// 데이터 삭제
localStorage.removeItem(`agent15_redefinition_${userId}`);
```

---

## 🌐 API 엔드포인트

`orchestration` 폴더의 API를 사용합니다:

### 1. 데이터 수집 API

```
POST /moodle/local/augmented_teacher/alt42/orchestration_hs2/api/collect_workflow_data.php
```

**Request:**
```json
{
    "userId": 2
}
```

**Response:**
```json
{
    "step2": {...},
    "step3": {...},
    "step4": {...},
    ...
}
```

### 2. GPT API

```
POST /moodle/local/augmented_teacher/alt42/orchestration_hs2/api/problem_redefinition_api.php
```

**Request:**
```json
{
    "userId": 2,
    "data": {...},
    "guidanceMode": null
}
```

**Response:**
```json
{
    "success": true,
    "redefinition": "문제 재정의 내용..."
}
```

---

## ⚠️ 주의사항

1. **사용자 ID 필수**: `window.currentUserId` 반드시 설정
2. **Moodle 로그인**: PHP 페이지는 Moodle 로그인 필요
3. **API 의존성**: orchestration_hs2의 API가 정상 동작해야 함
4. **브라우저 호환성**: LocalStorage 지원 브라우저 필요

---

## 🐛 문제 해결

### 문제: "데이터 수집 실패" 에러

**원인:**
- API 엔드포인트 접근 불가
- 사용자 ID 미설정

**해결:**
1. `window.currentUserId` 확인
2. API 엔드포인트 URL 확인
3. 네트워크 탭에서 요청 상태 확인

### 문제: JavaScript 함수를 찾을 수 없음

**원인:**
- JavaScript 파일 로드 실패
- 경로 오류

**해결:**
1. 브라우저 콘솔에서 404 에러 확인
2. JavaScript 파일 경로 확인
3. 파일 권한 확인 (644)

### 문제: 우측 패널이 나타나지 않음

**원인:**
- CSS display 속성 문제
- showRightPanel() 함수 오류

**해결:**
1. 브라우저 개발자 도구로 요소 확인
2. 콘솔에서 에러 메시지 확인
3. #rightPanel 요소 존재 여부 확인

---

## 📞 추가 지원

문제가 지속되면:
1. 브라우저 콘솔 로그 복사
2. 네트워크 탭의 실패한 요청 확인
3. test.html 페이지로 각 기능 개별 테스트

---

**Last Updated:** 2025-10-21
**Version:** 1.0
**Author:** Claude Code
