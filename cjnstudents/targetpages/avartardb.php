<?php 
/////////////////////////////// code snippet ///////////////////////////////
header("Content-Type:text/html");
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$teacherid=$_GET["teacherid"]; 
$studentid=$_GET["studentid"]; 
$mode=$_GET["mode"];  // standard, my (no selection)
$type=$_GET["type"]; 
$checkid=$_GET["checkid"]; 
$timecreated=time();

if($mode==NULL)$mode='my';
if($teacherid==NULL)$teacherid='$USER->id';

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$nowhiteboard='avartar';
if($mode==='my')
	{
	$avartardb=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk where type='$type' AND checkid='$checkid' AND standard=1 AND userid='$teacherid' ORDER BY id ASC LIMIT 10");
	$results= json_decode(json_encode($avartardb), True);
	unset($value);  
 
	foreach($results as $value)
		{
		$text=$value['text'];
		$talkid=$value['id'];
 		$talklist.='<tr><td><span onClick="Comment(\''.$studentid.'\',\''.$USER->id.'\',\''.$nowhiteboard.'\',\''.$text.'\',\''.$checkid.'\',\''.$type.'\')"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666659075.png" width=40px></span></td><td width=2%></td><td>'.$text.'</td><td></td><td ><span type="button"  onClick="Edittext(\''.$talkid.'\',\''.$text.'\')"><img style="margin-bottom:0;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=20></span></td><td><span onClick="Remove(\''.$talkid.'\')"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png" width=20px></span></td></tr>	      <tr><td><hr></td><td><hr></td><td ><hr></td><td ><hr></td><td ><hr></td><td ><hr></td></tr>'; 
		} 
	echo '<br><table width=100%><tr><td align=center><b> 피드백 목록 </b></td><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/avartardb.php?type='.$type.'&studentid='.$studentid.'&checkid='.$checkid.'&teacherid='.$teacherid.'&mode=all">My</a></td></tr></table><hr><table width=100%>'.$talklist.'</table>';
	}
elseif($mode==='all')
	{
	$avartardb=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk where type='$type' AND checkid='$checkid' AND standard=1  ORDER BY id ASC LIMIT 20");
	$results= json_decode(json_encode($avartardb), True);
	unset($value);  
 
	foreach($results as $value)
		{
		$talkid=$value['id'];
		$text=$value['text'];
		if($value['userid']==$USER->id)$talklist1.='<tr><td></td><td>'.$text.'</td><td></td><td width=2%> </td></tr>	      <tr><td><hr></td><td><hr></td><td ><hr></td><td ><hr></td></tr>'; 
		else $talklist2.='<tr><td></td><td>'.$text.'</td><td></td><td width=2%><span onClick="Selectitem(\''.$talkid.'\')"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1666754909.png" width=20px></span></td></tr>	      <tr><td><hr></td><td><hr></td><td ><hr></td><td ><hr></td></tr>'; 
		} 
	echo '<br><table width=100%><tr><td align=center><b> 사용 중인 피드백 </b></td><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/avartardb.php?type='.$type.'&studentid='.$studentid.'&checkid='.$checkid.'&teacherid='.$teacherid.'&mode=my">All</a></td></tr></table><hr><table width=100%>'.$talklist1.'</table>
<br><table width=100%><tr><td align=center><b> 피드백 가져오기 </b></td><td width=20%> </td></tr></table><hr><table>'.$talklist2.'</table>';
	}
echo '<script>
 
function Edittext(Talkid,Inputtext)
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
		url:"check_status.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'4\',
		"talkid":Talkid,
		"inputtext":text,	
		},
		success:function(data){
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	}
function Remove(Talkid)
	{
	swal("", "삭제되었습니다.", {buttons: false,timer: 500});
		$.ajax({
		url:"check_status.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'5\',
		"talkid":Talkid,
		},
		success:function(data){
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	
	}
function Comment(Wbcreator,Userid,Wboardid,Text,Checkid,Type)
	{ 
			$.ajax({
       				url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'41\',
				"wboardid":Wboardid,
				"wbcreator":Wbcreator,	
				"inputtext":Text,	
				"checkid":Checkid,
				"type":Type,
				"userid":Userid,
            	 		  },
 	  	 		success: function (data){  
				var talkid=data.talkid;
				setTimeout(function(){window.top.location.reload(); 	},10);  
  	   		   	}
			  });	
	}
	 
function Selectitem(Talkid)
	{ 
	alert(Talkid);
			$.ajax({
       			url: "check.php",
        			type: "POST",
        			dataType: "json",
        			data : { 
 				"eventid":\'44\',
				"talkid":Talkid,
            	 		  },
 	  	 		success: function (data){  
				var talkid2=data.talkid2;
				setTimeout(function(){location.reload(); },10);  
  	   		   	}
			  });	
	}

function editBoost(Userid,Wboardid,Wboardid0,Thisgid,Memo) // 기존 입력 메뉴로 선택 + 설명요청 키보드로 입력
	 	{		
		 Swal.fire({
		  html: \'<iframe  class="foo" scrolling="no" style="border: 0px none; z-index:2; width:97vw; height:50vh; margin-left: -0px;margin-right:0px;margin-top: +0px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/students/ratecomments.php?wboardid=\'+Wboardid+\'&wboardid0=\'+Wboardid0+\'&studentid=\'+Userid+\'&thisgid=\'+Thisgid+\'" ></iframe>\',
		  input: "textarea",
		  inputPlaceholder: Memo,
		  inputValue: Memo,
		  allowOutsideClick: false,
		  inputAttributes: {
		    "aria-label": "내용을 입력해 주세요"
		  },
		}).then(  	
			function(result) { 
					var Inputtext=result.value;	  
					 
					swal("", "보충설명 : " + Inputtext, {buttons: false,timer: 500});
						$.ajax({
						url: "check_status.php",
						type: "POST",
						dataType:"json",
					 	data : {
						"eventid":\'6\',
						"inputtext":Inputtext,	
						"userid":Userid,
						"wboardid":Wboardid,
						"wboardid0":Wboardid0,
						"thisgid":Thisgid,					 	 
						},
						success:function(data){
						var memo2 =data.memo2;
						setTimeout(function() {location.reload(); },1000);	
						 }
					 });		 	 
			});   	
		}
</script>'; 

	echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
	 ';

?>