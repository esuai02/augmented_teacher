# 🐛 Q&A 시스템 디버깅 가이드

## 문제 증상
- 🔍 버튼을 클릭해도 항상 1단계 질문만 표시됨
- 다른 단계(2, 3, 4...)로 이동해도 질문이 변경되지 않음
- LaTeX 수식이 렌더링되지 않고 텍스트로 표시됨 (예: `\(x^2\)`)
- 질문/답변에 수식이 깨져서 표시됨

## 진단 절차

### 1단계: 브라우저 콘솔 확인
1. https://mathking.kr/moodle/local/augmented_teacher/books/mynote2.php?dmn=&cid=106&nch=1&cmid=87711&quizid=&page=1&studentid=1903 접속
2. **F12** 키를 눌러 개발자 도구 열기
3. **Console** 탭 선택
4. 단계를 변경 (▶ 버튼 클릭)
5. 🔍 버튼 클릭
6. 콘솔에서 다음 로그 확인:

```javascript
[Replay Click] File: mynote2.php, Line: 1391 {
  contentsId: 106,
  currentSection: 0,    // ← 이 값이 변경되는지 확인!
  nstep: 1,             // ← currentSection + 1
  sectionText: "...",   // ← 해당 구간의 자막 텍스트
  textSectionsDataLength: 4
}

[Replay Click] File: mynote2.php, Line: 1403, Full URL: https://...nstep=1...
```

**예상 결과**:
- 1단계 클릭 시: `currentSection: 0, nstep: 1`
- 2단계로 이동 후 클릭 시: `currentSection: 1, nstep: 2`
- 3단계로 이동 후 클릭 시: `currentSection: 2, nstep: 3`

**만약 항상 `currentSection: 0`이면**:
→ `currentSection` 변수가 업데이트되지 않는 문제 (mynote2.php의 `switchToSection` 함수 확인 필요)

### 2단계: drillingmath.php 로그 확인
오버레이가 열리면 콘솔에서:

```javascript
[drillingmath.php:loadQuestions] File: drillingmath.php, Line: 379, Parameters: {
  contentsid: "106",
  contentstype: "1",
  nstep: 1,             // ← URL에서 받은 nstep
  subtitle: "...",      // ← URL에서 받은 자막 텍스트
  thinkingContentLength: 123,
  thinkingContentPreview: "...",
  urlParams: "cid=106&ctype=1&nstep=1&section=0&subtitle=..."
}
```

**예상 결과**:
- `nstep` 값이 클릭한 단계와 일치해야 함
- `subtitle` 텍스트가 해당 구간의 자막과 일치해야 함

**만약 nstep이 항상 1이면**:
→ URL 생성 문제 또는 iframe이 이전 URL을 캐시하고 있음

### 3단계: DB 데이터 확인
진단 도구 실행:

```
https://mathking.kr/moodle/local/augmented_teacher/books/check_db_nstep.php?cid=106&ctype=1&nstep=1
https://mathking.kr/moodle/local/augmented_teacher/books/check_db_nstep.php?cid=106&ctype=1&nstep=2
https://mathking.kr/moodle/local/augmented_teacher/books/check_db_nstep.php?cid=106&ctype=1&nstep=3
```

**확인 사항**:
- 각 nstep에 대한 레코드가 존재하는가?
- qstn0 필드에 풀이 단계 내용이 있는가?
- qstn1-3, ans1-3 필드가 EMPTY인가 아니면 데이터가 있는가?

**예상 결과**:
```
✅ 레코드 발견 (id=123)
qstn0: 있음 (풀이 단계 내용)
qstn1: EMPTY (또는 있음 - 이전에 생성된 경우)
ans1: EMPTY (또는 있음)
...
```

### 4단계: Q&A 생성 API 로그 확인
DB에 Q&A가 없어서 AI로 생성하는 경우:

```javascript
[drillingmath.php:loadQuestions] No cached Q&A, generating with AI...
[drillingmath.php:loadQuestions] Request body: {
  nodeContent: "...",
  nodeType: "step",
  fullContext: "...",
  contentsid: "106",
  contentstype: "1",
  nstep: 1,           // ← 이 값이 올바른지 확인
  nodeIndex: 0
}

[drillingmath.php:loadQuestions] API Response: {
  success: true,
  qa_pairs: [...]
}
```

## 가능한 문제 시나리오

### 시나리오 A: currentSection이 업데이트되지 않음
**증상**: 콘솔에서 항상 `currentSection: 0`
**원인**: `switchToSection()` 함수가 호출되지 않거나 `currentSection` 변수가 업데이트되지 않음
**해결**: mynote2.php의 ▶/◀ 버튼 이벤트 확인

### 시나리오 B: iframe이 URL을 캐시함
**증상**: URL은 올바르게 생성되지만 iframe이 이전 URL을 로드
**원인**: 브라우저가 iframe을 캐시
**해결**: iframe에 `src = "about:blank"` 설정 후 새 URL 설정

### 시나리오 C: DB에 해당 nstep 레코드가 없음
**증상**: "풀이 단계 내용이 없습니다" 메시지 표시
**원인**: DB에 해당 구간의 qstn0 데이터가 없음
**해결**: drillingmath.php 또는 다른 도구로 먼저 풀이 단계 생성

### 시나리오 D: Q&A가 생성되지만 저장되지 않음
**증상**: 매번 같은 질문이 다시 생성됨 (캐시되지 않음)
**원인**: generate_questions_with_answers.php의 DB 저장 로직 실패
**해결**: 서버 로그에서 DB 저장 오류 확인

