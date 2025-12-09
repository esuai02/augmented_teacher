/**
 * Playwright Configuration for E2E Tests
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  // Test directory
  testDir: './e2e',
  
  // Run tests in files in parallel
  fullyParallel: false,
  
  // Fail the build on CI if you accidentally left test.only in the source code
  forbidOnly: !!process.env.CI,
  
  // Retry on CI only
  retries: process.env.CI ? 2 : 0,
  
  // Opt out of parallel tests on CI
  workers: process.env.CI ? 1 : undefined,
  
  // Reporter to use
  reporter: [
    ['html', { outputFolder: 'coverage/playwright-report' }],
    ['junit', { outputFile: 'coverage/playwright-results.xml' }],
    ['json', { outputFile: 'coverage/playwright-results.json' }]
  ],
  
  // Shared settings for all the projects below
  use: {
    // Base URL for tests
    baseURL: process.env.TEST_BASE_URL || 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui',
    
    // Browser context options
    viewport: { width: 1280, height: 720 },
    ignoreHTTPSErrors: true,
    
    // Collect trace when retrying the failed test
    trace: 'on-first-retry',
    
    // Record video on failure
    video: 'retain-on-failure',
    
    // Take screenshots on failure
    screenshot: 'only-on-failure',
    
    // Global timeout
    actionTimeout: 10000,
    navigationTimeout: 30000,
  },
  
  // Global test timeout
  timeout: 60000,
  
  // Expect timeout
  expect: {
    timeout: 5000
  },
  
  // Configure projects for major browsers
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    
    // Mobile testing
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
    
    // Tablet testing
    {
      name: 'iPad',
      use: { ...devices['iPad Pro'] },
    }
  ],
  
  // Global setup and teardown
  globalSetup: require.resolve('./e2e/global-setup.js'),
  globalTeardown: require.resolve('./e2e/global-teardown.js'),
  
  // Web server to start before tests
  webServer: process.env.CI ? undefined : {
    command: 'php -S localhost:8000 -t ../../..',
    port: 8000,
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
  },
  
  // Output directories
  outputDir: 'coverage/test-results',
});