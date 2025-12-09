/**
 * E2E tests for Routine Coach widget
 * 
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Using Playwright for E2E testing
const { test, expect } = require('@playwright/test');

// Test configuration
const BASE_URL = process.env.MOODLE_URL || 'http://localhost:8080';
const TEST_USER = {
    username: 'testuser1',
    password: 'Test123!@#'
};

// Helper function to login
async function login(page, username, password) {
    await page.goto(`${BASE_URL}/login/index.php`);
    await page.fill('#username', username);
    await page.fill('#password', password);
    await page.click('#loginbtn');
    await page.waitForURL(/.*\/my\/.*/);
}

// Helper to wait for widget
async function waitForWidget(page) {
    await page.waitForSelector('#routinecoach-widget', { timeout: 10000 });
}

test.describe('Routine Coach Widget E2E Tests', () => {
    
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await login(page, TEST_USER.username, TEST_USER.password);
    });
    
    test('Widget auto-injects on today42.php page', async ({ page }) => {
        // Navigate to today42.php
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        
        // Wait for widget to appear
        await waitForWidget(page);
        
        // Verify widget structure
        const widget = await page.$('#routinecoach-widget');
        expect(widget).toBeTruthy();
        
        // Check header exists
        const header = await page.$('.widget-header');
        expect(header).toBeTruthy();
        
        // Check body exists
        const body = await page.$('.widget-body');
        expect(body).toBeTruthy();
        
        // Take screenshot for visual regression
        await page.screenshot({ 
            path: 'tests/screenshots/widget-today42.png',
            fullPage: true 
        });
    });
    
    test('Widget auto-injects on studenthome page', async ({ page }) => {
        // Navigate to studenthome
        await page.goto(`${BASE_URL}/alt42/studenthome/index.php`);
        
        // Wait for widget
        await waitForWidget(page);
        
        // Verify widget exists
        const widget = await page.$('#routinecoach-widget');
        expect(widget).toBeTruthy();
        
        // Screenshot
        await page.screenshot({ 
            path: 'tests/screenshots/widget-studenthome.png',
            fullPage: true 
        });
    });
    
    test('Widget loads today tasks correctly', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Wait for tasks to load
        await page.waitForSelector('.task-item', { timeout: 5000 });
        
        // Count tasks
        const tasks = await page.$$('.task-item');
        expect(tasks.length).toBeGreaterThan(0);
        
        // Verify task structure
        const firstTask = tasks[0];
        const title = await firstTask.$('.task-title');
        expect(title).toBeTruthy();
        
        const checkbox = await firstTask.$('.task-checkbox');
        expect(checkbox).toBeTruthy();
        
        // Check for badges
        const badges = await firstTask.$$('.badge');
        expect(badges.length).toBeGreaterThan(0);
    });
    
    test('Task completion updates UI correctly', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Wait for tasks
        await page.waitForSelector('.task-item');
        
        // Get initial stats
        const initialProgress = await page.textContent('.progress-text');
        
        // Click first unchecked checkbox
        const uncheckedBox = await page.$('.task-checkbox:not(:checked)');
        if (uncheckedBox) {
            // Click checkbox
            await uncheckedBox.click();
            
            // Wait for update
            await page.waitForTimeout(1000);
            
            // Check progress updated
            const updatedProgress = await page.textContent('.progress-text');
            expect(updatedProgress).not.toBe(initialProgress);
            
            // Verify checkbox is checked
            const isChecked = await uncheckedBox.isChecked();
            expect(isChecked).toBeTruthy();
            
            // Verify strikethrough applied
            const taskLabel = await page.$('.task-checkbox:checked + .task-label .task-title');
            const textDecoration = await taskLabel.evaluate(el => 
                window.getComputedStyle(el).textDecoration
            );
            expect(textDecoration).toContain('line-through');
        }
    });
    
    test('Widget minimize/maximize functionality', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Click header to minimize
        await page.click('.widget-header');
        
        // Check widget is minimized
        const widget = await page.$('#routinecoach-widget');
        const hasMinimizedClass = await widget.evaluate(el => 
            el.classList.contains('minimized')
        );
        expect(hasMinimizedClass).toBeTruthy();
        
        // Verify body is hidden
        const bodyVisible = await page.isVisible('.widget-body');
        expect(bodyVisible).toBeFalsy();
        
        // Click header again to maximize
        await page.click('.widget-header');
        
        // Check widget is maximized
        const isMinimized = await widget.evaluate(el => 
            el.classList.contains('minimized')
        );
        expect(isMinimized).toBeFalsy();
        
        // Verify body is visible
        const bodyVisibleAfter = await page.isVisible('.widget-body');
        expect(bodyVisibleAfter).toBeTruthy();
    });
    
    test('Stats display shows correct exam info', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Wait for stats to load
        await page.waitForSelector('.stats-row');
        
        // Check exam label exists
        const examLabel = await page.$('.stat-item:first-child .stat-value');
        const examText = await examLabel.textContent();
        expect(examText).toBeTruthy();
        expect(examText).toMatch(/.*고사|시험|평가/);
        
        // Check D-day countdown
        const countdown = await page.$('.stat-item:nth-child(2) .stat-value');
        const countdownText = await countdown.textContent();
        expect(countdownText).toMatch(/D-\d+/);
        
        // Check ratio display
        const ratio = await page.$('.stat-item:nth-child(3) .stat-value');
        const ratioText = await ratio.textContent();
        expect(ratioText).toMatch(/\d+:\d+/);
    });
    
    test('Progress bar updates on task completion', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Get initial progress
        const progressBar = await page.$('.progress-fill');
        const initialWidth = await progressBar.evaluate(el => el.style.width);
        
        // Complete a task
        const uncheckedBox = await page.$('.task-checkbox:not(:checked)');
        if (uncheckedBox) {
            await uncheckedBox.click();
            await page.waitForTimeout(1000);
            
            // Check progress bar updated
            const updatedWidth = await progressBar.evaluate(el => el.style.width);
            expect(updatedWidth).not.toBe(initialWidth);
            
            // Parse percentages and verify increase
            const initialPercent = parseFloat(initialWidth);
            const updatedPercent = parseFloat(updatedWidth);
            expect(updatedPercent).toBeGreaterThan(initialPercent);
        }
    });
    
    test('No tasks message displays correctly', async ({ page }) => {
        // Create scenario with no tasks (might need mock or specific user)
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Check if no-tasks message exists (conditional)
        const noTasksElement = await page.$('.no-tasks');
        if (noTasksElement) {
            const message = await noTasksElement.textContent();
            expect(message).toContain('오늘 할 일이 없습니다');
            expect(message).toContain('🎉');
        }
    });
    
    test('AJAX error handling for task completion', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Intercept AJAX request and force error
        await page.route('**/local/routinecoach/index.php', route => {
            route.fulfill({
                status: 500,
                body: JSON.stringify({ success: false, message: 'Server error' })
            });
        });
        
        // Try to complete task
        const checkbox = await page.$('.task-checkbox:not(:checked)');
        if (checkbox) {
            const wasChecked = await checkbox.isChecked();
            await checkbox.click();
            
            // Wait for error handling
            await page.waitForTimeout(1000);
            
            // Verify checkbox reverted
            const isCheckedAfter = await checkbox.isChecked();
            expect(isCheckedAfter).toBe(wasChecked);
        }
    });
    
    test('Widget position adjusts for existing elements', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        
        // Add a mock element that would conflict
        await page.evaluate(() => {
            const mockWidget = document.createElement('div');
            mockWidget.id = 'existing-widget';
            mockWidget.style.cssText = 'position: fixed; bottom: 20px; right: 20px; width: 300px; height: 200px; background: blue;';
            document.body.appendChild(mockWidget);
        });
        
        // Now inject routine coach widget
        await page.evaluate(() => {
            require(['local_routinecoach/routinecoach'], function(RC) {
                RC.init({userid: M.cfg.userid});
            });
        });
        
        await waitForWidget(page);
        
        // Check widget position is adjusted
        const widget = await page.$('#routinecoach-widget');
        const position = await widget.evaluate(el => {
            const style = window.getComputedStyle(el);
            return {
                right: style.right,
                bottom: style.bottom
            };
        });
        
        // Should be adjusted from default 20px
        expect(parseInt(position.right)).toBeGreaterThan(20);
    });
    
    test('Widget data refreshes on visibility change', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Get initial task count
        const initialTasks = await page.$$('.task-item');
        const initialCount = initialTasks.length;
        
        // Simulate tab visibility change
        await page.evaluate(() => {
            document.dispatchEvent(new Event('visibilitychange'));
        });
        
        // Wait for potential refresh
        await page.waitForTimeout(2000);
        
        // Verify tasks still loaded
        const afterTasks = await page.$$('.task-item');
        expect(afterTasks.length).toBe(initialCount);
    });
    
    test('Responsive design on mobile viewport', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Check widget adapts to mobile
        const widget = await page.$('#routinecoach-widget');
        const widgetBox = await widget.boundingBox();
        
        // Widget should not exceed viewport width
        expect(widgetBox.width).toBeLessThanOrEqual(375);
        
        // Take mobile screenshot
        await page.screenshot({ 
            path: 'tests/screenshots/widget-mobile.png',
            fullPage: false 
        });
    });
    
    test('Keyboard navigation support', async ({ page }) => {
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        
        // Focus first checkbox
        await page.focus('.task-checkbox:first-child');
        
        // Press space to toggle
        await page.keyboard.press('Space');
        
        // Check if toggled
        const firstCheckbox = await page.$('.task-checkbox:first-child');
        const isChecked = await firstCheckbox.isChecked();
        expect(isChecked).toBeTruthy();
        
        // Tab to next checkbox
        await page.keyboard.press('Tab');
        
        // Verify focus moved
        const focusedElement = await page.evaluate(() => document.activeElement.className);
        expect(focusedElement).toContain('task-checkbox');
    });
    
    test('Performance: Widget loads within 2 seconds', async ({ page }) => {
        const startTime = Date.now();
        
        await page.goto(`${BASE_URL}/augmented_teacher/students/today42.php`);
        await waitForWidget(page);
        await page.waitForSelector('.task-item');
        
        const loadTime = Date.now() - startTime;
        
        // Widget should load within 2 seconds
        expect(loadTime).toBeLessThan(2000);
        
        // Log performance metrics
        const metrics = await page.evaluate(() => {
            const perf = performance.getEntriesByType('navigation')[0];
            return {
                domContentLoaded: perf.domContentLoadedEventEnd - perf.domContentLoadedEventStart,
                loadComplete: perf.loadEventEnd - perf.loadEventStart
            };
        });
        
        console.log('Performance metrics:', metrics);
    });
});

