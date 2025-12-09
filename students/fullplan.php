<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$studentid=required_param('id', PARAM_INT);  

$timecreated=time();
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$birthyear = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='89' ");//출생년도 
$mode = $_GET["mode"]; 
$univ=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='105' ");// 학교 
$nuniv=$univ->data;
$pathtype=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='106' ");// 커리큘럼 유형
$npath=$pathtype->data;
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder>0 ORDER BY norder ASC"); // missiontype으로 mission 종류 선택

$preset7= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=7 ORDER BY id DESC LIMIT 1"); 
if($preset7->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,e41,e42,e51,e52,e61,e62,m11,m12,m21,m22,m31,m32,h11,h12,h21,h22,h31,h32,h33,timemodified,timecreated) VALUES('$studentid','7','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','$timecreated','$timecreated')"); 
$preset2= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=2 ORDER BY id DESC LIMIT 1"); 
if($preset2->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,e41,e42,e51,e52,e61,e62,m11,m12,m21,m22,m31,m32,h11,h12,h21,h22,h31,h32,h33,timemodified,timecreated) VALUES('$studentid','2','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','$timecreated','$timecreated')"); 
$preset3= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=3 ORDER BY id DESC LIMIT 1"); 
if($preset3->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,e41,e42,e51,e52,e61,e62,m11,m12,m21,m22,m31,m32,h11,h12,h21,h22,h31,h32,h33,timemodified,timecreated) VALUES('$studentid','3','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','$timecreated','$timecreated')"); 
$preset4= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=4 ORDER BY id DESC LIMIT 1"); 
if($preset4->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,e41,e42,e51,e52,e61,e62,m11,m12,m21,m22,m31,m32,h11,h12,h21,h22,h31,h32,h33,timemodified,timecreated) VALUES('$studentid','4','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','$timecreated','$timecreated')"); 

$preset5= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=5 ORDER BY id DESC LIMIT 1"); //중간고사
if($preset5->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,e41,e42,e51,e52,e61,e62,m11,m12,m21,m22,m31,m32,h11,h12,h21,h22,h31,h32,h33,timemodified,timecreated) VALUES('$studentid','5','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','$timecreated','$timecreated')"); 
$preset6= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=6 ORDER BY id DESC LIMIT 1"); //기말고사
if($preset6->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,e41,e42,e51,e52,e61,e62,m11,m12,m21,m22,m31,m32,h11,h12,h21,h22,h31,h32,h33,timemodified,timecreated) VALUES('$studentid','6','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','$timecreated','$timecreated')"); 
$preset8= $DB->get_record_sql("SELECT * FROM mdl_abessi_preset WHERE userid='$studentid' AND mtid=8 ORDER BY id DESC LIMIT 1"); //모의고사
if($preset8->id==NULL)$DB->execute("INSERT INTO {abessi_preset} (userid,mtid,h11,h12,h21,h22,h31,h32,timemodified,timecreated) VALUES('$studentid','8','0','0','0','0','0','0','$timecreated','$timecreated')"); 


$achieve1='가능 : 충남대, 경북대, 충북대, 부산대 등...';$achieve2='가능 : 건국대, 숭실대, 단국대 등...';$achieve3='가능 : 중앙대, 경희대, 외국어대, 시립대 등';$achieve4='가능 : 한양대, 성균관대, 서강대';$achieve5='가능 : 서울대, 카이스트, 연세대, 고려대, 포항공대';$achieve6='가능 : 의대, 치대, 한의대';

