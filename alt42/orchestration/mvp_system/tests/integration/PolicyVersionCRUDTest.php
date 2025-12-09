<?php
// File: tests/integration/PolicyVersionCRUDTest.php
// Integration tests for policy version CRUD operations

require_once(__DIR__ . '/../../lib/MvpDatabase.php');
require_once(__DIR__ . '/../../lib/MvpConfig.php');
require_once(__DIR__ . '/../../lib/MvpException.php');

echo "=== Running Policy Version CRUD Integration Tests ===\n\n";

$db = MvpDatabase::getInstance();
$db->connect();

$prefix = $db->getTablePrefix();
$tableName = $prefix . 'mvp_policy_versions';

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Create new policy version
echo "Test 1: Create new policy version\n";
try {
    $testData = [
        time(),                                  // created_at
        'agent_01',                             // policy_source
        '/policies/agent_01/mission.md',        // file_path
        md5('test_policy_v1'),                  // version_hash
        json_encode([                           // parsed_rules
            'priority' => 'high',
            'scope' => 'global',
            'rules' => ['rule1', 'rule2']
        ]),
        0,                                      // is_active
        null,                                   // activated_at
        null,                                   // deactivated_at
        'system'                                // author
    ];

    $insertSql = "INSERT INTO {$tableName}
        (created_at, policy_source, file_path, version_hash, parsed_rules, is_active, activated_at, deactivated_at, author)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $db->execute($insertSql, $testData);
    $policyId = $db->lastInsertId();

    if ($policyId > 0) {
        echo "  ✓ Policy created with ID: {$policyId}\n";
        $testsPassed++;
    } else {
        echo "  ❌ Failed to create policy\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 2: Read policy version
echo "\nTest 2: Read policy version\n";
try {
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $policy = $db->fetchOne($selectSql, [$policyId]);

    if ($policy &&
        $policy->policy_source === 'agent_01' &&
        $policy->file_path === '/policies/agent_01/mission.md' &&
        $policy->is_active == 0 &&
        $policy->author === 'system') {

        echo "  ✓ Policy retrieved successfully\n";
        echo "    - Source: {$policy->policy_source}\n";
        echo "    - Path: {$policy->file_path}\n";
        echo "    - Active: {$policy->is_active}\n";
        echo "    - Author: {$policy->author}\n";
        $testsPassed++;
    } else {
        echo "  ❌ Policy data mismatch\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 3: Update policy (activate)
echo "\nTest 3: Update policy (activate)\n";
try {
    $activatedAt = time();

    $updateSql = "UPDATE {$tableName} SET is_active = ?, activated_at = ? WHERE id = ?";
    $db->execute($updateSql, [1, $activatedAt, $policyId]);

    $affected = $db->affectedRows();

    // Verify update
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $updated = $db->fetchOne($selectSql, [$policyId]);

    if ($affected === 1 && $updated->is_active == 1 && $updated->activated_at == $activatedAt) {
        echo "  ✓ Policy activated successfully\n";
        echo "    - Active: {$updated->is_active}\n";
        echo "    - Activated at: {$updated->activated_at}\n";
        $testsPassed++;
    } else {
        echo "  ❌ Policy activation failed\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 4: Query active policies
echo "\nTest 4: Query active policies\n";
try {
    $querySql = "SELECT * FROM {$tableName} WHERE is_active = ? AND policy_source = ?";
    $activePolicies = $db->fetchAll($querySql, [1, 'agent_01']);

    if (count($activePolicies) > 0 && $activePolicies[0]->id == $policyId) {
        echo "  ✓ Active policy query successful\n";
        echo "    - Found " . count($activePolicies) . " active policy/policies\n";
        $testsPassed++;
    } else {
        echo "  ❌ Active policy query failed\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 5: Update policy (deactivate)
echo "\nTest 5: Update policy (deactivate)\n";
try {
    $deactivatedAt = time();

    $updateSql = "UPDATE {$tableName} SET is_active = ?, deactivated_at = ? WHERE id = ?";
    $db->execute($updateSql, [0, $deactivatedAt, $policyId]);

    // Verify update
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $deactivated = $db->fetchOne($selectSql, [$policyId]);

    if ($deactivated->is_active == 0 && $deactivated->deactivated_at == $deactivatedAt) {
        echo "  ✓ Policy deactivated successfully\n";
        echo "    - Active: {$deactivated->is_active}\n";
        echo "    - Deactivated at: {$deactivated->deactivated_at}\n";
        $testsPassed++;
    } else {
        echo "  ❌ Policy deactivation failed\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 6: Transaction rollback
echo "\nTest 6: Transaction rollback\n";
try {
    $db->beginTransaction();

    // Insert a test record
    $testData2 = [
        time(),
        'agent_02',
        '/policies/agent_02/mission.md',
        md5('test_policy_v2'),
        json_encode(['test' => 'rollback']),
        0
    ];

    $insertSql = "INSERT INTO {$tableName}
        (created_at, policy_source, file_path, version_hash, parsed_rules, is_active)
        VALUES (?, ?, ?, ?, ?, ?)";

    $db->execute($insertSql, $testData2);
    $rollbackId = $db->lastInsertId();

    // Rollback transaction
    $db->rollback();

    // Verify record was not saved
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $rolledBack = $db->fetchOne($selectSql, [$rollbackId]);

    if (!$rolledBack) {
        echo "  ✓ Transaction rollback successful\n";
        echo "    - Record with ID {$rollbackId} was not saved\n";
        $testsPassed++;
    } else {
        echo "  ❌ Transaction rollback failed\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 7: Transaction commit
echo "\nTest 7: Transaction commit\n";
try {
    $db->beginTransaction();

    // Insert a test record
    $testData3 = [
        time(),
        'agent_03',
        '/policies/agent_03/mission.md',
        md5('test_policy_v3'),
        json_encode(['test' => 'commit']),
        0
    ];

    $insertSql = "INSERT INTO {$tableName}
        (created_at, policy_source, file_path, version_hash, parsed_rules, is_active)
        VALUES (?, ?, ?, ?, ?, ?)";

    $db->execute($insertSql, $testData3);
    $commitId = $db->lastInsertId();

    // Commit transaction
    $db->commit();

    // Verify record was saved
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $committed = $db->fetchOne($selectSql, [$commitId]);

    if ($committed && $committed->policy_source === 'agent_03') {
        echo "  ✓ Transaction commit successful\n";
        echo "    - Record with ID {$commitId} was saved\n";
        $testsPassed++;

        // Cleanup
        $deleteSql = "DELETE FROM {$tableName} WHERE id = ?";
        $db->execute($deleteSql, [$commitId]);

    } else {
        echo "  ❌ Transaction commit failed\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Test 8: Delete policy version
echo "\nTest 8: Delete policy version\n";
try {
    $deleteSql = "DELETE FROM {$tableName} WHERE id = ?";
    $db->execute($deleteSql, [$policyId]);

    $affected = $db->affectedRows();

    // Verify deletion
    $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
    $deleted = $db->fetchOne($selectSql, [$policyId]);

    if ($affected === 1 && !$deleted) {
        echo "  ✓ Policy deleted successfully\n";
        echo "    - Affected rows: {$affected}\n";
        $testsPassed++;
    } else {
        echo "  ❌ Policy deletion failed\n";
        $testsFailed++;
    }

} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✓ Passed: {$testsPassed}/8\n";
echo "❌ Failed: {$testsFailed}/8\n\n";

$db->disconnect();

if ($testsFailed === 0) {
    echo "✅ All integration tests passed\n";
    exit(0);
} else {
    echo "❌ Some integration tests failed\n";
    exit(1);
}
?>
