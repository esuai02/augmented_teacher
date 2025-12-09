<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid=required_param('id', PARAM_INT); 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role!=='student')echo '';
elseif($USER->id!==$studentid)
	{
	echo '접근권한이 없습니다.';
	exit();
	}
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname; 

$institute = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='88' ");// 학교 

$birthdate = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='89' ");//출생년도 

$email1 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='112' ");//이메일 (부)
$email2 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='113' "); //이메일 (모)
$phone1 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='54' ");//학생 연락처 
$phone2 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='85' "); //아버지 연락처 
$phone3 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='55' "); //어머니 연락처 
$brotherhood =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='44' ");//형제관계 
$academy =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='46' "); //학원명
$location = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='68' ");//지역 
$addcourse = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='83' "); //코스추천
$roleinfo = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='22' ");// 사용자 유형

$fluency = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='60' "); // 사용법 능숙도 
$goalstability = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='80' "); //목표설정 안정도 
$effectivelearning = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='81' ");// 81 논리분리
$lmode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='90' "); // 신규,자율,지도,도제
$evaluate= $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='92' ");// 92 완결형/도전형
$curriculum = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='70' "); // 70 쇠퇴형/표준형/성장형
$nboosters = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='86' "); //부스터 활동 횟수 
$inspecttime = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='72' "); //점검주기
$roleinfo = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='22' "); //사용자유형
// 학습데이터
// 호출조건
$termhours = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='107' ");// 학기중 주별 공부시간
$vachours = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='108' ");// 방학중 주별 공부시간

$univ = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='105' ");// 학교 
$curtype = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='106' ");// 커리큘럼 유형 
$Preseta = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='93' ");// 93 개념미션 PRESET
$Presetb = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='94' ");// 94 심화미션 PRESET 
$Presetc = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='95' ");// 95 내신미션 PRESET 
$Presetd = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='96' ");// 96 수능미션 PRESET

$mathlevel = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='114' ");// 학습수준
$classtype = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='115' ");// 보충과정
$progresstype= $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='116' ");// 진도

echo '<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center ><td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><table align=center style="1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><tr><td><b> We transfer intelligence with CJN scaffolding</b> </td><td  width=5% ></td><td style="font-size:14px;">  KAIST TOUCH MATH powered by CJN</td></tr></table></td></tr></table></div></div> <br> <br> ';

if($addcourse->data==='개념')$adselectstate1='selected'; elseif($addcourse->data==='심화')$adselectstate2='selected'; elseif($addcourse->data==='내신')$adselectstate3='selected'; elseif($addcourse->data==='수능')$adselectstate4='selected';elseif($addcourse->data==='주제별특강')$adselectstate5='selected'; 
if($location->data==='서울')$loselectstate1='selected'; elseif($location->data==='경기')$loselectstate2='selected'; elseif($location->data==='충남')$loselectstate3='selected'; elseif($location->data==='충북')$loselectstate4='selected';elseif($location->data==='경남')$loselectstate5='selected';elseif($location->data==='경북')$loselectstate6='selected';elseif($location->data==='전남')$loselectstate7='selected';elseif($location->data==='전북')$loselectstate8='selected';elseif($location->data==='강원')$loselectstate9='selected';elseif($location->data==='부산')$loselectstate10='selected';elseif($location->data==='인천')$loselectstate11='selected';elseif($location->data==='대전')$loselectstate12='selected';elseif($location->data==='대구')$loselectstate13='selected';elseif($location->data==='광주')$loselectstate14='selected';elseif($location->data==='울산')$loselectstate15='selected';elseif($location->data==='제주')$loselectstate16='selected';
if($fluency->data==='시작')$flselectstate1='selected'; elseif($fluency->data==='적응')$flselectstate2='selected'; elseif($fluency->data==='능숙')$flselectstate3='selected'; elseif($fluency->data==='마스터')$flselectstate4='selected';
if($goalstability->data==='장기계획')$goalselectstate1='selected'; elseif($goalstability->data==='분기목표')$goalselectstate2='selected'; elseif($goalstability->data==='중간목표')$goalselectstate3='selected'; elseif($goalstability->data==='주간목표')$goalselectstate4='selected';elseif($goalstability->data==='오늘목표')$goalselectstate5='selected';  
if($effectivelearning->data==='논리지식 분리')$efselectstate1='selected'; elseif($effectivelearning->data==='논리지식 혼합')$efselectstate2='selected'; elseif($effectivelearning->data==='지식계산 분리')$efselectstate3='selected';  elseif($effectivelearning->data==='지식계산 혼합')$efselectstate4='selected'; 
if($lmode->data==='자율')$modeselectstate1='selected'; elseif($lmode->data==='능동')$modeselectstate2='selected'; elseif($lmode->data==='도제')$modeselectstate3='selected';  elseif($lmode->data==='신규')$modeselectstate4='selected';  
if($evaluate->data==='도전형')$evselectstate1='selected'; elseif($evaluate->data==='완결형')$evselectstate2='selected';  
if($curriculum->data==='성장형')$cuselectstate1='selected'; elseif($curriculum->data==='표준형')$cuselectstate2='selected';  elseif($curriculum->data==='쇠퇴형')$cuselectstate3='selected';  
if($nboosters->data==1)$boostselectstate1='selected'; elseif($nboosters->data==2)$boostselectstate2='selected'; elseif($nboosters->data==3)$boostselectstate3='selected'; elseif($nboosters->data==4)$boostselectstate4='selected';elseif($nboosters->data==5)$boostselectstate5='selected'; 
if($inspecttime->data==10)$inselectstate1='selected'; elseif($inspecttime->data==20)$inselectstate2='selected';  elseif($inspecttime->data==30)$inselectstate3='selected';  elseif($inspecttime->data==60)$inselectstate4='selected'; elseif($inspecttime->data==90)$inselectstate5='selected';  
if($roleinfo->data==='student')$rolestate1='selected'; elseif($roleinfo->data==='teacher')$rolestate2='selected';  elseif($roleinfo->data==='manager')$rolestate3='selected';   