$nforecast=0;
$result = json_decode(json_encode($missionlist), True);
unset($value);
foreach($result as $value)
	{
	$curid=$value['id'];
	$daystr='day'.$npath.$nuniv;
	$dday=$value[$daystr];
		
	$norder=$value['norder'];
	
	$datepid='datepicker'.$norder;
	$msnstr='msn'.$norder;
	
	$presetstr='squareInput'.$norder;
	$$msnstr = $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE subject LIKE '$curid' AND userid='$studentid' ORDER BY id DESC LIMIT 1"); // missiontype으로 mission 종류 선택

	$strdeadline=strtotime($$msnstr->deadline);

 	$nyears=substr($$msnstr->deadline, 0, 4); //  $nyears=round($dday/100,0);  //
	$$mnth=substr($$msnstr->deadline,6, 2); //$mnth=$dday-$nyears*100;
	  //($birthyear->data+6+$nyears+$mnth/12-1970)*86400*365;  // school years
 	$ngrade=$nyears-$birthyear->data-6;
	if($ngrade<=6 && $strdeadline>time() )
		{
		$ddaygrade='초'.$ngrade;
		}
	elseif($ngrade<=9 && $strdeadline>time())//중학교 입학
		{
		$ngrade=$ngrade-6;$ddaygrade='중'.$ngrade;
		if($nforecast==0)
			{
			if($norder>=111)$predict1=$achieve1;
			elseif($norder>=113)$predict1=$achieve2;
			elseif($norder>=115)$predict1=$achieve3;
			elseif($norder>=117)$predict1=$achieve4;
			elseif($norder>=118)$predict1=$achieve5;
			elseif($norder>=119)$predict1=$achieve6;
			$nforecast=1;
			}	
		}
	elseif($ngrade<=12 && $strdeadline>time())//고등학교 입학
		{
		$ngrade=$ngrade-9;$ddaygrade='고'.$ngrade;
		if($nforecast==1)
			{
			if($norder>=116)$predict2=$achieve1;
			elseif($norder>=117)$predict2=$achieve2;
			elseif($norder>=118)$predict2=$achieve3;
			elseif($norder>=119)$predict2=$achieve4;
			elseif($norder>=215)$predict2=$achieve5;
			elseif($norder>=216)$predict2=$achieve6;
			$nforecast=2;
			} 
		}
	elseif($ngrade<=12 && $strdeadline>time())//고2 개학
		{
		$ngrade=$ngrade-9;$ddaygrade='고'.$ngrade;
		if($nforecast==2)
			{
			if($norder>=116)$predict3=$achieve1;
			elseif($norder>=117)$predict3=$achieve2;
			elseif($norder>=118)$predict3=$achieve3;
			elseif($norder>=119)$predict3=$achieve4;
			elseif($norder>=215)$predict3=$achieve5;
			elseif($norder>=216)$predict3=$achieve6;
			$nforecast=3;
			}
		}
 	elseif($ngrade<=13 && $strdeadline>time())//고3 개학
		{
		$ngrade=$ngrade-9;$ddaygrade='고'.$ngrade;
		if($nforecast==3)
			{
			if($norder>=116)$predict4=$achieve1;
			elseif($norder>=117)$predict4=$achieve2;
			elseif($norder>=118)$predict4=$achieve3;
			elseif($norder>=119)$predict4=$achieve4;
			elseif($norder>=215)$predict4=$achieve5;
			elseif($norder>=216)$predict4=$achieve6;
			$nforecast=4;
			}
		}
 
	$timegap=round(($strdeadline-time())/86400,0);
 
	$txtcolor='blue';
	if($timegap<=0)$txtcolor='#cccccc';
	elseif($timegap>=365)$txtcolor='black';
	if($value['mtid']==7) // 개념
		{
		if($norder==103)$$presetstr=$preset7->e41;elseif($norder==104)$$presetstr=$preset7->e42;elseif($norder==105)$$presetstr=$preset7->e51;elseif($norder==106)$$presetstr=$preset7->e52;elseif($norder==107)$$presetstr=$preset7->e61;elseif($norder==108)$$presetstr=$preset7->e62;
		elseif($norder==109)$$presetstr=$preset7->m11;elseif($norder==110)$$presetstr=$preset7->m12;elseif($norder==111)$$presetstr=$preset7->m21;elseif($norder==112)$$presetstr=$preset7->m22;elseif($norder==113)$$presetstr=$preset7->m31;elseif($norder==114)$$presetstr=$preset7->m32;
		elseif($norder==115)$$presetstr=$preset7->h11;elseif($norder==116)$$presetstr=$preset7->h12;elseif($norder==117)$$presetstr=$preset7->h21;elseif($norder==118)$$presetstr=$preset7->h22;elseif($norder==119)$$presetstr=$preset7->h31;elseif($norder==120)$$presetstr=$preset7->h32;elseif($norder==121)$$presetstr=$preset7->h33;
		
		 
		if($mode==='edit')$mission1.='<tr><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center><input   style="font-size:16px;"type="text" class="form-control" id="'.$datepid.'" name="'.$datepid.'"  placeholder="'.$birthyear->data.'" value="'.$$msnstr->deadline.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$presetstr.'" value="'.$$presetstr.'"></td></tr>';   // 개념노트
		else $mission1.='<tr style="color:'.$txtcolor.'"><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center>'.$$msnstr->deadline.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$ddaygrade.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$timegap.'일</td></tr>';   // 개념노트
		}
	if($value['mtid']==2) // 심화
		{
		if($norder==203)$$presetstr=$preset2->e41;elseif($norder==204)$$presetstr=$preset2->e42;elseif($norder==205)$$presetstr=$preset2->e51;elseif($norder==206)$$presetstr=$preset2->e52;elseif($norder==207)$$presetstr=$preset2->e61;elseif($norder==208)$$presetstr=$preset2->e62;
		elseif($norder==209)$$presetstr=$preset2->m11;elseif($norder==210)$$presetstr=$preset2->m12;elseif($norder==211)$$presetstr=$preset2->m21;elseif($norder==212)$$presetstr=$preset2->m22;elseif($norder==213)$$presetstr=$preset2->m31;elseif($norder==214)$$presetstr=$preset2->m32;
		elseif($norder==215)$$presetstr=$preset2->h11;elseif($norder==216)$$presetstr=$preset2->h12;elseif($norder==217)$$presetstr=$preset2->h21;elseif($norder==218)$$presetstr=$preset2->h22;elseif($norder==219)$$presetstr=$preset2->h31;elseif($norder==220)$$presetstr=$preset2->h32;elseif($norder==221)$$presetstr=$preset2->h33;
		
		if($mode==='edit')$mission2.='<tr><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center><input   style="font-size:16px;"type="text" class="form-control" id="'.$datepid.'" name="'.$datepid.'"  placeholder="'.$birthyear->data.'" value="'.$$msnstr->deadline.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$presetstr.'" value="'.$$presetstr.'"></td></tr>';   // 심화미션
		else $mission2.='<tr style="color:'.$txtcolor.'"><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center>'.$$msnstr->deadline.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$ddaygrade.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$timegap.'일</td></tr>';   // 심화미션
		}
	if($value['mtid']==3) // 내신
		{
		$mathgradem='mathgradem'.$norder;
		$mathgradef='mathgradef'.$norder;
		//가중치 preset
		if($norder==309)$$presetstr=$preset3->m11;elseif($norder==310)$$presetstr=$preset3->m12;elseif($norder==311)$$presetstr=$preset3->m21;elseif($norder==312)$$presetstr=$preset3->m22;elseif($norder==313)$$presetstr=$preset3->m31;elseif($norder==314)$$presetstr=$preset3->m32;
		elseif($norder==315)$$presetstr=$preset3->h11;elseif($norder==316)$$presetstr=$preset3->h12;elseif($norder==317)$$presetstr=$preset3->h21;elseif($norder==318)$$presetstr=$preset3->h22;elseif($norder==319)$$presetstr=$preset3->h31;elseif($norder==320)$$presetstr=$preset3->h32;elseif($norder==321)$$presetstr=$preset3->h33;

		//중간고사
		if($norder==309)$$mathgradem=$preset5->m11;elseif($norder==310)$$mathgradem=$preset5->m12;elseif($norder==311)$$mathgradem=$preset5->m21;elseif($norder==312)$$mathgradem=$preset5->m22;elseif($norder==313)$$mathgradem=$preset5->m31;elseif($norder==314)$$mathgradem=$preset5->m32;
		elseif($norder==315)$$mathgradem=$preset5->h11;elseif($norder==316)$$mathgradem=$preset5->h12;elseif($norder==317)$$mathgradem=$preset5->h21;elseif($norder==318)$$mathgradem=$preset5->h22;elseif($norder==319)$$mathgradem=$preset5->h31;elseif($norder==320)$$mathgradem=$preset5->h32;elseif($norder==321)$$mathgradem=$preset5->h33;
		//기말고사
		if($norder==309)$$mathgradef=$preset6->m11;elseif($norder==310)$$mathgradef=$preset6->m12;elseif($norder==311)$$mathgradef=$preset6->m21;elseif($norder==312)$$mathgradef=$preset6->m22;elseif($norder==313)$$mathgradef=$preset6->m31;elseif($norder==314)$$mathgradef=$preset6->m32;
		elseif($norder==315)$$mathgradef=$preset6->h11;elseif($norder==316)$$mathgradef=$preset6->h12;elseif($norder==317)$$mathgradef=$preset6->h21;elseif($norder==318)$$mathgradef=$preset6->h22;elseif($norder==319)$$mathgradef=$preset6->h31;elseif($norder==320)$$mathgradef=$preset6->h32;elseif($norder==321)$$mathgradef=$preset6->h33;
		
		
		if($mode==='edit')$mission3.='<tr><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center><input   style="font-size:16px;"type="text" class="form-control" id="'.$datepid.'" name="'.$datepid.'"  placeholder="'.$birthyear->data.'" value="'.$$msnstr->deadline.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$presetstr.'" value="'.$$presetstr.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$mathgradem.'" value="'.$$mathgradem.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$mathgradef.'" value="'.$$mathgradef.'"></td></tr>';   // 내신미션
		else $mission3.='<tr style="color:'.$txtcolor.'"><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center>'.$$msnstr->deadline.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$ddaygrade.'</td><td width=10%>'.$$mathgradem.'</td><td width=10%>'.$$mathgradef.'</td></tr>';   // 내신미션
		}
	if($value['mtid']==4) // 수능
		{
		$mathgrademofrst='mathgrademofrst'.$norder;
		$mathgrademoscnd='mathgrademoscnd'.$norder;
		
		if($norder==401)$$presetstr=$preset4->h11;elseif($norder==402)$$presetstr=$preset4->h21;elseif($norder==403)$$presetstr=$preset4->h31; //가중치 preset
		if($norder==401)$$mathgrademofrst=$preset8->h11;elseif($norder==402)$$mathgrademofrst=$preset8->h21;elseif($norder==403)$$mathgrademofrst=$preset8->h31; // 1학기등급
		if($norder==401)$$mathgrademoscnd=$preset8->h12;elseif($norder==402)$$mathgrademoscnd=$preset8->h22;elseif($norder==403)$$mathgrademoscnd=$preset8->h32; //2학기등급
		if($mode==='edit')$mission4.='<tr><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center><input   style="font-size:16px;"type="text" class="form-control" id="'.$datepid.'" name="'.$datepid.'"  placeholder="'.$birthyear->data.'" value="'.$$msnstr->deadline.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$presetstr.'" value="'.$$presetstr.'"></td><td width=10%><input type="text" class="form-control input-square" id="'.$mathgrademofrst.'" value="'.$$mathgrademofrst.'"><td width=10%><input type="text" class="form-control input-square" id="'.$mathgrademoscnd.'" value="'.$$mathgrademoscnd.'"></tr>';   // 수능미션
		else $mission4.='<tr style="color:'.$txtcolor.'"><td width=3%></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"  width=30% >'.$value['name'].'</td><td width=3%></td><td width=30% align=center>'.$$msnstr->deadline.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$ddaygrade.'</td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center>'.$timegap.'일</td><td width=10%></td></tr>';   // 수능미션
		
		}
	} 

