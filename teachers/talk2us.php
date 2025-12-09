<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");
$id=$_GET["id"];   
$mode=$_GET["mode"];   
$tb=$_GET["tb"];   
 
echo '<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
 
// url 정보 이용하여 기간, 내용, 학생, 선생님 등 검색 가능하도록 *********************************************
 
$tbegin=time()-$tb; //1주 전
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='7128' AND  timecreated> '$tbegin'    ORDER BY timemodified DESC ");  
$talklist= json_decode(json_encode($share), True);
 
unset($value);  
foreach($talklist as $value)
	{
	$sid=$value['id'];
	$studentid=$value['studentid'];
	$teacherid=$value['teacherid'];
	$sharetext=$value['text'];
	$stdname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdname->firstname.$stdname->lastname;
	$tchname= $DB->get_record_sql("SELECT institution, lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
	$teachername=$tchname->firstname.$tchname->lastname;
	if($tchname->institution!==$academy)continue;
 
	 
	$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
	$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid' AND courseid NOT LIKE '239' AND component NOT LIKE 'core' AND  component NOT LIKE 'local_webhooks'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog		 
	$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 

	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
	$tgoal=time()-$goal->timecreated;

	$ratio1=$engagement3->todayscore;  $ngrowth=$engagement3->ngrowth; $usedtime=$engagement3->usedtime; $totaltime=$engagement3->totaltime; $nattempts=$engagement3->nattempts; 
	$attemptefficiency=$nattempts/$totaltime;
	 
	$weekdata= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE  type LIKE '주간목표' AND userid='$studentid' ORDER BY id DESC LIMIT 1  ");  // abessi_indicators 
	$ratio2= $weekdata->score; $daysetgoal=(time()-$weekdata->timecreated)/86400;
  	$analysistext='';
	if($usedtime<70)$analysistext='출결이상';
	elseif($nattempts<30)$analysistext='풀이이상';
	elseif($attemptefficiency<5)$analysistext='효율이상';
		
		
	$useinfo=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where  userid='$studentid' AND fieldid='90' "); 
	if($useinfo->data==NULL)$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646447390.png" width=30>'; 
	elseif($useinfo->data==='자습')
		{
		$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646435924.png" width=30>'; 
		if($indicators->aion==1)
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646435924.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436175.png" width=30> '; 
			}
		else
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
			}
		}
	elseif($useinfo->data==='지도')
		{
		$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436605.png" width=30>'; 
		if($indicators->aion==1)
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436605.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436540.png" width=30> '; 
			}
		else
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
			}
		}
 	 elseif($useinfo->data==='도제') 
		{
		$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436775.png" width=30>';
		if($indicators->aion==1)
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436775.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436824.png" width=30> '; 
			}
		else
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
			}
		}

	if($tlastaction>36000)$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646616360.png" width=30> '; 

	if($ratio2<70)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
	elseif($ratio2<75)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
	elseif($ratio2<80)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
	elseif($ratio2<85)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
	elseif($ratio2<90)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
	elseif($ratio2<95)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	else $imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
	if($ratio2==0) $imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

 	if($ratio1<70)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<75)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<80)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<85)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<90)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<95)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	else $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	if($ratio1==0 && $Qnum2==0) $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';

	if($mode==='my')
		{
		if($id==$teacherid)
          			{
			$sharelist.='<table width=100% ><tbody><tr><td width=1%></td><td width=7% style="white-space: nowrap; text-overflow: ellipsis;" valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank"><b style="color:black;">'.$studentname.'</b></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/agamotto.png" width=20></a></td><td width=5% valign=top><a  style="color:#3399ff;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800&mode=my">'.$teachername.'</a></td><td style="color:#3399ff;"  valign=top>'.$sharetext.' <span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span type="button"  onClick="reportData(\''.$studentid.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=25></span><hr>  </td><td width=5%  valign=top>'.$analysistext.'</td>
<td width=6% style="white-space: nowrap; text-overflow: ellipsis;" valign=top>✎<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank">개념</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank">심화</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank">내신</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank">수능</a></td><td width=10% valign=top>'.$imgtoday.' ('.$ngrowth.')</td><td width=10% valign=top>'.date("m/d", $value['timecreated']).'</td></tr></tbody></table>';

			$feedback=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='8217' AND talkid='$sid'    ORDER BY id ASC ");  
			$feedbacklist= json_decode(json_encode($feedback), True);
			$fbname='fb'.$sid;
			unset($value2);  
			foreach($feedbacklist as $value2)
				{
				$fbid=$value2['id'];
				$feederid=$value2['teacherid'];
				$feedertext=$value2['text'];
				$tcreated=round((time()-$value2['timecreated'])/60,0);
				$feeder= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$feederid' ");
				if($value2['hide']==1) $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:16px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 0)"><img src=https://mathking.kr/Contents/IMAGES/hide.png width=20></span><hr></td><td width=35%></td></tr>';
				else $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:14px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$fbid.'\',\''.$feedertext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 1)"><img src=https://mathking.kr/Contents/IMAGES/view.png width=20></span><hr></td><td width=35%></td></tr>';
				}
			$sharelist.='<table width=100%><tbody>'.$$fbname.'</tbody></table>';
			}
		}
	else
		{ 
		$sharelist.='<table width=100% ><tbody><tr><td width=1%></td><td width=7% style="white-space: nowrap; text-overflow: ellipsis;" valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank"><b style="color:black;">'.$studentname.'</b></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'"target="_blank"><img style="margin-bottom:5px" src="https://mathking.kr/Contents/IMAGES/agamotto.png" width=25></a></td><td width=5% valign=top><a style="color:#3399ff;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800&mode=my">'.$teachername.'</a></td><td style="color:#3399ff;"  valign=top>'.$sharetext.' <span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span type="button"  onClick="reportData(\''.$studentid.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=25></span><hr> </td><td width=5%  valign=top>'.$analysistext.'</td>
<td width=6% style="white-space: nowrap; text-overflow: ellipsis;"  valign=top>✎<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank">개념</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank">심화</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank">내신</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank">수능</a></td><td width=10%  valign=top>'.$imgtoday.' ('.$ngrowth.')</td><td width=10%  valign=top>'.date("m/d", $value['timecreated']).'</td></tr></tbody></table>';

		$feedback=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='8217' AND talkid='$sid'    ORDER BY id ASC ");  
		$feedbacklist= json_decode(json_encode($feedback), True);
		$fbname='fb'.$sid;
		unset($value2);  
		foreach($feedbacklist as $value2)
			{
			$fbid=$value2['id'];
			$feederid=$value2['teacherid'];
			$feedertext=$value2['text'];
			$tcreated=round((time()-$value2['timecreated'])/60,0);
			$feeder= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$feederid' ");
			$feedername=$feeder->firstname.$feeder->lastname;
			if($value2['hide']==1) $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:16px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$fbid.'\',\''.$feedertext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 0)"><img src=https://mathking.kr/Contents/IMAGES/hide.png width=20></span><hr></td><td width=35%></td></tr>';
			else $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:14px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$fbid.'\',\''.$feedertext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 1)"><img src=https://mathking.kr/Contents/IMAGES/view.png width=20></span><hr></td><td width=35%></td></tr>';
			}
		$sharelist.='<table width=100%><tbody>'.$$fbname.'</tbody></table>';
		}
	}
 
