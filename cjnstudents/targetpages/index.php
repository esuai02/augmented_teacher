<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentindex','$timecreated')");
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
 
$timestart3=time()-86400;
 
// 개념노트
 
$timestart2=time()-43200;
$aweekago=time()-604800;  //AND
$getcmid=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid' AND timemodified> '$aweekago' AND   contentstype=1  ORDER BY timemodified DESC LIMIT 20 ");
  
$nnote=0;
$nreview=0;
$ncomplete=0;
$nask=0;
$ntotal=$nright+$nwrong+$ngaveup;
$result1 = json_decode(json_encode($getcmid), True);
unset($value);
foreach($result1 as $value) 
{
$nnote++; 
if($cmid!==$value['cmid'])
	{
	$cmid=$value['cmid'];
	$wboardlist0='';$wboardlist1='';$wboardlist2='';
	$wboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid' AND cmid='$cmid' AND  contentstype=1  ORDER BY pagenum ASC LIMIT 20 ");
	$wboardlist.= '<tr><td> <br> </td><td> <br></td> <td> <br> </td> <td>  <br> </td> <td>  <br> </td> <td>  <br> </td>   </tr> '; 
	$topictitle=iconv_substr($value['contentstitle'], 0, 20, "utf-8");
	$wboardlist.= '<tr><td> <hr> </td><td style="color:blue;"><b>'.$topictitle.'</b></td> <td> <hr> </td> <td> <hr> </td> <td> <hr> </td> <td> <hr> </td>   </tr> '; 
	$result2 = json_decode(json_encode($wboards), True);
	unset($value2);
	foreach($result2 as $value2) 
		{
		$nstroke=(int)($value2['nstroke']/2);
		$pageurl=$value2['url'];
		if($pageurl==NULL)continue;
		$ave_stroke=round($nstroke/(($value2['tlast']-$value2['tfirst'])/60),1);
		$contentstype=$value2['contentstype'];
		$status=$value2['status'];
		$contentsid=$value2['contentsid'];
  		$wboardid=$value2['wboardid'];
		$bessiboard=substr($wboardid, 0, strpos($wboardid, '_user')); // 문자 이후 삭제
		$bessiboard=str_replace("_user","",$bessiboard);
		$bessiboard='cjnNote'.$bessiboard;

		$subjectTitle=iconv_substr($value2['instruction'], 0, 20, "utf-8");
		
		$cmid=$value2['cmid'];
		$checkstatus='';
		$tutorid=$value2['userto'];
		$comment= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$tutorid' ");
		$tutorname=$comment->firstname.$comment->lastname;
		if($value2['student_check']==1)$checkstatus='checked'; 
 
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
			if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
			}

		$questiontext='<img src="'.$imgSrc.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 	 
		if($nstroke<3)
			{
			$ave_stroke='###';
			$nstroke='###';
			}
 
		include("../whiteboard/status_icons.php");
		$topictye='';
		if($value2['pagenum']==0)$topictye='<img src=https://mathking.kr/Contents/IMAGES/handw.png width=20>';  //  서술평가
		elseif($value2['pagenum']==1)$topictye='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626656809001.png width=20>';  //  개념도입
		elseif(strpos($value2['instruction'], 'Approach')!== false || $value2['pagenum']==1) $topictye='<img src=https://mathking.kr/Contents/IMAGES/approach.png width=20>'; // 개념 Approach
		elseif(strpos($value2['instruction'], 'Check')!== false) $topictye='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626657039001.png width=20>';  //  개념체크
		elseif(strpos($value2['instruction'], '대표유형')!== false) $topictye='<img src=https://mathking.kr/Contents/IMAGES/necessary.png width=20>';  // 대표유형


		$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete0.png" height=10 width=90>';
		if($value2['star']==1)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete1.png" height=10 width=90>';
		if($value2['star']==2)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete2.png" height=10 width=90>';
		if($value2['star']==3)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete3.png" height=10 width=90>';
		if($value2['star']==4)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete4.png" height=10 width=90>';
		if($value2['star']==5)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete5.png" height=10 width=90>';

		$resultValue2='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" width=90>';
		if($value2['depth']==1)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" width=90>';
		if($value2['depth']==2)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" width=90>';
		if($value2['depth']==3)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" width=90>';
		if($value2['depth']==4)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" width=90>';
		if($value2['depth']==5)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" width=90>';

		$bstrate=$value2['nfire']/($value2['nmax']+0.01)*100;
		if($bstrate>99)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666457.png';
		elseif($bstrate>70)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666432.png';
		elseif($bstrate>40)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666363.png';
		elseif($bstrate>10)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666336.png';
		else $bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666304.png';

		if($value2['pagenum']==0)$wboardlist0.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$pageurl.'"target="_blank"><div class="tooltip3">&nbsp; '.$topictye.'&nbsp; '.$subjectTitle.'  <span class="tooltiptext3"><table align=center  ><tr><td>'.$value2['instruction'].'</td></tr><tr><td><hr></td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div>'.$imgstatus.'</a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$bessiboard.'&srcid='.$wboardid.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659013455.png" height=15></a></span> '.$value2['nstroke'].'획</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$wboardid.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a></td><td>  '.$resultValue.'  &nbsp;'.date("m월d일",$value2['timemodified']).'  </td><td width=2%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&nbsp;&nbsp;&nbsp;&nbsp;'.$resultValue2.'</td></tr> '; 
		elseif(strpos($value2['instruction'], 'Approach')!== false || $value2['pagenum']==1) $wboardlist1.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$pageurl.'"target="_blank"><div class="tooltip3">&nbsp; '.$topictye.'&nbsp; '.$subjectTitle.'  <span class="tooltiptext3"><table align=center  ><tr><td>'.$value2['instruction'].'</td></tr><tr><td><hr></td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div>'.$imgstatus.'</a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$bessiboard.'&srcid='.$wboardid.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659013455.png" height=15></a></span> '.$value2['nstroke'].'획</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$wboardid.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a></td><td>  '.$resultValue.'  &nbsp;'.date("m월d일",$value2['timemodified']).'  </td><td width=2%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&nbsp;&nbsp;&nbsp;&nbsp;'.$resultValue2.'</td></tr> '; 
		else $wboardlist2.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$pageurl.'"target="_blank"><div class="tooltip3">&nbsp; '.$topictye.'&nbsp; '.$subjectTitle.'  <span class="tooltiptext3"><table align=center  ><tr><td>'.$value2['instruction'].'</td></tr><tr><td><hr></td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div>'.$imgstatus.'</a></td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$bessiboard.'&srcid='.$wboardid.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659013455.png" height=15></a></span> '.$value2['nstroke'].'획</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$wboardid.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a></td><td>  '.$resultValue.'  &nbsp;'.date("m월d일",$value2['timemodified']).'  </td><td width=2%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&nbsp;&nbsp;&nbsp;&nbsp;'.$resultValue2.'</td></tr> '; 
		}
	//$wboardlist.=$wboardlist1.$wboardlist0.$wboardlist2;
	$wboardlist.=$wboardlist1.$wboardlist2;
	}
}   

