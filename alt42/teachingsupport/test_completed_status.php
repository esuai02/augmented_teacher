<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$teacherid = optional_param('teacherid', $USER->id, PARAM_INT);

// HTML 헤더
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>완료 상태 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .status-pending { background-color: #fff3cd; }
        .status-processing { background-color: #d1ecf1; }
        .status-completed { background-color: #d4edda; }
        .status-analyzing { background-color: #e2e3e5; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>';

echo "<h1>교사 ID: $teacherid 의 요청 상태</h1>";

// 모든 상태의 요청 가져오기
$sql = "SELECT ti.*, u.firstname, u.lastname
        FROM {ktm_teaching_interactions} ti
        JOIN {user} u ON ti.userid = u.id
        WHERE ti.timecreated > ?
        ORDER BY ti.timecreated DESC
        LIMIT 50";

$params = array(time() - (7 * 24 * 3600)); // 최근 7일
$all_requests = $DB->get_records_sql($sql, $params);

echo "<h2>최근 7일간 모든 요청 (" . count($all_requests) . "개)</h2>";
echo "<table>";
echo "<tr>
        <th>ID</th>
        <th>학생</th>
        <th>교사ID</th>
        <th>상태</th>
        <th>해설</th>
        <th>이미지</th>
        <th>생성시간</th>
        <th>액션</th>
      </tr>";

foreach ($all_requests as $req) {
    $status_class = 'status-' . $req->status;
    $has_solution = !empty($req->solution_text) ? '있음' : '없음';
    $has_image = !empty($req->problem_image) ? '있음' : '없음';
    $time = date('Y-m-d H:i:s', $req->timecreated);
    
    echo "<tr class='$status_class'>
            <td>{$req->id}</td>
            <td>{$req->firstname} {$req->lastname}</td>
            <td>" . ($req->teacherid ?: '미지정') . "</td>
            <td><strong>{$req->status}</strong></td>
            <td>{$has_solution}</td>
            <td>{$has_image}</td>
            <td>{$time}</td>
            <td>
                <button onclick='updateStatus({$req->id}, \"pending\")'>Pending</button>
                <button onclick='updateStatus({$req->id}, \"processing\")'>Processing</button>
                <button onclick='updateStatus({$req->id}, \"completed\")'>Completed</button>
            </td>
          </tr>";
}
echo "</table>";

// 새로운 요청만 표시
echo "<h2>새로운 풀이 요청 (get_new_requests.php 결과)</h2>";

$new_sql = "SELECT ti.*, u.firstname, u.lastname
            FROM {ktm_teaching_interactions} ti
            JOIN {user} u ON ti.userid = u.id
            WHERE ti.status IN ('pending', 'processing')
            AND (
                ti.teacherid = ? 
                OR ti.teacherid = 0 
                OR ti.teacherid IS NULL
            )
            AND (ti.solution_text IS NULL OR ti.solution_text = '')
            AND ti.problem_image IS NOT NULL
            AND ti.timecreated > ?
            ORDER BY ti.timecreated DESC";

$new_params = array($teacherid, time() - (24 * 3600));
$new_requests = $DB->get_records_sql($new_sql, $new_params, 0, 20);

echo "<p>찾은 요청: " . count($new_requests) . "개</p>";
echo "<table>";
echo "<tr>
        <th>ID</th>
        <th>학생</th>
        <th>교사ID</th>
        <th>상태</th>
        <th>해설</th>
        <th>생성시간</th>
      </tr>";

foreach ($new_requests as $req) {
    $has_solution = !empty($req->solution_text) ? '있음' : '없음';
    $time = date('Y-m-d H:i:s', $req->timecreated);
    
    echo "<tr>
            <td>{$req->id}</td>
            <td>{$req->firstname} {$req->lastname}</td>
            <td>" . ($req->teacherid ?: '미지정') . "</td>
            <td>{$req->status}</td>
            <td>{$has_solution}</td>
            <td>{$time}</td>
          </tr>";
}
echo "</table>";

echo '
<div id="result"></div>

<script>
async function updateStatus(id, status) {
    const resultDiv = document.getElementById("result");
    
    try {
        const response = await fetch("save_interaction.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "update_status",
                interactionId: id,
                status: status
            })
        });
        
        const data = await response.json();
        if (data.success) {
            resultDiv.innerHTML = `<p class="success">ID ${id}의 상태를 ${status}로 변경했습니다. 페이지를 새로고침하세요.</p>`;
            setTimeout(() => location.reload(), 1000);
        } else {
            resultDiv.innerHTML = `<p class="error">오류: ${data.error}</p>`;
        }
    } catch (error) {
        resultDiv.innerHTML = `<p class="error">네트워크 오류: ${error.message}</p>`;
    }
}
</script>

</body>
</html>';
?>