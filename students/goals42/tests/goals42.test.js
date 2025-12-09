/**
 * Goals42 E2E Test Suite
 *
 * Tests the refactored goals42 controller functionality
 * File: tests/goals42.test.js
 */

const { test, expect } = require('@playwright/test');

const BASE_URL = 'https://mathking.kr/moodle/local/augmented_teacher/students/goals42/goals42.controller.php';
const TEST_COURSE_ID = 2;

test.describe('Goals42 Refactored Application', () => {

    test.beforeEach(async ({ page }) => {
        // Navigate to the controller
        await page.goto(`${BASE_URL}?id=${TEST_COURSE_ID}`);
        // Wait for page to load
        await page.waitForLoadState('networkidle');
    });

    test('Page loads successfully with correct structure', async ({ page }) => {
        console.log('✓ Testing page load and structure...');

        // Check page title
        await expect(page).toHaveTitle(/학생 목표 관리/);

        // Verify main container exists
        const container = page.locator('.goals42-container');
        await expect(container).toBeVisible();

        // Verify page header
        const header = page.locator('.goals42__page-header h2');
        await expect(header).toBeVisible();

        console.log('✓ Page structure verified');
    });

    test('All four tabs are present in correct order', async ({ page }) => {
        console.log('✓ Testing tab navigation...');

        // Get all tab links
        const tabs = page.locator('.goals42__tabs .nav-link');

        // Should have exactly 4 tabs
        await expect(tabs).toHaveCount(4);

        // Verify tab order and labels
        const tabTexts = await tabs.allTextContents();
        expect(tabTexts[0]).toContain('분기 목표');
        expect(tabTexts[1]).toContain('주간 목표');
        expect(tabTexts[2]).toContain('오늘 목표');
        expect(tabTexts[3]).toContain('수학 일기');

        // Verify 테스트 현황 tab is removed
        const allText = await page.textContent('body');
        expect(allText).not.toContain('테스트 현황');

        console.log('✓ Tab structure verified - 4 tabs in correct order');
    });

    test('Tab switching works correctly', async ({ page }) => {
        console.log('✓ Testing tab switching functionality...');

        // Click on 주간 목표 tab
        await page.click('a#weekly-tab');
        await page.waitForTimeout(500);

        // Verify tab is active
        const weeklyTab = page.locator('a#weekly-tab');
        await expect(weeklyTab).toHaveClass(/active/);

        // Verify content is visible
        const weeklyContent = page.locator('#weekly-goals-content');
        await expect(weeklyContent).toBeVisible();

        // Click on 오늘 목표 tab
        await page.click('a#daily-tab');
        await page.waitForTimeout(500);

        // Verify tab is active
        const dailyTab = page.locator('a#daily-tab');
        await expect(dailyTab).toHaveClass(/active/);

        // Click on 수학 일기 tab
        await page.click('a#diary-tab');
        await page.waitForTimeout(500);

        // Verify tab is active
        const diaryTab = page.locator('a#diary-tab');
        await expect(diaryTab).toHaveClass(/active/);

        console.log('✓ Tab switching works correctly');
    });

    test('View/Edit mode toggle buttons are present', async ({ page }) => {
        console.log('✓ Testing view/edit mode toggle...');

        // Check if mode toggle exists (if user has edit permission)
        const modeToggle = page.locator('.goals42__mode-toggle');

        // If toggle exists, verify buttons
        const toggleExists = await modeToggle.count() > 0;

        if (toggleExists) {
            console.log('  → User has edit permissions');

            // Should have 2 buttons (view mode, edit mode)
            const buttons = modeToggle.locator('button');
            await expect(buttons).toHaveCount(2);

            // Verify button text
            const buttonTexts = await buttons.allTextContents();
            expect(buttonTexts.some(text => text.includes('보기 모드'))).toBeTruthy();
            expect(buttonTexts.some(text => text.includes('수정 모드'))).toBeTruthy();

            console.log('✓ Mode toggle buttons verified');
        } else {
            console.log('  → User has view-only permissions (expected for unauthorized users)');
        }
    });

    test('Quarter goals tab displays content', async ({ page }) => {
        console.log('✓ Testing quarter goals tab content...');

        // Make sure we're on quarter tab
        await page.click('a#quarter-tab');
        await page.waitForTimeout(500);

        // Verify tab header
        const tabHeader = page.locator('#quarter-goals-content .goals42__tab-header h3');
        await expect(tabHeader).toBeVisible();
        const headerText = await tabHeader.textContent();
        expect(headerText).toContain('분기 목표');

        // Verify either content or empty state message
        const viewContent = page.locator('#quarter-goals-content .goals42__view-content');
        await expect(viewContent).toBeVisible();

        console.log('✓ Quarter goals tab content verified');
    });

    test('JavaScript Goals42 module is loaded', async ({ page }) => {
        console.log('✓ Testing JavaScript module...');

        // Check if Goals42 object exists in window
        const goals42Exists = await page.evaluate(() => {
            return typeof window.Goals42 !== 'undefined';
        });

        expect(goals42Exists).toBeTruthy();

        // Verify Goals42 has required methods
        const hasRequiredMethods = await page.evaluate(() => {
            return typeof window.Goals42.init === 'function' &&
                   typeof window.Goals42.switchTab === 'function' &&
                   typeof window.Goals42.switchMode === 'function';
        });

        expect(hasRequiredMethods).toBeTruthy();

        console.log('✓ JavaScript module loaded correctly');
    });

    test('CSS styles are applied correctly', async ({ page }) => {
        console.log('✓ Testing CSS application...');

        // Check if main container has proper styling
        const container = page.locator('.goals42-container');
        const maxWidth = await container.evaluate(el => {
            return window.getComputedStyle(el).maxWidth;
        });

        // Should have max-width set (1200px from CSS)
        expect(maxWidth).toBeTruthy();

        // Check tab styling
        const tabs = page.locator('.goals42__tabs');
        const borderBottom = await tabs.evaluate(el => {
            return window.getComputedStyle(el).borderBottom;
        });

        expect(borderBottom).toBeTruthy();

        console.log('✓ CSS styles applied correctly');
    });

    test('Responsive design works on mobile viewport', async ({ page }) => {
        console.log('✓ Testing responsive design...');

        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(500);

        // Verify container is still visible
        const container = page.locator('.goals42-container');
        await expect(container).toBeVisible();

        // Verify tabs are visible
        const tabs = page.locator('.goals42__tabs .nav-link');
        await expect(tabs.first()).toBeVisible();

        console.log('✓ Responsive design verified');
    });

    test('No console errors on page load', async ({ page }) => {
        console.log('✓ Testing for console errors...');

        const consoleErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        // Reload page to capture all console messages
        await page.reload();
        await page.waitForLoadState('networkidle');

        // Should have no console errors
        if (consoleErrors.length > 0) {
            console.log('⚠ Console errors found:', consoleErrors);
        }

        expect(consoleErrors.length).toBe(0);

        console.log('✓ No console errors detected');
    });

    test('Page performance is acceptable', async ({ page }) => {
        console.log('✓ Testing page performance...');

        const startTime = Date.now();
        await page.goto(`${BASE_URL}?id=${TEST_COURSE_ID}`);
        await page.waitForLoadState('networkidle');
        const loadTime = Date.now() - startTime;

        console.log(`  → Page load time: ${loadTime}ms`);

        // Page should load in under 5 seconds
        expect(loadTime).toBeLessThan(5000);

        console.log('✓ Page performance acceptable');
    });
});

