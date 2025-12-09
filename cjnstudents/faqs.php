<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$studentid=required_param('userid', PARAM_INT); 

$period= $_GET["period"];  
$mode= $_GET["mode"];  
$wboardid= $_GET["wboardid"];  
if($period==NULL)$period=10;

$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$stdtname->firstname.$stdtname->lastname;
$timecreated=time();

$aweekago=$timecreated-604800;
$halfdayago=$timecreated-21600;
$adayago=$timecreated-86400;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role; 
if($wboardid==NULL)$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'   ORDER BY timemodified DESC LIMIT 1"); 
else $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid='$wboardid'  ORDER BY id DESC LIMIT 1");
$interactionwb='intotheworkingmemory'.$thisboard->contentstype.'cnt'.$thisboard->contentsid; 
$checkgoal= $DB->get_record_sql("SELECT text FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1 ");
$cntid= $thisboard->contentsid;
$cnttype=$thisboard->contentstype;

if($thisboard->contentstype==1)
	{
	$getimgbk=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$thisboard->contentsid'  ORDER BY id DESC LIMIT 1");
	$ctextbk=$getimgbk->pageicontent;
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($ctextbk);
	$imageTags2 = $htmlDom->getElementsByTagName('img');
	$extractedImages = array();
	$nimg=0;
	foreach($imageTags2 as $imageTag2)
		{
		$nimg++;
	    $imgSrc1 = $imageTag2->getAttribute('src'); 
		
		if(strpos($imgSrc1, '.png')!= false || strpos($imgSrc1, '.jpg')!= false)break;
		}
	}
elseif($thisboard->contentstype==2)
	{
	$qtext0 = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$thisboard->contentsid' ORDER BY id DESC LIMIT 1 ");
	$htmlDom2 = new DOMDocument;@$htmlDom2->loadHTML($qtext0->questiontext); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
	foreach($imageTags2 as $imageTag2)
		{
		$nimg++; $imgSrc1 = $imageTag2->getAttribute('src'); $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
		if(strpos($imgSrc1, 'hintimages')!= true && (strpos($imgSrc1, '.png')!= false || strpos($imgSrc1, '.jpg')!= false))break;

		//if(strpos($imgSrc1, 'Contents/MATH%20MATRIX/MATH%20images')!= false || strpos($imgSrc1, 'ContentsIMG')!= false)break;
		}
	$htmlDom1 = new DOMDocument;@$htmlDom1->loadHTML($qtext0->generalfeedback); $imageTags1 = $htmlDom1->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
	foreach($imageTags1 as $imageTag1)
		{
		$nimg++; $imgSrc2 = $imageTag1->getAttribute('src'); $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
		if(strpos($imgSrc2, 'MATRIX/MATH')!= false && strpos($imgSrc2, 'hintimages')==false)break;

		//if(strpos($imgSrc2, 'Contents/MATH%20MATRIX/MATH%20images')!= false || strpos($imgSrc2, 'ContentsIMG')!= false)break;  //local/ContentsIMG
		}
	}

//알고리즘으로 선택
$blogchoice='https://blog.naver.com/PostList.naver?blogId=cjn7128&from=postList&categoryNo=4';




if($imgSrc1!=NULL)$cnttext1='<img src="'.$imgSrc1.'" width=90%>';
if($imgSrc2!=NULL)$cnttext2='<hr><img src="'.$imgSrc2.'" width=90%>';
$usernoteid='usernote_'.$studentid.'week'.round(($timecreated-604800*3)/604800,0);
$contentsinfo=$cnttext1.$cnttext2;
$width1=40;
$width2=60;
if($mode=='info')
    {
    $selectpage='<iframe style="top: 0; left: 0; width: 100%; height:90vh; border: 1px solid lightgrey;" src="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"></iframe>';
    $tabcolor1='lightgreen';
    }
