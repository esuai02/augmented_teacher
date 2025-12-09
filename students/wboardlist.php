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
  overflow-x:hidden;
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


<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 

global $DB, $USER;
$studentid = $_GET["id"]; 
$contentsid = $_GET["contentsid"]; 
$contentstype = $_GET["contentstype"]; 
$wboardid = $_GET["wboardid"]; 
$tfinish = $_GET["tfinish"]; 
$timecreated=time(); 

$adayago=$timecreated-86400;
$halfdayago=$timecreated-43200;

$mode= $_GET["mode"]; //풀이노트
if($studentid==NULL)$studentid=$USER->id;
$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$stdtname->firstname.$stdtname->lastname;

$tabtitle=$studentname;
echo ' <head><title>'.$tabtitle.'P</title></head><body>';


if($mode==='realtime')
	{
	$list=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where   ORDER BY nstroke DESC LIMIT 3");  
	$result = json_decode(json_encode($list), True);
	$ncount=count($result);

	unset($value);
	foreach( $result as $value)
		{
		$nrslt++;
		
		if($wboardid==NULL && $ncount==$nrslt)$wboardid=$value['wboardid'];
		$contentsid=$value['contentsid'];
		if($tprev==NULL)$tprev=$value['tlaststroke'];
		$tamount=round(($value['tlaststroke']-$tprev)/60,1);
		$cnticon=$imgstatus;
		$tprev=$value['tlaststroke'];
		$status=$value['status'];
		include("../whiteboard/status_iconsonly.php");
	 
		
		if($value['boardtype']==='duplicate')$styleinfo='font-size:12px';
		elseif($value['status']==='neuroboard' || $value['status']==='boost')$styleinfo='font-size:8px';
		elseif($value['boardtype']==='test')$styleinfo='font-size:20px';
		else $styleinfo='font-size:16px';
 
		if($value['contentstype']==1)
			{
			$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
			$ctext=$getimg->pageicontent;$ctitle=$getimg->title;
			
			$htmlDom = new DOMDocument;@$htmlDom->loadHTML($ctext);$imageTags = $htmlDom->getElementsByTagName('img');$extractedImages = array();$nimg=0;
			foreach($imageTags as $imageTag)
				{
				$nimg++;
    				$imgSrc = $imageTag->getAttribute('src');
				$imgSrc = str_replace(' ', '%20', $imgSrc); 
				if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
				}
			$cnticon=$imgstatus;
			}
		elseif($value['contentstype']==2)
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
		$questiontext='<img src="'.$imgSrc.'" width=500>';  
		$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';  
 	  
		if($wboardid===$value['wboardid'])$view1.=$cnticon.'<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value['wboardid'].'&mode=mathtown&tfinish='.$tfinish.'"><b  style="font-size:30px;color:#4287f5;">'.date("h:i", $value['tlaststroke']).'</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value['wboardid'].' | '.$tamount.'분 | '.$value['nstroke'].'획<hr></span></div>  &nbsp;';
		 else $view1.=$cnticon.'<div class="tooltip3"> <a style="font-size:16px;color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value['wboardid'].'&mode=mathtown&tfinish='.$tfinish.'">'.date("h:i", $value['tlaststroke']).'</a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table>'.$value['wboardid'].' | '.$tamount.'분 | '.$value['nstroke'].'획 <hr></span></div> &nbsp; ';
			 
		}
 

	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | '.$view1.'</th></tr></table>';
	}
 
	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> |  오래된 기억에 대한 인출 활동을 한 다음 공부를 시작하면 해석/발상 메타인지가 향상됩니다.    '.$view1.'</th></tr></table>';
	}
