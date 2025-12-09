<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$lastpage=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$USER->id' ORDER BY id DESC LIMIT 1"); 
$url=$lastpage->context.'?'.$lastpage->currenturl;
echo '<br><br><hr><table width=100%  valign=center>
<tr valign=center><td width=30%><div class="container"></div></td><td width=70%><img src="https://mathking.kr/Contents/IMAGES/cometome.png" width=80%></td><td></td></tr>
<tr><td></td><td align=center style="font-size:20px;">다녀온 후 <a href="'.$url.'">이전 페이지</a> 또는 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$USER->id.'&tb=604800">오늘 활동 페이지</a>로 이동해 주세요</td><td></td></tr>
 
</table><br><br><hr>';


echo ' 
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.16/d3.min.js"></script>
<script>var width = 400,
  height = 400,
  timePassed = 0,
  timeLimit = 300;

var fields = [{
  value: timeLimit,
  size: timeLimit,
  update: function() {
    return timePassed = timePassed + 1;
  }
}];

var nilArc = d3.svg.arc()
  .innerRadius(width / 3 - 133)
  .outerRadius(width / 3 - 133)
  .startAngle(0)
  .endAngle(2 * Math.PI);

var arc = d3.svg.arc()
  .innerRadius(width / 3 - 55)
  .outerRadius(width / 3 - 25)
  .startAngle(0)
  .endAngle(function(d) {
    return ((d.value / d.size) * 2 * Math.PI);
  });

var svg = d3.select(".container").append("svg")
  .attr("width", width)
  .attr("height", height);

var field = svg.selectAll(".field")
  .data(fields)
  .enter().append("g")
  .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")")
  .attr("class", "field");

var back = field.append("path")
  .attr("class", "path path--background")
  .attr("d", arc);

var path = field.append("path")
  .attr("class", "path path--foreground");

var label = field.append("text")
  .attr("class", "label")
  .attr("dy", ".35em");

(function update() {

  field
    .each(function(d) {
      d.previous = d.value, d.value = d.update(timePassed);
    });

  path.transition()
    .ease("elastic")
    .duration(500)
    .attrTween("d", arcTween);

  if ((timeLimit - timePassed) <= 10)
    pulseText();
  else
    label
    .text(function(d) {
      return d.size - d.value;
    });

  if (timePassed <= timeLimit)
    setTimeout(update, 1000 - (timePassed % 1000));
  else
    destroyTimer();

})();

function pulseText() {
  back.classed("pulse", true);
  label.classed("pulse", true);

  if ((timeLimit - timePassed) >= 0) {
    label.style("font-size", "120px")
      .attr("transform", "translate(0," + +4 + ")")
      .text(function(d) {
        return d.size - d.value;
      });
  }

  label.transition()
    .ease("elastic")
    .duration(900)
    .style("font-size", "90px")
    .attr("transform", "translate(0," + -10 + ")");
}

function destroyTimer() {
  label.transition()
    .ease("back")
    .duration(700)
    .style("opacity", "0")
    .style("font-size", "5")
    .attr("transform", "translate(0," + -40 + ")")
    .each("end", function() {
      field.selectAll("text").remove()
    });

  path.transition()
    .ease("back")
    .duration(700)
    .attr("d", nilArc);

  back.transition()
    .ease("back")
    .duration(700)
    .attr("d", nilArc)
    .each("end", function() {
      field.selectAll("path").remove()
    });
}

function arcTween(b) {
  var i = d3.interpolate({
    value: b.previous
  }, b);
  return function(t) {
    return arc(i(t));
  };
}</script>
<style>
 @import url(https://fonts.googleapis.com/css?family=Open+Sans:800);

.container {
  margin: auto;
  width: 400px;
}

.path--background {
  fill: rgb(34, 213, 201);
  stroke: #fff;
  stroke-width: 0px;
}

.pulse {
  fill: rgb(255, 74, 74) !important;
}

.path--foreground {
  fill: #eee;
  stroke: #eee;
  stroke-width: 2px;
}

.label {
  font: 60px "Open Sans";
  font-weight: 900;
  text-anchor: middle;
  fill: rgb(34, 213, 201);
}
</style>';

echo '<script>
var Nexturl=\''.$url.'\';
setTimeout(function(){
	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":\'311\',
	},
	success:function(data){
	var Eventid=data.eventid;
	window.location.href =Nexturl; 
	 }
	 })
} , 300000);   

 
 
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
	<script src="../assets/js/demo.js"></script>';

//	$beingsullivan=$eventid;  include("../whiteboard/debug.php");
 

?>

