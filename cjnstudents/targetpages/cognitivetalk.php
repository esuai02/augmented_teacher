<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$wboardid=$_GET["wboardid"];   
$mode=$_GET["mode"];   
 
$getauthor=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1 "); 
$wbcreator=$getauthor->userid;   
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE wboardid LIKE '$wboardid' AND hide=0  ORDER BY id DESC LIMIT 50  ");  
$talklist= json_decode(json_encode($share), True);
 
unset($value);  
foreach($talklist as $value)
	{
	$sid=$value['id'];
	$type=$value['type'];
	$sharetext=$value['text'];
	$talkcreator=$value['userid'];
	$crname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$talkcreator' ");	
	$creatorname=$crname->firstname.$crname->lastname;
	$tcreated1=date("m월d일 h:i A", $value['timecreated']);   
     
	$sharelist.='<table width=100% ><tbody><tr><td width=3% style="white-space: nowrap; text-overflow: ellipsis;" valign=top> <span style="color:#3399ff;">'.$creatorname.'</span></td> <td width=1%></td>
	<td style="overflow:auto;"><div class="bubble"> &nbsp;&nbsp;&nbsp;'.$sharetext.' </div></td> <td><span type="button"  onClick="Edittext(\''.$sid.'\',\''.$sharetext.'\')"><img style="margin-bottom:0px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png" width=17></span></td><td> <span type="button"  onClick="Comment(\''.$creator.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:0px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=23></span></td> <td style="font-size:12px;">'.$type.'</td><td style="font-size:12px;">'.date("m/d", $value['timecreated']).'</td></tr></tbody></table>'; 
	}
echo '<table width=100% align=center><tr><td> </td> <td width=20% align=right>추천수 + 3</td><td width=5%></td></tr></table><hr>
<table width=100% align=center><tr><td width=5%></td><td><div class="select2-input"><select style="font-size:20px;"  id="basic1" name="basic" class="form-control" ><option value="" disabled selected>학습지능</option> <option value="발상">발상</option><option value="해석">해석</option><option value="숙달">숙달</option><option value="효율">효율</option></select></div></td><td><div class="select2-input"><select style="font-size:20px;"  id="basic2" name="basic2" class="form-control" ><option value="" disabled selected>체크번호</option> <option value="1">체크1</option><option value="2">체크2</option><option value="3">체크3</option><option value="4">체크4</option><option value="5">체크5</option></select></div></td><td width=60%><input style="font-size:20px;width:100%;" type="text" id="squareInput" name="squareInput"  placeholder="내용을 입력해 주세요"></td><td><button style="font-size:20px;"  onClick="Comment(\''.$wbcreator.'\',\''.$USER->id.'\',\''.$wboardid.'\',$(\'#squareInput\').val(),$(\'#basic1\').val(),$(\'#basic2\').val())">발송</button></td></tr></table><hr>
<table width=100%><tr height=90%><td width=2%></td><td valign=top style="overflow-y:hidden;">'.$sharelist.'</td></tr></table> ';
 
echo '
<script>	
function Comment(Wbcreator,Userid,Wboardid,Text,Type,Checkid)
	{ 
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'40\',
		"wboardid":Wboardid,
		"wbcreator":Wbcreator,	
		"inputtext":Text,	
		"type":Type,
		"checkid":Checkid,
		"userid":Userid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
function Reply(Userid,Wboardid,Sid,Text)
	{ 
	alert(Sid);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'40\',
		"wboardid":Wboardid,	
		"inputtext":Text,	
		"userid":Userid,
		"talkid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
function Edittext(Itemid,Inputtext)
	{
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'42\',
		"wboardid":Wboardid,	
		"inputtext":Text,	
		"userid":Userid,
		"talkid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
			  }
		 })
	}
function hide(Eventid,Fbid, Checkvalue){
		var checkimsi = 0;
   		if(Checkvalue==true){
        		checkimsi = 1;
    		}
 		swal("체크시 학생에게 보이지 않습니다.", {buttons: false,timer: 500});
  		 $.ajax({
       		 url: "check.php",
        		type: "POST",
        		dataType: "json",
        		data : { 
		"eventid":\'43\',
            		"fbid":Fbid,
            	 	"checkimsi":checkimsi,
            	 	  },
 	  	 success: function (data){  
		var Teacherid=data.teacherid
		setTimeout(function() {location.reload(); },100);	
  	   	   }
		  });
		}
</script> 



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
 
 ';
?>
