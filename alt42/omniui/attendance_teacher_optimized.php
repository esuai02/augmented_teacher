<?php
// 시작 시간 측정
$start_time = microtime(true);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 시간대 설정
date_default_timezone_set('Asia/Seoul');

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 캐싱 헬퍼 함수
function getCachedData($key, $callback, $ttl = 300) {
    $cacheFile = sys_get_temp_dir() . '/attendance_cache_' . md5($key) . '.cache';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
        $data = unserialize(file_get_contents($cacheFile));
        if ($data !== false) {
            return $data;
        }
    }
    
    $data = $callback();
    file_put_contents($cacheFile, serialize($data));
    return $data;
}

// 현재 로그인한 사용자의 역할 조회
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid=? AND fieldid=22", array($USER->id)); 
$role = $userrole ? $userrole->role : 'student';

// 교사 권한 확인
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
} elseif ($role !== 'student') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다. 교사 계정으로 로그인해주세요.</h2>");
}

// 교사 심볼 설정
$tsymbol = '';
$tsymbol1 = '';
$tsymbol2 = '';
$tsymbol3 = '';

if ($USER->firstname) {
    if (strpos($USER->firstname, '🌟') !== false) {
        $tsymbol = '🌟';
    } elseif (strpos($USER->firstname, '⭐') !== false) {
        $tsymbol = '⭐';
    } elseif (strpos($USER->firstname, '✨') !== false) {
        $tsymbol = '✨';
    } elseif (strpos($USER->firstname, '🎯') !== false) {
        $tsymbol = '🎯';
    } else {
        $teacherId = $USER->id;
        $symbols = array('🌟', '⭐', '✨', '🎯', '🔥', '💫', '🌈', '🎨', '🎪', '🎭');
        $symbolIndex = $teacherId % count($symbols);
        $tsymbol = $symbols[$symbolIndex];
    }
    
    $tsymbol1 = $tsymbol;
    $tsymbol2 = $tsymbol;
    $tsymbol3 = $tsymbol;
}

// 최근 3주 기준
$threeWeeksAgo = strtotime("-3 weeks");

// 최적화된 출결 시간 계산 함수 - 배치 처리 버전
function calculateAttendanceHoursBatch($DB, $studentIds, $threeWeeksAgo) {
    if (empty($studentIds)) {
        return array();
    }
    
    $currentTime = time();
    $startDate = date('Y-m-d', $threeWeeksAgo);
    $endDate = date('Y-m-d');
    
    // IN 절을 위한 플레이스홀더 생성
    $inSql = implode(',', array_fill(0, count($studentIds), '?'));
    
    // 모든 학생의 데이터를 한 번에 가져오기
    $sql = "SELECT 
                u.id as userid,
                u.firstname,
                u.lastname,
                COALESCE(absence.total, 0) as total_absence,
                COALESCE(makeup_past.total, 0) as past_makeup,
                COALESCE(makeup_future.total, 0) as future_makeup,
                COALESCE(extra.extra_hours, 0) as extra_study_hours
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
                WHERE event = 'makeup' AND hide = 0 AND due < ?
                GROUP BY userid
            ) makeup_past ON u.id = makeup_past.userid
            LEFT JOIN (
                SELECT userid, SUM(amount) as total 
                FROM mdl_abessi_classtimemanagement 
                WHERE event = 'makeup' AND hide = 0 AND due >= ?
                GROUP BY userid
            ) makeup_future ON u.id = makeup_future.userid
            LEFT JOIN (
                SELECT 
                    ml.userid,
                    SUM(
                        CASE 
                            WHEN (TIMESTAMPDIFF(HOUR, MIN(ml.timecreated), MAX(ml.timecreated)) - 
                                  CASE DAYOFWEEK(DATE(FROM_UNIXTIME(ml.timecreated)))
                                      WHEN 1 THEN s.duration7
                                      WHEN 2 THEN s.duration1
                                      WHEN 3 THEN s.duration2
                                      WHEN 4 THEN s.duration3
                                      WHEN 5 THEN s.duration4
                                      WHEN 6 THEN s.duration5
                                      WHEN 7 THEN s.duration6
                                  END) > 1
                            THEN (TIMESTAMPDIFF(HOUR, MIN(ml.timecreated), MAX(ml.timecreated)) - 
                                  CASE DAYOFWEEK(DATE(FROM_UNIXTIME(ml.timecreated)))
                                      WHEN 1 THEN s.duration7
                                      WHEN 2 THEN s.duration1
                                      WHEN 3 THEN s.duration2
                                      WHEN 4 THEN s.duration3
                                      WHEN 5 THEN s.duration4
                                      WHEN 6 THEN s.duration5
                                      WHEN 7 THEN s.duration6
                                  END)
                            ELSE 0
                        END
                    ) as extra_hours
                FROM mdl_abessi_missionlog ml
                JOIN mdl_abessi_schedule s ON ml.userid = s.userid AND s.pinned = 1
                WHERE DATE(FROM_UNIXTIME(ml.timecreated)) BETWEEN ? AND ?
                GROUP BY ml.userid, DATE(FROM_UNIXTIME(ml.timecreated))
            ) extra ON u.id = extra.userid
            WHERE u.id IN ($inSql)";
    
    $params = array($threeWeeksAgo, $currentTime, $currentTime, $startDate, $endDate);
    $params = array_merge($params, $studentIds);
    
    $records = $DB->get_records_sql($sql, $params);
    
    $results = array();
    foreach ($records as $record) {
        $neededMakeup = $record->total_absence - 
                       ($record->past_makeup + $record->future_makeup + $record->extra_study_hours);
        
        $results[$record->userid] = array(
            'totalAbsence' => $record->total_absence,
            'pastMakeup' => $record->past_makeup,
            'futureMakeup' => $record->future_makeup,
            'extraStudyHours' => round($record->extra_study_hours, 1),
            'neededMakeup' => round($neededMakeup, 1),
            'firstname' => $record->firstname,
            'lastname' => $record->lastname
        );
    }
    
    return $results;
}

