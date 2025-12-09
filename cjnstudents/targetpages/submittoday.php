<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
global $DB, $USER;
include("navbar.php");
 
$tbegin= $_GET["tb"]; 
$maxtime=time()-$tbegin;
$indicator= $DB->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
 
 
$gradedright = array_filter($graded_attempts, function($attempt) {return $attempt->state == 'gradedright';});
$nright = count($gradedright);
$gradedwrong = array_filter($graded_attempts, function($attempt) {return $attempt->state == 'gradedwrong' || $attempt->state == 'gradedpartial';});
$nwrong = count($gradedwrong);
$gaveup = array_filter($graded_attempts, function($attempt) {return $attempt->state == 'gaveup';});
$ngaveup = count($gaveup);


$timecreated=time();

// get mission list
$timestart2=time()-$tbegin;
$adayAgo=time()-43200;
$aweekAgo=time()-604800;
$timestart3=time()-86400*14;
         
echo '<table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center >
<td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><b style="color:black;">귀가검사 &nbsp;&nbsp;</b></td></td>
<td style="width: 15%;"><div class="select2-input"><select id="basic1" name="basic" class="form-control" > <option value="10" '.$rate10.'>주간목표 10%완료</option> <option value="20" '.$rate20.'>주간목표 20%완료</option> <option value="30" '.$rate30.'>주간목표 30%완료</option> <option value="40" '.$rate40.'>주간목표 40%완료</option> <option value="50" '.$rate50.'>주간목표 50%완료</option> <option value="60" '.$rate60.'>주간목표 60%완료</option> <option value="70" '.$rate70.'>주간목표 70%완료</option> <option value="80" '.$rate80.'>주간목표 80%완료</option> <option value="90" '.$rate90.'>주간목표 90%완료</option> <option value="100" '.$rate100.'>주간목표 100%완료</option></select></div></td>
<td style="width: 2%;"></td>'; 

if($checkgoal->type==='검사요청') echo '<td style="width: 25%;height:20px;"><div><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'" ></div></td>  <td style="width:5%;font-size: 20px; "><button id="clicksubmit" type="image" onclick="submittoday(21,'.$studentid.','.$pcomplete.',$(\'#basic1\').val(),$(\'#basic3\').val(),$(\'#basic5\').val(),$(\'#squareInput\').val())">제출</button></td><td style="font-size: 20px;width: 10%; text-align:center;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today"><img src=https://mathking.kr/Contents/IMAGES/improve.png width=80></a></td> <td style="text-align:center;font-size: 20px; width: 10%;"><div style="text-align:center;font-size: 20px; " class="tooltip2">보강차감 (총 : '.$reducetime.' 분) 가능 <span class="tooltiptext2"><table style="" align=center>'.$eventtext.'</table></span></div>
<button type="image" onclick="updatetime(93,'.$studentid.','.$reducetime.','.$tamounttotal.')">적용</button></td></tr></table></div></div>  <br>';
else echo '<td style="height:20px;"><div><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'" ></div></td> <td width=2%></td> <td style="width:10%;font-size: 20px; "><button id="clicksubmit" type="image" onclick="submittoday(21,'.$studentid.','.$pcomplete.',$(\'#basic1\').val(),$(\'#basic3\').val(),$(\'#basic5\').val(),$(\'#squareInput\').val()) ">제출</button></td> <td style="font-size: 20px;width: 10%; text-align:center;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today"><img src=https://mathking.kr/Contents/IMAGES/improve.png width=80></a></td><td style="text-align:center;font-size: 20px; width: 10%;"><div style="text-align:center;font-size: 20px; " class="tooltip2">보강차감 (총 : '.$reducetime.' 분) 가능<span class="tooltiptext2"><table style="" align=center>'.$eventtext.'</table></span></div></td> </tr></table></div></div>  <br>';
 

echo '<script>
function deletequiz(Attemptid)
	{
		swal({
					title: \'시도된 퀴즈를 삭제하시겠습니까 ?\',
					text: "원하지 않으시면 취소 버튼을 눌러주세요",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'취소\',
							className: \'btn btn-danger\'
						}      			

					}
		}).then((willDelete) => {
					if (willDelete) {
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
					 	data : {
						"eventid":\'300\',
						"attemptid":Attemptid,
					 		},
						 });
					setTimeout(function() {location.reload(); },100);
					} else {
					swal("취소되었습니다.", {buttons: false,timer: 500});
					}
				});	 				 
	}
