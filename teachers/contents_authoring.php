<?php 
/////////////////////////////// code snippet ///////////////////////////////
include("navbar.php");
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("../bessiboard/dbcon.php");
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$cntlist1='<tr><td>제작자</td><td>링크</td><td>수정</td><td>생성</td><td>의견</td><td> </td></tr>';
$tracking='<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
$cnt1=$DB->get_records_sql("SELECT * FROM mdl_abessi_orchestration WHERE type LIKE 'topic' ORDER BY timecreated DESC  "); 
$cnt2=$DB->get_records_sql("SELECT * FROM mdl_abessi_orchestration WHERE type LIKE 'interpret'  ORDER BY timemodified  DESC  "); 
$cnt3=$DB->get_records_sql("SELECT * FROM mdl_abessi_orchestration WHERE type LIKE 'question' ORDER BY timemodified  DESC  "); 
$result1= json_decode(json_encode($cnt1), True);
$result2= json_decode(json_encode($cnt2), True); 
$result3= json_decode(json_encode($cnt3), True);

$ncnt1=count($result1);
$ncnt2=count($result2);
$ncnt3=count($result3);
$rate1=round($ncnt1/2000*100,1);
$rate2='##';
$rate3='##';
 
unset($value1);
 
foreach($result1 as $value1)
	{
	$userid=$value1['userid'];
	$userinfo= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$authorname=$userinfo->firstname.$userinfo->lastname;
	$tcreated=round(  ( time()-$value1['timemodified'])/86400,0);
	$tfirstcreation=date("Y년 m월d일 h시m분", $value1['timecreated']);
 	$cntlist1.='<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" width=3%>'.$authorname.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" width=15%><h6><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?'.$value1['url'].'">'.$value1['wboardid'].'</a><h6></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" width=5%>'.$tcreated.'일 전</td><td width=10%  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$tfirstcreation.'</td><td style="color:#3399ff;"  valign=top>'.$sharetext.'</td><td width=5%><span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:5;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span type="button"  onClick="reportData(\''.$studentid.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=25></span></td></tr>';
	}

echo '<h5>
 &nbsp;&nbsp; &nbsp;&nbsp;  <b>컨텐츠 제작 현황</b> </h5> <hr>
<table align=center valign=top width=90%>'.$cntlist1.'</table>';
/*
echo '<h5>
 &nbsp;&nbsp; &nbsp;&nbsp;  <b>컨텐츠 제작 현황</b> </h5> <hr>
<table align=center valign=top>  <thead>
 <tr><th scope="col">개념 컨텐츠 '.$ncnt1.' 점 ('.$rate1.' %)</th> <th width=3%></th><th scope="col">해석 컨텐츠 '.$ncnt2.' 점 ('.$rate2.' %)</th> <th width=3%></th><th scope="col">풀이 컨텐츠 '.$ncnt3.' 점 ('.$rate3.' %)</th></tr>
<tr><th scope="col"><hr></th><th scope="col"><hr></th><th scope="col"><hr></th><th scope="col"><hr></th><th scope="col"><hr></th><th scope="col"><hr></th></tr>
<tr><th scope="col"><table>'.$cntlist1.'</table></th><th scope="col"></th><th scope="col"></th><th scope="col"></th><th scope="col"></th><th scope="col"></th></tr>
</tbody></table>  
 ';
*/

 echo '
<script>	
function reportData(Userid,Sid,Username)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "의견에 대한 답변",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: "내용을 입력해 주세요",
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
 

function Edittext(Itemid,Inputtext)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "Comment 전달",
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

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
 ';
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
echo '</div>
<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
</p></div><div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"><p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>
<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
</div></div></div></div>

<script>
function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
	    var checkimsi = 0;
	    if(Checkvalue==true){
	       checkimsi = 1;
 	   }
  	 $.ajax({
  	      url: "check.php",
   	     type: "POST",
   	     dataType: "json",
   	     data : {"userid":Userid,
   	             "questionid":Questionid,
   	             "attemptid":Attemptid,
     	           "checkimsi":checkimsi,
    	             "eventid":Eventid,
    	           },
  	      success: function (data){  
    	    }
	    });
	}


function ChangeCheckBox2(Eventid,Userid, Goalid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
	checkimsi = 1;
	}
	$.ajax({
	url: "../students/check.php",
	type: "POST",
	dataType: "json",
	data : {"userid":Userid,       
	"goalid":Goalid,
	"checkimsi":checkimsi,
	"eventid":Eventid,
	},
	success: function (data){  
	}
	});
	 window.open("https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id="+Userid+"&tb=43200");
	}
function ChangeCheckBox3(Eventid,Userid, Wboardid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
	checkimsi = 1;
	}
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
 
	}


 
function askstudent(Eventid,Studentid,Teacherid,Questionid)
	{
    	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"studentid":Studentid,
		"teacherid":Teacherid,
		"contentsid":Questionid,       	   
		      },
	 	success:function(data){
		}
	})
	}
</script> 
  
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

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
';
 
echo '
<style>
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
  width: 600px;
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
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 800px;
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
include("quicksidebar.php");
?>