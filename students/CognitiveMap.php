<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid=required_param('id', PARAM_INT); 
$period=required_param('tb', PARAM_INT); 
$tbegin=time()-$period;
 
echo ' 
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	 
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>KTM Cognitive Map</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" type="image/x-icon"/>
	<script src="../assets/js/plugin/webfont/webfont.min.js"></script>
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>';

// 과목별, 단원별 정보 가지고 오기 $cid = 59 ~ 79 , 70은 제외 학년 역순으로.. 추후 배열로 입력해서 정렬.
 
for($cid=59;$cid<=79;$cid++)
	{
	$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
	$subjectname=$curri->name;
	$chapnum=$curri->nch;
	$cntid=$curri->contentslist;
	$examid=$curri->examlist;
	$bookid=$curri->contentslist; 
	for($nch=1;$nch<=$curri->nch;$nch++)
		{
		$ch='ch'.$nch;
		$ch=$curri->$ch;  // 단원명
		$cnt='cnt'.$nch;
		$cnt=$curri->$cnt;  // 단원별 checklist cmid
		$qid='qid'.$nch;
		$qid=$curri->$qid;
		$cmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$qid' ");
		$quizid=$cmid->inst;
  
		$dday='dday'.$nch;
		$$dday=$mission->$dday;
		$ddaystamp = strtotime($$dday);
		$tbegin=time()-$tbegin;

		//단원별 T 정보
		$quizattempt = $DB->get_record_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades  
		FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.timefinish>'$tbegin' AND mdl_quiz_attempts.userid='$studentid' AND mdl_quiz.id='$quizid'   ORDER BY mdl_quiz_attempts.id DESC LIMIT 1"); 
		$grade=round($quizattempt->sgrades/$quizattempt->tgrades*100,0); 
		$attemptdate=date("Y-m-d", $quizattempt->timestart);

		if($quizattempt->timestart<time()-86400*10000)$qresult='<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'" target="_blank">응시전</a>';
		else $qresult='<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">'.$grade.'점 / '.$attemptdate.'</a>';
 		$pagemode='view';
		if($role!=='student')$pagemode='report';

		//단원 -> 주제 목록 가져오기  $cnt -> 단원별 체크리스트 아이디, checklist item들을 읽어서 주제목록을 배열
		$checklistCmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$cnt' ");
		$checklistId=$checklistCmid->inst;

		$contents = $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$checklistId' order by position ASC ");  
		$result = json_decode(json_encode($contents), True);
		$num=1;
		$topiclist='';
		unset($value);
		foreach($result as $value)
			{
			$mid2=$value['moduleid'];
			$name='contentname'.$num;   
			$displaytext=$value['displaytext'];   //str_replace("개념도약",".",$value['displaytext']);
			$displaytext2=str_replace('개념도약:','',$displaytext);
			$$name=$displaytext;	 // 주제명
			
			$link='contentlink'.$num;
			$whiteboardurl=$value['linkurl'].'&studentid='.$studentid; // $contentslink# 할당
			 
			$num++;	
			$cntlist=$mid2;
 		
			// 주제별 버튼 생성하기
			
			$url= $whiteboardurl;
			$currenturl=substr($url, 0, strpos($url, '&mode=100')); // 문자 이후 삭제
			$currenturl=strstr($currenturl, '?id=');  //before
			$currenturl=str_replace("?id=","",$currenturl);
			$topiccmid=str_replace("&mode=100","",$currenturl);
			
			 
			$getWb=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND pagenum=1 AND cmid='$topiccmid'ORDER BY id DESC LIMIT 1   "); //
			 
			$progress1=$getWb->star;
			$progress2=$getWb->depth;
			$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p0.png" height=27>';
			if($progress1<5)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p0.png" height=27>';
			elseif($progress1<15)$progressimg='<img style="vertical-align:top" src="https://mathking.kr/Contents/IMAGES/p1.png" height=27>';			
			elseif($progress1<25)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p2.png" height=27>';
			elseif($progress1<35)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p3.png" height=27>';
			elseif($progress1<45)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p4.png" height=27>';
			elseif($progress1<55)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p5.png" height=27>';
			elseif($progress1<65)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p6.png" height=27>';
			elseif($progress1<75)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p7.png" height=27>';
			elseif($progress1<85)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p8.png" height=27>';
			elseif($progress1<95)$progressimg='<img src="https://mathking.kr/Contents/IMAGES/p9.png" height=27>';
			else $progressimg='<img style="vertical-align:top;"  src="https://mathking.kr/Contents/IMAGES/p10.png" height=27>';
			if(strpos($$name, '개념도약')!==false)$topiclist.='<button type="text"  id="'.$cntlist.'"><a href="'.$whiteboardurl.'&moduleid='.$cnt.'"target="_blank">'.$displaytext2.' &nbsp;  '.$progressimg.'</a> </button>';
			 

 			}

		

		if($cid==73) $course1.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==74) $course2.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==75) $course3.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==76) $course4.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==78) $course5.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==79) $course6.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	

		if($cid==66) $course7.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==67) $course8.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==68) $course9.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==69) $course10.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==71) $course11.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==72) $course12.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	

		if($cid==59) $course13.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==60) $course14.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==61) $course15.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==62) $course16.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==63) $course17.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==64) $course18.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		if($cid==65) $course19.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
 		} 
	}
 

