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

// 교사 심볼 추출 (attendance_teacher.php와 동일한 로직)
$tsymbol = '';
$tsymbol1 = '';
$tsymbol2 = '';
$tsymbol3 = '';

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
    
    $tsymbol1 = $tsymbol;
    $tsymbol2 = $tsymbol;
    $tsymbol3 = $tsymbol;
}

echo "<h1>알림 테스트</h1>";
echo "<p>교사: {$USER->firstname} {$USER->lastname}</p>";
echo "<p>담당 심볼: $tsymbol</p>";

$threeWeeksAgo = strtotime("-3 weeks");

// 1. 보강 필요 학생 확인
echo "<h2>1. 보강 필요/추가 학습 학생</h2>";

$alertParams = array($threeWeeksAgo, $threeWeeksAgo);

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
if (!empty($tsymbol)) {
    $sqlAlerts .= " AND (u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ? OR u.firstname LIKE ?)";
    $alertParams[] = '%' . $tsymbol . '%';
    $alertParams[] = '%' . $tsymbol1 . '%';
    $alertParams[] = '%' . $tsymbol2 . '%';
    $alertParams[] = '%' . $tsymbol3 . '%';
}

$sqlAlerts .= " HAVING needed >= 4 OR needed <= -5
               ORDER BY ABS(needed) DESC
               LIMIT 20";

echo "<h3>쿼리:</h3>";
echo "<pre>" . htmlspecialchars($sqlAlerts) . "</pre>";
echo "<h3>파라미터:</h3>";
echo "<pre>" . print_r($alertParams, true) . "</pre>";

try {
    $alertStudents = $DB->get_records_sql($sqlAlerts, $alertParams);
    
    if ($alertStudents) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>이름</th><th>결석</th><th>보강</th><th>필요</th><th>타입</th></tr>";
        
        foreach ($alertStudents as $student) {
            $type = $student->needed >= 4 ? '보강 필요' : '추가 학습';
            $bgColor = $student->needed >= 4 ? '#fee2e2' : '#dcfce7';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$student->id}</td>";
            echo "<td>{$student->firstname} {$student->lastname}</td>";
            echo "<td>" . round($student->total_absence, 1) . "</td>";
            echo "<td>" . round($student->total_makeup, 1) . "</td>";
            echo "<td><strong>" . round($student->needed, 1) . "</strong></td>";
            echo "<td>$type</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>총 " . count($alertStudents) . "명의 알림 대상</p>";
    } else {
        echo "<p>알림 대상 학생이 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
}

// 2. AJAX 엔드포인트 테스트
echo "<h2>2. AJAX 엔드포인트 테스트</h2>";
echo "<button onclick='testAlerts()'>알림 AJAX 테스트</button>";
echo "<div id='ajax-result' style='border: 1px solid #ccc; padding: 10px; margin-top: 10px; background: #f5f5f5;'></div>";

?>

<script>
function testAlerts() {
    const resultDiv = document.getElementById('ajax-result');
    resultDiv.innerHTML = 'Loading...';
    
    fetch('attendance_teacher.php?ajax=alerts')
        .then(response => {
            console.log('Response:', response);
            return response.text();
        })
        .then(text => {
            console.log('Raw text:', text);
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML = '<h4>JSON 결과:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch(e) {
                resultDiv.innerHTML = '<h4>원본 응답:</h4><pre>' + text + '</pre>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = 'Error: ' + error;
        });
}
</script>