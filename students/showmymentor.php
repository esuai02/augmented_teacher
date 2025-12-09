<?php 

//include("p_navbar.php");
$timecreated=time();
 
 
echo '
    <style>
        .form-control {
            width: 100%; /* 적절한 너비 설정 */
            padding: 20px; /* 충분한 패딩 */
            margin: 15px 0; /* 여백 */
            border: 3px solid #ddd; /* 경계선 스타일 */
            border-radius: 5px; /* 경계선 둥글게 */
            box-sizing: border-box; /* 박스 크기 계산 방식 */
        }

        .form-control:focus {
            border-color: #007bff; /* 포커스 시 변경될 색상 */
            outline: none; /* 기본 윤곽선 제거 */
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* 포커스 시 그림자 효과 */
        }

        .form-control:hover {
            border-color: #0056b3; /* 호버 시 변경될 색상 */
        }

		#connectButton {
			background-color: #4CAF50; /* 녹색 배경 */
			color: white; /* 흰색 텍스트 */
			padding: 10px 22px; /* 상하, 좌우 패딩 */
			text-align: center; /* 텍스트 중앙 정렬 */
			text-decoration: none; /* 텍스트 꾸밈 없음 */
			display: inline-block; /* 인라인-블록 표시 */
			font-size: 16px; /* 폰트 크기 */
			margin: 4px 2px; /* 마진 */
			cursor: pointer; /* 커서 포인터 */
			border: none; /* 테두리 없음 */
			border-radius: 8px; /* 둥근 모서리 */
			transition-duration: 0.4s; /* 전환 효과 지속 시간 */
		}
		
		#connectButton:hover {
			background-color: #45a049; /* 호버시 색상 변경 */
		}
		
    </style>'; 
	
 echo ' 	  						
					<div class="row">
						<div class="col-md-12"> 
							<div class="card">	
								<div class="card-body"><!--user foreach to show recent 20 inputs-->
								<p align=center><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" width=100%></p>
									<table   align=center>
										<thead>
											<tr>
											<th width=85% scope="col" style="text-align:center;"> </th></tr>
										</thead>
									
										<tr><td><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="사용자 아이디를 입력해 주세요"></td></tr> 
										<tr><td valign=bottom align=center><button type="button" id="connectButton" onclick="studentpage($(\'#squareInput\').val()); ">연결하기</button></td></tr>
									 
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
 	function studentpage(Inputtext)
			{ 
		    $.ajax({
		            url:"connectuser.php",
					type: "POST",
		            dataType:"json",
					data : {
						"inputvalue":Inputtext,
							},
					success:function(data){
										var Mentorid=data.mentorid;
										window.location.href="https://chat.openai.com/g/"+Mentorid; 
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