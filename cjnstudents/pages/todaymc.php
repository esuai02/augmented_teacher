<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
 
$mcactive=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE (talkid=17) AND creator LIKE '$userid' ORDER BY id DESC LIMIT 1 ");   

//$lastcfeedback1=$mcactive->text.' &nbsp;(<a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type='.$mcactive->type.'"target="_blank">'.$mcactive->type.'</a>)';



$showpage= '<table with=100% align=center><tr><td>메타인지 페이지 홈</td></tr><tr><td>노력비용을 감소시키도록 도움을 주는 메타인지 플러그인을 성치해 보세요(+)</td></tr></table>'; // 기본 컨텐츠

$pagewelcome='마지막 활동 항목은 ( '.$mcactive->text.' )입니다. 오늘의 메타인지 활동을 선택해 주세요 !';



// 조건문으로 선생님별로 선택
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type='.$mcactive->type.'"target="_blank"><button class="submit-button">활동선택</button></a></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=mycourses"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
?>