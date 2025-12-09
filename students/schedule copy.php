<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$nedit=required_param('eid', PARAM_INT); 
$nprev=$nedit+1;
$nnext=$nedit-1;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");
$nweek = $_GET["nweek"]; 
$mode = $_GET["mode"]; 
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");

$typetext1='기본';
$typetext2='특강';
$typetext3='임시';

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
// timezone에 따라 시간표시 자동 변경
date_default_timezone_set('Asia/Seoul'); 

$timezone = $username->timezone;
if (is_numeric($timezone)) {
	$timezone_str= '<table align=center><tr><td>Asia/Seoul 시차 0 시간</td></tr></table>';
} else {
	date_default_timezone_set($timezone);

	// 사용자 타임존 ($timezone 변수에 값이 저장되어 있다고 가정)
	// 예: $timezone = "Asia/Tokyo"; 또는 "Europe/London" 등
	$seoulTZ = new DateTimeZone('Asia/Seoul');
	$userTZ = new DateTimeZone($timezone);

	// 동일 기준 시간 (서울 기준)으로 현재 시간 객체 생성
	$now = new DateTime("now", $seoulTZ);

	// 서울과 사용자 타임존의 오프셋(초 단위) 계산
	$offsetSeoul = $seoulTZ->getOffset($now);
	$offsetUser  = $userTZ->getOffset($now);

	// 시차 계산: 사용자 타임존의 오프셋에서 서울의 오프셋을 빼면 시차(초) 
	$timeDifferenceSeconds = $offsetUser - $offsetSeoul;

	// 시차를 절대값으로 변환하여 시간과 분 계산
	$absDiff = abs($timeDifferenceSeconds);
	$hours   = floor($absDiff / 3600);
	$minutes = floor(($absDiff % 3600) / 60);

	// 시차 문자열 생성 (시차가 0이면 "0시간" 출력)
	if ($timeDifferenceSeconds == 0) {
		$timeDifferenceStr = "0시간";
	} else {
		$timeDifferenceStr = ($hours > 0 ? $hours . '시간 ' : '') . ($minutes > 0 ? $minutes . '분' : '');
	}

// 최종 출력: 사용자 타임존과 서울과의 시차
$timezone_str = '<table align=center><tr><td>'.$timezone . ' 시차 서울시간 + (' . $timeDifferenceStr.')</td></tr></table>';
}


$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$studentid));
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND pinned=1 ORDER BY id DESC LIMIT 1 ");

if($schedule->type==='기본')$typetext1='<b style="color:red;">기본</b>';
elseif($schedule->type==='특강')$typetext2='<b style="color:red;">특강</b>';
elseif($schedule->type==='임시')$typetext3='<b style="color:red;">임시</b>';

if($nday==1){$untiltoday=$schedule->duration1; $todayduration=$schedule->duration1;}
if($nday==2){$untiltoday=$schedule->duration1+$schedule->duration2;$todayduration=$schedule->duration2;}
if($nday==3){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3;$todayduration=$schedule->duration3;}
if($nday==4){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;$todayduration=$schedule->duration4;}
if($nday==5){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;$todayduration=$schedule->duration5;}
if($nday==6){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;$todayduration=$schedule->duration6;}
if($nday==0){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;$todayduration=$schedule->duration7;}

  
$nview = $_GET["nview"]; 
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");

if($todayduration==0)$selected0='selected';elseif($todayduration==1)$selected1='selected';elseif($todayduration==1.5)$selected15='selected';elseif($todayduration==2)$selected2='selected';elseif($todayduration==2.5)$selected25='selected';elseif($todayduration==3)$selected3='selected';elseif($todayduration==3.5)$selected35='selected';elseif($todayduration==4)$selected4='selected';
elseif($todayduration==4.5)$selected45='selected';elseif($todayduration==5)$selected5='selected';elseif($todayduration==5.5)$selected55='selected';elseif($todayduration==6)$selected6='selected';elseif($todayduration==6.5)$selected65='selected';elseif($todayduration==7)$selected7='selected';elseif($todayduration==7.5)$selected75='selected';elseif($todayduration==8)$selected8='selected';

$nfee=0;
$begintime=$timecreated;
$thisyear = date("Y",time());
//<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=6"target="_blank">성찰하기</a> '.$create4.'</td>
echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-body"> ';
 
$nlog1=1;$nlog2=1;
$monthsago=time()-604800*$nweek; //12주
$today=date("Y-m-d",time());
$monthsago6=time()-604800*30;
$showlast.= '<tr style="background-color:#377ffb; color:white;"> <th width=3% ></th> <th width=5%>상태</th><th align=left style="font-size:12pt">유형</th><th align=left style="font-size:12pt">사유</th><th align=left style="font-size:12pt">계획</th><th align=left style="font-size:12pt">변경</th> <th align=left style="font-size:12pt">증감</th><th align=left style="font-size:12pt">합산</th> <th width=20% style="font-size:12pt">메모</th> <th width=5%></th><th width=3%>취소</th></tr>';		

