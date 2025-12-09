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
 * Daily routine update cron task
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');
require_once($CFG->dirroot . '/local/routinecoach/classes/service/kpi_service.php');
require_once($CFG->dirroot . '/local/routinecoach/classes/service/mbti_service.php');

use local_routinecoach\service\routine_service;
use local_routinecoach\service\kpi_service;
use local_routinecoach\service\mbti_service;

/**
 * Daily routine update task - runs every day at 5:00 AM
 */
class daily_routine_update extends \core\task\scheduled_task {
    
    /** @var routine_service */
    private $routine_service;
    
    /** @var kpi_service */
    private $kpi_service;
    
    /** @var mbti_service */
    private $mbti_service;
    
    /**
     * Get the name of the task
     */
    public function get_name() {
        return get_string('task_daily_routine_update', 'local_routinecoach');
    }
    
    /**
     * Execute the daily routine update
     */
    public function execute() {
        global $DB;
        
        $this->routine_service = new routine_service();
        $this->kpi_service = new kpi_service();
        $this->mbti_service = new mbti_service();
        
        mtrace('Starting Daily Routine Update at ' . date('Y-m-d H:i:s'));
        
        // 1. Update routines for all active exams
        $this->update_all_routines();
        
        // 2. Generate daily tasks
        $this->generate_daily_tasks();
        
        // 3. Calculate and cache KPIs
        $this->calculate_kpis();
        
        // 4. Send morning notifications
        $this->send_morning_notifications();
        
        // 5. Generate MBTI-based content
        $this->generate_mbti_content();
        
        // 6. Clean up old data
        $this->cleanup_old_data();
        
        mtrace('Daily Routine Update completed at ' . date('Y-m-d H:i:s'));
    }
    
    /**
     * Update all active routines
     */
    private function update_all_routines() {
        global $DB;
        
        mtrace('Updating active routines...');
        
        $now = time();
        $today = strtotime(date('Y-m-d 00:00:00', $now));
        
        // Get all active routines with upcoming exams
        $sql = "SELECT r.*, e.examdate, e.label, e.userid
                FROM {routinecoach_routine} r
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE r.status = 'active'
                  AND e.examdate > :now
                ORDER BY e.examdate ASC";
        
        $routines = $DB->get_records_sql($sql, ['now' => $now]);
        
        foreach ($routines as $routine) {
            $daysUntil = floor(($routine->examdate - $today) / 86400);
            
            // Check if ratio adjustment needed
            $needsAdjustment = false;
            $newRatioConcept = $routine->ratio_concept;
            $newRatioReview = $routine->ratio_review;
            
            // D-30: Standard 7:3
            if ($daysUntil == 30 && ($routine->ratio_concept != 70 || $routine->ratio_review != 30)) {
                $newRatioConcept = 70;
                $newRatioReview = 30;
                $needsAdjustment = true;
            }
            // D-14: Balanced 5:5
            elseif ($daysUntil == 14 && ($routine->ratio_concept != 50 || $routine->ratio_review != 50)) {
                $newRatioConcept = 50;
                $newRatioReview = 50;
                $needsAdjustment = true;
            }
            // D-7: Review focus 3:7
            elseif ($daysUntil == 7 && ($routine->ratio_concept != 30 || $routine->ratio_review != 70)) {
                $newRatioConcept = 30;
                $newRatioReview = 70;
                $needsAdjustment = true;
            }
            // D-3: Heavy review 1:9
            elseif ($daysUntil == 3 && ($routine->ratio_concept != 10 || $routine->ratio_review != 90)) {
                $newRatioConcept = 10;
                $newRatioReview = 90;
                $needsAdjustment = true;
            }
            // D-1: Pure review 0:10
            elseif ($daysUntil == 1 && ($routine->ratio_concept != 0 || $routine->ratio_review != 100)) {
                $newRatioConcept = 0;
                $newRatioReview = 100;
                $needsAdjustment = true;
            }
            
            if ($needsAdjustment) {
                mtrace("  Adjusting ratio for routine {$routine->id} (D-{$daysUntil}): " .
                       "{$routine->ratio_concept}:{$routine->ratio_review} -> {$newRatioConcept}:{$newRatioReview}");
                
                // Update routine ratio
                $routine->ratio_concept = $newRatioConcept;
                $routine->ratio_review = $newRatioReview;
                $routine->timemodified = time();
                $DB->update_record('routinecoach_routine', $routine);
                
                // Rebuild tasks with new ratio
                $this->routine_service->on_exam_saved(
                    $routine->userid,
                    $routine->examdate,
                    null,
                    $routine->label
                );
                
                // Log the adjustment
                $this->routine_service->log('ratio_adjusted_cron', [
                    'routineid' => $routine->id,
                    'userid' => $routine->userid,
                    'days_until' => $daysUntil,
                    'old_ratio' => "{$routine->ratio_concept}:{$routine->ratio_review}",
                    'new_ratio' => "{$newRatioConcept}:{$newRatioReview}"
                ]);
            }
        }
        
        mtrace('  Updated ' . count($routines) . ' routines');
    }
    
