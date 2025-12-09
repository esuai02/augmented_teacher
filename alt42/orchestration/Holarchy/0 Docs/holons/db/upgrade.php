<?php
/**
 * Database upgrade script for Quantum A/B Testing Framework
 * Phase 11.1: Database Integration
 *
 * This file handles database schema migrations for the A/B testing tables.
 * Can be executed standalone or integrated with Moodle's upgrade system.
 *
 * Usage:
 *   1. Web: https://mathking.kr/.../holons/db/upgrade.php?action=install
 *   2. CLI: php upgrade.php install
 *
 * @package    local_augmented_teacher
 * @subpackage holons
 * @copyright  2025 Hyperial Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle integration
$configPath = "/home/moodle/public_html/moodle/config.php";
if (file_exists($configPath)) {
    include_once($configPath);
    global $DB, $USER, $CFG;
} else {
    die("Error: Moodle config not found at " . $configPath . " [" . __FILE__ . ":" . __LINE__ . "]");
}

// Version tracking
define('QUANTUM_AB_VERSION', 2025120901);
define('QUANTUM_AB_RELEASE', '1.0.0');

/**
 * Main upgrade controller class
 */
class QuantumABUpgradeManager {

    private $db;
    private $prefix;
    private $results = [];

    public function __construct() {
        global $DB, $CFG;
        $this->db = $DB;
        $this->prefix = $CFG->prefix ?? 'mdl_';
    }

    /**
     * Execute database installation/upgrade
     * @param string $action 'install', 'upgrade', 'check', 'drop'
     * @return array Results of the operation
     */
    public function execute($action = 'install') {
        $this->log("Starting quantum A/B testing database $action...");
        $this->log("Version: " . QUANTUM_AB_RELEASE . " (" . QUANTUM_AB_VERSION . ")");

        switch ($action) {
            case 'install':
                return $this->install();
            case 'upgrade':
                return $this->upgrade();
            case 'check':
                return $this->check();
            case 'drop':
                return $this->dropTables();
            default:
                $this->log("Unknown action: $action", 'error');
                return ['success' => false, 'error' => 'Unknown action'];
        }
    }

    /**
     * Install all tables
     */
    private function install() {
        $tables = $this->getTableDefinitions();
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($tables as $tableName => $sql) {
            $fullName = $this->prefix . $tableName;

            if ($this->tableExists($tableName)) {
                $this->log("Table $fullName already exists - skipped", 'info');
                $skipped++;
                continue;
            }

            try {
                $this->db->execute($sql);
                $this->log("Table $fullName created successfully", 'success');
                $created++;
            } catch (Exception $e) {
                $error = "Failed to create $fullName: " . $e->getMessage();
                $this->log($error, 'error');
                $errors[] = $error;
            }
        }

        // Insert default configuration if config table was created
        if ($created > 0 || $this->tableExists('quantum_ab_test_config')) {
            $this->insertDefaultConfig();
        }

        return [
            'success' => empty($errors),
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'results' => $this->results
        ];
    }

    /**
     * Check table status
     */
    private function check() {
        $tables = [
            'quantum_ab_tests',
            'quantum_ab_test_outcomes',
            'quantum_ab_test_state_changes',
            'quantum_ab_test_reports',
            'quantum_ab_test_config'
        ];

        $status = [];
        foreach ($tables as $table) {
            $exists = $this->tableExists($table);
            $count = $exists ? $this->getRecordCount($table) : 0;
            $status[$table] = [
                'exists' => $exists,
                'records' => $count
            ];
            $this->log("Table {$this->prefix}$table: " . ($exists ? "EXISTS ($count records)" : "MISSING"),
                       $exists ? 'success' : 'warning');
        }

        return [
            'success' => true,
            'tables' => $status,
            'results' => $this->results
        ];
    }

    /**
     * Upgrade existing tables (version-based migrations)
     */
    private function upgrade() {
        $currentVersion = $this->getCurrentVersion();
        $this->log("Current DB version: $currentVersion");
        $this->log("Target version: " . QUANTUM_AB_VERSION);

        if ($currentVersion >= QUANTUM_AB_VERSION) {
            $this->log("Database is up to date", 'success');
            return ['success' => true, 'message' => 'Already up to date'];
        }

        $migrations = [];

        // Version-specific migrations
        if ($currentVersion < 2025120901) {
            $migrations[] = $this->migration_2025120901();
        }

        // Future migrations would go here:
        // if ($currentVersion < 2025120902) {
        //     $migrations[] = $this->migration_2025120902();
        // }

        // Update version
        $this->setCurrentVersion(QUANTUM_AB_VERSION);

        return [
            'success' => true,
            'migrations' => $migrations,
            'new_version' => QUANTUM_AB_VERSION,
            'results' => $this->results
        ];
    }

