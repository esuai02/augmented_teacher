<?php
// File: mvp_system/tests/migrate_policy_versions.test.php
// Test suite for mdl_mvp_policy_versions table migration
// Run: php tests/migrate_policy_versions.test.php

echo "=== Policy Versions Migration Test Suite ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Moodle DB Connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (!$DB) {
    die("ERROR: Moodle DB connection failed at " . __FILE__ . ":" . __LINE__ . "\n");
}

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function run_test($test_name, $test_function) {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;

    echo "Test $total_tests: $test_name... ";

    try {
        $result = $test_function();
        if ($result === true) {
            echo "✅ PASS\n";
            $passed_tests++;
            return true;
        } else {
            echo "❌ FAIL\n";
            echo "    Reason: " . ($result ?: "Test returned false") . "\n";
            $failed_tests++;
            return false;
        }
    } catch (Exception $e) {
        echo "❌ FAIL\n";
        echo "    Exception: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
        $failed_tests++;
        return false;
    }
}

echo "=== Test Suite 1: Table Existence ===\n";

run_test("Table mdl_mvp_policy_versions exists", function() use ($DB) {
    try {
        $DB->get_records('mdl_mvp_policy_versions', [], '', '*', 0, 1);
        return true;
    } catch (Exception $e) {
        return "Table does not exist";
    }
});

echo "\n=== Test Suite 2: Column Structure ===\n";

run_test("Column: id (BIGINT, AUTO_INCREMENT, PRIMARY KEY)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'id'");
    $col = reset($columns);
    if (!$col) return "Column 'id' not found";
    if (stripos($col->type, 'bigint') === false) return "Type is not BIGINT: " . $col->type;
    if (stripos($col->extra, 'auto_increment') === false) return "Not AUTO_INCREMENT";
    if ($col->key !== 'PRI') return "Not PRIMARY KEY";
    return true;
});

run_test("Column: policy_source (VARCHAR(50), NOT NULL)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'policy_source'");
    $col = reset($columns);
    if (!$col) return "Column 'policy_source' not found";
    if (stripos($col->type, 'varchar') === false) return "Type is not VARCHAR: " . $col->type;
    if ($col->null !== 'NO') return "NOT NULL constraint missing";
    return true;
});

run_test("Column: file_path (VARCHAR(255), NOT NULL)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'file_path'");
    $col = reset($columns);
    if (!$col) return "Column 'file_path' not found";
    if (stripos($col->type, 'varchar') === false) return "Type is not VARCHAR: " . $col->type;
    if ($col->null !== 'NO') return "NOT NULL constraint missing";
    return true;
});

run_test("Column: version_hash (VARCHAR(64), NOT NULL)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'version_hash'");
    $col = reset($columns);
    if (!$col) return "Column 'version_hash' not found";
    if (stripos($col->type, 'varchar') === false) return "Type is not VARCHAR: " . $col->type;
    if ($col->null !== 'NO') return "NOT NULL constraint missing";
    return true;
});

run_test("Column: parsed_rules (LONGTEXT, NOT NULL)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'parsed_rules'");
    $col = reset($columns);
    if (!$col) return "Column 'parsed_rules' not found";
    if (stripos($col->type, 'longtext') === false) return "Type is not LONGTEXT: " . $col->type;
    if ($col->null !== 'NO') return "NOT NULL constraint missing";
    return true;
});

run_test("Column: is_active (TINYINT(1), DEFAULT 0)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'is_active'");
    $col = reset($columns);
    if (!$col) return "Column 'is_active' not found";
    if (stripos($col->type, 'tinyint') === false) return "Type is not TINYINT: " . $col->type;
    if ($col->default !== '0') return "Default is not 0: " . $col->default;
    return true;
});

run_test("Column: activated_at (BIGINT(10), NULL allowed)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'activated_at'");
    $col = reset($columns);
    if (!$col) return "Column 'activated_at' not found";
    if (stripos($col->type, 'bigint') === false) return "Type is not BIGINT: " . $col->type;
    if ($col->null !== 'YES') return "NULL should be allowed";
    return true;
});

