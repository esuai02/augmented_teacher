<?php  
$timeback=time()-43200;
//$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
$tlastinput=$checkgoal->timecreated;
$goaltext=$checkgoal->text;
$ngoal=1;
//이부분은 추후 선택항목별 피드백으로 개선
//$tlastlog= $DB->get_record_sql("SELECT * FROM  mdl_abessi_missionlog WHERE userid='$studentid' AND page='studentfullengagement'  ORDER BY id DESC LIMIT 1 ");
//$tfulleng=$tlastlog->timecreated;
$tindex= $DB->get_record_sql("SELECT max(id),timecreated FROM  mdl_abessi_missionlog WHERE userid='$studentid' AND page='studentindex'  ");
//$schedule= $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid'   ORDER BY id DESC LIMIT 1 ");
$editnew=$schedule->editnew;
 
if($role==='student')
	{
	$termgoal = $DB->get_record_sql("SELECT id FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype LIKE '분기목표' AND hide=0 AND deadline>'$timecreated' ORDER by id DESC LIMIT 1");
	if($tindex->timecreated==NULL) // 신규 사용자 인사
		{
		$nn=1;
		header('Location: https://mathking.kr/moodle/local/augmented_teacher/twinery/bigplan.html');
		}   
	elseif(($tlastinput<$timeback || $wgoal->timecreated <$wtimestart1) && strpos($url, 'roadmap.php')==false && strpos($url, 'missionhome.php')==false && strpos($url, 'edittoday.php')==false  && strpos($url, 'selectmission.php')==false && strpos($url, 'cognitivism')==false  ) 
		{ 
		//$DB->execute("UPDATE {abessi_indicators} SET nforce=1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
		header('Location: https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$studentid);
		}
	elseif($termgoal->id==NULL && strpos($url, 'roadmap.php')==false) //분기목표 체크
		{	
 		header('Location: https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid);
		} // 목표설정 --> 현재 적용된 대로 목표입력 화면으로 이동 
	}
?>
