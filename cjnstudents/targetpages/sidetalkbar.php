<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
    
$teachers=$DB->get_records_sql("SELECT * FROM mdl_user_info_data where data LIKE '%teacher%'  ");
$teacherlist= json_decode(json_encode($teachers), True);
unset($user);

foreach($teacherlist as $user)
{
$userid=$user['userid'];
$name= $DB->get_record_sql("SELECT lastname, firstname, suspended FROM mdl_user WHERE id='$userid' "); 
if($name->suspended==0)
	{
	$teachername=$name->firstname.$name->lastname;
	$messagelinks.=' <div class="user-data"><span class="name"><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.'" target="_blank">'.$teachername.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=25></a>&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/time_accupancy.php?id='.$userid.'&tb=7" target="_blank">시간표 현황</a> </span></div><hr>';
	}
}

/////////////////////////// end of code snippet ///////////////////////////
echo '
<script>
var displaytitle="KAIST TOUCH MATH";
var displaytext="Updating real-time dashboard";

$.notify({
	icon: "flaticon-alarm-1",
	title: displaytitle,
	message: displaytext,
},{
	type: "info",
	placement: {
		from: "bottom",
		align: "right"
	},
	time: 1000,
});
</script>

 		<div class="quick-sidebar">
			<a href="#" class="close-quick-sidebar">
				<i class="flaticon-cross"></i>
			</a>
			<div class="quick-sidebar-wrapper">
				<ul class="nav nav-tabs nav-line nav-color-primary" role="tablist">
					<li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#messages" role="tab" aria-selected="false">Messages</a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#tasks" role="tab" aria-selected="false">Tasks</a> </li>
				</ul>
				<div class="tab-content mt-3">
					<div class="tab-pane fade show active" id="messages" role="tabpanel">
						 
							<div class="quick-wrapper">
								<div class="quick-scroll scrollbar-outer">
									<div class="quick-content contact-content">
										<span class="category-title">  </span>
										<div class="contact-list">
											<div class="user"><a href="#"> '.$messagelinks.'</a></div>
										</div>
									</div>
								</div>
							</div>
						 
						<div class="messages-wrapper">
							<div class="messages-title">
								<div class="user">
									<img src="../assets/img/chadengle.jpg" alt="chad">
									<span class="name">'.$teachername.'의 메세지 화면으로 이동하였습니다.</span>
									<span class="last-active"> </span>
								</div>
								<button class="return">
									<i class="flaticon-left-arrow-3"></i>
								</button>
							</div>
							
				 
						</div>
					</div>
					<div class="tab-pane fade" id="tasks" role="tabpanel">
						<div class="tasks-wrapper">
							<div class="tasks-scroll scrollbar-outer">
								<div class="tasks-content">
									    
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