function addquiztime(Attemptid)
	{		 
 
			var text1="-30";
			var text2="-20";
			var text3="-10";
			var text4="-5";
			var text5="+5";
			var text6="+10";
			var text7="+20";
			var text8="+30";
			var text9="입력";

			swal("퀴즈 시간변경",  "응시시간을 적절히 늘리거나 줄이면 집중력이 향상됩니다.",{
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
				visible: true,
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
				swal("","퀴즈종료 시간이 " + text1+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text1,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
				swal("","퀴즈종료 시간이 " + text2+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text2,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
				swal("","퀴즈종료 시간이 " + text3+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text3,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch4":
				swal("","퀴즈종료 시간이 " + text4+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text4,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
				 case "catch5":
					swal("","퀴즈종료 시간이 " + text5+"분 연장되었습니다.", "success");
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'301\',
							"inputtext":text5,	
							"attemptid":Attemptid,	
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch6":
					swal("","퀴즈종료 시간이 " + text6+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'301\',
						"inputtext":text6,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
				 case "catch7":
					swal("","퀴즈종료 시간이 " + text7+"분 연장되었습니다.", "success");
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'301\',
							"inputtext":text7,	
							"attemptid":Attemptid,	
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch8":
					swal("","퀴즈종료 시간이 " + text8+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'301\',
						"inputtext":text8,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
 			   case "catch9":
				swal({
					title: \'퀴즈 추가시간을 입력해 주세요\',
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
					swal("","퀴즈종료 시간이 " + Inputtext+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'301\',
					"inputtext":Inputtext,	
					"attemptid":Attemptid,				 
					},
					success:function(data){
					 }
					 })
				});	 
				location.reload();
				break;
 			   
			}
		})
	}
function updatetime(Eventid,Userid,Selecttime,Totaltime)
	{   
	if(Totaltime>=5)
		{
		alert("차감 가능한 보강시간이 없습니다. 대신 10분 일찍 귀가 가능합니다." );
		}
	else
		{
		var Inputtext= \''.$eventtext.'\';
		alert("총" + Selecttime + "분이 보강시간에서 차감됩니다. 내역은 다음과 같습니다. (" + Inputtext + ")" );
		swal("적용되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
				type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "selecttime":Selecttime,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		}
	}


function ChangeCheckSteps(Eventid, Userid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
		}
	swal({title: \'안전하게 전달하였습니다.\',});	
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
	data : {
	 "eventid":Eventid,
	"userid":Userid,       
	"checkimsi":checkimsi,
	},
	})
	location.reload();
	 		 				 
	}
function submittoday(Eventid,Userid,Pcomplete,Confident,Ask,Review,Inputtext)
	{ 
	var Timefilled= \''.$timefilled.'\';
	var Nask= \''.$NNnask.'\';
	var Nreview= \''.$NNreview.'\';
	var Check_reply= \''.$Ncheckreply.'\'; 
	
	 
	if(Inputtext=="")swal("잠깐 !","다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !", {buttons: false,timer: 5000});
	else 
		{  
		swal({		 
		title: "시간 : " + Timefilled +  "% | 질문 : " + Nask +  "개 | 응답 : " + Check_reply + "개",
			type: \'warning\',
			buttons:{
				confirm: {
					text : \'제출하기\',
					className : \'btn btn-primary\'
				},
				cancel: {
					visible: true,
					text : \'취소\',
					className: \'btn btn-danger\'
				}      			

			}
		}).then((willDelete) => {
					if (willDelete) {
							$.ajax({
								url:"database.php",
								type: "POST",
								dataType:"json",
								data : {"userid":Userid,
								"eventid":Eventid,
								"pcomplete":Pcomplete,
								"confident":Confident,
								"ask":Ask,
								"review":Review,
								"inputtext":Inputtext,
								},
								success:function(data){}
							})
						location.reload(); 
						} else
				 		{
						swal("취소되었습니다.", {buttons: false,timer: 500});
						}
				});	 
		}

	}




		function RubricCheckBox(Eventid,Userid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 
		   $.ajax({
		        url: "checkrubric.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		}	 

		function ChangeCheckBox(Eventid,Userid, Goalid,Checkvalue){
		    var checkimsi = 0;
		    var Nextgoal=\''.$checkgoal->comment.'\';
		    if(Eventid==3 && Nextgoal=="" && Checkvalue==true)
				{
				swal("잠깐 !","다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !", {buttons: false,timer: 5000});
				location.reload(); 
				}
		    else
				{
				if(Checkvalue==true){
					checkimsi = 1;
					}
					swal("처리되었습니다.", {
						buttons: false,
						timer: 500,
					});
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
									"goalid":Goalid,
									"checkimsi":checkimsi,
									"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	 
				} 
		 
		}
		function ContinueLearn(Eventid,Userid, Goalid,Checkvalue){
		    var checkimsi = 0;
			if(Checkvalue==true){
				checkimsi = 1;
				}
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
						"goalid":Goalid,
						"checkimsi":checkimsi,
						"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	
				swal("귀가검사 결과 보충활동이 발견되었습니다.", {buttons: false,timer: 3000});
				 location.reload(); 
		 
		}
		function quickReply(Eventid,Userid,Goalid){
		 
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
						"goalid":Goalid,
						"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	
					
					var Alerttime= \''.$checkgoal->alerttime.'\';
					if(Alerttime==0)swal("질문이 전달되었습니다.","기다리는 동안 후속 학습을 진행해 주세요.", {buttons: false,timer: 3000});
					else swal("피드백을 시작합니다.","충분히 이해가 될 수 있도록 유연하게 대화해 보세요", {buttons: false,timer: 3000});
					location.reload(); 
		 
		}
		function Resttime(Eventid,Userid, Goalid,Checkvalue)
			{
		    var checkimsi = 0;
		    var Timeleft= \''.$beforebreak.'\';
		    if(Checkvalue==true)
				{
				checkimsi = 1;
				if(Timeleft<0)
					{
					Swal.fire({
					backdrop: true,position:"top-center",width:1200,
					  customClass: {
									container: "my-background-color"
								   },
					html:
					\'<table align=center ><tr><td align=center><br><h5><b>정보입력이 멈춘 상태의 DMN 휴식</b>을 취하면 공부가 가속화됩니다 ! (<a href="https://brunch.co.kr/@kissfmdj/1"target="_blank">자세히</a>) </h5><br></td></tr><tr><td><iframe style="border: 1px none; z-index:2; width:60vw;height:50vh; margin-left: -30px;margin-top: 0px;"   src="https://e.ggtimer.com/10minutes" ></iframe></td></tr></table>\',
					})
					
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
							data : {
							"userid":Userid,       
										"goalid":Goalid,
										"checkimsi":checkimsi,
										"eventid":Eventid,
										 
							},
							success:function(data){
							 }
						})	 
				
					}
				else
					{
					swal("힘내세요 ! " + Timeleft + "분 더 공부하시면 휴식을 취하실 수 있습니다.", {buttons: false,timer: 3000});
					setTimeout(function() {location.reload(); },3000);
					}
					
	 			}
	 		else
				{
				swal("처리되었습니다.", {
						buttons: false,
						timer: 500,
						});
				if(Timeleft<0)
						{
						$.ajax({
								url:"check.php",
								type: "POST",
								dataType:"json",
								data : {
								"userid":Userid,       
								"goalid":Goalid,
								"checkimsi":checkimsi,
								"eventid":Eventid,
											 
								},
								success:function(data){
								 }
							})
						}
					else
							{
							
							$.ajax({
								url:"check.php",
								type: "POST",
								dataType:"json",
								data : {
								"userid":Userid,       
								"goalid":Goalid,
								"checkimsi":checkimsi,
								"eventid":\'331\',
											 
								},
								success:function(data){
								 }
							})	
						}
				}
				
		}
		function ChangeCheckBoxWeek(Eventid,Userid, Goalid,Checkvalue)
			{
		    var checkimsi = 0;
		    if(Checkvalue==true)
				{
		        checkimsi = 1;
				swal({
					title: \'한 주간 공부과정에 대한 한줄 평을 남겨주세요\',
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
  					swal("", "입력된 내용 : " + Inputtext, "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"userid":Userid,       
		                		"goalid":Goalid,
		                		"checkimsi":checkimsi,
		                 		"eventid":Eventid,
		                 		"inputtext":Inputtext,
					},
					success:function(data){
					 }
					 })
				 
				}
				);
			  }
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
		function AddReview(Eventid,Userid,Attemptid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,       
		                "attemptid":Attemptid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
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


?>
<html>
 
<style>
.my-background-color .swal2-container {
  background-color: black;
}

.feel {
  margin: 0px 5px;
  background-color: white;
  height:30px;
}
</style>
</html>
