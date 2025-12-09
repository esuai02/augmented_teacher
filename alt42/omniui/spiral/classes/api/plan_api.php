<?php
/**
 * Spiral Plan API
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\api;

defined('MOODLE_INTERNAL') || die();

use context_system;
use context_course;

class plan_api {
    
    /**
     * Check if user has permission to manage schedules
     */
    public static function require_teacher_permission($userid = null) {
        global $USER;
        
        if (!$userid) {
            $userid = $USER->id;
        }
        
        $context = context_system::instance();
        
        // Check for editingteacher role
        if (!has_capability('moodle/course:manageactivities', $context, $userid)) {
            throw new \moodle_exception('nopermission', 'local_spiral');
        }
        
        return true;
    }
    
    /**
     * Simplified teacher capability check
     */
    public static function require_teacher_capability(): void {
        global $PAGE;
        
        require_login();
        $context = $PAGE->context ?? context_system::instance();
        require_capability('moodle/course:update', $context);
    }
    
    /**
     * Create new spiral schedule
     */
    public static function create_schedule($data) {
        global $DB, $USER;
        
        self::require_teacher_permission();
        
        $record = new \stdClass();
        $record->teacher_id = $USER->id;
        $record->student_id = $data->student_id;
        $record->exam_id = $data->exam_id ?? null;
        $record->schedule_type = $data->schedule_type ?? 'auto';
        $record->status = 'draft';
        $record->ratio_preview = $data->ratio_preview ?? 0.70;
        $record->ratio_review = $data->ratio_review ?? 0.30;
        $record->total_hours = $data->total_hours ?? 0;
        $record->start_date = $data->start_date;
        $record->end_date = $data->end_date;
        $record->schedule_data = json_encode($data->schedule_data ?? []);
        $record->timecreated = time();
        $record->timemodified = time();
        
        $id = $DB->insert_record('spiral_schedules', $record);
        
        // Log event
        $event = \local_spiral\event\schedule_created::create([
            'objectid' => $id,
            'context' => context_system::instance(),
            'relateduserid' => $data->student_id
        ]);
        $event->trigger();
        
        return $id;
    }
    
    /**
     * Update existing schedule
     */
    public static function update_schedule($scheduleid, $data) {
        global $DB, $USER;
        
        self::require_teacher_permission();
        
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        
        // Check ownership
        if ($schedule->teacher_id != $USER->id && !is_siteadmin()) {
            throw new \moodle_exception('nopermission', 'local_spiral');
        }
        
        $schedule->schedule_type = $data->schedule_type ?? $schedule->schedule_type;
        $schedule->ratio_preview = $data->ratio_preview ?? $schedule->ratio_preview;
        $schedule->ratio_review = $data->ratio_review ?? $schedule->ratio_review;
        $schedule->total_hours = $data->total_hours ?? $schedule->total_hours;
        $schedule->start_date = $data->start_date ?? $schedule->start_date;
        $schedule->end_date = $data->end_date ?? $schedule->end_date;
        
        if (isset($data->schedule_data)) {
            $schedule->schedule_data = json_encode($data->schedule_data);
        }
        
        $schedule->timemodified = time();
        
        return $DB->update_record('spiral_schedules', $schedule);
    }
    
    /**
     * Publish schedule (change status to published)
     */
    public static function publish_schedule($scheduleid) {
        global $DB, $USER;
        
        self::require_teacher_permission();
        
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        
        // Check ownership
        if ($schedule->teacher_id != $USER->id && !is_siteadmin()) {
            throw new \moodle_exception('nopermission', 'local_spiral');
        }
        
        $schedule->status = 'published';
        $schedule->timemodified = time();
        
        $DB->update_record('spiral_schedules', $schedule);
        
        // Trigger event
        $event = \local_spiral\event\schedule_published::create([
            'objectid' => $scheduleid,
            'context' => context_system::instance(),
            'relateduserid' => $schedule->student_id
        ]);
        $event->trigger();
        
        // Send notification to student
        self::notify_student($schedule->student_id, $scheduleid);
        
        return true;
    }
    
    /**
     * Get schedules for teacher
     */
    public static function get_teacher_schedules($teacherid = null) {
        global $DB, $USER;
        
        if (!$teacherid) {
            $teacherid = $USER->id;
        }
        
        self::require_teacher_permission($teacherid);
        
        $sql = "SELECT s.*, u.firstname, u.lastname, u.email 
                FROM {spiral_schedules} s
                JOIN {user} u ON u.id = s.student_id
                WHERE s.teacher_id = ?
                ORDER BY s.timemodified DESC";
        
        return $DB->get_records_sql($sql, [$teacherid]);
    }
    
    /**
     * Get schedule details with sessions
     */
    public static function get_schedule_details($scheduleid) {
        global $DB;
        
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        
        // Get sessions
        $sessions = $DB->get_records('spiral_sessions', 
                                     ['schedule_id' => $scheduleid], 
                                     'session_date, session_time');
        
        // Get conflicts
        $conflicts = $DB->get_records('spiral_conflicts', 
                                      ['schedule_id' => $scheduleid],
                                      'timecreated DESC');
        
        return [
            'schedule' => $schedule,
            'sessions' => $sessions,
            'conflicts' => $conflicts
        ];
    }
    
    /**
     * Delete schedule
     */
    public static function delete_schedule($scheduleid) {
        global $DB, $USER;
        
        self::require_teacher_permission();
        
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        
        // Check ownership
        if ($schedule->teacher_id != $USER->id && !is_siteadmin()) {
            throw new \moodle_exception('nopermission', 'local_spiral');
        }
        
        // Delete related records (sessions and conflicts will be cascade deleted)
        $DB->delete_records('spiral_schedules', ['id' => $scheduleid]);
        
        return true;
    }
    
    /**
     * Send notification to student
     */
    private static function notify_student($studentid, $scheduleid) {
        global $DB;
        
        $student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        
        // TODO: Implement notification logic
        // Can use Moodle messaging API or custom notification system
        
        return true;
    }
}