if($termhours->data==10)$termhours10='selected'; if($termhours->data==11)$termhours11='selected'; elseif($termhours->data==12)$termhours12='selected'; elseif($termhours->data==13)$termhours13='selected'; elseif($termhours->data==14)$termhours14='selected';elseif($termhours->data==15)$termhours15='selected';elseif($termhours->data==16)$termhours16='selected';elseif($termhours->data==17)$termhours17='selected';elseif($termhours->data==18)$termhours18='selected';elseif($termhours->data==19)$termhours19='selected';elseif($termhours->data==20)$termhours20='selected'; 
if($vachours->data==10)$vachours10='selected'; if($vachours->data==12)$vachours12='selected'; elseif($vachours->data==14)$vachours14='selected'; elseif($vachours->data==16)$vachours16='selected'; elseif($vachours->data==18)$vachours18='selected';elseif($vachours->data==20)$vachours20='selected';elseif($vachours->data==22)$vachours22='selected';elseif($vachours->data==24)$vachours24='selected';elseif($vachours->data==26)$vachours26='selected';elseif($vachours->data==28)$vachours28='selected';elseif($vachours->data==30)$vachours30='selected'; 

if($univ->data==1)$univcat1='selected'; elseif($univ->data==2)$univcat2='selected'; elseif($univ->data==3)$univcat3='selected'; elseif($univ->data==4)$univcat4='selected';elseif($univ->data==5)$univcat5='selected'; elseif($univ->data==6)$univcat6='selected'; 
if($curtype->data==1)$selectcur1='selected'; elseif($curtype->data==2)$selectcur2='selected'; elseif($curtype->data==3)$selectcur3='selected';  
if($Preseta->data==='Preset1')$PAselectstate1='selected'; elseif($Preseta->data==='Preset2')$PAselectstate2='selected'; elseif($Preseta->data==='Preset3')$PAselectstate3='selected'; elseif($Preseta->data==='Preset4')$PAselectstate4='selected';elseif($Preseta->data==='Preset5')$PAselectstate5='selected';  
if($Presetb->data==='Preset1')$PBselectstate1='selected'; elseif($Presetb->data==='Preset2')$PBselectstate2='selected'; elseif($Presetb->data==='Preset3')$PBselectstate3='selected'; elseif($Presetb->data==='Preset4')$PBselectstate4='selected';elseif($Presetb->data==='Preset5')$PBselectstate5='selected';  
if($Presetc->data==='Preset1')$PCselectstate1='selected'; elseif($Presetc->data==='Preset2')$PCselectstate2='selected'; elseif($Presetc->data==='Preset3')$PCselectstate3='selected'; elseif($Presetc->data==='Preset4')$PCselectstate4='selected';elseif($Presetc->data==='Preset5')$PCselectstate5='selected';  
if($Presetd->data==='Preset1')$PDselectstate1='selected'; elseif($Presetd->data==='Preset2')$PDselectstate2='selected'; elseif($Presetd->data==='Preset3')$PDselectstate3='selected'; elseif($Presetd->data==='Preset4')$PDselectstate4='selected';elseif($Presetd->data==='Preset5')$PDselectstate5='selected';  

