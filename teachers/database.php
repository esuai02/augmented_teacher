<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$studentid = $_POST['studentid'];
$teacherid = $_POST['teacherid'];
$contentsid = $_POST['contentsid'];
$timecreated=time();

if($eventid==11) // �л����� ���� ������ ���� �����ǻ� �����
{
$DB->execute("INSERT INTO {abessi_messages} (userid,userto,talkid,nstep,turn,status,contentstype,contentsid,timeupdated,timecreated) VALUES('$teacherid','$studentid','1','0','0','begin','quiz','$contentsid','$timecreated','$timecreated')");
}
 
 
if($eventid==12) // �������� ����
	{	 
	$phone1 = $_POST['phone1']; //�л� ����ó 54
	$location = $_POST['location']; //��ġ 68

	$ticon = $_POST['ticon']; //���������� 79
	$assessmode = $_POST['assessmode']; // �ڵ�ä�� 82
	$networkstatus = $_POST['networkstatus']; //��Ʈ��ŷ ���� 69
	$networklevel = $_POST['networklevel']; //��Ʈ��ŷ ���� 71
	$team = $_POST['team']; //���� 103		 

	$teachingmode = $_POST['teachingmode']; // 102 	
	$setmode1 = $_POST['setmode1']; // 97
	$setmode2 = $_POST['setmode2']; // 98	
	$setmode3 = $_POST['setmode3']; // 99	
	$setmode4 = $_POST['setmode4']; // 100	
	$setmode5 = $_POST['setmode5']; // 101	

      //    	$beingsullivan=$location.$brotherhood.$assessment; include("../whiteboard/debug.php");
 
	$DB->execute("UPDATE {user_info_data} SET data='$phone1'  WHERE userid='$userid' AND fieldid=54 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$location'  WHERE userid='$userid' AND fieldid=68 ");  

	$DB->execute("UPDATE {user_info_data} SET data='$ticon'  WHERE userid='$userid' AND fieldid=79 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$assessmode'  WHERE userid='$userid' AND fieldid=82 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$networkstatus'  WHERE userid='$userid' AND fieldid=69 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$networklevel'  WHERE userid='$userid' AND fieldid=71 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$team'  WHERE userid='$userid' AND fieldid=103 ");  

	$DB->execute("UPDATE {user_info_data} SET data='$teachingmode'  WHERE userid='$userid' AND fieldid=102 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$setmode1'  WHERE userid='$userid' AND fieldid=97 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$setmode2'  WHERE userid='$userid' AND fieldid=98 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$setmode3'  WHERE userid='$userid' AND fieldid=99 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$setmode4'  WHERE userid='$userid' AND fieldid=100 ");  
	$DB->execute("UPDATE {user_info_data} SET data='$setmode5'  WHERE userid='$userid' AND fieldid=101 ");  	 
	}
if($eventid==20) // 정산 실행
	{	 
	$monthsago6=$timecreated-604800*30;

	
	date_default_timezone_set('Asia/Seoul');  
	$currentDate = new DateTime(); 
	$lastDayOfLastMonth = $currentDate->modify('last day of previous month'); 
	$lastDayOfLastMonth->setTime(23, 59, 59);
	$unixTimestamp = $lastDayOfLastMonth->getTimestamp();//정산 기준일

	$mystudents=$DB->get_records_sql("SELECT * FROM mdl_abessi_mystudents WHERE teacherid LIKE '$teacherid'  ORDER BY id DESC ");  
	$nactiveusers=0; $mtotal=0;
	$confirmdate=$settlementday=date("Y-m-d",$unixTimestamp);
	$result= json_decode(json_encode($mystudents), True);
	unset($user);
	foreach($result as $user)
		{
		$userid=$user['studentid']; 
		$enrollments = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND type LIKE 'enrol'  AND status LIKE '납부' AND  teacherid='$teacherid' AND timecreated>'$monthsago6' AND deposittime <'$unixTimestamp' hide LIKE '0' ORDER by id DESC ");
		$result2= json_decode(json_encode($enrollments), True);
		unset($value);
		foreach($result2 as $value)
			{
			$enrolid=$value['id'];
			$mtotal=$mtotal+$value['deposit'];
			$DB->execute("UPDATE {abessi_attendance} SET status='정산', confirmdate='$confirmdate' WHERE id='$enrolid' "); 			 		
			}				
		}
	$realtransfer=$mtotal*0.97;
	$mcompany=$mtotal*0.03;
	$tax=$mtotal*0.03;
	$rate=0.03;
	$cardfee=0;
	$systemfee=0;

	$DB->execute("INSERT INTO {abessi_settlements} (userid,confirmdate,mtotal,mcompany,tax,rate,cardfee,systemfee,realtransfer,timecreated) VALUES('$teacherid','$confirmdate','$mtotal','$mcompany','$tax','$rate','$cardfee','$systemfee','$realtransfer','$timecreated')");
	}

?>

