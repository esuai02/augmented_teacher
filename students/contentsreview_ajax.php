<?php
/**
 * AJAX Endpoint for Content Review System
 *
 * PURPOSE: Handle review submission and retrieval via AJAX
 *
 * ACTIONS:
 *   - submit_review: Save review to mdl_abessi_content_reviews
 *   - get_review: Retrieve existing review for a content item
 *
 * AUTHOR: Claude Code Assistant
 * DATE: 2025-01-29
 * VERSION: 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// Error handling wrapper
try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if (empty($action)) {
        throw new Exception('No action specified [contentsreview_ajax.php:26]');
    }

    switch($action) {
        case 'submit_review':
            handleSubmitReview($DB, $USER);
            break;

        case 'get_review':
            handleGetReview($DB, $USER);
            break;

        default:
            throw new Exception("Invalid action: $action [contentsreview_ajax.php:38]");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Handle review submission
 * Creates new review record and history entry
 */
function handleSubmitReview($DB, $USER) {
    // Validate required inputs
    $contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
    $cmid = isset($_POST['cmid']) ? intval($_POST['cmid']) : 0;
    $pagenum = isset($_POST['pagenum']) ? intval($_POST['pagenum']) : 0;
    $review_level = $_POST['review_level'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $improvements = $_POST['improvements'] ?? '';
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $wboard_id = $_POST['wboard_id'] ?? '';

    // Validation
    if ($contentsid === 0) {
        throw new Exception('Invalid contentsid [contentsreview_ajax.php:72]');
    }

    if ($cmid === 0) {
        throw new Exception('Invalid cmid [contentsreview_ajax.php:76]');
    }

    if (!in_array($review_level, ['L1', 'L2', 'L3', 'L4', 'L5'])) {
        throw new Exception("Invalid review level: $review_level [contentsreview_ajax.php:80]");
    }

    // Determine review status based on level
    $review_status = ($review_level === 'L5') ? 'approved' : 'pending';

    // Check for existing review BY THIS REVIEWER using raw SQL (bypass Moodle cache)
    // Each reviewer can have their own latest review (multi-reviewer support)
    $existing = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_content_reviews
         WHERE contentsid = ? AND reviewer_id = ? AND is_latest = 1",
        [$contentsid, $USER->id]
    );

    if ($existing) {
        // Mark THIS REVIEWER's old review as not latest (other reviewers unaffected)
        $updateSql = "UPDATE mdl_abessi_content_reviews SET is_latest = 0 WHERE id = ?";
        $DB->execute($updateSql, [$existing->id]);
        $version = $existing->version + 1;
    } else {
        $version = 1;
    }

    // Get reviewer role from user profile
    $userrole = $DB->get_record_sql(
        "SELECT data AS role FROM mdl_user_info_data
         WHERE userid=? AND fieldid='22'",
        [$USER->id]
    );
    $reviewer_role = ($userrole && isset($userrole->role)) ? $userrole->role : 'teacher';

    // Prepare review record
    $record = new stdClass();
    $record->contentsid = $contentsid;
    $record->cmid = $cmid;
    $record->pagenum = $pagenum;
    $record->review_level = $review_level;
    $record->review_status = $review_status;
    $record->feedback = $feedback;
    $record->improvements = $improvements;
    $record->reviewer_id = $USER->id;
    $record->reviewer_name = $USER->firstname . ' ' . $USER->lastname;
    $record->reviewer_role = $reviewer_role;
    $record->student_id = $student_id;
    $record->wboard_id = $wboard_id;
    $record->timecreated = time();
    $record->timemodified = time();
    $record->version = $version;
    $record->is_latest = 1;

    // Insert review record using raw SQL (bypass Moodle cache)
    $insertSql = "INSERT INTO mdl_abessi_content_reviews (
        contentsid, cmid, pagenum, review_level, review_status,
        feedback, improvements, reviewer_id, reviewer_name, reviewer_role,
        student_id, wboard_id, timecreated, timemodified, version, is_latest
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $DB->execute($insertSql, [
        $record->contentsid,
        $record->cmid,
        $record->pagenum,
        $record->review_level,
        $record->review_status,
        $record->feedback,
        $record->improvements,
        $record->reviewer_id,
        $record->reviewer_name,
        $record->reviewer_role,
        $record->student_id,
        $record->wboard_id,
        $record->timecreated,
        $record->timemodified,
        $record->version,
        $record->is_latest
    ]);

    // Get last inserted ID
    $review_id = $DB->get_field_sql("SELECT LAST_INSERT_ID()");

    if (!$review_id) {
        throw new Exception('Failed to insert review record [contentsreview_ajax.php:145]');
    }

    // Prepare history record
    $history = new stdClass();
    $history->review_id = $review_id;
    $history->contentsid = $contentsid;
    $history->action_type = $existing ? 'updated' : 'created';
    $history->old_level = $existing ? $existing->review_level : null;
    $history->new_level = $review_level;
    $history->old_status = $existing ? $existing->review_status : null;
    $history->new_status = $review_status;

    if ($existing) {
        $history->change_summary = "Review updated from {$history->old_level} to {$history->new_level}";
    } else {
        $history->change_summary = "Initial review created at level {$review_level}";
    }

    $history->changed_by = $USER->id;
    $history->changed_by_name = $USER->firstname . ' ' . $USER->lastname;
    $history->timecreated = time();

    // Insert history record using raw SQL (bypass Moodle cache)
    $historySql = "INSERT INTO mdl_abessi_review_history (
        review_id, contentsid, action_type, old_level, new_level,
        old_status, new_status, change_summary, changed_by, changed_by_name, timecreated
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $DB->execute($historySql, [
        $history->review_id,
        $history->contentsid,
        $history->action_type,
        $history->old_level,
        $history->new_level,
        $history->old_status,
        $history->new_status,
        $history->change_summary,
        $history->changed_by,
        $history->changed_by_name,
        $history->timecreated
    ]);

    // Get last inserted ID for history
    $history_id = $DB->get_field_sql("SELECT LAST_INSERT_ID()");

    if (!$history_id) {
        throw new Exception('Failed to insert history record [contentsreview_ajax.php:195]');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'review_id' => $review_id,
        'history_id' => $history_id,
        'status' => $review_status,
        'version' => $version,
        'message' => '검수가 성공적으로 저장되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get existing review for a content item
 * Returns the latest review if exists
 */
function handleGetReview($DB, $USER) {
    $contentsid = isset($_GET['contentsid']) ? intval($_GET['contentsid']) : 0;

    if ($contentsid === 0) {
        throw new Exception('Invalid contentsid [contentsreview_ajax.php:191]');
    }

    // Get latest review for this content using raw SQL (bypass Moodle cache)
    $review = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_content_reviews WHERE contentsid = ? AND is_latest = 1",
        [$contentsid]
    );

    if ($review) {
        echo json_encode([
            'success' => true,
            'review' => [
                'id' => $review->id,
                'review_level' => $review->review_level,
                'review_status' => $review->review_status,
                'feedback' => $review->feedback,
                'improvements' => $review->improvements,
                'reviewer_id' => $review->reviewer_id,
                'reviewer_name' => $review->reviewer_name,
                'reviewer_role' => $review->reviewer_role,
                'timecreated' => $review->timecreated,
                'timemodified' => $review->timemodified,
                'version' => $review->version
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'review' => null
        ], JSON_UNESCAPED_UNICODE);
    }
}

// End of file - no closing PHP tag to prevent whitespace issues in AJAX responses

/*
 * DATABASE TABLES USED
 * ====================
 *
 * mdl_abessi_content_reviews:
 * - contentsid (BIGINT) - FK to mdl_icontent_pages.id
 * - cmid (BIGINT) - Course module ID
 * - pagenum (INT) - Page number
 * - review_level (VARCHAR) - L1, L2, L3, L4, L5
 * - review_status (VARCHAR) - pending, approved
 * - feedback (TEXT) - Overall feedback
 * - improvements (TEXT) - Improvement suggestions
 * - reviewer_id (BIGINT) - FK to mdl_user.id
 * - reviewer_name (VARCHAR) - Reviewer full name
 * - reviewer_role (VARCHAR) - teacher, admin, expert
 * - student_id (BIGINT) - FK to mdl_user.id
 * - wboard_id (VARCHAR) - Whiteboard ID
 * - timecreated (BIGINT) - Unix timestamp
 * - timemodified (BIGINT) - Unix timestamp
 * - version (INT) - Version number
 * - is_latest (TINYINT) - 1=latest, 0=historical
 *
 * mdl_abessi_review_history:
 * - review_id (BIGINT) - FK to mdl_abessi_content_reviews.id
 * - contentsid (BIGINT) - FK to mdl_icontent_pages.id
 * - action_type (VARCHAR) - created, updated
 * - old_level (VARCHAR) - Previous level
 * - new_level (VARCHAR) - New level
 * - old_status (VARCHAR) - Previous status
 * - new_status (VARCHAR) - New status
 * - change_summary (TEXT) - Change description
 * - changed_by (BIGINT) - FK to mdl_user.id
 * - changed_by_name (VARCHAR) - User full name
 * - timecreated (BIGINT) - Unix timestamp
 */
