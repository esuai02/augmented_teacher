<?php
/**
 * Database Installation Script
 * Phase 11.1: A/B Testing Database Integration
 *
 * Executes SQL schema to create required tables
 *
 * @package    local_augmented_teacher
 * @subpackage holons
 * @author     Quantum A/B Testing Framework
 * @version    1.0
 * @created    2025-12-09
 *
 * Access: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/db/db_install.php
 */

// =============================================================================
// Moodle Integration
// =============================================================================
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

// Check admin permissions
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die(json_encode([
        'success' => false,
        'error' => 'Admin access required',
        'file' => __FILE__,
        'line' => __LINE__
    ]));
}

// =============================================================================
// Table Definitions
// =============================================================================
$tables = [
    'mdl_quantum_ab_tests' => "
        CREATE TABLE IF NOT EXISTS mdl_quantum_ab_tests (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'mdl_quantum_ab_test_outcomes' => "
        CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_outcomes (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
            student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
            metric_name VARCHAR(100) NOT NULL COMMENT 'Name of the metric',
            metric_value DECIMAL(12,4) NOT NULL COMMENT 'Numeric value of the metric',
            session_id VARCHAR(100) DEFAULT NULL COMMENT 'Optional session identifier',
            context_data TEXT DEFAULT NULL COMMENT 'JSON encoded additional context',
            timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of recording',
            PRIMARY KEY (id),
            KEY idx_test_metric (test_id, metric_name),
            KEY idx_student_test (student_id, test_id),
            KEY idx_metric_time (metric_name, timecreated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'mdl_quantum_ab_test_state_changes' => "
        CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_state_changes (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
            student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
            dimension_name VARCHAR(100) NOT NULL COMMENT '8D dimension name',
            before_value DECIMAL(10,4) DEFAULT NULL COMMENT 'State value before intervention',
            after_value DECIMAL(10,4) DEFAULT NULL COMMENT 'State value after intervention',
            change_value DECIMAL(10,4) DEFAULT NULL COMMENT 'Calculated change',
            intervention_type VARCHAR(50) DEFAULT NULL COMMENT 'Type of intervention applied',
            agent_id INT(10) DEFAULT NULL COMMENT 'Agent that caused the change',
            timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of recording',
            PRIMARY KEY (id),
            KEY idx_test_dimension (test_id, dimension_name),
            KEY idx_student_test (student_id, test_id),
            KEY idx_time_dimension (timecreated, dimension_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'mdl_quantum_ab_test_reports' => "
        CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_reports (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
            report_type VARCHAR(50) NOT NULL COMMENT 'Type of report',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'mdl_quantum_ab_test_config' => "
        CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_config (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

// =============================================================================
// HTML Output (if not JSON request)
// =============================================================================
$format = $_GET['format'] ?? 'html';
$action = $_GET['action'] ?? 'status';

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
}

// =============================================================================
// Check Table Status
// =============================================================================
function checkTableStatus($DB, $tableName) {
    try {
        $tableNameWithoutPrefix = str_replace('mdl_', '', $tableName);
        $exists = $DB->get_manager()->table_exists($tableNameWithoutPrefix);

        if ($exists) {
            $count = $DB->count_records($tableNameWithoutPrefix);
            return [
                'exists' => true,
                'record_count' => $count,
                'status' => 'ok'
            ];
        }
        return [
            'exists' => false,
            'record_count' => 0,
            'status' => 'missing'
        ];
    } catch (Exception $e) {
        return [
            'exists' => false,
            'record_count' => 0,
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
}

// =============================================================================
// Execute Installation
// =============================================================================
$results = [];
$allSuccess = true;

if ($action === 'install') {
    foreach ($tables as $tableName => $sql) {
        try {
            $DB->execute($sql);
            $status = checkTableStatus($DB, $tableName);
            $results[$tableName] = [
                'action' => 'created',
                'success' => true,
                'status' => $status
            ];
        } catch (Exception $e) {
            $allSuccess = false;
            $results[$tableName] = [
                'action' => 'failed',
                'success' => false,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ];
        }
    }

    // Insert default config if not exists
    if ($allSuccess) {
        try {
            $existing = $DB->get_record('quantum_ab_test_config', ['test_id' => 'quantum_v1']);
            if (!$existing) {
                $config = new stdClass();
                $config->test_id = 'quantum_v1';
                $config->test_name = 'Quantum Orchestrator A/B Test';
                $config->description = 'Comparing Quantum Orchestrator model (treatment) vs traditional model (control)';
                $config->status = 'active';
                $config->treatment_ratio = 0.50;
                $config->min_sample_size = 100;
                $config->target_metrics = json_encode(['learning_gain', 'engagement_rate', 'effectiveness_score']);
                $config->created_by = $USER->id;
                $config->timecreated = time();
                $config->timemodified = time();
                $config->timestarted = time();

                $DB->insert_record('quantum_ab_test_config', $config);
                $results['default_config'] = ['action' => 'inserted', 'success' => true];
            } else {
                $results['default_config'] = ['action' => 'exists', 'success' => true];
            }
        } catch (Exception $e) {
            $results['default_config'] = ['action' => 'failed', 'success' => false, 'error' => $e->getMessage()];
        }
    }
} else {
    // Just check status
    foreach ($tables as $tableName => $sql) {
        $results[$tableName] = checkTableStatus($DB, $tableName);
    }
}

// =============================================================================
// Output
// =============================================================================
if ($format === 'json') {
    echo json_encode([
        'success' => $allSuccess,
        'action' => $action,
        'tables' => $results,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $USER->id
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A/B Testing DB Installation - Phase 11.1</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #1a1f2c 100%);
            color: #c9d1d9;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .badge {
            background: linear-gradient(135deg, #a371f7 0%, #8957e5 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
        }
        .card {
            background: rgba(22, 27, 34, 0.8);
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #30363d;
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #30363d;
        }
        th {
            color: #8b949e;
            font-weight: 500;
        }
        .status-ok { color: #3fb950; }
        .status-missing { color: #f0883e; }
        .status-error { color: #f85149; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            margin-right: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46, 160, 67, 0.3);
        }
        .btn-secondary {
            background: rgba(48, 54, 61, 0.8);
            color: #c9d1d9;
            border: 1px solid #30363d;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .info-box {
            background: rgba(56, 139, 253, 0.1);
            border: 1px solid rgba(56, 139, 253, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ A/B Testing Database <span class="badge">Phase 11.1</span></h1>

        <div class="card">
            <div class="card-header">
                <span class="card-title">📊 Table Status</span>
                <span><?= date('Y-m-d H:i:s') ?></span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Status</th>
                        <th>Records</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $tableName => $info): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($tableName) ?></code></td>
                        <td>
                            <?php if ($info['status'] === 'ok' || (isset($info['success']) && $info['success'])): ?>
                                <span class="status-ok">✅ OK</span>
                            <?php elseif ($info['status'] === 'missing'): ?>
                                <span class="status-missing">⚠️ Missing</span>
                            <?php else: ?>
                                <span class="status-error">❌ Error</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $info['record_count'] ?? '-' ?></td>
                        <td>
                            <?php if (isset($info['action'])): ?>
                                <?= $info['action'] ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="actions">
            <?php if ($action !== 'install'): ?>
            <a href="?action=install" class="btn btn-primary" onclick="return confirm('Install all tables?')">
                📦 Install Tables
            </a>
            <?php endif; ?>
            <a href="?action=status&format=json" class="btn btn-secondary">📋 JSON Status</a>
            <a href="../ab_testing_dashboard.php" class="btn btn-secondary">🔙 Back to Dashboard</a>
        </div>

        <div class="info-box">
            <h3>ℹ️ Installation Notes</h3>
            <ul style="margin-top: 10px; padding-left: 20px;">
                <li>5 tables will be created for A/B testing framework</li>
                <li>Default test configuration (quantum_v1) will be added</li>
                <li>Existing tables will not be modified</li>
                <li>Admin access is required for installation</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
/**
 * Database Tables:
 * - mdl_quantum_ab_tests (test_id, student_id, group_name, treatment_ratio, seed, hash_value, timecreated, timemodified)
 * - mdl_quantum_ab_test_outcomes (test_id, student_id, metric_name, metric_value, session_id, context_data, timecreated)
 * - mdl_quantum_ab_test_state_changes (test_id, student_id, dimension_name, before_value, after_value, change_value, intervention_type, agent_id, timecreated)
 * - mdl_quantum_ab_test_reports (test_id, report_type, report_data, control_size, treatment_size, recommendation, confidence, timecreated, valid_until)
 * - mdl_quantum_ab_test_config (test_id, test_name, description, status, treatment_ratio, min_sample_size, target_metrics, created_by, timecreated, timemodified, timestarted, timeended)
 */
