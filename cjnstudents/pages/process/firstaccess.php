<?php 
$exist= $DB->get_record_sql("SELECT id FROM mdl_abessi_cjntalk WHERE userid LIKE '$userid' AND page LIKE '$type' AND timecreated>'$halfdayago' "); 
//$newtalk=$pagewelcome;
if($exist->id==NULL && $USER->id==$userid)
    {
    $DB->execute("INSERT INTO {abessi_cjntalk} (userid,text,page,timecreated) VALUES('$USER->id','$pagewelcome','$type','$timecreated')");	
    $newtalk=$pagewelcome;
    }

if($type==='firstvisit')$newtalk=$pagewelcome;
elseif($mode==='firstcourse')$newtalk='강좌는 크게 개념코스 / 심화코스 / 내신코스 / 수능코스로 이루어져 있습니다. 원하는 강좌를 선택해 주세요. 선택이 어렵다면 현재 진행 중인 개념공부 과목에서 시작할 수 있습니다. 이후 진행하면서 새로운 강좌를 추가하실 수 있습니다.';
elseif($newtalk==NULL)$newtalk='무엇을 도와드릴까요 ? 아래 입력창에 입력해 주세요';
 
?>