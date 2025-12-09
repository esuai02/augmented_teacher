<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

  

echo '

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
.accordion {
  background-color: #fff;
  color: #333;
  cursor: pointer;
  padding: 2px;
  width: 100%;
  border: none;
  text-align: left;
  outline: none;
  font-size: 12px;
  transition: 1.4s;
}

.active, .accordion:hover {
  background-color: #fff;
}

.accordion:after {

  color: #333;
  font-weight: bold;
  float: right;
  margin-left: 2px;
}

.active:after {
 
}

.panel {
  padding: 0 8px;
  background-color: #fff;
  max-height: 0;
  overflow: hidden;
  transition: max-height 1.2s ease-out;
}
</style>
';

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
////////////////////////////////////////////////////////////////////////// current messages  /////////////////////////////////////////////////////
  echo ' <iframe src=http://www.moreleap.com/replay.php?id=ig89mGdgsLKemro&speed=9  style="border: 0px none; margin-left: -0px; height:600px; margin-top: -0px; width: 1600px;"></iframe>';
$message=$DB->get_records_sql("SELECT mdl_messages.questionid AS questionid, mdl_messages.smallmessage AS small, mdl_messages.timecreated AS created, mdl_messages.useridfrom AS sender FROM mdl_messages LEFT JOIN mdl_message_conversation_members
 ON mdl_message_conversation_members.conversationid=mdl_messages.conversationid WHERE  (mdl_messages.smallmessage LIKE '%whiteboardfox%' OR mdl_messages.smallmessage LIKE '%ouwiki%') AND mdl_message_conversation_members.userid!= mdl_messages.useridfrom 
 AND mdl_message_conversation_members.userid='$USER->id'  ORDER BY mdl_messages.timecreated DESC LIMIT 12 ");
$result2 = json_decode(json_encode($message), True);
$num01=count($result2);
unset($value);
foreach($result2 as $value) 
{ 
$useridtmp=$USER->id;
$qidtmp=$value['questionid'];
$attemptid=0;//$value['id'];
echo '<div style="border: 0px solid rgb(201, 0, 1); overflow: hidden; margin: 15px auto; max-width: 1600px;">
 <iframe src=http://www.moreleap.com/replay.php?id=ig89mGdgsLKemro&speed=9  style="border: 0px none; margin-left: -0px; height:600px; margin-top: -0px; width: 1600px;"></iframe>
    <iframe src='.$value['small'].'?replay&speed=100 style="border: 0px none; margin-left: -0px; height:600px; margin-top: -0px; width: 1600px;"></iframe>
</div><table align=center style="width: 100%;"><tbody><tr><td><p align=right><a href='.$value['small'].'  " target="_blank" ><img src=https://findicons.com/files/icons/99/office/128/edit.png width=20><img src=https://findicons.com/files/icons/99/office/128/edit.png width=20></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.get_string('review', 'local_augmented_teacher').'<input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(4,\''.$useridtmp.'\',\''.$qidtmp.'\',\''.$attemptid.'\', this.checked)"/>
&nbsp;&nbsp;&nbsp;'.get_string('help', 'local_augmented_teacher').'<input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(5,\''.$useridtmp.'\',\''.$qidtmp.'\',\''.$attemptid.'\', this.checked)"/> &nbsp;&nbsp;'.get_string('complete', 'local_augmented_teacher').'<input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(6,\''.$useridtmp.'\',\''.$qidtmp.'\',\''.$attemptid.'\', this.checked)"/></td></tr></p></tbody></table>';
break;
} 

unset($value);
 
echo '<hr style="border: dashed 3px skyblue;"><button class="accordion"><p align=center><img src=https://cdn1.iconfinder.com/data/icons/vibrancie-action/30/action_028-detail-more-info-others-512.png width=50><span vertical-align: middle;>('.$num01.')</span></p></button><div class="panel">
<hr><table align=right style="width: 100%;"><tbody>';
echo ' <tr><td><table align=center style="width: 100%;"><tbody>';
foreach($result2 as $value) 
{ 
$useridtmp=$USER->id;
$qidtmp=$value['questionid'];
$attemptid=0;//$value['id'];
$timeafter=(time()-$value['created'])/86400;
if( $timeafter<8  )
	{
	echo '<tr><td valign="top" ><p align=center><a href='.$value['small'].'  " target="_blank" >'.$value['small'].'</a></p></td><td>'.get_string('review', 'local_augmented_teacher').'<input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(4,\''.$useridtmp.'\',\''.$qidtmp.'\',\''.$attemptid.'\', this.checked)"/> &nbsp;&nbsp'.get_string('help', 'local_augmented_teacher').'<input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(5,\''.$useridtmp.'\',\''.$qidtmp.'\',\''.$attemptid.'\', this.checked)"/> &nbsp;&nbsp;'.get_string('complete', 'local_augmented_teacher').'<input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(6,\''.$useridtmp.'\',\''.$qidtmp.'\',\''.$attemptid.'\', this.checked)"/>&nbsp;&nbsp;&nbsp;&nbsp;</td><tr>';
	}
}
 
echo '</tbody></table></div></td><td>
<p align=right><iframe width=800 height=600 src=https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?&theme=bubble&id=48 frameborder=3 border=3 allowfullscreen></iframe></p></td></tr></tbody></table>'; 
echo '<script>
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var panel = this.nextElementSibling;
    if (panel.style.maxHeight){
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = panel.scrollHeight + "px";
    } 
  });
}
</script>';
?>