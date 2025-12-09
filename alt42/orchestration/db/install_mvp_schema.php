<?php
/**
 * ALT42 Agent Links System - MVP Schema Installation Script
 *
 * Purpose: Install MVP schema with 5 tables, 4 FKs, 13 indexes
 * Version: MVP 1.0
 * Created: 2025-10-17
 */

// Moodle integration
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Security: Admin only
if (!is_siteadmin()) {
    die("Error: Admin access required - File: " . __FILE__ . ", Line: " . __LINE__);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/orchestration/db/install_mvp_schema.php');
$PAGE->set_title('ALT42 MVP Schema Installation');

echo $OUTPUT->header();
echo $OUTPUT->heading('ALT42 Agent Links System - MVP Schema Installation', 2);

$errors = [];
$warnings = [];
$success_messages = [];

try {
    // ============================================================
    // STEP 1: Check existing tables
    // ============================================================
    echo "<h3>Step 1: Checking Existing Tables</h3>";

    $dbman = $DB->get_manager();
    $existing_tables = [];
    $tables_to_create = [
        'alt42_agent_registry',
        'alt42_artifacts',
        'alt42_links',
        'alt42_events',
        'alt42_audit_log'
    ];

    foreach ($tables_to_create as $table) {
        $table_obj = new xmldb_table($table);
        if ($dbman->table_exists($table_obj)) {
            $existing_tables[] = $table;
            $warnings[] = "Table 'mdl_{$table}' already exists";
        }
    }

    if (!empty($existing_tables)) {
        echo "<p style='color: orange;'>⚠️ Warning: " . count($existing_tables) . " tables already exist</p>";
        echo "<p>Existing tables: " . implode(', ', $existing_tables) . "</p>";
        echo "<p><strong>Do you want to DROP and recreate these tables?</strong></p>";
        echo "<p style='color: red;'>⚠️ WARNING: This will delete all data in these tables!</p>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='confirm_drop' value='1'>";
        echo "<input type='submit' value='YES - Drop and Recreate' style='background: red; color: white; padding: 10px;'>";
        echo "</form>";
        echo "<p><a href='" . $PAGE->url->out() . "'>Cancel</a></p>";

        if (!optional_param('confirm_drop', 0, PARAM_INT)) {
            echo $OUTPUT->footer();
            exit;
        }

        // Drop existing tables
        echo "<h4>Dropping existing tables...</h4>";
        foreach (array_reverse($existing_tables) as $table) {
            $table_obj = new xmldb_table($table);
            try {
                $dbman->drop_table($table_obj);
                $success_messages[] = "Dropped table 'mdl_{$table}'";
                echo "<p style='color: green;'>✅ Dropped table 'mdl_{$table}'</p>";
            } catch (Exception $e) {
                $errors[] = "Failed to drop table 'mdl_{$table}': " . $e->getMessage() . " - File: " . __FILE__ . ", Line: " . __LINE__;
            }
        }
    } else {
        echo "<p style='color: green;'>✅ No existing tables found</p>";
    }

    // ============================================================
    // STEP 2: Create Agent Registry Table
    // ============================================================
    echo "<h3>Step 2: Creating Agent Registry Table</h3>";

    $table = new xmldb_table('alt42_agent_registry');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('agent_id', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL);
    $table->add_field('name', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('title_ko', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
    $table->add_field('capabilities', XMLDB_TYPE_TEXT, null, null, null);
    $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('agent_id_unique', XMLDB_KEY_UNIQUE, ['agent_id']);

    $dbman->create_table($table);
    $success_messages[] = "Created table 'mdl_alt42_agent_registry'";
    echo "<p style='color: green;'>✅ Created table 'mdl_alt42_agent_registry'</p>";

    // ============================================================
    // STEP 3: Create Artifacts Table
    // ============================================================
    echo "<h3>Step 3: Creating Artifacts Table</h3>";

    $table = new xmldb_table('alt42_artifacts');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('artifact_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('agent_id', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL);
    $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('task_id', XMLDB_TYPE_CHAR, '50', null, null);
    $table->add_field('summary_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
    $table->add_field('full_data', XMLDB_TYPE_TEXT, null, null, null);
    $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('artifact_id_unique', XMLDB_KEY_UNIQUE, ['artifact_id']);
    $table->add_key('agent_id_fk', XMLDB_KEY_FOREIGN, ['agent_id'], 'alt42_agent_registry', ['agent_id']);
    $table->add_index('idx_student_agent', XMLDB_INDEX_NOTUNIQUE, ['student_id', 'agent_id', 'created_at']);
    $table->add_index('idx_task_id', XMLDB_INDEX_NOTUNIQUE, ['task_id']);

    $dbman->create_table($table);
    $success_messages[] = "Created table 'mdl_alt42_artifacts'";
    echo "<p style='color: green;'>✅ Created table 'mdl_alt42_artifacts'</p>";

    // ============================================================
    // STEP 4: Create Links Table
    // ============================================================
    echo "<h3>Step 4: Creating Links Table</h3>";

    $table = new xmldb_table('alt42_links');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('link_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('source_agent_id', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL);
    $table->add_field('target_agent_id', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL);
    $table->add_field('artifact_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('task_id', XMLDB_TYPE_CHAR, '50', null, null);
    $table->add_field('prompt_text', XMLDB_TYPE_TEXT, null, null, null);
    $table->add_field('output_data', XMLDB_TYPE_TEXT, null, null, null);
    $table->add_field('render_hint', XMLDB_TYPE_CHAR, '20', null, null, false, 'text');
    $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, null, false, 'draft');
    $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('link_id_unique', XMLDB_KEY_UNIQUE, ['link_id']);
    $table->add_key('source_agent_id_fk', XMLDB_KEY_FOREIGN, ['source_agent_id'], 'alt42_agent_registry', ['agent_id']);
    $table->add_key('target_agent_id_fk', XMLDB_KEY_FOREIGN, ['target_agent_id'], 'alt42_agent_registry', ['agent_id']);
    $table->add_key('artifact_id_fk', XMLDB_KEY_FOREIGN, ['artifact_id'], 'alt42_artifacts', ['artifact_id']);
    $table->add_index('idx_student_target', XMLDB_INDEX_NOTUNIQUE, ['student_id', 'target_agent_id', 'created_at']);
    $table->add_index('idx_task_id', XMLDB_INDEX_NOTUNIQUE, ['task_id']);

    $dbman->create_table($table);
    $success_messages[] = "Created table 'mdl_alt42_links'";
    echo "<p style='color: green;'>✅ Created table 'mdl_alt42_links'</p>";

    // ============================================================
    // STEP 5: Create Events Table
    // ============================================================
    echo "<h3>Step 5: Creating Events Table</h3>";

    $table = new xmldb_table('alt42_events');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('event_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('event_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('link_id', XMLDB_TYPE_CHAR, '50', null, null);
    $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, null);
    $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('event_id_unique', XMLDB_KEY_UNIQUE, ['event_id']);
    $table->add_key('link_id_fk', XMLDB_KEY_FOREIGN, ['link_id'], 'alt42_links', ['link_id']);
    $table->add_index('idx_student_created', XMLDB_INDEX_NOTUNIQUE, ['student_id', 'created_at']);

    $dbman->create_table($table);
    $success_messages[] = "Created table 'mdl_alt42_events'";
    echo "<p style='color: green;'>✅ Created table 'mdl_alt42_events'</p>";

    // ============================================================
    // STEP 6: Create Audit Log Table
    // ============================================================
    echo "<h3>Step 6: Creating Audit Log Table</h3>";

    $table = new xmldb_table('alt42_audit_log');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $table->add_field('entity_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('entity_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('changed_by', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('changed_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_index('idx_entity', XMLDB_INDEX_NOTUNIQUE, ['entity_type', 'entity_id']);
    $table->add_index('idx_changed_at', XMLDB_INDEX_NOTUNIQUE, ['changed_at']);

    $dbman->create_table($table);
    $success_messages[] = "Created table 'mdl_alt42_audit_log'";
    echo "<p style='color: green;'>✅ Created table 'mdl_alt42_audit_log'</p>";

    // ============================================================
    // STEP 7: Register 22 Agents
    // ============================================================
    echo "<h3>Step 7: Registering 22 Agents</h3>";

    $agents = [
        ['agent_id' => 1, 'name' => 'agent01_onboarding', 'title_ko' => '온보딩', 'capabilities' => '["profile", "history", "initialization"]'],
        ['agent_id' => 2, 'name' => 'agent02_exam_schedule', 'title_ko' => '시험일정 식별', 'capabilities' => '["schedule", "context", "urgency"]'],
        ['agent_id' => 3, 'name' => 'agent03_goals_analysis', 'title_ko' => '목표 및 계획 분석', 'capabilities' => '["goals", "planning", "alignment"]'],
        ['agent_id' => 4, 'name' => 'agent04_problem_activity', 'title_ko' => '문제활동 식별', 'capabilities' => '["activity", "problem-solving", "patterns"]'],
        ['agent_id' => 5, 'name' => 'agent05_learning_emotion', 'title_ko' => '학습감정 분석', 'capabilities' => '["emotion", "sentiment", "motivation"]'],
        ['agent_id' => 6, 'name' => 'agent06_teacher_feedback', 'title_ko' => '선생님 피드백', 'capabilities' => '["feedback", "guidance", "instruction"]'],
        ['agent_id' => 7, 'name' => 'agent07_interaction_targeting', 'title_ko' => '상호작용 타게팅', 'capabilities' => '["targeting", "prioritization", "intervention"]'],
        ['agent_id' => 8, 'name' => 'agent08_calmness', 'title_ko' => '침착도 분석', 'capabilities' => '["biometric", "stress", "calmness"]'],
        ['agent_id' => 9, 'name' => 'agent09_learning_management', 'title_ko' => '학습관리 분석', 'capabilities' => '["attendance", "goals", "pomodoro", "notes", "tests"]'],
        ['agent_id' => 10, 'name' => 'agent10_concept_notes', 'title_ko' => '개념노트 분석', 'capabilities' => '["concept", "notes", "understanding"]'],
        ['agent_id' => 11, 'name' => 'agent11_problem_notes', 'title_ko' => '문제노트 분석', 'capabilities' => '["errors", "patterns", "correction"]'],
        ['agent_id' => 12, 'name' => 'agent12_rest_routine', 'title_ko' => '휴식루틴 분석', 'capabilities' => '["rest", "energy", "recovery"]'],
        ['agent_id' => 13, 'name' => 'agent13_learning_dropout', 'title_ko' => '학습이탈 분석', 'capabilities' => '["dropout", "prevention", "engagement"]'],
        ['agent_id' => 14, 'name' => 'agent14_current_position', 'title_ko' => '현재위치 평가', 'capabilities' => '["progress", "risk", "status"]'],
        ['agent_id' => 15, 'name' => 'agent15_problem_redefinition', 'title_ko' => '문제 재정의 & 개선방안', 'capabilities' => '["redefine", "solutions", "strategy"]'],
        ['agent_id' => 16, 'name' => 'agent16_interaction_preparation', 'title_ko' => '상호작용 준비', 'capabilities' => '["mode", "strategy", "preparation"]'],
        ['agent_id' => 17, 'name' => 'agent17_remaining_activities', 'title_ko' => '잔여활동 조정', 'capabilities' => '["adjustment", "planning", "booster"]'],
        ['agent_id' => 18, 'name' => 'agent18_signature_routine', 'title_ko' => '시그너처 루틴 찾기', 'capabilities' => '["routine", "optimization", "signature"]'],
        ['agent_id' => 19, 'name' => 'agent19_interaction_content', 'title_ko' => '상호작용 컨텐츠 생성', 'capabilities' => '["content", "personalization", "delivery"]'],
        ['agent_id' => 20, 'name' => 'agent20_intervention_preparation', 'title_ko' => '개입준비', 'capabilities' => '["planning", "preparation", "setup"]'],
        ['agent_id' => 21, 'name' => 'agent21_intervention_execution', 'title_ko' => '개입실행', 'capabilities' => '["execution", "intervention", "delivery"]'],
        ['agent_id' => 22, 'name' => 'agent22_module_improvement', 'title_ko' => '모듈성능 개선 제안', 'capabilities' => '["improvement", "optimization", "feedback"]']
    ];

    $inserted_count = 0;
    foreach ($agents as $agent) {
        $agent['created_at'] = time();

        // Check if agent already exists
        $existing = $DB->get_record('alt42_agent_registry', ['agent_id' => $agent['agent_id']]);

        if ($existing) {
            // Update existing
            $agent['id'] = $existing->id;
            $DB->update_record('alt42_agent_registry', $agent);
            echo "<p style='color: blue;'>🔄 Updated agent {$agent['agent_id']}: {$agent['title_ko']}</p>";
        } else {
            // Insert new
            $DB->insert_record('alt42_agent_registry', $agent);
            $inserted_count++;
            echo "<p style='color: green;'>✅ Inserted agent {$agent['agent_id']}: {$agent['title_ko']}</p>";
        }
    }

    $success_messages[] = "Registered {$inserted_count} new agents (total 22)";

    // ============================================================
    // STEP 8: Final Validation
    // ============================================================
    echo "<h3>Step 8: Final Validation</h3>";

    // Count tables
    $table_count = 0;
    foreach ($tables_to_create as $table) {
        $table_obj = new xmldb_table($table);
        if ($dbman->table_exists($table_obj)) {
            $table_count++;
        }
    }

    // Count agents
    $agent_count = $DB->count_records('alt42_agent_registry');

    echo "<p><strong>Validation Results:</strong></p>";
    echo "<ul>";
    echo "<li>Tables created: {$table_count}/5 " . ($table_count == 5 ? '✅' : '❌') . "</li>";
    echo "<li>Agents registered: {$agent_count}/22 " . ($agent_count == 22 ? '✅' : '❌') . "</li>";
    echo "</ul>";

    if ($table_count == 5 && $agent_count == 22) {
        echo "<h2 style='color: green;'>✅ MVP Schema Installation Successful!</h2>";
        $success_messages[] = "MVP schema installation completed successfully";
    } else {
        throw new Exception("Validation failed: {$table_count} tables, {$agent_count} agents - File: " . __FILE__ . ", Line: " . __LINE__);
    }

} catch (Exception $e) {
    $errors[] = "Installation error: " . $e->getMessage();
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ============================================================
// Summary Report
// ============================================================
echo "<hr>";
echo "<h2>Installation Summary</h2>";

if (!empty($success_messages)) {
    echo "<h3 style='color: green;'>✅ Success (" . count($success_messages) . ")</h3>";
    echo "<ul>";
    foreach ($success_messages as $msg) {
        echo "<li>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

if (!empty($warnings)) {
    echo "<h3 style='color: orange;'>⚠️ Warnings (" . count($warnings) . ")</h3>";
    echo "<ul>";
    foreach ($warnings as $msg) {
        echo "<li>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 style='color: red;'>❌ Errors (" . count($errors) . ")</h3>";
    echo "<ul>";
    foreach ($errors as $msg) {
        echo "<li>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='verify_mvp_schema.php'>Run integrity validation tests</a></li>";
echo "<li>Implement basic API endpoints (/api/artifacts, /api/links, /api/inbox)</li>";
echo "<li>Create agent popup UI with link functionality</li>";
echo "<li>Test with sample data</li>";
echo "</ol>";

echo "<p><a href='../index.php'>← Back to Orchestration Dashboard</a></p>";

echo $OUTPUT->footer();
