<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
//include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
include_once("../bessiboard/dbcon.php");

global $DB, $USER;
include("navbar.php");
$timecreated=time();
$mode=$_GET["mode"];
$update=$_GET["update"];


$tlastaccess=time()-604800*30;
$aweekago=time()-604800;
$halfdayago=time()-43200;
$adayago=time()-86400;

$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$nenergy=$collegues->nenergy;
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1=$teacher1->symbol;
$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2=$teacher2->symbol;
$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3=$teacher3->symbol;  
 
if($tsymbol1==NULL)$tsymbol1='KTM';
if($tsymbol2==NULL)$tsymbol2='KTM';
if($tsymbol3==NULL)$tsymbol3='KTM';

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$teacherid' AND fieldid='22' "); 
$role=$userrole->role;

if($teacherid==$USER->id)$DB->execute("INSERT INTO {abessi_missionlog} (teacherid,event,page,timecreated) VALUES('$USER->id','teacher','psclass','$timecreated')");
$lastpageview=$DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog where teacherid='$USER->id' AND timecreated<'$halfdayago' AND page='psclass' ORDER BY id DESC LIMIT 1 ");
$alerttalk2us='';
$lasttalk2us=$DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us where teacherid='$teacherid' AND eventid='7128' ORDER BY id DESC LIMIT 1 ");
//if($timecreated-$lasttalk2us->timecreated>86400 && $lastpageview->timecreated > $timecreated-86400 )$alerttalk2us='<table width=100%><tr><td align=center style="height: 40px; background-color:#ffe6e6;color:black;">Talk2us 정보공유가 누락되었습니다.</td></tr></table>';
if($timecreated-$lasttalk2us->timecreated>86400 )$alerttalk2us='<table width=100%><tr><td align=center style="height: 40px; background-color:#ffe6e6;color:black;">Talk2us 정보공유가 누락되었습니다.</td></tr></table>';

$period=required_param('tb', PARAM_INT); // get_record from $period ago
$periodp=$period+7;
$periodm=$period-7;
echo '<script>
setTimeout(function() 
	{
	document.getElementById("alert_nextpage").click();	
	},180000);
</script>';

$autogradeOn=$DB->get_record_sql("SELECT data   FROM mdl_user_info_data where userid='$USER->id' and fieldid='82' ");
$autoGradeState=$autogradeOn->data;

$begintable='<table width=100%  style="white-space: nowrap; overflow: hidden;  text-overflow: ellipsis;background-color:#FFFFE0;" align=left>';
if($role!=='student');
{
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE  suspended=0 AND lastaccess> '$amonthago'  AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
$size=count($mystudents); 
$result= json_decode(json_encode($mystudents), True);

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0); 
if($nday==0)$nday=7;
//secondbrain(Userid, Username)
$statusimg0='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638960385.png" width=30>'; // 완료
$statusimg1='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638943138.png" width=30>'; // 시작
$statusimg2='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638943291.png" width=30>';  // 분기
$statusimg3='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638943347.png" width=30>'; // 주간
$statusimg4='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638943376.png" width=30>'; // 오늘
$statusimg5='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638943508.png" width=30>'; // 개선
$statusimg6='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638941818.png" width=30>'; // 다시
$statusimg7='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638941855.png" width=30>'; // 발표
$statusimg8='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638941872.png" width=30>'; // 답변
$statusimg9='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638942467.png" width=30>'; // 평가
$statusimg10='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638941921.png" width=30>'; // 고민
$statusimg11='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638941932.png" width=30>'; // 요약
$statusimg12='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638941946.png" width=30>'; // 계획
//$userid=827;

$asklist= '<table width=100%><tr><td></d><td>진단</td><td>Restore_HP </d><td>자료</d><td>상태</td><td></td><td></td></tr>';
$feedbackreview.= '<table width=100%><tr><td>대상</d><td>학생노트</d><td>해설노트</d><td> ONAIR 피드백 </td><td>인지성장</td><td>시간</d></tr>
		<tr><td><hr></d><td><hr></d><td><hr></d><td><hr></td><td><hr></td><td><hr></d></tr>';
