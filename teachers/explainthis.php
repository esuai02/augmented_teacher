 <?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$contentstype = $_GET["contentstype"]; 
$contentsid = $_GET["contentsid"]; 
$wboardid=$_GET["wboardid"]; 
$userid = $_GET["userid"]; 
#오늘날짜
$timecreated=time(); 
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$userid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid='$wboardid'  ORDER BY id DESC LIMIT 1");
 
$DB->execute("UPDATE {abessi_today} SET inspect='3'  WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청')  ORDER BY id DESC LIMIT 1 ");
$DB->execute("UPDATE {abessi_messages} SET student_check='1',timemodified='$timecreated'  WHERE wboardid='$wboardid' "); 

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
 }
else
 {
	$qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid' ");
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
echo '<br><br><br><table width=90% class="scale-to-fit"><tr><td width=5%></td><td align=center>('.$stdname.'&nbsp;'.date("Y/m/d").')  &nbsp;&nbsp; '.$deadline.'까지 완료해 주세요</td><td width=10%> </td></tr></table><hr><table align=center width=90% class="scale-to-fit"><tr><td width=5%></td><td align=center style="font-size:20;">이미지를 복사하여 채팅 창에 입력해주세요.</td><td width=10%> </td></tr></table>
<table width=100% align=center><tr><td><hr></td><td><hr></td><td><hr></td></tr><tr><td width=10%></td><td width=40%><img style="object-fit: contain;" loading="lazy" src="'.$imgSrc.'" width=500"></td><td width=60%><iframe src="https://chat.openai.com/g/g-UKTVcUmFc-math-master" frameborder="0"></iframe></td></tr><tr><td width=10% valign=top></td><td>'.$reflections.'</td><td></td></tr></table><table align=center><tr><td align=center><a style="font-size:16px; text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_record.php?id='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img src=https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png width=30></a><hr>발표시작</td></tr></table>
<hr><table align=center><tr><td>완료 후 선생님에게 와주세요</td></tr></table>';

echo ' 
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

    <style>
    iframe {
        width: 60%; /* iframe의 너비를 100%로 설정 */
        height: 100%; /* iframe의 높이를 조정 */
        border: none; /* iframe 주변의 테두리 제거 */
    }
</style>
 ';

?>