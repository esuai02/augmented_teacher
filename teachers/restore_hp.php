<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;

$functionname='sendmessage2';
if($role==='manager')
	{
	$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$USER->id' "); 

	$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
	$tsymbol1=$teacher1->symbol;
	$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
	$tsymbol2=$teacher2->symbol;
	$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
	$tsymbol3=$teacher3->symbol;  
	$functionname='sendmessage';
	}

if($tsymbol1==NULL)$tsymbol1='KTM';
if($tsymbol2==NULL)$tsymbol2='KTM';
if($tsymbol3==NULL)$tsymbol3='KTM';
echo ' 

<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
///////////////// begin of table /////////////////// 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$wtimestart=$timecreated-86400*($nday+3);

$sssskey= sesskey(); 
$hourAgo2=time()-7200;
$hoursago3=time()-10800;

$monthsago2=time()-6048000; // 10주 전
$wblist2='';
$nratewb=0;$nratewb2=0;
$nview=0;
$totalgrade1=0;$totalgrade2=0;
$nstudents1=0;$nstudents2=0; 

$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE  institution LIKE '$academy' AND lastaccess> '$monthsago2'  AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
$userlist= json_decode(json_encode($mystudents), True);

$attendance='';
$timecreated=time();
$daysago5=time()-432000;
$aweekago=time()-604800; 
$weeksago2=time()-604800*2;
$weeksago3=time()-604800*3;
$weeksago4=time()-604800*4;
 
$timedelayed1=time()-30;
$timedelayed2=time()-300;
 


