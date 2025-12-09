<?php 
/////////////////////////////// 전체 코드 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
$cntinput= $_GET["cntinput"];
$tperiod= $_GET["tp"];
$mode= $_GET["mode"];
if($studentid==NULL)$studentid=$USER->id;
$timecreated=time(); 
$hoursago=$timecreated-14400;
$halfdayago=$timecreated-43200; 

if($tperiod==NULL)$tperiod=604800; 
$tbegin=$timecreated-$tperiod;

$thisuser= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->lastname;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

// 녹음 동의 여부 확인
$recordingConsent = $DB->get_record_sql("SELECT * FROM mdl_abessi_mathtalk WHERE userid='$studentid' AND type='agreement' ORDER BY timecreated DESC LIMIT 1");
$hasRecordingConsent = ($recordingConsent && $recordingConsent->hide == 0) ? true : false;

if($role==='student') echo '<title>📒수학일기</title>';
else echo '<title>'.$stdname.'📒</title>';
   
$context=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$studentid' AND type LIKE 'context' ORDER BY id DESC LIMIT 1");
$contextinfo=$context->text;

if($studentid==2 && $USER->id!=2)
{
    exit();
}

$wgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$checkgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
$chapterlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$termplan2= $DB->get_record_sql("SELECT id FROM mdl_abessi_progress WHERE userid LIKE '$studentid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated' ORDER BY id DESC LIMIT 1 ");

$inspectToday =$checkgoal->inspect;
$date=gmdate("h:i A", $checkgoal->timecreated+32400);
  
if($inspectToday==2)$status4='checked';    
elseif($inspectToday==3)$status5='checked';  

$lastbreak= $DB->get_record_sql("SELECT id,timecreated FROM mdl_abessi_missionlog WHERE userid='$studentid' AND timecreated>'$halfdayago' AND eventid='7128' ORDER BY id DESC LIMIT 1 ");
$beforebreak=60-($timecreated-$lastbreak->timecreated)/60;
if($lastbreak->id!=NULL)$beforebreak=-1;

$todolist='상황별 조치방법 (학생 데이터를 토대로 아래 활동 중에서 필요한 활동을 선택하도록 해주세요)
 
1. 개념복습 : 개념을 직접 찾아보고 설명을 요청하거나 관련된 예제퀴즈나 대표유형을 10분정도 지시하는 것은 학생의 능동활동을 증가시키고 활력을 줄 수 있습니다.
2. 오답노트 검사 : 오답노트 방식을 관찰하여 능동적인 상태인지를 체크하고 학생에게 피드백을 줄 수 있습니다.
3. ANKI 퀴즈활동 : 기초 개념들을 숙달하지 못해 문제 해석이나 선생님의 설명을 흡수하는데 어려움을 겪거나 지연되는 경우 효과적입니다. 
4. 질문준비 루틴 : 학생이 할 수 있는 부분을 능동적으로 수행한 후 질의응답이 이루어질 때 가장 효과적입니다. 이를 위해 유형별로 질문 방식을 알려주고 실행하도록 합니다. 충분한 공지가 이루어진 이후에는 질문을 시작할 때 준비 상태를 체크하고 필요한 경우 준비활동 후 다시 질문하도록 요청하는 방식으로 학생이 좀 더 능동적으로 공부하도록 유도할 수 있습니다.
5. 분기목표 입력 : 방학기간 또는 시험기간 등 분기별 최종목표를 입력하여 반복적으로 각인되도록 합니다. 총 6개의 분기로 이루어져 있음. 겨울방학, 1학기 중간고사, 1학기 기말고사, 여름방학, 2학기 중간고사, 2학기 기말고사.
6. 주간목표 입력 : 분기목표를 토대로 주간목표를 설정합니다.
7. 오늘목표 입력 : 주가목표를 토대로 오늘의 목표를 설정합니다.
8. 활동추적 및 자가진단 평가하기 : 오늘목표를 염두해 두고 작은 단위의 활동과 예상 시간을 입력하게 합니다. 학생이 활동을 진행하면서 자신의 상태를 체크하고 평가할 수 있도록 도와줍니다.	
9. 지면평가 : 활동 중 특정 부분을 준비하여 선생님에게 직접 설명하며 피드백을 받는 활동입니다. 학생의 능동적인 학습태도를 고취시킬 수 있습니다. 해당 구간에서 부족한 부분을 드러내게 하고 피드백을 통하여 돌파하도록 돕습니다.
10. 질의응답 : 능동적인 질의응답의 몰입을 돕고 동기를 유지하는 최고의 방법입니다.';

$instructions=$DB->get_records_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$studentid' AND duration > '$tbegin' AND hide=0 ORDER BY id DESC LIMIT 100");
if($USER->id==2)$usercontext='<SPAN ONCLICK="addContext(\''.$studentid.'\');">➕</SPAN>';

$result = json_decode(json_encode($instructions), True);
unset($value);
$np=0;
$pmresult=0;
$directionlist1='';
$directionlist2='';
$tend_prev=0;

// 그래프용 데이터 배열 생성 (완료 기록에 한함)
$graphData = array();

