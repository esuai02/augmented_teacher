 <?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$contentstype = $_GET["contentstype"]; 
$contentsid = $_GET["contentsid"]; 
$wboardid=$_GET["wboardid"]; 
$userid = $_GET["userid"]; 
$mode=$_GET["mode"];

#오늘날짜
$timecreated=time(); 
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$userid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid='$wboardid'  ORDER BY id DESC LIMIT 1");
 
$DB->execute("UPDATE {abessi_today} SET inspect='3'  WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청')  ORDER BY id DESC LIMIT 1 ");
//$DB->execute("UPDATE {abessi_messages} SET student_check='1',timemodified='$timecreated'  WHERE wboardid='$wboardid' "); 
$termplan= $DB->get_record_sql("SELECT memo FROM mdl_abessi_progress WHERE userid='$userid' AND plantype ='분기목표' AND hide=0   ORDER BY id DESC LIMIT 1  ");
$DB->execute("UPDATE {abessi_messages} SET turn='0', student_check='1',active='1',feedback=feedback+1,timemodified='$timecreated'  WHERE wboardid='$wboardid' "); 

$thisgmset=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages_rating WHERE wboardid='$wboardid'  ORDER BY id DESC LIMIT 1");

if($thisgmset->timemodified < time()-43200 || $thisgmset->id==NULL)$DB->execute("INSERT INTO {abessi_messages_rating} (userid,wboardid,status,interprete, ideate,solve,timemodified,timecreated) VALUES('$userid','$wboardid','begin','0','0','0','$timecreated','$timecreated')");


$footertext='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 275 15">
  <!-- 배경 -->
  <rect width="275" height="15" fill="#f8f9fa"/>
  
  <!-- 왼쪽 텍스트 -->
  <text x="22" y="10" font-family="Arial" font-size="4" fill="#495057">어려움</text>
  <!-- 선 (텍스트 사이 부분만) -->
  <line x1="38" y1="10" x2="235" y2="10" stroke="#dee2e6" stroke-width="0.5"/>
  
  <!-- 체크 원들 -->
  <circle cx="55" cy="10" r="2" fill="#ffffff"/>
  <circle cx="55" cy="10" r="2.2" stroke="#ff6b6b" stroke-width="0.5" fill="none"/>
  
  <circle cx="95" cy="10" r="2" fill="#ffffff"/>
  <circle cx="95" cy="10" r="2.2" stroke="#ffd43b" stroke-width="0.5" fill="none"/>
  
  <circle cx="135" cy="10" r="2" fill="#ffffff"/>
  <circle cx="135" cy="10" r="2.2" stroke="#69db7c" stroke-width="0.5" fill="none"/>
  
  <circle cx="175" cy="10" r="2" fill="#ffffff"/>
  <circle cx="175" cy="10" r="2.2" stroke="#4dabf7" stroke-width="0.5" fill="none"/>
  
  <circle cx="215" cy="10" r="2" fill="#ffffff"/>
  <circle cx="215" cy="10" r="2.2" stroke="#748ffc" stroke-width="0.5" fill="none"/>
  
  <!-- 오른쪽 텍스트 -->
  <text x="240" y="10" font-family="Arial" font-size="4" fill="#495057">이해함</text>
</svg>';
if($contentsid==NULL || $contentstype==NULL)
	{
	$contentsid=$thisboard->contentsid;
	$contentstype=$thisboard->contentstype;
	}
$message=$thisboard->instruction;
if(strpos($wboardid, 'jnrsorksqcrark')!== false)
 {
	$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
	$ctext=$getimg->pageicontent;
	 
	$ctitle=$getimg->title; 
	 
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
	if($message==NULL)$message='개념공부';
	$reflections=$getimg->reflections;
  if($mode==='questiononly')
	 {
		if(strpos($imgSrc, 'MathNote')!=false)$imgSrc=str_replace('MathNote','MathNote_exam',$imgSrc);
	 }
	 
 }
else
 {
	$qtext = $DB->get_record_sql("SELECT questiontext,reflections1,name  FROM mdl_question WHERE id='$contentsid' ");
	$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
	$reflections=$qtext->reflections1;
	foreach($imageTags as $imageTag)
		{
		$imgSrc = $imageTag->getAttribute('src');
		$imgSrc = str_replace(' ', '%20', $imgSrc); 
		if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
		}
 
	if($message==NULL)$message='유형공부';
 }
$deadline= date('H:i', time()+600);  
$reflections='';
echo '
<style>
  /* 화면(브라우저)에서 보이는 경우 */
  .page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #ffffff; /* 필요하다면 배경색 적용 */
    padding: 5px 0; /* 일부 여백 */
    border-top: 1px solid #ccc;
  }
  /* 본문 내용이 푸터와 겹치지 않도록 여백을 확보 */
  body {
    margin-bottom: 80px; /* footer 높이보다 조금 더 크게 */
  }
  
  /* 인쇄 시에도 하단에 고정 */
  @media print {
    .page-footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
    }
  }
</style>

<!-- 상단 콘텐츠 -->
<table>
  <tr><td width="10%"></td>
    <td>
      <img style="max-height: 950px; max-width: 100%; object-fit: contain;"
           loading="lazy"
           src="'.$imgSrc.'"
           width="500">
    </td>
  </tr>
</table>
<br>

<!-- 실제 푸터 영역 -->
<div class="page-footer">
  <table align="left" width="100%" class="scale-to-fit">
    <tr><td width="5%"></td>
      <td width="5%"><a href="https://mathking.kr/moodle/local/augmented_teacher/talk2us/listentoyou.php?userid=2&wboardid='.$wboardid.'&cntid='.$contentsid.'&cnttype='.$contentstype.'">🎙️발표</a></td>
      <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        '.$stdname.'&nbsp;'.date("Y/m/d").'
        <span id="print" onclick="window.print()"> (인쇄) </span>
      </td>
      <td width="70%">
        '.$footertext.'
      </td>
    </tr>
  </table>
</div>
';


echo ' 
<style>
@media print {
  /* 이미지 컨테이너를 세팅 */
  .image-container {
    width: 100%;
    max-height: 700px; /* 페이지에 맞춰 적절히 조절 */
    overflow: hidden; /* 넘치는 부분은 숨김 */
    page-break-inside: auto;
  }

</style>
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
<script>
  function printPage() {
	window.print();
  } 
  window.onload = function() {
	var element = document.getElementById("print");
	setTimeout(function() {
		element.click();
		},500);
	};
</script>';

?>