<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';

$studentid=$userid;
 
	// get mission list
    $trecent2=time()-31104000;  // 1year ago
    $missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE  timecreated>'$trecent2' AND userid='$studentid' ORDER by norder ASC ");
    $result = json_decode(json_encode($missionlist), True);
     
    unset($value);
    foreach($result as $value)
        {
        $mtid=0;
        $mid=$value['id'];
        $subject=$value['subject'];	
        $deadline= $value['deadline']; 	
        $unixtimedeadline=strtotime($deadline);	
        if($unixtimedeadline > time()+31536000 || $unixtimedeadline < time()-31536000)continue;
        $passgrade=$value['grade'];
        $mtname=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' ");
        $contentslist=$mtname->contentslist;
        $subjectname=$mtname->name;
        $mtid=$mtname->mtid;
        $subjectname=str_replace("개념 :","",$subjectname);
        $subjectname=str_replace("심화 :","",$subjectname);
        $subjectname=str_replace("내신 :","",$subjectname);
        $subjectname=str_replace("수능 :","",$subjectname);
    if($value['complete']==0)
        {
        if($mtid==1 ||$mtid==7)
            {
            $mt01.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$subject.'&nch=1&studentid='.$studentid.'&type=init"target="_blank"><img style="margin-bottom:4px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png width=20> GPT '.$subjectname.' </a> </td>
            <td width=4% style=""></td><td  width=30% align="left" style="font-size:12pt"> <img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>예전 페이지</b></td><td width=20% style="font-size:10pt">합격 : '.$passgrade.'점</td>
            <td width=4%><div class="form-check"> 완료 &nbsp;<label  style="margin-bottom:5px;"  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
            }
        elseif($mtid==2)
            {
            if(strpos($subjectname,'초등')!==false)$mt02.='<tr> <td width=30% align="left"   style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
            else $mt02.='<tr><td   width=30% align="left"  style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span style="margin-bottom:5px;" class="form-check-sign"></span></label></div></td></tr>';
            }
        elseif($mtid==3)
            {
            $mt03.='<tr> <td  width=30% align="left"  style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label  style="margin-bottom:5px;"  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            }
        elseif($mtid==4)
            {
            $mt04.='<tr><td  width=30% align="left"  style="font-size:12pt"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654400635.png" width=20> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'"><b>'.$subjectname.'</b></td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 완료 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            }
        } 
    else 
        {
         if($mtid==1 ||$mtid==7)
            {
            $mt05.='<tr><td  width=30% align="left" style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">개념 : '.$subjectname.'</td><td width=4% style=""></td><td width=20% style="font-size:10pt">합격 : '.$passgrade.'점</td>
            <td width=4%><div class="form-check"> 추가 &nbsp;<label  style=""  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
            }
        elseif($mtid==2)
            {
            if(strpos($subjectname,'초등')!==false)$mt06.='<tr> <td width=30% align="left"   style="font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">심화 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
            else $mt06.='<tr><td   width=30% align="left"  style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">심화 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span style="" class="form-check-sign"></span></label></div></td></tr>';
            }
        elseif($mtid==3)
            {
            $mt07.='<tr><td  width=30% align="left"  style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">내신 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label  style=""  class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            }
        elseif($mtid==4)
            {
            $mt08.='<tr><td  width=30% align="left"  style="color:grey;font-size:10pt"><img style="" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655184717.png" width=15> <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$contentslist.'&studentid='.$studentid.'">수능 : '.$subjectname.'</td><td width=4%></td><td  width=20%  style="font-size:10pt">합격 : '.$passgrade.'점</td><td width=4%><div class="form-check"> 추가 &nbsp;<label class="form-check-label"><input type="checkbox"  onclick="changecheckbox(13,'.$studentid.','.$mid.', this.checked)"/><span class="form-check-sign"></span></label></div></td></tr>';
            }
         
        }
    }
$courselist='<table width=100%  valign=top  ><tr><th width=10%></th><th width=90%></th></tr> <tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 개념</td><td align=right style="background-color:#3383FF;color:white;"><span onclick="addcourse(7,\''.$studentid.'\');">추가 <i style="color:white;" class="flaticon-plus"></i></span> &nbsp;&nbsp;&nbsp;<a style="color:white" href="http://mathking.kr/moodle/local/augmented_teacher/twinery/topiclearning.html"target="_blank">도움말</a>&nbsp;&nbsp;&nbsp;</td></tr>
            <tr><td></td><td  valign=top ><table width=100%  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt01.'</table></td></tr>  <tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 심화</td><td align=right style="background-color:#3383FF;color:white;"><span onclick="addcourse(2,\''.$studentid.'\');">추가 <i style="color:white;" class="flaticon-plus"></i></span> &nbsp;&nbsp;&nbsp;<a style="color:white" href="http://mathking.kr/moodle/local/augmented_teacher/twinery/deeperlearning.html"target="_blank">도움말</a>&nbsp;&nbsp;&nbsp;</td></tr>
            <tr><td></td><td  valign=top ><table width=100% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt02.'</table></td></tr> <tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 내신</td><td align=right style="background-color:#3383FF;color:white;"><span onclick="addcourse(3,\''.$studentid.'\');">추가 <i style="color:white;" class="flaticon-plus"></i></span> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
            <tr><td></td><td  valign=top ><table width=100% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt03.'</table></td></tr> <tr><td align=center width=10% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;background-color:#E05D22;color:white;font-size:14pt;height:40px;">&nbsp; 수능</td><td align=right style="background-color:#3383FF;color:white;"><span onclick="addcourse(4,\''.$studentid.'\');">추가 <i style="color:white;" class="flaticon-plus"></i></span> &nbsp;&nbsp;&nbsp;도움말&nbsp;&nbsp;&nbsp;</td></tr>
            <tr><td></td><td  valign=top ><table width=100% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$mt04.'</table></td></tr> </table>  ';

$showpage= '<table width=80% align=center><tr><td> </td></tr><tr><td>'.$courselist.'</td></tr></table>
<table align=center><tr><td><button class="submit-button" onclick="addcourse(7,\''.$studentid.'\');">개념추가</button></td> 
<td><button class="submit-button" onclick="addcourse(2,\''.$studentid.'\');">심화추가</button></td> 
<td><button class="submit-button" onclick="addcourse(3,\''.$studentid.'\');">내신추가</button></td> 
<td><button class="submit-button" onclick="addcourse(4,\''.$studentid.'\');">수능추가</button></td></table>';
 
$pagewelcome='내 공부방으로 이동하여 새로운 활동을 시작하실 수 있습니다.';

// 조건문으로 선생님별로 선택
$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"target="_blank"><button class="submit-button">내 공부방</button></a></td>';


$buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$userid.'&type=submittoday"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
 
echo '<script>
	function addcourse(Mtid,Userid)
		{
		Swal.fire({
		position:"top-end",showCloseButton: true, width:1200,
		  html:
		    \'<iframe  style="border: 1px none; z-index:2; width:80vw; height:80vw;  margin-left: -50px;margin-right: -50px;  margin-top: -200px; "  src="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id=\'+Userid+\'&mtid=\'+Mtid+\'&cid=0" ></iframe>\',
		  showConfirmButton: false,
		        })
		}
 	</script>';
?>