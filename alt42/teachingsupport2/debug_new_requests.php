<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=UTF-8');

$teacherid = optional_param('teacherid', 2, PARAM_INT);

echo "<h2>새로운 풀이요청 디버깅</h2>";
echo "<p>현재 사용자 ID: {$USER->id}</p>";
echo "<p>검색할 선생님 ID: $teacherid</p>";

// 1. 테이블 존재 확인
echo "<h3>1. 테이블 존재 확인</h3>";
if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    echo "✅ ktm_teaching_interactions 테이블 존재<br>";
    
    // 테이블 구조 확인
    $columns = $DB->get_columns('ktm_teaching_interactions');
    echo "필드 목록: " . implode(', ', array_keys($columns)) . "<br>";
} else {
    echo "❌ ktm_teaching_interactions 테이블이 없습니다!<br>";
}

// 2. 전체 레코드 수 확인
echo "<h3>2. 전체 레코드 통계</h3>";
$total = $DB->count_records('ktm_teaching_interactions');
echo "전체 레코드 수: $total<br>";

$pending = $DB->count_records('ktm_teaching_interactions', array('status' => 'pending'));
echo "Pending 상태: $pending<br>";

$completed = $DB->count_records('ktm_teaching_interactions', array('status' => 'completed'));
echo "Completed 상태: $completed<br>";

// 3. 최근 5개 레코드 확인
echo "<h3>3. 최근 5개 레코드</h3>";
$recent = $DB->get_records('ktm_teaching_interactions', array(), 'timecreated DESC', '*', 0, 5);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th><th>User ID</th><th>Teacher ID</th><th>Status</th>";
echo "<th>Problem Type</th><th>Has Image</th><th>Has Solution</th>";
echo "<th>Modification</th><th>Created</th>";
echo "</tr>";

foreach ($recent as $r) {
    echo "<tr>";
    echo "<td>{$r->id}</td>";
    echo "<td>{$r->userid}</td>";
    echo "<td>" . ($r->teacherid ?: 'NULL') . "</td>";
    echo "<td style='color: " . ($r->status == 'pending' ? 'orange' : 'green') . "'>{$r->status}</td>";
    echo "<td>" . ($r->problem_type ?: '-') . "</td>";
    echo "<td>" . (!empty($r->problem_image) ? '✅' : '❌') . "</td>";
    echo "<td>" . (!empty($r->solution_text) ? '✅' : '❌') . "</td>";
    echo "<td>" . (!empty($r->modification_prompt) ? substr($r->modification_prompt, 0, 30) . '...' : '-') . "</td>";
    echo "<td>" . date('Y-m-d H:i:s', $r->timecreated) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. 새로운 요청 쿼리 테스트
echo "<h3>4. 새로운 요청 쿼리 결과</h3>";
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

try {
    $new_requests = $DB->get_records_sql($sql, array('teacherid1' => $teacherid));
    echo "찾은 새로운 요청 수: " . count($new_requests) . "<br><br>";
    
    if (count($new_requests) > 0) {
        foreach ($new_requests as $req) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: #f9f9f9;'>";
            echo "<strong>ID:</strong> {$req->id}<br>";
            echo "<strong>학생:</strong> " . fullname($req) . " (ID: {$req->userid})<br>";
            echo "<strong>선생님 ID:</strong> " . ($req->teacherid ?: 'NULL') . "<br>";
            echo "<strong>상태:</strong> {$req->status}<br>";
            echo "<strong>문제 유형:</strong> " . ($req->problem_type ?: '-') . "<br>";
            echo "<strong>추가 요청:</strong> " . ($req->modification_prompt ?: '없음') . "<br>";
            echo "<strong>생성 시간:</strong> " . date('Y-m-d H:i:s', $req->timecreated) . "<br>";
            if (!empty($req->problem_image)) {
                echo "<strong>이미지:</strong><br>";
                echo "<img src='{$req->problem_image}' style='max-width: 200px; border: 1px solid #ddd;'><br>";
            }
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>조건에 맞는 새로운 요청이 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>쿼리 실행 오류: " . $e->getMessage() . "</p>";
}

// 5. 특정 조건별 확인
echo "<h3>5. 조건별 상세 확인</h3>";

// 5-1. teacher가 NULL이거나 0인 pending 확인
$null_teacher_pending = $DB->get_records_sql(
    "SELECT * FROM {ktm_teaching_interactions} 
     WHERE (teacherid IS NULL OR teacherid = 0) 
     AND status = 'pending'
     AND (solution_text IS NULL OR solution_text = '')
     AND problem_image IS NOT NULL"
);
echo "선생님 미지정 pending: " . count($null_teacher_pending) . "개<br>";

// 5-2. 특정 선생님의 pending 확인
$teacher_pending = $DB->get_records_sql(
    "SELECT * FROM {ktm_teaching_interactions} 
     WHERE teacherid = :teacherid
     AND status = 'pending'
     AND (solution_text IS NULL OR solution_text = '')
     AND problem_image IS NOT NULL",
    array('teacherid' => $teacherid)
);
echo "선생님 ID $teacherid 의 pending: " . count($teacher_pending) . "개<br>";

// 5-3. 이미지가 없는 pending 확인
$no_image_pending = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
     WHERE status = 'pending'
     AND (problem_image IS NULL OR problem_image = '')"
);
echo "이미지 없는 pending: $no_image_pending 개<br>";

// 5-4. solution_text가 있는 pending 확인 
$has_solution_pending = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
     WHERE status = 'pending'
     AND solution_text IS NOT NULL
     AND solution_text != ''"
);
echo "해설이 이미 있는 pending: $has_solution_pending 개<br>";

// 6. AJAX 요청 URL 확인
echo "<h3>6. AJAX 요청 테스트</h3>";
$ajax_url = "get_new_requests.php?teacherid=$teacherid";
echo "AJAX URL: <a href='$ajax_url' target='_blank'>$ajax_url</a><br>";
echo "위 링크를 클릭해서 JSON 응답을 확인하세요.<br>";

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
}
table { 
    margin: 10px 0; 
    font-size: 14px;
}
th { 
    background: #f0f0f0; 
    padding: 8px; 
    text-align: left;
}
td { 
    padding: 8px; 
    border: 1px solid #ddd; 
}
h3 {
    background: #e8f4f8;
    padding: 10px;
    margin: 20px 0 10px 0;
    border-left: 4px solid #2196F3;
}
</style>