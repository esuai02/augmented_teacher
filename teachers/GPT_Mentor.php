 <?php 
 /////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$timecreated=time();
$hoursago=$timecreated-3600*12;
$teacherid = $_GET["id"];
$mode = $_GET["mode"];

$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1=$teacher1->symbol;
$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2=$teacher2->symbol;
$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3=$teacher3->symbol;  
 
$nenergy_class=$collegues->nenergy;
if($tsymbol==NULL)$tsymbol='KTM';
if($tsymbol1==NULL)$tsymbol1='KTM';
if($tsymbol2==NULL)$tsymbol2='KTM';
if($tsymbol3==NULL)$tsymbol3='KTM';

echo '<meta http-equiv="refresh" content="30">';

//echo '<meta http-equiv="refresh" content="180"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/papertest.php?id='.$teacherid.'&mode=student">학생모드</a>';

$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  student_check=1 AND timemodified > '$hoursago' ORDER BY timemodified ");

$result = json_decode(json_encode($handwriting), True);
unset($value);
 
foreach($result as $value) 
	{
	$userid=$value['userid'];
	$suspended= $DB->get_record_sql("SELECT suspended FROM mdl_user WHERE id LIKE'$userid'"); 
	if($suspended->suspended==1)continue;

	$contentsid=$value['contentsid'];
	$url=$value['url'];
	$wboardid=$value['wboardid'];
	$status=$value['status'];
	$contentstitle=$value['contentstitle'];
	$instruction=$value['instruction'];
	$tpassed=round(($timecreated-$value['timemodified'])/60,0);
	if($tpassed>20)$tpassed='<b style="font-size:20px;color:red;">'.$tpassed.'분</b>';
	elseif($tpassed>10)$tpassed='<b style="color:blue;">'.$tpassed.'분</b>';
	else $tpassed='<b style="color:green;">'.$tpassed.'분</b>';
	$cnttext='';
	 
	if($instruction!=NULL)$instruction=' | <span style="color:black;">'.$instruction.'</span>';
	else $instruction='';

	$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$stdname=$thisuser->firstname.$thisuser->lastname;
    if(strpos($thisuser->firstname,$tsymbol) !== false || strpos($thisuser->firstname,$tsymbol1) !== false || strpos($thisuser->firstname,$tsymbol2) !== false || strpos($thisuser->firstname,$tsymbol3) !== false)
		{
		if(strpos($wboardid, 'jnrsorksqcrark')!== false)
				{
				$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
				$ctext=$getimg->pageicontent;
				$htmlDom = new DOMDocument;
				if($studentid==NULL)$studentid=2;
					
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
		   else
				{
				$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
				$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
				foreach($imageTags as $imageTag)
					{
					$imgSrc = $imageTag->getAttribute('src');
					$imgSrc = str_replace(' ', '%20', $imgSrc); 
					if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
					} 
				} 
		$checkout='';
		if($mode!=='student')$checkout='<input type="checkbox" name="checkAccount"  Checked  onClick="ChangeCheckBox(213,\''.$userid.'\',\''.$wboardid.'\', this.checked)"/>';
		
		if($status==='commit')
			{
			$cnttext=' | <span style="color:black;">'.$contentstitle.'</span>';
			$todoinfo='독립센션 실행 후 점검받기1 | '.$tpassed;
			}
		elseif($status==='commitquiz')
			{
			$cnttext=' | <span style="color:black;">'.$contentstitle.'</span>';
			if($url==NULL)
				{
				$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$contentsid'  ");
				$quiz=$DB->get_record_sql("SELECT * FROM mdl_quiz where id='$moduleid->instance'  ");
				$todoinfo='<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$moduleid->instance.'"target="_blank">독립센션 실행 후 점검받기2</a> | '.$tpassed;
				}
			else $todoinfo='<a href="https://mathking.kr/moodle/mod/quiz/review.php?'.$url.'"target="_blank">독립센션 실행 후 점검받기3</a> | '.$tpassed;
			}
		else $todoinfo='<div class="tooltip3"><a style="font-size:16px; text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$userid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img src=https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png width=20> 인쇄하기</a> <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div> | <div class="tooltip3"><a style="font-size:16px; text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img src=https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png width=20> 발표하기</a> <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div> | '.$tpassed;
		 
		if(strpos($wboardid, 'jnrsorksqcrark')!== false)$papertest1.='<tr><td width=1%>'.$checkout.'</td><td width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><a style="text-decoration: none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?userid='.$userid.'&tb=604800"target="_blank">'.$stdname.'</a></td><td width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$todoinfo.'</td><td>&nbsp; &nbsp;<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width=20 onclick="Instruction(\''.$wboardid.'\');"> &nbsp; <span style="font-size:16px;">   '.$instruction.'   '.$cnttext.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		else $papertest2.='<tr><td width=1%>'.$checkout.'</td><td width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><a style="text-decoration: none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?userid='.$userid.'&tb=604800"target="_blank">'.$stdname.'</a></td><td width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$todoinfo.'</td><td>&nbsp;&nbsp; <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width=20 onclick="Instruction(\''.$wboardid.'\');"> &nbsp; <span style="font-size:16px;">   '.$instruction.'  '.$cnttext.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		}
	else continue;
	}
echo '<br><table align=center><tr><td>풀이를 작성한 다음 선생님에게 제출해 주세요.</td><td>  (질문은 귀가검사 10분 전까지 가능합니다.)</td><td><hr></td></tr><tr><td><hr></td><td><hr></td><td><hr></td></tr></table><table align=center width=90%>'.$papertest1.'<tr><td><br></td><td><br></td><td><br></td><td><br></td></tr>'.$papertest2.'</table>';

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

<script>

function Instruction(Wboardid){
	swal({
		title: \'메세지 입력\',
		width: 1200,
		html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
		content: {
			element: "input",
			attributes: {
				placeholder: "지시사항을 입력해 주세요",
				type: "text",
				id: "input-field",
				className: "form-control",
				style: "width: 100%;height: 5%;"  
			},
		},
		buttons: {		
			confirm: {
				className : \'btn btn-success\'
			}
		},
	}).then(
	function() {
		var Inputtext=$(\'#input-field\').val();							
		swal("", "입력된 내용 : " + Inputtext, {buttons: false,timer: 2000});
		$.ajax({
		url:"../students/check.php",
		type: "POST",
		dataType:"json",
		 data : {
		"eventid":\'215\',	
		"inputtext":Inputtext,
		"wboardid":Wboardid,
		},
		success:function(data){
			var wbid=data.wbid;
			location.reload();
		 }
		 })
	});
	
};	 

function ChangeCheckBox(Eventid,Userid, Wboardid, Checkvalue){
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
	}
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
}</script>
';
?>