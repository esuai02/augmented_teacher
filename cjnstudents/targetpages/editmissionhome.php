<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");

$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,eventid,timecreated) VALUES('$studentid','studenteditmissionhome',7,'$timecreated')");

//$studentid=required_param('id', PARAM_INT);
$cid=required_param('cid', PARAM_INT);
$mtid=required_param('mtid', PARAM_INT);

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND userid='$studentid' AND subject='$cid'  ORDER BY id DESC LIMIT 1");

if($mtid==1||$mtid==2 ||$mtid==7) // 개념미션, 심화미션
	{
	 echo ' 
	<div class="row"> 
		<div class="col-md-8">
			<div class="card">
				<div class="card-header"><div class="card-title">'.$curri->name.'</div></div>
				<div class="card-body">
	<table class="table table-hover"><thead><tr><th scope="col">#</th><th scope="col">단원명</th><th scope="col">인증시험</th><th scope="col"></th>
	<th scope="col">&nbsp;&nbsp; 

	<button type="button" onclick="inputmission2(12,'.$studentid.','.$cid.')"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editmissionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'">일정 만들기</a></button>

	</th>
	</tr></thead><tbody>';
	for($nch=1;$nch<=$curri->nch;$nch++)
		{
		$ch='ch'.$nch;
		$ch=$curri->$ch;
		$cnt='cnt'.$nch;
		$cnt=$curri->$cnt;
		$qid='qid'.$nch;
		$qid=$curri->$qid;
		$cmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$qid' ");
		$quizid=$cmid->inst;
		$datepicker='datepicker'.$nch;
		$dday2='dday'.$nch;

		$getdate2=$mission->$dday2;
		//echo '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------'.$dday2.'----------------s'.$mission->timecreated.'-----'.$datepicker.'-----'.$getdate2.'---<br>';
		$quizattempt = $DB->get_record_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades  
		FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$studentid' AND mdl_quiz.id='$quizid'   ORDER BY mdl_quiz_attempts.id DESC LIMIT 1"); 
		$grade=round($quizattempt->sgrades/$quizattempt->tgrades*100,0); 
		//$check=0
		//if($grade>=passgrade)$check=1;

		echo'<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cnt.'" target="_blank">'.$ch.'</td>
		<td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">인증시험</a></td><td></td><td>
		<input type="text" class="form-control" id="'.$datepicker.'"  value="'.$getdate2.'"></td></tr>';  //******************************************************************************input
		}
//$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission where complete=0 AND subject='$cid' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
//$deadline=$mission->deadline;
 
echo '<tr><td> </td><td></td><td><a href=</td><td></td>
<td><button type="button" id="alert_updatemissionschedule" onclick="
editschedule(13,'.$studentid.','.$cid.',$(\'#datepicker1\').val(),$(\'#datepicker2\').val(),$(\'#datepicker3\').val(),
$(\'#datepicker4\').val(),$(\'#datepicker5\').val(),$(\'#datepicker6\').val(),$(\'#datepicker7\').val(),$(\'#datepicker8\').val(),$(\'#datepicker9\').val(),$(\'#datepicker10\').val(),$(\'#datepicker11\').val(),
$(\'#datepicker12\').val(),$(\'#datepicker13\').val(),$(\'#datepicker14\').val(),$(\'#datepicker15\').val())"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'&tb=90">
저장하기</a></button>
</td></tr>
				</tbody>
				</table>
				</div>
			</div>
							 
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">오늘학습량</span>
														<span class="text-muted"> $3K</span>
													</div>
													<div class="progress" style="height: 2px;">
														<div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="78%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">주간학습량</span>
														<span class="text-muted"> 576</span>
													</div>
													<div class="progress" style="height: 4px;">
														<div class="progress-bar bg-info" role="progressbar" style="width: 65%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">완료율</span>
														<span class="text-muted fw-bold"> 70%</span>
													</div>
													<div class="progress" style="height: 6px;">
														<div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="70%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">잔여시간</span>
														<span class="text-muted fw-bold"> 60%</span>
													</div>
													<div class="progress">
														<div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="60%"></div>
													</div>
												</div>
											</div>
				</div>
			</div>
		</div>
 	</div>';
	}
 
if($mtid==3) // 내신미션
	{
	$cntitemid1=$curri->cntitem1;
	$cntitemid2=$curri->cntitem2;
	$cntitemid3=$curri->cntitem3;
	$cntitemid4=$curri->cntitem4;
 	$cntitemid5=$curri->cntitem5;
 	$cntitemid6=$curri->cntitem6;
	$prepexam=$curri->contentslist;  	
  
 	$getcntitems=$DB->get_records_sql("SELECT instance FROM mdl_course_modules WHERE id='$cntitemid1' OR  id='$cntitemid2' OR  id='$cntitemid3' OR  id='$cntitemid4' OR  id='$cntitemid5' OR  id='$cntitemid6' "); 
 	$getresult = json_decode(json_encode($getcntitems), True);
	$num=1; 
	unset($value);
	foreach($getresult as $value)
		{
		$instance=$value['instance'];
		$contents = $DB->get_records_sql("SELECT * FROM mdl_checklist_item LEFT JOIN mdl_checklist_check ON mdl_checklist_item.id=mdl_checklist_check.item  WHERE mdl_checklist_check.userid='$studentid' AND mdl_checklist_check.usertimestamp >10 AND mdl_checklist_item.checklist='$instance' ORDER BY usertimestamp ASC "); 
		$result2 = json_decode(json_encode($contents), True);
		
		unset($value2);
		foreach($result2 as $value2)
			{
			$name='quiz'.$num;
			$expl = explode('{if',$value2['displaytext']);
			$$name=$expl[0];
			$quizid='quizid'.$num;
			$$quizid=str_replace("https://mathking.kr/moodle/mod/quiz/view.php?id=","",$value2['linkurl']);
			$$quizid=$value2['moduleid'];
			$num++;
			} 
		}



 echo ' 
<div class="row"><div class="col-md-8"><div class="card">
					<div class="card-header"><div class="card-title">'.$curri->name.' .............. 시험기간 :   ................ 시험범위 : ............. 총 (   ) 단계</div></div>
<div class="card-body">
<table class="table table-hover" width=90%><tr><th scope="col" width=5%>#</th><th scope="col" width=45%>내신 테스트 유형</th><th scope="col" width=10%>결과</th><th scope="col" width=10%>통과 점수</th><th scope="col" width=5%></th>
<th scope="col"  width=25%><button type="button" onclick="inputmission2(12,'.$studentid.','.$cid.')"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editmissionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'">일정 만들기</a></button>
</th></tr>';

$nmax=$num-1; 
for($nch=1;$nch<=13;$nch++)
	{
//	$nch2=$nch+10;

	$qname='quiz'.$nch;
	$quizname=$$qname;	
	$quizid='quizid'.$nch;
	$qid=$$quizid;

	$cmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$qid' ");
	$quizid=$cmid->inst;
 
	$datepicker='datepicker'.$nch;
	$dday2='dday'.$nch;
 	$getdate2=$mission->$dday2;

	$basicp='basic'.$nch;
//	$basicq='basic'.$nch2;
	$grade='grade'.$nch;
	$hours='hours'.$nch;

	if($mission->$grade==NULL)$mission->$grade=0;
	if($mission->$hours==NULL)$mission->$hours=0;
	 
	$passgrade=$mission->$grade;
	$duration=$mission->$hours;

 	$quizattempt = $DB->get_record_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades  
	FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$studentid' AND mdl_quiz.id='$quizid'   ORDER BY mdl_quiz_attempts.id DESC LIMIT 1"); 
	$grade=round($quizattempt->sgrades/$quizattempt->tgrades*100,0); 

	//if($nch<=$nmax)
		{	
		$selectid='basic'.$nch;
 		echo '<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">'.$quizname.' </a></td><td>('.$grade.'점)</td>
		<td ><div class="select2-input"><select id='.$selectid.' name="basic" class="form-control" style="width:100px;"><option value="'.$passgrade.'">'.$passgrade.'</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></td>
		<td></td><td><input type="text" class="form-control" id="'.$datepicker.'"  value="'.$getdate2.'"></td></tr>';  
		}   
	}

$deadline=$mission->deadline;

echo '<tr><td> </td><td></td><td></td><td></td><td></td>
<td><button type="button" id="alert_updatemissionschedule" onclick="editschedule2(17,'.$studentid.','.$cid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#basic4\').val(),$(\'#basic5\').val(),$(\'#basic6\').val(),$(\'#basic7\').val(),$(\'#basic8\').val(),$(\'#basic9\').val(),$(\'#basic10\').val(),$(\'#basic11\').val(),$(\'#basic12\').val(),$(\'#basic13\').val(),
$(\'#datepicker1\').val(),$(\'#datepicker2\').val(),$(\'#datepicker3\').val(),$(\'#datepicker4\').val(),$(\'#datepicker5\').val(),$(\'#datepicker6\').val(),$(\'#datepicker7\').val(),$(\'#datepicker8\').val(),$(\'#datepicker9\').val(),$(\'#datepicker10\').val(),$(\'#datepicker11\').val(),$(\'#datepicker12\').val(),$(\'#datepicker13\').val())">
<a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'&tb=90">저장하기</a></button></td></tr>																			
									</table>
								</div>
							</div>
							 
						</div> 
								<div class="card-body">
									<div class="row">
										<div class="col-md-12">
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">오늘학습량</span>
														<span class="text-muted"> $3K</span>
													</div>
													<div class="progress" style="height: 2px;">
														<div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="78%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">주간학습량</span>
														<span class="text-muted"> 576</span>
													</div>
													<div class="progress" style="height: 4px;">
														<div class="progress-bar bg-info" role="progressbar" style="width: 65%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">완료율</span>
														<span class="text-muted fw-bold"> 70%</span>
													</div>
													<div class="progress" style="height: 6px;">
														<div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="70%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">잔여시간</span>
														<span class="text-muted fw-bold"> 60%</span>
													</div>
													<div class="progress">
														<div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="60%"></div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
 

					 
				</div>';
	
echo ' 
			</div>
			
		</div>
 
	</div>
';

}   // 내신미션 마지막 부분

if($mtid==4) // 수능미션
	{ 
 
 echo ' 
<div class="row"><div class="col-md-8"><div class="card">
					<div class="card-header"><div class="card-title">'.$curri->name.' .............. 시험기간 :   ................ 시험범위 : ............. 총 (   ) 단계</div></div>
<div class="card-body">
<table class="table table-hover" width=90%><tr><th scope="col" width=5%>#</th><th scope="col" width=45%>수능/모의고사 대비</th><th scope="col" width=13%>결과</th><th scope="col" width=10%>통과 점수</th><th scope="col" width=2%></th>
<th scope="col"  width=25%><button type="button" onclick="inputmission2(12,'.$studentid.','.$cid.')"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editmissionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'">일정 만들기</a></button>
</th></tr>';



$cntid=$curri->contentslist;
$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$cntid' "); 
$contents = $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$moduleid->instance' ORDER BY position ASC ");  
$result = json_decode(json_encode($contents), True);
$num=1;
unset($value);
foreach($result as $value)
	{
	$name='contentname'.$num;
	$$name=$value['displaytext'];	
	$link='contentlink'.$num;
	$$link=$value['linkurl']; // $contentslink# 할당
  
	$datepicker='datepicker'.$num;
	$dday2='dday'.$num;
 	$getdate2=$mission->$dday2;
 
	$grade='grade'.$num;
	//$hours='hours'.$num;

	if($mission->$grade==NULL)$mission->$grade=0;
	//if($mission->$hours==NULL)$mission->$hours=0;
	 
	$passgrade=$mission->$grade;
	//$duration=$mission->$hours;

	$selectid='basic'.$num;
 	echo '<tr><td>'.$num.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">'.$$name.' </a></td><td>('.$grade.'점)</td>
	<td ><div class="select2-input"><select id='.$selectid.' name="basic" class="form-control" style="width:100px;"><option value="'.$passgrade.'">'.$passgrade.'</option><option value="70">70점</option><option value="75">75점</option><option value="80">80점</option><option value="85">85점</option><option value="90">90점</option><option value="95">95점</option> <option value="100">100점</option></select></div></td>
	<td></td><td><input type="text" class="form-control" id="'.$datepicker.'"  value="'.$getdate2.'"></td></tr>';  

	$num++;	
	} 

/*
	$moduleid2=$DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$examid' "); 
	$exams = $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$moduleid2->instance' ");  
	$result2 = json_decode(json_encode($exams), True);
	$num=1;
	unset($value);
	foreach($result2 as $value)
		{	
		$link='examlink'.$num;
		$$link=$value['linkurl'];  // %examlink# 할당
		$num++;	
		} 
*/

$deadline=$mission->deadline;
echo '<tr><td> </td><td></td><td></td><td></td><td></td>
<td><button type="button" id="alert_updatemissionschedule" onclick="editschedule2(17,'.$studentid.','.$cid.',$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#basic4\').val(),$(\'#basic5\').val(),$(\'#basic6\').val(),$(\'#basic7\').val(),$(\'#basic8\').val(),$(\'#basic9\').val(),$(\'#basic10\').val(),$(\'#basic11\').val(),$(\'#basic12\').val(),$(\'#basic13\').val(),
$(\'#datepicker1\').val(),$(\'#datepicker2\').val(),$(\'#datepicker3\').val(),$(\'#datepicker4\').val(),$(\'#datepicker5\').val(),$(\'#datepicker6\').val(),$(\'#datepicker7\').val(),$(\'#datepicker8\').val(),$(\'#datepicker9\').val(),$(\'#datepicker10\').val(),$(\'#datepicker11\').val(),$(\'#datepicker12\').val(),$(\'#datepicker13\').val())">
<a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'&tb=90">저장하기</a></button></td></tr>																		
									</table>
								</div>
							</div>
							 
						</div> 
								<div class="card-body">
									<div class="row">
										<div class="col-md-12">
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">오늘학습량</span>
														<span class="text-muted"> $3K</span>
													</div>
													<div class="progress" style="height: 2px;">
														<div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="78%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">주간학습량</span>
														<span class="text-muted"> 576</span>
													</div>
													<div class="progress" style="height: 4px;">
														<div class="progress-bar bg-info" role="progressbar" style="width: 65%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">완료율</span>
														<span class="text-muted fw-bold"> 70%</span>
													</div>
													<div class="progress" style="height: 6px;">
														<div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="70%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">잔여시간</span>
														<span class="text-muted fw-bold"> 60%</span>
													</div>
													<div class="progress">
														<div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="60%"></div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
 

					 
				</div>';
	
echo ' 
			</div>
			
		</div>
 
	</div>
';

}      // 수능미션 끝
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
		function editschedule(Eventid,Userid,Subject,Day1,Day2,Day3,Day4,Day5,Day6,Day7,Day8,Day9,Day10,Day11,Day12,Day13,Day14,Day15)
		{		
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			  data : {
		             "eventid":Eventid,
			"userid":Userid,
			"subject":Subject,
			"dday1":Day1,
			"dday2":Day2,
			"dday3":Day3,
			"dday4":Day4,
			"dday5":Day5,
			"dday6":Day6,
			"dday7":Day7,
			"dday8":Day8,
			"dday9":Day9,
			"dday10":Day10,
			"dday11":Day11,
			"dday12":Day12,
			"dday13":Day13,
			"dday14":Day14,
			"dday15":Day15,
		               },
		
		            success:function(data){
			            }
		        })
		 
		setTimeout(function(){
		location.reload();
		},3000); // 3000밀리초 = 3초
		}
 	
		function editschedule2(Eventid,Userid,Subject,Grade1,Grade2,Grade3,Grade4,Grade5,Grade6,Grade7,Grade8,Grade9,Grade10,Grade11,Grade12,Grade13,Day1,Day2,Day3,Day4,Day5,Day6,Day7,Day8,Day9,Day10,Day11,Day12,Day13)
		{		
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			  data : {
		             "eventid":Eventid,
			"userid":Userid,
			"subject":Subject,
			"grade1":Grade1,
			"grade2":Grade2,
			"grade3":Grade3,
			"grade4":Grade4,
			"grade5":Grade5,
			"grade6":Grade6,
			"grade7":Grade7,
			"grade8":Grade8,
			"grade9":Grade9,
			"grade10":Grade10,
			"grade11":Grade11,
			"grade12":Grade12,
			"grade13":Grade13,
			"dday1":Day1,
			"dday2":Day2,
			"dday3":Day3,
			"dday4":Day4,
			"dday5":Day5,
			"dday6":Day6,
			"dday7":Day7,
			"dday8":Day8,
			"dday9":Day9,
			"dday10":Day10,
			"dday11":Day11,
			"dday12":Day12,
			"dday13":Day13,
		               },
		
		            success:function(data){
			            }
		        })
		 
		setTimeout(function(){
		location.reload();
		},3000); // 3000밀리초 = 3초
		}
 
		function inputmission2(Eventid,Userid,Subject){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,   
			  "subject":Subject,	     
		               },
		            success:function(data){
			            }
		        })
		 
		setTimeout(function(){
		location.reload();
		},3000); // 3000밀리초 = 3초
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

		$("#datepicker1").datetimepicker({
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
		$("#datepicker6").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker7").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker8").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker9").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker10").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker11").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker12").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker13").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker14").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker15").datetimepicker({
			format: "YYYY/MM/DD",
		});

		 
		$("#timepicker").datetimepicker({
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
		$("#basic8").select2({
			theme: "bootstrap"
		});
		$("#basic9").select2({
			theme: "bootstrap"
		});
		$("#basic10").select2({
			theme: "bootstrap"
		});
		$("#basic11").select2({
			theme: "bootstrap"
		});
		$("#basic12").select2({
			theme: "bootstrap"
		});
		$("#basic13").select2({
			theme: "bootstrap"
		});
		$("#basic14").select2({
			theme: "bootstrap"
		});
		$("#basic15").select2({
			theme: "bootstrap"
		});
		$("#basic16").select2({
			theme: "bootstrap"
		});
		$("#basic17").select2({
			theme: "bootstrap"
		});
		$("#basic18").select2({
			theme: "bootstrap"
		});
		$("#basic19").select2({
			theme: "bootstrap"
		});
		$("#basic20").select2({
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

echo '
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, don\'t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script>
		var lineChart = document.getElementById(\'lineChart\').getContext(\'2d\'),
		barChart = document.getElementById(\'barChart\').getContext(\'2d\'),
		pieChart = document.getElementById(\'pieChart\').getContext(\'2d\'),
		doughnutChart = document.getElementById(\'doughnutChart\').getContext(\'2d\'),
		radarChart = document.getElementById(\'radarChart\').getContext(\'2d\'),
		bubbleChart = document.getElementById(\'bubbleChart\').getContext(\'2d\'),
		multipleLineChart = document.getElementById(\'multipleLineChart\').getContext(\'2d\'),
		multipleBarChart = document.getElementById(\'multipleBarChart\').getContext(\'2d\'),
		htmlLegendsChart = document.getElementById(\'htmlLegendsChart\').getContext(\'2d\');

		var myLineChart = new Chart(lineChart, {
			type: \'line\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets: [{
					label: "Active Users",
					borderColor: "#1d7af3",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#1d7af3",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [542, 480, 430, 550, 530, 453, 380, 434, 568, 610, 700, 900]
				}]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position: \'bottom\',
					labels : {
						padding: 10,
						fontColor: \'#1d7af3\',
					}
				},
				tooltips: {
					bodySpacing: 4,
					mode:"nearest",
					intersect: 0,
					position:"nearest",
					xPadding:10,
					yPadding:10,
					caretPadding:10
				},
				layout:{
					padding:{left:15,right:15,top:15,bottom:15}
				}
			}
		});

		var myBarChart = new Chart(barChart, {
			type: \'bar\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets : [{
					label: "Sales",
					backgroundColor: \'rgb(23, 125, 255)\',
					borderColor: \'rgb(23, 125, 255)\',
					data: [3, 2, 9, 5, 4, 6, 4, 6, 7, 8, 7, 4],
				}],
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				},
			}
		});

		var myPieChart = new Chart(pieChart, {
			type: \'pie\',
			data: {
				datasets: [{
					data: [50, 35, 15],
					backgroundColor :["#1d7af3","#f3545d","#fdaf4b"],
					borderWidth: 0
				}],
				labels: [\'New Visitors\', \'Subscribers\', \'Active Users\'] 
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position : \'bottom\',
					labels : {
						fontColor: \'rgb(154, 154, 154)\',
						fontSize: 11,
						usePointStyle : true,
						padding: 20
					}
				},
				pieceLabel: {
					render: \'percentage\',
					fontColor: \'white\',
					fontSize: 14,
				},
				tooltips: false,
				layout: {
					padding: {
						left: 20,
						right: 20,
						top: 20,
						bottom: 20
					}
				}
			}
		})

		var myDoughnutChart = new Chart(doughnutChart, {
			type: \'doughnut\',
			data: {
				datasets: [{
					data: [10, 20, 30],
					backgroundColor: [\'#f3545d\',\'#fdaf4b\',\'#1d7af3\']
				}],

				labels: [
				\'Red\',
				\'Yellow\',
				\'Blue\'
				]
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: \'bottom\'
				},
				layout: {
					padding: {
						left: 20,
						right: 20,
						top: 20,
						bottom: 20
					}
				}
			}
		});

		var myRadarChart = new Chart(radarChart, {
			type: \'radar\',
			data: {
				labels: [\'Running\', \'Swimming\', \'Eating\', \'Cycling\', \'Jumping\'],
				datasets: [{
					data: [20, 10, 30, 2, 30],
					borderColor: \'#1d7af3\',
					backgroundColor : \'rgba(29, 122, 243, 0.25)\',
					pointBackgroundColor: "#1d7af3",
					pointHoverRadius: 4,
					pointRadius: 3,
					label: \'Team 1\'
				}, {
					data: [10, 20, 15, 30, 22],
					borderColor: \'#716aca\',
					backgroundColor: \'rgba(113, 106, 202, 0.25)\',
					pointBackgroundColor: "#716aca",
					pointHoverRadius: 4,
					pointRadius: 3,
					label: \'Team 2\'
				},
				]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: \'bottom\'
				}
			}
		});

		var myBubbleChart = new Chart(bubbleChart,{
			type: \'bubble\',
			data: {
				datasets:[{
					label: "Car", 
					data:[{x:25,y:17,r:25},{x:30,y:25,r:28}, {x:35,y:30,r:8}], 
					backgroundColor:"#716aca"
				},
				{
					label: "Motorcycles", 
					data:[{x:10,y:17,r:20},{x:30,y:10,r:7}, {x:35,y:20,r:10}], 
					backgroundColor:"#1d7af3"
				}],
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position: \'bottom\'
				},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}],
					xAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				},
			}
		});

		var myMultipleLineChart = new Chart(multipleLineChart, {
			type: \'line\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets: [{
					label: "Python",
					borderColor: "#1d7af3",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#1d7af3",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [30, 45, 45, 68, 69, 90, 100, 158, 177, 200, 245, 256]
				},{
					label: "PHP",
					borderColor: "#59d05d",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#59d05d",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [10, 20, 55, 75, 80, 48, 59, 55, 23, 107, 60, 87]
				}, {
					label: "Ruby",
					borderColor: "#f3545d",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#f3545d",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [10, 30, 58, 79, 90, 105, 117, 160, 185, 210, 185, 194]
				}]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position: \'top\',
				},
				tooltips: {
					bodySpacing: 4,
					mode:"nearest",
					intersect: 0,
					position:"nearest",
					xPadding:10,
					yPadding:10,
					caretPadding:10
				},
				layout:{
					padding:{left:15,right:15,top:15,bottom:15}
				}
			}
		});

		var myMultipleBarChart = new Chart(multipleBarChart, {
			type: \'bar\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets : [{
					label: "First time visitors",
					backgroundColor: \'#59d05d\',
					borderColor: \'#59d05d\',
					data: [95, 100, 112, 101, 144, 159, 178, 156, 188, 190, 210, 245],
				},{
					label: "Visitors",
					backgroundColor: \'#fdaf4b\',
					borderColor: \'#fdaf4b\',
					data: [145, 256, 244, 233, 210, 279, 287, 253, 287, 299, 312,356],
				}, {
					label: "Pageview",
					backgroundColor: \'#177dff\',
					borderColor: \'#177dff\',
					data: [185, 279, 273, 287, 234, 312, 322, 286, 301, 320, 346, 399],
				}],
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position : \'bottom\'
				},
				title: {
					display: true,
					text: \'Traffic Stats\'
				},
				tooltips: {
					mode: \'index\',
					intersect: false
				},
				responsive: true,
				scales: {
					xAxes: [{
						stacked: true,
					}],
					yAxes: [{
						stacked: true
					}]
				}
			}
		});

		// Chart with HTML Legends

		var gradientStroke = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientStroke.addColorStop(0, \'#177dff\');
		gradientStroke.addColorStop(1, \'#80b6f4\');

		var gradientFill = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientFill.addColorStop(0, "rgba(23, 125, 255, 0.7)");
		gradientFill.addColorStop(1, "rgba(128, 182, 244, 0.3)");

		var gradientStroke2 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientStroke2.addColorStop(0, \'#f3545d\');
		gradientStroke2.addColorStop(1, \'#ff8990\');

		var gradientFill2 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientFill2.addColorStop(0, "rgba(243, 84, 93, 0.7)");
		gradientFill2.addColorStop(1, "rgba(255, 137, 144, 0.3)");

		var gradientStroke3 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientStroke3.addColorStop(0, \'#fdaf4b\');
		gradientStroke3.addColorStop(1, \'#ffc478\');

		var gradientFill3 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientFill3.addColorStop(0, "rgba(253, 175, 75, 0.7)");
		gradientFill3.addColorStop(1, "rgba(255, 196, 120, 0.3)");

		var myHtmlLegendsChart = new Chart(htmlLegendsChart, {
			type: \'line\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets: [ {
					label: "Subscribers",
					borderColor: gradientStroke2,
					pointBackgroundColor: gradientStroke2,
					pointRadius: 0,
					backgroundColor: gradientFill2,
					legendColor: \'#f3545d\',
					fill: true,
					borderWidth: 1,
					data: [154, 184, 175, 203, 210, 231, 240, 278, 252, 312, 320, 374]
				}, {
					label: "New Visitors",
					borderColor: gradientStroke3,
					pointBackgroundColor: gradientStroke3,
					pointRadius: 0,
					backgroundColor: gradientFill3,
					legendColor: \'#fdaf4b\',
					fill: true,
					borderWidth: 1,
					data: [256, 230, 245, 287, 240, 250, 230, 295, 331, 431, 456, 521]
				}, {
					label: "Active Users",
					borderColor: gradientStroke,
					pointBackgroundColor: gradientStroke,
					pointRadius: 0,
					backgroundColor: gradientFill,
					legendColor: \'#177dff\',
					fill: true,
					borderWidth: 1,
					data: [542, 480, 430, 550, 530, 453, 380, 434, 568, 610, 700, 900]
				}]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					display: false
				},
				tooltips: {
					bodySpacing: 4,
					mode:"nearest",
					intersect: 0,
					position:"nearest",
					xPadding:10,
					yPadding:10,
					caretPadding:10
				},
				layout:{
					padding:{left:15,right:15,top:15,bottom:15}
				},
				scales: {
					yAxes: [{
						ticks: {
							fontColor: "rgba(0,0,0,0.5)",
							fontStyle: "500",
							beginAtZero: false,
							maxTicksLimit: 5,
							padding: 20
						},
						gridLines: {
							drawTicks: false,
							display: false
						}
					}],
					xAxes: [{
						gridLines: {
							zeroLineColor: "transparent"
						},
						ticks: {
							padding: 20,
							fontColor: "rgba(0,0,0,0.5)",
							fontStyle: "500"
						}
					}]
				}, 
				legendCallback: function(chart) { 
					var text = []; 
					text.push(\'<ul class="\' + chart.id + \'-legend html-legend">\'); 
					for (var i = 0; i < chart.data.datasets.length; i++) { 
						text.push(\'<li><span style="background-color:\' + chart.data.datasets[i].legendColor + \'"></span>\'); 
						if (chart.data.datasets[i].label) { 
							text.push(chart.data.datasets[i].label); 
						} 
						text.push(\'</li>\'); 
					} 
					text.push(\'</ul>\'); 
					return text.join(\'\'); 
				}  
			}
		});

		var myLegendContainer = document.getElementById("myChartLegend");

		// generate HTML legend
		myLegendContainer.innerHTML = myHtmlLegendsChart.generateLegend();

		// bind onClick event to all LI-tags of the legend
		var legendItems = myLegendContainer.getElementsByTagName(\'li\');
		for (var i = 0; i < legendItems.length; i += 1) {
			legendItems[i].addEventListener("click", legendClickCallback, false);
		}

	</script>

';

?>