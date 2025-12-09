<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid=required_param('id', PARAM_INT);
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("p_navbar.php"); 
 
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','editschedule','$timecreated')");

 echo ' 

					<div class="row">
						<div class="col-md-12">
							 
							<div class="card">
								<div class="card-header">
									<div class="card-title">Weekly schedule</div>
								</div>
								<div class="card-body" style="height: 100%; width: 100%">
									<table  align=center style="width: 100%" class="table table-head-bg-primary mt-4">
										<thead>
											<tr>
												<th scope="col" style="width: 12.5%;">요일</th>
												<th scope="col" style="width: 12.5%;">시작시간&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
												<th scope="col" style="width: 12.5%;">공부시간&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
												<th scope="col" style="width: 12.5%;">학습코스&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
												<th scope="col" style="width: 12.5%;">상담시간&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
											</tr>
										</thead>
										<tbody>';
$timeplan = $DB->get_records_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid' ORDER BY timecreated DESC LIMIT 1");
$result = json_decode(json_encode($timeplan), True);
if(count($timeplan)==0)
{
if($start1==NULL)$start1=0;
if($start2==NULL)$start2=0;
if($start3==NULL)$start3=0;
if($start4==NULL)$start4=0;
if($start5==NULL)$start5=0;
if($start6==NULL)$start6=0;
if($start7==NULL)$start7=0;

if($start11==NULL)$start1=0;
if($start12==NULL)$start2=0;
if($start13==NULL)$start3=0;
if($start14==NULL)$start4=0;
if($start15==NULL)$start5=0;
if($start16==NULL)$start6=0;
if($start17==NULL)$start7=0;

if($duration1==NULL)$duration1=0;
if($duration2==NULL)$duration2=0;
if($duration3==NULL)$duration3=0;
if($duration4==NULL)$duration4=0;
if($duration5==NULL)$duration5=0;
if($duration6==NULL)$duration6=0;
if($duration7==NULL)$duration7=0;

if($memo1==NULL)$memo1='...';
if($memo2==NULL)$memo2='...';
if($memo3==NULL)$memo3='...';
if($memo4==NULL)$memo4='...';
if($memo5==NULL)$memo5='...';
if($memo6==NULL)$memo6='...';
if($memo7==NULL)$memo7='...';
if($memo8==NULL)$memo8='...';
if($memo9==NULL)$memo9='...';
}

