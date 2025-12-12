/**
 * 추론 실험실 오류 디버깅 스크립트
 */

const { chromium } = require('@playwright/test');

(async () => {
  console.log('\n🔍 추론 실험실 오류 진단 시작...\n');

  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  // 콘솔 메시지 캡처
  page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    console.log(`[Browser ${type}]:`, text);
  });

  // 페이지 오류 캡처
  page.on('pageerror', error => {
    console.error('❌ 페이지 오류:', error.message);
  });

  // 네트워크 요청 실패 캡처
  page.on('requestfailed', request => {
    console.error('❌ 요청 실패:', request.url(), request.failure().errorText);
  });

  try {
    const url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab.php';
    console.log(`📄 페이지 로드 중: ${url}\n`);

    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

    // 페이지 타이틀 확인
    const title = await page.title();
    console.log('✅ 페이지 타이틀:', title);

    // HTML 구조 확인
    const h1Text = await page.locator('h1').textContent().catch(() => 'h1 없음');
    console.log('✅ H1 텍스트:', h1Text);

    // PHP 오류 확인
    const bodyText = await page.locator('body').textContent();
    if (bodyText.includes('Fatal error') || bodyText.includes('Parse error') || bodyText.includes('Warning')) {
      console.error('\n❌ PHP 오류 발견:');
      console.error(bodyText.substring(0, 500));
    }

    // 스크린샷 저장
    await page.screenshot({
      path: 'test-results/debug-inference-lab.png',
      fullPage: true
    });
    console.log('\n📸 스크린샷 저장: test-results/debug-inference-lab.png');

    // HTML 소스 저장
    const html = await page.content();
    const fs = require('fs');
    fs.writeFileSync('test-results/debug-inference-lab.html', html);
    console.log('📄 HTML 소스 저장: test-results/debug-inference-lab.html\n');

    console.log('✅ 진단 완료!\n');

  } catch (error) {
    console.error('\n❌ 진단 중 오류 발생:', error.message);
  } finally {
    await browser.close();
  }
})();
