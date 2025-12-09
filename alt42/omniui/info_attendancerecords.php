<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 공통 함수들
/**
 * 최근 3주간 출결 시간 계산
 */

// 현재 로그인한 사용자의 역할 조회 (기존 $role 변수를 현재 사용자 기준으로 변경)
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"); 
$role = $userrole ? $userrole->role : 'student';
 
function calculateAttendanceHours($DB, $studentid, $threeWeeksAgo) {
    // 결석 시간 계산
    $sqlAbsence = "SELECT SUM(amount) as total_absence 
                   FROM {abessi_classtimemanagement} 
                   WHERE userid = ? AND event = 'absence' AND hide = 0 AND due >= ?";
    $absenceRecord = $DB->get_record_sql($sqlAbsence, array($studentid, $threeWeeksAgo));
    $totalAbsence = $absenceRecord ? floatval($absenceRecord->total_absence) : 0;
    
    // 보강 시간 계산 (과거/미래 분리)
    $sqlMakeupAll = "SELECT amount, due 
                     FROM {abessi_classtimemanagement} 
                     WHERE userid = ? AND event = 'makeup' AND hide = 0 AND due >= ?";
    $makeupRecords = $DB->get_records_sql($sqlMakeupAll, array($studentid, $threeWeeksAgo));
    
    $pastMakeup = 0;
    $futureMakeup = 0;
    if ($makeupRecords) {
        foreach ($makeupRecords as $rec) {
            if (date("Y-m-d", $rec->due) < date("Y-m-d")) {
                $pastMakeup += floatval($rec->amount);
            } else {
                $futureMakeup += floatval($rec->amount);
            }
        }
    }
    
    $neededMakeup = $totalAbsence - ($pastMakeup + $futureMakeup);
    if ($neededMakeup < 0) { $neededMakeup = 0; }
    
    return array(
        'totalAbsence' => $totalAbsence,
        'pastMakeup' => $pastMakeup,
        'futureMakeup' => $futureMakeup,
        'neededMakeup' => $neededMakeup
    );
}

/**
 * 사용자 권한 확인
 */
function checkUserPermission($role, $recordTimecreated, $currentTime, $currentUserId, $recordUserId) {
    // student가 아니면 교사/관리자로 간주하여 모든 권한 부여
    if ($role !== 'student') {
        return true;
    }
    // 학생 본인인 경우 모든 기록 삭제 가능 (시간 제한 제거)
    if ($role === 'student' && $currentUserId == $recordUserId) {
        return true;
    }
    return false;
}

/**
 * 입력값 검증
 */
function validateInput($selectedDate, $selectedHours) {
    $errors = array();
    
    if (empty($selectedDate)) {
        $errors[] = "날짜를 선택해주세요.";
    } else {
        $timestamp = strtotime($selectedDate);
        if ($timestamp === false) {
            $errors[] = "올바른 날짜 형식이 아닙니다.";
        }
    }
    
    if (empty($selectedHours) || !is_numeric($selectedHours)) {
        $errors[] = "시간을 선택해주세요.";
    } else {
        $hours = floatval($selectedHours);
        if ($hours <= 0 || $hours > 6) {
            $errors[] = "시간은 0.5시간부터 6시간까지 선택 가능합니다.";
        }
    }
    
    return $errors;
}

// URL로부터 학생 ID 받아오기 (없으면 현재 로그인한 사용자)
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;
 
// 권한 확인: 본인이거나 교사/관리자만 접근 가능
if ($studentid != $USER->id) {
    // 다른 학생의 페이지에 접근하려는 경우에만 권한 체크
    if ($role === 'student') {
        die("접근 권한이 없습니다.");
    }
}

// 학생 정보 및 역할 조회
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
if (!$thisuser) {
    die("존재하지 않는 사용자입니다.");
}
$stdname = $thisuser->firstname . " " . $thisuser->lastname;

// 달력 표시 연도와 월 (GET 파라미터 우선, 없으면 현재)
$displayYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$displayMonth = isset($_GET['month']) ? intval($_GET['month']) - 1 : date('n') - 1;

// 연도와 월 범위 검증
if ($displayYear < 2020 || $displayYear > 2030) {
    $displayYear = date('Y');
}
if ($displayMonth < 0 || $displayMonth > 11) {
    $displayMonth = date('n') - 1;
}

