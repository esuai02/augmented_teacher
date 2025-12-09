<?php
// File: mvp_system/database/drop_policy_versions_table.php
// Quick table drop utility (browser-accessible)
// WARNING: This will permanently delete the table and all data!

echo "=== Policy Versions Table Drop Utility ===\n";
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
echo "=== Table Check ===\n";
$table_exists = false;
try {
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    $table_exists = true;
    echo "✓ Table exists: mdl_mvp_policy_versions\n";

    // Show current record count
    $record_count = $DB->count_records('mvp_policy_versions');
    echo "✓ Current records: $record_count\n\n";

} catch (Exception $e) {
    echo "ℹ️  Table does not exist, nothing to drop\n\n";
    exit(0);
}

// Drop the table
echo "=== Dropping Table ===\n";
try {
    $DB->execute("DROP TABLE IF EXISTS mdl_mvp_policy_versions");
    echo "✓ Table dropped: mdl_mvp_policy_versions\n\n";
} catch (Exception $e) {
    echo "❌ Failed to drop table at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Verify deletion
echo "=== Verification ===\n";
try {
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    echo "❌ ERROR: Table still exists after drop! at " . __FILE__ . ":" . __LINE__ . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "✓ Table successfully removed\n";
}

echo "\n=== Drop Complete ===\n";
echo "✅ Table mdl_mvp_policy_versions has been dropped\n";
echo "   You can now run the migration script to create it fresh:\n";
echo "   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/migrate_policy_versions.php\n\n";

echo "Completed at " . date('Y-m-d H:i:s') . "\n";
exit(0);
?>
