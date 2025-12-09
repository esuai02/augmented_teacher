<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
$type=$_GET["type"]; 
$questionid=$_GET["qstnid"]; 
$threshold=$_GET["threshold"]; 
if($threshold==NULL)$threshold=0;

$seed = $DB->get_record_sql("SELECT * FROM mdl_question WHERE id LIKE '$questionid' ORDER BY id DESC LIMIT 1");
echo '<table align=center width=90%><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/mathpixall.php?type=question&name="target="_blank">mathpix_all</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/cat_questions.php?contentsid="target="_blank">gather similar qstns</a></td><td>검색</td><td>연관단원 목록</td><td>tag 검색</td></tr></table><hr>';
echo '<table width=90%><tr style="background-color:#c2ecff;"><td>'.$seed->id.'</td><td>'.$seed->subject.'</td><td>'.$seed->domain.'</td><td>'.$seed->chapter.'</td><td>'.$seed->name.'</td><td>'.$seed->difficulty.'</td><td>'.$seed->mathexpression.'</td><td>내용생략</td></tr></table><hr>';
 
$qstnlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_catquestions WHERE (srcid LIKE '$questionid' OR trgtid LIKE '$questionid') 
AND similarity > '$threshold' ORDER BY id DESC LIMIT 30");// 미변환

$result = json_decode(json_encode($qstnlist), True);

unset($value);
foreach($result as $value)
	{
    $trgtid=$value['trgtid'];
    if($trgtid==$questionid)$trgtid=$value['srcid']; 

    $info = $DB->get_record_sql("SELECT * FROM mdl_question WHERE id LIKE '$trgtid' ORDER BY id DESC LIMIT 1");   
    $qstnrow.='<tr><td>'.$trgtid.'</td><td>'.$info->domain.'</td><td>'.$info->subject.'</td><td>'.$info->chapter.'</td><td>'.$info->name.'</td><td>'.$info->difficulty.'</td><td>'.$info->mathexpression.'</td><td>'.$info->questiontext.'</td></tr>';
    }
 
echo '연관문제 목록 (threshold : '.$threshold.')<hr><table width=90%><tr><td>id</td><td>영역</td><td>과목</td><td>단원</td><td>제목</td><td>난이도</td><td>mathpix</td><td>유사도 값</td></tr>';
echo $qstnrow; 
echo '</table><hr>https://mathking.kr/moodle/local/augmented_teacher/LLM/mathpixall.php?type=solution&name=MXM1FC01%20LS';

?>
