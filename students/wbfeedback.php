<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid = $_GET["studentid"]; 
$type = $_GET["type"]; 

if($type==NULL)$type='목표';
$nowhiteboard='chat';
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$timecreated=time();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($USER->id==NULL)
{
echo '로그인을 해주세요';
exit();
}

$ctalk=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE (talkid=7128 OR talkid=17 OR talkid=7 OR talkid=77  OR talkid=8) AND creator LIKE '$studentid' AND userid NOT LIKE '$studentid'   ORDER BY id DESC LIMIT 1 ");  
$fbtype=$ctalk->type;


// 자세 피드백을 통하여 사용법을 교정
$setcolor1='lightgrey';$setcolor2='lightgrey';$setcolor3='lightgrey';$setcolor4='lightgrey';$setcolor5='lightgrey';$setcolor6='lightgrey';$setcolor7='lightgrey';$setcolor8='lightgrey';
 
if($fbtype==='목표')$setcolor1='red';
elseif($fbtype==='순서')$setcolor2='red';
elseif($fbtype==='기억')$setcolor3='red';
elseif($fbtype==='몰입')$setcolor4='red';
elseif($fbtype==='발상')$setcolor5='red';
elseif($fbtype==='해석')$setcolor6='red';
elseif($fbtype==='숙달')$setcolor7='red';
elseif($fbtype==='효율')$setcolor8='red';
   
   
$lastcheckstatus=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE talkid=88 AND type LIKE '$type' AND creator LIKE '$studentid'  AND hide=0  ORDER BY id DESC LIMIT 1  ");  
$chk1='';$chk2='';$chk3='';$chk4='';$chk5='';
if($lastcheckstatus->checked1==1){$chk1='checked'; $textstyle1='style="color:#2085f7;"';}
if($lastcheckstatus->checked2==1){$chk2='checked'; $textstyle2='style="color:#2085f7;"';}
if($lastcheckstatus->checked3==1){$chk3='checked'; $textstyle3='style="color:#2085f7;"';}
if($lastcheckstatus->checked4==1){$chk4='checked'; $textstyle4='style="color:#2085f7;"';}
if($lastcheckstatus->checked5==1){$chk5='checked'; $textstyle5='style="color:#2085f7;"';}

include("flowrubric.php");
include("avartartalk.php");

