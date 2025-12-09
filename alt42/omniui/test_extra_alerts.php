<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 교사 권한 확인
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
}

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"); 
$role = $userrole ? $userrole->role : 'student';
if ($role !== 'student') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다.</h2>");
}

// 교사 심볼 추출
$tsymbol = '';
if ($USER->firstname) {
    preg_match_all('/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{27BF}]/u', $USER->firstname, $matches);
    $emojis = $matches[0];
    
    if (count($emojis) > 0) {
        $tsymbol = $emojis[0];
    } else {
        $teacherId = $USER->id;
        $symbols = array('🌟', '⭐', '✨', '🎯', '🔥', '💫', '🌈', '🎨', '🎪', '🎭');
        $symbolIndex = $teacherId % count($symbols);
        $tsymbol = $symbols[$symbolIndex];
    }
}

$threeWeeksAgo = strtotime("-3 weeks");

// calculateAttendanceHours 함수 정의 (attendance_teacher.php와 동일)
function calculateAttendanceHours($DB, $studentid, $threeWeeksAgo, $skipExtraStudy = false) {
    $sqlCombined = "SELECT 
                        event,
                        SUM(amount) as total_amount,
                        SUM(CASE WHEN due < ? THEN amount ELSE 0 END) as past_amount,
                        SUM(CASE WHEN due >= ? THEN amount ELSE 0 END) as future_amount
                    FROM {abessi_classtimemanagement} 
                    WHERE userid = ? AND hide = 0 AND due >= ?
                    GROUP BY event";
    
    $currentTime = time();
    $records = $DB->get_records_sql($sqlCombined, array($currentTime, $currentTime, $studentid, $threeWeeksAgo));
    
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
    
    $extraStudyHours = 0;
    
    if (!$skipExtraStudy) {
        $schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule 
                                         WHERE userid = ? AND pinned = 1 
                                         ORDER BY id DESC LIMIT 1", array($studentid));
        
        if ($schedule) {
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
            
            if ($studyRecords) {
                foreach ($studyRecords as $record) {
                    $dayOfWeek = date('w', strtotime($record->study_date));
                    
                    if ($dayOfWeek == 0) {
                        $duration_field = 'duration7';
                    } else {
                        $duration_field = 'duration' . $dayOfWeek;
                    }
                    
                    $regularHours = isset($schedule->$duration_field) ? floatval($schedule->$duration_field) : 0;
                    
                    $actualStudyHours = 0;
                    if ($record->log_count > 1) {
                        $actualStudyHours = ($record->last_time - $record->first_time) / 3600;
                    }
                    
                    if ($regularHours > 0 && $actualStudyHours > ($regularHours + 1)) {
                        $extraHoursForDay = $actualStudyHours - $regularHours;
                        $extraStudyHours += $extraHoursForDay;
                    }
                }
            }
        }
    }
    
    $neededMakeup = $totalAbsence - ($pastMakeup + $futureMakeup + $extraStudyHours);
    
    return array(
        'totalAbsence' => $totalAbsence,
        'pastMakeup' => $pastMakeup,
        'futureMakeup' => $futureMakeup,
        'extraStudyHours' => round($extraStudyHours, 1),
        'neededMakeup' => round($neededMakeup, 1)
    );
}

echo "<h1>초과 학습 시간 알림 테스트</h1>";
echo "<p>교사: {$USER->firstname} {$USER->lastname}</p>";
echo "<p>담당 심볼: $tsymbol</p>";

// 1. 시간표가 있는 담당 학생 찾기
echo "<h2>1. 시간표가 있는 담당 학생 목록</h2>";

$sqlStudents = "SELECT DISTINCT u.id, u.firstname, u.lastname, s.id as schedule_id
                FROM mdl_user u
                INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                INNER JOIN mdl_abessi_schedule s ON u.id = s.userid AND s.pinned = 1
                WHERE uid.fieldid = 22 AND uid.data = 'student'
                AND u.deleted = 0 AND u.suspended = 0";

$params = array();
if (!empty($tsymbol)) {
    $sqlStudents .= " AND (u.firstname LIKE ? OR u.firstname LIKE ?)";
    $params[] = '%' . $tsymbol . '%';
    $params[] = '%' . $tsymbol . '%';
}

$sqlStudents .= " LIMIT 20";

$students = $DB->get_records_sql($sqlStudents, $params);

if ($students) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>이름</th><th>시간표ID</th><th>초과학습</th><th>보강필요</th></tr>";
    
    $extraStudyCount = 0;
    foreach ($students as $student) {
        $attendanceData = calculateAttendanceHours($DB, $student->id, $threeWeeksAgo, false);
        
        $bgColor = '';
        if ($attendanceData['extraStudyHours'] > 0) {
            $bgColor = 'style="background: #e0f2fe;"';
            $extraStudyCount++;
        }
        
        echo "<tr $bgColor>";
        echo "<td>{$student->id}</td>";
        echo "<td>{$student->firstname} {$student->lastname}</td>";
        echo "<td>{$student->schedule_id}</td>";
        echo "<td><strong>" . $attendanceData['extraStudyHours'] . "h</strong></td>";
        echo "<td>" . $attendanceData['neededMakeup'] . "h</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>총 " . count($students) . "명 중 <strong>$extraStudyCount</strong>명이 초과 학습 시간이 있습니다.</p>";
} else {
    echo "<p>시간표가 있는 담당 학생이 없습니다.</p>";
}