foreach($result as $value) 
{	 
    if($prev_time!==date("m_d", $value['timecreated']))
    {
       $directionlist2.='<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
    }

    $statustext=$value['status']; 
    $trackingtext=$value['text']; 
    $trackingid=$value['id'];
    $tresult = $value['timefinished'] - $value['timecreated'];
    $tamount = $value['duration'] - $value['timecreated'];
    if($tresult < 0) $tresult = 0;
    $headingtext='';
    if($statustext==='waiting') $headingtext='🔒 대기 | ';
    elseif(strpos($trackingtext, '개념') !== false) $headingtext='🌱 준비 | ';
    elseif(strpos($trackingtext, '유형') !== false || strpos($trackingtext, '단원') !== false || strpos($trackingtext, '도약') !== false) $headingtext='🍎 응시 | ';
    elseif(strpos($trackingtext, '오답') !== false) $headingtext='📝 오답 | ';
    elseif(strpos($trackingtext, '과제') !== false) $headingtext='📚 과제 | ';
    elseif(strpos($trackingtext, '시험') !== false) $headingtext='🏬 시험 | ';
    else $headingtext='🌈 기타 | ';

    // 원시 값(분)으로 계산 (그래프에 활용하기 위해)
    $finalMinutes = round(($value['timefinished'] - $value['timecreated'])/60, 0);
    if($finalMinutes < 0) $finalMinutes = 0;
    if($finalMinutes > 60) $finalMinutes = 60;
    
    if($tresult > $tamount)
        $tresult_disp = '<div style="display: inline;color:#fcddd9;">'.round(($tresult)/60,0).'분</div>';
    else 
        $tresult_disp = '<div style="display: inline;color:green;">'.round(($tresult)/60,0).'분</div>';
    $tamount_disp = '<div style="display: inline;">'.round(($tamount)/60,0).'분</div>';

    $tinterval = $tend_prev - $value['duration'];
    $statuscolor=''; $rowheight='20px';
    $comeon='';$realtimecomment='';
    if($statustext==='begin')
    { 
        $currenttrackingid=$value['id'];
        $lefttime = round(($value['duration']-$timecreated)/60,0);
        $statustext = '<button id="completebtn" style="background-color: #4CAF50; border: none; color: white; padding:2px 5px; text-align: center; font-size: 16px; cursor: pointer; border-radius: 10px;" onmouseover="this.style.backgroundColor=#45a049;" onmouseout="this.style.backgroundColor=#4CAF50;" ONCLICK="evaluateResult(\''.$studentid.'\');">완료</button> <img ONCLICK="addTime(\''.$studentid.'\');" style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/addtime.png width=20> ';
        $duetime = '<div style="float: right; white-space: nowrap;" id="second">('.$lefttime.'분 남음)</div>';
        $statuscolor='#e0e0e0'; $rowheight='50px';
        echo '<script>
        var counter = '.$lefttime.'; 
        var Userid= \''.$studentid.'\'; 
        var Inputtext= \''.$trackingtext.'\'; 

        if (counter > 3 ) document.title ="🟢수학일기(" + counter + "분) " ;  
        else if(counter <= 3 && counter >= 0)document.title ="🟡수학일기(" + counter + "분) " ;
        else document.title ="🔴수학일기(" + counter + "분) " ;  

        var auto_refresh = setInterval(function () {
            var newcontent=counter+"분 남음";
            $("#second").html(newcontent);
             if (counter <= 0 ) 
                {
                document.getElementById("completebtn").click();
                document.title ="🔴수학일기(" + counter + "분) " ;  
                }
            
            else if (counter <= 3 && counter % 3 === 0) 
                {
                document.title ="🟡수학일기(" + counter + "분) " ;  
                alertTime(\''.$studentid.'\');
                    $.ajax({
                    url:"check.php",
                    type: "POST",
                    dataType:"json",
                    data : {
                    "eventid":\'31\',
                    "userid":Userid,       
                    "inputtext":Inputtext,        
                    },
                    success:function(data){}
                     })
                }
            else if(counter % 30 === 0)
                {
                document.title ="🟡수학일기(" + counter + "분) " ;  
                $.ajax({
                    url:"check.php",
                    type: "POST",
                    dataType:"json",
                    data : {
                    "eventid":\'32\',
                    "userid":Userid,       
                    "inputtext":Inputtext,        
                    },
                    success:function(data){}
                     })
                }
            else
                {
                document.title ="🟢수학일기(" + counter + "분) " ;  
                }
            counter=counter-1;
        },60000);  
        </script>';
    }
    elseif($statustext==='homework')
    { 
        $lefttime=''; 
        $tamount_disp = date('Y-m-d', $value['duration']);
        $statustext = '<span onclick="hideItem(\''.$trackingid.'\');"><b style="color:blue;">과제</b></span> '; 
        $duetime='';
    }
    elseif($statustext==='weeklyreview')
    { 
        $lefttime='';
        $tamount_disp = date('Y-m-d', $value['duration']);
        $statustext = '<span onclick="hideItem(\''.$trackingid.'\');"><b style="color:blue;">주간복습</b></span> '; 
        $duetime='';
    }
    elseif($statustext==='schedule')
    { 
        $lefttime='';
        $tamount_disp = date('Y-m-d', $value['duration']);
        $statustext = '<span onclick="hideItem(\''.$trackingid.'\');"><b style="color:blue;">일정</b></span> '; 
        $duetime='';
    }
    elseif($statustext==='complete')
    {
        $duetime='';     
        if($value['type']==='schedule' || $value['type']==='homework') $tamount_disp = date('Y-m-d', $value['duration']);
        $totalduration += $value['duration'] - $value['timecreated'];
        $np++;
        $pmresult = $pmresult + $value['result'];
        // 그래프용 데이터 추가 (완료 상태인 경우)
        if($value['timefinished'] > $value['timecreated']){
            $graphData[] = array(
                'time' => date("m-d H:i", $value['timecreated']),
                'final' => $finalMinutes,
                'wbtimeave' => min(round($value['wbtimeave'],0),30)
            );
        }

      if($np==1)
        {
        $realtimecomment='<span style="background: skyblue; border-radius: 0.4em; display: inline-block; margin-top:15px;font-size: 16px;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"> '.iconv_substr($value['feedback'], 0, 20, "utf-8").'...</span>';
        $alertmessage='다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !';
        }
     

    }
    elseif($statustext==='waiting')
    { 
        $lefttime='';
        $tamount_disp = '';
        $statustext = '<span onclick="hideItem(\''.$trackingid.'\');"><b style="color:blue;">일정</b></span> '; 
        $duetime='';
    }
    elseif($statustext==='context') continue;

    $warningtext='';
    if($tinterval>600 && $tinterval<3600*6 && ($statustext==='begin' || $statustext==='complete'))
        $warningtext='<SPAN style="color:red;"> | 이탈 ('.round($tinterval/60,0).')</SPAN> '; 
    $tend_prev = $value['timecreated'];
    if($value['result']==3)
        $statustext='<span style="color:green;">매우 만족</span> ('.$value['ndisengagement'].')';
    elseif($value['result']==2)
        $statustext='<span style="color:grey;">만족</span> ('.$value['ndisengagement'].')';
    elseif($value['result']==1)
        $statustext='<span style="color:orange;">불만족</span> ('.$value['ndisengagement'].')';
    $comment='';
    $feedbacktext='';
    if($value['comment']!=NULL)
        $comment=' &nbsp;<div style="margin-bottom:5px;" class="tooltip3">🌞<span class="tooltiptext3"><table align=center width=90%><tr><td>'.$value['comment'].'</td></tr></table></span></div>';

    if($value['feedback']!=NULL)
    $feedbacktext=' &nbsp;<div class="tooltip3"><span style="font-size:18px;">👦🏻</span>'.$realtimecomment.'<span class="tooltiptext3"><table align=center width=90%><tr><td>'.$value['feedback'].'</td></tr></table></span>'.$comeon.'</div>';

    $thislog=$DB->get_record_sql("SELECT * FROM mdl_abessi_reflections WHERE trackingid ='$trackingid' "); 
   
    if($role!=='student') $hidebtn = '<input type="checkbox" name="checkAccount" onclick="hideItem(\''.$trackingid.'\');"> '; 

    if($thislog->id==NULL)
        $eva_status='🗨️';
    else 
        $eva_status='📑';

    $fixtext='<img onclick="fixText(\''.$trackingid.'\',\''.$trackingtext.'\');" style="margin-bottom:5px" src=https://mathking.kr/Contents/IMAGES/createnote.png width=12>';
    $report='<a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/challenge_report.php?tid='.$trackingid.'&userid='.$studentid.'">'.$eva_status.'</a>';
    $activitieslog='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?userid='.$studentid.'&tb='.$value['timecreated'].'&te='.$value['duration'].'">📜</a>';
    if($value['status']==='waiting') $directionlist0.='<tr style="background-color:#ebf8fc;" height='.$rowheight.'px>
    <td align=left>'.$hidebtn.' <span style="cursor: pointer;" onclick="BeginInstruction(\''.$trackingid.'\',\''.$studentid.'\',\''.$value['text'].'\');">시작🔄</span>&nbsp;&nbsp;&nbsp;&nbsp;  </td>
    <td><div style="float: left;"><SPAN ONCLICK="addComment(\''.$trackingid.'\',\''.$studentid.'\',\''.$value['feedback'].'\');">'.$headingtext.'</span> '.$value['text'].' '.$fixtext.$warningtext.$comment.$feedbacktext.'</div></td><td style="white-space: nowrap;"> </td>
    <td width=5% align=center> </td>    <td></td>
    <td style="white-space: nowrap;"> </td>
    <td> </td>
    <td> </td>
    </tr>';
	elseif($value['timecreated'] > $timecreated-43200) $directionlist1.='<tr style="background-color:#ebf8fc;" height='.$rowheight.'px>
        <td align=left>'.$hidebtn.' '.date("m/d h:i", $value['timecreated']).'</td>
        <td style="white-space: nowrap;"><div style="float: left;"><SPAN ONCLICK="addComment(\''.$trackingid.'\',\''.$studentid.'\',\''.$value['feedback'].'\');">'.$headingtext.'</span> '.$value['text'].' '.$fixtext.$warningtext.$comment.$feedbacktext.'</div></td><td> '.$duetime.'</td>
        <td width=5% align=center>'.$tamount_disp.'</td>
        <td>'.$tresult_disp.'</td>
        <td style="white-space: nowrap;">'.$statustext.'</td>
        <td style="white-space: nowrap;"> '.$report.' ('.$value['nwboard'].')</td>
        <td style="white-space: nowrap;">'.$activitieslog.'('.round($value['wbtimeave'],0).')</td>
        </tr>';
    else $directionlist2.='<tr style="background-color:white;" height='.$rowheight.'px>
        <td align=left>'.$hidebtn.' '.date("m/d h:i", $value['timecreated']).'</td>
        <td style="white-space: nowrap;"><div style="float: left;"><SPAN ONCLICK="addComment(\''.$trackingid.'\',\''.$studentid.'\',\''.$value['feedback'].'\');">'.$headingtext.'</span> '.$value['text'].' '.$fixtext.$warningtext.$comment.$feedbacktext.'</div></td><td> '.$duetime.'</td>
        <td width=5% align=center>'.$tamount_disp.'</td>
        <td>'.$tresult_disp.'</td>
        <td style="white-space: nowrap;">'.$statustext.'</td>
        <td style="white-space: nowrap;"> '.$report.' ('.$value['nwboard'].')</td>
        <td style="white-space: nowrap;">'.$activitieslog.'('.round($value['wbtimeave'],0).')</td>
        </tr>';

    if($value['status']==='complete') $prev_time=date("m_d", $value['timecreated']);
} 

if($cntinput != NULL) echo '<script>    
window.onload = function() {
    ContentsGoalInput(\'' . $studentid . '\', \'' . $cntinput . '\');
};
</script>';
    
$totalduration=round($totalduration/60/$np,0);
$pmresult=round($pmresult/$np/3*10,0);
if(is_nan($totalduration) || $totalduration>=60)$totalduration=60;
if(is_nan($pmresult))$pmresult=0;
$DB->execute("UPDATE {abessi_indicators} SET pmresult='$pmresult', npomodoro='$np', kpomodoro='$totalduration' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  
$goalid=$checkgoal->id;
$headtext='  |  <a href="https://blog.naver.com/esuai02/223627321735" target="_blank">포모도르 공부법</a> | <audio controls style="width:150px;height:20px;" 
                       src="https://mathking.kr/Contents/Development/%ED%8F%AC%EB%AA%A8%EB%8F%84%EB%A1%9C%EC%99%80%20KTM%20%ED%95%99%EC%8A%B5%EB%B2%95.wav">
                </audio>  |   <a style="font-size:30px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$studentid.'"target="_blank">🎭</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/attendancerecords.php?userid='.$studentid.'"target="_blank">출결</a>  | <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$studentid.'&tp=7257600">최근 3개월</a>';

// 복사 버튼 추가 
$copyButtonHtml = '<button id="copyButton" class="btn btn-success" title="학습 내용을 클립보드에 복사" style="position: fixed; top: 0; right: 10px; z-index: 1000; padding: 10px 15px; font-weight: bold; border-radius: 0 0 15px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); cursor: pointer;"><i class="fas fa-clipboard" aria-hidden="true"></i></button>';
// 복사 버튼은 페이지 하단에 출력하므로 여기서는 추가하지 않음
// $headtext .= $copyButtonHtml;

// 복사할 내용 준비
$copyContent = $thisuser->firstname . $thisuser->lastname . "의 이번주 공부 내용은 다음과 같습니다.\n\n";

// 대기 중인 활동 정리
$waitingActivities = strip_tags(str_replace(['<tr>', '</tr>', '<td>', '</td>'], ["\n", "", "", " | "], $directionlist0));
if (trim($waitingActivities) !== '') {
    $copyContent .= "【대기 중인 활동】\n" . $waitingActivities . "\n\n";
}

// 오늘 활동 정리
$todayActivities = strip_tags(str_replace(['<tr>', '</tr>', '<td>', '</td>'], ["\n", "", "", " | "], $directionlist1));
if (trim($todayActivities) !== '') {
    $copyContent .= "【오늘 활동】\n" . $todayActivities . "\n\n";
}

// 지난 활동 정리
$pastActivities = strip_tags(str_replace(['<tr>', '</tr>', '<td>', '</td>'], ["\n", "", "", " | "], $directionlist2));
if (trim($pastActivities) !== '') {
    $copyContent .= "【지난 활동】\n" . $pastActivities . "\n\n";
}

// 오늘 목표 추가
if (!empty($checkgoal->text)) {
    $copyContent .= "【오늘 목표】\n" . $checkgoal->text . "\n\n";
}

// 주간 목표 추가
if (!empty($wgoal->text)) {
    $copyContent .= "【주간 목표】\n" . $wgoal->text . "\n\n";
}

$copyContent .= "이상의 값들을 분석하여 학생의 지난 일주일간의 학습 여정을 추론해줘. 추론된 결과를 토대로 학생의 학습여정을 학생의 화법으로 학습일지 스토리텔링을 블로그 형식으로 작성해줘.";

if($mode==='parental') {
    echo '<br><div  class="top-menu"><table align="left"><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1" class="btn btn-sm btn-info">일정</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800" class="btn btn-sm btn-info">계획</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$studentid.'" class="btn btn-sm btn-danger">일지</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200" class="btn btn-sm btn-info">오늘</a></td><td></td>'.$teachereval.'</tr></table></div>
         <table align="left" width="80%">
         <tr><td> </td><td width="60%"> </td><td><td align="center">Plan</td><td align="center">Final</td><td align="center">상태</td><td></td></tr>
         '.$directionlist0.$directionlist1.'
<!-- 두 그래프를 나란히 표시할 컨테이너 -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin: 3px auto; width: 90%;">
    <div style="flex: 1; margin-right: 10px;">
        <canvas id="chartCanvasFinal" style="width:100%; height:200px;"></canvas>
    </div>
    <div style="flex: 1; margin-left: 10px;">
        <canvas id="chartCanvasWbtimeave" style="width:100%; height:200px;"></canvas>
    </div>
</div>'.$directionlist2.'
         </table>';
} 
else {
    echo '<table align=center width=90%>
            <tr><td align=center><img src="https://mathking.kr/Contents/IMAGES/std1.png" width=100%></td></tr>
         </table>
         <table align=center width=70%>
            <tr><td>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$thisuser->firstname.$thisuser->lastname.'</a> '.$usercontext.'
                </td>
                <td>
                    <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1" target="_blank">
                        <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=60px>
                    </a>
                </td>
                <td>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'">
                        <img style="margin-bottom:0px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40>
                    </a> &nbsp;                     
                </td>
                <td>책/프린트 공부 <input type="checkbox" name="checkAccount" '.$status5.' onClick="ChangeCheckBox(333,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/> </td>
                <td>DMN 휴식 <input type="checkbox" name="checkAccount" '.$status4.' onClick="Resttime(33,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/></td>
                <td>
                    <button onclick="evaluateStudentAttitude(\''.$studentid.'\')"
                            style="background-color: #ff9800; border: none; color: white; padding: 5px 10px;
                                   text-align: center; font-size: 14px; cursor: pointer; border-radius: 5px;
                                   font-weight: bold;"
                            onmouseover="this.style.backgroundColor=\'#fb8c00\'"
                            onmouseout="this.style.backgroundColor=\'#ff9800\'">
                        📊 학습태도평가
                    </button>
                </td>
                <td>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/alt42_index.php?userid='.$studentid.'&view=teacher" target="_blank">🤖</a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly letter.php?userid='.$studentid.'" target="_blank">
                        <span style="color:white;width:100px;">✉️</span>
                    </a>
                    <b style="color:#038cfc;"> </b>
                </td>
                <td align=right style="color:lightgrey;">'.$headtext.'</td>
            </tr>
         </table>
         <table align=center width=70%><tr><td width=70%>
         <table width=100%>
            <tr>
                <td style="white-space: nowrap;">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$studentid.'&type=init">
                        <img style="margin-bottom:0px;" src=https://mathking.kr/Contents/IMAGES/timefolding.png width=30>
                    </a> &nbsp;&nbsp;
                    <span onclick="addFixNote(\''.$studentid.'\');">오답 ➕</span>   <span  onclick="addDirectInput(\''.$studentid.'\');">직접 ➕</span> 
                </td>
                <td width="60%">&nbsp; 
                     
                   
                    &nbsp;&nbsp;&nbsp;&nbsp;
 
                    <span onclick="showalltext(\''.$checkgoal->text.'\');">'.iconv_substr($checkgoal->text, 0, 30, "utf-8").'</span>
                </td><td></td>
                <td align="center">Plan</td>
                <td align="center">Final</td>
                <td align="center">상태</td>
                <td></td>
            </tr>
            <tr><td align="left" width=80%><hr></td><td width="60%"><hr></td><td align="center"><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
            '.$directionlist0.$directionlist1.'
<!-- 두 그래프를 나란히 표시할 컨테이너 -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin: 30px auto; width: 90%;">
    <div style="flex: 1; margin-right: 10px;">
        <canvas id="chartCanvasFinal" style="width:100%; height:200px;"></canvas>
    </div>
    <div style="flex: 1; margin-left: 10px;">
        <canvas id="chartCanvasWbtimeave" style="width:100%; height:200px;"></canvas>
    </div>
</div>'.$directionlist2.'
         </table></td><td width=1%></td><td><div class="sticky-notes-container">
    <div class="sticky-notes-header">
        <table width=100%><tr><td><span class="sticky-notes-title">📝 메모장</span></td><td align=right>
        <button class="add-note-btn" onclick="addNewNote()"><i class="fas fa-plus"></i> 새 메모</button></td><td width=1%><button class="add-note-btn upload-image-btn" onclick="uploadImageNote()"><i class="fas fa-upload"></i></button></td><td> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/editmemos.php?userid='.$studentid.'"><i class="fas fa-pencil-alt"></i></a></td></tr></table>
        <input type="file" id="uploadImageInput" accept="image/*" style="display:none;">
    </div>
    <div class="sticky-notes-area" id="sticky-notes-area">
        <!-- 선생님 메모 영역 -->
        <div class="teacher-notes-section">
     
            <div class="teacher-notes-area" id="teacher-notes-area">
                <div class="loading-notes">선생님 메모가 없습니다.</div>
            </div>
        </div>
        
        <!-- 학생 메모 영역 -->
        <div class="student-notes-section">
            <div class="section-header">
                <span class="section-title"></span>
            </div>
            <div class="student-notes-area" id="student-notes-area">
                <div class="loading-notes">학생 메모가 없습니다.</div>
            </div>
        </div>
    </div>
    <!-- 전체 삭제 버튼 추가 -->
    <div class="sticky-notes-footer">
        <?php if($hasRecordingConsent): ?>
        <!-- 녹음 관련 인터페이스 -->
        <div class="recording-interface" id="recordingInterface">
            <button class="record-btn" id="recordBtn" onclick="toggleRecording()" title="녹음 시작/정지">
                <i class="fas fa-microphone" id="recordIcon"></i>
            </button>
            <button class="record-list-btn" onclick="showRecordingList()" title="녹음 목록">
                <i class="fas fa-list-ul"></i>
            </button>
            <div class="recording-status" id="recordingStatus" style="display: none;">
                <span class="recording-time" id="recordingTime">00:00</span>
                <div class="recording-indicator"></div>
            </div>
        </div>
        <?php else: ?>
        <!-- 녹음 동의 요청 버튼 -->
        <div class="consent-interface" id="consentInterface">
            <button class="consent-btn" onclick="showConsentModal()" title="녹음 동의">
                <i class="fas fa-microphone-slash"></i>
                <span class="consent-text">녹음동의</span>
            </button>
        </div>
        <?php endif; ?>
        <button class="callback-all-btn" onclick="openCallbackModal()" title="전체 알림 설정">
            <i class="fas fa-clock"></i>
        </button>
        <button class="delete-all-btn" onclick="deleteAllNotes()" title="모든 메모 삭제">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
</div>
</td></tr></table>';
}

echo '<script>
function ShowMessage(Alerttext){
    swal("잠깐 !",Alerttext, {buttons: false,timer: 5000});
}
function ChangeCheckBox(Eventid,Userid, Goalid,Checkvalue){
    var checkimsi = 0;
    var Nextgoal=\''.$checkgoal->comment.'\';
    if(Eventid==3 && Nextgoal=="" && Checkvalue==true)
    {
        swal("잠깐 !","다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !", {buttons: false,timer: 5000});
        location.reload(); 
    }
    else
    {
        if(Checkvalue==true){
            checkimsi = 1;
        }
        swal("처리되었습니다.", {
            buttons: false,
            timer: 500,
        });
        $.ajax({
            url:"../students/check.php",
            type: "POST",
            dataType:"json",
            data : {
                "userid":Userid,       
                "goalid":Goalid,
                "checkimsi":checkimsi,
                "eventid":Eventid,
            },
            success:function(data){}
        });
    } 
} 

function Resttime(Eventid,Userid,Goalid,Checkvalue)
{
    var checkimsi = 0;
    var Timeleft= \''.$beforebreak.'\';
    var TimebeforeFinish= 40;
    if(Checkvalue==true)
    {
        checkimsi = 1;
        if(Timeleft<0)
        {
            Swal.fire({
                backdrop: true,
                position:"top-center",
                showConfirmButton: false,
                customClass: {
                    container: "my-background-color"
                },
                html:
                \'<table align="center" style="width:100%; height:100%; margin:0; padding:0;"><tr><td style="width:100%; height:100%; margin:0; padding:0;"><iframe style="border: none; width:100%; height:100%; margin:0; padding:0; position:fixed; top:0; left:0;" src="https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/growthmindset.php?id='.$studentid.'&mode=autoclick" ></iframe></td></tr></table>\',
            });
            
            $.ajax({
                url:"../students/check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "userid":Userid,       
                    "goalid":Goalid,
                    "checkimsi":checkimsi,
                    "eventid":Eventid,
                },
                success:function(data){}
            });
        }
        else if(TimebeforeFinish<30)
        {
            swal("귀가시간이 다가 오고 있어요. 마무리 활동 후 귀가검사를 준비해 주세요 ^^", {buttons: false,timer: 3000});
            setTimeout(function() {location.reload(); },3000);
        }
        else 
        {
            swal("힘내세요 ! " + Timeleft + "분 더 공부하시면 휴식을 취하실 수 있습니다.", {buttons: false,timer: 3000});
            setTimeout(function() {location.reload(); },1000);
        }				
    }
    else
    {
        swal("처리되었습니다.", {
            buttons: false,
            timer: 500,
        });
        if(Timeleft<0)
        {
            $.ajax({
                url:"../students/check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "userid":Userid,       
                    "goalid":Goalid,
                    "checkimsi":checkimsi,
                    "eventid":Eventid,
                },
                success:function(data){}
            });
        }
        else
        {
            $.ajax({
                url:"../students/check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "userid":Userid,       
                    "goalid":Goalid,
                    "checkimsi":checkimsi,
                    "eventid":\'331\',
                },
                success:function(data){}
            });
        }
    }				
}

