<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$nedit=required_param('eid', PARAM_INT); 
$nprev=$nedit+1;
$nnext=$nedit-1;
include("navbar.php");
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");

$nweek = $_GET["nweek"]; 
$mode = $_GET["mode"]; 
$timecreated=time();
 
$moneyrate=1.0;

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);

$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$teacherid));
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$teacherid' ");  
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$teacherid' ORDER BY id DESC LIMIT 1 ");
if($nday==1){$untiltoday=$schedule->duration1; $todayduration=$schedule->duration1;}
if($nday==2){$untiltoday=$schedule->duration1+$schedule->duration2;$todayduration=$schedule->duration2;}
if($nday==3){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3;$todayduration=$schedule->duration3;}
if($nday==4){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;$todayduration=$schedule->duration4;}
if($nday==5){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;$todayduration=$schedule->duration5;}
if($nday==6){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;$todayduration=$schedule->duration6;}
if($nday==0){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;$todayduration=$schedule->duration7;}

 
$nview = $_GET["nview"]; 
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teacherschedule','$timecreated')");

if($todayduration==0)$selected0='selected';elseif($todayduration==1)$selected1='selected';elseif($todayduration==1.5)$selected15='selected';elseif($todayduration==2)$selected2='selected';elseif($todayduration==2.5)$selected25='selected';elseif($todayduration==3)$selected3='selected';elseif($todayduration==3.5)$selected35='selected';elseif($todayduration==4)$selected4='selected';
elseif($todayduration==4.5)$selected45='selected';elseif($todayduration==5)$selected5='selected';elseif($todayduration==5.5)$selected55='selected';elseif($todayduration==6)$selected6='selected';elseif($todayduration==6.5)$selected65='selected';elseif($todayduration==7)$selected7='selected';elseif($todayduration==7.5)$selected75='selected';elseif($todayduration==8)$selected8='selected';

$types1='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic1" class="form-control"  ><h3><option value="근무시간 입력">근무시간 입력</option></h3></select> </div>';
$reasons1='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic2" name="basic2" class="form-control"  ><h3><option value="정상근무">정상근무</option><option value="이동근무">이동근무</option><option value="보조활동">보조활동</option></h3></select> </div>';
$selecttime1='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic3" name="basic3" class="form-control"  placeholder="공부양" ><h3><option '.$selected0.' value="0">0</option><option value="0.5">0.5</option><option '.$selected1.' value="1">1</option><option '.$selected15.'  value="1.5">1.5</option><option '.$selected2.'  value="2">2</option><option '.$selected25.'  value="2.5">2.5</option><option '.$selected3.'  value="3">3</option><option '.$selected35.'  value="3.5">3.5</option><option '.$selected4.'  value="4">4</option><option '.$selected45.'  value="4.5">4.5</option><option '.$selected5.'  value="5">5</option><option '.$selected55.'  value="5.5">5.5</option><option '.$selected6.'  value="6">6</option><option '.$selected65.'  value="6.5">6.5</option><option '.$selected7.'  value="7">7</option><option '.$selected75.'  value="7.5">7.5</option><option '.$selected8.'  value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></h3></select> </div>';

$thisyear = date("Y",time());
//<td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/metacognition_synapse.php?contentstype=6"target="_blank">성찰하기</a> '.$create4.'</td>
echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-body"> ';
 
$nlog=1;
$monthsago=time()-604800*$nweek; //12주
$today=date("Y-m-d",time()); 
$showlast.= '<tr style="background-color:#377ffb; color:white;"> <th width=3% ></th> <th width=5%>상태</th><th align=left style="font-size:12pt">유형</th><th align=left style="font-size:12pt">사유</th><th align=left style="font-size:12pt">계획</th><th align=left style="font-size:12pt">변경</th> <th align=left style="font-size:12pt">증감</th><th align=left style="font-size:12pt">합산</th> <th width=20% style="font-size:12pt">메모</th> <th width=5%></th><th width=3%>취소</th></tr>';		
if($nview==1) $attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$teacherid' AND timecreated>'$monthsago'  ORDER by id DESC " );
	else $attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$teacherid' AND timecreated>'$monthsago' AND hide=0 ORDER by id DESC " );										