$studentid=required_param('id', PARAM_INT); 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$fullplanurl='https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid;
echo '<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center width=100%> <td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><table align=center style="padding-bottom:3px; font-size: 20px; color:#ffffff;" width=100%><tr><td align=center><a  style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">내 공부방</a></td><td align=right>나의 성공 스토리를 만들어 보세요 (<a href="https://mathking.kr/moodle/local/augmented_teacher/students/hellobrain.php?id='.$studentid.'">입력</a>)</td><td align=right><b> We transfer intelligence with CJN scaffolding</b> </td><td  width=5% ></td><td style="font-size:14px;">  KAIST TOUCH MATH powered by CJN</td></tr></table></td></tr></table></div></div> <br> <br> ';
 
 if($lmode->data==='자율')$modeselectstate1='selected'; elseif($lmode->data==='지도')$modeselectstate2='selected'; elseif($lmode->data==='도제')$modeselectstate3='selected';  elseif($lmode->data==='신규')$modeselectstate4='selected';  
 
 
// 강좌목록 & 데드라인 표시

$thisyear=date("Y",time());
$thismonth=date("m",time());
$stdage=$thisyear-$birthyear->data+1;

if($stdage<=13)
	{
	$stdgrade=$stdage-7;
	$numgrade='1'.$stdgrade.$thismonth;
	$gradestr='초등학교 '.$stdgrade.'학년 '.$thismonth.'월';
	}
