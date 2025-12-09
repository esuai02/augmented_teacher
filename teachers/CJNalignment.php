<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
include("navbar.php");
$gtype=$_GET["gtype"];

$timecreated=time();
 
/////////////////////////// end of code snippet ///////////////////////////
echo ' 

		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
							
echo '
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 700px;
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
// a:visited { color: blue; text-decoration: none;}
  
</style>';

echo '
<style>
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 400px;
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
  width: 800px;
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
  
///////////////// ajax to fire popup in a real time by tslee ////////////////////////###################################
//<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
echo '
<script>

function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
    var checkimsi = 0;
    if(Checkvalue==true){
        checkimsi = 1;
    }
   $.ajax({
        url: "check.php",
        type: "POST",
        dataType: "json",
        data : {"userid":Userid,
                "questionid":Questionid,
                "attemptid":Attemptid,
                "checkimsi":checkimsi,
                 "eventid":Eventid,
               },
        success: function (data){  
        }
    });
}

</script>';
////////////////////////////////////////////end of ajax//////////////////////////////////////////////
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
$sssskey= sesskey();  
$amonthago=time()-604800*12;
$aweekago=time()-604800-3600;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE institution LIKE '$academy' AND suspended=0 AND  (lastaccess> '$amonthago' OR timecreated> '$amonthago' ) AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
 
$nusers=count($mystudents);
$userlist= json_decode(json_encode($mystudents), True);
/////////////////////////////// /////////////////////////////// begin of no whiteboard submission /////////////////////////////// /////////////////////////////// 
unset($user); 
$termgoal='분기목표';
$weekgoal='주간목표';
$todaygoal='오늘목표';
if($gtype==='term'){$termgoal='<b style="color:blue;"> 분기목표 </b>';$goaltype='분기목표';}
elseif($gtype==='week'){$weekgoal='<b style="color:blue;"> 주간목표 </b>';$goaltype='주간목표';}
elseif($gtype==='today'){$todaygoal='<b style="color:blue;"> 오늘목표 </b>';$goaltype='오늘목표';}
echo '<table align="left"><tr><td width=15%></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/CJNalignment.php?id='.$teacherid.'&gtype=term">'.$termgoal.'</a></td><td width=15%></td> <td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/CJNalignment.php?id='.$teacherid.'&gtype=week">'.$weekgoal.'</a></td> <td width=15%></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/CJNalignment.php?id='.$teacherid.'&gtype=today">'.$todaygoal.'</a></td> </tr></table><br><hr>';
 
echo '<table align=center width=100%><tr> <th></th><th></th><th> </th> <th width=40%>'.$goaltype.'</th><th width=40%>오늘목표 설정규칙</th></tr><tr> <th><hr></th><th><hr></th><th><hr></th> <th width=40%><hr></th><th width=40%><hr></th></tr>
<tr><td> </td><td> </td><td>   </td> </tr>'; 
 
foreach($userlist as $user)
	{
	$userid=$user['id'];
	$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
	$role=$userrole->role;
	if($role!=='student')continue;
	$firstname=$user['firstname'];
	$lastname=$user['lastname']; //
	if($gtype==='term') 
		{
		$getgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$userid' AND hide=0 AND plantype LIKE '분기목표' AND deadline >'$timecreated' ORDER by deadline DESC LIMIT 1");	
		$goaltext=$getgoal->memo;
		$dateString = date("Y-m-d",$getgoal->deadline);
		$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
		$ruletext='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.'&nweek=4&eid='.$schedule->id.'"target="_blank">편집</a> '.$schedule->memo8;
		$stateimg='</td><td>';
		$checktime=$getgoal->deadline-604800*3;
		if($getgoal->deadline-time()<604800*2 && $getgoal->deadline-time() > -604800 * 24)$stateimg='</td><td>임박<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width=15>';
		$goalstep = $DB->get_record_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$userid' AND hide=0 AND (plantype LIKE '개념미션' OR plantype LIKE '심화미션' OR plantype LIKE '내신미션' OR plantype LIKE '수능미션') AND deadline > '$checktime' AND deadline < '$getgoal->deadline' ORDER by deadline DESC LIMIT 1");	
		if($goalstep->id==NULL && $getgoal->deadline-time()>604800*2 )$stateimg.='<b style="color:red;">단계 미설정</b>';
		if($getgoal->id==NULL)$users1.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$userid.'"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 
		elseif($goaltext==NULL)$users2.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$userid.'"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 
		elseif($getgoal->timecreated<time()-604800*8)$users3.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$userid.'"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 
		else $users4.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span> '.$stateimg.'</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$userid.'"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 

		 
		}
	elseif($gtype==='week') 
		{
		$getgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid'  AND type LIKE '주간목표' AND timecreated>'$aweekago'   ORDER by id DESC LIMIT 1");	
		$goaltext=$getgoal->text;
		$dateString = date("Y-m-d",$getgoal->timecreated);
		$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
		$ruletext='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.'&nweek=4&eid='.$schedule->id.'"target="_blank">편집</a> '.$schedule->memo8;
		$stateimg='</td><td>';
		if($timecreated-$getgoal->timecreated>604800+3600)$stateimg='</td><td><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width=15>';
		if($getgoal->id==NULL)$users1.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'&gtype=주간목표"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 
		else $users2.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'&gtype=주간목표"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 

		}
	elseif($gtype==='today') 
		{
		$getgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid'  AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$aweekago'   ORDER by id DESC LIMIT 1");	
		$goaltext=$getgoal->text;
		$dateString = date("Y-m-d",$getgoal->timecreated);
		$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
		$ruletext='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.'&nweek=4&eid='.$schedule->id.'"target="_blank">편집</a> '.$schedule->memo8;
		$stateimg='</td><td>';
		 
		if($timecreated-$getgoal->timecreated <43200)$users1.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'&gtype=오늘목표"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 
		else $users2.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a> '.$stateimg.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'&gtype=오늘목표"target="_blank">'.$dateString.'</a></td> <td>'.$goaltext.'</td><td>'.$ruletext.'</td> </tr>'; 

		}

 	}

echo '<tr><td style="color:bule;"><b> &nbsp; # '.$goaltype.' 점검</b><br> </td><td></td><td></td><td></td><td></td></tr>'.$users1.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users2.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users4.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr></table>';

echo '</div><div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
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
	<script src="../assets/js/demo.js"></script>
</body>';
?>
