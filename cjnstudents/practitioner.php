<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘.. 
$userid=$_GET["userid"]; 
$cntid=$_GET["cntid"];   
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

$timecreated=time();
$minutesago=$timecreated-600;
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;  

$imrsv=$DB->get_record_sql("SELECT * FROM mdl_abessi_immersive WHERE id='$cntid' ORDER BY id DESC LIMIT 1");

  
for($ncnt=1;$ncnt<=12;$ncnt++)
  {
  $thistype='type'.$ncnt;
  $thisurl='url'.$ncnt;
  $wb=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid ='$wboardid'  ORDER BY id DESC LIMIT 1 "); 
  //if($imrsv->$thistype==NULL)continue;
  if($imrsv->$thistype==='topic')$thisurl='https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$imrsv->$thisurl;
  elseif($imrsv->$thistype==='quiz')$thisurl='https://mathking.kr/moodle/mod/quiz/view.php?'.$imrsv->$thisurl;
  else continue;
  $todolist.='<tr><td><input type="checkbox" name="checkAccount" '.$checkstatus.'  onClick="CheckProgress(2,\''.$studentid.'\',\''.$chkitemid.'\', this.checked)"/></td><td>'.$wb->contentstitle.'</td><td>('.$imrsv->$thistype.')</td><td><a href="'.$thisurl.'"target="_blank">활동보기</a></td></tr>';
  }

echo '<br><br><table align=center width=90%><tr><td style="font-size:25;">독립세션</td> <td> 활동명 </td><td> 평균 소요시간 </td></tr></table><hr><table width=90% align=center><td width=40% valign=top>챗봇 <hr> 활동 개요 <hr> 활동 결과 평가 및 의견 <hr> 축하합니다.</td><td><table width=100%>'.$todolist.'</table></td></table>';
?>