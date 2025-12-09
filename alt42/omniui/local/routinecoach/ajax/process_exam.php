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
 * AJAX handler to process detected exam from schedule
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/routinecoach/hooks/schedule_exam_hook.php');

require_login();

$userid = required_param('userid', PARAM_INT);
$scheduleid = required_param('scheduleid', PARAM_INT);
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
    // Initialize hook and process schedule
    $examHook = new schedule_exam_hook($userid);
    $result = $examHook->process_schedule($scheduleid);
    
    // Return result as JSON
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'show_popup' => true
    ]);
}