if($mathlevel->data==='상')$mlevelselectstate1='selected'; elseif($mathlevel->data==='중')$mlevelselectstate2='selected'; elseif($mathlevel->data==='하')$mlevelselectstate3='selected'; 
if($classtype->data==='개념')$additionalselectstate1='selected'; elseif($classtype->data==='심화')$additionalselectstate2='selected'; elseif($classtype->data==='내신')$additionalselectstate3='selected'; elseif($classtype->data==='수능')$additionalselectstate4='selected'; elseif($classtype->data==='주제별특강')$additionalselectstate5='selected'; 

if($progresstype->data==='초등')$progressselectstate1='selected'; elseif($progresstype->data==='중등')$progressselectstate2='selected'; elseif($progresstype->data==='고등')$progressselectstate3='selected'; elseif($progresstype->data==='수능')$progressselectstate4='selected'; 

if($academy->data==NULL)$academydata='KTM';
else $academydata=$academy->data;

 echo '<table align=center><tr style="font-size:25px;"><td align=center><b style="color:#0066cc;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a> 기본정보</b></td><td width=3%></td><td align=center><b style="color:#0066cc;">학습조건</b></td><td width=3%></td><td align=center><b style="color:#0066cc;">목표설정 PRESET</b></td></tr>
<tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
<td valign=top>
<table>
<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=institute"target="_blank">▷</a> 학교 </td><td><input   style="font-size:16px;"type="text" class="form-control" id="squareInput1" name="squareInput1"  placeholder="'.$institute->data.'" value="'.$institute->data.'"></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=birthdate"target="_blank">▷</a> 출생년도</td><td><input   style="font-size:16px;"type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="'.$birthdate->data.'" value="'.$birthdate->data.'"></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><hr></td><td><hr></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=email1"target="_blank">✉</a> 이메일(부)</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="email1" name="email1"  placeholder="'.$email1->data.'" value="'.$email1->data.'" ></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=email2"target="_blank">✉</a> 이메일(모)</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="email2" name="email2"  placeholder="'.$email2->data.'" value="'.$email2->data.'" ></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=phone1"target="_blank">☎️</a> 연락처(학생)</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="squareInput2" name="squareInput2"  placeholder="'.$phone1->data.'" value="'.$phone1->data.'" ></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=phone2"target="_blank">☎️</a> 연락처(부)</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="squareInput3" name="squareInput3"  placeholder="'.$phone2->data.'" value="'.$phone2->data.'" ></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=phone3"target="_blank">☎️</a> 연락처(모)</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="squareInput4" name="squareInput4"  placeholder="'.$phone3->data.'" value="'.$phone3->data.'" ></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><hr></td><td><hr></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=institute"target="_blank">▷</a> 형제관계</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="squareInput5" name="squareInput5"  placeholder="'.$brotherhood->data.'" value="'.$brotherhood->data.'" ></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=academy"target="_blank">▷</a> 소속기관</td><td><div><input  style="font-size:16px;" type="text" class="form-control input-square" id="squareInput6" name="squareInput6"  placeholder="'.$academydata.'" value="'.$academydata.'" ></div></td></tr>



