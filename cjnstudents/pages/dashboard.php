<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
 
$showpage= '<table with=100% align=center><tr><td>학습현황판입니다. 필요한 메뉴를 선택해 주세요...</td></tr></table>'; // 기본 컨텐츠

$pagewelcome='학습현황판입니다. 필요한 메뉴를 선택해 주세요';

// 조건문으로 선생님별로 선택
$buttons.= '<td><button class="submit-button" id="updateButton1" onclick="">목표 수정</button></td>';
$buttons.= '<td><button class="submit-button" id="updateButton2" onclick="">공부법 변경</button></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid=2&type=goodbye"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
?>
 