elseif($stdage<=16)
	{
	$stdgrade=$stdage-13;
	$numgrade='2'.$stdgrade.$thismonth;
	$gradestr='중학교 '.$stdgrade.'학년 '.$thismonth.'월';
	}
elseif($stdage<=20)
	{
	$stdgrade=$stdage-16;
	$numgrade='3'.$stdgrade.$thismonth;
	$gradestr='고등학교 '.$stdgrade.'학년 '.$thismonth.'월';
	}



if($mode==='edit') echo '<table align=center  width=95%><tr style="font-size:25px;"><td align=center width=27%><b style="color:#0066cc;">개념미션</b></td><td width=3%></td><td  width=36% align=center><b style="color:#0066cc;">내신미션</b></td><td width=3%></td><td width=26% align=center><b style="color:#0066cc;">심화미션(촉진)</b></td></tr>
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
<td valign=top><table width=100%><tr><th></th><th>과목</th><th></th><th>데드라인</th><th>Bias</th></tr>'.$mission1.'</table></td><td></td><td valign=top><table width=100%><tr><th></th><th>과목</th><th></th><th>데드라인</th><th>Bias</th><th>중간</th><th>기말</th></tr>'.$mission3.'</table><br><table align=center><tr><td><b style="font-size:25px;color:#0066cc;">수능미션</b></td></tr></table><hr><table width=100%><tr><th></th><th>과목</th><th></th><th>데드라인</th><th>Bias</th><th>1학기</th><th>2학기</th></tr>'.$mission4.'</table></td><td></td><td valign=top><table width=100%><tr><th></th><th>과목</th><th></th><th>데드라인</th><th>Bias</th></tr>'.$mission2.'</table></td></tr> 
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
 <tr><td ><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'">'.$studentname.'</a> ('.$birthyear->data.'년 출생 | '.$gradestr.')</td><td></td>
