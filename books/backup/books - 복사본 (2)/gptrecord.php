

<?php   
$gptlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_gptultratalk where contextid='$contextid' AND status NOT LIKE 'hide' ORDER BY id DESC ");  

$talkid=$gptlog->id;
$gptquestion = $gptlog->question;
$gpttalk = $gptlog->gpttalk;
 
?>