### 시나리오 E: LaTeX 수식이 렌더링되지 않음
**증상**: `\(x^2\)`, `$x^2$`, `\frac{a}{b}` 같은 수식이 텍스트로 표시됨
**원인**:
1. MathJax가 로드되지 않음
2. HTML 삽입 시 이스케이프 처리로 백슬래시가 깨짐
3. MathJax 렌더링 타이밍 문제
4. **MathJax 구분자(delimiter) 설정 누락**: `$...$` 형식 미지원

**해결**:
1. 브라우저 콘솔에서 `typeof MathJax` 확인 → `undefined`면 MathJax 미로드
2. MathJax 설정에서 `$...$` 구분자 확인 (drillingmath.php line 340):
   ```javascript
   inlineMath: [['$', '$'], ['\\(', '\\)']],
   displayMath: [['$$', '$$'], ['\\[', '\\]']]
   ```
3. 콘솔에서 MathJax 설정 로그 확인:
   ```javascript
   [MathJax] Configuration loaded with $ delimiters enabled
   ```
4. drillingmath.php에서 LaTeX 처리 확인:
   - ❌ 잘못된 방법: `htmlspecialchars($content)` → 백슬래시 이스케이프
   - ❌ 잘못된 방법: `addslashes($content)` → 백슬래시 중복
   - ✅ 올바른 방법: `json_encode($content, JSON_UNESCAPED_UNICODE)` (PHP→JS 전달 시)
   - ✅ 올바른 방법: `preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content)` (HTML 출력 시)
5. 콘솔에서 MathJax 렌더링 로그 확인:
   ```javascript
   [drillingmath.php] File: drillingmath.php, Line: 497, Cached Q&A MathJax rendered successfully
   [drillingmath.php] File: drillingmath.php, Line: 557, AI-generated Q&A MathJax rendered successfully
   [drillingmath.php] File: drillingmath.php, Line: 615, Question and Answer MathJax rendered for question 0
   ```

### 시나리오 F: 질문/답변에 수식이 깨져서 표시됨
**증상**: 일부 수식만 렌더링되거나 백슬래시가 중복됨 (`\\(x^2\\)`), 또는 `$x` 부분이 사라짐
**원인**:
1. JavaScript 템플릿 리터럴에서 `$` 기호가 변수로 해석됨
2. 이스케이프 처리로 백슬래시가 중복됨

**해결**: drillingmath.php의 `createQuestionCard` 함수 확인 (line 590-637)
```javascript
// ❌ 잘못된 방법 1: 템플릿 리터럴 사용 ($ 기호 충돌)
return `<div class="question-text">${safeQuestion}</div>`;
// $x^2$ → JavaScript가 $x를 변수로 해석

// ❌ 잘못된 방법 2: 백슬래시와 달러 기호 이스케이프
const escapedAnswer = answer.replace(/`/g, '\\`').replace(/\$/g, '\\$');

// ✅ 올바른 방법: DOM API 사용 ($ 기호 충돌 없음)
const questionText = document.createElement('div');
questionText.className = 'question-text';
questionText.innerHTML = safeQuestion;  // $ 기호 안전하게 보존
```

**추가 확인사항**:
- AI 프롬프트에서 `$...$` 형식 명시 (generate_questions_with_answers.php line 82-85)
- 브라우저 콘솔에서 `$x` 같은 부분이 누락되지 않았는지 확인

## 해결 방법

### 즉시 테스트 가능한 해결책

#### 1. iframe 캐시 방지
mynote2.php에서 iframe을 완전히 재로드:

```javascript
// 현재 코드 (라인 1414-1416):
iframe.src = url;
overlay.classList.add("active");

// 개선된 코드:
iframe.src = "about:blank";  // 먼저 비우기
setTimeout(() => {
  iframe.src = url;  // 새 URL 로드
  overlay.classList.add("active");
}, 100);
```

#### 2. URL에 타임스탬프 추가 (캐시 무력화)
```javascript
const url = "https://...&subtitle=" + encodeURIComponent(sectionText) +
            "&_t=" + Date.now();  // 캐시 방지
```

#### 3. 로컬 변수 대신 window.currentSection 사용
mynote2.php에서 `currentSection`이 전역으로 공유되는지 확인:

```javascript
// Line 1094
let currentSection = window.currentSection;  // ← 로컬 변수

// Line 1144-1145 (switchToSection 함수 내)
currentSection = newSection;
window.currentSection = currentSection;  // ← 전역으로 동기화
```

#### 4. 클릭 이벤트에서 직접 section 가져오기
만약 `currentSection`이 신뢰할 수 없다면:

```javascript
// 현재 재생 중인 오디오에서 직접 확인
const audioSrc = audioPlayer2.src;
const currentIndex = sectionFiles.findIndex(file => audioSrc.includes(file));
const actualSection = currentIndex >= 0 ? currentIndex : currentSection;

console.log("Actual playing section:", actualSection);
```

## 다음 단계

1. **위 1단계 진단부터 순서대로 실행**
2. 각 단계의 콘솔 로그를 캡처하여 공유
3. 어느 단계에서 문제가 발생하는지 확인
4. 해당 문제에 맞는 해결책 적용

## 추가 디버깅 도구

### 실시간 변수 모니터링
브라우저 콘솔에서 실행:

```javascript
// currentSection 변화 감지
let _currentSection = window.currentSection;
Object.defineProperty(window, 'currentSection', {
  get: () => _currentSection,
  set: (val) => {
    console.log('🔄 currentSection changed:', _currentSection, '→', val);
    _currentSection = val;
  }
});

// 구간 전환 감지
const originalSwitch = switchToSection;
window.switchToSection = function(newSection) {
  console.log('📍 switchToSection called:', newSection);
  return originalSwitch(newSection);
};
```

이렇게 하면 `currentSection`이 언제 어떻게 변경되는지 실시간으로 확인할 수 있습니다.
