<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid= $_GET["studentid"]; 
$nweek= $_GET["nweek"]; 
$contentsid= $_GET["contentsid"]; 
$contentstype= $_GET["contentstype"]; 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$teacherid=$USER->id; 
 
echo ' 
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	 
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>KTM Cognitive Map</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" type="image/x-icon"/>
	<script src="../assets/js/plugin/webfont/webfont.min.js"></script>
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>';

 // 개념노트 사용기록
$aweekago=time()-604800*$nweek;   
$getcmid=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid' AND tlaststroke > '$aweekago' AND (pagenum > 1 OR pagenum=0) AND  contentstype=1  ORDER BY tlaststroke DESC LIMIT 100");
  
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
if($cmid!==$value['cmid'] && strpos($value['contentstitle'], '중급')===false  && strpos($value['contentstitle'], '심화')===false)
	{
	$cmid=$value['cmid'];
	$wboardlist0='';$wboardlist1='';$wboardlist2='';
	$wboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid' AND cmid='$cmid' AND  contentstype=1  ORDER BY pagenum ASC LIMIT 30 ");
	$topictitle=iconv_substr($value['contentstitle'], 0, 20, "utf-8");
	if(strpos($topictitle, '개념 Check')===false)$wboardlist.= '<tr><td> <hr> </td><td style="color:blue;"><b>'.$topictitle.'</b></td> <td> <hr> </td> <td> <hr> </td> <td> <hr> </td> <td> <hr> </td>   </tr> ';  
	$result2 = json_decode(json_encode($wboards), True);
	unset($value2);
	foreach($result2 as $value2) 
		{
		$nstroke=(int)($value2['nstroke']/2);
		$ave_stroke=round($nstroke/(($value2['tlast']-$value2['tfirst'])/60),1);
		$contentstype=$value2['contentstype'];
		$status=$value2['status'];
		$contentsid0=$value2['contentsid'];
		$contentstype0=1; 
		if(strpos($value2['wboardid'], '_step')!==false)
			{
			$nstep=strstr($value2['wboardid'], '_step');  //before
			$nstep=str_replace("_step","",$nstep);
			}
		else $nstep=0;
		$currenturl= $value2['url'];
		
		$thisurl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$value2['url'];

		$subjectTitle=iconv_substr($value2['instruction'], 0, 20, "utf-8"); 
		$query = parse_url($thisurl, PHP_URL_QUERY);
		parse_str($query, $params);
		 
		$cntpageid=$params['cntpageid'];
		if($cntpageid==NULL)$cntpageid=$params['pageid']; 
		if($cntpageid==NULL)$cntpageid=$contentsid0;
		
		//echo 'page>>>>'.$cntpageid.'<hr>';
		$thisboardid=$value2['wboardid'];
		if(strpos($currenturl, 'mode')!== false) $currenturl='id='.$thisboardid;
		//$currenturl='id='.$thisboardid;
		$DB->execute("UPDATE {abessi_messages} SET contentsid='$cntpageid' WHERE wboardid='$thisboardid' ORDER BY id DESC LIMIT 1 ");
		$cmid=$value2['cmid'];
		$checkstatus='';
		$tutorid=$value2['userto'];
		$comment= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$tutorid' ");
		$tutorname=$comment->firstname.$comment->lastname;
		if($value2['student_check']==1)$checkstatus='checked'; 
 
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid0' ");
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
		if($value2['depth']==1)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete1.png" height=10 width=90>';
		if($value2['depth']==2)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete2.png" height=10 width=90>';
		if($value2['depth']==3)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete3.png" height=10 width=90>';
		if($value2['depth']==4)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete4.png" height=10 width=90>';
		if($value2['depth']==5)$resultValue='<img src="https://mathking.kr/Contents/IMAGES/complete5.png" height=10 width=90>';

		$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1623817278001.png" width=90>';
		if($value2['star']==1)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" width=90>';
		if($value2['star']==2)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" width=90>';
		if($value2['star']==3)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" width=90>';
		if($value2['star']==4)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" width=90>';
		if($value2['star']==5)$resultValue2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" width=90>';
 
		if($value2['pagenum']==0 )$wboardlist0.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl.'&refid=-1&contentsid0='.$contentsid.'&contentstype0='.$contentstype.'"target="_blank"><div class="tooltip3">&nbsp;'.$topictye.'&nbsp;'.$subjectTitle.''.$imgstatus.'<span class="tooltiptext3"><table align=center  ><tr><td>'.$value2['instruction'].'</td></tr><tr><td><hr></td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td width=2%></td><td width=2%></td><td>  '.$resultValue.'  &nbsp;'.date("m월d일",$value2['tlaststroke']).'  </td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&nbsp;&nbsp;&nbsp;&nbsp;'.$resultValue2.'   '.$tutorname.' </td></tr> '; 
		elseif(strpos($value2['instruction'], 'Approach')=== false || $value2['pagenum']==1) $wboardlist1.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl.'&refid=-1&contentsid0='.$contentsid.'&contentstype0='.$contentstype.'"target="_blank"><div class="tooltip3">&nbsp;'.$topictye.'&nbsp;'.$subjectTitle.''.$imgstatus.'<span class="tooltiptext3"><table align=center  ><tr><td>'.$value2['instruction'].'</td></tr><tr><td><hr></td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td width=2%></td><td width=2%></td><td>  '.$resultValue.'  &nbsp;'.date("m월d일",$value2['tlaststroke']).'  </td>
											       <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&nbsp;&nbsp;&nbsp;&nbsp;'.$resultValue2.'   '.$tutorname.'  </td></tr> '; 
		else $wboardlist2.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl.'&refid=-1&contentsid0='.$contentsid.'&contentstype0='.$contentstype.'"target="_blank"><div class="tooltip3">&nbsp;'.$topictye.'&nbsp; '.$subjectTitle.' '.$imgstatus.'<span class="tooltiptext3"><table align=center  ><tr><td>'.$value2['instruction'].'</td></tr><tr><td><hr></td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td width=2%></td><td width=2%></td><td>  '.$resultValue.'  &nbsp;'.date("m월d일",$value2['tlaststroke']).'  </td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">&nbsp;&nbsp;&nbsp;&nbsp;'.$resultValue2.'   '.$tutorname.'  </td></tr> '; 
		}
	$wboardlist.=$wboardlist1.$wboardlist0.$wboardlist2;
	}
}   

