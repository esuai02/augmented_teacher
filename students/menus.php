<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$mtype=$_GET["mtype"];
$studentid = $_GET["studentid"];
$stepnum = $_GET["nstep"]; 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
echo '
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
';

if($mtype==1) // 활동목차 (개념노트)
	{
	$pageid = $_GET["pageid"];
	$moduleid = $_GET["moduleid"];   
	$icntid = $_GET["cntid"];
	$icontent=$DB->get_record_sql("SELECT * FROM mdl_icontent where id='$icntid' ORDER BY id DESC LIMIT 1    ");  // icontent 모듈 기본 정보 가지고 오기
	$cognitive=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivesteps where contentsid='$pageid' AND contentstype='1'  ORDER BY id DESC LIMIT 1   ");  // 서술평가 문항 가지고 오기
	$quiztitle='';
	if($quizid!=NULL)$quiztitle='<br><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621944443001.png" width=20> 연습문제';
	if($cognitive->step1!=NULL){$menuimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624807004001.png';$menucolor='#4a86e8';}
	$textbookid2='pageid'.$pageid.'jnrsorksqcrark_user'.$studentid; 
	$teachernoteId='pageid'.$pageid.'jnrsorksqcrark';

	$tutorExpl='';
	if($thisboard->aion==1 ||  (strpos($id, '_user')!==false && $role!=='student')  ) $tutorExpl='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1616029078001.png" width=60>'; 

	for($nstep=1;$nstep<=7;$nstep++)  // 서술질문 나열하기
		{ 
		$stepid='step'.$nstep;
		$steptext=$cognitive->$stepid; 
		if($steptext==NULL)break;	
		$cnttextbookid=$textbookid2.'_step'.$nstep;
		$keypattern=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid='$cnttextbookid' ORDER BY id DESC LIMIT 1    ");
		$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width:0%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==1)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width:20%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==2)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width:40%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==3)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width:60%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==4)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width:80%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==5)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width:100%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
 		$thispagenum=0;
		if($nstep==$stepnum)  // 개념질문 현재 페이지 표시
			{
			 
			// 질의응답 상태에 따라 아이콘 표시
			if($role==='student')
					{
					$lectureNoteId=$teachernoteId.'_step'.$nstep;
					$lectureNote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid LIKE '$lectureNoteId' ORDER BY id DESC LIMIT 1    ");
					$noteReady=$lectureNote->star;
					if($noteReady>=3) $tutorExpl='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1616029078001.png" width=60>'; 
					$seeTutorial='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=bessi'.$teachernoteId.'_step'.$nstep.'&originalid='.$textbookid2.'_step'.$nstep.'&cntid='.$icntid.'&pageid='.$pageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'&nstep='.$nstep.'">'.$tutorExpl.'</a>';
					$questionlist.='<b><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$textbookid2.'_step'.$nstep.'&srcid='.$textbookid2.'_step'.$nstep.'&cntid='.$icntid.'&pageid='.$pageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'&nstep='.$nstep.'">질문'.$nstep.' : '.$steptext.'</a></b>'.$seeTutorial.$resultValue;
					}
			// 선생님 설명 페이지 아이콘 표시
			elseif(strpos($id, 'jnrsorksqcrark_step')!==false) // 개념질문에서 선생님 설명화면
					{
					$tutorExpl='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621665743001.png" width=60>';  // BACK (학생노트)
					$questionlist.='<b>'.$nstep.' : '.$steptext.'<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$textbookid2.'_step'.$nstep.'&srcid='.$textbookid2.'_step'.$nstep.'&cntid='.$icntid.'&pageid='.$pageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'&nstep='.$nstep.'">'.$tutorExpl.'</a></b>'.$resultValue;
					}
			elseif(strpos($id, 'jnrsorksqcrark_user')!==false)
					{
					$tutorExpl='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1616029078001.png" width=60>';  // BACK (학생노트)
					$questionlist.='<b>질문'.$nstep.' : '.$steptext.'<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$teachernoteId.'_step'.$nstep.'&srcid='.$teachernoteId.'_step'.$nstep.'&cntid='.$icntid.'&pageid='.$pageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'&nstep='.$nstep.'">'.$tutorExpl.'</a></b>'.$resultValue;
					}
			 
				
 
 			}
		// 현재 페이지 이외에는 학생페이지로 이동
		else  $questionlist.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$textbookid2.'_step'.$nstep.'&cntid='.$icntid.'&pageid='.$pageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'&nstep='.$nstep.'">질문'.$nstep.' : '.$steptext.'</a>'.$resultValue; 
 		}
	// 대표유형 및 개념 체크 문제
	$cntpages=$DB->get_records_sql("SELECT * FROM mdl_icontent_pages where icontentid='$icntid' ORDER BY pagenum ASC   ");  //AND  title NOT LIKE '%Approach%' 
	$result = json_decode(json_encode($cntpages), True);
 
	unset($value);
	foreach($result as $value)
		{
		if($value['pagenum']==1)$subjecttitle=$value['title'];
		$cntpageid=$value['id'];
		$title=$value['title'];
		$thiscmid=$value['cmid'];
		$thispagenum=$value['pagenum'];
		$cnttextbookid='pageid'.$cntpageid.'jnrsorksqcrark_user'.$studentid;
 		$teachernoteId='pageid'.$cntpageid.'jnrsorksqcrark';

		$keypattern=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid='$cnttextbookid' ORDER BY id DESC LIMIT 1    ");
		$resultValue='<div class="progress" style="height: 4px;margin-bottom:10px;"><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width:0%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==1)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width:20%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==2)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width:40%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==3)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width:60%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==4)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width:80%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
		if($keypattern->depth==5)$resultValue='<div class="progress" style="height: 5px;margin-bottom:10px; "><div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width:100%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="0" data-toggle="tooltip" data-placement="top" title="" data-original-title="2%"></div></div>';
	 
 		if($value['pagenum']==1) 
			{
			$tutorExpl='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1616029078001.png" width=60>'; 
			$seeTutorial='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$teachernoteId.'&srcid='.$teachernoteId.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$tutorExpl.'</a>';
			if($cntpageid0==NULL && $stepnum==NULL)$begintopic=$seeTutorial;
			else $begintopic='';
	 		}
 
		if(strpos($title, 'Approach')!== false || strpos($title, 'Check')!== false || strpos($title, '대표유형')!== false || strpos($title, 'Note')!== false)  
				{
			 
			 
				if($role==='student')
						{
						$lectureNote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid LIKE '$teachernoteId' ORDER BY id DESC LIMIT 1    ");
						$noteReady=$lectureNote->star;
						if($noteReady>=3) $tutorExpl='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1616029078001.png" width=60>'; 
						$seeTutorial='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=bessi'.$teachernoteId.'&originalid='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$tutorExpl.'</a>';
						if(strpos($title, 'Approach')!== false)$contentslist0='<b><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$title.'</a></b>'.$seeTutorial.$resultValue;
						else $contentslist.='<b><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$title.'</a></b>'.$seeTutorial.$resultValue;
						}
				elseif(strpos($id, 'jnrsorksqcrark_user')!==false)
						{
						$seeTutorial='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$teachernoteId.'&srcid='.$teachernoteId.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$tutorExpl.'</a>';
						if( strpos($title, 'Approach')!== false)$contentslist0='<b><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$teachernoteId.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$title.'</a></b>'.$seeTutorial.$resultValue;
						else $contentslist.='<b><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$teachernoteId.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$title.'</a></b>'.$seeTutorial.$resultValue;
						}
				else 
						{
						$tutorExpl='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621665743001.png" width=60>';  // BACK (학생노트)
						if(strpos($title, 'Approach')!== false)$contentslist0='<b>&nbsp;&nbsp;'.$title.'<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$cnttextbookid.'&srcid='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$tutorExpl.'</a></b>'.$resultValue; 
						else $contentslist.='<b>&nbsp;&nbsp;'.$title.'<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$cnttextbookid.'&srcid='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$tutorExpl.'</a></b>'.$resultValue; 
						}
					  
 				}
 				 
			else 
				{
				if(strpos($title, 'Approach')!== false) $contentslist0='<br><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$title.'</a></span>'.$resultValue;
				else $contentslist.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$cnttextbookid.'&cntid='.$icntid.'&pageid='.$pageid.'&cntpageid='.$cntpageid.'&cmid='.$thiscmid.'&pagenum='.$thispagenum.'&moduleid='.$moduleid.'&studentid='.$studentid.'&quizid='.$quizid.'">'.$title.'</a></span>'.$resultValue;
				}
			 
		}


	echo '<table align=center ><tr><td>목차</td></tr><tr><td>'.$begintopic.$contentslist0.$questionlist.'<hr>'.$contentslist.'</td></tr></table>   ';
	}
if($mtype==2) //  
	{

	}
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