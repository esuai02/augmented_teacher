<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;


if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')$url = "https://";   
else $url = "http://";   
$url.= $_SERVER['HTTP_HOST'];   
$url.= $_SERVER['REQUEST_URI'];    
if(strpos($url, 'php?id')!= false)$studentid=required_param('id', PARAM_INT); 
else $studentid=$USER->id;


$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$symbol=substr($username->firstname,0, 3); 
$myteacher=$DB->get_record_sql("SELECT * FROM mdl_user_info_data where fieldid=64 AND data LIKE '%$symbol%' ORDER BY id  DESC  LIMIT 1");
$teacherid=$myteacher->userid;
$tname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$teachername=$tname->firstname.$tname->lastname;

if(strpos($url, 'index.php')!= false)$ailink='ai_index.html';
if(strpos($url, 'fullengagement.php')!= false)$ailink='ai_fullengagement.html';
if(strpos($url, 'schedule.php')!= false)$ailink='ai_schedule.html';
if(strpos($url, 'today.php')!= false)$ailink='ai_today.html';
if(strpos($url, 'missionhome.php')!= false)$ailink='ai_missionhome.html';
if(strpos($url, 'selectmission.php')!= false)$ailink='ai_selecthome.html';
if(strpos($url, 'editschedule.php')!= false)$ailink='ai_editschedule.html';
if(strpos($url, 'edittoday.php')!= false)$ailink='ai_edittoday.html';

include_once("intervention.php");
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$studentid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;
require_login();

 
$img=$DB->get_record_sql("SELECT data AS name FROM mdl_user_info_data where userid='$USER->id' and fieldid='59' ");
$userpic=$img->name;
 //  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 //<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
 
echo '
 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 



  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 
<script>
var statusIntervalId = window.setInterval(update, 5000);
function update() {
$.ajax({
    url: "/moodle/theme/adaptable/layout/includes/check_status.php",
    type: "POST",
    dataType: "json",
    success: function (data){
	if(data.mid=="100")
		{
	 	var message=data.content;
		 
 		$("#myModal").modal("show");

	    window > setTimeout(function() {
	        parent.$.colorbox.close();
	    }, 10000);
	}
	else if(data.mid=="61")
		{
 		var message=data.content;
	   	setTimeout(function() {
	       		$.colorbox({
	            			escKey: true,
	            			innerWidth: 1200,
	            			innerHeight: 600,
	           	 		html: "<iframe width=1200 height=600 src=https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?&theme=bubble&id="+message+" frameborder=0 border=0 allowfullscreen></iframe>"
	        			});
	    		}, 4000);
    		window > setTimeout(function() {
        		parent.$.colorbox.close();
    		}, 6000000);
	}
	else if(data.mid=="6")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href=https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?&theme=bubble&id="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 600,
	            innerHeight: 400,
	            html: popup+" target=_blank><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/mail.gif width=300 ><br>yotube</a></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 10000);
	}
	else if(data.mid=="51")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center>"
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 900,
	            innerHeight: 600,
	            html: popup+" <img src="+message+" width=800 ></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 60000);
	}
	else if(data.mid=="5")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 600,
	            innerHeight: 400,
	            html: popup+" target=_blank><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/mail.gif width=300 ><br>hint</a></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 10000);
	}
	else if(data.mid=="4")
	{
	/////////////////////////// under coding /////////////////////////////
            var userpic = document.cookie;	 
	var message=data.content;
 	var popup="<br><h4 align=center><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/einstein.png width=150><hr><br></h4> <h3><p align=center>"+message;

	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
 	           innerWidth: 600,
 	           innerHeight: 400,
 	           html: popup+" </h3></p><br><br><h5><p align=center><a href=https://mathking.kr/moodle/message/index.php?id="+data.sender+" target=_blank>'.get_string('reply', 'quiz').'</a></p></h5> "
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 20000);
	}
	else if(data.mid=="31")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 1200,
	            innerHeight: 600,
	            html: "<iframe width=1200 height=600 src="+message+" frameborder=0 border=0 allowfullscreen></iframe>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 6000000);
	}
	else if(data.mid=="3")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 600,
	            innerHeight: 400,
	            html: popup+" target=_blank><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/mail.gif width=300 ><br>yotube</a></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 10000);
	}
	else if(data.mid=="21")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 1200,
	            innerHeight: 600,
	            html: "<iframe width=1200 height=600 src="+message+" frameborder=0 border=0 allowfullscreen></iframe>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 6000000);
	}
	else if(data.mid=="2")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 600,
	            innerHeight: 400,
	            html: popup+" target=_blank><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/mail.gif width=300 ><br>whiteboard</a></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 10000);
	}
	else if(data.mid=="11")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 1200,
	            innerHeight: 900,
	            html: "<iframe width=1200 height=900 src="+message+" frameborder=0 border=0 allowfullscreen></iframe>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 6000000);
	}
	else if(data.mid=="1")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 600,
	            innerHeight: 400,
	            html: popup+" target=_blank><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/mail.gif width=300 ><br>mathking</a></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 10000);
	}
	else if(data.mid=="123")
	{
 	var message=data.content;
	var popup="<br><h2 align=center>New message ! </h2> <hr><p align=center><a href=http://www.whiteboard.moreleap.com/replay.php?speed=9&id="+message
	    setTimeout(function() {
	        $.colorbox({
	            escKey: true,
	            innerWidth: 600,
	            innerHeight: 400,
	            html: popup+" target=_blank><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/mail.gif width=300 ><br>hint</a></p>"
	        });
	    }, 4000);
    window > setTimeout(function() {
        parent.$.colorbox.close();
    }, 10000);
	}


}	 
});
}
</script>';
 