// 최근 3주 기준 (반영 기간)
$threeWeeksAgo = strtotime("-3 weeks");

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $redirectUrl = "?userid=" . urlencode($studentid) . "&year=" . urlencode($displayYear) . "&month=" . urlencode($displayMonth + 1);
    
    // 삭제 요청 처리
    if ($action === 'delete' && isset($_POST['delete_record_id'])) {
        $delete_record_id = intval($_POST['delete_record_id']);
        $recordToDelete = $DB->get_record('abessi_classtimemanagement', array('id' => $delete_record_id, 'userid' => $studentid));
        
        if (!$recordToDelete) {
            $_SESSION['error'] = "기록을 찾을 수 없습니다.";
            header("Location: " . $redirectUrl);
            exit;
        } else {
            // 권한 확인
            if (checkUserPermission($role, $recordToDelete->timecreated, time(), $USER->id, $studentid)) {
                // 휴강 삭제 시 보강이 있는지 확인
                if ($recordToDelete->event === 'absence') {
                    $attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo);
                    $totalMakeup = $attendanceData['pastMakeup'] + $attendanceData['futureMakeup'];
                    
                    if ($totalMakeup > 0) {
                        $_SESSION['error'] = "보강 기록이 있는 상태에서는 휴강을 삭제할 수 없습니다. 먼저 보강 기록을 삭제해주세요.";
                        header("Location: " . $redirectUrl);
                        exit;
                    } else {
                        $recordToDelete->hide = 1;
                        if ($DB->update_record('abessi_classtimemanagement', $recordToDelete)) {
                            $_SESSION['success'] = "기록이 삭제되었습니다.";
                            header("Location: " . $redirectUrl);
                            exit;
                        } else {
                            $_SESSION['error'] = "기록 삭제 중 오류가 발생했습니다.";
                            header("Location: " . $redirectUrl);
                            exit;
                        }
                    }
                } else {
                    // 보강 삭제는 제한 없음
                    $recordToDelete->hide = 1;
                    if ($DB->update_record('abessi_classtimemanagement', $recordToDelete)) {
                        $_SESSION['success'] = "기록이 삭제되었습니다.";
                        header("Location: " . $redirectUrl);
                        exit;
                    } else {
                        $_SESSION['error'] = "기록 삭제 중 오류가 발생했습니다.";
                        header("Location: " . $redirectUrl);
                        exit;
                    }
                }
            } else {
                $_SESSION['error'] = "삭제할 수 있는 권한이 없습니다. (학생은 기록 생성 후 6시간 이내만 삭제 가능)";
                header("Location: " . $redirectUrl);
                exit;
            }
        }
    } else {
        // 출결 기록 등록 처리 (휴강 / 보강)
        $selectedDate = isset($_POST['selectedDate']) ? trim($_POST['selectedDate']) : '';
        $selectedHours = isset($_POST['selectedHours']) ? $_POST['selectedHours'] : '';
        
        // 입력값 검증
        $validationErrors = validateInput($selectedDate, $selectedHours);
        if (!empty($validationErrors)) {
            $_SESSION['error'] = implode(' ', $validationErrors);
            header("Location: " . $redirectUrl);
            exit;
        } else {
            $selectedHours = floatval($selectedHours);
            $selectedTimestamp = strtotime($selectedDate);
            $status = 'done';
            
            // 현재 출결 시간 계산
            $attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo);
            
            if ($action === 'absence') {
                // 신규 결석 반영 후 보강 필요시간 계산
                $newNeededMakeup = ($attendanceData['totalAbsence'] + $selectedHours) - ($attendanceData['pastMakeup'] + $attendanceData['futureMakeup']);
                if ($newNeededMakeup < 0) { $newNeededMakeup = 0; }
                
                $record = new stdClass();
                $record->userid = $studentid;
                $record->event = 'absence';
                $record->hide = 0;
                $record->amount = $selectedHours;
                $record->text = '';
                $record->due = $selectedTimestamp;
                $record->timecreated = time();
                $record->status = $status;
                $record->needmakeup = $newNeededMakeup;
                $record->role = $role; // 현재 사용자의 역할 저장
                
                if ($DB->insert_record('abessi_classtimemanagement', $record)) {
                    $_SESSION['success'] = "휴강 기록이 추가되었습니다.";
                    header("Location: " . $redirectUrl);
                    exit;
                } else {
                    $_SESSION['error'] = "휴강 기록 추가 중 오류가 발생했습니다.";
                    header("Location: " . $redirectUrl);
                    exit;
                }
            } elseif ($action === 'makeup') {
                // 휴강이 없는 상태에서 보강 추가 방지
                if ($attendanceData['totalAbsence'] <= 0) {
                    $_SESSION['error'] = "휴강 기록이 없는 상태에서는 보강을 추가할 수 없습니다. 먼저 휴강을 등록해주세요.";
                    header("Location: " . $redirectUrl);
                    exit;
                } else {
                    // 보강 가능 시간 확인
                    $availableMakeupTime = $attendanceData['totalAbsence'] - ($attendanceData['pastMakeup'] + $attendanceData['futureMakeup']);
                    
                    if ($selectedHours > $availableMakeupTime) {
                        $_SESSION['error'] = "보강 시간이 결석 시간보다 많을 수 없습니다. (가능한 보강 시간: {$availableMakeupTime}시간)";
                        header("Location: " . $redirectUrl);
                        exit;
                    } else {
                        // 신규 보강 반영 후 보강 필요시간 계산
                        $newNeededMakeup = $availableMakeupTime - $selectedHours;
                        if ($newNeededMakeup < 0) { $newNeededMakeup = 0; }
                        
                        $record = new stdClass();
                        $record->userid = $studentid;
                        $record->event = 'makeup';
                        $record->hide = 0;
                        $record->amount = $selectedHours;
                        $record->text = '';
                        $record->due = $selectedTimestamp;
                        $record->timecreated = time();
                        $record->status = $status;
                        $record->needmakeup = $newNeededMakeup;
                        $record->role = $role; // 현재 사용자의 역할 저장
                        
                        if ($DB->insert_record('abessi_classtimemanagement', $record)) {
                            $_SESSION['success'] = "보강 기록이 추가되었습니다.";
                            header("Location: " . $redirectUrl);
                            exit;
                        } else {
                            $_SESSION['error'] = "보강 기록 추가 중 오류가 발생했습니다.";
                            header("Location: " . $redirectUrl);
                            exit;
                        }
                    }
                }
            }
        }
    }
}

