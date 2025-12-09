<?php
// File: mvp_system/database/migrate_policy_versions.php
// Agent Policy Integration - Policy Versioning Table Migration
// Phase 3: Create mdl_mvp_policy_versions table for dynamic policy loading

echo "=== Agent Policy Integration: Policy Versions Migration ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Moodle DB Connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (!$DB) {
    die("ERROR: Moodle DB connection failed at " . __FILE__ . ":" . __LINE__ . "\n");
}

echo "✓ Moodle DB connection established\n";
echo "✓ MySQL Version: " . $DB->get_server_info()['version'] . "\n\n";

// Check if table already exists
echo "=== Pre-Migration Check ===\n";
try {
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    echo "⚠️  WARNING: Table mdl_mvp_policy_versions already exists!\n";
    echo "    If you want to recreate it, please run rollback_policy_versions.php first.\n\n";
    exit(1);
} catch (Exception $e) {
    echo "✓ Table does not exist, proceeding with migration\n\n";
}

// Create table SQL (MySQL 5.7 compatible - use {tablename} for Moodle prefix handling)
// NOTE: Only PRIMARY KEY inline, other indexes added separately to avoid Moodle DB abstraction issues
$create_table_sql = "
CREATE TABLE {mvp_policy_versions} (
  id BIGINT(10) NOT NULL AUTO_INCREMENT,
  policy_source VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  version_hash VARCHAR(64) NOT NULL,
  parsed_rules LONGTEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  activated_at BIGINT(10) DEFAULT NULL,
  deactivated_at BIGINT(10) DEFAULT NULL,
  author VARCHAR(100) DEFAULT NULL,
  created_at BIGINT(10) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

echo "=== Creating Table ===\n";
echo "Table: mdl_mvp_policy_versions\n";
echo "Columns: 10 (id, policy_source, file_path, version_hash, parsed_rules, is_active, activated_at, deactivated_at, author, created_at)\n";
echo "Step 1: Create table with PRIMARY KEY\n\n";

try {
    $DB->execute($create_table_sql);
    echo "✓ Table structure created successfully\n\n";
} catch (Exception $e) {
    echo "❌ FAILED to create table at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Add indexes separately to ensure they are created properly
echo "Step 2: Adding indexes\n";

try {
    // Add idx_active composite index
    $DB->execute("ALTER TABLE mdl_mvp_policy_versions ADD INDEX idx_active (is_active, policy_source)");
    echo "✓ idx_active composite index added (is_active, policy_source)\n";

    // Add idx_hash index
    $DB->execute("ALTER TABLE mdl_mvp_policy_versions ADD INDEX idx_hash (version_hash)");
    echo "✓ idx_hash index added (version_hash)\n\n";

    echo "✅ Table and all indexes created successfully!\n\n";
} catch (Exception $e) {
    echo "❌ FAILED to add indexes at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Error: " . $e->getMessage() . "\n";
    echo "    Note: Table was created but indexes may be incomplete.\n";
    echo "    Run fix_missing_indexes.php to repair.\n\n";
    exit(1);
}

// Verify table structure
echo "=== Verification ===\n";

try {
    // Test 1: Table exists
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    echo "✓ Table exists: mdl_mvp_policy_versions\n";

    // Test 2: Verify columns using SHOW COLUMNS
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions");
    $column_count = count($columns);
    echo "✓ Columns found: $column_count/10\n";

    $expected_columns = [
        'id', 'policy_source', 'file_path', 'version_hash', 'parsed_rules',
        'is_active', 'activated_at', 'deactivated_at', 'author', 'created_at'
    ];

    $missing_columns = [];
    foreach ($expected_columns as $expected) {
        $found = false;
        foreach ($columns as $col) {
            if (strtolower($col->field) === strtolower($expected)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing_columns[] = $expected;
        }
    }

    if (empty($missing_columns)) {
        echo "✓ All required columns present\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missing_columns) . " at " . __FILE__ . ":" . __LINE__ . "\n";
        exit(1);
    }

    // Test 3: Verify indexes
    $indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");

    // Extract unique index names (Key_name field)
    $index_names_raw = [];
    foreach ($indexes as $idx) {
        $index_names_raw[] = $idx->key_name;
    }
    $index_names = array_unique($index_names_raw);

    echo "✓ Indexes found: " . count($index_names) . "/3\n";
    echo "  Found: " . implode(', ', $index_names) . "\n";

    $has_primary = false;
    $has_idx_active = false;
    $has_idx_hash = false;

    foreach ($index_names as $index_name) {
        if ($index_name === 'PRIMARY') $has_primary = true;
        if ($index_name === 'idx_active') $has_idx_active = true;
        if ($index_name === 'idx_hash') $has_idx_hash = true;
    }

    if ($has_primary && $has_idx_active && $has_idx_hash) {
        echo "✓ All required indexes present (PRIMARY, idx_active, idx_hash)\n";
    } else {
        $missing = [];
        if (!$has_primary) $missing[] = 'PRIMARY';
        if (!$has_idx_active) $missing[] = 'idx_active';
        if (!$has_idx_hash) $missing[] = 'idx_hash';
        echo "❌ Missing indexes: " . implode(', ', $missing) . " at " . __FILE__ . ":" . __LINE__ . "\n";
        exit(1);
    }

    // Test 4: Test insert operation
    echo "\n=== Testing Basic Operations ===\n";
    $test_record = new stdClass();
    $test_record->policy_source = 'test_agent';
    $test_record->file_path = '/test/path.md';
    $test_record->version_hash = md5('test');
    $test_record->parsed_rules = json_encode(['test' => 'data']);
    $test_record->is_active = 0;
    $test_record->created_at = time();

    $insert_id = $DB->insert_record('mvp_policy_versions', $test_record);
    echo "✓ INSERT test passed (ID: $insert_id)\n";

    // Test 5: Test select operation
    $retrieved = $DB->get_record('mvp_policy_versions', ['id' => $insert_id]);
    if ($retrieved && $retrieved->policy_source === 'test_agent') {
        echo "✓ SELECT test passed\n";
    } else {
        echo "❌ SELECT test failed at " . __FILE__ . ":" . __LINE__ . "\n";
        exit(1);
    }

    // Test 6: Test update operation
    $DB->execute("UPDATE {mvp_policy_versions} SET is_active = 1 WHERE id = ?", [$insert_id]);
    $updated = $DB->get_record('mvp_policy_versions', ['id' => $insert_id]);
    if ($updated && $updated->is_active == 1) {
        echo "✓ UPDATE test passed\n";
    } else {
        echo "❌ UPDATE test failed at " . __FILE__ . ":" . __LINE__ . "\n";
        exit(1);
    }

    // Test 7: Test delete operation
    $DB->delete_records('mvp_policy_versions', ['id' => $insert_id]);
    $deleted = $DB->get_record('mvp_policy_versions', ['id' => $insert_id]);
    if (!$deleted) {
        echo "✓ DELETE test passed\n";
    } else {
        echo "❌ DELETE test failed at " . __FILE__ . ":" . __LINE__ . "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Verification failed at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "\n=== Migration Summary ===\n";
echo "✅ Migration SUCCESSFUL!\n";
echo "   - Table created: mdl_mvp_policy_versions\n";
echo "   - Columns: 10/10\n";
echo "   - Indexes: 3/3 (PRIMARY, idx_active, idx_hash)\n";
echo "   - Operations tested: INSERT, SELECT, UPDATE, DELETE\n\n";

echo "=== Next Steps ===\n";
echo "1. Create lib/markdown_parser.php for parsing agent markdown files\n";
echo "2. Create lib/policy_parser.php for extracting policy rules\n";
echo "3. Create lib/policy_cache.php for caching policies\n";
echo "4. Create lib/policy_loader.php for loading and versioning policies\n\n";

echo "To rollback this migration, run:\n";
echo "  php database/rollback_policy_versions.php\n\n";

echo "Migration completed at " . date('Y-m-d H:i:s') . "\n";
exit(0);
?>
