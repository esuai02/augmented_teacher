<!DOCTYPE html>
<html>
<style>
* {
  box-sizing: border-box;
}
@media print  
{
    div {
        page-break-inside: avoid;
    }
}
img {
border: 1px solid #555;
 
}
body {
  margin: 0;
  font-family: Arial;
}

.header {
  text-align: center;
  padding: 32px;
}

.row {
  display: -ms-flexbox; /* IE10 */
  display: flex;
  -ms-flex-wrap: wrap; /* IE10 */
  flex-wrap: wrap;
  padding: 0 4px;
}

/* Create four equal columns that sits next to each other */
.column {
  -ms-flex: 25%; /* IE10 */
  flex: 25%;
  max-width: 25%;
  padding: 0 4px;
}

.column img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

/* Responsive layout - makes a two column-layout instead of four columns */
@media screen and (max-width: 1000px) {
  .column {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

/* Responsive layout - makes the two columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
}
 

</style>
<body>

 
<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;
$studentid = $_GET["userid"]; 

$adayago=time()-43200;
$attempts = $DB->get_records_sql("SELECT * FROM mdl_abessi_activelearning WHERE userid='$studentid' AND timecreated > '$adayago' "); 
$result = json_decode(json_encode($attempts), True); 
 
unset($value);
foreach( $result as $value)
	{
	
	$wboardid=$value['wboardid']; 
	$contentsid=$value['contentsid'];
	$contentstype=$value['contentstype'];
	if($contentstype==1)
		{
		$getimg=$DB->get_record_sql("SELECT pageicontent,title FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
		$ctext=$getimg->pageicontent;
		$ctitle=$getimg->title;
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
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id ='$contentsid' ");
		$qtext=$getimg->questiontext;

		$htmlDom = new DOMDocument;
		@$htmlDom->loadHTML($qtext);
		$imageTags = $htmlDom->getElementsByTagName('img');
		$extractedImages = array();

		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
				$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'imagefiles')!= false)break;
			}
		}

	$imagegrid.='<div class="column"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_confirm.php?id='.$wboardid.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank"><img loading="lazy" src="'.$imgSrc.'" style="width:100%"></a></div>';       
	} 

echo '<br> &nbsp;📐&nbsp; Spaced Repetition : 랜덤평가를 준비해 주세요.  (참고 : 나누어 생각하기)<br><hr>'.$imagegrid; 
 
?> 

</body>
</html>
