<?php declare(strict_types=1);
/**
 * Core Spiral Scheduler Class
 * 
 * @package    OmniUI
 * @subpackage spiral
 * @copyright  2024 MathKing
 */

namespace omniui\spiral\core;

require_once(__DIR__ . '/../config/spiral_config.php');
require_once(__DIR__ . '/../config/algorithm_rules.php');

class SpiralScheduler {
    
    private $studentId;
    private $examDate;
    private $startDate;
    private $previewRatio;
    private $reviewRatio;
    private $rules;
    private $db;
    
    public function __construct() {
        $this->rules = include(__DIR__ . '/../config/algorithm_rules.php');
        $this->db = get_spiral_db();
        $this->previewRatio = SPIRAL_DEFAULT_PREVIEW_RATIO;
        $this->reviewRatio = SPIRAL_DEFAULT_REVIEW_RATIO;
    }
    
    /**
     * Generate complete spiral schedule
     */
    public function generateSchedule($params) {
        // Initialize parameters
        $this->initializeParams($params);
        
        // Step 1: Load exam and curriculum data
        $examData = $this->loadExamData($params['exam_id'] ?? null);
        $units = $this->loadCurriculumUnits($params['units'] ?? []);
        
        // Step 2: Analyze student performance
        $studentAnalysis = $this->analyzeStudentPerformance($this->studentId);
        
        // Step 3: Calculate total available time
        $timeAllocation = $this->calculateTimeAllocation();
        
        // Step 4: Apply 7:3 ratio distribution
        $distribution = $this->applySpiraDistribution($timeAllocation, $units);
        
        // Step 5: Generate daily sessions
        $sessions = $this->generateDailySessions($distribution, $units, $studentAnalysis);
        
        // Step 6: Optimize schedule
        $optimizedSchedule = $this->optimizeSchedule($sessions, $studentAnalysis);
        
        // Step 7: Add metadata
        $schedule = $this->addScheduleMetadata($optimizedSchedule, $params);
        
        spiral_log("Schedule generated for student {$this->studentId}");
        
        return $schedule;
    }
    
    /**
     * Initialize scheduler parameters
     */
    private function initializeParams($params) {
        $this->studentId = $params['student_id'];
        $this->examDate = $params['exam_date'];
        $this->startDate = $params['start_date'] ?? date('Y-m-d');
        
        if (isset($params['ratio_preview'])) {
            $this->previewRatio = $params['ratio_preview'];
            $this->reviewRatio = 1 - $this->previewRatio;
        }
    }
    
