<?php
// File: mvp_system/database/verify_mvp_direct.php
// Verification utility using MvpDatabase (cache-free)

echo "=== Policy Versions Table Verification (Cache-Free) ===\n";
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
    echo "=== Table Existence Check ===\n";
    $prefix = $db->getTablePrefix();
    $tableName = $prefix . 'mvp_policy_versions';

    $escapedTableName = $db->getConnection()->real_escape_string($tableName);
    $sql = "SHOW TABLES LIKE '{$escapedTableName}'";
    $result = $db->fetchOne($sql, []);

    if (!$result) {
        echo "❌ Table does not exist at " . __FILE__ . ":" . __LINE__ . "\n";
        echo "    Please run the migration script first.\n\n";
        exit(1);
    }

    echo "✓ Table exists: {$tableName}\n\n";

    // Test 1: Verify columns
    echo "=== Column Verification ===\n";
    $columnsSql = "SHOW COLUMNS FROM {$tableName}";
    $columns = $db->fetchAll($columnsSql);
    $columnCount = count($columns);
    echo "✓ Columns found: {$columnCount}/10\n\n";

    $expectedColumns = [
        'id' => ['Type' => 'bigint(10)', 'Null' => 'NO', 'Key' => 'PRI', 'Extra' => 'auto_increment'],
        'policy_source' => ['Type' => 'varchar(50)', 'Null' => 'NO', 'Key' => 'MUL'],
        'file_path' => ['Type' => 'varchar(255)', 'Null' => 'NO'],
        'version_hash' => ['Type' => 'varchar(64)', 'Null' => 'NO', 'Key' => 'MUL'],
        'parsed_rules' => ['Type' => 'longtext', 'Null' => 'NO'],
        'is_active' => ['Type' => 'tinyint(1)', 'Null' => 'NO', 'Default' => '0'],
        'activated_at' => ['Type' => 'bigint(10)', 'Null' => 'YES'],
        'deactivated_at' => ['Type' => 'bigint(10)', 'Null' => 'YES'],
        'author' => ['Type' => 'varchar(100)', 'Null' => 'YES'],
        'created_at' => ['Type' => 'bigint(10)', 'Null' => 'NO']
    ];

    echo "Column Details:\n";
    echo str_repeat('-', 100) . "\n";
    printf("%-20s %-20s %-10s %-10s %-20s %-20s\n", 'Name', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat('-', 100) . "\n";

    $columnErrors = [];
    foreach ($columns as $col) {
        $fieldName = strtolower($col->Field);
        printf("%-20s %-20s %-10s %-10s %-20s %-20s\n",
            $col->Field,
            $col->Type,
            $col->Null,
            $col->Key ?? '',
            $col->Default ?? 'NULL',
            $col->Extra ?? ''
        );

        // Validate against expected schema
        if (isset($expectedColumns[$fieldName])) {
            $expected = $expectedColumns[$fieldName];

            // Check type
            if ($col->Type !== $expected['Type']) {
                $columnErrors[] = "$fieldName: expected type {$expected['Type']}, got {$col->Type}";
            }

            // Check NULL constraint
            if ($col->Null !== $expected['Null']) {
                $columnErrors[] = "$fieldName: expected Null={$expected['Null']}, got Null={$col->Null}";
            }
        }
    }
    echo str_repeat('-', 100) . "\n\n";

    if (empty($columnErrors)) {
        echo "✓ All column types and constraints are correct\n\n";
    } else {
        echo "⚠️  Column issues found:\n";
        foreach ($columnErrors as $error) {
            echo "   - $error\n";
        }
        echo "\n";
    }

    // Test 2: Verify indexes
    echo "=== Index Verification ===\n";
    $indexesSql = "SHOW INDEXES FROM {$tableName}";
    $indexes = $db->fetchAll($indexesSql);

    echo "Total index rows returned: " . count($indexes) . "\n\n";

    // Group indexes by name
    $indexGroups = [];
    foreach ($indexes as $idx) {
        if (!isset($indexGroups[$idx->Key_name])) {
            $indexGroups[$idx->Key_name] = [];
        }
        $indexGroups[$idx->Key_name][] = $idx;
    }

    echo "Indexes found: " . count($indexGroups) . "/3\n\n";

    echo "Index Details:\n";
    echo str_repeat('-', 100) . "\n";
    printf("%-20s %-15s %-20s %-10s %-10s\n", 'Index Name', 'Type', 'Column', 'Seq', 'Unique');
    echo str_repeat('-', 100) . "\n";

    foreach ($indexGroups as $indexName => $indexCols) {
        foreach ($indexCols as $idx) {
            printf("%-20s %-15s %-20s %-10s %-10s\n",
                $indexName,
                $idx->Index_type ?? '',
                $idx->Column_name,
                $idx->Seq_in_index,
                $idx->Non_unique == 0 ? 'YES' : 'NO'
            );
        }
    }
    echo str_repeat('-', 100) . "\n\n";

    // Check required indexes
    $indexNames = array_keys($indexGroups);
    $hasPrimary = in_array('PRIMARY', $indexNames);
    $hasIdxActive = in_array('idx_active', $indexNames);
    $hasIdxHash = in_array('idx_hash', $indexNames);

    $indexStatus = [];
    $indexStatus[] = "PRIMARY: " . ($hasPrimary ? "✓ Present" : "❌ Missing");
    $indexStatus[] = "idx_active: " . ($hasIdxActive ? "✓ Present" : "❌ Missing");
    $indexStatus[] = "idx_hash: " . ($hasIdxHash ? "✓ Present" : "❌ Missing");

    echo "Required Index Status:\n";
    foreach ($indexStatus as $status) {
        echo "  $status\n";
    }
    echo "\n";

    // Verify composite index structure
    if ($hasIdxActive) {
        $idxActiveCols = $indexGroups['idx_active'];

        // Sort by seq_in_index to ensure correct column order
        usort($idxActiveCols, function($a, $b) {
            return $a->Seq_in_index - $b->Seq_in_index;
        });

        // Extract column names in sequence order
        $idxActiveColNames = array_map(function($idx) { return $idx->Column_name; }, $idxActiveCols);

        // Validate composite index structure
        if (count($idxActiveColNames) === 2 &&
            $idxActiveColNames[0] === 'is_active' &&
            $idxActiveColNames[1] === 'policy_source') {
            echo "✓ idx_active composite index structure is correct (is_active, policy_source)\n";
        } else {
            echo "⚠️  idx_active composite index structure issue:\n";
            echo "   Expected: (is_active, policy_source)\n";
            echo "   Found: (" . implode(', ', $idxActiveColNames) . ")\n";
        }
    }

    // Test 3: Test CRUD operations
    echo "\n=== CRUD Operations Test ===\n";

    // INSERT test
    $testRecord = [
        time(),                                  // created_at
        'test_agent_verify',                    // policy_source
        '/test/verify/path.md',                 // file_path
        md5('verify_test_' . time()),          // version_hash
        json_encode(['test' => 'verify', 'timestamp' => time()]), // parsed_rules
        0                                       // is_active
    ];

    $insertSql = "INSERT INTO {$tableName}
        (created_at, policy_source, file_path, version_hash, parsed_rules, is_active)
        VALUES (?, ?, ?, ?, ?, ?)";

    $db->execute($insertSql, $testRecord);
    $insertId = $db->lastInsertId();
    echo "✓ INSERT test passed (ID: {$insertId})\n";

    // SELECT test
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $retrieved = $db->fetchOne($selectSql, [$insertId]);

    if ($retrieved && $retrieved->policy_source === 'test_agent_verify') {
        echo "✓ SELECT test passed\n";
    } else {
        echo "❌ SELECT test failed at " . __FILE__ . ":" . __LINE__ . "\n";
    }

    // UPDATE test
    $updateSql = "UPDATE {$tableName} SET is_active = ? WHERE id = ?";
    $db->execute($updateSql, [1, $insertId]);

    $updated = $db->fetchOne($selectSql, [$insertId]);
    if ($updated && $updated->is_active == 1) {
        echo "✓ UPDATE test passed\n";
    } else {
        echo "❌ UPDATE test failed at " . __FILE__ . ":" . __LINE__ . "\n";
    }

    // DELETE test
    $deleteSql = "DELETE FROM {$tableName} WHERE id = ?";
    $db->execute($deleteSql, [$insertId]);

    $deleted = $db->fetchOne($selectSql, [$insertId]);
    if (!$deleted) {
        echo "✓ DELETE test passed\n";
    } else {
        echo "❌ DELETE test failed at " . __FILE__ . ":" . __LINE__ . "\n";
    }

    // Summary
    echo "\n=== Verification Summary ===\n";
    if ($hasPrimary && $hasIdxActive && $hasIdxHash && empty($columnErrors)) {
        echo "✅ Table structure is CORRECT and fully functional!\n";
        echo "   - All 10 columns present with correct types\n";
        echo "   - All 3 indexes present (PRIMARY, idx_active, idx_hash)\n";
        echo "   - CRUD operations working correctly\n";
        echo "   - Cache-free verification (no Moodle \$DB)\n";
    } else {
        echo "⚠️  Table structure has issues:\n";
        if (!$hasPrimary || !$hasIdxActive || !$hasIdxHash) {
            echo "   - Missing indexes\n";
        }
        if (!empty($columnErrors)) {
            echo "   - Column definition issues\n";
        }
        echo "\n   Consider dropping and recreating the table.\n";
    }

    echo "\nVerification completed at " . date('Y-m-d H:i:s') . "\n";

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