if($nview==1) $attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND timecreated>'$monthsago'  ORDER by id DESC " );
else $attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND (timecreated>'$monthsago' OR (timecreated>'$monthsago6' AND reason LIKE 'addperiod')) AND hide=0 ORDER by id DESC " );	
									
$result = json_decode(json_encode($attendlog), True);
unset($value);										
foreach($result as $value)										
	{	
	$logid=$value['id']; $type=$value['type']; $reason=$value['reason']; $thissubject=$value['subject']; $tamount=$value['tamount'];$tupdate=$value['tupdate']; $doriginal=$value['doriginal']; $dchanged=$value['dchanged'];	
	$text=$value['text'];$complete=$value['complete']; $hide=$value['hide']; $tcreated=$value['timecreated']; 
	$doriginal=date("Y-m/d",$doriginal); $dchanged=date("Y-m/d",$dchanged);
	$dfinish=date("m/d",$value['doriginal']+604800*4-86400); 
	$checked1='';if($complete==1)$checked1='checked';
 	$checked2='';if($hide==1)$checked2='checked';  //onclick="updatecheck(151,'.$studentid.','.$logid.',  this.checked)"
	if(($type=='보강' || $type=='이동수업') && $complete==0)
		{
		$type='<b style="color:red;">'.$type.'</b>';
		}
	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox" '.$checked1.' /><span class="form-check-sign"></span></label></div>';
	if($role!=='student')$checkhide='<input type="checkbox" '.$checked2.' onclick="updatecheck2(201,'.$studentid.','.$logid.',  this.checked)"/>';
	if($value['type']!=='enrol')
		{
		if(($value['type']==='4주보강' || $value['type']==='8주보강' || $value['type']==='12주보강') && $value['complete']!=1)
			{
			$passedhours=round($value['tamount']*($timecreated-$value['doriginal'])/($value['dchanged']-$value['doriginal']),1);
			if($passedhours>0)$passedhours=$value['tamount'];
 			$recentlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason NOT LIKE 'addperiod' ORDER BY id DESC LIMIT 1  " );	
			$showlast0.= '<tr style="background-color:#ffdad9"> <td width=3%> </td> <td width=5%>'.$checkcomplete.'</td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['doriginal']+86400).'" target=_blank">'.$doriginal.'</a></td><td align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['dchanged']+86400).'" target=_blank">'.$dchanged.'</a></td> <td align=left style="font-size:12pt">'.$passedhours.'h/'.$tamount.'h</td><td align=left style="font-size:20pt">'.($recentlog->tupdate+$passedhours).'</td><td align=left style="font-size:12pt"><table>'.$text.'</table></td> <td ></td>   <td >'.$checkhide.'</td></tr>';		 									
 
			if($timecreated-$value['dchanged']>0)
				{
				$DB->execute("UPDATE {abessi_attendance} SET tupdate=tupdate+'$tamount' WHERE type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason NOT LIKE 'addperiod' ORDER BY id DESC LIMIT 1 ");  
				$DB->execute("UPDATE {abessi_attendance} SET  complete=1 WHERE id LIKE '$logid' ORDER BY id DESC LIMIT 1 ");  
				}
			else $DB->execute("UPDATE {abessi_attendance} SET appliedhours='$passedhours'  WHERE id LIKE '$logid' ORDER BY id DESC LIMIT 1 ");  
				 
			}
		elseif($nlog1==1 && $value['reason']!=='addperiod')
			{
			$showlast1.= '<tr style="background-color:#0588ed;color:white" height=50px>  <td width=3%> </td><td width=5%>  </td><td align=left style="font-size:12pt">유형</td><td align=left style="font-size:12pt">사유</td><td align=left style="font-size:12pt"> 시작</td><td align=left style="font-size:12pt">종료</td> <td align=left style="font-size:12pt">시간</td><td align=left style="font-size:20pt">합산 </td><td align=left style="font-size:12pt;" width=40%>메모  (<span onclick="ShowPopup();">출결기록</span>)</td> <td ></td>   <td >취소</td></tr>
			<tr style="background-color:#ffdad9"> <td width=3%> </td> <td width=5%>'.$checkcomplete.'</td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['doriginal']+86400).'" target=_blank">'.$doriginal.'</a></td><td align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['dchanged']+86400).'" target=_blank">'.$dchanged.'</a></td> <td align=left style="font-size:12pt">'.$tamount.'</td><td align=left style="font-size:20pt">'.$tupdate.'</td><td align=left style="font-size:12pt"><table>'.$text.'</table></td> <td ></td>   <td >'.$checkhide.'</td></tr>';		 									
			$nlog1++;
			}
		elseif($value['doriginal']>time() || $value['dchanged'] >time() ) $showattendlog1.= '<tr>  <td width=3%></td> <td width=5%>'.$checkcomplete.'</td><td align=left style="color:red;font-size:12pt">'.$type.'</td><td align=left style="color:red;font-size:12pt">'.$reason.'</td><td align=left style="color:red;font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['doriginal']+86400).'" target=_blank">'.$doriginal.'</a></td><td align=left style="color:red;font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['dchanged']+86400).'" target=_blank">'.$dchanged.'</a></td> <td align=left style="color:red;font-size:12pt">'.$tamount.'</td><td align=left style="color:red;font-size:12pt">'.$tupdate.'</td><td align=left style="color:red;font-size:12pt"><table>'.$text.'</table></td> <td ></td>   <td >'.$checkhide.'</td></tr>';	 									
		else $showattendlog1.= '<tr>  <td width=3%></td> <td width=5%>'.$checkcomplete.'</td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['doriginal']+86400).'" target=_blank">'.$doriginal.'</a></td><td align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=86400&tf='.($value['dchanged']+86400).'" target=_blank">'.$dchanged.'</a></td> <td align=left style="font-size:12pt">'.$tamount.'</td><td align=left style="font-size:12pt">'.$tupdate.'</td><td align=left style="font-size:12pt"><table>'.$text.'</table></td> <td ></td>   <td >'.$checkhide.'</td></tr>';	 									
		
		}
	else // 수강료 생성
		{ 
		
		if($nfee==0)
			{
			$begintime=$value['doriginal']+604800*4+43200;
			$nfee=1;
			}
		
		if($role==='manager')
			{
			$statusstr='<button   type="button"  style = "font-size:16;background-color:green;color:white;border:0;outline:0;" onclick="updateenrol('.$studentid.','.$logid.')" >미납</button>';
			if($value['complete']==1)$statusstr='<button   type="button"  style = "font-size:16;background-color:green;color:white;border:0;outline:0;" onclick="updateenrol('.$studentid.','.$logid.')" >납부완료</button>';
			}
		else
			{
			$statusstr=$statusstr='미납';
			if($value['complete']==1)$statusstr='납부완료';
			}
		$hidestr='';
		if($nview==1 && $value['hide']==1)
			{
			$hidestr='(수정기록)';
			$nviewswtch=0;
			}
		else $nviewswtch=1;
		if($nlog2==1)$showlast2.= '<tr style="background-color:#006cbf;color:white" height=50px> <td width=3%></td><td align=left style="font-size:12pt"  width=5%>유형</td><td align=left style="font-size:12pt" width=12%>수강기간</td><td align=left style="font-size:12pt" width=8%> 수강료 (만원)</td><td align=left style="font-size:12pt" width=8%> 입금액  (만원)</td><td align=left style="font-size:12pt"  width=10%>입금일</td><td align=left style="font-size:12pt"> 메모</td><td align=left style="font-size:12pt"  width=8%>상태</td><td width=7%><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12&nview='.$nviewswtch.'">*기록</a></td></td></td></td></tr>';		 									
		if($complete==1) $showattendlog2c.= '<tr style="background-color:#cfffc7"> <td width=3%></td><td align=left style="font-size:12pt">'.$thissubject.'</td><td align=left style="font-size:12pt">'.$doriginal.' ~ '.$dfinish.'  </td><td align=left style="font-size:12pt"> '.$value['fee'].'  </td><td align=left style="font-size:12pt"> '.$value['deposit'].'  </td><td align=left style="font-size:12pt">'.$dchanged.'</td> <td align=left style="font-size:12pt"><table>'.$text.'</table></td><td >'.$statusstr.'</td>  <td>'.$checkhide.''.$hidestr.'</td></tr>';				
		elseif($value['doriginal']>time()) $showattendlog2a.= '<tr style="background-color:#ebebeb"> <td width=3%></td><td align=left style="font-size:12pt">'.$thissubject.'</td><td align=left style="font-size:12pt">'.$doriginal.' ~ '.$dfinish.'  </td><td align=left style="font-size:12pt"> '.$value['fee'].'  </td><td align=left style="font-size:12pt"> '.$value['deposit'].'  </td><td align=left style="font-size:12pt">'.$dchanged.'</td><td align=left style="font-size:12pt"><table>'.$text.'</table></td><td >'.$statusstr.'</td>  <td >'.$checkhide.''.$hidestr.'</td></tr>';	 									
		else $showattendlog2b.= '<tr style="background-color:#ffdad9"> <td width=3%></td> <td align=left style="font-size:12pt">'.$thissubject.'</td><td align=left style="font-size:12pt"> '.$doriginal.' ~ '.$dfinish.'  </td><td align=left style="font-size:12pt"> '.$value['fee'].'  </td><td align=left style="font-size:12pt"> '.$value['deposit'].'  </td><td align=left style="font-size:12pt">'.$dchanged.'</td><td align=left style="font-size:12pt"><table>'.$text.'</table></td><td >'.$statusstr.'</td>  <td >'.$checkhide.''.$hidestr.'</td></tr>';	 	 			
		$nlog2++;
		}
	if($today===$doriginal)$schedule_alert1.=$type.' | '.$reason.' | '.$doriginal.' | '.$dchanged.' | '.$tamount.'시간 | '.$text;	 
	if($today===$dchanged)$schedule_alert2.=$type.' | '.$reason.' | '.$doriginal.' | '.$dchanged.' | '.$tamount.'시간 | '.$text;
	}
	$sch_id=$schedule->id;
 
	$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
	$edittime=date('m/d',$schedule->timecreated);
	if($schedule->date!=0)$startdate='(임시 시간표 : '.date('Y/m/d',$schedule->date).'까지)';
	$start1=$schedule->start1;
	$start2=$schedule->start2;
	$start3=$schedule->start3;
	$start4=$schedule->start4;
	$start5=$schedule->start5;
	$start6=$schedule->start6;
	$start7=$schedule->start7;

	$start11=$schedule->start11;
	$start12=$schedule->start12;
	$start13=$schedule->start13;
	$start14=$schedule->start14;
	$start15=$schedule->start15;
	$start16=$schedule->start16;
	$start17=$schedule->start17;

	if($start1=='12:00 AM')$start1=NULL;
	if($start2=='12:00 AM')$start2=NULL;
	if($start3=='12:00 AM')$start3=NULL;
	if($start4=='12:00 AM')$start4=NULL;
	if($start5=='12:00 AM')$start5=NULL;
	if($start6=='12:00 AM')$start6=NULL;
	if($start7=='12:00 AM')$start7=NULL; 

	if($start11=='12:00 AM')$start11=NULL;
	if($start12=='12:00 AM')$start12=NULL;
	if($start13=='12:00 AM')$start13=NULL;
	if($start14=='12:00 AM')$start14=NULL;
	if($start15=='12:00 AM')$start15=NULL;
	if($start16=='12:00 AM')$start16=NULL;
	if($start17=='12:00 AM')$start17=NULL; 

	$duration1=$schedule->duration1;
	$duration2=$schedule->duration2;
	$duration3=$schedule->duration3;
	$duration4=$schedule->duration4;
	$duration5=$schedule->duration5;
	$duration6=$schedule->duration6;
	$duration7=$schedule->duration7;

	if($duration1==0)$duration1=NULL;
	if($duration2==0)$duration2=NULL;
	if($duration3==0)$duration3=NULL;
	if($duration4==0)$duration4=NULL;
	if($duration5==0)$duration5=NULL;
	if($duration6==0)$duration6=NULL;
	if($duration7==0)$duration7=NULL;

	$room1=$schedule->room1;
	$room2=$schedule->room2;
	$room3=$schedule->room3;
	$room4=$schedule->room4;
	$room5=$schedule->room5;
	$room6=$schedule->room6;
	$room7=$schedule->room7;

	$memo1=$schedule->memo1;
	$memo2=$schedule->memo2;
	$memo3=$schedule->memo3;
	$memo4=$schedule->memo4;
	$memo5=$schedule->memo5;
	$memo6=$schedule->memo6;
	$memo7=$schedule->memo7;
	$memo8=$schedule->memo8;
	$memo9=$schedule->memo9;
 

