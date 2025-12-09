<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

//if($USER->id!=2)exit;
global $DB, $USER;
$studentid= $_GET["userid"];
if($studentid==NULL)$studentid=$USER->id;

$timecreated=time(); 
$hoursago12=$timecreated-43200;
$aweekago=$timecreated-604800;
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;  
$timeline=$DB->get_records_sql("SELECT * FROM mdl_abessi_timeline WHERE userid='$studentid'  AND timecreated> '$hoursago12'   ORDER BY id DESC");
$nstamp=0;$nwandering1=0;$nwandering2=0;$nwandering3=0;
$result = json_decode(json_encode($timeline), True);
unset($value); 
foreach($result as $value)
	{
	$wboardid=$value['wboardid']; 
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where wboardid='$wboardid' ORDER BY id DESC LIMIT 1");

	if(strpos($wboardid, 'cjnNotepageid')!== false)echo '';
	elseif(strpos($wboardid, 'jnrsorksqcrark')!==false && $thisboard->nstroke<3)continue;

	$nstamp++;
	if($nstamp==2 && $value['tstamparray'] == NULL)$nupdate=1;
	
    $thisid=$value['id'];
	
    $tcreated=$value['timecreated'];
    
    $fontsize=10+$value['nview'];
	if($fontsize>50)$fontsize=50;
    $timestamp=date('H:i:s',$tcreated);
    $timestayed=$tprev-$tcreated;
    if($timestayed/60>15)$timestamp='<b style="font-size:20px;">'.$timestamp.'</b>';
    elseif($timestayed/60>10)$timestamp='<b style="font-size:17px;">'.$timestamp.'</b>';
    elseif($timestayed/60>5)$timestamp='<b style="font-size:14px;">'.$timestamp.'</b>';

    $durationcolor = "rgb(" . ($timestayed) . ", " . (255 - $timestayed) . ", 0)";

    $gidarray = $value['gidarray']; // 예: "6,7,8,9,10,11,12,13,14,15,16"
    $tstamparray = $value['tstamparray']; // 예: "6,1,8,2,3,11,12,13,14,15,16"
    $snapshots='';
    $generate_ids = explode(",", $gidarray);
    $timegaps = explode(",", $tstamparray);

    for ($i = 0; $i < count($generate_ids); $i++) 
        {
        $generate_id = $generate_ids[$i];
        $timegap = $timegaps[$i];
        
        $testwbid = "your_wbid"; // 적절한 값으로 설정
        $barWidth = $timegap*250/60; // 폭을 $timegap 값에 비례하게 설정

        if($barWidth>250)$barWidth=250;
        if($timegap==7128 && $barWidth>250)
			{
			$color = "rgb(0,0,0)";
			$barWidth=50;
			}
		elseif($timegap==7128)
			{
			$color = "rgb(0,0,0)";
			$barWidth=25;
			}
		else 
			{
			$barWidth=$barWidth*1/5;
			$color = "rgb(" . ($barWidth * 5) . ", " . (255 - $barWidth * 5) . ", 0)";
			}
		if($barWidth<2)$barWidth=2;
        $snapshots.='<span type="button" onMouseOver="showMoment(\''.$wboardid.'\',\''.$generate_id.'\')" style="display:inline-block; width:'.$barWidth.'px; height:20px; background-color:'.$color.'; margin-right:5px;"></span>';
        }
 
    if(strpos($wboardid, 'cjnNotepageid')!== false && ($value['gidarray'] == NULL || empty($value['gidarray'])))
        {
        $sql = "SELECT * FROM replaydb_trans where userid='$studentid' AND encryption_id='$wboardid' AND timecreated > '$tcreated' AND timecreated < '$tprev'  ORDER BY id ASC";  
        $gidarray='';
        $tstamparray='1,';
        $rs = mysqli_query($conn, $sql);
        while($info = mysqli_fetch_array($rs))
            {
            $strokegap=$info['timecreated']-$tstroke_prev;
            $tstroke_prev=$info['timecreated'];
            if($strokegap>=0 && $strokegap<43200)
                {
                $gidarray.=$info['generate_id'].',';
				$tstamparray.=$strokegap.',';
                }
            }
       
        $DB->execute("UPDATE {abessi_timeline} SET gidarray='$gidarray',tstamparray='$tstamparray' WHERE id='$thisid' ORDER BY id DESC LIMIT 1 ");
       // header("Refresh:0");
        }
	elseif($value['gidarray'] == NULL || empty($value['gidarray']))
        {
        $sql = "SELECT * FROM boarddb where encryption_id='$wboardid' AND timecreated > '$tcreated' AND timecreated < '$tprev'  ORDER BY id ASC";  
        $gidarray='';
        $tstamparray='1,';
        $rs = mysqli_query($conn, $sql);
        while($info = mysqli_fetch_array($rs))
            {
            $strokegap=$info['timecreated']-$tstroke_prev;
            $shapedata=$info['shape_data'];
            $tstroke_prev=$info['timecreated'];
            if($strokegap>=0 && $strokegap<43200)
                {
                $gidarray.=$info['generate_id'].',';
				if(strpos($shapedata, 'eraser')!== false) $tstamparray.='7128,';
				else $tstamparray.=$strokegap.',';
                }
               // echo 'gid'.$gidarray.'tstamp'.$tstamparray.'<br>';
            }
       
        $DB->execute("UPDATE {abessi_timeline} SET gidarray='$gidarray',tstamparray='$tstamparray' WHERE id='$thisid' ORDER BY id DESC LIMIT 1 ");
       // header("Refresh:0");
        }
    $tprev=$tcreated;

	// 시각화 부
	if(strpos($wboardid,'cjnNotepageid')!== false)
		{
	 	$cntwbid= substr($wboardid, 0,strpos($wboardid, "_user"));
		$cnturl='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$cntwbid.'&studentid='.$studentid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/play.png" width=15></a>';
		}
	elseif(strpos($wboardid, 'jnrsorksqcrark')!== false)
		{
 		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$thisboard->contentsid' "); // 전자책에서 가져오기
		$ctext=$getimg->pageicontent;
		if($getimg->reflections!=NULL)$reflections=$getimg->reflections.'<hr>';
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
		$cnturl='<a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$thisboard->url.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15></a>';
		} 
	else
		{
		$qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$thisboard->contentsid' ");
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
			}
		$cnturl='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/play.png" width=15></a>';
		}
        if($timestayed<20)
			{
			$nwandering1++;
			continue;
			}
		elseif($timestayed<40)$nwandering2++;
		else $nwandering3++;

		$timelog.='<tr><td width=8%  style="background-color:white; font-size:15px; white-space:nowrap; color:'.$durationcolor.';overflow: hidden; text-overflow: ellipsis;"  align=center>   '.$timestamp.'   ('.round($timestayed/60,1).')</td><td>'.$value['status'].'</td><td style="font-size:'.$fontsize.';">'.$value['nview'].'</td><td width=5% align=center><div class="tooltip3">  <span onMouseOver="Swal.close();"><a style="color:#000000;text-decoration:none;font-size:'.$fontsize.'px;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/focused_analysis.php?id='.$wboardid.'&userid='.$studentid.'">'.iconv_substr($wboardid, 0, 20, "utf-8").'</a></span> <span class="tooltiptext3"><table><tr><td> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td align=center>'.$cnturl.'</td><td>'.$snapshots.'</td></tr>'; 
	} 

	$nwandering=$nwandering1+$nwandering2+$nwandering3;
	$nwandering1=round($nwandering1/$nwandering*100,0);
	$nwandering2=round($nwandering2/$nwandering*100,0);
	$nwandering3=round($nwandering3/$nwandering*100,0);
	if($nupdate==1)echo '<meta http-equiv="refresh" content="0">';
	
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

	<table align=center width=90%><tr><th valign=top><div class="table-wrapper"><table width=100%><thead><tr><th style=" white-space:nowrap; color:black;overflow: hidden;" width=1%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?userid='.$studentid.'"><img style="margin-bottom:0px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=30></a></th><th width=1%></th><th style="color:#1956FF;font-size:10px;white-space:nowrap; color:black;overflow: hidden;" width=3%><a style="text-decoration:none;color:#1956FF;font-size:10px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$stdname.'</a> </><th width=5%></th><th width=5%></th><th>nw1='.$nwandering1.'% |  nw2='.$nwandering2.'% |  nw3='.$nwandering3.'%<table align=center><tr>'.$examplenote.'</tr></table></th></tr></thead><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$timelog.'</table></div></th></tr></table><br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> ';
	
