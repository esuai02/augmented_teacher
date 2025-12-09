/**
 * 추론 실험실 인터랙션 테스트
 */

const { chromium } = require('@playwright/test');

(async () => {
  console.log('\n🧪 추론 실행 테스트 시작...\n');

  const browser = await chromium.launch({ headless: false, slowMo: 500 });
  const context = await browser.newContext();
  const page = await context.newPage();

  // 모든 네트워크 요청 로깅
  page.on('request', request => {
    if (request.method() === 'POST') {
      console.log(`📤 POST 요청:`, request.url());
    }
  });

  page.on('response', async response => {
    if (response.request().method() === 'POST') {
      console.log(`📥 POST 응답:`, response.status(), response.url());
      try {
        const body = await response.text();
        console.log('응답 내용:', body.substring(0, 500));
      } catch (e) {
        console.log('응답 읽기 실패');
      }
    }
  });

  // 콘솔 메시지 캡처
  page.on('console', msg => {
    console.log(`[Browser]:`, msg.text());
  });

  // 페이지 오류 캡처
  page.on('pageerror', error => {
    console.error('❌ 페이지 오류:', error.message);
  });

  try {
    const url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab.php';
    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

    console.log('✅ 페이지 로드 완료\n');

    // 좌절 예제 버튼 클릭
    console.log('🖱️  "좌절" 예제 버튼 클릭...');
    await page.click('text=😰 좌절');
    await page.waitForTimeout(1000);

    // 입력값 확인
    const studentValue = await page.locator('#student').inputValue();
    const emotionValue = await page.locator('#emotion').inputValue();
    console.log(`   학생: ${studentValue}, 감정: ${emotionValue}\n`);

    // 추론 실행 버튼 클릭
    console.log('🖱️  "추론 실행" 버튼 클릭...');
    await page.click('button:has-text("추론 실행")');

    // 결과 대기
    console.log('⏳ 결과 대기 중...\n');
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
      console.log('   내용 (처음 200자):', content.substring(0, 200));
    } else {
      console.log('❌ 결과가 표시되지 않음');
    }

    // 최종 스크린샷
    await page.screenshot({
      path: 'test-results/inference-interaction.png',
      fullPage: true
    });
    console.log('\n📸 스크린샷 저장: test-results/inference-interaction.png');

    console.log('\n✅ 테스트 완료!\n');

    // 5초 대기 후 종료
    await page.waitForTimeout(5000);

  } catch (error) {
    console.error('\n❌ 테스트 중 오류:', error.message);
    await page.screenshot({
      path: 'test-results/inference-error.png',
      fullPage: true
    });
  } finally {
    await browser.close();
  }
})();
