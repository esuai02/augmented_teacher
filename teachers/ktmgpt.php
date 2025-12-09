<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
require_login();

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
  
$timecreated=time();
$id = $_GET["id"]; 
$srcid='usr'.$USER->id.'time'.$timecreated;
 
echo '

 
<iframe  class="foo" scrolling="no"  src="https://chat.openai.com/chat" width="100%" height="100%" style="border:1px solid black;"></iframe>
 
 ';

 
?>
