<?php
/**
 * 교사용 출결관리 시스템
 * 
 * 목적: 교사가 담당 학생들의 출결(휴강/보강)을 관리하고, 학습 시간을 추적하여 보강 필요 여부를 판단
 * 
 * 주요 기능:
 * - 출결 기록 관리 (휴강/보강 추가/삭제)
 * - 학습 시간 추적 및 초과 학습 시간 계산
 * - 보강 필요 여부 자동 계산
 * - 월간 캘린더 뷰
 * - 알림 시스템 (보강 필요, 초과 학습 학생)
 * 
 * @file attendance_teacher.php
 * @version 2.0
 */

// 시작 시간 측정
$start_time = microtime(true);

// Moodle 환경 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 시간대 설정
date_default_timezone_set('Asia/Seoul');

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ==================== 유틸리티 클래스 ====================

/**
 * 쿼리 결과 캐싱 클래스
 */
class QueryCache {
    private static $cache = [];
    private static $ttl = 300; // 5분
    
    public static function get($key) {
        if (isset(self::$cache[$key]) && self::$cache[$key]['expires'] > time()) {
            return self::$cache[$key]['data'];
        }
        return null;
    }
    
    public static function set($key, $data) {
        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + self::$ttl
        ];
        return $data;
    }
    
    public static function clear() {
        self::$cache = [];
    }
}

/**
 * 에러 로깅 헬퍼 함수
 */
function logError($message, $file, $line) {
    $errorMsg = "[{$file}:{$line}] {$message}";
    error_log($errorMsg);
    return $errorMsg;
}

// ==================== 권한 확인 ====================

/**
 * 교사 권한 확인
 * @return bool 교사 여부
 */
