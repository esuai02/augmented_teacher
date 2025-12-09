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
 * Routine Coach dashboard page.
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');

use local_routinecoach\service\routine_service;

// Require login
require_login();
$context = context_system::instance();

// Check capability
require_capability('local/routinecoach:view', $context);

// Get parameters
$view = optional_param('view', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$taskid = optional_param('taskid', 0, PARAM_INT);
$format = optional_param('format', 'html', PARAM_ALPHA);

// Verify user access
if ($userid != $USER->id && !has_capability('local/routinecoach:viewall', $context)) {
    $userid = $USER->id;
}

// Initialize service
$service = new routine_service();

// Handle JSON API requests
if ($format == 'json' || $view == 'today' || $action == 'complete') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // Handle view=today request
        if ($view == 'today') {
            $todayTasks = $service->get_today_tasks($userid);
            
            // Format response
            $response = [
                'success' => true,
                'data' => $todayTasks,
                'message' => 'Tasks loaded successfully'
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Handle action=complete request
        if ($action == 'complete') {
            $completed = optional_param('completed', 0, PARAM_INT);
            
            if (!$taskid) {
                throw new Exception('Task ID is required');
            }
            
            // Complete or uncomplete task
            $result = $service->complete_task($taskid, $userid, $completed);
            
            if ($result) {
                // Get updated tasks
                $todayTasks = $service->get_today_tasks($userid);
                
                $response = [
                    'success' => true,
                    'data' => $todayTasks,
                    'message' => $completed ? 'Task completed' : 'Task reopened'
                ];
            } else {
                throw new Exception('Failed to update task');
            }
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Default JSON response
        $response = [
            'success' => false,
            'message' => 'Invalid request'
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Page setup for HTML view
$PAGE->set_url('/local/routinecoach/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('dashboard_title', 'local_routinecoach'));
$PAGE->set_heading(get_string('dashboard_title', 'local_routinecoach'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/routinecoach/styles.css');

// Get active routines
$routines = routine_service::get_user_routines($userid);

// Process routines for display
foreach ($routines as &$routine) {
    $routine->examdate_formatted = date('Y-m-d', $routine->examdate);
    $routine->daysleft = max(0, floor(($routine->examdate - time()) / 86400));
}

// Get pending tasks
$tasks = routine_service::get_pending_tasks($userid);

// Process tasks for display
foreach ($tasks as &$task) {
    $task->duedate_formatted = date('Y-m-d H:i', $task->duedate);
    $task->priorityclass = 'primary';
    if ($task->priority >= 8) {
        $task->priorityclass = 'danger';
    } else if ($task->priority >= 5) {
        $task->priorityclass = 'warning';
    }
}

// Calculate statistics
$stats = new stdClass();
$stats->exam_count = count($routines);
$stats->task_count = count($tasks);
$stats->today_minutes = 0; // Would be calculated from actual study logs
$stats->ratio_display = '7:3'; // Would be calculated from actual data

// Prepare template context
$templatecontext = [
    'userid' => $userid,
    'routines' => array_values($routines),
    'hasroutines' => !empty($routines),
    'tasks' => array_values($tasks),
    'hastasks' => !empty($tasks),
    'exam_count' => $stats->exam_count,
    'task_count' => $stats->task_count,
    'today_minutes' => $stats->today_minutes,
    'ratio_display' => $stats->ratio_display
];

// Output page
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_routinecoach/routinecoach', $templatecontext);
echo $OUTPUT->footer();