// 개별 학생 출결 계산 (선택된 학생용)
function calculateAttendanceHours($DB, $studentid, $threeWeeksAgo, $skipExtraStudy = false) {
    $result = calculateAttendanceHoursBatch($DB, array($studentid), $threeWeeksAgo);
    return isset($result[$studentid]) ? $result[$studentid] : 
           array('totalAbsence' => 0, 'pastMakeup' => 0, 'futureMakeup' => 0, 
                 'extraStudyHours' => 0, 'neededMakeup' => 0);
}

// URL 파라미터
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : (isset($_POST['userid']) ? intval($_POST['userid']) : 0);
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $studentid > 0) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'absence' || $action === 'makeup') {
        $selectedDate = isset($_POST['selectedDate']) ? trim($_POST['selectedDate']) : '';
        $selectedHours = isset($_POST['selectedHours']) ? floatval($_POST['selectedHours']) : 0;
        
        if (!empty($selectedDate) && $selectedHours > 0) {
            $selectedTimestamp = strtotime($selectedDate);
            $selectedMonth = date('Y-m', $selectedTimestamp);
            
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
                $_SESSION['success'] = ($action === 'absence' ? "휴강" : "보강") . " 기록이 추가되었습니다.";
                $viewMonth = $selectedMonth;
            } else {
                $_SESSION['error'] = "기록 추가 중 오류가 발생했습니다.";
            }
        }
        
        $redirectUrl = "?userid=" . urlencode($studentid) . "&search=" . urlencode($searchQuery) . "&month=" . urlencode($viewMonth);
        header("Location: " . $redirectUrl);
        exit;
    }
    
    if ($action === 'delete') {
        $recordId = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
        
        if ($recordId > 0) {
            $record = $DB->get_record('abessi_classtimemanagement', array('id' => $recordId, 'userid' => $studentid));
            
            if ($record) {
                $record->hide = 1;
                if ($DB->update_record('abessi_classtimemanagement', $record)) {
                    $_SESSION['success'] = "출결 기록이 삭제되었습니다.";
                } else {
                    $_SESSION['error'] = "삭제 중 오류가 발생했습니다.";
                }
            } else {
                $_SESSION['error'] = "해당 기록을 찾을 수 없습니다.";
            }
        }
        
        $redirectUrl = "?userid=" . urlencode($studentid) . "&search=" . urlencode($searchQuery) . "&month=" . urlencode($viewMonth);
        header("Location: " . $redirectUrl);
        exit;
    }
}

// 세션 메시지
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
unset($_SESSION['error']);
unset($_SESSION['success']);

