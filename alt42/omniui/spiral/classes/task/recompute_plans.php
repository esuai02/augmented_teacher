<?php declare(strict_types=1);
/**
 * Scheduled task for recomputing spiral plans and collecting KPIs
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to recompute active plans and collect daily KPIs
 */
final class recompute_plans extends \core\task\scheduled_task {
    
    /**
     * Get the name of this task
     */
    public function get_name(): string {
        return get_string('task_recompute_plans', 'local_spiral');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        $now = time();
        $horizon = 14 * 24 * 60 * 60; // 14 days in seconds
        
        mtrace('Starting spiral plan recomputation...');
        
        try {
            // 1) 대상 스케줄 조회 (active|draft, 종료일이 14일 이내)
            $sql = "SELECT s.*, u.firstname, u.lastname 
                    FROM {spiral_schedules} s
                    JOIN {user} u ON u.id = s.student_id
                    WHERE s.status IN ('active', 'draft', 'published') 
                    AND s.end_date >= :today 
                    AND s.end_date <= :until
                    AND u.deleted = 0";
            
            $params = [
                'today' => $now,
                'until' => $now + $horizon
            ];
            
            $schedules = $DB->get_records_sql($sql, $params);
            mtrace('Found ' . count($schedules) . ' schedules to process');
            
            // 2) 각 스케줄 충돌 스캔 및 제안 기록
            $conflictCount = 0;
            $resolver = new \omniui\spiral\core\ConflictResolver();
            
            foreach ($schedules as $schedule) {
                // 세션 데이터 조회
                $sessions = $DB->get_records('spiral_sessions', 
                    ['schedule_id' => $schedule->id], 
                    'session_date, session_time'
                );
                
                if (empty($sessions)) {
                    continue;
                }
                
                // 충돌 스캔 실행
                $sessionArray = [];
                foreach ($sessions as $session) {
                    $sessionArray[] = [
                        'date' => date('Y-m-d', $session->session_date),
                        'time' => $session->session_time,
                        'duration' => $session->duration_minutes,
                        'type' => $session->session_type,
                        'difficulty' => $session->difficulty_level ?? 3,
                        'unit_id' => $session->unit_id
                    ];
                }
                
                $conflicts = $resolver->scan($sessionArray);
                
                // 기존 제안 삭제 (재계산)
                $DB->delete_records('spiral_conflicts', [
                    'schedule_id' => $schedule->id,
                    'resolution_type' => 'suggestion'
                ]);
                
                // 새로운 충돌 제안 기록
                foreach ($conflicts as $conflict) {
                    $conflictRecord = (object)[
                        'schedule_id' => $schedule->id,
                        'conflict_type' => $conflict['type'],
                        'conflict_data' => json_encode($conflict, JSON_UNESCAPED_UNICODE),
                        'resolution_type' => 'suggestion',
                        'resolved_by' => 0,
                        'timecreated' => time(),
                        'timeresolved' => null
                    ];
                    
                    $DB->insert_record('spiral_conflicts', $conflictRecord);
                    $conflictCount++;
                }
                
                mtrace("Processed schedule {$schedule->id} - found " . count($conflicts) . " conflicts");
            }
            
            mtrace("Total conflicts found: {$conflictCount}");
            
            // 3) KPI 수집 실행
            mtrace('Collecting daily KPIs...');
            $kpiData = \local_spiral\local\kpi_service::collect_daily();
            
            mtrace('KPI Results:');
            mtrace("- 7:3 Ratio Achievement: {$kpiData['ratio']}%");
            mtrace("- Conflict Rate: {$kpiData['conflict']}%");
            mtrace("- Completion Rate: {$kpiData['completion']}%");
            mtrace("- Teacher Modifications: {$kpiData['modcnt']}");
            
            // 4) 임계치 경고 체크
            $this->check_thresholds($kpiData);
            
            mtrace('Spiral plan recomputation completed successfully');
            
        } catch (\Throwable $e) {
            mtrace('Error during plan recomputation: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check KPI thresholds and send alerts if needed
     */
    private function check_thresholds(array $kpiData): void {
        $alerts = [];
        
        // 충돌률 > 5% 경고
        if ($kpiData['conflict'] > 5.0) {
            $alerts[] = "높은 충돌률 감지: {$kpiData['conflict']}% (기준: 5%)";
        }
        
        // 7:3 비율 준수율 < 90% 경고  
        if ($kpiData['ratio'] < 65.0 || $kpiData['ratio'] > 75.0) {
            $alerts[] = "7:3 비율 이탈: {$kpiData['ratio']}% (기준: 65-75%)";
        }
        
        // 완료율 < 80% 경고
        if ($kpiData['completion'] < 80.0) {
            $alerts[] = "낮은 완료율: {$kpiData['completion']}% (기준: 80%)";
        }
        
        // 경고 발송
        if (!empty($alerts)) {
            $this->send_threshold_alerts($alerts, $kpiData);
        }
    }
    
    /**
     * Send threshold alert emails
     */
    private function send_threshold_alerts(array $alerts, array $kpiData): void {
        global $DB;
        
        // 관리자와 담당 교사들에게 알림
        $admins = get_admins();
        $activeTeachers = $DB->get_records_sql("
            SELECT DISTINCT u.*
            FROM {user} u
            JOIN {spiral_schedules} s ON s.teacher_id = u.id
            WHERE s.status IN ('active', 'published')
            AND s.end_date >= ?
            AND u.deleted = 0
        ", [time()]);
        
        $recipients = array_merge($admins, array_values($activeTeachers));
        
        foreach ($recipients as $user) {
            \local_spiral\local\notify::threshold_alert($user, $alerts, $kpiData);
        }
        
        mtrace('Sent threshold alerts to ' . count($recipients) . ' recipients');
    }
}