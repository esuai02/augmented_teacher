<?php 

include("p_navbar.php");
$timecreated=time();
 
 echo ' 	  						
					<div class="row">
						<div class="col-md-12"> 
							<div class="card">	
								<div class="card-body"><!--user foreach to show recent 20 inputs-->
								<p align=center><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" width=100%></p>
									<table width=100% align=center class="table table-head-bg-info mt-12">
										<thead>
											<tr>
											<th width=85% scope="col" style="text-align:center;"> </th><th  width=0% scope="col"></th><th  width=15% scope="col"></th>
											</tr>
										</thead>
										<tbody>
										<tr> <td><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="이름을 입력해 주세요 (성 제외)"></td>  
										<td><div class="select2-input" style="font-size: 2.0em;padding-top:15px;"><select id="basic1" name="basic1" class="form-control"  ><h3><option value="1"  selected></option> </h3></select> </div></td><td valign=bottom><button type="button" style="height:40;" id="alert_updateuserinfo" onclick="studentpage($(\'#squareInput\').val(),$(\'#basic1\').val()); ">발표 페이지로 이동하기</button></td>
										</tr> 
										</tbody>
									</table>
								</div> 
							</div> 

						</div> 		 
				</div></div>
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

<script>
 	function studentpage(Inputtext,Type)
		{ 
		   
		        $.ajax({
		            url:"checkuserid2.php",
			type: "POST",
		            dataType:"json",
 			  data : {
		              "inputvalue":Inputtext,
		              "type":Type,
		               },
		            success:function(data){
			 
				var Userid1=data.userid1;
				var Userid2=data.userid2;
				var Userid3=data.userid3;
				var Userid4=data.userid4;
				var Userid5=data.userid5;
  			 				 
				var Username1=data.username1;
				var Username2=data.username2;
				var Username3=data.username3;
				var Username4=data.username4;
				var Username5=data.username5;

				if(Userid2==0)
					{
					window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid1+"&tb=43200"; 
				             }
				else if(Userid3==0)
					{  		 
						swal("사용자 선택하기",  "",{
						  buttons: {
						    catch1: { text:  Username1, value: "catch1",className : \'btn btn-primary\'},
						    catch2: { text:  Username2, value: "catch2",className : \'btn btn-primary\'},			 		
						    cancel: {
							text: "취소",
							visible: true,
							className: \'btn btn-Success\'
							}, 
			  			},
						})
						.then((value) => {
						  switch (value) {
						     case "defeat":
			 			     swal("취소되었습니다.", {buttons: false,timer: 500});
			  			    break;			 
 			 			  case "catch1": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid1+"&tb=43200"; break;
 			 			  case "catch2": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid2+"&tb=43200"; break; 			 		
 						  default:
						      swal("취소되었습니다.", {buttons: false,timer: 500});
						       }
						});
					}
				else if(Userid4==0)
					{  		 
						swal("사용자 선택하기",  "",{
						  buttons: {
						    catch1: { text:  Username1, value: "catch1",className : \'btn btn-primary\'},
						    catch2: { text:  Username2, value: "catch2",className : \'btn btn-primary\'},
			 			    catch3: { text:  Username3, value: "catch3",className : \'btn btn-primary\'}, 
						    cancel: {
							text: "취소",
							visible: true,
							className: \'btn btn-Success\'
							}, 
			  			},
						})
						.then((value) => {
						  switch (value) {
						     case "defeat":
			 			     swal("취소되었습니다.", {buttons: false,timer: 500});
			  			    break;			 
 			 			  case "catch1": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid1+"&tb=43200"; break;
 			 			  case "catch2": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid2+"&tb=43200"; break;
 			 			  case "catch3": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid3+"&tb=43200"; break;
 						  default:
						      swal("취소되었습니다.", {buttons: false,timer: 500});
						       }
						});
					}
				else if(Userid5==0)
					{  		 
						swal("사용자 선택하기",  "",{
						  buttons: {
						    catch1: { text:  Username1, value: "catch1",className : \'btn btn-primary\'},
						    catch2: { text:  Username2, value: "catch2",className : \'btn btn-primary\'},
			 			    catch3: { text:  Username3, value: "catch3",className : \'btn btn-primary\'},
			  			    catch4: { text:  Username4,value: "catch4",className : \'btn btn-primary\'},			   			   
						    cancel: {
							text: "취소",
							visible: true,
							className: \'btn btn-Success\'
							}, 
			  			},
						})
						.then((value) => {
						  switch (value) {
						     case "defeat":
			 			     swal("취소되었습니다.", {buttons: false,timer: 500});
			  			    break;			 
 			 			  case "catch1": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid1+"&tb=43200"; break;
 			 			  case "catch2": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid2+"&tb=43200"; break;
 			 			  case "catch3": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid3+"&tb=43200"; break;
 			 			  case "catch4": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid4+"&tb=43200"; break;
 						  default:
						      swal("취소되었습니다.", {buttons: false,timer: 500});
						       }
						});
					}
				else
					{  		 
						swal("사용자 선택하기",  "",{
						  buttons: {
						    catch1: { text:  Username1, value: "catch1",className : \'btn btn-primary\'},
						    catch2: { text:  Username2, value: "catch2",className : \'btn btn-primary\'},
			 			    catch3: { text:  Username3, value: "catch3",className : \'btn btn-primary\'},
			  			    catch4: { text:  Username4,value: "catch4",className : \'btn btn-primary\'},
			   			    catch5: { text:  Username5,value: "catch5",className : \'btn btn-primary\'},
 
						    cancel: {
							text: "취소",
							visible: true,
							className: \'btn btn-Success\'
							}, 
			  			},
						})
						.then((value) => {
						  switch (value) {
						     case "defeat":
			 			     swal("취소되었습니다.", {buttons: false,timer: 500});
			  			    break;			 
 			 			  case "catch1": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid1+"&tb=43200"; break;
 			 			  case "catch2": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid2+"&tb=43200"; break;
 			 			  case "catch3": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid3+"&tb=43200"; break;
 			 			  case "catch4": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid4+"&tb=43200"; break;
 			 			  case "catch5": window.location.href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id="+Userid5+"&tb=43200"; break;
 						  default:
						      swal("취소되었습니다.", {buttons: false,timer: 500});
						       }
						});
					}
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

		$("#basic1").select2({
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