unset($user);
foreach($userlist as $user)
	{
	$studentid=$user['id'];
	$firstname=$user['firstname'];
	$lastname=$user['lastname'];
	
 	$studentname=$firstname.$lastname;

 	$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$studentid' AND fieldid='22' "); 
	$thisuserrole=$userrole->role;
	$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
	$ratio1=$engagement3->todayscore;  $ngrowth=$engagement3->ngrowth; $usedtime=$engagement3->usedtime; $totaltime=$engagement3->totaltime; $nattempts=$engagement3->nattempts; 
	$attemptefficiency=$nattempts/$totaltime;
	$dayslastaccess=round((time()-$user['lastaccess'])/86400,0);
	$weekdata= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE  type LIKE '주간목표' AND userid='$studentid' ORDER BY id DESC LIMIT 1  ");  // abessi_indicators 
	$ratio2= $weekdata->score; $daysetgoal=(time()-$weekdata->timecreated)/86400;
 
	if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
	elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
	elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
	elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
	elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
	elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
	if($ratio1==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

	if($ratio2<70)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
	elseif($ratio2<75)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
	elseif($ratio2<80)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
	elseif($ratio2<85)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
	elseif($ratio2<90)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
	elseif($ratio2<95)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	else $imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
	if($ratio2==0) $imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

 	$tlastaction=time()-$user['lastaccess'];
	$recentcurl='https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid;
	if($attemptefficiency<5  && $thisuserrole==='student' && $user['lastaccess']>$aweekago)    // 4
		{
		$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '4' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
		$user_alert14.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1643479473.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4" target="_blank" >('.$nattempts.'/'.$totaltime.')</a> </td><td width=2%></td><td>오늘<img src="'.$imgtoday.'" width=20> 최근<img src="'.$imgmonth.'" width=20></td><td> '.$dayslastaccess.'일전</td> <td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
		<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left >효율이상</td><td>&nbsp;&nbsp; </td><td width=2.5%><span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(4,'.$studentid.');">메세지</span> </td> <td> </td></tr>';
		$n114++;
 		}
	elseif($nattempts<30  && $thisuserrole==='student' &&$user['lastaccess']>$aweekago)  // 5
		{
		$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '5' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
		$user_alert15.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1643479473.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4" target="_blank" >('.$nattempts.'/'.$totaltime.')</a> </td><td width=2%></td><td>오늘<img src="'.$imgtoday.'" width=20> 최근<img src="'.$imgmonth.'" width=20></td><td> '.$dayslastaccess.'일전</td> <td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
		<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left >풀이이상</td><td>&nbsp;&nbsp; </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(5,'.$studentid.');">메세지</span> </td> <td> </td></tr>';
		$n115++;
 		}
	if($user['suspended']==0&&$user['lastaccess']>$aweekago && $ratio2<80  && $ratio2>0 && $ratio1<80 && $ngrowth < 2  && $thisuserrole==='student') // 7
		{
		$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '7' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
		$user_alert13.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1630796526001.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" ><b>'.$studentname.'</b></a></td><td>'.$ngrowth.'개 </td><td width=2%></td><td>오늘<img src="'.$imgtoday.'" width=20> 최근<img src="'.$imgmonth.'" width=20></td><td> '.$dayslastaccess.'일전</td> <td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
		<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left >성취+침착도 이상 </td><td>&nbsp;&nbsp; </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(7,'.$studentid.');">메세지</span> </td> <td> </td></tr>';
		$n111++;
		}
	elseif($user['suspended']==0&&$user['lastaccess']>$aweekago && $ratio2<80 && $ratio2>0 && $ngrowth < 2  && $thisuserrole==='student')  // 6
		{
		$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '6' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
		$user_alert12.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642414407.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td>'.$ngrowth.'개 </td><td width=2%></td><td>오늘<img src="'.$imgtoday.'" width=20> 최근<img src="'.$imgmonth.'" width=20></td><td> '.$dayslastaccess.'일전</td> <td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
		<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left >성취 이상 </td><td>&nbsp;&nbsp; </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(6,'.$studentid.');">메세지</span> </td> <td> </td></tr>';
		$n112++;
 		}
	elseif($usedtime<70  && $thisuserrole==='student' &&$user['lastaccess']>$aweekago) // 3
		{
		$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '3' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
		$user_alert11.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1643479473.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4" target="_blank" >'.$usedtime.'%</a> </td><td width=2%></td><td>오늘<img src="'.$imgtoday.'" width=20> 최근<img src="'.$imgmonth.'" width=20></td><td> '.$dayslastaccess.'일전</td> <td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
		<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left >출결 이상 </td><td>&nbsp;&nbsp; </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(3,'.$studentid.');">메세지</span> </td> <td> </td></tr>';
		$n113++;
 		}

	if(($user['lastaccess']<$daysago5 || ($daysetgoal>7 && $daysetgoal<60) ) && $thisuserrole==='student')  
		{
		if($user['suspended']==0)
			{
			if($daysetgoal>7 && $user['lastaccess']>$daysago5 ) // 8
				{
				$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '8' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
				$user_alert2.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1643479473.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'><span style="color:blue;">분기목표 정비</span></a></td><td> '.$dayslastaccess.'일전</td>
				<td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
				<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left > </td><td>&nbsp;&nbsp; </td><td width=2.5%><b style="color:blue;">정비</b></td> <td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(8,'.$studentid.');">메세지</span> </td></tr>'; 
				}
			else                          // 9
				{
				$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '9' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
				$user_alert2.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1630796526001.png" width=30></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a> </td><td><span style="color:red;">장기 미접속</span></td><td> '.$dayslastaccess.'일전</td>
				<td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
				<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left > </td><td>&nbsp;&nbsp; </td><td width=2.5%><b style="color:red;">위험</b></td> <td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(9,'.$studentid.');">메세지</span> </td></tr>';
				}
			$n121++;
			}
		else                                      // 10
			{
			$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '10' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
			$user_alert3.= '<tr><td width=5%></td><td>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642111146.png" width=20></a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td></td><td> '.$dayslastaccess.'일전</td>
			<td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
			<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left > </td><td>&nbsp;&nbsp; </td><td width=2.5%>휴원</td> <td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(10,'.$studentid.');">메세지</span> </td></tr>';
			$n122++;
			}
		}
 
 	if($user['suspended']==0&&$user['lastaccess']>$daysago5 && $ratio1<80 && $ratio1>1 && $thisuserrole==='student') 
		{
		if($tlastaction<36000)  // active user 들에 대한 정보만 가지고 오기         // 11
			{
			$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '11' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
 			$n21++;
			if($prevNview!=$nview)$$viewname='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0';		 

			$wblist0='<td><a href="'.$lastUrl.'" target="_blank">'.$statusmark.'</a></td>';
  
			// 화이트보드 목록 가져오기

 
			echo '
			<script>

			function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue)
				{
				var checkimsi = 0;
   				if(Checkvalue==true){
        					checkimsi = 1;
    					}
  			 	$.ajax({
       				 url: "../managers/check.php",
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
			</script>';
 
	 
			if($tlastaction<60)$num=1;
			elseif($tlastaction<120)$num=2;
			elseif($tlastaction<180)$num=3;
			elseif($tlastaction<240)$num=4;			
			elseif($tlastaction<300)$num=5;
			elseif($tlastaction<420)$num=6;
			elseif($tlastaction<600)$num=7;
			elseif($tlastaction<900)$num=8;
			elseif($tlastaction<1200)$num=9;
			elseif($tlastaction<1500)$num=10;
			elseif($tlastaction<1800)$num=11;
			elseif($tlastaction<2400)$num=12;
			elseif($tlastaction<3000)$num=13;
			elseif($tlastaction<3600)$num=14;
			elseif($tlastaction<5400)$num=15;
			elseif($tlastaction<7200)$num=16;
			elseif($tlastaction<9000)$num=17;
			elseif($tlastaction<10800)$num=18;
			else $num=19;

			$today='today'.$num;
			$$today.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="'.$imgtoday.'" width=20></a>'.$wblist0.'</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td> '.round($tlastaction/60,0).'분</td>
			<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0" target="_blank">Onair</a></td><td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
			<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left > </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(11,'.$studentid.');">메세지</span> </td><td> </td>
			</tr>';
			}
		}
 	if($user['suspended']==0&&$user['lastaccess']>$daysago5 && $ngrowth<2 && $thisuserrole==='student') 
		{
		if($tlastaction<36000)  // active user 들에 대한 정보만 가지고 오기   //12
			{
			$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid LIKE '$studentid' AND eventid LIKE '12' AND status NOT LIKE 'complete' ORDER BY id DESC LIMIT 1"); $statecolor='lightgreen'; if($exit->id!=NULL)$statecolor='pink';
			if($prevNview!=$nview)$$viewname='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0';		 

			$wblist0='<td><a href="'.$lastUrl.'" target="_blank">'.$statusmark.'</a></td>';
  
			// 화이트보드 목록 가져오기

 
			echo '
			<script>

			function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue)
				{
				var checkimsi = 0;
   				if(Checkvalue==true){
        					checkimsi = 1;
    					}
  			 	$.ajax({
       				 url: "../managers/check.php",
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
			</script>';
 
	 
			if($tlastaction<60)$num=1;
			elseif($tlastaction<120)$num=2;
			elseif($tlastaction<180)$num=3;
			elseif($tlastaction<240)$num=4;			
			elseif($tlastaction<300)$num=5;
			elseif($tlastaction<420)$num=6;
			elseif($tlastaction<600)$num=7;
			elseif($tlastaction<900)$num=8;
			elseif($tlastaction<1200)$num=9;
			elseif($tlastaction<1500)$num=10;
			elseif($tlastaction<1800)$num=11;
			elseif($tlastaction<2400)$num=12;
			elseif($tlastaction<3000)$num=13;
			elseif($tlastaction<3600)$num=14;
			elseif($tlastaction<5400)$num=15;
			elseif($tlastaction<7200)$num=16;
			elseif($tlastaction<9000)$num=17;
			elseif($tlastaction<10800)$num=18;
			else $num=19;

			 
			if($ngrowth==0)  
				{
				$month1.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="'.$imgmonth.'" width=20></a>'.$wblist0.'</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td> '.round($tlastaction/60,0).'분</td>
				<td><b style="color:red;">'.$ngrowth.'개</b></td><td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
				<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left > </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(12,'.$studentid.');">메세지</span> </td><td> </td></tr>';
				$n221++;
				}
			if($ngrowth==1)   
				{
				$month2.= '<tr><td width=5%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.' " target="_blank" ><img src="'.$imgmonth.'" width=20></a>'.$wblist0.'</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.'</a></td><td> '.round($tlastaction/60,0).'분</td>
				<td><b style="color:blue;">'.$ngrowth.'개</b></td><td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
				<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td> <td><a href="'.$recentcurl.'"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png width=20></a> &nbsp; &nbsp;</td><td align=left > </td><td width=2.5%> <span style="background-color:'.$statecolor.';" onclick="'.$functionname.'(12,'.$studentid.');">메세지</span> </td><td> </td></tr>';
				$n222++;
				}
			}
		}
	 }  
 
$afewminutesago=time()-3600;

$cell11='<table style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$user_alert11.$user_alert14.$user_alert15.'<tr><td width=5%><hr></td><td> <hr></td><td> <hr></td><td><hr></td><td width=2%><hr></td><td><hr></td><td><hr></td> <td width=5%><hr></td> <td><hr></td><td align=left ><hr> </td><td> <hr> </td><td width=2.5%><hr> </td> <td> <hr></td></tr>'.$user_alert12.$user_alert13.'</tbody></table>';		
$cell12='<table style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$user_alert2.$user_alert3.'</tbody></table>';	
$cell21='<table style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$today1.$today2.$today3.$today4.$today5.$today6.$today7.$today8.$today9.$today10.$today11.$today12.$today13.$today14.$today15.$today16.$today17.$today18.$today19.'</tbody></table>';	
$cell22='<table style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$month1.$month2.'</tbody></table>';	

echo '<table width=90%><tr><th width=8%></th><th width=45%>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;학생상담 (월간평점 + 성장지표, <b style="color:red;"> '.$n111.' (반순환 추천)</b> + <b style="color:green;"> '.$n112.' </b> 명)</th><th width=2%></th><th width=45%>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;위험학생 (미접속 장기화, <b style="color:red;"> '.$n121.' (학부모 상담)</b> + <b style="color:green;"> '.$n122.' </b> 명)</th></tr>
<tr><td></td><td><hr></td><td><hr></td><td><hr></td></tr>
<tr><td></td><td valign=top>'.$cell11.'</td><td></td><td valign=top>'.$cell12.'</td></tr>
<tr><td></td><td><hr></td><td><hr></td><td><hr></td></tr>
<tr><td></td><td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;오늘평점 (활동중, <b style="color:red;"> '.$n21.'명 (실시간 촉진))</b></b></td><td> </td><td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;성장지표(1개/주 이하, <b style="color:red;"> '.$n221.' (커리큘럼 조정)</b> + <b style="color:green;"> '.$n222.' </b> 명)</td></tr>
<tr><td></td><td><hr></td><td><hr></td><td><hr></td></tr>
<tr><td></td><td valign=top>'.$cell21.'</td><td></td><td valign=top>'.$cell22.'</td></tr></table>';

echo '<hr><table width=100%><tr><td width=2.5%><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596540001.png" height=28></td><td align=left> 매일 새롭게 HP restore를 업그레이드 한다. 사전조치, 시작 전 활동 등으로 대응.</td><td width=10%><a href="#"  onclick="'.$viewurls.'" >'.$viewallimg.'</a></td><td width=10%><a href="#"  onclick="'.$openurls2.'" >'.$evaltext2.'</a></td><td width=10%><a href="#"  onclick="'.$openurls.'" >'.$evaltext.'</a></td></tr></table>';
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
</style>
<script>
function sendmessage(Eventid,Studentid)
			{
			var Managerid= \''.$USER->id.'\';
                                           //alert(Studentid);
			if(Eventid==3)
				{
				var text1="전달내용 입력하기";
				var text2="접속기록과 출결기록이 일치하지 않습니다. 확인바랍니다.";
				var text3="휴강빈도 수가 너무 많은 거 같습니다. 안정화 방안에 대해 고민해 보시면 좋겠습니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="출결이상";
				}
			else if(Eventid==4)
				{
				var text1="전달내용 입력하기";
				var text2="학생의 총 공부양에 비하여 문제풀이 양이 부족해 보입니다.";
				var text3="문제풀이 양이 부족한 원인을 분석하고 개선방안을 찾아주시기 바랍니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="효율이상";
				}
			else if(Eventid==5)
				{
				var text1="전달내용 입력하기";
				var text2="문제풀이의 총량이 너무 적어 보입니다.";
				var text3="문제풀이 양이 부족한 원인을 분석하고 개선방안을 찾아주시기 바랍니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="풀이이상";
				}
			else if(Eventid==6)
				{
				var text1="전달내용 입력하기";
				var text2="주 단위 인증시험 테스트 빈도가 낮습니다.";
				var text3="테스트 통과 빈도가 낮은 원인을 분석해 주시고 조치해 주시기 바랍니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="성취이상";
				}
			else if(Eventid==7)
				{
				var text1="전달내용 입력하기";
				var text2="학생상담이 필요해 보입니다.";
				var text3="평점관리에 집중하여 안정화하는 방향으로 접근해 보시는 것을 추천드립니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="성취및침착이상";
				}
			else if(Eventid==8)
				{
				var text1="전달내용 입력하기";
				var text2="학생의 학습활동이 분기목표 중심의 흐름에서 이탈한 것으로 보입니다.";
				var text3="분기목표에 대한 학생상담과 주간목표 설정이 필요해 보입니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="분기목표정비";
				}
			else if(Eventid==9)
				{
				var text1="전달내용 입력하기";
				var text2="장기 미접속 상황입니다. ";
				var text3="학부모 상담을 통한 문제해결 또는 전반 등을 검토해 보시기 바랍니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="장기미접속";
				}
			else if(Eventid==10)
				{
				var text1="전달내용 입력하기";
				var text2="입시정보 제공 등 마무리 talk으로 이미지 관리 및 재등원의 가능성을 만들 수 있습니다 ";
				var text3="좋지 않은 상황의 퇴원이라면 상황을 공유해주시고 마무리 talk 방향을 상의해 주세요 ~";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="휴원";
				}
			else if(Eventid==11)
				{
				var text1="전달내용 입력하기";
				var text2="실시간 평점 이상 상황입니다. 메타인지 컨텐츠를 활용해 주세요";
				var text3="오늘 일정 중 남아있는 부분을 프리뷰 해 주시고 활동흐름을 회복해 주세요";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="실시간평점이상";
				}
			else if(Eventid==12)
				{
				var text1="전달내용 입력하기";
				var text2="성장지표가 좋지 않습니다. 커리큘럼이나 목표설정을 정비해 주시기 바랍니다.";
				var text3="커리큘럼이나 목표설정이 학생에게 내재화되어 있는 상태인지 확인해 주시고 필요하다면 수정해 주시기 바랍니다.";
				var text4="문제해결에 어려움이 있으신 경우라면 상황을 공유해 주시기 바랍니다.";
				var text5="체크리스트 회신 부탁드립니다.";
				var context="실시간성장지표이상";
				}
			swal("전달사항 선택",  "친구에게 전하고 싶은 말을 선택해 주세요",{
				
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
				
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: text5,
			      value: "catch5",className : \'btn btn-primary\'
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
					"eventid":Eventid,
					"feedbackid":\'1\',
					"inputtext":Inputtext,	
					"studentid":Studentid,
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
					"eventid":Eventid,
					"feedbackid":\'2\',
					"inputtext":text2,	
					"studentid":Studentid,
					"context":context,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":Eventid,
					"feedbackid":\'3\',
					"inputtext":text3,	
					"studentid":Studentid,
					"context":context,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":Eventid,
					"feedbackid":\'4\',
					"inputtext":text4,	
					"studentid":Studentid,
					"context":context,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":Eventid,
					"feedbackid":\'5\',
					"inputtext":text5,	
					"studentid":Studentid,
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
 
function sendmessage2(Eventid,Studentid)
			{
			 
			var Teacherid= \''.$teacherid.'\';
			var Managerid= \''.$USER->id.'\';
                                           //alert(Studentid);
			if(Eventid==3)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="출결이상";
				}
			else if(Eventid==4)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="효율이상";
				}
			else if(Eventid==5)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="풀이이상";
				}
			else if(Eventid==6)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="성취이상";
				}
			else if(Eventid==7)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="성취및침착이상";
				}
			else if(Eventid==8)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="분기목표정비";
				}
			else if(Eventid==9)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="장기미접속";
				}
			else if(Eventid==10)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="휴원";
				}
			else if(Eventid==11)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="실시간평점이상";
				}
			else if(Eventid==12)
				{
				var text1="전달내용 입력하기";
				var text2="메세지";
				var text3="메세지";
				var text4="메세지";
				var text5="메세지";
				var context="실시간성장지표이상";
				}
			swal("전달사항 선택",  "친구에게 전하고 싶은 말을 선택해 주세요",{
				
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
				
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: text5,
			      value: "catch5",className : \'btn btn-primary\'
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
					"eventid":Eventid,
					"feedbackid":\'2\',
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
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":Eventid,
					"feedbackid":\'3\',
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
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":Eventid,
					"feedbackid":\'4\',
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
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
					url:"../managers/check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":Eventid,
					"feedbackid":\'5\',
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
 			    break; 
 			   default:
			     swal("취소되었습니다.", {buttons: false,timer: 500});
				  }
				});			 		
			};
	</script>';
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