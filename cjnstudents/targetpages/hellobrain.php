<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");
$thisyear = date("Y",time());

$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");

$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by deadline");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$missionid=$value['id'];
	$plantype=$value['plantype'];
	$text=$value['memo'];										
	$deadline= $value['deadline'];    
	$dateString = date("Y-m-d",$deadline);
	$checkbox='';
	if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
	elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
	elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
	else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';

	if($plantype==='분기목표')$plantype='<b style="color:purple;">분기목표</b>';
	elseif($plantype==='방향설정')$plantype='<b style="color:red;">방향설정</b>';
	elseif($plantype==='중간고사')$plantype='<b style="color:blue;">중간고사</b>';
	elseif($plantype==='기말고사')$plantype='<b style="color:blue;">기말고사</b>';
	elseif($plantype==='모의고사')$plantype='<b style="color:blue;">모의고사</b>';

	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck(150,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck2(200,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';

	if($value['plantype']!=='방향설정')$goalsteps.= '<tr> <td width=5%></td> <td width=3% style="padding-top:5px;">'.$checkcomplete.'</td><td width=15% align=left style="font-size:12pt">'.$plantype.'</td> <td width=30% align=left style="font-size:12pt;" >'.$text.'</td> <td width=5%></td> <td width=10% style="font-size:10pt">'.$dateString.'</td><td width=5%>'.$checkdeadline.'</td><td width=10% style="font-size:10pt">도움말 (컨텐츠 은행에서 선택/<a href="https://docs.google.com/document/d/10mtwkRyQ6sGjSUDrN4c39FuGPoCWu3BovqI-Oibnyzo/edit" target="_blank">메뉴얼</a>)</td><td width=3%>'.$checkhide.'</td></tr>';		 									
	else $longtermplan= $text.' &nbsp;&nbsp;<label class="form-check-label"><input type="checkbox"  onclick="updatecheck2(200,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label>';
	} 

 

echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-header"><div class="card-title"><table align=center><tr><td width=15% style="padding-bottom:10px;" align=center valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png" width=100></a><br><br>어서오세요, 환영합니다 ! </td> 
<td width=10%></td><td><br><br>다음 내용을 입력하시면 자신에게 맞는 공부환경이 준비됩니다 !<hr><br><br>
몇 학년이신가요 ? <input type="text" class="form-control input-square" id="'.$mathgrademoscnd.'" value="'.$$mathgrademoscnd.'"> <br><br>
개념공부 진행과목을 선택해 주세요 ! <input type="text" class="form-control input-square" id="'.$mathgrademoscnd.'" value="'.$$mathgrademoscnd.'"> <br><br>
가고 싶은 대학은 어디인가요 ? <input type="text" class="form-control input-square" id="'.$mathgrademoscnd.'" value="'.$$mathgrademoscnd.'">  
<br><br><button style="background-color:green; " ><a style="color:white" href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'">시작하기</a></br> </td></tr></table>
</div></div><div class="card-body"> ';
 

$plantypes='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic" class="form-control"  ><h3><option value="개념미션">성취계단 : 개념미션</option><option value="심화미션">성취계단 : 심화미션</option><option value="내신미션">성취계단 : 내신미션</option><option value="수능미션">성취계단 : 수능미션</option><option value=""> </option><option value="분기목표">분기목표</option><option value="방향설정">방향설정</option></h3></select> </div>';

$Aweekago=time()-604800;
$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
		LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid WHERE mdl_question.name LIKE '%MX%' AND mdl_question_attempt_steps.userid='$studentid' AND  state NOT LIKE 'todo' AND  state NOT LIKE 'complete' AND  mdl_question_attempt_steps.timecreated > '$Aweekago'   ");
$nattempts=count($questionattempts);
$DB->execute("UPDATE {abessi_indicators} SET nattempts='$nattempts' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  


$imgWgrade0='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1623817278001.png" height=15>';
$imgWgrade1='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15>';
$imgWgrade2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15>';
$imgWgrade3='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15>';
$imgWgrade4='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15>';
$imgWgrade5='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15>';

$nnn=1;
$monthsago2=time()-604800*8;
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' AND timecreated>'$monthsago2' ORDER BY id DESC ");
 
   
echo '
				
								</div>
							</div>
						</div>
					 </div>
				</div>
			</div>
			
		</div>';
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
		 swal("새로고침하면 목록에서 사라집니다.", {buttons: false,timer: 1000});  
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




<script>

function WeeklyGrade() 
{
  let wrap = document.createElement('div');
  wrap.setAttribute('class', 'text-muted');
  wrap.innerHTML = '<button onclick="reply(\'level1\',\'1\')" type="button" value="level1" class="btn feel">이탈 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009610001.png" width=30 height=30></button><button onclick="reply(\'level2\',\'2\')" type="button" value="level2" class="btn feel">시작 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009642001.png" width=30 height=30></button><button onclick="reply(\'level3\',\'3\')" type="button" value="level3" class="btn feel">연결 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009715001.png" width=30 height=30></button><button onclick="reply(\'level4\',\'4\')" type="button" value="level4" class="btn feel">루틴 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009756001.png" width=30 height=30></button><button onclick="reply(\'level5\',\'5\')" type="button" value="level5" class="btn feel">안정 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009790001.png" width=30 height=30></button><hr>' ;
swal({
    title: "분기목표 안정도",
    text: "분기목표-중간목표-주간목표-오늘목표-활동결과의 연결상태를 표시",
    closeOnClickOutside: false,
    content: {
      element: wrap
    },
    buttons: {
      confirm: {
        text:"취소",
        visible: true,
        className: "btn btn-default",
        closeModal: true,
      }
    },
  }).then((value) => {
    if (value === 'level1') {
      swal("Booster step 시작단계입니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level2') {
      swal("Booster step 실행단계입니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level3') {
      swal("Booster step 숙달단계입니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level4') {
      swal("Booster step 체화단계입니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level5') {
      swal("Booster step 마스터 클레스입니다.", {
        icon: "success",
        buttons: false
      });
    }
  });
}

function reply(feel,resultValue){
	var Userid= "<?php echo $studentid;?>";
	var Tutorid= "<?php echo $USER->id;?>";
	var Eventid="100";

swal.setActionValue(feel);
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":Eventid,
	"userid":Userid,
	"tutorid":Tutorid,
 	"value":resultValue,
	},
	success:function(data){
	 }
	 })
swal("상위 단계 목표와의 연결상태가 업데이트 되었습니다.", {buttons: false, timer: 2000, });
}
 
</script>