test.describe('Schedule Integration E2E Tests', () => {
    
    test('Exam detection from pinned schedule', async ({ page }) => {
        // Navigate to schedule page
        await page.goto(`${BASE_URL}/students/schedule42.php`);
        
        // Create/update schedule with exam info
        await page.fill('#memo', '3월 중간고사 2024-03-31');
        await page.check('#pinned');
        await page.click('#save-schedule');
        
        // Wait for save confirmation
        await page.waitForTimeout(1000);
        
        // Check if routine coach detected exam
        const notification = await page.$('.notification-success');
        if (notification) {
            const text = await notification.textContent();
            expect(text).toContain('시험 일정이 자동으로 등록되었습니다');
        }
    });
    
    test('Manual exam registration popup', async ({ page }) => {
        await page.goto(`${BASE_URL}/students/schedule42.php`);
        
        // Create schedule without clear exam info
        await page.fill('#memo', 'Study session');
        await page.click('#save-schedule');
        
        // Wait for popup
        await page.waitForSelector('#exam-registration-popup', { 
            state: 'visible',
            timeout: 5000 
        });
        
        // Fill exam details
        await page.fill('#exam-label', '4월 모의고사');
        await page.fill('#exam-date', '2024-04-15');
        await page.selectOption('#exam-type', '모의고사');
        
        // Submit
        await page.click('#exam-registration-form button[type="submit"]');
        
        // Verify success
        await page.waitForTimeout(1000);
        const successNotification = await page.$('.notification-success');
        expect(successNotification).toBeTruthy();
    });
});