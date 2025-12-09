<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");

$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teacherconfirm','$timecreated')");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

/////////////////////////// end of code snippet ///////////////////////////
 
							
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
  width: 800px;
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
echo '<div class="main-panel"><div class="content"><div class="row"><div class="col-md-12">';
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$Nday=jddayofweek($jd,0);
//$today = date('Y-m-d');
//$Nday=getWeekday($today); 
if($Nday==0)$Nday=7;
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");

$userlist= json_decode(json_encode($mystudents), True);
echo '<table align=center style="width: 100%" class="table table-head-bg-primary mt-8"><tbody><thead>
<tr>
<th scope="col" style="width: 8%;"> </th>
<th scope="col" style="width: 8%;">상태</th>
<th scope="col" style="width: 8%;">계획</th>
<th scope="col" style="width: 8%;">실제</th>
<th scope="col" style="width: 8%;">시도</th>
<th scope="col" style="width: 8%;">시간/일</th>
<th scope="col" style="width: 8%;">시간/주</th>
<th scope="col" style="width: 8%;">속도</th>
<th scope="col" style="width: 8%;">질문</th>
<th scope="col" style="width: 8%;">목표</th>
<th scope="col" style="width: 8%;">메세지</th>
<th scope="col" style="width: 8%;">다음</th>
</tr></thead>';
unset($user);
foreach($userlist as $user)
{
$studentid=$user['id'];
$firstname=$user['firstname'];
$lastname=$user['lastname'];
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
$lastaccesstime=time()-$Timelastaccess->maxtc;
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
for($i=1; $i <= 7; $i++) {
$dayon1=$schedule->start1;
$dayon2=$schedule->start2;
$dayon3=$schedule->start3;
$dayon4=$schedule->start4;
$dayon5=$schedule->start5;
$dayon6=$schedule->start6;
$dayon7=$schedule->start7;
$amount1=$schedule->duration1;
$amount2=$schedule->duration2;
$amount3=$schedule->duration3;
$amount4=$schedule->duration4;
$amount5=$schedule->duration5;
$amount6=$schedule->duration6;
$amount7=$schedule->duration7;
}
$dayon=${'dayon'.$Nday};
$amount=${'amount'.$Nday};
 
if($dayon!=NULL && $amount>0 && $lastaccesstime>43200)
{
$missions=$DB->get_records_sql("SELECT * FROM mdl_abessi_mission where userid='$studentid'  ORDER BY timecreated DESC LIMIT 4 "); 
$result_missions= json_decode(json_encode($missions), True); 
$mission4=NULL;
unset($value);
foreach($result_missions as $value)
{
$mission4.=$value['msntype'].'|'.$value['subject'].'|'.$value['text'].'|'.$value['deadline'].'<br>';
} 
 
$Ttime = $DB->get_record('block_use_stats_totaltime', array('userid' =>$studentid));
$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$HP=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' and fieldid='54' ");
$HP2=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' and fieldid='55' ");
$compratio=$Ttime->totaltime/$weektotal*100;
$sssskey= sesskey();  
 
$tbegin=date("Y/m/d ").$dayon;
$tready=time()-strtotime($tbegin);
 
$status2='대기중';

$Info1.= '<tr><td><div class="tooltip2"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.' " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"><b> '.$firstname.$lastname.'</b></span></a><span class="tooltiptext2">    
<br><h5><span class="" align="right"  style="color: rgb(51, 51, 251);">KAIST TOUCH MATH ::: '.$mission.' + '.$plan.' + '.$goal.'  ('.round($Ttime->totaltime,0).' h / '.$weektotal.' h) </span></h5><hr>
<table align=center style="width: 100%" class="table table-head-bg-primary mt-8">
                    <caption></caption>
                    <thead>
                        <tr>
                            <th scope="col"></th><th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('mon', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left" ><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('tue', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('wed', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('thu', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('fri', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(42, 100, 211);">'.get_string('sat', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(239, 69, 64);">'.get_string('sun', 'report_log').'</span></b></h5></th>
                        </tr>
                    </thead>
                    <tbody>
 		<tr>
                            <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('begin', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start1.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start2.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start3.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start4.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start5.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start6.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start7.'</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('time', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><span style="font-size: 12.44px;">'.$schedule->duration1.'</span></td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration2.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration3.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration4.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration5.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration6.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration7.'</td>
                        </tr>
</tbody></table> 


</div></td><td><span class="" style="color: rgb(29, 69, 224);">'.$status2.'</span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'" target="_blank">'.get_string('starttime', 'local_augmented_teacher').$dayon.'</a></td><td>'.get_string('duration2', 'local_augmented_teacher').$amount.'h</td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$studentid.' " target="_blank" >'.round($Ttime->totaltime,0).'</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$studentid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$studentid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$studentid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td> <td><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td></td><td></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td> '.$status4.'</tr>'; 

 }
//$Info1.='</tbody></table>';

if($dayon!=NULL && $amount>0 && $lastaccesstime<43200)
{

$missions2=$DB->get_records_sql("SELECT * FROM mdl_abessi_mission where userid='$studentid'  ORDER BY timecreated DESC LIMIT 4 "); 
$result_missions2= json_decode(json_encode($missions2), True); 
$mission4=NULL;
unset($value2);
foreach($result_missions2 as $value2)
{
$mission4.=$value2['msntype'].'|'.$value2['subject'].'|'.$value2['text'].'|'.$value2['deadline'].'<br>';
} 
 
$Ttime = $DB->get_record('block_use_stats_totaltime', array('userid' =>$studentid));
$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$HP=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' and fieldid='54' ");
$HP2=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' and fieldid='55' ");
$compratio=$Ttime->totaltime/$weektotal*100;
$sssskey= sesskey();  
 
$tbegin=date("Y/m/d ").$dayon;
$tready=time()-strtotime($tbegin);
 
$status2='접속함';

$Info2.= '<tr><td><div class="tooltip2"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.' " target="_blank" ><span class="" style="color: rgb(0, 0, 0);"><b> '.$firstname.$lastname.'</b></span></a><span class="tooltiptext2">    
<br><h5><span class="" align="right"  style="color: rgb(51, 51, 251);">KAIST TOUCH MATH ::: '.$mission.' + '.$plan.' + '.$goal.'  ('.round($Ttime->totaltime,0).' h / '.$weektotal.' h) </span></h5><hr>
<table align=center style="width: 100%" class="table table-head-bg-primary mt-8">
                    <caption></caption>
                    <thead>
                        <tr>
                            <th scope="col"></th><th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('mon', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left" ><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('tue', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('wed', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('thu', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('fri', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(42, 100, 211);">'.get_string('sat', 'report_log').'</span></b></h5></th>
                            <th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(239, 69, 64);">'.get_string('sun', 'report_log').'</span></b></h5></th>
                        </tr>
                    </thead>
                    <tbody>
 		<tr>
                            <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('begin', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start1.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start2.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start3.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start4.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start5.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start6.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start7.'</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('time', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><span style="font-size: 12.44px;">'.$schedule->duration1.'</span></td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration2.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration3.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration4.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration5.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration6.'</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration7.'</td>
                        </tr>
 </tbody></table>  </div>


</td>  <td><span class="" style="color: rgb(29, 69, 224);">'.$status2.'</span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'" target="_blank">'.get_string('starttime', 'local_augmented_teacher').$dayon.'</a></td><td>'.get_string('duration2', 'local_augmented_teacher').$amount.'h</td><td><a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$studentid.' " target="_blank" >'.round($Ttime->totaltime,0).'</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$studentid.' " target="_blank" >'.$weektotal.'h</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$studentid.' " target="_blank" >'.get_string('concept', 'local_augmented_teacher').'</a></td><td><a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$studentid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;</td> <td><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP->data.' " target="_blank" ><img src="https://i.stack.imgur.com/ePwhV.jpg" width=20></a></td><td></td><td></td><td><a href="https://app.mysms.com/#messages:+8210'.$HP2->data.' " target="_blank" ><img src="https://tecvid.org/wp-content/uploads/2017/07/mail-icon.png" width=20></a>&nbsp;&nbsp;&nbsp;</td> '.$status4.'</tr>'; 
 
}
}
echo $Info2;
echo $Info1;
echo '</tbody></table>';
echo '</div>
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
						</div>
					</div>
				</div>
			</div>';
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
';
?>