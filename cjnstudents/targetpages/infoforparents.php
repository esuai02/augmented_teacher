<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid=required_param('id', PARAM_INT); 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
 

echo '<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center ><td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><table align=center style="1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><tr><td><b> We transfer intelligence with CJN scaffolding</b> </td><td  width=5% ></td><td style="font-size:14px;">  KAIST TOUCH MATH powered by CJN</td></tr></table></td></tr></table></div></div>  ';
  
 echo '<br><table align=center><tr style="font-size:25px;"><td></td><td></td><td></td><td><b><a style="color:#0066cc;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a></b> 학습현황 (상담자료)</td><td></td></tr>
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">오늘 평점</td><td width=5%>###</td><td>오늘 답을 구한 문제의 정답률을 의미합니다. 만약 90%라면 정답을 선택한 문제 중 90%가 정답임을 의미합니다. 정답을 선택하지 않으면 평점이 떨어지지 않습니다. 따라서 판단한 내용이 얼마나 정확한지를 나타내게 되고 이것이 우리가 평점을 침착도라고 부르는 이유입니다.<td width=2%></td></td></tr>
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">최근 평점</td><td width=5%>###</td><td>최근 한달 동안의 평점(침착도)를 의미합니다. 학생의 공부습관이 얼마나 안정적으로 형성되었는지를 판단하기 위한 근거 중 하나로 사용됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">공부시간</td><td width=5%>###</td><td>주간 공부시간이 시스템에 의하여 측정이 됩니다. 만약 집중을 하지 않는다면 머신러닝 알고리즘에 의하여 해당 시간이 측정 결과에 제외될 수 있습니다. 치팅이 힘들므로 <b>순공시간</b>으로 볼 수 있습니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">풀이양</td><td width=5%>###</td><td>문제를 얼마나 많이 풀었는지를 나타냅니다. 문항의 수가 너무 적다면 공부의 흐름에 문제가 발생한 것으로 볼 수 있으므로 학생상담을 통하여 자세한 원인을 분석하고 동기부여, 활동방법 등을 다각적으로 검토하여 개선활동을 진행합니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">풀이효율</td><td width=5%>###</td><td>최근 1주일 동안 시간당 풀었던 문항수에 대한 시간 평균값으로 계산됩니다. 이 값이 너무 작다면 시간이 낭비되고 있거나 혼자 비효율적으로 공부가 진행되고 있을 가능성이 있습니다. 이상 데이터 발견시 조치를 취하게 됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 

 <tr><td width=2%></td><td width=10%>오늘목표</td><td width=5%>데이터</td><td>주간목표를 토대로 오늘목표를 설정합니다. 효과적인 배분인지 검토한 다음 결과가 표시됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>주간목표</td><td width=5%>데이터</td><td>중간목표를 토대로 주간목표를 설정합니다. 효과적인 배분인지 검토한 다음 결과가 표시됩니다.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>분기목표</td><td width=5%>데이터</td><td>장기계획에 쓰여있는 코스의 순서를 토대로 지정된 기간 동안 성취할 목표를 정합니다. 주어진 시간과 현재의 학년, 학교성적 목표 등을 토대로 적합성 여부를 판단할 수 있습니다. 그 결과가 표시됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>장기계획</td><td width=5%>데이터</td><td>학습코스(개념, 내신, 심화 등)의 순서를 정합니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>시간계획</td><td width=5%>데이터</td><td>주별 시간 계획입니다. 수학공부는 집중력이 유지가 된다면 연속적으로 진행되는 것이 효과적일 수 있습니다. 시간표가 효과적으로 짜여져 있는지를 검토해 볼 수 있습니다. 요일별 데이터 상에서 저조한 성취도가 도드라진다면 시간표를 변경하는 것을 고려할 수 있습니다.<td width=2%></td></td></tr>  
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 


 <tr><td width=2%></td><td width=10% style="color:blue;">공부방법 체화</td><td width=5%>데이터</td><td>개념공부, 향상노트, 고민지점 극복, 발표활동, 부스터 활동 등에 대한 전반적은 활용정도에 대한 평가지표<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">개념공부</td><td width=5%>데이터</td><td>개념공부는 내용을 이해한 다음 충분히 체화될 수 있도록 부스터 활동을 진행하게 되어 있습니다. 또한 노트검색을 통하여 능동적인 개념 복습활동이 이루어지는지도 확인이 되어져야 합니다. 또한 대표유형 수준의 개념적용 단계까지 잘 진행되는지도 점검의 대상입니다. 평가 결과가 데이터로 표시됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">향상노트 활용</td><td width=5%>데이터</td><td>풀이노트, 평가준비, 서술평가가 원활하게 진행되고 있는지 여부. 처리시간에 대한 관성이 아니라 논리훈련 중심으로 확실히 전환되어 있는지 여부 등을 토대로 평가.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">고민지점 활용</td><td width=5%>데이터</td><td>풀이과정에서 능숙도가 낮은 지점을 찾아서 반복훈련을 통하여 실질적은 논리훈련을 하고 있는지 여부를 토대로 판단. 풀이시간 단축의 루틴을 체득하였는지를 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">발표활동 활용</td><td width=5%>데이터</td><td>Think Alound 방식의 인지촉진, 인지성장 활동을 효과적으로 활용하고 있는지에 대한 평가. 학습정체 구간에 대한 효과적인 해소 및 취약지점에 대한 인지적 상태변화의 수단으로 잘 활용되는지 여부를 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">부스터 활동 활용</td><td width=5%>데이터</td><td>논리훈련의 수단으로 부스터 활동을 얼마나 잘 활용하고 있는지에 대한 평가. 개념, 공식 체화, 논리훈련 등 필요한 부분마다 효과적으로 자발적 부스터 활동을 실행하고 있는지에 여부에 대한 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">피어러닝 활용</td><td width=5%>데이터</td><td>피어러닝 환경을 셋업하여 Social learning을 통한 동기부여 및 몰입향상을 경험하고 루틴화되어 있는지 여부에 대한 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 

 <tr><td width=2%></td><td width=10%>내신테스트 현황</td><td width=5%>데이터</td><td>설정된 분기목표와 중간목표에 맞추어 효과적인 일정으로 내신테스트가 진행되고 있는지에 대한 평가. <td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>심화학습 현황</td><td width=5%>데이터</td><td>학습의 단계에 맞게 심화문제를 적절히 배합한 방식의 커리큘럼이 운영되고 있는지 여부에 대한 평가. 심화문제에 대한 학생의 도전 특성에 대한 평가.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>문항난이도 분포</td><td width=5%>데이터</td><td>최근 다루고 있는 문항들의 난이도에 대한 데이터를 표시합니다. 학생의 현재 시험대비 정도, 학교시험 난이도와의 적합성 등의 판단 근거로 사용됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 

 <tr><td width=2%></td><td width=10% style="color:blue;">학생 상담내용</td><td  width=5%>데이터</td><td>최근 학생상담 중 특이사항이 있는 내용을 표시합니다. 부모님과의 소통 및 의견 제시를 위해 활용될 수 있습니다.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">향후 전망</td><td width=5%>데이터</td><td>최근 1년동안의 학습의 추이, 습관형성, 몰입(flow) 단계 변화 등을 토대로 현재 학생의 학습 흐름이 하락, 유지, 상승인지를 판단할 수 있습니다. 학부모 상담에 활용될 수 있습니다.<td width=2%></td></td></tr>   

