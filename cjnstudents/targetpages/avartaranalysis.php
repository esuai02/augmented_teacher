<?php  
$type = $_GET["type"]; 
$studentid = $_GET["studentid"]; 
$teacherid = $_GET["teacherid"]; 
$checkid = $_GET["checkid"];  

$moreleap = $DB->get_record_sql("SELECT * FROM  mdl_abessi_cognitivetalk WHERE creator='$studentid' AND type='$type' AND hide=0 ORDER BY id DESC LIMIT 1 ");  // 메타인지 피드백


?>