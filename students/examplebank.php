<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;
 
$studentid= $_GET["userid"];
$cntid= $_GET["cntid"];
$viewtype= $_GET["viewtype"];
$notetitle= $_GET["title"];
$timecreated=time(); 
$hoursago=$timecreated-14400;

$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role; 
  
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
  
 
$cntpages=$DB->get_records_sql("SELECT * FROM mdl_icontent_pages where cmid='$cntid' ORDER BY pagenum ASC   ");   
$result = json_decode(json_encode($cntpages), True);
$papertest='<div style="display: flex; flex-wrap: wrap;">';
unset($value);
foreach($result as $value)
	{
	$title=$value['title']; 
	$npage=$value['pagenum']; 
	$contentsid=$value['id'];  
	$wboardid='jnrsorksqcrark'.$contentsid.'_user'.$studentid;
	$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1");
	if($note->id==NULL)$DB->execute("INSERT INTO {abessi_messages} (wboardid,userid,userto,userrole,status,active,contentstitle,contentstype,contentsid,timemodified,timecreated) VALUES('$wboardid','$studentid','2','student','userexamplenote','0','$title','1','$contentsid','$timecreated','$timecreated')");
	$noteurl=$note->url;
	if($npage==1)$examnoteurl=$note->url;
	$timeused_tmp=$note->idusedtime;
	$usedtime=round($timeused_tmp/60,1).'분';
	$ncommit=$note->feedback;
	if($ncommit!=0)$ncommit='<b style="color:#FF0000;">'.$ncommit.'</b>';
	$checkout='';
	if($note->student_check==1)$checkstatus='Checked';
	else $checkstatus='';

	$checkout='<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
 
	$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
	$ctext=$getimg->pageicontent;
	if($getimg->reflections!=NULL)$reflections=$getimg->reflections.'<hr>';
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
	if($viewtype==='papertest')
		{
		$imgSrc=str_replace('MathNote','MathNote_exam',$imgSrc);
		$easy_checked = ($note->status === 'complete') ? 'checked' : '';
		$itemClass = 'papertest-item'.($easy_checked ? ' easy-checked' : '');
		$papertest.='<div class="'.$itemClass.'"><table style=" width:100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 auto;" align=center><tr><td valign=top><hr style="border: none; border-top: 2px solid; height: 5px;"><div class="ease-check"><label><input type="checkbox" '.$easy_checked.' data-wid="'.$wboardid.'" onclick="markEasy(this, \''.$wboardid.'\')"> 쉬움</label></div>   <img src="'.$imgSrc.'" style="width:100%; height:auto;"><br></td></tr></table></div>'; 
		}
	else
		{
		if($note->student_check==1)$papertest.='<table style="display: inline-table; background-color:#EBDEDE; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 10px;" align=center><tr><td valign=top><hr style="border: none; border-top: 2px solid; height: 5px;">'.$checkout.'<b> 페이지 '.$value['pagenum'].'</b> | '.$usedtime.' | '.$ncommit.'번| <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_test.php?'.$noteurl.'"target="_blank">'.$note->nstroke.'획</a><a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/patternbank.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&mode=questiononly"target="_blank">【 】</a><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&mode=questiononly"target="_blank"><img style="margin-top:3px;"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer2.png" width=20></a> <hr><img src="'.$imgSrc.'" width="400"><br></td></tr></table>'; 
		else $papertest.='<table style="display: inline-table; background-color:#FFFFFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 10px;" align=center><tr><td valign=top><hr style="border: none; border-top: 2px solid; height: 5px;">'.$checkout.'<b> 페이지 '.$value['pagenum'].'</b> | '.$usedtime.' | '.$ncommit.'번| <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_test.php?'.$noteurl.'"target="_blank">'.$note->nstroke.'획</a><a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/patternbank.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&mode=questiononly"target="_blank">【 】</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=1&mode=questiononly"target="_blank"><img style="margin-top:3px;"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer2.png" width=20></a>  <hr><img src="'.$imgSrc.'" width="400"><br></td></tr></table>'; 
		}

	}
 
	$papertest.='</div>';
    // ---------------- 페이지 공통 스크립트 ----------------
    $commonScripts=' <script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
 	  
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
 	</script>';

    // ---------------- 헤더 영역 (papertest 에서는 제거) ----------------
    $headerHtml='<table align=center><tr><td valign=top><div class="table-wrapper"><table width=100%><thead><tr><th width=30%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"><img style="margin-top:5px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40></a> <span style="font-size:20px;margin-bottom:10px;"> 유사문제 은행 </span></th><th><a style="text-decoration:none;color:#1956FF;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/examplebank.php?userid='.$studentid.'&cntid='.$cntid.'&title=%EC%A7%80%EB%A9%B4%ED%8F%89%EA%B0%80&viewtype=papertest"target="_blank">시험지 출력</a></th><th><a style="text-decoration:none;color:#1956FF;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_test.php?'.$examnoteurl.'"target="_blank">온라인 시험</a></th><th><a style="text-decoration:none;color:#1956FF;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$stdname.'</a> '.$notetitle.'   </th></tr></thead></table><table><tr><td><hr></td><td><hr></td><td><hr></td></tr></table>'.$papertest.'</td></tr></table></div>';

    // papertest 모드인 경우: 표지 + 본문, 그 외 모드: 기존 헤더 + 본문
    if($viewtype==='papertest'){
        $coverPage='<div class="cover-page"><div class="cover-title">유사문제 평가</div><button class="clear-btn" onclick="clearEasy()">전체 체크 해제</button><div class="cover-footer">'.$stdname.' | KAIST TOUCH MATH</div></div>';
        $bodyHtml = $coverPage . $papertest;
    }else{
        $bodyHtml = $headerHtml;
    }

    echo $commonScripts.$bodyHtml;

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
		
		swal("적용되었습니다.", {buttons: false,timer: 100});
	 
		setTimeout(function(){
	 
		location.href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid="+Userid+"&wboardid="+Wboardid;
 		}, 100);
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

  /* ---------- papertest printable layout ---------- */
  .papertest-item {
    width: 50%;               /* 정확히 두 칼럼 */
    float: left;              /* flex 대신 float 로 인쇄 안정성 확보 */
    margin: 0;                /* 여백 제거하여 상·하 50% 정렬 */
    box-sizing: border-box;
    height: 48vh;             /* 한 행이 용지 높이의 50% 차지 */
    overflow: hidden;         /* 내용 넘침 방지 */
    page-break-inside: avoid; /* 행 분리 방지 */
    break-inside: avoid;
  }

  .papertest-item img {
    width: 100%;
    height: 100%;             /* 행 높이에 맞게 */
    object-fit: contain;      /* 비율 유지하며 안쪽에 맞춤 */
  }

  .ease-check{
    margin-bottom:6px;
  }

  /* blur for checked (screen only) */
  .easy-checked img{
    filter: blur(3px) grayscale(1);
    opacity:0.4;
  }

  @media print{
    .ease-check{display:none !important;}
    .easy-checked{display:none !important;}
    .clear-btn{display:none !important;}
  }

  @media print {
    .papertest-item { height: 48vh; }

    /* 4번째 항목마다 자동 페이지 분리 */
    .papertest-item:nth-of-type(4n) {
      page-break-after: always;
    }
  }

  /* ---------- cover page ---------- */
  .cover-page{
    width:100%;
    height:100vh;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    page-break-after:always;
  }
  .cover-title{
    font-size:48px;
    font-weight:bold;
    margin-bottom:50px;
  }
  .cover-footer{
    font-size:24px;
    position:absolute;
    bottom:60px;
  }

  .clear-btn{
    font-size:14px;
    border:none;
    background:#f0f0f0;
    padding:6px 12px;
    cursor:pointer;
    margin-top:20px;
  }

	</style>

<script>
function markEasy(checkboxEl, wboardid){
  const item = checkboxEl.closest(".papertest-item");
  if(checkboxEl.checked){
    item.classList.add("easy-checked");
    fetch("../students/update_status.php?wboardid="+encodeURIComponent(wboardid))
      .then(()=>console.log("status updated"));
  }else{
    item.classList.remove("easy-checked");
  }
}

function clearEasy(){
  const cbs=document.querySelectorAll(".ease-check input[type=checkbox]");
  cbs.forEach(cb=>{
    if(cb.checked){
      cb.checked=false;
      const item=cb.closest(".papertest-item");
      if(item) item.classList.remove("easy-checked");
      const wid=cb.dataset.wid;
      if(wid){
        fetch("../students/update_status.php?wboardid="+encodeURIComponent(wid)+"&reset=1");
      }
    }
  });
}
</script>';
?>
