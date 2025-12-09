<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");
$thisyear = date("Y",time());

$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");

$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by deadline DESC LIMIT 2");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$missionid=$value['id'];
	$plantype=$value['plantype'];
	$text=$value['memo'];		
	$text=iconv_substr($text, 0, 70, "utf-8");								
	$deadline= $value['deadline'];    
	$dateString = date("Y-m-d",$deadline);
	$checkbox='';
	if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
	elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
	elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
	else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';

	if($plantype==='분기목표')$plantype='<b style="color:purple;">분기목표</b>';
	elseif($plantype==='방향설정')$plantype='<b style="color:red;">방향설정</b>';
	//elseif($plantype==='중간고사')$plantype='<b style="color:blue;">중간고사</b>';
	//elseif($plantype==='기말고사')$plantype='<b style="color:blue;">기말고사</b>';
	//elseif($plantype==='모의고사')$plantype='<b style="color:blue;">모의고사</b>';

	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck(150,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck2(200,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';

	if($value['plantype']==='방향설정') $Grandgolden.= '<tr> <td width=4% style="padding-bottom:0px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'"><img src="https://mathking.kr/Contents/IMAGES/direction.gif" width=40></a></td><td width=15% align=left style="font-size:12pt">'.$plantype.'</td> <td align=left style="font-size:12pt;" >'.$text.'</td> <td width=5%></td> <td width=10% style="font-size:10pt">'.$dateString.'</td><td width=5%>'.$checkdeadline.'</td><td width=10% style="font-size:10pt"></td><td>'.$checkhide.'</td></tr>';	 									
	else $goalsteps.= '<tr> <td width=4%><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/bigplan.html"target="_blank"><img style="padding-bottom:0px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641245056.png width=40></a></td><td width=15% align=left style="font-size:12pt">'.$plantype.'</td> <td align=left style="font-size:12pt;" >'.$text.'</td> <td width=5%></td> <td width=10% style="font-size:10pt">'.$dateString.'</td><td width=5%>'.$checkdeadline.'</td><td width=10% style="font-size:10pt"></td><td>'.$checkhide.'</td></tr>';
	} 

if($role!=='student')
	{
	$create1='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=3"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create2='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=4"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create3='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=5"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create4='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=6"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create5='<a href="http://twinery.org/2/#!/stories"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	}

include("../books/gpttalk.php");
$gpteventname='Golden Plan';
$contextid='goldenplan'.$studentid;
include("../books/gptrecord.php");
if($gptquestion==NULL)$scriptontopic='<table width=100%><tr><td>My golden story goes like this ...</td><td width=2% align=right><span onclick="GPTTalk(\''.$gpteventname.'\',\'질문\',\''.$gptquestion.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt2.png width=18></span></td></tr></table>';
else $scriptontopic='<table width=100%><tr><td>'.$gptquestion.'</td><td><span onclick="GPTTalk(\''.$gpteventname.'\',\'질문\',\''.$gptquestion.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt2.png width=18></span></td></tr><tr><td> '.$gpttalk.'</td><td width=2%><span onclick="GPTTalk(\''.$gpteventname.'\',\'답변\',\''.$gpttalk.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png width=18></span></td></tr></table>';


echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-header"><div class="card-title"><table align=center width=90% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$Grandgolden.$goalsteps.'</table><hr><table align=center width=90%><tr><td width=4% valign=top><a href="https://moreleap.clickn.co.kr/pages/visionbook"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/visionbook.png width=40></a></td><td>'.$scriptontopic.'</td></tr></table></div></div><div class="card-body"> ';
 

$plantypes='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic" class="form-control"  ><h3><option value="분기목표">분기목표</option><option value="방향설정">방향설정</option></h3></select> </div>';

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
 
$result2 = json_decode(json_encode($goals), True);
unset($value);
 
foreach($result2 as $value)
	{
	$date_pre=$date;
	$att=gmdate("m월 d일 ", $value['timecreated']+32400);
	$date=gmdate("d", $value['timecreated']+32400);	 
	$goaltype='<b style="color:#bf04e0;">주간목표</b>';
	$notetype='weekly';	 
	$daterecord=date('Y_m_d', $value['timecreated']);  	 
	$tend=$value['timecreated'];
	 
 	$imgthisweek='imgWgrade'.$value['planscore'];
	$imgresult=$$imgthisweek;
	$goalhistory.= '<tr><td width=10%>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
	<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td>'.$imgresult.'</td> </tr>';
	}
$weekplanhistory='<table width=100%><tr><td width=70%>   <table width=100% >'.$goalhistory.'</table>    </td><td></td><td width=30%>   </td></tr></table>';

$stateColor1='primary';
$stateColor2='primary'; 
$stateColor3='primary'; 
if($username->state==1)$stateColor1='Default'; 
if($username->state==2)$stateColor2='Default'; 
if($username->state==0)$stateColor3='Default'; 
if($role!=='student')$teacherScore='<button class="btn btn-success"  type="button"  style = "font-size:16;background-color:lightblue;color:black;border:0;height:40px;outline:0;"  onclick="WeeklyGrade()">분기목표 원활도 평가</button>';
 
if($USER->id==2)$exceptionButton=' <button   type="button"   id="alert_exception"  class="btn btn-'.$stateColor3.'" style = "font-size:16;background-color:lightblue;color:white;border:0;height:40px;outline:0;" >예외설정</button>';
$analysistext='<table width=1%><tr><td>'.$username->lesson.'</td></tr></table>';
echo ' 									
	<table width=80% align=center class="table" align=center><thead><tr><th scope="col" style="width: 2%; font-size:12pt" ></th><th scope="col" style="width: 15%; font-size:15pt" >'.$plantypes.'</th><th  style="width:15%; font-size:18pt"><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="데드라인"></th><th scope="col" style="width: 35%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="학습방향"></th><th scope="col" >
	<span  onclick="inputgoalstep(8,'.$studentid.',$(\'#basic1\').val(),$(\'#datepicker\').val(),$(\'#squareInput\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th> <th style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size:20px;"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$studentid.'&mode=CA">목표입력 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647829655.png width=20></a>  </th>
	</tr></thead></table><br><br>'.$weekplanhistory;
if($role!=='student') echo' <br><br><table  width=80% align=center><tr><th><button   type="button"  id="alert_sharecare"  class="btn btn-'.$stateColor1.'" style = "font-size:16;background-color:lightblue;color:white;border:0;height:40px;outline:0;" ><div class="tooltip7">관심공유<span class="tooltiptext7"><table><tr><td width=40% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$analysistext.'</td></tr></table></span></div></button>&nbsp;&nbsp;&nbsp;<button   type="button"   id="alert_sharedanger"  class="btn btn-'.$stateColor2.'" style = "font-size:16;background-color:lightblue;color:white;border:0;height:40px;outline:0;" ><div class="tooltip7">위험공유<span class="tooltiptext7"><table><tr><td width=40% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$analysistext.'</td></tr></table></span></button>&nbsp;&nbsp;&nbsp;<button   type="button"   id="alert_normalcondition"  class="btn btn-'.$stateColor3.'" style = "font-size:16;background-color:lightblue;color:white;border:0;height:40px;outline:0;" >일상모드</button>'.$exceptionButton.'</th><th>'.$teacherScore.'</th><th width=20%></th><th align=center><button   type="button"  class="btn btn-success"  id="alert_commentWeek"  style = "font-size:16;background-color:lightblue;color:black;border:0;height:40px;outline:0;" >건의사항</button></th></tr></table><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';

echo '
				<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
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
