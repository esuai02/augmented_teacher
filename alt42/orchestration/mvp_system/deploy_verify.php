<?php
// Environment setup for user-installed Python packages
putenv("PATH=/home/moodle/.local/bin:" . getenv("PATH"));
putenv("PYTHONPATH=/home/moodle/.local/lib/python3.10/site-packages");

// File: mvp_system/deploy_verify.php
// Mathking Agentic MVP System - Web-Based Deployment Verification
//
// Purpose: Web interface for deployment verification
// Access: Admin only
// Usage: https://mathking.kr/.../mvp_system/deploy_verify.php?mode=full

// Server connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

// Security check - allow admin or manager roles
if (!is_siteadmin() && $role !== 'manager') {
    header("HTTP/1.1 403 Forbidden");
    echo "<h1>Access Denied</h1>";
    echo "<p>This page is only accessible to site administrators or managers.</p>";
    echo "<p>Debug Info: User ID: {$USER->id}, Role: {$role}, is_siteadmin: " . (is_siteadmin() ? 'true' : 'false') . "</p>";
    exit;
}

// Get mode from query parameter
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'quick';
if (!in_array($mode, ['quick', 'full'])) {
    $mode = 'quick';
}

// Verification results
$results = [
    'pass' => 0,
    'warn' => 0,
    'fail' => 0,
    'checks' => []
];

// Helper function to add check result
function addCheck(&$results, $phase, $status, $message) {
    $results['checks'][] = [
        'phase' => $phase,
        'status' => $status,
        'message' => $message
    ];

    if ($status === 'PASS') {
        $results['pass']++;
    } elseif ($status === 'WARN') {
        $results['warn']++;
    } elseif ($status === 'FAIL') {
        $results['fail']++;
    }
}

// PHASE 1: File Structure Verification
$critical_files = [
    'config/app.config.php',
    'lib/database.php',
    'lib/logger.php',
    'sensing/calm_calculator.py',
    'decision/rule_engine.py',
    'execution/intervention_dispatcher.php',
    'orchestrator.php',
    'ui/teacher_panel.php',
    'api/feedback.php',
    'monitoring/sla_monitor.php'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        addCheck($results, 'File Structure', 'PASS', "File exists: $file");
    } else {
        addCheck($results, 'File Structure', 'FAIL', "Missing file: $file");
    }
}

// PHASE 2: Python Environment Check
$python_version = shell_exec('python3 --version 2>&1');
if ($python_version) {
    addCheck($results, 'Python Environment', 'PASS', "Python available: " . trim($python_version));

    // Check Python modules
    $python_modules = ['yaml', 'json', 'sys', 'datetime'];
    foreach ($python_modules as $module) {
        $check = shell_exec("python3 -c \"import $module\" 2>&1");
        if (empty($check)) {
            addCheck($results, 'Python Environment', 'PASS', "Python module: $module");
        } else {
            addCheck($results, 'Python Environment', 'FAIL', "Missing Python module: $module");
        }
    }
} else {
    addCheck($results, 'Python Environment', 'FAIL', 'Python3 not found');
}

// PHASE 3: PHP Environment Check
$php_version = phpversion();
if ($php_version) {
    addCheck($results, 'PHP Environment', 'PASS', "PHP available: PHP $php_version");

    // Check PHP extensions
    $required_extensions = ['mysqli', 'json', 'mbstring'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            addCheck($results, 'PHP Environment', 'PASS', "PHP extension: $ext");
        } else {
            addCheck($results, 'PHP Environment', 'WARN', "Missing PHP extension: $ext (may not be critical)");
        }
    }
} else {
    addCheck($results, 'PHP Environment', 'FAIL', 'PHP not available');
}

// PHASE 4: Directory Permissions
$critical_dirs = [
    'logs',
    'sensing',
    'decision',
    'execution',
    'ui',
    'api',
    'monitoring',
    'tests'
];

foreach ($critical_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            addCheck($results, 'Directory Permissions', 'PASS', "Directory readable: $dir");
        } else {
            addCheck($results, 'Directory Permissions', 'WARN', "Directory not readable: $dir");
        }
    } else {
        addCheck($results, 'Directory Permissions', 'FAIL', "Missing directory: $dir");
    }
}

// Check logs directory writability
if (is_dir('logs')) {
    if (is_writable('logs')) {
        addCheck($results, 'Directory Permissions', 'PASS', 'Logs directory writable');
    } else {
        addCheck($results, 'Directory Permissions', 'WARN', 'Logs directory not writable (will affect logging)');
    }
}