    /**
     * Load exam information
     */
    private function loadExamData($examId) {
        if (!$examId) {
            return null;
        }
        
        try {
            // Query Alt42t database for exam info
            global $CFG;
            
            // Use config from Moodle settings or environment
            $host = $CFG->alt42t_dbhost ?? getenv('ALT42T_DB_HOST') ?: 'localhost';
            $dbname = $CFG->alt42t_dbname ?? getenv('ALT42T_DB_NAME') ?: 'alt42t';
            $user = $CFG->alt42t_dbuser ?? getenv('ALT42T_DB_USER') ?: 'root';
            $pass = $CFG->alt42t_dbpass ?? getenv('ALT42T_DB_PASS') ?: '';
            
            $alt42tDB = new \PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $alt42tDB->prepare("
                SELECT * FROM student_exam_settings 
                WHERE id = :examid
            ");
            $stmt->execute(['examid' => $examId]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            // Log error without exposing credentials
            debugging('Alt42t connection failed', DEBUG_DEVELOPER);
            spiral_log("Failed to load exam data", 'error');
            return null; // Graceful degradation
        }
    }
    
    /**
     * Load curriculum units
     */
    private function loadCurriculumUnits($unitIds = []) {
        $units = [];
        
        // If specific units provided
        if (!empty($unitIds)) {
            // TODO: Load from curriculum database
            foreach ($unitIds as $unitId) {
                $units[] = [
                    'id' => $unitId,
                    'name' => "Unit $unitId", // Placeholder
                    'difficulty' => rand(1, 5),
                    'estimated_hours' => rand(2, 6),
                    'prerequisites' => []
                ];
            }
        } else {
            // Load default curriculum units
            $units = $this->loadDefaultCurriculum();
        }
        
        return $units;
    }
    
    /**
     * Analyze student performance history
     */
    private function analyzeStudentPerformance($studentId) {
        $analysis = [
            'weak_areas' => [],
            'strong_areas' => [],
            'average_session_duration' => 40,
            'preferred_times' => [],
            'completion_rate' => 0.85,
            'recent_performance' => []
        ];
        
        try {
            // Get attendance and performance data
            $stmt = $this->db->prepare("
                SELECT 
                    AVG(actual_duration) as avg_duration,
                    AVG(performance_score) as avg_score,
                    COUNT(CASE WHEN completion_status = 'completed' THEN 1 END) / COUNT(*) as completion_rate
                FROM mdl_spiral_sessions ss
                JOIN mdl_spiral_schedules s ON s.id = ss.schedule_id
                WHERE s.student_id = :student_id
                AND ss.session_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute(['student_id' => $studentId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats) {
                $analysis['average_session_duration'] = $stats['avg_duration'] ?: 40;
                $analysis['completion_rate'] = $stats['completion_rate'] ?: 0.85;
            }
            
            // Identify weak areas
            $stmt = $this->db->prepare("
                SELECT unit_id, AVG(performance_score) as avg_score
                FROM mdl_spiral_sessions ss
                JOIN mdl_spiral_schedules s ON s.id = ss.schedule_id
                WHERE s.student_id = :student_id
                AND ss.completion_status = 'completed'
                GROUP BY unit_id
                HAVING avg_score < 70
            ");
            $stmt->execute(['student_id' => $studentId]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $analysis['weak_areas'][$row['unit_id']] = $row['avg_score'];
            }
            
        } catch (PDOException $e) {
            spiral_log("Failed to analyze student performance: " . $e->getMessage(), 'error');
        }
        
        return $analysis;
    }
    
    /**
     * Calculate total time allocation
     */
    private function calculateTimeAllocation() {
        $startTimestamp = strtotime($this->startDate);
        $examTimestamp = strtotime($this->examDate);
        $totalDays = ($examTimestamp - $startTimestamp) / 86400;
        
        if ($totalDays <= 0) {
            throw new Exception("Invalid date range");
        }
        
        // Get grade level to determine daily limits
        $gradeLevel = $this->getStudentGradeLevel($this->studentId);
        $dailyLimit = $GLOBALS['SPIRAL_DAILY_LIMITS'][$gradeLevel] ?? 120;
        
        // Calculate total available hours
        $weekdays = 0;
        $weekends = 0;
        
        for ($i = 0; $i < $totalDays; $i++) {
            $dayTimestamp = $startTimestamp + ($i * 86400);
            $dayOfWeek = date('w', $dayTimestamp);
            
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                $weekends++;
            } else {
                $weekdays++;
            }
        }
        
        // Different time allocation for weekdays vs weekends
        $weekdayHours = $weekdays * ($dailyLimit / 60);
        $weekendHours = $weekends * ($dailyLimit / 60) * 1.2; // 20% more on weekends
        
        return [
            'total_days' => $totalDays,
            'weekdays' => $weekdays,
            'weekends' => $weekends,
            'total_hours' => $weekdayHours + $weekendHours,
            'daily_limit_minutes' => $dailyLimit
        ];
    }
    
    /**
     * Apply spiral distribution (7:3 ratio)
     */
    private function applySpiraDistribution($timeAllocation, $units) {
        $totalMinutes = $timeAllocation['total_hours'] * 60;
        
        // Calculate preview and review time
        $previewMinutes = $totalMinutes * $this->previewRatio;
        $reviewMinutes = $totalMinutes * $this->reviewRatio;
        
        // Distribute among units based on priority
        $distribution = [];
        $totalPriority = 0;
        
        foreach ($units as $unit) {
            $priority = calculate_unit_priority($unit, [], $this->examDate);
            $distribution[$unit['id']] = [
                'unit' => $unit,
                'priority' => $priority,
                'preview_minutes' => 0,
                'review_minutes' => 0
            ];
            $totalPriority += $priority;
        }
        
        // Allocate time based on priority
        foreach ($distribution as $unitId => &$dist) {
            $ratio = $dist['priority'] / $totalPriority;
            $dist['preview_minutes'] = round($previewMinutes * $ratio);
            $dist['review_minutes'] = round($reviewMinutes * $ratio);
        }
        
        return $distribution;
    }
    
    /**
     * Generate daily sessions
     */
    private function generateDailySessions($distribution, $units, $studentAnalysis) {
        $sessions = [];
        $currentDate = $this->startDate;
        $sessionId = 1;
        
        while (strtotime($currentDate) <= strtotime($this->examDate)) {
            $dailySessions = $this->generateDaySchedule(
                $currentDate,
                $distribution,
                $studentAnalysis
            );
            
            foreach ($dailySessions as $session) {
                $session['id'] = $sessionId++;
                $session['date'] = $currentDate;
                $sessions[] = $session;
            }
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        return $sessions;
    }
    
    /**
     * Generate schedule for a single day
     */
    private function generateDaySchedule($date, $distribution, $studentAnalysis) {
        $dayOfWeek = date('w', strtotime($date));
        $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        $daysUntilExam = (strtotime($this->examDate) - strtotime($date)) / 86400;
        
        // Determine session configuration
        $config = $isWeekend ? 
                  $this->rules['session_distribution']['weekend'] :
                  $this->rules['session_distribution']['weekday'];
        
        // Adjust for exam proximity
        if ($daysUntilExam <= 7) {
            $config = $this->rules['session_distribution']['exam_week'];
        }
        
        // Get optimal session times
        $sessionTimes = get_optimal_session_times($date, $studentAnalysis);
        
        $sessions = [];
        foreach ($sessionTimes as $timeSlot) {
            // Select unit for this session
            $unit = $this->selectUnitForSession($distribution, $daysUntilExam);
            
            if ($unit) {
                $sessions[] = [
                    'time' => sprintf("%02d:00:00", $timeSlot['start_hour']),
                    'duration_minutes' => $timeSlot['duration'],
                    'session_type' => $daysUntilExam > 7 ? 'preview' : 'review',
                    'unit_id' => $unit['id'],
                    'unit_name' => $unit['name'],
                    'difficulty_level' => $unit['difficulty'] ?? 3
                ];
            }
        }
        
        return $sessions;
    }
    
    /**
     * Select unit for session based on distribution
     */
    private function selectUnitForSession(&$distribution, $daysUntilExam) {
        $sessionType = $daysUntilExam > 7 ? 'preview' : 'review';
        $minutesKey = $sessionType . '_minutes';
        
        // Find units with remaining time
        $availableUnits = array_filter($distribution, function($dist) use ($minutesKey) {
            return $dist[$minutesKey] > 0;
        });
        
        if (empty($availableUnits)) {
            return null;
        }
        
        // Weighted random selection
        $totalWeight = array_sum(array_column($availableUnits, 'priority'));
        $random = mt_rand(0, $totalWeight * 100) / 100;
        
        $cumulative = 0;
        foreach ($availableUnits as $unitId => &$dist) {
            $cumulative += $dist['priority'];
            if ($random <= $cumulative) {
                // Deduct time
                $sessionMinutes = min(45, $dist[$minutesKey]);
                $dist[$minutesKey] -= $sessionMinutes;
                
                return $dist['unit'];
            }
        }
        
        return null;
    }
    
    /**
     * Optimize generated schedule
     */
    private function optimizeSchedule($sessions, $studentAnalysis) {
        // Apply cognitive load balancing
        $sessions = $this->balanceCognitiveLoad($sessions);
        
        // Apply spaced repetition
        $sessions = $this->applySpacedRepetition($sessions);
        
        // Resolve any remaining conflicts
        $sessions = $this->resolveScheduleConflicts($sessions);
        
        return $sessions;
    }
    
    /**
     * Balance cognitive load across sessions
     */
    private function balanceCognitiveLoad($sessions) {
        $dailySessions = [];
        
        // Group by date
        foreach ($sessions as $session) {
            $date = $session['date'];
            if (!isset($dailySessions[$date])) {
                $dailySessions[$date] = [];
            }
            $dailySessions[$date][] = $session;
        }
        
        // Balance each day
        foreach ($dailySessions as $date => &$daySessions) {
            $totalDifficulty = array_sum(array_column($daySessions, 'difficulty_level'));
            $maxDifficulty = $this->rules['cognitive_load']['max_difficulty_sum_per_session'] * count($daySessions);
            
            if ($totalDifficulty > $maxDifficulty) {
                // Reduce difficulty or redistribute
                foreach ($daySessions as &$session) {
                    if ($session['difficulty_level'] > 3) {
                        $session['difficulty_level'] = 3;
                    }
                }
            }
        }
        
        // Flatten back to single array
        $optimized = [];
        foreach ($dailySessions as $daySessions) {
            $optimized = array_merge($optimized, $daySessions);
        }
        
        return $optimized;
    }
    
    /**
     * Apply spaced repetition principle
     */
    private function applySpacedRepetition($sessions) {
        $unitLastSeen = [];
        $intervals = $this->rules['cognitive_load']['spaced_repetition_intervals'];
        
        foreach ($sessions as &$session) {
            $unitId = $session['unit_id'];
            
            if (isset($unitLastSeen[$unitId])) {
                $daysSince = (strtotime($session['date']) - strtotime($unitLastSeen[$unitId])) / 86400;
                
                // Check if it matches spaced repetition interval
                $optimalInterval = false;
                foreach ($intervals as $interval) {
                    if (abs($daysSince - $interval) <= 1) {
                        $optimalInterval = true;
                        $session['repetition_bonus'] = true;
                        break;
                    }
                }
            }
            
            $unitLastSeen[$unitId] = $session['date'];
        }
        
        return $sessions;
    }
    
    /**
     * Resolve schedule conflicts
     */
    private function resolveScheduleConflicts($sessions) {
        // Check for time overlaps
        usort($sessions, function($a, $b) {
            $dateCompare = strcmp($a['date'], $b['date']);
            if ($dateCompare === 0) {
                return strcmp($a['time'], $b['time']);
            }
            return $dateCompare;
        });
        
        for ($i = 1; $i < count($sessions); $i++) {
            $prev = $sessions[$i - 1];
            $curr = $sessions[$i];
            
            if ($prev['date'] === $curr['date']) {
                $prevEnd = strtotime($prev['time']) + ($prev['duration_minutes'] * 60);
                $currStart = strtotime($curr['time']);
                
                if ($prevEnd > $currStart) {
                    // Conflict detected - shift current session
                    $newTime = $prevEnd + (SPIRAL_BREAK_MINUTES * 60);
                    $sessions[$i]['time'] = date('H:i:s', $newTime);
                }
            }
        }
        
        return $sessions;
    }
    
    /**
     * Add metadata to schedule
     */
    private function addScheduleMetadata($sessions, $params) {
        $totalPreviewMinutes = 0;
        $totalReviewMinutes = 0;
        
        foreach ($sessions as $session) {
            if ($session['session_type'] === 'preview') {
                $totalPreviewMinutes += $session['duration_minutes'];
            } else {
                $totalReviewMinutes += $session['duration_minutes'];
            }
        }
        
        return [
            'student_id' => $this->studentId,
            'exam_date' => $this->examDate,
            'start_date' => $this->startDate,
            'total_sessions' => count($sessions),
            'total_hours' => ($totalPreviewMinutes + $totalReviewMinutes) / 60,
            'preview_hours' => $totalPreviewMinutes / 60,
            'review_hours' => $totalReviewMinutes / 60,
            'actual_preview_ratio' => $totalPreviewMinutes / ($totalPreviewMinutes + $totalReviewMinutes),
            'actual_review_ratio' => $totalReviewMinutes / ($totalPreviewMinutes + $totalReviewMinutes),
            'sessions' => $sessions,
            'generated_at' => date('Y-m-d H:i:s'),
            'algorithm_version' => SPIRAL_VERSION
        ];
    }
    
    /**
     * Get student grade level
     */
    private function getStudentGradeLevel($studentId) {
        // TODO: Implement actual grade level detection
        return 'middle'; // Default to middle school
    }
    
    /**
     * Load default curriculum
     */
    private function loadDefaultCurriculum() {
        // TODO: Load from actual curriculum database
        return [
            ['id' => 'math_algebra', 'name' => '대수학', 'difficulty' => 3, 'estimated_hours' => 5],
            ['id' => 'math_geometry', 'name' => '기하학', 'difficulty' => 4, 'estimated_hours' => 4],
            ['id' => 'math_statistics', 'name' => '통계', 'difficulty' => 2, 'estimated_hours' => 3],
            ['id' => 'math_functions', 'name' => '함수', 'difficulty' => 5, 'estimated_hours' => 6]
        ];
    }
}