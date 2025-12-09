<?php
// File: mvp_system/database/rollback_mvp_direct.php
// Rollback utility for MVP policy_versions migration

echo "=== MVP Policy Versions Migration Rollback ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Load MvpDatabase
require_once(__DIR__ . '/../lib/MvpDatabase.php');
require_once(__DIR__ . '/../lib/MvpConfig.php');
require_once(__DIR__ . '/../lib/MvpException.php');

try {
    $db = MvpDatabase::getInstance();
    $db->connect();

    echo "✓ MvpDatabase connection established\n";
    echo "✓ MySQL Version: " . $db->getServerInfo() . "\n\n";

    // Check if table exists
    echo "=== Rollback Check ===\n";
    $prefix = $db->getTablePrefix();
    $tableName = $prefix . 'mvp_policy_versions';

    $escapedTableName = $db->getConnection()->real_escape_string($tableName);
    $sql = "SHOW TABLES LIKE '{$escapedTableName}'";
    $result = $db->fetchOne($sql, []);

    if (!$result) {
        echo "ℹ️  Table does not exist: {$tableName}\n";
        echo "   Nothing to rollback\n\n";
        exit(0);
    }

    echo "✓ Table exists: {$tableName}\n";

    // Show current record count
    $countSql = "SELECT COUNT(*) as count FROM {$tableName}";
    $countResult = $db->fetchOne($countSql);
    $recordCount = $countResult->count;
    echo "✓ Current records: {$recordCount}\n\n";

    // Warning message
    echo "⚠️  WARNING: This will permanently delete the table and all data!\n";
    echo "   Table: {$tableName}\n";
    echo "   Records: {$recordCount}\n\n";

    // Confirmation (in real usage, this would be interactive)
    // For automated execution, set environment variable MVP_ROLLBACK_CONFIRM=yes
    $confirmed = getenv('MVP_ROLLBACK_CONFIRM') === 'yes';

    if (!$confirmed) {
        echo "❌ Rollback cancelled (set MVP_ROLLBACK_CONFIRM=yes to confirm)\n\n";
        exit(1);
    }

    // Drop the table
    echo "=== Executing Rollback ===\n";
    $dropSql = "DROP TABLE IF EXISTS {$tableName}";
    $db->execute($dropSql);
    echo "✓ Table dropped: {$tableName}\n\n";

    // Verify deletion
    echo "=== Verification ===\n";
    $verifyResult = $db->fetchOne($sql, []);

    if ($verifyResult) {
        echo "❌ ERROR: Table still exists after drop! at " . __FILE__ . ":" . __LINE__ . "\n\n";
        exit(1);
    }

    echo "✓ Table successfully removed\n";

    echo "\n=== Rollback Complete ===\n";
    echo "✅ Table {$tableName} has been dropped\n";
    echo "   All data has been removed\n";
    echo "   To recreate the table, run:\n";
    echo "   php database/migrate_mvp_direct.php\n\n";

    echo "Rollback completed at " . date('Y-m-d H:i:s') . "\n";

    $db->disconnect();
    exit(0);

} catch (MvpConnectionException $e) {
    echo "❌ Connection Error at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);

} catch (MvpQueryException $e) {
    echo "❌ Query Error at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);

} catch (Exception $e) {
    echo "❌ Unexpected Error at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
