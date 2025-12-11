<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=UTF-8');

$teacherid = optional_param('teacherid', 2, PARAM_INT);

echo "<h2>새로운 풀이요청 테스트</h2>";
echo "<p>Teacher ID: $teacherid</p>";

// 1. 최근 제출된 interactions 확인
echo "<h3>최근 제출된 데이터 (최근 5개)</h3>";
$recent = $DB->get_records_sql(
    "SELECT ti.*, u.firstname, u.lastname 
     FROM {ktm_teaching_interactions} ti
     JOIN {user} u ON ti.userid = u.id
     ORDER BY ti.timecreated DESC
     LIMIT 5"
);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>
      <th>ID</th><th>학생</th><th>선생님ID</th><th>문제유형</th>
      <th>이미지</th><th>추가요청</th><th>상태</th><th>생성시간</th>
      </tr>";

foreach ($recent as $r) {
    echo "<tr>";
    echo "<td>{$r->id}</td>";
    echo "<td>" . fullname($r) . " ({$r->userid})</td>";
    echo "<td style='color: " . ($r->teacherid == $teacherid ? 'green' : 'red') . "'>" . 
         ($r->teacherid ?: 'NULL') . "</td>";
    echo "<td>" . ($r->problem_type ?: '-') . "</td>";
    echo "<td>" . (!empty($r->problem_image) ? '✅' : '❌') . "</td>";
    echo "<td>" . ($r->modification_prompt ? '있음' : '-') . "</td>";
    echo "<td style='color: " . ($r->status == 'pending' ? 'orange' : 'green') . "'>{$r->status}</td>";
    echo "<td>" . date('Y-m-d H:i:s', $r->timecreated) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. get_new_requests.php 쿼리와 동일한 조건으로 검색
echo "<h3>새로운 풀이요청 (get_new_requests.php 쿼리)</h3>";
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

$new_requests = $DB->get_records_sql($sql, array('teacherid1' => $teacherid));
echo "<p>찾은 요청 수: " . count($new_requests) . "</p>";

if (count($new_requests) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #e8f5e9;'>
          <th>ID</th><th>학생</th><th>선생님ID</th><th>문제유형</th>
          <th>추가요청</th><th>생성시간</th>
          </tr>";
    
    foreach ($new_requests as $r) {
        echo "<tr>";
        echo "<td>{$r->id}</td>";
        echo "<td>" . fullname($r) . "</td>";
        echo "<td>" . ($r->teacherid ?: 'NULL') . "</td>";
        echo "<td>" . ($r->problem_type ?: '-') . "</td>";
        echo "<td>" . ($r->modification_prompt ?: '-') . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', $r->timecreated) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>조건에 맞는 새로운 요청이 없습니다.</p>";
}

// 3. 문제 진단
echo "<h3>문제 진단</h3>";

// Teacher ID가 0이나 NULL인 pending 확인
$unassigned = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
     WHERE (teacherid = 0 OR teacherid IS NULL) 
     AND status = 'pending'"
);
echo "<p>선생님 미지정 pending: $unassigned 개</p>";

// 이미지가 없는 pending
$no_image = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
     WHERE status = 'pending' 
     AND (problem_image IS NULL OR problem_image = '')"
);
echo "<p>이미지 없는 pending: $no_image 개</p>";

// 해설이 있는 pending
$has_solution = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
     WHERE status = 'pending' 
     AND solution_text IS NOT NULL 
     AND solution_text != ''"
);
echo "<p>해설이 있는 pending: $has_solution 개</p>";

// 4. AJAX URL 테스트
echo "<h3>AJAX 테스트</h3>";
echo "<p>get_new_requests.php 결과:</p>";
echo "<div id='ajax-result' style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>
      <button onclick='testAjax()'>AJAX 테스트 실행</button>
      <pre id='ajax-output'></pre>
      </div>";

?>

<script>
function testAjax() {
    fetch('get_new_requests.php?teacherid=<?php echo $teacherid; ?>')
        .then(response => response.json())
        .then(data => {
            document.getElementById('ajax-output').textContent = JSON.stringify(data, null, 2);
        })
        .catch(error => {
            document.getElementById('ajax-output').textContent = 'Error: ' + error;
        });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th { background: #f0f0f0; padding: 8px; }
td { padding: 8px; border: 1px solid #ddd; }
h3 { background: #e3f2fd; padding: 10px; margin: 20px 0 10px 0; }
</style>