//////////////////////// 오늘의 활동 현황판 //////////////////
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

	$userid=$studentid;

	$timeafter=time()-604800;
	$wboard=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$userid' AND ( (turn LIKE '1')  OR ( turn LIKE '0' AND status LIKE 'ask' ) ) AND timemodified > '$timeafter' AND status NOT LIKE 'complete' ");
	$waitinglist= json_decode(json_encode($wboard), True);
	$list1=NULL;
	$count=0;

	unset($value);
	foreach(array_reverse($waitinglist) as $value)
		{	
		$count++;
		$boardid=$value['wboardid'];
		$elapsed=(time()-$value['timemodified'])/60;
		$contentsid=$value['contentsid'];
		$wboardid=$value['wboardid'];
 
		$qinfo=$DB->get_record_sql("SELECT questiontext  FROM mdl_question WHERE id='$contentsid'");
		$contentstext=$qinfo->questiontext;
		

		if(strpos($contentstext, 'ifminassistant')!= false)$contentstext=substr($contentstext, 0, strpos($contentstext, "<p>{ifminassistant}"));  
		if(strpos($contentstext, '/MY')!= false&&strpos($contentstext, 'slowhw')!= false)$contentstext='<p> MY A step </p>';
		if(strpos($contentstext, 'shutterstock')!= false)
			{
			$contentstext=substr($contentstext, 0, strpos($contentstext, '{ifminassistant}'));   
			$contentstext=strstr($contentstext, '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
			}


	 	$list1.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'" target="_blank" ><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/question_mark.png" width="30">
		<span class="tooltiptext2"><h3>'.round($elapsed,0).'분 </h3>'.$contentstext.'</div></a>&nbsp;';
		}
	 if($count>0.5) $Qlist.='<tr  align="left"><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a><td align="left">'.$list1;	 
 

 	$telapsed=time()-604800;
	
	$Qflag = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid,  mdl_question_attempt_steps.userid AS userid, mdl_question_attempts.checkflag AS checkflag, mdl_question_attempts.timemodified AS timemodified FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE (mdl_question.name LIKE '%MX%' OR mdl_question.name LIKE '%MY%') AND mdl_question_attempt_steps.userid='$userid' AND flagged ='1'  AND mdl_question_attempts.timemodified > '$telapsed' ");
	  
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
		if($message->timemodified > $telapsed && $message->turn==0)
			{
			$flaglist.='';
			$nflag=$nflag-1;
			}
		else $flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/Moodle/flag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
		}
	if($nflag==0)$flaglist.='';
	elseif($count==0 && $nflag!=NULL) $Qlist.='<tr  align="left"><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a></td><td align="left">'.$flaglist;	 
	if($count>0 &&  $nflag!=NULL)  $Qlist.=$flaglist;

	$telapsed2=time()-86400;
	$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE (mdl_question.name LIKE '%MX%' OR mdl_question.name LIKE '%MY%') AND mdl_question_attempt_steps.userid='$userid' AND (state='gradedwrong' OR state ='gradedpartial')   AND mdl_question_attempt_steps.timecreated > '$telapsed2'   ");
	$result1 = json_decode(json_encode($questionattempts), True);
	//$nwrong=count($result1);
 	$ntry=0;
	$marks=NULL;
	unset($value);
	foreach(array_reverse($result1) as $value)
		{
		$state=NULL;
 		$questionid=$value['questionid'];
		$message=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentsid='$questionid'  ORDER BY id ASC LIMIT 1  ");
 
		$mstatus=$message->status;  
		$mturn=$message->turn; 
  
		if($mstatus==='complete' || $mstatus==='reply' || $mstatus==='review'  || $mturn==1)
			{
			$marks.='';
			}
		else
			{
			$tpassed=round((time()-$value['timecreated'])/60,0);
			if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
			if(strpos($value['questiontext'], '/MY')!= false&&strpos($value['questiontext'], 'slowhw')!= false)$value['questiontext']='<p> MY A step </p>';
			if(strpos($value['questiontext'], 'shutterstock')!= false)
				{
				$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '{ifminassistant}'));   
				$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
				}
 
			if($value['state']===gradedpartial)
				{   
				$state=' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial1.png" width=20><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				}
			elseif($value['state']==='gradedwrong' && strpos($value['responsesummary'], '{')== false)
				{ 
				$state=' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong2.png" width=20><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				}

			$ntry++;
			if($ntry<=10)$marks.=$state;
			}
	  
		}
	if($count==0 && $nflag==0 && $ntry!=0) $Qlist.=$marks.'('.$ntry.')';	
	else $Qlist.=$marks;



