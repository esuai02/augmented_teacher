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
 * Daily cron task for routine coach.
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');

use local_routinecoach\service\routine_service;

/**
 * Daily cron task class - runs at 04:00 daily
 */
class daily_cron extends \core\task\scheduled_task {
    
    /** @var routine_service Service instance */
    private $service;
    
    /**
     * Get the name of the task
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_daily_cron', 'local_routinecoach');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        $this->service = new routine_service();
        
        mtrace('Starting Routine Coach daily cron at ' . date('Y-m-d H:i:s'));
        
        // Process exam countdowns for key milestones
        $this->process_exam_countdowns();
        
        // Rebuild tasks for upcoming exams
        $this->rebuild_upcoming_tasks();
        
        // Check and enforce push notification limits
        $this->check_push_limits();
        
        // Clean up old completed tasks
        $this->cleanup_old_tasks();
        
        mtrace('Routine Coach daily cron completed.');
    }
    
    /**
     * Process exam countdowns and trigger actions for D-30, D-7, D-1
     */
    private function process_exam_countdowns() {
        global $DB;
        
        mtrace('Processing exam countdowns...');
        
        $now = time();
        $today = strtotime(date('Y-m-d 00:00:00', $now));
        
        // Get all active exams
        $sql = "SELECT e.*, r.id as routineid, r.status, r.ratio_concept, r.ratio_review
                FROM {routinecoach_exam} e
                LEFT JOIN {routinecoach_routine} r ON r.examid = e.id
                WHERE e.examdate > :now
                  AND (r.status = 'active' OR r.status IS NULL)
                ORDER BY e.examdate ASC";
        
        $exams = $DB->get_records_sql($sql, ['now' => $now]);
        
        foreach ($exams as $exam) {
            $daysUntilExam = floor(($exam->examdate - $today) / 86400);
            
            // Check for key milestone days
            $this->process_milestone($exam, $daysUntilExam, 30);
            $this->process_milestone($exam, $daysUntilExam, 7);
            $this->process_milestone($exam, $daysUntilExam, 1);
        }
    }
    
    /**
     * Process milestone day actions
     *
     * @param object $exam Exam record
     * @param int $daysUntilExam Days until exam
     * @param int $milestone Milestone day (30, 7, or 1)
     */
    private function process_milestone($exam, $daysUntilExam, $milestone) {
        global $DB;
        
        if ($daysUntilExam != $milestone) {
            return;
        }
        
        mtrace("Processing D-{$milestone} for exam {$exam->id} ({$exam->label})");
        
        // Check if already processed today
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $existing = $DB->get_record_sql(
            "SELECT * FROM {routinecoach_log} 
             WHERE userid = :userid 
               AND examid = :examid 
               AND action = :action 
               AND timecreated >= :today",
            [
                'userid' => $exam->userid,
                'examid' => $exam->id,
                'action' => 'milestone_d' . $milestone,
                'today' => $todayStart
            ]
        );
        
        if ($existing) {
            mtrace("  Already processed today, skipping.");
            return;
        }
        
        // Adjust routine ratios based on milestone
        if ($exam->routineid) {
            $this->adjust_routine_ratio($exam->routineid, $milestone);
        }
        
        // Rebuild tasks with new ratios
        $this->rebuild_exam_tasks($exam->id);
        
        // Send notification if within push limits
        $this->send_milestone_notification($exam->userid, $exam, $milestone);
        
        // Log the milestone processing
        $this->service->log('milestone_d' . $milestone, [
            'userid' => $exam->userid,
            'examid' => $exam->id,
            'label' => $exam->label,
            'days_until' => $milestone
        ]);
    }
    
