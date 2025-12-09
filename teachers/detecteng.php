<?php 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$userid'   ORDER BY id DESC LIMIT 1 ");  // missionlog
$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
$engagement3 = $DB->get_record_sql("SELECT speed,todayscore, tlaststroke FROM  mdl_abessi_indicators WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators

$teng1=$engagement1->timecreated;
$teng2=$engagement2->timecreated;
$teng3=$engagement3->tlaststroke;  
$todayscore=$engagement3->todayscore;
$tspeed=$engagement3->speed;
$tlast=max($teng1,$teng2,$teng3);
/*
if($teng1>$teng2 && $teng1>$teng3)lasturl='<a href=""><img src="https://mathking.kr/IMG/HintIMG/BESSI1598169191001.png "></a>';
if($teng2>$teng1 && $teng2>$teng3)lasturl='https://mathking.kr/IMG/HintIMG/BESSI1598169225001.png';
if($teng3>$teng1 && $teng3>$teng1)lasturl='https://mathking.kr/IMG/HintIMG/BESSI1598169249001.png';

0. 활동 미감지 5분 이상인 경우  :: 무들 쪽 마지막 log 시간과 abessi 쪽 마지막 log 및 화이트보드 마지막 log 중 가장 큰 값을 현재 시간과의 차를 기록해서 표시해준다.
1. 연속 오답 
2. 속도 지연
3. 질문 지연
4. 오답노트 미작성 / 대충작성
*/
?>