elseif($mode==='note')
	{
	$cid = $_GET["cid"]; 
	$chnum=$_GET["nch"]; 
	$domain = $_GET["domain"]; 
	$chlist=$DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$domain'  ");
	$domaintitle=$chlist->title;
	$chapnum=$chlist->chnum;
	$notetitle=$username.'의 개념집착 : '.$domaintitle;
	for($nch=1;$nch<=$chapnum;$nch++)
		{
		$cidstr='cid'.$nch;
		$chstr='nch'.$nch;
		$cid2=$chlist->$cidstr;
		$nchapter=$chlist->$chstr;

		$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid2'  ");
		$chname='ch'.$nchapter;
		$title=$curri->$chname;
		
 		if($cid==$cid2 && $nchapter==$chnum)
			{
			 $wboardid='obsnote'.$cid2.'_ch'.$nchapter.'_user'.$studentid;
			$view1.='#<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&domain='.$domain.'&mode=note&cid='.$cid2.'&nch='.$nchapter.'"><b  style="color:purple;">'.$nch.' '.$title.'</b></a><span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
			}
		else 
			{
			$view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&domain='.$domain.'&mode=note&cid='.$cid2.'&nch='.$nchapter.'"><b  style="color:purple;">'.$nch.' '.$title.'</b></a><span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
			}		
		}
	echo '<head><title>'.$notetitle.'P</title></head><body>';
 	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid.'&nch='.$chnum.'&mode=domain&domain='.$domain.'&studentid='.$studentid.'">노트보기</a> |  '.$view1.'</th></tr></table>';
	}
elseif($mode==='subject')
	{
	$cid = $_GET["cid"]; 
	$chnum=$_GET["nch"]; 
	$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
	$subjectname=$curri->name;
	$chapnum=$curri->nch;
	$notetitle=$username.'의 개념집착 노트';
	for($nch=1;$nch<=$chapnum;$nch++)
		{
		$chname='ch'.$nch;
		$title=$curri->$chname;
		
 		if($nch==$chnum)
			{
			$wboardid='obsnote'.$cid.'_ch'.$chnum.'_user'.$studentid;
			$view1.='#<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=subject&cid='.$cid.'&nch='.$nch.'"><b  style="color:purple;">'.$nch.' '.$title.'</b></a><span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
			}
		else 
			{
			$view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=subject&cid='.$cid.'&nch='.$nch.'"><b  style="color:purple;">'.$nch.' '.$title.'</b></a><span class="tooltiptext3">'.$tamount.'분<hr><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
			}
		}
	echo ' <head><title>'.$notetitle.'P</title></head><body>';
 	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid.'&nch='.$chnum.'&studentid='.$studentid.'">노트보기</a> |  '.$view1.'</th></tr></table>';
	}
elseif($mode==='ltm')
	{
	$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdtname->firstname.$stdtname->lastname;

	$tabtitle=$studentname;
	echo ' <head><title>'.$tabtitle.'P</title></head><body>';
	$tweek1=time()-604800;
	$tweek2=time()-604800*2;
	 
	$replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND status='attempt' AND tlaststroke >'$tweek2' AND tlaststroke <'$tweek1' ORDER BY nstroke DESC LIMIT 10");  
	$result1 = json_decode(json_encode($replay1), True);
 
	unset($value1);
	foreach( $result1 as $value1)
		{
		if($wboardid==NULL)$wboardid=$value1['wboardid'];
		$dayspassed=round((time()-$value1['tlaststroke'])/86400,0);
		$contentsid=$value1['contentsid'];
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
 
		 if($wboardid===$value1['wboardid'])$view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm"><b style="color:red;">'.$value1['nstroke'].'획</b></a>('.$dayspassed.'일)<span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
		 else $view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm">'.$value1['nstroke'].'획</a>획('.$dayspassed.'일)<span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div> &nbsp; ';
		}
 
 
	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a> | 기억관찰&nbsp;  '.$view1.' </th></tr></table>';
	}