    /**
     * Generate daily tasks for all users
     */
    private function generate_daily_tasks() {
        global $DB;
        
        mtrace('Generating daily tasks...');
        
        $today = strtotime(date('Y-m-d 00:00:00'));
        $tomorrow = $today + 86400;
        
        // Get users with active routines
        $sql = "SELECT DISTINCT e.userid
                FROM {routinecoach_exam} e
                JOIN {routinecoach_routine} r ON r.examid = e.id
                WHERE r.status = 'active'
                  AND e.examdate > :now";
        
        $users = $DB->get_records_sql($sql, ['now' => time()]);
        
        foreach ($users as $user) {
            // Check if tasks already exist for today
            $existingTasks = $DB->count_records_select(
                'routinecoach_task',
                'routineid IN (
                    SELECT r.id FROM {routinecoach_routine} r
                    JOIN {routinecoach_exam} e ON r.examid = e.id
                    WHERE e.userid = :userid
                )
                AND duedate >= :today
                AND duedate < :tomorrow',
                [
                    'userid' => $user->userid,
                    'today' => $today,
                    'tomorrow' => $tomorrow
                ]
            );
            
            if ($existingTasks == 0) {
                mtrace("  Generating tasks for user {$user->userid}");
                
                // Get user's active routines
                $routines = $DB->get_records_sql(
                    "SELECT r.*, e.examdate, e.label
                     FROM {routinecoach_routine} r
                     JOIN {routinecoach_exam} e ON r.examid = e.id
                     WHERE e.userid = :userid
                       AND r.status = 'active'
                       AND e.examdate > :now",
                    ['userid' => $user->userid, 'now' => time()]
                );
                
                foreach ($routines as $routine) {
                    // Generate today's tasks based on routine settings
                    $this->generate_tasks_for_routine($routine, $today);
                }
            }
        }
        
