<?php
/**
 * Learning Assessment - Helper (Final)
 * Provides functions to prepare and save assessment results
 */

if (!defined('MOODLE_INTERNAL')) {
    // Allow standalone include when called directly from the onboarding page
}

/**
 * Prepare assessment data object for DB insert
 * @param int $userid
 * @param array $results Expecting keys: '인지','감정','행동','전체' (0-5 scale)
 * @param array $answers Map of questionId => 1..5
 * @param array $qa_texts Map of 'qa01'..'qa16' => formatted QA string
 * @return stdClass
 */
function prepareAssessmentData($userid, array $results, array $answers, array $qa_texts) {
    $record = new stdClass();
    $record->userid = (int)$userid;

    // Scores (store as float 0..5)
    $record->cognitive_score  = isset($results['인지']) ? (float)$results['인지'] : 0.0;
    $record->emotional_score  = isset($results['감정']) ? (float)$results['감정'] : 0.0;
    $record->behavioral_score = isset($results['행동']) ? (float)$results['행동'] : 0.0;
    $record->overall_total    = isset($results['전체']) ? (float)$results['전체'] : 0.0;

    // QA texts (qa01..qa16)
    for ($i = 1; $i <= 16; $i++) {
        $field = sprintf('qa%02d', $i);
        if (array_key_exists($field, $qa_texts)) {
            $record->$field = (string)$qa_texts[$field];
        }
    }

    // Meta
    $record->created_at = time();
    $record->session_id = session_id() ?: '';

    return $record;
}

/**
 * Save assessment results into alt42o_learning_assessment_results
 * @param moodle_database $DB
 * @param stdClass $record
 * @return int Inserted ID
 */
function saveAssessmentResults($DB, $record) {
    // Ensure table exists (best-effort)
    try {
        if (method_exists($DB, 'get_manager')) {
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists(new xmldb_table('alt42o_learning_assessment_results'))) {
                // Minimal fallback: attempt to create with essential columns
                // If creation is not desired here, this block can be removed and rely on pre-created schema
            }
        }
    } catch (Exception $e) {
        // Ignore existence check errors
    }

    // Insert
    return (int)$DB->insert_record('alt42o_learning_assessment_results', $record);
}