elseif($mode==='mysol')
	{
	$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdtname->firstname.$stdtname->lastname;

	$tabtitle=$studentname;
	echo ' <head><title>'.$tabtitle.'P</title></head><body>';
	$tweek1=time()-604800;
	$tweek2=time()-604800*2;
	 
	$replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentsid='$contentsid' AND contentstype=2 AND boardtype='prep'  ORDER BY id DESC LIMIT 10");  
	$result1 = json_decode(json_encode($replay1), True);
 
	unset($value1);
	foreach( $result1 as $value1)
		{
		if($wboardid==NULL)$wboardid=$value1['wboardid'];
		$dayspassed=round((time()-$value1['tlaststroke'])/86400,0);
		$contentsid=$value1['contentsid'];
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
 
		 if($wboardid===$value1['wboardid'])$view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm"><b style="color:red;">'.$value1['nstroke'].'획</b></a>('.$dayspassed.'일)<span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
		 else $view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'&mode=ltm">'.$value1['nstroke'].'획</a>획('.$dayspassed.'일)<span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div> &nbsp; ';
		}
 
 
	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a> | 기억관찰&nbsp;  '.$view1.' </th></tr></table>';
	}
elseif($mode==='retry') // 이곳에 문항 추천 알고리즘 적용.
	{
	$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdtname->firstname.$stdtname->lastname;

	$tabtitle=$studentname;
	echo ' <head><title>Smart Recovery</title></head><body>';
	$tweek1=time()-604800;
	$tweek2=time()-604800*2;
	 
 	$questionid=73359;
 
	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" ></iframe></th></tr></table>';
	}
else
	{ 
	$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdtname->firstname.$stdtname->lastname;

	$tabtitle=$studentname;
	echo ' <head><title>'.$tabtitle.'P</title></head><body>';
	 
	$replay1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND active=1 AND tlaststroke >'$halfdayago' ORDER BY nstroke DESC LIMIT 20");  $result1 = json_decode(json_encode($replay1), True);
 
	unset($value1);
	foreach( $result1 as $value1)
		{
		if($wboardid==NULL)$wboardid=$value1['wboardid'];
		$contentsid=$value1['contentsid'];
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
 
		 if($wboardid===$value1['wboardid'])$view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'"><b style="color:red;">'.$value1['nstroke'].'획</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp;';
		 else $view1.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value1['wboardid'].'">'.$value1['nstroke'].'획</a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div> &nbsp; ';
		}
 
	$replay2=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages where userid LIKE '$studentid' AND contentstype=2 AND active=1 AND tlaststroke >'$halfdayago' ORDER BY neraser DESC LIMIT 5");  
	$result2 = json_decode(json_encode($replay2), True);
 
	unset($value2);
	foreach( $result2 as $value2)
		{
		$contentsid=$value2['contentsid'];
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
	
		if($wboardid===$value2['wboardid'])$view2.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value2['wboardid'].'"><b style="color:red;">'.$value2['neraser'].'회</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>   &nbsp;';
		else $view2.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$value2['wboardid'].'">'.$value2['neraser'].'회</a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>  &nbsp; ';
		}
	echo '<table width=100%><tr><th><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:90vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'&speed=+9" ></iframe></th></tr> 
 	</table><table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1">Onair</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=sol">오답노트</a> &nbsp;&nbsp;&nbsp; 필기&nbsp;&nbsp; '.$view1.'&nbsp;&nbsp;&nbsp;&nbsp;지우개&nbsp;&nbsp; '.$view2.'</th></tr></table>';
 	}
 
echo '<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
 
// url 정보 이용하여 기간, 내용, 학생, 선생님 등 검색 가능하도록 *********************************************
 
$tbegin=time()-$tb; //1주 전
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='7128' AND  timecreated> '$tbegin'    ORDER BY timemodified DESC ");  
$talklist= json_decode(json_encode($share), True);
 
