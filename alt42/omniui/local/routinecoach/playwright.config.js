/**
 * Playwright configuration for E2E tests
 * 
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 */

const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    // Test directory
    testDir: './tests/e2e',
    
    // Test match pattern
    testMatch: '**/*.e2e.js',
    
    // Maximum time one test can run
    timeout: 30 * 1000,
    
    // Maximum time to wait for page load
    expect: {
        timeout: 5000
    },
    
    // Run tests in parallel
    fullyParallel: true,
    
    // Fail the build on CI if you accidentally left test.only
    forbidOnly: !!process.env.CI,
    
    // Retry failed tests
    retries: process.env.CI ? 2 : 0,
    
    // Number of workers
    workers: process.env.CI ? 1 : undefined,
    
    // Reporter configuration
    reporter: [
        ['html', { outputFolder: 'tests/playwright-report' }],
        ['json', { outputFile: 'tests/test-results.json' }],
        ['junit', { outputFile: 'tests/junit.xml' }],
        ['list']
    ],
    
    // Global test settings
    use: {
        // Base URL
        baseURL: process.env.MOODLE_URL || 'http://localhost:8080',
        
        // Collect trace when retrying failed test
        trace: 'on-first-retry',
        
        // Screenshot on failure
        screenshot: 'only-on-failure',
        
        // Video on failure
        video: 'retain-on-failure',
        
        // Viewport size
        viewport: { width: 1280, height: 720 },
        
        // Ignore HTTPS errors
        ignoreHTTPSErrors: true,
        
        // Locale
        locale: 'ko-KR',
        
        // Timezone
        timezoneId: 'Asia/Seoul'
    },
    
    // Configure projects for different browsers
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] }
        },
        
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] }
        },
        
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] }
        },
        
        // Mobile viewports
        {
            name: 'Mobile Chrome',
            use: { ...devices['Pixel 5'] }
        },
        
        {
            name: 'Mobile Safari',
            use: { ...devices['iPhone 12'] }
        }
    ],
    
    // Run local dev server before tests
    webServer: process.env.CI ? undefined : {
        command: 'php -S localhost:8080 -t /home/moodle/public_html/moodle',
        port: 8080,
        reuseExistingServer: true
    }
});