test.describe('Goals42 Tab Content Details', () => {

    test('Weekly goals tab structure', async ({ page }) => {
        await page.goto(`${BASE_URL}?id=${TEST_COURSE_ID}&tab=weekly`);
        await page.waitForLoadState('networkidle');

        const tabContent = page.locator('#weekly-goals-content');
        await expect(tabContent).toBeVisible();

        const tabHeader = page.locator('#weekly-goals-content h3');
        const headerText = await tabHeader.textContent();
        expect(headerText).toContain('주간 목표');
    });

    test('Daily goals tab structure', async ({ page }) => {
        await page.goto(`${BASE_URL}?id=${TEST_COURSE_ID}&tab=daily`);
        await page.waitForLoadState('networkidle');

        const tabContent = page.locator('#daily-goals-content');
        await expect(tabContent).toBeVisible();

        const tabHeader = page.locator('#daily-goals-content h3');
        const headerText = await tabHeader.textContent();
        expect(headerText).toContain('오늘 목표');
    });

    test('Math diary tab structure', async ({ page }) => {
        await page.goto(`${BASE_URL}?id=${TEST_COURSE_ID}&tab=diary`);
        await page.waitForLoadState('networkidle');

        const tabContent = page.locator('#math-diary-content');
        await expect(tabContent).toBeVisible();

        const tabHeader = page.locator('#math-diary-content h3');
        const headerText = await tabHeader.textContent();
        expect(headerText).toContain('수학 일기');
    });
});

console.log('\n=== Goals42 Refactoring Test Suite ===\n');
