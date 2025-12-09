<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성

$pageintro= '<table align=center><tr><td align=center>Cognitive tutor</td></tr></table>';
 
$showpage= '<table with=100% align=center><tr><td><iframe style="border: 1px none; z-index:2; width:70vw; height:80vh;  margin-left:-0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id=Q7MQFA528140tsDoHfRT_user921_2021_04_28&speed=+9" ></iframe></td></tr></table>'; // 기본 컨텐츠

$pagewelcome='다음 내용을 보고 궁금한 부분을 질문해 주세요';

// 조건문으로 선생님별로 선택
$buttons.= '<td><button class="submit-button" id="updateButton1" onclick="">내 노트로</button></td>';
$buttons.= '<td><button class="submit-button" id="updateButton2" onclick="">질문하기</button></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid=2&type=goodbye"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
?>
 