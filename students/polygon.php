<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid = $_GET["studentid"]; 
$fid = $_GET["fid"]; 

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

// 자세 피드백을 통하여 사용법을 교정

include("flowexpressions.php");
$sex=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='107' "); 
$usersex=$sex->data;
if($usersex==='여')$usersex='woman';
else $usersex='man';
 
$current=$DB->get_record_sql("SELECT * FROM mdl_abessi_mcupdate where userid LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");
for($nchk=1;$nchk<=40;$nchk++)
	{
	$colstr='c'.$nchk;
	if($current->$colstr==0)
		{
		$ncur=$nchk; 
		break;
		}
	}
	
$DB->execute("UPDATE {abessi_mcupdate} SET ncur='$ncur' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");   
$itmid=$DB->get_record_sql("SELECT * FROM mdl_abessi_mcpreset where userid LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");
$colstr1='c'.($ncur-1);
$colstr2='c'.$ncur;
$nmc1=$itmid->$colstr1;
$nmc2=$itmid->$colstr2;

$lasttrystr='todoitem'.$nmc1;
$nexttrystr='todoitem'.$nmc2;
$lasttry=$$lasttrystr;
$nexttry=$$nexttrystr;

if($ncur<=4)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/41.png" height=200>';
elseif($ncur<=8)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/42.png" height=200>';
elseif($ncur<=12)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/43.png" height=200>';
elseif($ncur<=16)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/44.png" height=200>';
elseif($ncur<=20)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/45.png" height=200>';
elseif($ncur<=25)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/46.png" height=200>';
elseif($ncur<30)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/47.png" height=200>';
elseif($ncur==30)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/477.png" height=200>';
elseif($ncur<35)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/48.png" height=200>';
elseif($ncur==35)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/488.png" height=200>';
elseif($ncur<40)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/49.png" height=200>';
elseif($ncur==40)$cjnimg='<img src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/'.$usersex.'/499.png" height=200>';

$history2=$DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog where userid LIKE '$studentid' AND id > '$fid' ORDER BY id ASC LIMIT 1");
 
$timestamp1=$history->timecreated;
$timestamp2=$history2->timecreated;
if($timestamp2==NULL)$timestamp2=time();
$chart= '<table align=center><tr><td><div class="chart-container" style="width: 500px; height:500px;horizontal-align: center;"><canvas  id="radarChart"></canvas></div></td></tr></table>';


//$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE  creator LIKE '$studentid'  AND timecreated>'$timestamp1' AND timecreated < '$timestamp2' AND text !='' ORDER BY id ASC ");  
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE  creator LIKE '$studentid'  AND timecreated < '$timestamp2' AND text !='' ORDER BY id DESC LIMIT 30");  
$talklist= json_decode(json_encode($share), True);

unset($value);  
foreach($talklist as $value)
	{
	$sharetext=$value['text'];
	$type=$value['type'];
	$talkcreator=$value['userid'];
	$wboardid=$value['wboardid'];
	$crname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$talkcreator' ");	
	$creatorname='<a style="text-decoration: none; color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$talkcreator.'&fid='.$history->id.'"target="_blank">'.$crname->firstname.$crname->lastname.'</a>';

	$tcreated1=date("m월d일 h:i A", $value['timecreated']);   

	

	$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$talkcreator' AND fieldid='22' "); 
	$role2=$userrole2->role;
	if($role2==='student')$bubblestr='bubble';
	else $bubblestr='bubble2';

	$getauthor=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1 "); 
	$contentsid=$getauthor->contentsid;
	$seewb='';
	
	$seewb='<a  style="text-decoration: none; color:#07a2f0;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$value['creator'].'&type='.$type.'"target="_parent">'.$type.'</a>'; 
	$sharelist.='<tr><td width=3% style="white-space: nowrap; text-overflow: ellipsis;" valign=top> <span>'.$creatorname.'</span></td> <td width=1%></td>
	<td style="overflow:auto;" valign=center><div class="'.$bubblestr.'">'.$sharetext.' </div></td> <td width=10%  valign=top>'.date("m/d", $value['timecreated']).'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top>'.$seewb.'</td></tr>';
	}

echo '<table align=center width=90%><tr><td width=50% valign=top>'.$chart.'<br><table align=center><tr><td align=center>'.$cjnimg.'<hr> 최근정복 : '.$lasttry.'<hr> 현재실행 : '.$nexttry.'<hr></td></tr></table></td><td width=50%  valign=top>
<table align=center><tr><th align=left><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> | CJN 메타인지 | <a style="color:black;"  href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=목표"target="_parent">목표</a> | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=순서"target="_parent">순서</a> | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=기억"target="_parent">기억</a> | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=몰입"target="_parent">몰입</a>
 | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=발상"target="_parent">발상</a> | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=해석"target="_parent">해석</a> | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=숙달"target="_parent">숙달</a> | <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=효율"target="_parent">효율</a>  &nbsp;  '.$view.' </th></tr></table>