<td align=right>
<button style="font-size:18;" type="image" onclick="saveproperties('.$studentid.',$(\'#datepicker103\').val(),$(\'#datepicker104\').val(),$(\'#datepicker105\').val(),$(\'#datepicker106\').val(),$(\'#datepicker107\').val(),$(\'#datepicker108\').val(),$(\'#datepicker109\').val(),$(\'#datepicker110\').val(),$(\'#datepicker111\').val(),$(\'#datepicker112\').val(),$(\'#datepicker113\').val(),$(\'#datepicker114\').val(),$(\'#datepicker115\').val(),$(\'#datepicker116\').val(),$(\'#datepicker117\').val(),$(\'#datepicker118\').val(),$(\'#datepicker119\').val(),$(\'#datepicker120\').val(),$(\'#datepicker121\').val()
,$(\'#datepicker203\').val(),$(\'#datepicker204\').val(),$(\'#datepicker205\').val(),$(\'#datepicker206\').val(),$(\'#datepicker207\').val(),$(\'#datepicker208\').val(),$(\'#datepicker209\').val(),$(\'#datepicker210\').val(),$(\'#datepicker211\').val(),$(\'#datepicker212\').val(),$(\'#datepicker213\').val(),$(\'#datepicker214\').val(),$(\'#datepicker215\').val(),$(\'#datepicker216\').val(),$(\'#datepicker217\').val(),$(\'#datepicker218\').val(),$(\'#datepicker219\').val(),$(\'#datepicker220\').val(),$(\'#datepicker221\').val()
,$(\'#datepicker309\').val(),$(\'#datepicker310\').val(),$(\'#datepicker311\').val(),$(\'#datepicker312\').val(),$(\'#datepicker313\').val(),$(\'#datepicker314\').val(),$(\'#datepicker315\').val(),$(\'#datepicker316\').val(),$(\'#datepicker317\').val(),$(\'#datepicker318\').val(),$(\'#datepicker319\').val(),$(\'#datepicker320\').val(),$(\'#datepicker321\').val()
,$(\'#datepicker401\').val(),$(\'#datepicker402\').val(),$(\'#datepicker403\').val()) ">데드라인 설정</button></td><td></td>

<td align=center> <button style="font-size:18;" type="image" onclick="saveproperties3('.$studentid.',$(\'#mathgradem309\').val(),$(\'#mathgradem310\').val(),$(\'#mathgradem311\').val(),$(\'#mathgradem312\').val(),$(\'#mathgradem313\').val(),$(\'#mathgradem314\').val(),$(\'#mathgradem315\').val(),$(\'#mathgradem316\').val(),$(\'#mathgradem317\').val(),$(\'#mathgradem318\').val(),$(\'#mathgradem319\').val(),$(\'#mathgradem320\').val(),$(\'#mathgradem321\').val(),$(\'#mathgrademofrst401\').val(),$(\'#mathgrademofrst402\').val(),$(\'#mathgrademofrst403\').val()
,$(\'#mathgradef309\').val(),$(\'#mathgradef310\').val(),$(\'#mathgradef311\').val(),$(\'#mathgradef312\').val(),$(\'#mathgradef313\').val(),$(\'#mathgradef314\').val(),$(\'#mathgradef315\').val(),$(\'#mathgradef316\').val(),$(\'#mathgradef317\').val(),$(\'#mathgradef318\').val(),$(\'#mathgradef319\').val(),$(\'#mathgradef320\').val(),$(\'#mathgradef321\').val(),$(\'#mathgrademoscnd401\').val(),$(\'#mathgrademoscnd402\').val(),$(\'#mathgrademoscnd403\').val()) ">목표점수 업데이트</button>

<button style="font-size:18;" type="image" onclick="saveproperties2('.$studentid.',$(\'#squareInput103\').val(),$(\'#squareInput104\').val(),$(\'#squareInput105\').val(),$(\'#squareInput106\').val(),$(\'#squareInput107\').val(),$(\'#squareInput108\').val(),$(\'#squareInput109\').val(),$(\'#squareInput110\').val(),$(\'#squareInput111\').val(),$(\'#squareInput112\').val(),$(\'#squareInput113\').val(),$(\'#squareInput114\').val(),$(\'#squareInput115\').val(),$(\'#squareInput116\').val(),$(\'#squareInput117\').val(),$(\'#squareInput118\').val(),$(\'#squareInput119\').val(),$(\'#squareInput120\').val(),$(\'#squareInput121\').val()
,$(\'#squareInput203\').val(),$(\'#squareInput204\').val(),$(\'#squareInput205\').val(),$(\'#squareInput206\').val(),$(\'#squareInput207\').val(),$(\'#squareInput208\').val(),$(\'#squareInput209\').val(),$(\'#squareInput210\').val(),$(\'#squareInput211\').val(),$(\'#squareInput212\').val(),$(\'#squareInput213\').val(),$(\'#squareInput214\').val(),$(\'#squareInput215\').val(),$(\'#squareInput216\').val(),$(\'#squareInput217\').val(),$(\'#squareInput218\').val(),$(\'#squareInput219\').val(),$(\'#squareInput220\').val(),$(\'#squareInput221\').val()
,$(\'#squareInput309\').val(),$(\'#squareInput310\').val(),$(\'#squareInput311\').val(),$(\'#squareInput312\').val(),$(\'#squareInput313\').val(),$(\'#squareInput314\').val(),$(\'#squareInput315\').val(),$(\'#squareInput316\').val(),$(\'#squareInput317\').val(),$(\'#squareInput318\').val(),$(\'#squareInput319\').val(),$(\'#squareInput320\').val(),$(\'#squareInput321\').val()
,$(\'#squareInput401\').val(),$(\'#squareInput402\').val(),$(\'#squareInput403\').val()) ">PRESET 적용</button>   

</td></tr> </table>';     
                               
else echo '<table align=center  width=95%><tr style="font-size:25px;"><td align=center><b style="color:#0066cc;">개념미션</b></td><td width=3%></td><td align=center><b style="color:#0066cc;">내신미션</b></td><td width=3%></td><td align=center><b style="color:#0066cc;">심화미션(촉진)</b></td></tr>
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
<td valign=top><table width=100%>'.$mission1.'</table></td><td></td><td valign=top><table width=100%>'.$mission3.'</table><br><table align=center><tr><td><b style="font-size:25px;color:#0066cc;">수능미션</b></td></tr></table><hr><table width=100%>'.$mission4.'</table></td><td></td><td valign=top><table width=100%>'.$mission2.'</table></td></tr> 
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
 <tr><td></td><td ></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'">'.$studentname.'</a> ('.$birthyear->data.'년 출생 | '.$gradestr.')</td><td ></td><td><button><a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'&mode=edit">편집하기</a></button></td></tr> </table>';                                    

// 예측결과
echo '<table align=center  width=95%><tr style="font-size:20px;"><td align=center><b style="color:#0666cc;">중학교 입학 시점 전망</b></td><td width=3%></td><td align=center><b style="color:#0666cc;">고등학교 입학 시점 전망</b></td><td width=3%></td><td align=center><b style="color:#bd004f;">수시(내신) 목표대학</b></td><td width=3%></td><td align=center><b style="color:#bd004f;">정시(수능) 목표대학</b></td></tr>
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
<tr><td align=center>'.$predict1.'</td><td><hr></td><td align=center>'.$predict2.'</td><td><hr></td><td align=center>'.$predict3.'</td><td><hr></td><td align=center>'.$predict4.'</td></tr>
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';

echo '<br><br><hr><table width="100%"><tr><td>난이도</td><td><img  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654452243.png" width=50 ></td><td>상태</td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/departure.gif" width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flying.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flyingthroughfield.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646909102.png" width=200  ></td></tr></table><hr>
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 500px;
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
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 500px;
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
  width: 500px;
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

 echo '

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
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
	<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>

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

  
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<link rel="stylesheet" href="../assets/css/ready.min.css"> 
	<script>
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker101").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker102").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker103").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker104").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker105").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker106").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker107").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker108").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker109").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker110").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker111").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker112").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker113").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker114").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker115").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker116").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker117").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker118").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker119").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker120").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker121").datetimepicker({
			format: "YYYY/MM/DD",
		});

		$("#datepicker201").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker202").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker203").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker204").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker205").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker206").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker207").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker208").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker209").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker210").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker211").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker212").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker213").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker214").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker215").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker216").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker217").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker218").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker219").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker220").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker221").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker301").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker302").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker303").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker304").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker305").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker306").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker307").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker308").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker309").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker310").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker311").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker312").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker313").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker314").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker315").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker316").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker317").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker318").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker319").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker320").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker321").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker401").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker402").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker403").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker404").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker405").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker406").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker407").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker408").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker409").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker410").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker411").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker412").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker413").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker414").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker415").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker416").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker417").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker418").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker419").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker420").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker421").datetimepicker({
			format: "YYYY/MM/DD",
		});
