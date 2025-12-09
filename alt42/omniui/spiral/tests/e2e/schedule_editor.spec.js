/**
 * E2E Tests for Schedule Editor
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const BASE_URL = process.env.TEST_BASE_URL || 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui';
const TEACHER_USERNAME = process.env.TEST_TEACHER_USER || 'teacher1';
const TEACHER_PASSWORD = process.env.TEST_TEACHER_PASS || 'Teacher123!';
const STUDENT_ID = process.env.TEST_STUDENT_ID || '123';

test.describe('Schedule Editor E2E Tests', () => {
    
    test.beforeEach(async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        // Wait for redirect to dashboard
        await page.waitForURL(/.*\/local\/spiral\/.*/);
    });
    
    test.afterEach(async ({ page }) => {
        // Logout
        await page.goto(`${BASE_URL}/logout.php`);
    });
    
    test('Generate new schedule with 7:3 ratio', async ({ page }) => {
        // Navigate to schedule editor
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Select student
        await page.selectOption('#student_id', STUDENT_ID);
        
        // Set date range
        const startDate = new Date();
        const endDate = new Date();
        endDate.setMonth(endDate.getMonth() + 1);
        
        await page.fill('#start_date', startDate.toISOString().split('T')[0]);
        await page.fill('#end_date', endDate.toISOString().split('T')[0]);
        
        // Set hours per week
        await page.fill('#hours_per_week', '14');
        
        // Set 7:3 ratio using slider
        const slider = await page.locator('#alpha_slider');
        await slider.fill('70');
        
        // Verify ratio display
        await expect(page.locator('#alpha_display')).toHaveText('70%');
        await expect(page.locator('#beta_display')).toHaveText('30%');
        
        // Generate schedule
        await page.click('#generate-btn');
        
        // Wait for schedule generation
        await page.waitForSelector('#schedule-display:not(.d-none)', { timeout: 10000 });
        
        // Verify schedule generated
        await expect(page.locator('#schedule-display')).toBeVisible();
        
        // Check summary statistics
        const totalSessions = await page.locator('[data-stat="total-sessions"]').textContent();
        expect(parseInt(totalSessions)).toBeGreaterThan(0);
        
        // Verify 7:3 ratio in summary
        const previewRatio = await page.locator('[data-stat="preview-ratio"]').textContent();
        const reviewRatio = await page.locator('[data-stat="review-ratio"]').textContent();
        
        expect(parseInt(previewRatio)).toBeCloseTo(70, 5);
        expect(parseInt(reviewRatio)).toBeCloseTo(30, 5);
    });
    
    test('Drag and drop session to different day', async ({ page }) => {
        // Generate a schedule first
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        await page.selectOption('#student_id', STUDENT_ID);
        await page.fill('#start_date', '2024-01-01');
        await page.fill('#end_date', '2024-01-31');
        await page.click('#generate-btn');
        await page.waitForSelector('#schedule-display:not(.d-none)');
        
        // Find a draggable session
        const sessionCard = await page.locator('.session-card').first();
        const targetDay = await page.locator('.day-container').nth(2);
        
        // Get initial session count of target day
        const initialCount = await targetDay.locator('.session-card').count();
        
        // Perform drag and drop
        await sessionCard.dragTo(targetDay);
        
        // Wait for drop to complete
        await page.waitForTimeout(500);
        
        // Verify session moved
        const newCount = await targetDay.locator('.session-card').count();
        expect(newCount).toBe(initialCount + 1);
        
        // Check pending changes indicator
        await expect(page.locator('#save-changes-btn')).toContainText('변경사항 저장');
    });
    
    test('Conflict detection and resolution', async ({ page }) => {
        // Generate schedule with high load to trigger conflicts
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        await page.selectOption('#student_id', STUDENT_ID);
        await page.fill('#start_date', '2024-01-01');
        await page.fill('#end_date', '2024-01-07');
        await page.fill('#hours_per_week', '35'); // High load
        await page.click('#generate-btn');
        
        await page.waitForSelector('#schedule-display:not(.d-none)');
        
        // Check for conflict indicators
        const conflictBadge = await page.locator('.conflict-badge');
        
        if (await conflictBadge.count() > 0) {
            // Verify conflict display
            await expect(conflictBadge.first()).toBeVisible();
            
            // Click on conflict to see details
            await conflictBadge.first().click();
            
            // Wait for conflict modal
            await page.waitForSelector('.conflict-modal');
            
            // Check conflict information
            await expect(page.locator('.conflict-type')).toBeVisible();
            
            // Try to resolve conflict
            await page.click('.resolve-btn');
            
            // Verify resolution feedback
            await expect(page.locator('.toast-notification')).toContainText('충돌 해결');
        }
    });
    
    test('Save and publish schedule', async ({ page }) => {
        // Generate schedule
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        await page.selectOption('#student_id', STUDENT_ID);
        await page.fill('#start_date', '2024-01-01');
        await page.fill('#end_date', '2024-01-31');
        await page.click('#generate-btn');
        
        await page.waitForSelector('#schedule-display:not(.d-none)');
        
        // Make a change to enable save
        const sessionCard = await page.locator('.session-card').first();
        const targetDay = await page.locator('.day-container').nth(1);
        await sessionCard.dragTo(targetDay);
        
        // Save changes
        await page.click('#save-changes-btn');
        
        // Wait for save confirmation
        await page.waitForSelector('.toast-notification:has-text("저장되었습니다")');
        
        // Publish schedule
        await page.click('#publish-btn');
        
        // Confirm publication dialog
        await page.click('.confirm-publish-btn');
        
        // Wait for publish confirmation
        await page.waitForSelector('.toast-notification:has-text("발행되었습니다")');
        
        // Verify publish button is disabled
        await expect(page.locator('#publish-btn')).toBeDisabled();
        await expect(page.locator('#publish-btn')).toContainText('발행됨');
    });
    
    test('Filter and search sessions', async ({ page }) => {
        // Generate schedule
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        await page.selectOption('#student_id', STUDENT_ID);
        await page.fill('#start_date', '2024-01-01');
        await page.fill('#end_date', '2024-01-31');
        await page.click('#generate-btn');
        
        await page.waitForSelector('#schedule-display:not(.d-none)');
        
        // Filter by session type
        await page.selectOption('#filter-type', 'preview');
        
        // Verify only preview sessions shown
        const visibleSessions = await page.locator('.session-card:visible');
        const sessionTypes = await visibleSessions.evaluateAll(
            elements => elements.map(el => el.dataset.sessionType)
        );
        
        sessionTypes.forEach(type => {
            expect(type).toBe('preview');
        });
        
        // Filter by subject
        await page.selectOption('#filter-subject', 'math');
        
        // Search for specific unit
        await page.fill('#search-unit', 'unit_1');
        
        // Verify filtered results
        const searchResults = await page.locator('.session-card:visible');
        expect(await searchResults.count()).toBeGreaterThan(0);
    });
    
    test('Calendar view interaction', async ({ page }) => {
        // Navigate to calendar view
        await page.goto(`${BASE_URL}/local/spiral/calendar.php`);
        
        // Wait for calendar to load
        await page.waitForSelector('.calendar-container');
        
        // Select month view
        await page.click('[data-view="month"]');
        
        // Navigate to next month
        await page.click('.calendar-nav-next');
        
        // Click on a specific date
        const targetDate = await page.locator('.calendar-day').nth(15);
        await targetDate.click();
        
        // Verify day detail view opens
        await page.waitForSelector('.day-detail-modal');
        
        // Check sessions for that day
        const daySessions = await page.locator('.day-detail-modal .session-item');
        
        if (await daySessions.count() > 0) {
            // Click on a session to edit
            await daySessions.first().click();
            
            // Verify edit modal opens
            await page.waitForSelector('.session-edit-modal');
            
            // Edit session duration
            await page.fill('#edit-duration', '45');
            
            // Save changes
            await page.click('.save-session-btn');
            
            // Verify success message
            await expect(page.locator('.toast-notification')).toContainText('수정되었습니다');
        }
    });
    
    test('Responsive design on mobile', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        
        // Navigate to schedule editor
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Check mobile menu toggle
        await expect(page.locator('.mobile-menu-toggle')).toBeVisible();
        
        // Open mobile menu
        await page.click('.mobile-menu-toggle');
        
        // Verify navigation drawer
        await expect(page.locator('.mobile-nav-drawer')).toBeVisible();
        
        // Generate schedule on mobile
        await page.selectOption('#student_id', STUDENT_ID);
        await page.fill('#start_date', '2024-01-01');
        await page.fill('#end_date', '2024-01-07');
        await page.click('#generate-btn');
        
        await page.waitForSelector('#schedule-display:not(.d-none)');
        
        // Check mobile-optimized layout
        await expect(page.locator('.mobile-schedule-view')).toBeVisible();
        
        // Verify swipe gestures for navigation
        const scheduleContainer = await page.locator('.mobile-schedule-view');
        
        // Simulate swipe left
        await scheduleContainer.swipe({ direction: 'left' });
        await page.waitForTimeout(300);
        
        // Verify next week is shown
        await expect(page.locator('.week-indicator')).toContainText('2주차');
    });
    
    test('Accessibility compliance', async ({ page }) => {
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Check ARIA labels
        const formInputs = await page.locator('input, select, button');
        const inputCount = await formInputs.count();
        
        for (let i = 0; i < inputCount; i++) {
            const input = formInputs.nth(i);
            const ariaLabel = await input.getAttribute('aria-label');
            const id = await input.getAttribute('id');
            
            if (id) {
                const label = await page.locator(`label[for="${id}"]`);
                const hasLabel = await label.count() > 0;
                
                expect(ariaLabel || hasLabel).toBeTruthy();
            }
        }
        
        // Test keyboard navigation
        await page.keyboard.press('Tab');
        await expect(page.locator('#student_id')).toBeFocused();
        
        await page.keyboard.press('Tab');
        await expect(page.locator('#start_date')).toBeFocused();
        
        // Test screen reader announcements
        await page.click('#generate-btn');
        
        // Check for live region updates
        const liveRegion = await page.locator('[aria-live="polite"]');
        await expect(liveRegion).toContainText('스케줄 생성 중');
    });
    
    test('Performance monitoring', async ({ page }) => {
        // Start performance measurement
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        const performanceMetrics = await page.evaluate(() => {
            const navigation = performance.getEntriesByType('navigation')[0];
            return {
                domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
                firstPaint: performance.getEntriesByName('first-paint')[0]?.startTime || 0,
                firstContentfulPaint: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0
            };
        });
        
        // Assert performance thresholds
        expect(performanceMetrics.domContentLoaded).toBeLessThan(3000);
        expect(performanceMetrics.loadComplete).toBeLessThan(5000);
        expect(performanceMetrics.firstContentfulPaint).toBeLessThan(2000);
        
        // Test schedule generation performance
        const startTime = Date.now();
        
        await page.selectOption('#student_id', STUDENT_ID);
        await page.fill('#start_date', '2024-01-01');
        await page.fill('#end_date', '2024-03-31'); // 3 months
        await page.click('#generate-btn');
        
        await page.waitForSelector('#schedule-display:not(.d-none)');
        
        const generationTime = Date.now() - startTime;
        
        // Generation should complete within 5 seconds
        expect(generationTime).toBeLessThan(5000);
    });
});