$instantmessage=' <span align="center">'.$Qlist.'</span> ';
 
$maxtime=time()-43200;
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum1=count($recentquestions);
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$studentid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum2=count($recentquestions);
$Qnum2=$Qnum1+$Qnum2;
$ratio1= round($Qnum1/($Qnum2-0.0001)*100,3);
if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
if($ratio1==0 && $Qnum2==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';


$mark4='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"> '.$ratio1.'% </span>';
 
$maxtime=time()-604800*3;
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum11=count($recentquestions);
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$studentid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum22=count($recentquestions);
$Qnum22=$Qnum11+$Qnum22;

$ratio2=round($Qnum11/($Qnum22-0.0001)*100,3);
if($ratio2<70)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio2<75)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio2<80)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio2<85)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio2<90)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio2<95)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';

//$mark5='<span class="" style="font-size: 10pt; color: rgb(255,255, 255);">('.$ratio2.'%)</span>';
if($ratio1-$ratio2>=20)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji1.png" width=60 ></span>';
elseif($ratio1-$ratio2>=15)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji2.png" width=60 ></span>';
elseif($ratio1-$ratio2>=10)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji3.png" width=60 ></span>';
elseif($ratio1-$ratio2>=5)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji4.png" width=60 ></span>';
elseif($ratio1-$ratio2>=0)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji5.png" width=60 ></span>';
elseif($ratio1-$ratio2>=-5)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji6.png" width=60 ></span>';
elseif($ratio1-$ratio2>=-10)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji7.png" width=60 ></span>';
elseif($ratio1-$ratio2>=-15)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji8.png" width=60 ></span>';
else $mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji9.png" width=60 ></span>';
if($Qnum1==0) $mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji0.gif" width=60 ></span>';
if($ratio2==0  && $Qnum22==0) $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

// how fast student are solving questions ?  by tslee
  
// how fast student are solving questions ?  by tslee
	// 이곳에 몰입이탈 알고리즘 배치 (연속 RED, 속도 저하, 화이트보드 포함 현재 시간과 5분 이상 연장된 경우, $userid 로 검색
