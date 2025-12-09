<?php
// File: mvp_system/database/fix_missing_indexes.php
// Repair script to add missing PRIMARY KEY and idx_active index
// This fixes the issue where multiple indexes in CREATE TABLE failed

echo "=== Policy Versions Index Repair ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Moodle DB Connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (!$DB) {
    die("ERROR: Moodle DB connection failed at " . __FILE__ . ":" . __LINE__ . "\n");
}

echo "✓ Moodle DB connection established\n";
echo "✓ MySQL Version: " . $DB->get_server_info()['version'] . "\n\n";

// Check current index status
echo "=== Current Index Status ===\n";
$indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");

$index_names = [];
foreach ($indexes as $idx) {
    if (!in_array($idx->key_name, $index_names)) {
        $index_names[] = $idx->key_name;
    }
}

// Check PRIMARY KEY via column information (more reliable than SHOW INDEXES)
$id_columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'id'");
$has_primary_via_column = false;
if (!empty($id_columns)) {
    $id_col = reset($id_columns);
    $has_primary_via_column = (isset($id_col->key) && $id_col->key === 'PRI');
}

// PRIMARY KEY exists if found in either SHOW INDEXES or column info
$has_primary = in_array('PRIMARY', $index_names) || $has_primary_via_column;
$has_idx_active = in_array('idx_active', $index_names);
$has_idx_hash = in_array('idx_hash', $index_names);

echo "Current indexes (from SHOW INDEXES): " . implode(', ', $index_names) . "\n";
if ($has_primary_via_column && !in_array('PRIMARY', $index_names)) {
    echo "  Note: PRIMARY KEY detected via column information (id column has PRI key)\n";
}
echo "  PRIMARY: " . ($has_primary ? "✓ Present" : "❌ Missing") . "\n";
echo "  idx_active: " . ($has_idx_active ? "✓ Present" : "❌ Missing") . "\n";
echo "  idx_hash: " . ($has_idx_hash ? "✓ Present" : "❌ Missing") . "\n\n";

// Add missing PRIMARY KEY if needed
if (!$has_primary) {
    echo "=== Adding PRIMARY KEY ===\n";
    try {
        // First check if id column exists and is suitable for PRIMARY KEY
        $columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'id'");
        if (empty($columns)) {
            echo "❌ ERROR: id column does not exist at " . __FILE__ . ":" . __LINE__ . "\n";
            exit(1);
        }

        $id_col = reset($columns);
        if ($id_col->null !== 'NO' || stripos($id_col->extra, 'auto_increment') === false) {
            echo "⚠️  WARNING: id column is not properly configured at " . __FILE__ . ":" . __LINE__ . "\n";
            echo "   NULL: {$id_col->null}, Extra: {$id_col->extra}\n";
        }

        // Add PRIMARY KEY
        $DB->execute("ALTER TABLE mdl_mvp_policy_versions ADD PRIMARY KEY (id)");
        echo "✓ PRIMARY KEY added successfully\n\n";
    } catch (Exception $e) {
        echo "❌ Failed to add PRIMARY KEY at " . __FILE__ . ":" . __LINE__ . "\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
        exit(1);
    }
} else {
    echo "=== PRIMARY KEY Check ===\n";
    echo "✓ PRIMARY KEY already exists, skipping\n\n";
}

// Check and fix idx_active structure
$idx_active_needs_fix = false;
if ($has_idx_active) {
    // Check if idx_active has correct structure
    $idx_active_cols = [];
    foreach ($indexes as $idx) {
        if ($idx->key_name === 'idx_active') {
            $idx_active_cols[] = $idx->column_name;
        }
    }
    
    // Sort by sequence to get correct order
    $idx_active_details = [];
    foreach ($indexes as $idx) {
        if ($idx->key_name === 'idx_active') {
            $idx_active_details[] = ['col' => $idx->column_name, 'seq' => $idx->seq_in_index];
        }
    }
    usort($idx_active_details, function($a, $b) {
        return $a['seq'] - $b['seq'];
    });
    $idx_active_col_names = array_map(function($d) { return $d['col']; }, $idx_active_details);
    
    // Verify structure: should be (is_active, policy_source)
    if (count($idx_active_col_names) !== 2 || 
        $idx_active_col_names[0] !== 'is_active' || 
        $idx_active_col_names[1] !== 'policy_source') {
        $idx_active_needs_fix = true;
        echo "=== idx_active Structure Issue Detected ===\n";
        echo "  Current structure: (" . implode(', ', $idx_active_col_names) . ")\n";
        echo "  Expected structure: (is_active, policy_source)\n";
        echo "  Will recreate index...\n\n";
    }
}

