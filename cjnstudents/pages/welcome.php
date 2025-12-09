<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
$avartarimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png';
$showpage= '<table width=100% align=center><tr><td>Welcome 페이지 홈</td></tr><tr><td>공부를 시작할 때 효과적인 플러그인들을 추가하여 학습의 흐름을 원활하게 할 수 있습니다.</td></tr><tr><td>학습을 촉진시킬 감정엔진을 만들어보세요</td></tr>
<tr><td>플러그인을 추가해 주세요 (+)</td></tr></table>'; // 기본 컨텐츠
$finetuning='다음 내용에 대해 익살스러운 톤으로 답변해줘 (답변만 표시) :  ';
$pagewelcome='Welcome ! Mathking에 오신 것을 환영합니다.';

// 조건문으로 선생님별로 선택
$buttons.= '<td><button class="submit-button" id="updateButton1" onclick="">퀴즈결과</button></td>';
$buttons.= '<td><button class="submit-button" id="updateButton2" onclick="">오답노트</button></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=prepare"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';

$answerShort=false; 
$count=2;
$rolea='';
$roleb='';
$talka1='';
$talkb1='AI tutor';
$tone1='가볍고 친절한 톤으로'
 
?>