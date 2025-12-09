<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php"); 
 
$daybegin=required_param('begin', PARAM_INT); 
$dayend=required_param('end', PARAM_INT); 

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$fullname=$username->firstname.$username->lastname;
 
   
 
		/////////////////////////////// prepare question icon array   (not applied 2019.12.22) /////////////////////

		$subject=$DB->get_record_sql("SELECT data AS subject FROM mdl_user_info_data where userid='$teacherid' and fieldid='57' ");
		if($subject->subject==='MATH')$contains='%MX%';
		elseif($subject->subject==='SCIENCE') $contains='%SCIENCE%';

		$ipageaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtime,contextinstanceid, objectid FROM mdl_logstore_standard_log WHERE userid='$studentid' AND objecttable='icontent_pages' "); 
		$ipageaccess->maxtime=(time()-$ipageaccess->maxtime)/60;
		if($ipageaccess->maxtime>120) $ipageaccess->maxtime=999;

		/////////////////////////////// evaluate speed of average speed of question attempts + show array of question attempts icons /////////////////////

		$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
		LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
		WHERE mdl_question.name LIKE '$contains' AND mdl_question_attempt_steps.userid='$studentid' AND  state !='todo' AND  state !='gaveup'  ORDER BY mdl_question_attempt_steps.timecreated DESC LIMIT 10");
		$result1 = json_decode(json_encode($questionattempts), True);
 
		$marks=NULL;
		unset($value);
		$ntry=0; 
		$ninit=0;
		foreach(array_reverse($result1) as $value)
			{
			$state=NULL;
			$helplist=NULL;
			$timediff=time()-$value['timecreated'];

			if($timediff<3600 && $ninit==0)
				{
				//echo $timediff.'<br>';
				$tperiod_init=$timediff/60;
				$ninit=1;
				}
			$tperiod=$tperiod_init-$timediff/60;
			$useridtmp=$studentid;
			$qidtmp=$value['questionid'];
			$status='';
			$attemptid=$value['id'];

			if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
			if(strpos($value['questiontext'], '/MY')!= false&&strpos($value['questiontext'], 'slowhw')!= false)$value['questiontext']='<p> MY A step </p>';
			if(strpos($value['questiontext'], 'shutterstock')!= false)
				{
				$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '{ifminassistant}'));   
				$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
				}
			if($value['state']==gradedright && $timediff<3600)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				$ntry++;
				}
			elseif($value['state']==gradedright && $timediff<259200)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right2.png" width=20></a>&nbsp;';
				}
			elseif($value['state']==gradedpartial && $timediff<3600)
				{  
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				$ntry++;
				}
			elseif($value['state']==gradedpartial && $timediff<259200)
				{   
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial2.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				}
			elseif($value['state']==gradedwrong && $timediff<3600)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;'; 
				$ntry++;
				}
			elseif($value['state']==gradedwrong && $timediff<259200)
				{ 
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong2.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				}
			elseif($value['state']==complete && $timediff<3600)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
				$ntry++;
				}
			elseif($value['state']==complete && $timediff<259200)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete2.png" width=20></a>&nbsp;';
				}
			$marks.=$state; 
			}
 
		// recent quiz list ********************************************************************************************** 
		 
		$recentquiz = $DB->get_records_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz.name AS name, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades,mdl_course_modules.id AS quizid FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$studentid'   ORDER BY mdl_quiz_attempts.timestart DESC LIMIT 3");
		$quizrslt= json_decode(json_encode($recentquiz), True);
		$quizinfo=NULL;
		unset($value);
		foreach(array_reverse($quizrslt) as $value)
			{
			$qzid=$value['id'];
			$qzname=$value['name'];
			$qzgrade=round($value['sgrades']/$value['tgrades']*100,0); 
			$quizinfo.='<td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$qzid.' " target="_blank">'.substr($qzname,0,17).'('.$qzgrade.get_string('points', 'local_augmented_teacher').')</a></td>';
			}
			/////////////////////////////// 2-5. prepare tooltip for personal information
			$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
			$missions=$DB->get_records_sql("SELECT * FROM mdl_abessi_mission where userid='$studentid'  ORDER BY timecreated DESC LIMIT 4 "); 
			$result_missions= json_decode(json_encode($missions), True); 
			$todaygoal=$DB->get_record_sql("SELECT * FROM mdl_abessi_today where userid='$studentid' ORDER BY timecreated DESC LIMIT 1  "); 
			$time2=time()-43200;

			if($todaygoal->timecreated<$time2)$todaygoal->text='User didn\'t input today\'s goal';
  
			$mission4=NULL;
			unset($value);
			foreach($result_missions as $value)
				{
				$mission4.=$value['msntype'].'|'.$value['subject'].'|'.$value['text'].'|'.$value['deadline'].'<br>';
				}
			if($lastcomment2<21) $plan='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'" target="_blank">Schedule</a>';
			else $plan='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'" target="_blank"><span class="" style="color: rgb(239, 69, 64);">Schedule</span></a>';
			$goal='GOAL';
			if($lastcomment<14) $mission='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank" >Missions</a>';
			else $mission='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank" ><span class="" style="color: rgb(239, 69, 64);">Missions</span></a>';
			
			if($lastlog<60*1)$num=1;
			elseif($lastlog<60*2)$num=2;
			elseif($lastlog<60*3)$num=3;
			elseif($lastlog<60*4)$num=4;			
			elseif($lastlog<60*5)$num=5;
			elseif($lastlog<60*7)$num=6;
			elseif($lastlog<60*10)$num=7;
			elseif($lastlog<60*15)$num=8;
			elseif($lastlog<60*20)$num=9;
			elseif($lastlog<60*25)$num=10;
			elseif($lastlog<60*30)$num=11;
			elseif($lastlog<60*40)$num=12;
			elseif($lastlog<60*50)$num=13;
			elseif($lastlog<60*60)$num=14;
			elseif($lastlog<60*90)$num=15;
			elseif($lastlog<60*120)$num=16;
			elseif($lastlog<60*150)$num=17;
			elseif($lastlog<60*180)$num=18;
			else $num=19;

			$today='today'.$num;
			$$today.= '<tr><td><img src="'.$imgtoday.'" width=20><a href="https://mathking.kr/moodle/mod/hotquestion/view.php?id=76943" target="_blank" ><img src='.$imgtoday2.' width=20></a><div class="tooltip2"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.' " target="_blank" >'.$mark5.'</a><span class="tooltiptext2">    
			<br><h5><span class="" align="right"  style="color: rgb(51, 51, 251);">KAIST TOUCH MATH ::: '.$mission.' + '.$plan.' + '.$goal.'  ('.round($Ttime->totaltime,0).' h / '.$weektotal.' h) </span></h5>		 
			<table align="center" style="width: 100%;">
      	                         <caption></caption>
                    		<thead>
                      		 <tr>
                            	<th scope="col"></th>
                           	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('mon', 'report_log').'</span></b></h5></th>
                            	<th scope="col" align="left" ><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('tue', 'report_log').'</span></b></h5></th>
                            	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('wed', 'report_log').'</span></b></h5></th>
                            	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('thu', 'report_log').'</span></b></h5></th>
                            	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('fri', 'report_log').'</span></b></h5></th>
                            	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(42, 100, 211);">'.get_string('sat', 'report_log').'</span></b></h5></th>
                            	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(239, 69, 64);">'.get_string('sun', 'report_log').'</span></b></h5></th>
                        		</tr>
                    		</thead><tbody>
 			<tr>
                            	<td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('begin', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start1.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start2.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start3.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start4.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start5.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start6.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start7.'</td>
                        		</tr>
                        		<tr>
                            	<td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('time', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><span style="font-size: 12.44px;">'.$schedule->duration1.'</span></td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration2.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration3.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration4.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration5.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration6.'</td>
                            	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration7.'</td>
                        		</tr></tbody>
                		</table><hr><h5><span class="" align="center"  style="color: rgb(51, 51, 251);">* '.get_string('Today', 'local_augmented_teacher').$todaygoal->text.' </span></h5>'.$mission4.'<hr></div>      
			</td><td>'.round($lastlog/60,0).'"</td><td>'.$v_quiz.'</td><td>'.$Qnum1.'/'.$mark4.'</td><td>'.$location2.'</td><td style="text-align: right;">'.$marks.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
			<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td><td>'.$quizinfo.'</td><td>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_submitted&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$studentid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=18></a></td><td>'.$mark7.'</td>
			<td>|<a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$studentid.' " target="_blank" ><img src=https://www.freeiconspng.com/uploads/open-book-icon-32.png width=24></a><a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$ipageaccess->contextinstanceid.'&pageid='.$ipageaccess->objectid.' " target="_blank" >'.round($ipageaccess->maxtime,0).get_string('timeago', 'local_augmented_teacher').'</a></td></tr>';
	 
echo $today1.$today2.$today3.$today4.$today5.$today6.$today7.$today8.$today9.$today10.$today11.$today12.$today13.$today14.$today15.$today16.$today17.$today18.$today19;	
echo '</tbody></table><hr>'; 
echo '<table style="width: 100%;"><tbody>'.$active.'</tbody></table><hr>';  
 


include("quicksidebar.php");
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
  width: 800px;
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
  width: 1000px;
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

	<!-- Ready Pro DEMO methods, dont include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>


<!--  END   -->

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
	<!-- eventid,userid,mtid,과목,점수,시간,메모,마감일-->	
	<script>
		function inputmission(Eventid,Userid,Mtype,Subject,Grade,Chhours,Chstart,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "subject":Subject,
		       	  "grade":Grade,
			  "hours":Chhours,
			  "chstart":Chstart,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })

		}
		function inputmission2(Eventid,Userid,Mtype,Idcreated,Grade,Chhours,Chstart,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "idcreated":Idcreated,
		       	  "grade":Grade,
			  "hours":Chhours,
			  "chstart":Chstart,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })

		}
 		function inputmission3(Eventid,Userid,Mtype,Subject,Grade,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "subject":Subject,
		       	  "grade":Grade,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })

		}
		function inputmission4(Eventid,Userid,Mtype,Idcreated,Grade,Weekhours,Startdate){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
		       	  "msntype":Mtype,
			  "idcreated":Idcreated,
		       	  "grade":Grade,
			  "weekhours":Weekhours,
		               "startdate":Startdate,
		               },
		            success:function(data){
			            }
		        })

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