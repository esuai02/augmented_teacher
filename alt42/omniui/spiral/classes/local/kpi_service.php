<?php declare(strict_types=1);
/**
 * KPI Collection and Analysis Service
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Service for collecting and analyzing Spiral Scheduler KPIs
 */
final class kpi_service {
    
    /**
     * Collect daily KPI metrics
     * 
     * @return array Collected KPI data
     */
    public static function collect_daily(): array {
        global $DB;
        
        // 7:3 비율 준수율: preview/review 시간(분)으로 비율 계산
        $ratio = self::calculate_ratio_achievement();
        
        // 충돌률: 최근 1일 기록 / 활성 스케줄 수
        $conflictRate = self::calculate_conflict_rate();
        
        // 완료율: 완료된 세션 / 전체 세션
        $completionRate = self::calculate_completion_rate();
        
        // 교사 수정 횟수
        $modificationCount = self::calculate_modification_count();
        
        // 사용자 만족도 (간접 지표)
        $satisfactionScore = self::calculate_satisfaction_score();
        
        // 스케줄 활용률
        $utilizationRate = self::calculate_utilization_rate();
        
        $kpiData = [
            'ratio' => $ratio,
            'conflict' => $conflictRate,
            'completion' => $completionRate,
            'modcnt' => $modificationCount,
            'satisfaction' => $satisfactionScore,
            'utilization' => $utilizationRate,
            'timestamp' => time()
        ];
        
        // KPI 이력 저장
        self::store_kpi_history($kpiData);
        
        // 메일 발송
        $admin = get_admin();
        notify::daily_summary($admin, $kpiData);
        
        return $kpiData;
    }
    
    /**
     * Calculate 7:3 ratio achievement percentage
     */
    private static function calculate_ratio_achievement(): float {
        global $DB;
        
        $sql = "SELECT 
                    SUM(CASE WHEN session_type = 'preview' THEN duration_minutes ELSE 0 END) as preview_minutes,
                    SUM(CASE WHEN session_type = 'review' THEN duration_minutes ELSE 0 END) as review_minutes
                FROM {spiral_sessions} ses
                JOIN {spiral_schedules} s ON s.id = ses.schedule_id
                WHERE s.status IN ('active', 'published')
                AND ses.session_date >= :week_ago
                AND ses.session_date <= :today";
        
        $params = [
            'week_ago' => strtotime('-7 days'),
            'today' => time()
        ];
        
        $result = $DB->get_record_sql($sql, $params);
        
        if (!$result || ($result->preview_minutes + $result->review_minutes) <= 0) {
            return 0.0;
        }
        
        $totalMinutes = $result->preview_minutes + $result->review_minutes;
        $actualPreviewRatio = $result->preview_minutes / $totalMinutes;
        
        // 목표 70%와의 차이를 백분율로 계산
        $targetRatio = 0.7;
        $deviation = abs($actualPreviewRatio - $targetRatio);
        $achievement = max(0, (1 - ($deviation / 0.1)) * 100); // 10% 이내는 100%, 그 이상은 감점
        
        return round($achievement, 1);
    }
    
    /**
     * Calculate conflict rate percentage
     */
    private static function calculate_conflict_rate(): float {
        global $DB;
        
        $dayStart = strtotime('today');
        
        // 최근 1일간 발생한 충돌 수
        $conflictCount = $DB->count_records_select('spiral_conflicts', 
            'timecreated >= ? AND resolution_type = ?', 
            [$dayStart, 'suggestion']
        );
        
        // 활성 스케줄 수
        $activeSchedules = $DB->count_records_select('spiral_schedules', 
            "status IN ('active', 'published')"
        );
        
        if ($activeSchedules === 0) {
            return 0.0;
        }
        
        return round(($conflictCount / $activeSchedules) * 100, 1);
    }
    
    /**
     * Calculate completion rate percentage
     */
    private static function calculate_completion_rate(): float {
        global $DB;
        
        $sql = "SELECT 
                    SUM(CASE WHEN completion_status = 'completed' THEN 1 ELSE 0 END) as completed,
                    COUNT(*) as total
                FROM {spiral_sessions} ses
                JOIN {spiral_schedules} s ON s.id = ses.schedule_id
                WHERE s.status IN ('active', 'published')
                AND ses.session_date <= ?";
        
        $result = $DB->get_record_sql($sql, [time()]);
        
        if (!$result || $result->total === 0) {
            return 0.0;
        }
        
        return round(($result->completed / $result->total) * 100, 1);
    }
    
