<?php
/**
 * MVP System Web-Based Database Migration Tool
 *
 * Web interface for executing database migrations
 * Access: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/web_migrate.php
 *
 * Error Location: /mvp_system/database/web_migrate.php
 */

// Include Moodle configuration
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

// Security check - allow admin or manager roles
$is_admin = ($role === 'admin' || $role === 'manager');
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVP System Database Migration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .section h2::before {
            content: "▶";
            margin-right: 10px;
            font-size: 14px;
        }
        .table-list {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .table-item {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-item:last-child { border-bottom: none; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-exists { background: #d4edda; color: #155724; }
        .status-missing { background: #f8d7da; color: #721c24; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .log-output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-success { color: #4ec9b0; }
        .log-error { color: #f48771; }
        .log-warning { color: #ffc107; }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ MVP System Database Migration</h1>
            <p>Mathking Agentic Intervention · Database Setup Tool</p>
        </div>

        <div class="content">
            <?php if (!$is_admin): ?>
                <div class="alert alert-danger">
                    <strong>⛔ Access Denied</strong><br>
                    Admin privileges required for database migrations.<br>
                    Current role: <?php echo htmlspecialchars($role ?? 'none'); ?><br>
                    Error Location: web_migrate.php:line 110
                </div>
            <?php else: ?>

                <?php if ($action === 'execute'): ?>
                    <!-- Migration Execution -->
                    <div class="alert alert-info">
                        <strong>🔄 Executing Migration...</strong>
                    </div>

                    <div class="section">
                        <h2>Migration Log</h2>
                        <div class="log-output"><?php
                            // Execute migration
                            $sql_file = dirname(__FILE__) . '/migrations/001_create_tables.sql';

                            if (!file_exists($sql_file)) {
                                echo "<span class='log-error'>❌ ERROR: Migration file not found: $sql_file\n";
                                echo "Error Location: web_migrate.php:line " . __LINE__ . "</span>\n";
                            } else {
                                echo "📁 Reading migration file: $sql_file\n";
                                echo "⏰ Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

                                $sql_content = file_get_contents($sql_file);
                                $statements = preg_split('/;[\s]*$/m', $sql_content, -1, PREG_SPLIT_NO_EMPTY);

                                echo "📊 Found " . count($statements) . " SQL statements\n\n";

                                $executed = 0;
                                $failed = 0;
                                $skipped = 0;

                                foreach ($statements as $index => $statement) {
                                    $statement = trim($statement);

                                    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
                                        $skipped++;
                                        continue;
                                    }

                                    // Extract table name
                                    $table_name = 'unknown';
                                    if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([^\s(]+)/i', $statement, $matches)) {
                                        $table_name = trim($matches[1], '`');
                                    }

                                    echo "[" . ($index + 1) . "] Creating table: <span class='log-warning'>$table_name</span>\n";

                                    try {
                                        $DB->execute($statement);
                                        echo "    <span class='log-success'>✅ SUCCESS</span>\n\n";
                                        $executed++;
                                    } catch (Exception $e) {
                                        echo "    <span class='log-error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                                        echo "    Error Location: web_migrate.php:line " . __LINE__ . "\n\n";
                                        $failed++;
                                    }
                                }

                                echo "\n═══ Migration Summary ═══\n";
                                echo "Total Statements: " . count($statements) . "\n";
                                echo "<span class='log-success'>Executed Successfully: $executed</span>\n";
                                if ($failed > 0) {
                                    echo "<span class='log-error'>Failed: $failed</span>\n";
                                }
                                echo "Skipped (empty/comments): $skipped\n\n";

                                if ($failed === 0 && $executed > 0) {
                                    echo "<span class='log-success'>🎉 Migration completed successfully!</span>\n";
                                } elseif ($failed > 0) {
                                    echo "<span class='log-warning'>⚠️  WARNING: Some statements failed</span>\n";
                                }
                            }
                        ?></div>
                    </div>

                    <div class="btn-group">
                        <a href="web_migrate.php" class="btn btn-primary">↻ Check Status Again</a>
                        <a href="../deploy_verify.php" class="btn btn-secondary">→ Run Deployment Verification</a>
                    </div>

                <?php else: ?>
                    <!-- Migration Status View -->
                    <div class="section">
                        <h2>Current Database Status</h2>
                        <div class="table-list">
                            <?php
                            $required_tables = [
                                'mvp_snapshot_metrics' => 'Sensing layer metrics storage',
                                'mvp_decision_log' => 'Decision layer rule execution log',
                                'mvp_intervention_execution' => 'Execution layer intervention tracking',
                                'mvp_teacher_feedback' => 'Teacher feedback and adjustments',
                                'mvp_system_metrics' => 'System performance monitoring'
                            ];

                            $missing_count = 0;
                            foreach ($required_tables as $table => $description) {
                                $exists = $DB->get_manager()->table_exists($table);
                                if (!$exists) $missing_count++;
                                ?>
                                <div class="table-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($CFG->prefix . $table); ?></strong>
                                        <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">
                                            <?php echo htmlspecialchars($description); ?>
                                        </div>
                                    </div>
                                    <span class="status-badge <?php echo $exists ? 'status-exists' : 'status-missing'; ?>">
                                        <?php echo $exists ? '✅ EXISTS' : '❌ MISSING'; ?>
                                    </span>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>

                    <?php if ($missing_count > 0): ?>
                        <div class="alert alert-warning">
                            <strong>⚠️  Database Not Ready</strong><br>
                            <?php echo $missing_count; ?> out of <?php echo count($required_tables); ?> required tables are missing.<br>
                            Click the button below to create all required tables.
                        </div>

                        <div class="section">
                            <h2>Migration Details</h2>
                            <p style="margin-bottom: 15px; color: #6c757d; font-size: 14px;">
                                This will execute: <code>migrations/001_create_tables.sql</code>
                            </p>
                            <ul style="padding-left: 20px; color: #6c757d; font-size: 14px; line-height: 1.8;">
                                <li>Creates 5 required database tables</li>
                                <li>Sets up proper indexes for performance</li>
                                <li>Configures foreign key relationships</li>
                                <li>Adds comprehensive field documentation</li>
                            </ul>
                        </div>

                        <div class="btn-group">
                            <a href="web_migrate.php?action=execute" class="btn btn-primary"
                               onclick="return confirm('Execute database migration?\n\nThis will create <?php echo $missing_count; ?> missing tables.\n\nProceed?')">
                                🚀 Execute Migration
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <strong>✅ Database Ready</strong><br>
                            All required tables exist and are ready for use.
                        </div>

                        <div class="btn-group">
                            <a href="../deploy_verify.php" class="btn btn-primary">→ Run Deployment Verification</a>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
