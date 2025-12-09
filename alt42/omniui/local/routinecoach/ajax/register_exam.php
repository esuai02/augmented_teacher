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
 * AJAX handler for manual exam registration
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');

use local_routinecoach\service\routine_service;

require_login();

$userid = required_param('userid', PARAM_INT);
$label = required_param('label', PARAM_TEXT);
$date = required_param('date', PARAM_TEXT);
$type = required_param('type', PARAM_TEXT);
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
    // Validate inputs
    if (empty($label) || empty($date)) {
        throw new Exception('시험 이름과 날짜는 필수입니다.');
    }
    
    // Convert date to timestamp
    $examdate = strtotime($date);
    if (!$examdate || $examdate < time()) {
        throw new Exception('유효한 미래 날짜를 선택해주세요.');
    }
    
    // Initialize service and create exam
    $service = new routine_service();
    $examid = $service->on_exam_saved($userid, $examdate, null, $label);
    
    if ($examid) {
        echo json_encode([
            'success' => true,
            'message' => '시험이 성공적으로 등록되었습니다.',
            'examid' => $examid,
            'label' => $label,
            'date' => date('Y-m-d', $examdate),
            'type' => $type
        ]);
    } else {
        throw new Exception('시험 등록에 실패했습니다.');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}