// AJAX 요청 처리
if (isset($_GET['ajax'])) {
    $ajaxType = $_GET['ajax'];
    
    // 알림 데이터 로드 - 최적화된 버전
    if ($ajaxType === 'alerts') {
        $cacheKey = "alerts_{$USER->id}_" . date('Y-m-d-H', time());
        
        $alertsData = getCachedData($cacheKey, function() use ($DB, $tsymbol, $tsymbol1, $tsymbol2, $tsymbol3, $threeWeeksAgo) {
            // 담당 학생 ID 가져오기
            $studentFilter = "";
            $params = array();
            
            if (!empty($tsymbol)) {
                $studentFilter = " AND (u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ?)";
                $params[] = '%' . $tsymbol . '%';
                $params[] = '%' . $tsymbol1 . '%';
                $params[] = '%' . $tsymbol2 . '%';
                $params[] = '%' . $tsymbol3 . '%';
            }
            
            $sql = "SELECT u.id
                    FROM mdl_user u
                    INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                    WHERE uid.fieldid = 22 AND uid.data = 'student'
                    AND u.deleted = 0 AND u.suspended = 0
                    $studentFilter
                    LIMIT 100";
            
            $students = $DB->get_records_sql($sql, $params);
            
            if (empty($students)) {
                return array();
            }
            
            $studentIds = array_keys($students);
            
            // 배치로 모든 학생의 출결 데이터 계산
            $attendanceData = calculateAttendanceHoursBatch($DB, $studentIds, $threeWeeksAgo);
            
            $alerts = array();
            foreach ($attendanceData as $studentId => $data) {
                if ($data['neededMakeup'] >= 4) {
                    $alerts[] = array(
                        'id' => $studentId,
                        'name' => $data['firstname'] . ' ' . $data['lastname'],
                        'type' => 'makeup_needed',
                        'hours' => round($data['neededMakeup'], 1)
                    );
                } elseif ($data['neededMakeup'] <= -5) {
                    $alerts[] = array(
                        'id' => $studentId,
                        'name' => $data['firstname'] . ' ' . $data['lastname'],
                        'type' => 'surplus_study',
                        'hours' => round(abs($data['neededMakeup']), 1)
                    );
                } elseif ($data['extraStudyHours'] > 0) {
                    $alerts[] = array(
                        'id' => $studentId,
                        'name' => $data['firstname'] . ' ' . $data['lastname'],
                        'type' => 'extra_study',
                        'hours' => round($data['extraStudyHours'], 1)
                    );
                }
            }
            
            // 정렬
            usort($alerts, function($a, $b) {
                $priority = array('makeup_needed' => 1, 'extra_study' => 2, 'surplus_study' => 3);
                if ($priority[$a['type']] != $priority[$b['type']]) {
                    return $priority[$a['type']] - $priority[$b['type']];
                }
                return $b['hours'] - $a['hours'];
            });
            
            return array_slice($alerts, 0, 15);
        }, 300); // 5분 캐시
        
        header('Content-Type: application/json');
        echo json_encode($alertsData);
        exit;
    }
    
    // 학생 목록 로드
    if ($ajaxType === 'students') {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 50;
        $offset = ($page - 1) * $pageSize;
        
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        $params = array();
        
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1 as phone
                FROM mdl_user u
                INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                WHERE uid.fieldid = 22 AND uid.data = 'student'
                AND u.deleted = 0 
                AND u.suspended = 0";
        
        if (!empty($tsymbol)) {
            $sql .= " AND (u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ?)";
            $params[] = '%' . $tsymbol . '%';
            $params[] = '%' . $tsymbol1 . '%';
            $params[] = '%' . $tsymbol2 . '%';
            $params[] = '%' . $tsymbol3 . '%';
        }
        
        if ($searchQuery) {
            $searchTerm = '%' . $searchQuery . '%';
            $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // 전체 개수 구하기
        $countSql = "SELECT COUNT(*) as total FROM ($sql) as t";
        $totalCount = $DB->get_record_sql($countSql, $params)->total;
        
        // 페이징 적용
        $sql .= " ORDER BY u.firstname ASC, u.lastname ASC LIMIT $pageSize OFFSET $offset";
        
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
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'status' => 'success',
            'count' => count($studentsData),
            'total' => $totalCount,
            'page' => $page,
            'pages' => ceil($totalCount / $pageSize),
            'data' => $studentsData
        ));
        exit;
    }
}

