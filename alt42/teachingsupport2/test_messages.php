<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h2>메시지 테이블 테스트</h2>";

// ktm_mathmessages 테이블 확인
if ($DB->get_manager()->table_exists('ktm_mathmessages')) {
    echo "<h3>ktm_mathmessages 테이블</h3>";
    $count = $DB->count_records('ktm_mathmessages');
    echo "<p>전체 레코드 수: $count</p>";
    
    $records = $DB->get_records('ktm_mathmessages', array(), 'id DESC', '*', 0, 5);
    if ($records) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>학생ID</th><th>선생님ID</th><th>제목</th><th>생성일</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>{$record->id}</td>";
            echo "<td>{$record->student_id}</td>";
            echo "<td>{$record->teacher_id}</td>";
            echo "<td>{$record->subject}</td>";
            echo "<td>" . date('Y-m-d H:i:s', $record->timecreated) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>ktm_mathmessages 테이블이 존재하지 않습니다.</p>";
}

// ktm_teaching_interactions 테이블 확인
if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    echo "<h3>ktm_teaching_interactions 테이블</h3>";
    $count = $DB->count_records('ktm_teaching_interactions');
    echo "<p>전체 레코드 수: $count</p>";
    
    $completed_count = $DB->count_records('ktm_teaching_interactions', array('status' => 'completed'));
    echo "<p>완료된 레코드 수: $completed_count</p>";
    
    $records = $DB->get_records('ktm_teaching_interactions', array('status' => 'completed'), 'id DESC', '*', 0, 5);
    if ($records) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>사용자ID</th><th>선생님ID</th><th>상태</th><th>오디오URL</th><th>생성일</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>{$record->id}</td>";
            echo "<td>{$record->userid}</td>";
            echo "<td>{$record->teacherid}</td>";
            echo "<td>{$record->status}</td>";
            echo "<td>" . ($record->audio_url ? '있음' : '없음') . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $record->timecreated) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>ktm_teaching_interactions 테이블이 존재하지 않습니다.</p>";
}

// 현재 사용자 정보
echo "<h3>현재 사용자 정보</h3>";
echo "<p>사용자 ID: {$USER->id}</p>";
echo "<p>사용자 이름: " . fullname($USER) . "</p>";

// API 테스트
echo "<h3>API 테스트</h3>";
$url = "get_student_messages.php?studentid={$USER->id}&page=0&perpage=10";
echo "<p><a href='$url' target='_blank'>메시지 API 테스트</a></p>";
?>