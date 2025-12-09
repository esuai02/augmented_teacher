<?php 
/////////////////////////////// 전체 코드 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
$cntinput= $_GET["cntinput"];
$mode= $_GET["mode"];
if($studentid==NULL)$studentid=$USER->id;
$timecreated=time(); 
$hoursago=$timecreated-14400;
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
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

$inspectToday = isset($checkgoal->inspect) ? $checkgoal->inspect : 0;
$date = isset($checkgoal->timecreated) ? gmdate("h:i A", $checkgoal->timecreated+32400) : '';
  
if($inspectToday==2)$status4='checked';    
elseif($inspectToday==3)$status5='checked';  

$lastbreak= $DB->get_record_sql("SELECT id,timecreated FROM mdl_abessi_missionlog WHERE userid='$studentid' AND timecreated>'$halfdayago' AND eventid='7128' ORDER BY id DESC LIMIT 1 ");
$beforebreak = 60;
if($lastbreak && isset($lastbreak->timecreated)) {
    $beforebreak=60-($timecreated-$lastbreak->timecreated)/60;
    if($lastbreak->id!=NULL)$beforebreak=-1;
}

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

$instructions=$DB->get_records_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$studentid' AND duration > '$aweekago' AND hide=0 ORDER BY id DESC LIMIT 100");
if($USER->id==2)$usercontext='<SPAN ONCLICK="addContext(\''.$studentid.'\');">➕</SPAN>';

$result = json_decode(json_encode($instructions), True);
unset($value);
$np=0;
$pmresult=0;
$directionlist0=''; // 대기 중인 활동
$directionlist1=''; // 오늘 활동
$directionlist2=''; // 지난 활동
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
                </audio>  |   <a style="font-size:30px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$studentid.'"target="_blank">🎭</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/attendancerecords.php?userid='.$studentid.'"target="_blank">출결</a> ';

// 복사 버튼 추가
$copyButtonHtml = '<button id="copyButton" class="btn btn-success" title="학습 내용을 클립보드에 복사" style="position: fixed; top: 0; right: 10px; z-index: 1000; padding: 10px 15px; font-weight: bold; border-radius: 0 0 15px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); cursor: pointer;"><i class="fas fa-clipboard" aria-hidden="true"></i></button>';
// 복사 버튼은 페이지 하단에 출력하므로 여기서는 추가하지 않음
// $headtext .= $copyButtonHtml;

// 복사할 내용 준비
$userName = $thisuser ? $thisuser->firstname . $thisuser->lastname : 'Unknown User';
$copyContent = $userName . "의 이번주 공부 내용은 다음과 같습니다.\n\n";

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
if ($checkgoal && !empty($checkgoal->text)) {
    $copyContent .= "【오늘 목표】\n" . $checkgoal->text . "\n\n";
}

// 주간 목표 추가
if ($wgoal && !empty($wgoal->text)) {
    $copyContent .= "【주간 목표】\n" . $wgoal->text . "\n\n";
}

$copyContent .= "이상의 값들을 분석하여 학생의 지난 일주일간의 학습 여정을 추론해줘. 추론된 결과를 토대로 학생의 학습여정을 학생의 화법으로 학습일지 스토리텔링을 블로그 형식으로 작성해줘.";