$ndays=(INT)($tb/86400);
if($mode==='my')echo '<table width=100%><tr><td align=center width=7%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb=604800"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646960692.png" width=50></a></td><td width=10%></td><td><b style="font-size:28;"> &nbsp;We transfer intelligence </b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-size:12;"> (작용점 + 방법 + 효율향상 아이디어) </span>  <a href="https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?id=301&theme=bubble"target="_blank"><img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQT1m2nrYhDmd3vVvZx3uXscv2hidCSKNCvXA&usqp=CAU" width=40></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb=86400">오늘</a>&nbsp;&nbsp;&nbsp;&nbsp;<td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb='.($tb+86400).'">더보기</a></td><td width=2%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb='.($tb-86400).'">덜보기</a></td></td><td>('.$ndays.'일)</td><td width=5%></td></tr></table><hr><table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$sharelist.'</tbody></table> ';
                 else echo '<table width=100%><tr><td align=center width=7%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb=604800&mode=my"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646961547.png" width=50></a></td><td width=10%></td><td><b style="font-size:28;"> &nbsp;We transfer intelligence </b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-size:12;"> (작용점 + 방법 + 효율향상 아이디어) </span> <a href="https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?id=301&theme=bubble"target="_blank"><img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQT1m2nrYhDmd3vVvZx3uXscv2hidCSKNCvXA&usqp=CAU" width=40></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb=86400">오늘</a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb='.($tb+86400).'">더보기</a></td><td width=2%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$USER->id.'&tb='.($tb-86400).'">덜보기</a></td><td>('.$ndays.'일)</td><td width=5%></td></tr></table><hr><table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$sharelist.'</tbody></table> ';
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
	include("quicksidebar.php");
 

echo '
<script>	
function reportData(Userid,Sid,Username)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "Talk2us (" + Username +")",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: "공유된 의견과 데이터를 토대로 의견을 입력해 주세요",
  	inputAttributes: {
   	 "aria-label": "Type your message here"
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
 		"eventid":\'11\',
		"inputtext":text,	
		"userid":Userid,
		"sid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
	}
function hide(Eventid,Fbid, Checkvalue){
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
		"eventid":Eventid,
            		"fbid":Fbid,
            	 	"checkimsi":checkimsi,
            	 	  },
 	  	 success: function (data){  
		var Teacherid=data.teacherid
		setTimeout(function() {location.reload(); },100);	
  	   	   }
		  });
		}

function Edittext(Itemid,Inputtext)
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
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'19\',
		"itemid":Itemid,
		"inputtext":text,	
		},
		success:function(data){
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
	}
</script> 


<style>
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

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
 ';
?>