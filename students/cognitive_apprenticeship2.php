<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$contentsid=required_param('cntid', PARAM_INT); 
$contentstype=required_param('cnttype', PARAM_INT); 
$recommend=required_param('recommend', PARAM_INT);   
$cntsteps= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivesteps WHERE contentstype LIKE '$contentstype' AND recommend='$recommend' AND  contentsid='$contentsid' "); // 과목정보 가져오기
$step1=$cntsteps->step1;
$step2=$cntsteps->step2;
$step3=$cntsteps->step3;
$step4=$cntsteps->step4;
$step5=$cntsteps->step5;
$step6=$cntsteps->step6;
$step7=$cntsteps->step7;

echo ' <section id="how-to"><div class="panel-group" id="superaccordion">';
  
for($nstep1=1;$nstep1<=7;$nstep1++)// 초기 도입, 단계별 입력, 최종 결과물
	{ 
	$cntsteps='step'.$nstep1;
	$explaintitle=$$cntsteps; // 풀이유형 제목
	$cogwboard='nr1rjrk0sc0akscnttype'.$contentstype.$contentsid.$nstep1.$studentid; // 단계별 화이트보드 아이디
	if($explaintitle==NULL)break;

 
	$cogstep= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivesteps WHERE  wboardid LIKE '$cogwboard' ");
	if($cogstep->id!=NULL) $wboardid=$cogwboard;
	else  
		{
		$wboardid=$cogwboard;
		include("createnote.php");
		//$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,homework,status,contentstype,wboardid,contentstitle,cmid,contentsid,timemodified,timecreated) VALUES('$userid','2','$role','2','0','0','$checkimsi','complete','cognitivestep','$wboardid','$explaintitle','1','$contentsid','$timecreated','$timecreated')");
		} 
 
 
echo ' 
        <!-- Accordion -->
         	<div class="panel">
          	<div class="panel-heading parent">
              <a class="accordion-toggle" data-toggle="collapse" data-parent="#superaccordion" href="#collapse'.$nstep1.'" aria-expanded="false">
              <h4><span style="color:white">'.$explaintitle.'</span></h4>
              </a>
          	</div>
 	<div id="collapse'.$nstep1.'" class="panel-collapse collapse">
              <div class="panel-body">	     
	<iframe src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$wboardid.'&userid='.$studentid.'&cnttype='.$contentstype.'&cntid='.$contentsid.'&nstep='.$nstep1.'"  style="border: 0px none; width:100%; margin-left: 0px; height:50%; margin-top: 0px;"></iframe>
	<hr><table align=center widht=60%><tr><th width=50%> <a href="" taget="_blank">질문하기</a></th><th width=50%>  <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$wboardid.'&userid='.$studentid.'&cnttype='.$contentstype.'&cntid='.$contentsid.'&nstep='.$nstep1.'" target="_blank">큰 화면으로</a></th></tr> </table><hr>
	</div>
	</div>	     
	</div>
	      ';
	}
echo '</div></section>';
 
 
 
echo ' 
<style>
 body{
  padding:20px;
  background-color: #fff;
}
/*Vertical Steps*/
.inside-body{
  padding:25px;
}
.list-group.vertical-steps .list-group-item{
  border:none;
  border-left:3px solid #5cadff;
  box-sizing:border-box;
  border-radius:0;
  counter-increment: step-counter;
  padding-left:20px;
  padding-right:0px;
  padding-bottom:20px;
  padding-top:0px;
}
.list-group.vertical-steps .list-group-item.active{
  background-color:transparent;
  color:#18191a;
}
.list-group.vertical-steps .list-group-item:last-child{
  border-left:3px solid transparent;
  padding-bottom:0;
}
.list-group.vertical-steps .list-group-item::before {
  border-radius: 50%;
  background-color:#5cadff;
  color:#fff;
  content: counter(step-counter);
  display:inline-block;
  float:left;
  height:25px;
  line-height:25px;
  margin-left:-35px;
  text-align:center;
  width:25px;
}
.list-group.vertical-steps .list-group-item span,
.list-group.vertical-steps .list-group-item a{
  display:block;
  overflow:hidden;
  padding-top:2px;
}
/* End of Vertical Step */
#how-to .panel-group .panel{
  border-radius:0px;
  border: 0px;
}
#how-to .panel-group{
  margin:0px;
}
#how-to .panel-heading{
  padding:0px !important;
  border-radius: 0px;
}
#how-to .parent a{
  display: block;
  text-decoration: none;
  padding:25px;
}
#how-to .child a{
  display: block;
  text-decoration: none;
  padding:25px;
}
#how-to .parent{
  background-color: #29accc !important;  /* bar 의 채우기 색 */
}
#how-to .child{
  background-color: #6dcbf7 !important;  /* 2단계 박스 채우기 색 */
}
#how-to .panel-body{
  border: none;
}
#how-to .panel-body{
  padding:0px;
}
#how-to .panel-group .panel+.panel{
  margin:0px;
}
#how-to .panel-group .parent{
  border-bottom: 1px solid #fff;
}
#how-to .panel-group .child{
  border-bottom: 1px solid #fff;
}
#superaccordion{
  box-shadow:0 2px 4px 0 rgba(11,0,0,0.16),0 2px 10px 0 rgba(11,0,0,0.12)!important;
}
.panel-heading a:after {
  content: "";
  position: relative;
  top: 1px;
  right:10px;
  display: inline-block;
  font-style: normal;
  font-weight:500;
  font-size:10pt;
  line-height: 1;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  float: right;
  transition: transform .25s linear;
  -webkit-transition: -webkit-transform .25s linear;
  color:#333;
}
.panel-heading a[aria-expanded="true"]:after {
  content: "\2212";
  -webkit-transform: rotate(180deg);
  transform: rotate(180deg);
}
.panel-heading a[aria-expanded="false"]:after {
  content: "\002b";
  -webkit-transform: rotate(90deg);
  transform: rotate(90deg);
}
.parent a:after{
  content: "";
  position: relative;
  top: -15px;
  right:10px;
  display: inline-block;
  line-height: 0;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  float: right;
  transition: transform .25s linear;
  -webkit-transition: -webkit-transform .25s linear;
  color:#333;
}

</style>
 
';
 
echo '
<script>
$(".card-header").parent(".card").hover(
			function() {
				$(this).children(".collapse").collapse("show");
			}, function() {
				$(this).children(".collapse").collapse("hide");
			}
		);
</script>
 
<script>https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js</script>
 
<script>https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js</script>

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


	<script>    
		function changecheckbox(Eventid,Userid,Wboardid,Checkvalue){
 
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
 
		function changecheckbox2(Eventid,Userid,Wboardid,Pageid,Checkvalue){
 		alert(Checkvalue);
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
			 "pageid":Pageid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });	
		} 
	</script>

';

?>