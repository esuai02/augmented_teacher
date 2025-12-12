/**
 * 온톨로지 추론 엔진 웹 테스트 - 기존 크롬 브라우저 사용
 *
 * 사용법:
 * 1. 크롬에서 mathking.kr에 로그인
 * 2. 크롬을 디버그 모드로 재시작:
 *    Windows: "C:\Program Files\Google\Chrome\Application\chrome.exe" --remote-debugging-port=9222
 *    Mac: /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --remote-debugging-port=9222
 * 3. npm run test:chrome 실행
 */

const { chromium } = require('@playwright/test');
const { test, expect } = require('@playwright/test');

test.describe('온톨로지 추론 엔진 - 기존 크롬 사용', () => {
  const BASE_URL = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/test_inference.php';

  let browser;
  let context;
  let page;

  test.beforeAll(async () => {
    // 이미 실행 중인 크롬에 연결
    try {
      browser = await chromium.connectOverCDP('http://localhost:9222');
      const contexts = browser.contexts();

      if (contexts.length > 0) {
        context = contexts[0];
      } else {
        context = await browser.newContext();
      }

      page = await context.newPage();
      console.log('✅ 기존 크롬 브라우저에 연결 성공');
    } catch (error) {
      console.error('❌ 크롬 연결 실패:', error.message);
      console.log('\n다음 명령으로 크롬을 디버그 모드로 실행하세요:');
      console.log('chrome.exe --remote-debugging-port=9222\n');
      throw error;
    }
  });

  test.afterAll(async () => {
    if (page) await page.close();
    // 브라우저는 닫지 않음 (사용자가 계속 사용 중)
  });

  test('TC-01: 페이지 로드 확인', async () => {
    await page.goto(BASE_URL);

    await expect(page.locator('h3:has-text("시스템 정보")')).toBeVisible();
    await expect(page.locator('h3:has-text("온톨로지 구조")')).toBeVisible();

    console.log('✅ 페이지 로드 성공');
  });

  test('TC-02: 추론 엔진 실행', async () => {
    await page.goto(BASE_URL);
    await page.click('button[name="run_test"]');

    await page.waitForSelector('.status-success, .error-message', { timeout: 30000 });

    const isSuccess = await page.locator('.status-success').isVisible();
    if (isSuccess) {
      console.log('✅ 추론 엔진 실행 성공');
    }
  });
});
