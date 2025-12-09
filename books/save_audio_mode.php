<?php
/**
 * Save Audio Mode Preference
 * File: save_audio_mode.php
 *
 * Saves user's audio playback mode preference (full/section)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST parameters
$contentsid = required_param('contentsid', PARAM_INT);
$wboardid = required_param('wboardid', PARAM_TEXT);
$audioMode = required_param('audio_mode', PARAM_TEXT);
$userid = required_param('userid', PARAM_INT);

// Log all requests for debugging
error_log(sprintf(
    '[Audio Mode Save] File: %s, User: %d, Mode: %s, Board: %s',
    basename(__FILE__),
    $userid,
    $audioMode,
    $wboardid
));

// Validate audio mode
if(!in_array($audioMode, ['full', 'section'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid audio mode',
        'file' => 'save_audio_mode.php',
        'line' => __LINE__
    ]);
    exit;
}

try {
    // Get existing record
    $existingRecord = $DB->get_record_sql(
        "SELECT * FROM {abessi_messages}
         WHERE wboardid = :wboardid
         ORDER BY timemodified DESC
         LIMIT 1",
        ['wboardid' => $wboardid]
    );

    if($existingRecord) {
        // Update existing record's reflections2 field
        $reflections2Data = [];

        // Parse existing JSON if present
        if(!empty($existingRecord->reflections2)) {
            $reflections2Data = json_decode($existingRecord->reflections2, true);
            if(!is_array($reflections2Data)) {
                $reflections2Data = [];
            }
        }

        // Add/update audio mode
        $reflections2Data['audio_mode'] = $audioMode;

        // Save back to database
        $DB->execute(
            "UPDATE {abessi_messages}
             SET reflections2 = :reflections2,
                 timemodified = :timemodified
             WHERE id = :id",
            [
                'reflections2' => json_encode($reflections2Data),
                'timemodified' => time(),
                'id' => $existingRecord->id
            ]
        );

        echo json_encode([
            'success' => true,
            'mode' => $audioMode,
            'action' => 'updated',
            'record_id' => $existingRecord->id
        ]);

    } else {
        // No record exists - this shouldn't happen in normal flow
        echo json_encode([
            'success' => false,
            'error' => 'No message record found',
            'file' => 'save_audio_mode.php',
            'line' => __LINE__,
            'wboardid' => $wboardid
        ]);
    }

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => 'save_audio_mode.php',
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
