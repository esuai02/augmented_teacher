<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar_note.php");
$bookid=required_param('bookid', PARAM_INT); 
$contentsid = $_GET["contentsid"];
$contentstype = $_GET["contentstype"];
$wboardid0= $_GET["wboardid"];
$pw = $_GET["pw"];
$book= $DB->get_record_sql("SELECT * FROM mdl_abessi_book WHERE id='$bookid' "); // 과목정보 가져오기
$cmid=$book->cmid;
$instance= $DB->get_record_sql("SELECT * FROM mdl_course_modules WHERE id='$cmid' "); 
$checklistid=$instance->instance;
$checklist= $DB->get_record_sql("SELECT * FROM mdl_checklist WHERE id='$checklistid' ");  
$listname= $checklist->name;
$contents= $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$checklistid' "); //단원 정보 가져오기
if($wboardid0==NULL)$wboardid0='NULL';
$result = json_decode(json_encode($contents), True);

echo ' <section id="how-to"><div class="panel-group" id="superaccordion">';
 
unset($value);
foreach($result as $value) // 단원 반복생성
	{ 
	$chaptertitle=$value['displaytext']; //단원명
	$cmid2=$value['moduleid']; //단원 체크리스트 cmid

	$instance2= $DB->get_record_sql("SELECT * FROM mdl_course_modules WHERE id='$cmid2' "); 
	$checklistid2=$instance2->instance;
	//$cmid3= $DB->get_record_sql("SELECT * FROM mdl_checklist WHERE id='$checklistid2' ");

	$contents2= $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$checklistid2' ");
	$result2 = json_decode(json_encode($contents2), True);

echo ' 
        <!-- Accordion -->
          <div class="panel">
          <div class="panel-heading parent">
              <a class="accordion-toggle" data-toggle="collapse" data-parent="#superaccordion" href="#collapse'.$cmid2.'" aria-expanded="false">
              <h4><span style="color:white">'.$chaptertitle.'</span></h4>
              </a>
          </div>
 <div id="collapse'.$cmid2.'" class="panel-collapse collapse">
    ';

	unset($value2);
	 
	$accordion='';
	foreach($result2 as $value2) // 소주제 반복생성
 		{
	 
		$title=$value2['displaytext'];  // 소주제명
		$topiccmid=str_replace('https://mathking.kr/moodle/mod/icontent/view.php?id=', '', $value2['linkurl']);
		$contents3= $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$topiccmid' ORDER BY pagenum");   // (icontent 부분에서 정보가지고 오기)
		$result3 = json_decode(json_encode($contents3), True);
	
//	echo '체크리스트id2='.$checklistid2.'$topiccmid='.$topiccmid;

		unset($value3);
		$items='';
 
		foreach($result3 as $value3)
			{
			$cnttitle=$value3['title']; // 주제내 목차명
			$pageid=$value3['id'];
			$cmid=$value3['cmid'];
			$wboardid='pageid'.$pageid.'jnrsorksqcrark'.$studentid;
			$message= $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' "); 

			if($message->wboardid==NULL) $items.='<li class="list-group-item"><table><tr><td><h5>'.$cnttitle.'</h5></td><td>&nbsp;</td><td><a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$pageid.'&userid='.$studentid.' " target="_blank">
			<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1594118432001.png" width=30></a></td><td><input type="checkbox"  onclick="changecheckbox2(9,\''.$studentid.'\',\''.$wboardid.'\',\''.$wboardid0.'\',\''.$pageid.'\', \''.$contentsid.'\', \''.$contentstype.'\', this.checked)"/></td></tr></table></li>';
			else $items.='<li class="list-group-item"><table><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'&contentsid0='.$contentsid.'&contentstype0='.$contentstype.'&userid0='.$studentid.'" target="_blank"><h5>'.$cnttitle.'</h5></a></td><td>&nbsp;</td><td><input type="checkbox"  onclick="changecheckbox(8,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/></td></tr></table></li>';
			}
 
	echo ' 	          <div class="panel-body">
		    <div class="panel-group" id="accordion'.$cmid2.'">
		    <div class="panel">
	                  <div class="panel-heading child">
	                    <h5 class="panel-title">
	                      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion'.$cmid2.'" href="#collapse'.$topiccmid.'" aria-expanded="false"><span style="color:white">'.$title.'</span></a>
	                    </h5>
	                  </div>
	                  <div id="collapse'.$topiccmid.'" class="panel-collapse collapse">
	                    <div class="panel-body">
	                      <div class="inside-body">
	                        <ol class="list-group vertical-steps">
			'.$items.'
	                        </ol>
	                      </div>
	                    </div>
	                  </div>
	                </div>
	              </div>
	            </div>

	      ';
		}
//echo $accordion;
 

echo '</div></div>';
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
  padding:15px;
}
#how-to .child a{
  display: block;
  text-decoration: none;
  padding:15px;
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
 
		function changecheckbox2(Eventid,Userid,Wboardid,Wboardid0,Pageid,Contentsid,Contentstype,Checkvalue){
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
		 window.open("https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+Wboardid+"&wboardid0="+Wboardid0+"&contentsid0="+Contentsid+"&contentstype0="+Contentstype+"&userid0="+Userid);
		} 
	</script>

';

?>