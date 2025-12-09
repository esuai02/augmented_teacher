 
/* echo ' 
	<script>
	alert("aaaa");
	var statusIntervalId = window.setInterval(update, 5000);
	var isonfocus=0;  

	function update() 
		{
		
		var Userid=\''.$studentid.'\';
		var Contextid=\''.$contextid.'\';
		var Currenturl=\''.$currenturl.'\';
		var Wboardid=\''.$id.'\';
		
		window.onfocus = function()
			{
			isonfocus=1;  
			} 
		window.onblur = function()
			{  
			isonfocus=0;  
			}   
		if(isonfocus==1)
			{
			$.ajax({
					url: "/moodle/local/augmented_teacher/LLM/updatemsg.php",
					type: "POST",
					dataType: "json",
					data : {
					"userid":Userid,
					"eventid":\'1\',  // 메세지 도착 체크
					"wboardid":Wboardid,
					"contextid":Contextid,	
					"currenturl":Currenturl,
					},
				success: function (data) 
					{
					if(data.mid=="1")   
						{
						location.reload();
						}//end of if 
					else if(data.mid=="2")   
						{
						var url=data.context+"?"+data.url;
						swal({
							title: \'메세지가 도착하였습니다.\',
							text: data.feedback,
							type: \'warning\',
							buttons:{
								confirm: {
									text : \'확인\',
									className : \'btn btn-success\'
								},
								cancel: {
									visible: true,
									text : \'새창으로\',
									className: \'btn btn-danger\'
								}      			
							},
							}).then((willDelete) => {
							if (willDelete) {
								$.ajax({
									url: "/moodle/local/augmented_teacher/LLM/updatemsg.php",
									type: "POST",
									dataType:"json",
									data : {
									"eventid":\'11\', // 학생 확인선택 적용
									"id":data.id,	
									},
									success:function(data){
									alert("success");
									}
									});								
								} 
							else 
								{
								$.ajax({
									url: "/moodle/local/augmented_teacher/LLM/updatemsg.php",
									type: "POST",
									dataType:"json",
									data : {
									"eventid":\'12\', // 학생 링크열기 적용
									"id":data.id,	
									},
									success:function(data){
									alert("success");
									}
									});
								window.open(url);
								}
							});
						}//end of if 
				})
			}
		}	
	</script>';
 */