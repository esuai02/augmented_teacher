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
 * Routine service class for managing exam-based routines.
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Routine service class
 */
class routine_service {
    
    /** @var int Default concept study ratio */
    const DEFAULT_RATIO_CONCEPT = 70;
    
    /** @var int Default review study ratio */
    const DEFAULT_RATIO_REVIEW = 30;
    
    /**
     * Handle exam save event - create or update exam and rebuild routine
     *
     * @param int $userid User ID
     * @param int $examdate Exam date timestamp
     * @param int|null $scheduleid Optional schedule ID from mdl_abessi_schedule
     * @param string $label Exam label
     * @return int The exam ID
     */
    public function on_exam_saved(int $userid, int $examdate, ?int $scheduleid, string $label): int {
        global $DB;
        
        // Check if exam already exists for this user and date
        $existing = $DB->get_record('routinecoach_exam', [
            'userid' => $userid,
            'examdate' => $examdate
        ]);
        
        if ($existing) {
            // Update existing exam
            $existing->label = $label;
            $existing->scheduleid = $scheduleid;
            $existing->timemodified = time();
            $DB->update_record('routinecoach_exam', $existing);
            $examid = $existing->id;
            
            $this->log('exam_updated', [
                'examid' => $examid,
                'userid' => $userid,
                'label' => $label
            ]);
            
            // Delete existing routine and tasks for rebuild
            if ($routine = $DB->get_record('routinecoach_routine', ['examid' => $examid])) {
                $DB->delete_records('routinecoach_task', ['routineid' => $routine->id]);
                $DB->delete_records('routinecoach_routine', ['id' => $routine->id]);
            }
        } else {
            // Create new exam
            $exam = new \stdClass();
            $exam->userid = $userid;
            $exam->scheduleid = $scheduleid;
            $exam->label = $label;
            $exam->examdate = $examdate;
            $exam->timecreated = time();
            $exam->timemodified = time();
            
            $examid = $DB->insert_record('routinecoach_exam', $exam);
            
            $this->log('exam_created', [
                'examid' => $examid,
                'userid' => $userid,
                'label' => $label
            ]);
        }
        
        // Build routine with default 7:3 ratio
        $routineid = $this->build_routine($examid);
        
        // Build tasks from various sources
        $sources = $this->gather_task_sources($userid, $scheduleid);
        $this->build_tasks($routineid, $sources);
        
        return $examid;
    }
    
    /**
     * Build routine for an exam
     *
     * @param int $examid Exam ID
     * @param int $ratioConcept Concept study ratio (default 70)
     * @param int $ratioReview Review study ratio (default 30)
     * @return int The routine ID
     */
    private function build_routine(int $examid, int $ratioConcept = 70, int $ratioReview = 30): int {
        global $DB;
        
        $exam = $DB->get_record('routinecoach_exam', ['id' => $examid], '*', MUST_EXIST);
        
        $routine = new \stdClass();
        $routine->examid = $examid;
        $routine->ratio_concept = $ratioConcept;
        $routine->ratio_review = $ratioReview;
        $routine->startdate = time();
        $routine->enddate = $exam->examdate;
        $routine->status = 'active';
        $routine->timecreated = time();
        $routine->timemodified = time();
        
        $routineid = $DB->insert_record('routinecoach_routine', $routine);
        
        $this->log('routine_created', [
            'routineid' => $routineid,
            'examid' => $examid,
            'ratio' => $ratioConcept . ':' . $ratioReview
        ]);
        
        return $routineid;
    }
    
    /**
     * Build tasks based on routine and available sources
     *
     * @param int $routineid Routine ID
     * @param array $sources Task sources (schedule, goals, wrongnote)
     */
    private function build_tasks(int $routineid, array $sources): void {
        global $DB;
        
        $routine = $DB->get_record('routinecoach_routine', ['id' => $routineid], '*', MUST_EXIST);
        $exam = $DB->get_record('routinecoach_exam', ['id' => $routine->examid], '*', MUST_EXIST);
        
        $now = time();
        $examdate = $exam->examdate;
        $daysleft = floor(($examdate - $now) / 86400);
        
        // Key milestone days for task generation
        $milestones = [30, 14, 7, 3, 1];
        
        foreach ($milestones as $days) {
            if ($daysleft >= $days) {
                $duedate = $examdate - ($days * 86400);
                
                // Calculate available study time from schedule
                $availableMinutes = $this->calculate_available_time($sources['schedule'] ?? null, $duedate);
                
                // Distribute time based on ratio
                $conceptMinutes = round($availableMinutes * ($routine->ratio_concept / 100));
                $reviewMinutes = round($availableMinutes * ($routine->ratio_review / 100));
                
                // Create concept task if ratio > 0
                if ($routine->ratio_concept > 0 && $conceptMinutes > 0) {
                    $task = new \stdClass();
                    $task->routineid = $routineid;
                    $task->duedate = $duedate;
                    $task->type = 'concept';
                    $task->source = $this->determine_task_source($sources, 'concept');
                    $task->refid = $sources['goals']['concept_id'] ?? null;
                    $task->title = $this->generate_task_title('concept', $days, $sources);
                    $task->durationmin = $conceptMinutes;
                    $task->priority = $this->calculate_priority($days, 'concept');
                    $task->completed = 0;
                    $task->timecreated = time();
                    $task->timemodified = time();
                    
                    $DB->insert_record('routinecoach_task', $task);
                }
                
                // Create review task if ratio > 0
                if ($routine->ratio_review > 0 && $reviewMinutes > 0) {
                    $task = new \stdClass();
                    $task->routineid = $routineid;
                    $task->duedate = $duedate;
                    $task->type = 'review';
                    $task->source = $this->determine_task_source($sources, 'review');
                    $task->refid = $sources['wrongnote']['id'] ?? null;
                    $task->title = $this->generate_task_title('review', $days, $sources);
                    $task->durationmin = $reviewMinutes;
                    $task->priority = $this->calculate_priority($days, 'review');
                    $task->completed = 0;
                    $task->timecreated = time();
                    $task->timemodified = time();
                    
                    $DB->insert_record('routinecoach_task', $task);
                }
                
                // Add wrong note tasks if available
                if (!empty($sources['wrongnote']) && $days <= 7) {
                    $this->create_wrongnote_tasks($routineid, $duedate, $sources['wrongnote']);
                }
            }
        }
        
        $this->log('tasks_created', [
            'routineid' => $routineid,
            'count' => $DB->count_records('routinecoach_task', ['routineid' => $routineid])
        ]);
    }
    
