<?php
// 파일: mvp_system/database/migrate.php (Line 1)
// Mathking Agentic MVP System - Database Migration Script

echo "=== MVP System Database Migration ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Moodle DB Connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (!$DB) {
    die("ERROR: Moodle DB connection failed at " . __FILE__ . ":" . __LINE__ . "\n");
}

echo "✓ Moodle DB connection established\n";

// Load SQL file
$sql_file = __DIR__ . '/migrations/001_create_tables.sql';
if (!file_exists($sql_file)) {
    die("ERROR: Migration file not found at " . __FILE__ . ":" . __LINE__ . "\n");
}

$sql = file_get_contents($sql_file);
echo "✓ Migration SQL loaded (" . strlen($sql) . " bytes)\n\n";

// Split SQL into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) &&
               strpos($stmt, '--') !== 0 &&
               strlen($stmt) > 10;
    }
);

echo "Found " . count($statements) . " SQL statements\n\n";

// Execute each statement
$success_count = 0;
$error_count = 0;

foreach ($statements as $index => $statement) {
    // Extract table name for logging
    if (preg_match('/CREATE TABLE.*?\s+(\w+)/i', $statement, $matches)) {
        $table_name = $matches[1];
        echo "[$index] Creating table: $table_name... ";

        try {
            $DB->execute($statement);
            echo "✓ SUCCESS\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ FAILED\n";
            echo "    Error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
            $error_count++;
        }
    }
    elseif (preg_match('/INSERT INTO.*?\s+(\w+)/i', $statement, $matches)) {
        $table_name = $matches[1];
        echo "[$index] Inserting sample data into: $table_name... ";

        try {
            $DB->execute($statement);
            echo "✓ SUCCESS\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ FAILED (skipping seed data)\n";
            echo "    Error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
            // Don't count seed failures as critical
        }
    }
    else {
        // Other statements (indexes, etc.)
        echo "[$index] Executing statement... ";
        try {
            if (trim($statement)) {
                $DB->execute($statement);
                echo "✓ SUCCESS\n";
                $success_count++;
            } else {
                echo "⊘ SKIPPED (empty)\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED\n";
            echo "    Error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
            $error_count++;
        }
    }
}

echo "\n=== Migration Summary ===\n";
echo "Successful: $success_count\n";
echo "Failed: $error_count\n";

// Verify tables created
echo "\n=== Verification ===\n";
$tables = [
    'mdl_mvp_snapshot_metrics',
    'mdl_mvp_decision_log',
    'mdl_mvp_teacher_feedback',
    'mdl_mvp_intervention_execution',
    'mdl_mvp_system_metrics'
];

$tables_created = 0;
foreach ($tables as $table) {
    try {
        // Check if table exists by querying it
        $DB->get_records($table, [], '', '*', 0, 1);
        echo "✓ Table exists: $table\n";
        $tables_created++;
    } catch (Exception $e) {
        echo "✗ Table missing: $table at " . __FILE__ . ":" . __LINE__ . "\n";
    }
}

echo "\n=== Final Status ===\n";
if ($tables_created == count($tables)) {
    echo "✅ Migration SUCCESSFUL! All " . count($tables) . " tables created.\n";

    // Display sample data counts
    try {
        $metrics_count = $DB->count_records('mdl_mvp_snapshot_metrics');
        $decisions_count = $DB->count_records('mdl_mvp_decision_log');
        echo "\nSample data:\n";
        echo "  - Snapshot metrics: $metrics_count rows\n";
        echo "  - Decision log: $decisions_count rows\n";
    } catch (Exception $e) {
        echo "Note: Sample data may not be seeded (non-critical)\n";
    }

    echo "\nYou can now run the system:\n";
    echo "  php ../orchestrator.php 123\n\n";
    exit(0);
} else {
    echo "⚠️ Migration INCOMPLETE: $tables_created/" . count($tables) . " tables created.\n";
    echo "Please check errors above and retry.\n\n";
    exit(1);
}
?>
