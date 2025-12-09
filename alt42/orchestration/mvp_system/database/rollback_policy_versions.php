<?php
// File: mvp_system/database/rollback_policy_versions.php
// Agent Policy Integration - Rollback Policy Versioning Table
// DANGER: This will delete the mdl_mvp_policy_versions table and ALL policy version history!

echo "=== Agent Policy Integration: Rollback Policy Versions Migration ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Moodle DB Connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (!$DB) {
    die("ERROR: Moodle DB connection failed at " . __FILE__ . ":" . __LINE__ . "\n");
}

echo "✓ Moodle DB connection established\n\n";

// Check if running from CLI (safer for destructive operations)
if (php_sapi_name() !== 'cli') {
    echo "⚠️  WARNING: This is a destructive operation!\n";
    echo "    For safety, this script should only be run from command line.\n";
    echo "    Use: php database/rollback_policy_versions.php\n\n";
    exit(1);
}

// Check if table exists
echo "=== Pre-Rollback Check ===\n";
$table_exists = false;
try {
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    $table_exists = true;
    echo "✓ Table exists: mdl_mvp_policy_versions\n";

    // Show current data count
    $record_count = $DB->count_records('mvp_policy_versions');
    echo "✓ Current records: $record_count\n";

    if ($record_count > 0) {
        echo "\n⚠️  WARNING: This table contains $record_count policy version record(s)!\n";
        echo "    All policy version history will be permanently deleted.\n\n";

        // Show recent versions
        $recent = $DB->get_records('mvp_policy_versions', [], 'created_at DESC',
            'id, policy_source, version_hash, is_active, created_at', 0, 5);
        if ($recent) {
            echo "Recent versions that will be deleted:\n";
            echo str_repeat('-', 80) . "\n";
            printf("%-5s %-15s %-20s %-8s %-20s\n", 'ID', 'Source', 'Hash', 'Active', 'Created');
            echo str_repeat('-', 80) . "\n";
            foreach ($recent as $r) {
                printf("%-5d %-15s %-20s %-8s %-20s\n",
                    $r->id,
                    $r->policy_source,
                    substr($r->version_hash, 0, 16) . '...',
                    $r->is_active ? 'YES' : 'NO',
                    date('Y-m-d H:i:s', $r->created_at)
                );
            }
            echo str_repeat('-', 80) . "\n\n";
        }
    }

} catch (Exception $e) {
    echo "✓ Table does not exist, nothing to rollback\n\n";
    exit(0);
}

// Confirmation prompt
echo "=== CONFIRMATION REQUIRED ===\n";
echo "⚠️  This will permanently delete the mdl_mvp_policy_versions table!\n";
echo "⚠️  All policy version history will be lost!\n";
echo "⚠️  This action CANNOT be undone!\n\n";
echo "Type 'DELETE TABLE' to confirm: ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'DELETE TABLE') {
    echo "\n✅ Rollback cancelled (confirmation not provided)\n";
    echo "   Table preserved: mdl_mvp_policy_versions\n\n";
    exit(0);
}

// Perform rollback
echo "\n=== Executing Rollback ===\n";

try {
    // Create backup before deletion
    $backup_data = $DB->get_records('mvp_policy_versions');
    $backup_count = count($backup_data);

    if ($backup_count > 0) {
        $backup_file = __DIR__ . '/../logs/policy_versions_backup_' . date('Y-m-d_His') . '.json';
        $backup_json = json_encode(array_values($backup_data), JSON_PRETTY_PRINT);

        if (!is_dir(dirname($backup_file))) {
            mkdir(dirname($backup_file), 0755, true);
        }

        file_put_contents($backup_file, $backup_json);
        echo "✓ Backup created: $backup_file ($backup_count records)\n";
    }

    // Drop the table
    $DB->execute("DROP TABLE IF EXISTS mdl_mvp_policy_versions");
    echo "✓ Table dropped: mdl_mvp_policy_versions\n";

} catch (Exception $e) {
    echo "❌ Rollback failed at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Verify deletion
echo "\n=== Verification ===\n";
try {
    $DB->get_records('mvp_policy_versions', [], '', '*', 0, 1);
    echo "❌ ERROR: Table still exists after rollback! at " . __FILE__ . ":" . __LINE__ . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "✓ Table successfully removed: mdl_mvp_policy_versions\n";
}

echo "\n=== Rollback Summary ===\n";
echo "✅ Rollback SUCCESSFUL!\n";
if ($backup_count > 0) {
    echo "   - Backup created: $backup_file\n";
    echo "   - Records backed up: $backup_count\n";
}
echo "   - Table dropped: mdl_mvp_policy_versions\n\n";

echo "=== Next Steps ===\n";
echo "To recreate the table, run:\n";
echo "  php database/migrate_policy_versions.php\n\n";

if ($backup_count > 0) {
    echo "To restore data from backup:\n";
    echo "  1. Run migration: php database/migrate_policy_versions.php\n";
    echo "  2. Manually insert from: $backup_file\n\n";
}

echo "Rollback completed at " . date('Y-m-d H:i:s') . "\n";
exit(0);
?>
