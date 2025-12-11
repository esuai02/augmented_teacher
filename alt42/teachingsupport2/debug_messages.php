<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? 817;

echo "<h2>Student Messages 디버깅 (학생 ID: $studentid)</h2>";

// 1. ktm_mathmessages 테이블 확인
if ($DB->get_manager()->table_exists('ktm_mathmessages')) {
    echo "<h3>✅ ktm_mathmessages 테이블 존재</h3>";
    
    // 메시지 개수 확인
    $count = $DB->count_records('ktm_mathmessages', array('studentid' => $studentid));
    echo "<p>학생 ID $studentid 의 메시지 개수: <strong>$count</strong></p>";
    
    // 최근 메시지 확인
    $messages = $DB->get_records('ktm_mathmessages', 
        array('studentid' => $studentid), 
        'timecreated DESC', 
        '*', 
        0, 
        5
    );
    
    if ($messages) {
        echo "<h3>최근 메시지:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Teacher</th><th>Subject</th><th>Interaction ID</th><th>Created</th></tr>";
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>{$msg->id}</td>";
            echo "<td>{$msg->teacherid}</td>";
            echo "<td>" . substr($msg->subject ?? 'No subject', 0, 50) . "</td>";
            echo "<td>{$msg->interaction_id}</td>";
            echo "<td>" . date('Y-m-d H:i:s', $msg->timecreated) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>메시지가 없습니다.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ ktm_mathmessages 테이블이 존재하지 않습니다!</p>";
}

// 2. ktm_teaching_interactions 테이블 확인
if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    echo "<h3>✅ ktm_teaching_interactions 테이블 존재</h3>";
    
    $interactions = $DB->get_records_sql(
        "SELECT * FROM {ktm_teaching_interactions} 
         WHERE userid = ? AND status = 'sent'
         ORDER BY timecreated DESC
         LIMIT 5",
        array($studentid)
    );
    
    echo "<p>전송 완료된 상호작용: " . count($interactions) . "개</p>";
    
    if ($interactions) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Status</th><th>Teacher</th><th>Created</th></tr>";
        foreach ($interactions as $int) {
            echo "<tr>";
            echo "<td>{$int->id}</td>";
            echo "<td>{$int->status}</td>";
            echo "<td>{$int->teacherid}</td>";
            echo "<td>" . date('Y-m-d H:i:s', $int->timecreated) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// 3. API 응답 확인
echo "<h3>API 응답 테스트:</h3>";
$_GET['studentid'] = $studentid;
$_GET['page'] = 0;
$_GET['perpage'] = 10;

// get_student_messages.php 의 로직을 직접 실행
try {
    $page = 0;
    $perpage = 10;
    
    // 메시지 가져오기
    $sql = "SELECT m.*, u.firstname, u.lastname 
            FROM {ktm_mathmessages} m
            JOIN {user} u ON m.teacherid = u.id
            WHERE m.studentid = ?
            ORDER BY m.timecreated DESC";
    
    $messages = $DB->get_records_sql($sql, array($studentid), $page * $perpage, $perpage);
    
    echo "<p>찾은 메시지: " . count($messages) . "개</p>";
    
    if ($messages) {
        echo "<pre>";
        foreach ($messages as $msg) {
            echo "ID: {$msg->id}, Teacher: {$msg->firstname} {$msg->lastname}, Created: " . date('Y-m-d H:i:s', $msg->timecreated) . "\n";
        }
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류: " . $e->getMessage() . "</p>";
}

// 4. 다른 학생들의 메시지 확인
echo "<h3>전체 메시지 통계:</h3>";
$sql = "SELECT studentid, COUNT(*) as count 
        FROM {ktm_mathmessages} 
        GROUP BY studentid 
        ORDER BY count DESC 
        LIMIT 10";
$stats = $DB->get_records_sql($sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Student ID</th><th>Message Count</th></tr>";
foreach ($stats as $stat) {
    echo "<tr><td>{$stat->studentid}</td><td>{$stat->count}</td></tr>";
}
echo "</table>";

// 링크
echo "<hr>";
echo "<p><a href='student_inbox.php?studentid=$studentid&userid=2'>학생 메시지함으로 이동</a></p>";
echo "<p><a href='get_student_messages.php?studentid=$studentid&page=0&perpage=10'>API 직접 호출</a></p>";
?>