function saveproperties(Userid,Date103,Date104,Date105,Date106,Date107,Date108,Date109,Date110,Date111,Date112,Date113,Date114,Date115,Date116,Date117,Date118,Date119,Date120,Date121,Date203,Date204,Date205,Date206,Date207,Date208,Date209,Date210,Date211,Date212,Date213,Date214,Date215,Date216,Date217,Date218,Date219,Date220,Date221,Date309,Date310,Date311,Date312,Date313,Date314,Date315,Date316,Date317,Date318,Date319,Date320,Date321,Date401,Date402,Date403)
	{
	 
	swal({title: \'저장되었습니다.\',});	
	var Fullplanurl= \''.$fullplanurl.'\'; 
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'41\',
	"userid":Userid,       
 	"date103":Date103,  
 	"date104":Date104,  
 	"date105":Date105,  
 	"date106":Date106,  
 	"date107":Date107,  
 	"date108":Date108,  
 	"date109":Date109,  
 	"date110":Date110,  
 	"date111":Date111,  
 	"date112":Date112,  
 	"date113":Date113,  
 	"date114":Date114,  
 	"date115":Date115,  
 	"date116":Date116,  
 	"date117":Date117,  
 	"date118":Date118,  
 	"date119":Date119,  
 	"date120":Date120,  
 	"date121":Date121,  

 	"date203":Date203,  
 	"date204":Date204,  
 	"date205":Date205,  
 	"date206":Date206,  
 	"date207":Date207,  
 	"date208":Date208,  
 	"date209":Date209,  
 	"date210":Date210,  
 	"date211":Date211,  
 	"date212":Date212,  
 	"date213":Date213,  
 	"date214":Date214,  
 	"date215":Date215,  
 	"date216":Date216,  
 	"date217":Date217,  
 	"date218":Date218,  
 	"date219":Date219,  
 	"date220":Date220,  
 	"date221":Date221,  

 	"date309":Date309,  
 	"date310":Date310,  
 	"date311":Date311,  
 	"date312":Date312,  
 	"date313":Date313,  
 	"date314":Date314,  
 	"date315":Date315,  
 	"date316":Date316,  
 	"date317":Date317,  
 	"date318":Date318,  
 	"date319":Date319,  
 	"date320":Date320,  
 	"date321":Date321,  

 	"date401":Date401,  
 	"date402":Date402,  
 	"date403":Date403,  
	},
	success:function(data){
		
			}
	 })
	
   	setTimeout(function(){location.href=Fullplanurl} , 1000);   
	}
