/**
 * 추론 실험실 v2 테스트
 */

const { chromium } = require('@playwright/test');

(async () => {
  console.log('\n🧪 추론 실험실 v2 테스트 시작...\n');

  const browser = await chromium.launch({ headless: false, slowMo: 300 });
  const context = await browser.newContext();
  const page = await context.newPage();

  // POST 응답 모니터링
  page.on('response', async response => {
    if (response.request().method() === 'POST') {
      console.log(`📥 POST 응답:`, response.status());
      try {
        const body = await response.text();
        if (body.length < 1000) {
          console.log('응답 내용:', body);
        } else {
          console.log('응답 길이:', body.length, '문자');
          console.log('응답 시작 (200자):', body.substring(0, 200));
        }
      } catch (e) {
        console.log('응답 읽기 실패');
      }
    }
  });

  // 페이지 오류 캡처
  page.on('pageerror', error => {
    console.error('❌ 페이지 오류:', error.message);
  });

  try {
    const url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v2.php';
    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

    console.log('✅ 페이지 로드 완료\n');

    // 좌절 예제 클릭
    console.log('🖱️  "좌절" 예제 클릭...');
    await page.click('text=😰 좌절');
    await page.waitForTimeout(1000);

    // 추론 실행
    console.log('🖱️  "추론 실행" 클릭...\n');
    await page.click('button:has-text("추론 실행")');

    // 결과 대기
    await page.waitForTimeout(5000);

    // 결과 확인
    const resultVisible = await page.locator('#resultBox.active').isVisible();
    if (resultVisible) {
      const status = await page.locator('#resultStatus').textContent();
      const title = await page.locator('#resultTitle').textContent();
      const content = await page.locator('#resultContent').textContent();

      console.log('📊 결과:');
      console.log('   상태:', status.trim());
      console.log('   제목:', title.trim());
      console.log('   내용:\n', content);
    } else {
      console.log('❌ 결과가 표시되지 않음');
    }

    // 스크린샷
    await page.screenshot({
      path: 'test-results/inference-lab-v2.png',
      fullPage: true
    });
    console.log('\n📸 스크린샷: test-results/inference-lab-v2.png');

    console.log('\n✅ 테스트 완료!\n');

    await page.waitForTimeout(3000);

  } catch (error) {
    console.error('\n❌ 오류:', error.message);
    await page.screenshot({
      path: 'test-results/inference-lab-v2-error.png',
      fullPage: true
    });
  } finally {
    await browser.close();
  }
})();
