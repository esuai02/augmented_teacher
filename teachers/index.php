<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");


$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentindex','$timecreated')");
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
// 오늘의 목표 불러오기
$timestart=time()-43200;
$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY id  DESC  LIMIT 1 ");
$todaygoal=$goal->text;
if($todaygoal==NULL)$todaygoal='목표 미입력';	

//최근 메세지 불러오기
  
$timeafter=time()-86400*7;
$wboard=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$studentid' AND  ((userrole LIKE 'teacher'  AND turn LIKE '0'  AND timemodified > '$timeafter' AND  status NOT LIKE 'complete') OR (homework NOT LIKE 'NULL') ) ORDER BY timemodified ");
$count2=count($wboard);
if($count2==0)$count2=NULL;
$waitinglist= json_decode(json_encode($wboard), True);
$count3=0;
unset($value);
foreach(array_reverse($waitinglist) as $value)
	{	
	$boardid=$value['wboardid'];
	$timemodified=date("m/d", $value['timemodified']);
	$tdiff=time()-$value['timemodified'];
	$questionid=$value['contentsid'];
	$telapsed=round($tdiff/60,0);
	$userrole=$value['userrole'];
	/*
 	$gettags=$DB->get_records_sql("SELECT * FROM mdl_tag_instance WHERE itemid='$questionid' ");
	$tags= json_decode(json_encode($gettags), True);
	$tagtitles=NULL;
	unset($value2);
	foreach($tags as $value2)
		{
		$tagid=$value2['tagid'];	
		if($tagid>136)
			{
			$tagname=$DB->get_record_sql("SELECT * FROM mdl_tag WHERE id='$tagid' ");
			if($tagname->description!=='book')$tagtitles.=$tagname->name.'|';
			}
		}
	*/
	if($value['nreview']==0 && $value['status']!=='returned' && $telapsed >= 0)
		{
		if($value['homework']==1)
			{
			if($tagtitles==NULL)$tagtitles=$value['contentstitle'];
			//$homework.='<tr><td></td><td>'.$tagtitles.'</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$boardid.'" target="_blank" >답안 작성하기</a></td><td></td><td>'.$telapsed.'분 전</td><td>'.$timemodified.'</td><tr>'; 
			$homework.='<tr><td></td><td>개념질문에 대한 답변</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$boardid.'" target="_blank" >답안 작성하기</a></td><td></td><td>'.$telapsed.'분 전</td><td>'.$timemodified.'</td><tr>'; 			
			$count3++;
			}
		elseif($value['homework']==2)
			{
			if($tagtitles==NULL)$tagtitles=$value['contentstitle'];
			//$homework.='<tr><td></td><td>'.$tagtitles.'</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$boardid.'" target="_blank" >평가 중</a></td><td></td><td>'.$telapsed.'분 전</td><td>'.$timemodified.'</td><tr>'; 
			$homework.='<tr><td></td><td>퀴즈풀이에 대한 답변</td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$boardid.'" target="_blank" >평가 중</a></td><td></td><td>'.$telapsed.'분 전</td><td>'.$timemodified.'</td><tr>'; 
			$count3++;
			}
		if($tdiff<43200)$new.= '<tr><td><label class="form-check-label"><input type="checkbox"  onclick="checkwhiteboard(7,'.$studentid.',\''.$boardid.'\', this.checked)"/></label></td>
		<td> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$boardid.'&speed=9" target="_blank" >'.$tagtitles.' 오늘/'.$telapsed.'분 전</a></td><td> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >퀴즈</a>|<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'" target="_blank" >질문하기</a></td><td>'.$timemodified.'</td><tr>'; 
		elseif($tdiff<86400*3) $new.= '<tr><td><label class="form-check-label"><input type="checkbox"  onclick="checkwhiteboard(7,'.$studentid.',\''.$boardid.'\', this.checked)"/></label></td><td> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$boardid.'&speed=9" target="_blank" >'.$tagtitles.' </a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'" target="_blank" >편집</a> | <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >퀴즈</a></td><td>'.$timemodified.'</td><tr>'; 
 		}
	}
if($count3==0)$count3=NULL; 
else $count3='제출요청';



