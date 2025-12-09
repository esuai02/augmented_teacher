<?php  
echo '
$(\'#alert_nextpage\').click(function(e) {
				var Userid= \''.$studentid.'\'; 
				var Username;
				var Fbtype;
				var Fbgoal;
				var Fbtext;
				var Fburl;
				var Prepareimg;
				var Summary;
              			 $.ajax({
					url: "/home/moodle/public_html/moodle/local/augmented_teacher/whiteboard/almtyroutine.php",
					type: "POST",
					dataType:"json",
              				data : {	 
				        	"userid":Userid,
               			        	}, 
                				success:function(data) 
						{
						
						Username=data.username;
						Fbtype=data.fbtype;
						Fbgoal=data.fbgoal;
						Fbtext=data.fbtext;
						Fburl=data.fburl;	
						Prepareimg=data.prepareimg;
						Summary=data.summary;	
						alert(Fburl);


						swal({
								title: Username+\'의 \' + Fbtype ,
								text: Fbgoal+Fbtext +\'참고 :\' + Summary + \')\',
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
						 
								 window.location.href =Fburl;	 					 
								} else {
									swal("취소되었습니다.", {
										buttons : {
											confirm : {
												className: \'btn btn-success\'
											}
										}
									});
								}
							});
						}
            	   		  	      });
			});
'; 
?>