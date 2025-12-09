<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$cntid = $_POST['cntid'];
$quizid = $_POST['quizid'];
$questionid = $_POST['questionid'];
$index = $_POST['index'];
$cnttype = $_POST['cnttype']; 
$threadid = $_POST['threadid'];
$checkimsi = $_POST['checkimsi']; 
$userid = $_POST['userid'];
$studentid = $_POST['studentid'];
$inputtitle = $_POST['inputtitle']; 
 

$inputtext0 = $_POST['inputtext0']; 
$inputtext1 = $_POST['inputtext1']; 
$inputtext2 = $_POST['inputtext2']; 
$inputtext3 = $_POST['inputtext3']; 
$inputtext4 = $_POST['inputtext4']; 
$inputtext5 = $_POST['inputtext5']; 


$inputtext0=str_replace("\(","$",$inputtext0);
$inputtext1=str_replace("\(","$",$inputtext1);
$inputtext2=str_replace("\(","$",$inputtext2);
$inputtext3=str_replace("\(","$",$inputtext3);
$inputtext4=str_replace("\(","$",$inputtext4);
$inputtext5=str_replace("\(","$",$inputtext5);

$inputtext0=str_replace("\)","$",$inputtext0);
$inputtext1=str_replace("\)","$",$inputtext1);
$inputtext2=str_replace("\)","$",$inputtext2);
$inputtext3=str_replace("\)","$",$inputtext3);
$inputtext4=str_replace("\)","$",$inputtext4);
$inputtext5=str_replace("\)","$",$inputtext5);

$inputtext0=str_replace("$$","$",$inputtext0);
$inputtext1=str_replace("$$","$",$inputtext1);
$inputtext2=str_replace("$$","$",$inputtext2);
$inputtext3=str_replace("$$","$",$inputtext3);
$inputtext4=str_replace("$$","$",$inputtext4);
$inputtext5=str_replace("$$","$",$inputtext5);

$inputtext0=str_replace("**","$",$inputtext0);
$inputtext1=str_replace("**","$",$inputtext1);
$inputtext2=str_replace("**","$",$inputtext2);
$inputtext3=str_replace("**","$",$inputtext3);
$inputtext4=str_replace("**","$",$inputtext4);
$inputtext5=str_replace("**","$",$inputtext5);

$inputtext0=str_replace("($","$",$inputtext0);
$inputtext1=str_replace("($","$",$inputtext1);
$inputtext2=str_replace("($","$",$inputtext2);
$inputtext3=str_replace("($","$",$inputtext3);
$inputtext4=str_replace("($","$",$inputtext4);
$inputtext5=str_replace("($","$",$inputtext5);

$inputtext0=str_replace("$)","$",$inputtext0);
$inputtext1=str_replace("$)","$",$inputtext1);
$inputtext2=str_replace("$)","$",$inputtext2);
$inputtext3=str_replace("$)","$",$inputtext3);
$inputtext4=str_replace("$)","$",$inputtext4);
$inputtext5=str_replace("$)","$",$inputtext5);


$checkimsi = $_POST['checkimsi']; 
$timecreated=time();
$aweekago=$timecreated-604800;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($role==='student')
	{
	echo '저장권한이 없습니다.';
	exit();
	}

if($eventid==1) // 개념 설명
	{
	$record = new stdClass();
	$record->id = $cntid;
	$record->reflections0 = $inputtext0;
	$record->reflections1 = $inputtext1;
	$record->maintext = $inputtext2;
	$record->timemodified = $timecreated;
	$DB->update_record('icontent_pages', $record);
 	echo json_encode( array("cntid" =>$cntid) );
	} 
elseif($eventid==11) // 개념 설명
	{
	$record = new stdClass();
	$originalcnt=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where type LIKE 'original' AND contentstype='$cnttype' AND contentsid='$cntid'  ORDER BY id DESC LIMIT 1");  
	 
	//$record->id = $cntid;
	$record->type = 'complementary'; //보충컨텐츠
	$record->userid = $USER->id;
	$record->subject = $originalcnt->subject;
	$record->chapter = $originalcnt->chapter;
	$record->ntopic = $originalcnt->ntopic;
	$record->topictitle = $originalcnt->topictitle;
	$record->npage = $originalcnt->npage;
	$record->title = $originalcnt->topictitle.' 보충 : '.$inputtitle;
	$record->helpcnt = $inputtext0;
	$record->text = $inputtext1;
	$record->contentstype = $originalcnt->contentstype;
	$record->contentsid = $originalcnt->contentsid;
	$record->timemodified = $timecreated;
	$record->timecreated = $timecreated;
	$DB->insert_record('abessi_ankiquiz', $record);
	//$DB->update_record('abessi_ankiquiz', $record);
 	echo json_encode( array("cntid" =>$originalcnt->contentsid) );
	} 
