<?php
/**
 * 🔄 Database Migration: V1 → V2 Schema Upgrade
 *
 * Purpose: Add V2 columns to mdl_mvp_decision_log table while preserving V1 data
 *
 * Migration Strategy:
 * - Add new columns with DEFAULT NULL to avoid breaking existing rows
 * - Modify confidence column precision DECIMAL(3,2) → DECIMAL(5,4)
 * - Add indexes for performance optimization
 *
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/db/migrate_v1_to_v2.php
 */

define('CLI_SCRIPT', true);
require_once("/home/moodle/public_html/moodle/config.php");
require_once($CFG->libdir.'/clilib.php');

global $DB, $CFG;

echo "🔄 MVPAgentOrchestrator V1 → V2 Database Migration\n";
echo str_repeat("=", 70) . "\n\n";

// Configuration
$table_name = 'mvp_decision_log';
$dry_run = false; // Set to true for testing without actual changes

if ($dry_run) {
    echo "⚠️  DRY RUN MODE - No actual database changes will be made\n\n";
}

// Migration Steps
$migration_steps = [];

try {
    echo "📋 Pre-Migration Checks\n";
    echo str_repeat("-", 70) . "\n";

    // Check if table exists
    $dbman = $DB->get_manager();
    $table = new xmldb_table($table_name);

    if (!$dbman->table_exists($table)) {
        throw new Exception("❌ Table '{$table_name}' does not exist. Please run initial setup first.");
    }
    echo "✅ Table '{$table_name}' exists\n";

    // Get current row count
    $row_count = $DB->count_records($table_name);
    echo "📊 Current records: {$row_count}\n";

    // Backup recommendation
    echo "\n⚠️  RECOMMENDATION: Create a database backup before proceeding\n";
    echo "   mysqldump -u username -p mathking mdl_mvp_decision_log > backup_mvp_decision_log_" . date('Y-m-d_His') . ".sql\n\n";

    if (!$dry_run) {
        echo "Press ENTER to continue or Ctrl+C to abort: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
    }

    echo "\n🚀 Starting Migration\n";
    echo str_repeat("-", 70) . "\n\n";

    // Step 1: Add agent_name column
    echo "Step 1: Adding 'agent_name' column...\n";
    $field = new xmldb_field('agent_name', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'agent_id');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'agent_name' added (VARCHAR(100) NULL)\n";
        $migration_steps[] = "Added agent_name column";
    } else {
        echo "  ℹ️  Column 'agent_name' already exists\n";
    }

    // Step 2: Add context_data column
    echo "\nStep 2: Adding 'context_data' column...\n";
    $field = new xmldb_field('context_data', XMLDB_TYPE_TEXT, null, null, null, null, null, 'confidence');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'context_data' added (TEXT NULL)\n";
        $migration_steps[] = "Added context_data column";
    } else {
        echo "  ℹ️  Column 'context_data' already exists\n";
    }

    // Step 3: Add result_data column
    echo "\nStep 3: Adding 'result_data' column...\n";
    $field = new xmldb_field('result_data', XMLDB_TYPE_TEXT, null, null, null, null, null, 'context_data');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'result_data' added (TEXT NULL)\n";
        $migration_steps[] = "Added result_data column";
    } else {
        echo "  ℹ️  Column 'result_data' already exists\n";
    }

    // Step 4: Add is_cascade column
    echo "\nStep 4: Adding 'is_cascade' column...\n";
    $field = new xmldb_field('is_cascade', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'result_data');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'is_cascade' added (TINYINT(1) NOT NULL DEFAULT 0)\n";
        $migration_steps[] = "Added is_cascade column";
    } else {
        echo "  ℹ️  Column 'is_cascade' already exists\n";
    }

    // Step 5: Add cascade_depth column
    echo "\nStep 5: Adding 'cascade_depth' column...\n";
    $field = new xmldb_field('cascade_depth', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'is_cascade');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'cascade_depth' added (INT NOT NULL DEFAULT 0)\n";
        $migration_steps[] = "Added cascade_depth column";
    } else {
        echo "  ℹ️  Column 'cascade_depth' already exists\n";
    }

    // Step 6: Add parent_decision_id column
    echo "\nStep 6: Adding 'parent_decision_id' column...\n";
    $field = new xmldb_field('parent_decision_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'cascade_depth');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'parent_decision_id' added (BIGINT NULL)\n";
        $migration_steps[] = "Added parent_decision_id column";
    } else {
        echo "  ℹ️  Column 'parent_decision_id' already exists\n";
    }

    // Step 7: Add execution_time_ms column
    echo "\nStep 7: Adding 'execution_time_ms' column...\n";
    $field = new xmldb_field('execution_time_ms', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'parent_decision_id');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'execution_time_ms' added (DECIMAL(10,2) NULL)\n";
        $migration_steps[] = "Added execution_time_ms column";
    } else {
        echo "  ℹ️  Column 'execution_time_ms' already exists\n";
    }

    // Step 8: Add notes column (different from rationale)
    echo "\nStep 8: Adding 'notes' column...\n";
    $field = new xmldb_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'execution_time_ms');
    if (!$dbman->field_exists($table, $field)) {
        if (!$dry_run) {
            $dbman->add_field($table, $field);
        }
        echo "  ✅ Column 'notes' added (TEXT NULL)\n";
        $migration_steps[] = "Added notes column";
    } else {
        echo "  ℹ️  Column 'notes' already exists\n";
    }

    // Step 9: Modify confidence column precision
    echo "\nStep 9: Modifying 'confidence' column precision...\n";
    echo "  ⚠️  WARNING: This requires ALTER TABLE which may lock the table briefly\n";

    // Get current column definition
    $current_confidence_type = $DB->get_field_sql(
        "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'mdl_mvp_decision_log'
         AND COLUMN_NAME = 'confidence'"
    );

    echo "  Current type: {$current_confidence_type}\n";

    if ($current_confidence_type === 'decimal(3,2)') {
        if (!$dry_run) {
            // Use raw SQL for precision modification (Moodle XMLDB doesn't support DECIMAL precision changes well)
            $DB->execute("ALTER TABLE {$table_name} MODIFY COLUMN confidence DECIMAL(5,4) NOT NULL");
        }
        echo "  ✅ Column 'confidence' modified to DECIMAL(5,4)\n";
        $migration_steps[] = "Modified confidence precision: DECIMAL(3,2) → DECIMAL(5,4)";
    } else {
        echo "  ℹ️  Column 'confidence' already has correct precision\n";
    }

    // Step 10: Add indexes for performance
    echo "\nStep 10: Adding performance indexes...\n";

    // Index on is_cascade for cascade queries
    $index = new xmldb_index('idx_is_cascade', XMLDB_INDEX_NOTUNIQUE, ['is_cascade']);
    if (!$dbman->index_exists($table, $index)) {
        if (!$dry_run) {
            $dbman->add_index($table, $index);
        }
        echo "  ✅ Index 'idx_is_cascade' added\n";
        $migration_steps[] = "Added index on is_cascade";
    } else {
        echo "  ℹ️  Index 'idx_is_cascade' already exists\n";
    }

    // Index on parent_decision_id for cascade tree traversal
    $index = new xmldb_index('idx_parent_decision', XMLDB_INDEX_NOTUNIQUE, ['parent_decision_id']);
    if (!$dbman->index_exists($table, $index)) {
        if (!$dry_run) {
            $dbman->add_index($table, $index);
        }
        echo "  ✅ Index 'idx_parent_decision' added\n";
        $migration_steps[] = "Added index on parent_decision_id";
    } else {
        echo "  ℹ️  Index 'idx_parent_decision' already exists\n";
    }

    // Step 11: Verify migration
    echo "\n📊 Post-Migration Verification\n";
    echo str_repeat("-", 70) . "\n";

    // Verify row count unchanged
    $new_row_count = $DB->count_records($table_name);
    if ($row_count === $new_row_count) {
        echo "✅ Row count preserved: {$new_row_count}\n";
    } else {
        throw new Exception("❌ Row count mismatch! Before: {$row_count}, After: {$new_row_count}");
    }

    // Verify all new columns exist
    $required_columns = [
        'agent_name', 'context_data', 'result_data', 'is_cascade',
        'cascade_depth', 'parent_decision_id', 'execution_time_ms', 'notes'
    ];

    foreach ($required_columns as $col) {
        $field = new xmldb_field($col);
        if ($dbman->field_exists($table, $field)) {
            echo "✅ Column '{$col}' verified\n";
        } else {
            throw new Exception("❌ Column '{$col}' not found after migration");
        }
    }

    // Verify confidence precision
    $new_confidence_type = $DB->get_field_sql(
        "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'mdl_mvp_decision_log'
         AND COLUMN_NAME = 'confidence'"
    );

    if ($new_confidence_type === 'decimal(5,4)') {
        echo "✅ Confidence precision verified: DECIMAL(5,4)\n";
    } else {
        echo "⚠️  WARNING: Confidence type is '{$new_confidence_type}', expected 'decimal(5,4)'\n";
    }

    // Final summary
    echo "\n" . str_repeat("=", 70) . "\n";
    if ($dry_run) {
        echo "✅ DRY RUN COMPLETED - No actual changes made\n";
    } else {
        echo "✅ MIGRATION COMPLETED SUCCESSFULLY\n";
    }
    echo str_repeat("=", 70) . "\n\n";

    echo "📝 Migration Summary:\n";
    foreach ($migration_steps as $step) {
        echo "  • {$step}\n";
    }

    echo "\n📋 Next Steps:\n";
    echo "  1. Run backward compatibility tests: php tests/test_backward_compatibility.php\n";
    echo "  2. Run cascade tests: php tests/test_cascade.php\n";
    echo "  3. Monitor production logs for any issues\n";
    echo "  4. Document migration in project changelog\n\n";

    // Create migration log
    if (!$dry_run) {
        $log_file = __DIR__ . "/migration_log_" . date('Y-m-d_His') . ".txt";
        $log_content = "Migration V1 → V2 completed at " . date('Y-m-d H:i:s') . "\n";
        $log_content .= "Steps executed:\n";
        foreach ($migration_steps as $step) {
            $log_content .= "  • {$step}\n";
        }
        file_put_contents($log_file, $log_content);
        echo "📄 Migration log saved: {$log_file}\n";
    }

} catch (Exception $e) {
    echo "\n❌ MIGRATION FAILED\n";
    echo str_repeat("=", 70) . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n⚠️  Database state may be partially migrated. Manual intervention may be required.\n";
    echo "⚠️  Check database backup before retrying.\n\n";
    exit(1);
}

