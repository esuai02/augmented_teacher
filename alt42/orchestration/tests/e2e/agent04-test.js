/**
 * Agent04 Activity Panel E2E Test
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/tests/e2e/agent04-test.js
 *
 * Comprehensive browser-based testing of Agent04 activity selection UI
 * using Playwright for automated E2E validation.
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Test configuration
const TEST_URL = 'http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/ui/test_panel.html';
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots');
const TIMEOUT = 30000; // 30 seconds

// Test results
const testResults = {
    timestamp: new Date().toISOString(),
    testUrl: TEST_URL,
    results: {},
    screenshots: [],
    consoleLogs: [],
    consoleErrors: [],
    networkRequests: [],
    executionTimeMs: 0
};

// Ensure screenshots directory exists
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

/**
 * Main test execution
 */
async function runTest() {
    const startTime = Date.now();
    let browser;
    let page;

    try {
        console.log('🚀 Starting Agent04 E2E Test...');
        console.log(`📍 Test URL: ${TEST_URL}\n`);

        // Task 1: Launch browser and load page
        console.log('📋 Task 1: Playwright 브라우저 초기화 및 test_panel.html 로드');

        browser = await chromium.launch({
            headless: true,
            timeout: TIMEOUT
        });
        console.log('✅ Browser launched successfully');

        page = await browser.newPage();

        // Setup console listeners
        page.on('console', msg => {
            const text = msg.text();
            testResults.consoleLogs.push({
                type: msg.type(),
                text: text,
                timestamp: new Date().toISOString()
            });

            if (msg.type() === 'error') {
                testResults.consoleErrors.push({
                    text: text,
                    timestamp: new Date().toISOString()
                });
            }
        });

        // Monitor network requests
        page.on('request', request => {
            if (request.url().includes('save_activity.php')) {
                testResults.networkRequests.push({
                    method: request.method(),
                    url: request.url(),
                    postData: request.postData(),
                    timestamp: new Date().toISOString()
                });
            }
        });

        // Navigate to test page
        console.log(`🌐 Navigating to ${TEST_URL}...`);
        const response = await page.goto(TEST_URL, {
            waitUntil: 'domcontentloaded',
            timeout: TIMEOUT
        });

        const statusCode = response.status();
        console.log(`📡 Response status: ${statusCode}`);

        if (statusCode !== 200) {
            throw new Error(`Page load failed with status ${statusCode}`);
        }
        testResults.results.pageLoad = `PASS (${statusCode})`;

        // Wait for JavaScript objects to be defined
        console.log('⏳ Waiting for JavaScript modules to load...');

        await page.waitForFunction(
            () => typeof window.Agent04ActivityCategories !== 'undefined',
            { timeout: 10000 }
        );
        console.log('✅ Agent04ActivityCategories loaded');

        await page.waitForFunction(
            () => typeof window.Agent04ActivityPanel !== 'undefined',
            { timeout: 10000 }
        );
        console.log('✅ Agent04ActivityPanel loaded');

        // Verify objects are accessible
        const categoriesExists = await page.evaluate(() => {
            return typeof window.Agent04ActivityCategories === 'object' &&
                   typeof window.Agent04ActivityCategories.getAllCategories === 'function';
        });

        const panelExists = await page.evaluate(() => {
            return typeof window.Agent04ActivityPanel === 'object' &&
                   typeof window.Agent04ActivityPanel.selectCategory === 'function';
        });

        if (!categoriesExists || !panelExists) {
            throw new Error('JavaScript objects not properly initialized');
        }

        // Capture initial screenshot
        const screenshotPath = path.join(SCREENSHOTS_DIR, 'agent04-initial.png');
        await page.screenshot({ path: screenshotPath, fullPage: true });
        testResults.screenshots.push('agent04-initial.png');
        console.log(`📸 Initial screenshot captured: ${screenshotPath}`);

        // Check for console errors
        const criticalErrors = testResults.consoleErrors.filter(e =>
            !e.text.includes('save_activity.php') // Ignore expected API errors
        );

        if (criticalErrors.length > 0) {
            console.warn(`⚠️  Found ${criticalErrors.length} console errors (non-API)`);
            testResults.results.consoleErrors = `WARNING (${criticalErrors.length} errors)`;
        } else {
            console.log('✅ No critical console errors detected');
            testResults.results.consoleErrors = 'PASS (0 errors)';
        }

        console.log('\n✅ Task 1 COMPLETED: Browser initialized and page loaded successfully\n');

        testResults.results.jsObjectsLoaded = 'PASS (Agent04ActivityCategories, Agent04ActivityPanel)';
        testResults.results.initialScreenshot = 'PASS (captured)';

        // Task 2: Validate 7 category buttons rendering
        console.log('📋 Task 2: 7개 활동 카테고리 버튼 렌더링 검증');

        // Expected categories with icons and names
        const expectedCategories = [
            { icon: '📚', name: '개념이해' },
            { icon: '🎯', name: '유형학습' },
            { icon: '✏️', name: '문제풀이' },
            { icon: '📝', name: '오답노트' },
            { icon: '💬', name: '질의응답' },
            { icon: '🔄', name: '복습활동' },
            { icon: '⏰', name: '포모도르' }
        ];

        // Query all category buttons
        const buttons = await page.locator('#category-buttons button').all();
        const buttonCount = buttons.length;
        console.log(`🔍 Found ${buttonCount} category buttons`);

        if (buttonCount !== 7) {
            throw new Error(`Expected 7 category buttons, but found ${buttonCount}`);
        }
        testResults.results.categoryButtonCount = `PASS (${buttonCount} buttons)`;

        // Validate each button
        let validatedButtons = 0;
        for (let i = 0; i < buttons.length; i++) {
            const button = buttons[i];
            const text = await button.textContent();
            const isVisible = await button.isVisible();
            const isEnabled = await button.isEnabled();

            const expected = expectedCategories[i];

            // Verify icon and name are present
            const hasIcon = text.includes(expected.icon);
            const hasName = text.includes(expected.name);

            if (!hasIcon || !hasName) {
                throw new Error(`Button ${i + 1} missing icon or name. Expected: ${expected.icon} ${expected.name}, Got: ${text}`);
            }

            if (!isVisible) {
                throw new Error(`Button ${i + 1} (${expected.name}) is not visible`);
            }

            if (!isEnabled) {
                throw new Error(`Button ${i + 1} (${expected.name}) is not enabled/clickable`);
            }

            console.log(`  ✅ Button ${i + 1}: ${expected.icon} ${expected.name} (visible, clickable)`);
            validatedButtons++;
        }

        testResults.results.categoryButtonsValidation = `PASS (${validatedButtons}/7 buttons validated)`;

        // Verify all expected categories are present
        const allCategoriesFound = expectedCategories.every((expected, index) => {
            return buttons.length > index; // We already validated content above
        });

        if (!allCategoriesFound) {
            throw new Error('Not all expected categories were found');
        }

        console.log('✅ All 7 category buttons validated: icons, names, visibility, and clickability confirmed');
        console.log('\n✅ Task 2 COMPLETED: Category buttons rendering validated successfully\n');

        // Task 3: Click category and validate modal popup
        console.log('📋 Task 3: 카테고리 클릭 시 모달 팝업 표시 검증');

        // Click first category button (개념이해)
        const firstButton = page.locator('#category-buttons button').first();
        console.log('🖱️  Clicking first category button: 📚 개념이해');
        await firstButton.click();

        // Wait for modal to appear
        const modal = page.locator('#agent04-activity-modal');
        await modal.waitFor({ state: 'visible', timeout: 3000 });
        console.log('✅ Modal appeared');

        const isModalVisible = await modal.isVisible();
        if (!isModalVisible) {
            throw new Error('Modal is not visible after button click');
        }

        // Verify modal header
        const modalHeader = modal.locator('.agent04-modal-header h3');
        const headerText = await modalHeader.textContent();

        if (!headerText.includes('📚') || !headerText.includes('개념이해') || !headerText.includes('세부 활동 선택')) {
            throw new Error(`Modal header incorrect. Expected: "📚 개념이해 - 세부 활동 선택", Got: "${headerText}"`);
        }
        console.log(`  ✅ Modal header: ${headerText}`);

        // Verify close button exists
        const closeBtn = modal.locator('.agent04-close-btn');
        const closeBtnVisible = await closeBtn.isVisible();
        if (!closeBtnVisible) {
            throw new Error('Close button (✕) not visible');
        }
        console.log('  ✅ Close button (✕) present');

        // Verify cancel button exists
        const cancelBtn = modal.locator('.agent04-btn-cancel');
        const cancelBtnVisible = await cancelBtn.isVisible();
        if (!cancelBtnVisible) {
            throw new Error('Cancel button not visible');
        }
        console.log('  ✅ Cancel button present');

        // Capture modal screenshot
        const modalScreenshot = path.join(SCREENSHOTS_DIR, 'agent04-modal-open.png');
        await page.screenshot({ path: modalScreenshot, fullPage: true });
        testResults.screenshots.push('agent04-modal-open.png');
        console.log(`📸 Modal screenshot captured: ${modalScreenshot}`);

        testResults.results.modalPopup = 'PASS (displayed with correct header and controls)';
        console.log('\n✅ Task 3 COMPLETED: Modal popup validated successfully\n');

        // Task 4: Validate 4 sub-items in modal
        console.log('📋 Task 4: 모달 내 4개 하위 항목 표시 검증');

        const subItemButtons = modal.locator('.agent04-sub-item-btn');
        const subItemCount = await subItemButtons.count();
        console.log(`🔍 Found ${subItemCount} sub-item buttons`);

        if (subItemCount !== 4) {
            throw new Error(`Expected 4 sub-items, but found ${subItemCount}`);
        }

        const expectedSubItems = [
            '핵심 개념 정리',
            '공식 유도 과정',
            '개념 간 연결',
            '실생활 적용 예시'
        ];

        for (let i = 0; i < subItemCount; i++) {
            const subItem = subItemButtons.nth(i);
            const itemText = await subItem.locator('.item-text').textContent();
            const itemNumber = await subItem.locator('.item-number').textContent();
            const isVisible = await subItem.isVisible();
            const isEnabled = await subItem.isEnabled();

            if (itemText !== expectedSubItems[i]) {
                throw new Error(`Sub-item ${i + 1} text mismatch. Expected: "${expectedSubItems[i]}", Got: "${itemText}"`);
            }

            if (itemNumber !== String(i + 1)) {
                throw new Error(`Sub-item ${i + 1} number mismatch. Expected: "${i + 1}", Got: "${itemNumber}"`);
            }

            if (!isVisible || !isEnabled) {
                throw new Error(`Sub-item ${i + 1} is not visible or enabled`);
            }

            console.log(`  ✅ Sub-item ${i + 1}: ${itemNumber}. ${itemText} (visible, clickable)`);
        }

        testResults.results.subItemsDisplay = `PASS (${subItemCount}/4 sub-items validated)`;
        console.log('\n✅ Task 4 COMPLETED: Sub-items validated successfully\n');

        // Task 5: Click sub-item and validate success message
        console.log('📋 Task 5: 하위 항목 선택 시 성공 메시지 표시 검증');

        // Mock API response to bypass authentication (테스트 환경에서 인증 우회)
        console.log('🔧 Setting up API mock to bypass Moodle authentication...');
        await page.route('**/save_activity.php', route => {
            console.log('📡 Intercepted API call - returning mocked success response');
            route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    status: 'ok',
                    message: 'Activity saved successfully',
                    id: 1,
                    timestamp: new Date().toISOString()
                })
            });
        });
        console.log('✅ API mock configured successfully');

        // Click first sub-item
        const firstSubItem = subItemButtons.first();
        console.log('🖱️  Clicking first sub-item: 핵심 개념 정리');
        await firstSubItem.click();

        // Wait for success message
        const successMessage = page.locator('.agent04-success-message');
        await successMessage.waitFor({ state: 'visible', timeout: 5000 });
        console.log('✅ Success message appeared');

        // Verify success icon
        const successIcon = successMessage.locator('.success-icon');
        const iconText = await successIcon.textContent();
        if (!iconText.includes('✓')) {
            throw new Error(`Success icon missing. Expected: "✓", Got: "${iconText}"`);
        }
        console.log('  ✅ Success icon (✓) displayed');

        // Verify success text content
        const successText = successMessage.locator('.success-text');
        const textContent = await successText.textContent();

        if (!textContent.includes('📚') || !textContent.includes('개념이해')) {
            throw new Error('Success message missing category information');
        }
        console.log('  ✅ Category information displayed: 📚 개념이해');

        if (!textContent.includes('핵심 개념 정리')) {
            throw new Error('Success message missing sub-item information');
        }
        console.log('  ✅ Sub-item information displayed: 핵심 개념 정리');

        // Verify required Korean notice text
        const futureNotice = successMessage.locator('.future-notice');
        const noticeText = (await futureNotice.textContent()).trim(); // Trim whitespace
        const requiredText = '추후 학생의 행동유형과 관련된 설문이 추가될 예정입니다.';

        if (noticeText !== requiredText) {
            throw new Error(`Future notice text incorrect. Expected: "${requiredText}", Got: "${noticeText}"`);
        }
        console.log(`  ✅ Future notice text validated: "${requiredText}"`);

        // Capture success message screenshot
        const successScreenshot = path.join(SCREENSHOTS_DIR, 'agent04-success-message.png');
        await page.screenshot({ path: successScreenshot, fullPage: true });
        testResults.screenshots.push('agent04-success-message.png');
        console.log(`📸 Success message screenshot captured: ${successScreenshot}`);

        // Verify API call was attempted
        if (testResults.networkRequests.length === 0) {
            console.warn('⚠️  Warning: No API requests detected (may indicate network monitoring issue)');
            testResults.results.apiCall = 'WARNING (no requests detected)';
        } else {
            const apiRequest = testResults.networkRequests[0];
            console.log(`  ✅ API call attempted: ${apiRequest.method} ${apiRequest.url}`);
            testResults.results.apiCall = `PASS (${apiRequest.method} request attempted)`;
        }

        testResults.results.successMessage = 'PASS (displayed with correct content and Korean notice)';
        console.log('\n✅ Task 5 COMPLETED: Success message validated successfully\n');

        // Task 6: Validate auto-close timing and document results
        console.log('📋 Task 6: 모달 자동 닫힘 타이밍 검증 및 테스트 결과 문서화');

        // Start timer
        const autoCloseStart = Date.now();
        console.log('⏱️  Starting timer for auto-close (expected: 2000ms)');

        // Wait for modal to be removed from DOM
        await modal.waitFor({ state: 'detached', timeout: 3000 });
        const elapsedTime = Date.now() - autoCloseStart;
        console.log(`⏱️  Modal closed after ${elapsedTime}ms`);

        // Validate timing (2000ms ± 15% tolerance = 1700-2300ms)
        // Note: Browser timing can vary due to system load and rendering performance
        if (elapsedTime < 1700 || elapsedTime > 2300) {
            throw new Error(`Auto-close timing out of range. Expected: 1700-2300ms, Got: ${elapsedTime}ms`);
        }
        console.log(`  ✅ Auto-close timing within acceptable range (2000ms ± 15% = 1700-2300ms)`);

        // Verify modal no longer exists in DOM
        const modalCount = await page.locator('#agent04-activity-modal').count();
        if (modalCount !== 0) {
            throw new Error('Modal still exists in DOM after auto-close');
        }
        console.log('  ✅ Modal completely removed from DOM');

        // Capture final state screenshot
        const closedScreenshot = path.join(SCREENSHOTS_DIR, 'agent04-closed.png');
        await page.screenshot({ path: closedScreenshot, fullPage: true });
        testResults.screenshots.push('agent04-closed.png');
        console.log(`📸 Final state screenshot captured: ${closedScreenshot}`);

        testResults.results.autoCloseTiming = `PASS (${elapsedTime}ms, within 1700-2300ms range)`;
        testResults.results.modalRemoval = 'PASS (completely removed from DOM)';

        console.log('\n✅ Task 6 COMPLETED: Auto-close timing and documentation validated\n');
        console.log('🎉 ALL TASKS COMPLETED SUCCESSFULLY! 🎉\n');

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        testResults.results.error = error.message;
        testResults.results.taskStatus = 'FAILED';

        // Capture error screenshot if page exists
        if (page) {
            try {
                const errorScreenshot = path.join(SCREENSHOTS_DIR, 'agent04-error.png');
                await page.screenshot({ path: errorScreenshot, fullPage: true });
                testResults.screenshots.push('agent04-error.png');
                console.log(`📸 Error screenshot captured: ${errorScreenshot}`);
            } catch (screenshotError) {
                console.error('Failed to capture error screenshot:', screenshotError.message);
            }
        }
    } finally {
        // Cleanup
        if (browser) {
            await browser.close();
            console.log('🔒 Browser closed');
        }

        testResults.executionTimeMs = Date.now() - startTime;

        // Save test results
        const resultsPath = path.join(__dirname, 'test-results.json');
        fs.writeFileSync(resultsPath, JSON.stringify(testResults, null, 2));
        console.log(`\n💾 Test results saved: ${resultsPath}`);

        // Print summary
        printSummary();
    }
}

/**
 * Print test summary
 */
function printSummary() {
    console.log('\n' + '='.repeat(60));
    console.log('📊 TEST SUMMARY');
    console.log('='.repeat(60));
    console.log(`⏱️  Execution Time: ${testResults.executionTimeMs}ms`);
    console.log(`📸 Screenshots: ${testResults.screenshots.length}`);
    console.log(`📝 Console Logs: ${testResults.consoleLogs.length}`);
    console.log(`❌ Console Errors: ${testResults.consoleErrors.length}`);
    console.log(`🌐 Network Requests: ${testResults.networkRequests.length}`);
    console.log('\n📋 Results:');

    Object.entries(testResults.results).forEach(([key, value]) => {
        const icon = value.toString().startsWith('PASS') ? '✅' :
                     value.toString().startsWith('WARNING') ? '⚠️' : '❌';
        console.log(`  ${icon} ${key}: ${value}`);
    });

    console.log('='.repeat(60) + '\n');
}

// Run the test
runTest().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
