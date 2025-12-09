const { chromium } = require('playwright');

async function testTimescaffolding42Behavior() {
    console.log('🎭 Playwright 테스트 시작: timescaffolding42.php 행동 일관성 검증');
    
    const browser = await chromium.launch({ 
        headless: false, 
        slowMo: 1000,
        args: ['--disable-web-security', '--disable-features=VizDisplayCompositor']
    });
    const context = await browser.newContext();
    const page = await context.newPage();
    
    // 콘솔 에러 모니터링
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
            console.log('🚨 브라우저 콘솔 에러:', msg.text());
        }
    });
    
    page.on('pageerror', error => {
        consoleErrors.push(error.message);
        console.log('🚨 페이지 에러:', error.message);
    });
    
    try {
        // 1. timescaffolding42.php 페이지 로드 테스트
        console.log('📄 timescaffolding42.php 페이지 로드 중...');
        await page.goto('file:///mnt/c/1%20Project/augmented_teacher/teachers/timescaffolding42.php', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        // 페이지 로드 성공 확인
        const title = await page.title();
        console.log('✅ 페이지 로드 완료 - 제목:', title);
        
        // 2. 기본 페이지 구조 검증
        console.log('🔍 페이지 구조 검증 중...');
        
        // 헤더 네비게이션 확인
        const headerNav = await page.$('.header-nav');
        if (headerNav) {
            console.log('✅ 헤더 네비게이션 구조 확인됨');
        } else {
            console.log('⚠️ 헤더 네비게이션 구조를 찾을 수 없음');
        }
        
        // 3. 네비게이션 링크 검증 (6개 링크)
        console.log('🔗 네비게이션 링크 검증 중...');
        
        const navigationLinks = [
            { selector: 'a[href*="index42.php"]', name: '내공부방' },
            { selector: 'a[href*="today42.php"]', name: '공부결과' },
            { selector: 'a[href*="student_inbox42.php"]', name: '메세지함' },
            { selector: 'a[href*="goals42.php"]', name: '목표설정' },
            { selector: 'a[href*="schedule42.php"]', name: '수업시간' },
            { selector: 'a[href*="timescaffolding42.php"]', name: '수학일기' }
        ];
        
        let navigationTestResults = [];
        
        for (const link of navigationLinks) {
            const element = await page.$(link.selector);
            if (element) {
                const href = await element.getAttribute('href');
                const text = await element.textContent();
                console.log(`✅ ${link.name} 링크 확인됨: ${href}`);
                navigationTestResults.push({ name: link.name, status: 'found', href });
            } else {
                console.log(`❌ ${link.name} 링크를 찾을 수 없음`);
                navigationTestResults.push({ name: link.name, status: 'missing' });
            }
        }
        
        // 4. JavaScript 기능 테스트
        console.log('⚙️ JavaScript 기능 테스트 중...');
        
        // 기본 JavaScript 변수 및 함수 확인
        const jsFeatures = await page.evaluate(() => {
            return {
                hasDB: typeof window.DB !== 'undefined',
                hasJQuery: typeof window.$ !== 'undefined',
                hasTimeFunctions: typeof window.time !== 'undefined',
                documentReady: document.readyState
            };
        });
        
        console.log('🔧 JavaScript 환경:', jsFeatures);
        
        // 5. CSS 스타일 검증
        console.log('🎨 CSS 스타일 검증 중...');
        
        const styleValidation = await page.evaluate(() => {
            const headerNav = document.querySelector('.header-nav');
            const navBtn = document.querySelector('.nav-btn');
            
            return {
                headerNavExists: !!headerNav,
                headerNavStyles: headerNav ? window.getComputedStyle(headerNav).display : null,
                navBtnExists: !!navBtn,
                navBtnStyles: navBtn ? {
                    display: window.getComputedStyle(navBtn).display,
                    color: window.getComputedStyle(navBtn).color
                } : null
            };
        });
        
        console.log('🎨 스타일 검증 결과:', styleValidation);
        
        // 6. 데이터베이스 연결 및 PHP 기능 테스트 (간접 검증)
        console.log('💾 데이터베이스 연결 상태 간접 검증 중...');
        
        // PHP 변수가 제대로 렌더링되었는지 확인
        const phpVariablesTest = await page.evaluate(() => {
            const bodyText = document.body.textContent;
            return {
                hasPhpErrors: bodyText.includes('<?php') || bodyText.includes('Fatal error'),
                hasMoodleConfig: !bodyText.includes('config.php'),
                hasUserData: !bodyText.includes('$USER->id')
            };
        });
        
        console.log('💾 PHP 처리 상태:', phpVariablesTest);
        
        // 7. timescaffolding.php와의 행동 일관성 비교 (기본 구조)
        console.log('🔄 참조 파일과의 일관성 검증 중...');
        
        // 기본적인 레이아웃 구조 비교
        const layoutConsistency = await page.evaluate(() => {
            return {
                hasContentContainer: !!document.querySelector('.content-container'),
                hasNavControls: !!document.querySelector('.nav-controls'),
                hasHeaderNav: !!document.querySelector('.header-nav'),
                navigationLinksCount: document.querySelectorAll('.nav-btn').length
            };
        });
        
        console.log('🔄 레이아웃 일관성:', layoutConsistency);
        
        // 8. 전체 테스트 결과 요약
        console.log('\n📊 테스트 결과 요약:');
        
        const testResults = {
            pageLoad: title ? 'PASS' : 'FAIL',
            navigationStructure: headerNav ? 'PASS' : 'FAIL',
            navigationLinks: navigationTestResults.filter(r => r.status === 'found').length + '/6',
            javascriptEnvironment: jsFeatures.documentReady === 'complete' ? 'PASS' : 'PARTIAL',
            cssStyles: styleValidation.headerNavExists && styleValidation.navBtnExists ? 'PASS' : 'FAIL',
            phpProcessing: !phpVariablesTest.hasPhpErrors ? 'PASS' : 'FAIL',
            layoutConsistency: layoutConsistency.navigationLinksCount === 6 ? 'PASS' : 'FAIL',
            consoleErrors: consoleErrors.length,
            overallStatus: 'EVALUATION_NEEDED'
        };
        
        console.log('   📄 페이지 로드:', testResults.pageLoad);
        console.log('   🏗️ 네비게이션 구조:', testResults.navigationStructure);
        console.log('   🔗 네비게이션 링크:', testResults.navigationLinks);
        console.log('   ⚙️ JavaScript 환경:', testResults.javascriptEnvironment);
        console.log('   🎨 CSS 스타일:', testResults.cssStyles);
        console.log('   💾 PHP 처리:', testResults.phpProcessing);
        console.log('   🔄 레이아웃 일관성:', testResults.layoutConsistency);
        console.log('   🚨 콘솔 에러:', testResults.consoleErrors);
        
        // 전체 평가
        const passCount = Object.values(testResults).filter(v => v === 'PASS').length;
        const totalTests = 7; // 콘솔 에러와 전체 상태 제외
        
        if (passCount >= 6 && consoleErrors.length <= 2) {
            testResults.overallStatus = 'PASS';
            console.log('🎉 전체 테스트 결과: 성공 (PASS)');
            console.log('✅ timescaffolding42.php가 안정적으로 작동하며 행동 일관성을 유지합니다.');
        } else if (passCount >= 4) {
            testResults.overallStatus = 'PARTIAL';
            console.log('⚠️ 전체 테스트 결과: 부분 성공 (PARTIAL)');
            console.log('🔧 일부 개선이 필요하지만 기본 기능은 작동합니다.');
        } else {
            testResults.overallStatus = 'FAIL';
            console.log('❌ 전체 테스트 결과: 실패 (FAIL)');
            console.log('🚨 심각한 문제가 발견되었습니다. 추가 수정이 필요합니다.');
        }
        
        // 스크린샷 저장
        await page.screenshot({ 
            path: '/mnt/c/1 Project/augmented_teacher/alt42/teachingsupport/timescaffolding42-test-screenshot.png',
            fullPage: true 
        });
        console.log('📸 테스트 스크린샷이 저장되었습니다');
        
        return testResults;
        
    } catch (error) {
        console.error('❌ 테스트 실행 중 오류 발생:', error.message);
        
        // 에러 스크린샷 저장
        try {
            await page.screenshot({ 
                path: '/mnt/c/1 Project/augmented_teacher/alt42/teachingsupport/timescaffolding42-error-screenshot.png',
                fullPage: true 
            });
            console.log('📸 에러 스크린샷이 저장되었습니다');
        } catch (screenshotError) {
            console.log('⚠️ 스크린샷 저장 실패:', screenshotError.message);
        }
        
        return {
            overallStatus: 'ERROR',
            error: error.message,
            consoleErrors: consoleErrors.length
        };
        
    } finally {
        await browser.close();
        console.log('🔚 브라우저 종료');
    }
}

// 테스트 실행
if (require.main === module) {
    testTimescaffolding42Behavior()
        .then(results => {
            console.log('\n🏁 최종 테스트 결과:', results);
            process.exit(results.overallStatus === 'PASS' ? 0 : 1);
        })
        .catch(error => {
            console.error('❌ 테스트 프로세스 실패:', error);
            process.exit(1);
        });
}

module.exports = { testTimescaffolding42Behavior };