<hr><table>'.$sharelist.'</table></td></tr></table>';

echo '
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, -->
	<script src="../assets/js/setting-demo.js"></script>';
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
 <style>

<style>
.bubble
{
position: relative;
width: 390px;
height: auto;
min-height:35px;
padding: 5px;
background: #B8FFFF;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: transparent #B8FFFF;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 12px;
}

.bubble2
{
position: relative;
width: 390px;
height: auto;
min-height:35px;
padding: 5px;
background: #99ccff;
-webkit-border-radius: 10px;
-moz-border-radius: 10px;
border-radius: 10px;

}

.bubble2:after
{
content: "";
position: absolute;
border-style: solid;
border-width: 16px 29px 16px 0;
border-color: white;
display: block;
width: 0;
z-index: 1;
left: -29px;
top: 12px;
}

a:link {
  color : red;
}
a:visited {
  color :grey;

}
a:hover {
  color : blue;
}
a:active {
  color : purple;
}

.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 800px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip1:hover .tooltiptext1 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 500px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 

.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip3 .tooltiptext3 {
    
  visibility: hidden;
  width:700px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 700px;
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



<script>

var radarChart = document.getElementById('radarChart').getContext('2d');
var Flow1= "<?php echo $flow1;?>";
var Flow2= "<?php echo $flow2;?>";
var Flow3= "<?php echo $flow3;?>";
var Flow4= "<?php echo $flow4;?>";
var Flow5= "<?php echo $flow5;?>";
var Flow6= "<?php echo $flow6;?>";
var Flow7= "<?php echo $flow7;?>";
var Flow8= "<?php echo $flow8;?>";
 

var myradarChart = new Chart(radarChart, {
			type: 'radar',
			data: {
				labels: ['목표', '순서', '기억', '몰입', '해석','논리','숙달','효율'],
				datasets: [{
					data: [Flow1, Flow2, Flow3, Flow4, Flow5, Flow6, Flow7, Flow8],
					borderColor: '#1d7af3',
					backgroundColor : 'rgba(29, 122, 243, 0.25)',
					pointBackgroundColor: "#1d7af3",
					pointHoverRadius: 4,
					pointRadius: 5,
					label: '몰입지표'
				},  
				
				]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: 'bottom'
				}
			}
		});
 

function EvaluateFlow() 
{
  let wrap = document.createElement('div');
  wrap.setAttribute('class', 'text-muted');
  wrap.innerHTML = '다음의 평가기준을 참고하여 플로우 평가를 선택해 주세요 (갯수선택)<hr>'+ Rubric +'<hr><button onclick="reply(\'level1\',\'1\')" type="button" value="level1" class="btn feel">+1<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009610001.png" width=30 height=30></button><button onclick="reply(\'level2\',\'2\')" type="button" value="level2" class="btn feel">+2 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009642001.png" width=30 height=30></button><button onclick="reply(\'level3\',\'3\')" type="button" value="level3" class="btn feel">+3 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009715001.png" width=30 height=30></button><button onclick="reply(\'level4\',\'4\')" type="button" value="level4" class="btn feel">+4 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009756001.png" width=30 height=30></button><button onclick="reply(\'level5\',\'5\')" type="button" value="level5" class="btn feel">+5 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009790001.png" width=30 height=30></button><hr>' ;
swal({
    title: "플로우 평가 ("+Type+")",
    closeOnClickOutside: false,
    content: {
      element: wrap
    },
    buttons: {
      confirm: {
        text:"취소",
        visible: true,
        className: "btn btn-default",
        closeModal: true,
      }
    },
  }).then((value) => {
    if (value === 'level1') {
      swal("+1이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level2') {
      swal("+2이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level3') {
      swal("+3이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level4') {
      swal("+4이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level5') {
      swal("+5이 선택되었습니다.", {
        icon: "success",
        buttons: false
      });
    }
  });
}

function reply(feel,resultValue){
	var Userid= "<?php echo $studentid;?>";
	var Tutorid= "<?php echo $USER->id;?>";
	var Type= "<?php echo $type;?>";
	var Eventid="104";
swal.setActionValue(feel);
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":Eventid,
	"userid":Userid,
	"tutorid":Tutorid,
	"type":Type,
 	"value":resultValue,
	},
	success:function(data){
	 }
	 })
swal("플로우 평가결과가 업데이트 되었습니다.", {buttons: false, timer: 2000, });
setTimeout(function() {location.reload(); },1000);	
}

</script>


