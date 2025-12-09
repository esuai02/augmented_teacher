<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
  
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$studentid = $_POST['userid'];

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$timecreated=time();
 
$wtimestart=$timecreated-86400*($nday+3);
$wtimestart2=$timecreated-86400*($nday+10);  // 주간목표 평가 시점
$halfdayago=$timecreated-43200;
$hoursago6=$timecreated-21600;
$aweekago=$timecreated-604800;
 
// 현재 데이터 가지고 와서 $ntodo 결정하기  



$moreleap = $DB->get_record_sql("SELECT * FROM  mdl_abessi_cognitivetalk WHERE creator='$studentid' AND timecreated>'$aweekago'  ORDER BY id DESC LIMIT 1 ");  // 메타인지 피드백
$nextlearning=$DB->get_record_sql("SELECT max(id) AS id FROM mdl_abessi_mission WHERE  userid='$studentid' AND complete=0 ");
 			
$checkgoal1= $DB->get_record_sql("SELECT max(id) AS id  FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated' "); 
$checkgoal2= $DB->get_record_sql("SELECT  max(id) AS id  FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$wtimestart' AND type LIKE '주간목표' ");
$checkgoal3= $DB->get_record_sql("SELECT  id, text,drilling FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$halfdayago' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1");
 
$missionlog = $DB->get_record_sql("SELECT id, timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
 
$note1=$DB->get_record_sql("SELECT id, tlaststroke, status,flag FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'flag'   OR status LIKE 'reply' OR status LIKE 'begin' OR status LIKE 'exam' OR status LIKE 'retry' OR status LIKE 'present' OR (status LIKE 'complete' AND depth LIKE 'NULL' AND teacher_check NOT LIKE '2')  OR  (status LIKE 'review' AND depth LIKE 'NULL' AND teacher_check NOT LIKE '2') ) AND hide=0 AND tlaststroke>'$hoursago6' AND contentstype=2 ORDER BY id DESC LIMIT 1 ");
//$note2=$DB->get_record_sql("SELECT id, nstroke  FROM mdl_abessi_messages WHERE  userid='$studentid'  AND status LIKE 'summary' AND timecreated > '$hoursago6'   ORDER BY id DESC LIMIT 1");
$quizattempts = $DB->get_record_sql("SELECT id FROM  mdl_quiz_attempts   WHERE userid='$studentid' AND maxgrade IS NULL AND timefinish >'$halfdayago' ORDER BY id DESC LIMIT 1 ");

$note3=$DB->get_record_sql("SELECT id, nstroke,timecreated FROM mdl_abessi_messages WHERE  userid='$studentid'  AND status LIKE 'weekly'  ORDER BY id DESC LIMIT 1");
//max(id) AS id 
/*
$filltime=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'summary'  OR status LIKE 'weekly')  AND tlaststroke>'$aweekago'     ORDER BY tlaststroke DESC LIMIT 1");
$addtime=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'summary'  OR  status LIKE 'weekly')  AND tlaststroke>'$aweekago'     ORDER BY tlaststroke DESC LIMIT 1");
$topic=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'summary'  OR   status LIKE 'weekly')  AND tlaststroke>'$aweekago'     ORDER BY tlaststroke DESC LIMIT 1");
$reflectquiz=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'summary'  OR  status LIKE 'weekly')  AND tlaststroke>'$aweekago'     ORDER BY tlaststroke DESC LIMIT 1");
$reflecthow=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'summary'  OR  status LIKE 'weekly')  AND tlaststroke>'$aweekago'     ORDER BY tlaststroke DESC LIMIT 1");
$engagement=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'summary'  OR status LIKE 'weekly')  AND tlaststroke>'$aweekago'     ORDER BY tlaststroke DESC LIMIT 1");
*/

$goaltext=$checkgoal3->text;

if($nextlearning->id==NULL)$ntodo=1; // 새로운 커리큘럼 생성
elseif($checkgoal1->id==NULL)$ntodo=2; //분기목표
elseif($checkgoal2->id==NULL)$ntodo=3; //주간목표
elseif($checkgoal3->id==NULL) $ntodo=4; //오늘목표
elseif(($moreleap->id!=NULL && $moreleap->userid!=$studentid) || $moreleap->id==NULL )$ntodo=0; // 새로운 몰입피드백
elseif(strpos($goaltext, '공부법') === false &&strpos($goaltext, '프린트') === false &&strpos($goaltext, '마무리') === false &&strpos($goaltext, '통과') === false &&strpos($goaltext, '완료') === false && strpos($goaltext, '점') === false && strpos($goaltext, '합격') === false &&  strpos($goaltext, '끝') === false && strpos($goaltext, '유형') === false && strpos($goaltext, '주제')=== false &&  strpos($goaltext, '단원')=== false && strpos($goaltext, '까지') === false && strpos($goaltext, '0') === false &&  strpos($goaltext, '5') === false)$ntodo=5;
elseif($quizattempts->id!=NULL)$ntodo=6; // 퀴즈분석
elseif($note1->status==='retry')$ntodo=7; //재시도
elseif($note1->status==='present')$ntodo=8; //발표요청
elseif($note1->status==='reply')$ntodo=9; // 답변도착
//elseif($moreleap->userid!=$studentid)$ntodo=10; // 새로운 몰입피드백
elseif($note1->status==='flag' && $note1->flag==1)$ntodo=11; //고민지점
elseif($note1->status==='begin' || $note1->status==='exam' )$ntodo=12; //오답노트 미완료
//elseif($note2->id !=NULL && $note2->nstroke<10)$ntodo=12; //요약하기
elseif($note3->nstroke<10 && $note3->timecreated > '$aweekago')$ntodo=13; //주간활동 설계
/*
elseif($filltime->id!=NULL)$ntodo=13; // 공부시간을 채워주세요
elseif($addtime->id!=NULL)$ntodo=14; // 보충공부 발생
elseif($topic->id!=NULL)$ntodo=15; // 개념 보충수업
elseif($reflectquiz->id!=NULL)$ntodo=16;  // 주간 퀴즈평균 이상
elseif($reflecthow->id!=NULL)$ntodo=17; // 공부법 루틴 레벨
elseif($engagement->id!=NULL)$ntodo=18; // 오늘 평점
*/
//$shine= $DB->get_record_sql("SELECT * FROM mdl_abessi_reflection WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 "); 

if($ntodo==0) 
	{
	if($moreleap->id==NULL)
		{
		$fbtype='메타인지를 점검하기';
		$fbtext='메타인지 점검을 통하여 공부에 활력을 만들어 보세요';
		$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php';
		$fburl='studentid='.$studentid;
		}
	else
		{
		$fbtype='새로운 메타인지 피드백';
		$fbtext='새로운 메타인지 피드백이 있습니다. 답변을 입력해 주세요';
		$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php';
		$fburl='studentid='.$studentid.'&type='.$moreleap->type;	
		}
	}  
elseif($ntodo==1) 
	{
	$fbtype='새로운 강좌 생성';
	$fbtext='현재 진행 중인 강좌가 없습니다. 새로운 강좌를 생성해 주세요';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/index.php';
	$fburl='id='.$studentid.'&gtype='.$fbtype;	
	}   
elseif($ntodo==2) 
	{
	$fbtype='분기목표 설정';
	$fbtext='분기목표를 설정하여 장기적인 학습의 흐름을 발생시킬 수 있습니다.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php';
	$fburl='id='.$studentid.'&gtype='.$fbtype;		 
	}
elseif($ntodo==3) 
	{
	$fbtype='주간목표 설정';
	$fbtext='주간목표를 설정하여 오늘 활동의 몰입감을 높일 수 있습니다.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php';
	$fburl='id='.$studentid.'&gtype='.$fbtype;	
	}
elseif($ntodo==4) 
	{
	$fbtype='오늘목표 설정';
	$fbtext='오늘목표를 설정하여 활동의 흐름의 촉진시킬 수 있습니다.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php';
	$fburl='id='.$studentid.'&gtype='.$fbtype;	
	}
elseif($ntodo==5) 
	{
	$fbtype='목표를 구체화해 주세요.';
	$fbtext=$goaltext.'를 좀 더 구체적인 목표로 바꿔주세요. 보다 쉽게 몰입할 수 있어요 !';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php';
	$fburl='id='.$studentid.'&gtype='.$fbtype;				
	}
	/*
elseif($ntodo==6) 
	{
	$fbtype='퀴즈분석 발견';
	$fbtext='향상노트 후 풀 수 있었다고 느끼는 문항들을 체크 후 결과보기를 클릭해 주세요';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php';
	$fburl='id='.$studentid.'&attemptid='.$quizattempts->id.'&gtype='.$fbtype;	 		
	}*/
elseif($ntodo==7) 
	{
	$fbtype='풀이 재시도';
	$fbtext='재시도할 노트가 발견되었습니다.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;	 	
	}
elseif($ntodo==8) 
	{
	$fbtype='발표요청';
	$fbtext='발표요청이 있습니다. 발표준비 후 선생님 자리로 와 주세요.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;			
	}
elseif($ntodo==9) 
	{
	$fbtype='답변도착';
	$fbtext='답변하지 않은 노트가 있습니다. 답변 후 결과를 선택해 주세요.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;			
	}
elseif($ntodo==10) 
	{
	$fbtype='복습예약 시도하기';
	$fbtext='예약된 복습노트가 발견되었습니다. 복습을 진행해 주세요';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;			
	}
elseif($ntodo==11) 
	{
	$fbtype='고민지점 해결하기';
	$fbtext='고민지점이 있는 노트들이 발견되었습니다. 삭제 후 재시도 해 주세요.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;		
	}
elseif($ntodo==12) 
	{
	$fbtype='오답노트 미완료';
	$fbtext='오답노트를 먼저 해결한 다음 후속 학습을 진행해 주세요.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;		
	}
else
	{
	$fbtype='OK !';
	$fbtext='점검사항이 모두 클리어 되었습니다';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$fburl='id='.$studentid.'&tb=604800&gtype='.$fbtype;
	$ntodo=14;
	}
/*
elseif($ntodo==12) 
	{
	$fbtype='오늘목표 요약하기';
	$fbtext='현재까지 공부한 내용을 요약노트에 추가해 주세요.';
	$smnote1=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND status LIKE 'summary'  ORDER BY id DESC LIMIT 1");
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php';
	$fburl='id='.$smnote1->wboardid.'&gtype='.$fbtype;		
	}
elseif($ntodo==13) 
	{
	$fbtype='주간목표 설계하기';
	$fbtext='이번 주 계획을 구체적으로 스케치 후 세밀한 계획을 세워 보세요';
	$smnote2=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND status LIKE 'weekly'  ORDER BY id DESC LIMIT 1");
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php';
	$fburl='id='.$smnote2->wboardid.'&gtype='.$fbtype;				
	}
*/

 

echo json_encode( array("fbtype"=>$fbtype,"fbtext"=>$fbtext,"contextid"=>$contextid,"fburl"=>$fburl,"ntodo"=>$ntodo) );
 
$DB->execute("UPDATE {abessi_indicators} SET ntodo='$ntodo', todo='$fbtype',timefired='$timecreated'  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  // 실행할 ntodo도출하기
?>