function saveproperties2(Userid,Bias103,Bias104,Bias105,Bias106,Bias107,Bias108,Bias109,Bias110,Bias111,Bias112,Bias113,Bias114,Bias115,Bias116,Bias117,Bias118,Bias119,Bias120,Bias121,Bias203,Bias204,Bias205,Bias206,Bias207,Bias208,Bias209,Bias210,Bias211,Bias212,Bias213,Bias214,Bias215,Bias216,Bias217,Bias218,Bias219,Bias220,Bias221,Bias309,Bias310,Bias311,Bias312,Bias313,Bias314,Bias315,Bias316,Bias317,Bias318,Bias319,Bias320,Bias321,Bias401,Bias402,Bias403)
	{
	swal({title: \'저장되었습니다.\',});	
	var Fullplanurl= \''.$fullplanurl.'\'; 
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'42\',
	"userid":Userid,       
 	"bias103":Bias103,  
 	"bias104":Bias104,  
 	"bias105":Bias105,  
 	"bias106":Bias106,  
 	"bias107":Bias107,  
 	"bias108":Bias108,  
 	"bias109":Bias109,  
 	"bias110":Bias110,  
 	"bias111":Bias111,  
 	"bias112":Bias112,  
 	"bias113":Bias113,  
 	"bias114":Bias114,  
 	"bias115":Bias115,  
 	"bias116":Bias116,  
 	"bias117":Bias117,  
 	"bias118":Bias118,  
 	"bias119":Bias119,  
 	"bias120":Bias120,  
 	"bias121":Bias121,  

 	"bias203":Bias203,  
 	"bias204":Bias204,  
 	"bias205":Bias205,  
 	"bias206":Bias206,  
 	"bias207":Bias207,  
 	"bias208":Bias208,  
 	"bias209":Bias209,  
 	"bias210":Bias210,  
 	"bias211":Bias211,  
 	"bias212":Bias212,  
 	"bias213":Bias213,  
 	"bias214":Bias214,  
 	"bias215":Bias215,  
 	"bias216":Bias216,  
 	"bias217":Bias217,  
 	"bias218":Bias218,  
 	"bias219":Bias219,  
 	"bias220":Bias220,  
 	"bias221":Bias221,  

 	"bias309":Bias309,  
 	"bias310":Bias310,  
 	"bias311":Bias311,  
 	"bias312":Bias312,  
 	"bias313":Bias313,  
 	"bias314":Bias314,  
 	"bias315":Bias315,  
 	"bias316":Bias316,  
 	"bias317":Bias317,  
 	"bias318":Bias318,  
 	"bias319":Bias319,  
 	"bias320":Bias320,  
 	"bias321":Bias321,  

 	"bias401":Bias401,  
 	"bias402":Bias402,  
 	"bias403":Bias403,      
	},
	success:function(data){
		
			}
	 })
	 
   	setTimeout(function(){location.href=Fullplanurl} , 1000);   
	}