$mbtilog= $DB->get_record_sql("SELECT * FROM mdl_abessi_mbtilog WHERE userid='$studentid' AND type='present' ORDER BY id DESC LIMIT 1");
$mbtitext=strtoupper($mbtilog->mbti);
if($mbtilog->id==NULL)$mbtitext='MBTI3';
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE (talkid=7128 OR talkid=17 OR talkid=8  OR talkid=7 OR talkid=77 OR talkid=88)    AND  type LIKE '$type' AND creator LIKE '$studentid'  AND text !='' ORDER BY id DESC LIMIT 20  ");  
$talklist= json_decode(json_encode($share), True);
$nmission=0;
unset($value);  
foreach($talklist as $value)
	{	
	$fbid=$value['id'];
	$sharetext=$value['text'];
	$talkcreator=$value['userid'];
	$wboardid=$value['wboardid'];
	$crname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$talkcreator' ");	
	$creatorname=$crname->firstname.$crname->lastname;
	$tcreated1=date("m월d일 h:i A", $value['timecreated']);   
	$timediff=time()-$value['timecreated'];
	if($timediff<43200)$tsubmit=round($timediff/60,0).'분전';
	else $tsubmit=date("m월d일", $value['timecreated']);   
	$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$talkcreator' AND fieldid='22' "); 
	$role2=$userrole2->role;
	if($role2==='student')$bubblestr='bubble';
	else $bubblestr='bubble2';
	 
	if($value['talkid']==88)$bubblestr='bubble3';

	$getauthor=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1 "); 
	$contentsid=$getauthor->contentsid;
	$hidebtn=''; $showbtn='';$pinbtn='';$unpinbtn='';
	if($studentid==$talkcreator || $role!=='student')
		{
		$hidebtn='<img src=https://mathking.kr/Contents/IMAGES/view.png width=20>';
		$showbtn='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666586411.png width=20>';
		}
	
	if($role!=='student')
		{
		$pinbtn='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666586029.png width=18>';
		$unpinbtn='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666585798.png width=20>';
		}
 	if($value['talkid']==7)
		{
		if($value['hide']==0)$seewb=' <span  onClick="hide(\''.$fbid.'\',1)">'.$hidebtn.'</span> <a style="color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'&mode=peer"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><a  href="https://mathking.kr/moodle/local/augmented_teacher/twinery/feedback/'.$fbtype.' 메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/speak.png width=25></a>';  
		elseif($role!=='student') $seewb='<span  onClick="hide(\''.$fbid.'\',0)">'.$showbtn.'</span> <a style="color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'&mode=peer"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><a  href="https://mathking.kr/moodle/local/augmented_teacher/twinery/feedback/'.$fbtype.' 메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/speak.png width=25></a>';  
		}
	else  
		{
		if($value['hide']==0)
			{
			if($value['pinned']==0)$seewb='<span  onClick="hide(\''.$fbid.'\',1)">'.$hidebtn.'</span> <span  onClick="Pinned(\''.$fbid.'\',1)">'.$pinbtn.'</span>';  
			else $seewb='<span  onClick="hide(\''.$fbid.'\',1)">'.$hidebtn.'</span> <span  onClick="Pinned(\''.$fbid.'\',0)">'.$unpinbtn.'</span>';  
			}
		elseif($role!=='student')
			{
			if($value['pinned']==0)$seewb='<span  onClick="hide(\''.$fbid.'\',0)">'.$showbtn.'</span> <span  onClick="Pinned(\''.$fbid.'\',1)">'.$pinbtn.'</span>';  
			else $seewb='<span  onClick="hide(\''.$fbid.'\',0)">'.$showbtn.'</span> <span  onClick="Pinned(\''.$fbid.'\',0)">'.$unpinbtn.'</span>';  
			}
		}	
	$checkid=$value['checkid'];
	
	
	if($value['talkid']==17)$replybutton=' [오늘집중]';
	elseif($checkid==NULL)$replybutton=' [대화]';
	else
		{
		if($role==='student')$replybutton=' ['.$checkid.']';
		else $replybutton=' <span onclick="Avartartalk('.$checkid.');"><u style="color:blue;">체크'.$checkid.'</u></span>';
		}
	if($value['hide']==0 && $nmission==0 && $value['creator']!=$value['userid'] && $value['talkid']==17)
		{
		$nmission++;
		$bubblestr='bubble3';
		$sharelist0.='<tr><td width=3% style="white-space: nowrap; text-overflow: ellipsis;" valign=top align=right><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/nextgoal.png
 width=60> <span style="color:#3399ff;"></span> <span    onClick="Edittext(\''.$fbid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=12></span></td> <td width=2%></td>
		<td style="overflow:auto;" valign=center><div class="'.$bubblestr.'"> &nbsp;&nbsp;&nbsp;<b>'.$sharetext.$replybutton.'</b></div></td><td width=2%></td><td valign=top style="white-space: nowrap; text-overflow: ellipsis;">'.$tsubmit.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top>'.$seewb.'</td></tr><tr><td>&nbsp;</td> <td></td><td></td><td></td><td></td><td></td><td></td></tr>';
		}
	elseif($value['hide']==0) 
		{
		$sharelist.='<tr><td width=3% style="white-space: nowrap; text-overflow: ellipsis;" valign=top align=right> <span style="color:#3399ff;">'.$creatorname.'</span> <span    onClick="Edittext(\''.$fbid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=12></span></td> <td width=2%></td>
	<td style="overflow:auto;" valign=center><div class="'.$bubblestr.'"> &nbsp;&nbsp;&nbsp;'.$sharetext.$replybutton.'</div></td><td width=2%></td><td valign=top style="white-space: nowrap; text-overflow: ellipsis;">'.$tsubmit.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top>'.$seewb.'</td></tr>
	<tr><td>&nbsp;</td> <td></td><td></td><td></td><td></td><td></td><td></td></tr>';
		}
	else $sharelist.='<tr><td width=3% style="white-space: nowrap; text-overflow: ellipsis;" valign=top align=right> <span style="color:#3399ff;">'.$creatorname.'</span></td> <td width=2%></td><td style="overflow:auto;" valign=center><div class="bubble4"> &nbsp;&nbsp;&nbsp;<del>'.iconv_substr($sharetext, 0, 30, "utf-8").'...</del>'.$replybutton.'</div></td><td width=2%></td><td valign=top style="white-space: nowrap; text-overflow: ellipsis;">'.$tsubmit.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top>'.$seewb.'</td></tr><tr><td>&nbsp;</td> <td></td><td></td><td></td><td></td><td></td><td></td></tr>';
	}