    /**
     * Get today's tasks for a user
     *
     * @param int $userid User ID
     * @param int $t0 Current timestamp (default: now)
     * @return array Array of task objects
     */
    public function get_today_tasks(int $userid, int $t0 = 0): array {
        global $DB;
        
        if ($t0 == 0) {
            $t0 = time();
        }
        
        // Calculate today's date range
        $todayStart = strtotime(date('Y-m-d 00:00:00', $t0));
        $todayEnd = strtotime(date('Y-m-d 23:59:59', $t0));
        
        // Get all today's tasks (both completed and uncompleted)
        $sql = "SELECT t.*, r.examid, r.ratio_concept, r.ratio_review, 
                       e.label as exam_label, e.examdate
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid = :userid 
                  AND t.duedate >= :start
                  AND t.duedate <= :end
                  AND r.status = 'active'
                ORDER BY t.completed ASC, t.priority DESC, t.duedate ASC";
        
        $params = [
            'userid' => $userid,
            'start' => $todayStart,
            'end' => $todayEnd
        ];
        
        $tasks = $DB->get_records_sql($sql, $params);
        
        // Calculate stats
        $stats = new \stdClass();
        $stats->total_count = count($tasks);
        $stats->completed_count = 0;
        $stats->exam_label = '';
        $stats->days_left = 0;
        $stats->ratio = '7:3';
        
        // Process tasks and stats
        foreach ($tasks as &$task) {
            $task->daysleft = floor(($task->examdate - $t0) / 86400);
            $task->countdown_label = 'D-' . $task->daysleft;
            
            if ($task->completed) {
                $stats->completed_count++;
            }
            
            // Set primary exam info from first task
            if (empty($stats->exam_label)) {
                $stats->exam_label = $task->exam_label;
                $stats->days_left = $task->daysleft;
                $stats->ratio = round($task->ratio_concept/10) . ':' . round($task->ratio_review/10);
            }
        }
        
        return [
            'tasks' => array_values($tasks),
            'stats' => $stats
        ];
    }
    
    /**
     * Mark a task as completed or uncompleted
     *
     * @param int $taskid Task ID
     * @param int $userid User ID (for validation)
     * @param int $completed 1 to complete, 0 to uncomplete
     * @return bool Success status
     */
    public function complete_task(int $taskid, int $userid, int $completed = 1): bool {
        global $DB;
        
        // Validate task belongs to user
        $sql = "SELECT t.*, e.userid
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE t.id = :taskid AND e.userid = :userid";
        
        $task = $DB->get_record_sql($sql, ['taskid' => $taskid, 'userid' => $userid]);
        
        if (!$task) {
            return false;
        }
        
        // Update task status
        $task->completed = $completed;
        $task->timemodified = time();
        $DB->update_record('routinecoach_task', $task);
        
        // Log to missionlog for tracking (only when completing)
        if ($completed) {
            $missionlog = new \stdClass();
            $missionlog->userid = $userid;
            $missionlog->page = 'routinecoach_task_' . $task->type;
            $missionlog->timecreated = time();
            
            // Check if mdl_abessi_missionlog exists and insert
            try {
                $DB->insert_record('abessi_missionlog', $missionlog);
            } catch (\Exception $e) {
                // Table might not exist in test environment
            }
        }
        
        $action = $completed ? 'task_completed' : 'task_reopened';
        $this->log($action, [
            'taskid' => $taskid,
            'userid' => $userid,
            'type' => $task->type,
            'duration' => $task->durationmin
        ]);
        
        return true;
    }
    