$Note=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$studentid' AND msntype=7  AND complete=0 ORDER BY timecreated DESC LIMIT 1 ");
$noteId=$Note->subject;
if($noteId==66)$nnote=1;
if($noteId==67)$nnote=2;
if($noteId==68)$nnote=3;
if($noteId==69)$nnote=4;
if($noteId==71)$nnote=5;
if($noteId==72)$nnote=6;
if($noteId==59)$nnote=7;
if($noteId==60)$nnote=8;
if($noteId==61)$nnote=9;
if($noteId==62)$nnote=10;
if($noteId==63)$nnote=11;
if($noteId==64)$nnote=12;
if($noteId==65)$nnote=13;
$ifactiveTemp='ifactive'.$nnote; 
$$ifactiveTemp=' active';
$showActivetemp='showActive'.$nnote;
$$showActivetemp=' show active';


echo '
<div class="row">
<div class="col-md-12">
<div class="card">
<div class="card-header"></div>
<div class="card-body">
<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
<li class="nav-item">
<a class="nav-link '.$ifactive0.'" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">개념 지도 '.$badgeH.'</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive1.'" id="pills-subject1-tab" data-toggle="pill" href="#pills-subject1" role="tab" aria-controls="pills-subject1" aria-selected="false">중등 1-1</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive2.'" id="pills-subject2-tab" data-toggle="pill" href="#pills-subject2" role="tab" aria-controls="pills-subject2" aria-selected="false">중등 1-2</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive3.'" id="pills-subject3-tab" data-toggle="pill" href="#pills-subject3" role="tab" aria-controls="pills-subject3" aria-selected="false">중등 2-1</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive4.'" id="pills-subject4-tab" data-toggle="pill" href="#pills-subject4" role="tab" aria-controls="pills-subject4" aria-selected="false">중등 2-2 </a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive5.'" id="pills-subject5-tab" data-toggle="pill" href="#pills-subject5" role="tab" aria-controls="pills-subject5" aria-selected="false">중등 3-1</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive6.'" id="pills-subject6-tab" data-toggle="pill" href="#pills-subject6" role="tab" aria-controls="pills-subject6" aria-selected="false">중등 3-2</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive7.'" id="pills-subject7-tab" data-toggle="pill" href="#pills-subject7" role="tab" aria-controls="pills-subject7" aria-selected="false">고등수학 상</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive8.'" id="pills-subject8-tab" data-toggle="pill" href="#pills-subject8" role="tab" aria-controls="pills-subject8" aria-selected="false">고등수학 하</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive9.'" id="pills-subject9-tab" data-toggle="pill" href="#pills-subject9" role="tab" aria-controls="pills-subject9" aria-selected="false">수학 1</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive10.'" id="pills-subject10-tab" data-toggle="pill" href="#pills-subject10" role="tab" aria-controls="pills-subject10" aria-selected="false">수학 2</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive12.'" id="pills-subject12-tab" data-toggle="pill" href="#pills-subject12" role="tab" aria-controls="pills-subject12" aria-selected="false">확률과 통계</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive11.'" id="pills-subject11-tab" data-toggle="pill" href="#pills-subject11" role="tab" aria-controls="pills-subject11" aria-selected="false">미분과 적분</a>
</li>
<li class="nav-item">
<a class="nav-link '.$ifactive13.'" id="pills-subject13-tab" data-toggle="pill" href="#pills-subject13" role="tab" aria-controls="pills-subject13" aria-selected="false">기하</a>
</li> 
</ul>';


