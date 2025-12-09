<?php
/**
 * Database setup script for Agent01 Onboarding Reports
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/db_setup.php
 * Location: Line 1
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get user role
$userrole = $DB->get_record_sql(
    "SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid='22'",
    array($USER->id)
);
$role = $userrole ? $userrole->data : '';

// Only allow admin/teacher roles
if (!in_array($role, ['admin', 'teacher'])) {
    die(json_encode([
        'success' => false,
        'error' => 'Permission denied - admin/teacher only',
        'file' => __FILE__,
        'line' => __LINE__
    ]));
}

try {
    $dbman = $DB->get_manager();

    // Define table structure
    $table = new xmldb_table('alt42o_onboarding_reports');

    // Add fields
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('report_type', XMLDB_TYPE_CHAR, '50', null, null, null, 'initial');
    $table->add_field('info_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('assessment_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('report_content', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('generated_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('generated_by', XMLDB_TYPE_CHAR, '100', null, null, null, 'agent01_onboarding');
    $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, null, null, 'draft');
    $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);

    // Add keys
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    // Add indexes
    $table->add_index('idx_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
    $table->add_index('idx_generated_at', XMLDB_INDEX_NOTUNIQUE, array('generated_at'));
    $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, array('status'));

    // Create table if it doesn't exist
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo json_encode([
            'success' => true,
            'message' => 'Table alt42o_onboarding_reports created successfully',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Table alt42o_onboarding_reports already exists',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__,
        'trace' => $e->getTraceAsString()
    ]);
}
