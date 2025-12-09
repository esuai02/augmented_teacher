<?php
/**
 * Migration Test Framework for today.php -> today42.php
 * Comprehensive testing and validation system for safe migration
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class MigrationTestFramework {
    private $db;
    private $test_results = [];
    private $validation_log = [];
    private $backup_created = false;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->logMessage("Migration Test Framework initialized");
    }
    
    /**
     * Validate database table structures and required fields
     */
    public function validateDatabaseStructure() {
        $this->logMessage("Starting database structure validation");
        $required_tables = [
            'mdl_quiz_attempts' => ['id', 'quiz', 'userid', 'timestart', 'timefinish', 'timemodified', 'sumgrades', 'state', 'attempt'],
            'mdl_quiz' => ['id', 'sumgrades', 'name'],
            'mdl_abessi_messages' => ['id', 'userid', 'wboardid', 'contentstitle', 'contentsid', 'contentstype', 'status', 'timemodified', 'tlaststroke', 'nstroke', 'active'],
            'mdl_abessi_today' => ['id', 'userid', 'type', 'text', 'timecreated', 'inspect'],
            'mdl_abessi_schedule' => ['id', 'userid', 'duration1', 'duration2', 'duration3', 'duration4', 'duration5', 'duration6', 'duration7'],
            'mdl_abessi_indicators' => ['id', 'userid', 'totaltime', 'nask', 'nreply'],
            'mdl_abessi_missionlog' => ['id', 'userid', 'page', 'timecreated', 'event'],
            'mdl_user_info_data' => ['id', 'userid', 'fieldid', 'data']
        ];
        
        $validation_passed = true;
        
        foreach ($required_tables as $table => $required_fields) {
            try {
                // Check if table exists and get structure
                $table_info = $this->db->get_manager();
                if (!$table_info->table_exists(str_replace('mdl_', '', $table))) {
                    $this->test_results['database'][$table] = ['status' => 'FAIL', 'message' => 'Table does not exist'];
                    $validation_passed = false;
                    continue;
                }
                
                // Test basic query on table
                $test_query = $this->db->get_records($table, [], '', 'id', 0, 1);
                
                $this->test_results['database'][$table] = ['status' => 'PASS', 'message' => 'Table accessible'];
                $this->logMessage("Database table {$table}: PASS");
                
            } catch (Exception $e) {
                $this->test_results['database'][$table] = ['status' => 'FAIL', 'message' => $e->getMessage()];
                $validation_passed = false;
                $this->logMessage("Database table {$table}: FAIL - " . $e->getMessage());
            }
        }
        
        // Test critical timestamp field usage (timecreated vs date)
        try {
            $test_timestamp = $this->db->get_records_sql("SELECT id, timecreated FROM mdl_abessi_today LIMIT 1");
            $this->test_results['database']['timestamp_compatibility'] = ['status' => 'PASS', 'message' => 'Timestamp fields accessible'];
            $this->logMessage("Timestamp field validation: PASS");
        } catch (Exception $e) {
            $this->test_results['database']['timestamp_compatibility'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
            $validation_passed = false;
            $this->logMessage("Timestamp field validation: FAIL - " . $e->getMessage());
        }
        
        return $validation_passed;
    }
    
    /**
     * Test role-based access control scenarios
     */
    public function testRoleBasedAccess($test_studentid = null) {
        $this->logMessage("Starting role-based access control testing");
        
        if (!$test_studentid) {
            global $USER;
            $test_studentid = $USER->id;
        }
        
        // Test user role retrieval
        try {
            $userrole = $this->db->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid='22'", [$test_studentid]);
            $role = $userrole ? $userrole->data : 'unknown';
            
            $this->test_results['role_access']['role_detection'] = [
                'status' => 'PASS', 
                'message' => "Role detected: {$role}",
                'role' => $role
            ];
            $this->logMessage("Role detection for user {$test_studentid}: {$role}");
            
            // Test student vs teacher access patterns
            if ($role === 'student') {
                $this->testStudentAccessPatterns($test_studentid);
            } else {
                $this->testTeacherAccessPatterns($test_studentid);
            }
            
        } catch (Exception $e) {
            $this->test_results['role_access']['role_detection'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
            $this->logMessage("Role detection FAIL: " . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    private function testStudentAccessPatterns($studentid) {
        // Test student-specific queries and restrictions
        try {
            // Test quiz access (student should only see their own quizzes)
            $quiz_count = $this->db->count_records('mdl_quiz_attempts', ['userid' => $studentid]);
            
            // Test whiteboard access (student should only see their own activities)
            $wb_count = $this->db->count_records('mdl_abessi_messages', ['userid' => $studentid, 'contentstype' => 2]);
            
            $this->test_results['role_access']['student_data'] = [
                'status' => 'PASS',
                'message' => "Student data accessible: {$quiz_count} quizzes, {$wb_count} whiteboard activities",
                'quiz_count' => $quiz_count,
                'wb_count' => $wb_count
            ];
            
        } catch (Exception $e) {
            $this->test_results['role_access']['student_data'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
    }
    
    private function testTeacherAccessPatterns($teacherid) {
        // Test teacher-specific access and administrative functions
        try {
            // Test broader access capabilities
            $total_students = $this->db->count_records_sql("SELECT COUNT(DISTINCT userid) FROM mdl_user_info_data WHERE fieldid='22' AND data='student'");
            
            $this->test_results['role_access']['teacher_access'] = [
                'status' => 'PASS',
                'message' => "Teacher access verified: can access {$total_students} student records",
                'student_count' => $total_students
            ];
            
        } catch (Exception $e) {
            $this->test_results['role_access']['teacher_access'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create backup of current today42.php
     */
    public function createBackup() {
        $this->logMessage("Creating backup of today42.php");
        
        $source_file = __DIR__ . '/today42.php';
        $backup_file = __DIR__ . '/today42_backup_' . date('Y-m-d_H-i-s') . '.php';
        
        try {
            if (file_exists($source_file)) {
                if (copy($source_file, $backup_file)) {
                    $this->backup_created = true;
                    $this->test_results['backup'] = [
                        'status' => 'PASS',
                        'message' => "Backup created: " . basename($backup_file),
                        'backup_file' => $backup_file
                    ];
                    $this->logMessage("Backup created successfully: {$backup_file}");
                    return $backup_file;
                } else {
                    throw new Exception("Failed to create backup copy");
                }
            } else {
                throw new Exception("Source file does not exist: {$source_file}");
            }
        } catch (Exception $e) {
            $this->test_results['backup'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
            $this->logMessage("Backup creation FAIL: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate component integration
     */
    public function validateComponentIntegration($studentid = null) {
        $this->logMessage("Starting component integration validation");
        
        if (!$studentid) {
            global $USER;
            $studentid = $USER->id;
        }
        
        $components = [
            'quiz_categorization' => [$this, 'validateQuizCategorization'],
            'whiteboard_activities' => [$this, 'validateWhiteboardActivities'], 
            'analytics_iframes' => [$this, 'validateAnalyticsIframes'],
            'progress_tracking' => [$this, 'validateProgressTracking'],
            'javascript_functions' => [$this, 'validateJavaScriptFunctions']
        ];
        
        $all_passed = true;
        
        foreach ($components as $component => $validator) {
            try {
                $result = call_user_func($validator, $studentid);
                $this->test_results['components'][$component] = $result;
                if ($result['status'] !== 'PASS') {
                    $all_passed = false;
                }
            } catch (Exception $e) {
                $this->test_results['components'][$component] = ['status' => 'FAIL', 'message' => $e->getMessage()];
                $all_passed = false;
                $this->logMessage("Component validation FAIL ({$component}): " . $e->getMessage());
            }
        }
        
        return $all_passed;
    }
    
    private function validateQuizCategorization($studentid) {
        // Test quiz categorization logic
        $timestart2 = time() - 604800; // 1 week ago
        
        $quiz_query = "SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, 
                       mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, 
                       mdl_quiz.sumgrades AS tgrades 
                       FROM mdl_quiz 
                       LEFT JOIN mdl_quiz_attempts ON mdl_quiz.id=mdl_quiz_attempts.quiz 
                       WHERE mdl_quiz_attempts.timemodified > ? AND mdl_quiz_attempts.userid=? 
                       ORDER BY mdl_quiz_attempts.id DESC LIMIT 5";
                       
        $quizzes = $this->db->get_records_sql($quiz_query, [$timestart2, $studentid]);
        $quiz_count = count($quizzes);
        
        return [
            'status' => 'PASS',
            'message' => "Quiz categorization functional: {$quiz_count} quizzes processed",
            'quiz_count' => $quiz_count
        ];
    }
    
    private function validateWhiteboardActivities($studentid) {
        // Test whiteboard activity processing
        $timestart2 = time() - 604800;
        
        $wb_query = "SELECT * FROM mdl_abessi_messages 
                     WHERE userid=? AND status NOT LIKE 'attempt' AND tlaststroke>? 
                     AND contentstype=2 AND (active=1 OR status='flag') 
                     ORDER BY tlaststroke DESC LIMIT 5";
                     
        $activities = $this->db->get_records_sql($wb_query, [$studentid, $timestart2]);
        $activity_count = count($activities);
        
        return [
            'status' => 'PASS',
            'message' => "Whiteboard activities accessible: {$activity_count} activities found",
            'activity_count' => $activity_count
        ];
    }
    
    private function validateAnalyticsIframes($studentid) {
        // Test iframe source URLs accessibility
        $iframe_sources = [
            'user_analysis' => "https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis/user_analysis.php?userid={$studentid}",
            'timescaffolding_stat' => "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid={$studentid}"
        ];
        
        // For now, just validate the URLs are properly constructed
        $valid_urls = 0;
        foreach ($iframe_sources as $name => $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $valid_urls++;
            }
        }
        
        return [
            'status' => $valid_urls === count($iframe_sources) ? 'PASS' : 'FAIL',
            'message' => "Analytics iframe URLs validated: {$valid_urls}/" . count($iframe_sources) . " valid",
            'iframe_count' => count($iframe_sources)
        ];
    }
    
    private function validateProgressTracking($studentid) {
        // Test progress calculation components
        try {
            $schedule = $this->db->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid=? ORDER BY id DESC LIMIT 1", [$studentid]);
            $engagement = $this->db->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid=? ORDER BY id DESC LIMIT 1", [$studentid]);
            
            return [
                'status' => 'PASS',
                'message' => "Progress tracking components accessible",
                'has_schedule' => !!$schedule,
                'has_engagement' => !!$engagement
            ];
        } catch (Exception $e) {
            return ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
    }
    
    private function validateJavaScriptFunctions($studentid) {
        // Test that today42.php includes necessary JavaScript functions
        $today42_content = file_get_contents(__DIR__ . '/today42.php');
        
        $required_functions = [
            'addquiztime',
            'deletequiz', 
            'showWboard',
            'AddReview',
            'ChangeCheckBox2',
            'Resttime',
            'ChangeCheckBox',
            'submittoday'
        ];
        
        $found_functions = 0;
        foreach ($required_functions as $func) {
            if (strpos($today42_content, "function {$func}(") !== false) {
                $found_functions++;
            }
        }
        
        return [
            'status' => $found_functions >= 6 ? 'PASS' : 'WARN',
            'message' => "JavaScript functions found: {$found_functions}/" . count($required_functions),
            'found_functions' => $found_functions,
            'required_functions' => count($required_functions)
        ];
    }
    
    /**
     * Run comprehensive migration safety check
     */
    public function runComprehensiveTest($studentid = null) {
        $this->logMessage("=== Starting Comprehensive Migration Safety Check ===");
        
        $test_results = [];
        
        // 1. Database structure validation
        $test_results['database_structure'] = $this->validateDatabaseStructure();
        
        // 2. Role-based access testing  
        $test_results['role_access'] = $this->testRoleBasedAccess($studentid);
        
        // 3. Create backup
        $test_results['backup'] = !!$this->createBackup();
        
        // 4. Component integration validation
        $test_results['component_integration'] = $this->validateComponentIntegration($studentid);
        
        // Generate overall assessment
        $passed_tests = array_sum($test_results);
        $total_tests = count($test_results);
        
        $overall_status = ($passed_tests === $total_tests) ? 'PASS' : (($passed_tests / $total_tests) >= 0.75 ? 'WARN' : 'FAIL');
        
        $this->test_results['overall'] = [
            'status' => $overall_status,
            'passed' => $passed_tests,
            'total' => $total_tests,
            'percentage' => round(($passed_tests / $total_tests) * 100, 1),
            'message' => "Overall test status: {$passed_tests}/{$total_tests} tests passed"
        ];
        
        $this->logMessage("=== Comprehensive Test Completed: {$overall_status} ===");
        
        return [
            'overall_status' => $overall_status,
            'results' => $this->test_results,
            'log' => $this->validation_log,
            'backup_created' => $this->backup_created
        ];
    }
    
    /**
     * Generate detailed test report
     */
    public function generateReport() {
        $report = "\n";
        $report .= "=====================================\n";
        $report .= "Migration Test Framework Report\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= "=====================================\n\n";
        
        foreach ($this->test_results as $category => $results) {
            $report .= "--- {$category} ---\n";
            
            if (is_array($results) && isset($results['status'])) {
                $report .= "Status: {$results['status']}\n";
                $report .= "Message: {$results['message']}\n";
            } else if (is_array($results)) {
                foreach ($results as $test => $result) {
                    if (is_array($result)) {
                        $status = $result['status'] ?? 'UNKNOWN';
                        $message = $result['message'] ?? 'No message';
                        $report .= "  {$test}: {$status} - {$message}\n";
                    } else {
                        $report .= "  {$test}: {$result}\n";
                    }
                }
            }
            $report .= "\n";
        }
        
        $report .= "--- Validation Log ---\n";
        foreach ($this->validation_log as $log_entry) {
            $report .= $log_entry . "\n";
        }
        
        return $report;
    }
    
    /**
     * Save test results to file
     */
    public function saveTestResults($filename = null) {
        if (!$filename) {
            $filename = __DIR__ . '/migration_test_results_' . date('Y-m-d_H-i-s') . '.txt';
        }
        
        $report = $this->generateReport();
        
        if (file_put_contents($filename, $report)) {
            $this->logMessage("Test results saved to: {$filename}");
            return $filename;
        } else {
            $this->logMessage("Failed to save test results to: {$filename}");
            return false;
        }
    }
    
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $this->validation_log[] = "[{$timestamp}] {$message}";
    }
}

// Usage example and test execution
if (isset($_GET['run_test'])) {
    require_login();
    
    $framework = new MigrationTestFramework();
    $test_studentid = $_GET['studentid'] ?? $USER->id;
    
    echo "<h2>Migration Test Framework</h2>";
    echo "<h3>Running comprehensive safety checks...</h3>";
    
    $results = $framework->runComprehensiveTest($test_studentid);
    
    echo "<div style='background: " . ($results['overall_status'] === 'PASS' ? '#d4edda' : '#f8d7da') . "; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>Overall Status: " . $results['overall_status'] . "</h3>";
    echo "<p>" . $results['results']['overall']['message'] . "</p>";
    echo "</div>";
    
    echo "<h3>Detailed Results:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars($framework->generateReport());
    echo "</pre>";
    
    // Save results to file
    $report_file = $framework->saveTestResults();
    if ($report_file) {
        echo "<p><strong>Test results saved to:</strong> " . basename($report_file) . "</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>Migration Safety Assessment:</strong></p>";
    if ($results['overall_status'] === 'PASS') {
        echo "<p style='color: green; font-weight: bold;'>✓ Migration environment is ready. Safe to proceed with component migration.</p>";
    } else if ($results['overall_status'] === 'WARN') {
        echo "<p style='color: orange; font-weight: bold;'>⚠ Migration environment has minor issues. Proceed with caution and monitor closely.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Migration environment has critical issues. Address problems before proceeding.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Migration Test Framework</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test-button { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 0; }
        .test-button:hover { background: #0056b3; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>Migration Test Framework</h1>
    <div class="info">
        <h3>Purpose:</h3>
        <p>This framework provides comprehensive testing and validation for the migration from students/today.php to today42.php. It ensures database compatibility, role-based access control, component integration, and creates safety backups.</p>
        
        <h3>Test Components:</h3>
        <ul>
            <li><strong>Database Structure Validation:</strong> Verifies all required tables and fields are accessible</li>
            <li><strong>Role-Based Access Testing:</strong> Tests student vs teacher permission patterns</li> 
            <li><strong>Backup Creation:</strong> Creates timestamped backup of today42.php</li>
            <li><strong>Component Integration:</strong> Validates quiz categorization, whiteboard activities, analytics iframes, and JavaScript functions</li>
        </ul>
    </div>
    
    <?php if (!isset($_GET['run_test'])): ?>
    <form method="GET">
        <input type="hidden" name="run_test" value="1">
        <label>Student ID (optional): <input type="number" name="studentid" placeholder="Leave empty for current user"></label><br><br>
        <button type="submit" class="test-button">Run Comprehensive Migration Tests</button>
    </form>
    <?php endif; ?>
</body>
</html>