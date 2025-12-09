<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid=required_param('id', PARAM_INT); 
$infotype=$_GET["info"]; 

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role!=='student')echo '';
else
	{
	echo '접근권한이 없습니다.';
	exit();
	}


if($infotype==='institute'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='88' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='88' "); }// 학교 
elseif($infotype==='birthdate'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='89' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='89' "); } //출생년도 
elseif($infotype==='academy'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='46' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='46' "); } //학원명
elseif($infotype==='location'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='68' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='68' "); } //지역 
elseif($infotype==='addcourse'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='83' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='83' "); } //코스추천
elseif($infotype==='roleinfo'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='22' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='22' "); } // 사용자 유형
elseif($infotype==='phone1'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='54' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='54' "); } // 사용자 유형
elseif($infotype==='phone2'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='85' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='85' "); } // 사용자 유형
elseif($infotype==='phone3'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='55' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='55' "); } // 사용자 유형

elseif($infotype==='fluency'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='60' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='60' "); } // 사용법 능숙도 
elseif($infotype==='goalstability'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='80' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='80' "); } //목표설정 안정도 
elseif($infotype==='effectivelearning'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='81' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='81' "); } // 81 논리분리
elseif($infotype==='lmode'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='90' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='90' "); } // 신규,자율,지도,도제
elseif($infotype==='evaluate'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='92' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='92' "); } // 92 완결형/도전형
elseif($infotype==='curriculum'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='70' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='70' "); } // 70 쇠퇴형/표준형/성장형
elseif($infotype==='nboosters'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='86' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='86' "); } //부스터 활동 횟수 
elseif($infotype==='inspecttime'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='72' "); $userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='72' "); } //점검주기

elseif($infotype==='termhours'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='107' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='107' "); } // 학기중 주별 공부시간
elseif($infotype==='vachours'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='108' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='108' "); } // 방학중 주별 공부시간

elseif($infotype==='univ'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='105' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='105' "); } // 학교 
elseif($infotype==='curtype'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='106' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='106' "); } // 커리큘럼 유형 
elseif($infotype==='Preseta'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='93' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='93' "); } // 93 개념미션 PRESET
elseif($infotype==='Presetb'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='94' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='94' "); } // 94 심화미션 PRESET 
elseif($infotype==='Presetc'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='95' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='95' "); } // 95 내신미션 PRESET 
elseif($infotype==='Presetd'){$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='96' ");$userlist = $DB->get_records_sql("SELECT * FROM mdl_user_info_data where  fieldid='96' "); } // 96 수능미션 PRESET

$thisyear=date("Y",time());									
$result = json_decode(json_encode($userlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$userid=$value['userid'];
	$username= $DB->get_record_sql("SELECT suspended,lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
	$role2=$userrole2->role;
	if($username->suspended==1 || $role2!=='student')continue;
	$studentname=$username->firstname.$username->lastname;


	$HP1 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='54' ");//학생 연락처 
	$HP2 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='85' "); //아버지 연락처 
	$HP3 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='55' "); //어머니 연락처 

	$institute = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='88' ");// 학교 
	$birthyear = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='89' ");//출생년도 
	
	$ngrade=$thisyear-$birthyear->data-6;
	if($ngrade<=6 )$ngrade=$ngrade; 		 
	elseif($ngrade<=9 )$ngrade=$ngrade-6; 
	elseif($ngrade<=13 )$ngrade=$ngrade-9; 
	$schinfo=$institute->data.' '.$ngrade;


	if($value['data']===$info->data) $list1.='<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$studentname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">✏ 수정</a></td><td>'.$schinfo.'</td> <td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
  	</td> <td><a href="https://app.mysms.com/#messages:+8210'.$HP1->data.' " target="_blank" >학생 '.$HP1->data.'</a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP3->data.' " target="_blank" >어머니 '.$HP3->data.'</a>&nbsp;&nbsp;&nbsp;</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" >아버지 '.$HP2->data.'</a>&nbsp;&nbsp;&nbsp;</td></tr>';
	elseif($value['data']==NULL) $list2.='<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$studentname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'"target="_blank">✏ 수정 </a></td><td>'.$schinfo.'</td> <td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
  	</td> <td><a href="https://app.mysms.com/#messages:+8210'.$HP1->data.' " target="_blank" >학생 '.$HP1->data.'</a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP3->data.' " target="_blank" >어머니 '.$HP3->data.'</a>&nbsp;&nbsp;&nbsp;</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" >아버지 '.$HP2->data.'</a>&nbsp;&nbsp;&nbsp;</td></tr>';

	}
echo '<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center ><td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><table align=center style="1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><tr><td><b> We transfer intelligence with CJN scaffolding</b> </td><td  width=5% ></td><td style="font-size:14px;">  KAIST TOUCH MATH powered by CJN</td></tr></table></td></tr></table></div></div> <br> <br> ';
 
echo '<h5 align=center><b>모든 '.$info->data.' 사용자</b></h5><hr><table align=center width=80%>'.$list1.'</table><hr><h6 align=center><b> 미입력 </b></h6><hr><table align=center width=80%>'.$list2.'</table>';
                                 
echo '<br><br><hr><table width="100%"><tr><td>난이도</td><td><img  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654452243.png" width=50 ></td><td>상태</td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/departure.gif" width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flying.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flyingthroughfield.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646909102.png" width=200  ></td></tr></table><hr>
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 500px;
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
  width: 500px;
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

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
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

  
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<link rel="stylesheet" href="../assets/css/ready.min.css"> 
	<script>
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY",
		});
function saveproperties(Userid,Institute,Birthdate,Phone1,Phone2,Phone3,Brotherhood,Academy,Addcourse,Location,Fluency,Goalstability,Efficiency,Lmode,Evaluate,Curriculum,Nboosters,Preseta,Presetb,Presetc,Presetd,Inspecttime,Userrole,Univ,Pathtype,Termhours,Vachours)
	{
	 
	swal({title: \'저장되었습니다.\',});	
	
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'40\',
	"userid":Userid,       
	"institute":Institute,
	"birthdate":Birthdate,
	"phone1":Phone1,
	"phone2":Phone2,
	"phone3":Phone3,
	"brotherhood":Brotherhood,
	"academy":Academy,
	"location":Location,
	"addcourse":Addcourse,
	"fluency":Fluency,
	"goalstability":Goalstability,
	"efficiency":Efficiency,
	"lmode":Lmode,
	"evaluate":Evaluate,
	"curriculum":Curriculum,
	"nboosters":Nboosters,
	"inspecttime":Inspecttime,
	"userrole":Userrole,
	"termhours":Termhours,
	"vachours":Vachours,

	"univ":Univ,
	"pathtype":Pathtype,
	"preseta":Preseta, 
	"presetb":Presetb, 
	"presetc":Presetc, 
	"presetd":Presetd, 
	},
	success:function(data){
		
			}
	 })
	
   	setTimeout(function() {location.reload(); },100);
	}

		function inputgoalstep(Eventid,Userid,Plantype,Deadline,Inputtext){   
		swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			
			  "plantype":Plantype,
			  "deadline":Deadline,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		setTimeout(function() {location.reload(); },100);
		}
 
		function updatecheck(Eventid,Userid,Missionid, Checkvalue){
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
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		}

		function updatecheck2(Eventid,Userid,Missionid, Checkvalue){
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
			    "missionid":Missionid,
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