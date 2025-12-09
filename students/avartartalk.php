<?php  
$moreleap = $DB->get_record_sql("SELECT * FROM  mdl_abessi_cognitivetalk WHERE creator='$studentid' AND type='$type' AND hide=0 ORDER BY id DESC LIMIT 1 ");  // 메타인지 피드백

if($moreleap->id==NULL)
	{
	echo '<script> swal("Welcome !", 환영합니다.   이곳은 "'.$type.'에 관련된 몰입피드백이 이루어지는 채팅방입니다.", {buttons: false,timer:3000});	</script>'; 
	$item= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE userid=2 AND type='$type' AND standard=1 AND hide=0 ORDER BY id ASC LIMIT 1");
	$DB->execute("INSERT INTO {abessi_cognitivetalk} (srcid,wboardid,creator,talkid,userid,type,standard,checkid,hide,text,timemodified,timecreated ) VALUES('$item->id','$item->wboardid','$studentid','$item->talkid','2','$item->type','0','$item->checkid','0','$item->text','$timecreated','$timecreated')");
  	echo '<script>setTimeout(function() {location.reload(); },4000);</script>';
	}  
 
$placeholder='활동내용/계획을 입력하면 팝업이 사라집니다.';

// 이부분에서 자동 체크 알고리즘 적용

?>