<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=location"target="_blank">▷</a> 지역</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic2" name="basic2" class="form-control" ><option value="서울" '.$loselectstate1.'>서울</option><option value="경기"  '.$loselectstate2.'>경기</option><option value="충남"  '.$loselectstate3.'>충남</option><option value="충북"  '.$loselectstate4.'>충북</option><option value="경남"  '.$loselectstate5.'>경남</option><option value="경북"  '.$loselectstate6.'>경북</option><option value="전남"  '.$loselectstate7.'>전남</option><option value="전북"  '.$loselectstate8.'>전북</option><option value="강원"  '.$loselectstate9.'>강원</option><option value="부산"  '.$loselectstate10.'>부산</option><option value="인천"  '.$loselectstate11.'>인천</option><option value="대전"  '.$loselectstate12.'>대전</option><option value="대구"  '.$loselectstate13.'>대구</option><option value="광주"  '.$loselectstate14.'>광주</option><option value="울산"  '.$loselectstate15.'>울산</option><option value="제주"  '.$loselectstate16.'>제주</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=roleinfo"target="_blank">▷</a> 사용자유형</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic15" name="basic15" class="form-control" ><option value="student" '.$rolestate1.'>학생</option><option value="teacher"  '.$rolestate2.'>선생님</option><option value="manager"  '.$rolestate3.'>매니저</option></select></div></td></tr>

</table> 
</td>
<td></td><td valign=top>  
<table>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=fluency"target="_blank"> ▷</a>사용법 능숙도</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic3" name="basic3" class="form-control" ><option value="시작" '.$flselectstate1.'>시작</option><option value="적응" '.$flselectstate2.'>적응</option><option value="능숙" '.$flselectstate3.'>능숙</option><option value="마스터"  '.$flselectstate4.'>마스터</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=goalstability"target="_blank"> ▷</a>목표설정 안정도</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic4" name="basic4" class="form-control" ><option value="장기계획" '.$goalselectstate1.'>장기계획</option><option value="분기목표" '.$goalselectstate2.'>분기목표</option><option value="중간목표" '.$goalselectstate3.'>중간목표</option><option value="주간목표" '.$goalselectstate4.'>주간목표</option><option value="오늘목표" '.$goalselectstate5.'>오늘목표</option><option value="활동설계" '.$goalselectstate6.'>활동설계</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=effectivelearning"target="_blank"> ▷</a>학습효율 단계</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic5" name="basic5" class="form-control" ><option value="논리지식 분리"  '.$efselectstate1.'>논리지식 분리</option><option value="논리지식 혼합" '.$efselectstate2.'>논리지식 혼합</option><option value="지식계산 분리" '.$efselectstate3.'>지식계산 분리</option><option value="지식계산 혼합" '.$efselectstate4.'>지식계산 혼합</option></select></div></td></tr> 

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=lmode"target="_blank"> ▷</a>학습모드 판단</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic6" name="basic6" class="form-control" ><option value="신규"  '.$modeselectstate4.'>신규</option><option value="자율"  '.$modeselectstate1.'>자율</option><option value="능동" '.$modeselectstate2.'>능동</option><option value="도제" '.$modeselectstate3.'>도제</option></select></div></td></tr> 

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=evaluate"target="_blank"> ▷</a>평가방식</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic7" name="basic7" class="form-control" ><option value="도전형"  '.$evselectstate1.'>도전형</option><option value="완결형" '.$evselectstate2.'>완결형</option></select></div></td></tr> 

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=curriculum"target="_blank"> ▷</a>커리큘럼 속성</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic8" name="basic8" class="form-control" ><option value="성장형"  '.$cuselectstate1.'>성장형</option><option value="표준형" '.$cuselectstate2.'>표준형</option><option value="쇠퇴형" '.$cuselectstate3.'>쇠퇴형</option></select></div></td></tr> 

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=nboosters"target="_blank"> ▷</a>부스터 활동 횟수</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic9" name="basic9" class="form-control" ><option value="1" '.$boostselectstate1.'>1</option><option value="2" '.$boostselectstate2.'>2</option><option value="3" '.$boostselectstate3.'>3</option><option value="4" '.$boostselectstate4.'>4</option><option value="5" '.$boostselectstate5.'>5</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=inspecttime"target="_blank"> ▷</a>활동점검 주기</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic14" name="basic14" class="form-control" ><option value="10" '.$inselectstate1.'>10</option><option value="20" '.$inselectstate2.'>20</option><option value="30" '.$inselectstate3.'>30</option><option value="60" '.$inselectstate4.'>60</option><option value="90" '.$inselectstate5.'>90</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=termhours"target="_blank"> ▷</a>공부시간 (학기중)</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic18" name="basic18" class="form-control" ><option value="10" '.$termhours10.'>10</option><option value="11" '.$termhours11.'>11</option><option value="12" '.$termhours12.'>12</option><option value="13" '.$termhours13.'>13</option><option value="14" '.$termhours14.'>14</option><option value="15" '.$termhours15.'>15</option><option value="16" '.$termhours16.'>16</option><option value="17" '.$termhours17.'>17</option><option value="18" '.$termhours18.'>18</option><option value="19" '.$termhours19.'>19</option><option value="20" '.$termhours20.'>20</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=vachours"target="_blank"> ▷</a>공부시간 (방학중)</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic19" name="basic19" class="form-control" ><option value="10" '.$vachours10.'>10</option><option value="12" '.$vachours12.'>12</option><option value="14" '.$vachours14.'>14</option><option value="16" '.$vachours16.'>16</option><option value="18" '.$vachours18.'>18</option><option value="20" '.$vachours20.'>20</option><option value="22" '.$vachours22.'>22</option><option value="24" '.$vachours24.'>24</option><option value="26" '.$vachours26.'>26</option><option value="28" '.$vachours28.'>28</option><option value="30" '.$vachours30.'>30</option></select></div></td></tr>


