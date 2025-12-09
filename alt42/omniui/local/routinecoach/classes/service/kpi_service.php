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
 * KPI calculation service for teacher dashboard
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\service;

defined('MOODLE_INTERNAL') || die();

/**
 * KPI service class
 */
class kpi_service {
    
    /**
     * Calculate teacher KPIs for dashboard
     *
     * @param int $teacherid Teacher user ID
     * @param array $studentids Array of student IDs
     * @return array KPI data
     */
    public function calculate_teacher_kpis($teacherid, $studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return $this->get_empty_kpis();
        }
        
        $kpis = [
            'completion_rate' => $this->calculate_completion_rate($studentids),
            'wrongnote_review_rate' => $this->calculate_wrongnote_review_rate($studentids),
            'd7_improvement_rate' => $this->calculate_d7_improvement_rate($studentids),
            'student_count' => count($studentids),
            'active_routines' => $this->count_active_routines($studentids),
            'upcoming_exams' => $this->count_upcoming_exams($studentids),
            'daily_stats' => $this->get_daily_stats($studentids),
            'weekly_trends' => $this->get_weekly_trends($studentids),
            'top_performers' => $this->get_top_performers($studentids),
            'at_risk_students' => $this->get_at_risk_students($studentids)
        ];
        
        return $kpis;
    }
    
    /**
     * Calculate overall routine completion rate
     *
     * @param array $studentids Student IDs
     * @return float Completion rate percentage
     */
    public function calculate_completion_rate($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return 0;
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        // Get tasks from last 7 days
        $sevenDaysAgo = time() - (7 * 86400);
        $params['cutoff'] = $sevenDaysAgo;
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid $insql
                  AND t.duedate > :cutoff";
        
        $result = $DB->get_record_sql($sql, $params);
        
        if ($result && $result->total > 0) {
            return round(($result->completed / $result->total) * 100, 1);
        }
        
        return 0;
    }
    
    /**
     * Calculate wrong note review rate
     *
     * @param array $studentids Student IDs
     * @return float Review rate percentage
     */
    public function calculate_wrongnote_review_rate($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return 0;
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        // Get wrongnote tasks from last 14 days
        $fourteenDaysAgo = time() - (14 * 86400);
        $params['cutoff'] = $fourteenDaysAgo;
        $params['type'] = 'wrongnote';
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid $insql
                  AND t.type = :type
                  AND t.duedate > :cutoff";
        
        $result = $DB->get_record_sql($sql, $params);
        
        if ($result && $result->total > 0) {
            return round(($result->completed / $result->total) * 100, 1);
        }
        
        return 0;
    }
    
    /**
     * Calculate D-7 improvement rate
     *
     * @param array $studentids Student IDs
     * @return float Improvement rate percentage
     */
    public function calculate_d7_improvement_rate($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return 0;
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        // Compare completion rates: D-7 to D-1 vs D-30 to D-8
        $now = time();
        
        // Get exams that are within D-7 or recently passed
        $sql = "SELECT 
                    e.id as examid,
                    e.examdate,
                    e.userid,
                    (
                        SELECT AVG(CASE WHEN t.completed = 1 THEN 100 ELSE 0 END)
                        FROM {routinecoach_task} t
                        JOIN {routinecoach_routine} r ON t.routineid = r.id
                        WHERE r.examid = e.id
                          AND t.duedate >= (e.examdate - (7 * 86400))
                          AND t.duedate < e.examdate
                    ) as d7_rate,
                    (
                        SELECT AVG(CASE WHEN t.completed = 1 THEN 100 ELSE 0 END)
                        FROM {routinecoach_task} t
                        JOIN {routinecoach_routine} r ON t.routineid = r.id
                        WHERE r.examid = e.id
                          AND t.duedate >= (e.examdate - (30 * 86400))
                          AND t.duedate < (e.examdate - (7 * 86400))
                    ) as d30_rate
                FROM {routinecoach_exam} e
                WHERE e.userid $insql
                  AND e.examdate BETWEEN :past AND :future";
        
        $params['past'] = $now - (7 * 86400);
        $params['future'] = $now + (7 * 86400);
        
        $exams = $DB->get_records_sql($sql, $params);
        
        if (empty($exams)) {
            return 0;
        }
        
        $totalImprovement = 0;
        $count = 0;
        
        foreach ($exams as $exam) {
            if ($exam->d30_rate !== null && $exam->d7_rate !== null) {
                $improvement = $exam->d7_rate - $exam->d30_rate;
                $totalImprovement += $improvement;
                $count++;
            }
        }
        
        if ($count > 0) {
            return round($totalImprovement / $count, 1);
        }
        
        return 0;
    }
    
    /**
     * Count active routines
     */
    private function count_active_routines($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return 0;
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        $params['status'] = 'active';
        
        $sql = "SELECT COUNT(DISTINCT r.id)
                FROM {routinecoach_routine} r
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid $insql
                  AND r.status = :status";
        
        return $DB->count_records_sql($sql, $params);
    }
    
    /**
     * Count upcoming exams
     */
    private function count_upcoming_exams($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return 0;
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        $params['now'] = time();
        
        $sql = "SELECT COUNT(*)
                FROM {routinecoach_exam} e
                WHERE e.userid $insql
                  AND e.examdate > :now";
        
        return $DB->count_records_sql($sql, $params);
    }
    
    /**
     * Get daily statistics
     */
    private function get_daily_stats($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return [];
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        $today = strtotime(date('Y-m-d 00:00:00'));
        $tomorrow = $today + 86400;
        
        $params['today'] = $today;
        $params['tomorrow'] = $tomorrow;
        
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(t.durationmin) as total_minutes,
                    SUM(CASE WHEN t.completed = 1 THEN t.durationmin ELSE 0 END) as completed_minutes
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid $insql
                  AND t.duedate >= :today
                  AND t.duedate < :tomorrow";
        
        $stats = $DB->get_record_sql($sql, $params);
        
        return [
            'total_tasks' => $stats->total_tasks ?? 0,
            'completed_tasks' => $stats->completed_tasks ?? 0,
            'total_minutes' => $stats->total_minutes ?? 0,
            'completed_minutes' => $stats->completed_minutes ?? 0,
            'completion_rate' => $stats->total_tasks > 0 
                ? round(($stats->completed_tasks / $stats->total_tasks) * 100, 1)
                : 0
        ];
    }
    
    /**
     * Get weekly trends
     */
    private function get_weekly_trends($studentids) {
        global $DB;
        
        if (empty($studentids)) {
            return [];
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        $trends = [];
        
        // Get completion rates for last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = strtotime(date('Y-m-d 00:00:00')) - ($i * 86400);
            $nextDate = $date + 86400;
            
            $params['date' . $i] = $date;
            $params['nextdate' . $i] = $nextDate;
            
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed
                    FROM {routinecoach_task} t
                    JOIN {routinecoach_routine} r ON t.routineid = r.id
                    JOIN {routinecoach_exam} e ON r.examid = e.id
                    WHERE e.userid $insql
                      AND t.duedate >= :date{$i}
                      AND t.duedate < :nextdate{$i}";
            
            $result = $DB->get_record_sql($sql, $params);
            
            $trends[] = [
                'date' => date('Y-m-d', $date),
                'day' => date('D', $date),
                'total' => $result->total ?? 0,
                'completed' => $result->completed ?? 0,
                'rate' => $result->total > 0 
                    ? round(($result->completed / $result->total) * 100, 1)
                    : 0
            ];
        }
        
        return $trends;
    }
    
    /**
     * Get top performing students
     */
    private function get_top_performers($studentids, $limit = 5) {
        global $DB;
        
        if (empty($studentids)) {
            return [];
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        $sevenDaysAgo = time() - (7 * 86400);
        $params['cutoff'] = $sevenDaysAgo;
        
        $sql = "SELECT 
                    e.userid,
                    u.firstname,
                    u.lastname,
                    COUNT(t.id) as total_tasks,
                    SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
                    ROUND(AVG(CASE WHEN t.completed = 1 THEN 100 ELSE 0 END), 1) as completion_rate
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                JOIN {user} u ON u.id = e.userid
                WHERE e.userid $insql
                  AND t.duedate > :cutoff
                GROUP BY e.userid, u.firstname, u.lastname
                ORDER BY completion_rate DESC
                LIMIT " . $limit;
        
        return $DB->get_records_sql($sql, $params);
    }
    
    /**
     * Get at-risk students
     */
    private function get_at_risk_students($studentids, $limit = 5) {
        global $DB;
        
        if (empty($studentids)) {
            return [];
        }
        
        list($insql, $params) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        
        $sevenDaysAgo = time() - (7 * 86400);
        $params['cutoff'] = $sevenDaysAgo;
        $params['threshold'] = 50; // Less than 50% completion rate
        
        $sql = "SELECT 
                    e.userid,
                    u.firstname,
                    u.lastname,
                    COUNT(t.id) as total_tasks,
                    SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
                    ROUND(AVG(CASE WHEN t.completed = 1 THEN 100 ELSE 0 END), 1) as completion_rate,
                    MIN(ex.examdate) as next_exam_date
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                JOIN {user} u ON u.id = e.userid
                LEFT JOIN {routinecoach_exam} ex ON ex.userid = e.userid AND ex.examdate > :now
                WHERE e.userid $insql
                  AND t.duedate > :cutoff
                GROUP BY e.userid, u.firstname, u.lastname
                HAVING completion_rate < :threshold
                ORDER BY completion_rate ASC
                LIMIT " . $limit;
        
        $params['now'] = time();
        
        return $DB->get_records_sql($sql, $params);
    }
    
    /**
     * Get empty KPIs structure
     */
    private function get_empty_kpis() {
        return [
            'completion_rate' => 0,
            'wrongnote_review_rate' => 0,
            'd7_improvement_rate' => 0,
            'student_count' => 0,
            'active_routines' => 0,
            'upcoming_exams' => 0,
            'daily_stats' => [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'total_minutes' => 0,
                'completed_minutes' => 0,
                'completion_rate' => 0
            ],
            'weekly_trends' => [],
            'top_performers' => [],
            'at_risk_students' => []
        ];
    }
    
    /**
     * Get cached KPIs for teacher
     */
    public function get_teacher_kpis($teacherid) {
        global $DB;
        
        // Check cache first
        $cache = $DB->get_record('routinecoach_kpi_cache', ['userid' => $teacherid]);
        
        if ($cache && (time() - $cache->timecalculated < 3600)) { // Cache valid for 1 hour
            return json_decode($cache->kpi_data, true);
        }
        
        // Calculate fresh KPIs
        $students = $this->get_teacher_students($teacherid);
        $studentIds = array_keys($students);
        
        return $this->calculate_teacher_kpis($teacherid, $studentIds);
    }
    
    /**
     * Get teacher's students
     */
    private function get_teacher_students($teacherid) {
        global $DB;
        
        // This would need proper implementation based on your system
        return $DB->get_records_sql(
            "SELECT DISTINCT userid as id
             FROM {routinecoach_exam}
             WHERE examdate > :now",
            ['now' => time()]
        );
    }
}