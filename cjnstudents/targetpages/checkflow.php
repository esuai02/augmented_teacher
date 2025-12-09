<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$type= $_POST['type'];
$itemid = $_POST['itemid'];
$userid = $_POST['userid'];
$checkimsi = $_POST['checkimsi'];
$inputtext = $_POST['inputtext']; 
$timecreated=time(); 

$halfdayago=$timecreated-43200;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

include("flowrubric.php");

$checkstr='checked'.$itemid;	 
if($eventid==1) // abessi_cognitivetalk에 5개 항목 체크상태 입력
	{
	$exist= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE talkid=7128 AND type LIKE '$type' AND creator='$userid' ORDER BY id DESC LIMIT 1");   
	if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_cognitivetalk} (wboardid,creator,talkid,userid,type,".$checkstr.",timemodified,timecreated ) VALUES('evaluate','$userid','7128','$USER->id','$type','$checkimsi','$timecreated','$timecreated')");
	else
		{
		if($itemid==1)$DB->execute("UPDATE {abessi_cognitivetalk} SET checked1='$checkimsi' WHERE talkid=7128 AND  type LIKE '$type' AND creator='$userid' ORDER BY id DESC LIMIT 1");   
		elseif($itemid==2)$DB->execute("UPDATE {abessi_cognitivetalk} SET checked2='$checkimsi' WHERE talkid=7128 AND  type LIKE '$type' AND creator='$userid' ORDER BY id DESC LIMIT 1"); 
		elseif($itemid==3)$DB->execute("UPDATE {abessi_cognitivetalk} SET checked3='$checkimsi' WHERE talkid=7128 AND  type LIKE '$type' AND creator='$userid' ORDER BY id DESC LIMIT 1"); 
		elseif($itemid==4)$DB->execute("UPDATE {abessi_cognitivetalk} SET checked4='$checkimsi' WHERE talkid=7128 AND  type LIKE '$type' AND creator='$userid' ORDER BY id DESC LIMIT 1"); 
		elseif($itemid==5)$DB->execute("UPDATE {abessi_cognitivetalk} SET checked5='$checkimsi' WHERE talkid=7128 AND  type LIKE '$type' AND creator='$userid' ORDER BY id DESC LIMIT 1");  	
		} 
	}
elseif($eventid==11) // abessi_cognitivetalk에 5개 항목 체크상태 입력
	{
	$DB->execute("INSERT INTO {abessi_cognitivetalk} (wboardid,creator,talkid,userid,type,text,".$checkstr.",timemodified,timecreated ) VALUES('stamp','$userid','17','$USER->id','$type','$inputtext','$checkimsi','$timecreated','$timecreated')");
	}
