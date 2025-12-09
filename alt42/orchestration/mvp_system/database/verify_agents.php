<?php
// 파일: mvp_system/database/verify_agents.php (Line 1)
// Mathking Agentic MVP System - Agent Status Verification
//
// Purpose: Verify 22 agents are properly seeded in database
// Usage: Direct browser access
// Security: Teachers and administrators only

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $CFG;

// Set embedded layout
$PAGE->set_pagelayout('embedded');
$PAGE->set_context(context_system::instance());

// Authentication
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student/parent
if ($role === 'student' || $role === 'parent') {
    header("HTTP/1.1 403 Forbidden");
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1><p>This page is not accessible to students or parents.</p>";
    echo "<p>Error Location: verify_agents.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Status Verification - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .active { color: #28a745; font-weight: bold; }
        .inactive { color: #dc3545; font-weight: bold; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #3498db; }
        .stat-label { font-size: 14px; color: #6c757d; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Agent Status Verification</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

<?php

try {
    // Count total agents
    $count_result = $DB->get_records_sql("SELECT COUNT(*) as count FROM mdl_mvp_agent_status");
    $total_count = $count_result ? reset($count_result)->count : 0;

    // Count active agents
    $active_result = $DB->get_records_sql("SELECT COUNT(*) as count FROM mdl_mvp_agent_status WHERE is_active = 1");
    $active_count = $active_result ? reset($active_result)->count : 0;

    // Get all agents
    $agents = $DB->get_records_sql("SELECT * FROM mdl_mvp_agent_status ORDER BY agent_id");

    echo "<div class='stats'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>{$total_count}</div>";
    echo "<div class='stat-label'>Total Agents</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>{$active_count}</div>";
    echo "<div class='stat-label'>Active Agents</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    echo "<div class='stat-value'>" . ($total_count - $active_count) . "</div>";
    echo "<div class='stat-label'>Inactive Agents</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    $expected = 22;
    $status_class = ($total_count === $expected) ? 'success' : 'error';
    echo "<div class='stat-value' style='color: " . ($total_count === $expected ? '#28a745' : '#dc3545') . "'>" . ($total_count === $expected ? '✅' : '❌') . "</div>";
    echo "<div class='stat-label'>Expected: {$expected}</div>";
    echo "</div>";
    echo "</div>";

    if ($total_count !== $expected) {
        echo "<div class='error'>";
        echo "<strong>⚠️ Warning:</strong> Expected 22 agents but found {$total_count}. Please check the migration.";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<strong>✅ Success:</strong> All 22 agents are properly initialized.";
        echo "</div>";
    }

    // Display agent table
    echo "<h2>📋 Agent Details</h2>";
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>#</th>";
    echo "<th>Agent ID</th>";
    echo "<th>Agent Name</th>";
    echo "<th>Status</th>";
    echo "<th>Execution Count</th>";
    echo "<th>Success Rate</th>";
    echo "<th>Last Execution</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    $index = 1;
    foreach ($agents as $agent) {
        $status_class = $agent->is_active ? 'active' : 'inactive';
        $status_text = $agent->is_active ? '✅ Active' : '❌ Inactive';

        $success_rate = 0;
        if ($agent->execution_count > 0) {
            $success_rate = round(($agent->success_count / $agent->execution_count) * 100, 1);
        }

        $last_exec = $agent->last_execution_at ? date('Y-m-d H:i', strtotime($agent->last_execution_at)) : 'Never';

        echo "<tr>";
        echo "<td>{$index}</td>";
        echo "<td><code>" . htmlspecialchars($agent->agent_id) . "</code></td>";
        echo "<td>" . htmlspecialchars($agent->agent_name) . "</td>";
        echo "<td class='{$status_class}'>{$status_text}</td>";
        echo "<td>{$agent->execution_count}</td>";
        echo "<td>{$success_rate}%</td>";
        echo "<td>{$last_exec}</td>";
        echo "</tr>";

        $index++;
    }

    echo "</tbody>";
    echo "</table>";

    // Additional verification
    echo "<hr>";
    echo "<h2>🔍 Additional Checks</h2>";
    echo "<div class='info'>";

    // Check rule_changes table
    $rule_changes_result = $DB->get_records_sql("SELECT COUNT(*) as count FROM mdl_mvp_rule_changes");
    $rule_changes_count = $rule_changes_result ? reset($rule_changes_result)->count : 0;
    echo "<p>✅ <strong>mdl_mvp_rule_changes table:</strong> {$rule_changes_count} records</p>";

    // Check decision_log agent_id column
    $columns = $DB->get_records_sql("DESCRIBE mdl_mvp_decision_log");
    $has_agent_id = false;
    foreach ($columns as $col) {
        if ($col->Field === 'agent_id') {
            $has_agent_id = true;
            break;
        }
    }
    echo "<p>" . ($has_agent_id ? '✅' : '❌') . " <strong>decision_log agent_id column:</strong> " . ($has_agent_id ? 'Found' : 'Not Found') . "</p>";

    // Check index
    $indexes = $DB->get_records_sql("SHOW INDEX FROM mdl_mvp_decision_log WHERE Key_name = 'idx_agent_id'");
    echo "<p>" . (!empty($indexes) ? '✅' : '❌') . " <strong>idx_agent_id index:</strong> " . (!empty($indexes) ? 'Created' : 'Not Found') . "</p>";

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";
}

?>

    </div>
</body>
</html>
