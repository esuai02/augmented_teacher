<?php
/**
 * ExamFocus Exam Detection Service
 * 실제 DB 데이터를 사용한 시험일 자동 감지
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examfocus\service;

defined('MOODLE_INTERNAL') || die();

/**
 * 시험 일정 감지 및 분석 서비스
 */
class exam_detector {
    
    /**
     * 사용자의 시험 일정을 감지하고 D-value 계산
     * 
     * @param int $userid 사용자 ID
     * @return array 시험 정보 배열
     */
    public function detect_upcoming_exams(int $userid): array {
        global $DB;
        
        $exams = [];
        $today = time();
        
        // 1. Alt42t DB에서 시험 정보 조회 (우선순위)
        $alt42t_exams = $this->get_alt42t_exams($userid);
        if (!empty($alt42t_exams)) {
            $exams = array_merge($exams, $alt42t_exams);
        }
        
        // 2. mdl_abessi_schedule에서 시험 정보 조회 (폴백)
        $schedule_exams = $this->get_schedule_exams($userid);
        if (!empty($schedule_exams)) {
            $exams = array_merge($exams, $schedule_exams);
        }
        
        // 3. 중복 제거 및 D-value 계산
        $processed_exams = $this->process_exams($exams, $today);
        
        // 4. 가장 가까운 시험 반환
        return $this->find_nearest_exam($processed_exams);
    }
    
    /**
     * Alt42t DB에서 시험 정보 조회
     */
    private function get_alt42t_exams(int $userid): array {
        $exams = [];
        
        try {
            // Alt42t DB 연결
            $dsn = "mysql:host=localhost;dbname=alt42t;charset=utf8mb4";
            $pdo = new \PDO($dsn, 'root', '');
            
            $stmt = $pdo->prepare("
                SELECT exam_type, math_exam_date, exam_start_date, exam_end_date, 
                       exam_scope, school, grade, updated_at
                FROM student_exam_settings 
                WHERE user_id = ? AND exam_status = 'confirmed'
                AND math_exam_date >= CURDATE()
                ORDER BY math_exam_date ASC
                LIMIT 5
            ");
            
            $stmt->execute([$userid]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $exams[] = [
                    'source' => 'alt42t',
                    'type' => $row['exam_type'],
                    'exam_date' => strtotime($row['math_exam_date']),
                    'scope' => $row['exam_scope'] ?? '',
                    'school' => $row['school'] ?? '',
                    'grade' => $row['grade'] ?? '',
                    'updated' => strtotime($row['updated_at'])
                ];
            }
            
        } catch (\Exception $e) {
            // Alt42t DB 연결 실패 시 로그만 기록하고 계속 진행
            error_log("ExamFocus: Alt42t DB connection failed - " . $e->getMessage());
        }
        
        return $exams;
    }
    
    /**
     * mdl_abessi_schedule에서 시험 정보 조회
     */
    private function get_schedule_exams(int $userid): array {
        global $DB;
        
        $exams = [];
        
        try {
            // pinned = 1인 스케줄 조회
            $schedules = $DB->get_records_sql("
                SELECT * FROM {abessi_schedule} 
                WHERE userid = ? AND pinned = 1 
                ORDER BY timemodified DESC 
                LIMIT 5
            ", [$userid]);
            
            foreach ($schedules as $schedule) {
                // memo8, memo9에서 시험 정보 파싱
                $exam_info = $this->parse_schedule_memos($schedule);
                if ($exam_info) {
                    $exams[] = [
                        'source' => 'schedule',
                        'type' => $schedule->type ?? '정기시험',
                        'exam_date' => $exam_info['exam_date'],
                        'scope' => $exam_info['scope'] ?? '',
                        'school' => '',
                        'grade' => '',
                        'updated' => $schedule->timemodified ?? time()
                    ];
                }
                
                // schedule_data JSON 파싱
                if (!empty($schedule->schedule_data)) {
                    $json_exams = $this->parse_schedule_json($schedule->schedule_data);
                    $exams = array_merge($exams, $json_exams);
                }
            }
            
        } catch (\Exception $e) {
            error_log("ExamFocus: Schedule DB query failed - " . $e->getMessage());
        }
        
        return $exams;
    }
    
    /**
     * memo8, memo9에서 시험 정보 파싱
     */
    private function parse_schedule_memos($schedule): ?array {
        $exam_date = null;
        $scope = '';
        
        // memo8에서 날짜 패턴 찾기
        $memo8 = $schedule->memo8 ?? '';
        $memo9 = $schedule->memo9 ?? '';
        
        // 날짜 패턴 검색 (YYYY-MM-DD, MM/DD, MM월 DD일 등)
        $date_patterns = [
            '/(\d{4})-(\d{1,2})-(\d{1,2})/', // YYYY-MM-DD
            '/(\d{1,2})\/(\d{1,2})/',        // MM/DD
            '/(\d{1,2})월\s*(\d{1,2})일/',    // MM월 DD일
        ];
        
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $memo8 . ' ' . $memo9, $matches)) {
                if (count($matches) >= 3) {
                    $year = $matches[1] ?? date('Y');
                    $month = $matches[2];
                    $day = $matches[3];
                    
                    if (strlen($year) == 2) $year = '20' . $year;
                    
                    $exam_date = mktime(0, 0, 0, $month, $day, $year);
                    break;
                }
            }
        }
        
