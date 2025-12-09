<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
if($studentid==NULL)$studentid=$USER->id;
require_login();
$timecreated=time(); 
$hoursago=$timecreated-14400;
$aweekago=$timecreated-604800;
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$instructions=$DB->get_records_sql("SELECT  * FROM mdl_abessi_tracking WHERE userid='$studentid' AND timecreated > '$aweekago'   ORDER BY timecreated ASC LIMIT 100");
$result = json_decode(json_encode($instructions), True);
unset($value);
 
foreach(array_reverse($result) as $value) 
	{	 
	if($prev_time!==date("m_d", $value['timecreated']))
		{
		$directionlist.='<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		}
	$statustext=$value['status'];
	if($statustext==='begin')$statustext='<SPAN ONCLICK="returntoSDL(\''.$studentid.'\');"><b style="color:blue;">진행중</b></SPAN>';
    $directionlist.='<tr><td>'.date("m/d h:i", $value['timecreated']).'</td><td>'.$value['text'].'</td><td>'.date("h시i분", $value['duration']).'</td><td>'.$statustext.'</td></tr>';
	$prev_time=date("m_d", $value['timecreated']);
	} 
	echo '<br><table align=center width=90%><tr><td>시작</td><td width=60%>내용 입력하기&nbsp;&nbsp;&nbsp; <SPAN ONCLICK="giveInstruction(\''.$studentid.'\');"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647517508.png width=20></SPAN> </td><td>종료</td><td>상태</td></tr>
	'.$directionlist.'</table>'; 
//<SPAN ONCLICK="beginTracking(\''.$studentid.'\');"><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/stopwatch.png width=20></SPAN>&nbsp;&nbsp;&nbsp;&nbsp;
echo '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

 
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
 
	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
<script>
function beginTracking(Studentid)
	{		 
			var text1="5";
			var text2="10";
			var text3="15";
			var text4="20";
			var text5="25";
			var text6="30";
			var text7="40";
			var text8="50";
			var text9="입력";

			swal("시간제한(분)",  "",{
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: text5,
			      value: "catch5",className : \'btn btn-success\'
			    },
			    catch6: {
			      text: text6,
			      value: "catch6",className : \'btn btn-success\'
			    },
			    catch7: {
			      text: text7,
			      value: "catch7",className : \'btn btn-success\'
			    },
			    catch8: {
				text: text8,
				value: "catch8",className : \'btn btn-success\'
				  },
				catch9: {
				text: text9,
				value: "catch9",className : \'btn btn-secondary\'
				  },
			cancel: {
				text: "취소",
				visible: false,
				className: \'btn btn-alert\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			     swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;
		 
 			   case "catch1":
				swal(""," " + text1+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'20\',
						"userid":Studentid,
						"duration":text1,	
							
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
				swal(""," " + text2+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'20\',
						"userid":Studentid,
						"duration":text2,	
							
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
				swal(""," " + text3+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'20\',
						"userid":Studentid,
						"duration":text3,	
							
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch4":
				swal(""," " + text4+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'20\',
						"userid":Studentid,
						"duration":text4,	
							
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
				 case "catch5":
					swal(""," " + text5+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'20\',
							"userid":Studentid,
							"duration":text5,	
								
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch6":
					swal(""," " + text6+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'20\',
						"userid":Studentid,
						"duration":text6,	
							
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
				 case "catch7":
					swal(""," " + text7+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'20\',
							"userid":Studentid,
							"duration":text7,	
								
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch8":
					swal(""," " + text8+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'20\',
						"userid":Studentid,
						"duration":text8,	
						
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
 			   case "catch9":
				swal({
					title: \'모니터링 시간을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "시간입력 (분)",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'20\',
					"userid":Studentid,
					"duration":Inputtext,	
									 
					},
					success:function(data){
					 }
					 })
				});	 
				 
				break;
 			   
			}
		})
	}

	function giveInstruction(Studentid)
	{		 
 
			var text1="10";
			var text2="20";
			var text3="30";
			var text4="40";
			var text5="50";
			var text6="60";
			var text7="90";
			var text8="120";
			var text9="180";

			swal("필요시간",  "",{
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: text5,
			      value: "catch5",className : \'btn btn-success\'
			    },
			    catch6: {
			      text: text6,
			      value: "catch6",className : \'btn btn-success\'
			    },
			    catch7: {
			      text: text7,
			      value: "catch7",className : \'btn btn-success\'
			    },
			    catch8: {
				text: text8,
				value: "catch8",className : \'btn btn-success\'
				  },
				catch9: {
				text: text9,
				value: "catch9",className : \'btn btn-secondary\'
				  },
			cancel: {
				text: "취소",
				visible: false,
				className: \'btn btn-alert\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			     swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;
		 
 			   case "catch1":
					swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text1,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				
 			   case "catch2":
				swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text2,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
 			   case "catch3":
				swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text3,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
 			   case "catch4":
				swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text4,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
				 case "catch5":
					swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text5,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
				case "catch6":
					swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text6,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
				 case "catch7":
					swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text7,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
				case "catch8":
					swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text8,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				 
				break;
				 
 			   case "catch9":
				swal({
					title: \'계획입력\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal(""," " + Inputtext+"분 동안 활동을 부여합니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'21\',
					"userid":Studentid,
					"duration":text9,	
					"inputtext":Inputtext,			 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				});	 
				
				break;
				 
 			   
			}
		})
	}

function returntoSDL(Studentid)
	{		  
	swal("SDL로 전환됩니다.",{buttons: false,timer: 500});
	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'22\',
	"userid":Studentid,	 			 
	},
	success:function(data){
		}
		})
	location.reload(); 
	}
</script>
	<style> 
	a {
		user-drag: none; /* for WebKit browsers including Chrome */
		user-select: none; /* for standard-compliant browsers */
		-webkit-user-drag: none; /* for Safari and Chrome */
		-webkit-user-select: none; /* for Safari */
		-moz-user-select: none; /* for Firefox */
		-ms-user-select: none; /* for Internet Explorer/Edge */
	}
	img {
		user-drag: none; /* for WebKit browsers including Chrome */
		user-select: none; /* for standard-compliant browsers */
		-webkit-user-drag: none; /* for Safari and Chrome */
		-webkit-user-select: none; /* for Safari */
		-moz-user-select: none; /* for Firefox */
		-ms-user-select: none; /* for Internet Explorer/Edge */
	}
	a, a:visited {
		color: black;
	  }
	#tableContainer {
		opacity: 0;
		transition: opacity 0.5s ease;
	  }
	  #tableContainer.active {
		opacity: 1;
	  } 
	.table-wrapper {
		position: relative;
		height: 100% /* 테이블이 표시될 영역의 높이를 지정하세요 */
		overflow: auto;
	  }
	  
	  .table-wrapper thead {
		position: sticky;
		top: 0;
		background-color: #BCD5FF; /* 첫 번째 행의 배경색을 지정하세요 */
		z-index: 1;
	  } 

	
.tooltip3:hover .tooltiptext1 {
	visibility: visible;
  }
  a:hover { color: green; text-decoration: underline;}
  
  .tooltip3 {
   position: relative;
	display: inline;
	border-bottom: 0px solid black;
  font-size: 14px;
  }
  
  .tooltip3 .tooltiptext3 {
	  
	visibility: hidden;
	width: 40%;
   
	background-color: #ffffff;
	color: #e1e2e6;
	text-align: center;
	font-size: 14px;
	border-radius: 10px;
	border-style: solid;
	border-color: #0aa1bf;
	padding: 20px 1;
  
	/* Position the tooltip */
	top:50;
	right:5%;
	position: fixed;
  z-index: 1;
   
  } 
  .tooltip3 img {
	max-width: 600px;
	max-height: 1200px;
  }
  .tooltip3:hover .tooltiptext3 {
	visibility: visible;
  }
	</>
';
?>