    /**
     * Calculate teacher modification count
     */
    private static function calculate_modification_count(): int {
        global $DB;
        
        $dayStart = strtotime('today');
        
        // timemodified가 오늘 이후인 세션 수 (수정된 세션)
        $modifiedCount = $DB->count_records_select('spiral_sessions', 
            'timemodified >= ? AND timemodified != timecreated', 
            [$dayStart]
        );
        
        return (int)$modifiedCount;
    }
    
    /**
     * Calculate user satisfaction score (indirect metrics)
     */
    private static function calculate_satisfaction_score(): float {
        global $DB;
        
        // 간접 지표들로 만족도 계산
        $factors = [];
        
        // 1. 스케줄 발행률 (생성 대비 발행)
        $publishedCount = $DB->count_records('spiral_schedules', ['status' => 'published']);
        $totalCount = $DB->count_records_select('spiral_schedules', "status != 'deleted'");
        $publishRate = $totalCount > 0 ? ($publishedCount / $totalCount) : 0;
        $factors['publish_rate'] = $publishRate * 100;
        
        // 2. 세션 참석률
        $attendanceRate = $DB->get_field_sql("
            SELECT AVG(CASE WHEN completion_status = 'completed' THEN 100 ELSE 0 END)
            FROM {spiral_sessions} ses
            JOIN {spiral_schedules} s ON s.id = ses.schedule_id
            WHERE s.status = 'published'
            AND ses.session_date <= ?
        ", [time()]) ?: 0;
        $factors['attendance_rate'] = (float)$attendanceRate;
        
        // 3. 교사 수정 빈도 (적당한 수정은 긍정적)
        $modCount = self::calculate_modification_count();
        $totalSessions = $DB->count_records_select('spiral_sessions', 
            'session_date >= ?', [strtotime('today')]
        );
        $modRate = $totalSessions > 0 ? ($modCount / $totalSessions) : 0;
        $factors['modification_balance'] = max(0, (1 - abs($modRate - 0.1)) * 100); // 10% 수정률이 이상적
        
        // 가중 평균으로 만족도 계산
        $weights = ['publish_rate' => 0.4, 'attendance_rate' => 0.4, 'modification_balance' => 0.2];
        $weightedSum = 0;
        $totalWeight = 0;
        
        foreach ($factors as $key => $value) {
            if (isset($weights[$key])) {
                $weightedSum += $value * $weights[$key];
                $totalWeight += $weights[$key];
            }
        }
        
        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : 0.0;
    }
    
    /**
     * Calculate schedule utilization rate
     */
    private static function calculate_utilization_rate(): float {
        global $DB;
        
        $sql = "SELECT 
                    COUNT(DISTINCT s.id) as active_schedules,
                    COUNT(DISTINCT s.teacher_id) as active_teachers,
                    COUNT(DISTINCT s.student_id) as active_students
                FROM {spiral_schedules} s
                WHERE s.status IN ('active', 'published')
                AND s.end_date >= ?";
        
        $result = $DB->get_record_sql($sql, [time()]);
        
        // 전체 교사 수 대비 활용률
        $totalTeachers = $DB->count_records_select('user', 
            "deleted = 0 AND id IN (
                SELECT DISTINCT userid FROM {user_info_data} 
                WHERE fieldid = 22 AND data != 'student'
            )"
        );
        
        if ($totalTeachers === 0) {
            return 0.0;
        }
        
        return round(($result->active_teachers / $totalTeachers) * 100, 1);
    }
    
    /**
     * Store KPI history for trend analysis
     */
    private static function store_kpi_history(array $kpiData): void {
        global $DB;
        
        $record = (object)[
            'date_collected' => date('Y-m-d'),
            'ratio_achievement' => $kpiData['ratio'],
            'conflict_rate' => $kpiData['conflict'],
            'completion_rate' => $kpiData['completion'],
            'modification_count' => $kpiData['modcnt'],
            'satisfaction_score' => $kpiData['satisfaction'],
            'utilization_rate' => $kpiData['utilization'],
            'timecreated' => time()
        ];
        
        // 오늘 날짜의 기존 기록 삭제 (중복 방지)
        $DB->delete_records('spiral_kpi_history', ['date_collected' => $record->date_collected]);
        
        // 새 기록 저장
        $DB->insert_record('spiral_kpi_history', $record);
    }
    
    /**
     * Get KPI trends for dashboard
     */
    public static function get_trends(int $days = 30): array {
        global $DB;
        
        $sql = "SELECT *
                FROM {spiral_kpi_history}
                WHERE date_collected >= ?
                ORDER BY date_collected DESC
                LIMIT ?";
        
        $records = $DB->get_records_sql($sql, [
            date('Y-m-d', strtotime("-{$days} days")),
            $days
        ]);
        
        return array_values($records);
    }
    
    /**
     * Get current KPI snapshot
     */
    public static function get_current_snapshot(): array {
        return self::collect_daily();
    }
}