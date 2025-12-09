<?php
// File: mvp_system/api/event_handler_modernized.php
// Modernized Event Handler with Security Best Practices
// Replaces legacy event handler with proper Moodle database API

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE;

// Security: Require login
require_login();

// Get user role
$userrole = $DB->get_record_sql(
    "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = ?",
    [$USER->id, 22]
);
$role = $userrole->data ?? '';

// Security: Block students and parents from certain operations
if (in_array($role, ['student', 'parent'])) {
    // Students/parents can only access specific event types
    $allowed_events = [8, 25, 26, 30]; // Add allowed event IDs
    if (!in_array($_POST['eventid'] ?? 0, $allowed_events)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Access denied',
            'location' => __FILE__ . ':' . __LINE__
        ]);
        exit;
    }
}

// Initialize response
$response = ['success' => false, 'error' => null];

try {
    // Security: Validate CSRF token (recommended for production)
    // require_sesskey(); // Uncomment in production

    // Get and validate input
    $eventid = clean_param($_POST['eventid'] ?? 0, PARAM_INT);
    $userid = clean_param($_POST['userid'] ?? 0, PARAM_INT);
    $teacherid = clean_param($_POST['teacherid'] ?? 0, PARAM_INT);
    $attemptid = clean_param($_POST['attemptid'] ?? 0, PARAM_INT);
    $questionid = clean_param($_POST['questionid'] ?? 0, PARAM_INT);
    $checkimsi = clean_param($_POST['checkimsi'] ?? 0, PARAM_INT);
    $trackingid = clean_param($_POST['trackingid'] ?? 0, PARAM_INT);
    $threadid = clean_param($_POST['threadid'] ?? 0, PARAM_INT);
    $inputtext = clean_param($_POST['inputtext'] ?? '', PARAM_TEXT);
    $result = clean_param($_POST['result'] ?? '', PARAM_TEXT);
    $status = clean_param($_POST['status'] ?? '', PARAM_ALPHA);
    $course = clean_param($_POST['course'] ?? 0, PARAM_INT);
    $type = clean_param($_POST['type'] ?? '', PARAM_ALPHA);
    $duration = clean_param($_POST['duration'] ?? 0, PARAM_INT);
    $date = clean_param($_POST['date'] ?? '', PARAM_TEXT);

    $timecreated = time();
    $aweekago = $timecreated - 604800;

    // Event handler using switch for better performance and readability
    switch ($eventid) {

        // Event 1: Check comment confirm
        case 1:
            if (!$userid || !$questionid) {
                throw new Exception("Missing required parameters at " . __FILE__ . ":" . __LINE__);
            }

            $record = new stdClass();
            $record->confirm = $checkimsi;
            $record->timemodified = $timecreated;

            $DB->execute(
                "UPDATE {studentquiz_comment} SET confirm = ?, timemodified = ? WHERE userid = ? AND questionid = ?",
                [$checkimsi, $timecreated, $userid, $questionid]
            );

            $response['success'] = true;
            $response['message'] = 'Comment confirmed';
            break;

        // Event 2: Check flag
        case 2:
            if (!$attemptid) {
                throw new Exception("Missing attemptid at " . __FILE__ . ":" . __LINE__);
            }

            $DB->execute(
                "UPDATE {question_attempts} SET checkflag = ?, timemodified = ? WHERE id = ?",
                [$checkimsi, $timecreated, $attemptid]
            );

            $response['success'] = true;
            $response['message'] = 'Flag updated';
            break;

        // Event 3: Check feedback
        case 3:
            if (!$attemptid) {
                throw new Exception("Missing attemptid at " . __FILE__ . ":" . __LINE__);
            }

            $DB->execute(
                "UPDATE {question_attempts} SET feedback = ?, timemodified = ? WHERE id = ?",
                [$checkimsi, $timecreated, $attemptid]
            );

            $response['success'] = true;
            $response['message'] = 'Feedback updated';
            break;

        // Event 8: Today's activity completion
        case 8:
            if (!$userid) {
                throw new Exception("Missing userid at " . __FILE__ . ":" . __LINE__);
            }

            // Get most recent indicator
            $indicator = $DB->get_record_sql(
                "SELECT * FROM {abessi_indicators} WHERE userid = ? ORDER BY id DESC LIMIT 1",
                [$userid]
            );

            if ($indicator) {
                $indicator->aion = $checkimsi;
                $indicator->timemodified = $timecreated;
                $DB->update_record('abessi_indicators', $indicator);

                $response['success'] = true;
                $response['message'] = 'Activity completed';
            } else {
                throw new Exception("No indicator found for user at " . __FILE__ . ":" . __LINE__);
            }
            break;

        // Event 10: psclass talk2us
        case 10:
            if (!$userid || !$inputtext) {
                throw new Exception("Missing required parameters at " . __FILE__ . ":" . __LINE__);
            }

            $record = new stdClass();
            $record->eventid = 7128;
            $record->studentid = $userid;
            $record->teacherid = $USER->id;
            $record->context = 'share';
            $record->status = 'begin';
            $record->text = $inputtext;
            $record->timemodified = $timecreated;
            $record->timecreated = $timecreated;

            $newid = $DB->insert_record('abessi_talk2us', $record);

            $response['success'] = true;
            $response['teacherid'] = $USER->id;
            $response['talkid'] = $newid;
            break;

        // Event 21: Input text tracking
        case 21:
            if (!$userid) {
                throw new Exception("Missing userid at " . __FILE__ . ":" . __LINE__);
            }

            // Get latest board
            $thisboard = $DB->get_record_sql(
                "SELECT * FROM {abessi_messages} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1",
                [$userid]
            );
            $wboardid = $thisboard->wboardid ?? 'none';

            // Check existing tracking
            $exist = $DB->get_record_sql(
                "SELECT * FROM {abessi_tracking}
                 WHERE userid = ? AND status = 'begin' AND timecreated > ?
                 ORDER BY id DESC LIMIT 1",
                [$userid, $aweekago]
            );

            $record = new stdClass();
            $record->userid = $userid;
            $record->teacherid = $USER->id;
            $record->timecreated = $timecreated;

            if ($status === 'waiting') {
                $record->type = 'instruction';
                $record->status = 'waiting';
                $record->duration = $timecreated + ($duration * 60);
                $record->text = $inputtext;

                $DB->insert_record('abessi_tracking', $record);

            } elseif (!$exist && $inputtext) {
                $record->type = 'task';
                $record->status = 'begin';
                $record->wboardid = $wboardid;
                $record->duration = $timecreated + ($duration * 60);
                $record->text = $inputtext;

                $DB->insert_record('abessi_tracking', $record);
            }

            $response['success'] = true;
            $response['userid'] = $USER->id;
            break;

        // Event 23: Update text
        case 23:
            if (!$trackingid) {
                throw new Exception("Missing trackingid at " . __FILE__ . ":" . __LINE__);
            }

            $exist = $DB->get_record('abessi_tracking', ['id' => $trackingid]);
            if (!$exist) {
                throw new Exception("Tracking record not found at " . __FILE__ . ":" . __LINE__);
            }

            $exist->text = $inputtext;
            $exist->duration = $exist->timecreated + ($duration * 60);
            $exist->timemodified = $timecreated;

            $DB->update_record('abessi_tracking', $exist);

            $response['success'] = true;
            $response['userid'] = $USER->id;
            break;

        // Event 26: Complete activity
        case 26:
            if (!$userid) {
                throw new Exception("Missing userid at " . __FILE__ . ":" . __LINE__);
            }

            $DB->execute(
                "UPDATE {abessi_tracking}
                 SET result = ?, status = 'complete', timefinished = ?
                 WHERE userid = ? AND status = 'begin'
                 ORDER BY id DESC LIMIT 1",
                [$result, $timecreated, $userid]
            );

            $response['success'] = true;
            $response['userid'] = $USER->id;
            break;

        // Event 30: Add comment
        case 30:
            if (!$trackingid || !$inputtext) {
                throw new Exception("Missing parameters at " . __FILE__ . ":" . __LINE__);
            }

            $record = $DB->get_record('abessi_tracking', ['id' => $trackingid]);
            if (!$record) {
                throw new Exception("Tracking not found at " . __FILE__ . ":" . __LINE__);
            }

            if ($role === 'student') {
                $record->comment = $inputtext;
            } else {
                $record->feedback = $inputtext;
            }
            $record->timemodified = $timecreated;

            $DB->update_record('abessi_tracking', $record);

            $response['success'] = true;
            $response['userid'] = $USER->id;
            break;

        default:
            throw new Exception("Unknown event ID: $eventid at " . __FILE__ . ":" . __LINE__);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['location'] = $e->getFile() . ':' . $e->getLine();

    // Log error for debugging
    error_log("Event Handler Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
