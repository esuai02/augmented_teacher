<?php
/**
 * 시험 일정 스캔 및 추천 생성 크론 태스크
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examfocus\task;

defined('MOODLE_INTERNAL') || die();

class scan_exams extends \core\task\scheduled_task {
    
    /**
     * 태스크 이름 반환
     */
    public function get_name() {
        return get_string('task_scan_exams', 'local_examfocus');
    }
    
    /**
     * 태스크 실행
     */
    public function execute() {
        global $DB;
        
        mtrace("ExamFocus: Starting exam scan...");
        
        $service = new \local_examfocus\service\exam_focus_service();
        
        // 활성 학생 목록 조회
        $students = $this->get_active_students();
        
        $processed = 0;
        $recommendations = 0;
        
        foreach ($students as $student) {
            $processed++;
            
            // 추천 생성
            $result = $service->get_recommendation_for_user($student->id);
            
            if ($result['has_recommendation']) {
                $recommendations++;
                mtrace("Generated recommendation for user {$student->id}: {$result['mode']}");
            }
        }
        
        mtrace("ExamFocus: Processed {$processed} students, generated {$recommendations} recommendations");
        
        return true;
    }
    
    /**
     * 활성 학생 목록 조회
     */
    private function get_active_students() {
        global $DB;
        
        // 최근 30일 내 활동이 있는 학생
        $thirty_days_ago = time() - (30 * 86400);
        
        return $DB->get_records_sql("
            SELECT DISTINCT u.id, u.firstname, u.lastname
            FROM {user} u
            JOIN {abessi_missionlog} ml ON ml.userid = u.id
            WHERE u.deleted = 0
            AND u.suspended = 0
            AND ml.timecreated > :since
            ORDER BY u.id
        ", ['since' => $thirty_days_ago]);
    }
}