// 연결된 컨텐츠들
 
$usedcnt=$DB->get_records_sql("SELECT * FROM mdl_abessi_references WHERE  contentsid='$contentsid' AND  contentstype='$contentstype' AND active=1  ORDER BY id DESC LIMIT 30");
$usedcnt_result = json_decode(json_encode($usedcnt), True);
unset($value3);
foreach($usedcnt_result as $value3) 
	{
	$nnote++; 
	$subjectTitle='';
	$relatedcontents0='';$relatedcontents1='';$relatedcontents2='';
	 
	$refid=$value3['id'];
	$nview=$value3['nview'];
	//$relatedcontents.= '<tr><td> <hr> </td><td style="color:blue;"><b>'.$topictitle.'</b></td> <td> <hr> </td> <td> <hr> </td> <td> <hr> </td> <td> <hr> </td>   </tr> '; 
  
	$contentstype0=$value3['contentstype0'];
	$contentsid0=$value3['contentsid0'];
	$currenturl=$value3['pageurl'];
	//$currenturl='id='.$thisboardid;
	$checkstatus='';
	$thisurl2='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl;
	$topictye='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1634287998.png width=25>'; 
	$query2 = parse_url($thisurl2, PHP_URL_QUERY);
	parse_str($query2, $params2);
	$wboardid=$params2['id']; 
	if(strpos($wboardid, '_step')!==false)
		{
		$nstep=strstr($wboardid, '_step');  //before
		$nstep=str_replace("_step","",$nstep);
		}
	else $nstep=0;

	$comment= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$tutorid' ");
	$tutorname=$comment->firstname.$comment->lastname;
	if($value3['active']==1)$checkstatus='checked'; 

	$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid0' ");  
	$topictitle=$getimg->title;

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
	if($nstep>0)
		{
		$insttext=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1"); 
		$subjectTitle=$insttext->instruction;
		$topictitle='';
		$topictye='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1634292777.png width=25>'; 
		}
	 

 	 
	if($nstroke<3)
		{
		$ave_stroke='###';
		$nstroke='###';
		}
	include("../whiteboard/status_icons.php");
 	$dismiss='';
	if($role!=='student')	$dismiss='<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(this.checked,\''.$contentsid0.'\',\''.$contentstype0.'\',\''.$currenturl.'\',\''.$nstep.'\')"/>';
	
	if($value3['pagenum']==0)$relatedcontents0.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl.'&refid='.$refid.'"target="_blank"><div class="tooltip3">&nbsp; '.$topictye.'&nbsp; '.$topictitle.$subjectTitle.'  <span class="tooltiptext3"><table align=center  ><tr><td>'.$value3['instruction'].'</td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div>'.$imgstatus.'</a></td><td></td><td width=2%>'.$dismiss.'</td><td>조회수 ('.$nview.')</td><td></td></tr> '; 
	elseif(strpos($value3['instruction'], 'Approach')!== false || $value3['pagenum']==1) $relatedcontents1.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl.'&refid='.$refid.'"target="_blank"><div class="tooltip3">&nbsp; '.$topictye.'&nbsp; '.$subjectTitle.'  <span class="tooltiptext3"><table align=center  ><tr><td>'.$value3['instruction'].'</td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div>'.$imgstatus.'</a></td><td></td><td width=2%>'.$dismiss.'</td><td>조회수 ('.$nview.')</td><td></td></tr> '; 
	else $relatedcontents2.= '<tr  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td valign=top> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$currenturl.'&refid='.$refid.'"target="_blank"><div class="tooltip3">&nbsp; '.$topictye.'&nbsp; '.$subjectTitle.'  <span class="tooltiptext3"><table align=center  ><tr><td>'.$value3['instruction'].'</td></tr><tr><td>'.$questiontext.'</td></tr></table></span></div>'.$imgstatus.'</a></td><td></td><td width=2%>'.$dismiss.'</td><td>조회수 ('.$nview.')</td><td></td></tr> '; 
		 
	if($contentsid!=1)$relatedcontents.=$relatedcontents1.$relatedcontents0.$relatedcontents2;
	}
		
