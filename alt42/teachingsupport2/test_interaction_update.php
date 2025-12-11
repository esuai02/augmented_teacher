<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die('관리자 권한이 필요합니다.');
}

echo "<h2>Interaction Update 테스트</h2>";

// 최근 상호작용 조회
$recent_interactions = $DB->get_records_sql(
    "SELECT * FROM {ktm_teaching_interactions} 
     ORDER BY timecreated DESC 
     LIMIT 10"
);

echo "<h3>최근 상호작용 목록:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>학생 ID</th><th>상태</th><th>이미지</th><th>해설</th><th>수정프롬프트</th><th>생성시간</th></tr>";

foreach ($recent_interactions as $interaction) {
    $has_image = !empty($interaction->problem_image) ? '✓' : '✗';
    $has_solution = !empty($interaction->solution_text) ? '✓' : '✗';
    $mod_prompt = !empty($interaction->modification_prompt) ? substr($interaction->modification_prompt, 0, 30) . '...' : '-';
    
    echo "<tr>";
    echo "<td>{$interaction->id}</td>";
    echo "<td>{$interaction->userid}</td>";
    echo "<td>{$interaction->status}</td>";
    echo "<td style='text-align:center;'>{$has_image}</td>";
    echo "<td style='text-align:center;'>{$has_solution}</td>";
    echo "<td>{$mod_prompt}</td>";
    echo "<td>" . date('Y-m-d H:i:s', $interaction->timecreated) . "</td>";
    echo "</tr>";
}

echo "</table>";

// 중복 확인
echo "<h3>동일 학생의 중복 레코드 확인:</h3>";
$duplicates = $DB->get_records_sql(
    "SELECT userid, COUNT(*) as count 
     FROM {ktm_teaching_interactions} 
     WHERE timecreated > ? 
     GROUP BY userid 
     HAVING COUNT(*) > 1",
    [time() - 3600] // 최근 1시간
);

if ($duplicates) {
    echo "<p style='color: red;'>최근 1시간 내 중복 레코드 발견:</p>";
    foreach ($duplicates as $dup) {
        $user = $DB->get_record('user', ['id' => $dup->userid]);
        echo "<p>학생 {$user->firstname} {$user->lastname} (ID: {$dup->userid}): {$dup->count}개의 레코드</p>";
    }
} else {
    echo "<p style='color: green;'>최근 1시간 내 중복 레코드 없음</p>";
}

// 최근 이벤트 로그
echo "<h3>최근 이벤트 로그:</h3>";
$recent_events = $DB->get_records_sql(
    "SELECT e.*, i.userid as student_id
     FROM {ktm_teaching_events} e
     LEFT JOIN {ktm_teaching_interactions} i ON e.interactionid = i.id
     ORDER BY e.timecreated DESC
     LIMIT 20"
);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>이벤트 ID</th><th>Interaction ID</th><th>학생 ID</th><th>이벤트 타입</th><th>설명</th><th>시간</th></tr>";

foreach ($recent_events as $event) {
    echo "<tr>";
    echo "<td>{$event->id}</td>";
    echo "<td>{$event->interactionid}</td>";
    echo "<td>{$event->student_id}</td>";
    echo "<td>{$event->event_type}</td>";
    echo "<td>{$event->event_description}</td>";
    echo "<td>" . date('Y-m-d H:i:s', $event->timecreated) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>