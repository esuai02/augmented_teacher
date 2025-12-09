<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teacherhelpfortopics','$timecreated')");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

$tbegin=required_param('tb', PARAM_INT);
 
$sssskey= sesskey(); 
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
$userlist= json_decode(json_encode($mystudents), True);
$subject=$DB->get_record_sql("SELECT data AS subject FROM mdl_user_info_data where userid='$USER->id' and fieldid='57' ");
if($subject->subject==='MATH')$contains='%MX%';
elseif($subject->subject==='SCIENCE') $contains='%SCIENCE%'; 

/////////////////////////// end of code snippet ///////////////////////////
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
 
$star1='';
$star2='';
$star3='';
if($tbegin==1800)$star1='*';
if($tbegin==3600)$star2='*';
if($tbegin==43200)$star3='*';
echo  '
 
 개념과제 출제 > 개념화이트 보드 작성 > 개념확인문제 풀기 > 제출 > 검사 (개념 노트를 제작하고 필기양 등으로 노트 완성정도를 시각화) - 협업환경 구성<hr>
 <br>
<br>
 
<table width="1200" height="400" background="http://mathking.kr/Contents/IMAGES/mathtopics.jpg" align="center">
<tr><th> <br> <br> <br> </th> <th></th> <th></th> <th></th> <th></th> </tr> 
    
        <tr>
 
      
            <th scope="col">
                <h4 style="text-align: center;"><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21172"target="_blank"target="_blank"><btarget="_blank"><span class="" style="color: rgb(255, 255, 255);">수체계</span></b></a></h4>
            </th>
     
            <td>
                <h4 style="text-align: center;"><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21173"target="_blank"target="_blank"><btarget="_blank"><span class="" style="color: rgb(255, 255, 255);">지수와 로그</span></b></a></h4>
            </td>
        
            <td>
                <h4 style="text-align: center;"><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21174"target="_blank"target="_blank"><btarget="_blank"><span class="" style="color: rgb(255, 255, 255);">수열</span></b></a></h4>
            </td>
     
            <td>
                <h4 style="text-align: center;"><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21175"target="_blank"target="_blank"><btarget="_blank"><span class="" style="color: rgb(255, 255, 255);">식의 계산</span></b></a></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21176"target="_blank"target="_blank"><btarget="_blank"><span class="" style="color: rgb(255, 255, 255);">집합과 명제</span></b></a></h4>
            </td>
        </tr><tr><th> <br>   </th> <th></th> <th></th> <th></th> <th></th> </tr> 
        <tr>
        <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21177"target="_blank"><span class="" style="color: rgb(255, 255, 255);">방정식</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21178"target="_blank"><span class="" style="color: rgb(255, 255, 255);">부등식</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21179"target="_blank"><span class="" style="color: rgb(255, 255, 255);">함수</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21180"target="_blank"><span class="" style="color: rgb(255, 255, 255);">미분</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21181"target="_blank"><span class="" style="color: rgb(255, 255, 255);">적분</span></a></b></h4>
            </td>
         </tr><tr><th> <br>   </th> <th></th> <th></th> <th></th> <th></th> </tr> 
        <tr>
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21182"target="_blank"><span class="" style="color: rgb(255, 255, 255);">평면도형</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21183"target="_blank"><span class="" style="color: rgb(255, 255, 255);">평면좌표</span></a></b></h4>
            </td>
  
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21184"target="_blank"><span class="" style="color: rgb(255, 255, 255);">입체도형</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21185"target="_blank"><span class="" style="color: rgb(255, 255, 255);">공간좌표</span></a></b></h4>
            </td>
 
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21186"target="_blank"><span class="" style="color: rgb(255, 255, 255);">벡터</span></a></b></h4>
            </td>
        </tr><tr><th> <br>   </th> <th></th> <th></th> <th></th> <th></th> </tr> 
        <tr>
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21187"target="_blank"><span class="" style="color: rgb(255, 255, 255);">확률</span></a></b></h4>
            </td>
  
            <td>
                <h4 style="text-align: center;"><b><a href="https://mathking.kr/moodle/mod/icontent/view.php?id=67866&amp;pageid=21188"target="_blank"><span class="" style="color: rgb(255, 255, 255);">통계</span></a></b></h4>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>
        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>


        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>


        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>


        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>


        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>


        </tr>
        <tr>
            <td style="text-align: center;"><span style="font-size: 24px;"><b>&nbsp;</b></span></td>


        </tr>
  
</table>
<br>
<p><br></p>';
     
echo '
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
'; 
 
 echo $today1.$today2.$today3.$today4.$today5.$today6.$today7.$today8.$today9.$today10.$today11.$today12.$today13.$today14.$today15.$today16.$today17.$today18.$today19.'</tbody></table><hr>'.$nowhiteboard.'<hr>'; 
 
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

	function AskStudent(Eventid,Studentid,Teacherid,Questionid)
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
include("quicksidebar.php");
?>