// 선택된 학생 정보
$thisuser = null;
$stdname = "";
$attendanceData = array('totalAbsence' => 0, 'pastMakeup' => 0, 'futureMakeup' => 0, 'extraStudyHours' => 0, 'neededMakeup' => 0);
$notifications = array();
$schedule = null;
$calendarData = array();
$studyData = array();

if ($studentid > 0) {
    $thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
    if ($thisuser) {
        $stdname = $thisuser->firstname . " " . $thisuser->lastname;
    }
    
    // 출결 시간 계산
    $attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo, false);
    
    // 최근 출결 기록
    $sqlNotifications = "SELECT * FROM mdl_abessi_classtimemanagement 
                         WHERE userid = ? AND hide = 0 
                         ORDER BY due DESC LIMIT 20";
    $notifications = $DB->get_records_sql($sqlNotifications, array($studentid));
    
    // 시간표 정보
    $schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule 
                                     WHERE userid = ? AND pinned = 1 
                                     ORDER BY id DESC LIMIT 1", array($studentid));
    
    // 캘린더 데이터
    $startDate = strtotime($viewMonth . '-01');
    $endDate = strtotime($viewMonth . '-' . date('t', $startDate) . ' 23:59:59');
    
    $sqlCalendar = "SELECT 
                        id, event, amount, due,
                        DATE(FROM_UNIXTIME(due)) as date_key
                    FROM mdl_abessi_classtimemanagement 
                    WHERE userid = ? AND hide = 0 AND due BETWEEN ? AND ?
                    ORDER BY due ASC";
    $calendarRecords = $DB->get_records_sql($sqlCalendar, array($studentid, $startDate, $endDate));
    
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
    
    $sqlStudy = "SELECT 
                    DATE(FROM_UNIXTIME(timecreated)) as study_date,
                    MIN(timecreated) as first_time,
                    MAX(timecreated) as last_time,
                    COUNT(*) as log_count
                 FROM mdl_abessi_missionlog 
                 WHERE userid = ? 
                 AND DATE(FROM_UNIXTIME(timecreated)) BETWEEN ? AND ?
                 GROUP BY DATE(FROM_UNIXTIME(timecreated))";
    
    $studyRecords = $DB->get_records_sql($sqlStudy, array($studentid, $startDateStr, $endDateStr));
    
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
}

// 로딩 시간 계산
$load_time = round((microtime(true) - $start_time) * 1000, 2);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>교사용 출결관리 (최적화)</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .performance-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #48bb78;
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
        }
        .loading-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        .loading-indicator.active {
            display: block;
        }
        /* 기존 스타일 유지 */
    </style>
</head>
<body>
    <div class="container">
        <h1>교사용 출결관리 시스템 (최적화 버전)</h1>
        
        <!-- 성능 표시 -->
        <div class="performance-badge">
            로딩 시간: <?php echo $load_time; ?>ms
        </div>
        
        <!-- 로딩 인디케이터 -->
        <div class="loading-indicator" id="loadingIndicator">
            <div>데이터 로딩 중...</div>
        </div>
        
        <!-- 나머지 HTML 내용은 기존과 동일 -->
    </div>
    
    <script>
        // 페이지네이션 지원
        let currentPage = 1;
        let totalPages = 1;
        
        // 학생 목록 로드 함수 개선
        function loadStudents(page = 1) {
            const indicator = document.getElementById('loadingIndicator');
            indicator.classList.add('active');
            
            fetch(`?ajax=students&page=${page}&search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentPage = data.page;
                        totalPages = data.pages;
                        updateStudentList(data.data);
                        updatePagination();
                    }
                })
                .finally(() => {
                    indicator.classList.remove('active');
                });
        }
        
        function updatePagination() {
            // 페이지네이션 UI 업데이트
            const paginationHtml = `
                <div class="pagination">
                    ${currentPage > 1 ? `<button onclick="loadStudents(${currentPage - 1})">이전</button>` : ''}
                    <span>${currentPage} / ${totalPages}</span>
                    ${currentPage < totalPages ? `<button onclick="loadStudents(${currentPage + 1})">다음</button>` : ''}
                </div>
            `;
            // 페이지네이션 표시
        }
        
        // 초기 로드
        document.addEventListener('DOMContentLoaded', function() {
            loadStudents(1);
            loadAlerts();
        });
    </script>
</body>
</html>