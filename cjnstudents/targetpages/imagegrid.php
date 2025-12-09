<!DOCTYPE html>
<html>
<style>
* {
  box-sizing: border-box;
}
@media print  
{
    div {
        page-break-inside: avoid;
    }
}
img {
border: 1px solid #555;
 
}
body {
  margin: 0;
  font-family: Arial;
}

.header {
  text-align: center;
  padding: 32px;
}

.row {
  display: -ms-flexbox; /* IE10 */
  display: flex;
  -ms-flex-wrap: wrap; /* IE10 */
  flex-wrap: wrap;
  padding: 0 4px;
}

/* Create four equal columns that sits next to each other */
.column {
  -ms-flex: 25%; /* IE10 */
  flex: 25%;
  max-width: 25%;
  padding: 0 4px;
}

.column img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

/* Responsive layout - makes a two column-layout instead of four columns */
@media screen and (max-width: 1000px) {
  .column {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

/* Responsive layout - makes the two columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
}
 

</style>
<body>

 
<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;
$studentid = $_GET["id"]; 
$N_days = $_GET["ndays"]; 
$qid = $_GET["qid"]; 
$mode= $_GET["mode"]; 
if($N_days==NULL)$N_days=1;
$nstar= $_GET["nstar"]; 


$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
//$contextid=substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$currenturl=strstr($url, '?');  //before
$currenturl=str_replace("?","",$currenturl);

if($mode==='print')
{
$atimeago=time()-86400*$N_days+43200;
$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE    (state='gaveup' OR state='gradedwrong' OR state ='gradedpartial' )  AND mdl_question_attempt_steps.userid='$studentid' AND mdl_question_attempt_steps.timecreated > '$atimeago' ");
$result1 = json_decode(json_encode($questionattempts), True); 

$imagegrid.='<hr><table width=100%><tr><td width=5%></td><td><b>향상노트</b></td><td ></td><td align=right> 해설지 부분을 가리고 풀면 효과적입니다. </td></tr><table><hr>';
unset($value);
foreach( $result1 as $value)
	{
	$questionid=$value['questionid']; 
	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND status NOT LIKE 'neuroboard' AND status NOT LIKE 'boost' AND contentsid='$questionid' AND contentstype='2'  AND active=1 ORDER BY id DESC LIMIT 1 ");
	$encryption_id=$handwriting->wboardid;
	
	if($nstar==NULL || $nstar<=$handwriting->depth)
		{
		$questiontext=$value['questiontext'];
 
		//Create a new DOMDocument object.
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		$htmlDom2 = new DOMDocument; @$htmlDom2->loadHTML($value['generalfeedback']); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages2 = array();
		$nimg=0;
		foreach($imageTags2 as $imageTag2)
			{
			$nimg++;
    			$solutionimg = $imageTag2->getAttribute('src');
			$solutionimg = str_replace(' ', '%20', $solutionimg); 
			if(strpos($solutionimg, 'MATRIX/MATH')!= false && strpos($solutionimg, 'hintimages') == false)break;
			}

		if($handwriting->depth==0)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" >';
		elseif($handwriting->depth==1)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" >';
		elseif($handwriting->depth==2)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" >';
		elseif($handwriting->depth==3)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" >';
		elseif($handwriting->depth==4)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" >';
		elseif($handwriting->depth==5)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" >';

		$imagegrid.='<div><table width=100%><tr><td valign=top width=40%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$questionimg.'" style="width:100%"></a><br>'.$resultValue.'</td><td%></td><td valign=top width=40%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$solutionimg.'" style="width:100%"></a></td></tr></table><br><hr></div>';
		}
	elseif($nstar==0 && $handwriting->depth==NULL)
		{
		$questiontext=$value['questiontext'];
 
		//Create a new DOMDocument object.
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		if($handwriting->depth==0)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" >';
		elseif($handwriting->depth==1)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" >';
		elseif($handwriting->depth==2)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" >';
		elseif($handwriting->depth==3)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" >';
		elseif($handwriting->depth==4)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" >';
		elseif($handwriting->depth==5)$resultValue=' &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" >';

		$imagegrid.='<div><table width=100%><tr><td valign=top width=40%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$questionimg.'" style="width:100%"></a><br>'.$resultValue.'</td><td></td><td valign=top width=40%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$solutionimg.'" style="width:100%"></a></td></tr></table><br><hr></div>';

		}
	 
     	}
}  // end of print
elseif($qid==NULL)
{
$atimeago=time()-86400*$N_days+43200;
$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE    (state='gaveup' OR state='gradedwrong' OR state ='gradedpartial' )  AND mdl_question_attempt_steps.userid='$studentid' AND mdl_question_attempt_steps.timecreated > '$atimeago' ");
$result1 = json_decode(json_encode($questionattempts), True); 
 
unset($value);
foreach( $result1 as $value)
	{
	$questionid=$value['questionid']; 
 
 

	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND status NOT LIKE 'neuroboard' AND status NOT LIKE 'boost' AND contentsid='$questionid' AND contentstype='2'  AND active=1 ORDER BY id DESC LIMIT 1 ");
	$encryption_id=$handwriting->wboardid;
	
	if($nstar==NULL || $nstar<=$handwriting->depth)
		{
		$questiontext=$value['questiontext'];
 
		//Create a new DOMDocument object.
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		if($handwriting->depth==0)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" >';
		elseif($handwriting->depth==1)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" >';
		elseif($handwriting->depth==2)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" >';
		elseif($handwriting->depth==3)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" >';
		elseif($handwriting->depth==4)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" >';
		elseif($handwriting->depth==5)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" >';

		$imagegrid.='<div class="column"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$questionimg.'" style="width:100%"></a><table align=right><tr><th>'.$resultValue.'</th></tr></table></div>';
		}
	elseif($nstar==0 && $handwriting->depth==NULL)
		{
		$questiontext=$value['questiontext'];
 
		//Create a new DOMDocument object.
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		if($handwriting->depth==0)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" >';
		elseif($handwriting->depth==1)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" >';
		elseif($handwriting->depth==2)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" >';
		elseif($handwriting->depth==3)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" >';
		elseif($handwriting->depth==4)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" >';
		elseif($handwriting->depth==5)$resultValue='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'&qid='.$questionid.'"><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" >';

		$imagegrid.='<div class="column"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$questionimg.'" style="width:100%"></a><table align=right><tr><th>'.$resultValue.'</th></tr></table></div>';

		}
	 
     	}
 }
else
{
$instance2=$DB->get_records_sql("SELECT * FROM mdl_tag_instance  WHERE itemid='$qid' AND itemtype LIKE 'question' ");
$tags2= json_decode(json_encode($instance2), True);   
unset($value3); 
foreach($tags2 as $value3)
	{
	$tagid=$value3['tagid'];
	$tag2=$DB->get_record_sql("SELECT * FROM mdl_tag WHERE id='$tagid' ORDER BY id DESC LIMIT 1 ");
	$tagname2=$tag2->name;	
	if(strpos($tagname2, 'mxm')!==false||strpos($tagname2, 'mxh')!==false)
		{
		$chid=$tagid; 
		$chaptername=$tagname2;
		}
	}
echo $chaptername;

$atimeago=time()-86400*$N_days+43200;
$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE    (state='gaveup' OR state='gradedwrong' OR state ='gradedpartial' )  AND mdl_question_attempt_steps.userid='$studentid' AND mdl_question_attempt_steps.timecreated > '$atimeago' ");
$result1 = json_decode(json_encode($questionattempts), True); 
 
unset($value);
foreach( $result1 as $value)
	{
	
	$questionid=$value['questionid']; 
	$instance=$DB->get_records_sql("SELECT * FROM mdl_tag_instance  WHERE itemid='$questionid' AND itemtype LIKE 'question' ");
	$tags= json_decode(json_encode($instance), True);   
	unset($value2); 
	foreach($tags as $value2)
		{
		$tagid=$value2['tagid'];
		$tag=$DB->get_record_sql("SELECT * FROM mdl_tag WHERE id='$tagid' ORDER BY id DESC LIMIT 1 ");
		$tagname=$tag->name;
		if(strpos($tagname, 'mxm')!==false||strpos($tagname, 'mxh')!==false)$chapterid=$tagid; 
		}

	 
	if($chid!=$chapterid)continue;


	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND status NOT LIKE 'neuroboard' AND status NOT LIKE 'boost' AND contentsid='$questionid' AND contentstype='2' AND active=1  ORDER BY id DESC LIMIT 1 ");
	$encryption_id=$handwriting->wboardid;
	
	if($nstar==NULL || $nstar<=$handwriting->depth)
		{
		$questiontext=$value['questiontext'];
 
		//Create a new DOMDocument object.
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		if($handwriting->depth==0)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" >';
		elseif($handwriting->depth==1)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" >';
		elseif($handwriting->depth==2)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" >';
		elseif($handwriting->depth==3)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" >';
		elseif($handwriting->depth==4)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" >';
		elseif($handwriting->depth==5)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" >';

		$imagegrid.='<div class="column"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$questionimg.'" style="width:100%"></a><table align=right><tr><th>'.$resultValue.'</th></tr></table></div>';
		}
	elseif($nstar==0 && $handwriting->depth==NULL)
		{
		$questiontext=$value['questiontext'];
 
		//Create a new DOMDocument object.
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		if($handwriting->depth==0)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" >';
		elseif($handwriting->depth==1)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" >';
		elseif($handwriting->depth==2)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" >';
		elseif($handwriting->depth==3)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" >';
		elseif($handwriting->depth==4)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" >';
		elseif($handwriting->depth==5)$resultValue='<a href=""><img style="border:none; width:20px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1653442721.png" ></a> &nbsp;&nbsp;<img style="border:none; width:80px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" >';

		$imagegrid.='<div class="column"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'"target="_blank"><img src="'.$questionimg.'" style="width:100%"></a><table align=right><tr><th>'.$resultValue.'</th></tr></table></div>';
		}
	 
       	}
}

if($mode==='print')
	{
	echo $imagegrid;
	}
else echo ' 
<div class="header">
  <h2>최근 '.$N_days.'일 동안의 오답문항들</h2> 
<p>(<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays='.$N_days.'">모두</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar=1&ndays='.$N_days.'">+1</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar=2&ndays='.$N_days.'">+2</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar=3&ndays='.$N_days.'">+3</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar=4&ndays='.$N_days.'">+4</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar=5&ndays='.$N_days.'">+5</a>)  별점을 업데이트하며 맞춤형 복습활동을 할 수 있습니다. .... <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?'.$currenturl.'&mode=print"target="_blank">프린트</a> (<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar='.$nstar.'&ndays=7">1주</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar='.$nstar.'&ndays=14">2주</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar='.$nstar.'&ndays=21">3주</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar='.$nstar.'&ndays=28">4주</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar='.$nstar.'&ndays=60">2개월</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&nstar='.$nstar.'&ndays=90">3개월</a>)</p>
</div>
<div class="row">'.$imagegrid.'</div>';
?>

</body>
</html>
