/**
 * Moodle 로그인 헬퍼 함수
 *
 * 사용법:
 *   const { loginToMoodle } = require('../helpers/moodle-login');
 *   await loginToMoodle(page, 'username', 'password');
 */

/**
 * Moodle에 로그인합니다
 *
 * @param {import('@playwright/test').Page} page - Playwright 페이지 객체
 * @param {string} username - Moodle 사용자명
 * @param {string} password - Moodle 비밀번호
 * @returns {Promise<void>}
 */
async function loginToMoodle(page, username, password) {
  console.log('🔐 Moodle 로그인 시도:', username);

  try {
    // Moodle 로그인 페이지로 이동
    await page.goto('https://mathking.kr/moodle/login/index.php', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // 로그인 폼이 나타날 때까지 대기
    await page.waitForSelector('#username', { timeout: 10000 });

    // 사용자명 입력
    await page.fill('#username', username);

    // 비밀번호 입력
    await page.fill('#password', password);

    // 로그인 버튼 클릭
    await page.click('#loginbtn');

    // 로그인 완료 대기
    // 성공하면 Dashboard로 리다이렉트됨
    await page.waitForURL('**/moodle/**', {
      timeout: 15000,
      waitUntil: 'networkidle'
    });

    console.log('✅ Moodle 로그인 성공');

  } catch (error) {
    console.error('❌ Moodle 로그인 실패:', error.message);
    throw error;
  }
}

module.exports = {
  loginToMoodle
};
