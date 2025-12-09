<?php
// File: mvp_system/database/migrate_mvp_direct.php
// Create policy_versions table using MvpDatabase (Moodle-independent)

echo "=== MVP Policy Versions Migration ===\n";
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

    // Pre-migration check
    echo "=== Pre-Migration Check ===\n";
    $prefix = $db->getTablePrefix();
    $tableName = $prefix . 'mvp_policy_versions';

    $escapedTableName = $db->getConnection()->real_escape_string($tableName);
    $sql = "SHOW TABLES LIKE '{$escapedTableName}'";
    $result = $db->fetchOne($sql, []);

    if ($result) {
        echo "⚠️  WARNING: Table already exists: {$tableName}\n";
        echo "   Run drop_mvp_table.php first if you need to recreate it\n\n";
        exit(0);
    }

    echo "✓ Table does not exist - proceeding with creation\n\n";

    // Load schema file
    echo "=== Loading Schema ===\n";
    $schemaFile = __DIR__ . '/schema/policy_versions.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Schema file not found: {$schemaFile}");
    }

    $schemaSql = file_get_contents($schemaFile);
    echo "✓ Schema loaded from: {$schemaFile}\n";
    echo "✓ Schema size: " . strlen($schemaSql) . " bytes\n\n";

    // Create table
    echo "=== Creating Table ===\n";
    $db->execute($schemaSql);
    echo "✓ Table created: {$tableName}\n\n";

    // Verification Phase
    echo "=== Verification Phase ===\n\n";

    // Test 1: Table exists
    echo "Test 1: Table Existence\n";
    $result = $db->fetchOne($sql, [$tableName]);
    if (!$result) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Table creation failed - table not found");
    }
    echo "✓ Table exists in database\n\n";

    // Test 2: Column verification
    echo "Test 2: Column Verification\n";
    $columnsSql = "SHOW COLUMNS FROM {$tableName}";
    $columns = $db->fetchAll($columnsSql);

    $expectedColumns = [
        'id', 'policy_source', 'file_path', 'version_hash',
        'parsed_rules', 'is_active', 'activated_at',
        'deactivated_at', 'author', 'created_at'
    ];

    $actualColumns = array_map(function($col) { return $col->Field; }, $columns);

    echo "✓ Found " . count($columns) . " columns\n";

    foreach ($expectedColumns as $expectedCol) {
        if (!in_array($expectedCol, $actualColumns)) {
            throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Missing column: {$expectedCol}");
        }
        echo "  ✓ {$expectedCol}\n";
    }
    echo "\n";

    // Test 3: Index verification
    echo "Test 3: Index Verification\n";
    $indexesSql = "SHOW INDEXES FROM {$tableName}";
    $indexes = $db->fetchAll($indexesSql);

    $expectedIndexes = ['PRIMARY', 'idx_active', 'idx_hash'];
    $actualIndexes = array_unique(array_map(function($idx) { return $idx->Key_name; }, $indexes));

    echo "✓ Found " . count($actualIndexes) . " indexes\n";

    foreach ($expectedIndexes as $expectedIdx) {
        if (!in_array($expectedIdx, $actualIndexes)) {
            throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Missing index: {$expectedIdx}");
        }
        echo "  ✓ {$expectedIdx}\n";
    }
    echo "\n";

    // CRUD Operation Tests
    echo "=== CRUD Operation Tests ===\n\n";

    // Test 4: INSERT operation
    echo "Test 4: INSERT Operation\n";
    $testData = [
        'policy_source' => 'test_migration',
        'file_path' => '/test/migration/path.md',
        'version_hash' => md5('test_migration_content'),
        'parsed_rules' => json_encode(['test' => 'migration']),
        'is_active' => 0,
        'created_at' => time()
    ];

    $insertSql = "INSERT INTO {$tableName} (policy_source, file_path, version_hash, parsed_rules, is_active, created_at)
                  VALUES (?, ?, ?, ?, ?, ?)";

    $db->execute($insertSql, [
        $testData['policy_source'],
        $testData['file_path'],
        $testData['version_hash'],
        $testData['parsed_rules'],
        $testData['is_active'],
        $testData['created_at']
    ]);

    $testId = $db->lastInsertId();
    echo "✓ INSERT successful - ID: {$testId}\n\n";

    // Test 5: SELECT operation
    echo "Test 5: SELECT Operation\n";
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $record = $db->fetchOne($selectSql, [$testId]);

    if (!$record) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] SELECT failed - record not found");
    }

    if ($record->policy_source !== $testData['policy_source']) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] SELECT data mismatch - policy_source");
    }

    echo "✓ SELECT successful\n";
    echo "  ✓ policy_source: {$record->policy_source}\n";
    echo "  ✓ version_hash: {$record->version_hash}\n\n";

    // Test 6: UPDATE operation
    echo "Test 6: UPDATE Operation\n";
    $newHash = md5('updated_content');
    $updateSql = "UPDATE {$tableName} SET version_hash = ? WHERE id = ?";
    $db->execute($updateSql, [$newHash, $testId]);

    $affectedRows = $db->affectedRows();
    if ($affectedRows !== 1) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] UPDATE failed - affected rows: {$affectedRows}");
    }

    // Verify update
    $updatedRecord = $db->fetchOne($selectSql, [$testId]);
    if ($updatedRecord->version_hash !== $newHash) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] UPDATE verification failed");
    }

    echo "✓ UPDATE successful\n";
    echo "  ✓ Old hash: {$testData['version_hash']}\n";
    echo "  ✓ New hash: {$newHash}\n\n";

    // Test 7: DELETE operation
    echo "Test 7: DELETE Operation\n";
    $deleteSql = "DELETE FROM {$tableName} WHERE id = ?";
    $db->execute($deleteSql, [$testId]);

    $affectedRows = $db->affectedRows();
    if ($affectedRows !== 1) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] DELETE failed - affected rows: {$affectedRows}");
    }

    // Verify deletion
    $deletedRecord = $db->fetchOne($selectSql, [$testId]);
    if ($deletedRecord !== null) {
        throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] DELETE verification failed - record still exists");
    }

    echo "✓ DELETE successful\n";
    echo "  ✓ Record removed from database\n\n";

    // Migration Summary
    echo "=== Migration Summary ===\n";
    echo "✅ Table created: {$tableName}\n";
    echo "✅ Structure verified: 10 columns, 3 indexes\n";
    echo "✅ CRUD operations tested: INSERT, SELECT, UPDATE, DELETE\n\n";

    echo "=== Next Steps ===\n";
    echo "1. Run verification script: php database/verify_mvp_direct.php\n";
    echo "2. Test with actual policy data\n";
    echo "3. Integrate with policy versioning system\n\n";

    echo "Completed at " . date('Y-m-d H:i:s') . "\n";

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
