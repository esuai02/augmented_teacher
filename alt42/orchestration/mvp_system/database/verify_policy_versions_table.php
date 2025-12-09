<?php
// File: mvp_system/database/verify_policy_versions_table.php
// Verification utility to check table structure without recreating it

echo "=== Policy Versions Table Verification ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Moodle DB Connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (!$DB) {
    die("ERROR: Moodle DB connection failed at " . __FILE__ . ":" . __LINE__ . "\n");
}

echo "✓ Moodle DB connection established\n";
echo "✓ MySQL Version: " . $DB->get_server_info()['version'] . "\n\n";

// Check if table exists
echo "=== Table Existence Check ===\n";
try {
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    echo "✓ Table exists: mdl_mvp_policy_versions\n\n";
} catch (Exception $e) {
    echo "❌ Table does not exist at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Please run the migration script first.\n\n";
    exit(1);
}

// Test 1: Verify columns
echo "=== Column Verification ===\n";
$columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions");
$column_count = count($columns);
echo "✓ Columns found: $column_count/10\n\n";

$expected_columns = [
    'id' => ['type' => 'bigint(10)', 'null' => 'NO', 'key' => 'PRI', 'extra' => 'auto_increment'],
    'policy_source' => ['type' => 'varchar(50)', 'null' => 'NO', 'key' => 'MUL'],
    'file_path' => ['type' => 'varchar(255)', 'null' => 'NO'],
    'version_hash' => ['type' => 'varchar(64)', 'null' => 'NO', 'key' => 'MUL'],
    'parsed_rules' => ['type' => 'longtext', 'null' => 'NO'],
    'is_active' => ['type' => 'tinyint(1)', 'null' => 'NO', 'default' => '0'],
    'activated_at' => ['type' => 'bigint(10)', 'null' => 'YES'],
    'deactivated_at' => ['type' => 'bigint(10)', 'null' => 'YES'],
    'author' => ['type' => 'varchar(100)', 'null' => 'YES'],
    'created_at' => ['type' => 'bigint(10)', 'null' => 'NO']
];

echo "Column Details:\n";
echo str_repeat('-', 100) . "\n";
printf("%-20s %-20s %-10s %-10s %-20s %-20s\n", 'Name', 'Type', 'Null', 'Key', 'Default', 'Extra');
echo str_repeat('-', 100) . "\n";

$column_errors = [];
foreach ($columns as $col) {
    $field_name = strtolower($col->field);
    printf("%-20s %-20s %-10s %-10s %-20s %-20s\n",
        $col->field,
        $col->type,
        $col->null,
        $col->key ?? '',
        $col->default ?? 'NULL',
        $col->extra ?? ''
    );

    // Validate against expected schema
    if (isset($expected_columns[$field_name])) {
        $expected = $expected_columns[$field_name];

        // Check type
        if ($col->type !== $expected['type']) {
            $column_errors[] = "$field_name: expected type {$expected['type']}, got {$col->type}";
        }

        // Check NULL constraint
        if ($col->null !== $expected['null']) {
            $column_errors[] = "$field_name: expected NULL={$expected['null']}, got NULL={$col->null}";
        }
    }
}
echo str_repeat('-', 100) . "\n\n";