elseif($mode=='instruct' || $mode==NULL)
    {
    $selectpage='<iframe style="top: 0; left: 0; width: 100%; height:90vh; border: 1px solid lightgrey;" src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/constructivists.php?cntid='.$thisboard->contentsid.'&cnttype='.$thisboard->contentstype.'&studentid='.$studentid.'&print=0&type=pedagogy"></iframe>';
    $tabcolor2='lightgreen';
    }
elseif($mode=='conversation')
    {
    $selectpage='<iframe style="top: 0; left: 0; width: 100%; height:90vh; border: 1px solid lightgrey;" src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/conversation.php?cnttype='.$thisboard->contentstype.'&type=conversation&cntid='.$thisboard->contentsid.'&userid='.$studentid.'"></iframe>';
    $tabcolor3='lightgreen';    
    }
elseif($mode=='strategize')
    {
    $selectpage='<iframe style="top: 0; left: 0; width: 100%; height:90vh; border: 1px solid lightgrey;" src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/strategize.php?cnttype='.$thisboard->contentstype.'&type=drilling&cntid='.$thisboard->contentsid.'&userid='.$studentid.'"></iframe>';
    $tabcolor4='lightgreen';    
    } 
$currentTab = '<div class="table-wrapper"><div style="display: flex; flex-wrap: wrap;"><table width=100% align=center> 
<tr><td  valign=top align=center width='.$width1.'%>'.$contentsinfo.'</td><td width='.$width2.'% valign=top  align=center style="overflow: hidden;">'.$selectpage.'</td></tr></table></div></div>';

echo '  
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>하이터치</title>
     <style>
    .tab {
        overflow: hidden;
        border: 1px solid #ccc;
        background-color: #f1f1f1;
    }
    .tab a {
        background-color: inherit;
        float: left;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 14px 16px;
        transition: 0.3s;
        font-size: 17px;
        text-decoration: none;
        color: black;
    }
    .tab a:hover {
        background-color: #ddd;
    }
    .tab a.active {
        background-color: red; /* 변경된 부분 */
    }
	.tab a.inactive {
        background-color: lightgrey; 
        color: red;
    }
    .tabcontent {
        padding: 0px 0px;
        border: 1px solid #ccc;
        border-top: none;
    }
</style>

</head>
<body>
    <div class="tab">
		<a style="background-color:'.$tabcolor1.';" href="?tab=tab1&cnttype='.$cnttype.'&type=drilling&cntid='.$cntid.'&userid='.$studentid.'&mode=info" ' . ($currentTab == 'tab1' ? 'class="inactive"' : '') . '><span style="margin-bottom: 20px;">하이터치_'.$studentname.' : '.$checkgoal->text.'</span> '.$imgtoday.' </a>
        <a style="background-color:'.$tabcolor2.';" href="?tab=tab2&cnttype='.$cnttype.'&type=drilling&cntid='.$cntid.'&userid='.$studentid.'&mode=instruct" ' . ($currentTab == 'tab2' ? ' class="active"' : '') . '>👩🏻 내용분석</a>
        <a style="background-color:'.$tabcolor3.';" href="?tab=tab3&cnttype='.$cnttype.'&type=drilling&cntid='.$cntid.'&userid='.$studentid.'&mode=conversation" ' . ($currentTab == 'tab3' ? ' class="active"' : '') . '>설명생성</a>
        <a style="background-color:'.$tabcolor4.';" href="?tab=tab4&cnttype='.$cnttype.'&type=drilling&cntid='.$cntid.'&userid='.$studentid.'&mode=strategize" ' . ($currentTab == 'tab4' ? ' class="active"' : '') . '>확인질문</a> 
    </div>
    <div class="tabcontent">
        '.$currentTab.' 
    </div>
</body>
</html>';
echo '<style>

  .scrollable-table {
    width: 100%;
    overflow-x: auto; /* 수평 스크롤 활성화 */
    overflow-y: auto; /* 수직 스크롤 활성화 */
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  iframe {
    width: 100%;
    height: 100%;
    border: 1px solid black;
  } 

 
    </style>';

 ?>   