$timestart2=time()-86400;
 
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE (mdl_quiz_attempts.timefinish > '$timestart2' OR mdl_quiz_attempts.timestart > '$timestart2') AND mdl_quiz_attempts.userid='$studentid' AND mdl_quiz_attempts.comment !='NULL' ORDER BY mdl_quiz_attempts.timestart ");
$count1=count($quizattempts);
$quizresult = json_decode(json_encode($quizattempts), True);
$nquiz=count($quizresult);
$quizlist=NULL;
unset($value); 	
foreach(array_reverse($quizresult) as $value) 
{
	$quizgrade=round($value['sumgrades']/$value['tgrades']*100,0);
	 
	if($quizgrade>89.99)
		{
		$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
		}
	elseif($quizgrade>69.99)
		{
		$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
		}
	else $imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';

	$quizlist.='<tr><td>'.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' | <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$value['state'].'...'.date("H:i",$value['timefinish']).'</td>
<td><img src=https://mathking.kr/IMG/HintIMG/BESSI1590817529001.png width=15>&nbsp;&nbsp;&nbsp;'.$value['comment'].'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&qid='.$value['id'].'" target="_blank">첨삭결과</a></td></tr>';
 
}

   
echo ' 			 			<div class="col-md-12">
							<div class="card">
								  <div class="card-title"><div class="card-body">
									<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">현재 미션들 &nbsp;&nbsp;</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-profile1-tab" data-toggle="pill" href="#pills-profile1" role="tab" aria-controls="pills-profile1" aria-selected="false">퀴즈결과 의견 &nbsp;&nbsp;<span class="badge badge-count badge-Success">'.$count1.'</span></a>											 	 
										</li> 
										<li class="nav-item">
											<a class="nav-link" id="pills-profile2-tab" data-toggle="pill" href="#pills-profile2" role="tab" aria-controls="pills-profile2" aria-selected="false">풀이 수신함 &nbsp;&nbsp;<span class="badge badge-count badge-Success">'.$count2.'</span></a>											 	 
										</li> 
										<li class="nav-item">
											<a class="nav-link" id="pills-profile3-tab" data-toggle="pill" href="#pills-profile3" role="tab" aria-controls="pills-profile3" aria-selected="false">서술형 평가 &nbsp;&nbsp;<span class="badge badge-count badge-Primary">'.$count3.'</span></a>											 	 
										</li> 
										<li class="nav-item">
											<a class="nav-link" id="pills-profile4-tab" data-toggle="pill" href="#pills-profile4" role="tab" aria-controls="pills-profile4" aria-selected="false">내 기억포털 &nbsp;&nbsp;<span class="badge badge-count badge-Success">'.$count4.'</span></a>											 	 
										</li> 
									</ul>
									<div class="tab-content mb-3" id="pills-tabContent">
									<div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"> 
									<table class="table"><tr><th width=3%></th><th width=60%></th><th width=25%></th></tr>
											<tbody>';
										// get mission list
										$trecent2=time()-31104000;  // 1year ago
										$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND timecreated>'$trecent2'  AND userid='$studentid'  ");
										$result = json_decode(json_encode($missionlist), True);

										unset($value);
										foreach($result as $value)
										{
										$mid=$value['id'];
										$subject=$value['subject'];
										$mtname=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' ");
										$subjectname=$mtname->name;
										$mtid=$mtname->mtid;
     
										$text=$value['text'];
										$deadline= $value['deadline']; 

										if($subject!=NULL)
											{
											echo '<tr><td><div class="form-check"><label class="form-check-label">
											<input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/>
											<span class="form-check-sign"></span></label></div></td>
											<td style="font-size:18pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">'.$subjectname.'</td><td style="font-size:18pt">'.$deadline.'</td></tr>';
											}
										else
											{
											echo '<tr><td><div class="form-check"><label class="form-check-label">
											<input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/>
											<span class="form-check-sign"></span></label></div></td><td style="font-size:18pt">'.$text.'</td><td style="font-size:18pt">'.$deadline.'</td></tr>';
											}
} 
 


										echo '</tbody></table>
										<table class="table"><thead><tr>
										<th scope="col" style="width: 70%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="목표를 입력해 주세요"></th>
										<th  style="width: 30%; font-size:18pt"><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="목표날짜"></th>
										<th scope="col" >
										<button type="button" onclick="inputmission(1,'.$studentid.',$(\'#squareInput\').val(),$(\'#datepicker\').val()) "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'"><img src="http://mathking.kr/Contents/Moodle/save.gif" width=30></a></button></th>
										</tr></thead></table> <h4 class="card-title"><div style=" font:bold 1.2em/1.0em 맑은고딕체;text-align: center ;color:blue;" > '.$todaygoal.' </h4></div> 


										 
									 
										<div class="tab-pane fade" id="pills-profile1" role="tabpanel" aria-labelledby="pills-profile1-tab"> 
										<table style="width: 100%;"><tr><th width="50%"></th><th width="40%"></th><th width="10%"></th></tr>'.$quizlist.' </table>	 
										</div> 
										<div class="tab-pane fade" id="pills-profile2" role="tabpanel" aria-labelledby="pills-profile2-tab"> 
										<p>오답 및 깃발표시 문항 풀이 수신함. <a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullengagement.php?id='.$studentid.'">... <i class="flaticon-envelope-3"></i>&nbsp;기억 연장하기</a></p>
													<table style="width: 90%;">'.$new.'</table>	 
										</div> 
										<div class="tab-pane fade" id="pills-profile3" role="tabpanel" aria-labelledby="pills-profile3-tab"> 
										<table style="width: 100%;"><tr><th width="3%"></th><th width="52%"></th><th width="15%"></th><th width="10%"></th><th width="10%"></th><th width="10%"></th></tr>'.$homework.'</table>	  
										</div> 
										<div class="tab-pane fade" id="pills-profile4" role="tabpanel" aria-labelledby="pills-profile4-tab"> 
										4	 
										</div> 

 </div></div></div></div> ';
 
 
include("brainportal.php");
echo '
							
					 
		 </div></div></div></div></div>';

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
		function inputmission(Eventid,Userid,Inputtext,Deadline){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "inputtext":Inputtext,
			  "deadline":Deadline,		 
		               },
		            success:function(data){
			            }
		        })

		}
		function changecheckbox(Eventid,Userid,Missionid, Checkvalue){
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
		function checkwhiteboard(Eventid,Userid,Wboardid, Checkvalue){
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
			    "wboardid":Wboardid,
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
';

?>