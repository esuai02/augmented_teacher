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
 * Language strings for the routinecoach plugin.
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name
$string['pluginname'] = 'Routine Coach';

// Dashboard
$string['dashboard_title'] = 'Routine Coach Dashboard';
$string['dashboard_subtitle'] = 'Exam-based learning automation system';

// Stats
$string['active_exams'] = 'Active Exams';
$string['pending_tasks'] = 'Pending Tasks';
$string['study_today'] = 'Study Today';
$string['study_ratio'] = 'Study Ratio';

// Routines
$string['active_routines'] = 'Active Routines';
$string['no_active_routines'] = 'No active routines. Create an exam to get started.';
$string['add_exam'] = 'Add Exam';
$string['createexam'] = 'Create New Exam';
$string['examlabel'] = 'Exam Name';
$string['examdate'] = 'Exam Date';

// Tasks
$string['today_tasks'] = 'Today\'s Tasks';
$string['no_pending_tasks'] = 'No pending tasks for today.';
$string['task_concept_d30'] = 'Concept Study - 30 days before exam';
$string['task_concept_d14'] = 'Concept Study - 14 days before exam';
$string['task_concept_d7'] = 'Concept Review - 7 days before exam';
$string['task_concept_d3'] = 'Final Concept Review - 3 days before exam';
$string['task_concept_d1'] = 'Last Minute Concept Check - 1 day before exam';
$string['task_review_d30'] = 'Review Session - 30 days before exam';
$string['task_review_d14'] = 'Review Session - 14 days before exam';
$string['task_review_d7'] = 'Intensive Review - 7 days before exam';
$string['task_review_d3'] = 'Final Review - 3 days before exam';
$string['task_review_d1'] = 'Last Minute Review - 1 day before exam';
$string['daily_task_concept'] = 'Daily Concept Study';
$string['daily_task_review'] = 'Daily Review Session';

// Progress
$string['weekly_progress'] = 'Weekly Study Progress';

// Cron task
$string['task_daily_cron'] = 'Routine Coach Daily Processing';

// Capabilities
$string['routinecoach:view'] = 'View routine coach dashboard';
$string['routinecoach:manage'] = 'Manage own routines';
$string['routinecoach:viewall'] = 'View all users\' routines';

// Errors
$string['routinealreadyexists'] = 'A routine already exists for this exam';
$string['invalidexamid'] = 'Invalid exam ID';
$string['invalidroutineid'] = 'Invalid routine ID';
$string['invalidtaskid'] = 'Invalid task ID';
$string['nopermission'] = 'You do not have permission to perform this action';