unset($value);  
foreach($talklist as $value)
	{
	$sid=$value['id'];
	$studentid=$value['studentid'];
	$teacherid=$value['teacherid'];
	$sharetext=$value['text'];
	$stdname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdname->firstname.$stdname->lastname;
	$tchname= $DB->get_record_sql("SELECT institution, lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
	$teachername=$tchname->firstname.$tchname->lastname;
	if($tchname->institution!==$academy)continue;
 
	 
	$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
	$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid' AND courseid NOT LIKE '239' AND component NOT LIKE 'core' AND  component NOT LIKE 'local_webhooks'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog		 
	$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 

	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
	$tgoal=time()-$goal->timecreated;

	$ratio1=$engagement3->todayscore;  $ngrowth=$engagement3->ngrowth; $usedtime=$engagement3->usedtime; $totaltime=$engagement3->totaltime; $nattempts=$engagement3->nattempts; 
	$attemptefficiency=$nattempts/$totaltime;
	 
	$weekdata= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE  type LIKE '주간목표' AND userid='$studentid' ORDER BY id DESC LIMIT 1  ");  // abessi_indicators 
	$ratio2= $weekdata->score; $daysetgoal=(time()-$weekdata->timecreated)/86400;
  	$analysistext='';
	if($usedtime<70)$analysistext='출결이상';
	elseif($nattempts<30)$analysistext='풀이이상';
	elseif($attemptefficiency<5)$analysistext='효율이상';
		
		
	$useinfo=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where  userid='$studentid' AND fieldid='90' "); 
  
	if($mode==='my')
		{
		if($id==$teacherid)
          			{
			$sharelist.='<table width=100% ><tbody><tr><td width=1%></td><td width=7% style="white-space: nowrap; text-overflow: ellipsis;" valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank"><b style="color:black;">'.$studentname.'</b></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/agamotto.png" width=20></a></td><td width=5% valign=top><a  style="color:#3399ff;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800&mode=my">'.$teachername.'</a></td><td style="color:#3399ff;"  valign=top>'.$sharetext.' <span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span type="button"  onClick="reportData(\''.$studentid.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=25></span><hr>  </td><td width=5%  valign=top>'.$analysistext.'</td>
<td width=6% style="white-space: nowrap; text-overflow: ellipsis;" valign=top>✎<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank">개념</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank">심화</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank">내신</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank">수능</a></td><td width=10% valign=top>'.$imgtoday.' ('.$ngrowth.')</td><td width=10% valign=top>'.date("m/d", $value['timecreated']).'</td></tr></tbody></table>';

			$feedback=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='8217' AND talkid='$sid'    ORDER BY id ASC ");  
			$feedbacklist= json_decode(json_encode($feedback), True);
			$fbname='fb'.$sid;
			unset($value2);  
			foreach($feedbacklist as $value2)
				{
				$fbid=$value2['id'];
				$feederid=$value2['teacherid'];
				$feedertext=$value2['text'];
				$tcreated=round((time()-$value2['timecreated'])/60,0);
				$feeder= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$feederid' ");
				if($value2['hide']==1) $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:16px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 0)"><img src=https://mathking.kr/Contents/IMAGES/hide.png width=20></span><hr></td><td width=35%></td></tr>';
				else $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:14px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$fbid.'\',\''.$feedertext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 1)"><img src=https://mathking.kr/Contents/IMAGES/view.png width=20></span><hr></td><td width=35%></td></tr>';
				}
			$sharelist.='<table width=100%><tbody>'.$$fbname.'</tbody></table>';
			}
		}
	else
		{ 
		$sharelist.='<table width=100% ><tbody><tr><td width=1%></td><td width=7% style="white-space: nowrap; text-overflow: ellipsis;" valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank"><b style="color:black;">'.$studentname.'</b></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'"target="_blank"><img style="margin-bottom:5px" src="https://mathking.kr/Contents/IMAGES/agamotto.png" width=25></a></td><td width=5% valign=top><a style="color:#3399ff;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800&mode=my">'.$teachername.'</a></td><td style="color:#3399ff;"  valign=top>'.$sharetext.' <span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span type="button"  onClick="reportData(\''.$studentid.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=25></span><hr> </td><td width=5%  valign=top>'.$analysistext.'</td>
<td width=6% style="white-space: nowrap; text-overflow: ellipsis;"  valign=top>✎<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank">개념</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank">심화</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank">내신</a>|<a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank">수능</a></td><td width=10%  valign=top>'.$imgtoday.' ('.$ngrowth.')</td><td width=10%  valign=top>'.date("m/d", $value['timecreated']).'</td></tr></tbody></table>';

		$feedback=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='8217' AND talkid='$sid'    ORDER BY id ASC ");  
		$feedbacklist= json_decode(json_encode($feedback), True);
		$fbname='fb'.$sid;
		unset($value2);  
		foreach($feedbacklist as $value2)
			{
			$fbid=$value2['id'];
			$feederid=$value2['teacherid'];
			$feedertext=$value2['text'];
			$tcreated=round((time()-$value2['timecreated'])/60,0);
			$feeder= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$feederid' ");
			$feedername=$feeder->firstname.$feeder->lastname;
			if($value2['hide']==1) $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:16px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$fbid.'\',\''.$feedertext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 0)"><img src=https://mathking.kr/Contents/IMAGES/hide.png width=20></span><hr></td><td width=35%></td></tr>';
			else $$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5% valign=top>'.$feedername.'</td><td style="font-size:14px;">'.$feedertext.' ('.$tcreated.'분) <span type="button"  onClick="Edittext(\''.$fbid.'\',\''.$feedertext.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span  onClick="hide(16,\''.$fbid.'\', 1)"><img src=https://mathking.kr/Contents/IMAGES/view.png width=20></span><hr></td><td width=35%></td></tr>';
			}
		$sharelist.='<table width=100%><tbody>'.$$fbname.'</tbody></table>';
		}
	}