    /**
     * Adjust routine ratio based on milestone
     *
     * @param int $routineid Routine ID
     * @param int $milestone Milestone day
     */
    private function adjust_routine_ratio($routineid, $milestone) {
        global $DB;
        
        $routine = $DB->get_record('routinecoach_routine', ['id' => $routineid]);
        if (!$routine) {
            return;
        }
        
        $originalRatio = $routine->ratio_concept . ':' . $routine->ratio_review;
        
        switch ($milestone) {
            case 30:
                // D-30: Standard 7:3 ratio
                $routine->ratio_concept = 70;
                $routine->ratio_review = 30;
                break;
                
            case 7:
                // D-7: Shift to more review (3:7)
                $routine->ratio_concept = 30;
                $routine->ratio_review = 70;
                break;
                
            case 1:
                // D-1: Pure review (0:10)
                $routine->ratio_concept = 0;
                $routine->ratio_review = 100;
                break;
        }
        
        if ($originalRatio != $routine->ratio_concept . ':' . $routine->ratio_review) {
            $routine->timemodified = time();
            $DB->update_record('routinecoach_routine', $routine);
            
            mtrace("  Adjusted ratio from {$originalRatio} to {$routine->ratio_concept}:{$routine->ratio_review}");
            
            $this->service->log('ratio_adjusted', [
                'routineid' => $routineid,
                'milestone' => 'D-' . $milestone,
                'old_ratio' => $originalRatio,
                'new_ratio' => $routine->ratio_concept . ':' . $routine->ratio_review
            ]);
        }
    }
    
    /**
     * Rebuild tasks for upcoming exams
     */
    private function rebuild_upcoming_tasks() {
        global $DB;
        
        mtrace('Rebuilding tasks for upcoming exams...');
        
        $now = time();
        $weekFromNow = $now + (7 * 86400);
        
        // Get exams happening in the next week
        $sql = "SELECT e.*, r.id as routineid
                FROM {routinecoach_exam} e
                JOIN {routinecoach_routine} r ON r.examid = e.id
                WHERE e.examdate > :now 
                  AND e.examdate <= :weekfromnow
                  AND r.status = 'active'";
        
        $upcomingExams = $DB->get_records_sql($sql, [
            'now' => $now,
            'weekfromnow' => $weekFromNow
        ]);
        
        foreach ($upcomingExams as $exam) {
            // Check if tasks need rebuilding (e.g., no tasks for today)
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayEnd = strtotime(date('Y-m-d 23:59:59'));
            
            $todayTaskCount = $DB->count_records_select(
                'routinecoach_task',
                'routineid = :routineid AND duedate >= :start AND duedate <= :end',
                [
                    'routineid' => $exam->routineid,
                    'start' => $todayStart,
                    'end' => $todayEnd
                ]
            );
            
            if ($todayTaskCount == 0) {
                mtrace("  Rebuilding tasks for exam {$exam->id} ({$exam->label})");
                $this->rebuild_exam_tasks($exam->id);
            }
        }
    }
    
    /**
     * Rebuild tasks for a specific exam
     *
     * @param int $examid Exam ID
     */
    private function rebuild_exam_tasks($examid) {
        global $DB;
        
        $exam = $DB->get_record('routinecoach_exam', ['id' => $examid]);
        if (!$exam) {
            return;
        }
        
        // Use the service to rebuild
        $this->service->on_exam_saved(
            $exam->userid,
            $exam->examdate,
            $exam->scheduleid,
            $exam->label
        );
        
        mtrace("    Tasks rebuilt for exam {$examid}");
    }
    
    /**
     * Check and enforce weekly push notification limits
     */
    private function check_push_limits() {
        global $DB;
        
        mtrace('Checking push notification limits...');
        
        // Get users with preferences
        $users = $DB->get_records('routinecoach_pref', ['enabled' => 1]);
        
        $weekAgo = time() - (7 * 86400);
        
        foreach ($users as $pref) {
            // Count notifications sent in past week
            $notificationCount = $DB->count_records_select(
                'routinecoach_log',
                'userid = :userid AND action LIKE :action AND timecreated > :weekago',
                [
                    'userid' => $pref->userid,
                    'action' => 'notification_%',
                    'weekago' => $weekAgo
                ]
            );
            
            if ($notificationCount >= $pref->weekly_max_push) {
                mtrace("  User {$pref->userid} has reached weekly push limit ({$pref->weekly_max_push})");
                
                // Mark in log to skip future notifications this week
                $this->service->log('push_limit_reached', [
                    'userid' => $pref->userid,
                    'count' => $notificationCount,
                    'limit' => $pref->weekly_max_push
                ]);
            }
        }
    }
    
