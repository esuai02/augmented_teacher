<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
   
$prev=$DB->get_record_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1");
$randomdream=$prev->dreamchallenge;
$randomdreamurl=$prev->dreamurl;
 
header("Location: $randomdreamurl"); 
?>

