<?php declare(strict_types=1);
/**
 * Secure Bridge between Moodle plugin and OmniUI core
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

use omniui\spiral\core\TimeAllocator;
use omniui\spiral\core\RatioCalculator;
use omniui\spiral\core\ConflictResolver;

class scheduler_bridge {
    
    private $db;
    private $rules;
    private $cfg;
    
    public function __construct($db, array $rules, array $cfg) {
        $this->db = $db;
        $this->rules = $rules;
        $this->cfg = $cfg;
    }
    
    /**
     * Generate spiral schedule with transaction support
     */
    public function generate(array $params): array {
        global $USER;
        
        // 권한 체크는 외부에서 수행됨
        
        // 입력 검증
        $this->validateParams($params);
        
        // 교사 ID는 항상 현재 사용자
        $params['teacherid'] = $USER->id;
        
        // 트랜잭션 시작
        $transaction = $this->db->start_delegated_transaction();
        
        try {
            // 스케줄 마스터 레코드 생성
            $schedule = new \stdClass();
            $schedule->teacher_id = $params['teacherid'];
            $schedule->student_id = $params['studentid'];
            $schedule->exam_id = $params['exam_id'] ?? null;
            $schedule->schedule_type = 'auto';
            $schedule->status = 'draft';
            $schedule->ratio_preview = $params['alpha'] ?? 0.7;
            $schedule->ratio_review = $params['beta'] ?? 0.3;
            $schedule->start_date = strtotime($params['start_date']);
            $schedule->end_date = strtotime($params['end_date']);
            $schedule->schedule_data = json_encode([]);
            $schedule->timecreated = time();
            $schedule->timemodified = time();
            
            $scheduleId = $this->db->insert_record('spiral_schedules', $schedule);
            
            // 시간 할당 계산
            $days = $this->generateDaysList(
                $params['start_date'],
                $params['end_date'],
                $params['hours_per_week'] ?? 14
            );
            
            $timeAllocator = new TimeAllocator();
            $timeAllocation = $timeAllocator->allocate(
                $days,
                count($days) * 120, // 일당 120분 기본
                ['min' => 20, 'max' => 50, 'break' => 10],
                ['math' => 0.4, 'korean' => 0.3, 'english' => 0.3]
            );
            
            // 7:3 비율 적용
            $ratioCalculator = new RatioCalculator();
            $candidates = $this->generateCandidates($timeAllocation);
            $splitResult = $ratioCalculator->split(
                $candidates,
                $params['alpha'] ?? 0.7,
                $params['beta'] ?? 0.3
            );
            
            // 세션 레코드 벌크 인서트
            $sessions = [];
            $sessionCount = 0;
            
            foreach ($timeAllocation as $dayData) {
                foreach ($dayData['slots'] as $slot) {
                    $session = new \stdClass();
                    $session->schedule_id = $scheduleId;
                    $session->session_date = strtotime($dayData['date']);
                    $session->session_time = '19:00:00'; // 기본 시간
                    $session->duration_minutes = $slot['duration'];
                    $session->session_type = ($sessionCount % 3 < 2) ? 'preview' : 'review';
                    $session->unit_id = 'unit_' . ($sessionCount + 1);
                    $session->unit_name = ($slot['subject'] ?? 'math') . '_unit_' . ($sessionCount + 1);
                    $session->difficulty_level = 3;
                    $session->completion_status = 'pending';
                    $session->timecreated = time();
                    $session->timemodified = time();
                    
                    $sessions[] = $session;
                    $sessionCount++;
                }
            }
            
            // 벌크 인서트
            if (!empty($sessions)) {
                $this->db->insert_records('spiral_sessions', $sessions);
            }
            
            // 충돌 검사
            $conflictResolver = new ConflictResolver();
            $conflicts = $conflictResolver->scan($this->sessionsToArray($sessions));
            
            // 충돌 기록
            foreach ($conflicts as $conflict) {
                $conflictRecord = new \stdClass();
                $conflictRecord->schedule_id = $scheduleId;
                $conflictRecord->conflict_type = $conflict['type'];
                $conflictRecord->conflict_data = json_encode($conflict);
                $conflictRecord->timecreated = time();
                
                $this->db->insert_record('spiral_conflicts', $conflictRecord);
            }
            
            // 스케줄 데이터 업데이트
            $schedule->id = $scheduleId;
            $schedule->total_hours = $sessionCount * 40 / 60; // 평균 40분
            $schedule->schedule_data = json_encode([
                 'sessions_count' => $sessionCount,
                 'conflicts_count' => count($conflicts)
            ]);
            $schedule->timemodified = time();
            $this->db->update_record('spiral_schedules', $schedule);
            
            // 트랜잭션 커밋
            $transaction->allow_commit();
            
            return [
                'schedule_id' => $scheduleId,
                'summary' => [
                    'total_sessions' => $sessionCount,
                    'total_hours' => round($sessionCount * 40 / 60, 1),
                    'preview_ratio' => $splitResult['ratio']['achieved'][0] ?? 0.7,
                    'review_ratio' => $splitResult['ratio']['achieved'][1] ?? 0.3
                ],
                'conflicts' => $conflicts
            ];
            
        } catch (\Throwable $e) {
            // 트랜잭션 롤백
            $transaction->rollback($e);
            
            // 사용자 친화적 에러 메시지
            throw new \moodle_exception(
                'schedule_generation_failed',
                'local_spiral',
                '',
                null,
                'Schedule generation failed. Please try again.'
            );
        }
    }
    
    /**
     * Validate input parameters
     */
    private function validateParams(array $params): void {
        if (empty($params['studentid'])) {
            throw new \moodle_exception('missing_studentid', 'local_spiral');
        }
        
        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw new \moodle_exception('missing_dates', 'local_spiral');
        }
        
        $start = strtotime($params['start_date']);
        $end = strtotime($params['end_date']);
        
        if ($start === false || $end === false || $start >= $end) {
            throw new \moodle_exception('invalid_date_range', 'local_spiral');
        }
    }
    
    /**
     * Generate days list for time allocation
     */
    private function generateDaysList(string $start, string $end, int $hoursPerWeek): array {
        $days = [];
        $current = strtotime($start);
        $endTime = strtotime($end);
        $dailyMinutes = ($hoursPerWeek * 60) / 7;
        
        while ($current <= $endTime) {
            $days[] = [
                'date' => date('Y-m-d', $current),
                'limit' => $dailyMinutes,
                'weight' => (date('w', $current) == 0 || date('w', $current) == 6) ? 1.2 : 1.0
            ];
            $current += 86400; // +1 day
        }
        
        return $days;
    }
    
    /**
     * Generate candidates for ratio calculator
     */
    private function generateCandidates(array $timeAllocation): array {
        $candidates = [];
        
        foreach ($timeAllocation as $day) {
            foreach ($day['slots'] as $slot) {
                $candidates[] = [
                    'type' => null, // Will be auto-assigned
                    'weight' => $slot['duration'] / 30, // Normalize to 30-minute units
                    'unit_id' => uniqid('unit_'),
                    'subject' => $slot['subject'] ?? 'math'
                ];
            }
        }
        
        return $candidates;
    }
    
    /**
     * Convert session objects to array for conflict resolver
     */
    private function sessionsToArray(array $sessions): array {
        $result = [];
        
        foreach ($sessions as $session) {
            $result[] = [
                'date' => date('Y-m-d', $session->session_date),
                'time' => $session->session_time,
                'duration' => $session->duration_minutes,
                'type' => $session->session_type,
                'difficulty' => $session->difficulty_level,
                'unit_id' => $session->unit_id
            ];
        }
        
        return $result;
    }
}