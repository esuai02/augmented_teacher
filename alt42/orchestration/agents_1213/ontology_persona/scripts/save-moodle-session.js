/**
 * Moodle 세션 저장 스크립트
 *
 * 사용법:
 *   node scripts/save-moodle-session.js
 *
 * 설명:
 *   브라우저를 열어 수동으로 Moodle에 로그인한 후,
 *   세션 정보를 moodle-auth.json 파일에 저장합니다.
 *   이 파일을 사용하면 테스트 시마다 로그인할 필요가 없습니다.
 */

const { chromium } = require('@playwright/test');
const path = require('path');

(async () => {
  console.log('\n=====================================');
  console.log('Moodle 세션 저장 스크립트');
  console.log('=====================================\n');

  const browser = await chromium.launch({
    headless: false,  // 브라우저 창을 보여줌
    slowMo: 100       // 액션을 천천히 실행
  });

  const context = await browser.newContext({
    viewport: { width: 1280, height: 720 }
  });

  const page = await context.newPage();

  try {
    // Moodle 로그인 페이지로 이동
    console.log('📄 Moodle 로그인 페이지 로드 중...');
    await page.goto('https://mathking.kr/moodle/login/index.php', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    console.log('\n==========================================');
    console.log('🖱️  브라우저 창에서 Moodle에 로그인하세요.');
    console.log('');
    console.log('로그인 완료 후:');
    console.log('1. 이 터미널로 돌아와서');
    console.log('2. Enter 키를 누르세요...');
    console.log('==========================================\n');

    // 사용자가 Enter를 누를 때까지 대기
    await new Promise(resolve => {
      process.stdin.once('data', resolve);
    });

    // 현재 URL 확인
    const currentUrl = page.url();
    console.log('\n현재 URL:', currentUrl);

    // 로그인되어 있는지 확인
    if (currentUrl.includes('/login/')) {
      console.log('\n⚠️  아직 로그인 페이지에 있습니다.');
      console.log('로그인을 완료한 후 다시 실행하세요.\n');
      await browser.close();
      process.exit(1);
    }

    // 세션 저장 파일 경로
    const authFilePath = path.join(__dirname, '..', 'moodle-auth.json');

    // 세션 저장
    console.log('💾 세션 저장 중...');
    await context.storageState({ path: authFilePath });

    console.log('\n✅ Moodle 세션 저장 완료!');
    console.log('📁 저장 위치:', authFilePath);
    console.log('\n이제 다음 명령어로 테스트를 실행할 수 있습니다:');
    console.log('  npm test\n');

  } catch (error) {
    console.error('\n❌ 오류 발생:', error.message);
  } finally {
    await browser.close();
  }
})();
