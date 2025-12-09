<?php
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

// Get student ID from parameter or current user
$studentid = optional_param('studentid', $USER->id, PARAM_INT);

// Calculate timestamp for one week ago
$aweekago = time() - (7 * 24 * 60 * 60);

// Query for remaining incorrect answer notes
$sql = "SELECT * FROM mdl_abessi_messages 
        WHERE userid = :userid 
        AND status NOT LIKE 'attempt' 
        AND status NOT LIKE 'complete' 
        AND tlaststroke > :aweekago 
        AND contentstype = 2 
        AND (active = 1 OR status = 'flag') 
        ORDER BY tlaststroke DESC 
        LIMIT 10";

$params = [
    'userid' => $studentid,
    'aweekago' => $aweekago
];

// Execute query
$remainingNotes = $DB->get_records_sql($sql, $params);

// Count the results
$remainingCount = count($remainingNotes);

// Output results
echo json_encode([
    'student_id' => $studentid,
    'remaining_count' => $remainingCount,
    'notes' => array_values($remainingNotes),
    'message' => $remainingCount == 0 ? '완료된 오답노트가 없습니다' : 
                 $remainingCount . '개의 미완료 오답노트가 있습니다'
]);