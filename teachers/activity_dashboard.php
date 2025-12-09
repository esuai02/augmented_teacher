<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");


$tb=required_param('tb', PARAM_INT);
 
 
 
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
 
$star1='';
$star2='';
$star3='';
if($tb==1800)$star1='*';
if($tb==3600)$star2='*';
if($tb==10800)$star3='*';
echo '  <table align="center" style="width: 100%;"><tbody><tr>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/whiteboard.php?id='.$teacherid.'&tb=1800">30분전'.$star1.' </a> </td>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/whiteboard.php?id='.$teacherid.'&tb=3600">1시간 전'.$star2.'</a> </td>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/whiteboard.php?id='.$teacherid.'&tb=10800">3시간 전'.$star3.'</a><td>
<td align=center><a href="http://cafe.daum.net/ktmits/el3H">출결전달</a><td><tr></tbody></table><hr>';
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol2%'");
$userlist= json_decode(json_encode($mystudents), True);
 
$timestart2=time()-10800;
unset($user);
foreach($userlist as $user)
	{
	$userid=$user['id'];
	$firstname=$user['firstname'];
	$lastname=$user['lastname'];
	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0);
	if($nday==0)$nday=7;

	for($n1=0;$n1<8;$n1++)
	{ 
	$var='start'.$n1;
	$var2=$schedule->$var;
	$var3='duration'.$n1;
	$var4=$schedule->$var3;
	$tbegin=date("H:i",strtotime($var2));
		$time    = explode(':', $tbegin);
		$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;
 		if($var2!=NULL && $var4!=NULL )
		{	 
		$n2=(int)(($minutes-530)/30);	
 		 	
		$date=date(" h:i A");
		$date2=date("H:i",strtotime($date));
		$time2    = explode(':', $date2);
		$minutes2 =(int)( ($time2[0] * 60.0 + $time2[1] * 1.0)-30);
		$npresent=(int)(($minutes2-530)/30);	

		if($minutes<500)$n2=0;
		if(($npresent==$n2+1||$npresent==$n2+2||$npresent==$n2+3||$npresent==$n2+4||$npresent==$n2+5||$npresent==$n2+6) && $nday==$n1)
			{	
			$lastaction=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' "); 
			$lastaction=$lastaction->maxtc;
			$lastaccess=time()-$lastaction;
			 
			if($lastaccess>36000)
				{
				$tinput=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_abessi_today where userid='$userid' "); 
				$inputtime=time()-$tinput->maxtc;    
				if( $npresent>=$n2 && $inputtime > 36000 ) $today .='&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13>'.$user['lastname'].'</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
			else 
				{
				$tinput=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_abessi_today where userid='$userid' "); 
				$inputtime=time()-$tinput->maxtc;
				if( $npresent>=$n2 && $inputtime > 36000 ) $today .='&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13>'.$user['lastname'].'</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
 				}
			}
		 
		} 
	}

	$checktoday = $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	$goalid=$checktoday->id;
	if($checktoday->timemodified>$timestart2 && $checktoday->submit==1)
	$confirm.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$firstname.$lastname.'</a>&nbsp;<input type="checkbox" name="checkAccount"  onClick="ChangeCheckBox2(4,\''.$userid.'\',\''.$goalid.'\', this.checked)"/>&nbsp;&nbsp;&nbsp;';

	$timestart3 =time()-30000;
	$tspent=time() - $checktoday->timemodified;
	$inspect=$DB->get_record_sql("SELECT data AS time FROM mdl_user_info_data where userid='$userid' AND fieldid='56' "); 
	$tinspect=$inspect->time*60;
	if($checktoday->timemodified>$timestart3 && $tspent > $tinspect && $checktoday->submit==0 )
		{
		$detectedusers.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$firstname.$lastname.'</a>&nbsp;<input type="checkbox" name="checkAccount"  onClick="ChangeCheckBox2(5,\''.$userid.'\',\''.$goalid.'\', this.checked)"/>&nbsp;&nbsp;&nbsp;';
		}
 	// 화이트보드 초기 작성자 피드백
	$tactive=time()-600;	
	$whiteboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$userid' AND timemodified>'$tactive' ");
	$result1 = json_decode(json_encode($whiteboards), True);
	unset($value);
	foreach(array_reverse($result1) as $value) 
		{
		$wbcreated=(time()-$value['timemodified'])/60;
		$encryption_id=$value['wboardid'];
    		$nstroke=(int)($value['nstroke']/2);
		$tlast=time()-$value['tlast'];
		$tlast=(int)($tlast/60);
		if($tlast>1000000)$tlast=0;  
		if($value['contentstype']==='question')
			{
			$statusdot='<img src=https://mathking.kr/IMG/HintIMG/BESSI1590403633001.png width=13>';
			if($nstroke >= 20)$statusdot='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13>';	
			else $statusdot='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13>';
			if($wbcreated<6)$statusdot='<img src=https://mathking.kr/IMG/HintIMG/BESSI1590465657001.png height=20>'; //new
			$activewb.= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$statusdot.'&nbsp;'.$lastname.'</a><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank" > ('.round($wbcreated,0).'"/'.$tlast.'"/'.$nstroke.'획)</a>'; // 최근 시작된 오답노트		
			}
 		}

	//화이트보드 상에서 질문
	$timeafter=time()-129600;
	$wboard=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$userid'  AND ( (turn LIKE '1')  OR ( turn LIKE '0' AND status LIKE 'ask' ) )AND timemodified > '$timeafter' AND status NOT LIKE 'complete' ");
	$waitinglist= json_decode(json_encode($wboard), True);
	$list1=NULL;
	$count=0;

	unset($value);
	foreach(array_reverse($waitinglist) as $value)
		{	
		$count++;
		$boardid=$value['wboardid'];
		$contentsid=$value['contentsid'];

		$elapsed=(time()-$value['timemodified'])/60;

		if(strpos($boardid, 'OVc4lRh')!==false)
			{
			$question = $DB->get_record_sql("SELECT questiontext AS text FROM mdl_question WHERE id='$contentsid' ");
			$questiontext=$question->text;
			if(strpos($questiontext, 'ifminassistant')!= false)$questiontext=substr($questiontext, 0, strpos($questiontext, "<p>{ifminassistant}"));  
			if(strpos($questiontext, '/MY')!= false&&strpos($questiontext, 'slowhw')!= false)$questiontext='<p> MY A step </p>';
			if(strpos($questiontext, 'shutterstock')!= false)
				{
				$questiontext=substr($questiontext, 0, strpos($questiontext, '{ifminassistant}'));   
				$questiontext=strstr($questiontext, '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
				}
			$qtype='문항질문';
			}		


		if(strpos($boardid, 'tsDoHfRT')!==false)
			{
			$question = $DB->get_record_sql("SELECT questiontext AS text FROM mdl_question WHERE id='$contentsid' ");
			$questiontext=$question->text;
			if(strpos($questiontext, 'ifminassistant')!= false)$questiontext=substr($questiontext, 0, strpos($questiontext, "<p>{ifminassistant}"));  
			if(strpos($questiontext, '/MY')!= false&&strpos($questiontext, 'slowhw')!= false)$questiontext='<p> MY A step </p>';
			if(strpos($questiontext, 'shutterstock')!= false)
				{
				$questiontext=substr($questiontext, 0, strpos($questiontext, '{ifminassistant}'));   
				$questiontext=strstr($questiontext, '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
				}
			$qtype='해설질문';
			}
		if(strpos($boardid, 'pageid')!==false)
			{
			$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
			$ctext=$getimg->pageicontent;
			$cmid=$getimg->cmid;
			$ctitle=$getimg->title;		 
			$htmlDom = new DOMDocument;
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
			$questiontext='<img src="'.$imgSrc.'" width= 600>';
			$qtype='개념질문';
			}
	 	$list1.= '&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'" target="_blank" ><div class="tooltip2">'.$qtype.'<span class="tooltiptext2"><h3>'.round($elapsed,0).'분전 </h3><hr>'.$value['helptext'].'<hr>'.$questiontext.'</span></div></a>&nbsp;';
		}
	 if($count>0.5) $Qlist.='<tr  align="left"><td>질의응답</td><td>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a><td align="left">'.$list1;	 
 

	$subject=$DB->get_record_sql("SELECT data AS subject FROM mdl_user_info_data where userid='$teacherid' and fieldid='57' ");
	if($subject->subject==='MATH')$contains='%MX%';
	elseif($subject->subject==='SCIENCE') $contains='%SCIENCE%';

	// 깃발표시 모니터링 & 피드백
 	 $telapsed=time()-86400;
	$Qflag = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid,  mdl_question_attempt_steps.userid AS userid, mdl_question_attempts.checkflag AS checkflag, mdl_question_attempts.timemodified AS timemodified FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question.name LIKE '$contains' AND mdl_question_attempt_steps.userid='$userid' AND flagged ='1'  AND mdl_question_attempts.timemodified > '$telapsed' ");
	  
	$result = json_decode(json_encode($Qflag), True);
	$nflag=count($result);
	$flaglist=NULL;
	unset($value);
	foreach($result as $value)
		{
		$questionid=$value['questionid'];
		$wboardid=$value['wboardid'];
		$tpassed=round((time()-$value['timemodified'])/60,0);
		if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
		if(strpos($value['questiontext'], '/MY')!= false&&strpos($value['questiontext'], 'slowhw')!= false)$value['questiontext']='<p> MY A step </p>';
		if(strpos($value['questiontext'], 'shutterstock')!= false)
			{
			$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '{ifminassistant}'));   
			$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
			}
	 
		$message=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$userid'   AND contentsid='$questionid'  ORDER BY id DESC LIMIT 1  ");
		if($message->timemodified > $telapsed && $message->turn==0 )
			{
			if($message->status==='review' || $message->status==='complete')$flaglist.='';
			if($message->status==='begin')$flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/pinkflag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
			if($message->status==='reply')$flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/greenflag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
			$nflag=$nflag-1;
			}
		else $flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/Moodle/flag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
		}
	if($nflag==0)$flaglist.='';
	elseif($count==0 && $nflag!=NULL) $Qlist.='<tr  align="left"><td>질의응답</td><td>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a></td><td align="left">'.$flaglist;	 
	if($count>0 &&  $nflag!=NULL)  $Qlist.=$flaglist;

	// 오답문제 처리 모니터링 & 피드백
	$telapsed2=time()-86400;
	$telapsed3=time()-1200;
	$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.timemodified AS timecreated, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question.name LIKE '$contains' AND mdl_question_attempt_steps.userid='$userid' AND (state='gradedwrong' OR state ='gradedpartial')   AND mdl_question_attempt_steps.timecreated > '$telapsed2'  ");
	$result1 = json_decode(json_encode($questionattempts), True);
	//$nwrong=count($result1);
 	$ntry=0;
	$nleft=0;
	$marks=NULL;
	unset($value);
	foreach(array_reverse($result1) as $value)
		{
		$state=NULL;
 		$questionid=$value['questionid'];
		$tpassed=round((time()-$value['timecreated'])/60,0);
		$message=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$userid' AND contentsid='$questionid'  ORDER BY id DESC LIMIT 1  ");
		//if($message->timemodified!==NULL)$tpassed=round((time()-$message->timemodified)/60,0);

		if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
		if(strpos($value['questiontext'], '/MY')!= false&&strpos($value['questiontext'], 'slowhw')!= false)$value['questiontext']='<p> MY A step </p>';
		if(strpos($value['questiontext'], 'shutterstock')!= false)
			{
			$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '{ifminassistant}'));   
			$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
			}
 
		if($value['state']==='gradedpartial')
			{   
			$nleft++;
			$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial1.png" width=20><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
			}
		elseif($value['state']==='gradedwrong' && strpos($value['responsesummary'], '{')== false)  // && strlen(trim($value['responsesummary'])) >0
			{ 
			$nleft++;
			$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong2.png" width=20><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
			} 
		
		if($message->timemodified > $telapsed2 && $message->turn==0 )
			{			
			if($message->status==='review' || $message->status==='complete')$marks.='';
			if($message->status==='begin')
				{
				$nleft--;
				$ntry++;
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/wrong_pink.png" width=20><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
				if($ntry<=10)$marks.=$state;
				}
			if($message->status==='reply')
				{
				$ntry++;
				$nleft--;
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/wrong_green.png" width=20><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
				if($ntry<=10)$marks.=$state;
				}
			}
		else
			{
			$ntry++;			
			if($ntry<=10)$marks.=$state;
			}
	  
		}
	if($count==0 && $nflag==0 && $nleft!=0) $Qlist.='<tr  align="left"><td>질의응답</td><td>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a></td><td align="left">'.$marks.'('.$nleft.')';	
	else $Qlist.=$marks.'</td></tr>';
 
	}
 

