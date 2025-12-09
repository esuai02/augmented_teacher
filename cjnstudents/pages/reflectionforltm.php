<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘.. 
$userid=$_GET["userid"]; 

// 오답노트 완료 버튼시 마무리 성찰 팝업. 마무리활동 추가
 
?>