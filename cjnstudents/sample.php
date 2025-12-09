<?php

// HTML 문서 시작
echo "<!DOCTYPE html>";
echo "<html>";

// head 태그 시작
echo "<head>";
echo "<title>스마트폰 로그인 화면</title>";
echo "</head>";

// body 태그 시작
echo "<body>";

// 로그인 폼 시작
//echo "<form action='login.php' method='post'>";
echo "<label for='username'>아이디:</label>";
echo "<input type='text' id='username' name='username'><br>";
echo "<label for='password'>비밀번호:</label>";
echo "<input type='password' id='password' name='password'><br>";
echo "<input type='submit' value='로그인'>";
echo "</form>";

// body 태그 종료
echo "</body>";

// HTML 문서 종료
echo "</html>";
 
// 세션 시작
session_start();

// 로그인 되어있지 않으면 로그인 페이지로 이동
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// GPT API 키
$gpt_api_key = 'your_gpt_api_key';

// 대화창 시작
echo '<div id="chat-window">';

// 대화창 내용 출력
echo '<div id="chat-content"></div>';

// 대화 입력 폼
echo '<form id="chat-form">';
echo '<input type="text" id="chat-input" placeholder="메시지 입력">';
echo '<button type="submit" id="chat-submit">전송</button>';
echo '</form>';

// 대화창 끝
echo '</div>';

 
// 사이드바 메뉴 구성을 배열로 선언
$description = array(
    "과목별 대화",
    "고민상담",
    "성찰 질문 주고 받기",
    "커뮤니티"
);
 
?>