// 2. AJAX 알림 테스트
echo "<h2>2. AJAX 알림 테스트</h2>";
echo "<button onclick='testAlerts()'>알림 데이터 로드</button>";
echo "<div id='alert-result' style='border: 1px solid #ccc; padding: 10px; margin-top: 10px; background: #f5f5f5;'></div>";

// 3. 특정 학생의 상세 초과 학습 기록
if (isset($_GET['student_id'])) {
    $studentId = intval($_GET['student_id']);
    $student = $DB->get_record('user', array('id' => $studentId));
    
    if ($student) {
        echo "<h2>3. {$student->firstname} {$student->lastname} 학생의 초과 학습 상세</h2>";
        
        $schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule 
                                         WHERE userid = ? AND pinned = 1 
                                         ORDER BY id DESC LIMIT 1", array($studentId));
        
        if ($schedule) {
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
                         GROUP BY DATE(FROM_UNIXTIME(timecreated))
                         ORDER BY study_date DESC";
            
            $studyRecords = $DB->get_records_sql($sqlStudy, array($studentId, $startDate, $endDate));
            
            if ($studyRecords) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>날짜</th><th>요일</th><th>정규시간</th><th>실제공부</th><th>초과인정</th></tr>";
                
                $totalExtra = 0;
                foreach ($studyRecords as $record) {
                    $dayOfWeek = date('w', strtotime($record->study_date));
                    $dayName = array('일', '월', '화', '수', '목', '금', '토')[$dayOfWeek];
                    
                    $duration_field = $dayOfWeek == 0 ? 'duration7' : 'duration' . $dayOfWeek;
                    $regularHours = floatval($schedule->$duration_field);
                    
                    $actualStudyHours = 0;
                    if ($record->log_count > 1) {
                        $actualStudyHours = ($record->last_time - $record->first_time) / 3600;
                    }
                    
                    $extraHours = 0;
                    if ($regularHours > 0 && $actualStudyHours > ($regularHours + 1)) {
                        $extraHours = $actualStudyHours - $regularHours;
                        $totalExtra += $extraHours;
                    }
                    
                    $bgColor = $extraHours > 0 ? 'style="background: #e0f2fe;"' : '';
                    
                    echo "<tr $bgColor>";
                    echo "<td>{$record->study_date}</td>";
                    echo "<td>$dayName</td>";
                    echo "<td>" . round($regularHours, 1) . "h</td>";
                    echo "<td>" . round($actualStudyHours, 1) . "h</td>";
                    echo "<td><strong>" . round($extraHours, 1) . "h</strong></td>";
                    echo "</tr>";
                }
                echo "<tr style='background: #f0f0f0;'>";
                echo "<td colspan='4'><strong>총 초과 학습</strong></td>";
                echo "<td><strong>" . round($totalExtra, 1) . "h</strong></td>";
                echo "</tr>";
                echo "</table>";
            }
        }
    }
}
?>

<script>
function testAlerts() {
    const resultDiv = document.getElementById('alert-result');
    resultDiv.innerHTML = 'Loading...';
    
    fetch('attendance_teacher.php?ajax=alerts')
        .then(response => {
            console.log('Response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Alert data:', data);
            
            let html = '<h3>알림 데이터 (' + data.length + '개)</h3>';
            
            if (data.length > 0) {
                html += '<table border="1" cellpadding="5" style="width: 100%;">';
                html += '<tr><th>이름</th><th>타입</th><th>시간</th><th>설명</th></tr>';
                
                data.forEach(alert => {
                    let type = '';
                    let bgColor = '';
                    
                    if (alert.type === 'makeup_needed') {
                        type = '보강 필요';
                        bgColor = '#fee2e2';
                    } else if (alert.type === 'extra_study') {
                        type = '초과 학습';
                        bgColor = '#e0f2fe';
                    } else if (alert.type === 'surplus_study') {
                        type = '추가 학습';
                        bgColor = '#dcfce7';
                    }
                    
                    html += `<tr style="background: ${bgColor};">`;
                    html += `<td>${alert.name}</td>`;
                    html += `<td>${type}</td>`;
                    html += `<td><strong>${alert.hours}h</strong></td>`;
                    html += `<td>${type === '초과 학습' ? '우수한 학습 성과' : type === '보강 필요' ? '보강 수업 필요' : '여유 시간 있음'}</td>`;
                    html += '</tr>';
                });
                
                html += '</table>';
            } else {
                html += '<p>알림이 없습니다.</p>';
            }
            
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = 'Error: ' + error.message;
        });
}
</script>