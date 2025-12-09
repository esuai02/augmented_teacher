<?php declare(strict_types=1);
/**
 * Bridge between Moodle plugin and OmniUI core
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\service;

defined('MOODLE_INTERNAL') || die();

// Include OmniUI core files
require_once(__DIR__ . '/../../../../spiral/core/SpiralScheduler.php');
require_once(__DIR__ . '/../../../../spiral/core/ConflictResolver.php');
require_once(__DIR__ . '/../../../../spiral/core/TimeAllocator.php');
require_once(__DIR__ . '/../../../../spiral/core/RatioCalculator.php');

use \SpiralScheduler;
use \ConflictResolver;
use \TimeAllocator;
use \RatioCalculator;

class scheduler_bridge {
    
    private $scheduler;
    private $conflictResolver;
    private $timeAllocator;
    private $ratioCalculator;
    
    public function __construct() {
        $this->scheduler = new SpiralScheduler();
        $this->conflictResolver = new ConflictResolver();
        $this->timeAllocator = new TimeAllocator();
        $this->ratioCalculator = new RatioCalculator();
    }
    
    /**
     * Generate spiral schedule using OmniUI core
     */
    public function generate_schedule($params) {
        global $DB;
        
        // Validate parameters
        $this->validate_params($params);
        
        // Get student info
        $student = $DB->get_record('user', ['id' => $params['student_id']], '*', MUST_EXIST);
        
        // Get exam info if provided
        $exam = null;
        if (!empty($params['exam_id'])) {
            // Query from Alt42t database
            $exam = $this->get_exam_info($params['exam_id']);
        }
        
        // Calculate time allocation
        $timeAllocation = $this->timeAllocator->allocate(
            $params['start_date'],
            $params['end_date'],
            $params['daily_hours'] ?? 2,
            $params['grade_level'] ?? 'middle'
        );
        
        // Apply 7:3 ratio
        $ratioData = $this->ratioCalculator->calculate(
            $timeAllocation['total_hours'],
            $params['ratio_preview'] ?? 0.7,
            $params['ratio_review'] ?? 0.3
        );
        
        // Generate schedule
        $schedule = $this->scheduler->generateSchedule([
            'student_id' => $params['student_id'],
            'exam_date' => $params['end_date'],
            'units' => $params['units'] ?? [],
            'time_allocation' => $timeAllocation,
            'ratio_data' => $ratioData,
            'difficulty_weights' => $params['difficulty_weights'] ?? []
        ]);
        
        // Check for conflicts
        $conflicts = $this->conflictResolver->detectConflicts($schedule);
        
        // Resolve conflicts if any
        if (!empty($conflicts)) {
            $schedule = $this->conflictResolver->resolveConflicts($schedule, $conflicts);
        }
        
        return [
            'success' => true,
            'schedule' => $schedule,
            'conflicts' => $conflicts,
            'stats' => [
                'total_hours' => $timeAllocation['total_hours'],
                'preview_hours' => $ratioData['preview_hours'],
                'review_hours' => $ratioData['review_hours'],
                'sessions_count' => count($schedule['sessions'] ?? [])
            ]
        ];
    }
    
    /**
     * Modify existing schedule
     */
    public function modify_schedule($scheduleid, $modifications) {
        global $DB;
        
        // Get existing schedule
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        $scheduleData = json_decode($schedule->schedule_data, true);
        
        // Apply modifications
        foreach ($modifications as $key => $value) {
            if (isset($scheduleData[$key])) {
                $scheduleData[$key] = $value;
            }
        }
        
        // Recalculate if needed
        if (isset($modifications['sessions'])) {
            $conflicts = $this->conflictResolver->detectConflicts($scheduleData);
            if (!empty($conflicts)) {
                $scheduleData = $this->conflictResolver->resolveConflicts($scheduleData, $conflicts);
            }
        }
        
        // Update database
        $schedule->schedule_data = json_encode($scheduleData);
        $schedule->timemodified = time();
        $DB->update_record('spiral_schedules', $schedule);
        
        return [
            'success' => true,
            'schedule' => $scheduleData
        ];
    }
    
    /**
     * Get conflicts for schedule
     */
    public function get_conflicts($scheduleid) {
        global $DB;
        
        $schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid], '*', MUST_EXIST);
        $scheduleData = json_decode($schedule->schedule_data, true);
        
        $conflicts = $this->conflictResolver->detectConflicts($scheduleData);
        
        // Save conflicts to database
        foreach ($conflicts as $conflict) {
            $record = new \stdClass();
            $record->schedule_id = $scheduleid;
            $record->conflict_type = $conflict['type'];
            $record->conflict_data = json_encode($conflict);
            $record->timecreated = time();
            
            $DB->insert_record('spiral_conflicts', $record);
        }
        
        return $conflicts;
    }
    
    /**
     * Resolve specific conflict
     */
    public function resolve_conflict($conflictid, $resolution) {
        global $DB, $USER;
        
        $conflict = $DB->get_record('spiral_conflicts', ['id' => $conflictid], '*', MUST_EXIST);
        
        // Apply resolution
        $schedule = $DB->get_record('spiral_schedules', ['id' => $conflict->schedule_id], '*', MUST_EXIST);
        $scheduleData = json_decode($schedule->schedule_data, true);
        
        $scheduleData = $this->conflictResolver->applyResolution(
            $scheduleData,
            json_decode($conflict->conflict_data, true),
            $resolution
        );
        
        // Update schedule
        $schedule->schedule_data = json_encode($scheduleData);
        $schedule->timemodified = time();
        $DB->update_record('spiral_schedules', $schedule);
        
        // Update conflict record
        $conflict->resolution_type = $resolution['type'];
        $conflict->resolved_by = $USER->id;
        $conflict->timeresolved = time();
        $DB->update_record('spiral_conflicts', $conflict);
        
        return [
            'success' => true,
            'schedule' => $scheduleData
        ];
    }
    
    /**
     * Validate generation parameters
     */
    private function validate_params($params) {
        $required = ['student_id', 'start_date', 'end_date'];
        
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \moodle_exception('missingparam', 'local_spiral', '', $field);
            }
        }
        
        // Validate dates
        if ($params['start_date'] >= $params['end_date']) {
            throw new \moodle_exception('invaliddaterange', 'local_spiral');
        }
        
        // Validate ratios
        if (isset($params['ratio_preview']) && isset($params['ratio_review'])) {
            $total = $params['ratio_preview'] + $params['ratio_review'];
            if (abs($total - 1.0) > 0.01) {
                throw new \moodle_exception('invalidratio', 'local_spiral');
            }
        }
    }
    
    /**
     * Get exam information from Alt42t database
     */
    private function get_exam_info($examid) {
        // Connect to Alt42t database
        try {
            $alt42tDB = new \PDO(
                "mysql:host=" . ALT42T_DB_HOST . ";dbname=" . ALT42T_DB_NAME . ";charset=utf8mb4",
                ALT42T_DB_USER,
                ALT42T_DB_PASS
            );
            
            $stmt = $alt42tDB->prepare("
                SELECT * FROM student_exam_settings 
                WHERE id = :examid
            ");
            $stmt->execute(['examid' => $examid]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Failed to get exam info: " . $e->getMessage());
            return null;
        }
    }
}