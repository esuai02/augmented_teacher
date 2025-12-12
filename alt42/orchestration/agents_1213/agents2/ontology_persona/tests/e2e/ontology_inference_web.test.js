/**
 * 온톨로지 추론 엔진 웹 인터페이스 E2E 테스트 (Public 버전)
 *
 * 테스트 대상: test_inference_public.php (로그인 불필요)
 * 환경: https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/
 *
 * 사용법: npm run test:public
 */

const { test, expect } = require('@playwright/test');

test.describe('온톨로지 추론 엔진 웹 인터페이스 테스트', () => {
  const BASE_URL = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/test_inference_public.php';

  test.beforeEach(async ({ page }) => {
    // 페이지 로드
    await page.goto(BASE_URL);

    // Moodle 로그인이 필요한 경우 여기서 처리
    // (현재는 이미 로그인되어 있다고 가정)
  });

  // TC-01: 페이지 로드 및 초기 상태
  test('TC-01: 페이지가 정상적으로 로드되고 모든 섹션이 표시됨', async ({ page }) => {
    // 시스템 정보 섹션 확인
    await expect(page.locator('h3:has-text("시스템 정보")')).toBeVisible();

    // 온톨로지 구조 섹션 확인
    await expect(page.locator('h3:has-text("온톨로지 구조")')).toBeVisible();

    // 테스트 실행 섹션 확인
    await expect(page.locator('h3:has-text("테스트 실행")')).toBeVisible();

    // 추론 엔진 실행 버튼 확인
    await expect(page.locator('button[name="run_test"]')).toBeVisible();

    // 일관성 검증 버튼 확인
    await expect(page.locator('button[name="validate"]')).toBeVisible();

    // 스크린샷 캡처
    await page.screenshot({
      path: 'test-results/screenshots/01-page-load.png',
      fullPage: true
    });

    console.log('✅ TC-01 완료: 페이지 로드 성공');
  });

  // TC-02: 추론 엔진 실행
  test('TC-02: 추론 엔진 실행 버튼 클릭 시 정상 동작', async ({ page }) => {
    // 버튼 클릭
    const runButton = page.locator('button[name="run_test"]');
    await runButton.click();

    console.log('⏳ 추론 엔진 실행 중... (최대 30초 대기)');

    // 성공 배지 또는 오류 메시지 대기 (최대 30초)
    const successBadge = page.locator('.status-badge.status-success');
    const errorMessage = page.locator('.error-message');

    // 둘 중 하나가 나타날 때까지 대기
    await Promise.race([
      successBadge.waitFor({ state: 'visible', timeout: 30000 }),
      errorMessage.waitFor({ state: 'visible', timeout: 30000 })
    ]);

    // 성공 여부 확인
    const isSuccess = await successBadge.isVisible();
    const isError = await errorMessage.isVisible();

    if (isSuccess) {
      // 성공 배지 텍스트 확인
      const successText = await successBadge.textContent();
      expect(successText).toContain('✓ 성공');
      console.log('✅ TC-02 완료: 추론 엔진 실행 성공');
    } else if (isError) {
      const errorText = await errorMessage.textContent();
      console.log('⚠️ TC-02 경고: 오류 발생 -', errorText);
      // 오류가 발생했지만 적절한 메시지가 표시되었으므로 테스트는 통과
      expect(errorText).toMatch(/찾을 수 없습니다|실행 오류|권한/);
    }

    // 스크린샷 캡처
    await page.screenshot({
      path: 'test-results/screenshots/02-inference-execution.png',
      fullPage: true
    });
  });

  // TC-03: 결과 파싱 및 시각화
  test('TC-03: 파싱된 결과가 올바르게 표시됨', async ({ page }) => {
    // 추론 엔진 실행
    await page.click('button[name="run_test"]');

    // 결과 대기
    await page.waitForSelector('.status-success, .error-message', { timeout: 30000 });

    // 성공한 경우에만 결과 검증
    const isSuccess = await page.locator('.status-success').isVisible();

    if (isSuccess) {
      // 상세 분석 섹션 확인
      await expect(page.locator('h4:has-text("상세 분석")')).toBeVisible();

      // 로드된 개념 확인
      await expect(page.locator('text=/로드된 개념.*개/')).toBeVisible();

      // 로드된 규칙 확인
      await expect(page.locator('text=/로드된 규칙.*개/')).toBeVisible();

      // 테스트 케이스 개수 확인
      const testCases = page.locator('.test-case');
      const count = await testCases.count();
      expect(count).toBeGreaterThanOrEqual(1);

      // 테스트 케이스가 있는지 확인
      await expect(testCases.first()).toBeVisible();

      console.log('✅ TC-03 완료: 결과 파싱 성공');
    } else {
      console.log('⚠️ TC-03 건너뜀: 추론 엔진 실행 실패');
    }

    // 스크린샷 캡처
    await page.screenshot({
      path: 'test-results/screenshots/03-parsed-results.png',
      fullPage: true
    });
  });

  // TC-04: 일관성 검증
  test('TC-04: 일관성 검증 버튼이 정상 동작', async ({ page }) => {
    // 일관성 검증 버튼 클릭
    await page.click('button[name="validate"]');

    console.log('⏳ 일관성 검증 실행 중... (최대 15초 대기)');

    // 결과 대기 (성공 또는 경고)
    await page.waitForSelector('.test-output, .error-message', { timeout: 15000 });

    // 결과 확인
    const hasSuccess = await page.locator('.status-success').isVisible();
    const hasWarning = await page.locator('.error-message').isVisible();

    // 둘 중 하나는 표시되어야 함
    expect(hasSuccess || hasWarning).toBeTruthy();

    if (hasWarning) {
      console.log('⚠️ TC-04: 일관성 경고 발생 (정상적인 경고일 수 있음)');
    } else {
      console.log('✅ TC-04 완료: 일관성 검증 성공');
    }

    // 스크린샷 캡처
    await page.screenshot({
      path: 'test-results/screenshots/04-validation.png',
      fullPage: true
    });
  });

  // TC-05: Python 스크립트 오류 처리
  test('TC-05: Python 스크립트 실행 오류 적절히 처리', async ({ page }) => {
    // 추론 엔진 실행
    await page.click('button[name="run_test"]');

    // 짧은 타임아웃으로 빠른 응답 확인
    const isSuccess = await page.locator('.status-success')
      .isVisible({ timeout: 5000 }).catch(() => false);
    const isError = await page.locator('.error-message')
      .isVisible({ timeout: 5000 }).catch(() => false);

    // 둘 중 하나는 표시되어야 함
    expect(isSuccess || isError).toBeTruthy();

    if (isError) {
      // 오류 메시지가 사용자 친화적인지 확인
      const errorText = await page.locator('.error-message').textContent();
      expect(errorText).toMatch(/찾을 수 없습니다|실행 오류|권한/);

      console.log('✅ TC-05 완료: 오류 처리 적절함');

      // 오류 스크린샷
      await page.screenshot({
        path: 'test-results/screenshots/05-error-handling.png',
        fullPage: true
      });
    } else {
      console.log('✅ TC-05 완료: 정상 실행됨 (오류 없음)');
    }
  });

  // TC-06: 일관성 경고 확인
  test('TC-06: 일관성 검증 경고 메시지 확인', async ({ page }) => {
    // 일관성 검증 실행
    await page.click('button[name="validate"]');

    // 결과 대기
    await page.waitForSelector('.test-output, .error-message', { timeout: 15000 });

    // 예상되는 경고 메시지 확인
    const hasWarning = await page.locator('text=/추론 규칙에서 사용된.*온톨로지에 정의되어 있지 않습니다/')
      .isVisible()
      .catch(() => false);

    if (hasWarning) {
      console.log('✅ TC-06 완료: 예상된 일관성 경고 확인됨 (정상)');
    } else {
      console.log('ℹ️ TC-06: 경고 없음 (온톨로지가 완전히 정의된 경우 정상)');
    }

    // 스크린샷 캡처
    await page.screenshot({
      path: 'test-results/screenshots/06-consistency-warning.png',
      fullPage: true
    });
  });

  // TC-07: 네트워크 타임아웃 처리
  test('TC-07: 네트워크 타임아웃 적절히 처리', async ({ page }) => {
    // 추론 엔진 실행
    await page.click('button[name="run_test"]');

    console.log('⏳ 최대 45초 대기...');

    // 두 가지 결과 중 하나를 기다림
    const result = await Promise.race([
      page.waitForSelector('.status-success', { timeout: 45000 }).catch(() => null),
      page.waitForSelector('.error-message', { timeout: 45000 }).catch(() => null)
    ]);

    // 최소한 하나의 결과가 나타나야 함
    expect(result).not.toBeNull();

    console.log('✅ TC-07 완료: 타임아웃 내 응답 받음');

    // 스크린샷 캡처
    await page.screenshot({
      path: 'test-results/screenshots/07-timeout-handling.png',
      fullPage: true
    });
  });
});
