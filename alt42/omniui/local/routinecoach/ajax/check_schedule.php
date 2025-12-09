<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AJAX handler to check for schedule changes
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$userid = required_param('userid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_RAW);

// Verify session
if (!confirm_sesskey($sesskey)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

// Verify user access
if ($userid != $USER->id && !has_capability('local/routinecoach:viewall', context_system::instance())) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Check for recent schedule changes
    $recentSchedule = $DB->get_record_sql(
        "SELECT * FROM {abessi_schedule} 
         WHERE userid = :userid 
           AND (pinned = 1 OR type IN ('임시', '특강'))
           AND timemodified > :recent
         ORDER BY timemodified DESC LIMIT 1",
        [
            'userid' => $userid,
            'recent' => time() - 60 // Changes within last minute
        ]
    );
    
    if ($recentSchedule) {
        // Check if this schedule has already been processed
        $processed = $DB->get_record('routinecoach_exam', [
            'userid' => $userid,
            'scheduleid' => $recentSchedule->id
        ]);
        
        if (!$processed) {
            echo json_encode([
                'success' => true,
                'schedule_changed' => true,
                'scheduleid' => $recentSchedule->id
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'schedule_changed' => false
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'schedule_changed' => false
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}