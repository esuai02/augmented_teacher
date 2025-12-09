<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
 
$showpage= '<table with=100% align=center><tr><td>귀가검사 제출 페이지입니다.</td></tr><tr><td>오늘 활동을 마무리하고 효율적인 다음 활동준비를 위한 성찰 플러그인들을 설치해 보세요</td></tr></table>'; // 기본 컨텐츠

$pagewelcome='오늘 활동이 잘 마무리 되었나요, 귀가검사를 제출하기 전에 마무리 상태를 점검해 주세요 !';

// 조건문으로 선생님별로 선택
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank"><button class="submit-button">오늘활동 점검 후 다음목표 입력</button></a></td>';
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=goodbye"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
?>