elseif($eventid==12) // fixanki
	{
	$record = new stdClass();
	//$cnt=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id LIKE '$quizid'  ORDER BY id DESC LIMIT 1");  
	 
	$record->id = $quizid;
	$record->title =$inputtitle;
	$record->helpcnt = $inputtext0;
	$record->text = $inputtext1;
	$record->timemodified = $timecreated; 
	 
	$DB->update_record('abessi_ankiquiz', $record);
 	echo json_encode( array("cntid" =>$quizid) );
	} 
elseif($eventid==13) // add quizid to thread
	{

	$ankihtml='qid='.$quizid.'&studentid='.$userid;
	$DB->execute("UPDATE {abessi_today} SET nextanki='$ankihtml' WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");	
	
	$record = new stdClass();
	//$cnt=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id LIKE '$quizid'  ORDER BY id DESC LIMIT 1");  
	$lastthread=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where id LIKE '$threadid'  ORDER BY id DESC LIMIT 1");   

	$record->id = $threadid; 
	if($lastthread->quiz1==NULL)$record->quiz1= $quizid; 
	elseif($lastthread->quiz2==NULL)$record->quiz2= $quizid;
	elseif($lastthread->quiz3==NULL)$record->quiz3= $quizid;
	elseif($lastthread->quiz4==NULL)$record->quiz4= $quizid;
	elseif($lastthread->quiz5==NULL)$record->quiz5= $quizid;
	elseif($lastthread->quiz6==NULL)$record->quiz6= $quizid;
	elseif($lastthread->quiz7==NULL)$record->quiz7= $quizid;
	elseif($lastthread->quiz8==NULL)$record->quiz8= $quizid;
	elseif($lastthread->quiz9==NULL)$record->quiz9= $quizid;
	elseif($lastthread->quiz10==NULL)$record->quiz10= $quizid;
	elseif($lastthread->quiz11==NULL)$record->quiz11= $quizid;
	elseif($lastthread->quiz12==NULL)$record->quiz12= $quizid;
	
	$record->timemodified = $timecreated; 
	 
	$DB->update_record('abessi_ankithread', $record); 

	$duration=15;
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
 
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated>'$aweekago'  ORDER BY id DESC LIMIT 1    ");
	if($exist->id==NULL)
		{
		$duration=$timecreated+$duration*60;
		$inputtext='ANKI 활동이 출제되었습니다. ';
		$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','anki','$USER->id','begin','$wboardid','$duration','$inputtext','$timecreated')"); 
		}




 	echo json_encode( array("cntid" =>$quizid) );
	} 
elseif($eventid==14) // createThread
	{
	$record = new stdClass();
	$lastthread=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where type LIKE 'topic'  ORDER BY id DESC LIMIT 1");  
	$nextid=$lastthread->id+1;
	$record->userid = $USER->id; 
	$record->studentid = $studentid; 
	$record->type = 'topic'; 
	$record->timemodified = $timecreated;
	$record->timecreated = $timecreated;
	 
	$DB->insert_record('abessi_ankithread', $record);
 	echo json_encode( array("nextid" =>$nextid) );
	}
elseif($eventid==15) // questionlogktm
	{
	$record = new stdClass();
	$halfdayago=$timecreated-43200;
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquestionlogktm where userid LIKE '$studentid' AND quizid LIKE '$quizid' AND timecreated > '$halfdayago'  ORDER BY id DESC LIMIT 1");  

	if($exist->id==NULL)
		{
		$record->userid = $studentid;
		$record->quizid = $quizid;
		$record->qstn1 = $index;	 
		$record->timecreated = $timecreated;			
		$DB->insert_record('abessi_ankiquestionlogktm', $record);
		} 
	else
		{
		if($exist->qstn1==NULL)$record->qstn1 = $index;
		elseif($exist->qstn2==NULL)$record->qstn2 = $index;
		elseif($exist->qstn3==NULL)$record->qstn3 = $index;
		elseif($exist->qstn4==NULL)$record->qstn4 = $index;
		elseif($exist->qstn5==NULL)$record->qstn5 = $index;
		elseif($exist->qstn6==NULL)$record->qstn6 = $index;
		elseif($exist->qstn7==NULL)$record->qstn7 = $index;
		elseif($exist->qstn8==NULL)$record->qstn8 = $index;
		elseif($exist->qstn9==NULL)$record->qstn9 = $index;
		elseif($exist->qstn10==NULL)$record->qstn10 = $index;

		$record->id = $exist->id; 

		$record->timecreated = $timecreated;
		$DB->update_record('abessi_ankiquestionlogktm', $record);
		}

 	echo json_encode( array("cntid"=>$quizid) );
	}
