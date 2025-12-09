<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid=required_param('id', PARAM_INT); 
$cid=required_param('cid', PARAM_INT); 
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
			$whiteboardurl=$value['linkurl']; // $contentslink# 할당
			 
			$num++;	
			$cntlist=$mid2;

			$icntid = $_GET["cntid"];   // icontent page cmid ---> 버튼 클릭 시 sweet alert로 표시해 줄 내용 생성
			$icontent=$DB->get_record_sql("SELECT * FROM mdl_icontent where id='$icntid' ORDER BY id DESC LIMIT 1    ");  // icontent 모듈 기본 정보 가지고 오기			

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
			 

			 
		//	if(strpos($$name, '개념도약')!==false)$topiclist.='<button type="text"  id="'.$cntlist.'"><a href="'.$whiteboardurl.'&moduleid='.$cnt.'"target="_blank">'.$displaytext2.'</a></button>';		 	
 			}
	 	$course.= '<tr><td width=3%></td><td>'.$nch.'</td><td width=20%><a href="https://mathking.kr/moodle/mod/checklist/'.$pagemode.'.php?id='.$cnt.'&studentid='.$studentid.' " target="_blank">'.$ch.'</td><td>'.$topiclist.'</td><td width=10%></td><td width=10%>'.$qresult.'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';	
		} 

 
echo '
<div class="row">
<div class="col-md-12">
<div class="card">
<div class="card-body"> 
<table width=100% style="font-size:25;text-align:center;background-color:lightgreen;"><tr><td>'.$subjectname.'</td></tr></table><hr>
<table style="background-color:#edf6f7">'.$course.'</table>
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