<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? 817;

echo "<h2>API 응답 테스트</h2>";

// 1. get_student_messages.php 테스트
echo "<h3>1. get_student_messages.php 테스트</h3>";
$_GET['studentid'] = $studentid;
$_GET['page'] = 0;
$_GET['perpage'] = 20;

ob_start();
include('get_student_messages.php');
$response = ob_get_clean();

echo "<h4>API 응답:</h4>";
echo "<pre>";
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    print_r($decoded);
} else {
    echo "JSON 파싱 오류: " . json_last_error_msg() . "\n";
    echo htmlspecialchars($response);
}
echo "</pre>";

// 2. 직접 데이터베이스 확인
echo "<h3>2. 데이터베이스 직접 확인</h3>";
$sql = "SELECT * FROM {ktm_teaching_interactions} 
        WHERE userid = :studentid 
        AND (status = 'completed' OR status = 'sent') 
        AND solution_text IS NOT NULL 
        ORDER BY timecreated DESC
        LIMIT 5";

$interactions = $DB->get_records_sql($sql, array('studentid' => $studentid));
echo "<p>찾은 상호작용: " . count($interactions) . "개</p>";

if ($interactions) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Teacher</th><th>Status</th><th>Solution</th><th>Created</th></tr>";
    foreach ($interactions as $int) {
        echo "<tr>";
        echo "<td>{$int->id}</td>";
        echo "<td>{$int->teacherid}</td>";
        echo "<td>{$int->status}</td>";
        echo "<td>" . (empty($int->solution_text) ? 'EMPTY' : 'EXISTS') . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', $int->timecreated) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. get_sent_requests.php 테스트
echo "<h3>3. get_sent_requests.php 테스트</h3>";
unset($_GET['page']);
unset($_GET['perpage']);

ob_start();
include('get_sent_requests.php');
$response2 = ob_get_clean();

echo "<h4>API 응답:</h4>";
echo "<pre>";
$decoded2 = json_decode($response2, true);
if (json_last_error() === JSON_ERROR_NONE) {
    print_r($decoded2);
} else {
    echo "JSON 파싱 오류: " . json_last_error_msg() . "\n";
    echo htmlspecialchars($response2);
}
echo "</pre>";

// 4. 브라우저 콘솔용 테스트 코드
echo "<h3>4. 브라우저 콘솔 테스트</h3>";
echo "<p>아래 코드를 브라우저 콘솔에서 실행해보세요:</p>";
echo "<pre>";
echo "fetch('get_student_messages.php?studentid=$studentid&page=0&perpage=20')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));";
echo "</pre>";

echo "<hr>";
echo "<p><a href='student_inbox.php?studentid=$studentid&userid=2'>학생 메시지함으로 돌아가기</a></p>";
?>