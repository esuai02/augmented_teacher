<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$teacherid=required_param('id', PARAM_INT); 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role!=='student')echo '';
else
	{
	echo '접근권한이 없습니다.';
	exit();
	}
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$studentname=$username->firstname.$username->lastname;
$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$teacherid' AND fieldid='22' "); 
$role2=$userrole2->role; 

$teachingMode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='102' "); //수업방식 102
$setMode1 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='97' "); //커리큘럼 97
$setMode2 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='98' "); //학습관리 98
$setMode3 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='99' "); //인지촉진 99
$setMode4 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='100' "); //인지성장 100
$setMode5 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='101' "); //상담스킬 101

$ticon =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); //고유아이콘 
$assessmode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='82' "); // 자동채점 
$networkstatus = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='69' "); //네트워킹 상태 
$networklevel =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='71' "); //네트워킹 레벨 
$team =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='103' "); //팀명
 
echo '<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center ><td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><table align=center style="1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><tr><td><b> We transfer intelligence with CJN scaffolding</b> </td><td  width=5% ></td><td style="font-size:14px;">  KAIST TOUCH MATH powered by CJN</td></tr></table></td></tr></table></div></div> <br> <br> ';
 
  
if($teachingMode->data==='메타버스')$TMselectstate1='selected'; elseif($teachingMode->data==='블렌디드')$TMselectstate2='selected'; elseif($teachingMode->data==='오프라인')$TMselectstate3='selected';  
if($setMode1->data==='LEVEL1')$smaselectstate1='selected'; elseif($setMode1->data==='LEVEL2')$smaselectstate2='selected'; elseif($setMode1->data==='LEVEL3')$smaselectstate3='selected'; elseif($setMode1->data==='LEVEL4')$smaselectstate4='selected';elseif($setMode1->data==='LEVEL5')$smaselectstate5='selected'; 
if($setMode2->data==='LEVEL1')$smbselectstate1='selected'; elseif($setMode2->data==='LEVEL2')$smbselectstate2='selected'; elseif($setMode2->data==='LEVEL3')$smbselectstate3='selected'; elseif($setMode2->data==='LEVEL4')$smbselectstate4='selected';elseif($setMode2->data==='LEVEL5')$smbselectstate5='selected'; 
if($setMode3->data==='LEVEL1')$smcselectstate1='selected'; elseif($setMode3->data==='LEVEL2')$smcselectstate2='selected'; elseif($setMode3->data==='LEVEL3')$smcselectstate3='selected'; elseif($setMode3->data==='LEVEL4')$smcselectstate4='selected';elseif($setMode3->data==='LEVEL5')$smcselectstate5='selected'; 
if($setMode4->data==='LEVEL1')$smdselectstate1='selected'; elseif($setMode4->data==='LEVEL2')$smdselectstate2='selected'; elseif($setMode4->data==='LEVEL3')$smdselectstate3='selected'; elseif($setMode4->data==='LEVEL4')$smdselectstate4='selected';elseif($setMode4->data==='LEVEL5')$smdselectstate5='selected'; 
if($setMode5->data==='LEVEL1')$smeselectstate1='selected'; elseif($setMode5->data==='LEVEL2')$smeselectstate2='selected'; elseif($setMode5->data==='LEVEL3')$smeselectstate3='selected'; elseif($setMode5->data==='LEVEL4')$smeselectstate4='selected';elseif($setMode5->data==='LEVEL5')$smeselectstate5='selected'; 

if($assessmode->data==='OFF')$asmodeselectstate1='selected'; elseif($assessmode->data==='ON')$asmodeselectstate2='selected'; elseif($assessmode->data==='AUTO')$asmodeselectstate3='selected';  elseif($assessmode->data==='AI')$asmodeselectstate4='selected';  
if($networkstatus->data==1)$nsselectstate1='selected'; elseif($networkstatus->data==2)$nsselectstate2='selected'; elseif($networkstatus->data==3)$nsselectstate3='selected'; elseif($networkstatus->data==4)$nsselectstate4='selected';elseif($networkstatus->data==5)$nsselectstate5='selected'; 
if($networklevel->data==1)$nlselectstate1='selected'; elseif($networklevel->data==2)$nlselectstate2='selected'; elseif($networklevel->data==3)$nlselectstate3='selected'; elseif($networklevel->data==4)$nlselectstate4='selected';elseif($networklevel->data==5)$nlselectstate5='selected'; 
if($team->data==='TEAM01')$grselectstate1='selected'; elseif($team->data==='TEAM02')$grselectstate2='selected'; elseif($team->data==='TEAM03')$grselectstate3='selected'; elseif($team->data==='TEAM04')$grselectstate4='selected';elseif($team->data==='TEAM05')$grselectstate5='selected'; elseif($team->data==='TEAM06')$grselectstate6='selected'; elseif($team->data==='TEAM07')$grselectstate7='selected';elseif($team->data==='TEAM08')$grselectstate8='selected';elseif($team->data==='TEAM09')$grselectstate9='selected';elseif($team->data==='TEAM10')$grselectstate10='selected'; 
elseif($team->data==='TEAM11')$grselectstate11='selected'; elseif($team->data==='TEAM12')$grselectstate12='selected'; elseif($team->data==='TEAM13')$grselectstate13='selected'; elseif($team->data==='TEAM14')$grselectstate14='selected';elseif($team->data==='TEAM15')$grselectstate15='selected'; elseif($team->data==='TEAM16')$grselectstate16='selected'; elseif($team->data==='TEAM17')$grselectstate17='selected';elseif($team->data==='TEAM18')$grselectstate18='selected';elseif($team->data==='TEAM19')$grselectstate19='selected';elseif($team->data==='TEAM20')$grselectstate20='selected'; 
 