$rstyle1='=text-align:center; font-size:12pt; width: 12.5%;';$rstyle2='text-align:center; font-size:12pt; width: 12.5%;';$rstyle3='text-align:center; font-size:12pt; width: 12.5%;';$rstyle4='text-align:center; font-size:12pt; width: 12.5%;';$rstyle5='text-align:center; font-size:12pt; width: 12.5%;';$rstyle6='text-align:center; font-size:12pt; width: 12.5%;'; $rstyle0='text-align:center; font-size:12pt; width: 12.5%;';


$rstyle='rstyle'.$nday;
$$rstyle='width: 12.5%; text-align:center; font-size:14pt; font-weight:bold; background-color:#ff99cc;';

echo ' 

					<div class="row">
						<div class="col-md-12">
							 
							<div class="card">
								<div class="card-header">
									<div class="card-title"><table width=100%><tr><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid='.$nprev.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1587591009001.png width=20></a>&nbsp;&nbsp;|&nbsp;</td>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid='.$nnext.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1587591105001.png width=20></a>&nbsp;&nbsp;&nbsp;</td><td>총 : '.$memo9.'시간&nbsp;&nbsp;</td><td>('.floor($memo9/5).'회)</td><td>&nbsp;&nbsp;&nbsp;수정 : '.$edittime.' </td><td>메모 &nbsp;&nbsp;&nbsp; '.$memo8.'  </td><td>&nbsp;&nbsp;&nbsp;</td><td> '.$startdate.'</td><td>'.$typetext1.'<input type="checkbox" name="checkAccount"  onclick="changemode(101,'.$studentid.','.$sch_id.', this.checked)"/>_'.$typetext2.'<input type="checkbox" name="checkAccount"  onclick="changemode(102,'.$studentid.','.$sch_id.', this.checked)"/>_'.$typetext3.'<input type="checkbox" name="checkAccount" onclick="changemode(103,'.$studentid.','.$sch_id.', this.checked)"/></td>
