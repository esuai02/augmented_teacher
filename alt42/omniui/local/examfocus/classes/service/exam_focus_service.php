<?php
/**
 * ExamFocus 핵심 서비스 클래스
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examfocus\service;

defined('MOODLE_INTERNAL') || die();

class exam_focus_service {
    
    /**
     * 사용자에 대한 추천 모드 계산
     * 
     * @param int $userid 사용자 ID
     * @return array 추천 정보
     */
    public function get_recommendation_for_user($userid) {
        global $DB;
        
        // 설정값 로드
        $config = get_config('local_examfocus');
        
        // 사용자 설정 확인
        $user_prefs = $this->get_user_preferences($userid);
        if (!$user_prefs->enabled) {
            return [
                'has_recommendation' => false,
                'reason' => 'disabled_by_user'
            ];
        }
        
        // 쿨다운 체크
        if ($this->is_in_cooldown($userid)) {
            return [
                'has_recommendation' => false,
                'reason' => 'in_cooldown'
            ];
        }
        
        // 시험 일정 조회 (mdl_abessi_schedule)
        $exam_info = $this->find_nearest_exam($userid);
        if (!$exam_info) {
            return [
                'has_recommendation' => false,
                'reason' => 'no_exam_scheduled'
            ];
        }
        
        // D-값 계산
        $days_until_exam = $exam_info['days_until'];
        
        // 학습 통계 조회
        $study_stats = $this->get_study_statistics($userid);
        
        // 추천 규칙 적용
        $recommendation = $this->apply_recommendation_rules(
            $days_until_exam,
            $study_stats,
            $config
        );
        
        if ($recommendation) {
            // 추천 이벤트 기록
            $this->record_recommendation_event($userid, $exam_info, $recommendation);
            
            return [
                'has_recommendation' => true,
                'mode' => $recommendation['mode'],
                'message' => $recommendation['message'],
                'exam_date' => $exam_info['date'],
                'days_until' => $days_until_exam,
                'priority' => $recommendation['priority'],
                'actions' => $recommendation['actions']
            ];
        }
        
        return [
            'has_recommendation' => false,
            'reason' => 'no_matching_rules'
        ];
    }
    
    /**
     * 가장 가까운 시험 일정 찾기
     */
    private function find_nearest_exam($userid) {
        global $DB;
        
        $now = time();
        
        // Alt42t DB에서 시험 정보 조회 (여러 연결 방법 시도)
        try {
            // 연결 방법 1: TCP/IP 연결
            $alt42t_dsn = "mysql:host=127.0.0.1;port=3306;dbname=alt42t;charset=utf8mb4";
            $alt42t_pdo = new \PDO($alt42t_dsn, 'root', '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5
            ]);
            
            // student_exam_settings 테이블에서 조회
            $stmt = $alt42t_pdo->prepare("
                SELECT exam_start_date, exam_end_date, math_exam_date, exam_type
                FROM student_exam_settings
                WHERE user_id = :userid
                AND exam_status = 'confirmed'
                AND math_exam_date >= CURDATE()
                ORDER BY math_exam_date ASC
                LIMIT 1
            ");
            $stmt->execute(['userid' => $userid]);
            $exam = $stmt->fetch();
            
            if ($exam && $exam['math_exam_date']) {
                $exam_timestamp = strtotime($exam['math_exam_date']);
                $days_until = floor(($exam_timestamp - $now) / 86400);
                
                return [
                    'date' => $exam['math_exam_date'],
                    'timestamp' => $exam_timestamp,
                    'days_until' => $days_until,
                    'type' => $exam['exam_type']
                ];
            }
        } catch (\PDOException $e) {
            // 연결 실패 시 대체 방법 시도
            try {
                // 연결 방법 2: Unix 소켓 명시
                $alt42t_dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=alt42t;charset=utf8mb4";
                $alt42t_pdo = new \PDO($alt42t_dsn, 'root', '', [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]);
                
                // 재시도로 성공하면 쿼리 실행
                $stmt = $alt42t_pdo->prepare("
                    SELECT exam_start_date, exam_end_date, math_exam_date, exam_type
                    FROM student_exam_settings
                    WHERE user_id = :userid
                    AND exam_status = 'confirmed'
                    AND math_exam_date >= CURDATE()
                    ORDER BY math_exam_date ASC
                    LIMIT 1
                ");
                $stmt->execute(['userid' => $userid]);
                $exam = $stmt->fetch();
                
                if ($exam && $exam['math_exam_date']) {
                    $exam_timestamp = strtotime($exam['math_exam_date']);
                    $days_until = floor(($exam_timestamp - $now) / 86400);
                    
                    return [
                        'date' => $exam['math_exam_date'],
                        'timestamp' => $exam_timestamp,
                        'days_until' => $days_until,
                        'type' => $exam['exam_type']
                    ];
                }
            } catch (\PDOException $e2) {
                error_log("ExamFocus: Alt42t DB connection failed - " . $e->getMessage());
                error_log("ExamFocus: Alt42t DB retry also failed - " . $e2->getMessage());
            }
        }
        
        // mdl_abessi_schedule에서도 조회 (fallback)
        $schedule = $DB->get_record_sql("
            SELECT schedule_data
            FROM {abessi_schedule}
            WHERE userid = :userid
            AND pinned = 1
            ORDER BY timemodified DESC
            LIMIT 1
        ", ['userid' => $userid]);
        
        if ($schedule && $schedule->schedule_data) {
            $data = json_decode($schedule->schedule_data, true);
            
            // schedule_data에서 시험 날짜 추출 (JSON 구조에 따라 조정 필요)
            if (isset($data['exam_date'])) {
                $exam_timestamp = strtotime($data['exam_date']);
                if ($exam_timestamp > $now) {
                    $days_until = floor(($exam_timestamp - $now) / 86400);
                    
                    return [
                        'date' => $data['exam_date'],
                        'timestamp' => $exam_timestamp,
                        'days_until' => $days_until,
                        'type' => $data['exam_type'] ?? 'general'
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * 학습 통계 조회
     */
    private function get_study_statistics($userid) {
        global $DB;
        
        $stats = [
            'total_hours' => 0,
            'week_hours' => 0,
            'recent_activity' => 0,
            'error_review_count' => 0
        ];
        
        // 누적 학습 시간 (block_use_stats_totaltime이 있다면)
        if ($DB->get_manager()->table_exists('block_use_stats_totaltime')) {
            $totaltime = $DB->get_field('block_use_stats_totaltime', 'totaltime', 
                ['userid' => $userid]);
            if ($totaltime) {
                $stats['total_hours'] = round($totaltime / 3600, 1);
            }
        }
        
        // 최근 1주일 학습 시간 (mdl_abessi_missionlog 기반)
        $week_ago = time() - (7 * 86400);
        $mission_count = $DB->count_records_select('abessi_missionlog',
            'userid = :userid AND timecreated > :weekago',
            ['userid' => $userid, 'weekago' => $week_ago]
        );
        
        // 대략적인 주간 학습 시간 계산 (미션당 평균 30분 가정)
        $stats['week_hours'] = round($mission_count * 0.5, 1);
        
        // 최근 활동 시간
        $last_activity = $DB->get_field_sql("
            SELECT MAX(timecreated)
            FROM {abessi_missionlog}
            WHERE userid = :userid
        ", ['userid' => $userid]);
        
        $stats['recent_activity'] = $last_activity ?: 0;
        
        return $stats;
    }
    
    /**
     * 추천 규칙 적용
     */
    private function apply_recommendation_rules($days_until, $study_stats, $config) {
        $d30 = (int)$config->d30_threshold;
        $d7 = (int)$config->d7_threshold;
        $min_week = (int)$config->min_week_hours;
        $min_total = (int)$config->min_total_hours;
        
        // D-7 체크 (우선순위 높음)
        if ($days_until <= $d7 && $days_until > 0) {
            return [
                'mode' => $config->mode_d7,
                'message' => $config->message_d7,
                'priority' => 'high',
                'actions' => [
                    'switch_mode' => $config->mode_d7,
                    'show_banner' => true,
                    'emphasis' => 'urgent'
                ]
            ];
        }
        
        // D-30 체크
        if ($days_until <= $d30 && $days_until > $d7) {
            // 학습량 조건 체크
            if ($study_stats['week_hours'] >= $min_week || 
                $study_stats['total_hours'] >= $min_total) {
                return [
                    'mode' => $config->mode_d30,
                    'message' => $config->message_d30,
                    'priority' => 'medium',
                    'actions' => [
                        'switch_mode' => $config->mode_d30,
                        'show_banner' => true,
                        'emphasis' => 'important'
                    ]
                ];
            }
        }
        
        return null;
    }
    
    /**
     * 쿨다운 체크
     */
    private function is_in_cooldown($userid) {
        global $DB;
        
        $cooldown_hours = (int)get_config('local_examfocus', 'cooldown_hours');
        $cooldown_seconds = $cooldown_hours * 3600;
        
        $last_event = $DB->get_record_sql("
            SELECT MAX(timecreated) as lasttime
            FROM {local_examfocus_events}
            WHERE userid = :userid
            AND accepted = 0
        ", ['userid' => $userid]);
        
        if ($last_event && $last_event->lasttime) {
            if ((time() - $last_event->lasttime) < $cooldown_seconds) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 추천 이벤트 기록
     */
    private function record_recommendation_event($userid, $exam_info, $recommendation) {
        global $DB;
        
        $event = new \stdClass();
        $event->userid = $userid;
        $event->examdate = $exam_info['timestamp'];
        $event->examtype = $exam_info['type'];
        $event->dvalue = $exam_info['days_until'];
        $event->suggestedmode = $recommendation['mode'];
        $event->reason = json_encode([
            'message' => $recommendation['message'],
            'priority' => $recommendation['priority']
        ]);
        $event->shownat = time();
        $event->accepted = 0;
        $event->timecreated = time();
        
        return $DB->insert_record('local_examfocus_events', $event);
    }
    
    /**
     * 사용자 설정 조회
     */
    private function get_user_preferences($userid) {
        global $DB;
        
        $prefs = $DB->get_record('local_examfocus_user_prefs', ['userid' => $userid]);
        
        if (!$prefs) {
            // 기본값으로 새 레코드 생성
            $prefs = new \stdClass();
            $prefs->userid = $userid;
            $prefs->enabled = 1;
            $prefs->notify_d30 = 1;
            $prefs->notify_d7 = 1;
            $prefs->auto_mode_switch = 0;
            $prefs->timecreated = time();
            $prefs->timemodified = time();
            
            $prefs->id = $DB->insert_record('local_examfocus_user_prefs', $prefs);
        }
        
        return $prefs;
    }
    
    /**
     * 추천 수락 처리
     */
    public function accept_recommendation($userid, $mode) {
        global $DB;
        
        // 가장 최근 추천 이벤트 업데이트
        $event = $DB->get_record_sql("
            SELECT *
            FROM {local_examfocus_events}
            WHERE userid = :userid
            AND accepted = 0
            ORDER BY timecreated DESC
            LIMIT 1
        ", ['userid' => $userid]);
        
        if ($event) {
            $event->accepted = 1;
            $event->acceptedat = time();
            $DB->update_record('local_examfocus_events', $event);
            
            // mdl_abessi_missionlog에 기록
            $log = new \stdClass();
            $log->userid = $userid;
            $log->page = 'examfocus_accepted';
            $log->timecreated = time();
            $DB->insert_record('abessi_missionlog', $log);
            
            return true;
        }
        
        return false;
    }
}