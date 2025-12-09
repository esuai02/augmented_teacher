<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
if($studentid==NULL)$studentid=$USER->id;

$timecreated=time(); 
$hoursago=$timecreated-14400;
$aweekago=$timecreated-604800;
$weeksago=$timecreated-604800*2;
$amonthago=$timecreated-604800*4;
$monthsago=$timecreated-604800*20;
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;

$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
   
echo '<table align=center width=90%><tr><td valign=top><div class="table-wrapper"><table width=100%><thead><tr><th style="white-space: nowrap;" width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$studentid.'&type=init"> <img loading="lazy"  src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=70></a>  <img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/visionbook.png width=58></th><th style="color:#1956FF;font-size:20px;" width=30%><a style="text-decoration:none;color:#1956FF;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$stdname.'</a> (Life Equalizer for students.)</th><th > <a href="https://moreleap.clickn.co.kr/pages/visionbook">비전스토리</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=%EB%AA%A9%ED%91%9C"> MBTI</a>  |   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'">커리큘럼 최적화</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'">분기목표</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$studentid.'&mode=CA">주간목표 & 오늘목표</a>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/bigplan.html">소개</a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'">개인설정</a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$studentid.'&type=submittoday">프롬프트</a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/hellobrain.php?id='.$studentid.'">초기설정</a> </th></tr></thead><tr><td><hr></td><td><hr></td><td><hr></td></tr>'.$ongoinglist.'<hr>'.$completedlist.'</table></div></td></tr></table>';


$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role; 