// PHASE 5: Configuration Validation
if (file_exists('config/app.config.php')) {
    $config_content = file_get_contents('config/app.config.php');

    if (strpos($config_content, "define('MVP_VERSION'") !== false) {
        addCheck($results, 'Configuration', 'PASS', 'MVP_VERSION defined in config');
    } else {
        addCheck($results, 'Configuration', 'WARN', 'MVP_VERSION not found in config');
    }

    if (strpos($config_content, "define('SLA_TARGET_SECONDS'") !== false) {
        addCheck($results, 'Configuration', 'PASS', 'SLA_TARGET_SECONDS defined in config');
    } else {
        addCheck($results, 'Configuration', 'WARN', 'SLA_TARGET_SECONDS not found in config');
    }
}

// PHASE 6: YAML Rules Validation
if (file_exists('decision/rules/calm_break_rules.yaml')) {
    addCheck($results, 'YAML Rules', 'PASS', 'YAML rules file exists');

    // Try to validate YAML syntax using Python
    $yaml_check = shell_exec("python3 -c \"import yaml; yaml.safe_load(open('decision/rules/calm_break_rules.yaml'))\" 2>&1");
    if (empty($yaml_check)) {
        addCheck($results, 'YAML Rules', 'PASS', 'YAML rules file syntax valid');
    } else {
        addCheck($results, 'YAML Rules', 'FAIL', 'YAML rules file syntax invalid');
    }
} else {
    addCheck($results, 'YAML Rules', 'FAIL', 'Missing YAML rules file');
}

// PHASE 7: Database Connection (Full mode only)
if ($mode === 'full') {
    try {
        require_once('lib/database.php');
        $mvp_db = new MVPDatabase();
        addCheck($results, 'Database', 'PASS', 'Database connection successful');

        // Check tables using Moodle's DBManager
        $dbman = $DB->get_manager();
        $tables = [
            'mvp_snapshot_metrics',
            'mvp_decision_log',
            'mvp_intervention_execution',
            'mvp_teacher_feedback',
            'mvp_system_metrics'
        ];

        foreach ($tables as $table) {
            $full_table = 'mdl_' . $table;
            if ($dbman->table_exists($table)) {
                addCheck($results, 'Database', 'PASS', "Table exists: $full_table");
            } else {
                addCheck($results, 'Database', 'FAIL', "Missing table: $full_table");
            }
        }
    } catch (Exception $e) {
        addCheck($results, 'Database', 'FAIL', 'Database connection failed: ' . $e->getMessage());
    }
}

// Determine overall status
$overall_status = 'READY';
$status_class = 'success';

