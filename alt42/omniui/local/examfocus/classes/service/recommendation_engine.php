<?php
/**
 * ExamFocus Recommendation Engine
 * 설정 기반 학습 모드 추천 엔진 (하드코딩 제거)
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examfocus\service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/examfocus/classes/service/exam_detector.php');

/**
 * 학습 모드 추천 엔진
 */
class recommendation_engine {
    
    private $exam_detector;
    
    public function __construct() {
        $this->exam_detector = new exam_detector();
    }
    
    /**
     * 사용자를 위한 학습 모드 추천
     * 
     * @param int $userid 사용자 ID
     * @return array 추천 결과
     */
    public function recommend_for_user(int $userid): array {
        // 1. 시험 정보 감지
        $exam_info = $this->exam_detector->detect_upcoming_exams($userid);
        
        if (!$exam_info) {
            return $this->get_no_exam_result();
        }
        
        // 2. 설정값 로드
        $settings = $this->load_settings();
        
        // 3. 사용자 학습 데이터 수집
        $user_stats = $this->get_user_learning_stats($userid);
        
        // 4. 추천 로직 실행
        $recommendation = $this->calculate_recommendation(
            $exam_info, 
            $settings, 
            $user_stats
        );
        
        // 5. 쿨다운 체크
        if ($this->is_in_cooldown($userid, $recommendation['mode'])) {
            return $this->get_cooldown_result();
        }
        
        return $recommendation;
    }
    
    /**
     * 플러그인 설정값 로드
     */
    private function load_settings(): array {
        return [
            'd30_threshold' => get_config('local_examfocus', 'd30_threshold') ?: 30,
            'd7_threshold' => get_config('local_examfocus', 'd7_threshold') ?: 7,
            'message_d30' => get_config('local_examfocus', 'message_d30') ?: 
                '시험까지 D-30! 오답 회독 모드를 시작하세요.',
            'message_d7' => get_config('local_examfocus', 'message_d7') ?: 
                '시험 D-7! 개념요약과 대표유형에 집중하세요.',
            'min_week_hours' => get_config('local_examfocus', 'min_week_hours') ?: 5,
            'min_total_hours' => get_config('local_examfocus', 'min_total_hours') ?: 50,
            'cooldown_hours' => get_config('local_examfocus', 'cooldown_hours') ?: 24,
            'auto_switch' => get_config('local_examfocus', 'auto_switch') ?: 1,
            'mode_d30' => get_config('local_examfocus', 'mode_d30') ?: 'review_errors',
            'mode_d7' => get_config('local_examfocus', 'mode_d7') ?: 'concept_summary',
        ];
    }
    
