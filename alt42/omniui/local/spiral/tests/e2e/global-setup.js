/**
 * Global Playwright Setup
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const { chromium } = require('@playwright/test');

async function globalSetup() {
  console.log('Setting up global test environment...');
  
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
    // Setup test data or perform initial login
    const baseUrl = process.env.TEST_BASE_URL || 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui';
    
    // Check if test environment is accessible
    const response = await page.goto(`${baseUrl}/login.php`, { 
      waitUntil: 'domcontentloaded',
      timeout: 30000 
    });
    
    if (!response.ok()) {
      throw new Error(`Test environment not accessible: ${response.status()}`);
    }
    
    console.log('Test environment is accessible');
    
    // Setup test database if needed
    await setupTestData(page);
    
    // Save authentication state for faster login in tests
    await saveAuthenticationState(page);
    
    console.log('Global setup completed successfully');
    
  } catch (error) {
    console.error('Global setup failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

async function setupTestData(page) {
  try {
    // Create test users if they don't exist
    const setupUrl = `${process.env.TEST_BASE_URL || 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui'}/tests/setup-test-data.php`;
    
    const response = await page.goto(setupUrl, {
      waitUntil: 'domcontentloaded',
      timeout: 10000
    }).catch(() => null);
    
    if (response && response.ok()) {
      console.log('Test data setup completed');
    } else {
      console.log('Test data setup skipped (endpoint not available)');
    }
  } catch (error) {
    console.log('Test data setup skipped:', error.message);
  }
}

async function saveAuthenticationState(page) {
  try {
    const baseUrl = process.env.TEST_BASE_URL || 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui';
    const teacherUser = process.env.TEST_TEACHER_USER || 'teacher1';
    const teacherPass = process.env.TEST_TEACHER_PASS || 'Teacher123!';
    
    // Login as teacher and save state
    await page.goto(`${baseUrl}/login.php`);
    
    if (await page.locator('#username').isVisible()) {
      await page.fill('#username', teacherUser);
      await page.fill('#password', teacherPass);
      await page.click('#loginbtn');
      
      // Wait for successful login
      await page.waitForURL(/.*\/(?!login).*/, { timeout: 10000 });
      
      // Save the signed-in state
      await page.context().storageState({ path: './e2e/teacher-auth.json' });
      
      console.log('Teacher authentication state saved');
    }
  } catch (error) {
    console.log('Authentication state save skipped:', error.message);
  }
}

module.exports = globalSetup;