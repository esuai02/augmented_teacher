<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$teacherid = optional_param('teacherid', 2, PARAM_INT);

echo "<h2>새로운 풀이요청 확인</h2>";
echo "<p>Teacher ID: $teacherid</p>";

// 1. 전체 interactions 수 확인
$total = $DB->count_records('ktm_teaching_interactions');
echo "<p>전체 interactions 수: $total</p>";

// 2. 최근 interactions 확인
echo "<h3>최근 10개 interactions:</h3>";
$recent = $DB->get_records('ktm_teaching_interactions', array(), 'timecreated DESC', '*', 0, 10);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>User ID</th><th>Teacher ID</th><th>Status</th><th>Problem Type</th><th>Has Solution</th><th>Has Image</th><th>Created</th></tr>";
foreach ($recent as $r) {
    echo "<tr>";
    echo "<td>{$r->id}</td>";
    echo "<td>{$r->userid}</td>";
    echo "<td>" . ($r->teacherid ?? 'NULL') . "</td>";
    echo "<td>{$r->status}</td>";
    echo "<td>{$r->problem_type}</td>";
    echo "<td>" . (!empty($r->solution_text) ? 'Yes' : 'No') . "</td>";
    echo "<td>" . (!empty($r->problem_image) ? 'Yes' : 'No') . "</td>";
    echo "<td>" . date('Y-m-d H:i:s', $r->timecreated) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. 새로운 요청 조건에 맞는 것 확인
echo "<h3>새로운 풀이요청 (조건에 맞는 것):</h3>";
$sql = "SELECT ti.*, u.firstname, u.lastname 
        FROM {ktm_teaching_interactions} ti
        JOIN {user} u ON ti.userid = u.id
        WHERE (
            (ti.teacherid = :teacherid1 AND ti.status = 'pending')
            OR (ti.teacherid = 0 OR ti.teacherid IS NULL)
        )
        AND (ti.solution_text IS NULL OR ti.solution_text = '')
        AND ti.problem_image IS NOT NULL
        ORDER BY ti.timecreated DESC";

$new_requests = $DB->get_records_sql($sql, array('teacherid1' => $teacherid));
echo "<p>찾은 새로운 요청 수: " . count($new_requests) . "</p>";

if (count($new_requests) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Student</th><th>Teacher ID</th><th>Status</th><th>Image</th><th>Created</th></tr>";
    foreach ($new_requests as $r) {
        echo "<tr>";
        echo "<td>{$r->id}</td>";
        echo "<td>" . fullname($r) . " (ID: {$r->userid})</td>";
        echo "<td>" . ($r->teacherid ?? 'NULL') . "</td>";
        echo "<td>{$r->status}</td>";
        echo "<td>";
        if (!empty($r->problem_image)) {
            echo "<img src='{$r->problem_image}' style='max-width: 100px;'>";
        }
        echo "</td>";
        echo "<td>" . date('Y-m-d H:i:s', $r->timecreated) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Teacher ID가 NULL인 것들 확인
echo "<h3>Teacher ID가 NULL인 pending 상태:</h3>";
$null_teacher = $DB->get_records_sql(
    "SELECT * FROM {ktm_teaching_interactions} 
     WHERE (teacherid IS NULL OR teacherid = 0) 
     AND status = 'pending' 
     ORDER BY timecreated DESC 
     LIMIT 5"
);
echo "<p>Teacher가 없는 pending 수: " . count($null_teacher) . "</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th { background: #f8f9fa; padding: 8px; }
td { padding: 8px; border: 1px solid #ddd; }
img { display: block; }
</style>