</table>';
 
                                                                                        
 echo '  <br> <br> <hr>
<table width="100%"><tr><td>난이도</td><td><img  src="https://play-lh.googleusercontent.com/PkNdm5zWBQoe7JVYWu_b3fyw8SxkeeF8EkZiGKc71LOAj1-BNaWREVkUf_Asqfq4_Co" width=50 ></td><td>상태</td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/departure.gif" width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flying.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flyingthroughfield.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646909102.png" width=200  ></td></tr></table><hr>
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 500px;
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
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 500px;
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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
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

	<script>

function saveproperties(Userid,Institute,Birthdate,Phone1,Phone2,Phone3,Brotherhood,Otherinstitutions,Location,Fluency,Goalstability,Assessment,Nboosters,Lmode,Ticon,Assessmode,Networkstatus,Networklevel,Team)
	{
	swal({title: \'저장되었습니다.\',});	
	
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'40\',
	"userid":Userid,       
	"institute":Institute,
	"birthdate":Birthdate,
	"phone1":Phone1,
	"phone2":Phone2,
	"phone3":Phone3,
	"brotherhood":Brotherhood,
	"otherinstitutions":Otherinstitutions,
	"location":Location,
	"fluency":Fluency,
	"goalstability":Goalstability,
	"assessment":Assessment,
	"nboosters":Nboosters,
	"lmode":Lmode,
	"ticon":Ticon,
	"assessmode":Assessmode,
	"networkstatus":Networkstatus,
	"networklevel":Networklevel,
	"team":Team,
	},
	success:function(data){
		
			}
	 })
	// alert("Hello0");
   	setTimeout(function() {location.reload(); },100);
	}

		function inputgoalstep(Eventid,Userid,Plantype,Deadline,Inputtext){   
		swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			
			  "plantype":Plantype,
			  "deadline":Deadline,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		setTimeout(function() {location.reload(); },100);
		}
 
		function updatecheck(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("완료상태가 변경되었습니다.", {buttons: false,timer: 1000});
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		}

		function updatecheck2(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 alert("체크 상태에서 새로고침하면 목록에서 사라집니다");
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
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