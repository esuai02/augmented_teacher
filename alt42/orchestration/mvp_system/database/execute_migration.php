<?php
/**
 * MVP System Database Migration Executor
 *
 * Executes SQL migration files to create required database tables
 *
 * Usage: php execute_migration.php [migration_file]
 * Example: php execute_migration.php migrations/001_create_tables.sql
 *
 * Error Location: /mvp_system/database/execute_migration.php
 */

// Include Moodle configuration
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

// Check if user has admin or manager privileges
if ($role !== 'admin' && $role !== 'manager') {
    die("ERROR [execute_migration.php:25]: Admin or manager privileges required for database migrations\n");
}

// Get migration file from command line argument or use default
$migration_file = isset($argv[1]) ? $argv[1] : 'migrations/001_create_tables.sql';

// Resolve absolute path
$base_dir = dirname(__FILE__);
$sql_file = $base_dir . '/' . $migration_file;

// Verify file exists
if (!file_exists($sql_file)) {
    die("ERROR [execute_migration.php:37]: Migration file not found: $sql_file\n");
}

echo "=== MVP System Database Migration ===\n";
echo "File: $sql_file\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Read SQL file
$sql_content = file_get_contents($sql_file);
if ($sql_content === false) {
    die("ERROR [execute_migration.php:47]: Failed to read migration file\n");
}

// Split into individual statements (separated by semicolons outside of quotes)
$statements = preg_split('/;[\s]*$/m', $sql_content, -1, PREG_SPLIT_NO_EMPTY);

$executed = 0;
$failed = 0;
$skipped = 0;

echo "Processing " . count($statements) . " SQL statements...\n\n";

foreach ($statements as $index => $statement) {
    $statement = trim($statement);

    // Skip empty statements and comments
    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
        $skipped++;
        continue;
    }

    // Extract table name for better logging
    $table_name = 'unknown';
    if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([^\s(]+)/i', $statement, $matches)) {
        $table_name = trim($matches[1], '`');
    } elseif (preg_match('/ALTER\s+TABLE\s+([^\s]+)/i', $statement, $matches)) {
        $table_name = trim($matches[1], '`');
    } elseif (preg_match('/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?([^\s;]+)/i', $statement, $matches)) {
        $table_name = trim($matches[1], '`');
    }

    echo "[" . ($index + 1) . "] Executing: " . substr($statement, 0, 60) . "...\n";
    echo "    Table: $table_name\n";

    try {
        // Execute using Moodle's DB manager for DDL statements
        $DB->change_database_structure($statement);
        echo "    ✅ SUCCESS\n\n";
        $executed++;
    } catch (Exception $e) {
        echo "    ❌ FAILED: " . $e->getMessage() . "\n";
        echo "    Error Location: execute_migration.php:line " . __LINE__ . "\n\n";
        $failed++;

        // Continue with other statements even if one fails
        // This allows partial migrations to complete
    }
}

echo "\n=== Migration Summary ===\n";
echo "Total Statements: " . count($statements) . "\n";
echo "Executed Successfully: $executed\n";
echo "Failed: $failed\n";
echo "Skipped (empty/comments): $skipped\n";

if ($failed > 0) {
    echo "\n⚠️  WARNING: Some statements failed. Check error messages above.\n";
    exit(1);
} else {
    echo "\n✅ Migration completed successfully!\n";

    // Verify tables were created
    echo "\n=== Verifying Created Tables ===\n";
    $required_tables = [
        'mvp_snapshot_metrics',
        'mvp_decision_log',
        'mvp_intervention_execution',
        'mvp_teacher_feedback',
        'mvp_system_metrics'
    ];

    $verified = 0;
    foreach ($required_tables as $table) {
        $exists = $DB->get_manager()->table_exists($table);
        $full_name = $CFG->prefix . $table;
        if ($exists) {
            echo "✅ $full_name - EXISTS\n";
            $verified++;
        } else {
            echo "❌ $full_name - NOT FOUND\n";
        }
    }

    echo "\nVerified: $verified / " . count($required_tables) . " tables\n";

    if ($verified === count($required_tables)) {
        echo "\n🎉 All required tables created successfully!\n";
        exit(0);
    } else {
        echo "\n⚠️  WARNING: Not all tables were created. Check migration SQL.\n";
        exit(1);
    }
}
