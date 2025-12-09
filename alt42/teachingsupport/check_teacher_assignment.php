<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Teacher Assignment 확인</h2>";

// URL 파라미터 확인
$userid = optional_param('userid', 0, PARAM_INT);
echo "<p>URL userid parameter: $userid</p>";
echo "<p>현재 로그인 사용자 ID: {$USER->id}</p>";

// 최근 interactions 확인
echo "<h3>최근 10개 Interactions</h3>";
$recent = $DB->get_records_sql(
    "SELECT ti.*, u.firstname, u.lastname 
     FROM {ktm_teaching_interactions} ti
     LEFT JOIN {user} u ON ti.userid = u.id
     ORDER BY ti.timecreated DESC
     LIMIT 10"
);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>
      <th>ID</th>
      <th>학생ID</th>
      <th>선생님ID</th>
      <th>상태</th>
      <th>문제유형</th>
      <th>이미지</th>
      <th>해설</th>
      <th>추가요청</th>
      <th>생성시간</th>
      </tr>";

foreach ($recent as $r) {
    $highlightTeacher = ($r->teacherid == 2) ? 'background: #ffffcc;' : '';
    echo "<tr>";
    echo "<td>{$r->id}</td>";
    echo "<td>{$r->userid} (" . ($r->firstname ?? 'Unknown') . ")</td>";
    echo "<td style='$highlightTeacher'>" . ($r->teacherid ?: 'NULL') . "</td>";
    echo "<td style='color: " . ($r->status == 'pending' ? 'orange' : 'green') . "'>{$r->status}</td>";
    echo "<td>" . ($r->problem_type ?: '-') . "</td>";
    echo "<td>" . (!empty($r->problem_image) ? '✅' : '❌') . "</td>";
    echo "<td>" . (!empty($r->solution_text) ? '✅' : '❌') . "</td>";
    echo "<td>" . (!empty($r->modification_prompt) ? substr($r->modification_prompt, 0, 30) . '...' : '-') . "</td>";
    echo "<td>" . date('Y-m-d H:i:s', $r->timecreated) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 선생님별 통계
echo "<h3>선생님별 Pending 통계</h3>";
$teacher_stats = $DB->get_records_sql(
    "SELECT teacherid, COUNT(*) as count
     FROM {ktm_teaching_interactions}
     WHERE status = 'pending'
     GROUP BY teacherid
     ORDER BY teacherid"
);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Teacher ID</th><th>Pending Count</th></tr>";
foreach ($teacher_stats as $stat) {
    $tid = $stat->teacherid ?: 'NULL';
    echo "<tr><td>$tid</td><td>{$stat->count}</td></tr>";
}
echo "</table>";

// 특정 조건으로 필터링된 새 요청들
echo "<h3>get_new_requests.php 쿼리 결과 (teacherid=2)</h3>";
$sql = "SELECT ti.*, u.firstname, u.lastname 
        FROM {ktm_teaching_interactions} ti
        JOIN {user} u ON ti.userid = u.id
        WHERE (
            (ti.teacherid = :teacherid1 AND ti.status = 'pending')
            OR (ti.teacherid = 0 OR ti.teacherid IS NULL)
        )
        AND (ti.solution_text IS NULL OR ti.solution_text = '')
        AND ti.problem_image IS NOT NULL
        ORDER BY ti.timecreated DESC
        LIMIT 10";

$new_requests = $DB->get_records_sql($sql, array('teacherid1' => 2));
echo "<p>찾은 요청 수: " . count($new_requests) . "</p>";

if (count($new_requests) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #e8f5e9;'>
          <th>ID</th>
          <th>학생</th>
          <th>선생님ID</th>
          <th>문제유형</th>
          <th>추가요청</th>
          <th>이미지 미리보기</th>
          </tr>";
    
    foreach ($new_requests as $r) {
        echo "<tr>";
        echo "<td>{$r->id}</td>";
        echo "<td>" . fullname($r) . " ({$r->userid})</td>";
        echo "<td>" . ($r->teacherid ?: 'NULL') . "</td>";
        echo "<td>" . ($r->problem_type ?: '-') . "</td>";
        echo "<td>" . ($r->modification_prompt ?: '-') . "</td>";
        echo "<td>";
        if (!empty($r->problem_image)) {
            echo "<img src='{$r->problem_image}' style='max-width: 100px; max-height: 100px;'>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// AJAX 엔드포인트 테스트
echo "<h3>AJAX 엔드포인트 직접 테스트</h3>";
echo "<button onclick='testEndpoint(2)'>Teacher ID 2로 테스트</button>";
echo "<button onclick='testEndpoint(" . $USER->id . ")'>현재 사용자 ID로 테스트</button>";
echo "<pre id='ajax-result' style='background: #f5f5f5; padding: 10px; margin-top: 10px; border: 1px solid #ddd;'></pre>";

?>

<script>
function testEndpoint(teacherId) {
    const url = 'get_new_requests.php?teacherid=' + teacherId;
    document.getElementById('ajax-result').textContent = 'Loading from: ' + url + '...';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('ajax-result').textContent = JSON.stringify(data, null, 2);
        })
        .catch(error => {
            document.getElementById('ajax-result').textContent = 'Error: ' + error;
        });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th { background: #f0f0f0; padding: 8px; text-align: left; }
td { padding: 8px; border: 1px solid #ddd; }
h3 { background: #e3f2fd; padding: 10px; margin: 20px 0 10px 0; }
img { display: block; }
button { padding: 8px 16px; margin: 5px; cursor: pointer; }
</style>