$inputdata1='<table> 
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">고유 아이콘</td><td><div><input  style="font-size:20px;" type="text" class="form-control input-square" id="squareInput2" name="squareInput2"  placeholder="'.$ticon->data.'" value="'.$ticon->data.'" ></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">ASSIST MODE</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic2" name="basic2" class="form-control" ><option value="OFF"  '.$asmodeselectstate1.'>OFF</option><option value="ON"  '.$asmodeselectstate2.'>ON</option><option value="AUTO"  '.$asmodeselectstate3.'>AUTO</option><option value="AI"  '.$asmodeselectstate4.'>AI</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">수업방식 유형</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic6" name="basic6" class="form-control" ><option value="메타버스"  '.$TMselectstate1.'>메타버스</option><option value="블렌디드"  '.$TMselectstate2.'>블렌디드</option><option value="오프라인" '.$TMselectstate3.'>오프라인</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
</table>'; 

$inputdata2='<table>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">소속팀</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic5" name="basic5" class="form-control" ><option value="TEAM01"  '.$grselectstate1.'>TEAM01</option><option value="TEAM02"  '.$grselectstate2.'>TEAM02</option><option value="TEAM03" '.$grselectstate3.'>TEAM03</option><option value="TEAM04" '.$grselectstate4.'>TEAM04</option><option value="TEAM05" '.$grselectstate5.'>TEAM05</option><option value="TEAM06" '.$grselectstate6.'>TEAM06</option><option value="TEAM07" '.$grselectstate7.'>TEAM07</option><option value="TEAM08" '.$grselectstate8.'>TEAM08</option><option value="TEAM09" '.$grselectstate9.'>TEAM09</option><option value="TEAM10" '.$grselectstate10.'>TEAM10</option>
<option value="TEAM11"  '.$grselectstate11.'>TEAM11</option><option value="TEAM12"  '.$grselectstate12.'>TEAM12</option><option value="TEAM13" '.$grselectstate13.'>TEAM13</option><option value="TEAM14" '.$grselectstate14.'>TEAM14</option><option value="TEAM15" '.$grselectstate15.'>TEAM15</option><option value="TEAM16" '.$grselectstate16.'>TEAM16</option><option value="TEAM17" '.$grselectstate17.'>TEAM17</option><option value="TEAM18" '.$grselectstate18.'>TEAM18</option><option value="TEAM19" '.$grselectstate19.'>TEAM19</option><option value="TEAM20" '.$grselectstate20.'>TEAM20</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">네트워킹 현황</td><td><div style="font-size:20;" class="width:250;select2-input"><select  style="width:250;font-size:20px;" id="basic3" name="basic3" class="form-control" ><option value="1"  '.$nsselectstate1.'>1</option><option value="2"  '.$nsselectstate2.'>2</option><option value="3" '.$nsselectstate3.'>3</option><option value="4" '.$nsselectstate4.'>4</option><option value="5" '.$nsselectstate5.'>5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">네트워킹 레벨</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic4" name="basic4" class="form-control" ><option value="1"  '.$nlselectstate1.'>1</option><option value="2"  '.$nlselectstate2.'>2</option><option value="3" '.$nlselectstate3.'>3</option><option value="4" '.$nlselectstate4.'>4</option><option value="5" '.$nlselectstate5.'>5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr></table> ';