if ($results['fail'] > 0) {
    $overall_status = 'NOT READY';
    $status_class = 'critical';
} elseif ($results['warn'] > 0) {
    $overall_status = 'READY WITH WARNINGS';
    $status_class = 'warning';
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVP System - Deployment Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2c3e50;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .header .subtitle {
            font-size: 16px;
            color: #7f8c8d;
        }

        .mode-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .mode-btn {
            padding: 12px 24px;
            border: 2px solid #667eea;
            border-radius: 8px;
            background: white;
            color: #667eea;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .mode-btn:hover {
            background: #667eea;
            color: white;
        }

        .mode-btn.active {
            background: #667eea;
            color: white;
        }

        .summary {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .summary-item.pass {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .summary-item.warn {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .summary-item.fail {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .summary-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .summary-label {
            font-size: 14px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
        }

        .overall-status {
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 700;
        }

        .overall-status.success {
            background: #28a745;
            color: white;
        }

        .overall-status.warning {
            background: #ffc107;
            color: #333;
        }

        .overall-status.critical {
            background: #dc3545;
            color: white;
        }

        .checks-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .phase-group {
            margin-bottom: 30px;
        }

        .phase-title {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            margin-bottom: 15px;
        }

        .check-item {
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .check-item.pass {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .check-item.warn {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .check-item.fail {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .check-status {
            font-weight: 700;
            font-size: 14px;
            min-width: 60px;
        }

        .check-status.pass { color: #28a745; }
        .check-status.warn { color: #ffc107; }
        .check-status.fail { color: #dc3545; }

        .check-message {
            flex: 1;
            font-size: 14px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196f3;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .info-box h3 {
            color: #2196f3;
            margin-bottom: 10px;
        }

        .info-box ul {
            margin-left: 20px;
        }

        .info-box li {
            margin-bottom: 8px;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 MVP System Deployment Verification</h1>
            <div class="subtitle">
                Mathking Agentic Intervention System v1.3
                <br>
                Test Date: <?php echo date('Y-m-d H:i:s'); ?>
                <br>
                User: <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?>
            </div>
        </div>

        <!-- Mode Selector -->
        <div class="mode-selector">
            <a href="?mode=quick" class="mode-btn <?php echo $mode === 'quick' ? 'active' : ''; ?>">
                🚀 Quick Mode (2 min)
            </a>
            <a href="?mode=full" class="mode-btn <?php echo $mode === 'full' ? 'active' : ''; ?>">
                🔍 Full Mode (5 min)
            </a>
        </div>

        <!-- Overall Status -->
        <div class="overall-status <?php echo $status_class; ?>">
            <?php if ($status_class === 'success'): ?>
                ✅ SYSTEM <?php echo $overall_status; ?> FOR DEPLOYMENT
            <?php elseif ($status_class === 'warning'): ?>
                ⚠️ SYSTEM <?php echo $overall_status; ?>
            <?php else: ?>
                ❌ SYSTEM <?php echo $overall_status; ?> FOR DEPLOYMENT
            <?php endif; ?>
        </div>

        <!-- Summary -->
        <div class="summary">
            <h2>Verification Summary</h2>
            <div class="summary-grid">
                <div class="summary-item pass">
                    <div class="summary-number"><?php echo $results['pass']; ?></div>
                    <div class="summary-label">Passed</div>
                </div>
                <div class="summary-item warn">
                    <div class="summary-number"><?php echo $results['warn']; ?></div>
                    <div class="summary-label">Warnings</div>
                </div>
                <div class="summary-item fail">
                    <div class="summary-number"><?php echo $results['fail']; ?></div>
                    <div class="summary-label">Failed</div>
                </div>
            </div>
        </div>

        <!-- Detailed Checks -->
        <div class="checks-container">
            <h2>Detailed Verification Results</h2>
            <?php
            // Group checks by phase
            $phases = [];
            foreach ($results['checks'] as $check) {
                $phases[$check['phase']][] = $check;
            }

            foreach ($phases as $phase_name => $checks):
            ?>
                <div class="phase-group">
                    <div class="phase-title"><?php echo htmlspecialchars($phase_name); ?></div>
                    <?php foreach ($checks as $check): ?>
                        <div class="check-item <?php echo strtolower($check['status']); ?>">
                            <div class="check-status <?php echo strtolower($check['status']); ?>">
                                <?php echo $check['status']; ?>
                            </div>
                            <div class="check-message">
                                <?php echo htmlspecialchars($check['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Next Steps -->
        <?php if ($results['fail'] > 0 || $results['warn'] > 0): ?>
        <div class="info-box">
            <h3>📋 Next Steps</h3>
            <?php if ($results['fail'] > 0): ?>
            <p><strong>Critical Issues Found:</strong></p>
            <ul>
                <li>Review failed checks above</li>
                <li>Fix missing files or configurations</li>
                <li>Re-run verification after fixes</li>
                <?php if ($mode === 'quick'): ?>
                <li>Run <a href="?mode=full">Full Mode</a> for database verification</li>
                <?php endif; ?>
            </ul>
            <?php elseif ($results['warn'] > 0): ?>
            <p><strong>Warnings Found:</strong></p>
            <ul>
                <li>Review warnings above</li>
                <li>Address non-critical issues if needed</li>
                <li>System may still be deployable</li>
            </ul>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="info-box">
            <h3>🎉 All Checks Passed!</h3>
            <p><strong>Ready for deployment. Next steps:</strong></p>
            <ul>
                <?php if ($mode === 'quick'): ?>
                <li>Run <a href="?mode=full">Full Mode</a> for complete database verification</li>
                <?php endif; ?>
                <li>Execute test pipeline: <code>php orchestrator.php 123</code></li>
                <li>Access Teacher Panel: <a href="ui/teacher_panel.php">Teacher Panel</a></li>
                <li>Access SLA Dashboard: <a href="monitoring/sla_dashboard.php">SLA Dashboard</a></li>
                <li>Review deployment checklist: <a href="DEPLOYMENT_CHECKLIST.md">DEPLOYMENT_CHECKLIST.md</a></li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            MVP System v1.0 | Last Updated: 2025-11-02
            <br>
            Mode: <?php echo strtoupper($mode); ?> | Total Checks: <?php echo count($results['checks']); ?>
        </div>
    </div>
</body>
</html>
