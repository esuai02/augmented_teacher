<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$studentid=$_GET["id"]; 
$timecreated=time();
$tbegin=$timecreated-$_GET["tb"]; 
$weeksago2=$timecreated-604800*2;
$halfdayago=$timecreated-43200;
$anhourago=$timecreated-0;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($studentid==NULL)$studentid=$USER->id;

//if($tbegin==NULL)$tbegin=$timecreated-43200;
echo '<p align=center><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" width=100%></p> <br><table width=100%><tr><td>개념 부스터활동</td><td align=right><a href="https://mathking.kr/moodle/local/augmented_teacher/students/logicalstairway.php?id='.$studentid.'&tb=43200">오늘</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/logicalstairway.php?id='.$studentid.'&tb=604800">최근 일주일</a></td> </tr></table><hr>';

//$todo=$DB->get_record_sql("SELECT * FROM mdl_abessi_resurrection WHERE userid='$studentid' AND nretry<3  AND timemodified<'$anhourago' AND timemodified>'$weeksago2' ORDER BY  rand()   LIMIT 1"); 
$todo = $DB->get_records_sql("SELECT * FROM mdl_abessi_resurrection WHERE  userid='$studentid'  AND timemodified<'$anhourago' AND timemodified>'$weeksago2'  ORDER BY timemodified DESC LIMIT 30");
$todolist = json_decode(json_encode($todo), True); 
unset($value);
foreach($todolist as $value)  // type,userid,teacherid,itemid,nretry,status,url,active,timemodified,timecreated
	{
 	$rsid=$value['id'];
	$checkbox='';
	$cntlink='';

	if($value['type']==='기억저장')
		{
		$cntlink='https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replaycjn.php?id='.$value['url'];$titlecolor='#0099ff';
		$wboardid=substr($value['url'], 0, strpos($value['url'], '&cntid')); // 문자 이후 삭제
		$wboardid=str_replace("&cntid","",$wboardid);
		
		$check= $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' AND tlaststroke>'$tbegin' ORDER BY id DESC LIMIT 1");
		if($check->id!=NULL)
			{
			//$checkstatus='checked';
			$result='<b style="color:'.$titlecolor.';">'.$check->nstroke.'획</b>';   
			}
		else {$result='활동없음'; $nremain=1;}
		$checkbox='<input type="checkbox" '.$checkstatus.'  onclick="changecheckbox(1,'.$studentid.','.$rsid.', this.checked)"/>';
 		$activities.='<tr><td> '.$checkbox.' </td> <td width=2%></td><td>'.$value['type'].'</td><td width=2%></td><td>'.$value['nretry'].'회차</td><td width=2%></td><td><span style="color:'.$titlecolor.'" onclick="NextAction(\''.$cntlink.'\');" >32'.$value['title'].'</span></td><td width=2%></td><td>'.$result.'</td><td width=2%></td><td>'.round(($timecreated-$value['timemodified'])/86400,0).'일</td><td width=2%></td><td></td></tr>';

		}/*
	elseif($value['type']==='개념연습')
		{
		$cntlink='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$value['url'];$titlecolor='#0099ff';
		$wboardid=substr($value['url'], 0, strpos($value['url'], '&cntid')); // 문자 이후 삭제
		$wboardid=str_replace("&cntid","",$wboardid);
		
		$check= $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' AND tlaststroke>'$tbegin' ORDER BY id DESC LIMIT 1");
		if($check->id!=NULL)
			{
			//$checkstatus='checked';
			$result='<b style="color:'.$titlecolor.';">'.$check->nstroke.'획</b>';   
			}
		else {$result='활동없음'; $nremain=1;}
		}
	elseif($value['type']==='유형연습')
		{
		$cntlink='https://mathking.kr/moodle/mod/quiz/view.php?'.$value['url'];$titlecolor='#ff1a1a';
		$quizmoduleid=str_replace('id=','',$value['url']);   

		$module=$DB->get_record_sql("SELECT * FROM mdl_course_modules where id='$quizmoduleid'  "); 
		$quizid=$module->instance;
		$beingsullivan=$instanceid->id;  include("../whiteboard/debug.php");  
		$check= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE userid='$studentid' AND quiz='$quizid' AND state='finished' AND timemodified>'$tbegin' ORDER BY id DESC LIMIT 1");
		if($check->id!=NULL)
			{
			$quizattempts = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades, mdl_quiz.timelimit AS timelimit FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.id='$check->id'   ");
			$quizgrade=round($quizattempts->sumgrades/$quizattempts->tgrades*100,0);  // 점수
			//$checkstatus='checked';
			$result='<b style="color:'.$titlecolor.';">'.$quizgrade.'점</b>';
			}
		else {$result='활동없음'; $nremain=1;}
		}
	elseif($value['type']==='단원연습')
		{
		$cntlink='https://mathking.kr/moodle/mod/quiz/view.php?'.$value['url'];$titlecolor='#ff1a1a';
		$quizmoduleid=str_replace('id=','',$value['url']);   

		$module=$DB->get_record_sql("SELECT * FROM mdl_course_modules where id='$quizmoduleid'  "); 
		$quizid=$module->instance;
		$beingsullivan=$instanceid->id;  include("../whiteboard/debug.php");  
		$check= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE userid='$studentid' AND quiz='$quizid' AND state='finished' AND timemodified>'$tbegin' ORDER BY id DESC LIMIT 1");
		if($check->id!=NULL)
			{
			$quizattempts = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades, mdl_quiz.timelimit AS timelimit FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.id='$check->id'   ");
			$quizgrade=round($quizattempts->sumgrades/$quizattempts->tgrades*100,0);  // 점수
			//$checkstatus='checked';
			$result='<b style="color:'.$titlecolor.';">'.$quizgrade.'점</b>';
			}
		else {$result='활동없음'; $nremain=1;}
		}*/
 
	} 
if($nremain==0)$DB->execute("UPDATE {abessi_today} SET drilling=0  WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");

echo '<table align=center width=100%>'.$activities.'</table>';
echo '<script>
function NextAction(NEXTURL)
	{
	setTimeout(function(){window.top.location.assign(NEXTURL); 	},10);  
	}
</script>';
  
?>