$result = json_decode(json_encode($attendlog), True);
unset($value);										
foreach($result as $value)										
	{	
	$logid=$value['id']; $type=$value['type']; $reason=$value['reason']; $tamount=$value['tamount'];$tupdate=$value['tupdate']; $doriginal=$value['doriginal']; $dchanged=$value['dchanged'];	
	$text=$value['text'];$complete=$value['complete']; $hide=$value['hide']; $tcreated=$value['tcreated']; 
	$doriginal=date("Y-m-d",$doriginal); $dchanged=date("Y-m-d",$dchanged);
	
	$checked1='';if($complete==1)$checked1='checked';
 	$checked2='';if($hide==1)$checked2='checked';  //onclick="updatecheck(151,'.$teacherid.','.$logid.',  this.checked)"
	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox" '.$checked1.' /><span class="form-check-sign"></span></label></div>';
	$checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox" '.$checked2.' onclick="updatecheck2(201,'.$teacherid.','.$logid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	if($nlog==1)
		{
		$showlast.= '<tr style="background-color:#ccffff;"> <td width=3%></td> <td width=5%>'.$checkcomplete.'</td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt">'.$doriginal.'</td><td align=left style="font-size:12pt">'.$dchanged.'</td> <td align=left style="font-size:12pt">'.$tamount.'</td><td align=left style="font-size:20pt">'.$tupdate.'</td><td align=left style="font-size:12pt">'.$text.'</td> <td ></td>   <td >'.$checkhide.'</td></tr>';		 									
		$totaltime=$tupdate;
		}
	else $showattendlog.= '<tr>  <td width=3%></td> <td width=5%>'.$checkcomplete.'</td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt">'.$doriginal.'</td><td align=left style="font-size:12pt">'.$dchanged.'</td> <td align=left style="font-size:12pt">'.$tamount.'</td><td align=left style="font-size:12pt">'.$tupdate.'</td><td align=left style="font-size:12pt">'.$text.'</td> <td ></td>   <td >'.$checkhide.'</td></tr>';	 									
	$nlog++;
	if($today===$doriginal)$schedule_alert1.=$type.' | '.$reason.' | '.$doriginal.' | '.$dchanged.' | '.$tamount.'시간 | '.$text;	 
	if($today===$dchanged)$schedule_alert2.=$type.' | '.$reason.' | '.$doriginal.' | '.$dchanged.' | '.$tamount.'시간 | '.$text;
	}
 
$timeplan = $DB->get_records_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$teacherid' AND  (timecreated < date OR date=0)  ORDER BY timecreated DESC LIMIT 20 ");
$result = json_decode(json_encode($timeplan), True);
$index=0;
foreach($result as $value)
{
$index++;
if($index==$nedit)
	{
	$sch_id=$value['id'];
  	$status='';
	if($value['editnew']==1)$status='checked';

	$weektotal=$value['duration1']+$value['duration2']+$value['duration3']+$value['duration4']+$value['duration5']+$value['duration6']+$value['duration7'];
	$edittime=date('m/d',$value['timecreated']);
	if($value['date']!=0)$startdate='(임시 시간표 : '.date('Y/m/d',$value['date']).'까지)';
	$start1=$value['start1'];
	$start2=$value['start2'];
	$start3=$value['start3'];
	$start4=$value['start4'];
	$start5=$value['start5'];
	$start6=$value['start6'];
	$start7=$value['start7'];

	$start11=$value['start11'];
	$start12=$value['start12'];
	$start13=$value['start13'];
	$start14=$value['start14'];
	$start15=$value['start15'];
	$start16=$value['start16'];
	$start17=$value['start17'];

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

	$duration1=$value['duration1'];
	$duration2=$value['duration2'];
	$duration3=$value['duration3'];
	$duration4=$value['duration4'];
	$duration5=$value['duration5'];
	$duration6=$value['duration6'];
	$duration7=$value['duration7'];

	if($duration1==0)$duration1=NULL;
	if($duration2==0)$duration2=NULL;
	if($duration3==0)$duration3=NULL;
	if($duration4==0)$duration4=NULL;
	if($duration5==0)$duration5=NULL;
	if($duration6==0)$duration6=NULL;
	if($duration7==0)$duration7=NULL;

	$room1=$value['room1'];
	$room2=$value['room2'];
	$room3=$value['room3'];
	$room4=$value['room4'];
	$room5=$value['room5'];
	$room6=$value['room6'];
	$room7=$value['room7'];

	$memo1=$value['memo1'];
	$memo2=$value['memo2'];
	$memo3=$value['memo3'];
	$memo4=$value['memo4'];
	$memo5=$value['memo5'];
	$memo6=$value['memo6'];
	$memo7=$value['memo7'];
	$memo8=$value['memo8'];
	$memo9=$value['memo9'];
	}
}



$rstyle1='=text-align:center; font-size:12pt; width: 12.5%;';$rstyle2='text-align:center; font-size:12pt; width: 12.5%;';$rstyle3='text-align:center; font-size:12pt; width: 12.5%;';$rstyle4='text-align:center; font-size:12pt; width: 12.5%;';$rstyle5='text-align:center; font-size:12pt; width: 12.5%;';$rstyle6='text-align:center; font-size:12pt; width: 12.5%;'; $rstyle0='text-align:center; font-size:12pt; width: 12.5%;';


$rstyle='rstyle'.$nday;
$$rstyle='width: 12.5%; text-align:center; font-size:14pt; font-weight:bold; background-color:#ff99cc;';

echo ' 

		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">
 						 
							<div class="card">
								<div class="card-header">
									<div class="card-title"><table width=100%><tr><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/schedule.php?id='.$teacherid.'&eid='.$nprev.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1587591009001.png width=20></a>&nbsp;&nbsp;|&nbsp;</td>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/schedule.php?id='.$teacherid.'&eid='.$nnext.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1587591105001.png width=20></a>&nbsp;&nbsp;&nbsp;</td><td>총 : '.$memo9.'시간&nbsp;&nbsp;</td><td>('.floor($memo9/5).'회)</td><td>&nbsp;&nbsp;&nbsp;수정 : '.$edittime.' </td><td>메모 &nbsp;&nbsp;&nbsp; '.$memo8.'  </td><td>&nbsp;&nbsp;&nbsp;</td><td> '.$startdate.'</td><td width=3%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/editschedule.php?id='.$teacherid.'&nweek=4&eid='.$sch_id.'"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524941001.png" width=20></a></td><td width=5%></td><td>시급 : '.$moneyrate.' 만원 </td><td>정산금액 : '.($totaltime*$moneyrate).' 만원 </td></tr></table></div>
								</div>
								<div class="card-body">
									<table class="table table-head-bg-primary mt-12" style=";">
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
<tr><td>근무시간</td><td style="'.$rstyle1.'">'.$duration1.'</td><td style="'.$rstyle2.'">'.$duration2.'</td><td style="'.$rstyle3.'">'.$duration3.'</td><td style="'.$rstyle4.'">'.$duration4.'</td><td style="'.$rstyle5.'">'.$duration5.'</td><td style="'.$rstyle6.'">'.$duration6.'</td><td style="'.$rstyle0.'">'.$duration7.'</td></tr>
<tr><td>근무장소</td><td style="'.$rstyle1.'">'.$room1.'</td><td style="'.$rstyle2.'">'.$room2.'</td><td style="'.$rstyle3.'">'.$room3.'</td><td style="'.$rstyle4.'">'.$room4.'</td><td  style="'.$rstyle5.'">'.$room5.'</td><td style="'.$rstyle6.'">'.$room6.'</td><td style="'.$rstyle0.'">'.$room7.'</td></tr>	
<tr><td>참고사항</td><td style="'.$rstyle1.'">'.$memo1.'</td><td style="'.$rstyle2.'">'.$memo2.'</td><td style="'.$rstyle3.'">'.$memo3.'</td><td style="'.$rstyle4.'">'.$memo4.'</td><td  style="'.$rstyle5.'">'.$memo5.'</td><td style="'.$rstyle6.'">'.$memo6.'</td><td style="'.$rstyle0.'">'.$memo7.'</td></tr>	
<tr><td>보충근무</td><td style="'.$rstyle1.'">'.$start11.'</td><td style="'.$rstyle2.'">'.$start12.'</td><td style="'.$rstyle3.'">'.$start13.'</td><td style="'.$rstyle4.'">'.$start14.'</td><td  style="'.$rstyle5.'">'.$start15.'</td><td style="'.$rstyle6.'">'.$start16.'</td><td style="'.$rstyle0.'">'.$start17.'</td></tr>			
</tbody>	</table><hr>';
 
$DB->execute("UPDATE {abessi_schedule} SET weektotal='$weektotal' WHERE userid='$teacherid' ORDER BY id DESC LIMIT 1 ");  
 
echo '<table  class="table table-head-bg-primary mt-12" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$showlast.$showattendlog.'</table>';

echo '<table class="table" align=center><thead><tr><th scope="col" style="width: 2%; font-size:12pt" ></th><th scope="col" style="font-size:30pt" >'.$types1.'</th><th scope="col" style="font-size:30pt" >'.$reasons1.'</th><th>근무일</th><th  style="width:10%; font-size:18pt"><input type="text" class="form-control" id="datepicker2" name="datepicker2"  value= "'.$today.'" placeholder="'.$today.'"></th><th  style="width:0%; font-size:0pt"><input type="text" class="form-control" id="datepicker3" name="datepicker3"  placeholder=""></th><th scope="col" style="font-size:30pt" >'.$selecttime1.'</th><th scope="col" style="width: 20%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="메모"></th><th scope="col" >
<span  onclick="attendance(9,'.$teacherid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker2\').val(),$(\'#datepicker3\').val(),$(\'#basic3\').val(),$(\'#squareInput\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th><th> </th>
</tr></thead></table> </div></div></div></div></div></div></div>';

 
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

		function attendance(Eventid,Userid,Type,Reason,Doriginal,Dchanged,Selecttime,Inputtext){   
		swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"../students/database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "type":Type,
			  "reason":Reason,
			  "doriginal":Doriginal,
			  "dchanged":Dchanged,
			  "selecttime":Selecttime,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
		 
   		setTimeout(function() {location.reload(); },100);
		}
 
		function updatecheck(Eventid,Userid,Logid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("완료상태가 변경되었습니다.", {buttons: false,timer: 1000});
		   $.ajax({
		        url: "../students/check.php",
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
		 alert("체크 상태에서 새로고침하면 목록에서 사라집니다");
		   $.ajax({
		        url: "../students/check.php",
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
		            url:"../students/database.php",
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
		        url: "../students/check.php",
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
		    alert(Schid);
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "../students/check.php",
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

?>