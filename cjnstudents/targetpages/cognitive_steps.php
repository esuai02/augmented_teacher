<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$contentsid=required_param('cntid', PARAM_INT); 
$contentstype=required_param('cnttype', PARAM_INT); 
$recommend=required_param('recommend', PARAM_INT);  
$nstep=required_param('nstep', PARAM_INT);  
$cntsteps= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivesteps WHERE contentstype LIKE '$contentstype' AND recommend='$recommend' AND  contentsid='$contentsid' "); // 과목정보 가져오기
$step1=$cntsteps->step1;
$step2=$cntsteps->step2;
$step3=$cntsteps->step3;
$step4=$cntsteps->step4;
$step5=$cntsteps->step5;
$step6=$cntsteps->step6;
$step7=$cntsteps->step7;

$link1=$cntsteps->link1;
$link2=$cntsteps->link2;
$link3=$cntsteps->link3;
$link4=$cntsteps->link4;
$link5=$cntsteps->link5;
$link6=$cntsteps->link6;
$link7=$cntsteps->link7;
$wboardid=$cntsteps->wboardid;

$cntsteps='step'.$nstep;
$cntlinks='link'.$nstep;
$explaintitle=$$cntsteps; // 풀이유형 제목
$cntlink=$$cntlinks;
$prev=$nstep-1;
$next=$nstep+1;

$linkicon='';
if($cntlink!=NULL)$linkicon='<a href="'.$cntlink.'" target="_blank"><img src=https://mathking.kr/IMG/HintIMG/BESSI1597839069001.png width=40></a>';

$finalstep='step'.$next;
$fstep=$$finalstep;
if($prev==0)$prev=1;
if($fstep==NULL)$next=$next-1;
 
//    <table width=100% align=center><tr><td><h4><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_steps.php?id='.$studentid.'&cntid='.$contentsid.'&cnttype='.$contentstype.'&recommend='.$recommend.'&nstep='.$prev.'">◁</a></h4></td><td><h4 align=center><span style="color:black">'.$nstep.'단계 : '.$explaintitle.' </span></h4></td><td>'.$linkicon.'</td><td align=right><h4><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_steps.php?id='.$studentid.'&cntid='.$contentsid.'&cnttype='.$contentstype.'&recommend='.$recommend.'&nstep='.$next.'">▷</a></h4></td></tr></table>

echo ' 
 				 

         	<div class="panel">
          	<div class="panel-heading parent">    
              <a class="accordion-toggle">
   <table width=100% align=center><tr><td><h4><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_steps.php?id='.$studentid.'&cntid='.$contentsid.'&cnttype='.$contentstype.'&recommend='.$recommend.'&nstep='.$prev.'">◁</a></h4></td><td><h4 align=center><span style="color:black">'.$explaintitle.' </span></h4></td><td>'.$linkicon.'</td><td align=right><h4><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_steps.php?id='.$studentid.'&cntid='.$contentsid.'&cnttype='.$contentstype.'&recommend='.$recommend.'&nstep='.$next.'">▷</a></h4></td></tr></table>
                </a><hr>
          	</div>

              <div class="panel-body">	     
	<iframe src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$wboardid.'&userid='.$studentid.'&cnttype='.$contentstype.'&cntid='.$contentsid.'&nstep='.$nstep.'"  style="border: 0px none; width:100%; margin-left: 0px; height:65%; margin-top: 0px;"></iframe>
	<table align="center"><tr><th  align=center width=33%> <a href="" taget="_blank">질문하기</a></th><th  align=center  width=33%> </th><th align=center width=40%>  <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_homework.php?id='.$wboardid.'&userid='.$studentid.'&cnttype='.$contentstype.'&cntid='.$contentsid.'&nstep='.$nstep.'" target="_blank">큰 화면으로</a></th></tr> </table><br>
	 </div>	
	</div>	      ';
	  
 
echo '</div></div></div> ';
include("quicksidebar.php");
include("quicksidebar.php");
echo ' </div></div></div> 
<style>
 body{
  padding:0px;
  background-color: #fff;
}
/*Vertical Steps*/
.inside-body{
  padding:0px;
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
	</script></body>';
?>