$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by deadline DESC LIMIT 2");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{		$missionid=$value['id'];
        $plantype=$value['plantype'];
        $text=$value['memo'];		
        $text=iconv_substr($text, 0, 70, "utf-8");								
        $deadline= $value['deadline'];    
        $dateString = date("Y-m-d",$deadline);
        $checkbox='';
        if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
        elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
        elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
        else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';
    
        if($plantype==='분기목표')$plantype='<b style="color:purple;">분기목표</b>';
        elseif($plantype==='방향설정')$plantype='<b style="color:red;">방향설정</b>';
        //elseif($plantype==='중간고사')$plantype='<b style="color:blue;">중간고사</b>';
        //elseif($plantype==='기말고사')$plantype='<b style="color:blue;">기말고사</b>';
        //elseif($plantype==='모의고사')$plantype='<b style="color:blue;">모의고사</b>';
    
        $checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck(150,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
        $checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck2(200,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
    
        if($value['plantype']==='방향설정') $Grandgolden.= '<tr> <td width=4% style="padding-bottom:0px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'"><img src="https://mathking.kr/Contents/IMAGES/direction.gif" width=40></a></td><td width=15% align=left style="font-size:12pt">'.$plantype.'</td> <td align=left style="font-size:12pt;" >'.$text.'</td> <td width=5%></td> <td width=10% style="font-size:10pt">'.$dateString.'</td><td width=5%>'.$checkdeadline.'</td><td width=10% style="font-size:10pt"></td><td>'.$checkhide.'</td></tr>';	 									
        else $goalsteps.= '<tr> <td width=4%><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/bigplan.html"target="_blank"><img style="padding-bottom:0px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641245056.png width=40></a></td><td width=15% align=left style="font-size:12pt">'.$plantype.'</td> <td align=left style="font-size:12pt;" >'.$text.'</td> <td width=5%></td> <td width=10% style="font-size:10pt">'.$dateString.'</td><td width=5%>'.$checkdeadline.'</td><td width=10% style="font-size:10pt"></td><td>'.$checkhide.'</td></tr>';
	} 
	
    echo' <table align=center width=90% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$Grandgolden.$goalsteps.'</table><hr> '; 

	 
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid'  AND timecreated>'$weeksago' ORDER BY id DESC ");
$adayAgo=time()-43200;
$result2 = json_decode(json_encode($goals), True);
unset($value);
 
foreach(array_reverse($result2) as $value)
	{
	$date_pre=$date;
	$att=gmdate("m월 d일 ", $value['timecreated']+32400);
	$date=gmdate("d", $value['timecreated']+32400);
	$goaltype=$value['type'];


	$daterecord=date('Y_m_d', $value['timecreated']);  	 
	$tend=$value['timecreated'];
	 
	$tfinish0=date('m/d/Y', $value['timecreated']+86400); 
 	$tfinish=strtotime($tfinish0);

    if($goaltype==='오늘목표' || $goaltype==='검사요청')
        {
        $notetype='summary';
        $goalhistory.= '<tr><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
        <td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>';
        }
   elseif($goaltype==='주간목표')
        {
       $notetype='weekly';
        $goalhistory.= '<tr style="background-color:lightblue;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
        <td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>';
        }
    elseif($goaltype==='시험목표')
        {
        $goaltype='분기목표';$notetype='examplan';
        $goalhistory.= '<tr style="background-color:black;color:white;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
        <td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>';
        }  
    }
    $schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
    
    $lastGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated<='$wtimestart1' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
    $wgoaldate = date('Y-m-d', $lastGoal->timecreated);
     
    $displaymemo='<br><table width=100%><tr><td><h5 align=center style="color:#4287f5;"> # <b style="color:#020202;">Golden Goal</b>우상향 주간목표 + Vertical drilling을 통한 학습효율 향상  </h5></td><td><h6><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&nweek=4&eid='.$schedule->id.'"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></a> '.$schedule->memo8.' </h6></td></tr></table>';

    echo '<table width=90% align=center>'.$goalhistory.'</table> 
    <table width=90% align=center><tr>'.$chosenitems.'</tr></table>'.$displaymemo.'<table width=90% align=center>
        <tbody>
        <tr><td>'.$summarywb.' </td> <td><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  '.$placeholder.' '.$presettext.'></td><td><div class="select2-input" style="font-size: 2.0em;padding-top:15px;"> <select id="basic1" name="basic" class="form-control"  ><h3><option value="오늘목표" '.$selectgtype1.'>오늘목표</option><option value="주간목표"  '.$selectgtype2.'>주간목표</option><option value="분기목표"  '.$selectgtype3.'>분기목표</option></h3></select> </div></td>	
        <td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="데드라인" value="'.$deadline.'"></td><td><div class="select2-input" style="font-size: 2.0em;padding-top:1px;"><select id="basic2" name="basic2" class="form-control"  ><h3><option value="1">1 쉬운</option><option value="2" selected>2 보통</option><option value="3">3 도전</option><option value="4">4 열공</option><option value="5">5 몰입</option></h3></select> </div></td><td valign=bottom><button type="button" id="update" style="width:100;height:40;" onclick="edittoday(2,'.$studentid.',$(\'#squareInput\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val()); "> 업데이트</a></button></td>
        <td>'.$checkstudentinput.'</td>
        </tr> 
        </tbody>
    </table>';


if($USER->id==2)echo '<table width=90% align=center>
<tr><td>
# Mass genius 시대를 만든다. 상온 상압 초전도체 >> 교육에서는 Mass Genius 시대를 의미.
# 용어는 기억 캡슐이다. 모듈화된 기억저장은 에너지 캡슐이 된다. <br>
# 인지과학 및 생물학적 관점에서.. 도파미네이션 ( GPT 피드백 )</td></tr></table><hr><table width=90% align=center><tr><td># 각각의 목표 단계별 작용 사건 구체화. 정성적 체크리스트를 이용한 대화에서 시작</td></tr>
<tr><td># 정성적 체크리스트를 정량화하고 GPT 피드백을 순환적으로 한 다음 End-point detection 및 상담시스템과 연동<br> 
# 지치기 전에 휴식을 취하세요.. ## 하기 전에 ## 하세요 ~ 포모도르 공부법. 하루종일 지치지 않고 오히려 에너지가 상승합니다.
# 학생설문을 설계하고 설문을 토대로 학생과 대화를 준비. 학생과의 개인대화를 진행하여 대화에서 중요한 것으로 보이는 감정, 정보 등 측면의 포인트를 확정. 초기 대화에서 출발하여 1년 동안의 대화의 흐름의 골격을 형성하고 골격에 맞는 대화가 자동으로 trigger 되며 대화 기억을 토대로 캐어하는 시스템을 설계하여 적용한다. 자동 동영상 생성 프로그램으로 매주 미션 동영상 재생.. 미션 임파서블 같은 임팩트 있는 방식의 미션 전달. 8월 한달 todo.
# 상담시스템은 시뮬레이션이 끝난 상태에서 진행. 시뮬레이션 시간을 최소화하기 위한 루틴을 시스템화<br>
# 끝말잇기, 징검다리 교수법. coming of new human.  <br>
# 문제풀이 = 계획 + 실행 확립하기  (독립세션 및 평가)  <br>
# 복습 = 확인 + 숙달 (독립세션 및 평가)  <br>
# 도덕경 베이스... <br>
# 책을 만들어서 캐릭터화<br>
# 학생이 작은 질문을 만들고 답하는 루틴을 시스템이 촉진시킨다. (진입질문, 해체질문, 기호질문, 정의질문.. 질문의 종류와 질문을 하는 비법을 체계화 한다. 묻고 답하는 절차를 가속화하고 세밀하게 만드는 시스템을 구축한다. GPT를 이를 위해 사용한다. 내가 하려는 질문을 목차에서 찾아내는 시스템 + 상호작용 촉진시스템으로 간다.)

# 모르는 것이 나타난 것이 아니라 점프를 시도하는 것이 문제다. 이것을 배경화하라.
# 순서와 왜 (이유)가 쉽게 표현된 풀이로 !!! __ 화살표 (순서) / 이음말 (이유)<br>
# spaced repition의 모듈화된 독립세션 환경 적용 (주기와 횟수 설정, 최대 5개까지 ?) <br>
# GPT 필기인식 자동평가 : 반복하여 쓰기 횟수 테스트 https://chat.openai.com/share/9d88d08c-6ee0-481b-a559-f9dc704b82aa  <br> 
# 지면평가 회로를 개인화된 도파민 회로를 만들어라. <br>
#1주일 단위의 완벽한 인지적 진화를 경험하게 한다.
# GPT는 수직적으로 구조화된 내용을 처리하는 것이 탁월하다. 최면도구..###########
<hr>
#vertical 상호작용 스킬<hr>
1. 지면평가 2. 미니 지면 평가 
<br>3. 화이트보드 retry (긍정적 활동 시각피드백 적용) 학습경로가 달라지는 것을 시각화하여 전달 
<br>4. 계단 내려가기 .. 수학마을로.. 5. 독립모듈활동과 모듈활동간을 통한 학습경로 업그레이드.. 회로 뚫기 
<br>6. 일상화전략 (단순 스토리텔링) 7. 도파미네이션 전략 (반복에 의한 신경망 형성에 대한 임상적 접근)
<br>8. 과거 학습기억은 이미 무의식의 어느 영역으로 이동을 하였다. 따라서 새로운 학습시작 시 해당 문의식에
접근하여야 한다. 이것이 몰입 메타인지다. 기억은 계속해서 더 깊은 무의식으로 이동하기 때문에 이러한 방법을 사용하면 무의식과 의식의 통합이 이러나게 되고 결국 인간은 각성상태에 도달하게 된다.<hr>
# 학생들이 자신의 기억에 접속하게 하는 스킬<hr>
- 로딩시간이 오래걸리는 문제. 해당 컨텐츠 띄우기. 독립세션을 주고 시간을 리듬감있게 활용하여 기억을 활성화시키기.
- 학습은 개념을 이어주는 과정이다. seed는 DNA 속 정보다
</td></tr>
</table> ';



echo '

<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
	  
	<script type="text/x-mathjax-config">
	MathJax.Hub.Config({
	  tex2jax: {
		inlineMath:[ ["$","$"], ["\\[","\\]"] ],
	   // displayMath: [ ["$","$"], ["\\[","\\]"] ]
	  }
	});
	</script>
	<script type="text/javascript" async
	  src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML">
	</script> 


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

	<script> 
	window.onload = function() {
		window.scrollTo(0, document.body.scrollHeight);
	  };
	function ChangeCheckBox(Eventid,Userid, Wboardid, Checkvalue){
		var checkimsi = 0;
		if(Checkvalue==true){
			checkimsi = 1;
		}
		swal("적용되었습니다.", {buttons: false,timer: 100});
	   $.ajax({
			url: "../students/check.php",
			type: "POST",
			dataType: "json",
			data : {"userid":Userid,       
					"wboardid":Wboardid,
					"checkimsi":checkimsi,
					 "eventid":Eventid,
				   },
			success: function (data){  
			}
		});
		setTimeout(function(){
 		 location.reload();
		}, 200);
	}
	</script>
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
 
	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<style>
	.table-wrapper {
		position: relative;
		height: 100% /* 테이블이 표시될 영역의 높이를 지정하세요 */
		overflow: auto;
	  }
	  
	  .table-wrapper thead {
		position: sticky;
		top: 0;
		background-color: #BCD5FF; /* 첫 번째 행의 배경색을 지정하세요 */
		z-index: 1;
	  } 

	
.tooltip3:hover .tooltiptext1 {
	visibility: visible;
  }
  a:hover { color: green; text-decoration: underline;}
  
  .tooltip3 {
   position: relative;
	display: inline;
	border-bottom: 0px solid black;
  font-size: 14px;
  }
  
  .tooltip3 .tooltiptext3 {
	  
	visibility: hidden;
	width: 40%;
   
	background-color: #ffffff;
	color: #e1e2e6;
	text-align: center;
	font-size: 14px;
	border-radius: 10px;
	border-style: solid;
	border-color: #0aa1bf;
	padding: 20px 1;
  
	/* Position the tooltip */
	top:50;
	right:5%;
	position: fixed;
  z-index: 1;
   
  } 
  .tooltip3 img {
	max-width: 600px;
	max-height: 1200px;
  }
  .tooltip3:hover .tooltiptext3 {
	visibility: visible;
  }
	</style>
';
?>
