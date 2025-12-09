<!DOCTYPE html>
<html>
<style>
* {
  box-sizing: border-box;
}
@media print  
{
    div { 
        page-break-inside: avoid;
    }
}
img {
border: 1px solid #555;
 
}
body {
  margin: 0;
  font-family: Arial;
  overflow-x:hidden;
}

.header {
  text-align: center;
  padding: 32px;
}

.row {
  display: -ms-flexbox; /* IE10 */
  display: flex;
  -ms-flex-wrap: wrap; /* IE10 */
  flex-wrap: wrap;
  padding: 0 4px;
}

/* Create four equal columns that sits next to each other */
.column {
  -ms-flex: 25%; /* IE10 */
  flex: 25%;
  max-width: 25%;
  padding: 0 4px;
}

.column img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

/* Responsive layout - makes a two column-layout instead of four columns */
@media screen and (max-width: 1000px) {
  .column {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

/* Responsive layout - makes the two columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
}
 
</style>


<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 

global $DB, $USER;
$studentid = $_GET["studentid"]; 
$fid = $_GET["fid"]; //flowid
 
$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$stdtname->firstname.$stdtname->lastname;

$tabtitle=$studentname;
$history=$DB->get_records_sql("SELECT * FROM mdl_abessi_flowlog where userid='$studentid'  ORDER BY id DESC LIMIT 30");
$polygons= json_decode(json_encode($history), True);

unset($value);  
foreach(array_reverse($polygons) as $value)
	{
	$id=$value['id'];

	$tcreated=date("y/m/d", $value['timecreated']);   
 	$flowtalks='대화기록';
	if($fid==$id)
		{
		$flow1=$value['flow1'];
		$flow2=$value['flow2'];
		$flow3=$value['flow3'];
		$flow4=$value['flow4'];
		$flow5=$value['flow5'];
		$flow6=$value['flow6'];
		$flow7=$value['flow7'];
		$flow8=$value['flow8'];  
		$view.='<div class="tooltip3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'&fid='.$id.'"><b style="color:red;">'.$tcreated.'</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$flowtalks.'</td></tr></table></span></div>  &nbsp;';
		}
	else $view.='<div class="tooltip3"> <a style="color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'&fid='.$id.'">'.$tcreated.'</a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$flowtalks.'</td></tr></table></span></div>  &nbsp;';
 	}

echo '<br><table align=center width=98%><tr><td>'.$hat.'</td><td align=left><a  style="text-decoration: none; font-size:20px;color:black; white-space: nowrap; text-overflow: ellipsis;"  href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a></td><td style="color:#2085f7; font-size:16px;"></td><td><img src=https://mathking.kr/Contents/IMG22/meta0.png width=100%></td></tr></table>
';
if($fid==NULL)$fid=$id;
	echo '<table width=100%><tr><th><iframe style="border: 1px none; z-index:2; width:99vw;height:85vh;  margin-left: -0px;margin-right: -0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/students/polygon.php?studentid='.$studentid.'&fid='.$fid.'"></iframe>
</th></tr></table>
<table width=90% align=center><tr><th align=left>업데이트 이력  | '.$view.' </th></tr></table>';
 
 // 이부분 우측창으로..
echo ' <table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$sharelist.'</tbody></table> ';
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										 
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';

 


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
	<script src="../assets/js/setting-demo.js"></script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<link rel="stylesheet" href="../assets/css/ready.min.css">
<script src="https://code.jquery.com/pep/0.4.3/pep.js"></script> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
 

 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />



 

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
border-color: black;
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
  background-color: #4287f5;
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
  background-color: #4287f5;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  top: -200%;
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
  color: #4287f5;
  background: #4287f5;

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
 
 ';
 
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
 

var myRadarChart = new Chart(radarChart, {
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
 
</script>
 

</body>
</html>
