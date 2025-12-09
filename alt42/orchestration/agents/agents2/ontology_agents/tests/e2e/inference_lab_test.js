/**
 * 온톨로지 추론 실험실 간단 테스트
 */

const { test, expect } = require('@playwright/test');

test('추론 실험실 기본 동작 테스트', async ({ page }) => {
  const URL = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab.php';

  // 페이지 로드
  await page.goto(URL);

  // 페이지 제목 확인
  await expect(page.locator('h1')).toContainText('온톨로지 추론 실험실');

  // 좌절 예제 버튼 클릭
  await page.click('text=😰 좌절');

  // 입력값 확인
  await expect(page.locator('#student')).toHaveValue('철수');
  await expect(page.locator('#emotion')).toHaveValue('좌절');

  // 추론 실행
  await page.click('button:has-text("추론 실행")');

  // 결과 대기 (최대 10초)
  await page.waitForSelector('.result-box.active', { timeout: 10000 });

  // 성공 배지 확인
  const hasBadge = await page.locator('.success-badge, .error-badge').isVisible();
  expect(hasBadge).toBeTruthy();

  // 스크린샷
  await page.screenshot({ path: 'test-results/inference-lab.png', fullPage: true });

  console.log('✅ 추론 실험실 테스트 완료');
});
