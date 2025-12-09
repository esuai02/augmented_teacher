<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$timecreated=time(); 
$contentsid= $_GET['contentsid']; 
$src=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");
$question1 =$src->mathexpression;
 
/*
$contentsid2= $_GET['contentsid2']; 
$text2=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id='$contentsid2' ORDER BY id DESC LIMIT 1");
$question2 = $text2->mathexpression;
*/
//$question1='What is the capital of France';
//$question2='Which country has the Eiffel Tower';
if($src->difficulty=='기초')$qbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE (difficulty LIKE '기초' OR difficulty LIKE '기본') AND chapter LIKE '$src->chapter' AND mathexpression IS NOT NULL ORDER BY id DESC LIMIT 10");

elseif($src->difficulty=='기본')$qbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE (difficulty LIKE '기본' OR difficulty LIKE '중급') AND chapter LIKE '$src->chapter' AND mathexpression IS NOT NULL ORDER BY id DESC LIMIT 10");
 
elseif($src->difficulty=='중급')$qbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE (difficulty LIKE '기본' OR difficulty LIKE '중급' OR difficulty LIKE '심화') AND chapter LIKE '$src->chapter' AND mathexpression IS NOT NULL ORDER BY id DESC LIMIT 10");
 
elseif($src->difficulty=='심화' || $src->difficulty=='고난도')$qbank=$DB->get_records_sql("SELECT * FROM mdl_question WHERE (difficulty LIKE '중급' OR difficulty LIKE '심화' OR difficulty LIKE '고난도') AND chapter LIKE '$src->chapter' AND mathexpression IS NOT NULL ORDER BY id DESC LIMIT 10");

$result = json_decode(json_encode($qbank), True);
 
unset($value);
foreach($result as $value)
	{
    $question2=$value['mathexpression'];
    $trgtid=$value['id'];
    $command = "python3.10 checksimilarity.py " . escapeshellarg($question1) . " " . escapeshellarg($question2);
    $output = shell_exec($command);
    $similarity=round($output,6);
    if($contentstype==NULL)
        { 
        $exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_catquestions where (srcid LIKE '$src->id' AND trgtid LIKE '$trgtid') OR  (srcid LIKE '$trgtid' AND trgtid LIKE '$src->id') ORDER BY id DESC LIMIT 1 "); 
        if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_catquestions} (contentstype,srcid,trgtid,similarity,updater,timemodified,timecreated) VALUES('2','$src->id','$trgtid','$similarity','$USER->id','$timecreated','$timecreated')");
        else $DB->execute("UPDATE {abessi_catquestions} SET similarity='$similarity',updater='$USER->id',timemodified='$timecreated'  WHERE (srcid LIKE '$src->id' AND trgtid LIKE '$trgtid') OR  (srcid LIKE '$trgtid' AND trgtid LIKE '$src->id') ORDER BY id DESC LIMIT 1  ");
        }
    elseif($contentstype==1)
        {

        }
    $showthis='<hr><table width=100%><tr><td># q1_'.$contentsid.' : '.$question1.' </td><td># q2_'.$trgtid.' : '.$question2.'</td></tr></table><hr># similarity_value='.$output.'<br>';
    include("../showthis.php");
    echo $showthis;
    }

 
 
?>