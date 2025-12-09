/**
 * Global Playwright Teardown
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const fs = require('fs');
const path = require('path');

async function globalTeardown() {
  console.log('Cleaning up global test environment...');
  
  try {
    // Clean up authentication files
    const authFiles = [
      './e2e/teacher-auth.json',
      './e2e/student-auth.json'
    ];
    
    for (const file of authFiles) {
      if (fs.existsSync(file)) {
        fs.unlinkSync(file);
        console.log(`Removed ${file}`);
      }
    }
    
    // Clean up test data if needed
    await cleanupTestData();
    
    console.log('Global teardown completed successfully');
    
  } catch (error) {
    console.error('Global teardown failed:', error);
    // Don't throw - teardown failures shouldn't fail the test run
  }
}

async function cleanupTestData() {
  try {
    // If you have test data cleanup endpoint
    const cleanupUrl = `${process.env.TEST_BASE_URL || 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui'}/tests/cleanup-test-data.php`;
    
    const { chromium } = require('@playwright/test');
    const browser = await chromium.launch();
    const page = await browser.newPage();
    
    const response = await page.goto(cleanupUrl, {
      waitUntil: 'domcontentloaded',
      timeout: 10000
    }).catch(() => null);
    
    if (response && response.ok()) {
      console.log('Test data cleanup completed');
    }
    
    await browser.close();
    
  } catch (error) {
    console.log('Test data cleanup skipped:', error.message);
  }
}

module.exports = globalTeardown;