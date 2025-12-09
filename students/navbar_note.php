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

//include_once("intervention.php");
 
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
 
			<!-- End Navbar -->
		</div>

		<div class="sidebar">
 
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
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=83048">
								<i class="flaticon-agenda-1"></i>
								<p>개념노트</p>
								 
							</a>
						</li>
  
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">내 개념노트</h4>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav2">
								<i class="flaticon-agenda-1"></i>
								<p>초등수학</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav2"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82865"> <i class="flaticon-pencil"></i> <p>초등수학 4-1</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82867"> <i class="flaticon-pencil"></i> <p>초등수학 4-2</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82868"> <i class="flaticon-pencil"></i> <p>초등수학 5-1</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82870"> <i class="flaticon-pencil"></i> <p>초등수학 5-2</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82869"> <i class="flaticon-pencil"></i> <p>초등수학 6-1</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82866"> <i class="flaticon-pencil"></i> <p>초등수학 6-2</p></a></li> 
							</ul></div>
						</li>
 
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav3">
								<i class="flaticon-agenda-1"></i>
								<p>중등수학 1 - 1</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav3"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82804"> <i class="flaticon-pencil"></i> <p>1. 소인수분해</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82803"> <i class="flaticon-pencil"></i> <p>2. 최대공약수와 최소공배수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82802"> <i class="flaticon-pencil"></i> <p>3. 정수와 유리수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82801"> <i class="flaticon-pencil"></i> <p>4. 유리식의 계산</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82800"> <i class="flaticon-pencil"></i> <p>5. 문자와 식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82799"> <i class="flaticon-pencil"></i> <p>6. 일차방정식의 풀이</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82798"> <i class="flaticon-pencil"></i> <p>7. 일차방정식의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82797"> <i class="flaticon-pencil"></i> <p>8. 함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82796"> <i class="flaticon-pencil"></i> <p>9. 함수의 그래프와 활용</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav4">
								<i class="flaticon-agenda-1"></i>
								<p>중등수학 1 - 2</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav4"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82815"> <i class="flaticon-pencil"></i> <p>1. 자료의 정리</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82814"> <i class="flaticon-pencil"></i> <p>2. 자료의 분석</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82813"> <i class="flaticon-pencil"></i> <p>3. 기본도형</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82812"> <i class="flaticon-pencil"></i> <p>4. 위치 관계</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82811"> <i class="flaticon-pencil"></i> <p>5. 평행선</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82810"> <i class="flaticon-pencil"></i> <p>6. 작도와 합동</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82820"> <i class="flaticon-pencil"></i> <p>7. 다각형</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82819"> <i class="flaticon-pencil"></i> <p>8. 원과 부채꼴</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82818"> <i class="flaticon-pencil"></i> <p>9. 다면체</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82817"> <i class="flaticon-pencil"></i> <p>10. 회전체</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82816"> <i class="flaticon-pencil"></i> <p>11. 입체도형의 부피와 겉넓이</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav5">
								<i class="flaticon-agenda-1"></i>
								<p>중등수학 2 - 1</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav5"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82831"> <i class="flaticon-pencil"></i> <p>1. 유리수와 순환소수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82830"> <i class="flaticon-pencil"></i> <p>2. 단항식의 계산</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82829"> <i class="flaticon-pencil"></i> <p>3. 다항식의 계산(1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82828"> <i class="flaticon-pencil"></i> <p>4. 일차부등식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82827"> <i class="flaticon-pencil"></i> <p>5. 연립일차부등식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82826"> <i class="flaticon-pencil"></i> <p>6. 부등식의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82825"> <i class="flaticon-pencil"></i> <p>7. 연립일차방정식의 풀이</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82824"> <i class="flaticon-pencil"></i> <p>8. 연립일차방정식의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82823"> <i class="flaticon-pencil"></i> <p>9. 일차함수와 그 그래프(1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82822"> <i class="flaticon-pencil"></i> <p>10. 일차함수와 그 그래프(2)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82821"> <i class="flaticon-pencil"></i> <p>11. 일차함수와 일차방정식의 관계</p></a></li>

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav6">
								<i class="flaticon-agenda-1"></i>
								<p>중등수학 2 - 2</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav6"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82841"> <i class="flaticon-pencil"></i> <p>1. 삼각형의 성질(1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82840"> <i class="flaticon-pencil"></i> <p>2. 삼각형의 성질(2)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82839"> <i class="flaticon-pencil"></i> <p>3. 평행사변형</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82838"> <i class="flaticon-pencil"></i> <p>4. 여러 가지 사각형</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82837"> <i class="flaticon-pencil"></i> <p>5. 도형의 닮음</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82836"> <i class="flaticon-pencil"></i> <p>6. 평행선 사이의 선분의 길이의 비</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82835"> <i class="flaticon-pencil"></i> <p>7. 닮음의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82834"> <i class="flaticon-pencil"></i> <p>8. 피타고라스 정리</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82833"> <i class="flaticon-pencil"></i> <p>9. 경우의 수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82832"> <i class="flaticon-pencil"></i> <p>10. 확률</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav7">
								<i class="flaticon-agenda-1"></i>
								<p>중등수학 3 - 1</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav7"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82842"> <i class="flaticon-pencil"></i> <p>1. 제곱근의 뜻과 성질</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82856"> <i class="flaticon-pencil"></i> <p>2. 무리수와 실수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82855"> <i class="flaticon-pencil"></i> <p>3. 근호를 포함한 식의 계산 (1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82854"> <i class="flaticon-pencil"></i> <p>4. 근호를 포함한 식의 계산 (2)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82853"> <i class="flaticon-pencil"></i> <p>5. 다항식의 곱셈</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82852"> <i class="flaticon-pencil"></i> <p>6. 인수분해</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82851"> <i class="flaticon-pencil"></i> <p>7. 이차방정식의 풀이</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82850"> <i class="flaticon-pencil"></i> <p>8. 이차방정식의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82849"> <i class="flaticon-pencil"></i> <p>9. 이차함수의 그래프(1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82848"> <i class="flaticon-pencil"></i> <p>10. 이차함수의 그래프(2)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82847"> <i class="flaticon-pencil"></i> <p>11. 이차함수의 활용</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav8">
								<i class="flaticon-agenda-1"></i>
								<p>중등수학 3 - 2</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav8"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82846"> <i class="flaticon-pencil"></i> <p>1. 대푯값과 산포도</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82857"> <i class="flaticon-pencil"></i> <p>2. 피타고라스 정리</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82845"> <i class="flaticon-pencil"></i> <p>3. 피타고라스 정리와 도형</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82843"> <i class="flaticon-pencil"></i> <p>4. 피타고라스 정리의 평면도형에의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82860"> <i class="flaticon-pencil"></i> <p>5. 피타고라스 정리의 입체도형에의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82859"> <i class="flaticon-pencil"></i> <p>6. 삼각비</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82858"> <i class="flaticon-pencil"></i> <p>7. 삼각비의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82863"> <i class="flaticon-pencil"></i> <p>8. 원과 직선</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82862"> <i class="flaticon-pencil"></i> <p>9. 원주각</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82861"> <i class="flaticon-pencil"></i> <p>10. 원주각의 활용</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav9">
								<i class="flaticon-agenda-1"></i>
								<p>고등수학 상</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav9"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82706"> <i class="flaticon-pencil"></i> <p>1. 다항식의 연산</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82708"> <i class="flaticon-pencil"></i> <p>2. 나머지정리와 인수분해</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82710"> <i class="flaticon-pencil"></i> <p>3. 복소수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82707"> <i class="flaticon-pencil"></i> <p>4. 이차방정식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82711"> <i class="flaticon-pencil"></i> <p>5. 이차방정식과 이차함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82709"> <i class="flaticon-pencil"></i> <p>6. 고차방정식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82712"> <i class="flaticon-pencil"></i> <p>7. 연립방정식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82713"> <i class="flaticon-pencil"></i> <p>8. 부등식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82718"> <i class="flaticon-pencil"></i> <p>9. 이차부등식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82717"> <i class="flaticon-pencil"></i> <p>10. 평면좌표</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82716"> <i class="flaticon-pencil"></i> <p>11. 직선의 방정식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82715"> <i class="flaticon-pencil"></i> <p>12. 원의 방정식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82714"> <i class="flaticon-pencil"></i> <p>13. 도형의 이동</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82719"> <i class="flaticon-pencil"></i> <p>14. 부등식의 영역</p></a></li>

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav10">
								<i class="flaticon-agenda-1"></i>
								<p>고등수학 하</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav10"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82742"> <i class="flaticon-pencil"></i> <p>1. 집합의 뜻과 표현</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82729"> <i class="flaticon-pencil"></i> <p>2. 집합의 연산</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82733"> <i class="flaticon-pencil"></i> <p>3. 명제</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82738"> <i class="flaticon-pencil"></i> <p>4. 절대부등식</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82728"> <i class="flaticon-pencil"></i> <p>5. 함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82743"> <i class="flaticon-pencil"></i> <p>6. 유리식과 유리함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82741"> <i class="flaticon-pencil"></i> <p>7. 무리식과 무리함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82740"> <i class="flaticon-pencil"></i> <p>8. 순열</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82739"> <i class="flaticon-pencil"></i> <p>9. 조합</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav11">
								<i class="flaticon-agenda-1"></i>
								<p>고등수학 1</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav11"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82761"> <i class="flaticon-pencil"></i> <p>1. 지수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82751"> <i class="flaticon-pencil"></i> <p>2. 로그</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82752"> <i class="flaticon-pencil"></i> <p>3. 지수함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82753"> <i class="flaticon-pencil"></i> <p>4. 로그함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82754"> <i class="flaticon-pencil"></i> <p>5. 삼각함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82760"> <i class="flaticon-pencil"></i> <p>6. 삼각함수의 그래프</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82757"> <i class="flaticon-pencil"></i> <p>7. 삼각함수의 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82756"> <i class="flaticon-pencil"></i> <p>8. 등차수열</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82759"> <i class="flaticon-pencil"></i> <p>9. 등비수열</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82755"> <i class="flaticon-pencil"></i> <p>10. 수열의 합</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82758"> <i class="flaticon-pencil"></i> <p>11. 수학적 귀납법</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav12">
								<i class="flaticon-agenda-1"></i>
								<p>고등수학 2</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav12"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82749"> <i class="flaticon-pencil"></i> <p>1. 함수의 극한</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82750"> <i class="flaticon-pencil"></i> <p>2. 함수의 연속</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82737"> <i class="flaticon-pencil"></i> <p>3. 미분계수와 도함수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82744"> <i class="flaticon-pencil"></i> <p>4. 도함수의 활용 (1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82747"> <i class="flaticon-pencil"></i> <p>5. 도함수의 활용 (2)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82746"> <i class="flaticon-pencil"></i> <p>6. 도함수의 활용 (3)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82745"> <i class="flaticon-pencil"></i> <p>7. 부정적분</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82770"> <i class="flaticon-pencil"></i> <p>8. 정적분</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82748"> <i class="flaticon-pencil"></i> <p>9. 정적분의 활용</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav13">
								<i class="flaticon-agenda-1"></i>
								<p>확률과 통계</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav14"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82783"> <i class="flaticon-pencil"></i> <p>1. 순열</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82791"> <i class="flaticon-pencil"></i> <p>2. 여러 가지 순열</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82784"> <i class="flaticon-pencil"></i> <p>3. 조합</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82785"> <i class="flaticon-pencil"></i> <p>4. 이항정리와 분할</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82786"> <i class="flaticon-pencil"></i> <p>5. 확률의 뜻과 활용</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82787"> <i class="flaticon-pencil"></i> <p>6. 조건부 확률</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82788"> <i class="flaticon-pencil"></i> <p>7. 확률분포</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82789"> <i class="flaticon-pencil"></i> <p>8. 정규분포</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82790"> <i class="flaticon-pencil"></i> <p>9. 통계적 추정</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav14">
								<i class="flaticon-agenda-1"></i>
								<p>미분과 적분</p>
								<span class="caret"></span>

							</a>
							<div class="collapse" id="email-nav13"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82777"> <i class="flaticon-pencil"></i> <p>1. 수열의 극한</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82778"> <i class="flaticon-pencil"></i> <p>2. 급수</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82762"> <i class="flaticon-pencil"></i> <p>3. 지수함수와 로그함수의 미분</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82763"> <i class="flaticon-pencil"></i> <p>4. 삼각함수의 미분</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82764"> <i class="flaticon-pencil"></i> <p>5. 여러 가지 미분법</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82765"> <i class="flaticon-pencil"></i> <p>6. 도함수의 활용 (1)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82766"> <i class="flaticon-pencil"></i> <p>7. 도함수의 활용 (2)</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82769"> <i class="flaticon-pencil"></i> <p>8. 여러 가지 적분법</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82768"> <i class="flaticon-pencil"></i> <p>9. 정적분</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82767"> <i class="flaticon-pencil"></i> <p>10. 정적분의 활용</p></a></li> 

							</ul></div>
						</li>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav15">
								<i class="flaticon-agenda-1"></i>
								<p>기하</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav15"><ul class="nav nav-collapse">
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82775"> <i class="flaticon-pencil"></i> <p>1. 이차곡선</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82793"> <i class="flaticon-pencil"></i> <p>2. 평면 곡선의 접선</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82792"> <i class="flaticon-pencil"></i> <p>3. 벡터의 연산</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82779"> <i class="flaticon-pencil"></i> <p>4. 평면벡터와 평면 운동</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82780"> <i class="flaticon-pencil"></i> <p>5. 공간도형</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82782"> <i class="flaticon-pencil"></i> <p>6. 공간좌표</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82776"> <i class="flaticon-pencil"></i> <p>7. 공간벡터</p></a></li> 
							<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mynote.php?id='.$studentid.'&cntid=82724"> <i class="flaticon-pencil"></i> <p>8. 도형의 방정식</p></a></li> 

							</ul></div>
						</li>
 
';
 
echo '
 
					</ul>
				</div>
			</div>
		</div> 
		<!-- End Sidebar --> 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
	  ';

 ?>