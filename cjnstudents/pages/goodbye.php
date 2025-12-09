<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
 
$promptdata=$DB->get_records_sql("SELECT * FROM mdl_aba_userprompt where userid LIKE '$userid' AND checked LIKE '1' ORDER BY id ASC ");  
$promptresult = json_decode(json_encode($promptdata), True);
$numdata=count($promptresult);
$ndata=0;
unset($value);
foreach($promptresult as $value)
	{
    $prompttype=$value['type'];
    $promptchecked=$value['checked'];
    $userprompt=$value['userprompt'];   
    if($prompttype=='staycalm' && $promptchecked==1)   
        {
        $checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1 ");
        $wgoal= $DB->get_record_sql("SELECT *  FROM mdl_abessi_today WHERE userid='$userid'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
        $ratio1=$checkgoal->score;$ratio2=$wgoal->score;

        if($ratio1<70)$statusstr1='침착도가 매우 낮습니다.';
        elseif($ratio1<75)$statusstr1='침착도가 낮습니다.';
        elseif($ratio1<80)$statusstr1='침착도가 부족합니다.';
        elseif($ratio1<85)$statusstr1='침착도가 보통입니다.';
        elseif($ratio1<90)$statusstr1='침착도가 양호합니다.';
        elseif($ratio1<95)$statusstr1='침착도가 높습니다';
        elseif($ratio1<101)$statusstr1='침착도가 매우 높습니다.';

        if($ratio2<70)$statusstr2='최근 침착도가 매우 낮습니다.';
        elseif($ratio2<75)$statusstr2='최근 침착도가 낮습니다.';
        elseif($ratio2<80)$statusstr2='최근 침착도가 부족합니다.';
        elseif($ratio2<85)$statusstr2='최근 침착도가 보통입니다.';
        elseif($ratio2<90)$statusstr2='최근 침착도가 양호합니다.';
        elseif($ratio2<95)$statusstr2='최근 침착도가 높습니다';
        elseif($ratio2<101)$statusstr2='최근 침착도가 매우 높습니다.';

        if($ratio1-ratio2>15)$statusstr3='오늘은 평상시 보다 매우 침착합니다.';
        elseif($ratio1-ratio2>10)$statusstr3='오늘은 평상시 보다 많이 침착합니다.';
        elseif($ratio1-ratio2>5)$statusstr3='오늘은 평상시 보다 다소 침착합니다.';
        elseif($ratio1-ratio2>0)$statusstr3='오늘은 평상시 보다 좀 더 침착합니다.';
        elseif($ratio1-ratio2>-5)$statusstr3='오늘은 평상시 보다 침착도가 다소 부족합니다.';
        elseif($ratio1-ratio2>-10)$statusstr3='오늘은 평상시 보다 침착도가 많이 부족합니다.';
        elseif($ratio1-ratio2>-15)$statusstr3='오늘은 평상시 보다 매우 많이 부족합니다.';
        $prompttext.=$userprompt.' : '.$statusstr1.$statusstr2.$statusstr3;
        }
    elseif($prompttype=='distract' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='inactive' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='rest' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='longquiz' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='shortquiz' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='topics' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='diagnosis' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='usedtime' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='attendance' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='goldenplan' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='goldengoal' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='todaygoal' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='feedbacktext' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='schoolexam' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='staycalm' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='learningskill' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='metacognition' && $promptchecked==1)   
        { 
        $prompttext.=$userprompt;
        }
    elseif($prompttype=='mainprompt')   
        { 
        $prompttext.=$userprompt;
        }
    }  

$preparegpt=$prompttext.'라는 문장을 '.$mainprompt.'으로 써줘';

$showpage= '<table with=100% align=center><tr><td>수고하셨습니다.</td></tr><tr><td>오늘의 노력과 성취를 축하하고 성장 마인드셋을 고취할 테마 플러그인을 설치해 보세요</td></tr><tr><td>다음과 같은 내용으로 안내 카톡이 발송됩니다.</td></tr><tr><td>귀가 카톡 내용<hr>'.$statusstr1.$statusstr2.$statusstr3.'</td></tr></table><br><br>';

if ($role !== 'student') {
    $url='https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/popup/todayresult_display.php?userid='.$userid.'&teacherid='.$teacherid.'&type=goodbye';
    $showpage .= '<br><br><table align=center><tr><td><button onclick="DragWindowLeft(90,80,\''.$url.'\')">프롬프트 수정</button></td></tr></table>';
} // 기본 컨텐츠


$pagewelcome='수고하셨습니다.';

// 조건문으로 선생님별로 선택
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=mycourses"><button class="submit-button2">되돌아가기</button></a></td>';

$buttons='<tr>'.$buttons.'</tr>';
?>