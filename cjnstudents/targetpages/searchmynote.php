<?php 

//include("navbar.php");

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$cid = $_GET["cid"]; 
$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    

if(strpos($url, 'php?id')!= false)$studentid=required_param('id', PARAM_INT); 
else $studentid=$USER->id;

$timecreated=time();
 
$minutesago=time()-3;
$text1 = $_GET["word1"]; 
$text2 = $_GET["word2"]; 
$text3 = $_GET["word3"]; 
$searchcourseid= $_GET["courseid"]; 

$result_input= $DB->get_record_sql("SELECT *  FROM mdl_abessi_search WHERE timecreated >'$minutesago'  ORDER BY id DESC LIMIT 1  ");
$searchtext1=$result_input->text1;
$searchtext2=$result_input->text2;
$searchtext3=$result_input->text3;

if($text1!=NULL)$searchtext1=$text1;
if($text2!=NULL)$searchtext2=$text2;
if($text3!=NULL)$searchtext3=$text3;
if($searchcourseid==NULL)$searchcourseid=$result_input->courseid;

if($searchcourseid==193)$coursetext='고등수학';
elseif($searchcourseid==201)$coursetext='중등수학';
elseif($searchcourseid==197)$coursetext='초등수학';
else $coursetext='';

if($searchtext1!=NULL || $searchtext2!=NULL || $searchtext3!=NULL)
	{
	$notelist= $DB->get_records_sql("SELECT *  FROM mdl_checklist_item WHERE type=1 AND displaytext LIKE '%$searchtext1%'  AND displaytext LIKE '%$searchtext2%' AND displaytext LIKE '%$searchtext3%' ORDER BY id");
	  // mysql full text search로 속도 향상 가능
	$results= json_decode(json_encode($notelist), True);
	unset($value);
	foreach($results as $value)
		{
 		$checklistid=$value['checklist']; 
		$module= $DB->get_record_sql("SELECT *  FROM mdl_course_modules WHERE instance='$checklistid' ORDER BY id DESC LIMIT 1 ");
		$courseid=$module->course;
		if($searchcourseid==$courseid || $searchcourseid==0)
			{
			if($value['redirect']!=NULL)$linkurl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.str_replace('jnrsorksqcrark','jnrsorksqcrark_user'.$studentid,$value['redirect']).'&studentid='.$studentid;
			else $linkurl= $value['linkurl'].'&itemid='.$value['id'];
			$displaytext= $value['displaytext']; 
			if($courseid==193)$ctext='고등수학';
			elseif($courseid==201)$ctext='중등수학';
			elseif($courseid==197)$ctext='초등수학';
			 
			$displaytext=str_replace('개념도약',$ctext,$displaytext);
			if($courseid==193)$Mynotes1.='<tr><td><a href="'.$linkurl.'" target="_blank">'.$displaytext.'</a></td><td></td><td> </td></tr>';
			elseif($courseid==201)$Mynotes2.='<tr><td><a href="'.$linkurl.'" target="_blank">'.$displaytext.'</a></td><td></td><td> </td></tr>';
			elseif($courseid==197)$Mynotes3.='<tr><td><a href="'.$linkurl.'" target="_blank">'.$displaytext.'</a></td><td></td><td> </td></tr>';
			}
		}
	$Mynotes=$Mynotes3.'<tr><td><hr></td><td><hr></td><td><hr></td></tr>'.$Mynotes2.'<tr><td><hr></td><td><hr></td><td><hr></td></tr>'.$Mynotes1;
	}
if($searchtext1!=NULL){$searchtext.=$searchtext1.'&nbsp;&nbsp;&nbsp;'; $addword.='word1='.$searchtext1;}
if($searchtext2!=NULL){$searchtext.=$searchtext2.'&nbsp;&nbsp;&nbsp;'; $addword.='&word2='.$searchtext2;}
if($searchtext3!=NULL){$searchtext.=$searchtext3; $addword.='&word3='.$searchtext3;}
$addword.= '&courseid='.$searchcourseid;

if($searchtext1!=NULL)$searchtext.=' &nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/searchmynote.php?'.$addword.'">링크</a>';
if($text1!=NULL)$searchtext.='&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/searchmynote.php">초기화</a>';

$showinterface='				<div class="col-md-6"> 
							<div class="card">							
								<div class="card-body"><!--user foreach to show recent 20 inputs-->
								
									<table width=60% align=center class="table table-head-bg-info mt-8">
										<thead>
											<tr>
											<th width=80% scope="col" style="text-align:center;"><b>'.$coursetext.'</b>&nbsp;&nbsp; 키워드&nbsp;&nbsp;  '.$searchtext.' </th><th  width=10% scope="col"></th><th  width=10% scope="col"></th>
											</tr>'.$Mynotes.'
										</thead>
										<tbody>
										 
										</tbody>
									</table>
								</div> 
							</div>
						</div>';


 echo ' 	 
					<div class="row">
						<div class="col-md-6"> 
							<div class="card">							
								
								<p align=center><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" width=60%></p>
									<table align=center  class="table table-head-bg-info mt-8">
										<thead>
											<tr>
											<th  scope="col" style="text-align:center;"></th><th scope="col"></th><th  scope="col"></th>
											</tr>
										</thead>
										<tbody>
										<tr> <td><span style="font-size:30;" type="button" class="btn btn-default" onclick="javascript:history.go(-1)"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647374680.png width=40></span></td><td><input style="height:30;font-size:16;" type="text" class="form-control input-square" id="squareInput1" name="squareInput1"  placeholder="검색단어 입력"></td><td><input  style="height:30;font-size:16;" type="text" class="form-control input-square" id="squareInput2" name="squareInput2"  placeholder="검색단어 입력"></td><td><input  style="height:30;font-size:16;"  type="text" class="form-control input-square" id="squareInput3" name="squareInput3"  placeholder="검색단어 입력"></td>
										<td> <div class="select2-input" style="height:30;font-size:16;"><select style="height:30;font-size:16;" id="basic1" name="basic1" class="form-control"  ><h3><option selected></option><option value="197">초등</option><option value="201">중등</option><option value="193">고등</option></h3></select> </div></td>
										<td valign=bottom><span style="height:40;" id="alert_search" onclick="searchnote($(\'#squareInput1\').val(),$(\'#squareInput2\').val(),$(\'#squareInput3\').val(),$(\'#basic1\').val()); "><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1640176934.png width=40></span></td>
										</tr> 
										</tbody>
									</table>
								
							</div>
						</div>'.$showinterface.'

					</div> 		 
				</div>
			</div>
		 </div>';

//include("quicksidebar.php");
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
 	function searchnote(Inputtext1,Inputtext2,Inputtext3,Courseid)
		{ 
		 
		        $.ajax({
		            url:"search.php",
			type: "POST",
		            dataType:"json",
 			  data : {
		              "inputvalue1":Inputtext1,
		              "inputvalue2":Inputtext2,
		              "inputvalue3":Inputtext3,
			"courseid":Courseid,
		               },
		            success:function( ){
				 
			            }
		        });
		setTimeout(function(){location.reload();},200);  
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
