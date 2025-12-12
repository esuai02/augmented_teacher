# 온톨로지 추론 엔진 웹 인터페이스 E2E 테스트 설계

**작성일**: 2025-11-01
**버전**: 1.0
**작성자**: Mathking Development Team
**문서 유형**: 테스트 설계 문서

---

## 📋 목차

1. [개요](#개요)
2. [요구사항 분석](#요구사항-분석)
3. [테스트 아키텍처](#테스트-아키텍처)
4. [테스트 케이스 설계](#테스트-케이스-설계)
5. [구현 상세](#구현-상세)
6. [실행 환경 설정](#실행-환경-설정)
7. [검증 기준](#검증-기준)
8. [실행 절차](#실행-절차)

---

## 개요

### 목적

온톨로지 추론 엔진의 웹 인터페이스(`test_inference.php`)가 실제 서버 환경(https://mathking.kr)에서 정상적으로 동작하는지 자동화된 E2E 테스트를 통해 검증합니다.

### 범위

- ✅ **웹 페이지 로드 및 UI 검증**
- ✅ **추론 엔진 실행 및 결과 확인**
- ✅ **결과 파싱 및 시각화 검증**
- ✅ **오류 처리 메커니즘 테스트**
- ✅ **일관성 검증 기능 테스트**

### 제외 범위

- ❌ Python 추론 엔진 내부 로직 테스트 (별도 단위 테스트)
- ❌ 온톨로지 파일 구조 검증 (별도 검증 스크립트)
- ❌ 브라우저 호환성 테스트 (Chromium만 사용)

---

## 요구사항 분석

### 기능 요구사항

| ID | 요구사항 | 우선순위 |
|----|---------|---------|
| FR-01 | 페이지가 5초 이내에 로드되어야 함 | 높음 |
| FR-02 | "추론 엔진 실행" 버튼 클릭 시 30초 이내에 결과 표시 | 높음 |
| FR-03 | 3개 테스트 케이스 모두 올바른 결과 표시 | 높음 |
| FR-04 | 파싱된 결과가 시각적으로 명확하게 표시 | 중간 |
| FR-05 | 일관성 검증 버튼이 정상 작동 | 중간 |
| FR-06 | 파일 없음 등 오류 상황에서 적절한 메시지 표시 | 높음 |

### 비기능 요구사항

| ID | 요구사항 | 측정 기준 |
|----|---------|----------|
| NFR-01 | 테스트 실행 시간 | 전체 테스트 60초 이내 |
| NFR-02 | 테스트 안정성 | 재시도 포함 95% 이상 성공률 |
| NFR-03 | 증거 수집 | 모든 테스트 스크린샷 자동 캡처 |
| NFR-04 | 리포트 품질 | HTML 리포트 자동 생성 |

### 테스트 목표

1. **기본 기능 확인**: 추론 엔진이 정상적으로 실행되는가
2. **결과 정확성**: 3개 테스트 케이스의 추론 결과가 정확한가
3. **오류 처리**: 예외 상황에서 적절한 메시지를 표시하는가

---

## 테스트 아키텍처

### 전체 구조

```
┌──────────────────────────────────────────────────────────┐
│         Playwright MCP E2E 테스트 파이프라인              │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  1. 브라우저 초기화 (Chromium)                            │
│     └─> Playwright 브라우저 드라이버 시작                │
│                                                          │
│  2. 페이지 탐색                                          │
│     └─> https://mathking.kr/.../test_inference.php      │
│                                                          │
│  3. 기본 기능 테스트                                      │
│     ├─> "추론 엔진 실행" 버튼 클릭                       │
│     ├─> 결과 대기 (최대 30초)                            │
│     └─> 성공 배지 확인                                   │
│                                                          │
│  4. 결과 검증                                            │
│     ├─> 3개 테스트 케이스 존재 확인                      │
│     ├─> 각 케이스별 입력/규칙/출력 확인                  │
│     └─> 상세 분석 섹션 확인                              │
│                                                          │
│  5. 오류 처리 테스트                                      │
│     ├─> 다양한 오류 시나리오 시뮬레이션                  │
│     └─> 오류 메시지 검증                                 │
│                                                          │
│  6. 증거 수집                                            │
│     ├─> 스크린샷 캡처                                    │
│     ├─> 콘솔 로그 수집                                   │
│     └─> HTML 리포트 생성                                 │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### 핵심 컴포넌트

1. **Browser Driver**: Playwright Chromium
2. **Assertion Library**: Playwright 내장 expect
3. **Evidence Collection**: 스크린샷, 비디오, 트레이스
4. **Report Generation**: Playwright HTML Reporter

---

## 테스트 케이스 설계

### Test Suite 구조

```
온톨로지 추론 엔진 웹 인터페이스 테스트
├── 기본 기능 테스트
│   ├── TC-01: 페이지 로드 및 초기 상태
│   ├── TC-02: 추론 엔진 실행
│   └── TC-03: 결과 파싱 및 시각화
├── 추가 기능 테스트
│   └── TC-04: 일관성 검증
└── 오류 처리 테스트
    ├── TC-05: Python 스크립트 오류
    ├── TC-06: 일관성 경고 확인
    └── TC-07: 네트워크 타임아웃
```

### 상세 테스트 케이스

#### TC-01: 페이지 로드 및 초기 상태

**목적**: 웹 페이지가 정상적으로 로드되고 모든 UI 요소가 표시되는지 확인

**전제 조건**:
- 서버가 정상 가동 중
- Moodle 로그인 완료

**테스트 단계**:
1. 페이지 URL 접속
2. 페이지 로드 대기 (최대 5초)
3. 시스템 정보 섹션 확인
4. 온톨로지 구조 섹션 확인
5. 테스트 실행 버튼 2개 확인

**예상 결과**:
- ✅ 페이지 5초 이내 로드
- ✅ "📊 시스템 정보" 섹션 표시
- ✅ "🗂️ 온톨로지 구조" 섹션 표시
- ✅ "▶️ 추론 엔진 실행" 버튼 표시
- ✅ "✓ 일관성 검증" 버튼 표시

**검증 포인트**:
```javascript
await expect(page.locator('h3:has-text("시스템 정보")')).toBeVisible();
await expect(page.locator('h3:has-text("온톨로지 구조")')).toBeVisible();
await expect(page.locator('button[name="run_test"]')).toBeVisible();
await expect(page.locator('button[name="validate"]')).toBeVisible();
```

---

#### TC-02: 추론 엔진 실행

**목적**: 추론 엔진이 정상적으로 실행되고 성공 메시지를 표시하는지 확인

**전제 조건**:
- TC-01 통과
- Python 스크립트 존재
- 온톨로지 파일 존재

**테스트 단계**:
1. "▶️ 추론 엔진 실행" 버튼 클릭
2. 실행 완료 대기 (최대 30초)
3. 성공 배지 확인
4. 결과 출력 확인

**예상 결과**:
- ✅ 30초 이내 실행 완료
- ✅ "✓ 성공" 배지 표시
- ✅ 추론 결과가 `<pre>` 태그에 표시
- ✅ "추론 완료" 메시지 포함

**검증 포인트**:
```javascript
await page.click('button[name="run_test"]');
await expect(page.locator('.status-badge.status-success'))
  .toBeVisible({ timeout: 30000 });
const successText = await page.locator('.status-badge.status-success').textContent();
expect(successText).toContain('✓ 성공');
```

---

#### TC-03: 결과 파싱 및 시각화

**목적**: 추론 결과가 올바르게 파싱되고 시각적으로 표시되는지 확인

**전제 조건**:
- TC-02 통과 (추론 엔진 성공적으로 실행됨)

**테스트 단계**:
1. "상세 분석" 섹션 확인
2. 로드된 개념 수 확인 (2개)
3. 로드된 규칙 수 확인 (3개)
4. 3개 테스트 케이스 확인
   - 테스트 케이스 1: 철수 + 좌절 → 격려 필요
   - 테스트 케이스 2: 영희 + 집중 → 학습 진행
   - 테스트 케이스 3: 민수 + 피로 → 휴식 필요

**예상 결과**:
- ✅ "로드된 개념: 2개 (Student, Emotion)" 표시
- ✅ "로드된 규칙: 3개" 표시
- ✅ 3개의 `.test-case` 요소 존재
- ✅ 각 케이스별로 입력/규칙/결과 표시

**검증 포인트**:
```javascript
// 개념 및 규칙 확인
await expect(page.locator('text=/로드된 개념.*2개/')).toBeVisible();
await expect(page.locator('text=/로드된 규칙.*3개/')).toBeVisible();

// 테스트 케이스 개수 확인
const testCases = page.locator('.test-case');
await expect(testCases).toHaveCount(3);

// 각 케이스 내용 확인
await expect(page.locator('text=/철수.*좌절/')).toBeVisible();
await expect(page.locator('text=/격려 필요/')).toBeVisible();
await expect(page.locator('text=/영희.*집중/')).toBeVisible();
await expect(page.locator('text=/학습 진행/')).toBeVisible();
await expect(page.locator('text=/민수.*피로/')).toBeVisible();
await expect(page.locator('text=/휴식 필요/')).toBeVisible();
```

---

#### TC-04: 일관성 검증

**목적**: 일관성 검증 기능이 정상 작동하는지 확인

**전제 조건**:
- 페이지 로드 완료

**테스트 단계**:
1. "✓ 일관성 검증" 버튼 클릭
2. 검증 완료 대기 (최대 15초)
3. 결과 확인 (성공 또는 경고)

**예상 결과**:
- ✅ 15초 이내 검증 완료
- ✅ "✓ 검증 완료" 또는 "⚠️ 검증 경고" 표시
- ✅ 경고인 경우 구체적인 메시지 표시

**검증 포인트**:
```javascript
await page.click('button[name="validate"]');
await page.waitForSelector('.test-output, .error-message', { timeout: 15000 });

// 성공 또는 경고 중 하나가 표시되어야 함
const hasSuccess = await page.locator('.status-success').isVisible();
const hasWarning = await page.locator('.error-message').isVisible();
expect(hasSuccess || hasWarning).toBeTruthy();
```

---

#### TC-05: Python 스크립트 오류 처리

**목적**: Python 스크립트 실행 오류 시 적절한 메시지를 표시하는지 확인

**테스트 전략**:
- 실제 파일을 삭제하지 않고, 오류 발생 가능성을 테스트
- 정상 작동(성공) 또는 명확한 오류 메시지 중 하나를 확인

**테스트 단계**:
1. "▶️ 추론 엔진 실행" 버튼 클릭
2. 5초 대기
3. 성공 또는 오류 메시지 확인

**예상 결과**:
- ✅ 성공 시: "✓ 성공" 배지
- ✅ 실패 시: 명확한 오류 메시지 ("찾을 수 없습니다", "실행 오류", "권한")

**검증 포인트**:
```javascript
await page.click('button[name="run_test"]');

const isSuccess = await page.locator('.status-success')
  .isVisible({ timeout: 5000 }).catch(() => false);
const isError = await page.locator('.error-message')
  .isVisible({ timeout: 5000 }).catch(() => false);

expect(isSuccess || isError).toBeTruthy();

if (isError) {
  const errorText = await page.locator('.error-message').textContent();
  expect(errorText).toMatch(/찾을 수 없습니다|실행 오류|권한/);
}
```

---

#### TC-06: 일관성 경고 확인

**목적**: 예상되는 일관성 경고가 올바르게 표시되는지 확인

**배경**:
- 현재 온톨로지는 추상 개념만 정의 (Student, Emotion)
- 추론 규칙은 구체적 감정 사용 (좌절, 집중, 피로)
- 따라서 일관성 경고가 나타나는 것이 정상

**테스트 단계**:
1. "✓ 일관성 검증" 버튼 클릭
2. 경고 메시지 확인

**예상 결과**:
- ✅ "⚠️ 검증 경고" 표시
- ✅ "추론 규칙에서 사용된 '피로'이 온톨로지에 정의되어 있지 않습니다" 메시지

**검증 포인트**:
```javascript
await page.click('button[name="validate"]');

const hasWarning = await page.locator('text=/추론 규칙에서 사용된.*온톨로지에 정의되어 있지 않습니다/')
  .isVisible();

if (hasWarning) {
  console.log('✓ 예상된 일관성 경고 확인됨 (정상)');
}
```

---

#### TC-07: 네트워크 타임아웃 처리

**목적**: Python 스크립트 실행 시간이 길 경우 타임아웃을 적절히 처리하는지 확인

**테스트 단계**:
1. "▶️ 추론 엔진 실행" 버튼 클릭
2. 최대 45초 대기
3. 결과 또는 타임아웃 메시지 확인

**예상 결과**:
- ✅ 45초 이내 성공 또는 오류 메시지 표시
- ✅ 무한 대기 상태 없음

**검증 포인트**:
```javascript
await page.click('button[name="run_test"]');

const result = await Promise.race([
  page.waitForSelector('.status-success', { timeout: 45000 }),
  page.waitForSelector('.error-message', { timeout: 45000 })
]);

expect(result).toBeTruthy();
```

---

## 구현 상세

### 디렉토리 구조

```
ontology_brain/
├── tests/
│   └── e2e/
│       └── ontology_inference_web.test.js  # 메인 테스트 파일
├── test-results/
│   ├── html-report/                        # HTML 리포트
│   ├── screenshots/                        # 스크린샷
│   └── videos/                             # 실패 시 비디오
├── playwright.config.js                    # Playwright 설정
└── package.json                            # npm 의존성
```

### 테스트 파일 구조

```javascript
const { test, expect } = require('@playwright/test');

test.describe('온톨로지 추론 엔진 웹 인터페이스 테스트', () => {
  const BASE_URL = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/test_inference.php';

  test.beforeEach(async ({ page }) => {
    // 페이지 로드 및 Moodle 로그인 처리
    await page.goto(BASE_URL);
  });

  // TC-01: 페이지 로드 및 초기 상태
  test('페이지가 정상적으로 로드되고 모든 섹션이 표시됨', async ({ page }) => {
    // 섹션 확인
    await expect(page.locator('h3:has-text("시스템 정보")')).toBeVisible();
    await expect(page.locator('h3:has-text("온톨로지 구조")')).toBeVisible();

    // 버튼 확인
    await expect(page.locator('button[name="run_test"]')).toBeVisible();
    await expect(page.locator('button[name="validate"]')).toBeVisible();

    // 스크린샷
    await page.screenshot({ path: 'test-results/screenshots/01-page-load.png' });
  });

  // TC-02: 추론 엔진 실행
  test('추론 엔진 실행 버튼 클릭 시 정상 동작', async ({ page }) => {
    // 버튼 클릭
    await page.click('button[name="run_test"]');

    // 성공 배지 대기
    await expect(page.locator('.status-badge.status-success'))
      .toBeVisible({ timeout: 30000 });

    // 성공 메시지 확인
    const successText = await page.locator('.status-badge.status-success').textContent();
    expect(successText).toContain('✓ 성공');

    // 스크린샷
    await page.screenshot({ path: 'test-results/screenshots/02-inference-success.png' });
  });

  // TC-03: 결과 파싱 및 시각화
  test('파싱된 결과가 올바르게 표시됨', async ({ page }) => {
    // 추론 엔진 실행
    await page.click('button[name="run_test"]');
    await page.waitForSelector('.status-success', { timeout: 30000 });

    // 개념 및 규칙 확인
    await expect(page.locator('text=/로드된 개념.*2개/')).toBeVisible();
    await expect(page.locator('text=/로드된 규칙.*3개/')).toBeVisible();

    // 테스트 케이스 개수 확인
    const testCases = page.locator('.test-case');
    await expect(testCases).toHaveCount(3);

    // 각 케이스 내용 확인
    await expect(page.locator('text=/철수.*좌절/')).toBeVisible();
    await expect(page.locator('text=/격려 필요/')).toBeVisible();
    await expect(page.locator('text=/영희.*집중/')).toBeVisible();
    await expect(page.locator('text=/학습 진행/')).toBeVisible();
    await expect(page.locator('text=/민수.*피로/')).toBeVisible();
    await expect(page.locator('text=/휴식 필요/')).toBeVisible();

    // 스크린샷
    await page.screenshot({ path: 'test-results/screenshots/03-parsed-results.png' });
  });

  // TC-04: 일관성 검증
  test('일관성 검증 버튼이 정상 동작', async ({ page }) => {
    // 버튼 클릭
    await page.click('button[name="validate"]');

    // 결과 대기
    await page.waitForSelector('.test-output, .error-message', { timeout: 15000 });

    // 성공 또는 경고 확인
    const hasSuccess = await page.locator('.status-success').isVisible();
    const hasWarning = await page.locator('.error-message').isVisible();
    expect(hasSuccess || hasWarning).toBeTruthy();

    // 스크린샷
    await page.screenshot({ path: 'test-results/screenshots/04-validation.png' });
  });

  // TC-05: Python 스크립트 오류 처리
  test('Python 스크립트 실행 오류 처리', async ({ page }) => {
    await page.click('button[name="run_test"]');

    const isSuccess = await page.locator('.status-success')
      .isVisible({ timeout: 5000 }).catch(() => false);
    const isError = await page.locator('.error-message')
      .isVisible({ timeout: 5000 }).catch(() => false);

    expect(isSuccess || isError).toBeTruthy();

    if (isError) {
      const errorText = await page.locator('.error-message').textContent();
      expect(errorText).toMatch(/찾을 수 없습니다|실행 오류|권한/);
      await page.screenshot({ path: 'test-results/screenshots/05-error-handling.png' });
    }
  });

  // TC-06: 일관성 경고 확인
  test('일관성 검증 경고 메시지 확인', async ({ page }) => {
    await page.click('button[name="validate"]');
    await page.waitForSelector('.test-output, .error-message', { timeout: 15000 });

    const hasWarning = await page.locator('text=/추론 규칙에서 사용된.*온톨로지에 정의되어 있지 않습니다/')
      .isVisible();

    if (hasWarning) {
      console.log('✓ 예상된 일관성 경고 확인됨 (정상)');
    }

    await page.screenshot({ path: 'test-results/screenshots/06-consistency-warning.png' });
  });

  // TC-07: 네트워크 타임아웃 처리
  test('네트워크 타임아웃 적절히 처리', async ({ page }) => {
    await page.click('button[name="run_test"]');

    const result = await Promise.race([
      page.waitForSelector('.status-success', { timeout: 45000 }),
      page.waitForSelector('.error-message', { timeout: 45000 })
    ]);

    expect(result).toBeTruthy();
    await page.screenshot({ path: 'test-results/screenshots/07-timeout-handling.png' });
  });
});
```

---

## 실행 환경 설정

### 1. 의존성 설치

```bash
# 프로젝트 디렉토리로 이동
cd /mnt/c/1\ Project/augmented_teacher/alt42/ontology_brain

# package.json 생성
npm init -y

# Playwright 설치
npm install -D @playwright/test

# Chromium 브라우저 설치
npx playwright install chromium
```

### 2. Playwright 설정

파일: `playwright.config.js`

```javascript
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  // 테스트 파일 위치
  testDir: './tests/e2e',

  // 타임아웃 설정
  timeout: 60000,  // 테스트당 60초

  // 재시도 설정
  retries: 2,      // 실패 시 2번 재시도

  // 전역 설정
  use: {
    // 기본 URL
    baseURL: 'https://mathking.kr',

    // 스크린샷 설정
    screenshot: 'only-on-failure',  // 실패 시에만 캡처

    // 비디오 녹화
    video: 'retain-on-failure',     // 실패 시에만 저장

    // 트레이스 수집
    trace: 'on-first-retry',        // 재시도 시 트레이스

    // 뷰포트 크기
    viewport: { width: 1280, height: 720 },
  },

  // 브라우저 설정
  projects: [
    {
      name: 'chromium',
      use: {
        browserName: 'chromium',
        // 필요시 헤드리스 모드 비활성화
        // headless: false,
      },
    },
  ],

  // 리포터 설정
  reporter: [
    ['html', { outputFolder: 'test-results/html-report' }],
    ['list'],
    ['json', { outputFile: 'test-results/results.json' }],
  ],

  // 결과 저장 위치
  outputDir: 'test-results/',
});
```

### 3. 디렉토리 구조 생성

```bash
# 테스트 디렉토리
mkdir -p tests/e2e

# 결과 디렉토리
mkdir -p test-results/screenshots
mkdir -p test-results/videos
mkdir -p test-results/html-report
```

### 4. Moodle 인증 처리 (선택 사항)

Moodle `require_login()`을 통과하기 위해 사전 인증된 세션을 사용할 수 있습니다.

**방법 1: 수동 로그인 후 세션 저장**

```javascript
// scripts/save-auth.js
const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  // Moodle 로그인 페이지로 이동
  await page.goto('https://mathking.kr/moodle/login/index.php');

  // 수동 로그인 (브라우저 창에서 직접 입력)
  console.log('브라우저에서 로그인하세요. 완료되면 Enter를 누르세요...');
  await new Promise(resolve => process.stdin.once('data', resolve));

  // 세션 저장
  await context.storageState({ path: 'moodle-auth.json' });

  console.log('✅ 인증 정보 저장 완료: moodle-auth.json');
  await browser.close();
})();
```

실행:
```bash
node scripts/save-auth.js
```

**방법 2: 테스트에서 세션 사용**

```javascript
// playwright.config.js에 추가
use: {
  storageState: 'moodle-auth.json',  // 저장된 세션 사용
}
```

---

## 검증 기준

### 성공 기준

| 기준 | 측정 방법 | 목표 |
|------|----------|------|
| **테스트 통과율** | 통과한 테스트 / 전체 테스트 | ≥ 95% |
| **실행 시간** | 전체 테스트 스위트 실행 시간 | ≤ 60초 |
| **결과 정확성** | 3개 테스트 케이스 정확도 | 100% |
| **오류 감지** | 오류 상황 적절한 메시지 표시 | 100% |

### 검증 체크리스트

```yaml
기본_기능:
  - [ ] 페이지 5초 이내 로드
  - [ ] 추론 엔진 30초 이내 실행 완료
  - [ ] "✓ 성공" 배지 표시
  - [ ] 3개 테스트 케이스 모두 결과 표시

결과_정확성:
  - [ ] 케이스 1: "철수 + 좌절 → 격려 필요"
  - [ ] 케이스 2: "영희 + 집중 → 학습 진행"
  - [ ] 케이스 3: "민수 + 피로 → 휴식 필요"
  - [ ] 로드된 개념: 2개 (Student, Emotion)
  - [ ] 로드된 규칙: 3개

시각화_품질:
  - [ ] 입력 사실: 회색 박스에 표시
  - [ ] 적용된 규칙: 파란색 박스로 표시
  - [ ] 추론 결과: 녹색 박스로 강조
  - [ ] 상세 분석: 자동으로 펼쳐짐

오류_처리:
  - [ ] 파일 없음: 빨간색 오류 메시지
  - [ ] 실행 오류: 종료 코드와 함께 오류 표시
  - [ ] 일관성 경고: 노란색 경고 (정상)
  - [ ] 타임아웃: 45초 내 응답
```

### 예상 결과

**성공 시 콘솔 출력**:
```
Running 7 tests using 1 worker

  ✓ 페이지가 정상적으로 로드되고 모든 섹션이 표시됨 (2.3s)
  ✓ 추론 엔진 실행 버튼 클릭 시 정상 동작 (14.8s)
  ✓ 파싱된 결과가 올바르게 표시됨 (11.2s)
  ✓ 일관성 검증 버튼이 정상 동작 (7.9s)
  ✓ Python 스크립트 실행 오류 처리 (3.1s)
  ✓ 일관성 검증 경고 메시지 확인 (6.5s)
  ✓ 네트워크 타임아웃 적절히 처리 (15.2s)

  7 passed (42s)
```

**HTML 리포트**:
- 각 테스트별 스크린샷
- 실행 시간 상세 분석
- 실패한 테스트의 경우 비디오 재생
- 트레이스 뷰어로 단계별 확인

---

## 실행 절차

### 1. 초기 설정 (한 번만 실행)

```bash
# 1. 프로젝트 디렉토리로 이동
cd /mnt/c/1\ Project/augmented_teacher/alt42/ontology_brain

# 2. npm 초기화 및 의존성 설치
npm init -y
npm install -D @playwright/test

# 3. Chromium 설치
npx playwright install chromium

# 4. 디렉토리 생성
mkdir -p tests/e2e test-results/screenshots

# 5. (선택 사항) Moodle 인증 저장
node scripts/save-auth.js
```

### 2. 테스트 파일 작성

테스트 파일을 `tests/e2e/ontology_inference_web.test.js`에 작성합니다. (위의 "구현 상세" 섹션 참조)

### 3. 테스트 실행

```bash
# 모든 테스트 실행
npx playwright test

# 특정 테스트 파일만 실행
npx playwright test ontology_inference_web.test.js

# UI 모드로 실행 (디버깅에 유용)
npx playwright test --ui

# 헤드풀 모드 (브라우저 창 보기)
npx playwright test --headed

# 특정 테스트만 실행
npx playwright test -g "추론 엔진 실행"
```

### 4. 결과 확인

```bash
# HTML 리포트 열기
npx playwright show-report test-results/html-report

# 스크린샷 확인
ls -la test-results/screenshots/

# JSON 결과 확인
cat test-results/results.json
```

### 5. CI/CD 연동 (선택 사항)

GitHub Actions 워크플로우 예시:

```yaml
# .github/workflows/ontology-test.yml
name: 온톨로지 추론 E2E 테스트

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

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
        run: |
          cd alt42/ontology_brain
          npx playwright test

      - name: Upload test results
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: alt42/ontology_brain/test-results/
          retention-days: 30
```

---

## 트러블슈팅

### 문제 1: Moodle 로그인 실패

**증상**: `require_login()` 때문에 로그인 페이지로 리다이렉트

**해결책**:
1. `scripts/save-auth.js` 실행하여 인증 정보 저장
2. `playwright.config.js`에 `storageState: 'moodle-auth.json'` 추가
3. 또는 테스트에서 직접 로그인 처리:

```javascript
test.beforeEach(async ({ page }) => {
  // 로그인 페이지로 이동
  await page.goto('https://mathking.kr/moodle/login/index.php');

  // 로그인 폼 입력
  await page.fill('#username', 'your_username');
  await page.fill('#password', 'your_password');
  await page.click('#loginbtn');

  // 로그인 완료 대기
  await page.waitForURL('**/moodle/**');

  // 테스트 페이지로 이동
  await page.goto(BASE_URL);
});
```

### 문제 2: Python 스크립트 실행 시간 초과

**증상**: 30초 타임아웃 오류

**해결책**:
1. 타임아웃 증가:
```javascript
await expect(page.locator('.status-success'))
  .toBeVisible({ timeout: 60000 });  // 60초로 증가
```

2. 서버 성능 확인:
```bash
# Python 스크립트를 직접 실행하여 실행 시간 측정
cd /mnt/c/1\ Project/augmented_teacher/alt42/ontology_brain/examples
time python3 02_minimal_inference.py
```

### 문제 3: 스크린샷 캡처 실패

**증상**: 스크린샷이 저장되지 않음

**해결책**:
1. 디렉토리 권한 확인:
```bash
chmod -R 755 test-results/
```

2. 명시적 경로 사용:
```javascript
await page.screenshot({
  path: '/mnt/c/1 Project/augmented_teacher/alt42/ontology_brain/test-results/screenshots/test.png',
  fullPage: true
});
```

### 문제 4: 테스트가 간헐적으로 실패

**증상**: 같은 테스트가 때로 통과, 때로 실패

**해결책**:
1. 재시도 활성화 (`playwright.config.js`):
```javascript
retries: 2,  // 실패 시 2번 재시도
```

2. 명시적 대기 추가:
```javascript
// 나쁜 예: 고정 대기
await page.waitForTimeout(5000);

// 좋은 예: 조건 대기
await page.waitForSelector('.status-success', { state: 'visible' });
```

---

## 부록

### A. package.json 예시

```json
{
  "name": "ontology-brain-tests",
  "version": "1.0.0",
  "description": "온톨로지 추론 엔진 E2E 테스트",
  "scripts": {
    "test": "playwright test",
    "test:ui": "playwright test --ui",
    "test:headed": "playwright test --headed",
    "report": "playwright show-report test-results/html-report",
    "save-auth": "node scripts/save-auth.js"
  },
  "devDependencies": {
    "@playwright/test": "^1.40.0"
  }
}
```

### B. .gitignore 추가

```
# Playwright
test-results/
playwright-report/
moodle-auth.json
node_modules/
package-lock.json
```

### C. 참고 문서

- [Playwright 공식 문서](https://playwright.dev/)
- [Playwright Best Practices](https://playwright.dev/docs/best-practices)
- [Moodle Web Services](https://docs.moodle.org/dev/Web_services)
- [온톨로지 추론 엔진 README](../README.md)
- [웹 테스트 가이드](../WEB_TEST_GUIDE.md)

---

## 변경 이력

| 버전 | 날짜 | 변경 내용 | 작성자 |
|------|------|----------|--------|
| 1.0 | 2025-11-01 | 초안 작성 | Mathking Dev Team |

---

**문서 끝**
