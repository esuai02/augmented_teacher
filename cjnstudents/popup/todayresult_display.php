<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
/*
아래 내용들을 데이터로 기본 내용을 발생 -- > 다음 글을 균형있는 안내글로 바꿔서 만들어줘 (GPT).
 
이탈빈도 (이탈빈도 0. 시작부터 끝까지 안정적인 페이스로 공부가 진행되었습니다. 1 ~ 3 : 일부 구간 흐름이 끊어지긴 했지만 전체적으로 안정적인 공부가 진행되었습니다. 4 ~ : 집중력이 흩어진 구간들이 있어 상담을 통하여 회복하였습니다.)
DMN 휴식
활동없음
표준테스트 점수 (90점 이상 능숙, 80점 이상 기본완성, 80점 미만 노력필요)
인지촉진 점수
개념공부 단원 (마지막 지점)
퀴지 시작 시 팝업 유형 기록 반영
*/
$userid=$_GET["userid"]; 
$teacherid=$_GET["teacherid"];
$timecreated=time();
$promptdata=$DB->get_records_sql("SELECT * FROM mdl_aba_userprompt where userid LIKE '0' AND hide LIKE '0' ORDER BY id ASC ");  
$promptresult = json_decode(json_encode($promptdata), True);
$numdata=count($promptresult);
$ndata=0;
unset($value);
foreach($promptresult as $value)
	{
    $prompttype=$value['type'];
    $prompttypetext=$value['typetext'];
    $promptinfo=$value['info'];
    $promptnum=$value['num'];
    $userprompt=$value['userprompt'];
    $promptnum=$value['num'];

    $userdata=$DB->get_record_sql("SELECT * FROM mdl_aba_userprompt where type='$prompttype' AND userid='$userid' ORDER BY id DESC LIMIT 1"); 
    if($timecreated-$userdata->timecreated<3)header("Refresh:0"); 
    if($userdata->id==NULL)
      {
      $DB->execute("INSERT INTO {aba_userprompt} (userid,type,typetext,info,userprompt,num,timemodified,timecreated) VALUES('$userid','$prompttype','$prompttypetext','$promptinfo','$userprompt','$promptnum','$timecreated','$timecreated')");	
      $ndata++;
      if($numdata==$ndata)header("Refresh:0"); 
      }
    else
      {
        $prompttypestr='prompttype'.$userdata->num;
        $promptstr='userprompt'.$userdata->num;  
        $infostr='info'.$userdata->num;  
        $$prompttypestr=$userdata->typetext;
        $$promptstr=$userdata->userprompt;
        $$infostr=$userdata->info;
      }
    
	}  
 
//  else $DB->execute("UPDATE {abessi_messages} SET helptext='$inputtext', timemodified='$timecreated' WHERE wboardid='$boardid' and userid='$userid' ORDER BY timemodified DESC LIMIT 1 ");

 
echo '
<br>
<table width=70%><tr><td align=center style="width: 70%; font-size: 20px;"><귀가검사 메세지 프롬프트 조절></td></tr><tr><td><hr></td></tr><tr><td><input style="width: 70%; font-size: 16px;" type="text" placeholder="전체 작성 방향을 입력해 주세요" value="'.$userprompt18.'"></td></tr><tr><td><hr></td></tr><tr></table> 
 
<table width=90% style="font-size: 16px;">
<thead><tr align=left><th width=3%></th><th>데이터</th><th>설명</th><th width=60%>프롬프트'.$numdata.'</th></tr></thead>
<tbody>
<tr><td><input type="checkbox"></td><td>'.$prompttype1.'</td><td>'.$info1.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt1.'"></td></tr> 

  <tr><td></td><td></td><td></td><td><button>저장하기</button></td></tr>
</tbody>
</table>
';

/*
 
<tr><td><input type="checkbox"></td><td>'.$prompttype2.'</td><td>'.$info2.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt2.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype3.'</td><td>'.$info3.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt3.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype4.'</td><td>'.$info4.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt4.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype5.'</td><td>'.$info5.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt5.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype6.'</td><td>'.$info6.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt6.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype7.'</td><td>'.$info7.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt7.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype8.'</td><td>'.$info8.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt8.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype9.'</td><td>'.$info9.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt9.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype10.'</td><td>'.$info10.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt10.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype11.'</td><td>'.$info11.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt11.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype12.'</td><td>'.$info12.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt12.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype13.'</td><td>'.$info13.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt13.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype14.'</td><td>'.$info14.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt14.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype15.'</td><td>'.$info15.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt15.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype16.'</td><td>'.$info16.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt16.'"></td></tr> 
<tr><td><input type="checkbox"></td><td>'.$prompttype17.'</td><td>'.$info17.'</td><td><input type="text" style="width: 70%; font-size: 16px;" placeholder="데이터 해석 프롬프트 문장 입력" value="'.$userprompt17.'"></td></tr> 
*/
?>