$inputdata3='';
if($role==='manager')$inputdata3='<table>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">커리큘럼 조절</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic7" name="basic7" class="form-control" ><option value="LEVEL1" '.$smaselectstate1.'>LEVEL1</option><option value="LEVEL2" '.$smaselectstate2.'>LEVEL2</option><option value="LEVEL3" '.$smaselectstate3.'>LEVEL3</option><option value="LEVEL4" '.$smaselectstate4.'>LEVEL4</option><option value="LEVEL5" '.$smaselectstate5.'>LEVEL5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr> 
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">학습관리 레벨</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic8" name="basic8" class="form-control" ><option value="LEVEL1" '.$smbselectstate1.'>LEVEL1</option><option value="LEVEL2" '.$smbselectstate2.'>LEVEL2</option><option value="LEVEL3" '.$smbselectstate3.'>LEVEL3</option><option value="LEVEL4" '.$smbselectstate4.'>LEVEL4</option><option value="LEVEL5" '.$smbselectstate5.'>LEVEL5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">인지촉진 레벨</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic9" name="basic9" class="form-control" ><option value="LEVEL1" '.$smcselectstate1.'>LEVEL1</option><option value="LEVEL2" '.$smcselectstate2.'>LEVEL2</option><option value="LEVEL3" '.$smcselectstate3.'>LEVEL3</option><option value="LEVEL4" '.$smcselectstate4.'>LEVEL4</option><option value="LEVEL5" '.$smcselectstate5.'>LEVEL5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">인지성장 레벨</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic10" name="basic10" class="form-control" ><option value="LEVEL1" '.$smdselectstate1.'>LEVEL1</option><option value="LEVEL2" '.$smdselectstate2.'>LEVEL2</option><option value="LEVEL3" '.$smdselectstate3.'>LEVEL3</option><option value="LEVEL4" '.$smdselectstate4.'>LEVEL4</option><option value="LEVEL5" '.$smdselectstate5.'>LEVEL5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
<tr style="font-size:20px;"><td style="color:#1a75ff;font-weight:bold;">상담스킬 수준</td><td><div class="select2-input"><select  style="width:250;font-size:20px;" id="basic11" name="basic11" class="form-control" ><option value="LEVEL1" '.$smeselectstate1.'>LEVEL1</option><option value="LEVEL2" '.$smeselectstate2.'>LEVEL2</option><option value="LEVEL3" '.$smeselectstate3.'>LEVEL3</option><option value="LEVEL4" '.$smeselectstate4.'>LEVEL4</option><option value="LEVEL5" '.$smeselectstate5.'>LEVEL5</option></select></div></td></tr>
<tr><td><hr></td><td><hr></td></tr>
</table>';


echo '<table align=center><tr style="font-size:25px;"><td align=center><b style="color:#0066cc;">'.$studentname.' 개인설정</b></td><td width=3%></td><td align=center><b style="color:#0066cc;"></b></td><td width=3%></td><td align=center></td></tr>
<tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
<td valign=top>'.$inputdata1.'</td>
<td></td>
<td valign=top>'.$inputdata2.'</td> 
<td></td>
<td valign=top>'.$inputdata3.'</td>
</tr>
 <tr><td></td><td width=10%></td><td></td><td width=10%></td><td><button style="font-size:20;" type="image" onclick="saveproperties('.$teacherid.',$(\'#squareInput2\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#basic4\').val(),$(\'#basic5\').val(),$(\'#basic6\').val(),$(\'#basic7\').val(),$(\'#basic8\').val(),$(\'#basic9\').val(),$(\'#basic10\').val(),$(\'#basic11\').val()) ">저장하기</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$teacherid.'"target="_blank">전체 개인정보 수정</a></td></tr>

</table>';  

 




 





                                                                                        
 echo '  <br> <br> <hr>
<table width="100%"><tr><td>난이도</td><td><img  src="https://play-lh.googleusercontent.com/PkNdm5zWBQoe7JVYWu_b3fyw8SxkeeF8EkZiGKc71LOAj1-BNaWREVkUf_Asqfq4_Co" width=50 ></td><td>상태</td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/departure.gif" width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flying.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flyingthroughfield.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646909102.png" width=200  ></td></tr></table><hr>
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 500px;
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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
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

function saveproperties(Userid,Ticon,Assessmode,Networkstatus,Networklevel,Team,Teachingmode,Setmode1,Setmode2,Setmode3,Setmode4,Setmode5)
	{
	swal({title: \'저장되었습니다.\',});	
	
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'12\',
	"userid":Userid,       
	"ticon":Ticon,
	"assessmode":Assessmode,
	"networkstatus":Networkstatus,
	"networklevel":Networklevel,
	"team":Team,
	"teachingmode":Teachingmode,
	"setmode1":Setmode1,
	"setmode2":Setmode2,
	"setmode3":Setmode3,
	"setmode4":Setmode4,
	"setmode5":Setmode5,

	},
	success:function(data){
		
			}
	 })
	// alert("Hello0");
   	setTimeout(function() {location.reload(); },100);
	}

		function inputgoalstep(Eventid,Userid,Plantype,Deadline,Inputtext){   
		swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			
			  "plantype":Plantype,
			  "deadline":Deadline,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		setTimeout(function() {location.reload(); },100);
		}
 
		function updatecheck(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("완료상태가 변경되었습니다.", {buttons: false,timer: 1000});
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
			 
		}

		function updatecheck2(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 alert("체크 상태에서 새로고침하면 목록에서 사라집니다");
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


</body>';
?>