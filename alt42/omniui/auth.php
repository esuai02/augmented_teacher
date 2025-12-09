<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();
$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;

// 데이터베이스에서 사용자 역할 가져오기
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;
?> 