//	include("/home/moodle/public_html/moodle/local/augmented_teacher/teachers/detecteng.php");
$engagement3 = $DB->get_record_sql("SELECT speed,todayscore FROM  mdl_abessi_indicators WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators
 
$todayscore=$engagement3->todayscore;
$tspeed=$engagement3->speed;
 
	 // 풀이 속도 관리
 
	if($tspeed<10)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed6.png';
	elseif($tspeed<20)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed5.png';
	elseif($tspeed<30)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed4.png';
	elseif($tspeed<60)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed3.png';
	elseif($tspeed<90)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed2.png';
	elseif($tspeed<120)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed1.png';
	elseif($tspeed>120)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png';
	if($tspeed<0)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png';
  
$wboard1=$DB->get_record_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$studentid' AND  userrole LIKE 'teacher' AND wboardid NOT LIKE '%tsDoHfRT%'  AND turn LIKE '0'   AND  status NOT LIKE 'complete' ORDER BY timemodified DESC LIMIT 1 ");
$time1 = $wboard1->timemodified;
$wboard2=$DB->get_record_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$studentid' AND  userrole LIKE 'student'  AND wboardid NOT LIKE '%tsDoHfRT%' AND turn LIKE '0'   AND  status NOT LIKE 'complete' ORDER BY timemodified DESC LIMIT 1 ");
$time2 = $wboard2->timemodified;
$quizattempt = $DB->get_record_sql("SELECT mdl_quiz.id AS qid, mdl_quiz_attempts.timemodified AS timemodified, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.comment AS comment FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.userid='$studentid' AND mdl_quiz_attempts.comment !='NULL' ORDER BY mdl_quiz_attempts.timemodified DESC LIMIT 1 ");
$time3 =$quizattempt->timemodified;
$getlog1 = $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE userid='$studentid' AND page LIKE 'studentindex' ORDER BY id DESC LIMIT 1");
$timevisited1 = $getlog1->timecreated;
$getlog2 = $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE userid='$studentid' AND page LIKE 'studentfullengagement' ORDER BY id DESC LIMIT 1");
$timevisited2 = $getlog2->timecreated;
$recentmessage='';

if($time1>$timevisited1 )$recentmessage='<span style="color:white; font-size:18">새로운 선생님 풀이가 있습니다.<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank"> (확인)</a></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
if($time2>$timevisited2 )$recentmessage='<span style="color:white; font-size:18">질문에 대한 답이 도착하였습니다.<a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullengagement.php?id='.$studentid.'" target="_blank"> (확인)</a></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
if($time3>$timevisited1 )$recentmessage='<span style="color:white; font-size:18">최근 퀴즈결과에 대한 의견이 있습니다.<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank"> (확인)</a></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
 
 
$imgstatus=$recentmessage.'&nbsp;&nbsp;<img src='.$v_quiz.' width=50>&nbsp;&nbsp;&nbsp;&nbsp;'.$mark4.'&nbsp;<img src='.$imgtoday.' width=35><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/Preloader2.gif" width=60 ><img src='.$imgtoday2.' width=35>&nbsp;&nbsp;'.$mark5. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a  href="https://mathking.kr/moodle/message/index.php?id='.$teacherid.'" target="_blank"><img  src="https://download.seaicons.com/icons/aha-soft/free-large-boss/512/Teacher-icon.png" align="center" width=50></a>&nbsp;&nbsp;&nbsp;&nbsp;';

$timestart=time()-43200;
$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY id  DESC  LIMIT 1 ");
$tabtitle=$goal->text;
if($goal->text==NULL)$tabtitle=$username->firstname.$username->lastname.'&nbsp;'.get_string('mydashboard', 'local_augmented_teacher');
 echo ' 
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>'.$tabtitle.'</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" type="image/x-icon"/>

	<!-- Fonts and icons -->
	<script src="../assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {"families":["Montserrat:100,200,300,400,500,600,700,800,900"]},
			custom: {"families":["Flaticon", "LineAwesome"], urls: ["../assets/css/fonts.css"]},
			active: function() {
				sessionStorage.fonts = true;
			}
		});
	</script>

	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">

	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>

	<div class="wrapper">
		<div class="main-header">
			<!-- Logo Header -->
			<div align="center" class="logo-header">
			<b><a href="https://mathking.kr/moodle/my" target="_blank">카이스트 터치수학</a></b></div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="light-blue">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid">
					<div class="navbar-minimize">
						<button class="btn btn-minimize btn-rounded">
							<i class="la la-navicon"></i>
						</button>
					</div>
					<div>
					<span style="color:yellow;float:center;">'.$instantmessage.'</span>
					</div>
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
						 '.$imgstatus.' 		 
						<li class="nav-item">
							<a href="#" class="nav-link quick-sidebar-toggler">
								<i class="flaticon-shapes-1"></i>
							</a>
						</li>
					</ul>
				</div>
			</nav>
			<!-- End Navbar -->
		</div>

		<!-- Sidebar -->
		<div class="sidebar">
			<!--
				Tip 1: You can change the background color of the sidebar using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				Tip 2: you can also add an image using data-image attribute
			-->
			<div class="sidebar-background"></div>
			<div class="sidebar-wrapper scrollbar-inner">
				<div class="sidebar-content">
					<div class="user">
						<div class="photo">
							<img src="https://mathking.kr/moodle/user/pix.php/'.$studentid.'/f1.jpg"  alt="image profile">
						</div>
						<div class="info">
							<a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
								
									<span class="user-level"><h5>'.$username->firstname.$username->lastname.'</h5></span>
									<span class="caret"></span>
								</span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