    /**
     * Migration for version 2025120901 (initial release)
     */
    private function migration_2025120901() {
        $this->log("Running migration 2025120901 (initial tables)...");

        // This is the initial version, just ensure tables exist
        $result = $this->install();

        return [
            'version' => 2025120901,
            'description' => 'Initial A/B testing tables',
            'result' => $result
        ];
    }

    /**
     * Drop all tables (use with caution!)
     */
    private function dropTables() {
        $tables = [
            'quantum_ab_test_config',
            'quantum_ab_test_reports',
            'quantum_ab_test_state_changes',
            'quantum_ab_test_outcomes',
            'quantum_ab_tests'
        ];

        $dropped = 0;
        $errors = [];

        foreach ($tables as $table) {
            $fullName = $this->prefix . $table;

            if (!$this->tableExists($table)) {
                $this->log("Table $fullName does not exist - skipped", 'info');
                continue;
            }

            try {
                $this->db->execute("DROP TABLE IF EXISTS $fullName");
                $this->log("Table $fullName dropped", 'warning');
                $dropped++;
            } catch (Exception $e) {
                $error = "Failed to drop $fullName: " . $e->getMessage();
                $this->log($error, 'error');
                $errors[] = $error;
            }
        }

        return [
            'success' => empty($errors),
            'dropped' => $dropped,
            'errors' => $errors,
            'results' => $this->results
        ];
    }

