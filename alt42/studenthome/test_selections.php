<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;

echo "<h2>사용자 선택 정보 테스트</h2>";
echo "<p>User ID: $studentid</p>";

// 모든 선택 정보 조회
$selections = $DB->get_records('user_learning_selections', array('userid' => $studentid));

echo "<h3>저장된 선택 정보:</h3>";
echo "<pre>";
foreach ($selections as $selection) {
    echo "Page Type: " . $selection->page_type . "\n";
    echo "Last Unit: " . $selection->last_unit . "\n";
    echo "Last Topic: " . $selection->last_topic . "\n";
    echo "Last Path: " . $selection->last_path . "\n";
    echo "Selection Data: " . $selection->selection_data . "\n";
    echo "Time Modified: " . date('Y-m-d H:i:s', $selection->timemodified) . "\n";
    echo "-------------------\n";
}
echo "</pre>";

// 테이블 존재 여부 확인
$tables = $DB->get_tables();
echo "<h3>관련 테이블 존재 여부:</h3>";
echo "<ul>";
echo "<li>user_learning_selections: " . (in_array('user_learning_selections', $tables) ? '✓ 존재' : '✗ 없음') . "</li>";
echo "<li>user_recent_courses: " . (in_array('user_recent_courses', $tables) ? '✓ 존재' : '✗ 없음') . "</li>";
echo "</ul>";
?>