$history=$DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog where userid='$studentid'   ORDER BY id DESC LIMIT 1"); 
$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;

if($role!=='student') $teacherinputmenu='<td><span onclick="Avartartalk(1);"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666655683.png width=30></span></td>
<td><span onclick="Avartartalk(2);"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666655714.png width=30></span></td>
<td><span onclick="Avartartalk(3);"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666655737.png width=30></span></td>
<td><span onclick="Avartartalk(4);"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666655763.png width=30></span></td>
<td><span onclick="Avartartalk(5);"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666655786.png width=30></span></td>';

if($role==='student')$commenttype='Comment1';
else $commenttype='Comment2';


if($role!=='student' || $USER->id==$studentid)$updateFlowsate='<button class="btn btn-success"  type="button"  style = "font-size:16;background-color:lightblue;color:black;border:0;height:40px;outline:0;"  onclick="EvaluateFlow()">업데이트</button>';
echo '
<div id="navbar">
  
  <table align=center width=98%><tr><td><a href="'.$typeurl.'"target="_blank">'.$hat.'</a></td><td align=left><a style="text-decoration: none; font-size:20px;color:black; white-space: nowrap; text-overflow: ellipsis;"  href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> &nbsp;&nbsp;&nbsp;&nbsp;   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/mbti_types.php?studentid='.$studentid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/mbti.jpg" width=30></a>  <b style="font-size:20px;color:blue;"><a style="color:blue;" href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-'.$mbtitext.'"target="_blank">'.$mbtitext.'</a></b> &nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=today"><img src="https://mathking.kr/Contents/IMAGES/improveimg.png" width=30></a> </td> 
<td align=right style="color:#2085f7; font-size:16px;" width=50%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/flowwins.php?id='.$USER->id.'"><img src=https://mathking.kr/Contents/IMG22/meta'.$ntype.'.png height=80px></a></td></tr></table><hr style="border: solid 3px grey;">
 
 
</div>


<table width=100%><tr><td width=2%></td><td width=50% style="white-space: nowrap; text-overflow: ellipsis;" align=center >'.$typetext.' </td><td width=40%><table width=100% align=right><tr><td width=1%></td><td width=60%><input style="font-size:20px;width:100%;" type="text" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'"></td>
<td width=2%></td><td aglin=right><button style="font-size:20px;"  onClick="'.$commenttype.'(\''.$studentid.'\',\''.$USER->id.'\',\''.$nowhiteboard.'\',$(\'#squareInput\').val(),\''.$type.'\')">입력하기</button></td>
'.$teacherinputmenu.'
</tr></table></td></tr></table>
<table width=100% height=100% align=left><tr><td width=2%></td>
<td width=48% valign=top>
<table align=center><tr><td><div class="chart-container" style="width: 500px; height:600px;horizontal-align: center;"><canvas  id="radarChart"></canvas></div></td></tr></table>
<hr>'.$rubric.'<hr>
<table align=center><tr><td>'.$updateFlowsate.'</td></tr></table>
<hr>
</td>
<td width=2%></td><td valign=top style="overflow-y:hidden;"><br><table align=left><tr><td width=20%></td><td>CJN <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'&fid='.$history->id.'">메타인지</a> | <a style="color:'.$setcolor1.';"  href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=목표">목표</a> | <a style="color:'.$setcolor2.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=순서">순서</a> | <a style="color:'.$setcolor3.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=기억">기억</a> | <a style="color:'.$setcolor4.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=몰입">몰입</a>
 | <a style="color:'.$setcolor5.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=발상">발상</a> | <a style="color:'.$setcolor6.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=해석">해석</a> | <a style="color:'.$setcolor7.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=숙달">숙달</a> | <a style="color:'.$setcolor8.';" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=효율">효율</a>  </td></tr></table><br><hr><table>'.$sharelist0.$sharelist.'</table></td><td width=2%></td></tr></table> ';
 //<iframe style="width:45vw;height:45vh;" src="'.$typeurl.'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> 
