<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;  
 
 
$studentid=$_GET["studentid"]; 
$wboardid=$_GET["wboardid"]; 
$wboardid0=$_GET["wboardid0"]; 
$thisgid=$_GET["thisgid"]; 

if($studentid==NULL)$studentid=$USER->id;

$cmtlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_comments WHERE wboardid0='$wboardid0' ORDER BY nvote DESC LIMIT 5"); // missiontype으로 mission 종류 선택
 
$result = json_decode(json_encode($cmtlist), True);
unset($value);
foreach($result as $value)
	{
	$thismemo=$value['text'];
	$nvote=$value['nvote'];
	$thisgid0=$value['generate_id'];
	$srcwb=$value['wboardid'];
	$commentlist.='<tr><td> '.$thismemo.' </td><td>('.$nvote.')</td><td><span onclick="SelectWhy(\''.$studentid.'\',\''.$wboardid.'\',\''.$wboardid0.'\',\''.$srcwb.'\',\''.$thisgid.'\',\''.$thisgid0.'\',\''.$thismemo.'\')"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656835432.png" width=25></span></td></tr>';
	}

echo '
<script>
function SelectWhy(Userid,Wboardid,Wboardid0,Srcwb,Thisgid,Thisgid0,Memo) // 학생 데이터 수집
 	{			
	 
	swal("", "적용되었습니다.", {buttons: false,timer: 5000});
		$.ajax({
		url: "../bessiboard/check_status.php",
		type: "POST",
		dataType:"json",
		data : {
		"eventid":\'6\',
		"inputtext":Memo, 
		"userid":Userid,
		"wboardid":Wboardid,
		"wboardid0":Wboardid0,
		"srcwb":Srcwb,
		"thisgid":Thisgid,	
		"thisgid0":Thisgid0,					 	 
		},
		success:function(data){
		var memo2 =data.memo2;
		setTimeout(function() {window.top.location.reload(); },1000);	
		 }
	});	

	}
</script>';

 
echo '<table width=60%>'.$commentlist.'</table>';
 
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
 ';
?>