// parental 모드 처리
if($mode==='parental') {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="https://mathking.kr/moodle/local/augmented_teacher/CSS/default.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
            .top-menu { margin-bottom: 20px; }
            .top-menu table { width: 100%; }
            .btn { padding: 8px 16px; margin: 2px; text-decoration: none; border-radius: 4px; }
            .btn-info { background-color: #17a2b8; color: white; }
            .btn-danger { background-color: #dc3545; color: white; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 8px; border: 1px solid #dee2e6; text-align: left; }
            th { background-color: #f8f9fa; }
        </style>
    </head>
    <body>
    <div class="top-menu">
        <table align="left">
            <tr>
                <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1" class="btn btn-info">일정</a></td>
                <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800" class="btn btn-info">계획</a></td>
                <td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$studentid.'" class="btn btn-danger">일지</a></td>
                <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200" class="btn btn-info">오늘</a></td>
                <td><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20letter.php?userid='.$studentid.'" class="btn btn-info">상담</a></td>
            </tr>
        </table>
    </div>
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
    </table>
    
    <script>
    // 차트 그리기 함수
    function drawParentalCharts() {
        const graphData = '.json_encode($graphData).';
        
        if (graphData.length > 0) {
            const labels = graphData.map(item => item.time);
            const finalData = graphData.map(item => item.final);
            const wbtimeaveData = graphData.map(item => item.wbtimeave);
            
            // Final 차트
            const ctxFinal = document.getElementById("chartCanvasFinal").getContext("2d");
            new Chart(ctxFinal, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "실제 학습 시간 (분)",
                        data: finalData,
                        borderColor: "#667eea",
                        backgroundColor: "rgba(102, 126, 234, 0.1)",
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 60
                        }
                    }
                }
            });
            
            // Wbtimeave 차트
            const ctxWbtimeave = document.getElementById("chartCanvasWbtimeave").getContext("2d");
            new Chart(ctxWbtimeave, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "화이트보드 활동 시간 (분)",
                        data: wbtimeaveData,
                        backgroundColor: "#764ba2"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 30
                        }
                    }
                }
            });
        }
    }

    // 페이지 로드 시 차트 그리기
    window.addEventListener("load", function() {
        drawParentalCharts();
    });
    </script>
    </body>
    </html>';
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="https://mathking.kr/moodle/local/augmented_teacher/CSS/default.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* 모던 디자인 스타일 */
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* 헤더 스타일 */
        .modern-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
        }

        .header-info a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .header-info a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* 뷰 모드 전환 버튼 */
        .view-toggle {
            display: flex;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 4px;
            gap: 4px;
        }

        .view-toggle button {
            padding: 8px 20px;
            border: none;
            background: transparent;
            color: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .view-toggle button.active {
            background: white;
            color: #667eea;
        }

        /* 메인 컨테이너 */
        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        /* 탭 모드 스타일 */
        .tab-mode {
            display: none;
        }

        .tab-mode.active {
            display: block;
        }

        .tabs-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tabs-nav {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .tab-button {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-button:hover {
            background: #e9ecef;
        }

        .tab-button.active {
            color: #667eea;
            background: white;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }

        .tab-content {
            display: none;
            padding: 30px;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 스크롤 모드 스타일 */
        .scroll-mode {
            display: none;
        }

        .scroll-mode.active {
            display: block;
        }

        .content-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f3f5;
        }

        /* 활동 테이블 스타일 */
        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .activity-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .activity-table tr:hover {
            background: #f8f9fa;
        }

        /* 메모장 스타일 */
        .memo-section {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 350px;
            max-height: 70vh;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            z-index: 1000;
        }

        .memo-header {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .memo-content {
            max-height: calc(70vh - 60px);
            overflow-y: auto;
            padding: 20px;
        }

        /* 포스트잇 스타일 */
        .sticky-note {
            background: #fef3c7;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
            transform: rotate(-1deg);
            transition: all 0.3s ease;
        }

        .sticky-note:nth-child(even) {
            transform: rotate(1deg);
        }

        .sticky-note:hover {
            transform: rotate(0deg) scale(1.02);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .sticky-note.yellow { background: #fef3c7; }
        .sticky-note.green { background: #d1fae5; }
        .sticky-note.blue { background: #dbeafe; }
        .sticky-note.pink { background: #fce7f3; }

        /* 차트 컨테이너 */
        .charts-container {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }

        .chart-box {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .tabs-nav {
                flex-wrap: wrap;
            }

            .tab-button {
                flex: 1 1 50%;
            }

            .memo-section {
                position: static;
                width: 100%;
                margin-top: 20px;
                border-radius: 15px;
            }

            .charts-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- 모던 헤더 -->
    <div class="modern-header">
        <div class="header-content">
            <div class="header-title">
                <?php if($role==='student'): ?>
                    <span>📒 수학일기</span>
                <?php else: ?>
                    <span><?=$stdname?>님의 📒 수학일기</span>
                <?php endif; ?>
            </div>
            
            <div class="view-toggle">
                <button class="active" onclick="switchView('tab')">탭 모드</button>
                <button onclick="switchView('scroll')">스크롤 모드</button>
            </div>
            
            <div class="header-info">
                <?php if($mode==='parental'): ?>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id=<?=$studentid?>&eid=1">일정</a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id=<?=$studentid?>&tb=604800">계획</a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id=<?=$studentid?>&tb=43200">오늘</a>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20letter.php?userid=<?=$studentid?>">상담</a>
                <?php else: ?>
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?=$studentid?>&tb=604800"><?=($thisuser ? $thisuser->firstname.$thisuser->lastname : 'Unknown User')?></a>
                    <span>책/프린트 공부 <input type="checkbox" name="checkAccount" <?=isset($status5) ? $status5 : ''?> onClick="ChangeCheckBox(333,'<?=$studentid?>','<?=isset($goalid) ? $goalid : 0?>', this.checked)"/></span>
                    <span>DMN 휴식 <input type="checkbox" name="checkAccount" <?=isset($status4) ? $status4 : ''?> onClick="Resttime(33,'<?=$studentid?>','<?=isset($goalid) ? $goalid : 0?>', this.checked)"/></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 메인 컨테이너 -->
    <div class="main-container">
        <!-- 탭 모드 -->
        <div class="tab-mode active">
            <div class="tabs-container">
                <div class="tabs-nav">
                    <button class="tab-button active" onclick="switchTab('current')">현재 활동</button>
                    <button class="tab-button" onclick="switchTab('today')">오늘 활동</button>
                    <button class="tab-button" onclick="switchTab('past')">지난 활동</button>
                    <button class="tab-button" onclick="switchTab('goals')">목표 관리</button>
                    <button class="tab-button" onclick="switchTab('analytics')">분석</button>
                </div>
                
                <div class="tab-content active" id="current-tab">
                    <div class="section-title">🔄 현재 진행 중인 활동</div>
                    <?php if (!empty($directionlist0)): ?>
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>시간</th>
                                    <th>활동내용</th>
                                    <th>예정시간</th>
                                    <th>실제시간</th>
                                    <th>상태</th>
                                    <th>보고서</th>
                                    <th>활동로그</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?=$directionlist0?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 40px 0;">현재 대기 중인 활동이 없습니다.</p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 30px;">
                        <button onclick="addDirectInput('<?=$studentid?>')" class="btn btn-primary">직접 입력 ➕</button>
                        <button onclick="addFixNote('<?=$studentid?>')" class="btn btn-secondary">오답노트 ➕</button>
                    </div>
                </div>
                
                <div class="tab-content" id="today-tab">
                    <div class="section-title">📅 오늘의 활동</div>
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>활동내용</th>
                                <th>예정시간</th>
                                <th>실제시간</th>
                                <th>상태</th>
                                <th>보고서</th>
                                <th>활동로그</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?=$directionlist1?>
                        </tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="past-tab">
                    <div class="section-title">📚 지난 활동 기록</div>
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>활동내용</th>
                                <th>예정시간</th>
                                <th>실제시간</th>
                                <th>상태</th>
                                <th>보고서</th>
                                <th>활동로그</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?=$directionlist2?>
                        </tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="goals-tab">
                    <div class="section-title">🎯 목표 관리</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="content-section">
                            <h3>오늘 목표</h3>
                            <p><?=isset($checkgoal->text) ? $checkgoal->text : '설정된 오늘 목표가 없습니다.'?></p>
                            <?php if($checkgoal && $chapterlog): ?>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id=<?=$studentid?>&cid=<?=$chapterlog->cid?>&pid=<?=$checkgoal->id?>&nch=<?=$chapterlog->nch?>" class="btn btn-sm btn-outline-primary">수정</a>
                            <?php endif; ?>
                        </div>
                        <div class="content-section">
                            <h3>주간 목표</h3>
                            <p><?=isset($wgoal->text) ? $wgoal->text : '설정된 주간 목표가 없습니다.'?></p>
                            <?php if($wgoal && $chapterlog): ?>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id=<?=$studentid?>&cid=<?=$chapterlog->cid?>&pid=<?=$wgoal->id?>" class="btn btn-sm btn-outline-primary">수정</a>
                            <?php endif; ?>
                        </div>
                        <div class="content-section">
                            <h3>분기 목표</h3>
                            <?php if($termplan2 && $chapterlog): ?>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id=<?=$studentid?>&cid=<?=$chapterlog->cid?>&pid=<?=$termplan2->id?>" class="btn btn-sm btn-outline-primary">보기/수정</a>
                            <?php else: ?>
                            <p>설정된 분기 목표가 없습니다.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="tab-content" id="analytics-tab">
                    <div class="section-title">📊 학습 분석</div>
                    <div class="charts-container">
                        <div class="chart-box">
                            <h4>실제 학습 시간</h4>
                            <canvas id="chartCanvasFinal-tab" width="400" height="200"></canvas>
                        </div>
                        <div class="chart-box">
                            <h4>화이트보드 활동</h4>
                            <canvas id="chartCanvasWbtimeave-tab" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <p>총 포모도로 수: <?=$np?>회</p>
                        <p>평균 집중 시간: <?=$totalduration?>분</p>
                        <p>만족도: <?=$pmresult?>점</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 스크롤 모드 -->
        <div class="scroll-mode">
            <?php if (!empty($directionlist0)): ?>
            <div class="content-section">
                <div class="section-title">🔄 현재 진행 중인 활동</div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>활동내용</th>
                            <th>예정시간</th>
                            <th>실제시간</th>
                            <th>상태</th>
                            <th>보고서</th>
                            <th>활동로그</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?=$directionlist0?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="section-title">📅 오늘의 활동</div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>활동내용</th>
                            <th>예정시간</th>
                            <th>실제시간</th>
                            <th>상태</th>
                            <th>보고서</th>
                            <th>활동로그</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?=$directionlist1?>
                    </tbody>
                </table>
            </div>
            
            <div class="content-section">
                <div class="section-title">📊 학습 분석</div>
                <div class="charts-container">
                    <div class="chart-box">
                        <h4>실제 학습 시간</h4>
                        <canvas id="chartCanvasFinal-scroll" width="400" height="200"></canvas>
                    </div>
                    <div class="chart-box">
                        <h4>화이트보드 활동</h4>
                        <canvas id="chartCanvasWbtimeave-scroll" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-title">📚 지난 활동 기록</div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>활동내용</th>
                            <th>예정시간</th>
                            <th>실제시간</th>
                            <th>상태</th>
                            <th>보고서</th>
                            <th>활동로그</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?=$directionlist2?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 메모장 섹션 -->
    <div class="memo-section" id="memo-section">
        <div class="memo-header">
            <span>📝 메모장</span>
            <div>
                <button onclick="addNewNote()" style="background: white; color: #667eea; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-plus"></i> 새 메모
                </button>
            </div>
        </div>
        <div class="memo-content">
            <div id="teacher-notes-area"></div>
            <div id="student-notes-area"></div>
        </div>
    </div>

    <!-- 복사 버튼 -->
    <button id="copyButton" class="btn btn-success" title="학습 내용을 클립보드에 복사" style="position: fixed; bottom: 20px; left: 20px; z-index: 1000;">
        <i class="fas fa-clipboard"></i> 복사
    </button>

    <script>
    // 뷰 모드 전환
    function switchView(mode) {
        const tabMode = document.querySelector('.tab-mode');
        const scrollMode = document.querySelector('.scroll-mode');
        const buttons = document.querySelectorAll('.view-toggle button');
        
        buttons.forEach(btn => btn.classList.remove('active'));
        
        if (mode === 'tab') {
            tabMode.classList.add('active');
            scrollMode.classList.remove('active');
            buttons[0].classList.add('active');
            
            // 탭 모드에서 차트 그리기
            setTimeout(() => {
                drawCharts('tab');
            }, 100);
        } else {
            scrollMode.classList.add('active');
            tabMode.classList.remove('active');
            buttons[1].classList.add('active');
            
            // 스크롤 모드에서 차트 그리기
            setTimeout(() => {
                drawCharts('scroll');
            }, 100);
        }
    }

    // 탭 전환
    function switchTab(tabName) {
        const tabs = document.querySelectorAll('.tab-content');
        const buttons = document.querySelectorAll('.tab-button');
        
        tabs.forEach(tab => tab.classList.remove('active'));
        buttons.forEach(btn => btn.classList.remove('active'));
        
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
        
        // 분석 탭이 선택되면 차트 그리기
        if (tabName === 'analytics') {
            setTimeout(() => {
                drawCharts('tab');
            }, 100);
        }
    }

    // 차트 그리기 함수
    function drawCharts(mode) {
        const graphData = <?=json_encode($graphData)?>;
        
        if (graphData.length > 0) {
            const labels = graphData.map(item => item.time);
            const finalData = graphData.map(item => item.final);
            const wbtimeaveData = graphData.map(item => item.wbtimeave);
            
            // Final 차트
            const ctxFinal = document.getElementById('chartCanvasFinal-' + mode).getContext('2d');
            new Chart(ctxFinal, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '실제 학습 시간 (분)',
                        data: finalData,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 60
                        }
                    }
                }
            });
            
            // Wbtimeave 차트
            const ctxWbtimeave = document.getElementById('chartCanvasWbtimeave-' + mode).getContext('2d');
            new Chart(ctxWbtimeave, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '화이트보드 활동 시간 (분)',
                        data: wbtimeaveData,
                        backgroundColor: '#764ba2'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 30
                        }
                    }
                }
            });
        }
    }

    // 페이지 로드 시 차트 그리기
    window.addEventListener('load', function() {
        drawCharts('tab');
    });

    // 복사 버튼 기능
    document.getElementById('copyButton').addEventListener('click', function() {
        const copyContent = `<?=str_replace(["\n", "\r", "'"], ["\\n", "\\r", "\\'"], $copyContent)?>`;
        
        navigator.clipboard.writeText(copyContent).then(function() {
            Swal.fire({
                icon: 'success',
                title: '복사 완료!',
                text: '학습 내용이 클립보드에 복사되었습니다.',
                timer: 2000,
                showConfirmButton: false
            });
        }, function(err) {
            Swal.fire({
                icon: 'error',
                title: '복사 실패',
                text: '복사하는 중 오류가 발생했습니다.'
            });
        });
    });

    // 기존 JavaScript 함수들
    function ShowMessage(Alerttext){
        swal("잠깐 !",Alerttext, {buttons: false,timer: 5000});
    }
    
    function ChangeCheckBox(Eventid,Userid, Goalid,Checkvalue){
        var checkimsi = 0;
        var Nextgoal='<?=isset($checkgoal->comment) ? $checkgoal->comment : ""?>';
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
        var Timeleft= '<?=isset($beforebreak) ? $beforebreak : 60?>';
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
                    '<table align="center" style="width:100%; height:100%; margin:0; padding:0;"><tr><td style="width:100%; height:100%; margin:0; padding:0;"><iframe style="border: none; width:100%; height:100%; margin:0; padding:0; position:fixed; top:0; left:0;" src="https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/growthmindset.php?id=<?=$studentid?>&mode=autoclick" ></iframe></td></tr></table>',
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
                        "eventid":'331',
                    },
                    success:function(data){}
                });
            }
        }				
    }

    // 메모 관련 변수들
    let currentNotes = [];
    let currentUserId = <?=$studentid?>;
    let userRole = "<?=$role?>";
    let loggedInUserId = <?=$USER->id?>;
    let hasRecordingConsent = <?=($hasRecordingConsent ? 'true' : 'false')?>;
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
                <div class="color-selector" style="margin-top: 10px; display: flex; gap: 10px; justify-content: center;">
                    <span class="color-option yellow active" data-color="yellow" style="width: 30px; height: 30px; background: #fef3c7; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                    <span class="color-option green" data-color="green" style="width: 30px; height: 30px; background: #d1fae5; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                    <span class="color-option blue" data-color="blue" style="width: 30px; height: 30px; background: #dbeafe; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                    <span class="color-option pink" data-color="pink" style="width: 30px; height: 30px; background: #fce7f3; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
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
                        colorOptions.forEach(o => {
                            o.classList.remove("active");
                            o.style.border = "2px solid transparent";
                        });
                        option.classList.add("active");
                        option.style.border = "2px solid #667eea";
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

    // 메모 저장
    function saveNote(content, color) {
        $.ajax({
            url: "../api/stickynotes_api.php",
            type: "POST",
            data: {
                action: "add_note",
                userid: currentUserId,
                content: content,
                color: color
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    loadNotes();
                    Swal.fire({
                        icon: 'success',
                        title: '저장 완료!',
                        text: '메모가 저장되었습니다.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: response.message || '메모 저장에 실패했습니다.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 연결에 실패했습니다.'
                });
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
            Swal.fire({
                icon: 'warning',
                title: '권한 없음',
                text: '이 메모를 수정할 권한이 없습니다.'
            });
            return;
        }
        
        Swal.fire({
            title: "메모 수정",
            html: `
                <textarea id="note-content" class="swal2-textarea" rows="4">${note.content}</textarea>
                <div class="color-selector" style="margin-top: 10px; display: flex; gap: 10px; justify-content: center;">
                    <span class="color-option yellow" data-color="yellow" style="width: 30px; height: 30px; background: #fef3c7; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                    <span class="color-option green" data-color="green" style="width: 30px; height: 30px; background: #d1fae5; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                    <span class="color-option blue" data-color="blue" style="width: 30px; height: 30px; background: #dbeafe; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                    <span class="color-option pink" data-color="pink" style="width: 30px; height: 30px; background: #fce7f3; border-radius: 5px; cursor: pointer; border: 2px solid transparent;"></span>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "수정",
            cancelButtonText: "취소",
            didOpen: () => {
                // 현재 색상 선택
                const currentColor = note.color || 'yellow';
                const colorOptions = document.querySelectorAll(".color-option");
                colorOptions.forEach(option => {
                    if (option.getAttribute("data-color") === currentColor) {
                        option.classList.add("active");
                        option.style.border = "2px solid #667eea";
                    }
                    option.addEventListener("click", () => {
                        colorOptions.forEach(o => {
                            o.classList.remove("active");
                            o.style.border = "2px solid transparent";
                        });
                        option.classList.add("active");
                        option.style.border = "2px solid #667eea";
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

    // 메모 업데이트
    function updateNote(noteId, content, color) {
        $.ajax({
            url: "../api/stickynotes_api.php",
            type: "POST",
            data: {
                action: "update_note",
                id: noteId,
                content: content,
                color: color
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    loadNotes();
                    Swal.fire({
                        icon: 'success',
                        title: '수정 완료!',
                        text: '메모가 수정되었습니다.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: response.message || '메모 수정에 실패했습니다.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 연결에 실패했습니다.'
                });
            }
        });
    }

    // 메모 삭제
    function deleteNote(noteId) {
        const note = currentNotes.find(n => n.id == noteId);
        if (!note) return;
        
        // 권한 체크: 선생님만 삭제 가능
        if (userRole === "student") {
            Swal.fire({
                icon: 'warning',
                title: '권한 없음',
                text: '메모를 삭제할 권한이 없습니다.'
            });
            return;
        }
        
        Swal.fire({
            title: '메모 삭제',
            text: '이 메모를 삭제하시겠습니까?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../api/stickynotes_api.php",
                    type: "POST",
                    data: {
                        action: "delete_note",
                        id: noteId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            loadNotes();
                            Swal.fire({
                                icon: 'success',
                                title: '삭제 완료!',
                                text: '메모가 삭제되었습니다.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '오류',
                                text: response.message || '메모 삭제에 실패했습니다.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: '오류',
                            text: '서버 연결에 실패했습니다.'
                        });
                    }
                });
            }
        });
    }

    // 오류 표시
    function showNoteError(message) {
        const teacherNotesArea = document.getElementById("teacher-notes-area");
        const studentNotesArea = document.getElementById("student-notes-area");
        
        const errorHtml = `<div class="error-message" style="color: red; text-align: center; padding: 20px;">${message}</div>`;
        teacherNotesArea.innerHTML = errorHtml;
        studentNotesArea.innerHTML = "";
    }

    // 기존 함수들
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
                    Swal.showValidationMessage("내용을 입력해주세요");
                    return false;
                }
                
                return { inputField, duration };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { inputField, duration } = result.value;
                
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        "eventid": '23',
                        "userid": Studentid,
                        "inputtext": inputField,
                        "duration": duration,
                    },
                    success: function(data) {
                        swal("계획입력 완료", {
                            buttons: false,
                            timer: 500,
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }
                });
            }
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
        }).then(inputField => {
            if (!inputField) throw null;
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'29',
                    "userid":Studentid,
                    "trackingid":Trackingid,       
                    "inputtext":inputField,             
                },
                success:function(data){
                    swal({
                        title: "선생님께 전달되었습니다. ^^",
                        buttons: false,
                        timer: 500,
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1000); 
                }
            })
        })
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
                setTimeout(function() {
                    location.reload();
                }, 1000);  
              break;
           case "catch2":
                swal("학습을 마무리합니다.",{buttons: false,timer: 1000});
                var CurrentTrackingId= '<?php echo isset($currenttrackingid) ? $currenttrackingid : 0; ?>';
                setTimeout(function() {
                    window.open('https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/challenge_report.php?tid='+CurrentTrackingId+'&userid='+Studentid, '_self');
                }, 1000);
              break;
          }
        });
    }

    function evaluateResult(Studentid)
    {		 
        var CurrentTrackingId= '<?php echo isset($currenttrackingid) ? $currenttrackingid : 0; ?>';
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
                    className : 'btn btn-success'
                }
            },
        }).then(inputField => {
            if (!inputField) throw null;
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'8',
                    "userid":Studentid,       
                    "inputtext":inputField,        
                },
                success:function(data){
                    swal({
                        title: "처리되었습니다.",
                        buttons: false,
                        timer: 500,
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1000); 
                }
            })
        })
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
                    Swal.showValidationMessage("내용을 입력해주세요");
                    return false;
                }
                
                return { inputField, duration };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { inputField, duration } = result.value;
                
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        "eventid": '23',
                        "userid": Studentid,
                        "inputtext": inputField,
                        "duration": duration,
                    },
                    success: function(data) {
                        swal("계획입력 완료", {
                            buttons: false,
                            timer: 500,
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }
                });
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
                    Swal.showValidationMessage("내용을 입력해주세요");
                    return false;
                }
                
                return { inputField, duration };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { inputField, duration } = result.value;
                
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        "eventid": '23',
                        "userid": Studentid,
                        "inputtext": inputField,
                        "duration": duration,
                    },
                    success: function(data) {
                        swal("계획입력 완료", {
                            buttons: false,
                            timer: 500,
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }
                });
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
                const inputField = Swal.getPopup().querySelector("#input-field").value.trim();
                const duration = Swal.getPopup().querySelector("#duration-slider").value;
                
                if (!inputField) {
                    Swal.showValidationMessage("내용을 입력해주세요");
                    return false;
                }
                
                return { inputField, duration };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { inputField, duration } = result.value;
                
                $.ajax({
                    url: "check.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        "eventid": '21',
                        "userid": Studentid,
                        "inputtext": inputField,
                        "duration": duration,
                        "trackingid": trackingid,
                    },
                    success: function(data) {
                        swal("활동을 시작합니다", {
                            buttons: false,
                            timer: 500,
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }
                });
            }
        });
    }

    function hideItem(trackingid) {
        $.ajax({
            url:"check.php",
            type: "POST",
            dataType:"json",
            data : {
                "eventid":'22',
                "trackingid":trackingid,               
            },
            success:function(data){}
        });
        setTimeout(function() {
            location.reload();
        }, 500);  
    }

    function fixText(trackingid, trackingtext) {
        swal({
            title: '수정하기',
            content: {
                element: "input",
                attributes: {
                    placeholder: "수정할 내용을 입력해 주세요",
                    type: "text",
                    value: trackingtext,
                    id: "input-field",
                    className: "form-control"
                },
            },
            buttons: {        
                confirm: {
                    className : 'btn btn-success'
                }
            },
        }).then(inputField => {
            if (!inputField) throw null;
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'30',
                    "trackingid":trackingid,       
                    "inputtext":inputField,             
                },
                success:function(data){
                    swal({
                        title: "수정되었습니다.",
                        buttons: false,
                        timer: 500,
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 500); 
                }
            })
        })
    }

    function showalltext(text) {
        swal({
            title: '오늘목표',
            text: text,
            buttons: {        
                confirm: {
                    className : 'btn btn-success'
                }
            },
        });
    }

    function addContext(Studentid) {
        swal({
            title: 'Context',
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
        }).then(inputField => {
            if (!inputField) throw null;
            $.ajax({
                url:"check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "eventid":'25',
                    "userid":Studentid,       
                    "inputtext":inputField,        
                },
                success:function(data){
                    swal({ 
                        title: "처리되었습니다.",
                        buttons: false,
                        timer: 500,
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1000); 
                }
            })
        })
    }

    // 알림 설정 관련 함수들은 필요하다면 추가
    function setNoteCallback(noteId) {
        // 알림 설정 기능 구현
        console.log('알림 설정:', noteId);
    }

    function openCallbackModal() {
        // 전체 알림 설정 모달
        console.log('전체 알림 설정');
    }

    function deleteAllNotes() {
        // 모든 메모 삭제
        if (userRole !== "student") {
            Swal.fire({
                title: '모든 메모 삭제',
                text: '정말로 모든 메모를 삭제하시겠습니까?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '모두 삭제',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    // 모든 메모 삭제 API 호출
                    console.log('모든 메모 삭제');
                }
            });
        }
    }
    </script>
</body>
</html>