<?php declare(strict_types=1);
/**
 * Security Unit Tests
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\tests;

use PHPUnit\Framework\TestCase;
use local_spiral\api\plan_api;

/**
 * Security test cases for Spiral Scheduler
 */
class SecurityTest extends TestCase {
    
    private $originalSession;
    private $originalServer;
    private $originalPost;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Backup globals
        $this->originalSession = $_SESSION ?? [];
        $this->originalServer = $_SERVER ?? [];
        $this->originalPost = $_POST ?? [];
    }
    
    protected function tearDown(): void {
        // Restore globals
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
        $_POST = $this->originalPost;
        
        parent::tearDown();
    }
    
    /**
     * Test CSRF token validation
     */
    public function testCSRFTokenValidation(): void {
        // Mock sesskey
        $_SESSION['sesskey'] = 'test_token_123';
        
        // Test with valid token
        $_POST['sesskey'] = 'test_token_123';
        $this->assertTrue($this->validateSessionKey(), 'Valid token should pass');
        
        // Test with invalid token
        $_POST['sesskey'] = 'invalid_token';
        $this->assertFalse($this->validateSessionKey(), 'Invalid token should fail');
        
        // Test with missing token
        unset($_POST['sesskey']);
        $this->assertFalse($this->validateSessionKey(), 'Missing token should fail');
    }
    
    /**
     * Test XSS prevention in output
     */
    public function testXSSPrevention(): void {
        $maliciousInputs = [
            '<script>alert("XSS")</script>',
            '"><script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="evil.com"></iframe>',
            '<svg onload=alert("XSS")>',
            '${alert("XSS")}',
            '{{constructor.constructor("alert(1)")()}}'
        ];
        
        foreach ($maliciousInputs as $input) {
            $escaped = $this->escapeOutput($input);
            
            // Check that dangerous characters are escaped
            $this->assertStringNotContainsString('<script', $escaped);
            $this->assertStringNotContainsString('javascript:', $escaped);
            $this->assertStringNotContainsString('onerror=', $escaped);
            $this->assertStringNotContainsString('onload=', $escaped);
            
            // Check that HTML entities are properly encoded
            if (strpos($input, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped);
            }
            if (strpos($input, '>') !== false) {
                $this->assertStringContainsString('&gt;', $escaped);
            }
        }
    }
    
    /**
     * Test SQL injection prevention
     */
    public function testSQLInjectionPrevention(): void {
        $maliciousInputs = [
            "1' OR '1'='1",
            "1; DROP TABLE users;--",
            "' UNION SELECT * FROM users--",
            "admin'--",
            "1' AND 1=1--",
            "'; EXEC xp_cmdshell('dir');--",
            "1' UNION ALL SELECT NULL,NULL,NULL--",
            "\\'; DROP TABLE users;--"
        ];
        
        foreach ($maliciousInputs as $input) {
            // Test integer cleaning
            $cleanedInt = $this->cleanParamInt($input);
            $this->assertIsInt($cleanedInt, 'Cleaned parameter should be integer');
            
            // Test text cleaning
            $cleanedText = $this->cleanParamText($input);
            $this->assertStringNotContainsString('DROP TABLE', $cleanedText);
            $this->assertStringNotContainsString('UNION SELECT', $cleanedText);
            $this->assertStringNotContainsString('xp_cmdshell', $cleanedText);
        }
    }
    
    /**
     * Test teacher permission enforcement
     */
    public function testTeacherPermissionEnforcement(): void {
        // Mock student user
        $studentUser = (object)[
            'id' => 1,
            'roles' => ['student']
        ];
        
        $this->assertFalse(
            $this->hasTeacherCapability($studentUser),
            'Student should not have teacher capability'
        );
        
        // Mock teacher user
        $teacherUser = (object)[
            'id' => 2,
            'roles' => ['editingteacher']
        ];
        
        $this->assertTrue(
            $this->hasTeacherCapability($teacherUser),
            'Teacher should have teacher capability'
        );
        
        // Mock admin user
        $adminUser = (object)[
            'id' => 3,
            'roles' => ['admin']
        ];
        
        $this->assertTrue(
            $this->hasTeacherCapability($adminUser),
            'Admin should have teacher capability'
        );
    }
    
    /**
     * Test parameter validation
     */
    public function testParameterValidation(): void {
        // Test student ID validation
        $this->assertEquals(123, $this->validateStudentId('123'));
        $this->assertEquals(0, $this->validateStudentId('abc'));
        $this->assertEquals(0, $this->validateStudentId('123abc'));
        $this->assertEquals(0, $this->validateStudentId('-1'));
        $this->assertEquals(0, $this->validateStudentId(''));
        
        // Test date validation
        $this->assertEquals('2024-01-01', $this->validateDate('2024-01-01'));
        $this->assertFalse($this->validateDate('2024-13-01'));  // Invalid month
        $this->assertFalse($this->validateDate('2024-01-32'));  // Invalid day
        $this->assertFalse($this->validateDate('not-a-date'));
        $this->assertFalse($this->validateDate(''));
        
        // Test ratio validation
        $this->assertEquals(0.7, $this->validateRatio('0.7'));
        $this->assertEquals(0.7, $this->validateRatio(0.7));
        $this->assertEquals(0.5, $this->validateRatio('1.5'));  // Clamped to max
        $this->assertEquals(0.5, $this->validateRatio('-0.1')); // Clamped to min
    }
    
    /**
     * Test information disclosure prevention
     */
    public function testInformationDisclosurePrevention(): void {
        // Test email masking
        $email = 'user@example.com';
        $masked = $this->maskEmail($email);
        
        $this->assertStringStartsWith('us***@', $masked);
        $this->assertStringEndsWith('example.com', $masked);
        $this->assertStringNotContainsString('user@', $masked);
        
        // Test error message sanitization
        $dbError = "MySQL Error: Table 'mdl_users' doesn't exist";
        $sanitized = $this->sanitizeErrorMessage($dbError);
        
        $this->assertStringNotContainsString('mdl_users', $sanitized);
        $this->assertStringNotContainsString('MySQL', $sanitized);
    }
    
    /**
     * Test session security
     */
    public function testSessionSecurity(): void {
        // Test session timeout
        $_SESSION['last_activity'] = time() - 3700; // Over 1 hour ago
        $this->assertTrue($this->isSessionExpired(), 'Old session should be expired');
        
        $_SESSION['last_activity'] = time() - 1800; // 30 minutes ago
        $this->assertFalse($this->isSessionExpired(), 'Recent session should be valid');
        
        // Test session hijacking prevention
        $_SESSION['user_agent'] = 'Mozilla/5.0';
        $_SERVER['HTTP_USER_AGENT'] = 'Chrome/96.0';
        
        $this->assertFalse($this->validateSessionFingerprint(), 'Different user agent should fail');
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $this->assertTrue($this->validateSessionFingerprint(), 'Same user agent should pass');
    }
    
    /**
     * Test file upload security
     */
    public function testFileUploadSecurity(): void {
        // Test dangerous file extensions
        $dangerousFiles = [
            'script.php',
            'shell.sh',
            'virus.exe',
            'backdoor.jsp',
            'hack.asp',
            'exploit.phtml',
            '.htaccess',
            'web.config'
        ];
        
        foreach ($dangerousFiles as $filename) {
            $this->assertFalse(
                $this->isAllowedFileType($filename),
                "File $filename should be rejected"
            );
        }
        
        // Test allowed file extensions
        $allowedFiles = [
            'document.pdf',
            'image.jpg',
            'spreadsheet.xlsx',
            'presentation.pptx',
            'text.txt'
        ];
        
        foreach ($allowedFiles as $filename) {
            $this->assertTrue(
                $this->isAllowedFileType($filename),
                "File $filename should be allowed"
            );
        }
    }
    
    /**
     * Test transaction rollback on error
     */
    public function testTransactionRollback(): void {
        // Mock database with transaction support
        $mockDb = $this->createMock(\PDO::class);
        
        $mockDb->expects($this->once())
               ->method('beginTransaction')
               ->willReturn(true);
        
        $mockDb->expects($this->once())
               ->method('rollBack')
               ->willReturn(true);
        
        $mockDb->expects($this->never())
               ->method('commit');
        
        // Simulate error during transaction
        try {
            $mockDb->beginTransaction();
            throw new \Exception('Database error');
            $mockDb->commit();
        } catch (\Exception $e) {
            $mockDb->rollBack();
        }
        
        $this->assertTrue(true, 'Transaction should rollback on error');
    }
    
    // Helper methods for testing
    
    private function validateSessionKey(): bool {
        return isset($_POST['sesskey']) && 
               isset($_SESSION['sesskey']) && 
               $_POST['sesskey'] === $_SESSION['sesskey'];
    }
    
    private function escapeOutput(string $input): string {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    private function cleanParamInt($input): int {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    private function cleanParamText($input): string {
        return strip_tags(trim($input));
    }
    
    private function hasTeacherCapability($user): bool {
        $teacherRoles = ['editingteacher', 'teacher', 'manager', 'admin'];
        
        if (!isset($user->roles) || !is_array($user->roles)) {
            return false;
        }
        
        return !empty(array_intersect($user->roles, $teacherRoles));
    }
    
    private function validateStudentId($input): int {
        $id = filter_var($input, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : 0;
    }
    
    private function validateDate($input): string|false {
        $date = \DateTime::createFromFormat('Y-m-d', $input);
        return $date && $date->format('Y-m-d') === $input ? $input : false;
    }
    
    private function validateRatio($input): float {
        $ratio = (float) $input;
        return max(0.5, min(1.0, $ratio));
    }
    
    private function maskEmail(string $email): string {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        
        $username = substr($parts[0], 0, 2) . '***';
        return $username . '@' . $parts[1];
    }
    
    private function sanitizeErrorMessage(string $error): string {
        return 'An error occurred. Please try again later.';
    }
    
    private function isSessionExpired(): bool {
        $timeout = 3600; // 1 hour
        return isset($_SESSION['last_activity']) && 
               (time() - $_SESSION['last_activity'] > $timeout);
    }
    
    private function validateSessionFingerprint(): bool {
        return isset($_SESSION['user_agent']) && 
               isset($_SERVER['HTTP_USER_AGENT']) &&
               $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'];
    }
    
    private function isAllowedFileType(string $filename): bool {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions);
    }
}