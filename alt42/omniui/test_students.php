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

echo "<h1>학생 데이터 테스트</h1>";

// 1. 학생 수 확인
$count = $DB->count_records_sql("SELECT COUNT(*) FROM mdl_user u 
                                 INNER JOIN mdl_user_info_data uid ON u.id = uid.userid 
                                 WHERE uid.fieldid = 22 AND uid.data = 'student' 
                                 AND u.deleted = 0 AND u.suspended = 0");
echo "<p>전체 학생 수: $count</p>";

// 2. 처음 10명의 학생 정보
$sql = "SELECT u.id, u.firstname, u.lastname, u.email
        FROM mdl_user u
        INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
        WHERE uid.fieldid = 22 AND uid.data = 'student'
        AND u.deleted = 0 AND u.suspended = 0
        ORDER BY u.firstname ASC
        LIMIT 10";

$students = $DB->get_records_sql($sql);

echo "<h2>처음 10명의 학생:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>이름</th><th>성</th><th>전체 이름</th><th>이메일</th></tr>";

if ($students) {
    foreach ($students as $student) {
        $fullname = $student->firstname . ' ' . $student->lastname;
        echo "<tr>";
        echo "<td>{$student->id}</td>";
        echo "<td>{$student->firstname}</td>";
        echo "<td>{$student->lastname}</td>";
        echo "<td>{$fullname}</td>";
        echo "<td>{$student->email}</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>학생 데이터가 없습니다</td></tr>";
}

echo "</table>";

// 3. AJAX 엔드포인트 테스트
echo "<h2>AJAX 엔드포인트 직접 테스트</h2>";
echo "<p><a href='attendance_teacher.php?ajax=students' target='_blank'>학생 목록 JSON</a></p>";
echo "<p><a href='attendance_teacher.php?ajax=alerts' target='_blank'>알림 JSON</a></p>";

// 3-1. JavaScript로 AJAX 테스트
echo '<h3>JavaScript AJAX 테스트</h3>';
echo '<button onclick="testStudents()">학생 목록 테스트</button>';
echo '<div id="result" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; background: #f5f5f5;"></div>';
echo '<script>
function testStudents() {
    const resultDiv = document.getElementById("result");
    resultDiv.innerHTML = "로딩중...";
    
    fetch("attendance_teacher.php?ajax=students")
        .then(response => {
            console.log("Response status:", response.status);
            console.log("Response headers:", response.headers);
            return response.text();
        })
        .then(text => {
            console.log("Raw response:", text);
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML = "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
            } catch(e) {
                resultDiv.innerHTML = "JSON 파싱 실패:<br>" + text;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = "Error: " + error;
            console.error("Fetch error:", error);
        });
}
</script>';

// 4. 데이터베이스 연결 정보
echo "<h2>데이터베이스 정보</h2>";
echo "<p>Host: " . $CFG->dbhost . "</p>";
echo "<p>Database: " . $CFG->dbname . "</p>";
echo "<p>User: " . $CFG->dbuser . "</p>";
echo "<p>Table Prefix: " . $CFG->prefix . "</p>";

// 5. 현재 사용자 정보
echo "<h2>현재 사용자 정보</h2>";
echo "<p>ID: {$USER->id}</p>";
echo "<p>Username: {$USER->username}</p>";
echo "<p>Name: {$USER->firstname} {$USER->lastname}</p>";
echo "<p>Lastname: '{$USER->lastname}'</p>";
echo "<p>교사 여부: " . ($isTeacher ? "예" : "아니오") . "</p>";
?>