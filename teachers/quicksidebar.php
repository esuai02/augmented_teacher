<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
    
$teachers=$DB->get_records_sql("SELECT * FROM mdl_user_info_data where data LIKE 'teacher' OR data LIKE 'manager' ");
$teacherlist= json_decode(json_encode($teachers), True);
unset($user);

$monitoring=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$mntr1=$monitoring->mntr1;
$mntr2=$monitoring->mntr2;
$mntr3=$monitoring->mntr3;

$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='46' "); 
$location=$info->data;
 
foreach($teacherlist as $user)
{
$userid=$user['userid'];
$name= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$userid' "); 
if($name->suspended==0)
	{
	$info2=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='46' "); 
	$location2=$info2->data;
	if($name->institution!==$academy)continue;
 
		$checkstatus=''; 
		if($mntr1==$userid)$checkstatus='checked'; 
		elseif($mntr2==$userid)$checkstatus='checked'; 
		elseif($mntr3==$userid)$checkstatus='checked'; 

		$teachername=$name->firstname.$name->lastname;
		if($USER->id==2 || $USER->id==632 || $USER->id==13)$messagelinks.=' <tr><td><input type="checkbox"  '.$checkstatus.'  onclick="monitoring(9,'.$userid.','.$teacherid.',this.checked)"/></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/chainreactionOn.php?id='.$userid.'&tb=7&mode=today" target="_blank">'.$teachername.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=25></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$userid.'&tb=7" target="_blank">시간표</a></td><td>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/time_accupancy.php?id='.$userid.'&tb=7" target="_blank">점유</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/CJNalignment.php?id='.$userid.'&gtype=term" target="_blank">목표</a></td>
		<td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/chainreactionOn.php?id='.$userid.'" target="_blank">자가</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/restore_hp.php?id='.$userid.'" target="_blank">진단</a></td></tr>';
	 
	}
}
 /////////////////////////// end of code snippet ///////////////////////////
echo '
<script>
function monitoring(Eventid,Userid,Teacherid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("적용되었습니다", {buttons: false,timer: 500});
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "teacherid":Teacherid,
    		                  "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		}
</script>
 		<div class="quick-sidebar">
			<a href="#" class="close-quick-sidebar">
				<i class="flaticon-cross"></i>
			</a>
			<div class="quick-sidebar-wrapper">
				<ul class="nav nav-tabs nav-line nav-color-primary" role="tablist">
					<li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#tasks" role="tab" aria-selected="false">오늘 시간표</a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#messages" role="tab" aria-selected="false"> 협업링크</a> </li>
					
				</ul>
				<div class="tab-content mt-10">

					<div class="tab-pane fade show active" id="tasks" role="tabpanel">
								 
							<div class="quick-wrapper">
								<div class="quick-scroll scrollbar-outer">
									<div class="quick-content contact-content">
										<span class="category-title"> </span>
										<div class="contact-list">
										<iframe height="100%" width="100%" src="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$teacherid.'&tb=7&tablemode=today" ></iframe>
										</div>
									</div>
								</div>
							</div>
					</div>
					<div class="tab-pane fade" id="messages" role="tabpanel">
						 
					<div class="quick-wrapper">
						<div class="quick-scroll scrollbar-outer">
							<div class="quick-content contact-content">
								<span class="category-title"> </span>
								<div class="contact-list">
									 <table width=100%> '.$messagelinks.'</table>
								</div>
							</div>
						</div>
					</div>
				 

			</div>
				</div>
			</div>
		</div>
 
	</div>
 ';
?>