function checkTeacherPermission($DB, $USER) {
    // lastname에 'T'가 포함된 경우
    if (strpos($USER->lastname, 'T') !== false || 
        $USER->lastname === 'T' || 
        trim($USER->lastname) === 'T') {
        return true;
    }
    
    // user_info_data에서 역할 확인
    try {
        $userrole = $DB->get_record_sql(
            "SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = ?",
            array($USER->id, 22)
        );
        $role = $userrole ? $userrole->role : 'student';
        return ($role !== 'student');
    } catch (Exception $e) {
        logError("권한 확인 중 오류: " . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

// 교사 권한 확인
if (!checkTeacherPermission($DB, $USER)) {
    die("<h2>접근 권한이 없습니다. 교사 계정으로 로그인해주세요.</h2>");
}

/**
 * 교사 심볼 가져오기
 * @return string 교사 심볼 (이모티콘)
 */
function getTeacherSymbol($DB, $USER) {
    try {
        $teacherInfo = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_teacher WHERE userid = ? ORDER BY id DESC LIMIT 1",
            array($USER->id)
        );
        
        if ($teacherInfo && isset($teacherInfo->symbol)) {
            return $teacherInfo->symbol;
        }
        
        // 기본 심볼 할당 (교사 ID 기반)
        $symbols = array('🌟', '⭐', '✨', '🎯', '🔥', '💫', '🌈', '🎨');
        $symbolIndex = $USER->id % count($symbols);
        return $symbols[$symbolIndex];
    } catch (Exception $e) {
        logError("교사 심볼 조회 오류: " . $e->getMessage(), __FILE__, __LINE__);
        return '👨‍🏫';
    }
}

$teacherSymbol = getTeacherSymbol($DB, $USER);

// ==================== 출결 시간 계산 함수 ====================

/**
 * 출결 시간 계산 (최근 3주 기준)
 * @param object $DB Moodle DB 객체
 * @param int $studentid 학생 ID
 * @param int $threeWeeksAgo 3주 전 타임스탬프
 * @param bool $skipExtraStudy 초과 학습 시간 계산 건너뛰기
 * @return array 출결 데이터
 */
function calculateAttendanceHours($DB, $studentid, $threeWeeksAgo, $skipExtraStudy = false) {
    // 캐시 확인
    $cacheKey = "attendance_{$studentid}_{$threeWeeksAgo}_" . ($skipExtraStudy ? '1' : '0');
    $cached = QueryCache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    try {
        // 단일 쿼리로 결석과 보강 시간 동시 계산
        $currentTime = time();
        $sql = "SELECT 
                    event,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN due < ? THEN amount ELSE 0 END) as past_amount,
                    SUM(CASE WHEN due >= ? THEN amount ELSE 0 END) as future_amount
                FROM {abessi_classtimemanagement} 
                WHERE userid = ? AND hide = 0 AND due >= ?
                GROUP BY event";
        
        $records = $DB->get_records_sql($sql, array($currentTime, $currentTime, $studentid, $threeWeeksAgo));
        
        $totalAbsence = 0;
        $pastMakeup = 0;
        $futureMakeup = 0;
        
        if ($records) {
            foreach ($records as $record) {
                if ($record->event === 'absence') {
                    $totalAbsence = floatval($record->total_amount);
                } elseif ($record->event === 'makeup') {
                    $pastMakeup = floatval($record->past_amount);
                    $futureMakeup = floatval($record->future_amount);
                }
            }
        }
        
        // 초과 학습 시간 계산
        $extraStudyHours = 0;
        if (!$skipExtraStudy) {
            $extraStudyHours = calculateExtraStudyHours($DB, $studentid, $threeWeeksAgo);
        }
        
        // 보강 필요 시간 계산
        $neededMakeup = $totalAbsence - ($pastMakeup + $futureMakeup + $extraStudyHours);
        
        $result = array(
            'totalAbsence' => $totalAbsence,
            'pastMakeup' => $pastMakeup,
            'futureMakeup' => $futureMakeup,
            'extraStudyHours' => round($extraStudyHours, 1),
            'neededMakeup' => round($neededMakeup, 1)
        );
        
        // 캐시 저장
        QueryCache::set($cacheKey, $result);
        
        return $result;
    } catch (Exception $e) {
        logError("출결 시간 계산 오류: " . $e->getMessage(), __FILE__, __LINE__);
        return array(
            'totalAbsence' => 0,
            'pastMakeup' => 0,
            'futureMakeup' => 0,
            'extraStudyHours' => 0,
            'neededMakeup' => 0
        );
    }
}

/**
 * 초과 학습 시간 계산
 * @param object $DB Moodle DB 객체
 * @param int $studentid 학생 ID
 * @param int $threeWeeksAgo 3주 전 타임스탬프
 * @return float 초과 학습 시간
 */
function calculateExtraStudyHours($DB, $studentid, $threeWeeksAgo) {
    try {
        // 시간표 정보 가져오기
        $schedule = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_schedule WHERE userid = ? AND pinned = 1 ORDER BY id DESC LIMIT 1",
            array($studentid)
        );
        
        if (!$schedule) {
            return 0;
        }
        
        // 최근 3주간의 학습 기록 분석
        $startDate = date('Y-m-d', $threeWeeksAgo);
        $endDate = date('Y-m-d');
        
        $sqlStudy = "SELECT 
                        DATE(FROM_UNIXTIME(timecreated)) as study_date,
                        MIN(timecreated) as first_time,
                        MAX(timecreated) as last_time,
                        COUNT(*) as log_count
                     FROM mdl_abessi_missionlog 
                     WHERE userid = ? 
                     AND DATE(FROM_UNIXTIME(timecreated)) BETWEEN ? AND ?
                     GROUP BY DATE(FROM_UNIXTIME(timecreated))";
        
        $studyRecords = $DB->get_records_sql($sqlStudy, array($studentid, $startDate, $endDate));
        
        $extraStudyHours = 0;
        
        if ($studyRecords) {
            foreach ($studyRecords as $record) {
                // 해당 날짜의 요일 확인
                $dayOfWeek = date('w', strtotime($record->study_date));
                
                // 해당 요일의 정규 수업 시간 확인
                if ($dayOfWeek == 0) {
                    $duration_field = 'duration7'; // 일요일
                } else {
                    $duration_field = 'duration' . $dayOfWeek;
                }
                
                $regularHours = isset($schedule->$duration_field) ? floatval($schedule->$duration_field) : 0;
                
                // 실제 공부 시간 계산
                $actualStudyHours = 0;
                if ($record->log_count > 1) {
                    $actualStudyHours = ($record->last_time - $record->first_time) / 3600;
                }
                
                // 정규 수업 시간보다 1시간 이상 더 공부한 경우만 초과 시간으로 인정
                if ($regularHours > 0 && $actualStudyHours > ($regularHours + 1)) {
                    $extraHoursForDay = $actualStudyHours - $regularHours;
                    $extraStudyHours += $extraHoursForDay;
                }
            }
        }
        
        return round($extraStudyHours, 1);
    } catch (Exception $e) {
        logError("초과 학습 시간 계산 오류: " . $e->getMessage(), __FILE__, __LINE__);
        return 0;
    }
}