if($mode==='today')
	{
	$n1=$nday;
	if($nday==1)$daytext='월요일';
	elseif($nday==2)$daytext='화요일';
	elseif($nday==3)$daytext='수요일';
	elseif($nday==4)$daytext='목요일';
	elseif($nday==5)$daytext='금요일';
	elseif($nday==6)$daytext='토요일';
	elseif($nday==7)$daytext='일요일';
	$nstd=0;  $ngrowth_total=0; $ncomplete_total=0; $ninteraction_total=0; $nviewtoday=0;
	$todaytext=' <b>오늘</b> ';
	$tomorrowtext=' 내일 ';
	unset($user);
	foreach($result as $user)
		{
		$userid=$user['id'];
		$studentname=$user['firstname'].$user['lastname'];
		$tafter=time()-86400*$period;

		$wtimestart1=$timecreated-86400*($nday+1);
		$wtimestart2=$timecreated-86400*($nday+8);  
		$lastwgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND type LIKE '주간목표' AND timecreated < '$wtimestart1' AND timecreated > '$wtimestart2' ORDER BY id DESC LIMIT 1");
		if($lastwgoal->planscore==NULL)$checkAlignment.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$userid.'" target="_blank" >'.$studentname.' </td><td>상태</td><td>#일전</td><tr>';

 		$indicators=$DB->get_record_sql("SELECT * FROM mdl_abessi_indicators where userid='$userid' ORDER BY id DESC LIMIT 1 ");
		$checkstatus='';
		$ntodo=$indicators->ntodo;
		
		$setimg='statusimg'.$ntodo;
		$laststatus=$$setimg;
		$tlastcheck=time()-$indicators->timefired;
		if($tlastcheck>43200)$laststatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638962128.png" width=30>';
		elseif($tlastcheck>3600)$laststatus=$laststatus.' * ';
		else $laststatus=$laststatus.'';
		
		$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
		$missionlog= $DB->get_record_sql("SELECT * FROM  mdl_abessi_missionlog WHERE userid='$userid' AND eventid=17 ORDER BY id DESC LIMIT 1 ");
		$recentcurl='https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?'.$missionlog->url;
		$mtid=$missionlog->mtid;

		// 온라인 질의응답 
		 
		$instructionlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_instructionlog WHERE studentid='$userid' AND timecreated>'$aweekago'  ORDER BY timecreated DESC LIMIT 1"); 
		$tpassed=round((time()-$instructionlog->timecreated)/60,0);
		$boardtype='whiteboard/board'; $boardname='';  

		$studentnote=substr($instructionlog->studentnote, 0, strpos($instructionlog->studentnote, '_user')); // 문자 이후 삭제
		$studentnote=str_replace("_user","",$studentnote);
		$message=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid='$instructionlog->studentnote' ORDER BY id DESC LIMIT 1    ");

		if($instructionlog->instructionid==7128 && $message->status!=='complete' && $message->status!=='review' && $message->status!=='present') // 화이트보드 일회성 상호작용 
			{
			$fbwboard='없음';
			
			//if($message->status==='complete' || $message->status==='review' ) $resulticon='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$instructionlog->studentnote.'"target="_blank">학습완료 <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"></a>';
			$resulticon='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$instructionlog->studentnote.'"target="_blank">'.$message->status.'</a>';   
			 
			$feedbackreview.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a></td><td>'.$resulticon.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$studentnote.'&srcid='.$instructionlog->studentnote.'&studentid='.$userid.'"target="_blank">Whiteboard</a></d><td width=40%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=2&wboardid='.$instructionlog->studentnote.'"target="_blank">'.$instructionlog->text.'</a></td><td align=center>없음</d><td>'.$tpassed.'분</d></tr>';
			}
 		elseif($instructionlog->id!=NULL && $message->status!=='complete' && $message->status!=='review' && $message->status!=='present') // onair 컨텐츠 피드백 활용
			{
			$instruction=$DB->get_record_sql("SELECT * FROM mdl_abessi_instruction where id='$instructionlog->instructionid' ORDER BY id DESC LIMIT 1 ");
			$fbwboard='없음';
			if($instruction->wb==1)$fbwboard='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$instruction->wboardid.'">있음</a>';
			//if($message->status==='complete' || $message->status==='review' )  $resulticon='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$instructionlog->studentnote.'"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"></a>';
			$resulticon='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$instructionlog->studentnote.'"target="_blank">'.$message->status.'</a>';
			$feedbackreview.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a></td><td>'.$resulticon.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$studentnote.'&srcid='.$instructionlog->studentnote.'&studentid='.$userid.'"target="_blank">Whiteboard</a></d><td width=40%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=2&wboardid='.$instructionlog->studentnote.'"target="_blank">'.$instruction->instruction.'</a></td><td align=center>'.$fbwboard.'</d><td>'.$tpassed.'분</d></tr>';
			}

		 // 메니저 피드백 
		$ask=$DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us where studentid='$userid' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1 ");
 		if($ask->id!=NULL && $ask->status==='ask' )$asklist.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a></d><td>'.$ask->context.'</td><td>'.$ask->text.'</d><td>자료</d><td>'.$ask->status.'</td><td><span style="background-color:lightgreen;" onclick="sendmessage('.$ask->id.','.$ask->eventid.','.$userid.');">메세지</span></td><td>'.date_format($ask->timemodified,"m/d H:i").'</td></tr>';

		// 메니저 피드백 끝
		$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
		$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$userid' AND courseid NOT LIKE '239' AND component NOT LIKE 'core' AND  component NOT LIKE 'local_webhooks'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog		 
		//$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
		    
		$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
		$tgoal=time()-$goal->timecreated;

		$ratio1=$indicators->todayscore;
		$ratio2=$indicators->weekscore;
		$missiontext='';
		if($mtid==1 || $mtid==7)$missiontext=' (개념미션) ';
		elseif($mtid==2)$missiontext=' (심화미션) ';
		elseif($mtid==3)$missiontext=' (내신미션) ';
		elseif($mtid==4)$missiontext=' (모의고사) ';
		 
		$tlastaction=time()-max($engagement1->timecreated,$engagement2->timecreated,$indicators->tlaststroke);	
		if($timecreated-$indicators->tlastview>300)$DB->execute("UPDATE {abessi_indicators} SET ninspect=1  WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
		if($autoGradeState==='AI' && $tlastaction>60 && $indicators->ninspect==1 && $tlastaction <43200 && $indicators->aion==1 ) // 자동수업 모드 알고리즘 적용
			{
			echo '<script>
				var Userid= \''.$userid.'\';
 				$(document).ready(function(){
    				window.open("https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=0", "_blank"); // will open new tab on document ready
				});
			</script>';
			}
		 
		if($tlastaction>180)$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646616090.png" width=30> '; 

		if($indicators->aion==1)$checkstatus='checked';
		else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> '; 

		// 자습모드, 지도모드, 도제모드
		//if($ratio2<80 && $indicators->ngrowth<2)
		$useinfo=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where  userid='$userid' AND fieldid='90' "); 
		if($useinfo->data==NULL)$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646447390.png" width=30>'; 
		elseif($useinfo->data==='자습')
			{
			$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646435924.png" width=30>'; 
			if($indicators->aion==1)
				{
				if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646435924.png" width=30> ';  // 회복됨
				else 
					{
					$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436175.png" width=30> '; 
				 	$DB->execute("UPDATE {abessi_indicators} SET ninspect=1, timemodified='$timecreated' WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
					}
				}
			else
				{
				if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
				else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
				}
			//$cognitivekick='cogkick_'.$userid;  SELECT age, name FROM people WHERE age IN (SELECT MAX(age) FROM people);
			$cognitivekick=$DB->get_record_sql("SELECT max(id) AS id FROM mdl_abessi_messages where userid LIKE '$userid' AND status LIKE 'begin' AND active='1' AND timecreated>'$halfdayago' ");
			$cowb=$DB->get_record_sql("SELECT wboardid FROM mdl_abessi_messages where id LIKE '$cognitivekick->id' ");
			//$cognitivekick=$DB->get_record_sql("SELECT max(id),wboardid FROM mdl_abessi_messages where userid LIKE '$userid' AND status LIKE 'begin' AND active='1' AND timecreated>'$halfdayago' ");
			if($cognitivekick->id!=NULL)$cogkick1.='<tr><td><span style="color:black;">자습</span></td><td width=10%></td> <td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=2&wboardid='.$cowb->wboardid.'" target="_blank">생각계단 설정</a></td></tr>';
			}
		elseif($useinfo->data==='지도')
			{
			$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436605.png" width=30>'; 
			if($indicators->aion==1)
				{
				if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436605.png" width=30> ';  // 회복됨
				else 
					{
					$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436540.png" width=30> '; 
			 		$DB->execute("UPDATE {abessi_indicators} SET ninspect=1, timemodified='$timecreated' WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
					}
				}
			else
				{
				if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
				else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 					
				}
			//$cognitivekick='cogkick_'.$userid;
			$cognitivekick=$DB->get_record_sql("SELECT max(id) AS id FROM mdl_abessi_messages where userid LIKE '$userid' AND status LIKE 'begin' AND active='1' AND timecreated>'$halfdayago' ");
			$cowb=$DB->get_record_sql("SELECT wboardid FROM mdl_abessi_messages where id LIKE '$cognitivekick->id' ");
			if($cognitivekick->id!=NULL)$cogkick2.='<tr><td><span style="color:blue;">지도</span></td><td width=10%></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=2&wboardid='.$cowb->wboardid.'" target="_blank">생각계단 설정</a></td></tr>';
			}
 	 	elseif($useinfo->data==='도제') 
			{
			$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436775.png" width=30>';
			if($indicators->aion==1)
				{
				if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436775.png" width=30> ';  // 회복됨
				else 
					{
					$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436824.png" width=30> '; 
				 	$DB->execute("UPDATE {abessi_indicators} SET ninspect=1, timemodified='$timecreated' WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
					}
				}
			else
				{
				if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
				else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
				}
			//$cognitivekick='cogkick_'.$userid;
			$cognitivekick=$DB->get_record_sql("SELECT max(id) AS id FROM mdl_abessi_messages where userid LIKE '$userid' AND status LIKE 'begin' AND active='1' AND timecreated>'$halfdayago' ");
			$cowb=$DB->get_record_sql("SELECT wboardid FROM mdl_abessi_messages where id LIKE '$cognitivekick->id' ");
			if($cognitivekick->id!=NULL)$cogkick3.='<tr><td><span style="color:red;">도제</span></td><td width=10%></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=2&wboardid='.$cowb->wboardid.'" target="_blank">생각계단 설정</a></td></tr>';
			}

		$inspect=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' and fieldid='72' ");
		$inspecttime=$inspect->data*60;

		if($autoGradeState==='AI' && $timecreated-$indicators->tinspect>=$inspecttime && $nviewtoday<4 && $inspect->data!=NULL)
			{ 
			$nviewtoday++;
			echo '<script>
				var Userid= \''.$userid.'\';
 				$(document).ready(function(){
    				window.open("https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800", "_blank"); // will open new tab on document ready
				});
			       </script>';  
			}
		elseif($useinfo->data==NULL && $nviewtoday<4 && $inspect->data==NULL)
			{
			$nviewtoday++;
			echo '<script>
				var Userid= \''.$userid.'\';
 				$(document).ready(function(){
    				window.open("https://mathking.kr/moodle/user/editadvanced.php?id='.$userid.'", "_blank"); // will open new tab on document ready
				});
			       </script>';  
			}
 
		if($tlastaction>36000)$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646616360.png" width=30> '; 
		
		/*

		활동지연 3분 이상인 경우 표시

		자습모드 이상 해제 방법 : 일시복구 (30분) - 연속 3개 정답(로켓상태), 마지막 테스트 80점 넘기기, 발표활동. 영구복구 - 평점복구, 주간성취 복구
		지도모드 이상 해제 방법 : 일시복구 (30분) - 연속 3개 정답(로켓상태), 마지막 테스트 80점 넘기기, 퀴즈활동에 대한 온라인 코멘트 전달, 퀴즈활동 점검 후 체크 클릭. 영구복구 : 평점복구, 주간성취 복구.
		도제모드 이상 해제 방법 : 일시복구 (30분) - 연속 3개 정답(로켓상태), 오답호출 후 피드백. 영구복구 :  1. 화이트보드 활동시간 데이터 2. 설계된 부스터 활동 전달 3. restore_hp에 화이트보드 활동시간 데이터 추가해서 모니터링. (어떻게 판별 ? 평가준비 첫 필기로부터 30분 넘는 경우 모니터링)
			 - 오답 발생 시 호출 > 피드백 후 체크 (새로고침 시 즉시 메세지 전달 방식)
		*/
 

 		if($ratio1<70)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		elseif($ratio1<75)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		elseif($ratio1<80)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		elseif($ratio1<85)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		elseif($ratio1<90)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		elseif($ratio1<95)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		else $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';
		if($ratio1==0 && $Qnum2==0) $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="reportData(\''.$userid.'\',\''.$studentname.'\')">'.$modebydata.'</span>';

		$var='start'.$n1;
		$var2=$schedule->$var;
		$var3='duration'.$n1;
		$var4=$schedule->$var3;
		$tbegin=date("H:i",strtotime($var2));
		$time    = explode(':', $tbegin);
		$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;

		$lastaction=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' "); 
		$lastaction=$lastaction->maxtc;
		$lastaccess=time()-$lastaction;

 		if($var2!=NULL  && $var4>0 && (time()- $schedule->timecreated)<86400000)
			{	 
			$n2=(int)(($minutes-530)/30);	 	
			$date=date(" h:i A");
			$date2=date("H:i",strtotime($date));
			$time2    = explode(':', $date2);
			$minutes2 =(int)( ($time2[0] * 60.0 + $time2[1] * 1.0)-30);
			$npresent=(int)(($minutes2-530)/30);	
			 
			if($minutes<500)$n2=0;
			if(($npresent==$n2+1||$npresent==$n2+2||$npresent==$n2+3||$npresent==$n2+4||$npresent==$n2+5|$npresent==$n2+6||$npresent==$n2+7||$npresent==$n2+8||$npresent==$n2+9||$npresent==$n2+10||$npresent==$n2) && $nday==$n1)
				{	
				if($lastaccess>36000)
					{
					if($tgoal >43200 )$name[$n1][$n2].='<tr style="white-space: nowrap; text-overflow: ellipsis;" height=30><td ><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " > <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638961977.png" width=30>'.$studentname.'</a></td><td width=7%>('.$indicators->ngrowth.')</td><td  width=40%>'.$imgtoday.'</td><td width=5%><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>'.$indicators->ninspect.'</td></tr>';
					else 
						{
						$name[$n1][$n2].='<tr style="white-space: nowrap; text-overflow: ellipsis;" height=30><td ><span type="button"  onClick="secondbrain(\''.$userid.'\',\''.$studentname.'\')">'.$laststatus.'</span><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >'.$studentname.'</a></td><td  width=7%>('.$indicators->ngrowth.')</td><td  width=40%>'.$imgtoday.'</td><td width=5%><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>'.$indicators->ninspect.'</td></tr>';
						}
					}
				else 
					{
					$name[$n1][$n2].='<tr style="white-space: nowrap; text-overflow: ellipsis;" height=30><td ><span type="button"  onClick="secondbrain(\''.$userid.'\',\''.$studentname.'\')">'.$laststatus.'</span><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >'.$studentname.'</a></td><td width=7%>('.$indicators->ngrowth.')</td><td  width=40%>'.$imgtoday.'</td><td width=5%><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>'.$indicators->ninspect.'</td></tr>';
					$nstd++;		
					
					$ngrowth_total=$ngrowth_total+$indicators->ngrowth;
					$ntodo0=$ntodo;
					if($ntodo==0)$ntodo0=13;
					$ncomplete_total=$ncomplete_total+$ntodo0;
					$ninteraction_total=$ninteraction_total+$indicators->ninteraction;
					}
				}
			else  
				{ 
				 $name[$n1][$n2].='<tr style="white-space: nowrap; text-overflow: ellipsis;"height=30><td><span type="button"  onClick="secondbrain(\''.$userid.'\',\''.$studentname.'\')"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646637882.png" width=30></span><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >'.$studentname.'</a></td><td width=7%>('.$indicators->ngrowth.')</td><td  width=40%>'.$imgtoday.'</td><td width=5%><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>'.$indicators->ninspect.'</td></tr>';
 				}
			}
		elseif($var4!=0&&(time()- $schedule->timecreated)<86400000)
			{	
			$name[$n1][29].='<tr style="white-space: nowrap; text-overflow: ellipsis;" height=30><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >'.$studentname.'</a></td><td width=7%>('.$indicators->ngrowth.')</td><td  width=40%>'.$imgtoday.'</td><td width=5%><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>'.$indicators->ninspect.'</td></tr>';
			}
		elseif($lastaccess<43200)
			{	
			$onlineusers.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >&nbsp;&nbsp;'.$studentname.' </a>&nbsp;&nbsp; <input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>'.$indicators->ninspect.'</td>';
			}
		}
 
	 
	$ngrowth=(INT)($ngrowth_total/$nstd);
	$ncomplete=(INT)(($ncomplete_total)/$nstd);
	$ninteraction=(INT)($ninteraction_total/$nstd);
	$DB->execute("UPDATE {abessi_indicators_class} SET  ngrowth='$ngrowth',ncomplete='$ncomplete', ninteraction='$ninteraction' WHERE teacherid='$teacherid' ORDER BY id DESC LIMIT 1 ");  

	// 보강 출석 체크
	$attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE  dchanged>'$adayago' AND hide=0 ORDER by id DESC " );	
	$results = json_decode(json_encode($attendlog), True);
	unset($value);										
	foreach($results as $value)										
		{
	 	$userid=$value['userid']; 
		$stdname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$dchanged=$value['dchanged'];$dchanged=date("Y-m-d",$dchanged); $today=date("Y-m-d",time()); 
		if($today===$dchanged && (strpos($stdname->firstname, $tsymbol )!==false || strpos($stdname->firstname, $tsymbol1 )!==false ||strpos($stdname->firstname, $tsymbol2 )!==false||strpos($stdname->firstname, $tsymbol3 )!==false) )$showattendlog.='<input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >'.$stdname->lastname.' | ';								 
		}

$feedbackreview.='</table>';
	}
elseif($mode==='tomorrow')
	{
	$n1=$nday+1;
	if($n1==8)
		{
		$nday=1;
		$n1=1;
		}
	if($nday==1)$daytext='월요일';
	elseif($nday==2)$daytext='화요일';
	elseif($nday==3)$daytext='수요일';
	elseif($nday==4)$daytext='목요일';
	elseif($nday==5)$daytext='금요일';
	elseif($nday==6)$daytext='토요일';
	elseif($nday==7)$daytext='일요일';
	$todaytext=' 오늘 ';
	$tomorrowtext=' <b>내일</b> ';
	unset($user);

	foreach($result as $user)
		{
		$userid=$user['id'];
		$studentname=$user['firstname'].$user['lastname'];
		$tafter=time()-86400*$period;
		
		$indicators=$DB->get_record_sql("SELECT * FROM mdl_abessi_indicators where userid='$userid' AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1 ");
		$checkstatus='';
		if($indicators->aion==1)$checkstatus='checked';
		 
		$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
	
		$missionlog= $DB->get_record_sql("SELECT * FROM  mdl_abessi_missionlog WHERE userid='$userid' AND eventid=17 ORDER BY id DESC LIMIT 1 ");
		$recentcurl='https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?'.$missionlog->url;
		$mtid=$missionlog->mtid;
		//if($schedule->timecreated==NULL || $schedule->timecreated > $tafter )$newschedule.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1 " >'.$studentname.'</a>';

		$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
		$ratio1=$engagement3->todayscore;
 
		if($mtid==1 || $mtid==7)$missiontext=' (개념미션) ';
		elseif($mtid==2)$missiontext=' (심화미션) ';
		elseif($mtid==3)$missiontext=' (내신미션) ';
		elseif($mtid==4)$missiontext=' (모의고사) ';
		
		if($ratio1<70)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20></a> <a href="'.$recentcurl.'">  '.$missiontext.'</a>';
		elseif($ratio1<75)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20></a> <a href="'.$recentcurl.'">  '.$missiontext.'</a>';
		elseif($ratio1<80)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20></a> <a href="'.$recentcurl.'">  '.$missiontext.'</a>';
		elseif($ratio1<85)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20></a> <a href="'.$recentcurl.'">  '.$missiontext.'</a>';
		elseif($ratio1<90)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20></a> <a href="'.$recentcurl.'">  '.$missiontext.'</a>';
		elseif($ratio1<95)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20></a>';
		else $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20></a>';
 /*
		if($ratio1<70)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20></a>';
		elseif($ratio1<75)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20></a>';
		elseif($ratio1<80)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20></a>';
		elseif($ratio1<85)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20></a>';
		elseif($ratio1<90)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20></a>';
		elseif($ratio1<95)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20></a>';
		else $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20></a>';
 */
		if($ratio1==0 && $Qnum2==0) $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png width=20></a>';

		$var='start'.$n1;
		$var2=$schedule->$var;
		$var3='duration'.$n1;
		$var4=$schedule->$var3;
		$tbegin=date("H:i",strtotime($var2));
		$time    = explode(':', $tbegin);
		$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;
 		if($var2!=NULL && $var4!=NULL && (time()- $schedule->timecreated)<86400000)
			{	 
			$n2=(int)(($minutes-530)/30);	
 		 	
			$date=date(" h:i A");
			$date2=date("H:i",strtotime($date));
			$time2    = explode(':', $date2);
			$minutes2 =(int)( ($time2[0] * 60.0 + $time2[1] * 1.0)-30);
			$npresent=(int)(($minutes2-530)/30);	

			if($minutes<500)$n2=0;
			if(($npresent==$n2+1||$npresent==$n2+2||$npresent==$n2+3||$npresent==$n2+4||$npresent==$n2+5|$npresent==$n2+6||$npresent==$n2+7||$npresent==$n2+8||$npresent==$n2+9||$npresent==$n2+10||$npresent==$n2) && $nday==$n1)
				{	
				$lastaction=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' "); 
				$lastaction=$lastaction->maxtc;
				$lastaccess=time()-$lastaction;
				$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");
				$tgoal=time()-$goal->timecreated;
				
				if($lastaccess>36000)
					{
					if($tgoal >43200 )$name[$n1][$n2].=$begintable.'<tr height=30><td><span type="button"  onClick=""><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638961977.png" width=30></span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >&nbsp;&nbsp;'.$studentname.'</a></td><td style="align:left;" width="150px"><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>&nbsp;&nbsp;&nbsp;'.$imgtoday.'</td></tr></table>';
					else 
						{
						//$today .=$begintable.'<tr height=30><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1638961977.png" width=30>&nbsp;&nbsp;'.$studentname.'</a> </td><td style="align:left;" width="150px"><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>&nbsp;&nbsp;&nbsp;'.$imgtoday.'</td></tr></table>';
 						$name[$n1][$n2].=$begintable.'<tr height=30><td><span type="button"  onClick="secondbrain(\''.$userid.'\',\''.$studentname.'\')">'.$laststatus.'</span></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >&nbsp;&nbsp;'.$studentname.'</a>  </td><td style="align:left;" width="150px"><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>&nbsp;&nbsp;&nbsp;'.$imgtoday.'</td></tr></table>';
						
						}
					}
				else 
					{
					$name[$n1][$n2].=$begintable.'<tr height=30><td><span type="button"  onClick="secondbrain(\''.$userid.'\',\''.$studentname.'\')">'.$laststatus.'</span></td><td style="align:left;"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >&nbsp;&nbsp;'.$studentname.'</a> </td><td style="align:left;" width="150px"> <input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>&nbsp;&nbsp;&nbsp;'.$imgtoday.'</td></tr></table>';
					
					}
				}
			else 
				{ 
				 $name[$n1][$n2].=$begintable.'<tr height=30><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >&nbsp;&nbsp;'.$studentname.'</a></td><td style="align:left;" width="150px"><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>&nbsp;&nbsp;&nbsp;'.$imgtoday.'</td></tr></table>';

 				}
			}
		elseif($var4!=0&&(time()- $schedule->timecreated)<86400000)
			{	 
			$name[$n1][29].=$begintable.'<tr height=30><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " >&nbsp;&nbsp;'.$studentname.'</a></td><td style="align:left;" width="150px"><input type="checkbox" name="checkAccount"  '.$checkstatus.' onClick="ChangeCheckBox(8,\''.$userid.'\',this.checked)"/>&nbsp;&nbsp;&nbsp;'.$imgtoday.'</td></tr></table>';
			}
		}
	}
}

$nweek= $_GET["nweek"];
if($nweek==NULL)$nweek=4;
$tbegin=time()-604800*$nweek;
$time = array(); 
 
// 협력수업 및 컨텐츠 관리
$collect=$DB->get_records_sql("SELECT * FROM mdl_abessi_abtestlog WHERE timecreated>'$aweekago'  ORDER BY id DESC LIMIT 30 "); 
$result3 = json_decode(json_encode($collect), True);
unset($value3);
foreach($result3 as $value3) 
	{
	$authorid=$value3['userid']; $abtestid=$value3['abtestid']; $drillingtext=$value3['drillingtext'];
	$abtest= $DB->get_record_sql("SELECT * FROM mdl_abessi_abtest WHERE id='$abtestid' ORDER BY id DESC LIMIT 1 ");
	$author= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$authorid' ");
	$creatorname=$author->firstname.$author->lastname;
	if(strpos($drillingtext, '긴급' )!==false)$drillingtext='<b style="color:red;">'.$drillingtext.'</b>';
	$wboardlist.='<tr ><td>'.$abtest->domain.'</td><td>'.$abtest->chapter.'</td><td>'.$abtest->topic.'</td><td>'.$drillingtext.'</td><td  style="vertical-align: top;"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$value3['studentnote'].'" target="_blank">학생노트</a></td><td  style="vertical-align: top;"><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$value3['teachernote'].'&srcid='.$value3['studentnote'].'" target="_blank">협력수업</a></td> <td  style="vertical-align: top; ">'.$creatorname.'</td><td  style="vertical-align: top;">'.date("m/d",$value3['timecreated']).'</td>  </tr>';
	}
$contentsusage= '<table width=100% align=center><tr ><td>영역</td><td>단원</td><td>토픽</td><td>공유내용</td><td>학생노트</td><td>협력수업</td><td>제작자</td><td>제작일</td></tr>'.$wboardlist.'</table><hr style="height:2px;border-width:0;color:gray;background-color:gray">';


// #####################Flywheel 모니터링#################################

 
$almty=$DB->get_records_sql("SELECT * FROM mdl_abessi_missionlog WHERE teacherid='$teacherid' AND event='almty' AND timecreated>'$halfdayago'  ORDER BY id DESC LIMIT 5 "); 
$result2 = json_decode(json_encode($almty), True);
unset($value2);
$nalmty=0;
foreach($result2 as $value2) 
	{
	$tinterval=$tprev-$value2['timecreated'];
	if($value2['page']==='관심학생 관리')$eventPrev='<b style="color:#09b846;">Cleared ! </b>';
	if($value2['page']!=='관심학생 관리' && $nalmty==0)
		{
		$lastview=round((time()-$value2['timecreated'])/60,0).'분전'; 
		$nalmty++;
		}
	if($tinterval<0)$dummy=0;
	else $flywheel.='<tr style="white-space: nowrap; overflow: hidden;  text-overflow: ellipsis;"><td>'.$usernamePrev.'</td><td><a href="'.$contextPrev.'?'.$urlPrev.'">'.$eventPrev.'</a></d><td>'.round($tinterval/60,0).'분</d></tr>';
	$tprev=$value2['timecreated'];
	$eventPrev=$value2['page'];	
	$usernamePrev=$value2['username'];	
	$urlPrev=$value2['url'];	
	$contextPrev=$value2['context'];	
	} 
$flywheel= '<table width=100%><tr><td style="background-color:#fff599;">인지성장 설정</td></tr></table><table width=100%>'.$cogkick3.'</table><hr><table width=100%>'.$cogkick2.'</table><hr><table width=100%>'.$cogkick1.'</table><hr> <table width=100%><tr><td style="background-color:#fff599;">주간평가 누락</td></tr></table><table width=100%>'.$checkAlignment.'</table><hr><table width=100%><tr><td style="background-color:#fff599;">Fly Wheel ('.$lastview.' 최종점검)</td></tr></table><table width=100%>'.$flywheel.'</table>';
 //class="table table-striped"
$nprint=0;
echo '<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					<div class="row">'.$alerttalk2us.'
						<div class="col-md-12">
							<div class="card card-invoice">
								<div class="table-responsive">
									<table width=100% >
									<thead>
									<tr><td width=25% align=center><table align=center><tr><td width=2%></td><td><a href="https://app.gather.town/app/8CrKtY0a9Q7pyTPu/CJN%20meeting%20room"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/gtown.jpg width=40></a> &nbsp;&nbsp;</td> <td><a href="https://app.gather.town/app/0bwIAlhyu6Z7ynWK/KAIST%20TOUCH%20MATH"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1648454380.png width=40></a> &nbsp;&nbsp;</td> <td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$teacherid.'&mode=0"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/livestream.png height=35></a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id='.$teacherid.'&tb=604800"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1640178142.png width=40></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646870805.png width=40></a> </td><td > &nbsp;<span type="button" id="alert_nextpage" style="background-color:#4287f5;color:white;height:40px;"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646906654.png width=40></span></td></tr><tr><td width=2%><hr></td><td><hr></td><td><hr></td><td ><hr></td></tr></table></td>
<td align=center><button type="button"   style="background-color:#fff599;color:black;width:100%;height:40px;">총 '.$size.'명(<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/psclass.php?id='.$teacherid.'&tb=7&mode=today">'.$todaytext.'</a> '.$daytext.'  | <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/psclass.php?id='.$teacherid.'&tb=7&mode=tomorrow">'.$tomorrowtext.'</a>)  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;열공지수 (<b>'.$nenergy.'</b>) &nbsp;&nbsp;&nbsp;성장지표 (<b>'.$ngrowth.'</b>)
&nbsp;&nbsp;&nbsp;완결지표 (<b>'.$ncomplete.'</b>) &nbsp;&nbsp;&nbsp;상호작용 지표 (<b>'.$ninteraction.'</b>)  &nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/checklist/view.php?id=89264&forceview=1"target="_blank">교수법</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/softlanding.php?id='.$teacherid.'"target="_blank">신규생</a></button></td><td><button type="button"  style="background-color:#fff599;color:black;width:100%;height:40px;">체크 포인트</button></td></tr></thead>
									<tr><td style="vertical-align: top"><table width=95% align=center>';
//if($name[$n1][0]!=NULL || $nprint==1){}
echo '<tr><td width=15%>보강</td><td><table width=95%>'.$showattendlog.''.$name[$n1][0].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';
if($name[$n1][1]!=NULL || $nprint==1){ echo '<tr><td width=15%>10:00</td><td><table width=95%>'.$name[$n1][1].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][2]!=NULL || $nprint==1){  echo '<tr><td width=15%>10:30</td><td><table width=95%>'.$name[$n1][2].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][3]!=NULL || $nprint==1){  echo '<tr><td width=15%>11:00</td><td><table width=95%>'.$name[$n1][3].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;} 
if($name[$n1][4]!=NULL || $nprint==1){  echo '<tr><td width=15%>11:30</td><td><table width=95%>'.$name[$n1][4].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][5]!=NULL || $nprint==1){  echo '<tr><td width=15%>12:00</td><td><table width=95%>'.$name[$n1][5].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][6]!=NULL || $nprint==1){  echo '<tr><td width=15%>12:30</td><td><table width=95%>'.$name[$n1][6].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][7]!=NULL || $nprint==1){  echo '<tr><td width=15%> 1:00</td><td><table width=95%>'.$name[$n1][7].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][8]!=NULL || $nprint==1){  echo '<tr><td width=15%> 1:30</td><td><table width=95%>'.$name[$n1][8].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][9]!=NULL || $nprint==1){  echo '<tr><td width=15%> 2:00</td><td><table width=95%>'.$name[$n1][9].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][10]!=NULL || $nprint==1){  echo '<tr><td width=15%> 2:30</td><td><table width=95%>'.$name[$n1][10].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][11]!=NULL || $nprint==1){  echo '<tr><td width=15%> 3:00</td><td><table width=95%>'.$name[$n1][11].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][12]!=NULL || $nprint==1){  echo '<tr><td width=15%> 3:30</td><td><table width=95%>'.$name[$n1][12].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][13]!=NULL || $nprint==1){  echo '<tr><td width=15%> 4:00</td><td><table width=95%>'.$name[$n1][13].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][14]!=NULL || $nprint==1){  echo '<tr><td width=15%> 4:30</td><td><table width=95%>'.$name[$n1][14].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][15]!=NULL || $nprint==1){  echo '<tr><td width=15%> 5:00</td><td><table width=95%>'.$name[$n1][15].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][16]!=NULL || $nprint==1){  echo '<tr><td width=15%> 5:30</td><td><table width=95%>'.$name[$n1][16].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][17]!=NULL || $nprint==1){  echo '<tr><td width=15%> 6:00</td><td><table width=95%>'.$name[$n1][17].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][18]!=NULL || $nprint==1){  echo '<tr><td width=15%> 6:30</td><td><table width=95%>'.$name[$n1][18].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][19]!=NULL || $nprint==1){  echo '<tr><td width=15%> 7:00</td><td><table width=95%>'.$name[$n1][19].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][20]!=NULL || $nprint==1){  echo '<tr><td width=15%> 7:30</td><td><table width=95%>'.$name[$n1][20].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][21]!=NULL || $nprint==1){  echo '<tr><td width=15%> 8:00</td><td><table width=95%>'.$name[$n1][21].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][22]!=NULL || $nprint==1){  echo '<tr><td width=15%> 8:30</td><td><table width=95%>'.$name[$n1][22].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][23]!=NULL || $nprint==1){  echo '<tr><td width=15%> 9:00</td><td><table width=95%>'.$name[$n1][23].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][24]!=NULL || $nprint==1){  echo '<tr><td width=15%> 9:30</td><td><table width=95%>'.$name[$n1][24].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][25]!=NULL || $nprint==1){  echo '<tr><td width=15%>10:00</td><td><table width=95%>'.$name[$n1][25].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][26]!=NULL || $nprint==1){  echo '<tr><td width=15%>10:30</td><td><table width=95%>'.$name[$n1][26].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][27]!=NULL || $nprint==1){  echo '<tr><td width=15%>11:00</td><td><table width=95%>'.$name[$n1][27].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
if($name[$n1][28]!=NULL || $nprint==1){  echo '<tr><td width=15%>11:30</td><td><table width=95%>'.$name[$n1][28].'</table></td></tr><tr><td width=15%><hr></td><td><hr></td></tr>';$nprint=1;}
 					 			
echo '</tbody></table></td><td style="vertical-align: top">'.$asklist.'</table><hr style="height:2px;border-width:0;color:gray;background-color:gray">'.$feedbackreview.'<hr style="height:2px;border-width:0;color:gray;background-color:gray">'.$contentsusage.'</td><td width=20% style="vertical-align: top">'.$flywheel.'</td></tr></table><hr><table><tr><td>&nbsp;&nbsp;&nbsp; # 일정 외 접속자 '.$onlineusers.' </td> </tr></table><br> 
									</div>
								</div>
							</div>	
						<div class="seperator-solid  mb-3"></div>
					</div>	
				</div>
			</div>
		</div> ';

include("quicksidebar.php");

$autogradeOn=$DB->get_record_sql("SELECT data   FROM mdl_user_info_data where userid='$USER->id' and fieldid='82' ");
$autoGradeState=$autogradeOn->data;
 
echo '
<script>	
function reportData(Userid,Username)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "Talk2us (" + Username + ")",
 	input: "textarea", 
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: "활동루틴 설계하기 (작용점 + 접근법)",
  	inputAttributes: {
   	 "aria-label": "Type your message here", Height:1500,
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'10\',
		"inputtext":text,	
		"userid":Userid,
		},
		success:function(data){
		var Teacherid=data.teacherid;
		 	
		window.open("https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?tb=604800&id="+Teacherid, "_blank");   	
				   }
			 })
	      	 }
		})()
	}