        mtrace('  Generated tasks for ' . count($users) . ' users');
    }
    
    /**
     * Generate tasks for a specific routine
     */
    private function generate_tasks_for_routine($routine, $date) {
        global $DB;
        
        $daysUntil = floor(($routine->examdate - $date) / 86400);
        
        // Skip if exam has passed
        if ($daysUntil < 0) {
            return;
        }
        
        // Calculate study time based on days until exam
        $totalMinutes = $this->calculate_study_minutes($daysUntil);
        $conceptMinutes = floor($totalMinutes * ($routine->ratio_concept / 100));
        $reviewMinutes = floor($totalMinutes * ($routine->ratio_review / 100));
        
        // Create concept task if needed
        if ($conceptMinutes > 0) {
            $task = new \stdClass();
            $task->routineid = $routine->id;
            $task->duedate = $date + (9 * 3600); // 9 AM
            $task->type = 'concept';
            $task->source = 'daily_cron';
            $task->title = "선행학습 (D-{$daysUntil})";
            $task->durationmin = $conceptMinutes;
            $task->priority = $this->calculate_priority($daysUntil, 'concept');
            $task->completed = 0;
            $task->timecreated = time();
            $task->timemodified = time();
            
            $DB->insert_record('routinecoach_task', $task);
        }
        
        // Create review task if needed
        if ($reviewMinutes > 0) {
            $task = new \stdClass();
            $task->routineid = $routine->id;
            $task->duedate = $date + (14 * 3600); // 2 PM
            $task->type = 'review';
            $task->source = 'daily_cron';
            $task->title = "복습 (D-{$daysUntil})";
            $task->durationmin = $reviewMinutes;
            $task->priority = $this->calculate_priority($daysUntil, 'review');
            $task->completed = 0;
            $task->timecreated = time();
            $task->timemodified = time();
            
            $DB->insert_record('routinecoach_task', $task);
        }
        
        // Create wrong note review task if D-7 or less
        if ($daysUntil <= 7) {
            $task = new \stdClass();
            $task->routineid = $routine->id;
            $task->duedate = $date + (19 * 3600); // 7 PM
            $task->type = 'wrongnote';
            $task->source = 'daily_cron';
            $task->title = "오답노트 복습 (D-{$daysUntil})";
            $task->durationmin = 30;
            $task->priority = 9;
            $task->completed = 0;
            $task->timecreated = time();
            $task->timemodified = time();
            
            $DB->insert_record('routinecoach_task', $task);
        }
    }
    
    /**
     * Calculate study minutes based on days until exam
     */
    private function calculate_study_minutes($daysUntil) {
        if ($daysUntil >= 30) {
            return 90; // 1.5 hours
        } elseif ($daysUntil >= 14) {
            return 120; // 2 hours
        } elseif ($daysUntil >= 7) {
            return 150; // 2.5 hours
        } elseif ($daysUntil >= 3) {
            return 180; // 3 hours
        } else {
            return 120; // 2 hours (final review)
        }
    }
    
    /**
     * Calculate task priority
     */
    private function calculate_priority($daysUntil, $type) {
        $basePriority = 5;
        
        if ($daysUntil <= 1) {
            $basePriority = 10;
        } elseif ($daysUntil <= 3) {
            $basePriority = 9;
        } elseif ($daysUntil <= 7) {
            $basePriority = 7;
        } elseif ($daysUntil <= 14) {
            $basePriority = 5;
        } else {
            $basePriority = 3;
        }
        
        // Adjust for type
        if ($type === 'review' && $daysUntil <= 7) {
            $basePriority++;
        }
        if ($type === 'wrongnote') {
            $basePriority = min(10, $basePriority + 1);
        }
        
        return min(10, $basePriority);
    }
    
    /**
     * Calculate and cache KPIs for teachers
     */
    private function calculate_kpis() {
        global $DB;
        
        mtrace('Calculating KPIs...');
        
        // Get all teachers (users with teacher role or 'T' in lastname)
        $sql = "SELECT DISTINCT u.id
                FROM {user} u
                WHERE u.lastname LIKE '%T%'
                   OR u.id IN (
                       SELECT userid FROM {role_assignments} ra
                       JOIN {role} r ON ra.roleid = r.id
                       WHERE r.shortname IN ('teacher', 'editingteacher')
                   )";
        
        $teachers = $DB->get_records_sql($sql);
        
        foreach ($teachers as $teacher) {
            // Get teacher's students
            $students = $this->get_teacher_students($teacher->id);
            
            if (empty($students)) {
                continue;
            }
            
            $studentIds = array_keys($students);
            
            // Calculate KPIs
            $kpis = $this->kpi_service->calculate_teacher_kpis($teacher->id, $studentIds);
            
            // Store KPIs in cache
            $cache = new \stdClass();
            $cache->userid = $teacher->id;
            $cache->kpi_data = json_encode($kpis);
            $cache->timecalculated = time();
            
            // Update or insert cache
            $existing = $DB->get_record('routinecoach_kpi_cache', ['userid' => $teacher->id]);
            if ($existing) {
                $cache->id = $existing->id;
                $DB->update_record('routinecoach_kpi_cache', $cache);
            } else {
                $DB->insert_record('routinecoach_kpi_cache', $cache);
            }
            
            mtrace("  Calculated KPIs for teacher {$teacher->id}: " .
                   "Completion: {$kpis['completion_rate']}%, " .
                   "Wrong Note: {$kpis['wrongnote_review_rate']}%, " .
                   "D-7 Improvement: {$kpis['d7_improvement_rate']}%");
        }
        
        mtrace('  KPIs calculated for ' . count($teachers) . ' teachers');
    }
    
    /**
     * Get students for a teacher
     */
    private function get_teacher_students($teacherid) {
        global $DB;
        
        // This would need to be implemented based on your system's teacher-student relationship
        // For now, returning all students with active routines
        return $DB->get_records_sql(
            "SELECT DISTINCT userid as id
             FROM {routinecoach_exam}
             WHERE examdate > :now",
            ['now' => time()]
        );
    }
    
    /**
     * Send morning notifications
     */
    private function send_morning_notifications() {
        global $DB;
        
        mtrace('Sending morning notifications...');
        
        $now = time();
        $hour = (int)date('H', $now);
        
        // Only send between 6 AM and 9 AM
        if ($hour < 6 || $hour > 9) {
            mtrace('  Skipping notifications - outside notification hours');
            return;
        }
        
        // Get users with tasks today and notification enabled
        $sql = "SELECT DISTINCT e.userid, u.firstname, u.lastname, p.mbti_type,
                       COUNT(t.id) as task_count
                FROM {routinecoach_exam} e
                JOIN {routinecoach_routine} r ON r.examid = e.id
                JOIN {routinecoach_task} t ON t.routineid = r.id
                JOIN {user} u ON u.id = e.userid
                LEFT JOIN {routinecoach_pref} p ON p.userid = e.userid
                WHERE t.duedate >= :today
                  AND t.duedate < :tomorrow
                  AND t.completed = 0
                  AND (p.enabled = 1 OR p.enabled IS NULL)
                GROUP BY e.userid, u.firstname, u.lastname, p.mbti_type";
        
        $today = strtotime(date('Y-m-d 00:00:00'));
        $tomorrow = $today + 86400;
        
        $users = $DB->get_records_sql($sql, [
            'today' => $today,
            'tomorrow' => $tomorrow
        ]);
        
        foreach ($users as $user) {
            // Check weekly notification limit
            if (!$this->check_notification_limit($user->userid)) {
                continue;
            }
            
            // Generate MBTI-based message
            $message = $this->mbti_service->generate_morning_message(
                $user->mbti_type ?? 'ISTJ',
                $user->task_count,
                $user->firstname
            );
            
            // Send notification (would integrate with Moodle messaging)
            $this->send_notification($user->userid, $message);
            
            mtrace("  Sent notification to user {$user->userid}");
        }
        
        mtrace('  Sent ' . count($users) . ' notifications');
    }
    
    /**
     * Check if user hasn't exceeded notification limit
     */
    private function check_notification_limit($userid) {
        global $DB;
        
        $weekAgo = time() - (7 * 86400);
        
        // Get user preferences
        $pref = $DB->get_record('routinecoach_pref', ['userid' => $userid]);
        if (!$pref) {
            // Default: 3 notifications per week
            $maxNotifications = 3;
        } else {
            $maxNotifications = $pref->weekly_max_push;
        }
        
        // Count recent notifications
        $count = $DB->count_records_select(
            'routinecoach_log',
            'userid = :userid AND action LIKE :action AND timecreated > :weekago',
            [
                'userid' => $userid,
                'action' => 'notification_%',
                'weekago' => $weekAgo
            ]
        );
        
        return $count < $maxNotifications;
    }
    
    /**
     * Send notification to user
     */
    private function send_notification($userid, $message) {
        global $DB;
        
        // Log notification
        $log = new \stdClass();
        $log->userid = $userid;
        $log->action = 'notification_morning';
        $log->meta = json_encode(['message' => $message]);
        $log->timecreated = time();
        $DB->insert_record('routinecoach_log', $log);
        
        // TODO: Integrate with actual notification system
        // This would send via Moodle messaging, email, push, etc.
    }
    
    /**
     * Generate MBTI-based content for the day
     */
    private function generate_mbti_content() {
        global $DB;
        
        mtrace('Generating MBTI-based content...');
        
        // Get all users with MBTI types
        $users = $DB->get_records('routinecoach_pref', null, '', 'userid, mbti_type');
        
        foreach ($users as $user) {
            if (empty($user->mbti_type)) {
                continue;
            }
            
            // Generate personalized content
            $content = $this->mbti_service->generate_daily_content(
                $user->userid,
                $user->mbti_type
            );
            
            // Store in database
            $daily = new \stdClass();
            $daily->userid = $user->userid;
            $daily->content_type = 'mbti_daily';
            $daily->content = json_encode($content);
            $daily->date = date('Y-m-d');
            $daily->timecreated = time();
            
            // Check if already exists for today
            $existing = $DB->get_record('routinecoach_daily_content', [
                'userid' => $user->userid,
                'date' => date('Y-m-d')
            ]);
            
            if ($existing) {
                $daily->id = $existing->id;
                $DB->update_record('routinecoach_daily_content', $daily);
            } else {
                $DB->insert_record('routinecoach_daily_content', $daily);
            }
        }
        
        mtrace('  Generated MBTI content for ' . count($users) . ' users');
    }
    
    /**
     * Clean up old data
     */
    private function cleanup_old_data() {
        global $DB;
        
        mtrace('Cleaning up old data...');
        
        $thirtyDaysAgo = time() - (30 * 86400);
        $ninetyDaysAgo = time() - (90 * 86400);
        
        // Delete old completed tasks (older than 30 days)
        $deleted = $DB->delete_records_select(
            'routinecoach_task',
            'completed = 1 AND timemodified < :cutoff',
            ['cutoff' => $thirtyDaysAgo]
        );
        mtrace("  Deleted $deleted old completed tasks");
        
        // Delete old logs (older than 90 days)
        $deleted = $DB->delete_records_select(
            'routinecoach_log',
            'timecreated < :cutoff',
            ['cutoff' => $ninetyDaysAgo]
        );
        mtrace("  Deleted $deleted old log entries");
        
        // Delete old daily content (older than 30 days)
        $deleted = $DB->delete_records_select(
            'routinecoach_daily_content',
            'timecreated < :cutoff',
            ['cutoff' => $thirtyDaysAgo]
        );
        mtrace("  Deleted $deleted old daily content entries");
        
        // Deactivate routines for past exams
        $sql = "UPDATE {routinecoach_routine} r
                SET status = 'completed', timemodified = :now
                WHERE status = 'active'
                  AND EXISTS (
                      SELECT 1 FROM {routinecoach_exam} e
                      WHERE e.id = r.examid
                      AND e.examdate < :cutoff
                  )";
        
        $DB->execute($sql, ['now' => time(), 'cutoff' => time()]);
        
        mtrace('  Cleanup completed');
    }
}