        // 시험 관련 키워드가 있는지 확인
        $exam_keywords = ['시험', '고사', '평가', '중간', '기말', '모의'];
        $has_exam_keyword = false;
        
        foreach ($exam_keywords as $keyword) {
            if (strpos($memo8 . $memo9, $keyword) !== false) {
                $has_exam_keyword = true;
                break;
            }
        }
        
        if ($exam_date && $has_exam_keyword) {
            return [
                'exam_date' => $exam_date,
                'scope' => trim($memo8 . ' ' . $memo9)
            ];
        }
        
        return null;
    }
    
    /**
     * schedule_data JSON에서 시험 정보 파싱
     */
    private function parse_schedule_json(string $json_data): array {
        $exams = [];
        
        try {
            $data = json_decode($json_data, true);
            if (!is_array($data)) return $exams;
            
            // 시험 관련 이벤트 찾기
            foreach ($data as $item) {
                if (is_array($item) && isset($item['date'], $item['title'])) {
                    $title = strtolower($item['title']);
                    
                    // 시험 키워드 포함 여부 확인
                    if (strpos($title, '시험') !== false || 
                        strpos($title, '고사') !== false ||
                        strpos($title, '평가') !== false) {
                        
                        $exam_date = strtotime($item['date']);
                        if ($exam_date && $exam_date > time()) {
                            $exams[] = [
                                'source' => 'schedule_json',
                                'type' => $item['title'],
                                'exam_date' => $exam_date,
                                'scope' => $item['description'] ?? '',
                                'school' => '',
                                'grade' => '',
                                'updated' => time()
                            ];
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            error_log("ExamFocus: JSON parsing failed - " . $e->getMessage());
        }
        
        return $exams;
    }
    
    /**
     * 시험 목록 처리 및 D-value 계산
     */
    private function process_exams(array $exams, int $today): array {
        $processed = [];
        
        foreach ($exams as $exam) {
            $exam_date = $exam['exam_date'];
            $days_until = ceil(($exam_date - $today) / (24 * 60 * 60));
            
            // 과거 시험이나 너무 먼 미래 시험 제외
            if ($days_until < 0 || $days_until > 365) {
                continue;
            }
            
            $exam['days_until'] = $days_until;
            $exam['d_value'] = $days_until;
            $processed[] = $exam;
        }
        
        // 날짜순 정렬
        usort($processed, function($a, $b) {
            return $a['exam_date'] <=> $b['exam_date'];
        });
        
        return $processed;
    }
    
    /**
     * 가장 가까운 시험 찾기
     */
    private function find_nearest_exam(array $exams): ?array {
        if (empty($exams)) {
            return null;
        }
        
        return $exams[0]; // 이미 날짜순으로 정렬됨
    }
    
    /**
     * 사용자 timezone 고려한 현재 시간 반환
     */
    private function get_user_time(int $userid): int {
        global $DB;
        
        try {
            $user = $DB->get_record('user', ['id' => $userid], 'timezone');
            if ($user && !empty($user->timezone)) {
                $timezone = new \DateTimeZone($user->timezone);
                $datetime = new \DateTime('now', $timezone);
                return $datetime->getTimestamp();
            }
        } catch (\Exception $e) {
            // timezone 처리 실패 시 서버 시간 사용
        }
        
        return time();
    }
}