</table>
</td> 
<td></td><td valign=top>
<table>
<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=univ"target="_blank"> ▷</a>대학목표</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic16" name="basic16" class="form-control" ><option value="1" '.$univcat1.'>지거국</option><option value="2" '.$univcat2.'>인서울</option><option value="3" '.$univcat3.'>중경외시</option><option value="4" '.$univcat4.'>서성한</option><option value="5" '.$univcat5.'>SKY</option><option value="6" '.$univcat6.'>의치한</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=curtype"target="_blank"> ▷</a>커리큘럼 유형</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic17" name="basic17" class="form-control" ><option value="1" '.$selectcur1.'>표준형</option><option value="2" '.$selectcur2.'>결합형</option><option value="3" '.$selectcur3.'>심화형</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=Preseta"target="_blank"> ▷</a>개념미션</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic10" name="basic10" class="form-control" ><option value="Preset1"  '.$PAselectstate1.'>Preset1</option><option value="Preset2"  '.$PAselectstate2.'>Preset2</option><option value="Preset3"  '.$PAselectstate3.'>Preset3</option><option value="Preset4"  '.$PAselectstate4.'>Preset4</option><option value="Preset5"  '.$PAselectstate5.'>Preset5</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=Presetb"target="_blank"> ▷</a>심화미션</td><td><div style="font-size:20;" class="width:250;select2-input"><select  style="width:250;font-size:16px;" id="basic11" name="basic11" class="form-control" ><option value="Preset1"  '.$PBselectstate1.'>Preset1</option><option value="Preset2"  '.$PBselectstate2.'>Preset2</option><option value="Preset3"  '.$PBselectstate3.'>Preset3</option><option value="Preset4"  '.$PBselectstate4.'>Preset4</option><option value="Preset5"  '.$PBselectstate5.'>Preset5</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=Presetc"target="_blank"> ▷</a>내신미션</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic12" name="basic12" class="form-control" ><option value="Preset1"  '.$PCselectstate1.'>Preset1</option><option value="Preset2"  '.$PCselectstate2.'>Preset2</option><option value="Preset3"  '.$PCselectstate3.'>Preset3</option><option value="Preset4"  '.$PCselectstate4.'>Preset4</option><option value="Preset5"  '.$PCselectstate5.'>Preset5</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=Presetd"target="_blank">▷</a>수능미션</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic13" name="basic13" class="form-control" ><option value="Preset1"  '.$PDselectstate1.'>Preset1</option><option value="Preset2"  '.$PDselectstate2.'>Preset2</option><option value="Preset3"  '.$PDselectstate3.'>Preset3</option><option value="Preset4"  '.$PDselectstate4.'>Preset4</option><option value="Preset5"  '.$PDselectstate5.'>Preset5</option></select></div></td></tr>








