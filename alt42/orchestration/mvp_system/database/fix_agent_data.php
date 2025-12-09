<?php
// 파일: mvp_system/database/fix_agent_data.php (Line 1)
// Mathking Agentic MVP System - Fix Agent Data
//
// Purpose: Replace hallucinated agent data with actual agents from agents folder
// Usage: Direct browser access (ONE TIME ONLY)
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
    echo "<p>Error Location: fix_agent_data.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP dependencies
require_once(__DIR__ . '/../lib/logger.php');
$logger = new MVPLogger('fix_agent_data');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Agent Data - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1000px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #3498db; color: white; }
        .old { background: #ffebee; }
        .new { background: #e8f5e9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Agent Data (Replace Hallucinations)</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Delete hallucinated agent data and insert actual agents from /agents folder
        </div>

<?php

// Define actual agents from agents folder
$actual_agents = [
    ['agent_id' => 'agent01_onboarding', 'agent_name' => 'Onboarding'],
    ['agent_id' => 'agent02_exam_schedule', 'agent_name' => 'Exam Schedule'],
    ['agent_id' => 'agent03_goals_analysis', 'agent_name' => 'Goals Analysis'],
    ['agent_id' => 'agent04_problem_activity', 'agent_name' => 'Problem Activity'],
    ['agent_id' => 'agent05_learning_emotion', 'agent_name' => 'Learning Emotion'],
    ['agent_id' => 'agent06_teacher_feedback', 'agent_name' => 'Teacher Feedback'],
    ['agent_id' => 'agent07_interaction_targeting', 'agent_name' => 'Interaction Targeting'],
    ['agent_id' => 'agent08_calmness', 'agent_name' => 'Calmness'],
    ['agent_id' => 'agent09_learning_management', 'agent_name' => 'Learning Management'],
    ['agent_id' => 'agent10_concept_notes', 'agent_name' => 'Concept Notes'],
    ['agent_id' => 'agent11_problem_notes', 'agent_name' => 'Problem Notes'],
    ['agent_id' => 'agent12_rest_routine', 'agent_name' => 'Rest Routine'],
    ['agent_id' => 'agent13_learning_dropout', 'agent_name' => 'Learning Dropout'],
    ['agent_id' => 'agent14_current_position', 'agent_name' => 'Current Position'],
    ['agent_id' => 'agent15_problem_redefinition', 'agent_name' => 'Problem Redefinition'],
    ['agent_id' => 'agent16_interaction_preparation', 'agent_name' => 'Interaction Preparation'],
    ['agent_id' => 'agent17_remaining_activities', 'agent_name' => 'Remaining Activities'],
    ['agent_id' => 'agent18_signature_routine', 'agent_name' => 'Signature Routine'],
    ['agent_id' => 'agent19_interaction_content', 'agent_name' => 'Interaction Content'],
    ['agent_id' => 'agent20_intervention_preparation', 'agent_name' => 'Intervention Preparation'],
    ['agent_id' => 'agent21_intervention_execution', 'agent_name' => 'Intervention Execution'],
    ['agent_id' => 'agent22_module_improvement', 'agent_name' => 'Module Improvement']
];

try {
    echo "<h2>Step 1: Current Database State</h2>";

    // Get current agents
    $current_agents = $DB->get_records_sql("SELECT * FROM mdl_mvp_agent_status ORDER BY agent_id");

    echo "<table>";
    echo "<thead><tr><th>#</th><th>Agent ID</th><th>Agent Name</th><th>Status</th></tr></thead>";
    echo "<tbody>";

    $index = 1;
    foreach ($current_agents as $agent) {
        $status_class = ($agent->agent_id === 'agent01_orchestrator' || $agent->agent_id === 'agent02_sensing') ? 'old' : '';
        echo "<tr class='{$status_class}'>";
        echo "<td>{$index}</td>";
        echo "<td>{$agent->agent_id}</td>";
        echo "<td>{$agent->agent_name}</td>";
        echo "<td>" . ($agent->is_active ? 'Active' : 'Inactive') . "</td>";
        echo "</tr>";
        $index++;
    }

    echo "</tbody></table>";

    echo "<div class='warning'>";
    echo "<strong>⚠️ Hallucinated Data Found:</strong> Current agents are LLM-generated hallucinations, not actual agents from /agents folder.";
    echo "</div>";

    echo "<hr>";
    echo "<h2>Step 2: Delete All Current Agents</h2>";

    $DB->execute("DELETE FROM mdl_mvp_agent_status");

    echo "<div class='success'>✅ Deleted all existing agent records</div>";

    $logger->info("Deleted hallucinated agent data", null, ['count' => count($current_agents)]);

    echo "<hr>";
    echo "<h2>Step 3: Insert Actual Agents from /agents Folder</h2>";

    $inserted_count = 0;

    foreach ($actual_agents as $agent) {
        $record = new stdClass();
        $record->agent_id = $agent['agent_id'];
        $record->agent_name = $agent['agent_name'];
        $record->is_active = 1;
        $record->execution_count = 0;
        $record->success_count = 0;
        $record->error_count = 0;
        $record->avg_execution_time = null;
        $record->last_execution_at = null;
        $record->last_error_at = null;
        $record->last_error_msg = null;
        $record->config_data = null;

        $DB->insert_record('mdl_mvp_agent_status', $record);
        $inserted_count++;

        echo "<div class='success'>✅ Inserted: {$agent['agent_id']} - {$agent['agent_name']}</div>";
    }

    $logger->info("Inserted actual agent data", null, ['count' => $inserted_count]);

    echo "<hr>";
    echo "<h2>Step 4: Verification</h2>";

    // Verify new agents
    $new_agents = $DB->get_records_sql("SELECT * FROM mdl_mvp_agent_status ORDER BY agent_id");

    echo "<table>";
    echo "<thead><tr><th>#</th><th>Agent ID</th><th>Agent Name</th><th>Folder Exists</th></tr></thead>";
    echo "<tbody>";

    $index = 1;
    foreach ($new_agents as $agent) {
        $folder_path = __DIR__ . '/../../agents/' . $agent->agent_id;
        $folder_exists = is_dir($folder_path);

        echo "<tr class='new'>";
        echo "<td>{$index}</td>";
        echo "<td>{$agent->agent_id}</td>";
        echo "<td>{$agent->agent_name}</td>";
        echo "<td>" . ($folder_exists ? '✅ Yes' : '❌ No') . "</td>";
        echo "</tr>";
        $index++;
    }

    echo "</tbody></table>";

    $count_result = $DB->get_records_sql("SELECT COUNT(*) as count FROM mdl_mvp_agent_status");
    $total_count = $count_result ? reset($count_result)->count : 0;

    if ($total_count === 22) {
        echo "<div class='success'>";
        echo "<h3>✅ Operation Complete!</h3>";
        echo "<p><strong>Total Agents:</strong> {$total_count} / 22 (Expected)</p>";
        echo "<p>All agents have been replaced with actual agents from /agents folder.</p>";
        echo "</div>";

        $logger->info("Agent data fix completed successfully", null, ['total' => $total_count]);
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Count Mismatch</h3>";
        echo "<p><strong>Total Agents:</strong> {$total_count} / 22 (Expected)</p>";
        echo "</div>";

        $logger->warning("Agent count mismatch", null, ['expected' => 22, 'actual' => $total_count]);
    }

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";

    $logger->error("Agent data fix failed", null, [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

?>

    </div>
</body>
</html>
