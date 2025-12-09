# 빠른 시작 가이드 - 온톨로지 추론 엔진 테스트

온톨로지 추론 엔진 E2E 테스트를 실행하는 두 가지 방법을 제공합니다.

---

## 🚀 방법 1: Public 버전 테스트 (권장 - 간단함)

**로그인 없이** 바로 테스트할 수 있는 방법입니다.

### 1단계: 의존성 설치
```bash
npm install
npx playwright install chromium
```

### 2단계: 테스트 실행
```bash
npm run test:public
```

### 특징
- ✅ 로그인 불필요
- ✅ 즉시 실행 가능
- ✅ 간단한 설정
- ⚠️ Public 버전 PHP 파일 필요 (`test_inference_public.php`)

---

## 🌐 방법 2: 기존 크롬 브라우저 사용

**이미 로그인되어 있는 크롬 브라우저**를 활용하는 방법입니다.

### 1단계: 크롬을 디버그 모드로 실행

**Windows:**
```bash
"C:\Program Files\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222
```

**Mac:**
```bash
/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --remote-debugging-port=9222
```

**Linux:**
```bash
google-chrome --remote-debugging-port=9222
```

### 2단계: 크롬에서 Moodle 로그인
1. 방금 열린 크롬 창에서 https://mathking.kr/moodle 접속
2. 로그인

### 3단계: 테스트 실행
```bash
npm run test:chrome
```

### 특징
- ✅ 실제 Moodle 세션 사용
- ✅ 로그인 상태 유지
- ⚠️ 크롬을 특수 모드로 실행 필요
- ⚠️ 매번 크롬 재시작 필요

---

## 📊 테스트 결과 확인

테스트 실행 후 결과를 확인하려면:

```bash
npm run report
```

HTML 리포트가 자동으로 브라우저에서 열립니다.

### 스크린샷 위치
```
test-results/screenshots/
├── 01-page-load.png
├── 02-inference-execution.png
├── 03-parsed-results.png
├── 04-validation.png
├── 05-error-handling.png
├── 06-consistency-warning.png
└── 07-timeout-handling.png
```

---

## 🧪 테스트 케이스 목록

| ID | 테스트 내용 | 예상 시간 |
|----|------------|----------|
| TC-01 | 페이지 로드 및 UI 확인 | ~2초 |
| TC-02 | 추론 엔진 실행 | ~15초 |
| TC-03 | 결과 파싱 및 시각화 | ~12초 |
| TC-04 | 일관성 검증 | ~8초 |
| TC-05 | 오류 처리 | ~3초 |
| TC-06 | 경고 메시지 | ~7초 |
| TC-07 | 타임아웃 처리 | ~15초 |

**총 예상 시간**: ~62초

---

## ❓ 문제 해결

### "크롬 연결 실패" 오류
```
Error: connect ECONNREFUSED 127.0.0.1:9222
```

**해결방법**: 크롬을 디버그 모드로 실행했는지 확인
```bash
chrome.exe --remote-debugging-port=9222
```

### "로그인 페이지로 리다이렉트" 오류

**해결방법**: Public 버전 테스트 사용
```bash
npm run test:public
```

### 타임아웃 오류

**해결방법**: `playwright.config.js`에서 타임아웃 증가
```javascript
timeout: 120000  // 2분으로 증가
```

---

## 📚 더 자세한 정보

- [전체 테스트 가이드](README.md)
- [테스트 설계 문서](../docs/plans/2025-11-01-ontology-web-testing-design.md)

---

**버전**: 1.0
**마지막 업데이트**: 2025-11-01
