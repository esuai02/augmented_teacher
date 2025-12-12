<?php
/**
 * Database Fix Script for Agent01 Onboarding
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/fix_db.php
 *
 * This script:
 * 1. Checks if required tables exist
 * 2. Creates alt42o_onboarding_reports table if missing
 * 3. Verifies table structure
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

// Check admin/teacher permission
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die(json_encode([
        'success' => false,
        'error' => 'Admin or teacher permission required',
        'file' => __FILE__,
        'line' => __LINE__
    ]));
}

$results = [];

try {
    $dbman = $DB->get_manager();

    // 1. Check mdl_alt42_student_profiles table
    $results['check_student_profiles'] = [
        'table' => 'mdl_alt42_student_profiles',
        'exists' => $DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles')),
        'note' => 'User provided this table structure earlier'
    ];

    // 2. Check mdl_abessi_mbtilog table
    $results['check_mbti_log'] = [
        'table' => 'mdl_abessi_mbtilog',
        'exists' => $DB->get_manager()->table_exists(new xmldb_table('abessi_mbtilog')),
        'note' => 'MBTI data source table'
    ];

    // 3. Check mdl_alt42o_onboarding_reports table
    $table = new xmldb_table('alt42o_onboarding_reports');
    $tableExists = $dbman->table_exists($table);

    $results['check_reports_table'] = [
        'table' => 'mdl_alt42o_onboarding_reports',
        'exists' => $tableExists
    ];

    // 4. Create alt42o_onboarding_reports if not exists
    if (!$tableExists) {
        $results['create_reports_table'] = [
            'action' => 'Creating mdl_alt42o_onboarding_reports table...'
        ];

        // Define table structure
        $table = new xmldb_table('alt42o_onboarding_reports');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('report_type', XMLDB_TYPE_CHAR, '50', null, null, null, 'initial');
        $table->add_field('info_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('assessment_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('report_content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('generated_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('generated_by', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, null, null, 'published');
        $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add indexes
        $table->add_index('idx_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, array('status'));
        $table->add_index('idx_generated_at', XMLDB_INDEX_NOTUNIQUE, array('generated_at'));

        // Create table
        $dbman->create_table($table);

        $results['create_reports_table']['success'] = true;
        $results['create_reports_table']['message'] = 'Table created successfully';
    } else {
        $results['create_reports_table'] = [
            'action' => 'Table already exists',
            'success' => true
        ];
    }

    // 5. Verify table structure
    if ($dbman->table_exists(new xmldb_table('alt42o_onboarding_reports'))) {
        $columns = $DB->get_columns('alt42o_onboarding_reports');
        $results['verify_structure'] = [
            'success' => true,
            'field_count' => count($columns),
            'fields' => array_keys($columns)
        ];
    }

    // 6. Check sample data
    $reportCount = $DB->count_records('alt42o_onboarding_reports');
    $results['data_check'] = [
        'total_reports' => $reportCount
    ];

    // Final summary
    $results['summary'] = [
        'success' => true,
        'tables_checked' => 3,
        'tables_created' => !$tableExists ? 1 : 0,
        'message' => 'Database verification complete'
    ];

} catch (Exception $e) {
    $results['error'] = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ];
}

// Output results
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