// 메모 관련 변수들
let currentNotes = [];
let currentUserId = '.$studentid.';
let userRole = "'.$role.'";
let loggedInUserId = '.$USER->id.';
let hasRecordingConsent = '.($hasRecordingConsent ? 'true' : 'false').';
let activeCallbacks = []; // 활성 콜백 목록

// 녹음 관련 변수들
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;
let recordingStartTime = null;
let recordingTimer = null;
let silenceTimer = null;
let audioContext = null;
let analyser = null;
let microphone = null;
let silenceThreshold = -50; // dB
let silenceTimeout = 5 * 60 * 1000; // 5분

// jQuery 로드 순서 문제를 방지하기 위해 window 로드 후 메모 불러오기
window.addEventListener("load", function() {
    loadNotes();
    checkMonitoringStatus();
});

// monitoring 상태 확인
function checkMonitoringStatus() {
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "get_callbacks",
            userid: currentUserId
        },
        dataType: "json",
        success: function(response) {
            if (response.success && response.callbacks) {
                // 현재 시간
                const currentTime = Math.floor(Date.now() / 1000);
                
                // monitoring 상태이고 아직 시간이 지나지 않은 콜백 필터링
                activeCallbacks = response.callbacks.filter(callback => {
                    return callback.status === "monitoring" && callback.timefinish > currentTime;
                });
                
                // 시계 아이콘 색상 변경
                if (activeCallbacks.length > 0) {
                    $(".callback-all-btn").addClass("monitoring-active");
                    $(".clock-note-btn").addClass("monitoring-active");
                } else {
                    $(".callback-all-btn").removeClass("monitoring-active");
                    $(".clock-note-btn").removeClass("monitoring-active");
                }
            }
        },
        error: function() {
            console.log("콜백 상태 확인 실패");
        }
    });
}