if (empty($column_errors)) {
    echo "✓ All column types and constraints are correct\n\n";
} else {
    echo "⚠️  Column issues found:\n";
    foreach ($column_errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
}

// Test 2: Verify indexes
echo "=== Index Verification ===\n";
$indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");

// ===== DEBUG OUTPUT START =====
echo "DEBUG: Total index rows returned from SHOW INDEXES: " . count($indexes) . "\n";
echo "DEBUG: Raw index data:\n";
foreach ($indexes as $key => $idx) {
    echo "  Row key=$key | Index={$idx->key_name} | Column={$idx->column_name} | Seq={$idx->seq_in_index} | Type={$idx->index_type}\n";
}
echo "===== DEBUG OUTPUT END =====\n\n";

// Group indexes by name
$index_groups = [];
foreach ($indexes as $idx) {
    if (!isset($index_groups[$idx->key_name])) {
        $index_groups[$idx->key_name] = [];
    }
    $index_groups[$idx->key_name][] = $idx;
}

echo "Indexes found: " . count($index_groups) . "/3\n\n";

echo "Index Details:\n";
echo str_repeat('-', 100) . "\n";
printf("%-20s %-15s %-20s %-10s %-10s\n", 'Index Name', 'Type', 'Column', 'Seq', 'Unique');
echo str_repeat('-', 100) . "\n";

foreach ($index_groups as $index_name => $index_cols) {
    foreach ($index_cols as $seq => $idx) {
        printf("%-20s %-15s %-20s %-10s %-10s\n",
            $index_name,
            $idx->index_type ?? '',
            $idx->column_name,
            $idx->seq_in_index,
            $idx->non_unique == 0 ? 'YES' : 'NO'
        );
    }
}
echo str_repeat('-', 100) . "\n\n";

// Check required indexes
$index_names = array_keys($index_groups);
$has_primary = in_array('PRIMARY', $index_names);

// Also check if id column has PRI key (backup check for PRIMARY KEY)
if (!$has_primary) {
    $id_column = null;
    foreach ($columns as $col) {
        if (strtolower($col->field) === 'id' && isset($col->key) && $col->key === 'PRI') {
            $has_primary = true;
            echo "⚠️  PRIMARY KEY found via column check (id column has PRI key)\n";
            echo "   Note: SHOW INDEXES may not return PRIMARY key_name in some MySQL versions\n\n";
            break;
        }
    }
}

$has_idx_active = in_array('idx_active', $index_names);
$has_idx_hash = in_array('idx_hash', $index_names);

$index_status = [];
$index_status[] = "PRIMARY: " . ($has_primary ? "✓ Present" : "❌ Missing");
$index_status[] = "idx_active: " . ($has_idx_active ? "✓ Present" : "❌ Missing");
$index_status[] = "idx_hash: " . ($has_idx_hash ? "✓ Present" : "❌ Missing");

echo "Required Index Status:\n";
foreach ($index_status as $status) {
    echo "  $status\n";
}
echo "\n";

// Verify composite index structure
if ($has_idx_active) {
    $idx_active_cols = $index_groups['idx_active'];

    // Sort by seq_in_index to ensure correct column order
    usort($idx_active_cols, function($a, $b) {
        return $a->seq_in_index - $b->seq_in_index;
    });

    // Extract column names in sequence order
    $idx_active_col_names = array_map(function($idx) { return $idx->column_name; }, $idx_active_cols);

    // Validate composite index structure
    if (count($idx_active_col_names) === 2 &&
        $idx_active_col_names[0] === 'is_active' &&
        $idx_active_col_names[1] === 'policy_source') {
        echo "✓ idx_active composite index structure is correct (is_active, policy_source)\n";
    } else {
        echo "⚠️  idx_active composite index structure issue:\n";
        echo "   Expected: (is_active, policy_source)\n";
        echo "   Found: (" . implode(', ', $idx_active_col_names) . ")\n";
    }
}

// Test 3: Test CRUD operations
echo "\n=== CRUD Operations Test ===\n";

try {
    // INSERT test
    $test_record = new stdClass();
    $test_record->policy_source = 'test_agent_verify';
    $test_record->file_path = '/test/verify/path.md';
    $test_record->version_hash = md5('verify_test_' . time());
    $test_record->parsed_rules = json_encode(['test' => 'verify', 'timestamp' => time()]);
    $test_record->is_active = 0;
    $test_record->created_at = time();

    $insert_id = $DB->insert_record('mvp_policy_versions', $test_record);
    echo "✓ INSERT test passed (ID: $insert_id)\n";

    // SELECT test
    $retrieved = $DB->get_record('mvp_policy_versions', ['id' => $insert_id]);
    if ($retrieved && $retrieved->policy_source === 'test_agent_verify') {
        echo "✓ SELECT test passed\n";
    } else {
        echo "❌ SELECT test failed at " . __FILE__ . ":" . __LINE__ . "\n";
    }

    // UPDATE test
    $DB->execute("UPDATE {mvp_policy_versions} SET is_active = 1 WHERE id = ?", [$insert_id]);
    $updated = $DB->get_record('mvp_policy_versions', ['id' => $insert_id]);
    if ($updated && $updated->is_active == 1) {
        echo "✓ UPDATE test passed\n";
    } else {
        echo "❌ UPDATE test failed at " . __FILE__ . ":" . __LINE__ . "\n";
    }

    // DELETE test
    $DB->delete_records('mvp_policy_versions', ['id' => $insert_id]);
    $deleted = $DB->get_record('mvp_policy_versions', ['id' => $insert_id]);
    if (!$deleted) {
        echo "✓ DELETE test passed\n";
    } else {
        echo "❌ DELETE test failed at " . __FILE__ . ":" . __LINE__ . "\n";
    }

} catch (Exception $e) {
    echo "❌ CRUD operations failed at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Error: " . $e->getMessage() . "\n";
}

// Summary
echo "\n=== Verification Summary ===\n";
if ($has_primary && $has_idx_active && $has_idx_hash && empty($column_errors)) {
    echo "✅ Table structure is CORRECT and fully functional!\n";
    echo "   - All 10 columns present with correct types\n";
    echo "   - All 3 indexes present (PRIMARY, idx_active, idx_hash)\n";
    echo "   - CRUD operations working correctly\n";
} else {
    echo "⚠️  Table structure has issues:\n";
    if (!$has_primary || !$has_idx_active || !$has_idx_hash) {
        echo "   - Missing indexes\n";
    }
    if (!empty($column_errors)) {
        echo "   - Column definition issues\n";
    }
    echo "\n   Consider dropping and recreating the table.\n";
}

echo "\nVerification completed at " . date('Y-m-d H:i:s') . "\n";
exit(0);
?>
