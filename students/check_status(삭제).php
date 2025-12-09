<?php 
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$status = $_POST['isactive'];
$contextid = $_POST['contextid'];
$currenturl = $_POST['currenturl'];
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$timecreated=time();

 
if($status!=0)$DB->execute("INSERT INTO {abessi_stayfocused} (userid,status,context,currenturl,timecreated) VALUES('$USER->id','$status','$contextid','$currenturl','$timecreated')"); //몰입이탈 체크

 
$feedback=$DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog  WHERE userid='$USER->id' ORDER BY timecreated DESC LIMIT 1 ");
$timediff=(time()-$feedback->timecreated);
$url=$feedback->url;  //$feedback->context
if( $timediff >0 && $timediff <8   ) //화이트보드 메세지
{
  echo json_encode( array("content" =>$url,"mid"=>"1") );
}
 
 

$Ttoday=$DB->get_record_sql("SELECT max(mdl_messages.timecreated) AS maxtime FROM mdl_messages LEFT JOIN mdl_message_conversation_members
 ON mdl_message_conversation_members.conversationid=mdl_messages.conversationid WHERE mdl_message_conversation_members.userid!= mdl_messages.useridfrom AND mdl_message_conversation_members.userid='$USER->id' ");
 
$message=$DB->get_record_sql("SELECT mdl_messages.smallmessage AS small, mdl_messages.useridfrom AS sender FROM mdl_messages LEFT JOIN mdl_message_conversation_members
 ON mdl_message_conversation_members.conversationid=mdl_messages.conversationid WHERE mdl_message_conversation_members.userid!= mdl_messages.useridfrom 
 AND mdl_message_conversation_members.userid='$USER->id'  AND mdl_messages.timecreated='$Ttoday->maxtime' ");
$timediff=(time()-$Ttoday->maxtime);

// TRUNCATE TABLE tablename
// 선생님 화이트보드 도착 알림

$wbmessage=$DB->get_record_sql("SELECT  *  FROM mdl_abessi_messages WHERE (userid='$USER->id' AND turn=0) OR (userto='$USER->id' AND turn=1) ORDER BY timemodified DESC LIMIT 1");
$wboardid=$wbmessage->wboardid;
$nstep=$wbmessage->nstep;
$turn=$wbmessage->turn;
$timediff2=time()-$wbmessage->timemodified;
 
if($nstep!=0 && $timediff2 >0 && $timediff2 <8 && $wboardid != NULL && (($turn==0 && $role==='student') || ($turn==1 && $role==='teacher')) ) //화이트보드 메세지
{
  echo json_encode( array("content" =>$wboardid,"mid"=>"123") );
}
// 질의응답 화이트보드 도착 알림

$wbmessage=$DB->get_record_sql("SELECT  *  FROM mdl_abessi_messages WHERE (userid='$USER->id' AND turn=0) OR (userto='$USER->id' AND turn=1) ORDER BY timemodified DESC LIMIT 1");
$wboardid=$wbmessage->wboardid;
$nstep=$wbmessage->nstep;
$turn=$wbmessage->turn;
$timediff2=time()-$wbmessage->timemodified;
 
if($nstep!=0 && $timediff2 >0 && $timediff2 <8 && $wboardid != NULL && (($turn==0 && $role==='student') || ($turn==1 && $role==='teacher')) ) //화이트보드 메세지
{
  echo json_encode( array("content" =>$wboardid,"mid"=>"234") );
}
 
// 퀴즈 피드백 도착 알림 ... 퀴즈 종료시간이 10초 이상 지난경우 부분 적용
 
$quizattempt = $DB->get_record_sql("SELECT mdl_quiz.id AS qid, mdl_quiz_attempts.timemodified AS timemodified, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.comment AS comment FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.userid='$USER->id' AND mdl_quiz_attempts.comment !='NULL' ORDER BY mdl_quiz_attempts.timemodified DESC LIMIT 1 ");
$tcomment=time()-$quizattempt->timemodified;
$tfinish=time()-$quizattempt->timefinish;
$qid=$quizattempt->qid;
$qcomment=$quizattempt->comment;
//if($tfinish > 10 && $tcomment > 0 && $tcomment < 6 &&  $qcomment != NULL  ) //화이트보드 메세지
if(  $tcomment > 0 && $tcomment < 6 &&  $qcomment != NULL  ) //화이트보드 메세지
{
 echo json_encode( array("content" =>$USER->id ,"mid"=>"345") );
}
 
//  mathking 메세지 처리부분 1. 메세지 2. 화이트보드 3. 유튜브 4. 이미지 5. 채팅방  
 
 
if(strpos($message->small, "::")!==true && $timediff < 6)  // 메세지 발송
{
$message->small=str_replace("::"," ",$message->small);
echo json_encode( array("content" =>$message->small,"sender"=>$message->sender, "mid"=>"4") );
}   // 채팅시작, 이부분은 화이트보드 채팅 부분으로 통합.10.28
elseif((strpos($message->small,"chat/gui_ajax/index.php")!==false||strpos($message->small,"talk1")!==false||strpos($message->small,"talk2")!==false||strpos($message->small,"talk2")!==false||strpos($message->small,"talk3")!==false||strpos($message->small,"talk4")!==false||strpos($message->small,"talk5")!==false||strpos($message->small,"talk6")!==false||strpos($message->small,"talk7")!==false)&& strpos($message->small,"::")!==false && $timediff <6) 
{
$message->small=str_replace("::"," ",$message->small);
if(strpos($message->small,"talk1")!==false)echo json_encode( array("content" =>"48", "mid"=>"61"));
if(strpos($message->small,"talk2")!==false)echo json_encode( array("content" =>"49", "mid"=>"61"));
if(strpos($message->small,"talk3")!==false)echo json_encode( array("content" =>"50", "mid"=>"61"));
if(strpos($message->small,"talk4")!==false)echo json_encode( array("content" =>"51", "mid"=>"61"));
if(strpos($message->small,"talk5")!==false)echo json_encode( array("content" =>"52", "mid"=>"61"));
if(strpos($message->small,"talk6")!==false)echo json_encode( array("content" =>"53", "mid"=>"61"));
if(strpos($message->small,"talk7")!==false)echo json_encode( array("content" =>"54", "mid"=>"61"));
}



?>

