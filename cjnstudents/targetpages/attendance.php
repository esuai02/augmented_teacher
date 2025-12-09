<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("p_navbar.php");
$nview = $_GET["nview"]; 
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");


$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);

$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
if($nday==1){$untiltoday=$schedule->duration1; $todayduration=$schedule->duration1;}
if($nday==2){$untiltoday=$schedule->duration1+$schedule->duration2;$todayduration=$schedule->duration2;}
if($nday==3){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3;$todayduration=$schedule->duration3;}
if($nday==4){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;$todayduration=$schedule->duration4;}
if($nday==5){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;$todayduration=$schedule->duration5;}
if($nday==6){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;$todayduration=$schedule->duration6;}
if($nday==0){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;$todayduration=$schedule->duration7;}

if($todayduration==0)$selected0='selected';elseif($todayduration==1)$selected1='selected';elseif($todayduration==1.5)$selected15='selected';elseif($todayduration==2)$selected2='selected';elseif($todayduration==2.5)$selected25='selected';elseif($todayduration==3)$selected3='selected';elseif($todayduration==3.5)$selected35='selected';elseif($todayduration==4)$selected4='selected';
elseif($todayduration==4.5)$selected45='selected';elseif($todayduration==5)$selected5='selected';elseif($todayduration==5.5)$selected55='selected';elseif($todayduration==6)$selected6='selected';elseif($todayduration==6.5)$selected65='selected';elseif($todayduration==7)$selected7='selected';elseif($todayduration==7.5)$selected75='selected';elseif($todayduration==8)$selected8='selected';


$types='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic1" class="form-control"  ><h3><option value="날짜이동">날짜이동</option><option value="시간이동">시간이동</option><option value="온라인수업">온라인수업</option><option value="보강">보강</option><option value="휴강">휴강</option><option value="최종휴강">최종휴강</option><option value="추가수업">추가수업</option></h3></select> </div>';
$reasons='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic2" name="basic2" class="form-control"  ><h3><option value="개인일정">개인일정</option><option value="다른과목">다른과목</option><option value="상담검토">상담검토</option></h3></select> </div>';
$selecttime='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic3" name="basic3" class="form-control"  placeholder="공부양" ><h3><option '.$selected0.' value="0">0</option><option value="0.5">0.5</option><option '.$selected1.' value="1">1</option><option '.$selected15.'  value="1.5">1.5</option><option '.$selected2.'  value="2">2</option><option '.$selected25.'  value="2.5">2.5</option><option '.$selected3.'  value="3">3</option><option '.$selected35.'  value="3.5">3.5</option><option '.$selected4.'  value="4">4</option><option '.$selected45.'  value="4.5">4.5</option><option '.$selected5.'  value="5">5</option><option '.$selected55.'  value="5.5">5.5</option><option '.$selected6.'  value="6">6</option><option '.$selected65.'  value="6.5">6.5</option><option '.$selected7.'  value="7">7</option><option '.$selected75.'  value="7.5">7.5</option><option '.$selected8.'  value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option></h3></select> </div>';

 
$thisyear = date("Y",time());
//<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=6"target="_blank">성찰하기</a> '.$create4.'</td>
echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-header"><div class="card-title"></div></div><div class="card-body"> ';
 
$nlog=1;
$monthsago3=time()-7257600; //12주
	
if($nview==1) $attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND timecreated>'$monthsago3'  ORDER by id DESC " );
else $attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND timecreated>'$monthsago3' AND hide=0 ORDER by id DESC " );										
$result = json_decode(json_encode($attendlog), True);
unset($value);										
foreach($result as $value)										
	{	
	$logid=$value['id']; $type=$value['type']; $reason=$value['reason']; $tamount=$value['tamount']; $tupdate=$value['tupdate']; $doriginal=$value['doriginal']; $dchanged=$value['dchanged'];	
	$complete=$value['complete']; $hide=$value['hide']; $tcreated=$value['tcreated']; 
	$doriginal=date("m-d",$doriginal); $dchanged=date("m-d",$dchanged);
	$checked1='';if($complete==1)$checked1='Checked';
 	$checked2='';if($hide==1)$checked1='Checked';

	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox"  "'.$checked1.'" onclick="updatecheck(151,'.$studentid.','.$logid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox" "'.$checked2.'" onclick="updatecheck2(201,'.$studentid.','.$logid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	if($nlog==1)$showlast.= '<tr style="color:red;"> <td width=3%></td> <td width=5%></td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt">'.$doriginal.' | </td><td align=left style="font-size:12pt">'.$dchanged.' | </td> <td align=left style="font-size:12pt">'.$tamount.' | </td><td align=left style="font-size:20pt">'.$tupdate.'</td><td align=left style="font-size:12pt">'.$text.'</td> <td ></td>   <td ></td></tr>';		 									
	else $showattendlog.= '<tr>  <td width=3%></td> <td width=5%>'.$checkcomplete.'</td><td align=left style="font-size:12pt">'.$type.'</td><td align=left style="font-size:12pt">'.$reason.'</td><td align=left style="font-size:12pt">'.$doriginal.' | </td><td align=left style="font-size:12pt">'.$dchanged.' | </td> <td align=left style="font-size:12pt">'.$tamount.' | </td><td align=left style="font-size:12pt">'.$tupdate.'</td><td align=left style="font-size:12pt">'.$text.'</td> <td ></td>   <td >'.$checkhide.'</td></tr>';	 									
	$nlog++;
	}

$today=date("Y-m-d",time()); 
echo '<table width=100% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></table>
											
	<table class="table" align=center><thead>    <tr>  <th scope="col" style="width: 2%; font-size:8pt" ></th><th scope="col" style="font-size:12pt" >유형</th><th scope="col" style="font-size:12pt" >'.$types.'</th></tr></tr></thead>
					       <tr>  <td style="width: 2%; font-size:8pt" ></td><td  style="font-size:12pt" >사유</td><td style="font-size:12pt" >'.$reasons.'</td>  </tr>
					       <tr>  <td style="width: 2%; font-size:8pt" ></td><td style="font-size:12pt" >계획</td><td style="font-size:12pt" ><input type="text" class="form-control" id="datepicker" name="datepicker"  value= "'.$today.'" placeholder="'.$today.'"></td> </tr>
					       <tr>  <td style="width: 2%; font-size:8pt" ></td><td style="font-size:12pt" >변경</td><td style="font-size:12pt" ><input type="text" class="form-control" id="datepicker2" name="datepicker2"  placeholder="변경후"></td> </tr>
					       <tr>  <td style="width: 2%; font-size:8pt" ></td><td style="font-size:12pt" >증감</td><td style="font-size:12pt" >'.$selecttime.'</td> </tr>
					       <tr>  <td style="width: 2%; font-size:8pt" ></td><td style="font-size:12pt" >메모</td><td style="font-size:12pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="메모"></td> </tr>
					       <tr>  <td style="width: 2%; font-size:8pt" ></td><td style="font-size:12pt" ><span  onclick="attendance(9,'.$studentid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val(),$(\'#datepicker2\').val(),$(\'#basic3\').val(),$(\'#squareInput\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></td><td style="font-size:12pt" ></td> </tr>
					       </table><table width=100% align=center>'.$showlast.$showattendlog.'</table> </div></div></div></div></div></div></div>';								
	
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
		            url:"database.php",
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
		 alert("체크 상태에서 새로고침하면 목록에서 사라집니다");
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
 
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker2").datetimepicker({
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
