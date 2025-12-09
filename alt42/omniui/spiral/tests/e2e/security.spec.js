/**
 * E2E Security Tests
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
const STUDENT_USERNAME = process.env.TEST_STUDENT_USER || 'student1';
const STUDENT_PASSWORD = process.env.TEST_STUDENT_PASS || 'Student123!';

test.describe('Security E2E Tests', () => {
    
    test('CSRF token validation', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        // Navigate to schedule editor
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Get the sesskey from the page
        const sesskey = await page.evaluate(() => {
            return document.querySelector('input[name="sesskey"]')?.value;
        });
        
        expect(sesskey).toBeTruthy();
        
        // Try to make request without CSRF token
        const response = await page.evaluate(async () => {
            const response = await fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    studentid: 123,
                    start_date: '2024-01-01',
                    end_date: '2024-01-31'
                    // Missing sesskey
                })
            });
            return {
                status: response.status,
                ok: response.ok
            };
        });
        
        // Should be rejected
        expect(response.ok).toBeFalsy();
        expect(response.status).toBe(403);
        
        // Try with valid CSRF token
        const validResponse = await page.evaluate(async (token) => {
            const response = await fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    studentid: 123,
                    start_date: '2024-01-01',
                    end_date: '2024-01-31',
                    sesskey: token
                })
            });
            return {
                status: response.status,
                ok: response.ok
            };
        }, sesskey);
        
        // Should be accepted (may fail for other reasons but not CSRF)
        expect(validResponse.status).not.toBe(403);
    });
    
    test('XSS prevention in user inputs', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Try to inject malicious scripts in various inputs
        const xssPayloads = [
            '<script>alert("XSS")</script>',
            '"><script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<svg onload=alert("XSS")>'
        ];
        
        for (const payload of xssPayloads) {
            // Try in search field
            if (await page.locator('#search-unit').isVisible()) {
                await page.fill('#search-unit', payload);
                
                // Trigger search
                await page.keyboard.press('Enter');
                
                // Check no alert dialog appears
                let alertTriggered = false;
                page.on('dialog', () => {
                    alertTriggered = true;
                });
                
                await page.waitForTimeout(1000);
                expect(alertTriggered).toBeFalsy();
                
                // Check the payload is properly escaped in display
                const displayedText = await page.locator('.search-results').textContent();
                expect(displayedText).not.toContain('<script>');
                expect(displayedText).not.toContain('javascript:');
            }
        }
    });
    
    test('SQL injection prevention', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Try SQL injection in student ID parameter
        const sqlPayloads = [
            "1' OR '1'='1",
            "1; DROP TABLE users;--",
            "' UNION SELECT * FROM mdl_user--"
        ];
        
        for (const payload of sqlPayloads) {
            const response = await page.evaluate(async (injection, token) => {
                const response = await fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        studentid: injection,
                        start_date: '2024-01-01',
                        end_date: '2024-01-31',
                        sesskey: token
                    })
                });
                const data = await response.json();
                return {
                    status: response.status,
                    error: data.error
                };
            }, payload, await page.evaluate(() => document.querySelector('input[name="sesskey"]')?.value));
            
            // Should handle gracefully without exposing SQL errors
            expect(response.error).not.toContain('SQL');
            expect(response.error).not.toContain('mysql');
            expect(response.error).not.toContain('DROP TABLE');
        }
    });
    
    test('Teacher permission enforcement', async ({ page }) => {
        // Try to access as student
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', STUDENT_USERNAME);
        await page.fill('#password', STUDENT_PASSWORD);
        await page.click('#loginbtn');
        
        // Try to access teacher-only pages
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Should be redirected or show error
        const url = page.url();
        const pageContent = await page.textContent('body');
        
        expect(
            url.includes('login') || 
            pageContent.includes('권한이 없습니다') ||
            pageContent.includes('Permission denied')
        ).toBeTruthy();
        
        // Try to call teacher-only API directly
        const response = await page.evaluate(async () => {
            const response = await fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    studentid: 123,
                    start_date: '2024-01-01',
                    end_date: '2024-01-31'
                })
            });
            return {
                status: response.status,
                ok: response.ok
            };
        });
        
        // Should be forbidden
        expect(response.status).toBe(403);
    });
    
    test('Session security and timeout', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        // Save session cookie
        const cookies = await page.context().cookies();
        const sessionCookie = cookies.find(c => c.name.includes('sess') || c.name === 'MoodleSession');
        
        expect(sessionCookie).toBeTruthy();
        expect(sessionCookie.httpOnly).toBeTruthy(); // Should be httpOnly
        expect(sessionCookie.secure).toBeTruthy(); // Should be secure on HTTPS
        
        // Test session timeout (simulated)
        await page.evaluate(() => {
            // Simulate long inactivity by modifying last activity time
            if (window.M && window.M.cfg) {
                window.M.cfg.sessiontimeout = 1; // 1 second timeout
            }
        });
        
        // Wait for timeout
        await page.waitForTimeout(2000);
        
        // Try to perform action
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Should require re-login
        const requiresLogin = page.url().includes('login') || 
                             await page.locator('#loginbtn').isVisible();
        
        expect(requiresLogin).toBeTruthy();
    });
    
    test('Information disclosure prevention', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Check that sensitive information is masked
        const studentEmails = await page.locator('.student-email').allTextContents();
        
        for (const email of studentEmails) {
            // Email should be masked (e.g., us***@example.com)
            expect(email).toMatch(/^[a-z]{2}\*\*\*@.+$/);
            expect(email).not.toContain('@gmail.com');
            expect(email).not.toMatch(/^[a-z]+@/); // Full username not exposed
        }
        
        // Check error messages don't expose system info
        const response = await page.evaluate(async (token) => {
            const response = await fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    studentid: 99999999, // Non-existent student
                    start_date: '2024-01-01',
                    end_date: '2024-01-31',
                    sesskey: token
                })
            });
            const data = await response.json();
            return data.error || data.message;
        }, await page.evaluate(() => document.querySelector('input[name="sesskey"]')?.value));
        
        // Error message should be generic
        expect(response).not.toContain('mdl_');
        expect(response).not.toContain('MySQL');
        expect(response).not.toContain('/home/');
        expect(response).not.toContain('localhost');
    });
    
    test('File upload security', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        // Navigate to a page with file upload (if exists)
        await page.goto(`${BASE_URL}/local/spiral/resources.php`);
        
        if (await page.locator('input[type="file"]').isVisible()) {
            // Try to upload dangerous file types
            const dangerousFiles = [
                { name: 'malicious.php', content: '<?php system($_GET["cmd"]); ?>' },
                { name: 'shell.sh', content: '#!/bin/bash\nrm -rf /' },
                { name: 'backdoor.jsp', content: '<%@ page import="java.io.*" %>' }
            ];
            
            for (const file of dangerousFiles) {
                // Create file buffer
                const buffer = Buffer.from(file.content);
                
                // Try to upload
                await page.setInputFiles('input[type="file"]', {
                    name: file.name,
                    mimeType: 'application/octet-stream',
                    buffer: buffer
                });
                
                // Submit form
                if (await page.locator('.upload-btn').isVisible()) {
                    await page.click('.upload-btn');
                    
                    // Check for error message
                    await page.waitForSelector('.error-message, .toast-notification');
                    
                    const errorText = await page.locator('.error-message, .toast-notification').textContent();
                    expect(errorText).toContain('허용되지 않는');
                }
            }
        }
    });
    
    test('Rate limiting and DoS prevention', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        const sesskey = await page.evaluate(() => document.querySelector('input[name="sesskey"]')?.value);
        
        // Make rapid repeated requests
        const responses = await page.evaluate(async (token) => {
            const promises = [];
            
            // Send 20 requests rapidly
            for (let i = 0; i < 20; i++) {
                promises.push(
                    fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_generate_spiral.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            studentid: 123,
                            start_date: '2024-01-01',
                            end_date: '2024-01-31',
                            sesskey: token
                        })
                    }).then(r => ({ status: r.status, time: Date.now() }))
                );
            }
            
            return await Promise.all(promises);
        }, sesskey);
        
        // Check if rate limiting is applied
        const rateLimited = responses.some(r => r.status === 429);
        const slowedDown = responses.slice(-5).some((r, i) => 
            i > 0 && (r.time - responses[responses.length - 6 + i].time) > 1000
        );
        
        expect(rateLimited || slowedDown).toBeTruthy();
    });
    
    test('Content Security Policy', async ({ page }) => {
        // Login as teacher
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Check CSP headers
        const response = await page.goto(`${BASE_URL}/local/spiral/index.php`);
        const headers = response.headers();
        
        // Check for security headers
        expect(headers['x-content-type-options']).toBe('nosniff');
        expect(headers['x-frame-options']).toMatch(/DENY|SAMEORIGIN/);
        
        // Try to inject inline script
        await page.evaluate(() => {
            const script = document.createElement('script');
            script.textContent = 'window.injectedScript = true;';
            document.head.appendChild(script);
        });
        
        // Check if inline script was blocked (if CSP is strict)
        const scriptExecuted = await page.evaluate(() => window.injectedScript);
        
        // If CSP is properly configured, inline scripts should be blocked
        // Note: This may vary based on Moodle configuration
        console.log('Inline script execution:', scriptExecuted ? 'allowed' : 'blocked');
    });
    
    test('Authentication bypass attempts', async ({ page }) => {
        // Try to access protected page without login
        const response1 = await page.goto(`${BASE_URL}/local/spiral/index.php`, {
            waitUntil: 'domcontentloaded'
        });
        
        // Should redirect to login
        expect(page.url()).toContain('login');
        
        // Try to manipulate cookies
        await page.context().addCookies([{
            name: 'MoodleSession',
            value: 'fake_session_id',
            domain: new URL(BASE_URL).hostname,
            path: '/'
        }]);
        
        const response2 = await page.goto(`${BASE_URL}/local/spiral/index.php`);
        
        // Should still require login
        expect(page.url()).toContain('login');
        
        // Try to access with manipulated user ID in request
        await page.goto(`${BASE_URL}/login.php`);
        await page.fill('#username', TEACHER_USERNAME);
        await page.fill('#password', TEACHER_PASSWORD);
        await page.click('#loginbtn');
        
        // Try to impersonate another user
        const impersonationResponse = await page.evaluate(async (token) => {
            const response = await fetch('/local/augmented_teacher/alt42/omniui/spiral/api/ajax_publish_schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    schedule_id: 1,
                    teacher_id: 99999, // Try to override teacher ID
                    sesskey: token
                })
            });
            return await response.json();
        }, await page.evaluate(() => document.querySelector('input[name="sesskey"]')?.value));
        
        // Should not allow teacher ID override
        expect(impersonationResponse.error).toBeTruthy();
    });
});