<li><a href="https://mathking.kr/moodle/user/profile.php?id='.$studentid.'" target="_blank">사용자 정보</a></li>
<li><a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$studentid.'" target="_blank">정보수정</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?id='.$studentid.'" target="_blank">출결정보</a></li>
<li><a href="https://mathking.kr/moodle/report/log/user.php?mode=today&course=1&&id='.$studentid.'" target="_blank">활동로그</a></li>
<li><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.'" target="_blank">메세지</a></li>
								</ul>
							</div>
						</div>
					</div>
					<ul class="nav">
						<li class="nav-item active">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">
								<i class="flaticon-desk"></i>
								<p>내 공부방</p>
								 
							</a>
						</li>
						<li class="nav-item">
							<a href="http://mathking.kr/moodle/local/augmented_teacher/students/missionguide.html">
								<i class="flaticon-plus"></i>
								<p>활동추가</p>
								 
							</a>
						</li>
						<li class="nav-item">
							<a href="fullengagement.php?id='.$studentid.'">
								<i class="flaticon-idea"></i>
								<p>기억연장</p>
								 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=83048">
								<i class="flaticon-agenda-1"></i>
								<p>개념노트</p> 
							</a>
						</li>
						</li>
						<li class="nav-item">
							<a href="http://mathking.kr/moodle/local/augmented_teacher/students/peerlearning.html">
								<i class="flaticon-users"></i>
								<p>동료학습</p>
								<span class="badge badge-count">준비중</span>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
							<h3 class="text-section">활동관리</h3>
						</li>

			 


						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1">
								<i class="flaticon-calendar"></i>
								<p>시간표</p>
								 
							</a>
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=43200">
									<i class="flaticon-star"></i>
									<p>오늘활동</p>
									 
									</a>	
						</li>		
						<li class="nav-item">
							<a href="timeline.php?id='.$studentid.'">
								<i class="flaticon-analytics"></i>
								<p>타임라인</p>
								<span class="badge badge-count badge-success">부모님용</span>
							</a>
						</li>
						<li class="nav-item">
									<a href="mentors.html">
									<i class="flaticon-chat-8"></i>
									<p>미션멘토</p>
									<span class="badge badge-count">준비중</span>
									</a>	
						</li>
';
if($role==='teacher')
{ 
	 echo '
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
							<h4 class="text-section">미션관리</h4>
						</li>
						<li class="nav-item"> 
							<a href="'.$ailink.'">
								<i class="flaticon-share-1"></i>
								<p>인공지능</p>
								<span class="badge badge-count badge-info">1</span>
							</a>
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=1&cid=0">
									<i class="flaticon-symbol-1"></i>
									<p>개념미션</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=2&cid=0">
									<i class="flaticon-symbol-1"></i>
									<p>심화미션</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=3&cid=0" target="_blank" >
									<i class="flaticon-symbol-1"></i>
									<p>내신미션</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/mod/lesson/view.php?id=65208&pageid=186336&startlastseen=no" target="_blank" >
									<i class="flaticon-symbol-1"></i>
									<p>수능미션</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/mod/lesson/view.php?id=66557&pageid=219579&startlastseen=no" target="_blank" >
									<i class="flaticon-list"></i>
									<p>인증시험</p>									
									</a>	
						</li>';
}
echo '
 
					</ul>
				</div>
			</div>
		</div> 
		<!-- End Sidebar --> 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					 <div class="row">
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<img src="https://mathking.kr/IMG/HintIMG/BESSI1579344522.png" height=80>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">물리학과</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>										
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<img src="https://mathking.kr/IMG/HintIMG/BESSI1575978058.png" height=80>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">기계공학과</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>										
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					 	<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<img src="https://mathking.kr/IMG/HintIMG/BESSI1575978150.png" height=80>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">전산학과</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>
												
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<img src="https://mathking.kr/IMG/HintIMG/BESSI1575983518.png" height=80> 
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">바이오 및 뇌</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>';

?>