$timestart=$timecreated-604800*2;

$nnn=1;
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY id DESC ");
$adayAgo=time()-43200;
$result2 = json_decode(json_encode($goals), True);
unset($value);
 
foreach($result2 as $value)
	{
	//$date_pre=$date;
	$att=gmdate("m월 d일 ", $value['timecreated']+32400);
	$date=gmdate("d", $value['timecreated']+32400);
	$goaltype=$value['type'];
  	if($goaltype==='오늘목표' || $goaltype==='검사요청'){$goaltype='<span style="color:black;">오늘목표</span>';$notetype='summary';}
	elseif($goaltype==='주간목표'){$goaltype='<b style="color:#bf04e0;">주간목표</b>';$notetype='weekly';}
	elseif($goaltype==='시험목표'){$goaltype='<b style="color:blue;">분기목표</b>';$notetype='examplan';}
 

	$daterecord=date('Y_m_d', $value['timecreated']);  	 
	$tend=$value['timecreated'];
	 
	$tfinish0=date('m/d/Y', $value['timecreated']+86400); 
 	$tfinish=strtotime($tfinish0);

	if($nnn==1 && ($value['type']==='오늘목표' || $value['type']==='검사요청'))
		{
		$goaltype='<b style="color:red;">지난시간</b>';
		$goalhistory0.= '<tr height=30 style="background-color:#b8fcfc;"><td>&nbsp;&nbsp;</td><td style="color:black;"><a   style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td  style="color:black;">&nbsp;&nbsp;&nbsp; </td>
		<td  style="color:black;">'.$att.'&nbsp;&nbsp;&nbsp;</td><td  style="color:black;"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td ><a  style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td width=5%></td><td style="color:white;"><a  style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">기억소생</a></td> </tr>';
		$nnn++;	 
		}
	else $goalhistory1.= '<tr><td>&nbsp;&nbsp;</td><td style="color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td style="color:white;">&nbsp;&nbsp;&nbsp; </td>
	<td style="color:white;">'.$att.'&nbsp;&nbsp;&nbsp;</td><td style="color:white;"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td style="color:white;"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td width=5%></td><td style="color:white;"> <a  style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish= '.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">기억소생</a></td> </tr>';
	}
	
	
 