echo '

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<link rel="stylesheet" href="../assets/css/ready.min.css">
<script src="https://code.jquery.com/pep/0.4.3/pep.js"></script> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />


<script>
window.onscroll = function() {myFunction()};

var navbar = document.getElementById("navbar");
var sticky = navbar.offsetTop;

function myFunction() {
  if (window.pageYOffset >= sticky) {
    navbar.classList.add("sticky")
  } else {
    navbar.classList.remove("sticky");
  }
}
</script>
<style>


#navbar {
  overflow: hidden;
  background-color: white;
  z-index:1;
}

.sticky {
  position: fixed;
  top: 0;
  width: 100%;
}

.sticky + .content {
  padding-top: 60px;
}
</style>
<script>	 
function Avartartalk(Checkid)
		{
		Swal.fire({
		backdrop: false,position:"top-left",width:1200,
		  html:
		    \'<iframe style="border: 1px none; z-index:2; width:60vw;height:90vh; margin-left: -30px;margin-top: 0px;"   src="https://mathking.kr/moodle/local/augmented_teacher/students/avartardb.php?type='.$type.'&studentid='.$studentid.'&checkid=\'+Checkid+\'&teacherid='.$USER->id.'" ></iframe>\',
		  
		        })
		}
function Avartaranalysis(Checkid)
		{
		Swal.fire({
		backdrop: false,position:"top-right",width:1200,
		  html:
		    \'<iframe style="border: 1px none; z-index:2; width:60vw;height:90vh; margin-left: -30px;margin-top: 0px;"   src="https://mathking.kr/moodle/local/augmented_teacher/students/avartaranalysis.php?type='.$type.'&studentid='.$studentid.'&checkid=\'+Checkid+\'&teacherid='.$USER->id.'" ></iframe>\',
		  
		        })
		}
function Edittext(Talkid,Inputtext)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "내용 수정하기",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputValue: Inputtext,
  	inputAttributes: {
   	 "aria-label": "Type your message here"
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check_status.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'4\',
		"talkid":Talkid,
		"inputtext":text,	
		},
		success:function(data){
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	}
function Checkflow(Itemid,Type,Userid,Text, Checkvalue)
	{
	var Eventid="1";
	var checkimsi = 0;
	if(Checkvalue==true)
			{
		    checkimsi = 1;
		    }
	swal("선택항목이 대화목록에 추가되었습니다.", {buttons: false,timer: 5000});
	$.ajax({
		        url: "checkflow.php",
  		      type: "POST",
		        dataType: "json",
		        data : {
				"eventid":Eventid,
				"type":Type,  
		                "itemid":Itemid,
						"userid":Userid,       
		                "inputtext":Text,
		                "checkimsi":checkimsi,
		               },
		        success: function (data){  
		       				 }
		});
	}
function flowstamp(Itemid,Type,Userid,Text, Checkvalue)
	{
	var Eventid="11";
	var checkimsi = 0;
	if(Checkvalue==true)
			{
		    checkimsi = 1;
		    }
	swal("선생님에게 오늘의 메타인지활동 내용을 전달하습니다.", {buttons: false, timer: 2000, });	
	$.ajax({
		        url: "checkflow.php",
  		      type: "POST",
		        dataType: "json",
		        data : {
				"eventid":Eventid,
				"type":Type,  
		                "itemid":Itemid,
						"userid":Userid,       
		                "inputtext":Text,
		                "checkimsi":checkimsi,
		               },
		        success: function (data){  
		       				 }
		});
	setTimeout(function() {location.reload(); },2000);	
	}
