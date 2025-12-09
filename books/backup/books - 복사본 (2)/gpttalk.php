

<?php  
$pageurl= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
$context=substr($pageurl, 0, strpos($pageurl, '?')); // 문자 이후 삭제
$currenturl=strstr($pageurl, '?');  //before
$url=str_replace("?","",$currenturl);

echo '<script> 
function GPTTalk(Eventid,Text,Contextid,Context,Url,Studentid)
	{
		$.ajax({
			url: "../books/check_status.php",
			type: "POST",
			dataType:"json", 
			data : {
				"eventid":Eventid,		 	 
				"studentid":Studentid,
				"contextid":Contextid,					 
				"context":Context,
				"url":Url,
			},
			success:function(data){
							var Cntid=data.cntid;
							var Nexturl="https://mathking.kr/moodle/local/augmented_teacher/books/edit.php?cntid="+Cntid;
							setTimeout(function(){window.open(Nexturl, \'_self\');} , 100);
							}
			 })
	}
</script>
 
';

?>

