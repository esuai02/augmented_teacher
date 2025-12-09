<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");
$mode=$_GET["mode"]; 
$studentid=$_GET["studentid"];   
echo '<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
 
// url 정보 이용하여 기간, 내용, 학생, 선생님 등 검색 가능하도록 *********************************************

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
if($mode==='CA')$coursetype='개념미션'; if($mode==='CB')$coursetype='심화미션'; if($mode==='CC')$coursetype='내신미션'; if($mode==='CD')$coursetype='수능미션';
 
$title='<b><a style="font-size:20px;color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>의 '.$coursetype.'</b>';
	// 분기목표
$plantype=$DB->get_records_sql("SELECT * FROM mdl_abessi_knowhow WHERE eventid='7128' AND course='$coursetype' AND active='1' ORDER BY timemodified ASC  ");  
$planlist= json_decode(json_encode($plantype), True);
 	
unset($value);  
foreach($planlist as $value)
	{	 
	$inputtype=$value['type'];	// 분기목표, 중간목표 .... , 교수법
 	$srcid=$value['id'];
	$plantext=$value['text']; 
	if($inputtype==='분기목표')$ninputtype=1;if($inputtype==='중간목표')$ninputtype=2;if($inputtype==='주간목표')$ninputtype=3;if($inputtype==='오늘목표')$ninputtype=4;if($inputtype==='활동조건')$ninputtype=5;if($inputtype==='사용법')$ninputtype=6;if($inputtype==='교수법')$ninputtype=7;
	$itemtitle='itemlist'.$ninputtype;
	$$itemtitle.='<table width=100% ><tbody><tr><td width=3%></td><td  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" width=3%> &nbsp;<span type="button"  onClick="addItem(\''.$srcid.'\',\''.$coursetype.'\',\''.$inputtype.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/IMAGES/additem.png  width=15></span></td><td style="color:#ff5050;"> '.$plantext.' <span type="button"  onClick="Edittext(\''.$srcid.'\',\''.$plantext.'\')"><img style="margin-bottom:8;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span onclick="uncheck(15,\''.$srcid.'\', this.checked)"><img style="margin-bottom:8px" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span> </td></tr></tbody></table>';
 	 
	$subitem=$DB->get_records_sql("SELECT * FROM mdl_abessi_knowhow WHERE srcid='$srcid' AND  eventid='8217' AND active='1' ORDER BY timemodified ASC ");  
	$subitemlist= json_decode(json_encode($subitem), True);
	$itemname='item'.$srcid;
	$$itemname='';
	unset($value2);  
	foreach($subitemlist as $value2)
		{
		$itemtext=$value2['text'];
		$itemid=$value2['id'];
		$knowhowlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhowlog WHERE itemid='$itemid' AND studentid='$studentid' ORDER BY id DESC ");  
		$checkstatus='';
		if($knowhowlog->active==1)$checkstatus='checked'; 
		$$itemname.='<tr style="height:30;"><td width=6%></td><td width=2%> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(14,\''.$studentid.'\',\''.$srcid.'\',\''.$itemid.'\', this.checked)"/></td><td>&nbsp;  '.$itemtext.' <span type="button"  onClick="Edittext(\''.$itemid.'\',\''.$itemtext.'\')"><img style="margin-bottom:8;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=15></span> <span onclick="uncheck(15,\''.$itemid.'\', this.checked)"><img style="margin-bottom:8px" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span></td><td></td></tr>';
		}
	$$itemtitle.='<table width=100%><tbody>'.$$itemname.'</tbody></table><br>';	
	} 
 
 
$ndays=(INT)($tb/86400); // <span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',$(\'#basic1\').val())"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647218910.png" width=30></span>
echo '<table width=100%><tr><td align=center width=10%></td><td width=10%></td><td>'.$title.'<b style="font-size:20;"> &nbsp;맞춤형 목표관리 및 학습흐름 조절</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-size:10;"> powered by Chojineung Inc. </span>  
 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode='.$mode.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647161635.png width=40></a>  </td><td width=10%></td></tr></table><hr>
<table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody><tr><td width=5%></td><td valign=top width=45%><b style="font-size:20;"> &nbsp;목표설정 선택</b></td><td width=5%></td><td valign=top width=45%><b style="font-size:20;"> 학습흐름 조절</b></td></tr>
<tr><td width=5%></td><td valign=top width=45%><hr></td><td width=5%><hr></td><td valign=top width=45%><hr></td></tr>
<tr><td width=5%></td><td valign=top width=45%><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'분기목표\')"><img style="margin-bottom:5px;" style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span><b style="color:#009933;"> 분기목표 </b><br><br>'.$itemlist1.'<hr><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'중간목표\')"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span> <b style="color:#009933;"> 중간목표 </b><br><br>'.$itemlist2.'<hr><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'주간목표\')"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span> <b style="color:#009933;"> 주간목표 </b><br><br>'.$itemlist3.'<hr><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'오늘목표\')"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span> <b style="color:#009933;"> 오늘목표 </b><br><br>'.$itemlist4.'</td><td width=5%></td>
<td valign=top width=45%><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'활동조건\')"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span> </span><b style="color:#009933;"> 활동조건 </b><br><br>'.$itemlist5.'<hr><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'사용법\')"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span> </span><b style="color:#009933;"> 사용법 </b><br><br>'.$itemlist6.'<hr><span type="button"  onClick="addMenu(\''.$teacherid.'\',\''.$coursetype.'\',\'교수법\')"><img style="margin-bottom:5px;"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647520194.png" width=20></span> </span><b style="color:#009933;"> 교수법 </b><br><br>'.$itemlist7.'</td></tr></tbody></table> ';
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
	include("quicksidebar.php");
 

echo '
<script>	
function addMenu(Userid,Mtype,Stype)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: Mtype + " " + Stype + " 기준 입력",
 	input: "textarea", 
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: Mtype + " " + Stype + " 설정 시 필요한 기준을 입력해 주세요",
  	inputAttributes: {
   	 "aria-label": "Type your message here", Height:500,
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
 		"eventid":\'12\',
		"course":Mtype,
		"type":Stype,
		"inputtext":text,	
		"userid":Userid,
		},
		success:function(data){
		var Teacherid=data.teacherid;
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	}
function addItem(Sid,Mtype,Stype)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "선택메뉴 입력",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: Mtype+" "+ Stype+" 설정 시 필요한 항목을 입력해 주세요",
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
 		"eventid":\'13\',
		"inputtext":text,	
		"srcid":Sid,
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
 		"eventid":\'18\',
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
function ChangeCheckBox(Eventid,Userid, Srcid, Itemid, Checkvalue){
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
            		"srcid":Srcid,
            		"itemid":Itemid,
            	 	"checkimsi":checkimsi,
               	
            	 	  },
 	 	      success: function (data){  
  	   	   }
		  });
		}
function uncheck(Eventid,Itemid, Checkvalue){
					swal({
					text: "항목을 삭제하시겠습니까 ?",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'취소\',
							className: \'btn btn-danger\'
						}      			

					}
		}).then((willDelete) => {
					if (willDelete) {
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
            		"itemid":Itemid,
            	 	"checkimsi":checkimsi,               	
            	 	  },
 	 	      success: function (data){  
  	   	   }
		  });
					setTimeout(function() {location.reload(); },100);
					} else {
					swal("취소되었습니다.", {buttons: false,timer: 500});
					}
				});	

		}
</script> 


<style>
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
?>