run_test("Column: deactivated_at (BIGINT(10), NULL allowed)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'deactivated_at'");
    $col = reset($columns);
    if (!$col) return "Column 'deactivated_at' not found";
    if (stripos($col->type, 'bigint') === false) return "Type is not BIGINT: " . $col->type;
    if ($col->null !== 'YES') return "NULL should be allowed";
    return true;
});

run_test("Column: author (VARCHAR(100), NULL allowed)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'author'");
    $col = reset($columns);
    if (!$col) return "Column 'author' not found";
    if (stripos($col->type, 'varchar') === false) return "Type is not VARCHAR: " . $col->type;
    if ($col->null !== 'YES') return "NULL should be allowed";
    return true;
});

run_test("Column: created_at (BIGINT(10), NOT NULL)", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'created_at'");
    $col = reset($columns);
    if (!$col) return "Column 'created_at' not found";
    if (stripos($col->type, 'bigint') === false) return "Type is not BIGINT: " . $col->type;
    if ($col->null !== 'NO') return "NOT NULL constraint missing";
    return true;
});

run_test("Total column count is exactly 10", function() use ($DB) {
    $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions");
    $count = count($columns);
    if ($count !== 10) return "Expected 10 columns, found $count";
    return true;
});

echo "\n=== Test Suite 3: Indexes ===\n";

run_test("Index: PRIMARY KEY on id", function() use ($DB) {
    $indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Key_name = 'PRIMARY'");
    if (empty($indexes)) return "PRIMARY KEY not found";
    $idx = reset($indexes);
    if ($idx->column_name !== 'id') return "PRIMARY KEY not on id column";
    return true;
});

run_test("Index: idx_active on (is_active, policy_source)", function() use ($DB) {
    $indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Key_name = 'idx_active' ORDER BY Seq_in_index");
    if (empty($indexes)) return "Index idx_active not found";

    $index_arr = array_values($indexes);
    if (count($index_arr) < 2) return "idx_active should have 2 columns";

    if ($index_arr[0]->column_name !== 'is_active') return "First column should be is_active, got " . $index_arr[0]->column_name;
    if ($index_arr[1]->column_name !== 'policy_source') return "Second column should be policy_source, got " . $index_arr[1]->column_name;

    return true;
});

run_test("Index: idx_hash on version_hash", function() use ($DB) {
    $indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Key_name = 'idx_hash'");
    if (empty($indexes)) return "Index idx_hash not found";
    $idx = reset($indexes);
    if ($idx->column_name !== 'version_hash') return "idx_hash not on version_hash column";
    return true;
});

run_test("Total index count is exactly 3 (PRIMARY, idx_active, idx_hash)", function() use ($DB) {
    $indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");
    $unique_indexes = array_unique(array_map(function($idx) { return $idx->key_name; }, $indexes));
    $count = count($unique_indexes);
    if ($count !== 3) return "Expected 3 indexes, found $count: " . implode(', ', $unique_indexes);
    return true;
});

echo "\n=== Test Suite 4: CRUD Operations ===\n";

$test_record_id = null;

run_test("INSERT: Create new policy version record", function() use ($DB, &$test_record_id) {
    $record = new stdClass();
    $record->policy_source = 'agent08';
    $record->file_path = '/test/agent08_calmness.md';
    $record->version_hash = md5('test_version_1');
    $record->parsed_rules = json_encode(['calm_thresholds' => ['low' => ['min' => 60, 'max' => 74]]]);
    $record->is_active = 0;
    $record->created_at = time();

    $test_record_id = $DB->insert_record('mdl_mvp_policy_versions', $record);

    if (!$test_record_id || $test_record_id <= 0) return "Insert failed or returned invalid ID";
    return true;
});

run_test("SELECT: Retrieve inserted record by ID", function() use ($DB, $test_record_id) {
    if (!$test_record_id) return "No test record ID available";

    $record = $DB->get_record('mdl_mvp_policy_versions', ['id' => $test_record_id]);
    if (!$record) return "Record not found";
    if ($record->policy_source !== 'agent08') return "policy_source mismatch";
    if ($record->is_active != 0) return "is_active should be 0";

    $parsed = json_decode($record->parsed_rules, true);
    if (!isset($parsed['calm_thresholds'])) return "parsed_rules JSON invalid";

    return true;
});

