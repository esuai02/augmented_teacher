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
/* Create four equal columns that sit next to each other */
.column1 {
  -ms-flex: 25%; /* IE10 */
  flex: 25%;
  max-width: 25%;
  padding: 0 4px;
}

.column1 img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
  border: 3px solid green;
  background-color: #ffffe0; /* 연한 노란색 */
  box-sizing: border-box;
}

/* Responsive layout - makes a three-column layout instead of four columns */
@media screen and (max-width: 1200px) {
  .column1 {
    -ms-flex: 33.33%;
    flex: 33.33%;
    max-width: 33.33%;
  }
}

/* Responsive layout - makes a two-column layout instead of three columns */
@media screen and (max-width: 900px) {
  .column1 {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

/* Responsive layout - makes the columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column1 {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
}


/* Create four equal columns that sits next to each other */
.column2 {
  -ms-flex: 33.3%; /* IE10 */
  flex: 33.3%;
  max-width: 33.3%;
  padding: 0 4px;
}

.column2 img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

/* Responsive layout - makes a two column-layout instead of four columns */
@media screen and (max-width: 1000px) {
  .column2 {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

/* Responsive layout - makes the two columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column2 {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
}
 
</style>
<body>

 
<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
$tbegin= $_GET["tb"];
$tend= $_GET["te"];
if($studentid==NULL)$studentid=$USER->id;
require_login();
$timecreated=time(); 
$hoursago=$timecreated-43200;
$aweekago=$timecreated-604800;
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");

//$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND (timemodified > '$hoursago' || (student_check=1 AND timemodified > '$aweekago')) ORDER BY timecreated DESC LIMIT 300");
if($tbegin==NULL)$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND timemodified > '$hoursago'   ORDER BY timemodified DESC LIMIT 100");
else $handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND timemodified > '$tbegin' AND timemodified < '$tend'   ORDER BY timemodified DESC LIMIT 100");
$result = json_decode(json_encode($handwriting), True);
 
$quizstatus=0;
$eventspaceanalysis='<a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic_timeline.php?userid='.$studentid.'">📊</a>';
$ForDeepLearning='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid='.$studentid.'"> <img loading="lazy"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651023487.png" width=40></a>';
 
 
unset($value);


foreach($result as $value) 
	{
	$wboardid=$value['wboardid'];
	$contentstype=$value['contentstype'];
	$contentsid=$value['contentsid'];
	$contentstitle=$value['contentstitle'];
	$instruction=$value['instruction'];
	$nstroke=$value['nstroke'];
	$ncommit=$value['feedback'];
	if($ncommit!=0)$ncommit='<b style="color:#FF0000;">'.$ncommit.'</b>';
	$usedtime=round($value['usedtime']/60,1).'분';
	$tinterval=round(($tprev-$value['timemodified'])/60,0).'분';
	$tprev=$value['timemodified'];
	$status=$value['status'];
	if($value['status']==='commitquiz' ||$value['status']==='reflect' || $value['status']==='examplenote')continue;
	if($tinterval<0)$tinterval=round(($timecreated-$value['timemodified'])/60,0).'분';
 
	$prsninfo='<a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/selectpersona.php?cnttype='.$contentstype.'&cntid='.$contentsid.'&userid='.$studentid.'"target="_blank">🎭</a>';
	$existprsn= $DB->get_record_sql("SELECT id FROM {prsn_contents} WHERE contentstype='$contentstype' AND contentsid='$contentsid'");
	if($existprsn!=NULL)$prsninfo='<a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/selectpersona.php?cnttype='.$contentstype.'&cntid='.$contentsid.'&userid='.$studentid.'"target="_blank">🎭 페르소나</a>'; 



if($role==='student')$prsninfo='';

	$timestamp=$timecreated-$value['timemodified'];
	if($timestamp<=60)$timestamp=$timestamp.'초 전';
	elseif($timestamp<=3600)$timestamp=round($timestamp/60,0).'분 전';
	elseif($timestamp<=86400)$timestamp=round($timestamp/3600,0).'시간 전';
	elseif($timestamp<=2592000)$timestamp=round($timestamp/86400,0).'일 전';

	
	$instructionBtn='&nbsp;&nbsp;('.$ncommit.')&nbsp;&nbsp;<a style="decoration:none;"  href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img style="border: 0px solid #555;decoration:none;width:25px;" src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png"></a>';
	if(strpos($wboardid, 'jnrsorksqcrark')!== false)
		{
		$noteurl=$value['url'];
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
		$ctext=$getimg->pageicontent;
		if($getimg->reflections!=NULL)$reflections=$getimg->reflections.'<hr>';
		$htmlDom = new DOMDocument;
		if($studentid==NULL)$studentid=2;
		
		@$htmlDom->loadHTML($ctext);
		$imageTags = $htmlDom->getElementsByTagName('img');
		$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
			}
		$imagegrid.='<div class="column1"><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"><img loading="lazy" src="'.$imgSrc.'" style="width:100%"></a><table align=right><tr><th>'.$timestamp.'분전 | '.$value['status'].$instructionBtn.'</th><th>'.$prsninfo.'</th></tr></table></div>';
		}
	else
		{
		$qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid' ");

		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
			} 
		$imagegrid.='<div class="column2"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?mode=1&userid='.$studentid.'&wboardid='.$wboardid.'"target="_blank"><img loading="lazy" src="'.$imgSrc.'" style="width:100%"></a><table align=right><tr><th>'.$timestamp.'분전 | '.$value['status'].$instructionBtn.'</th><th>'.$prsninfo.'</th></tr></table></div>';
		} 


	}
if($quizstatus==1)$currentstatus='응시중';
else $currentstatus='검토';
echo ' 	<table align=center width=100%><tr><th valign=top><div class="table-wrapper"><table width=100%><thead><tr><th style="white-space: nowrap;" width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$studentid.'&type=init"> <img loading="lazy"  src=https://mathking.kr/Contents/IMAGES/timefolding.png width=50></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/fixnote.php?userid='.$studentid.'"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/fixnote.png" width=40></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/activelearningnote.php?userid='.$studentid.'"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/commitnote.png" width=40></a>
	
	</th><th style="color:#1956FF;font-size:20px;" width=15%><a style="text-decoration:none;color:#08090B;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">'.$stdname.'</a> '.$eventspaceanalysis.' ('.$currentstatus.')</th><th width=5% style="white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;">'.$ForDeepLearning.'</th><th style="white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;">'.$subjectnav.'</th></tr></thead><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$papertest.'<hr>'.$submitted.'</table></div></th></tr></table> 
<div class="row">'.$imagegrid.'</div>';
?>

</body>
</html>