<td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" width=3%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&nweek=4&eid='.$sch_id.'"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524941001.png" width=20> 시간표 변경</a></td><td align=right style="font-size=12px">수정요청 &nbsp;<input type="checkbox" name="checkAccount" '.$status.'  onclick="changecheckbox(6,'.$studentid.','.$sch_id.', this.checked)"/></td></tr></table></div>
								</div>
								<div class="card-body">
									<table class="table table-head-bg-primary mt-12" style="width=100%;">
										<thead>
											<tr>
												<th scope="col" ></th>
												<th scope="col" style="'.$rstyle1.'">월</th>
												<th scope="col" style="'.$rstyle2.'">화</th>
												<th scope="col" style="'.$rstyle3.'">수</th>
												<th scope="col" style="'.$rstyle4.'">목</th>
												<th scope="col" style="'.$rstyle5.'">금</th>
												<th scope="col" style="'.$rstyle6.'">토</th>
												<th scope="col" style="'.$rstyle0.'">일</th>
											</tr>
										</thead>
										<tbody>';

echo '
<tr><td>시작시간</td><td style="'.$rstyle1.'">'.$start1.'</td><td style="'.$rstyle2.'">'.$start2.'</td><td style="'.$rstyle3.'">'.$start3.'</td><td style="'.$rstyle4.'">'.$start4.'</td><td style="'.$rstyle5.'">'.$start5.'</td><td style="'.$rstyle6.'">'.$start6.'</td><td style="'.$rstyle0.'">'.$start7.'</td></tr>
<tr><td>공부시간</td><td style="'.$rstyle1.'">'.$duration1.'</td><td style="'.$rstyle2.'">'.$duration2.'</td><td style="'.$rstyle3.'">'.$duration3.'</td><td style="'.$rstyle4.'">'.$duration4.'</td><td style="'.$rstyle5.'">'.$duration5.'</td><td style="'.$rstyle6.'">'.$duration6.'</td><td style="'.$rstyle0.'">'.$duration7.'</td></tr>
<tr><td>공부장소</td><td style="'.$rstyle1.'">'.$room1.'</td><td style="'.$rstyle2.'">'.$room2.'</td><td style="'.$rstyle3.'">'.$room3.'</td><td style="'.$rstyle4.'">'.$room4.'</td><td  style="'.$rstyle5.'">'.$room5.'</td><td style="'.$rstyle6.'">'.$room6.'</td><td style="'.$rstyle0.'">'.$room7.'</td></tr>	
<tr><td>참고사항</td><td style="'.$rstyle1.'">'.$memo1.'</td><td style="'.$rstyle2.'">'.$memo2.'</td><td style="'.$rstyle3.'">'.$memo3.'</td><td style="'.$rstyle4.'">'.$memo4.'</td><td  style="'.$rstyle5.'">'.$memo5.'</td><td style="'.$rstyle6.'">'.$memo6.'</td><td style="'.$rstyle0.'">'.$memo7.'</td></tr>	
<tr><td>상담시간</td><td style="'.$rstyle1.'">'.$start11.'</td><td style="'.$rstyle2.'">'.$start12.'</td><td style="'.$rstyle3.'">'.$start13.'</td><td style="'.$rstyle4.'">'.$start14.'</td><td  style="'.$rstyle5.'">'.$start15.'</td><td style="'.$rstyle6.'">'.$start16.'</td><td style="'.$rstyle0.'">'.$start17.'</td></tr>			
</tbody>	</table>
<table width=100%><tr><td><b style="color:blue;font-weight:bold;">알림 </b>'.$schedule_alert1.' </td><td> <b style="color:blue;font-weight:bold;">보강</b> '.$schedule_alert2.' </td><td>'.$timezone_str.'</td><td width=25% align=right>현재 : '.round($Ttime->totaltime,1).' 시간 /<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$studentid.' " target="_blank" > '.$untiltoday.'시간</a> (총 '.$weektotal.'시간 / '.$memo9.'시간) </td></tr></table><hr>';
$gptprep='주간 총 공부시간이 '.$weektotal.'입니다. 그 중 오늘까지 계획된 공부시간은 '.$untiltoday.'이고 그 중 '.$Ttime->totaltime.'시간을 공부한 것으로 나타납니다.';
$DB->execute("UPDATE {abessi_schedule} SET weektotal='$weektotal' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

if($role!=='student')
	{
	if($mode==='new') // 수강료 변경 시 (시수 또는 과정)
		{
 
		$reasons1='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic2" name="basic2" class="form-control"  ><h3><option value="시수변경">시수변경</option><option value="과정변경">과정변경</option></h3></select> </div>';
		$selecttime1='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic3" name="basic3" class="form-control"  placeholder="시수" ><h3><option value="1.5">1.5</option><option value="3">3</option><option value="5">5</option><option value="10">10</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option></h3></select> </div>';
 
		$recordattendance= '<table class="table" align=center><thead><tr style="background-color:#32a852;color:white;"><th scope="col" style="width: 2%; font-size:12pt" ></th><th  style="font-size:14pt;color:white;" >정보입력 </th><th scope="col" style="font-size:30pt" ></th><th scope="col" style="font-size:30pt" >'.$reasons1.'</th><th>적용날짜</th><th  style="width:10%; font-size:18pt"><input type="text" class="form-control" id="datepicker2" name="datepicker2"  value= "'.$today.'" placeholder="'.$today.'"></th><th  style="width:0%; font-size:0pt"></th><th scope="col" style="font-size:30pt" >'.$selecttime1.'</th><th scope="col" style="width: 20%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="메모"></th><th scope="col" >
		<span  onclick="attendance(9,'.$studentid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker2\').val(),$(\'#datepicker3\').val(),$(\'#basic3\').val(),$(\'#squareInput\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th><th><a style="color:white;"  href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4">출결관리</a></th>
		</tr></thead></table> ';
		}
	else
		{
		$types2='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic1" class="form-control"  ><h3><option value="시간이동">시간이동</option><option value="날짜이동">날짜이동</option><option value="최종휴강">휴강</option></h3></select> </div>';
		$reasons2='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic2" name="basic2" class="form-control"  ><h3><option value="개인일정">개인일정</option><option value="관심필요">관심필요</option><option value="학교일정">학교일정</option><option value="다른과목">다른과목</option><option value="미확인">미확인</option></h3></select> </div>';
		//$selecttime2='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic3" name="basic3" class="form-control"  placeholder="공부양" ><h3><option '.$selected0.' value="0">0</option><option value="0.1">6분</option><option value="0.2">12분</option><option value="0.3">18분</option><option value="0.4">24분</option><option value="0.5">0.5</option><option '.$selected1.' value="1">1시간</option><option '.$selected15.'  value="1.5">1.5시간</option><option '.$selected2.'  value="2">2시간</option><option '.$selected25.'  value="2.5">2.5시간</option><option '.$selected3.'  value="3">3시간</option><option '.$selected35.'  value="3.5">3.5시간</option><option '.$selected4.'  value="4">4시간</option><option '.$selected45.'  value="4.5">4.5시간</option><option '.$selected5.'  value="5">5시간</option><option '.$selected55.'  value="5.5">5.5시간</option><option '.$selected6.'  value="6">6시간</option><option '.$selected65.'  value="6.5">6.5시간</option><option '.$selected7.'  value="7">7시간</option><option '.$selected75.'  value="7.5">7.5시간</option><option '.$selected8.'  value="8">8시간</option><option value="8.5">8.5시간</option><option value="9">9시간</option><option value="9.5">9.5시간</option><option value="10">10시간</option><option value="15">15시간</option><option value="20">20시간</option><option value="25">25시간</option><option value="30">30시간</option></h3></select> </div>';

		$recordattendance= '<table class="table" align=center><thead><tr style="background-color:#32a852;color:white;"><th scope="col" style="width: 2%; font-size:12pt;" ></th><th  style="font-size:14pt;color:white;" >출결입력 </th><th scope="col" style="font-size:30pt" >'.$types2.'</th><th scope="col" style="font-size:30pt" >'.$reasons2.'</th><th align=right>시작</th><th  style="width:10%; font-size:18pt"><input type="text" class="form-control" id="datepicker2" name="datepicker2"  value= "'.$today.'" placeholder="'.$today.'"></th><th scope="col" style="font-size:30pt" ></th><th scope="col" style="width: 20%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="메모"></th><th scope="col" >
		<span  onclick="attendance(9,'.$studentid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker2\').val(),$(\'#squareInput\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th><th><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4&mode=new">정보입력</a> </th>
		</tr></thead></table> ';
		}
	}
 
echo '<table class="table table-head-bg-primary mt-12" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$recordattendance.'<table width=100%>'.$showlast1.$showlast0.$showattendlog1.'</table> </div></div></div></div></div></div></div>';

if($role!=='student') // 수납
	{
	$lastlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND type LIKE 'enrol' AND hide=0  ORDER by id DESC LIMIT 1 " );	
	
	//selected
	if($lastlog->subject==='초등수학')$selected1='selected'; elseif($lastlog->subject==='중등수학')$selected2='selected'; elseif($lastlog->subject==='중등수학3')$selected3='selected'; elseif($lastlog->subject==='고등수학')$selected4='selected'; elseif($lastlog->subject==='고3수학')$selected5='selected'; elseif($lastlog->subject==='수능수학')$selected6='selected'; elseif($lastlog->subject==='중등과학')$selected7='selected'; elseif($lastlog->subject==='고등과학')$selected8='selected'; elseif($lastlog->subject==='중등영어')$selected9='selected'; elseif($lastlog->subject==='고등영어')$selected10='selected'; elseif($lastlog->subject==='중등언어')$selected11='selected'; elseif($lastlog->subject==='고등언어')$selected12='selected';
	
	$lastdchanged=$lastlog->dchanged;

	$subject='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic4" name="basic4" class="form-control"  ><h3><option value="초등수학" '.$selected1.'>초등수학</option><option value="중등수학" '.$selected2.'>중등수학</option><option value="중등수학3" '.$selected3.'>중등수학3</option><option value="고등수학" '.$selected4.'>고등수학</option><option value="고3수학" '.$selected5.'>고3수학</option><option value="수능수학" '.$selected6.'>수능수학</option><option value="중등과학" '.$selected7.'>중등과학</option><option value="고등과학" '.$selected8.'>고등과학</option><option value="중등영어" '.$selected9.'>중등영어</option><option value="고등영어" '.$selected10.'>고등영어</option><option value="중등언어" '.$selected11.'>중등언어</option><option value="고등언어" '.$selected12.'>고등언어</option></h3></select> </div>';

	$nextfee=$lastlog->deposit;
	$beginday=date("Y-m-d",$lastlog->dchanged+86400);
	$settlementday=date("Y-m-d",$lastlog->dchanged+604800*4);
	if($lastlog->dchanged==NULL)
		{
		$beginday=$today;
		$settlementday=date("Y-m-d",$timecreated+604800*4-86400);
		}
	

	echo '<div class="col-md-12"><div class="card"><div class="card"><div class="card-header"><table class="table" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;width:98%;" align=center><tr style="background-color:#32a852;color:white;"><th scope="col" ></th><th align=center scope="col" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;width:3%; font-size:16pt;color:white;" >수강생성 </th><th  align=center scope="col" style="font-size:16pt" ></th><th  align=center scope="col" style="font-size:16pt">수강과목</th><th align=center  style="font-size:16pt" width=12%>수강기간</th><th align=center scope="col" style="font-size:16pt" >수강료</th>	<th align=center scope="col" style="font-size:16pt" >메모</th><th align=center scope="col">	<th align=center style="width:12%; font-size:16pt">정산 기준일</th><th></th><th></th></tr>

	<tr style="background-color:#32a852;color:white;"><th scope="col" style="width: 3%; font-size:12pt" ></th>
	<th scope="col" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;width:3%; font-size:14pt;color:white;" > </th><th scope="col" style="font-size:12pt" ></th><th scope="col" style="font-size:30pt">'.$subject.'</th><th  width=12%><input type="text" class="form-control" id="datepicker5" name="datepicker5"  value= "'.$beginday.'" placeholder="'.$beginday.'"></th><th scope="col" style="font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput3" name="squareInput3" value= "'.$nextfee.'"></th>
	<th scope="col" style="font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput4" name="squareInput4"  placeholder="메모"></th><th scope="col" >
	<th  style="width:12%; font-size:18pt"><input type="text" class="form-control" id="datepicker4" name="datepicker4"  value= "'.$settlementday.'" placeholder="'.$settlementday.'"></th>
	<th><span  onclick="enrollment(91,'.$studentid.',\'enrol\',$(\'#basic4\').val(),$(\'#squareInput3\').val(),$(\'#squareInput4\').val(),$(\'#datepicker5\').val(),$(\'#datepicker4\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th><th> </th></tr></table> 

	 <table align=center style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;width:98%;">'.$showlast2.$showattendlog2a.$showattendlog2b.$showattendlog2c.'</table><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></div></div></div></div>';
	}
include("quicksidebar.php");
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


	<script>
function ShowPopup()
		{
 
	//창 크기 지정
	var width =window.screen.width*0.8;
	var height = window.screen.height*1;
	
	//pc화면기준 가운데 정렬
	var left=(window.screen.width);
	var top =(window.screen.height)*0.3;
	
    	//윈도우 속성 지정
	var windowStatus = "width="+width+", height="+height+",left="+left+", top="+top+", scrollbars=yes, status=yes, resizable=yes";
	
    	//연결하고싶은url
    	const url ="https://mathking.kr/moodle/local/augmented_teacher/students/dailylog.php?id='.$studentid.'&nweek=12" ;

	//등록된 url 및 window 속성 기준으로 팝업창을 연다.
	window.open(url, "hello popup", windowStatus);
 
 

		}

	function attendance(Eventid,Userid,Type,Reason,Doriginal,Inputtext){   
	swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "type":Type,
			  "reason":Reason,
			  "doriginal":Doriginal,	
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
		 
   		setTimeout(function() {location.reload(); },100);
		}

 	function enrollment(Eventid,Userid,Type,Subject,Fee,Inputtext,Begintime,Selecttime){   		 
	swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "type":Type,
			  "subject":Subject,
			  "fee":Fee,
		   	  "inputtext":Inputtext,
		   	  "begintime":Begintime,	
			  "selecttime":Selecttime,		 
		               },
		            success:function(data){
		
				             }
		        })
		 
   		setTimeout(function() {location.reload(); },100);
		}


