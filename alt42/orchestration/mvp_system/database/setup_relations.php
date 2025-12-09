<?php
// 파일: mvp_system/database/setup_relations.php (Line 1)
// Mathking Agentic MVP System - Rule Relations Table Setup
//
// Purpose: Create mdl_mvp_rule_relations table for Rule Ontology system
// Usage: Direct browser access (ONE TIME SETUP)
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
    echo "<p>Error Location: setup_relations.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP dependencies
require_once(__DIR__ . '/../lib/logger.php');
$logger = new MVPLogger('setup_relations');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rule Relations Setup - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .step-title { font-weight: bold; font-size: 18px; color: #2c3e50; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Rule Relations Table Setup</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Create database table for Rule Ontology graph relationships
        </div>

<?php

try {
    // ============================================================
    // STEP 1: Check if table already exists
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 1: Checking Existing Table</div>";

    $dbman = $DB->get_manager();
    $table = new xmldb_table('mvp_rule_relations');
    $table_exists = $dbman->table_exists($table);

    if ($table_exists) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ Table Already Exists</strong><br>";
        echo "Table: mdl_mvp_rule_relations<br>";

        // Get row count
        $count = $DB->count_records('mvp_rule_relations');
        echo "Current rows: {$count}<br>";
        echo "<p>If you need to recreate the table, please drop it first using SQL:</p>";
        echo "<pre>DROP TABLE mdl_mvp_rule_relations;</pre>";
        echo "</div>";

        $logger->info("Table already exists", [], ['row_count' => $count]);
    } else {
        echo "<div class='info'>ℹ️ Table does not exist. Proceeding with creation.</div>";
    }

    echo "</div>"; // End Step 1

    // ============================================================
    // STEP 2: Create Table (if not exists)
    // ============================================================
    if (!$table_exists) {
        echo "<div class='step'>";
        echo "<div class='step-title'>Step 2: Creating mdl_mvp_rule_relations Table</div>";

        $sql = "
            CREATE TABLE mdl_mvp_rule_relations (
                id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                from_rule_id VARCHAR(100) NOT NULL COMMENT '출발 룰 ID (예: agent01_low_confidence)',
                to_rule_id VARCHAR(100) NOT NULL COMMENT '도착 룰 ID (예: agent01_focus_loss)',
                relation_type ENUM('causes', 'depends_on', 'contradicts', 'complements') NOT NULL COMMENT '관계 유형',
                weight DECIMAL(3,2) DEFAULT 1.0 COMMENT '전파 가중치 (0.0-1.0)',
                is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                notes TEXT DEFAULT NULL COMMENT '관계 설명 메모',
                PRIMARY KEY (id),
                UNIQUE KEY unique_relation (from_rule_id, to_rule_id, relation_type),
                INDEX idx_from_rule (from_rule_id),
                INDEX idx_to_rule (to_rule_id),
                INDEX idx_relation_type (relation_type),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='룰 간 관계 정의 (Rule Ontology)'
        ";

        $DB->execute($sql);

        echo "<div class='success'>";
        echo "✅ <strong>Table Created Successfully</strong><br>";
        echo "Table: mdl_mvp_rule_relations<br>";
        echo "</div>";

        $logger->info("Created mdl_mvp_rule_relations table", []);

        echo "</div>"; // End Step 2

        // ============================================================
        // STEP 3: Insert Sample Relations
        // ============================================================
        echo "<div class='step'>";
        echo "<div class='step-title'>Step 3: Inserting Sample Relations</div>";

        $sample_relations = [
            [
                'from_rule_id' => 'agent08_calmness_low',
                'to_rule_id' => 'agent13_dropout_risk',
                'relation_type' => 'causes',
                'weight' => 0.85,
                'notes' => '침착도 저하가 학습 이탈 위험을 유발함'
            ],
            [
                'from_rule_id' => 'agent13_dropout_risk',
                'to_rule_id' => 'agent08_calmness_low',
                'relation_type' => 'depends_on',
                'weight' => 1.0,
                'notes' => '학습 이탈 위험 감지는 침착도 저하에 의존함'
            ],
            [
                'from_rule_id' => 'agent05_emotion_negative',
                'to_rule_id' => 'agent08_calmness_recovery',
                'relation_type' => 'complements',
                'weight' => 0.90,
                'notes' => '부정적 학습 정서와 침착도 회복이 상호 보완됨'
            ],
            [
                'from_rule_id' => 'agent06_feedback_harsh',
                'to_rule_id' => 'agent06_feedback_encouraging',
                'relation_type' => 'contradicts',
                'weight' => 1.0,
                'notes' => '엄격한 피드백과 격려 피드백은 상충됨'
            ]
        ];

        $inserted_count = 0;
        foreach ($sample_relations as $rel) {
            $record = new stdClass();
            $record->from_rule_id = $rel['from_rule_id'];
            $record->to_rule_id = $rel['to_rule_id'];
            $record->relation_type = $rel['relation_type'];
            $record->weight = $rel['weight'];
            $record->is_active = 1;
            $record->notes = $rel['notes'];

            try {
                $DB->insert_record('mvp_rule_relations', $record);
                $inserted_count++;
                echo "<div class='success'>✅ Inserted: {$rel['from_rule_id']} → {$rel['to_rule_id']} ({$rel['relation_type']})</div>";
            } catch (Exception $e) {
                echo "<div class='warning'>⚠️ Skipped duplicate: {$rel['from_rule_id']} → {$rel['to_rule_id']}</div>";
            }
        }

        $logger->info("Inserted sample relations", [], ['count' => $inserted_count]);

        echo "</div>"; // End Step 3
    }

    // ============================================================
    // STEP 4: Verification
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 4: Table Verification</div>";

    // Verify table structure
    $columns = $DB->get_columns('mvp_rule_relations');

    echo "<h3>Table Columns:</h3>";
    echo "<table>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Nullable</th></tr></thead>";
    echo "<tbody>";

    $expected_columns = ['id', 'from_rule_id', 'to_rule_id', 'relation_type', 'weight', 'is_active', 'created_at', 'updated_at', 'notes'];
    $found_columns = [];

    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col->name) . "</td>";
        echo "<td>" . htmlspecialchars($col->type) . "</td>";
        echo "<td>" . ($col->not_null ? 'NO' : 'YES') . "</td>";
        echo "</tr>";
        $found_columns[] = $col->name;
    }

    echo "</tbody></table>";

    // Check for missing columns
    $missing = array_diff($expected_columns, $found_columns);
    if (empty($missing)) {
        echo "<div class='success'>";
        echo "<strong>✅ All Columns Present</strong><br>";
        echo "Expected: " . count($expected_columns) . " columns<br>";
        echo "Found: " . count($found_columns) . " columns";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Missing Columns</strong><br>";
        echo "Missing: " . implode(', ', $missing);
        echo "</div>";
    }

    // Verify indexes
    echo "<h3>Table Indexes:</h3>";
    $indexes = $DB->get_records_sql("SHOW INDEX FROM mdl_mvp_rule_relations");

    echo "<table>";
    echo "<thead><tr><th>Index Name</th><th>Column</th><th>Unique</th></tr></thead>";
    echo "<tbody>";

    foreach ($indexes as $idx) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($idx->key_name) . "</td>";
        echo "<td>" . htmlspecialchars($idx->column_name) . "</td>";
        echo "<td>" . ($idx->non_unique == 0 ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    // Row count
    $total_rows = $DB->count_records('mvp_rule_relations');

    echo "<div class='info'>";
    echo "<strong>Total Relations:</strong> {$total_rows}<br>";
    echo "</div>";

    echo "</div>"; // End Step 4

    // ============================================================
    // STEP 5: Next Steps
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 5: Next Steps</div>";

    echo "<div class='success'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>The Rule Ontology database table is ready for use.</p>";
    echo "<ul>";
    echo "<li>Table: mdl_mvp_rule_relations</li>";
    echo "<li>Columns: " . count($found_columns) . " / " . count($expected_columns) . "</li>";
    echo "<li>Indexes: " . count($indexes) . " created</li>";
    echo "<li>Sample relations: {$total_rows} inserted</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div class='info'>";
    echo "<h3>📋 Next Implementation Steps</h3>";
    echo "<ol>";
    echo "<li>Implement RuleGraphBuilder class</li>";
    echo "<li>Implement CascadeEngine class</li>";
    echo "<li>Implement ConflictResolver class</li>";
    echo "<li>Update MVPAgentOrchestrator</li>";
    echo "<li>Test with existing agents</li>";
    echo "</ol>";
    echo "</div>";

    echo "</div>"; // End Step 5

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Setup Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";

    $logger->error("Setup failed", $e, [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

?>

    </div>
</body>
</html>

<?php
/**
 * File Location: mvp_system/database/setup_relations.php (Line 1)
 * Purpose: One-click setup for Rule Ontology relations table
 *
 * Database Tables Created:
 * - mdl_mvp_rule_relations: Rule relationship storage
 *
 * Fields:
 * - id: BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT
 * - from_rule_id: VARCHAR(100) NOT NULL (출발 룰 ID)
 * - to_rule_id: VARCHAR(100) NOT NULL (도착 룰 ID)
 * - relation_type: ENUM('causes', 'depends_on', 'contradicts', 'complements') NOT NULL
 * - weight: DECIMAL(3,2) DEFAULT 1.0 (전파 가중치)
 * - is_active: TINYINT(1) DEFAULT 1 (활성화 여부)
 * - created_at: DATETIME DEFAULT CURRENT_TIMESTAMP
 * - updated_at: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * - notes: TEXT DEFAULT NULL (관계 설명 메모)
 *
 * Indexes:
 * - PRIMARY KEY (id)
 * - UNIQUE KEY unique_relation (from_rule_id, to_rule_id, relation_type)
 * - INDEX idx_from_rule (from_rule_id)
 * - INDEX idx_to_rule (to_rule_id)
 * - INDEX idx_relation_type (relation_type)
 * - INDEX idx_is_active (is_active)
 */
?>