function EvaluateFlow()
	{
	var Userid= \''.$studentid.'\';
	var Tutorid= \''.$USER->id.'\';
	var Type= \''.$type.'\';
	var Eventid="2";
  	swal("플로우 평가결과가 업데이트 되었습니다.", {buttons: false, timer: 2000, });			    
 	$.ajax({
		url:"checkflow.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"userid":Userid,
		"tutorid":Tutorid,
		"type":Type,
		},
	success:function(data){
				var talkid=data.talkid;
				if(talkid==199)swal("최근 선생님 평가가 이루어졌습니다. 72시간 이후 변경이 가능합니다.", {buttons: false, timer: 2000, });		
				else setTimeout(function() {location.reload(); },100);	
	 			}
	 		})
	 		setTimeout(function() {location.reload(); },2000);	
	}
function Comment1(Wbcreator,Userid,Wboardid,Text,Type)
	{ 
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
 
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function() {location.reload(); },100);		
  	   		   	}
			  });	
	 
 
	}
function Comment2(Wbcreator,Userid,Wboardid,Text,Type)
	{ 

			var text1="1번 체크항목이 선택되었습니다.";
			var text2="2번 체크항목이 선택되었습니다.";
			var text3="3번 체크항목이 선택되었습니다.";
			var text4="4번 체크항목이 선택되었습니다.";
			var text5="5번 체크항목이 선택되었습니다.";
					
			swal("",  "입력내용과 연관된 체크항목을 선택해 주세요",{
			  buttons: {
			    catch1: {
			      text: "체크1",
			      value: "catch1",className : \'btn btn-primary\'
				
			    },
			    catch2: {
			      text: "체크2",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "체크3",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "체크4",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "체크5",
			      value: "catch5",className : \'btn btn-primary\'
			    },
 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;			 
 			   case "catch1":
 			swal("", text1, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
				"checkid":\'1\',
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function() {location.reload(); },100);		
  	   		   	}
			  });			 
 			    break;
 			   case "catch2":
 			swal("", text2, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
				"checkid":\'2\',
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function() {location.reload(); },100);		
  	   		   	}
			  });			 
 			break;
 			   case "catch3":
 			swal("",  text3, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
				"checkid":\'3\',
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function() {location.reload(); },100);		
  	   		   	}
			  });
 			    break;		
 			   case "catch4":
 			swal("",  text4, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
				"checkid":\'4\',
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function() {location.reload(); },100);		
  	   		   	}
			  });
 			    break;
 			   case "catch5":
 			swal("", text5, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
				"checkid":\'5\',
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function() {location.reload(); },100);		
  	   		   	}
			  });
			    break;
 			   default:
			      swal("취소되었습니다.", {buttons: false,timer: 500});
			  }
			});
		}
	 
 
