<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$tagid=$_GET["tagid"];   
$studentid=$_GET["userid"];   
$studentid=$_GET["userid"];   
echo '
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
';
 
$tag=$DB->get_record_sql("SELECT * FROM  mdl_tag  WHERE id='$tagid' ");
$tagname=$tag->name;
$tag_instance=$DB->get_records_sql("SELECT * FROM mdl_tag_instance  WHERE tagid='$tagid'  ");  //137 ~ 141 (기초 ~ 고난도) & checkbox 클릭시 이미지 숨김모드, 원클릭 모두 숨김 (tooltip 보기) 및 숨김 해제

$questions= json_decode(json_encode($tag_instance), True);
$question_array='<hr><table align= center><tr><th></th><th></th><th></th></tr></table>';
unset($value); 
foreach($questions as $value)
	{
	$itemid=$value['itemid'];
	$wb=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages  WHERE userid LIKE '$studentid'  AND wboardid LIKE '%nx4HQkXq%' AND  contentsid='$itemid' AND contentstype='2' ORDER BY id DESC LIMIT 1");
	$encryption_id='';
	if($wb->id!=NULL)
		{
 
		$encryption_id=$wb->wboardid;
		$nstroke=(int)($wb->nstroke/2);
		$ave_stroke=round($nstroke/(($wb->tlast-$wb->tfirst)/60),1);
		$contentstype=$wb->contentstype;
		$timemodified=$wb->timemodified;
		$status=$wb->status;
		$contentsid=$wb->contentsid;
		if($nstroke<10)
			{
			$ave_stroke='###';
			$nstroke='###';
			}
		$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 학습완료'; 
		if($status!=='complete')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204763001.png" width="15"> 풀이진행';
		if($status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600203863001.png" width="15"> 질문발송';
		if($status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 복습예약';  
		if($status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"> 답변도착</span>';
		if($status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603089657001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$wb->wbfeedback.'&originalid='.$encryption_id.'" target="_blank"> 풀이수신</a></span>';   
		if($status==='solutionask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$wb->wbfeedback.'&originalid='.$encryption_id.'" target="_blank"> 풀이질문</a></span>';   
		if($status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603089657001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$wb->wbfeedback.'&originalid='.$encryption_id.'" target="_blank"> 답변수신</a></span>';   

		$question=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id='$itemid' ");
		$contentstitle=$question->name;




 	$state=NULL;
	$questionid=$question->id;
	$questiontext=$question->questiontext;
	$generalfeedback=$question->generalfeedback;
	//Create a new DOMDocument object.
	$htmlDom = new DOMDocument; @$htmlDom->loadHTML($questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
	$nimg=0;
	foreach($imageTags as $imageTag)
		{
		$nimg++;
    		$questionimg = $imageTag->getAttribute('src');
		$questionimg = str_replace(' ', '%20', $questionimg); 
		if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
		}

	$htmlDom2 = new DOMDocument; @$htmlDom2->loadHTML($generalfeedback); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages2 = array();
	$nimg=0;
	foreach($imageTags2 as $imageTag2)
		{
		$nimg++;
    		$solutionimg = $imageTag2->getAttribute('src');
		$solutionimg = str_replace(' ', '%20', $solutionimg); 
		if(strpos($solutionimg, 'MATRIX/MATH')!= false && strpos($solutionimg, 'hintimages') == false)break;
		}

	$qcomment=$wb->comment; 
	if($wb->state==='gradedwrong')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882870001.png" width=30>';
	if($wb->state==='gradedpartial')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882934001.png" width=30>';
	if($wb->state==='gaveup')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882809001.png" width=30>';
 
	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND contentsid='$questionid' AND contentstype='2'   ORDER BY id DESC LIMIT 1 ");
	$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
	if($handwriting->teacher_check==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
	if($handwriting->teacher_check==2)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
	$encryption_id=$handwriting->wboardid;
	$nstroke=(int)($handwriting->nstroke/2);
	$ave_stroke=round($nstroke/(($handwriting->tlast-$handwriting->tfirst)/60),1);

	$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 학습완료'; 

	if($handwriting->contentstitle==='realtime')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605616024001.png" width="15"> 시도완료'; 
	if($handwriting->status!=='complete')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204763001.png" width="15"> 노트작성';
	if($handwriting->status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603251593001.png" width="15"><span style="color: rgb(233, 33, 33);"> 질문발송</span>';
	if($handwriting->status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 복습예약';  
	if($handwriting->status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$encryption_id.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> 답변수신</a></span>';  
	if($handwriting->status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186545001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$wb->wbfeedback.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이수신</u></a></span>';   
	if($handwriting->status==='solutionask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$wb->wbfeedback.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이질문</u></a></span>';   
	if($handwriting->status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186950001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$wb->wbfeedback.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이답변</u></a></span>';   
	$wboardlist= $imgstatus.'&nbsp;&nbsp;'.$contentslink.' &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank">'.date("Y년 m월d일 |H:i",$timemodified).' &nbsp;&nbsp;총'.$nstroke.'획 &nbsp; '.$ave_stroke.'획/분 '.$fixhistory;
 
 
	$mathcompetency=NULL;
	if($wb->comment!=NULL)
		{
		$mathcompetency=str_replace('.php/','.php?',$wb->comment);
		$pattern = '@(http(s)?://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
		$mathcompetency = preg_replace($pattern, '<a href="http$2://$3" target="_blank"><img src=http://mathking.kr/Contents/IMAGES/external-link.png width=15></a>', $mathcompetency);	
		}
		 
	// 평가정보 가져오기
	$assess= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitiveassessment WHERE wboardid='$encryption_id'  ORDER BY id  DESC  LIMIT 1"); // 과목정보 가져오기
 
	if($assess->graded==1)  //<hr style="border: double 3px red;">
 		{
		$text_assess='  # 서술형 평가결과 : OO 점<hr align="center" style="border: solid 2px red; ">';
		if(isset($assess->step1))$text_assess.='감점요인 : '.$assess->step1.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step2))$text_assess.='감점요인 : '.$assess->step2.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step3))$text_assess.='감점요인 : '.$assess->step3.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step4))$text_assess.='감점요인 : '.$assess->step4.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step5))$text_assess.='감점요인 : '.$assess->step5.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step6))$text_assess.='감점요인 : '.$assess->step6.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step7))$text_assess.='감점요인 : '.$assess->step7.'<hr align="center" style="border: solid 1px red; ">';
		}

	$feedback= $DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE  userid='$studentid' AND contentsid='$questionid' AND contentstype='2' ORDER BY id DESC LIMIT 1"); // 퀴즈 대화정보

	$color1='#F91408';$color2='#F91408';$color3='#F91408';$color4='#F91408';$color5='#F91408';$color6='#F91408';$color7='#F91408';$color8='#F91408';$color9='#F91408';$color10='#F91408';

	if($feedback->feedback2!==NULL)$color1='#0572f7';if($feedback->feedback3!==NULL)$color2='#0572f7';if($feedback->feedback4!==NULL)$color3='#0572f7';if($feedback->feedback5!==NULL)$color4='#0572f7';
	if($feedback->feedback6!==NULL)$color5='#0572f7';if($feedback->feedback7!==NULL)$color6='#0572f7';if($feedback->feedback8!==NULL)$color7='#0572f7';if($feedback->feedback9!==NULL)$color8='#0572f7';if($feedback->feedback10!==NULL)$color9='#0572f7'; 

	$dialogue='<table align=left>
	<tr><td><h4><span style="color:'.$color1.'">'.$feedback->feedback.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color2.'">'.$feedback->feedback2.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color3.'">'.$feedback->feedback3.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color4.'">'.$feedback->feedback4.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color5.'">'.$feedback->feedback5.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color6.'">'.$feedback->feedback6.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color7.'">'.$feedback->feedback7.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color8.'">'.$feedback->feedback8.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color9.'">'.$feedback->feedback9.' </span></h4></td></tr>
	<tr><td><h4><span style="color:'.$color10.'">'.$feedback->feedback10.' </span></h4></td></tr>
	<tr><td><h4><span style="color:black">'.$text_assess.' </span></h4></td></tr>
	</table>';

	$level=$DB->get_record_sql("SELECT tagid FROM mdl_tag_instance  WHERE itemid='$itemid' AND tagid >136 AND tagid<142 ORDER BY id DESC LIMIT 1");  //137 ~ 141 (기초 ~ 고난도) & checkbox 클릭시 이미지 숨김모드, 원클릭 모두 숨김 (tooltip 보기) 및 숨김 해제
 	$leveltag=$level->tagid;

 	if($leveltag==137)$marks1.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr><tr><td align=center></td><td align=center>
               '.$reason.'  | '.$wboardlist.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >&nbsp;&nbsp;<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=20></a></td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
 	if($leveltag==138)$marks2.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr><tr><td align=center></td><td align=center>
               '.$reason.'  | '.$wboardlist.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >&nbsp;&nbsp;<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=20></a></td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
 	if($leveltag==139)$marks3.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr><tr><td align=center></td><td align=center>
               '.$reason.'  | '.$wboardlist.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >&nbsp;&nbsp;<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=20></a></td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
 	if($leveltag==140)$marks4.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr><tr><td align=center></td><td align=center>
               '.$reason.'  | '.$wboardlist.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >&nbsp;&nbsp;<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=20></a></td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
 	if($leveltag==141)$marks5.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr><tr><td align=center></td><td align=center>
               '.$reason.'  | '.$wboardlist.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >&nbsp;&nbsp;<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=20></a></td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
 
		}
	}

 
echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$thisusername.'<br><p align=left>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;최근 '.$period.'일 동안 서술평가 응시문항 총 '.$nattempts.'문제</p><table width=100% align=center>
<tr><th style="background-color:green;color:white;" width=35%> </th><th style="background-color:green;color:white;" width=35%>기초</th> <th style="background-color:green;color:white;" width=30%></th></tr>'.$marks1.'<tr><td style="background-color:green;color:white;"></td><td style="background-color:green;color:white;" align=center>기본</td> <td style="background-color:green;color:white;"></td></tr>'.$marks2.'<tr><td style="background-color:green;color:white;"></td><td style="background-color:green;color:white;" align=center>중급</td><td style="background-color:green;color:white;"></td></tr>'.$marks3.'<tr><td style="background-color:green;color:white;"></td><td style="background-color:green;color:white;" align=center>심화</td> <td style="background-color:green;color:white;"></td></tr>'.$marks4.'<tr><td style="background-color:green;color:white;"> </td><td style="background-color:green;color:white;" align=center>고난도</td> <td style="background-color:green;color:white;" > </td></tr>'.$marks5.'</table>
<br><br><table width=95% align="center"><tr><th width="33%">학습루브릭</th><th width="3%"></th><th width="28%">개선요청</th><th width="3%"></th><th width="28%">퀴즈 및 오답노트 (CogTalk)</th></tr>
<tr><td valign="top"><hr> </td><td ></td><td valign="top"> <hr> </td><td ></td><td><hr> </td></tr>		   
<tr><td valign="top"><table>'.$feedbacklog1.'</table></td><td ></td><td valign="top"><table>'.$feedbacklog2.'</table></td><td ></td><td valign="top"><table>'.$feedbacklog3.'</table></td></tr></table>'; 

 
echo '   
<style> 
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
  right:10%;
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
 
a:hover { color: green; text-decoration: underline;}

  
</style>  
  <script>
	function send_button(Eventid,Wboardid,Userid,Tutorid,Contentsid)
	{
		swal("오답노트 검토 전달", "복습을 위하여 오답노트 검토활동이 전달되었습니다.", {
			buttons: {        			
				confirm: {
					className : \'btn btn-success\'
				}
			},
		});
		$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"wboardid":Wboardid,	
		"userid":Userid,
		"tutorid":Tutorid,
		"contentsid":Contentsid,
		},
		success:function(data){
		 }
		 })
	setTimeout(function(){
	location.reload();
	},1000);  
	}
 

</script>
 
';

?>