elseif($eventid==2) // 목순기개 발해숙효 중 선택 항목에 대한 상태 업데이트 추가
	{
 
	if($role==='student')$clickrole='';
	else $clickrole='(선생님)';
		 
		$itemstate= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE creator='$userid'  AND type='$type' AND talkid=7128 ORDER BY id DESC LIMIT 1 ");
		$value=$itemstate->checked1+$itemstate->checked2+$itemstate->checked3+$itemstate->checked4+$itemstate->checked5;
		$summarytext.='<b>메타인지 체크결과</b> '.$clickrole.' <br>';
		if($itemstate->checked1==1)$summarytext.='[1] '.$flowitem1.'<br>';
		if($itemstate->checked2==1)$summarytext.='[2] '.$flowitem2.'<br>';
		if($itemstate->checked3==1)$summarytext.='[3] '.$flowitem3.'<br>';
		if($itemstate->checked4==1)$summarytext.='[4] '.$flowitem4.'<br>';
		if($itemstate->checked5==1)$summarytext.='[5] '.$flowitem5.'<br>';

		$history= $DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");
		$history2= $DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog WHERE role NOT LIKE 'student' AND userid='$userid' ORDER BY id DESC LIMIT 1 ");

		if($history->id==NULL)
			{
			if($type==='목표'){$flow1=$value;$flow2=0;$flow3=0;$flow4=0;$flow5=0;$flow6=0;$flow7=0;$flow8=0;}
			elseif($type==='순서'){$flow1=0;$flow2=$value;$flow3=0;$flow4=0;$flow5=0;$flow6=0;$flow7=0;$flow8=0;}
			elseif($type==='기억'){$flow1=0;$flow2=0;$flow3=$value;$flow4=0;$flow5=0;$flow6=0;$flow7=0;$flow8=0;}
			elseif($type==='몰입'){$flow1=0;$flow2=0;$flow3=0;$flow4=$value;$flow5=0;$flow6=0;$flow7=0;$flow8=0;}
			elseif($type==='발상'){$flow1=0;$flow2=0;$flow3=0;$flow4=0;$flow5=$value;$flow6=0;$flow7=0;$flow8=0;}
			elseif($type==='해석'){$flow1=0;$flow2=0;$flow3=0;$flow4=0;$flow5=0;$flow6=$value;$flow7=0;$flow8=0;}
			elseif($type==='숙달'){$flow1=0;$flow2=0;$flow3=0;$flow4=0;$flow5=0;$flow6=0;$flow7=$value;$flow8=0;}
			elseif($type==='효율'){$flow1=0;$flow2=0;$flow3=0;$flow4=0;$flow5=0;$flow6=0;$flow7=0;$flow8=$value;}
			$DB->execute("INSERT INTO {abessi_flowlog} (role,userid,teacherid,flow1,flow2,flow3,flow4,flow5,flow6,flow7,flow8,timecreated ) VALUES('$role','$userid','$USER->id','$flow1','$flow2','$flow3','$flow4','$flow5','$flow6','$flow7','$flow8','$timecreated')");
			}
		else
			{
			if($type==='목표'){$flow1=$value;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;}
			elseif($type==='순서'){$flow1=$history->flow1;$flow2=$value;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;}
			elseif($type==='기억'){$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$value;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;}
			elseif($type==='몰입'){$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$value;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;}
			elseif($type==='발상'){$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$value;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$history->flow8;}
			elseif($type==='해석'){$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$value;$flow7=$history->flow7;$flow8=$history->flow8;}
			elseif($type==='숙달'){$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$value;$flow8=$history->flow8;}
			elseif($type==='효율'){$flow1=$history->flow1;$flow2=$history->flow2;$flow3=$history->flow3;$flow4=$history->flow4;$flow5=$history->flow5;$flow6=$history->flow6;$flow7=$history->flow7;$flow8=$value;}
			if($timecreated-$history2->timecreated>43200 || $role!=='student')$DB->execute("INSERT INTO {abessi_flowlog} (role,userid,teacherid,flow1,flow2,flow3,flow4,flow5,flow6,flow7,flow8,timecreated ) VALUES('$role','$userid','$USER->id','$flow1','$flow2','$flow3','$flow4','$flow5','$flow6','$flow7','$flow8','$timecreated')");
			}
		
		$history3= $DB->get_record_sql("SELECT * FROM mdl_abessi_mcpreset WHERE userid='$userid' AND active=1 ORDER BY id DESC LIMIT 1 ");
		if($history3->id==NULL) // preset 초기화
			{
			$pr= $DB->get_record_sql("SELECT * FROM mdl_abessi_mcpreset WHERE id=1  ORDER BY id DESC LIMIT 1 ");
			$DB->execute("INSERT INTO {abessi_mcpreset} (userid,type,c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11,c12,c13,c14,c15,c16,c17,c18,c19,c20,c21,c22,c23,c24,c25,c26,c27,c28,c29,c30,c31,c32,c33,c34,c35,c36,c37,c38,c39,c40,timecreated ) VALUES('$userid','user','$pr->c1','$pr->c2','$pr->c3','$pr->c4','$pr->c5','$pr->c6','$pr->c7','$pr->c8','$pr->c9','$pr->c10','$pr->c11','$pr->c12','$pr->c13','$pr->c14','$pr->c15','$pr->c16','$pr->c17','$pr->c18','$pr->c19','$pr->c20','$pr->c21','$pr->c22','$pr->c23','$pr->c24','$pr->c25','$pr->c26','$pr->c27','$pr->c28','$pr->c29','$pr->c30','$pr->c31','$pr->c32','$pr->c33','$pr->c34','$pr->c35','$pr->c36','$pr->c37','$pr->c38','$pr->c39','$pr->c40','$timecreated')");
			}
 
 		$history4= $DB->get_record_sql("SELECT * FROM mdl_abessi_mcupdate WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");
		if($history4->id==NULL) // mc update 적용
			{
			$DB->execute("INSERT INTO {abessi_mcupdate} (userid,timemodified) VALUES('$userid','$timecreated')");
			}

		if($type==='목표')$DB->execute("UPDATE {abessi_mcupdate} SET c1='$itemstate->checked1',c2='$itemstate->checked2',c3='$itemstate->checked3',c4='$itemstate->checked4',c5='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='순서')$DB->execute("UPDATE {abessi_mcupdate} SET c6='$itemstate->checked1',c7='$itemstate->checked2',c8='$itemstate->checked3',c9='$itemstate->checked4',c10='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='기억')$DB->execute("UPDATE {abessi_mcupdate} SET c11='$itemstate->checked1',c12='$itemstate->checked2',c13='$itemstate->checked3',c14='$itemstate->checked4',c15='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='몰입')$DB->execute("UPDATE {abessi_mcupdate} SET c16='$itemstate->checked1',c17='$itemstate->checked2',c18='$itemstate->checked3',c19='$itemstate->checked4',c20='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='발상')$DB->execute("UPDATE {abessi_mcupdate} SET c21='$itemstate->checked1',c22='$itemstate->checked2',c23='$itemstate->checked3',c24='$itemstate->checked4',c25='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='해석')$DB->execute("UPDATE {abessi_mcupdate} SET c26='$itemstate->checked1',c27='$itemstate->checked2',c28='$itemstate->checked3',c29='$itemstate->checked4',c30='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='숙달')$DB->execute("UPDATE {abessi_mcupdate} SET c31='$itemstate->checked1',c32='$itemstate->checked2',c33='$itemstate->checked3',c34='$itemstate->checked4',c35='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
		elseif($type==='효율')$DB->execute("UPDATE {abessi_mcupdate} SET c36='$itemstate->checked1',c37='$itemstate->checked2',c38='$itemstate->checked3',c39='$itemstate->checked4',c40='$itemstate->checked5',timemodified='$timecreated' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");   
 
 		$talkid=88;	//  선택내용 채팅에 추가
		$DB->execute("INSERT INTO {abessi_cognitivetalk} (wboardid,creator,talkid,userid,type,hide,text,checked1,checked2,checked3,checked4,checked5,timemodified,timecreated ) VALUES('evalsummary','$userid','$talkid','$USER->id','$type','0','$summarytext','$itemstate->checked1','$itemstate->checked2','$itemstate->checked3','$itemstate->checked4','$itemstate->checked5','$timecreated','$timecreated')");
		if($timecreated-$history2->timecreated>259200 || $role!=='student')echo json_encode( array("talkid" =>$talkid) ); 
		else {$talkid=199; echo json_encode( array("talkid" =>$talkid) ); }  // 선생님 입력 후 72시간 지나야 학생입력 가능
	}

 elseif($eventid==3) // abessi_cognitivetalk에 5개 항목 체크상태 입력
	{
	$mbti= $_POST['mbti'];
	$mbti= strtolower($mbti);
	$DB->execute("INSERT INTO {abessi_mbtilog} (userid,type,mbti,timecreated) VALUES('$userid','$type','$mbti','$timecreated')");
	}

  
?>

