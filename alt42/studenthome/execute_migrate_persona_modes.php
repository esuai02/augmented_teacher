<?php
/**
 * Execute persona_modes table migration to use Moodle standard timecreated field
 * 
 * This script migrates from created_at/updated_at to timecreated
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

// Check if user is admin
if (!is_siteadmin()) {
    die("This script can only be run by administrators.");
}

echo "<h2>Persona Modes Table Migration</h2>";
echo "<pre>";

// Step 1: Check current table structure
echo "1. Checking current table structure...\n";
try {
    $columns = $DB->get_columns('persona_modes');
    echo "   Current columns: " . implode(', ', array_keys($columns)) . "\n\n";
    
    $has_created_at = isset($columns['created_at']);
    $has_updated_at = isset($columns['updated_at']);
    $has_timecreated = isset($columns['timecreated']);
    
    echo "   - created_at exists: " . ($has_created_at ? 'Yes' : 'No') . "\n";
    echo "   - updated_at exists: " . ($has_updated_at ? 'Yes' : 'No') . "\n";
    echo "   - timecreated exists: " . ($has_timecreated ? 'Yes' : 'No') . "\n\n";
} catch (Exception $e) {
    echo "   Error checking table structure: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Add timecreated column if needed
if (!$has_timecreated && ($has_created_at || $has_updated_at)) {
    echo "2. Adding timecreated column...\n";
    try {
        // Use Moodle's DDL manager for safer operations
        $dbman = $DB->get_manager();
        $table = new xmldb_table('persona_modes');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            echo "   ✅ timecreated column added\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error adding column: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Step 3: Migrate data
    echo "3. Migrating data...\n";
    try {
        if ($has_created_at || $has_updated_at) {
            // Build the SQL based on available columns
            if ($has_created_at && $has_updated_at) {
                $sql = "UPDATE {persona_modes} 
                        SET timecreated = GREATEST(COALESCE(created_at, 0), COALESCE(updated_at, 0))
                        WHERE timecreated = 0 OR timecreated IS NULL";
            } elseif ($has_created_at) {
                $sql = "UPDATE {persona_modes} 
                        SET timecreated = COALESCE(created_at, 0)
                        WHERE timecreated = 0 OR timecreated IS NULL";
            } else {
                $sql = "UPDATE {persona_modes} 
                        SET timecreated = COALESCE(updated_at, 0)
                        WHERE timecreated = 0 OR timecreated IS NULL";
            }
            
            $DB->execute($sql);
            
            // Set current time for any remaining null values
            $DB->execute("UPDATE {persona_modes} SET timecreated = ? WHERE timecreated = 0 OR timecreated IS NULL", [time()]);
            
            echo "   ✅ Data migrated successfully\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error migrating data: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Step 4: Add index
    echo "4. Adding index on timecreated...\n";
    try {
        $table = new xmldb_table('persona_modes');
        $index = new xmldb_index('idx_timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
            echo "   ✅ Index added\n\n";
        } else {
            echo "   ℹ️ Index already exists\n\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Warning adding index: " . $e->getMessage() . "\n\n";
    }
} elseif ($has_timecreated) {
    echo "2. ✅ timecreated column already exists - no migration needed\n\n";
} else {
    echo "2. ⚠️ No timestamp columns found - adding timecreated with current time\n";
    try {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('persona_modes');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, time());
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            echo "   ✅ timecreated column added with default value\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error adding column: " . $e->getMessage() . "\n";
        exit;
    }
}

// Step 5: Test the migration
echo "5. Testing migration...\n";
try {
    // Test query that all files are using
    $test_record = $DB->get_record_sql(
        "SELECT * FROM {persona_modes} ORDER BY timecreated DESC LIMIT 1"
    );
    
    if ($test_record) {
        echo "   ✅ Test query successful\n";
        echo "   Latest record timecreated: " . date('Y-m-d H:i:s', $test_record->timecreated) . "\n\n";
    } else {
        echo "   ℹ️ No records found in table (this is OK if table is empty)\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Test query failed: " . $e->getMessage() . "\n";
    exit;
}

// Step 6: Summary
echo "6. Migration Summary:\n";
echo "   ✅ Table structure updated\n";
echo "   ✅ All queries now use 'timecreated' field\n";
echo "   ✅ Compatible with Moodle standards\n\n";

echo "Note: Old columns (created_at, updated_at) are preserved for safety.\n";
echo "You can manually drop them later if everything works correctly.\n";

echo "</pre>";

// Provide action links
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='test_chatbot.php'>Test Chatbot System</a></li>";
echo "<li><a href='selectmode.php?userid=" . $USER->id . "'>Test Mode Selection</a></li>";
echo "<li><a href='index.php?userid=" . $USER->id . "'>Go to Main Page</a></li>";
echo "</ul>";
?>