function sendmessage(Talkid,Eventid,Studentid)
			{
			 
			 
			var Teacherid= \''.$teacherid.'\';
			var Managerid= \''.$USER->id.'\';
                                           //alert(Studentid);
			if(Eventid==3)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="출결이상";
				}
			else if(Eventid==4)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="효율이상";
				}
			else if(Eventid==5)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="풀이이상";
				}
			else if(Eventid==6)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="성취이상";
				}
			else if(Eventid==7)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="성취및침착이상";
				}
			else if(Eventid==8)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="분기목표정비";
				}
			else if(Eventid==9)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="장기미접속";
				}
			else if(Eventid==10)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="휴원";
				}
			else if(Eventid==11)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="실시간평점이상";
				}
			else if(Eventid==12)
				{
				var text1="전달내용 입력하기";
				var text2="조치완료 하였습니다.";
				var context="실시간성장지표이상";
				}
			swal("Talk2us",  "문제해결 과정에 대한 의견/도움요청 및 결과에 대한 메세지를 전달해주세요.",{
				
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
				
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			     swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
				swal({
					title: \'피드백을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "전달사항 입력하기",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();						
					swal("", "입력된 내용 : " + Inputtext, {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"talkid":Talkid,
					"eventid":Eventid,
					"feedbackid":\'1\',
					"inputtext":Inputtext,	
					"studentid":Studentid,
					"teacherid":Teacherid,
					"managerid":Managerid,
					"context":context,
					},
					success:function(data){
					
					 }
					 })
				 location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"talkid":Talkid,
					"eventid":Eventid,
					"feedbackid":\'2\',
					"inputtext":text2,	
					"studentid":Studentid,
					"teacherid":Teacherid,
					"managerid":Managerid,
					"context":context,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 
 			   default:
			     swal("취소되었습니다.", {buttons: false,timer: 500});
				  }
				});			 		
			};
 
 