function Reply(Userid,Wboardid,Sid,Text)
	{ 
	alert(Sid);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'40\',
		"wboardid":Wboardid,	
		"inputtext":Text,	
		"userid":Userid,
		"talkid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
 
function hide(Fbid, Checkvalue){
		var checkimsi = 0;
   		if(Checkvalue==true){
        		checkimsi = 1;
    		}
 		swal("체크시 학생에게 보이지 않습니다.", {buttons: false,timer: 500});
  		 $.ajax({
       		 url: "check.php",
        		type: "POST",
        		dataType: "json",
        		data : { 
			"eventid":\'43\',
            		"fbid":Fbid,
            	 	"checkimsi":checkimsi,
            	 	  },
 	  	 success: function (data){  
		 var Fbid2=data.fbid2
		 setTimeout(function() {location.reload(); },100);	
  	   	   }
		  });
		}
function Pinned(Fbid, Checkvalue){
			var checkimsi = 0;
   			if(Checkvalue==true){
        			checkimsi = 1;
    			}
		if(checkimsi==1)
			{
			var text1="1번 체크항목이 선택되었습니다.";
			var text2="2번 체크항목이 선택되었습니다.";
			var text3="3번 체크항목이 선택되었습니다.";
			var text4="4번 체크항목이 선택되었습니다.";
			var text5="5번 체크항목이 선택되었습니다.";
			var text6="자주 쓰는 표현으로 추가되었습니다.";
			var text7="2주 후 점검목록에 추가되었습니다.";
			swal("",  "입력내용과 연관된 체크항목을 선택해 주세요",{
			  buttons: {
			    catch1: {
			      text: "체크1",
			      value: "catch1",className : \'btn btn-primary\'
				
			    },
			    catch2: {
			      text: "체크2",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "체크3",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "체크4",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "체크5",
			      value: "catch5",className : \'btn btn-primary\'
			    },
 			    catch6: {
			      text: "표현추가",
			      value: "catch6",className : \'btn btn-primary\'
			    },
 			    catch7: {
			      text: "점검추가",
			      value: "catch7",className : \'btn btn-primary\'
			    },
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;			 
 			   case "catch1":
 			swal("", text1, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'1\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });			 
 			    break;
 			   case "catch2":
 			swal("", text2, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'2\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });			 
 			break;
 			   case "catch3":
 			swal("",  text3, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'3\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });
 			    break;		
 			   case "catch4":
 			swal("",  text4, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'4\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });
 			    break;
 			   case "catch5":
 			swal("", text5, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'5\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });
			    break;
 			   case "catch6":
 			swal("", text6, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'100\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });
			    break;
 			   case "catch7":
 			swal("", text7, {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'200\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });
			    break;
 			   default:
			      swal("취소되었습니다.", {buttons: false,timer: 500});
			  }
			});
			}
		else
			{
 			swal("", "해제되었습니다", {buttons: false,timer: 2000});
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
				"eventid":\'42\',
            			"fbid":Fbid,
				"checkid":\'0\',
            	 		"checkimsi":checkimsi,
            	 		  },
 	  	 		success: function (data){  
			 	var Fbid2=data.fbid2
			 	setTimeout(function() {location.reload(); },100);	
  	   		   	}
			  });
			}
		}
</script> 



<style>
.bubble
{
position: relative;
width: 390px;
width: auto;
min-height:35px;
padding: 0px;
background: #e1faf6;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: black;
display: block;
width: 1px;
z-index: 1;
left: -29px;
top: 0px;
}

.bubble2
{
position: relative;
width: auto;
height: auto;
min-height:35px;
padding: 5px;
background: #faf2d9;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble2:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: #faf2d9;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 0px;
}

 
.bubble3
{
position: relative;
width: auto;
height: auto;
min-height:35px;
padding: 5px;
background: #d7ebfc;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble3:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: #fcfbeb;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 0px;
}

.bubble4
{
position: relative;
width: auto;
height: auto;
min-height:35px;
padding: 5px;
background: #fcfbeb;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble4:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: #fcfbeb;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 0px;
}

a:link {
  color : red;
}
a:visited {
  color :grey;

}
a:hover {
  color : blue;
}
a:active {
  color : purple;
}

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
  width: 500px;
  background-color: #ffffff;
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
 

.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip3 .tooltiptext3 {
    
  visibility: hidden;
  width:700px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 700px;
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
echo ' 
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

	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, -->
	<script src="../assets/js/setting-demo.js"></script>


 ';
?>



<script>

var radarChart = document.getElementById('radarChart').getContext('2d');
var Flow1= "<?php echo $flow1;?>";
var Flow2= "<?php echo $flow2;?>";
var Flow3= "<?php echo $flow3;?>";
var Flow4= "<?php echo $flow4;?>";
var Flow5= "<?php echo $flow5;?>";
var Flow6= "<?php echo $flow6;?>";
var Flow7= "<?php echo $flow7;?>";
var Flow8= "<?php echo $flow8;?>";
 

var myRadarChart = new Chart(radarChart, {
			type: 'radar',

			data: {
				labels: ['목표', '순서', '기억', '몰입', '발상','해석','숙달','효율'],
				
				datasets: [{
					data: [Flow1, Flow2, Flow3, Flow4, Flow5, Flow6, Flow7, Flow8],
					borderColor: '#1d7af3',
					backgroundColor : 'rgba(29, 122, 243, 0.25)',
					pointBackgroundColor: "#1d7af3",
					pointHoverRadius: 5,
					pointRadius: 10,
					label: '몰입상태'
				},    
				
				]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: 'bottom'
				}
			}
		});
 

</script>