    /**
     * Get table definitions
     */
    private function getTableDefinitions() {
        $prefix = $this->prefix;

        return [
            'quantum_ab_tests' => "
                CREATE TABLE IF NOT EXISTS {$prefix}quantum_ab_tests (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    test_id VARCHAR(255) NOT NULL COMMENT 'Unique identifier for the A/B test',
                    student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
                    group_name VARCHAR(50) NOT NULL COMMENT 'control or treatment',
                    treatment_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.50 COMMENT 'Ratio of treatment group (0.00-1.00)',
                    seed INT(10) DEFAULT 42 COMMENT 'Random seed for reproducibility',
                    hash_value DECIMAL(10,8) DEFAULT NULL COMMENT 'Calculated hash value for assignment',
                    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of creation',
                    timemodified BIGINT(10) NOT NULL COMMENT 'Unix timestamp of last modification',
                    PRIMARY KEY (id),
                    UNIQUE KEY idx_test_student (test_id, student_id),
                    KEY idx_test_group (test_id, group_name),
                    KEY idx_student (student_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores A/B test group assignments for students'
            ",

            'quantum_ab_test_outcomes' => "
                CREATE TABLE IF NOT EXISTS {$prefix}quantum_ab_test_outcomes (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
                    student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
                    metric_name VARCHAR(100) NOT NULL COMMENT 'Name of the metric (learning_gain, engagement_rate, effectiveness_score)',
                    metric_value DECIMAL(12,4) NOT NULL COMMENT 'Numeric value of the metric',
                    session_id VARCHAR(100) DEFAULT NULL COMMENT 'Optional session identifier',
                    context_data TEXT DEFAULT NULL COMMENT 'JSON encoded additional context',
                    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of recording',
                    PRIMARY KEY (id),
                    KEY idx_test_metric (test_id, metric_name),
                    KEY idx_student_test (student_id, test_id),
                    KEY idx_metric_time (metric_name, timecreated)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Records learning outcomes for A/B test analysis'
            ",

            'quantum_ab_test_state_changes' => "
                CREATE TABLE IF NOT EXISTS {$prefix}quantum_ab_test_state_changes (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
                    student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
                    dimension_name VARCHAR(100) NOT NULL COMMENT '8D dimension name (cognitive_clarity, emotional_stability, etc.)',
                    before_value DECIMAL(10,4) DEFAULT NULL COMMENT 'State value before intervention',
                    after_value DECIMAL(10,4) DEFAULT NULL COMMENT 'State value after intervention',
                    change_value DECIMAL(10,4) DEFAULT NULL COMMENT 'Calculated change (after - before)',
                    intervention_type VARCHAR(50) DEFAULT NULL COMMENT 'Type of intervention applied',
                    agent_id INT(10) DEFAULT NULL COMMENT 'Agent that caused the change',
                    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of recording',
                    PRIMARY KEY (id),
                    KEY idx_test_dimension (test_id, dimension_name),
                    KEY idx_student_test (student_id, test_id),
                    KEY idx_time_dimension (timecreated, dimension_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks 8D StateVector changes during A/B tests'
            ",

            'quantum_ab_test_reports' => "
                CREATE TABLE IF NOT EXISTS {$prefix}quantum_ab_test_reports (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
                    report_type VARCHAR(50) NOT NULL COMMENT 'Type of report (overview, metrics, full)',
                    report_data LONGTEXT NOT NULL COMMENT 'JSON encoded report data',
                    control_size INT(10) DEFAULT NULL COMMENT 'Number of control group participants',
                    treatment_size INT(10) DEFAULT NULL COMMENT 'Number of treatment group participants',
                    recommendation VARCHAR(20) DEFAULT NULL COMMENT 'ADOPT, CONTINUE, or REJECT',
                    confidence VARCHAR(20) DEFAULT NULL COMMENT 'high, medium, low',
                    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of report generation',
                    valid_until BIGINT(10) DEFAULT NULL COMMENT 'Cache expiration timestamp',
                    PRIMARY KEY (id),
                    KEY idx_test_type (test_id, report_type),
                    KEY idx_recommendation (recommendation),
                    KEY idx_valid (valid_until)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores cached statistical analysis reports'
            ",

            'quantum_ab_test_config' => "
                CREATE TABLE IF NOT EXISTS {$prefix}quantum_ab_test_config (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    test_id VARCHAR(255) NOT NULL COMMENT 'Unique test identifier',
                    test_name VARCHAR(255) NOT NULL COMMENT 'Human readable test name',
                    description TEXT DEFAULT NULL COMMENT 'Test description and hypothesis',
                    status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, paused, completed, archived',
                    treatment_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.50 COMMENT 'Default treatment ratio',
                    min_sample_size INT(10) DEFAULT 100 COMMENT 'Minimum sample size for significance',
                    target_metrics TEXT DEFAULT NULL COMMENT 'JSON array of target metric names',
                    created_by BIGINT(10) NOT NULL COMMENT 'User ID who created the test',
                    timecreated BIGINT(10) NOT NULL COMMENT 'Test creation timestamp',
                    timemodified BIGINT(10) NOT NULL COMMENT 'Last modification timestamp',
                    timestarted BIGINT(10) DEFAULT NULL COMMENT 'Test start timestamp',
                    timeended BIGINT(10) DEFAULT NULL COMMENT 'Test end timestamp',
                    PRIMARY KEY (id),
                    UNIQUE KEY idx_test_id (test_id),
                    KEY idx_status (status),
                    KEY idx_created_by (created_by)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores A/B test configuration and metadata'
            "
        ];
    }

    /**
     * Insert default test configuration
     */
    private function insertDefaultConfig() {
        global $USER;

        $testId = 'quantum_v1';
        $now = time();
        $userId = isset($USER->id) ? $USER->id : 2;

        // Check if already exists
        try {
            $existing = $this->db->get_record('quantum_ab_test_config', ['test_id' => $testId]);
            if ($existing) {
                $this->log("Default config for '$testId' already exists", 'info');
                return;
            }
        } catch (Exception $e) {
            // Table might not exist yet, continue
        }

        try {
            $config = new stdClass();
            $config->test_id = $testId;
            $config->test_name = 'Quantum Orchestrator A/B Test';
            $config->description = 'Comparing Quantum Orchestrator model (treatment) vs traditional model (control) for learning effectiveness';
            $config->status = 'active';
            $config->treatment_ratio = 0.50;
            $config->min_sample_size = 100;
            $config->target_metrics = json_encode(['learning_gain', 'engagement_rate', 'effectiveness_score']);
            $config->created_by = $userId;
            $config->timecreated = $now;
            $config->timemodified = $now;
            $config->timestarted = $now;

            $this->db->insert_record('quantum_ab_test_config', $config);
            $this->log("Default config for '$testId' inserted", 'success');
        } catch (Exception $e) {
            $this->log("Failed to insert default config: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        global $CFG;

        try {
            $fullName = $this->prefix . $tableName;
            $sql = "SHOW TABLES LIKE '$fullName'";
            $result = $this->db->get_records_sql($sql);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get record count for a table
     */
    private function getRecordCount($tableName) {
        try {
            return $this->db->count_records($tableName);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get current database version
     */
    private function getCurrentVersion() {
        try {
            $record = $this->db->get_record('config_plugins', [
                'plugin' => 'local_quantum_ab',
                'name' => 'version'
            ]);
            return $record ? (int)$record->value : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Set current database version
     */
    private function setCurrentVersion($version) {
        try {
            $record = $this->db->get_record('config_plugins', [
                'plugin' => 'local_quantum_ab',
                'name' => 'version'
            ]);

            if ($record) {
                $record->value = $version;
                $this->db->update_record('config_plugins', $record);
            } else {
                $newRecord = new stdClass();
                $newRecord->plugin = 'local_quantum_ab';
                $newRecord->name = 'version';
                $newRecord->value = $version;
                $this->db->insert_record('config_plugins', $newRecord);
            }

            $this->log("Version updated to $version", 'success');
        } catch (Exception $e) {
            $this->log("Failed to update version: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * Log message
     */
    private function log($message, $type = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $this->results[] = [
            'time' => $timestamp,
            'type' => $type,
            'message' => $message
        ];

        // Also output for web/CLI
        $icons = [
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌'
        ];
        $icon = $icons[$type] ?? '📝';

        if (php_sapi_name() === 'cli') {
            echo "[$timestamp] $icon $message\n";
        }
    }

    /**
     * Get results
     */
    public function getResults() {
        return $this->results;
    }
}

// ============================================================
// Web/CLI Interface
// ============================================================

// Determine action
$action = 'check'; // Default action

if (php_sapi_name() === 'cli') {
    // CLI mode
    $action = isset($argv[1]) ? $argv[1] : 'check';
} else {
    // Web mode
    $action = isset($_GET['action']) ? $_GET['action'] : 'check';
}

// Valid actions
$validActions = ['install', 'upgrade', 'check', 'drop'];
if (!in_array($action, $validActions)) {
    $action = 'check';
}

// Execute
$manager = new QuantumABUpgradeManager();
$result = $manager->execute($action);

// Output
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum A/B Testing - Database Manager</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            padding: 20px;
            margin: 0;
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 {
            color: #58a6ff;
            border-bottom: 1px solid #30363d;
            padding-bottom: 10px;
        }
        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #238636; color: white; }
        .btn-warning { background: #f0883e; color: white; }
        .btn-danger { background: #f85149; color: white; }
        .btn-info { background: #58a6ff; color: white; }
        .log-entry {
            padding: 8px 12px;
            margin: 4px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
        .log-info { background: #1f6feb20; border-left: 3px solid #58a6ff; }
        .log-success { background: #23863620; border-left: 3px solid #238636; }
        .log-warning { background: #f0883e20; border-left: 3px solid #f0883e; }
        .log-error { background: #f8514920; border-left: 3px solid #f85149; }
        .status { font-weight: bold; }
        .status-success { color: #7ee787; }
        .status-error { color: #f85149; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #30363d;
            text-align: left;
        }
        th { background: #21262d; }
        .version-info {
            background: #21262d;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Quantum A/B Testing - Database Manager</h1>

        <div class="version-info">
            <strong>Version:</strong> <?php echo QUANTUM_AB_RELEASE; ?> (<?php echo QUANTUM_AB_VERSION; ?>)
            &nbsp;|&nbsp;
            <strong>Action:</strong> <?php echo strtoupper($action); ?>
            &nbsp;|&nbsp;
            <strong>Status:</strong>
            <span class="status <?php echo $result['success'] ? 'status-success' : 'status-error'; ?>">
                <?php echo $result['success'] ? 'SUCCESS' : 'FAILED'; ?>
            </span>
        </div>

        <div class="actions">
            <a href="?action=check" class="btn btn-info">🔍 Check Status</a>
            <a href="?action=install" class="btn btn-primary">📦 Install Tables</a>
            <a href="?action=upgrade" class="btn btn-warning">⬆️ Upgrade</a>
            <a href="?action=drop" class="btn btn-danger" onclick="return confirm('⚠️ This will DELETE all A/B testing data! Are you sure?');">🗑️ Drop Tables</a>
        </div>

        <?php if ($action === 'check' && isset($result['tables'])): ?>
        <div class="card">
            <h3>📊 Table Status</h3>
            <table>
                <tr>
                    <th>Table Name</th>
                    <th>Status</th>
                    <th>Records</th>
                </tr>
                <?php foreach ($result['tables'] as $table => $info): ?>
                <tr>
                    <td><code>mdl_<?php echo $table; ?></code></td>
                    <td>
                        <?php if ($info['exists']): ?>
                            <span style="color: #7ee787;">✅ EXISTS</span>
                        <?php else: ?>
                            <span style="color: #f85149;">❌ MISSING</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($info['records']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>📝 Execution Log</h3>
            <?php foreach ($result['results'] as $log): ?>
            <div class="log-entry log-<?php echo $log['type']; ?>">
                <span style="color: #8b949e;">[<?php echo $log['time']; ?>]</span>
                <?php echo htmlspecialchars($log['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>🔗 Related Links</h3>
            <ul>
                <li><a href="../ab_testing_dashboard.php" style="color: #58a6ff;">A/B Testing Dashboard</a></li>
                <li><a href="../quantum_monitoring_dashboard.php" style="color: #58a6ff;">Quantum Monitoring Dashboard</a></li>
                <li><a href="../test_ab_testing_integration.php?run_test=1" style="color: #58a6ff;">Run Integration Tests</a></li>
            </ul>
        </div>

        <p style="color: #8b949e; font-size: 12px; margin-top: 30px;">
            Phase 11.1 Database Integration |
            File: <?php echo __FILE__; ?>
        </p>
    </div>
</body>
</html>
    <?php
} else {
    // CLI output
    echo "\n";
    echo "============================================================\n";
    echo "Quantum A/B Testing - Database Manager\n";
    echo "============================================================\n";
    echo "Action: " . strtoupper($action) . "\n";
    echo "Status: " . ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
    echo "============================================================\n";

    if ($action === 'check' && isset($result['tables'])) {
        echo "\nTable Status:\n";
        foreach ($result['tables'] as $table => $info) {
            $status = $info['exists'] ? "EXISTS ({$info['records']} records)" : "MISSING";
            echo "  - mdl_$table: $status\n";
        }
    }

    echo "\nUsage:\n";
    echo "  php upgrade.php check    - Check table status\n";
    echo "  php upgrade.php install  - Install tables\n";
    echo "  php upgrade.php upgrade  - Run migrations\n";
    echo "  php upgrade.php drop     - Drop all tables (CAUTION!)\n";
    echo "\n";
}

// ============================================================
// Related DB Tables:
// ============================================================
// 1. mdl_quantum_ab_tests
//    - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
//    - test_id: VARCHAR(255) NOT NULL
//    - student_id: BIGINT(10) NOT NULL
//    - group_name: VARCHAR(50) NOT NULL
//    - treatment_ratio: DECIMAL(5,2) DEFAULT 0.50
//    - seed: INT(10) DEFAULT 42
//    - hash_value: DECIMAL(10,8)
//    - timecreated: BIGINT(10) NOT NULL
//    - timemodified: BIGINT(10) NOT NULL
//
// 2. mdl_quantum_ab_test_outcomes
//    - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
//    - test_id: VARCHAR(255) NOT NULL
//    - student_id: BIGINT(10) NOT NULL
//    - metric_name: VARCHAR(100) NOT NULL
//    - metric_value: DECIMAL(12,4) NOT NULL
//    - session_id: VARCHAR(100)
//    - context_data: TEXT
//    - timecreated: BIGINT(10) NOT NULL
//
// 3. mdl_quantum_ab_test_state_changes
//    - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
//    - test_id: VARCHAR(255) NOT NULL
//    - student_id: BIGINT(10) NOT NULL
//    - dimension_name: VARCHAR(100) NOT NULL
//    - before_value: DECIMAL(10,4)
//    - after_value: DECIMAL(10,4)
//    - change_value: DECIMAL(10,4)
//    - intervention_type: VARCHAR(50)
//    - agent_id: INT(10)
//    - timecreated: BIGINT(10) NOT NULL
//
// 4. mdl_quantum_ab_test_reports
//    - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
//    - test_id: VARCHAR(255) NOT NULL
//    - report_type: VARCHAR(50) NOT NULL
//    - report_data: LONGTEXT NOT NULL
//    - control_size: INT(10)
//    - treatment_size: INT(10)
//    - recommendation: VARCHAR(20)
//    - confidence: VARCHAR(20)
//    - timecreated: BIGINT(10) NOT NULL
//    - valid_until: BIGINT(10)
//
// 5. mdl_quantum_ab_test_config
//    - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
//    - test_id: VARCHAR(255) NOT NULL UNIQUE
//    - test_name: VARCHAR(255) NOT NULL
//    - description: TEXT
//    - status: VARCHAR(20) DEFAULT 'active'
//    - treatment_ratio: DECIMAL(5,2) DEFAULT 0.50
//    - min_sample_size: INT(10) DEFAULT 100
//    - target_metrics: TEXT (JSON)
//    - created_by: BIGINT(10) NOT NULL
//    - timecreated: BIGINT(10) NOT NULL
//    - timemodified: BIGINT(10) NOT NULL
//    - timestarted: BIGINT(10)
//    - timeended: BIGINT(10)
// ============================================================
?>