function ChangeCheckBox(Eventid,Userid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
	 			}
	  				swal("처리되었습니다.", {
					buttons: false,
					timer: 500,
				});
				$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"userid":Userid,       
		                		"checkimsi":checkimsi,
		                 		"eventid":Eventid,
		                 		 
					},
					success:function(data){
					 }
				})	 
		 
		}

function secondbrain(Userid, Username){
				var Fbtype;
				var Fbtext;
				var Contextid;
				var Fburl;
			 	swal({text: \'활동 데이터를 분석 중입니다. \',buttons: false,})
              			 $.ajax({
					url: "../students/2ndbrain.php",
					type: "POST",
					dataType:"json",
              				data : {	 
				        	"userid":Userid,
               			        	}, 
                				success:function(data) 
						{
						Fbtype=data.fbtype;
						Fbtext=data.fbtext;
						Contextid=data.contextid;	
						Fburl=data.fburl;	
						swal({
								title: Username+\'의 \' + Fbtype ,
								text: Fbtext,
								type: \'warning\',
								buttons:{
									confirm: {
										text : \'확인하기\',
										className : \'btn btn-primary\'
									},
									cancel: {
										visible: true,
										text : \'취소\',
										className: \'btn btn-danger\'
									}   
							 	}
							}).then((willDelete) => {
								if (willDelete) {
								 window.location.href =Contextid+"?"+Fburl;	 					 
								} 
							});
					setTimeout(function(){location.reload();},1000);  	
						}
            	   		  	      });
				}	

	
$(\'#alert_nextpage\').click(function(e) {
				var Userid= \''.$teacherid.'\'; 
				var AutoGrade= \''.$autoGradeState.'\'; 
				var Username;
				var Fbtype;
				var Fbgoal;
				var Fbtext;
				var Fburl;
				var Prepareimg;
				var Summary;
				var Source=8;
				var audiourl;
				var audio;

              			 $.ajax({
					url: "../whiteboard/almtyroutine.php",
					type: "POST",
					dataType:"json",
              				data : {	 
				        	"userid":Userid,
					"source":Source,
               			        	}, 
                				success:function(data) 
						{
						Username=data.username;
						Fbtype=data.fbtype;
						Fbgoal=data.fbgoal;
						Fbtext=data.fbtext;
						Fburl=data.fburl;	
						Prepareimg=data.prepareimg;
						
						audiourl=data.audio;
						audio =new Audio(audiourl); 
						audio.play();
 					 
					              if(AutoGrade.indexOf("AUTO") >= 0 && Fbtype==="학습결과 평가" )
							{
							 window.location.href =Fburl;	 
							}
						else
							{
							        swal({
									title: Username+\'의 \' + Fbtype ,
									text: Fbtext,
									type: \'warning\',
									buttons:{
									 	confirm: {
										text : \'NEXT\',
										className : \'btn btn-primary\'
										},
									}
									}).then((willDelete) => {
									if (willDelete) {
									 window.location.href =Fburl;	 					 
									} 
							            });
							}
						}
            	   		  	      });
			}); 
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
	<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>
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
?>