elseif($eventid==2) // 문항설명
	{
	$record = new stdClass();
	$record->id = $cntid;
	$record->userid = $userid;
	$record->reflections0 = $inputtext0;
	$record->reflections1 = $inputtext1;
	$record->mathexpression = $inputtext2;
	$record->ans1 = $inputtext3;
	$record->timemodified = $timecreated;
	$DB->update_record('question', $record);
	echo json_encode( array("cntid" =>$cntid) );
	} 
elseif($eventid==3) // 블로그 자동화
	{ 
	$image1 = $_POST['image1']; 
	$image2 = $_POST['image2']; 
	$image3 = $_POST['image3']; 
	$image4 = $_POST['image4']; 
	$image5 = $_POST['image5']; 
	$image6 = $_POST['image6']; 
	$image7 = $_POST['image7']; 
	$image8 = $_POST['image8']; 
	$image9 = $_POST['image9']; 
	$image10 = $_POST['image10']; 
	$image11 = $_POST['image11']; 
	$image12 = $_POST['image12']; 

	$guidetext1 = $_POST['guidetext1']; 
	$guidetext2 = $_POST['guidetext2']; 
	$guidetext3 = $_POST['guidetext3']; 
	$guidetext4 = $_POST['guidetext4']; 
	$guidetext5 = $_POST['guidetext5']; 
	$guidetext6 = $_POST['guidetext6']; 
	$guidetext7 = $_POST['guidetext7']; 
	$guidetext8 = $_POST['guidetext8']; 
	$guidetext9 = $_POST['guidetext9']; 
	$guidetext10 = $_POST['guidetext10']; 
	$guidetext11 = $_POST['guidetext11']; 
	$guidetext12 = $_POST['guidetext12']; 

	$record = new stdClass();
	$record->id = $cntid;
	$record->userid = $userid;
	$record->img1 = $image1;
	$record->img2 = $image2;
	$record->img3 = $image3; 
	$record->img4 = $image4;
	$record->img5 = $image5;
	$record->img6 = $image6;
	$record->img7 = $image7;
	$record->img8 = $image8; 
	$record->img9 = $image9;
	$record->img10 = $image10;
	$record->img11 = $image11;
	$record->img12 = $image12; 

	$record->prompt1 = $guidetext1;
	$record->prompt2 = $guidetext2;
	$record->prompt3 = $guidetext3;
	$record->prompt4 = $guidetext4;
	$record->prompt5 = $guidetext5; 
	$record->prompt6 = $guidetext6;
	$record->prompt7 = $guidetext7;
	$record->prompt8 = $guidetext8;
	$record->prompt9 = $guidetext9;
	$record->prompt10 = $guidetext10; 
	$record->prompt11 = $guidetext11;
	$record->prompt12 = $guidetext12; 

	$record->timemodified = $timecreated;
	$DB->update_record('abessi_blog', $record);
	echo json_encode( array("cntid" =>$cntid) );
	} 
elseif($eventid==4) // 보충컨텐츠
	{ 
	$record = new stdClass();
	$cnttext=$DB->get_record_sql("SELECT * FROM mdl_abessi_adaptivecontents where contentstype='$cnttype' AND contentsid='$cntid'  ORDER BY id DESC LIMIT 1");  
	$record->userid = $userid;
	$record->contentstype = $cnttype;
	$record->contentsid = $cntid;
	$record->cnttext1 = $inputtext1;
	$record->cnttext2 = $inputtext2;
	$record->cnttext3 = $inputtext3;
	$record->cnttext4 = $inputtext4;
	$record->cnttext5 = $inputtext5;
	$record->timemodified = $timecreated;
	if($cnttext->id==NULL)$DB->insert_record('abessi_adaptivecontents', $record);
	else $DB->update_record('abessi_adaptivecontents', $record);
	echo json_encode( array("cntid" =>$cntid) );
	} 	
elseif($eventid==5) // 보충컨텐츠
	{ 
	if($cntid==NULL)$cntid=0;
	if($cnttype==NULL)$cnttype=0;
	$record = new stdClass();
	$record->userid = $USER->id;
	$record->contentstype = $cnttype;
	$record->contentsid = $cntid;
	$record->cnttext = $inputtext1; 
	$record->timemodified = $timecreated;
	$DB->insert_record('abessi_onetimeusecontents', $record);	
	echo json_encode( array("otuid" =>$otuid) );
	} 
?>