    /**
     * Send milestone notification to user
     *
     * @param int $userid User ID
     * @param object $exam Exam record
     * @param int $milestone Milestone day
     */
    private function send_milestone_notification($userid, $exam, $milestone) {
        global $DB;
        
        // Check user preferences
        $pref = $DB->get_record('routinecoach_pref', ['userid' => $userid]);
        
        if (!$pref) {
            // Create default preferences
            $pref = new \stdClass();
            $pref->userid = $userid;
            $pref->weekly_max_push = 2;
            $pref->quiet_hours_from = 22;
            $pref->quiet_hours_to = 8;
            $pref->timezone = 'Asia/Seoul';
            $pref->enabled = 1;
            $pref->timecreated = time();
            $pref->timemodified = time();
            $DB->insert_record('routinecoach_pref', $pref);
        }
        
        if (!$pref->enabled) {
            return;
        }
        
        // Check quiet hours
        $currentHour = (int)date('H');
        if ($currentHour >= $pref->quiet_hours_from || $currentHour < $pref->quiet_hours_to) {
            mtrace("  Skipping notification during quiet hours");
            return;
        }
        
        // Check weekly push limit
        $weekAgo = time() - (7 * 86400);
        $notificationCount = $DB->count_records_select(
            'routinecoach_log',
            'userid = :userid AND action LIKE :action AND timecreated > :weekago',
            [
                'userid' => $userid,
                'action' => 'notification_%',
                'weekago' => $weekAgo
            ]
        );
        
        if ($notificationCount >= $pref->weekly_max_push) {
            mtrace("  Skipping notification - weekly limit reached");
            return;
        }
        
        // Send notification (integrate with Moodle messaging system)
        $message = $this->build_milestone_message($exam, $milestone);
        
        // Log the notification
        $this->service->log('notification_sent', [
            'userid' => $userid,
            'examid' => $exam->id,
            'milestone' => 'D-' . $milestone,
            'message' => $message
        ]);
        
        mtrace("  Notification sent to user {$userid} for D-{$milestone}");
    }
    
    /**
     * Build milestone notification message
     *
     * @param object $exam Exam record
     * @param int $milestone Milestone day
     * @return string Message text
     */
    private function build_milestone_message($exam, $milestone) {
        $examDate = date('Y-m-d', $exam->examdate);
        
        switch ($milestone) {
            case 30:
                return "[{$exam->label}] 시험이 30일 남았습니다! 오늘부터 체계적인 준비를 시작하세요. (시험일: {$examDate})";
                
            case 7:
                return "[{$exam->label}] 시험이 일주일 남았습니다! 복습 위주로 전환하여 마무리 준비를 하세요. (시험일: {$examDate})";
                
            case 1:
                return "[{$exam->label}] 내일이 시험입니다! 오늘은 최종 점검과 컨디션 관리에 집중하세요. 파이팅!";
                
            default:
                return "[{$exam->label}] 시험 D-{$milestone} 알림";
        }
    }
    
    /**
     * Clean up old completed tasks
     */
    private function cleanup_old_tasks() {
        global $DB;
        
        mtrace('Cleaning up old completed tasks...');
        
        // Delete completed tasks older than 30 days
        $thirtyDaysAgo = time() - (30 * 86400);
        
        $deletedCount = $DB->delete_records_select(
            'routinecoach_task',
            'completed = 1 AND timemodified < :cutoff',
            ['cutoff' => $thirtyDaysAgo]
        );
        
        if ($deletedCount > 0) {
            mtrace("  Deleted {$deletedCount} old completed tasks");
            
            $this->service->log('cleanup_completed', [
                'count' => $deletedCount,
                'cutoff_days' => 30
            ]);
        }
        
        // Also clean up old exam records with no future date
        $now = time();
        $deletedExams = $DB->delete_records_select(
            'routinecoach_exam',
            'examdate < :cutoff',
            ['cutoff' => $thirtyDaysAgo]
        );
        
        if ($deletedExams > 0) {
            mtrace("  Deleted {$deletedExams} old exam records");
        }
    }
}