function saveproperties3(Userid,Biasp309,Biasp310,Biasp311,Biasp312,Biasp313,Biasp314,Biasp315,Biasp316,Biasp317,Biasp318,Biasp319,Biasp320,Biasp321,Biasp401,Biasp402,Biasp403,Biasq309,Biasq310,Biasq311,Biasq312,Biasq313,Biasq314,Biasq315,Biasq316,Biasq317,Biasq318,Biasq319,Biasq320,Biasq321,Biasq401,Biasq402,Biasq403)
	{
	swal({title: \'저장되었습니다.\',});	
	var Fullplanurl= \''.$fullplanurl.'\'; 
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'43\',
	"userid":Userid,       

 	"biasp309":Biasp309,  
 	"biasp310":Biasp310,  
 	"biasp311":Biasp311,  
 	"biasp312":Biasp312,  
 	"biasp313":Biasp313,  
 	"biasp314":Biasp314,  
 	"biasp315":Biasp315,  
 	"biasp316":Biasp316,  
 	"biasp317":Biasp317,  
 	"biasp318":Biasp318,  
 	"biasp319":Biasp319,  
 	"biasp320":Biasp320,  
 	"biasp321":Biasp321,  
 	"biasp401":Biasp401,  
 	"biasp402":Biasp402,  
 	"biasp403":Biasp403,      

 	"biasq309":Biasq309,  
 	"biasq310":Biasq310,  
 	"biasq311":Biasq311,  
 	"biasq312":Biasq312,  
 	"biasq313":Biasq313,  
 	"biasq314":Biasq314,  
 	"biasq315":Biasq315,  
 	"biasq316":Biasq316,  
 	"biasq317":Biasq317,  
 	"biasq318":Biasq318,  
 	"biasq319":Biasq319,  
 	"biasq320":Biasq320,  
 	"biasq321":Biasq321,  
 	"biasq401":Biasq401,  
 	"biasq402":Biasq402,  
 	"biasq403":Biasq403,  
	},
	success:function(data){
		
			}
	 }) 
   	setTimeout(function(){location.href=Fullplanurl} , 1000);   
	}

	function inputgoalstep(Eventid,Userid,Plantype,Deadline,Inputtext){   
		swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			
			  "plantype":Plantype,
			  "deadline":Deadline,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		setTimeout(function() {location.reload(); },100);
		}
 
	function updatecheck(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("완료상태가 변경되었습니다.", {buttons: false,timer: 1000});
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		}

	function updatecheck2(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 alert("체크 상태에서 새로고침하면 목록에서 사라집니다");
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
			 
		} 
 
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
 
 
		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic").select2({
			theme: "bootstrap"
		});

		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});

		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );
	</script>


</body>';
?>