# 온톨로지 추론 엔진 E2E 테스트 실행 가이드

**작성일**: 2025-11-01
**대상**: mathking.kr 서버 실제 테스트

---

## 📋 목차

1. [개요](#개요)
2. [Moodle 로그인 문제 해결](#moodle-로그인-문제-해결)
3. [테스트 실행 방법](#테스트-실행-방법)
4. [결과 확인](#결과-확인)
5. [트러블슈팅](#트러블슈팅)

---

## 개요

온톨로지 추론 엔진 웹 인터페이스(`test_inference.php`)를 자동으로 테스트하기 위한 Playwright E2E 테스트가 구성되어 있습니다.

**현재 상태**: ✅ 테스트 파일 작성 완료, ⚠️ Moodle 로그인 필요

---

## Moodle 로그인 문제 해결

`test_inference.php`는 `require_login()`을 사용하므로, 테스트 실행 전에 Moodle 로그인이 필요합니다.

### 방법 1: 로그인 스크립트 사용 (권장)

**Step 1**: 로그인 헬퍼 스크립트 생성

파일: `tests/helpers/moodle-login.js`

```javascript
/**
 * Moodle 로그인 헬퍼 함수
 */

async function loginToMoodle(page, username, password) {
  // Moodle 로그인 페이지로 이동
  await page.goto('https://mathking.kr/moodle/login/index.php');

  // 로그인 폼이 나타날 때까지 대기
  await page.waitForSelector('#username', { timeout: 10000 });

  // 사용자명 입력
  await page.fill('#username', username);

  // 비밀번호 입력
  await page.fill('#password', password);

  // 로그인 버튼 클릭
  await page.click('#loginbtn');

  // 로그인 완료 대기 (Dashboard로 리다이렉트)
  await page.waitForURL('**/moodle/**', { timeout: 15000 });

  console.log('✅ Moodle 로그인 성공');
}

module.exports = { loginToMoodle };
```

**Step 2**: 테스트 파일에 로그인 추가

`tests/e2e/ontology_inference_web.test.js`의 `beforeEach` 수정:

```javascript
const { loginToMoodle } = require('../helpers/moodle-login');

test.beforeEach(async ({ page }) => {
  // Moodle 로그인
  await loginToMoodle(page, 'your_username', 'your_password');

  // 테스트 페이지로 이동
  await page.goto(BASE_URL);
});
```

**Step 3**: 환경 변수로 로그인 정보 관리 (보안)

파일: `.env` (gitignore에 추가)

```bash
MOODLE_USERNAME=your_username
MOODLE_PASSWORD=your_password
```

설치:
```bash
npm install -D dotenv
```

테스트 파일:
```javascript
require('dotenv').config();

const { loginToMoodle } = require('../helpers/moodle-login');

test.beforeEach(async ({ page }) => {
  await loginToMoodle(
    page,
    process.env.MOODLE_USERNAME,
    process.env.MOODLE_PASSWORD
  );
  await page.goto(BASE_URL);
});
```

---

### 방법 2: 세션 저장 사용

**Step 1**: 수동 로그인 후 세션 저장

파일: `scripts/save-moodle-session.js`

```javascript
const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  // Moodle 로그인 페이지로 이동
  await page.goto('https://mathking.kr/moodle/login/index.php');

  console.log('\n==========================================');
  console.log('브라우저 창에서 Moodle에 로그인하세요.');
  console.log('로그인 완료 후 이 터미널에서 Enter를 누르세요...');
  console.log('==========================================\n');

  // 사용자가 Enter를 누를 때까지 대기
  await new Promise(resolve => {
    process.stdin.once('data', resolve);
  });

  // 세션 저장
  await context.storageState({ path: 'moodle-auth.json' });

  console.log('\n✅ Moodle 세션 저장 완료: moodle-auth.json');
  console.log('이제 테스트를 실행할 수 있습니다.\n');

  await browser.close();
})();
```

**Step 2**: 세션 저장 스크립트 실행

```bash
node scripts/save-moodle-session.js
```

**Step 3**: Playwright 설정에 세션 사용

`playwright.config.js`에 추가:

```javascript
use: {
  // 저장된 세션 사용
  storageState: 'moodle-auth.json',

  // ... 기존 설정
},
```

**Step 4**: 테스트 실행

```bash
npm test
```

---

### 방법 3: CI/CD 환경에서 사용 (GitHub Actions 등)

`.github/workflows/test.yml`:

```yaml
name: E2E Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Install dependencies
        run: |
          cd alt42/ontology_brain
          npm install

      - name: Install Playwright browsers
        run: |
          cd alt42/ontology_brain
          npx playwright install chromium

      - name: Run tests
        env:
          MOODLE_USERNAME: ${{ secrets.MOODLE_USERNAME }}
          MOODLE_PASSWORD: ${{ secrets.MOODLE_PASSWORD }}
        run: |
          cd alt42/ontology_brain
          npm test

      - name: Upload test results
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-results
          path: alt42/ontology_brain/test-results/
```

---

## 테스트 실행 방법

### 전제 조건

```bash
# 1. 의존성이 설치되어 있어야 함
npm install

# 2. Chromium 브라우저 설치
npx playwright install chromium

# 3. Moodle 로그인 설정 완료 (위의 방법 1 또는 2)
```

### 테스트 명령어

```bash
# 모든 테스트 실행
npm test

# UI 모드로 실행 (디버깅)
npm run test:ui

# 헤드풀 모드 (브라우저 창 보기)
npm run test:headed

# 특정 테스트만 실행
npx playwright test -g "TC-01"

# 디버그 모드
npm run test:debug
```

---

## 결과 확인

### 콘솔 출력

```
Running 7 tests using 1 worker

  ✓ TC-01: 페이지가 정상적으로 로드되고 모든 섹션이 표시됨 (2.3s)
  ✓ TC-02: 추론 엔진 실행 버튼 클릭 시 정상 동작 (14.8s)
  ✓ TC-03: 파싱된 결과가 올바르게 표시됨 (11.2s)
  ✓ TC-04: 일관성 검증 버튼이 정상 동작 (7.9s)
  ✓ TC-05: Python 스크립트 실행 오류 적절히 처리 (3.1s)
  ✓ TC-06: 일관성 검증 경고 메시지 확인 (6.5s)
  ✓ TC-07: 네트워크 타임아웃 적절히 처리 (15.2s)

  7 passed (61s)
```

### HTML 리포트

```bash
# HTML 리포트 열기
npm run report

# 또는
npx playwright show-report test-results/html-report
```

### 스크린샷 확인

```bash
# 실패한 테스트의 스크린샷
ls test-results/screenshots/

# 예: 01-page-load.png, 02-inference-execution.png
```

---

## 트러블슈팅

### 문제 1: "로그인 페이지로 리다이렉트"

**증상**: 테스트가 항상 로그인 페이지에서 시작됨

**원인**: Moodle 로그인이 설정되지 않음

**해결**:
1. 위의 "방법 1" 또는 "방법 2" 사용
2. `.env` 파일에 올바른 로그인 정보 입력
3. `moodle-auth.json` 파일이 유효한지 확인

### 문제 2: "세션 만료"

**증상**: 처음에는 작동하다가 나중에 로그인 페이지로 돌아감

**원인**: Moodle 세션이 만료됨

**해결**:
```bash
# 세션 재저장
node scripts/save-moodle-session.js

# 또는 로그인 헬퍼 사용 (방법 1)
```

### 문제 3: "타임아웃 오류"

**증상**: `Test timeout of 60000ms exceeded`

**원인**: Python 스크립트 실행 시간이 너무 오래 걸림

**해결**:
`playwright.config.js`에서 타임아웃 증가:
```javascript
timeout: 120000,  // 120초로 증가
```

### 문제 4: "element(s) not found"

**증상**: 특정 요소를 찾을 수 없음

**해결**:
1. 스크린샷 확인: `test-results/*/test-failed-1.png`
2. 실제 페이지 HTML 구조 확인
3. 테스트 파일의 selector 수정

---

## 권장 워크플로우

### 개발 시

1. **로그인 설정 (한 번만)**:
   ```bash
   node scripts/save-moodle-session.js
   ```

2. **테스트 작성**:
   - `tests/e2e/` 폴더에 새 테스트 추가

3. **UI 모드로 디버깅**:
   ```bash
   npm run test:ui
   ```

4. **전체 테스트 실행**:
   ```bash
   npm test
   ```

5. **결과 확인**:
   ```bash
   npm run report
   ```

### CI/CD 환경

1. GitHub Secrets에 로그인 정보 저장
2. Workflow에서 환경 변수 사용
3. 테스트 결과 아티팩트로 저장

---

## 빠른 시작 체크리스트

```bash
# 1. 의존성 설치
npm install
npx playwright install chromium

# 2. 환경 변수 설정
echo "MOODLE_USERNAME=your_username" > .env
echo "MOODLE_PASSWORD=your_password" >> .env

# 3. 로그인 헬퍼 생성
mkdir -p tests/helpers
# (위의 moodle-login.js 파일 생성)

# 4. 테스트 파일 수정
# (beforeEach에 loginToMoodle 추가)

# 5. 테스트 실행
npm test

# 6. 결과 확인
npm run report
```

---

## 참고 자료

- [Playwright 공식 문서](https://playwright.dev/)
- [Playwright Authentication](https://playwright.dev/docs/auth)
- [테스트 설계 문서](./plans/2025-11-01-ontology-web-testing-design.md)
- [온톨로지 추론 엔진 README](../README.md)

---

**문서 버전**: 1.0
**최종 업데이트**: 2025-11-01
**작성자**: Mathking Development Team