echo ' 	<table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td align="left">목표입력</td><td align="left">'.$today.'</td></tr></table><hr>
	<table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td align="left">오답작성</td><td align="left">'.$activewb.'</td></tr></table><hr>	
	<table style="width: 100%;"><tr><th width=5%></th><th width=5%></th><th width=85%></th></tr>'.$Qlist.'</table><hr>	

 	 <table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td>중간점검</td><td>&nbsp;&nbsp;&nbsp;'.$detectedusers.'</td></tr></table><hr>
	 <table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td>귀가요청</td><td align="left">&nbsp;&nbsp;&nbsp;'.$confirm.'</td></tr></table><hr>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
'; 
 
  
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
echo '</div>
<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
</p></div><div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"><p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>
<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
</div></div></div></div>

	<script>
	function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
	    var checkimsi = 0;
	    if(Checkvalue==true){
	       checkimsi = 1;
 	   }
  	 $.ajax({
  	      url: "check.php",
   	     type: "POST",
   	     dataType: "json",
   	     data : {"userid":Userid,
   	             "questionid":Questionid,
   	             "attemptid":Attemptid,
     	           "checkimsi":checkimsi,
    	             "eventid":Eventid,
    	           },
  	      success: function (data){  
    	    }
	    });
	}


	function ChangeCheckBox2(Eventid,Userid, Goalid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
	checkimsi = 1;
	}
	$.ajax({
	url: "../students/check.php",
	type: "POST",
	dataType: "json",
	data : {"userid":Userid,       
	"goalid":Goalid,
	"checkimsi":checkimsi,
	"eventid":Eventid,
	},
	success: function (data){  
	}
	});
	}


	function askstudent(Eventid,Studentid,Teacherid,Questionid)
	{
    	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"studentid":Studentid,
		"teacherid":Teacherid,
		"contentsid":Questionid,       	   
		      },
	 	success:function(data){
		}
	})
	}
	</script> 
  
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
';
 
echo '
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 800px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip1:hover .tooltiptext1 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 600px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 800px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
}
a.tooltips span:after {
  position: absolute;
  bottom: 100%;
  right: 1%;
  margin-left: -10px;
  width: 0;
  height: 0;
  border-bottom: 8px solid #23ad5f;
  border-right: 8px solid #0a5cf5;
  border-left: 8px solid #0a5cf5;
}
a:hover.tooltips span {
  visibility: visible;
  opacity: 1;
  top: 0px;
  right: 0%;
  margin-left: 10px;
  z-index: 999;
  border-bottom: 1px solid #15ff00;
  border-right: 1px solid #15ff00; 
  border-left: 1px solid #15ff00;
}

 

</style>';
include("quicksidebar.php");
?>