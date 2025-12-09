<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
 
$timestart=$timecreated-604800;
 $quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.timemodified > '$timestart' AND mdl_quiz_attempts.userid='$userid' ORDER BY mdl_quiz_attempts.id DESC LIMIT 20 ");
$quizresult = json_decode(json_encode($quizattempts), True);

$nquiz=count($quizresult);
$quizlist='<hr>';
$todayGrade=0;  $ntodayquiz=0;  $weekGrade=0;  $nweekquiz=0;$totalquizgrade1=0;$totalmaxgrade1=0;$nmaxgrade1=0; $totalquizgrade2=0;$totalmaxgrade2=0;$nmaxgrade2=0; $totalquizgrade3=0;$totalmaxgrade3=0;$nmaxgrade3=0; 
unset($value); 	
foreach($quizresult as $value) 
    {
  
    if($value['review']==3)  // 워밍업 활동
        {
          $quizlist00.='<tr><td> <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.' <input type="checkbox" name="checkAccount"    onClick="AddReview(11111,\''.$userid.'\',\''.$value['id'].'\', this.checked)"/> </a></td></tr> ';
        } 
    } 
 
$eventtime=$timecreated-604800;
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'  AND tlaststroke>'$eventtime' AND contentstype=2 AND status='review' AND  active=1  ORDER BY tlaststroke DESC LIMIT 300 ");

$result1 = json_decode(json_encode($handwriting), True);
unset($value);
$wboardlist.= '<tr><td><hr></d><td><hr></d><td><hr></d><td><hr></d></tr>';
foreach($result1 as $value) 
    {  

    if($value['status']==='review' && time()> $value['treview'])
        {
        $nreview2++;
        $imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626450444001.png" width="15">';  // 복습예약 활동문항
        $reviewwb0.= $imgstatus.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$userid.'" target="_blank" >복습예약 </a> ('.$value['nreview'].'회) | ';
        }      
    }

$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
 
$showpage= '<table align=center width=90%><tr><td>플러그인</td><td>학습흐름에 맞는 사전학습 플러그인을 설정해 보세요</td></tr><tr><td>복습예약</td><td style=" vertical-align: top; "><hr>'.$reviewwb0.'</td></tr>
<tr><td>준비퀴즈</td><td style=" vertical-align: top; "><hr>'.$quizlist00.'</td></tr></table>';

$pagewelcome='오늘 수업을 시작하기 전에 준비학습을 진행해 주세요.';
$gptprompteng.='';
// 조건문으로 선생님별로 선택
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=todaygoal"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
 
?>