<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=addcourse"target="_blank">▷</a> 학습진도</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic103" name="basic103" class="form-control" ><option value="초등" '.$progressselectstate1.'>초등</option><option value="중등" '.$progressselectstate2.'>중등</option><option value="고등" '.$progressselectstate3.'>고등</option><option value="수능" '.$progressselectstate4.'>수능</option></select></div></td></tr>
 

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=addcourse"target="_blank">▷</a> 기본강좌</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic1" name="basic1" class="form-control" ><option value="개념" '.$adselectstate1.'>개념</option><option value="심화" '.$adselectstate2.'>심화</option><option value="내신" '.$adselectstate3.'>내신</option><option value="수능" '.$adselectstate4.'>수능</option><option value="주제별특강" '.$adselectstate5.'>주제별특강</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=addcourse"target="_blank">▷</a> 보충강좌</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic101" name="basic101" class="form-control" ><option value="개념" '.$additionalselectstate1.'>개념</option><option value="심화" '.$additionalselectstate2.'>심화</option><option value="내신" '.$additionalselectstate3.'>내신</option><option value="수능" '.$additionalselectstate4.'>수능</option><option value="주제별특강" '.$additionalselectstate5.'>주제별특강</option></select></div></td></tr>

<tr style="font-size:16px;"><td style="color:#1a75ff;font-weight:bold;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=addcourse"target="_blank">▷</a> 학습수준</td><td><div class="select2-input"><select  style="width:250;font-size:16px;" id="basic102" name="basic102" class="form-control" ><option value="상" '.$mlevelselectstate1.'>상</option><option value="중" '.$mlevelselectstate2.'>중</option><option value="하" '.$mlevelselectstate3.'>하</option></select></div></td></tr>








</table></td>
</tr>
 <tr><td></td><td width=10%></td><td></td><td width=10%></td>
<td><button style="font-size:20;" type="image" onclick="saveproperties('.$studentid.',$(\'#squareInput1\').val(),$(\'#datepicker\').val(),$(\'#email1\').val(),$(\'#email2\').val(),$(\'#squareInput2\').val(),$(\'#squareInput3\').val(),$(\'#squareInput4\').val(),$(\'#squareInput5\').val(),$(\'#squareInput6\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#basic4\').val(),$(\'#basic5\').val(),$(\'#basic6\').val(),$(\'#basic7\').val(),$(\'#basic8\').val(),$(\'#basic9\').val(),$(\'#basic10\').val(),$(\'#basic11\').val(),$(\'#basic12\').val(),$(\'#basic13\').val(),$(\'#basic14\').val(),$(\'#basic15\').val(),$(\'#basic16\').val(),$(\'#basic17\').val(),$(\'#basic18\').val(),$(\'#basic19\').val(),$(\'#basic102\').val(),$(\'#basic101\').val(),$(\'#basic103\').val()) ">저장하기</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$studentid.'"target="_blank">개인정보 수정</a> &nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullplan.php?id='.$studentid.'">시간설계</a></td></tr>
</table>';                                    
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
			format: "YYYY",
		});
function saveproperties(Userid,Institute,Birthdate,Email1,Email2,Phone1,Phone2,Phone3,Brotherhood,Academy,Addcourse,Location,Fluency,Goalstability,Efficiency,Lmode,Evaluate,Curriculum,Nboosters,Preseta,Presetb,Presetc,Presetd,Inspecttime,Userrole,Univ,Pathtype,Termhours,Vachours,Mathlevel,Additional,Progress)
	{
	 
	swal({title: \'저장되었습니다.\',});	
	
 	$.ajax({
	url:"database.php",
	type: "POST",
	dataType:"json",
	data : {
	"eventid":\'40\',
	"userid":Userid,       
	"institute":Institute,
	"birthdate":Birthdate,
	"email1":Email1,
	"email2":Email2,
	"phone1":Phone1,
	"phone2":Phone2,
	"phone3":Phone3,
	"brotherhood":Brotherhood,
	"academy":Academy,
	"location":Location,
	"addcourse":Addcourse,
	"fluency":Fluency,
	"goalstability":Goalstability,
	"efficiency":Efficiency,
	"lmode":Lmode,
	"evaluate":Evaluate,
	"curriculum":Curriculum,
	"nboosters":Nboosters,
	"inspecttime":Inspecttime,
	"userrole":Userrole,
	"termhours":Termhours,
	"vachours":Vachours,

	"univ":Univ,
	"pathtype":Pathtype,
	"preseta":Preseta, 
	"presetb":Presetb, 
	"presetc":Presetc, 
	"presetd":Presetd, 
	"mathlevel":Mathlevel,
	"additional":Additional,
	"progress":Progress,
	},
	success:function(data){
		
			}
	 })
	
   	setTimeout(function() {location.reload(); },100);
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