// ==================== POST 요청 처리 ====================

$threeWeeksAgo = strtotime("-3 weeks");
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : (isset($_POST['userid']) ? intval($_POST['userid']) : 0);
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $studentid > 0) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // 휴강/보강 추가
    if ($action === 'absence' || $action === 'makeup') {
        $selectedDate = isset($_POST['selectedDate']) ? trim($_POST['selectedDate']) : '';
        $selectedHours = isset($_POST['selectedHours']) ? floatval($_POST['selectedHours']) : 0;
        
        if (!empty($selectedDate) && $selectedHours > 0) {
            $selectedTimestamp = strtotime($selectedDate);
            $selectedMonth = date('Y-m', $selectedTimestamp);
            
            try {
                $record = new stdClass();
                $record->userid = $studentid;
                $record->event = $action;
                $record->hide = 0;
                $record->amount = $selectedHours;
                $record->text = '';
                $record->due = $selectedTimestamp;
                $record->timecreated = time();
                $record->status = 'done';
                $record->role = 'teacher';
                
                if ($DB->insert_record('abessi_classtimemanagement', $record)) {
                    // 캐시 초기화
                    QueryCache::clear();
                    $_SESSION['success'] = ($action === 'absence' ? "휴강" : "보강") . " 기록이 추가되었습니다.";
                    $viewMonth = $selectedMonth;
                } else {
                    $_SESSION['error'] = logError("기록 추가 실패", __FILE__, __LINE__);
                }
            } catch (Exception $e) {
                $_SESSION['error'] = logError("기록 추가 중 오류: " . $e->getMessage(), __FILE__, __LINE__);
            }
        }
        
        $redirectUrl = "?userid=" . urlencode($studentid) . "&search=" . urlencode($searchQuery) . "&month=" . urlencode($viewMonth);
        header("Location: " . $redirectUrl);
        exit;
    }
    
    // 기록 삭제
    if ($action === 'delete') {
        $recordId = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
        
        if ($recordId > 0) {
            try {
                $record = $DB->get_record('abessi_classtimemanagement', array('id' => $recordId, 'userid' => $studentid));
                
                if ($record) {
                    $record->hide = 1;
                    if ($DB->update_record('abessi_classtimemanagement', $record)) {
                        QueryCache::clear();
                        $_SESSION['success'] = "출결 기록이 삭제되었습니다.";
                    } else {
                        $_SESSION['error'] = logError("삭제 실패", __FILE__, __LINE__);
                    }
                } else {
                    $_SESSION['error'] = "해당 기록을 찾을 수 없습니다.";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = logError("삭제 중 오류: " . $e->getMessage(), __FILE__, __LINE__);
            }
        }
        
        $redirectUrl = "?userid=" . urlencode($studentid) . "&search=" . urlencode($searchQuery) . "&month=" . urlencode($viewMonth);
        header("Location: " . $redirectUrl);
        exit;
    }
}

// 세션 메시지 읽기
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
unset($_SESSION['error']);
unset($_SESSION['success']);