// 세션에서 메시지 읽기 (한 번 읽으면 삭제)
$error = null;
$success = null;
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// 최종 출결 시간 계산 (화면 표시용)
$attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo);
$totalAbsence = $attendanceData['totalAbsence'];
$pastMakeup = $attendanceData['pastMakeup'];
$futureMakeup = $attendanceData['futureMakeup'];
$neededMakeup = $attendanceData['neededMakeup'];

// 달력 표시용: 해당 월의 시작/끝 날짜 및 기간 내 기록 조회
$startDate = mktime(0, 0, 0, $displayMonth + 1, 1, $displayYear);
$endDate = mktime(23, 59, 59, $displayMonth + 1, date('t', $startDate), $displayYear);

// 메인 목록에서는 hide=0 인 기록만 표시
$sqlNotifications = "SELECT * FROM {abessi_classtimemanagement} 
                     WHERE userid = ? AND hide = 0 
                     AND timecreated BETWEEN ? AND ? 
                     ORDER BY due ASC";
$notifications = $DB->get_records_sql($sqlNotifications, array($studentid, $startDate, $endDate));

// 삭제된 기록 (hide=1) 목록
$sqlDeleted = "SELECT * FROM {abessi_classtimemanagement} 
               WHERE userid = ? AND hide = 1 
               AND timecreated BETWEEN ? AND ? 
               ORDER BY due ASC";
$deletedRecords = $DB->get_records_sql($sqlDeleted, array($studentid, $startDate, $endDate));

// 달력 그리드 함수 (월요일 시작)
function getDaysInMonth($year, $month) {
    $firstDay = date('w', mktime(0, 0, 0, $month + 1, 1, $year));
    $startOffset = ($firstDay == 0) ? 6 : $firstDay - 1;
    $daysInMonth = date('t', mktime(0, 0, 0, $month + 1, 1, $year));
    $todayDay = date('j');
    $todayMonth = date('n') - 1;
    $todayYear = date('Y');
    $days = array();
    for ($i = 0; $i < $startOffset; $i++) {
        $days[] = array('date' => '', 'isCurrentMonth' => false);
    }
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $isToday = ($i == $todayDay && $month == $todayMonth && $year == $todayYear);
        $days[] = array('date' => $i, 'isCurrentMonth' => true, 'isToday' => $isToday);
    }
    return $days;
}
$calendarDays = getDaysInMonth($displayYear, $displayMonth);

