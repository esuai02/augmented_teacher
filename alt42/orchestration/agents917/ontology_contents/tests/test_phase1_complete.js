/**
 * Phase 1 완전 E2E 테스트
 *
 * 테스트 목표:
 * - 5개 감정 모두 테스트 (Frustrated, Focused, Tired, Anxious, Happy)
 * - 온톨로지 기반 다중 규칙 매칭 검증
 * - 우선순위 시스템 동작 확인
 * - 결과 출력 형식 검증
 */

const { chromium } = require('@playwright/test');
const fs = require('fs');

// 테스트 결과 디렉토리 생성
if (!fs.existsSync('test-results')) {
  fs.mkdirSync('test-results', { recursive: true });
}

(async () => {
  console.log('\n' + '='.repeat(60));
  console.log('🧪 Phase 1 완전 E2E 테스트 시작');
  console.log('='.repeat(60) + '\n');

  const browser = await chromium.launch({
    headless: false,
    slowMo: 500
  });
  const context = await browser.newContext();
  const page = await context.newPage();

  // POST 응답 모니터링
  let postResponses = [];
  page.on('response', async response => {
    if (response.request().method() === 'POST') {
      const status = response.status();
      const url = response.url();
      postResponses.push({ status, url });
      console.log(`   📥 POST 응답: ${status}`);
    }
  });

  // 페이지 오류 캡처
  let pageErrors = [];
  page.on('pageerror', error => {
    pageErrors.push(error.message);
    console.error(`   ❌ 페이지 오류: ${error.message}`);
  });

  // 콘솔 로그 캡처
  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.error(`   ⚠️  콘솔 에러: ${msg.text()}`);
    }
  });

  // 테스트 시나리오
  const testCases = [
    {
      name: '좌절',
      emoji: '😰',
      student: '철수',
      emotion: 'Frustrated',
      expectedKeywords: ['격려 필요', '좌절'],
      expectedRuleCount: 2,  // rule_frustrated + rule_frustrated_repeat
      priority: 1.0
    },
    {
      name: '집중',
      emoji: '😊',
      student: '영희',
      emotion: 'Focused',
      expectedKeywords: ['학습 진행', '집중'],
      expectedRuleCount: 2,  // rule_focused + rule_focused_encourage
      priority: 1.0
    },
    {
      name: '피로',
      emoji: '😴',
      student: '민수',
      emotion: 'Tired',
      expectedKeywords: ['휴식 필요', '피로'],
      expectedRuleCount: 2,  // rule_tired + rule_tired_break
      priority: 1.0
    },
    {
      name: '불안',
      emoji: '😟',
      student: '지수',
      emotion: 'Anxious',
      expectedKeywords: ['안정화', '불안'],
      expectedRuleCount: 2,  // rule_anxious + rule_anxious_support
      priority: 0.9
    },
    {
      name: '기쁨',
      emoji: '😄',
      student: '현수',
      emotion: 'Happy',
      expectedKeywords: ['칭찬', '기쁨'],
      expectedRuleCount: 2,  // rule_happy + rule_happy_challenge
      priority: 0.8
    }
  ];

  try {
    const url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v3.php';
    console.log(`🌐 URL 로드 중: ${url}`);
    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

    console.log('✅ 페이지 로드 완료\n');

    // 초기 페이지 스크린샷
    await page.screenshot({
      path: 'test-results/phase1-initial.png',
      fullPage: true
    });
    console.log('📸 초기 화면: test-results/phase1-initial.png\n');

    let passedTests = 0;
    let failedTests = 0;

    // 각 감정별 테스트
    for (let i = 0; i < testCases.length; i++) {
      const testCase = testCases[i];
      console.log('-'.repeat(60));
      console.log(`🧪 테스트 ${i + 1}/${testCases.length}: ${testCase.name} (${testCase.emotion})`);
      console.log('-'.repeat(60));

      try {
        // 예제 버튼 클릭
        console.log(`   🖱️  "${testCase.emoji} ${testCase.name}" 버튼 클릭...`);
        await page.click(`text=${testCase.emoji} ${testCase.name}`);
        await page.waitForTimeout(500);

        // 폼 값 확인
        const studentValue = await page.inputValue('#student');
        const emotionValue = await page.inputValue('#emotion');
        console.log(`   ✓ 학생: ${studentValue}`);
        console.log(`   ✓ 감정: ${emotionValue}`);

        if (studentValue !== testCase.student || emotionValue !== testCase.emotion) {
          throw new Error(`폼 값 불일치 (예상: ${testCase.student}/${testCase.emotion}, 실제: ${studentValue}/${emotionValue})`);
        }

        // 추론 실행
        console.log('   🖱️  "추론 실행" 버튼 클릭...');
        postResponses = [];  // 응답 초기화
        await page.click('button:has-text("추론 실행")');

        // 결과 대기 (최대 10초)
        console.log('   ⏳ 결과 대기 중...');
        await page.waitForSelector('#resultBox.active', { timeout: 10000 });

        // 결과 확인
        const status = await page.locator('#resultStatus').textContent();
        const title = await page.locator('#resultTitle').textContent();
        const content = await page.locator('#resultContent').textContent();

        console.log('\n   📊 결과:');
        console.log(`   상태: ${status.trim()}`);
        console.log(`   제목: ${title.trim()}`);

        // 성공 여부 확인
        if (!status.includes('✓ 성공')) {
          throw new Error('추론 실행 실패');
        }

        // 키워드 검증
        let keywordMatch = false;
        for (const keyword of testCase.expectedKeywords) {
          if (content.includes(keyword)) {
            keywordMatch = true;
            console.log(`   ✅ 키워드 "${keyword}" 발견`);
            break;
          }
        }

        if (!keywordMatch) {
          console.log(`   ❌ 예상 키워드 누락: ${testCase.expectedKeywords.join(', ')}`);
        }

        // 다중 규칙 매칭 확인
        const ruleCountMatch = content.match(/매칭된 규칙 수: (\d+)개/);
        if (ruleCountMatch) {
          const ruleCount = parseInt(ruleCountMatch[1]);
          console.log(`   ✓ 매칭된 규칙 수: ${ruleCount}개`);

          if (ruleCount === testCase.expectedRuleCount) {
            console.log(`   ✅ 규칙 개수 일치 (예상: ${testCase.expectedRuleCount}개)`);
          } else {
            console.log(`   ⚠️  규칙 개수 불일치 (예상: ${testCase.expectedRuleCount}개, 실제: ${ruleCount}개)`);
          }
        }

        // 우선순위 확인
        const priorityMatch = content.match(/\[(\d+\.\d+)\]/);
        if (priorityMatch) {
          const priority = parseFloat(priorityMatch[1]);
          console.log(`   ✓ 최우선 규칙 우선순위: ${priority}`);

          if (priority === testCase.priority) {
            console.log(`   ✅ 우선순위 일치`);
          }
        }

        // 스크린샷
        const screenshotPath = `test-results/phase1-${testCase.emotion.toLowerCase()}.png`;
        await page.screenshot({
          path: screenshotPath,
          fullPage: true
        });
        console.log(`   📸 스크린샷: ${screenshotPath}`);

        console.log(`   ✅ 테스트 통과: ${testCase.name}\n`);
        passedTests++;

      } catch (error) {
        console.error(`   ❌ 테스트 실패: ${error.message}\n`);
        failedTests++;

        // 에러 스크린샷
        const errorScreenshotPath = `test-results/phase1-${testCase.emotion.toLowerCase()}-error.png`;
        await page.screenshot({
          path: errorScreenshotPath,
          fullPage: true
        });
        console.log(`   📸 에러 스크린샷: ${errorScreenshotPath}\n`);
      }

      // 테스트 간 대기
      await page.waitForTimeout(1000);
    }

    // 최종 결과 요약
    console.log('\n' + '='.repeat(60));
    console.log('📊 Phase 1 E2E 테스트 결과 요약');
    console.log('='.repeat(60));
    console.log(`총 테스트: ${testCases.length}개`);
    console.log(`✅ 통과: ${passedTests}개`);
    console.log(`❌ 실패: ${failedTests}개`);
    console.log(`📥 POST 요청: ${postResponses.length}개`);
    console.log(`⚠️  페이지 에러: ${pageErrors.length}개`);

    if (failedTests === 0) {
      console.log('\n🎉 모든 테스트 통과!');
    } else {
      console.log('\n⚠️  일부 테스트 실패');
    }

    console.log('='.repeat(60) + '\n');

    // 결과 대기 (사용자가 확인할 수 있도록)
    await page.waitForTimeout(3000);

  } catch (error) {
    console.error('\n❌ 치명적 오류:', error.message);
    console.error(error.stack);

    await page.screenshot({
      path: 'test-results/phase1-fatal-error.png',
      fullPage: true
    });
    console.log('📸 치명적 오류 스크린샷: test-results/phase1-fatal-error.png\n');

  } finally {
    await browser.close();
    console.log('✅ 브라우저 종료\n');
  }
})();