echo '<table width=90% align=center>
<tr><td># 의심활동 선택 >> 공부순서 교정 >> 비효율적 피드백 감소 >> 학습루틴 고도화 >> 인지리듬 개선 >> 학습 기울기 상승 >> 성적향상</td></tr></table>';
if($USER->id==2)echo '<table width=90% align=center><tr><td># 순서교정 :  개념요약 >> 개념이해 >> 개념체크 >> 개념퀴즈 >> 대표유형 >> 기억인출 >> 대표유형 확인 테스트 >> 단원별 테스트</td></tr>
<tr><td># 자동추천 알고리즘 적용. preset 제공 후 업데이트 환경. 1. 학습상황 구조화,   2. 학생정보 구조화  3. 추천 컨텐츠 구조화</td></tr>
</table> ';

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

function showMoment(Wbid,Gidmax)
    {
    Swal.fire({
    backdrop: false,position:"bottom-left",showCloseButton: true,
    width: 600,
    height:900,
    allowOutsideClick:false, // 팝업 바깥 클릭 시 창이 닫히도록 설정
    customClass: {
        popup: "custom-popup-class" // 팝업에 적용할 사용자 정의 클래스
    },
      html:
        \'<iframe style="border: 1px none; z-index:2; width:600;height:900px;  margin-left: 0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_review.php?id=\'+Wbid+\'&gidmax=\'+Gidmax+\'" ></iframe>\',
      showConfirmButton: false,
            })
    }
 
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
    .custom-popup-class {
        transform: translate3d(0, 0, 0) !important; /* 흔들림 방지 */
        animation: none !important; /* 애니메이션 비활성화 */
        opacity: 1 !important; /* 불투명도 설정 */
    }

	.table-wrapper {
		position: relative;
		height: 100% /* 테이블이 표시될 영역의 높이를 지정하세요 */
		overflow: auto;
	  }
	  
	  .table-wrapper thead {
		position: sticky;
		top: 0;
		background-color: #BCD5FF; /* 첫 번째 행의 배경색을 지정하세요 */
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