// 달력에 미래 보강 및 미래 휴강 날짜 계산
$futureMakeupDays = array();
$futureAbsenceDays = array();
if ($notifications) {
    foreach ($notifications as $record) {
        if ($record->event == 'makeup' && date("Y-m-d", $record->due) > date("Y-m-d")) {
            $dayOfMonth = date("j", $record->due);
            $futureMakeupDays[] = $dayOfMonth;
        }
        if ($record->event == 'absence' && date("Y-m-d", $record->due) > date("Y-m-d")) {
            $dayOfMonth = date("j", $record->due);
            $futureAbsenceDays[] = $dayOfMonth;
        }
    }
    $futureMakeupDays = array_unique($futureMakeupDays);
    $futureAbsenceDays = array_unique($futureAbsenceDays);
}

// 시간 선택 옵션 (0.5시간 단위, 최대 6시간)
$timeOptions = array();
for ($i = 1; $i <= 12; $i++) {
    $val = $i * 0.5;
    if ($val <= 6) {
        $timeOptions[] = $val;
    }
}

// "더보기/접기" 기능 처리
$notificationsArr = $notifications ? array_values($notifications) : array();
$showAll = isset($_GET['showAll']) && $_GET['showAll'] == 1;
$recordsToShow = $showAll ? $notificationsArr : array_slice($notificationsArr, 0, 5);