/*
echo '<script>
function dragChatbox(Wboardid)
		{
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:700,
		   showClass: {
   		 popup: "animate__animated animate__fadeInRight"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutRight"
		  },
		  html:
		    \'<iframe  class="foo"  style="border: 0px none; z-index:2; width:680; height:95vh;margin-left: -40px;margin-top:-30px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivetalk.php?wboardid=\'+Wboardid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
	</script>';
*/
$ndays=(INT)($tb/86400);
 // 이부분 우측창으로..
echo ' <table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$sharelist.'</tbody></table> ';
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										 
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';

echo '
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
 
<style>
.foo {
  width: 200px;
  height: 200px;
  overflow-y: hidden;
}

body.swal2-shown > [aria-hidden="true"] {
  transition: 0.01s filter;
  filter: blur(20px);
}
</style>';



/* 
echo '
<script>	
function reportData(Userid,Sid,Username)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "Talk2us (" + Username +")",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: "공유된 의견과 데이터를 토대로 의견을 입력해 주세요",
  	inputAttributes: {
   	 "aria-label": "Type your message here"
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'11\',
		"inputtext":text,	
		"userid":Userid,
		"sid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
	}
function hide(Eventid,Fbid, Checkvalue){
		var checkimsi = 0;
   		if(Checkvalue==true){
        		checkimsi = 1;
    		}
 		swal("체크시 학생에게 보이지 않습니다.", {buttons: false,timer: 500});
  		 $.ajax({
       		 url: "check.php",
        		type: "POST",
        		dataType: "json",
        		data : { 
		"eventid":Eventid,
            		"fbid":Fbid,
            	 	"checkimsi":checkimsi,
            	 	  },
 	  	 success: function (data){  
		var Teacherid=data.teacherid
		setTimeout(function() {location.reload(); },100);	
  	   	   }
		  });
		}

function Edittext(Itemid,Inputtext)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "내용 수정하기",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputValue: Inputtext,
  	inputAttributes: {
   	 "aria-label": "Type your message here"
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'19\',
		"itemid":Itemid,
		"inputtext":text,	
		},
		success:function(data){
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
	}
</script> ';
*/
 
echo ' 
<style>
.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip3 .tooltiptext3 {
  visibility: hidden;
 
  background-color: white;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 0px ;

 bottom:8%;
 left:40%;
  position: fixed;
   z-index: 2;
}
 
.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position:fixed;
  display: inline;
}
</style>';
 
?>


</body>
</html>