    /**
     * Log an action to routinecoach_log
     *
     * @param string $action Action name
     * @param array $meta Additional metadata
     */
    public function log(string $action, array $meta = []): void {
        global $DB, $USER;
        
        $log = new \stdClass();
        $log->userid = $meta['userid'] ?? $USER->id ?? 0;
        $log->examid = $meta['examid'] ?? 0;
        $log->action = $action;
        $log->meta = json_encode($meta);
        $log->timecreated = time();
        
        try {
            $DB->insert_record('routinecoach_log', $log);
        } catch (\Exception $e) {
            // Log table might not exist yet
            error_log('RoutineCoach log failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Gather task sources from various systems
     *
     * @param int $userid User ID
     * @param int|null $scheduleid Schedule ID
     * @return array Sources array
     */
    private function gather_task_sources(int $userid, ?int $scheduleid): array {
        global $DB;
        
        $sources = [
            'schedule' => null,
            'goals' => [],
            'wrongnote' => []
        ];
        
        // Get schedule information if available
        if ($scheduleid) {
            try {
                $sources['schedule'] = $DB->get_record('abessi_schedule', ['id' => $scheduleid]);
            } catch (\Exception $e) {
                // Table might not exist
            }
        }
        
        // Get goals from mdl_abessi_today
        try {
            $goals = $DB->get_records_sql(
                "SELECT * FROM {abessi_today} 
                 WHERE userid = :userid 
                   AND type LIKE '%목표%' 
                 ORDER BY id DESC LIMIT 5",
                ['userid' => $userid]
            );
            
            foreach ($goals as $goal) {
                if (strpos($goal->type, '오늘') !== false) {
                    $sources['goals']['today'] = $goal;
                } elseif (strpos($goal->type, '주간') !== false) {
                    $sources['goals']['weekly'] = $goal;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist
        }
        
        // Get wrong notes (simplified - would need actual implementation)
        // This would integrate with existing wrong answer tracking system
        
        return $sources;
    }
    
    /**
     * Calculate available study time from schedule
     *
     * @param object|null $schedule Schedule object
     * @param int $date Target date
     * @return int Available minutes
     */
    private function calculate_available_time($schedule, int $date): int {
        if (!$schedule) {
            return 60; // Default 1 hour if no schedule
        }
        
        // Get day of week (1=Monday, 7=Sunday)
        $dayOfWeek = date('N', $date);
        
        // Map to schedule duration fields
        $durationField = 'duration' . $dayOfWeek;
        
        if (isset($schedule->$durationField)) {
            // Convert hours to minutes
            return (int)($schedule->$durationField * 60);
        }
        
        return 60; // Default 1 hour
    }
    
    /**
     * Determine task source based on available sources and type
     *
     * @param array $sources Available sources
     * @param string $type Task type
     * @return string Source name
     */
    private function determine_task_source(array $sources, string $type): string {
        if ($type === 'review' && !empty($sources['wrongnote'])) {
            return 'wrongnote';
        }
        
        if (!empty($sources['goals'])) {
            return 'goals';
        }
        
        return 'schedule';
    }
    
    /**
     * Generate task title based on type and countdown
     *
     * @param string $type Task type
     * @param int $days Days until exam
     * @param array $sources Task sources
     * @return string Task title
     */
    private function generate_task_title(string $type, int $days, array $sources): string {
        $typeLabel = ($type === 'concept') ? '선행학습' : '복습';
        
        // Check if there's a specific goal text
        if ($type === 'concept' && !empty($sources['goals']['today'])) {
            return $sources['goals']['today']->text . ' (D-' . $days . ')';
        }
        
        return $typeLabel . ' - ' . $days . '일 전';
    }
    
    /**
     * Calculate task priority based on countdown and type
     *
     * @param int $days Days until exam
     * @param string $type Task type
     * @return int Priority (1-10)
     */
    private function calculate_priority(int $days, string $type): int {
        // Base priority from countdown
        if ($days <= 1) {
            $priority = 10;
        } elseif ($days <= 3) {
            $priority = 9;
        } elseif ($days <= 7) {
            $priority = 7;
        } elseif ($days <= 14) {
            $priority = 5;
        } else {
            $priority = 3;
        }
        
        // Adjust for type (review gets +1 priority as exam approaches)
        if ($type === 'review' && $days <= 7) {
            $priority = min(10, $priority + 1);
        }
        
        return $priority;
    }
    
    /**
     * Create tasks for wrong notes
     *
     * @param int $routineid Routine ID
     * @param int $duedate Due date
     * @param array $wrongnotes Wrong note data
     */
    private function create_wrongnote_tasks(int $routineid, int $duedate, array $wrongnotes): void {
        global $DB;
        
        // This would create specific tasks for reviewing wrong answers
        // Implementation would depend on actual wrong note tracking system
        
        $task = new \stdClass();
        $task->routineid = $routineid;
        $task->duedate = $duedate;
        $task->type = 'wrongnote';
        $task->source = 'wrongnote';
        $task->title = '오답노트 복습';
        $task->durationmin = 30;
        $task->priority = 8;
        $task->completed = 0;
        $task->timecreated = time();
        $task->timemodified = time();
        
        $DB->insert_record('routinecoach_task', $task);
    }
}