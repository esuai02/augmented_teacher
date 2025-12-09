<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
 
$showpage= '<table with=100% align=center><tr><td>Check your golden plan, golden goal and find golden path !</td></tr>
<tr><td>나에게 맞는 목표설정 도움 플러그인을 설치해 보세요 (+)</td></tr></table>'; // 기본 컨텐츠
 
$termplan= $DB->get_record_sql("SELECT id,deadline,memo FROM mdl_abessi_progress WHERE userid='$userid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated' ORDER BY id DESC LIMIT 1 ");
	{
	$EGinputtime=date("m/d",$termplan->deadline);
	$termMission=$termplan->memo;
	}
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$aweekago' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$weeklyGoalText='<span style="color:white;font-size=15;"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1612786844001.png" width=40> 이번 주 목표가 설정되지 않았습니다. </span>';

if($termMission==NULL)$goaldisplay='분기목표를 설정해 주세요';
elseif($weeklyGoal->id==NULL)$goaldisplay= $EGinputtime.'까지 계획이 '.$termMission.'입니다. 주간목표를 입력해 주세요 !';
else $goaldisplay= $EGinputtime.'까지 계획이 '.$termMission.'이어서 이번 주는 '.$weeklyGoal->text.'(을)를 목표로 정진 중입니다. ';

$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1 ");
 
if($checkgoal->result/$checkgoal->pcomplete>1)$evaluateResult='<span sytle="color:green;">주간목표의 '.$checkgoal->result.'%를 진행하였습니다. 수고하셨습니다 ! </span>';
elseif($checkgoal->result/$checkgoal->pcomplete>0.7)$evaluateResult='주간목표의 '.$checkgoal->result.'%를 진행하였습니다.  당신이 사용한 시간은 '.$checkgoal->pcomplete.'%이므로 학습속도를 향상시킬 수 있는 방법에 대해 고민해 보시기바랍니다.';
else $evaluateResult='주간목표의 '.$checkgoal->result.'%를 진행하였습니다. 당신이 사용한 시간은 '.$checkgoal->pcomplete.'%이므로 계획이 위태롭습니다. 선생님과 주간목표 수정에 대해 상의해 주세요 !';
  
$pagewelcome=$goaldisplay.$evaluateResult;

// 조건문으로 선생님별로 선택
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$userid.'"target="_blank"><button class="submit-button">분기목표</button></a></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'&gtype=%EC%98%A4%EB%8A%98%EB%AA%A9%ED%91%9C%20%EC%84%A4%EC%A0%95c"target="_blank"><button class="submit-button">목표입력</button></a></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=todaymc"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
 
$answerShort=false; 
$count=2;
$rolea='';
$roleb='';
$talka1='';
$talkb1='AI tutor';
$tone1='가볍고 친절한 톤으로'
?>