function updateenrol(Userid,Logid){   	 
			var text1="납부";
			var text2="미납";
			var text3="전월납부";
			var text4="익월납부";	
				 
			swal("수강등록 상태입력",  "현재 상태에 맞게 수강등록 상태를 선택해 주세요",{
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
				swal({
					title: \'금액입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "수강료와 동일한 경우 입력없이 클릭해주세요",
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
				            url:"database.php",
					type: "POST",
				            dataType:"json",
 					data : {
					 "eventid":\'92\',
					  "userid":Userid,
					  "logid":Logid,	 
					  "inputtext":text1,	
					  "deposit":Inputtext,	
					},
					success:function(data){
					
					 }
					 })
				location.reload(); 
				}
				);
			   
 			    break;

 			   case "catch2":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
				            url:"database.php",
					type: "POST",
				            dataType:"json",
 					data : {
					 "eventid":\'92\',
					  "userid":Userid,
					  "logid":Logid,	 
					  "inputtext":text2,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
				            url:"database.php",
					type: "POST",
				            dataType:"json",
 					data : {
					 "eventid":\'92\',
					  "userid":Userid,
					  "logid":Logid,	 
					  "inputtext":text3,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", {buttons: false,timer: 2000});
					$.ajax({
				            url:"database.php",
					type: "POST",
				            dataType:"json",
 					data : {
					 "eventid":\'92\',
					  "userid":Userid,
					  "logid":Logid,	 
					  "inputtext":text4,	
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
   		 
		}
	function updatecheck(Eventid,Userid,Logid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("완료상태가 변경되었습니다.", {buttons: false,timer: 1000});
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "logid":Logid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		}

		function updatecheck2(Eventid,Userid,Logid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("체크 상태에서 새로고침하면 목록에서 사라집니다", {buttons: false,timer: 1000}); 
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "logid":Logid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		} 

		function inputpersonal(Eventid,Userid,Inputtext,Deadline){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "inputtext":Inputtext,
			  "eventtype":\'8\',
			  "deadline":Deadline,		 
		               },
		            success:function(data){
			            }
		        })

		}
		function hideschedule(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		}
 


		function inputmission(userid,inputtext,deadline){
		   //tslee

		      
		        $.ajax({
		            url:"./databasewrite.php",
		            dataType:"json",
		            success:function(data){
			            }
		        })
		setTimeout(function(){
		location.reload();
		},1000); // 3000밀리초 = 3초
		} 
		function changecheckbox(Eventid,Userid, Schid, Checkvalue){
		    var checkimsi = 0;
		   swal("전달하였습니다.", {buttons: false,timer: 1000}); 
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
		                  "schid":Schid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		setTimeout(function(){location.reload();},1000); 
		}
		function changemode(Eventid,Userid, Schid, Checkvalue){
		    var checkimsi = 0;
		 swal("시간표가 이동됩니다.", {buttons: false,timer: 1000}); 
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
		                  "schid":Schid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		setTimeout(function(){location.reload();},1000); 
		}
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		 
		$("#datepicker2").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker3").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker4").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker5").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic").select2({
			theme: "bootstrap"
		});

		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});

		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );
	</script>


</body>';
$pagetype='popup';
$initialtalk='수학 실력향은 안정적인 스케줄 관리와 꾸준한 연습이 시작입니다. 성찰을 위한 도움이 필요하시면 성찰이라고 입력해 보세요.';
$finetuning=$username->lastname.'(학생이름)의 공부시간관리 : '.$gptprep.' . 을 간단히 요약하고 공부양과 스케줄 관리에 대한 성찰질문 3개 만들어줘. (답변만 표시)';
include("../LLM/gptsnippet.php");
?>