if (!$has_idx_active || $idx_active_needs_fix) {
    echo "=== Adding/Recreating idx_active Index ===\n";
    try {
        // Verify required columns exist
        $is_active_col = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'is_active'");
        $policy_source_col = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'policy_source'");

        if (empty($is_active_col)) {
            echo "❌ ERROR: is_active column does not exist at " . __FILE__ . ":" . __LINE__ . "\n";
            exit(1);
        }
        if (empty($policy_source_col)) {
            echo "❌ ERROR: policy_source column does not exist at " . __FILE__ . ":" . __LINE__ . "\n";
            exit(1);
        }

        // Drop existing index if it has wrong structure - try multiple times to ensure it's gone
        if ($idx_active_needs_fix || $has_idx_active) {
            // MySQL 5.7 doesn't support IF EXISTS in DROP INDEX, so try without it
            try {
                $DB->execute("ALTER TABLE mdl_mvp_policy_versions DROP INDEX idx_active");
                echo "✓ Dropped existing idx_active index\n";
            } catch (Exception $e) {
                // Index might not exist, check if error is about missing index
                $error_msg = $e->getMessage();
                if (stripos($error_msg, 'does not exist') !== false || 
                    stripos($error_msg, 'Unknown key') !== false ||
                    stripos($error_msg, 'Can\'t DROP') !== false) {
                    echo "  Note: idx_active index does not exist (or already dropped)\n";
                } else {
                    echo "  ⚠️  Warning: Could not drop idx_active: " . $e->getMessage() . "\n";
                    echo "     Continuing anyway...\n";
                }
            }
        }

        // Wait a moment for MySQL to process the drop
        usleep(100000); // 0.1 second

        // Add composite index with correct structure
        // Use explicit syntax to ensure both columns are included
        try {
            $DB->execute("ALTER TABLE mdl_mvp_policy_versions ADD INDEX idx_active (is_active, policy_source)");
            echo "✓ idx_active composite index added successfully\n";
            echo "  Columns: (is_active, policy_source)\n";
        } catch (Exception $e) {
            // If it fails, try creating index with CREATE INDEX syntax
            echo "  Note: ALTER TABLE ADD INDEX failed, trying CREATE INDEX...\n";
            try {
                $DB->execute("CREATE INDEX idx_active ON mdl_mvp_policy_versions (is_active, policy_source)");
                echo "✓ idx_active composite index created successfully (using CREATE INDEX)\n";
                echo "  Columns: (is_active, policy_source)\n";
            } catch (Exception $e2) {
                throw new Exception("Both ALTER TABLE and CREATE INDEX failed: " . $e2->getMessage());
            }
        }
        
        // Wait a moment for MySQL to process the creation
        usleep(100000); // 0.1 second
        
        // Force MySQL to update table metadata (may fail if no RELOAD privilege)
        try {
            $DB->execute("FLUSH TABLES");
        } catch (Exception $e) {
            // FLUSH TABLES might require RELOAD privilege, continue anyway
        }
        
        // Verify the index was created correctly - reload indexes with fresh query
        sleep(1); // Give MySQL time to update metadata
        $verify_indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Key_name = 'idx_active' ORDER BY seq_in_index");
        
        // Debug: Show all indexes for debugging
        $all_indexes_debug = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");
        echo "  Debug: All indexes on table:\n";
        foreach ($all_indexes_debug as $dbg_idx) {
            echo "    - {$dbg_idx->key_name} on {$dbg_idx->column_name} (seq: {$dbg_idx->seq_in_index})\n";
        }
        
        $verify_cols = [];
        foreach ($verify_indexes as $v_idx) {
            $verify_cols[] = ['col' => $v_idx->column_name, 'seq' => $v_idx->seq_in_index];
        }
        usort($verify_cols, function($a, $b) {
            return $a['seq'] - $b['seq'];
        });
        $verify_col_names = array_map(function($d) { return $d['col']; }, $verify_cols);
        
        if (count($verify_col_names) === 2 && 
            $verify_col_names[0] === 'is_active' && 
            $verify_col_names[1] === 'policy_source') {
            echo "  ✓ Verified: Index structure is correct\n\n";
        } else {
            echo "  ⚠️  Warning: Index structure verification failed at " . __FILE__ . ":" . __LINE__ . "\n";
            echo "     Expected: (is_active, policy_source)\n";
            echo "     Found: (" . implode(', ', $verify_col_names) . ")\n";
            echo "     Debug: Found " . count($verify_indexes) . " index rows\n";
            if (empty($verify_indexes)) {
                echo "     Error: No idx_active index found after creation!\n";
                echo "     This might indicate the index creation failed silently.\n";
            } else {
                echo "     Error: Index structure does not match expected format.\n";
                echo "     This might be a MySQL 5.7 issue. Please check the table manually.\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "❌ Failed to add idx_active at " . __FILE__ . ":" . __LINE__ . "\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
        exit(1);
    }
} else {
    echo "=== idx_active Check ===\n";
    echo "✓ idx_active already exists with correct structure, skipping\n\n";
}

// Add missing idx_hash if needed
if (!$has_idx_hash) {
    echo "=== Adding idx_hash Index ===\n";
    try {
        // Verify required column exists
        $version_hash_col = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'version_hash'");
        
        if (empty($version_hash_col)) {
            echo "❌ ERROR: version_hash column does not exist at " . __FILE__ . ":" . __LINE__ . "\n";
            exit(1);
        }

        $vh_col = reset($version_hash_col);
        if (isset($vh_col->key) && $vh_col->key === 'MUL') {
            echo "  Note: version_hash column has MUL key (might have existing index)\n";
        }

        // Reload indexes to get current state (in case idx_active was just recreated)
        $current_indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");
        
        // Check if version_hash has any existing index (might have different name)
        $version_hash_indexes = [];
        foreach ($current_indexes as $idx) {
            if ($idx->column_name === 'version_hash' && $idx->key_name !== 'PRIMARY') {
                if (!in_array($idx->key_name, $version_hash_indexes)) {
                    $version_hash_indexes[] = $idx->key_name;
                }
            }
        }
        
        // Also check if there's an unnamed index (MySQL might create implicit indexes)
        // This can happen if the column was part of a foreign key or unique constraint
        if (empty($version_hash_indexes) && isset($vh_col->key) && $vh_col->key === 'MUL') {
            echo "  Warning: Column has MUL key but no named index found in SHOW INDEXES\n";
            echo "  This might indicate an implicit index. Trying to create idx_hash anyway...\n";
        }

        // Drop existing indexes on version_hash if they exist (but not idx_hash)
        if (!empty($version_hash_indexes)) {
            echo "  Found existing index(es) on version_hash: " . implode(', ', $version_hash_indexes) . "\n";
            foreach ($version_hash_indexes as $old_idx_name) {
                if ($old_idx_name !== 'idx_hash') {
                    try {
                        $DB->execute("ALTER TABLE mdl_mvp_policy_versions DROP INDEX " . $old_idx_name);
                        echo "  ✓ Dropped existing index: $old_idx_name\n";
                        // Force table metadata update (may fail if no RELOAD privilege)
                        try {
                            $DB->execute("FLUSH TABLES");
                        } catch (Exception $flush_e) {
                            // Continue anyway
                        }
                    } catch (Exception $e) {
                        echo "  ⚠️  Warning: Could not drop index $old_idx_name at " . __FILE__ . ":" . __LINE__ . "\n";
                        echo "     Error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }

        // Check if idx_hash already exists (double check after reload)
        sleep(1); // Give MySQL time to update metadata
        $recheck_hash = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Key_name = 'idx_hash'");
        if (!empty($recheck_hash)) {
            echo "  ✓ idx_hash already exists, skipping creation\n\n";
        } else {
            // Try to add idx_hash index with multiple methods
            $hash_created = false;
            
            // Method 1: ALTER TABLE ADD INDEX
            try {
                $DB->execute("ALTER TABLE mdl_mvp_policy_versions ADD INDEX idx_hash (version_hash)");
                echo "✓ idx_hash index added successfully (using ALTER TABLE)\n";
                echo "  Column: (version_hash)\n\n";
                $hash_created = true;
            } catch (Exception $e1) {
                // Method 2: CREATE INDEX
                echo "  Note: ALTER TABLE ADD INDEX failed, trying CREATE INDEX...\n";
                echo "  Error: " . $e1->getMessage() . "\n";
                try {
                    $DB->execute("CREATE INDEX idx_hash ON mdl_mvp_policy_versions (version_hash)");
                    echo "✓ idx_hash index created successfully (using CREATE INDEX)\n";
                    echo "  Column: (version_hash)\n\n";
                    $hash_created = true;
                } catch (Exception $e2) {
                    // Show detailed error
                    echo "  ❌ CREATE INDEX also failed\n";
                    echo "  Error: " . $e2->getMessage() . "\n";
                    
                    // Check if there's a constraint or other issue
                    $error_msg = $e2->getMessage();
                    if (stripos($error_msg, 'duplicate') !== false || 
                        stripos($error_msg, 'already exists') !== false ||
                        stripos($error_msg, 'Duplicate key') !== false) {
                        echo "  Note: Index might already exist. Checking again...\n";
                        sleep(1);
                        $final_check = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Key_name = 'idx_hash' OR Column_name = 'version_hash'");
                        if (!empty($final_check)) {
                            echo "  Found index(es) related to version_hash:\n";
                            foreach ($final_check as $idx) {
                                echo "    - {$idx->key_name} on {$idx->column_name}\n";
                            }
                            echo "  Skipping creation (index already exists)\n\n";
                            $hash_created = true;
                        }
                    }
                    
                    if (!$hash_created) {
                        throw $e2;
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Failed to add idx_hash at " . __FILE__ . ":" . __LINE__ . "\n";
        echo "   Error: " . $e->getMessage() . "\n";
        
        // Try to get more detailed error information
        $error_msg = $e->getMessage();
        if (stripos($error_msg, 'duplicate') !== false || stripos($error_msg, 'already exists') !== false) {
            echo "   Note: Index might already exist with different name. Checking...\n";
            $hash_indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions WHERE Column_name = 'version_hash'");
            if (!empty($hash_indexes)) {
                echo "   Found index(es) on version_hash:\n";
                foreach ($hash_indexes as $idx) {
                    echo "     - {$idx->key_name} on {$idx->column_name}\n";
                }
            }
        }
        echo "\n";
        exit(1);
    }
} else {
    echo "=== idx_hash Check ===\n";
    echo "✓ idx_hash already exists, skipping\n\n";
}

// Verify final state
echo "=== Final Verification ===\n";
$final_indexes = $DB->get_records_sql("SHOW INDEXES FROM mdl_mvp_policy_versions");

$final_index_names = [];
foreach ($final_indexes as $idx) {
    if (!in_array($idx->key_name, $final_index_names)) {
        $final_index_names[] = $idx->key_name;
    }
}

// Check PRIMARY KEY via column information
$final_id_columns = $DB->get_records_sql("SHOW COLUMNS FROM mdl_mvp_policy_versions WHERE Field = 'id'");
$final_has_primary_via_column = false;
if (!empty($final_id_columns)) {
    $final_id_col = reset($final_id_columns);
    $final_has_primary_via_column = (isset($final_id_col->key) && $final_id_col->key === 'PRI');
}

$final_has_primary = in_array('PRIMARY', $final_index_names) || $final_has_primary_via_column;
$final_has_idx_active = in_array('idx_active', $final_index_names);
$final_has_idx_hash = in_array('idx_hash', $final_index_names);

// Verify idx_active structure
$final_idx_active_correct = false;
if ($final_has_idx_active) {
    $final_idx_active_details = [];
    foreach ($final_indexes as $idx) {
        if ($idx->key_name === 'idx_active') {
            $final_idx_active_details[] = ['col' => $idx->column_name, 'seq' => $idx->seq_in_index];
        }
    }
    usort($final_idx_active_details, function($a, $b) {
        return $a['seq'] - $b['seq'];
    });
    $final_idx_active_col_names = array_map(function($d) { return $d['col']; }, $final_idx_active_details);
    
    $final_idx_active_correct = (count($final_idx_active_col_names) === 2 && 
        $final_idx_active_col_names[0] === 'is_active' && 
        $final_idx_active_col_names[1] === 'policy_source');
}

echo "Final indexes (from SHOW INDEXES): " . implode(', ', $final_index_names) . "\n";
if ($final_has_primary_via_column && !in_array('PRIMARY', $final_index_names)) {
    echo "  Note: PRIMARY KEY confirmed via column information\n";
}
echo "  PRIMARY: " . ($final_has_primary ? "✓ Present" : "❌ Missing") . "\n";
echo "  idx_active: " . ($final_has_idx_active ? "✓ Present" : "❌ Missing");
if ($final_has_idx_active) {
    if ($final_idx_active_correct) {
        echo " (structure: ✓ correct)";
    } else {
        echo " (structure: ❌ incorrect)";
    }
}
echo "\n";
echo "  idx_hash: " . ($final_has_idx_hash ? "✓ Present" : "❌ Missing") . "\n\n";

if ($final_has_primary && $final_has_idx_active && $final_has_idx_hash && $final_idx_active_correct) {
    echo "=== Repair Summary ===\n";
    echo "✅ All indexes successfully repaired!\n";
    echo "   - PRIMARY KEY (id)\n";
    echo "   - idx_active (is_active, policy_source)\n";
    echo "   - idx_hash (version_hash)\n\n";

    echo "Next step: Run verification script to confirm:\n";
    echo "  https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/verify_policy_versions_table.php\n\n";
} else {
    echo "=== Repair Failed ===\n";
    echo "❌ Some indexes are still missing at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "   Please check error messages above and try manual index creation.\n\n";
    exit(1);
}

echo "Repair completed at " . date('Y-m-d H:i:s') . "\n";
exit(0);
?>