// 달력 네비게이션 계산
$prevYear = ($displayMonth == 0) ? $displayYear - 1 : $displayYear;
$prevMonth = ($displayMonth == 0) ? 12 : $displayMonth;
$nextYear = ($displayMonth == 11) ? $displayYear + 1 : $displayYear;
$nextMonth = ($displayMonth == 11) ? 1 : $displayMonth + 2;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>수업 출결 관리</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8fafc;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 1.5rem;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* 토스트 알림 스타일 */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }
    
    .toast {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      margin-bottom: 10px;
      padding: 16px 20px;
      min-width: 300px;
      max-width: 400px;
      transform: translateX(400px);
      opacity: 0;
      transition: all 0.3s ease-in-out;
      border-left: 4px solid #10b981;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .toast.show {
      transform: translateX(0);
      opacity: 1;
    }
    
    .toast.error {
      border-left-color: #ef4444;
    }
    
    .toast.success {
      border-left-color: #10b981;
    }
    
    .toast-icon {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: bold;
      font-size: 12px;
      flex-shrink: 0;
    }
    
    .toast.error .toast-icon {
      background-color: #ef4444;
    }
    
    .toast.success .toast-icon {
      background-color: #10b981;
    }
    
    .toast-content {
      flex: 1;
    }
    
    .toast-title {
      font-weight: 600;
      margin-bottom: 4px;
      color: #1f2937;
    }
    
    .toast-message {
      color: #6b7280;
      font-size: 14px;
      line-height: 1.4;
    }
    
    .toast-close {
      background: none;
      border: none;
      color: #9ca3af;
      cursor: pointer;
      font-size: 18px;
      padding: 0;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .toast-close:hover {
      color: #6b7280;
    }

    /* 달력 영역 */
    .calendar { margin-bottom: 1rem; }
    .calendar-nav {
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 1rem;
    }
    .calendar-nav a {
      background-color: #93c5fd;
      color: #fff;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .calendar-nav a:hover {
      background-color: #60a5fa;
    }
    .calendar-title {
      font-size: 1.25rem;
      font-weight: bold;
    }
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
    }
    .week-header, .day {
      text-align: center; 
      padding: 0.75rem;
    }
    .week-header {
      font-weight: bold;
      background-color: #e2e8f0;
    }
    .day {
      border: 1px solid #e5e7eb;
      border-radius: 4px;
      min-height: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .day:hover {
      background-color: #f3f4f6;
    }
    .day.selected {
      background-color: #cbd5e1;
    }
    .day.today {
      border: 2px solid #6366f1;
    }
    /* 미래 일정 표시 */
    .day.future-makeup {
      background-color: #dbeafe;
    }
    .day.future-absence {
      background-color: #d1fae5;
    }

    /* 상태 카드 영역 */
    .status-container {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .status-card {
      flex: 1;
      background-color: #f1f5f9;
      padding: 1rem;
      border-radius: 4px;
      text-align: center;
      font-size: 1.1rem;
    }

    /* 폼 영역: 한 줄에 배치 */
    .form-row.single-line {
      display: flex;
      align-items: center;
      gap: 1rem; /* 버튼 사이 간격 */
      margin-bottom: 1rem;
      flex-wrap: wrap; /* 화면 좁을 때 줄바꿈 */
    }

    /* 시간 선택 버튼 스타일 */
    .time-selection {
      display: flex; 
      flex-wrap: wrap; 
      gap: 0.5rem;
    }
    .time-option {
      padding: 0.5rem;
      border: 1px solid #d1d5db;
      background-color: #fff;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.2s;
      min-width: 50px;
      text-align: center;
      font-size: 0.9rem;
    }
    .time-option:hover {
      background-color: #f3f4f6;
    }
    .time-option.selected {
      background-color: #cbd5e1;
    }

    /* 버튼 스타일 */
    .btn {
      display: inline-block;
      padding: 0.5rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-align: center;
      transition: background-color 0.3s;
      font-size: 0.9rem;
    }
    .btn-absence {
      background-color: #f97316; 
      color: #fff;
    }
    .btn-absence:hover {
      background-color: #ea580c;
    }
    .btn-makeup {
      background-color: #14b8a6; 
      color: #fff;
    }
    .btn-makeup:hover {
      background-color: #0d9488;
    }

    /* 폭 줄이기 */
    .narrow-btn {
      width: 60px; /* 필요시 조정 */
    }

    .btn-reschedule {
      background-color: #34d399; 
      color: #fff; 
      border: none; 
      padding: 0.5rem; 
      margin-left: 1rem; 
      border-radius: 4px; 
      cursor: pointer;
    }

    /* 삭제 버튼 스타일 */
    .delete-btn {
      background-color: #ef4444;
      color: #fff;
      border: none;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.8rem;
    }

    /* 기록 리스트 스타일 */
    .record {
      padding: 5px;
      border-radius: 4px;
      margin-bottom: 5px;
    }
    .record.absence {
      background-color: #d4edda;
    }
    .record.makeup {
      background-color: #cffafe;
    }
    .record-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.95rem;
    }
    .group-header {
      font-weight: bold;
      margin-top: 1rem;
      margin-bottom: 0.5rem;
      border-bottom: 1px solid #d1d5db;
      padding-bottom: 3px;
    }
    .toggle-link {
      display: block;
      text-align: center;
      margin-top: 1rem;
      color: #4b5563;
      text-decoration: none;
    }
    .toggle-link:hover {
      text-decoration: underline;
    }
    
    /* 학생 아이콘 스타일 */
    .student-icon {
      display: inline-block;
      background-color: #3b82f6;
      color: #fff;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      text-align: center;
      line-height: 18px;
      font-size: 12px;
      font-weight: bold;
      margin-left: 5px;
      vertical-align: middle;
    }
  </style>
  <script>
    // 토스트 알림 함수
    function showToast(message, type = 'success', title = '') {
      // 토스트 컨테이너가 없으면 생성
      let container = document.querySelector('.toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
      }
      
      // 토스트 요소 생성
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      
      const icon = type === 'error' ? '✕' : '✓';
      const defaultTitle = type === 'error' ? '오류' : '성공';
      
      toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
          <div class="toast-title">${title || defaultTitle}</div>
          <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this)">×</button>
      `;
      
      container.appendChild(toast);
      
      // 애니메이션 시작
      setTimeout(() => {
        toast.classList.add('show');
      }, 100);
      
      // 자동 제거 (5초 후)
      setTimeout(() => {
        closeToast(toast.querySelector('.toast-close'));
      }, 5000);
    }
    
    function closeToast(button) {
      const toast = button.closest('.toast');
      toast.classList.remove('show');
      setTimeout(() => {
        toast.remove();
      }, 300);
    }

    // 달력에서 날짜 선택
    function selectDate(day, year, month) {
      if(day === '' || !day) return;
      
      // 입력값 검증
      if (isNaN(day) || isNaN(year) || isNaN(month)) {
        console.error('잘못된 날짜 값입니다.');
        return;
      }
      
      try {
        var selectedDateElement = document.getElementById('selectedDate');
        if (!selectedDateElement) {
          console.error('selectedDate 요소를 찾을 수 없습니다.');
          return;
        }
        
        selectedDateElement.value = 
          year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        
        // 기존 선택 해제
        var days = document.getElementsByClassName('day');
        for(var i = 0; i < days.length; i++){
            days[i].classList.remove('selected');
        }
        
        // 새로운 날짜 선택
        var dayElement = document.getElementById('day-' + day);
        if (dayElement) {
          dayElement.classList.add('selected');
        }
      } catch (error) {
        console.error('날짜 선택 중 오류가 발생했습니다:', error);
      }
    }

    // 시간 선택 버튼 클릭 시
    function selectHours(hours, e) {
      if (!e || !e.target) {
        console.error('잘못된 이벤트 객체입니다.');
        return;
      }
      
      // 입력값 검증
      if (isNaN(hours) || hours <= 0 || hours > 6) {
        console.error('잘못된 시간 값입니다:', hours);
        return;
      }
      
      try {
        // hidden input에 시간 값 저장
        var selectedHoursElement = document.getElementById('selectedHours');
        if (!selectedHoursElement) {
          console.error('selectedHours 요소를 찾을 수 없습니다.');
          return;
        }
        selectedHoursElement.value = hours;

        // 모든 시간 버튼에서 selected 해제
        var btns = document.getElementsByClassName('time-option');
        for(var i = 0; i < btns.length; i++){
            btns[i].classList.remove('selected');
        }
        
        // 클릭된 버튼에 selected 추가
        e.target.classList.add('selected');
      } catch (error) {
        console.error('시간 선택 중 오류가 발생했습니다:', error);
      }
    }

    // 폼 제출 전 검증
    function validateForm() {
      var selectedDate = document.getElementById('selectedDate').value;
      var selectedHours = document.getElementById('selectedHours').value;
      
      if (!selectedDate) {
        showToast('날짜를 선택해주세요.', 'error', '입력 오류');
        return false;
      }
      
      if (!selectedHours) {
        showToast('시간을 선택해주세요.', 'error', '입력 오류');
        return false;
      }
      
      return true;
    }

    // 수강일 조정 대상 버튼 클릭 시
    function reschedule() {
      try {
        if (confirm("수강일 조정을 요청하시겠습니까?")) {
          showToast("부모님과 상의결과에 따라 수강일이 조정될 수 있습니다.", 'success', '조정 요청');
          var rescheduleButton = document.getElementById("rescheduleButton");
          if (rescheduleButton) {
            rescheduleButton.innerText = "조정 요청됨";
            rescheduleButton.disabled = true;
          }
        }
      } catch (error) {
        console.error('수강일 조정 요청 중 오류가 발생했습니다:', error);
      }
    }

    // 페이지 로드 시 초기화
    document.addEventListener('DOMContentLoaded', function() {
      // 폼에 제출 이벤트 리스너 추가
      var forms = document.querySelectorAll('form[method="post"]');
      forms.forEach(function(form) {
        // 삭제 폼이 아닌 경우에만 검증 적용
        if (!form.querySelector('input[name="delete_record_id"]')) {
          form.addEventListener('submit', function(e) {
            if (!validateForm()) {
              e.preventDefault();
            }
          });
        }
      });
    });
  </script>
</head>
<body>
  <div class="container"> 
    <?php if(isset($error)): ?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          showToast('<?php echo addslashes($error); ?>', 'error', '오류');
        });
      </script>
    <?php endif; ?>
    <?php if(isset($success)): ?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          showToast('<?php echo addslashes($success); ?>', 'success', '성공');
        });
      </script>
    <?php endif; ?>

    <!-- 달력 영역 -->
    <div class="calendar">
      <div class="calendar-nav">
        <a href="?userid=<?php echo urlencode($studentid); ?>&year=<?php echo urlencode($prevYear); ?>&month=<?php echo urlencode($prevMonth); ?>">◀</a>
        <div class="calendar-title">
          <table align="center">
            <tr>
              <td>수업 출결 관리 : <?php echo htmlspecialchars($stdname, ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($displayYear . "년 " . ($displayMonth + 1) . "월", ENT_QUOTES, 'UTF-8'); ?></td>
              <td>&nbsp;&nbsp;&nbsp;
                <audio controls style="width:200px;height:20px;" 
                       src="https://mathking.kr/moodle/local/augmented_teacher/LLM/audiofiles/458772de-e490-414c-9945-bb4075b6f0ec.wav">
                </audio>
              </td>
            </tr>
          </table>
        </div>
        <a href="?userid=<?php echo urlencode($studentid); ?>&year=<?php echo urlencode($nextYear); ?>&month=<?php echo urlencode($nextMonth); ?>">▶</a>
      </div>
      <div class="calendar-grid">
        <?php 
        $weekDays = array('월', '화', '수', '목', '금', '토', '일');
        foreach ($weekDays as $day) {
            echo "<div class='week-header'>" . htmlspecialchars($day, ENT_QUOTES, 'UTF-8') . "</div>";
        }
        foreach ($calendarDays as $day) {
            if ($day['isCurrentMonth']) {
                $dayNum = intval($day['date']); // 숫자 검증
                $classes = "day";
                if (in_array($dayNum, $futureAbsenceDays)) { 
                    $classes .= " future-absence"; 
                } elseif (in_array($dayNum, $futureMakeupDays)) { 
                    $classes .= " future-makeup"; 
                }
                if (isset($day['isToday']) && $day['isToday']) {
                  $classes .= " today";
                }
                echo "<div id='day-{$dayNum}' class='" . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . "' 
                      onclick='selectDate({$dayNum}, " . intval($displayYear) . ", " . intval($displayMonth) . ")'>
                      " . htmlspecialchars($dayNum, ENT_QUOTES, 'UTF-8') . "
                      </div>";
            } else {
                echo "<div class='day'></div>";
            }
        }
        ?>
      </div>
    </div>

    <!-- 상태 카드 (최근 3주 기준) -->
    <?php
      if ($neededMakeup > 0) {
        echo '<div class="status-container">';
        echo '<div class="status-card">보강예정 ' . htmlspecialchars($totalAbsence - $pastMakeup, ENT_QUOTES, 'UTF-8') . '시간</div>';
        echo '<div class="status-card" style="background-color: #fee2e2;">보강시간 필요 : ' . htmlspecialchars($neededMakeup, ENT_QUOTES, 'UTF-8') . '시간';
        if ($neededMakeup > 8) {
          // '데스크로 전달' -> '수강일 조정 대상' 으로 버튼 이름 변경
          echo ' <button id="rescheduleButton" class="btn-reschedule" onclick="reschedule()">수강일 조정 대상</button>';
        }
        echo '</div>';
        echo '</div>';
      } else {
        echo '<div class="status-card" style="width: 100%;">남은 보강 ' . htmlspecialchars($totalAbsence - $pastMakeup, ENT_QUOTES, 'UTF-8') . '시간</div>';
      }
    ?>

    <!-- 출결 관리 폼: 한 줄에 시간 버튼 + 휴강/보강 버튼 배치 -->
    <form method="post">
      <input type="hidden" id="selectedDate" name="selectedDate" value="">
      <input type="hidden" id="selectedHours" name="selectedHours" value="">

      <div class="form-row single-line">
        <!-- 시간 선택 버튼들 -->
        <div class="time-selection">
          <?php foreach ($timeOptions as $hours): ?>
            <button type="button" 
                    class="time-option" 
                    onclick="selectHours(<?php echo $hours; ?>, event)">
              <?php 
                // 정수면 정수만, 아니면 소수로 표시
                echo ($hours == floor($hours)) ? (int)$hours : $hours; 
              ?>
            </button>
          <?php endforeach; ?>
        </div>

        <!-- '휴강' 버튼 -->
        <button type="submit" 
                name="action" 
                value="absence" 
                class="btn btn-absence narrow-btn">
          휴강
        </button>

        <!-- '보강' 버튼 -->
        <button type="submit" 
                name="action" 
                value="makeup" 
                class="btn btn-makeup narrow-btn">
          보강
        </button>
      </div>
    </form>

    <!-- 하단 기록 리스트: 예정 기록과 지난 기록 구분, 삭제 버튼 포함 -->
    <div>
      <?php 
      $upcomingRecords = array();
      $pastRecords = array();
      foreach ($recordsToShow as $record) {
          if (date("Y-m-d", $record->due) > date("Y-m-d")) {
              $upcomingRecords[] = $record;
          } else {
              $pastRecords[] = $record;
          }
      }
      if(count($upcomingRecords) > 0){
          echo "<div class='group-header'>예정 기록</div>";
          foreach ($upcomingRecords as $record) {
              $recordDate = date("m-d", $record->timecreated);
              $eventText = ($record->event == 'absence') ? '휴강' : '보강';
              $recordClass = ($record->event == 'absence') ? 'record absence' : 'record makeup';
              $displayStatus = (date("Y-m-d", $record->due) < date("Y-m-d") ? "완료" : "대기");
              
              // 학생이 작성한 기록인지 확인
              $studentIcon = '';
              if (isset($record->role) && $record->role === 'student') {
                  $studentIcon = '<span class="student-icon">학</span>';
              }
              
              echo "<div class='{$recordClass}'>";
              echo "<div class='record-top'>";
              echo "<span class='record-date'>{$recordDate}</span>";
              echo "<span class='record-event'>{$eventText}{$studentIcon}</span>";
              echo "<span class='record-hours'>{$record->amount}시간</span>";
              echo "<span class='record-status'>{$displayStatus}</span>";
              echo "<span class='record-due'>실행 날짜 : " . date("m-d", $record->due) . "</span>";
              // 개선된 권한 체크 함수 사용
              if (checkUserPermission($role, $record->timecreated, time(), $USER->id, $studentid)) {
                  echo '<form method="post" style="display:inline; margin-left:10px;">';
                  echo '<input type="hidden" name="delete_record_id" value="' . htmlspecialchars($record->id) . '">';
                  echo '<button type="submit" name="action" value="delete" class="delete-btn" onclick="return confirm(\'정말 삭제하시겠습니까?\');">삭제</button>';
                  echo '</form>';
              }
              echo "</div>";
              echo "</div>";
          }
      }
      if(count($pastRecords) > 0){
          echo "<div class='group-header'>지난 기록</div>";
          foreach ($pastRecords as $record) {
              $recordDate = date("m-d", $record->timecreated);
              $eventText = ($record->event == 'absence') ? '휴강' : '보강';
              $recordClass = ($record->event == 'absence') ? 'record absence' : 'record makeup';
              $displayStatus = (date("Y-m-d", $record->due) < date("Y-m-d") ? "완료" : "대기");
              
              // 학생이 작성한 기록인지 확인
              $studentIcon = '';
              if (isset($record->role) && $record->role === 'student') {
                  $studentIcon = '<span class="student-icon">학</span>';
              }
              
              echo "<div class='{$recordClass}'>";
              echo "<div class='record-top'>";
              echo "<span class='record-date'>{$recordDate}</span>";
              echo "<span class='record-event'>{$eventText}{$studentIcon}</span>";
              echo "<span class='record-hours'>{$record->amount}시간</span>";
              echo "<span class='record-status'>{$displayStatus}</span>";
              echo "<span class='record-due'>실행 날짜 : " . date("m-d", $record->due) . "</span>";
              // 개선된 권한 체크 함수 사용
              if (checkUserPermission($role, $record->timecreated, time(), $USER->id, $studentid)) {
                  echo '<form method="post" style="display:inline; margin-left:10px;">';
                  echo '<input type="hidden" name="delete_record_id" value="' . htmlspecialchars($record->id) . '">';
                  echo '<button type="submit" name="action" value="delete" class="delete-btn" onclick="return confirm(\'정말 삭제하시겠습니까?\');">삭제</button>';
                  echo '</form>';
              }
              echo "</div>";
              echo "</div>";
          }
      }
      if (count($notificationsArr) > 5) {
          $toggleUrl = "?userid=" . urlencode($studentid) . "&year=" . urlencode($displayYear) . "&month=" . urlencode($displayMonth + 1) . "&showAll=" . ($showAll ? "0" : "1");
          echo "<a href='{$toggleUrl}' class='toggle-link'>" . ($showAll ? "접기" : "더보기") . "</a>";
      }
      ?>
    </div>

    <!-- 삭제된 기록 목록 -->
    <?php 
    if ($deletedRecords) {
        echo "<div class='group-header'>삭제된 기록</div>";
        foreach ($deletedRecords as $record) {
            $recordDate = date("m-d", $record->timecreated);
            $eventText = ($record->event == 'absence') ? '휴강' : '보강';
            $recordClass = ($record->event == 'absence') ? 'record absence' : 'record makeup';
            
            // 학생이 작성한 기록인지 확인
            $studentIcon = '';
            if (isset($record->role) && $record->role === 'student') {
                $studentIcon = '<span class="student-icon">학</span>';
            }
            
            echo "<div class='{$recordClass}'>";
            echo "<div class='record-top'>";
            echo "<span class='record-date'>{$recordDate}</span>";
            echo "<span class='record-event'>{$eventText}{$studentIcon}</span>";
            echo "<span class='record-hours'>{$record->amount}시간</span>";
            echo "<span class='record-due'>실행 날짜 : " . date("m-d", $record->due) . "</span>";
            echo "</div>";
            echo "</div>";
        }
    }
    ?>
  </div>
</body>
</html>