// 메모 불러오기 함수
function loadNotes() {
    $.ajax({
        url: "../api/stickynotes_api.php",
        type: "GET",
        data: {
            action: "get_notes",
            userid: currentUserId
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // API가 객체 또는 배열을 반환할 수 있으므로 확실히 배열 형태로 변환
                if (Array.isArray(response.notes)) {
                    currentNotes = response.notes;
                } else {
                    currentNotes = Object.values(response.notes);
                }
                // created_at 필드를 정수형으로 변환 (문자열인 경우)
                currentNotes.forEach(n => {
                    if (typeof n.created_at === "string") {
                        n.created_at = parseInt(n.created_at, 10);
                    }
                });
                
                // 최신 메모가 가장 위에 오도록 created_at 기준 내림차순 정렬
                currentNotes.sort((a, b) => b.created_at - a.created_at);
                
                renderNotes();
            } else {
                showNoteError("메모를 불러오는데 실패했습니다.");
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 메모 렌더링 함수
function renderNotes() {
    const teacherNotesArea = document.getElementById("teacher-notes-area");
    const studentNotesArea = document.getElementById("student-notes-area");
    
    // 영역 초기화
    teacherNotesArea.innerHTML = "";
    studentNotesArea.innerHTML = "";
    
    if (currentNotes.length === 0) {
        teacherNotesArea.innerHTML = `<div class="empty-notes">선생님 메모가 없습니다.</div>`;
        studentNotesArea.innerHTML = `<div class="empty-notes">학생 메모가 없습니다.</div>`;
        return;
    }
    
    // 메모를 선생님과 학생으로 분리 (DB의 author_role 필드 기반)
    const teacherNotes = [];
    const studentNotes = [];
    
    currentNotes.forEach(note => {
        // author_role 필드를 기반으로 분류
        if (note.author_role === "student") {
            studentNotes.push(note);
        } else {
            // author_role이 "student"가 아니거나 null인 경우 선생님 메모로 분류
            teacherNotes.push(note);
        }
    });
    
    // 선생님 메모 렌더링
    if (teacherNotes.length === 0) {
        teacherNotesArea.innerHTML = `<div class="empty-notes">선생님 메모가 없습니다.</div>`;
    } else {
        teacherNotes.forEach(note => {
            const noteEl = createNoteElement(note);
            teacherNotesArea.appendChild(noteEl);
        });
    }
    
    // 학생 메모 렌더링
    if (studentNotes.length === 0) {
        studentNotesArea.innerHTML = `<div class="empty-notes">학생 메모가 없습니다.</div>`;
    } else {
        studentNotes.forEach(note => {
            const noteEl = createNoteElement(note);
            studentNotesArea.appendChild(noteEl);
        });
    }
}

// 메모 요소 생성 함수
function createNoteElement(note) {
    const noteEl = document.createElement("div");
    let noteClasses = `sticky-note ${note.color || "yellow"}`;
    
    // 학생이 자신의 메모인 경우 특별한 클래스 추가
    if (userRole === "student" && note.author_role === "student" && note.authorid == loggedInUserId) {
        noteClasses += " student-own-note";
    }
    
    noteEl.className = noteClasses;
    noteEl.setAttribute("data-id", note.id);
    
    // 경과 시간 계산 (초/분/시간/일/주)
    const elapsedText = formatElapsed(note.created_at);
    
    // URL을 링크 아이콘으로 변환하는 함수
    function linkifyWithIcon(text) {
        // 정규식: http(s):// 또는 www.로 시작하는 URL
        const urlRegex = /(https?:\/\/[\w\-._~:/?#[\]@!$&"()*+,;=%]+|www\.[\w\-._~:/?#[\]@!$&"()*+,;=%]+)/g;
        return text.replace(urlRegex, function(url) {
            let href = url;
            if (!href.match(/^https?:\/\//)) {
                href = "http://" + href;
            }
            return `<a href="${href}" target="_blank" style="text-decoration:none;"><i class="fas fa-link"></i></a>`;
        });
    }
    
    // note.content가 이미지 태그 등 HTML이 포함될 수 있으므로, 텍스트만 변환
    let contentHtml = note.content;
    // 만약 이미지가 아니라면 링크 변환
    if (!/^<img/i.test(contentHtml.trim())) {
        contentHtml = linkifyWithIcon(contentHtml);
    }
    
    // 액션 버튼 생성 (권한이 있는 경우에만)
    let actionButtons = "";
    if (userRole !== "student") {
        // 선생님의 경우: 모든 메모에 편집, 시계, 삭제 버튼 모두 표시
        actionButtons = `
            <button class="edit-note-btn" onclick="editNote(${note.id})" title="메모 수정"><i class="fas fa-pen"></i></button>
            <button class="clock-note-btn" onclick="setNoteCallback(${note.id})" title="알림 설정">⏰</button>
            <button class="delete-note-btn" onclick="deleteNote(${note.id})" title="메모 삭제"><i class="fas fa-trash"></i></button>
        `;
    } else if (note.author_role === "student" && note.authorid == loggedInUserId) {
        // 학생의 경우: 자신이 작성한 학생 메모에 편집, 시계 버튼 표시
        actionButtons = `
            <button class="edit-note-btn" onclick="editNote(${note.id})" title="메모 수정"><i class="fas fa-pen"></i></button>
            <button class="clock-note-btn" onclick="setNoteCallback(${note.id})" title="알림 설정">⏰</button>
        `;
    }
    
    noteEl.innerHTML = `
        <div class="note-header">
            <span class="note-date">${elapsedText}</span>
            <div class="note-actions">
                ${actionButtons}
            </div>
        </div>
        <div class="note-content">${contentHtml}</div>
    `;
    
    return noteEl;
}

// 경과 시간을 사람이 읽기 쉬운 형태로 변환
function formatElapsed(createdAtSec) {
    const nowSec = Math.floor(Date.now() / 1000);
    let diff = nowSec - createdAtSec;
    if (diff < 0) diff = 0;
    if (diff < 60) {
        return `${diff}초 전`;
    }
    const minutes = Math.floor(diff / 60);
    if (minutes < 60) {
        return `${minutes}분 전`;
    }
    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}시간 전`;
    }
    const days = Math.floor(hours / 24);
    if (days < 7) {
        return `${days}일 전`;
    }
    const weeks = Math.floor(days / 7);
    return `${weeks}주 전`;
}

// 새 메모 추가
function addNewNote() {
    Swal.fire({
        title: "새 메모",
        html: `
            <textarea id="note-content" class="swal2-textarea" placeholder="메모 내용을 입력하세요" rows="4"></textarea>
            <div class="color-selector">
                <span class="color-option yellow active" data-color="yellow"></span>
                <span class="color-option green" data-color="green"></span>
                <span class="color-option blue" data-color="blue"></span>
                <span class="color-option pink" data-color="pink"></span>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "저장",
        cancelButtonText: "취소",
        didOpen: () => {
            // 색상 선택 이벤트
            const colorOptions = document.querySelectorAll(".color-option");
            colorOptions.forEach(option => {
                option.addEventListener("click", () => {
                    colorOptions.forEach(o => o.classList.remove("active"));
                    option.classList.add("active");
                });
            });
        },
        preConfirm: () => {
            const content = document.getElementById("note-content").value.trim();
            const color = document.querySelector(".color-option.active").getAttribute("data-color");
            
            if (!content) {
                Swal.showValidationMessage("메모 내용을 입력해주세요");
                return false;
            }
            
            return { content, color };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const { content, color } = result.value;
            saveNote(content, color);
        }
    });
}

// 메모 수정
function editNote(noteId) {
    const note = currentNotes.find(n => n.id == noteId);
    if (!note) return;
    
    // 권한 체크: 선생님이거나 자신이 작성한 메모인 경우에만 수정 가능
    const canEdit = (userRole !== "student") || (note.author_role === "student" && note.authorid == loggedInUserId);
    if (!canEdit) {
        showNoteError("이 메모를 수정할 권한이 없습니다.");
        return;
    }
    
    Swal.fire({
        title: "메모 수정",
        html: `
            <textarea id="note-content" class="swal2-textarea" rows="4">${note.content}</textarea>
            <div class="color-selector">
                <span class="color-option yellow ${note.color === "yellow" ? "active" : ""}" data-color="yellow"></span>
                <span class="color-option green ${note.color === "green" ? "active" : ""}" data-color="green"></span>
                <span class="color-option blue ${note.color === "blue" ? "active" : ""}" data-color="blue"></span>
                <span class="color-option pink ${note.color === "pink" ? "active" : ""}" data-color="pink"></span>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "저장",
        cancelButtonText: "취소",
        didOpen: () => {
            // 색상 선택 이벤트
            const colorOptions = document.querySelectorAll(".color-option");
            colorOptions.forEach(option => {
                option.addEventListener("click", () => {
                    colorOptions.forEach(o => o.classList.remove("active"));
                    option.classList.add("active");
                });
            });
        },
        preConfirm: () => {
            const content = document.getElementById("note-content").value.trim();
            const color = document.querySelector(".color-option.active").getAttribute("data-color");
            
            if (!content) {
                Swal.showValidationMessage("메모 내용을 입력해주세요");
                return false;
            }
            
            return { content, color };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const { content, color } = result.value;
            updateNote(noteId, content, color);
        }
    });
}

// 메모 삭제
function deleteNote(noteId) {
    const note = currentNotes.find(n => n.id == noteId);
    if (!note) return;
    
    // 권한 체크: 선생님이거나 자신이 작성한 메모인 경우에만 삭제 가능
    const canDelete = (userRole !== "student") || (note.author_role === "student" && note.authorid == loggedInUserId);
    if (!canDelete) {
        showNoteError("이 메모를 삭제할 권한이 없습니다.");
        return;
    }
    
    Swal.fire({
        title: "메모 삭제",
        text: "이 메모를 삭제하시겠습니까?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "삭제",
        cancelButtonText: "취소"
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: "../api/stickynotes_api.php",
                type: "POST",
                data: {
                    action: "delete_note",
                    userid: currentUserId,
                    note_id: noteId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        currentNotes = currentNotes.filter(n => n.id != noteId);
                        renderNotes();
                        Swal.fire({
                            icon: "success",
                            title: "삭제 완료",
                            text: "메모가 삭제되었습니다.",
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        showNoteError("메모 삭제에 실패했습니다.");
                    }
                },
                error: function() {
                    showNoteError("서버 연결에 실패했습니다.");
                }
            });
        }
    });
}

// 메모 콜백 설정
function setNoteCallback(noteId) {
    const note = currentNotes.find(n => n.id == noteId);
    if (!note) return;
    
    // 모든 사용자가 콜백 설정 가능
    
    // 시간 선택 옵션 생성 (10분부터 60분까지)
    let timeOptions = "";
    for (let i = 10; i <= 60; i += 10) {
        timeOptions += `<option value="${i}">${i}분 후</option>`;
    }
    
    Swal.fire({
        title: "점검 알림 설정",
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">알림 시간 선택</label>
                    <select id="callback-time" class="swal2-input" style="width: 100%; padding: 8px;">
                        ${timeOptions}
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">알림 내용</label>
                    <input type="text" id="callback-content" class="swal2-input" value="선생님에게 점검받기" style="width: 100%; padding: 8px;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "설정",
        cancelButtonText: "취소",
        confirmButtonColor: "#007bff",
        preConfirm: () => {
            const time = document.getElementById("callback-time").value;
            const content = document.getElementById("callback-content").value;
            
            if (!content.trim()) {
                Swal.showValidationMessage("알림 내용을 입력해주세요");
                return false;
            }
            
            return { time: parseInt(time), content };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const { time, content } = result.value;
            saveCallback(noteId, time, content);
        }
    });
}

// 콜백 저장
function saveCallback(noteId, timeMinutes, content) {
    const currentTime = Math.floor(Date.now() / 1000); // 현재 시간 (Unix timestamp)
    const finishTime = currentTime + (timeMinutes * 60); // 선택된 시간만큼 더하기
    
    console.log("Saving callback - content:", content, "timeMinutes:", timeMinutes);
    
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "save_callback",
            userid: currentUserId,
            status: "monitoring",
            ncall: 0,
            timecreated: currentTime,
            timefinish: finishTime,
            content: content,
            note_id: noteId
        },
        dataType: "json",
        success: function(response) {
            console.log("Response:", response);
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "알림 설정 완료",
                    text: `${timeMinutes}분 후에 알림이 설정되었습니다.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                // monitoring 상태 다시 확인
                checkMonitoringStatus();
            } else {
                showNoteError("알림 설정에 실패했습니다: " + (response.error || ""));
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 새 메모 저장
function saveNote(content, color) {
    $.ajax({
        url: "../api/stickynotes_api.php",
        type: "POST",
        data: {
            action: "add_note",
            userid: currentUserId,
            authorid: loggedInUserId,
            content: content,
            color: color
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // 새로 추가된 메모를 배열에 추가
                currentNotes.push(response.note);
                // 최신 메모가 맨 위에 오도록 정렬
                currentNotes.sort((a, b) => b.created_at - a.created_at);
                renderNotes();
                Swal.fire({
                    icon: "success",
                    title: "메모 추가",
                    text: "새 메모가 추가되었습니다.",
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                showNoteError("메모 저장에 실패했습니다.");
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 메모 업데이트
function updateNote(noteId, content, color) {
    $.ajax({
        url: "../api/stickynotes_api.php",
        type: "POST",
        data: {
            action: "update_note",
            userid: currentUserId,
            note_id: noteId,
            content: content,
            color: color
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // 수정된 메모 정보 업데이트
                const index = currentNotes.findIndex(n => n.id == noteId);
                if (index !== -1) {
                    currentNotes[index].content = content;
                    currentNotes[index].color = color;
                    // author_role 정보는 이미 있으므로 유지됨
                }
                renderNotes();
                Swal.fire({
                    icon: "success",
                    title: "수정 완료",
                    text: "메모가 수정되었습니다.",
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                showNoteError("메모 수정에 실패했습니다.");
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 에러 메시지 표시
function showNoteError(message) {
    Swal.fire({
        icon: "error",
        title: "오류",
        text: message,
        timer: 3000,
        showConfirmButton: false
    });
}

// 콜백 완료 처리
function completeCallback(callbackId) {
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "update_callback_status",
            userid: currentUserId,
            callback_id: callbackId,
            status: "finish"
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "완료",
                    text: "알림이 완료 처리되었습니다.",
                    timer: 2000,
                    showConfirmButton: false
                });
                // monitoring 상태 다시 확인
                checkMonitoringStatus();
            } else {
                showNoteError("완료 처리에 실패했습니다.");
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 콜백 시간 연장
function extendCallback(callbackId, additionalMinutes) {
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "extend_callback",
            userid: currentUserId,
            callback_id: callbackId,
            additional_minutes: additionalMinutes
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "시간 연장",
                    text: `${additionalMinutes}분이 추가되었습니다.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                // monitoring 상태 다시 확인
                checkMonitoringStatus();
            } else {
                showNoteError("시간 연장에 실패했습니다.");
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 일반 콜백 저장 (특정 메모와 연결되지 않은)
function saveCallbackGeneral(timeMinutes, content) {
    const currentTime = Math.floor(Date.now() / 1000); // 현재 시간 (Unix timestamp)
    const finishTime = currentTime + (timeMinutes * 60); // 선택된 시간만큼 더하기
    
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "save_callback",
            userid: currentUserId,
            status: "monitoring",
            ncall: 0,
            timecreated: currentTime,
            timefinish: finishTime,
            content: content,
            note_id: 0 // 특정 메모와 연결되지 않음
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "알림 설정 완료",
                    text: `${timeMinutes}분 후에 알림이 설정되었습니다.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                // monitoring 상태 다시 확인
                checkMonitoringStatus();
            } else {
                showNoteError("알림 설정에 실패했습니다.");
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 전체 알림 설정
function openCallbackModal() {
    // 모든 사용자가 사용 가능
    
    // 활성 모니터링이 있는지 확인
    if (activeCallbacks.length > 0) {
        // 가장 최근 콜백 정보 사용
        const latestCallback = activeCallbacks[0];
        const remainingTime = Math.ceil((latestCallback.timefinish - Math.floor(Date.now() / 1000)) / 60);
        
        // 시간 추가 옵션 생성 (10분부터 60분까지)
        let timeOptions = "";
        for (let i = 10; i <= 60; i += 10) {
            timeOptions += `<option value="${i}">${i}분 추가</option>`;
        }
        
        Swal.fire({
            title: "알림 완료하기",
            html: `
                <div style="text-align: left;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">현재 알림 내용</label>
                        <div style="padding: 10px; background-color: #f8f9fa; border-radius: 5px; color: #495057;">
                            ${latestCallback.text || "선생님에게 점검받기"}
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">남은 시간</label>
                        <div style="padding: 10px; background-color: #f8f9fa; border-radius: 5px; color: #dc3545; font-weight: bold;">
                            약 ${remainingTime}분 남음
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">시간 추가하기</label>
                        <select id="callback-time" class="swal2-input" style="width: 100%; padding: 8px;">
                            ${timeOptions}
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "완료하기",
            cancelButtonText: "추가",
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#007bff",
            reverseButtons: true,
            preConfirm: () => {
                return { action: "complete" };
            }
        }).then(result => {
            if (result.isConfirmed) {
                // 완료하기 클릭
                completeCallback(latestCallback.id);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // 추가 클릭
                const time = document.getElementById("callback-time").value;
                extendCallback(latestCallback.id, parseInt(time));
            }
        });
        return;
    }
    
    // 활성 모니터링이 없을 때는 기존 로직
    // 시간 선택 옵션 생성 (10분부터 60분까지)
    let timeOptions = "";
    for (let i = 10; i <= 60; i += 10) {
        timeOptions += `<option value="${i}">${i}분 후</option>`;
    }
    
    Swal.fire({
        title: "점검 알림 설정",
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">알림 시간 선택</label>
                    <select id="callback-time" class="swal2-input" style="width: 100%; padding: 8px;">
                        ${timeOptions}
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">알림 내용</label>
                    <input type="text" id="callback-content" class="swal2-input" value="선생님에게 점검받기" style="width: 100%; padding: 8px;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "설정",
        cancelButtonText: "취소",
        confirmButtonColor: "#007bff",
        preConfirm: () => {
            const time = document.getElementById("callback-time").value;
            const content = document.getElementById("callback-content").value;
            
            if (!content.trim()) {
                Swal.showValidationMessage("알림 내용을 입력해주세요");
                return false;
            }
            
            return { time: parseInt(time), content };
        }
    }).then(result => {
        if (result.isConfirmed) {
            const { time, content } = result.value;
            saveCallbackGeneral(time, content);
        }
    });
}

// 일반 콜백 저장 (특정 메모와 연결되지 않은)
function saveCallbackGeneral(timeMinutes, content) {
    const currentTime = Math.floor(Date.now() / 1000); // 현재 시간 (Unix timestamp)
    const finishTime = currentTime + (timeMinutes * 60); // 선택된 시간만큼 더하기
    
    $.ajax({
        url: "../api/callback_api.php",
        type: "POST",
        data: {
            action: "save_callback",
            userid: currentUserId,
            status: "monitoring",
            ncall: 0,
            timecreated: currentTime,
            timefinish: finishTime,
            content: content,
            note_id: 0 // 특정 메모와 연결되지 않음
        },
        dataType: "json",
        success: function(response) {
            console.log("Response:", response);
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "알림 설정 완료",
                    text: `${timeMinutes}분 후에 알림이 설정되었습니다.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                // monitoring 상태 다시 확인
                checkMonitoringStatus();
            } else {
                showNoteError("알림 설정에 실패했습니다: " + (response.error || ""));
            }
        },
        error: function() {
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}

// 전체 메모 삭제
function deleteAllNotes() {
    console.log("deleteAllNotes 함수 호출됨");
    console.log("현재 메모 개수:", currentNotes.length);
    console.log("사용자 역할:", userRole);
    
    if (currentNotes.length === 0) {
        showNoteError("삭제할 메모가 없습니다.");
        return;
    }
    
    // 권한 체크: 선생님만 전체 삭제 가능
    if (userRole === "student") {
        showNoteError("전체 삭제 권한이 없습니다.");
        return;
    }
    
    Swal.fire({
        title: "전체 메모 삭제",
        text: `모든 메모(${currentNotes.length}개)를 삭제하시겠습니까?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "모두 삭제",
        cancelButtonText: "취소",
        confirmButtonColor: "#e53935"
    }).then(result => {
        if (result.isConfirmed) {
            console.log("삭제 확인됨");
            
            // 모든 메모 ID 수집
            const noteIds = currentNotes.map(note => note.id);
            console.log("삭제할 메모 IDs:", noteIds);
            
            let deletedCount = 0;
            let totalCount = noteIds.length;
            let hasError = false;
            
            // 각 메모를 순차적으로 삭제
            noteIds.forEach((noteId, index) => {
                console.log(`메모 ${noteId} 삭제 시도 중...`);
                
                $.ajax({
                    url: "../api/stickynotes_api.php",
                    type: "POST",
                    data: {
                        action: "delete_note",
                        userid: currentUserId,
                        note_id: noteId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log(`메모 ${noteId} 삭제 응답:`, response);
                        deletedCount++;
                        
                        if (deletedCount === totalCount) {
                            // 모든 삭제가 완료되면
                            console.log("모든 메모 삭제 완료");
                            currentNotes = [];
                            renderNotes();
                            
                            if (!hasError) {
                                Swal.fire({
                                    icon: "success",
                                    title: "삭제 완료",
                                    text: `${totalCount}개의 메모가 모두 삭제되었습니다.`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(`메모 ${noteId} 삭제 실패:`, error);
                        hasError = true;
                        deletedCount++;
                        
                        if (deletedCount === totalCount) {
                            showNoteError("일부 메모 삭제에 실패했습니다.");
                        }
                    }
                });
            });
        }
    });
}
  
</script>';

echo '<span style="font-size:0.001px;color:white;">'.$contextinfo.'</span><br>';
echo '<span style="font-size:0.001px;color:white;">'.$todolist.'</span><br>';
echo $copyButtonHtml;

// 복사 기능 스크립트 추가
echo '<script>
var copyContent = ' . json_encode($copyContent) . ';

document.addEventListener("DOMContentLoaded", function() {
    var copyButton = document.getElementById("copyButton");
    if (copyButton) {
        copyButton.addEventListener("click", function() {
            // 클립보드에 복사
            navigator.clipboard.writeText(copyContent).then(function() {
                // 복사 성공 알림
                Swal.fire({
                    icon: "success",
                    title: "복사 완료",
                    text: "클립보드에 복사되었습니다.",
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // 버튼 아이콘 임시 변경
                var icon = copyButton.querySelector("i");
                icon.className = "fas fa-check";
                setTimeout(function() {
                    icon.className = "fas fa-clipboard";
                }, 2000);
            }).catch(function(err) {
                console.error("복사 실패:", err);
                Swal.fire({
                    icon: "error",
                    title: "복사 실패",
                    text: "클립보드 복사에 실패했습니다.",
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        });
    }
});
</script>';

 

// 하단에 그래프와 기존 스크립트, CSS 및 외부 라이브러리 로드

echo '<script> ShowMessage("'.$alertmessage.'"); </script>';
?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
// PHP의 $graphData 배열을 JSON으로 변환
var graphData = <?php echo json_encode($graphData); ?>;

// X축 라벨 생성 (공통)
var labels = [];
graphData.forEach(function(item) {
    labels.push(item.time);
});

// Final 데이터셋 생성
var finalData = [];
graphData.forEach(function(item) {
    finalData.push(item.final);
});

// wbtimeave 데이터셋 생성
var wbtimeaveData = [];
graphData.forEach(function(item) {
    wbtimeaveData.push(item.wbtimeave);
});

// 배열 역순으로 변경하여 x축 순서를 반전
labels.reverse();
finalData.reverse();
wbtimeaveData.reverse();

// 왼쪽 그래프: Final (분)
var ctxFinal = document.getElementById('chartCanvasFinal').getContext('2d');
var chartFinal = new Chart(ctxFinal, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: '세션별 소요시간',
            data: finalData,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: false,
            tension: 0.1
        }]
    },
    options: {
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '분';
                    }
                }
            }
        },
        scales: {
            y: {
                display: true,
                title: { display: true, text: '분' },
                beginAtZero: true
            }
        }
    }
});

// 오른쪽 그래프: wbtimeave (분)
var ctxWbtimeave = document.getElementById('chartCanvasWbtimeave').getContext('2d');
var chartWbtimeave = new Chart(ctxWbtimeave, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: '노트 작성시간',
            data: wbtimeaveData,
            borderColor: 'rgba(153, 102, 255, 1)',
            backgroundColor: 'rgba(153, 102, 255, 0.2)',
            fill: false,
            tension: 0.1
        }]
    },
    options: {
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '분';
                    }
                }
            }
        },
        scales: {
            y: {
                display: true,
                title: { display: true, text: '분' },
                beginAtZero: true
            }
        }
    }
});
</script>


<!-- 기존 나머지 스크립트 및 CSS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
 
<!--   Core JS Files   -->
<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
 
<!-- Bootstrap Notify -->
<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<!-- CSS Files -->
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/ready.min.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="../assets/css/demo.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
/* ... existing styles ... */

/* 포스트잇 스타일 */
.sticky-notes-container {
    display: flex;
    flex-direction: column;
    height: 70vh;
    width: 300px;
    background-color: #f5f5f5;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 1000;
    overflow: hidden;
}

.sticky-notes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background-color: #ffeb3b;
    border-bottom: 1px solid #e6d535;
}

.sticky-notes-title {
    font-weight: bold;
    font-size: 16px;
    color: #5f5c41;
}

.add-note-btn {
    background-color: #fff;
    color: #5f5c41;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.add-note-btn:hover {
    background-color: #f9f9f9;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}

.sticky-notes-area {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background-color: #f9f9f9;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.sticky-note {
    position: relative;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.sticky-note:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.sticky-note::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background-color: rgba(0,0,0,0.1);
}

.sticky-note.yellow {
    background-color: #fff9c4;
}

.sticky-note.green {
    background-color: #dcedc8;
}

.sticky-note.blue {
    background-color: #e3f2fd;
}

.sticky-note.pink {
    background-color: #f8bbd0;
}

.note-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.note-date {
    font-size: 12px;
    color: #666;
}

.note-actions {
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.sticky-note:hover .note-actions {
    opacity: 1;
}

.edit-note-btn, .delete-note-btn, .clock-note-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    padding: 3px 6px;
    color: #666;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.edit-note-btn:hover {
    background-color: rgba(0,0,0,0.05);
}

.delete-note-btn:hover {
    background-color: rgba(255,0,0,0.1);
    color: #e53935;
}

.clock-note-btn {
    color: #007bff !important;
}

.clock-note-btn:hover {
    background-color: rgba(0,123,255,0.1);
    color: #0056b3;
}

.clock-note-btn.monitoring-active {
    color: #dc3545 !important;
    animation: pulse-red 2s infinite;
}

.clock-note-btn.monitoring-active:hover {
    background-color: rgba(220,53,69,0.1);
    color: #bd2130 !important;
}

.note-content {
    font-size: 14px;
    line-height: 1.5;
    overflow-wrap: break-word;
    white-space: pre-wrap;
}

.loading-notes, .empty-notes {
    text-align: center;
    padding: 20px;
    color: #999;
    font-style: italic;
}

.empty-notes {
    line-height: 1.5;
}

/* 색상 선택 옵션 */
.color-selector {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.color-option {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.2s ease;
    border: 2px solid transparent;
}

.color-option:hover {
    transform: scale(1.1);
}

.color-option.active {
    transform: scale(1.1);
    border-color: #666;
}

.color-option.yellow {
    background-color: #fff9c4;
}

.color-option.green {
    background-color: #dcedc8;
}

.color-option.blue {
    background-color: #e3f2fd;
}

.color-option.pink {
    background-color: #f8bbd0;
}

.upload-image-btn { margin-left: 6px; }

/* 학생이 자신의 메모인 경우 연필 아이콘 항상 표시 */
.student-own-note .note-actions {
    opacity: 1;
}

/* 전체 삭제 버튼 스타일 */
.sticky-notes-footer {
    position: relative;
    height: 40px;
    background-color: #f9f9f9;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 8px;
}

/* 녹음 인터페이스 스타일 */
.recording-interface {
    display: flex;
    align-items: center;
    gap: 8px;
}

.record-btn, .record-list-btn {
    background-color: transparent;
    border: none;
    color: #666;
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: all 0.3s ease;
    opacity: 0.7;
}

.record-btn:hover, .record-list-btn:hover {
    color: #333;
    background-color: rgba(0,0,0,0.1);
    opacity: 1;
    transform: scale(1.1);
}

.record-btn.recording {
    color: #e53935;
    animation: pulse 1s infinite;
}

.record-btn.recording:hover {
    color: #c62828;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@keyframes pulse-red {
    0% { 
        opacity: 1;
        transform: scale(1);
    }
    50% { 
        opacity: 0.6;
        transform: scale(1.05);
    }
    100% { 
        opacity: 1;
        transform: scale(1);
    }
}

.recording-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #666;
}

.recording-time {
    font-family: monospace;
    font-weight: bold;
}

.recording-indicator {
    width: 8px;
    height: 8px;
    background-color: #e53935;
    border-radius: 50%;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

.delete-all-btn {
    background-color: transparent;
    border: none;
    color: #ccc;
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: all 0.3s ease;
    opacity: 0.3;
}

.callback-all-btn {
    background-color: transparent;
    border: none;
    color: #007bff;
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: all 0.3s ease;
    opacity: 0.7;
}

.delete-all-btn:hover {
    color: #e53935;
    background-color: rgba(229, 57, 53, 0.1);
    opacity: 1;
    transform: scale(1.1);
}

.callback-all-btn:hover {
    color: #0056b3;
    background-color: rgba(0, 123, 255, 0.1);
    opacity: 1;
    transform: scale(1.1);
}

.callback-all-btn.monitoring-active {
    color: #dc3545 !important;
    opacity: 1;
    animation: pulse-red 2s infinite;
}

.callback-all-btn.monitoring-active:hover {
    color: #bd2130 !important;
    background-color: rgba(220, 53, 69, 0.1);
}

.sticky-notes-container:hover .delete-all-btn {
    opacity: 0.7;
}

.sticky-notes-container:hover .callback-all-btn {
    opacity: 1;
}

/* 메모 영역 분리 스타일 */
.teacher-notes-section, .student-notes-section {
    margin-bottom: 15px;
}

.section-header {
    background-color: #f0f0f0;
    padding: 8px 12px;
    border-radius: 5px 5px 0 0;
    border-bottom: 1px solid #ddd;
    margin-bottom: 10px;
}

.section-title {
    font-weight: bold;
    font-size: 14px;
    color: #555;
}

.teacher-notes-area, .student-notes-area {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 50px;
}

.teacher-notes-section .section-header {
    background-color:rgb(170, 192, 232);
}

.student-notes-section .section-header {
    background-color: #e8f5e8;
}

/* 녹음 목록 모달 스타일 */
.recording-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.recording-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin-bottom: 10px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid #4CAF50;
}

.recording-info {
    flex: 1;
}

.recording-title {
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.recording-date {
    font-size: 12px;
    color: #666;
}

.recording-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.delete-recording-btn {
    background-color: transparent;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.delete-recording-btn:hover {
    color: #e53935;
    background-color: rgba(229, 57, 53, 0.1);
}

.recording-list-modal .swal2-html-container {
    padding: 0;
}

.recording-list-modal .swal2-popup {
    padding: 20px;
}

.recording-list::-webkit-scrollbar {
    width: 6px;
}

.recording-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.recording-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.recording-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* 녹음 인터페이스 스타일 */
.consent-interface {
    display: flex;
    align-items: center;
    gap: 8px;
}

.consent-btn {
    background-color: #ff9800;
    border: none;
    color: white;
    font-size: 14px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0.9;
}

.consent-btn:hover {
    background-color: #f57c00;
    opacity: 1;
    transform: scale(1.05);
}

.consent-text {
    font-size: 12px;
    font-weight: 500;
}

/* 동의 모달 스타일 */
.consent-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.consent-modal.show {
    opacity: 1;
    visibility: visible;
}

.consent-modal-content {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(30px);
    transition: transform 0.3s ease;
}

.consent-modal.show .consent-modal-content {
    transform: translateY(0);
}

.consent-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    padding: 30px;
    text-align: center;
    color: white;
    border-radius: 20px 20px 0 0;
}

.consent-header h2 {
    font-size: 24px;
    margin-bottom: 10px;
    font-weight: 600;
}

.consent-body {
    padding: 30px;
}

.consent-info {
    background: #f8f9ff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    border-left: 4px solid #4facfe;
}

.consent-highlight {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
}

.consent-options {
    margin: 25px 0;
}

.consent-option {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.consent-option:hover {
    border-color: #4facfe;
    box-shadow: 0 5px 15px rgba(79, 172, 254, 0.2);
}

.consent-option.selected {
    border-color: #4facfe;
    background: #f0f8ff;
}

.consent-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.consent-option label {
    cursor: pointer;
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 5px;
}

.consent-option-desc {
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.consent-checkmark {
    position: absolute;
    right: 15px;
    top: 15px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #ddd;
    transition: all 0.3s ease;
}

.consent-option.selected .consent-checkmark {
    background: #4facfe;
    border-color: #4facfe;
}

.consent-option.selected .consent-checkmark::after {
    content: "✓";
    color: white;
    font-size: 12px;
    position: absolute;
    top: 1px;
    left: 4px;
}

.consent-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.consent-btn-modal {
    flex: 1;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.consent-btn-primary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.consent-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
}

.consent-btn-secondary {
    background: #f8f9fa;
    color: #666;
    border: 2px solid #e9ecef;
}

.consent-btn-secondary:hover {
    background: #e9ecef;
}

.consent-btn-modal:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

@media (max-width: 480px) {
    .consent-modal-content {
        width: 95%;
        margin: 10px;
    }
    
    .consent-header {
        padding: 20px;
    }
    
    .consent-body {
        padding: 20px;
    }
    
    .consent-buttons {
        flex-direction: column;
    }
}
</style>
<script> 
 

function showalltext(text) {
    alert(text);
}

function ContentsGoalInput(Studentid, Gettext) {
    Swal.fire({
        title: "계획입력",
        html: `
            <input type="text" id="input-field" class="form-control" placeholder="내용을 입력해 주세요" value="${Gettext}">
            <input type="range" min="0" max="90" step="5" value="10" id="duration-slider" style="width:100%; margin-top:10px;">
            <p>시간: <span id="duration-value">10</span> 분</p>
        `,
        showCancelButton: true,
        confirmButtonText: "확인",
        cancelButtonText: "취소",
        didOpen: () => {
            const slider = Swal.getPopup().querySelector("#duration-slider");
            const output = Swal.getPopup().querySelector("#duration-value");
            output.textContent = slider.value;
            slider.addEventListener("input", function() {
                output.textContent = this.value;
            });
        },
        preConfirm: () => {
            const inputField = Swal.getPopup().querySelector("#input-field").value.trim();
            const duration = Swal.getPopup().querySelector("#duration-slider").value;
            if (!inputField) {
                Swal.showValidationMessage("내용을 입력해 주세요.");
            }
            return { inputField: inputField, duration: duration };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const Inputtext = result.value.inputField;
            const duration = result.value.duration;
            if (duration < 10) {
                Swal.fire("", "최소 10분 이상의 활동을 부여해야 합니다.", { showConfirmButton: false, timer: 1500 });
            } else {
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        "eventid": "29",
                        "userid": Studentid,
                        "duration": duration,
                        "inputtext": Inputtext
                    },
                    success: function(data) {
                        window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Studentid;
                    }
                });
            }
        } else {
            Swal.fire("취소되었습니다.", { showConfirmButton: false, timer: 1500 });
        }
    });
}

function fixText(Trackingid,Text) {
    var Userid= '<?php echo $studentid; ?>'; 
    swal({
        title: 'Comment',
        text:"내용 수정하기",
        html: '<br><input class="form-control" placeholder="Input Something" id="input-field">',
        content: {
            element: "input",
            attributes: {
                placeholder: "내용을 입력해 주세요",
                value: Text,
                type: "text",
                id: "input-field",
                className: "form-control"
            },
        },
        buttons: {        
            confirm: {
                className : 'btn btn-success'
            }
        },
    }).then(function() {
        var Inputtext = $("#input-field").val();
        $.ajax({
            url: "check.php",
            type: "POST",
            dataType: "json",
            data: {
                "eventid": "301",
                "trackingid":Trackingid,
                "inputtext": Inputtext
            },
            success: function(data) {
                var thisuser=data.usrid;
                window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Userid;
            }
        });          
    }); 
}

function addComment(Trackingid,Studentid,Text) 
{
    swal({
        title: 'Comment',
        text:"진행과정과 결과에 대한 의견 입력하기",
        html: '<br><input class="form-control" placeholder="Input Something"  id="input-field">',
        content: {
            element: "input",
            attributes: {
                placeholder: "내용을 입력해 주세요",
                type: "text",
                value: Text,
                id: "input-field",
                className: "form-control"
            },
        },
        buttons: {        
            confirm: {
                className : 'btn btn-success'
            }
        },
    }).then(function() {
        var Inputtext = $("#input-field").val();
        $.ajax({
            url: "check.php",
            type: "POST",
            dataType: "json",
            data: {
                "eventid": "30",
                "userid":Studentid,
                "trackingid":Trackingid,
                "inputtext": Inputtext
            },
            success: function(data) {
                window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Studentid;
            }
        });          
    }); 
}

function updateText(Trackingid)
{		 
    var text1="30";
    var text2="40";
    var text3="50";
    var text4="60";
    var text5="70";
    var text6="80";
    var text7="90";
    var text8="120";
    var text9="180";

    swal("예상 소요시간",  "",{
      buttons: {
        catch1: {
          text: text1,
          value: "catch1",className : 'btn btn-primary'
        },
        catch2: {
          text: text2,
          value: "catch2",className : 'btn btn-primary'
        },
        catch3: {
          text: text3,
          value: "catch3",className : 'btn btn-primary'
        },
        catch4: {
          text: text4,
          value: "catch4",className : 'btn btn-primary'
        },
        catch5: {
          text: text5,
          value: "catch5",className : 'btn btn-success'
        },
        catch6: {
          text: text6,
          value: "catch6",className : 'btn btn-success'
        },
        catch7: {
          text: text7,
          value: "catch7",className : 'btn btn-success'
        },
        catch8: {
            text: text8,
            value: "catch8",className : 'btn btn-success'
        },
        catch9: {
            text: text9,
            value: "catch9",className : 'btn btn-secondary'
        },
        cancel: {
            text: "취소",
            visible: false,
            className: 'btn btn-alert'
        }, 
      },
    })
    .then((value) => {
      switch (value) {
        case "defeat":
         swal("취소되었습니다.", {buttons: false,timer: 500});
          break;
       case "catch1":
            swal({
            title: '계획입력',
            html: '<br><input class="form-control" placeholder="Input Something" id="input-field">',
            content: {
                element: "input",
                attributes: {
                    placeholder: "내용을 입력해 주세요",
                    type: "text",
                    id: "input-field",
                    className: "form-control"
                },
            },
            buttons: {        
                confirm: {
                    className : 'btn btn-success'
                }
            },
        }).then(
        function() {
            var Inputtext=$('#input-field').val();
            swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'23',
                    "trackingid":Trackingid,
                    "duration":text1,    
                    "inputtext":Inputtext,             
                },
                success:function(data){
                    location.reload();  
                 }
             });
        });	 
            break;
       case "catch2":
            swal({
                title: '계획입력',
                html: '<br><input class="form-control" placeholder="Input Something" id="input-field">',
                content: {
                    element: "input",
                    attributes: {
                        placeholder: "내용을 입력해 주세요",
                        type: "text",
                        id: "input-field",
                        className: "form-control"
                    },
                },
                buttons: {        
                    confirm: {
                        className : 'btn btn-success'
                    }
                },
            }).then(
            function() {
                var Inputtext=$('#input-field').val();
                swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
                $.ajax({
                    url:"check.php",
                    type: "POST",
                    dataType:"json",
                    data : {
                        "eventid":'23',
                        "trackingid":Trackingid,
                        "duration":text2,    
                        "inputtext":Inputtext,             
                    },
                    success:function(data){
                        location.reload();  
                     }
                 });
            });	 
            break;
       default:
          swal("취소되었습니다.", {buttons: false,timer: 500});
      }
    });
}

function alertTime(Studentid)
{	
    swal("",  "종료 3분 전입니다.",{
      buttons: {
        catch1: {
          text: "시간 연장하기",
          value: "catch1",className : 'btn btn-primary'
        },
        catch2: {
          text: "이대로 마무리하기",
          value: "catch2",className : 'btn btn-primary'
        }, 
      },
    })
    .then((value) => {
      switch (value) {
       case "defeat":
          swal("취소되었습니다.", {buttons: false,timer: 500});
          break;
       case "catch1":
            swal("10분을 연장합니다.",{buttons: false,timer: 1000});
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'24',
                    "userid":Studentid,               
                },
                success:function(data){}
            });
            location.reload();  
            break;
       case "catch2":
            swal("예정대로 마무리합니다.",{buttons: false,timer: 1000});
            break; 
       default:
            swal("취소되었습니다.", {buttons: false,timer: 500});
      }
    });
}

function hideItem(Trackingid)
{	
    swal("",  "항목을 제거하시겠습니까 ?",{
      buttons: {
        catch1: {
          text: "제거하기",
          value: "catch1",className : 'btn btn-primary'
        },
        catch2: {
          text: "취소",
          value: "catch2",className : 'btn btn-primary'
        }, 
      },
    })
    .then((value) => {
      switch (value) {
       case "defeat":
          swal("취소되었습니다.", {buttons: false,timer: 500});
          break;
       case "catch1":
            swal("항목을 완료합니다.",{buttons: false,timer: 1000});
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'244',
                    "trackingid":Trackingid,               
                },
                success:function(data){}
            });
            setTimeout(function() {location.reload(); },500);  
            break;
       case "catch2":
            swal("취소되었습니다.",{buttons: false,timer: 1000});
            break; 
       default:
            swal("취소되었습니다.", {buttons: false,timer: 500});
      }
    });
}

function evaluateResult(Studentid)
{
    var CurrentTrackingId= '<?php echo $currenttrackingid; ?>';
    swal("수고하셨습니다. 마무리 점검 페이지로 이동합니다.", {buttons: false,timer: 1000});
    setTimeout(function() {
        window.open('https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/challenge_report.php?tid='+CurrentTrackingId+'&userid='+Studentid, '_self');
    }, 1000);
} 

function addHomework(Studentid) {
    swal({
        title: "과제입력",
        content: {
            element: "input",
            attributes: {
                placeholder: "내용을 입력해 주세요",
                type: "text",
                id: "input-field",
                className: "form-control"
            },
        },
        buttons: {
            confirm: {
                text: "다음",
                className: "btn btn-success"
            }
        },
    }).then(function() {
        var inputText = document.getElementById("input-field").value;
        swal({
            title: "데드라인",
            content: {
                element: "input",
                attributes: {
                    type: "date",
                    id: "date-field",
                    className: "form-control"
                },
            },
            buttons: {
                confirm: {
                    text: "저장",
                    className: "btn btn-success"
                }
            },
            onOpen: function() {
                var dateField = document.getElementById("date-field");
                dateField.focus();
                dateField.click();
            }
        }).then(function() {
            var selectedDate = document.getElementById("date-field").value;
            swal("", "과제내용 : " + inputText + "\n날짜: " + selectedDate, {buttons: false, timer: 1000});
            $.ajax({
                url: "check.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": "27",
                    "inputtext": inputText,
                    "date": selectedDate,
                    "userid": Studentid,
                },
                success: function(data) {
                    location.reload();
                }
            });
        });
    });
}

// 선생님 전용: 학생 학습태도 평가 함수
function evaluateStudentAttitude(Studentid)
{
    swal({
        title: "📊 학습 태도 평가",
        text: "학생의 오늘 학습 태도를 평가해주세요",
        buttons: {
            btn1: {
                text: "1. 길을 잃음",
                value: 1,
                className: 'btn btn-danger'
            },
            btn2: {
                text: "2. 산만함",
                value: 2,
                className: 'btn btn-warning'
            },
            btn3: {
                text: "3. 성실함",
                value: 3,
                className: 'btn btn-info'
            },
            btn4: {
                text: "4. 매우 성실",
                value: 4,
                className: 'btn btn-primary'
            },
            btn5: {
                text: "5. 열정적",
                value: 5,
                className: 'btn btn-success'
            },
            cancel: {
                text: "취소",
                value: null,
                visible: true,
                className: 'btn btn-secondary'
            }
        },
    })
    .then((value) => {
        if (value) {
            // mdl_abessi_today 테이블에 오늘 평가 내용 추가 (reflection2 필드에 1~5 값 저장)
            $.ajax({
                url: "check.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": '401',
                    "userid": Studentid,
                    "reflection2": value,
                },
                success: function(data) {
                    if (data.success) {
                        swal("평가 완료", "평가가 성공적으로 저장되었습니다.\n점수: " + value + " (" + data.text + ")", "success");
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else if (data.error) {
                        console.error("Error in evaluateStudentAttitude (timescaffolding.php:line ~2880): " + data.error);
                        swal("오류", "평가 저장 중 오류가 발생했습니다.\n" + data.error + "\n파일: " + data.file + ", 라인: " + data.line, "error");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error in evaluateStudentAttitude (timescaffolding.php:line ~2880): " + error);
                    swal("오류", "평가 저장 중 오류가 발생했습니다: " + error, "error");
                }
            });
        } else if (value === null) {
            swal("취소", "평가가 취소되었습니다.", {buttons: false, timer: 1000});
        }
    });
}

function addMemo(Studentid) {
    swal({
        title: "일정 입력",
        content: {
            element: "input",
            attributes: {
                placeholder: "내용을 입력해 주세요",
                type: "text",
                id: "input-field",
                className: "form-control"
            },
        },
        buttons: {
            confirm: {
                text: "다음",
                className: "btn btn-success"
            }
        },
    }).then(function() {
        var inputText = document.getElementById("input-field").value;
        swal({
            title: "날짜 선택",
            content: {
                element: "input",
                attributes: {
                    type: "date",
                    id: "date-field",
                    className: "form-control"
                },
            },
            buttons: {
                confirm: {
                    text: "저장",
                    className: "btn btn-success"
                }
            },
            onOpen: function() {
                var dateField = document.getElementById("date-field");
                dateField.focus();
                dateField.click();
            }
        }).then(function() {
            var selectedDate = document.getElementById("date-field").value;
            swal("", "과제 내용: " + inputText + "\n날짜: " + selectedDate, {buttons: false, timer: 1000});
            $.ajax({
                url: "check.php",
                type: "POST",
                dataType: "json",
                data: {
                    "eventid": "277",
                    "inputtext": inputText,
                    "date": selectedDate,
                    "userid": Studentid,
                },
                success: function(data) {
                    location.reload();
                }
            });
        });
    });
}

function addContext(Studentid) {
    swal({
        title: "학생 문맥입력",
        content: {
            element: "input",
            attributes: {
                placeholder: "내용을 입력해 주세요",
                type: "text",
                id: "input-field",
                className: "form-control"
            },
        },
        buttons: {
            confirm: {
                className: "btn btn-success"
            }
        },
    }).then(function() {
        var inputText = document.getElementById("input-field").value;
        swal("", "WXSPERT : " + inputText, {buttons: false, timer: 500});
        $.ajax({
            url: "check.php",
            type: "POST",
            dataType: "json",
            data: {
                "eventid": "28",
                "inputtext": inputText,
                "userid": Studentid,
            },
            success: function(data) {
                location.reload();
            }
        });
    });
}

function addTime(Studentid)
{	
    swal("10분을 연장합니다.",{buttons: false,timer: 1000});
    $.ajax({
        url:"check.php",
        type: "POST",
        dataType:"json",
        data : {
            "eventid":'24',
            "userid":Studentid,               
        },
        success:function(data){}
    });
    setTimeout(function() {
        location.reload();
    }, 1000);  
} 
function addFixNote(Studentid) {
    let value = "오답노트 시간";
    let placeholderText = "";
    let inputTitle = "오답노트";
    Swal.fire({
        title: inputTitle,
        html: `
            <input type="text" id="input-field" class="form-control" placeholder="${placeholderText}" value="${value}">
            <input type="range" min="0" max="60" step="5" value="10" id="duration-slider" style="width:100%; margin-top:10px;">
            <p>시간: <span id="duration-value">10</span> 분</p>
            <label for="status-select" style="margin-top:10px; display:block;">상태 선택:</label>
            <select id="status-select" class="form-control" style="margin-top:5px;">
                <option value="begin">begin</option>
                <option value="waiting">waiting</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: "확인",
        cancelButtonText: "취소",
        didOpen: () => {
            const slider = Swal.getPopup().querySelector("#duration-slider");
            const output = Swal.getPopup().querySelector("#duration-value");
            output.textContent = slider.value;
            slider.addEventListener("input", function() {
                output.textContent = this.value;
            });
        },
        preConfirm: () => {
            const inputField = Swal.getPopup().querySelector("#input-field").value;
            const duration = Swal.getPopup().querySelector("#duration-slider").value;
            const status = Swal.getPopup().querySelector("#status-select").value;
            return { inputField: inputField, duration: duration, status: status };
        }
    }).then((result) => {
        if(result.isConfirmed) {
            const Inputtext = result.value.inputField;
            const duration = result.value.duration;
            const status = result.value.status;
            if(duration < 5) {
                Swal.fire("", "최소 5분 이상의 활동을 부여해야 합니다.", { showConfirmButton: false, timer:1500 });
            } else {
                Swal.fire("", duration + "분 동안 활동을 부여합니다. 상태: " + status, { showConfirmButton: false, timer:1500 });
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        "eventid": "21",
                        "userid": Studentid,
                        "duration": duration,
                        "inputtext": Inputtext,
                        "status": status
                    },
                    success: function(data) { location.reload(); }
                });
            }
        } else {
            Swal.fire("취소되었습니다.", { showConfirmButton: false, timer:1500 });
        }
    });
}

// 새로 추가된 도전 버튼 기능
function BeginInstruction(trackingid, Studentid, text) {
    let value = text;
    let placeholderText = "내용을 입력해 주세요";
    let inputTitle = "직접입력";
    Swal.fire({
        title: inputTitle,
        html: `
            <input type="text" id="input-field" class="form-control" placeholder="${placeholderText}" value="${value}">
            <input type="range" min="0" max="60" step="5" value="10" id="duration-slider" style="width:100%; margin-top:10px;">
            <p>시간: <span id="duration-value">10</span> 분</p>
        `,
        showCancelButton: true,
        confirmButtonText: "확인",
        cancelButtonText: "취소",
        didOpen: () => {
            const slider = Swal.getPopup().querySelector("#duration-slider");
            const output = Swal.getPopup().querySelector("#duration-value");
            output.textContent = slider.value;
            slider.addEventListener("input", function() { output.textContent = this.value; });
        },
        preConfirm: () => {
            const inputField = Swal.getPopup().querySelector("#input-field").value;
            const duration = Swal.getPopup().querySelector("#duration-slider").value;
            return { inputField: inputField, duration: duration };
        }
    }).then((result) => {
        if(result.isConfirmed) {
            const Inputtext = result.value.inputField;
            const duration = result.value.duration;
            if(duration < 5) {
                Swal.fire("", "최소 5분 이상의 활동을 부여해야 합니다.", { showConfirmButton: false, timer:1500 });
            } else {
                Swal.fire("", duration + "분 동안 활동을 부여합니다.", { showConfirmButton: false, timer:1500 });
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType:"json",
                    data: {
                        "eventid": "291",
                        "trackingid": trackingid,
                        "userid": Studentid,
                        "duration": duration,
                        "inputtext": Inputtext,
                    },
                    success: function(data) { location.reload(); }
                });
            }
        } else {
            Swal.fire("취소되었습니다.", { showConfirmButton: false, timer:1500 });
        }
    });
}


function addDirectInput(Studentid) {
    let value = "";
    let placeholderText = "내용을 입력해 주세요";
    let inputTitle = "직접입력";
    Swal.fire({
        title: inputTitle,
        html: `
            <input type="text" id="input-field" class="form-control" placeholder="${placeholderText}" value="${value}">
            <input type="range" min="0" max="60" step="5" value="10" id="duration-slider" style="width:100%; margin-top:10px;">
            <p>시간: <span id="duration-value">10</span> 분</p>
            <label for="status-select" style="margin-top:10px; display:block;">상태 선택:</label>
            <select id="status-select" class="form-control" style="margin-top:5px;">
                <option value="begin">begin</option>
                <option value="waiting">waiting</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: "확인",
        cancelButtonText: "취소",
        didOpen: () => {
            const slider = Swal.getPopup().querySelector("#duration-slider");
            const output = Swal.getPopup().querySelector("#duration-value");
            output.textContent = slider.value;
            slider.addEventListener("input", function() {
                output.textContent = this.value;
            });
        },
        preConfirm: () => {
            const inputField = Swal.getPopup().querySelector("#input-field").value;
            const duration = Swal.getPopup().querySelector("#duration-slider").value;
            const status = Swal.getPopup().querySelector("#status-select").value;
            return { inputField: inputField, duration: duration, status: status };
        }
    }).then((result) => {
        if(result.isConfirmed) {
            const Inputtext = result.value.inputField;
            const duration = result.value.duration;
            const status = result.value.status;
            if(duration < 5) {
                Swal.fire("", "최소 5분 이상의 활동을 부여해야 합니다.", { showConfirmButton: false, timer:1500 });
            } else {
                Swal.fire("", duration + "분 동안 활동(" + status + ")을 부여합니다.", { showConfirmButton: false, timer:1500 });
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType:"json",
                    data: {
                        "eventid": "21",
                        "userid": Studentid,
                        "duration": duration,
                        "inputtext": Inputtext,
                        "status": status
                    },
                    success: function(data) { location.reload(); }
                });
            }
        } else {
            Swal.fire("취소되었습니다.", { showConfirmButton: false, timer:1500 });
        }
    });
}

const uploadInput = document.getElementById("uploadImageInput");

function uploadImageNote(){
    if(uploadInput){
        uploadInput.click();
    }
}

if(uploadInput){
    uploadInput.addEventListener("change", function(e){
        const file = e.target.files[0];
        if(file){
            sendImageToServer(file);
        }
        uploadInput.value = ""; // reset
    });
}

document.addEventListener("paste", function(e){
    const items = (e.clipboardData || window.clipboardData).items;
    for(let i=0;i<items.length;i++){
        const item = items[i];
        if(item.kind === "file" && item.type.startsWith("image/")){
            const file = item.getAsFile();
            sendImageToServer(file);
            e.preventDefault();
            break;
        }
    }
});

function sendImageToServer(file){
    const formData = new FormData();
    formData.append("image", file);
    formData.append("studentid", currentUserId);
    formData.append("contentsid", Date.now()); // 임시 ID
    formData.append("print", 0);

    $.ajax({
        url: "../LLM/uploadimage.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(response){
            if(response.success){
                const imgTag = `<img src="${response.url}" class="note-img" style="max-width:100%;cursor:pointer;">`;
                saveNote(imgTag, "yellow");
            }else{
                showNoteError(response.error || "이미지 업로드에 실패했습니다.");
            }
        },
        error: function(){
            showNoteError("서버 연결에 실패했습니다.");
        }
    });
}
// ---------- 추가: 이미지 클릭 시 확대 ----------
document.addEventListener("click", function(e){
    if(e.target.tagName === "IMG" && e.target.closest(".sticky-note")){
        Swal.fire({
            html: `<img src="${e.target.src}" style="max-width:100%;height:auto;">`,
            showConfirmButton: false,
            width: '90%',
            background: 'transparent',
            backdrop: true,
            allowOutsideClick: true  // 외부 클릭 시 닫힘
        });
    }
});

// 녹음 기능 관련 함수들
async function initializeRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            } 
        });
        
        // 오디오 컨텍스트 설정 (음성 활동 감지용)
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        analyser = audioContext.createAnalyser();
        microphone = audioContext.createMediaStreamSource(stream);
        microphone.connect(analyser);
        
        analyser.fftSize = 512;
        
        mediaRecorder = new MediaRecorder(stream, {
            mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4'
        });
        
        mediaRecorder.ondataavailable = function(event) {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };
        
        mediaRecorder.onstop = function() {
            uploadRecording();
        };
        
        return true;
    } catch (error) {
        console.error('마이크 접근 실패:', error);
        showNoteError('마이크에 접근할 수 없습니다. 브라우저 설정을 확인해주세요.');
        return false;
    }
}

async function toggleRecording() {
    if (!isRecording) {
        await startRecording();
    } else {
        stopRecording();
    }
}

async function startRecording() {
    if (!mediaRecorder) {
        const initialized = await initializeRecording();
        if (!initialized) return;
    }
    
    audioChunks = [];
    isRecording = true;
    recordingStartTime = Date.now();
    
    // UI 업데이트
    document.getElementById('recordBtn').classList.add('recording');
    document.getElementById('recordIcon').className = 'fas fa-stop';
    document.getElementById('recordingStatus').style.display = 'flex';
    
    // 녹음 시작
    mediaRecorder.start();
    
    // 타이머 시작
    startRecordingTimer();
    
    // 음성 활동 모니터링 시작
    startSilenceDetection();
    
    console.log('녹음 시작');
}

function stopRecording() {
    if (!isRecording) return;
    
    isRecording = false;
    
    // UI 업데이트
    document.getElementById('recordBtn').classList.remove('recording');
    document.getElementById('recordIcon').className = 'fas fa-microphone';
    document.getElementById('recordingStatus').style.display = 'none';
    
    // 녹음 정지
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }
    
    // 타이머 정지
    if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }
    
    // 침묵 감지 타이머 정지
    if (silenceTimer) {
        clearTimeout(silenceTimer);
        silenceTimer = null;
    }
    
    console.log('녹음 정지');
}

function startRecordingTimer() {
    recordingTimer = setInterval(() => {
        if (recordingStartTime) {
            const elapsed = Date.now() - recordingStartTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            document.getElementById('recordingTime').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }, 1000);
}

function startSilenceDetection() {
    function checkAudioLevel() {
        if (!isRecording || !analyser) return;
        
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        analyser.getByteFrequencyData(dataArray);
        
        // 평균 음량 계산
        let sum = 0;
        for (let i = 0; i < bufferLength; i++) {
            sum += dataArray[i];
        }
        const average = sum / bufferLength;
        
        // dB로 변환
        const decibels = 20 * Math.log10(average / 255);
        
        if (decibels > silenceThreshold) {
            // 음성 활동 감지됨 - 침묵 타이머 리셋
            if (silenceTimer) {
                clearTimeout(silenceTimer);
            }
            silenceTimer = setTimeout(() => {
                if (isRecording) {
                    console.log('5분간 음성 활동이 없어 자동으로 녹음을 종료합니다.');
                    stopRecording();
                }
            }, silenceTimeout);
        }
        
        // 다음 체크 스케줄링
        if (isRecording) {
            setTimeout(checkAudioLevel, 1000); // 1초마다 체크
        }
    }
    
    // 초기 침묵 타이머 설정
    silenceTimer = setTimeout(() => {
        if (isRecording) {
            console.log('5분간 음성 활동이 없어 자동으로 녹음을 종료합니다.');
            stopRecording();
        }
    }, silenceTimeout);
    
    checkAudioLevel();
}

function uploadRecording() {
    if (audioChunks.length === 0) {
        showNoteError('녹음된 오디오가 없습니다.');
        return;
    }
    
    const audioBlob = new Blob(audioChunks, { 
        type: mediaRecorder.mimeType || 'audio/webm' 
    });
    
    const formData = new FormData();
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const fileName = `recording_${currentUserId}_${timestamp}.webm`;
    
    formData.append('audio', audioBlob, fileName);
    formData.append('contentstype', '3'); // 녹음 타입
    formData.append('contentsid', Date.now().toString());
    formData.append('userid', currentUserId);
    
    // 로딩 표시
    Swal.fire({
        title: '업로드 중...',
        text: '녹음 파일을 업로드하고 있습니다.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '../LLM/upload_recording.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '업로드 완료',
                    text: '녹음 파일이 성공적으로 업로드되었습니다.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                showNoteError(response.error || '업로드에 실패했습니다.');
            }
        },
        error: function() {
            Swal.close();
            showNoteError('서버 연결에 실패했습니다.');
        }
    });
    
    // 정리
    audioChunks = [];
}

function showRecordingList() {
    $.ajax({
        url: '../api/recording_api.php',
        type: 'GET',
        data: {
            action: 'get_recordings',
            userid: currentUserId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayRecordingList(response.recordings);
            } else {
                showNoteError('녹음 목록을 불러오는데 실패했습니다.');
            }
        },
        error: function() {
            showNoteError('서버 연결에 실패했습니다.');
        }
    });
}

function displayRecordingList(recordings) {
    let listHtml = '<div class="recording-list">';
    
    if (recordings.length === 0) {
        listHtml += '<p style="text-align: center; color: #999;">녹음 파일이 없습니다.</p>';
    } else {
        recordings.forEach(recording => {
            const date = new Date(recording.timecreated * 1000).toLocaleString('ko-KR');
            listHtml += `
                <div class="recording-item">
                    <div class="recording-info">
                        <div class="recording-title">${recording.text || '제목 없음'}</div>
                        <div class="recording-date">${date}</div>
                    </div>
                    <div class="recording-actions">
                        <audio controls style="width: 200px; height: 30px;">
                            <source src="${recording.fileurl}" type="audio/webm">
                            <source src="${recording.fileurl}" type="audio/mp4">
                            브라우저가 오디오를 지원하지 않습니다.
                        </audio>
                        ${userRole !== 'student' ? `<button onclick="deleteRecording(${recording.id})" class="delete-recording-btn"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </div>
            `;
        });
    }
    
    listHtml += '</div>';
    
    Swal.fire({
        title: '녹음 목록',
        html: listHtml,
        width: '80%',
        showConfirmButton: false,
        showCloseButton: true,
        customClass: {
            container: 'recording-list-modal'
        }
    });
}

function deleteRecording(recordingId) {
    Swal.fire({
        title: '녹음 삭제',
        text: '이 녹음을 삭제하시겠습니까?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '삭제',
        cancelButtonText: '취소'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/recording_api.php',
                type: 'POST',
                data: {
                    action: 'delete_recording',
                    recording_id: recordingId,
                    userid: currentUserId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '삭제 완료',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            showRecordingList(); // 목록 새로고침
                        });
                    } else {
                        showNoteError('삭제에 실패했습니다.');
                    }
                },
                error: function() {
                    showNoteError('서버 연결에 실패했습니다.');
                }
            });
        }
    });
}

// ... existing code ...

// 동의 모달 관련 함수들
function showConsentModal() {
    const modalHtml = `
        <div class="consent-modal" id="consentModal" onclick="closeConsentModal(event)">
            <div class="consent-modal-content" onclick="event.stopPropagation()">
                <div class="consent-header">
                    <h2>📞 상담 녹음 동의</h2>
                    <p>더 나은 상담을 위한 선택</p>
                </div>
                
                <div class="consent-body">
                    <div class="consent-info">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 12px; display: flex; align-items: center;">
                            🎙️ 상담 녹음에 대해 알려드려요
                        </div>
                        <div style="color: #666; line-height: 1.6; margin-bottom: 15px;">
                            오늘 진행되는 상담 내용을 음성으로 녹음하여, AI가 상담 내용을 정리한 리포트를 만들 수 있어요.
                        </div>
                        <div style="color: #666; line-height: 1.6;">
                            <strong>녹음은 오직 당신을 위한 상담 기록 작성에만 사용되며, 다른 용도로는 절대 사용되지 않아요.</strong>
                        </div>
                    </div>

                    <div class="consent-highlight">
                        <strong>중요:</strong> 녹음을 거부해도 상담에는 전혀 영향이 없어요. 
                        언제든지 마음을 바꿀 수 있고, 상담 중에도 녹음 중단을 요청할 수 있어요.
                    </div>

                    <div class="consent-options">
                        <div class="consent-option" onclick="selectConsentOption(this, 'agree')">
                            <input type="radio" name="consent" value="agree" id="agreeConsent">
                            <label for="agreeConsent">✅ 네, 녹음에 동의해요</label>
                            <div class="consent-option-desc">
                                상담 내용을 녹음하고, AI 리포트를 받을게요. 
                                리포트 생성 후 녹음 파일은 즉시 삭제됩니다.
                            </div>
                            <div class="consent-checkmark"></div>
                        </div>

                        <div class="consent-option" onclick="selectConsentOption(this, 'disagree')">
                            <input type="radio" name="consent" value="disagree" id="disagreeConsent">
                            <label for="disagreeConsent">❌ 아니요, 녹음하지 말아주세요</label>
                            <div class="consent-option-desc">
                                녹음 없이 일반 상담을 진행해요. 
                                선생님이 직접 상담 내용을 기록할게요.
                            </div>
                            <div class="consent-checkmark"></div>
                        </div>
                    </div>

                    <div class="consent-buttons">
                        <button class="consent-btn-modal consent-btn-secondary" onclick="closeConsentModal()">
                            취소
                        </button>
                        <button class="consent-btn-modal consent-btn-primary" id="submitConsentBtn" onclick="submitConsent()" disabled>
                            확인
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    setTimeout(() => {
        document.getElementById('consentModal').classList.add('show');
    }, 10);
}

let selectedConsentOption = null;

function selectConsentOption(element, value) {
    // 모든 옵션에서 selected 클래스 제거
    document.querySelectorAll('.consent-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // 선택된 옵션에 selected 클래스 추가
    element.classList.add('selected');
    
    // 라디오 버튼 체크
    document.getElementById(value + 'Consent').checked = true;
    
    selectedConsentOption = value;
    
    // 제출 버튼 활성화
    document.getElementById('submitConsentBtn').disabled = false;
}

function submitConsent() {
    if (!selectedConsentOption) {
        showNoteError('선택해 주세요.');
        return;
    }

    const isAgreed = selectedConsentOption === 'agree';
    
    $.ajax({
        url: '../api/consent_api.php',
        type: 'POST',
        data: {
            action: 'save_consent',
            userid: currentUserId,
            consent: selectedConsentOption
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                closeConsentModal();
                
                if (isAgreed) {
                    Swal.fire({
                        icon: 'success',
                        title: '동의 완료',
                        text: '녹음 동의가 완료되었습니다. 이제 녹음 기능을 사용할 수 있어요!',
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        // 인터페이스 전환
                        document.getElementById('consentInterface').style.display = 'none';
                        document.getElementById('recordingInterface').style.display = 'flex';
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: '선택 완료',
                        text: '일반 상담으로 진행됩니다.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } else {
                showNoteError(response.error || '저장에 실패했습니다.');
            }
        },
        error: function() {
            showNoteError('서버 연결에 실패했습니다.');
        }
    });
}

function closeConsentModal(event) {
    if (event && event.target !== event.currentTarget) return;
    
    const modal = document.getElementById('consentModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
    selectedConsentOption = null;
}

// ... existing code ...
</script>
</body>
</html>