echo "\n🎉 Migration process completed!\n\n";

/**
 * Database Fields Reference:
 *
 * mdl_mvp_decision_log (V2 Schema):
 * - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
 * - student_id: BIGINT(10) NOT NULL (indexed)
 * - agent_id: VARCHAR(50) NULL (indexed)
 * - agent_name: VARCHAR(100) NULL ← NEW in V2
 * - rule_id: VARCHAR(100) NULL (indexed)
 * - action: VARCHAR(50) NOT NULL (indexed)
 * - confidence: DECIMAL(5,4) NOT NULL ← MODIFIED precision in V2
 * - rationale: TEXT NOT NULL
 * - context_data: TEXT NULL ← NEW in V2
 * - result_data: TEXT NULL ← NEW in V2
 * - is_cascade: TINYINT(1) NOT NULL DEFAULT 0 ← NEW in V2
 * - cascade_depth: INT NOT NULL DEFAULT 0 ← NEW in V2
 * - parent_decision_id: BIGINT NULL ← NEW in V2
 * - execution_time_ms: DECIMAL(10,2) NULL ← NEW in V2
 * - timestamp: DATETIME NOT NULL (indexed)
 * - created_at: DATETIME DEFAULT CURRENT_TIMESTAMP
 * - notes: TEXT NULL ← NEW in V2
 */