foreach($result as $value)
{
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

$duration1=$value['duration1'];
$duration2=$value['duration2'];
$duration3=$value['duration3'];
$duration4=$value['duration4'];
$duration5=$value['duration5'];
$duration6=$value['duration6'];
$duration7=$value['duration7'];

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
if($start1=='12:00 AM')$start1=NULL;
if($start2=='12:00 AM')$start2=NULL;
if($start3=='12:00 AM')$start3=NULL;
if($start4=='12:00 AM')$start4=NULL;
if($start5=='12:00 AM')$start5=NULL;
if($start6=='12:00 AM')$start6=NULL;
if($start7=='12:00 AM')$start7=NULL;
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;

echo ' 
 <tr><td>월요일</td><td><input type="text" class="form-control" id="timepicker1"     value="'.$start1.'"></td><td><div class="select2-input"><select id="basic1" name="basic1"  class="form-control"   ><option value="'.$duration1.'">'.$duration1.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput1" value="'.$memo1.'"></td><td><input type="text" class="form-control"    id="timepicker11" value="'.$start11.'"></td></tr>
 <tr><td>화요일</td><td><input type="text" class="form-control"   id="timepicker2"   value="'.$start2.'"></td><td><div class="select2-input"><select id="basic2" name="basic2"   class="form-control"   ><option value="'.$duration2.'">'.$duration2.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput2" value="'.$memo2.'"></td><td><input type="text" class="form-control"   id="timepicker12" value="'.$start12.'"></td></tr>
 <tr><td>수요일</td><td><input type="text" class="form-control"  id="timepicker3"   value="'.$start3.'"></td><td><div class="select2-input"><select id="basic3" name="basic3"   class="form-control"   ><option value="'.$duration3.'">'.$duration3.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput3" value="'.$memo3.'"></td><td><input type="text" class="form-control"   id="timepicker13" value="'.$start13.'"></td></tr>
 <tr><td>목요일</td><td><input type="text" class="form-control"   id="timepicker4"   value="'.$start4.'"></td><td><div class="select2-input"><select id="basic4" name="basic4"   class="form-control"    ><option value="'.$duration4.'">'.$duration4.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput4" value="'.$memo4.'"></td><td><input type="text" class="form-control"   id="timepicker14" value="'.$start14.'"></td></tr>
 <tr><td>금요일</td><td><input type="text" class="form-control"   id="timepicker5"   value="'.$start5.'"></td><td><div class="select2-input"><select id="basic5" name="basic5"   class="form-control"  ><option value="'.$duration5.'">'.$duration5.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput5" value="'.$memo5.'"></td><td><input type="text" class="form-control"   id="timepicker15" value="'.$start15.'"></td></tr>
 <tr><td>토요일</td><td><input type="text" class="form-control"  id="timepicker6"   value="'.$start6.'"></td><td><div class="select2-input"><select id="basic6" name="basic6"   class="form-control"  ><option value="'.$duration6.'">'.$duration6.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput6" value="'.$memo6.'"></td><td><input type="text" class="form-control"   id="timepicker16" value="'.$start16.'"></td></tr>
 <tr><td>일요일</td><td><input type="text" class="form-control"   id="timepicker7"   value="'.$start7.'"></td><td><div class="select2-input"><select id="basic7" name="basic7"   class="form-control"   ><option value="'.$duration7.'">'.$duration7.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td><td><input type="text" class="form-control input-square" id="squareInput7" value="'.$memo7.'"></td><td><input type="text" class="form-control"   id="timepicker17" value="'.$start17.'"></td></tr>
<tr> <td></td><td></td><td>총 '.$memo9.'시간</td><td>주'.floor($memo9/5).' 회 수업</td><td></td></tr>

 </tbody></table>
<table  align=center style="width: 100%" class="table table-head-bg-primary mt-4">
<tr><th scope="col" style="width: 10%;">✎ 총 시간</th><th scope="col" style="width: 60%;">'.$memo9.' 시간</th></tr>
<tr><td>✎ 메모</td><td><input type="text" class="form-control input-square" id="squareInput8" value="'.$memo8.'"></td></tr>
<tr><td>✎ 적용날짜</td><td><input type="text" class="form-control" id="datepicker" name="datepicker"   value="today"  placeholder="적용날짜"></td></tr>

<tr><td> 저장하기 </td><td><button type="button" onclick="editschedule(3,'.$studentid.',$(\'#timepicker1\').val(),$(\'#timepicker2\').val(),$(\'#timepicker3\').val(),$(\'#timepicker4\').val(),$(\'#timepicker5\').val(),$(\'#timepicker6\').val(),$(\'#timepicker7\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#basic4\').val(),$(\'#basic5\').val(),$(\'#basic6\').val(),$(\'#basic7\').val(),$(\'#squareInput1\').val(),$(\'#squareInput2\').val(),$(\'#squareInput3\').val(),$(\'#squareInput4\').val(),$(\'#squareInput5\').val(),$(\'#squareInput6\').val(),$(\'#squareInput7\').val(),$(\'#squareInput8\').val(),$(\'#squareInput9\').val(),$(\'#datepicker\').val(),$(\'#timepicker11\').val(),$(\'#timepicker12\').val(),$(\'#timepicker13\').val(),$(\'#timepicker14\').val(),$(\'#timepicker15\').val(),$(\'#timepicker16\').val(),$(\'#timepicker17\').val())"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1"><img src="http://mathking.kr/Contents/Moodle/save.gif" width=20 height=20></a></button></td><tr></table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
		</div>
';
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
		function editschedule(Eventid,Userid,Start1,Start2,Start3,Start4,Start5,Start6,Start7,Duration1,Duration2,Duration3,Duration4,Duration5,Duration6,Duration7,Memo1,Memo2,Memo3,Memo4,Memo5,Memo6,Memo7,Memo8,Memo9,Date,Start11,Start12,Start13,Start14,Start15,Start16,Start17){		 
		
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			  data : {
			"userid":Userid,
		             "eventid":Eventid,
			"start1":Start1,
			"start2":Start2,
			"start3":Start3,
			"start4":Start4,
			"start5":Start5,
			"start6":Start6,
			"start7":Start7,
			"start11":Start11,
			"start12":Start12,
			"start13":Start13,
			"start14":Start14,
			"start15":Start15,
			"start16":Start16,
			"start17":Start17,
			"duration1":Duration1,
			"duration2":Duration2,
			"duration3":Duration3,
			"duration4":Duration4,
			"duration5":Duration5,
			"duration6":Duration6,
			"duration7":Duration7,
			"memo1":Memo1,
			"memo2":Memo2,
			"memo3":Memo3,
			"memo4":Memo4,
			"memo5":Memo5,
			"memo6":Memo6,
			"memo7":Memo7,
			"memo8":Memo8,
			"memo9":Memo9,	
			"date":Date,			
		               },
		
		            success:function(data){
			            }
		        })
		 
		setTimeout(function(){
		location.reload();
		},2000); // 3000밀리초 = 3초
		}
		function ChangeCheckBox(Eventid,Userid, Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
		                "missionid":Questionid,
		                "attemptid":Missionid,
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
		
		$("#timepicker1").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker2").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker3").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker4").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker5").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker6").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker7").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker11").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker12").datetimepicker({
			format: "h:mm A", 
  
		});
		$("#timepicker13").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker14").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker15").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker16").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#timepicker17").datetimepicker({
			format: "h:mm A", 
 
		});
		$("#basic").select2({
			theme: "bootstrap"
 
		});
		$("#basic1").select2({
			theme: "bootstrap"
 
		});
		$("#basic2").select2({
			theme: "bootstrap"
 
		});
		$("#basic3").select2({
			theme: "bootstrap"
 
		});
		$("#basic4").select2({
			theme: "bootstrap"
 
 		});
		$("#basic5").select2({
			theme: "bootstrap"
 
 
		});
		$("#basic6").select2({
			theme: "bootstrap"
 
 		});
		$("#basic7").select2({
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