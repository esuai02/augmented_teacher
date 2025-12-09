<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$tagid=required_param('tagid', PARAM_TEXT);  
$studentid=$_GET["userid"];   
echo '
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
';
 
$id = $_GET["id"];
$teacherid = $_GET["teacherid"];
$contentsid = $_GET["contentsid"];
 

$tag=$DB->get_record_sql("SELECT * FROM  mdl_tag  WHERE id='$tagid' ");
$tagname=$tag->name;
$tag_instance=$DB->get_records_sql("SELECT * FROM mdl_tag_instance  WHERE tagid='$tagid'  ");  //137 ~ 141 (기초 ~ 고난도) & checkbox 클릭시 이미지 숨김모드, 원클릭 모두 숨김 (tooltip 보기) 및 숨김 해제

$questions= json_decode(json_encode($tag_instance), True);
//$question_array='<hr><table align= center><tr><th></th><th></th><th></th><th></th></tr></table>';
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
 		include("../whiteboard/status_icons.php");
		$question=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id='$itemid' ");
		$contentstitle=$question->name;
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($question->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++; $questionimg = $imageTag->getAttribute('src'); 
			$questionimg =str_replace(' ', '%20', $questionimg);
			$questionimg='<img src="'.$questionimg.'" width=500>';
			if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
			}

		$htmlDom2 = new DOMDocument; @$htmlDom2->loadHTML($question->generalfeedback); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages2 = array();
		$nimg=0;
		foreach($imageTags2 as $imageTag2)
			{
			$nimg++; $solutionimg = $imageTag2->getAttribute('src');
			$solutionimg =str_replace(' ', '%20', $solutionimg);
			$solutionimg ='<img src="'.$solutionimg.'" width=500>';
			if(strpos($solutionimg, 'MATRIX/MATH')!= false && strpos($solutionimg, 'hintimages') == false)break;
			}
		$level=$DB->get_record_sql("SELECT tagid FROM mdl_tag_instance  WHERE itemid='$itemid' AND tagid >136 AND tagid<142 ORDER BY id DESC LIMIT 1");  //137 ~ 141 (기초 ~ 고난도) & checkbox 클릭시 이미지 숨김모드, 원클릭 모두 숨김 (tooltip 보기) 및 숨김 해제
 		$leveltag=$level->tagid;
		if($leveltag==137)$question_array1.= '<tr><td></td><td>'.$imgstatus.'&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> 화이트보드 &nbsp;'.date("Y년 m월 d일 | H:i",$timemodified).'   <span class="tooltiptext3"><table align=center><tr><td>'.$questionimg.'<hr>'.$solutionimg.'</td></tr></table></span> </a></div></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(120,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">복습</button></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:100px;"  onclick="send_button(110,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">발표요청</button></td><td>&nbsp;&nbsp;&nbsp;&nbsp;총'.$nstroke.'획 &nbsp;&nbsp; '.$ave_stroke.'획/분</td></tr> '; 
		if($leveltag==138)$question_array2.= '<tr><td></td><td>'.$imgstatus.'&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> 화이트보드 &nbsp;'.date("Y년 m월 d일 | H:i",$timemodified).'   <span class="tooltiptext3"><table align=center><tr><td>'.$questionimg.'<hr>'.$solutionimg.'</td></tr></table></span> </a></div></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(120,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">복습</button></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:100px;"  onclick="send_button(110,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">발표요청</button></td><td>&nbsp;&nbsp;&nbsp;&nbsp;총'.$nstroke.'획 &nbsp;&nbsp; '.$ave_stroke.'획/분</td></tr> '; 
		if($leveltag==139)$question_array3.= '<tr><td></td><td>'.$imgstatus.'&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> 화이트보드 &nbsp;'.date("Y년 m월 d일 | H:i",$timemodified).'   <span class="tooltiptext3"><table align=center><tr><td>'.$questionimg.'<hr>'.$solutionimg.'</td></tr></table></span> </a></div></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(120,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">복습</button></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:100px;"  onclick="send_button(110,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">발표요청</button></td><td>&nbsp;&nbsp;&nbsp;&nbsp;총'.$nstroke.'획 &nbsp;&nbsp; '.$ave_stroke.'획/분</td></tr> '; 
		if($leveltag==140)$question_array4.= '<tr><td></td><td>'.$imgstatus.'&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> 화이트보드 &nbsp;'.date("Y년 m월 d일 | H:i",$timemodified).'   <span class="tooltiptext3"><table align=center><tr><td>'.$questionimg.'<hr>'.$solutionimg.'</td></tr></table></span> </a></div></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(120,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">복습</button></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:100px;"  onclick="send_button(110,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">발표요청</button></td><td>&nbsp;&nbsp;&nbsp;&nbsp;총'.$nstroke.'획 &nbsp;&nbsp; '.$ave_stroke.'획/분</td></tr> '; 
		if($leveltag==141)$question_array5.= '<tr><td></td><td>'.$imgstatus.'&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> 화이트보드 &nbsp;'.date("Y년 m월 d일 | H:i",$timemodified).'   <span class="tooltiptext3"><table align=center><tr><td>'.$questionimg.'<hr>'.$solutionimg.'</td></tr></table></span> </a></div></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(120,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">복습</button></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:100px;"  onclick="send_button(110,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">발표요청</button></td><td>&nbsp;&nbsp;&nbsp;&nbsp;총'.$nstroke.'획 &nbsp;&nbsp; '.$ave_stroke.'획/분</td></tr> '; 

		}
	}


echo  $tagname.'<hr><table><tr><td><tr><td>기초</td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$question_array1.'<tr><td>기본</td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$question_array2.'<tr><td>중급</td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$question_array3.'<tr><td>심화</td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$question_array4.'<tr><td>고난도</td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$question_array5.'</td></tr></table>';
 
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
		url:"../whiteboard/database.php",
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