echo '
<div class="tab-content mb-3" id="pills-tabContent">
<div class="tab-pane fade '.$showActive0.'" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
수학 개념지도</div>			 
<div class="tab-pane fade '.$showActive1.'" id="pills-subject1" role="tabpanel" aria-labelledby="pills-subject1-tab">
<table style="background-color:#edf6f7">'.$course7.'</table></div>
<div class="tab-pane fade '.$showActive2.'" id="pills-subject2" role="tabpanel" aria-labelledby="pills-subject2-tab">
<table style="background-color:#edf6f7">'.$course8.'</table></div>
<div class="tab-pane fade '.$showActive3.'" id="pills-subject3" role="tabpanel" aria-labelledby="pills-subject3-tab">
<table style="background-color:#edf6f7">'.$course9.'</table></div>
<div class="tab-pane fade '.$showActive4.'" id="pills-subject4" role="tabpanel" aria-labelledby="pills-subject4-tab">
<table style="background-color:#edf6f7">'.$course10.'</table></div>
<div class="tab-pane fade '.$showActive5.'" id="pills-subject5" role="tabpanel" aria-labelledby="pills-subject5-tab">
<table style="background-color:#edf6f7">'.$course11.'</table></div>
<div class="tab-pane fade '.$showActive6.'" id="pills-subject6" role="tabpanel" aria-labelledby="pills-subject6-tab">
<table style="background-color:#edf6f7">'.$course12.'</table></div>
<div class="tab-pane fade '.$showActive7.'" id="pills-subject7" role="tabpanel" aria-labelledby="pills-subject7-tab">
<table style="background-color:#edf6f7">'.$course13.'</table></div>
<div class="tab-pane fade '.$showActive8.'" id="pills-subject8" role="tabpanel" aria-labelledby="pills-subject8-tab">
<table style="background-color:#edf6f7">'.$course14.'</table></div>
<div class="tab-pane fade '.$showActive9.'" id="pills-subject9" role="tabpanel" aria-labelledby="pills-subject9-tab">
<table style="background-color:#edf6f7">'.$course15.'</table></div>
<div class="tab-pane fade '.$showActive10.'" id="pills-subject10" role="tabpanel" aria-labelledby="pills-subject10-tab">
<table style="background-color:#edf6f7">'.$course16.'</table></div>
<div class="tab-pane fade '.$showActive11.'" id="pills-subject11" role="tabpanel" aria-labelledby="pills-subject11-tab">
<table style="background-color:#edf6f7">'.$course17.'</table></div>
<div class="tab-pane fade '.$showActive12.'" id="pills-subject12" role="tabpanel" aria-labelledby="pills-subject12-tab">
<table style="background-color:#edf6f7">'.$course18.'</table></div>
<div class="tab-pane fade '.$showActive13.'" id="pills-subject13" role="tabpanel" aria-labelledby="pills-subject13-tab">
<table style="background-color:#edf6f7">'.$course19.'</table></div>
</div>
 </div></div></div></div>	 ';

echo '	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script><!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	 
	<script src="../assets/js/setting-demo.js"></script>
	<script>
		$("#datepicker").datetimepicker({
			format: "MM/DD/YYYY",
		});
	</script>
 ';




 echo '   
<style> 
html,body{
  height:0%;
}
body{
  text-align:center;
}
a:link {
  color: black;
  background-color: transparent;
  text-decoration: none;
}
body:before{
  content:"";
  height:0%;
  display:inline-block;
  vertical-align:middle;
}
button{
  background:#e8fdff;
  color:#fff;

  border: 1px solid #18ad31;
  position:relative;
  height:30px;
  font-size:1.0em;
  padding:0 5em;
  cursor:pointer;
  transition:600ms ease all;
  outline:1px;
}
button:hover{
  background:#fff;
  color:#1AAB8A;
}
button:before,button:after{
  content:"";
  position:absolute;
  top:0;
  right:0;
  height:2px;
  width:0;
  background: #1AAB8A;
  transition:400ms ease all;
}
button:after{
  right:inherit;
  top:inherit;
  left:0;
  bottom:0;
}
button:hover:before,button:hover:after{
  width:100%;
  transition:800ms ease all;
}


.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 500px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip1:hover .tooltiptext1 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 500px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 500px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
}
a.tooltips span:after {
  position: absolute;
  bottom: 100%;
  right: 1%;
  margin-left: -10px;
  width: 0;
  height: 0;
  border-bottom: 8px solid #23ad5f;
  border-right: 8px solid #0a5cf5;
  border-left: 8px solid #0a5cf5;
}
a:hover.tooltips span {
  visibility: visible;
  opacity: 1;
  top: 0px;
  right: 0%;
  margin-left: 10px;
  z-index: 999;
  border-bottom: 1px solid #15ff00;
  border-right: 1px solid #15ff00; 
  border-left: 1px solid #15ff00;
}
</style>';
?>