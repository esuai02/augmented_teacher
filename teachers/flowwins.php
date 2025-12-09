<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
if($USER->id==$teacherid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,eventid,page,timecreated) VALUES('$USER->id',72,'flowwins','$timecreated')");

$tlastaccess=time()-604800*30;
 
$halfdayago=time()-43200;
$aweekago=time()-604800;
$weeksago3=time()-604800*3;

$amonthago6=time()-604800*30;
$timestart=date("Y-m-d", time());
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($role==='student')
{
echo '권한이 없습니다.';
exit();
}

$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended=0 AND institution LIKE '$academy' AND lastaccess> '$amonthago6' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
 
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$studentname=$username->firstname.$username->lastname;
	$thisuser= $DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog WHERE  userid='$userid' ORDER  BY id DESC LIMIT 1 ");
	$flowrate=$thisuser->flow1+$thisuser->flow2+$thisuser->flow3+$thisuser->flow4+$thisuser->flow5+$thisuser->flow6+$thisuser->flow7+$thisuser->flow8;

	$ctalk=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE  creator LIKE '$userid'  AND timecreated>'$weeksago3' ORDER BY id DESC LIMIT 1 ");  
	$sharetext=$ctalk->text;
	$sharetext=iconv_substr($sharetext, 0, 50, "utf-8");
	$type=$ctalk->type;
	$wboardid=$ctalk->wboardid;
	$tcreated1=date("m월d일 h:i A", $ctalk->timecreated);   
	$bubblestr='bubble';

	$ctalk2=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE  creator LIKE '$userid' AND userid NOT LIKE '$userid' AND timecreated>'$weeksago3' ORDER BY id DESC LIMIT 1 ");  

	$getauthor=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1 "); 
	$contentsid=$getauthor->contentsid;
	$seewb='talk';  // style="white-space: nowrap; text-overflow: ellipsis;" 
 	if($user['talkid']!=7)$seewb='talk';
	else $seewb=='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$userid.'&mode=peer"target="_blank">자세히</a>';
	$cognitivetalk='<table><tr><td><div class="'.$bubblestr.'">(<b><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type='.$type.'"target="_blank">'.$type.'</a></b>)'.$sharetext.' '.date("m/d", $ctalk->timecreated).' '.$seewb.'</div></td></tr></table>';

	$insertrow='<tr><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a></td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=목표"target="_blank">목</a>'.$thisuser->flow1.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=순서"target="_blank">순</a>'.$thisuser->flow2.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=기억"target="_blank">기</a>'.$thisuser->flow3.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=몰입"target="_blank">몰</a>'.$thisuser->flow4.'</td>
	<td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=발상"target="_blank">발</a>'.$thisuser->flow5.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=해석"target="_blank">해</a>'.$thisuser->flow6.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=숙달"target="_blank">숙</a>'.$thisuser->flow7.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=효율"target="_blank">효</a>'.$thisuser->flow8.'</td><td valign=top>('.$flowrate.'/40)</td><td width=60%>'.$cognitivetalk.'</td></tr>';

	$pin=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE  creator LIKE '$userid' AND pinned=1 AND timecreated>'$weeksago3' ORDER BY id ASC LIMIT 1 ");  
	if($pin->id!=NULL)
		{
		$deadline=$pin->timemodified+604800*2-86400;
		$deadlinestr=date("m/d", $pin->timemodified+604800*2);
		if($timecreated-$deadline>0)$dday='<b style="color:red;">'.$deadlinestr.'</b>'; // 점검실시
		else $dday='<b style="color:green;">'.$deadlinestr.'</b>'; 

		$conversationdate='<table><tr><td><div class="'.$bubblestr.'">(<b><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type='.$type.'"target="_blank">'.$type.'</a></b>)'.$sharetext.' 데드라인 ( '.$dday.' )</div></td></tr></table>';  
 
		$pinnedtalk='<tr><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a></td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=목표"target="_blank">목</a>'.$thisuser->flow1.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=순서"target="_blank">순</a>'.$thisuser->flow2.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=기억"target="_blank">기</a>'.$thisuser->flow3.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=몰입"target="_blank">몰</a>'.$thisuser->flow4.'</td>
		<td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=발상"target="_blank">발</a>'.$thisuser->flow5.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=해석"target="_blank">해</a>'.$thisuser->flow6.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=숙달"target="_blank">숙</a>'.$thisuser->flow7.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=효율"target="_blank">효</a>'.$thisuser->flow8.'</td><td valign=top>('.$flowrate.'/40)</td><td width=60%>'.$conversationdate.'</td></tr>';
		$pinnedlist.=$pinnedtalk;
		}
	elseif($ctalk->timecreated >$halfdayago)
		{
		if($ctalk->timecreated > $ctalk2->timecreated)$flowlist01.=$insertrow;
		else $flowlist02.=$insertrow;
		}
	elseif($flowrate>30)$flowlist1.=$insertrow;
	elseif($flowrate>20)$flowlist2.=$insertrow; 
	elseif($flowrate>15)$flowlist3.=$insertrow;
	elseif($flowrate>10)$flowlist4.=$insertrow;
	elseif($flowrate>5)$flowlist5.=$insertrow;
	else $flowlist6.=$insertrow;
	}