echo '<br><br><table align=right with=80%><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/searchmynote.php">노트검색</a></td> <td width=20%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/CognitiveTopicMap.php?studentid='.$studentid.'&nweek=4&contentsid='.$contentsid.'&contentstype='.$contentstype.'">최근 한달</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/CognitiveTopicMap.php?studentid='.$studentid.'&nweek=15&contentsid='.$contentsid.'&contentstype='.$contentstype.'">최근 3개월</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/CognitiveTopicMap.php?studentid='.$studentid.'&nweek=30&contentsid='.$contentsid.'&contentstype='.$contentstype.'">최근 6개월</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/CognitiveTopicMap.php?studentid='.$studentid.'&nweek=65&contentsid='.$contentsid.'&contentstype='.$contentstype.'">최근 1년</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table><br>
<br><table align=center>'.$relatedcontents.'</table><br><br><table align=center>'.$wboardlist.'</table>';  
 
echo '	
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script>
function ChangeCheckBox(Checkvalue,Contentsid0,Contentstype0,Currenturl,Nstep){
		    var checkimsi = 0;
		    var Eventid = 2;
		    var Userid= \''.$studentid.'\';
		    var Tutorid= \''.$teacherid.'\';
		    var Contentstype= \''.$contentstype.'\';
		    var Contentsid= \''.$contentsid.'\';
 
			 
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check_status.php",
  		      type: "POST",
		        dataType: "json",
		        data : {
			  "eventid":Eventid,   
			  "userid":Userid,       
		                "tutorid":Tutorid,
		                "currenturl":Currenturl,
		                "nstep":Nstep,
		                "checkimsi":checkimsi,
		                "contentsid":Contentsid,
		                "contentsid0":Contentsid0,
		                "contentstype":Contentstype,
		                "contentstype0":Contentstype0,		                
		               },
		        success: function (data){  

		        }
		    });
    
      swal("저장되었습니다.", {icon: "success",buttons: false,timer:500,});
		}
</script>

<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script><!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	 
	<script src="../assets/js/setting-demo.js"></script>
	<script>
		$("#datepicker").datetimepicker({
			format: "MM/DD/YYYY",
		});
	</script>
 ';




 echo '   
<style> 
html,body{
  height:0%;
}
body{
  text-align:center;
}
a:link {
  color: black;
  background-color: transparent;
  text-decoration: none;
}
body:before{
  content:"";
  height:0%;
  display:inline-block;
  vertical-align:middle;
}
button{
  background:#e8fdff;
  color:#fff;

  border: 1px solid #18ad31;
  position:relative;
  height:30px;
  font-size:1.0em;
  padding:0 5em;
  cursor:pointer;
  transition:600ms ease all;
  outline:1px;
}
button:hover{
  background:#fff;
  color:#1AAB8A;
}
button:before,button:after{
  content:"";
  position:absolute;
  top:0;
  right:0;
  height:2px;
  width:0;
  background: #1AAB8A;
  transition:400ms ease all;
}
button:after{
  right:inherit;
  top:inherit;
  left:0;
  bottom:0;
}
button:hover:before,button:hover:after{
  width:100%;
  transition:800ms ease all;
}

 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 500px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
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
a.tooltips span:after {
  position: absolute;
  bottom: 100%;
  right: 1%;
  margin-left: -10px;
  width: 0;
  height: 0;
  border-bottom: 8px solid #23ad5f;
  border-right: 8px solid #0a5cf5;
  border-left: 8px solid #0a5cf5;
}
a:hover.tooltips span {
  visibility: visible;
  opacity: 1;
  top: 0px;
  right: 0%;
  margin-left: 10px;
  z-index: 999;
  border-bottom: 1px solid #15ff00;
  border-right: 1px solid #15ff00; 
  border-left: 1px solid #15ff00;
}
</style>';
?>