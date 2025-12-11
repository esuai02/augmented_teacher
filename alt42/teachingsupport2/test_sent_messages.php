<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = 817;

// 테스트: 학생이 보낸 요청 확인
echo "<h2>학생 ID $studentid 의 보낸 메시지 테스트</h2>";

$sql = "SELECT ti.*, u.firstname, u.lastname
        FROM {ktm_teaching_interactions} ti
        LEFT JOIN {user} u ON ti.teacherid = u.id
        WHERE ti.userid = ?
        ORDER BY ti.timecreated DESC
        LIMIT 10";

$requests = $DB->get_records_sql($sql, array($studentid));

echo "<h3>찾은 요청: " . count($requests) . "개</h3>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>선생님</th><th>상태</th><th>문제유형</th><th>이미지</th><th>해설</th><th>생성시간</th></tr>";

foreach ($requests as $req) {
    $teacher_name = $req->teacherid ? fullname($req) : '미지정';
    $has_image = !empty($req->problem_image) ? '✓' : '✗';
    $has_solution = !empty($req->solution_text) ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>{$req->id}</td>";
    echo "<td>{$teacher_name} (ID: {$req->teacherid})</td>";
    echo "<td>{$req->status}</td>";
    echo "<td>{$req->problem_type}</td>";
    echo "<td style='text-align:center;'>{$has_image}</td>";
    echo "<td style='text-align:center;'>{$has_solution}</td>";
    echo "<td>" . date('Y-m-d H:i:s', $req->timecreated) . "</td>";
    echo "</tr>";
}

echo "</table>";

// API 테스트
echo "<h3>API 응답 테스트:</h3>";
echo "<pre>";
$api_url = "https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/get_sent_requests.php?studentid=$studentid";
echo "API URL: $api_url\n";
echo "</pre>";

echo "<p><a href='student_inbox.php?studentid=$studentid&userid=2'>학생 메시지함으로 이동</a></p>";
?>