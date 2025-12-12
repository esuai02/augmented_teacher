<?php
/**
 * Agent01 Onboarding - Diagnostics API
 * Query onboarding-related tables for a given userid
 * GET params: userid (required)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

try {
    require_login();

    $userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
    if ($userid <= 0) {
        throw new Exception('Invalid or missing userid');
    }

    $result = [
        'success' => true,
        'userid' => $userid,
        'tables' => []
    ];

    // mdl_user (basic)
    $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname, email, lastaccess');
    $result['tables']['mdl_user'] = [
        'exists' => (bool)$user,
        'record' => $user
    ];

    // mdl_alt42_student_profiles
    $profile = null;
    try {
        if ($DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
            $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $userid]);
        }
    } catch (Exception $e) { /* ignore */ }
    $result['tables']['mdl_alt42_student_profiles'] = [
        'exists' => $profile !== null,
        'record' => $profile
    ];

    // mdl_abessi_mbtilog (latest)
    $mbti = $DB->get_record_sql(
        "SELECT * FROM {abessi_mbtilog} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
        [$userid]
    );
    $result['tables']['mdl_abessi_mbtilog'] = [
        'exists' => (bool)$mbti,
        'latest' => $mbti
    ];

    // mdl_alt42o_learning_assessment_results (latest)
    $assessment = $DB->get_record_sql(
        "SELECT * FROM {alt42o_learning_assessment_results} WHERE userid = ? ORDER BY created_at DESC LIMIT 1",
        [$userid]
    );
    $result['tables']['mdl_alt42o_learning_assessment_results'] = [
        'exists' => (bool)$assessment,
        'latest' => $assessment
    ];

    // mdl_alt42o_onboarding_reports (latest)
    $report = $DB->get_record_sql(
        "SELECT id, userid, report_type, generated_at, status FROM {alt42o_onboarding_reports} WHERE userid = ? ORDER BY generated_at DESC LIMIT 1",
        [$userid]
    );
    $result['tables']['mdl_alt42o_onboarding_reports'] = [
        'exists' => (bool)$report,
        'latest' => $report
    ];

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}


