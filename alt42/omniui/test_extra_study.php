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

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다.</h2>");
}

$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

echo "<h1>초과 학습 시간 테스트</h1>";

if ($studentid > 0) {
    $student = $DB->get_record('user', array('id' => $studentid));
    if ($student) {
        echo "<h2>{$student->firstname} {$student->lastname} 학생</h2>";
        
        // 시간표 확인
        echo "<h3>시간표 정보</h3>";
        $schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule 
                                         WHERE userid = ? AND pinned = 1 
                                         ORDER BY id DESC LIMIT 1", array($studentid));
        
        if ($schedule) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>요일</th><th>수업 시간</th></tr>";
            echo "<tr><td>월요일</td><td>{$schedule->duration1}시간</td></tr>";
            echo "<tr><td>화요일</td><td>{$schedule->duration2}시간</td></tr>";
            echo "<tr><td>수요일</td><td>{$schedule->duration3}시간</td></tr>";
            echo "<tr><td>목요일</td><td>{$schedule->duration4}시간</td></tr>";
            echo "<tr><td>금요일</td><td>{$schedule->duration5}시간</td></tr>";
            echo "<tr><td>토요일</td><td>{$schedule->duration6}시간</td></tr>";
            echo "<tr><td>일요일</td><td>{$schedule->duration7}시간</td></tr>";
            echo "</table>";
            
            // 최근 3주간 학습 기록
            echo "<h3>최근 3주간 학습 기록</h3>";
            $threeWeeksAgo = strtotime("-3 weeks");
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
                         ORDER BY study_date DESC
                         LIMIT 20";
            
            $studyRecords = $DB->get_records_sql($sqlStudy, array($studentid, $startDate, $endDate));
            
            if ($studyRecords) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>날짜</th><th>요일</th><th>정규시간</th><th>실제공부</th><th>초과인정</th><th>시작</th><th>종료</th></tr>";
                
                $totalExtraHours = 0;
                foreach ($studyRecords as $record) {
                    $dayOfWeek = date('w', strtotime($record->study_date));
                    $dayName = array('일', '월', '화', '수', '목', '금', '토')[$dayOfWeek];
                    
                    // 해당 요일의 정규 수업 시간
                    if ($dayOfWeek == 0) {
                        $duration_field = 'duration7';
                    } else {
                        $duration_field = 'duration' . $dayOfWeek;
                    }
                    
                    $regularHours = isset($schedule->$duration_field) ? floatval($schedule->$duration_field) : 0;
                    
                    // 실제 공부 시간
                    $actualStudyHours = 0;
                    if ($record->log_count > 1) {
                        $actualStudyHours = ($record->last_time - $record->first_time) / 3600;
                    }
                    
                    // 초과 학습 인정 (정규 시간보다 1시간 이상 더 공부)
                    $extraHours = 0;
                    $extraText = '-';
                    if ($regularHours > 0 && $actualStudyHours > ($regularHours + 1)) {
                        $extraHours = $actualStudyHours - $regularHours;
                        $totalExtraHours += $extraHours;
                        $extraText = round($extraHours, 1) . 'h';
                    }
                    
                    $bgColor = $extraHours > 0 ? 'style="background: #e0f2fe;"' : '';
                    
                    echo "<tr $bgColor>";
                    echo "<td>{$record->study_date}</td>";
                    echo "<td>{$dayName}</td>";
                    echo "<td>" . round($regularHours, 1) . "h</td>";
                    echo "<td>" . round($actualStudyHours, 1) . "h</td>";
                    echo "<td><strong>{$extraText}</strong></td>";
                    echo "<td>" . date('H:i', $record->first_time) . "</td>";
                    echo "<td>" . date('H:i', $record->last_time) . "</td>";
                    echo "</tr>";
                }
                echo "<tr style='background: #f0f0f0;'>";
                echo "<td colspan='4'><strong>총 초과 학습 인정</strong></td>";
                echo "<td colspan='3'><strong>" . round($totalExtraHours, 1) . "시간</strong></td>";
                echo "</tr>";
                echo "</table>";
            } else {
                echo "<p>학습 기록이 없습니다.</p>";
            }
            
        } else {
            echo "<p style='color:red;'>시간표 정보가 없습니다.</p>";
        }
    }
} else {
    // 학생 목록
    echo "<h2>학생 선택</h2>";
    $sql = "SELECT u.id, u.firstname, u.lastname
            FROM mdl_user u
            INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
            WHERE uid.fieldid = 22 AND uid.data = 'student'
            AND u.deleted = 0 AND u.suspended = 0
            ORDER BY u.firstname ASC
            LIMIT 20";
    
    $students = $DB->get_records_sql($sql);
    
    if ($students) {
        echo "<ul>";
        foreach ($students as $student) {
            echo "<li><a href='?userid={$student->id}'>{$student->firstname} {$student->lastname}</a></li>";
        }
        echo "</ul>";
    }
}
?>