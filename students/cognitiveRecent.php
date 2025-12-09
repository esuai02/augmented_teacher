<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid = $_GET["userid"];
$tlength=$_GET["tb"];
$timestart=time()-$tlength; 

echo '
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
';

$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND wboardid LIKE '%nx4HQkXq%'  AND timemodified>'$timestart'  ORDER BY tlaststroke DESC LIMIT 200 ");
 
$nnote=0;
$nreview=0;
$ncomplete=0;
$nask=0;
$ntotal=$nright+$nwrong+$ngaveup;
$result1 = json_decode(json_encode($handwriting), True);
unset($value);
$wboardlist.= '<tr><td><hr></d><td><hr></d><td><hr></d><td><hr></d><td><hr></d><td><hr></d></tr>';
foreach($result1 as $value) 
{
$nnote++;
if($value['status']==='review')$nreview++;
if($value['status']==='complete')$ncomplete++;
if($value['status']==='begin')$nask++;
$Q_id=$value['contentsid'];
if($encryption_id!==$value['wboardid'])
{

$resultValue=$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1623817278001.png" height=15>';
if($value['star']==1)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15>';
if($value['star']==2)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15>';
if($value['star']==3)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15>';
if($value['star']==4)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15>';
if($value['star']==5)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15>';

$encryption_id=$value['wboardid'];
$nstroke=(int)($value['nstroke']/2);
$ave_stroke=round($nstroke/(($value['tlast']-$value['tfirst'])/60),1);
$contentstype=$value['contentstype'];
$nstep=$value['nstep'];
$status=$value['status'];
$contentstitle=$value['contentstitle'];
$contentsid=$value['contentsid'];
$cmid=$value['cmid']; 
$checkstatus='';
$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
if($value['teacher_check']==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
elseif($value['teacher_check']==2 && $value['nstep']==0)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
elseif($value['teacher_check']==2 && $value['nstep']>0)$fixhistory='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1620732184001.png" width=15>'; 
if($value['student_check']==1)$checkstatus='checked'; 
 
if($value['contentstype']==2)
	{
	$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
	$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
	foreach($imageTags as $imageTag)
		{
    		$questionimg = $imageTag->getAttribute('src');
		$questionimg = str_replace(' ', '%20', $questionimg); 
		if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
		}
	$questiontext='<img src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
	$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';
	}
else
	{
	$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
	$ctext=$getimg->pageicontent;
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($ctext);
	$imageTags = $htmlDom->getElementsByTagName('img');
	$extractedImages = array();
	$nimg=0;
	foreach($imageTags as $imageTag)
		{
		$nimg++;
	    	$imgSrc = $imageTag->getAttribute('src');
		$imgSrc = str_replace(' ', '%20', $imgSrc); 
		if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
		}

	$questiontext='<img src="'.$imgSrc.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 	$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$userid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
	}
if($nstroke<3)
	{
	$ave_stroke='###';
	$nstroke='###';
	}
 
include("../whiteboard/status_icons.php");
if(($contentstitle==='incorrect' || $nstep!=0) && $status!=='attempt' )$wboardlist.= '<tr><td></td><td><span  onClick="showWboard(\''.$encryption_id.'\')"><div class="tooltip3"> '.$imgstatus.'&nbsp;  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span> </div></span></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(120,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">복습</button></td><td><button   type="button"  id="alert_demo_review" style="background-color:green;color:white;width:60px;"  onclick="send_button(110,\''.$encryption_id.'\',\''.$studentid.'\',\''.$teacherid.'\',\''.$contentsid.'\')">발표</button></td><td> &nbsp;'.date("m월 d일 | H:i",$value['tlaststroke']).' </td><td>'.$resultValue.'</td></tr> ';
}
}
  
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;활동을 출제해 주세요 (오늘활동 내역) <table style="white-space: nowrap; text-overflow: ellipsis;" >'.$wboardlist.'</table><br> <br> <br> <br> <br> <br> <br><br> <br> <br> <br> <br> <br> <br> ';
  
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
  width: 620px;
 
  background-color: #ffffff;
  color: #e1e2e6;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  border-style: solid;
  border-color: #0aa1bf;
  padding: 20px 1;

  /* Position the tooltip */
  top:20px;
  left:190px;
  position: absolute;
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
	function showWboard(Wbid)
		{
		Swal.fire({
		backdrop: false,position:"top-right",showCloseButton: true,width:400,
		  html:
		    \'<iframe style="border: 1px none; z-index:2; width:400; height:600;  margin-left: -100px; margin-top: -130px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_review.php?id=\'+Wbid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		}	

	function send_button(Eventid,Wboardid,Userid,Tutorid,Contentsid)
	{
		swal("활동이 전달되었습니다.", {buttons: false, timer: 2000, });
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