echo ' 			 			<div class="col-md-12">
							<div class="card">
								  <div class="card-title"><div class="card-body"> 
									 
									  ';
										// get mission list
										$trecent2=time()-31104000;  // 1year ago
										$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE  timecreated>'$trecent2' AND userid='$studentid' ORDER by norder ASC ");
										$result = json_decode(json_encode($missionlist), True);
										 
										unset($value);
										foreach($result as $value)
										{
										$mtid=0;
										$mid=$value['id'];
										$subject=$value['subject'];	
										$deadline= $value['deadline']; 	
										$unixtimedeadline=strtotime($deadline);	
										if($unixtimedeadline > time()+31536000 || $unixtimedeadline < time()-31536000)continue;
										$passgrade=$value['grade'];
										$mtname=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' ");
										$contentslist=$mtname->contentslist;
										$subjectname=$mtname->name;
										$mtid=$mtname->mtid;
     										$subjectname=str_replace("개념 :","",$subjectname);
     										$subjectname=str_replace("심화 :","",$subjectname);
     										$subjectname=str_replace("내신 :","",$subjectname);
     										$subjectname=str_replace("수능 :","",$subjectname);
										 
										
										
										if($value['complete']==0)
											{
											if($mtid==1 ||$mtid==7)
												{
												$mt01.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$subject.'&nch=1&studentid='.$studentid.'&type=init"target="_blank"><img style="margin-bottom:4px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png width=20> GPT '.$subjectname.' </a> </td>
												<td width=4% style=""></td><td  width=30% align="left" style="font-size:12pt"> <img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>예전 페이지</b></td><td width=20% style="font-size:10pt">합격 : '.$passgrade.'점</td>
												<td width=4%><div class="form-check"> 완료 &nbsp;<label  style="margin-bottom:5px;"  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==2)
												{
												if(strpos($subjectname,'초등')!==false)$mt02.='<tr> <td width=30% align="left"   style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
												else $mt02.='<tr><td   width=30% align="left"  style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==3)
												{
												$mt03.='<tr> <td  width=30% align="left"  style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label  style="margin-bottom:5px;"  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==4)
												{
												$mt04.='<tr><td  width=30% align="left"  style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											} 
										else 
											{
										 	if($mtid==1 ||$mtid==7)
												{
												$mt05.='<tr><td  width=30% align="left" style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">개념 : '.$subjectname.'</td><td width=4% style=""></td><td width=20% style="font-size:10pt">합격 : '.$passgrade.'점</td>
												<td width=4%><div class="form-check"> 추가 &nbsp;<label  style=""  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==2)
												{
												if(strpos($subjectname,'초등')!==false)$mt06.='<tr> <td width=30% align="left"   style="font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">심화 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
												else $mt06.='<tr><td   width=30% align="left"  style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">심화 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==3)
												{
												$mt07.='<tr><td  width=30% align="left"  style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">내신 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label  style=""  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==4)
												{
												$mt08.='<tr><td  width=30% align="left"  style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">수능 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											 
											}
										}
 										echo '<table width=100%  > <tr><th width=2%> </th><th width=40% > </th>  <th width=3%> </th> <th></th></tr>
													<tr><td></td><td   valign=top >
													<table width=100%  valign=top  ><tr><th width=5%></th><th width=80%></th></tr> 
													 												
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 개념</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=7&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;<a style="color:white" href="http://mathking.kr/moodle/local/augmented_teacher/twinery/topiclearning.html"target="_blank">도움말</a>&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt01.'</table></td></tr>   
													 
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 심화</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=2&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;<a style="color:white" href="http://mathking.kr/moodle/local/augmented_teacher/twinery/deeperlearning.html"target="_blank">도움말</a>&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$mt02.'</table></td></tr>   
													 
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 내신</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=3&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt03.'</table></td></tr>   
													 
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 수능</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=4&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt04.'</table></td></tr>   
													<tr><td align=center  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 후속</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'">장기계획 설정 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt05.$mt06.$mt07.$mt08.'</table></td></tr></table>
													<table width=100% align=center style="background-image:url(https://mathking.kr/moodle/local/augmented_teacher/IMAGES/restore.png);background-size:cover;">'.$goalhistory0.$goalhistory1.'</table></td><td></td>
													<td valign=top > 
													<table width=100% valign=top>
													<tr><td align=center  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; Cognitive tools</td></tr>
													<tr><td></td></tr></table><table><tr><td  valign=top width=8%  align=center><a href="https://app.gather.town/app/0bwIAlhyu6Z7ynWK/KAIST%20TOUCH%20MATH"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1648457660.png width=60></a></td></tr></table>
											 
													<table width=100% valign=top><tr><td align=center  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 개념</td><td align=center   style="background-color:#3383FF;color:white;height:40px;">&nbsp;&nbsp;&nbsp; 개념 생각회로 만들기 &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;   현황&nbsp;&nbsp;&nbsp;  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/CognitiveMap.php?id='.$studentid.'&tb=43200" target="_blank"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655175200.png" width=25></a> </td></tr><tr><td></td><td><table  >'.$wboardlist.'</table></td></tr></table></td>
													</tr></table>
										  <h4 class="card-title"><div style=" font:bold 1.2em/1.0em 맑은고딕체;text-align: center ;color:blue;" > '.$todaygoal.' </h4></div> </div></div></div></div> ';
 
 								
include("brainportal.php");
echo ' </div></div></div></div></div>';

include("quicksidebar.php");
echo '
  	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>
	<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
 
	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>


	<script>
		function inputmission(Eventid,Userid,Inputtext,Deadline){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "inputtext":Inputtext,
			  "deadline":Deadline,		 
		               },
		            success:function(data){
			            }
		        })

		}
		function changecheckbox(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		 location.reload();
		}
		function ChangeCheckBox2(Eventid,Userid, Wboardid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
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
		}
		function checkwhiteboard(Eventid,Userid,Wboardid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "wboardid":Wboardid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		}	 


 
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});

		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
			
		});		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 		 
		});

		$("#basic").select2({
			theme: "bootstrap"
		});

		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});
		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );

	</script>
';

?>
