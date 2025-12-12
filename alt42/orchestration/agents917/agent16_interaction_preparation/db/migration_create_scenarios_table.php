<?php
/**
 * Agent 16 Interaction Preparation - DB Migration Script
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/db/migration_create_scenarios_table.php
 *
 * Purpose: Create interaction_scenarios table for storing generated scenarios
 * Run this script once to create the database table
 *
 * Usage: Access via browser: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/db/migration_create_scenarios_table.php
 */

require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Check if user has admin privileges
require_capability('moodle/site:config', context_system::instance());

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Agent 16 DB Migration</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:4px;}</style>\n";
echo "</head>\n<body>\n";
echo "<h1>Agent 16 Interaction Preparation - DB Migration</h1>\n";

try {
    // Table name
    $tableName = 'agent16_interaction_scenarios';

    // Check if table already exists
    $dbman = $DB->get_manager();
    $table = new xmldb_table($tableName);

    if ($dbman->table_exists($table)) {
        echo "<p class='info'>ℹ️ Table '{$tableName}' already exists. Skipping creation.</p>\n";
        echo "<p>If you want to recreate the table, drop it manually first.</p>\n";
    } else {
        // Define table structure
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('guide_mode', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('vibe_coding_prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('db_tracking_prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('scenario', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Define primary key
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Define indexes
        $table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('guide_mode_idx', XMLDB_INDEX_NOTUNIQUE, ['guide_mode']);
        $table->add_index('created_at_idx', XMLDB_INDEX_NOTUNIQUE, ['created_at']);

        // Create table
        $dbman->create_table($table);

        echo "<p class='success'>✅ Table '{$tableName}' created successfully!</p>\n";
    }

    // Display table structure
    echo "<h2>Table Structure</h2>\n";
    echo "<pre>\n";
    echo "Table: {$tableName}\n\n";
    echo "Columns:\n";
    echo "  - id (INT, AUTO_INCREMENT, PRIMARY KEY)\n";
    echo "  - userid (INT, NOT NULL) - References Moodle user ID\n";
    echo "  - guide_mode (VARCHAR(50), NOT NULL) - Selected interaction mode\n";
    echo "  - vibe_coding_prompt (TEXT) - VibeCoding context prompt\n";
    echo "  - db_tracking_prompt (TEXT) - DBTracking data prompt\n";
    echo "  - scenario (TEXT, NOT NULL) - Generated scenario in markdown\n";
    echo "  - created_at (INT, NOT NULL) - Unix timestamp\n";
    echo "  - updated_at (INT) - Unix timestamp for updates\n\n";
    echo "Indexes:\n";
    echo "  - userid_idx on (userid)\n";
    echo "  - guide_mode_idx on (guide_mode)\n";
    echo "  - created_at_idx on (created_at)\n";
    echo "</pre>\n";

    // Test database connection
    echo "<h2>Database Connection Test</h2>\n";
    $testResult = $DB->get_records($tableName, null, 'id DESC', '*', 0, 1);
    echo "<p class='success'>✅ Database connection successful!</p>\n";
    echo "<p>Table '{$tableName}' is ready to use.</p>\n";
    echo "<p>Current row count: " . $DB->count_records($tableName) . "</p>\n";

    echo "<h2>Next Steps</h2>\n";
    echo "<ul>\n";
    echo "<li>Table is now ready to store interaction scenarios</li>\n";
    echo "<li>save_scenario.php API will use this table</li>\n";
    echo "<li>Result tab will query this table to display saved scenarios</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<p class='error'>❌ Migration failed!</p>\n";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>File: " . __FILE__ . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";

    echo "<h3>Stack Trace:</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "</body>\n</html>";
