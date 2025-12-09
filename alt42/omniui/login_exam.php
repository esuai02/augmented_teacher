<?php
include_once("/home/moodle/public_html/moodle/config.php");

// 이미 로그인되어 있으면 exam_system.php로 리다이렉트
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/omniui/exam_system.php'));
}

// 로그인 페이지로 리다이렉트
$loginurl = get_login_url();
redirect($loginurl);
?>