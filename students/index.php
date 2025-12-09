<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentindex','$timecreated')");
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
 
$timestart3=time()-86400;
 
// 개념노트

$timestart2=time()-43200;
$aweekago=time()-604800;  //AND
 
$reviewnotes=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND timemodified > '$aweekago' AND mtype='audio' ORDER BY timemodified DESC LIMIT 30"); 

$rvresult = json_decode(json_encode($reviewnotes), True);
unset($rvvalue);									 
foreach($rvresult as $rvvalue)
	{
	$url=$rvvalue['url'];
	$cnttitle=$rvvalue['contentstitle'];
	$nreview=$rvvalue['nreview'];
	$nlastview=round(($timecreated-$rvvalue['timemodified'])/86400,0);
 
	$reviewhistory.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$url.'"target="_blank">🎧 복습 : '.$cnttitle.' ('.$nreview.') </a></td></tr>';  //___('.$nlastview.'일 전)
}

//echo 'nalt:'.$naltnotes;
//$DB->execute("UPDATE {abessi_indicators} SET nalt='$naltnotes' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
$timestart=$timecreated-604800*2; 
echo ' 			 			<div class="col-md-12">
							<div class="card">
								  <div class="card-title"><div class="card-body"> 
									 
									  ';
										// get mission list
										$trecent2=time()-31104000;  // 1year ago
										$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE  timecreated>'$trecent2' AND userid='$studentid' ORDER by norder ASC ");
										$result = json_decode(json_encode($missionlist), True);
										 
										unset($value);
										foreach($result as $value)
										{
										$mtid=0;
										$mid=$value['id'];
										$subject=$value['subject'];	
										$deadline= $value['deadline']; 	
										$unixtimedeadline=strtotime($deadline);	
										if($unixtimedeadline > time()+31536000 || $unixtimedeadline < time()-31536000)continue;
										$passgrade=$value['grade'];
										$mtname=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' ");
										$contentslist=$mtname->contentslist;
										$subjectname=$mtname->name;
										$mtid=$mtname->mtid;
										$subjectname=str_replace("개념 :","",$subjectname);
										$subjectname=str_replace("심화 :","",$subjectname);
										$subjectname=str_replace("내신 :","",$subjectname);
										$subjectname=str_replace("수능 :","",$subjectname);								
										
										if($value['complete']==0)
											{
											if($mtid==1 ||$mtid==7)
												{
												$mt01.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$subject.'&nch=1&studentid='.$studentid.'&type=init"target="_blank"><img loading="lazy" style="margin-bottom:4px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png width=20> GPT '.$subjectname.' </a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=math&sbjt=h3&studentid='.$studentid.'&nch=9"target="_blank"><img src=https://ankiweb.net/logo.png width=20></a></td>
												<td width=4% style=""></td><td  width=30% align="left" style="font-size:12pt">  </td><td width=20% style="font-size:10pt">합격 : '.$passgrade.'점</td>
												<td width=4%><div class="form-check"> 완료 &nbsp;<label  style="margin-bottom:5px;"  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==2)
												{
												if(strpos($subjectname,'초등')!==false)$mt02.='<tr> <td width=30% align="left"   style="font-size:12pt"><img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
												else $mt02.='<tr><td   width=30% align="left"  style="font-size:12pt"><img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==3)
												{
												$mt03.='<tr> <td  width=30% align="left"  style="font-size:12pt"><img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label  style="margin-bottom:5px;"  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==4)
												{
												$mt04.='<tr><td  width=30% align="left"  style="font-size:12pt"><img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											} 
										else 
											{
										 	if($mtid==1 ||$mtid==7)
												{
												$mt05.='<tr><td  width=30% align="left" style="color:grey;font-size:10pt"><img loading="lazy" style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">개념 : '.$subjectname.'</a> &nbsp;  <a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=math&sbjt=h3&studentid='.$studentid.'&nch=9"target="_blank"><img src=https://ankiweb.net/logo.png width=20></a></td><td width=4% style=""></td><td width=20% style="font-size:10pt">합격 : '.$passgrade.'점</td>
												<td width=4%><div class="form-check"> 추가 &nbsp;<label  style=""  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==2)
												{
												if(strpos($subjectname,'초등')!==false)$mt06.='<tr> <td width=30% align="left"   style="font-size:10pt"><img loading="lazy" style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">심화 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
												else $mt06.='<tr><td   width=30% align="left"  style="color:grey;font-size:10pt"><img loading="lazy" style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">심화 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==3)
												{
												$mt07.='<tr><td  width=30% align="left"  style="color:grey;font-size:10pt"><img loading="lazy" style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">내신 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label  style=""  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											elseif($mtid==4)
												{
												$mt08.='<tr><td  width=30% align="left"  style="color:grey;font-size:10pt"><img loading="lazy" style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">수능 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
												}
											 
											}
										}
										if($role!=='student')$inspect_fixnotes=' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a style="text-decoration:none;color:white;font-size:18px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/beactivelearner.php?userid='.$studentid.'"target="_blank">귀가평가</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a style="text-decoration:none;color:white;font-size:14px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$studentid.'"target="_blank">오답노트 검사</a> ';
 										echo '<table width=100%  > <tr><th width=2%> </th><th width=40% > </th>  <th width=3%> </th> <th></th></tr>
													<tr><td></td><td   valign=top >
													<table width=100%  valign=top  ><tr><th width=5%></th><th width=80%></th></tr> 
													 												
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 개념</td><td align=right style="background-color:#3383FF;color:white;">  &nbsp;&nbsp;&nbsp;<a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=7&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;<a style="color:white" href="http://mathking.kr/moodle/local/augmented_teacher/twinery/topiclearning.html"target="_blank">도움말</a>&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt01.'</table><hr><b style="font-size:16px;">개념복습 추천 </b><hr>
													<table width=100% align=center >'.$reviewhistory.'</table><hr></td></tr>   
													 
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 심화</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=2&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;<a style="color:white" href="http://mathking.kr/moodle/local/augmented_teacher/twinery/deeperlearning.html"target="_blank">도움말</a>&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$mt02.'</table></td></tr>   
													 
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 내신</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=3&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt03.'</table></td></tr>   
													 
													<tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 수능</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=4&cid=0">추가 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt04.'</table></td></tr>   
													<tr><td align=center  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 후속</td><td align=right style="background-color:#3383FF;color:white;"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'">장기계획 설정 <i style="color:white;" class="flaticon-plus"></i></a> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
													<tr><td></td><td  valign=top ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt05.$mt06.$mt07.$mt08.'</table></td></tr></table>
													
													<table width=100% align=center style="background-image:url(https://mathking.kr/moodle/local/augmented_teacher/IMAGES/restore.png);background-size:cover;">'.$goalhistory0.$goalhistory1.'</table></td><td></td>
													<td valign=top > 
													<table width=100% valign=top>
													<tr><td align=center  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#0082D8;color:white;font-size:14pt;height:40px;"> &nbsp;KTM 서술평가 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style="text-decoration:none;color:white;font-size:14px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid='.$studentid.'"target="_blank">출제목록</a> '.$inspect_fixnotes.'</td></tr>
													<tr><td>';
													
													include("SPEC Intelligence.php"); 
													echo '</td></tr>
													
													</table> 
											 
													 </table></td></tr></table></td>
													</tr></table>
										  <h4 class="card-title"><div style=" font:bold 1.2em/1.0em 맑은고딕체;text-align: center ;color:blue;" > '.$todaygoal.' </h4></div> </div></div></div></div> ';
 
 								
//include("brainportal.php");
echo ' </div></div></div></div></div>';

include("quicksidebar.php");
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

 

	<script>
		function inputmission(Eventid,Userid,Inputtext,Deadline){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "inputtext":Inputtext,
			  "deadline":Deadline,		 
		               },
		            success:function(data){
			            }
		        })

		}
		function changecheckbox(Eventid,Userid,Missionid, Checkvalue){
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
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		 location.reload();
		}
		function ChangeCheckBox2(Eventid,Userid, Wboardid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,       
		                "wboardid":Wboardid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		}
		function checkwhiteboard(Eventid,Userid,Wboardid, Checkvalue){
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
';
$pagetype='mystudy';
include("../LLM/postit.php");
?>
