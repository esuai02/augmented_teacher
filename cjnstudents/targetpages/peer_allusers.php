<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
include("peer_navbar.php");
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teacherallusers','$timecreated')");

$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
//echo $username->firstname//.$tsymbol.count($getuserid);
$result= json_decode(json_encode($mystudents), True);

/////////////////////////// end of code snippet ///////////////////////////
echo ' 

		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
							
echo '
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 700px;
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
// a:visited { color: blue; text-decoration: none;}
  
</style>';

echo '
<style>
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 400px;
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
  width: 800px;
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
  
///////////////// ajax to fire popup in a real time by tslee ////////////////////////###################################
//<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
echo '
<script>

function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
    var checkimsi = 0;
    if(Checkvalue==true){
        checkimsi = 1;
    }
   $.ajax({
        url: "check.php",
        type: "POST",
        dataType: "json",
        data : {"userid":Userid,
                "questionid":Questionid,
                "attemptid":Attemptid,
                "checkimsi":checkimsi,
                 "eventid":Eventid,
               },
        success: function (data){  
        }
    });
}

</script>';
////////////////////////////////////////////end of ajax//////////////////////////////////////////////
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
$sssskey= sesskey(); 
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
$nusers=count($mystudents);
$userlist= json_decode(json_encode($mystudents), True);
/////////////////////////////// /////////////////////////////// begin of no whiteboard submission /////////////////////////////// /////////////////////////////// 
unset($user); 
echo '<table align="center" style="width: 70%;"><tr><td align=center><a href="https://mathking.kr/moodle/admin/user.php" target="_blank">사용자 검색</a></td><td><td align=center><a href="https://mathking.kr/moodle/admin/roles/assign.php?contextid=1" target="_blank">사용자 역할부여</a></td><td><td align=center><a href="https://mathking.kr/moodle/cohort/index.php" target="_blank">수업집단 관리</a></td><td><td align=center><a href="https://mathking.kr/moodle/user/editadvanced.php?id=-1" target="_blank">사용자 추가</a></td></tr></table><hr>';
 
echo '<table align=center width=90%><tr><th> </th><th> </th><th>    </th><th>    </th><th>  </th> <th> </th><th width=40%> </th><th> </th><th>  </th><th>  </th></tr>'; 
 
foreach($userlist as $user)
	   {
 
$userid=$user['id'];
$firstname=$user['firstname'];
$lastname=$user['lastname'];
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' ");  
$timeafter=(time()-$Timelastaccess->maxtc);
  
$Ttime = $DB->get_record('block_use_stats_totaltime', array('userid' =>$userid));
$Tlastcheck=$DB->get_record_sql("SELECT max(usertimestamp) AS maxusertimestamp FROM mdl_checklist_check where userid='$userid' "); 
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$info=$schedule->memo8;
$HP=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' and fieldid='54' ");
$HP2=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' and fieldid='55' ");
$Tcomment=$DB->get_record_sql("SELECT max(timestamp) AS maxtimestamp FROM mdl_checklist_comment where userid='$userid' "); 
  
//tslee modification for checklist timestamp

$timestamp=$Tlastcheck->maxusertimestamp;  //this is unix timestamp.
$ctimestamp=$Tcomment->maxtimestamp;
//$Wtime=$weektotal->text; 
$start  = $timestamp;
$end 	= time(); // Current time and date
$diff  	= round(($end-$start)/60,0);
$hour=floor($diff/60);
$day=floor($hour/24);
$minutes=$diff-60*($hour);
 
$hour=$hour-24*$day;
$Tlastcheck->maxusertimestamp=$day;

$lastcheck=$Tlastcheck->maxusertimestamp;   
$compratio=$Ttime->totaltime/$Wtime->data*100;
  
$start  = $ctimestamp;
$diff  	= round(($end-$start)/60,0);
$hour=floor($diff/60);
$day=floor($hour/24);
$Tcomment->maxtimestamp=$day;
$compratio=$Ttime->totaltime/$weektotal->text*100;
  
$Tlastaccess=$lastaccesstime/(3600*24);
$Time0=0.5;
 
$dateString = date("Y-m-d", time());
$plannedtime=strtotime($dateString.' '.$daily2->ctext);

$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;

$times=floor($weektotal/5);

$period=86400*56;

if($Ttime->totaltime<5)$users1.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'" target="_blank">주 '.$times.' 회 수업</a></td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,0).'h</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' " target="_blank" >현재 미션</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.' " target="_blank" >메모 : '.$info.'</a></td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?tb='.$period.'&id='.$userid.'" target="_blank">출결정보</a>
</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td></tr>'; 

elseif($Ttime->totaltime<10)$users2.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'" target="_blank">주 '.$times.' 회 수업</a></td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,0).'h</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' " target="_blank" >현재 미션</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.' " target="_blank" >메모 : '.$info.'</a></td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?tb='.$period.'&id='.$userid.'" target="_blank">출결정보</a>
</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td></tr>'; 

elseif($Ttime->totaltime<15)$users3.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'" target="_blank">주 '.$times.' 회 수업</a></td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,0).'h</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' " target="_blank" >현재 미션</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.' " target="_blank" >메모 : '.$info.'</a></td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?tb='.$period.'&id='.$userid.'" target="_blank">출결정보</a>
</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td></tr>'; 

elseif($Ttime->totaltime<20)$users4.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'" target="_blank">주 '.$times.' 회 수업</a></td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,0).'h</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' " target="_blank" >현재 미션</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.' " target="_blank" >메모 : '.$info.'</a></td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?tb='.$period.'&id='.$userid.'" target="_blank">출결정보</a>
</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td></tr>'; 

elseif($Ttime->totaltime<25)$users5.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'" target="_blank">주 '.$times.' 회 수업</a></td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,0).'h</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' " target="_blank" >현재 미션</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.' " target="_blank" >메모 : '.$info.'</a></td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?tb='.$period.'&id='.$userid.'" target="_blank">출결정보</a>
</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td></tr>'; 

else $users6.= '<tr><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span></a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'" target="_blank">주 '.$times.' 회 수업</a></td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,0).'h</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' " target="_blank" >현재 미션</a></td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$userid.' " target="_blank" >메모 : '.$info.'</a></td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a>
</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?tb='.$period.'&id='.$userid.'" target="_blank">출결정보</a>
</td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td></tr>'; 


 }

echo $users1.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users2.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users4.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users5.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$users6.'</table></div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
 ';
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
</body>';
?>