// POST 처리 후 URL 파라미터 다시 읽기
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// ==================== AJAX 요청 처리 ====================

if (isset($_GET['ajax'])) {
    $ajaxType = $_GET['ajax'];
    
    // 알림 데이터 로드
    if ($ajaxType === 'alerts') {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $alertsData = array();
            $alertParams = array($threeWeeksAgo, $threeWeeksAgo);
            
            // 보강 필요 또는 추가 학습 시간이 있는 학생 찾기
            $sqlAlerts = "SELECT 
                        u.id,
                        u.firstname,
                        u.lastname,
                        COALESCE(absence.total, 0) as total_absence,
                        COALESCE(makeup.total, 0) as total_makeup,
                        (COALESCE(absence.total, 0) - COALESCE(makeup.total, 0)) as needed
                      FROM mdl_user u
                      LEFT JOIN (
                        SELECT userid, SUM(amount) as total 
                        FROM mdl_abessi_classtimemanagement 
                        WHERE event = 'absence' AND hide = 0 AND due >= ?
                        GROUP BY userid
                      ) absence ON u.id = absence.userid
                      LEFT JOIN (
                        SELECT userid, SUM(amount) as total 
                        FROM mdl_abessi_classtimemanagement 
                        WHERE event = 'makeup' AND hide = 0 AND due >= ?
                        GROUP BY userid
                      ) makeup ON u.id = makeup.userid
                      INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                      WHERE uid.fieldid = 22 AND uid.data = 'student'
                      AND u.deleted = 0 AND u.suspended = 0";
            
            // 교사 심볼로 필터링
            if (!empty($teacherSymbol)) {
                $sqlAlerts .= " AND u.firstname LIKE ?";
                $alertParams[] = '%' . $teacherSymbol . '%';
            }
            
            $sqlAlerts .= " HAVING needed >= 4 OR needed <= -5
                           ORDER BY ABS(needed) DESC
                           LIMIT 20";
            
            $alertStudents = $DB->get_records_sql($sqlAlerts, $alertParams);
            
            if ($alertStudents) {
                foreach ($alertStudents as $student) {
                    $type = 'makeup_needed';
                    $hours = $student->needed;
                    
                    if ($student->needed <= -5) {
                        $type = 'surplus_study';
                        $hours = abs($student->needed);
                    }
                    
                    $alertsData[] = array(
                        'id' => $student->id,
                        'name' => $student->firstname . ' ' . $student->lastname,
                        'type' => $type,
                        'hours' => round($hours, 1)
                    );
                }
            }
            
            // 초과 학습 시간이 있는 학생 찾기
            $sqlExtraStudy = "SELECT DISTINCT u.id, u.firstname, u.lastname
                            FROM mdl_user u
                            INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                            INNER JOIN mdl_abessi_schedule s ON u.id = s.userid AND s.pinned = 1
                            WHERE uid.fieldid = 22 AND uid.data = 'student'
                            AND u.deleted = 0 AND u.suspended = 0";
            
            $extraParams = array();
            if (!empty($teacherSymbol)) {
                $sqlExtraStudy .= " AND u.firstname LIKE ?";
                $extraParams[] = '%' . $teacherSymbol . '%';
            }
            $sqlExtraStudy .= " LIMIT 30";
            
            $candidateStudents = $DB->get_records_sql($sqlExtraStudy, $extraParams);
            
            if ($candidateStudents) {
                foreach ($candidateStudents as $student) {
                    $attendanceData = calculateAttendanceHours($DB, $student->id, $threeWeeksAgo, false);
                    
                    if ($attendanceData['extraStudyHours'] > 0) {
                        $alertsData[] = array(
                            'id' => $student->id,
                            'name' => $student->firstname . ' ' . $student->lastname,
                            'type' => 'extra_study',
                            'hours' => $attendanceData['extraStudyHours']
                        );
                    }
                }
            }
            
            // 중요도에 따라 정렬
            usort($alertsData, function($a, $b) {
                $priority = array('makeup_needed' => 1, 'extra_study' => 2, 'surplus_study' => 3);
                if ($priority[$a['type']] != $priority[$b['type']]) {
                    return $priority[$a['type']] - $priority[$b['type']];
                }
                return $b['hours'] - $a['hours'];
            });
            
            $alertsData = array_slice($alertsData, 0, 15);
            
            echo json_encode($alertsData, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(array('error' => logError("알림 데이터 로드 오류: " . $e->getMessage(), __FILE__, __LINE__)), JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 학생 목록 로드
    if ($ajaxType === 'students') {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
            $params = array();
            
            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1 as phone
                    FROM mdl_user u
                    INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                    WHERE uid.fieldid = 22 AND uid.data = 'student'
                    AND u.deleted = 0 AND u.suspended = 0";
            
            // 교사 심볼로 필터링
            if (!empty($teacherSymbol)) {
                $sql .= " AND u.firstname LIKE ?";
                $params[] = '%' . $teacherSymbol . '%';
            }
            
            // 검색 조건
            if ($searchQuery) {
                $searchTerm = '%' . $searchQuery . '%';
                $sql .= " AND (
                    CONCAT(u.firstname, ' ', u.lastname) LIKE ? 
                    OR CONCAT(u.firstname, u.lastname) LIKE ? 
                    OR u.firstname LIKE ? 
                    OR u.lastname LIKE ? 
                    OR u.email LIKE ?
                )";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY u.firstname ASC, u.lastname ASC";
            
            $students = $DB->get_records_sql($sql, $params);
            
            $studentsData = array();
            foreach ($students as $student) {
                $studentsData[] = array(
                    'id' => $student->id,
                    'name' => $student->firstname . ' ' . $student->lastname,
                    'email' => isset($student->email) ? $student->email : '',
                    'phone' => isset($student->phone) ? $student->phone : ''
                );
            }
            
            echo json_encode(array(
                'status' => 'success',
                'count' => count($studentsData),
                'data' => $studentsData
            ), JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'error' => logError("학생 목록 로드 오류: " . $e->getMessage(), __FILE__, __LINE__)
            ), JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 학생 상세 정보 로드
    if ($ajaxType === 'student_detail') {
        header('Content-Type: application/json; charset=utf-8');
        
        $studentid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
        $viewMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        
        if ($studentid <= 0) {
            echo json_encode(array('success' => false, 'error' => '유효하지 않은 학생 ID입니다.'), JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!preg_match('/^\d{4}-\d{2}$/', $viewMonth)) {
            $viewMonth = date('Y-m');
        }
        
        try {
            // 권한 검증
            if (!empty($teacherSymbol)) {
                $authCheck = $DB->get_record_sql(
                    "SELECT id FROM mdl_user
                     WHERE id = ? AND deleted = 0 AND suspended = 0
                     AND firstname LIKE ?",
                    array($studentid, '%'.$teacherSymbol.'%')
                );
                
                if (!$authCheck) {
                    throw new Exception('접근 권한이 없는 학생입니다.');
                }
            }
            
            // 학생 기본 정보
            $thisuser = $DB->get_record_sql(
                "SELECT id, lastname, firstname FROM mdl_user WHERE id = ?",
                array($studentid)
            );
            
            if (!$thisuser) {
                throw new Exception('학생을 찾을 수 없습니다.');
            }
            
            $stdname = $thisuser->firstname . " " . $thisuser->lastname;
            
            // 출결 시간 계산
            $attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo, false);
            
            // 최근 출결 기록
            $notifications = $DB->get_records_sql(
                "SELECT * FROM {abessi_classtimemanagement}
                 WHERE userid = ? AND hide = 0
                 ORDER BY due DESC LIMIT 20",
                array($studentid)
            );
            
            // 캘린더 데이터
            $startDate = strtotime($viewMonth . '-01');
            $endDate = strtotime($viewMonth . '-' . date('t', $startDate) . ' 23:59:59');
            
            $calendarRecords = $DB->get_records_sql(
                "SELECT id, event, amount, due, DATE(FROM_UNIXTIME(due)) as date_key
                 FROM mdl_abessi_classtimemanagement
                 WHERE userid = ? AND hide = 0 AND due BETWEEN ? AND ?
                 ORDER BY due ASC",
                array($studentid, $startDate, $endDate)
            );
            
            $calendarData = array();
            if ($calendarRecords) {
                foreach ($calendarRecords as $record) {
                    $dateKey = $record->date_key;
                    if (!isset($calendarData[$dateKey])) {
                        $calendarData[$dateKey] = array();
                    }
                    $calendarData[$dateKey][] = $record;
                }
            }
            
            // 학습 시간 데이터
            $studyData = array();
            $startDateStr = date('Y-m-d', $startDate);
            $endDateStr = date('Y-m-d', $endDate);
            
            $studyRecords = $DB->get_records_sql(
                "SELECT DATE(FROM_UNIXTIME(timecreated)) as study_date,
                        MIN(timecreated) as first_time,
                        MAX(timecreated) as last_time,
                        COUNT(*) as log_count
                 FROM mdl_abessi_missionlog
                 WHERE userid = ? AND DATE(FROM_UNIXTIME(timecreated)) BETWEEN ? AND ?
                 GROUP BY DATE(FROM_UNIXTIME(timecreated))",
                array($studentid, $startDateStr, $endDateStr)
            );
            
            if ($studyRecords) {
                foreach ($studyRecords as $record) {
                    $studyHours = 0;
                    if ($record->log_count > 1) {
                        $studyHours = round(($record->last_time - $record->first_time) / 3600, 1);
                    }
                    $studyData[$record->study_date] = (object) array(
                        'hours' => $studyHours,
                        'first_time' => $record->first_time,
                        'last_time' => $record->last_time,
                        'actual_start' => date('H:i', $record->first_time),
                        'actual_end' => date('H:i', $record->last_time),
                        'log_count' => $record->log_count
                    );
                }
            }
            
            // 시간표 정보
            $schedule = $DB->get_record_sql(
                "SELECT * FROM mdl_abessi_schedule WHERE userid = ? AND pinned = 1 ORDER BY id DESC LIMIT 1",
                array($studentid)
            );
            
            // 캘린더 HTML 생성 (인라인으로 생성)
            ob_start();
            // 캘린더 뷰는 JavaScript로 동적 생성하므로 여기서는 빈 문자열 반환
            // 실제 캘린더는 프론트엔드에서 생성
            $calendarHTML = '';
            ob_end_clean();
            
            // 알림 HTML 생성
            ob_start();
            if ($notifications) {
                foreach ($notifications as $notif) {
                    $eventClass = ($notif->event === 'absence') ? 'absence' : 'makeup';
                    echo '<div class="notification-item ' . $eventClass . '">';
                    echo '<div class="notif-date">' . date('Y-m-d', $notif->due) . '</div>';
                    echo '<div class="notif-content">';
                    echo '<strong>' . htmlspecialchars($notif->event === 'absence' ? '휴강' : '보강') . '</strong>: ';
                    echo $notif->amount . '시간';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p class="no-data">최근 출결 기록이 없습니다.</p>';
            }
            $notificationsHTML = ob_get_clean();
            
            echo json_encode(array(
                'success' => true,
                'student_id' => $studentid,
                'student_name' => $stdname,
                'attendance' => $attendanceData,
                'calendar_html' => $calendarHTML,
                'notifications_html' => $notificationsHTML,
                'view_month' => $viewMonth
            ), JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'error' => logError("학생 상세 정보 로드 오류: " . $e->getMessage(), __FILE__, __LINE__)
            ), JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

// ==================== 학생 정보 조회 ====================

$thisuser = null;
$stdname = "";
$attendanceData = array('totalAbsence' => 0, 'pastMakeup' => 0, 'futureMakeup' => 0, 'extraStudyHours' => 0, 'neededMakeup' => 0);
$notifications = array();
$schedule = null;
$calendarData = array();
$studyData = array();

if ($studentid > 0) {
    try {
        $thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
        if ($thisuser) {
            $stdname = $thisuser->firstname . " " . $thisuser->lastname;
        }
        
        // 출결 시간 계산
        $attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo, false);
        
        // 최근 출결 기록
        $notifications = $DB->get_records_sql(
            "SELECT * FROM {abessi_classtimemanagement} WHERE userid = ? AND hide = 0 ORDER BY due DESC LIMIT 20",
            array($studentid)
        );
        
        // 시간표 정보
        $schedule = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_schedule WHERE userid = ? AND pinned = 1 ORDER BY id DESC LIMIT 1",
            array($studentid)
        );
        
        // 캘린더용 출결 데이터
        $startDate = strtotime($viewMonth . '-01');
        $endDate = strtotime($viewMonth . '-' . date('t', $startDate) . ' 23:59:59');
        
        $calendarRecords = $DB->get_records_sql(
            "SELECT id, event, amount, due, DATE(FROM_UNIXTIME(due)) as date_key
             FROM mdl_abessi_classtimemanagement 
             WHERE userid = ? AND hide = 0 AND due BETWEEN ? AND ?
             ORDER BY due ASC",
            array($studentid, $startDate, $endDate)
        );
        
        if ($calendarRecords) {
            foreach ($calendarRecords as $record) {
                if (!isset($calendarData[$record->date_key])) {
                    $calendarData[$record->date_key] = array();
                }
                $calendarData[$record->date_key][] = $record;
            }
        }
        
        // 학습 시간 데이터
        $startDateStr = date('Y-m-d', $startDate);
        $endDateStr = date('Y-m-d', $endDate);
        
        $studyRecords = $DB->get_records_sql(
            "SELECT DATE(FROM_UNIXTIME(timecreated)) as study_date,
                    MIN(timecreated) as first_time,
                    MAX(timecreated) as last_time,
                    COUNT(*) as log_count
             FROM mdl_abessi_missionlog 
             WHERE userid = ? AND DATE(FROM_UNIXTIME(timecreated)) BETWEEN ? AND ?
             GROUP BY DATE(FROM_UNIXTIME(timecreated))",
            array($studentid, $startDateStr, $endDateStr)
        );
        
        if ($studyRecords) {
            foreach ($studyRecords as $record) {
                $studyHours = 0;
                if ($record->log_count > 1) {
                    $studyHours = round(($record->last_time - $record->first_time) / 3600, 1);
                }
                
                $studyData[$record->study_date] = (object) array(
                    'date' => $record->study_date,
                    'hours' => $studyHours,
                    'first_time' => $record->first_time,
                    'last_time' => $record->last_time,
                    'log_count' => $record->log_count,
                    'actual_start' => date('H:i', $record->first_time),
                    'actual_end' => date('H:i', $record->last_time)
                );
            }
        }
    } catch (Exception $e) {
        $error = logError("학생 정보 조회 오류: " . $e->getMessage(), __FILE__, __LINE__);
    }
}

// ==================== HTML 출력 ====================
// HTML 부분은 별도 파일로 분리하거나 인라인으로 작성
// 파일 크기 제한을 고려하여 최소한의 HTML만 포함

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>교사용 출결관리</title>
    <link rel="stylesheet" href="attendance_teacher.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>교사용 출결관리 시스템</h1>
            <p><?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> 선생님 
               <?php if (!empty($teacherSymbol)): ?>
               <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; margin: 0 10px;">
                   담당: <?php echo htmlspecialchars($teacherSymbol); ?>
               </span>
               <?php endif; ?>
               | <a href="/moodle/login/logout.php" style="color: white;">로그아웃</a></p>
            
            <button class="notification-btn" onclick="toggleNotifications()" id="notificationBtn">
                🔔 알림
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
            
            <div id="notificationDropdown" class="notification-dropdown">
                <div class="notification-header">📢 알림 로딩중...</div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="main-grid">
            <div class="sidebar">
                <h3>학생 목록</h3>
                <form method="GET" action="">
                    <?php if ($studentid > 0): ?>
                        <input type="hidden" name="userid" value="<?php echo $studentid; ?>">
                    <?php endif; ?>
                    <?php if ($viewMonth): ?>
                        <input type="hidden" name="month" value="<?php echo htmlspecialchars($viewMonth); ?>">
                    <?php endif; ?>
                    <div class="search-box">
                        <input type="text" name="search" id="searchInput" class="search-input" 
                               placeholder="학생 이름 검색..." value="<?php echo htmlspecialchars($searchQuery); ?>" autocomplete="off">
                        <button type="submit" class="search-btn">검색</button>
                    </div>
                </form>
                
                <?php if ($searchQuery): ?>
                    <a href="?" class="clear-search">✖ 검색 초기화</a>
                <?php endif; ?>
                
                <ul class="student-list" id="studentList">
                    <li class="no-results">학생 목록 로딩중...</li>
                </ul>
            </div>
            
            <div class="content">
                <?php if ($studentid > 0 && $thisuser): ?>
                    <h2><?php echo htmlspecialchars($stdname); ?> 학생 출결 관리</h2>
                    
                    <div class="status-container">
                        <div class="status-card">
                            <div class="status-value"><?php echo number_format($attendanceData['totalAbsence'], 1); ?></div>
                            <div class="status-label">총 휴강 시간</div>
                        </div>
                        <div class="status-card">
                            <div class="status-value"><?php echo number_format($attendanceData['pastMakeup'] + $attendanceData['futureMakeup'], 1); ?></div>
                            <div class="status-label">보강 시간</div>
                        </div>
                        <?php if (isset($attendanceData['extraStudyHours']) && $attendanceData['extraStudyHours'] > 0): ?>
                        <div class="status-card" style="background: #e0f2fe;">
                            <div class="status-value" style="color: #0369a1;"><?php echo number_format($attendanceData['extraStudyHours'], 1); ?></div>
                            <div class="status-label">초과 학습 인정</div>
                        </div>
                        <?php endif; ?>
                        <div class="status-card <?php echo $attendanceData['neededMakeup'] > 0 ? 'alert' : ''; ?>" 
                             style="<?php echo $attendanceData['neededMakeup'] < 0 ? 'background: #dcfce7;' : ''; ?>">
                            <div class="status-value" style="<?php echo $attendanceData['neededMakeup'] < 0 ? 'color: #16a34a;' : ''; ?>">
                                <?php echo number_format($attendanceData['neededMakeup'], 1); ?>
                            </div>
                            <div class="status-label">
                                <?php echo $attendanceData['neededMakeup'] < 0 ? '추가 학습 시간' : '보강 필요'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 캘린더 및 기타 UI는 별도 파일로 분리하거나 JavaScript로 동적 생성 -->
                    <div id="studentDetailContent">
                        <!-- 학생 상세 정보가 여기에 표시됩니다 -->
                    </div>
                    
                <?php else: ?>
                    <div class="no-data">
                        <h2>학생을 선택해주세요</h2>
                        <p>왼쪽 목록에서 관리할 학생을 선택하면 출결 정보를 확인하고 관리할 수 있습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 모달 및 기타 UI 요소는 별도 파일로 분리 -->
    
    <script src="attendance_teacher.js"></script>
    <script>
        // 초기화
        document.addEventListener('DOMContentLoaded', function() {
            loadAlerts();
            loadStudents('<?php echo addslashes($searchQuery); ?>');
            <?php if ($studentid > 0): ?>
            loadStudentDetail(<?php echo $studentid; ?>, null);
            <?php endif; ?>
        });
    </script>
    
    <div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 5px; font-size: 11px;">
        <?php 
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        echo "로딩 시간: {$execution_time}ms";
        ?>
    </div>
</body>
</html>