    /**
     * 사용자 학습 통계 수집
     */
    private function get_user_learning_stats(int $userid): array {
        global $DB;
        
        $stats = [
            'total_time' => 0,
            'week_time' => 0,
            'recent_activity' => 0
        ];
        
        try {
            // 1. 누적 학습 시간 (block_use_stats_totaltime)
            $total_time_record = $DB->get_record('block_use_stats_totaltime', 
                ['userid' => $userid], 'totaltime');
            if ($total_time_record) {
                $stats['total_time'] = $total_time_record->totaltime / 3600; // 초 -> 시간
            }
            
            // 2. 최근 7일 학습 시간 (mdl_abessi_missionlog 기반)
            $week_ago = time() - (7 * 24 * 60 * 60);
            $recent_logs = $DB->get_records_sql("
                SELECT COUNT(*) as sessions, 
                       COUNT(DISTINCT DATE(FROM_UNIXTIME(timecreated))) as days
                FROM {abessi_missionlog} 
                WHERE userid = ? AND timecreated >= ?
            ", [$userid, $week_ago]);
            
            if ($recent_logs) {
                $log = reset($recent_logs);
                $stats['recent_activity'] = $log->sessions ?? 0;
                $stats['active_days'] = $log->days ?? 0;
            }
            
            // 3. 스케줄의 weektotal 활용
            $schedule = $DB->get_record_sql("
                SELECT weektotal FROM {abessi_schedule} 
                WHERE userid = ? AND pinned = 1 
                ORDER BY timemodified DESC LIMIT 1
            ", [$userid]);
            
            if ($schedule && $schedule->weektotal) {
                $stats['week_time'] = $schedule->weektotal;
            }
            
        } catch (\Exception $e) {
            error_log("ExamFocus: Failed to get user stats - " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * 추천 로직 계산
     */
    private function calculate_recommendation(array $exam_info, array $settings, array $user_stats): array {
        $days_until = $exam_info['days_until'];
        $d30_threshold = $settings['d30_threshold'];
        $d7_threshold = $settings['d7_threshold'];
        
        // 학습 시간 조건 체크
        $meets_requirements = $this->check_learning_requirements($user_stats, $settings);
        
        if ($days_until <= $d7_threshold) {
            // D-7: 개념요약/최종점검 모드
            return [
                'has_recommendation' => true,
                'mode' => $settings['mode_d7'],
                'title' => $this->get_mode_title($settings['mode_d7'], $days_until),
                'message' => str_replace('{days}', $days_until, $settings['message_d7']),
                'priority' => 'urgent',
                'exam_info' => $exam_info,
                'actions' => $this->get_d7_actions(),
                'meets_requirements' => $meets_requirements,
                'auto_switch' => $settings['auto_switch']
            ];
        } elseif ($days_until <= $d30_threshold) {
            // D-30: 오답회독/체계학습 모드
            return [
                'has_recommendation' => true,
                'mode' => $settings['mode_d30'],
                'title' => $this->get_mode_title($settings['mode_d30'], $days_until),
                'message' => str_replace('{days}', $days_until, $settings['message_d30']),
                'priority' => 'normal',
                'exam_info' => $exam_info,
                'actions' => $this->get_d30_actions(),
                'meets_requirements' => $meets_requirements,
                'auto_switch' => $settings['auto_switch']
            ];
        } else {
            // 일반 학습 기간
            return [
                'has_recommendation' => false,
                'mode' => 'study',
                'title' => '일반 학습 모드',
                'message' => '시험까지 충분한 시간이 있습니다. 꾸준한 학습을 이어가세요.',
                'priority' => 'info',
                'exam_info' => $exam_info,
                'actions' => $this->get_regular_actions(),
                'meets_requirements' => true,
                'auto_switch' => false
            ];
        }
    }
    
    /**
     * 학습 시간 요구사항 체크
     */
    private function check_learning_requirements(array $user_stats, array $settings): bool {
        $min_week_hours = $settings['min_week_hours'];
        $min_total_hours = $settings['min_total_hours'];
        
        $meets_total = $user_stats['total_time'] >= $min_total_hours;
        $meets_weekly = $user_stats['week_time'] >= $min_week_hours || 
                       $user_stats['recent_activity'] >= 10; // 최근 활동도 고려
        
        return $meets_total && $meets_weekly;
    }
    
    /**
     * 모드별 제목 생성
     */
    private function get_mode_title(string $mode, int $days_until): string {
        $titles = [
            'review_errors' => "🔄 D-{$days_until} 오답 회독 모드",
            'concept_summary' => "🚨 D-{$days_until} 개념요약 집중",
            'practice_problems' => "⚡ D-{$days_until} 실전 연습",
            'key_problems' => "🎯 D-{$days_until} 핵심 문제",
            'final_review' => "📋 D-{$days_until} 최종 점검",
            'exam_day' => "⭐ D-{$days_until} 시험당일",
        ];
        
        return $titles[$mode] ?? "📚 D-{$days_until} 학습 모드";
    }
    
    /**
     * D-7 추천 액션
     */
    private function get_d7_actions(): array {
        return [
            '핵심 개념 최종 정리',
            '대표 유형 문제 풀이',
            '오답 노트 마지막 점검',
            '시험 준비물 확인'
        ];
    }
    
    /**
     * D-30 추천 액션
     */
    private function get_d30_actions(): array {
        return [
            '틀린 문제 체계적 복습',
            '취약 단원 집중 학습',
            '심화 문제 도전',
            '학습 계획 점검'
        ];
    }
    
    /**
     * 일반 학습 액션
     */
    private function get_regular_actions(): array {
        return [
            '규칙적인 진도 학습',
            '개념 이해 중심 학습',
            '기본 문제 연습',
            '학습 습관 유지'
        ];
    }
    
    /**
     * 쿨다운 체크
     */
    private function is_in_cooldown(int $userid, string $mode): bool {
        global $DB;
        
        try {
            $cooldown_hours = get_config('local_examfocus', 'cooldown_hours') ?: 24;
            $cooldown_time = time() - ($cooldown_hours * 3600);
            
            // 최근 알림 기록 체크
            $recent_notification = $DB->get_record_sql("
                SELECT timecreated FROM {abessi_missionlog}
                WHERE userid = ? AND page LIKE 'examfocus_%' 
                AND timecreated > ?
                ORDER BY timecreated DESC LIMIT 1
            ", [$userid, $cooldown_time]);
            
            return !empty($recent_notification);
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 시험 없음 결과
     */
    private function get_no_exam_result(): array {
        return [
            'has_recommendation' => false,
            'mode' => 'study',
            'title' => '📚 일반 학습 모드',
            'message' => '현재 등록된 시험 일정이 없습니다. 시험 일정을 등록하시면 맞춤형 학습 모드를 추천해 드립니다.',
            'priority' => 'info',
            'exam_info' => null,
            'actions' => ['시험 일정 등록하기', '꾸준한 학습 이어가기'],
            'meets_requirements' => true,
            'auto_switch' => false
        ];
    }
    
    /**
     * 쿨다운 결과
     */
    private function get_cooldown_result(): array {
        return [
            'has_recommendation' => false,
            'mode' => null,
            'title' => '알림 대기 중',
            'message' => '최근에 알림을 확인하셨습니다. 잠시 후 다시 확인해 주세요.',
            'priority' => 'info',
            'exam_info' => null,
            'actions' => [],
            'meets_requirements' => true,
            'auto_switch' => false
        ];
    }
    
    /**
     * 추천 수락 로그 기록
     */
    public function log_recommendation_accepted(int $userid, string $mode, array $exam_info = null): bool {
        global $DB;
        
        try {
            $log_data = [
                'userid' => $userid,
                'page' => 'examfocus_accepted_' . $mode,
                'timecreated' => time()
            ];
            
            return $DB->insert_record('abessi_missionlog', $log_data);
            
        } catch (\Exception $e) {
            error_log("ExamFocus: Failed to log acceptance - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 추천 거부 로그 기록
     */
    public function log_recommendation_dismissed(int $userid, string $mode): bool {
        global $DB;
        
        try {
            $log_data = [
                'userid' => $userid,
                'page' => 'examfocus_dismissed_' . $mode,
                'timecreated' => time()
            ];
            
            return $DB->insert_record('abessi_missionlog', $log_data);
            
        } catch (\Exception $e) {
            error_log("ExamFocus: Failed to log dismissal - " . $e->getMessage());
            return false;
        }
    }
}