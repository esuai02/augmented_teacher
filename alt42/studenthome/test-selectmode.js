const { chromium } = require('playwright');

(async () => {
  // 브라우저 실행
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  try {
    console.log('페이지 접속 중...');
    
    // selectmode.php 페이지로 이동 (학생 ID 포함)
    await page.goto('https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/selectmode.php?userid=123');
    
    // 페이지 로드 대기
    await page.waitForTimeout(3000);
    
    // 현재 페이지 타이틀과 URL 확인
    const title = await page.title();
    const url = page.url();
    console.log('페이지 타이틀:', title);
    console.log('현재 URL:', url);
    
    // 로그인이 필요한지 확인
    if (url.includes('login')) {
      console.log('로그인이 필요합니다.');
      // 로그인 처리가 필요한 경우
      return;
    }
    
    // 역할 확인 (선생님/학생)
    const roleElement = await page.$('.switch-button');
    if (roleElement) {
      const roleText = await roleElement.textContent();
      console.log('현재 역할:', roleText);
    }
    
    // 선생님 모드 그리드 확인
    const teacherGrid = await page.$('#teacherModeGrid');
    if (teacherGrid) {
      console.log('선생님 모드 그리드 발견!');
      const teacherCards = await page.$$('#teacherModeGrid .mode-card');
      console.log('선생님 모드 카드 수:', teacherCards.length);
      
      // 각 카드의 정보 출력
      for (let i = 0; i < teacherCards.length; i++) {
        const title = await teacherCards[i].$eval('.mode-title', el => el.textContent);
        console.log(`선생님 모드 ${i+1}: ${title}`);
      }
    }
    
    // 학생 모드 그리드 확인
    const studentGrid = await page.$('#studentModeGrid');
    if (studentGrid) {
      console.log('\n학생 모드 그리드 발견!');
      const studentCards = await page.$$('#studentModeGrid .mode-card');
      console.log('학생 모드 카드 수:', studentCards.length);
      
      // 각 카드의 정보 출력
      for (let i = 0; i < studentCards.length; i++) {
        const title = await studentCards[i].$eval('.mode-title', el => el.textContent);
        console.log(`학생 모드 ${i+1}: ${title}`);
      }
    }
    
    // 콘솔 로그 캡처
    page.on('console', msg => {
      console.log('브라우저 콘솔:', msg.text());
    });
    
    // 페이지 스크린샷 저장
    await page.screenshot({ path: 'selectmode-screenshot.png', fullPage: true });
    console.log('\n스크린샷 저장됨: selectmode-screenshot.png');
    
    // CSS 스타일 확인
    const gridStyle = await page.$eval('#teacherModeGrid', el => {
      return window.getComputedStyle(el).display + ' / ' + 
             window.getComputedStyle(el).gridTemplateColumns;
    });
    console.log('\n그리드 스타일:', gridStyle);
    
  } catch (error) {
    console.error('오류 발생:', error);
  } finally {
    // 브라우저는 열어둠 (디버깅용)
    // await browser.close();
  }
})();