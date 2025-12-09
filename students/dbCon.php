<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");

global $DB,$USER;

$studentid = $_GET['studentid'] ?? 'unknown';
$todaygoal = $_GET['asktype'] ?? 'unknown';
//$studentid='jsm04';

//$nid = $DB->get_record_sql("SELECT id FROM mdl_user WHERE id LIKE '$studentid' ORDER BY id DESC LIMIT 1");

//$userid=1467;
$nid = $DB->get_record_sql("SELECT id FROM mdl_user WHERE username LIKE '$studentid' ORDER BY id DESC LIMIT 1");
$userid=$nid->id;
echo '학생 아이디 : '.$studentid.' | todaygoal : '.$todaygoal;
header("Location: https://mathking.kr/moodle/local/augmented_teacher/students/sendtogpt.php?id={$userid}&tb=43200");
?>