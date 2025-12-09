 

<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;
 
$studentid= $_GET["userid"]; 
$tbegin= $_GET["tbegin"]; 
$timecreated=time(); 
$hoursago=$timecreated-14400;

if($tbegin==NULL)$amonthago=$timecreated-604800*4;
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role; 
  
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND feedback > 0 AND timemodified > '$amonthago' ORDER BY tlaststroke DESC ");
$result2 = json_decode(json_encode($handwriting), True);
unset($value);

$papertest='<div style="display: flex; flex-wrap: wrap;">';
foreach(array_reverse($result2) as $value) 
	{
	$wboardid=$value['wboardid'];
	$contentsid=$value['contentsid'];
    $contentstype=$value['contentstype'];
	$contentstitle=$value['contentstitle'];
    $tlastupdate=round(($timecreated-$value['timemodified'])/86400,0).'일';
	$instruction=$value['instruction'];
	$nstroke=$value['nstroke'];
    $ncommit=$value['feedback'];
	if($ncommit!=0)$ncommit='<b style="color:#FF0000;">'.$ncommit.'</b>';
	$timeused_tmp=$value['usedtime'];
	$usedtime=round($timeused_tmp/60,1).'분';
	$tinterval=round(($tprev-$value['timemodified'])/60,0).'분';
	$tprev=$value['timemodified'];
	$status=$value['status'];

    //echo 'contentsid='.$contentsid;
	if($tinterval<0)$tinterval=round(($timecreated-$value['timemodified'])/60,0).'분';

	$timestamp=$timecreated-$value['timemodified'];
	
	if($timestamp<=60)$timestamp=$timestamp.'초 전';
	elseif($timestamp<=3600)$timestamp=round($timestamp/60,0).'분 전';
	elseif($timestamp<=86400)$timestamp=round($timestamp/3600,0).'시간 전';
	elseif($timestamp<=2592000)$timestamp=round($timestamp/86400,0).'일 전';

	$checkout='';
	if($value['student_check']==1)$checkstatus='Checked';
	else $checkstatus='';

	$checkout='<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
  
    $totaltime=$totaltime+$timeused_tmp;
    if(strpos($wboardid, 'jnrsorksqcrark')!== false)
        { 
        $getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
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
            if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
            }
        }
    elseif($contentstype==2)
        { 
        $qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid' ");
        $htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
        foreach($imageTags as $imageTag)
            {
            $imgSrc = $imageTag->getAttribute('src');
            $imgSrc = str_replace(' ', '%20', $imgSrc); 
            if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
            } 
        }
    if($value['student_check']==1)$papertest.='<table style="display: inline-table; background-color:#EBDEDE; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 10px;" align=center><tr><td valign=top><hr style="border: none; border-top: 2px solid; height: 5px;">'.$checkout.'<b>오답노트</b> | '.$usedtime.' | '.$ncommit.'번| <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$nstroke.'획</a> ('.$tlastupdate.') <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1"target="_blank"><img  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer2.png" width=20></a><hr><img src="'.$imgSrc.'" width="400"><br></td></tr></table>'; 
    else $papertest.='<table style="display: inline-table; background-color:#FFFFFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 10px;" align=center><tr><td valign=top><hr style="border: none; border-top: 2px solid; height: 5px;">'.$checkout.'<b>오답노트</b> | '.$usedtime.' | '.$ncommit.'번| <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$nstroke.'획</a>  ('.$tlastupdate.') <hr><img src="'.$imgSrc.'" width="400"><br></td></tr></table>'; 
		 
	}
    $papertest.='</div>';

	echo ' <script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
	  
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
	<div class="table-wrapper">
	<table align=center><tr><td valign=top><table width=100%><thead><tr><th width=30%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"><img style="margin-top:5px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40></a> <span style="font-size:20px;margin-bottom:10px;"> 커밋노트 </span></th><th><a style="text-decoration:none;color:#1956FF;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$stdname.'</a>의  기록 (기초 | 기본 | 중급 | 심화 | 고난도)'.$notetitle.'   </th></tr></thead></table><table><tr><td><hr></td><td><hr></td><td><hr></td></tr></table>'.$papertest.'</td></tr></table></div>';
	  

echo '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

	<script> 

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
		background-color: #FFE4C1; /* 첫 번째 행의 배경색을 지정하세요 */
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