run_test("UPDATE: Activate policy version", function() use ($DB, $test_record_id) {
    if (!$test_record_id) return "No test record ID available";

    $DB->execute("UPDATE mdl_mvp_policy_versions SET is_active = 1, activated_at = ? WHERE id = ?",
        [time(), $test_record_id]);

    $updated = $DB->get_record('mdl_mvp_policy_versions', ['id' => $test_record_id]);
    if ($updated->is_active != 1) return "is_active not updated";
    if ($updated->activated_at == null) return "activated_at not set";

    return true;
});

run_test("SELECT: Query by active status", function() use ($DB, $test_record_id) {
    $active_records = $DB->get_records('mdl_mvp_policy_versions', ['is_active' => 1]);
    $found = false;
    foreach ($active_records as $rec) {
        if ($rec->id == $test_record_id) {
            $found = true;
            break;
        }
    }
    if (!$found) return "Active record query did not return test record";
    return true;
});

run_test("DELETE: Remove test record", function() use ($DB, $test_record_id) {
    if (!$test_record_id) return "No test record ID available";

    $DB->delete_records('mdl_mvp_policy_versions', ['id' => $test_record_id]);

    $deleted = $DB->get_record('mdl_mvp_policy_versions', ['id' => $test_record_id]);
    if ($deleted) return "Record still exists after deletion";

    return true;
});

echo "\n=== Test Suite 5: Constraint Validation ===\n";

run_test("Constraint: policy_source required (NOT NULL)", function() use ($DB) {
    $record = new stdClass();
    // Intentionally omit policy_source
    $record->file_path = '/test/file.md';
    $record->version_hash = md5('test');
    $record->parsed_rules = '{}';
    $record->created_at = time();

    try {
        $DB->insert_record('mdl_mvp_policy_versions', $record);
        return "Should have failed due to NULL policy_source";
    } catch (Exception $e) {
        // Expected to fail
        return true;
    }
});

run_test("Constraint: version_hash accepts 32-char MD5 hash", function() use ($DB) {
    $record = new stdClass();
    $record->policy_source = 'agent20';
    $record->file_path = '/test/file.md';
    $record->version_hash = md5('valid_hash_test'); // 32 characters
    $record->parsed_rules = '{}';
    $record->created_at = time();

    $id = $DB->insert_record('mdl_mvp_policy_versions', $record);
    if (!$id) return "Failed to insert with valid MD5 hash";

    // Cleanup
    $DB->delete_records('mdl_mvp_policy_versions', ['id' => $id]);
    return true;
});

run_test("Constraint: parsed_rules accepts large JSON (1KB+)", function() use ($DB) {
    $large_policy = [];
    for ($i = 0; $i < 100; $i++) {
        $large_policy["threshold_$i"] = ['min' => $i, 'max' => $i + 10, 'action' => 'test'];
    }

    $record = new stdClass();
    $record->policy_source = 'agent21';
    $record->file_path = '/test/file.md';
    $record->version_hash = md5('large_test');
    $record->parsed_rules = json_encode($large_policy);
    $record->created_at = time();

    $json_size = strlen($record->parsed_rules);
    if ($json_size < 1024) return "Test JSON too small ($json_size bytes)";

    $id = $DB->insert_record('mdl_mvp_policy_versions', $record);
    if (!$id) return "Failed to insert large JSON ($json_size bytes)";

    // Verify retrieval
    $retrieved = $DB->get_record('mdl_mvp_policy_versions', ['id' => $id]);
    $decoded = json_decode($retrieved->parsed_rules, true);
    if (count($decoded) !== 100) return "JSON corrupted during storage";

    // Cleanup
    $DB->delete_records('mdl_mvp_policy_versions', ['id' => $id]);
    return true;
});

// Final Summary
echo "\n=== Test Summary ===\n";
echo "Total tests:  $total_tests\n";
echo "Passed:       $passed_tests (✅)\n";
echo "Failed:       $failed_tests (❌)\n";

if ($failed_tests === 0) {
    echo "\n✅ ALL TESTS PASSED! Migration is verified.\n\n";
    exit(0);
} else {
    $pass_rate = round(($passed_tests / $total_tests) * 100, 1);
    echo "\n⚠️  Some tests failed. Pass rate: $pass_rate%\n";
    echo "Please review failures above.\n\n";
    exit(1);
}
?>