echo '
		<div class="main-panel">
			<div class="content"> 
				<div class="container-fluid"><table align=center><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/chainreactionOn.php?id='.$teacherid.'" accesskey="r"><b>동작기억 연쇄작용 일으키기</b></a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/selfreactionOn.php?id='.$teacherid.'">자가피드백 현황</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/flowwins.php?id='.$teacherid.'">메타인지</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id=2&tb=7"target="_blank">시간표</a></td></tr></table><hr> 		
					<table align=center width=100%>
					<tr><td valign=top align=center>새메세지<br><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1665242268.png" width=100px></td><td><table align=center width=100%>'.$flowlist01.'</table></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
					<tr><td valign=top align=center>상담예약<br><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666542033.png" width=100px></td><td><table align=center width=100%>'.$pinnedlist.'</table></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
					<tr><td valign=top align=center>답변완료<br><img src="https://mathking.kr/Contents/IMAGES/sendmessage.png" width=80px></td><td><table align=center width=100%>'.$flowlist02.'</table></td></tr><tr><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td></tr>
					<tr><td valign=top align=center>수학좀비1<br><img src="https://mathking.kr/Contents/IMAGES/mathzombie.jpg" width=100px></td><td><table align=center width=100%>'.$flowlist6.'</table></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
					<tr><td valign=top align=center>수학좀비2<br><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1664926073.png" width=100px></td><td><table align=center width=100%>'.$flowlist5.'</table></td></tr><tr><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td></tr>
					<tr><td valign=top align=center>인간학생1<br><img src="https://mathking.kr/Contents/IMAGES/humanstate.png" width=100px></td><td><table align=center width=100%>'.$flowlist4.'</table></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
					<tr><td valign=top align=center>인간학생2<br><img src="https://mathking.kr/Contents/IMAGES/humanstate.png" width=100px></td><td><table align=center width=100%>'.$flowlist3.'</table></td></tr><tr><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td><td><hr style="border: solid 3px grey;"></td></tr>
					<tr><td valign=top align=center>초지능계1<br><img src="https://mathking.kr/Contents/IMAGES/superintelligence.jpg" width=100px></td><td><table align=center width=100%>'.$flowlist2.'</table></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
					<tr><td valign=top align=center>초지능계2<br><img src="https://mathking.kr/Contents/IMAGES/superintelligence.jpg" width=100px></td><td><table align=center width=100%>'.$flowlist1.'</table></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
					</table>
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
	<script src="../assets/js/demo.js"></script>
';
?>
