<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid = $_GET["studentid"];
$type = $_GET["type"];


if($type==NULL || $type==='present'){$type='present';$typeimg='https://mathking.kr/Contents/IMAGES/present.png';$mode='initial';$placeholder='최근 기준 MBTI 결과 입력하기';}
elseif($type==='initial'){$typeimg='https://mathking.kr/Contents/IMAGES/baby.png';$mode='present';$placeholder='과거 기준 MBTI 결과 입력하기';}

$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$timecreated=time();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role!=='student')
	{
	$analysis11='<a href="https://docs.google.com/document/d/1_aw4xm5e_a3fDrTHvwkhvY_yuHzADRq1uFu6KqnbRn0/edit"target="_blank">#</a>'; //istj
	$analysis12='<a href="https://docs.google.com/document/d/1ZQpoTs8ACu5WKzKmE2mbyaH3cxvPKwhZwZMKZYGkq_s/edit"target="_blank">#</a>'; //isfj
	$analysis13='<a href="https://docs.google.com/document/d/1dx_LBLk79L3spAHBr2GjqURw-hDWjUkIt3eGTc5NrKU/edit"target="_blank">#</a>'; //infj
	$analysis14='<a href="https://docs.google.com/document/d/1opqBgjH5wfj6d-tRcs7tsSp2L2n0ObkbIfzXMAsW9Zk/edit"target="_blank">#</a>'; //intj

	$analysis21='<a href="https://docs.google.com/document/d/14D7HssvmHtFa7FQq06pnucNsgGt7byRH1ksp2NyntAQ/edit"target="_blank">#</a>'; //istp
	$analysis22='<a href="https://docs.google.com/document/d/1gqFZjKQIuLnl_B5NmSz4TWX26Q7LGPzx06gI2ZChPVM/edit"target="_blank">#</a>'; //isfp
	$analysis23='<a href="https://docs.google.com/document/d/1wYQXPMR6CBHbbAKdhMvDZz5EiuhTRxj0nivGBoHudyQ/edit"target="_blank">#</a>'; //infp
	$analysis24='<a href="https://docs.google.com/document/d/11ZMRXNsP-PJFXOja7H4xxNl-jzp9X2yNFnnwu9Jdnjk/edit"target="_blank">#</a>'; //intp

	$analysis31='<a href="https://docs.google.com/document/d/1NBwCCl0m17ajwa-gisvv9qCEOMP-LFopgz0QA3pAEUE/edit"target="_blank">#</a>'; //estp
	$analysis32='<a href="https://docs.google.com/document/d/1hs6gFCJHc2p1AImgTn-0YHgN-ty1eres9UuFwPY9Dow/edit"target="_blank">#</a>'; //esfp
	$analysis33='<a href="https://docs.google.com/document/d/194fjtKS49TIdwh7oTupPANYUmz-_QYS82FDSCnixVTU/edit"target="_blank">#</a>'; //enfp
	$analysis34='<a href="https://docs.google.com/document/d/1k2e8c5iTMx594ARnHm2HfbbaMyLO__YMug_oMvp6JRI/edit"target="_blank">#</a>'; //entp

	$analysis41='<a href="https://docs.google.com/document/d/1XddzvLxOpcWF7PEAAunoXPb_eNIOCHmA0Vtv7M6fksU/edit"target="_blank">#</a>'; //estj
	$analysis42='<a href="https://docs.google.com/document/d/17N3lVd6Evjc43YlPCOJXDqfOPrazD0Rz8FoKaYnFq9I/edit"target="_blank">#</a>'; //esfj
	$analysis43='<a href="https://docs.google.com/document/d/1QttmyZ9M482a8jHWQMl-KTlLPb5EBQ1kHC4KjDAYxf4/edit"target="_blank">#</a>'; //enfj
	$analysis44='<a href="https://docs.google.com/document/d/1w032kGrEhtBqG3X59i4nOrebxh-vuVN8SRnxiWO8Kr8/edit"target="_blank">#</a>'; //entj
}

$mbtilog1= $DB->get_records_sql("SELECT * FROM mdl_abessi_mbtilog WHERE userid='$studentid' AND type='present' ORDER BY id ASC LIMIT 100");
$result1= json_decode(json_encode($mbtilog1), True);

unset($value1);  
foreach($result1 as $value1)
	{
	$tcreated1=date("m월d일", $value1['timecreated']);   
	$mbti1.='<td><img src="https://mathking.kr/Contents/IMAGES/present.png" width=20> <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-'.$value1['mbti'].'"target="_blank"><b>'.strtoupper($value1['mbti']).'</b></a>('.$tcreated1.') &nbsp;&nbsp;&nbsp;&nbsp; </td>';	
	}
 
$mbtilog2= $DB->get_records_sql("SELECT * FROM mdl_abessi_mbtilog WHERE userid='$studentid' AND type='initial' ORDER BY id ASC LIMIT 100");
$result2= json_decode(json_encode($mbtilog2), True);

unset($value2);  
foreach($result2 as $value2)
	{
	$tcreated2=date("m월d일", $value2['timecreated']);   
	$mbti2.='<td><img src="https://mathking.kr/Contents/IMAGES/baby.png" width=20> <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-'.$value2['mbti'].'"target="_blank"><b>'.strtoupper($value2['mbti']).'</b></a>('.$tcreated2.')  &nbsp;&nbsp;&nbsp;&nbsp;  </td>';	
	}
echo '<br><br>
<table align=center width=80%><tr><td width=15%></td><td style="font-size:20px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800">'.$studentname.'</a></td><td style="font-size:20px;" align=center>MBTI 결과에 따라 맞춤형 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'">메타인지 피드백</a>을 제공해 드립니다.</td>  <td style="font-size:20px;"> &nbsp;&nbsp;&nbsp;<a href="https://www.16personalities.com/ko/%EB%AC%B4%EB%A3%8C-%EC%84%B1%EA%B2%A9-%EC%9C%A0%ED%98%95-%EA%B2%80%EC%82%AC"target="_blank">MBTI 검사하기</a>
 &nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/mbti_types.php?studentid='.$studentid.'&type='.$mode.'"><img style="margin-bottom:7px;" src='.$typeimg.' width=30></a></td></tr></table><hr>

<table align=center><tr>
<td><img src=https://mathking.kr/Contents/IMAGES/mbti-diagram.jpg width=590></td><td width=1%></td>
<td> 
<table align=center>
<tr>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-istj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/istj.png width=220></a></td><td>'.$analysis11.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-isfj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/isfj.png width=220></a></td><td>'.$analysis12.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-infj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/infj.png width=220></a></td><td>'.$analysis13.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-intj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/intj.png width=220></a></td><td>'.$analysis14.'</td>
</tr> 
 
<tr>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-istp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/istp.png width=220></a></td><td>'.$analysis21.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-isfp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/isfp.png width=220></a></td><td>'.$analysis22.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-infp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/infp.png width=220></a></td><td>'.$analysis23.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-intp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/intp.png width=220></a></td><td>'.$analysis24.'</td>
</tr>

<tr>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-estp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/estp.png width=220></a></td><td>'.$analysis31.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-esfp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/esfp.png width=220></a></td><td>'.$analysis32.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-enfp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/enfp.png width=220></a></td><td>'.$analysis33.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-entp"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/entp.png width=220></a></td><td>'.$analysis34.'</td>
</tr>

<tr>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-estj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/estj.png width=220></a></td><td>'.$analysis41.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-esfj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/esfj.png width=220></a></td><td>'.$analysis42.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-enfj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/enfj.png width=220></a></td><td>'.$analysis43.'</td>
<td><a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-entj"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/entj.png width=220></a></td><td>'.$analysis44.'</td>
</tr>
</table></td></tr></table><hr>';
 
echo '<table align=center width=60%><tr><td><input style="font-size:20px;width:100%;" type="text" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'"></td><td><button style="font-size:20px;"  onClick="Submitmbti(\''.$studentid.'\',\''.$type.'\',$(\'#squareInput\').val())">제출</button></td><td width=3%></td><td><input style="font-size:20px;width:100%;" type="text" id="squareInput" name="squareInput"  placeholder="MBTI 문자 전체 또는 일부를 입력해 주세요"></td><td><button style="font-size:20px;"  onClick="Submitmbti(\''.$studentid.'\',\''.$type.'\',$(\'#squareInput\').val())">검색하기</button></td></tr></table><hr><table align=center><tr>'.$mbti1.'</tr></table><hr><table align=center><tr>'.$mbti2.'</tr></table><hr>';
echo '

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<link rel="stylesheet" href="../assets/css/ready.min.css">
<script src="https://code.jquery.com/pep/0.4.3/pep.js"></script> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>


<script>

function Submitmbti(Userid,Type,Mbti)
	{
	swal("MBTI 프로필이 업데이트 되었습니다 !", {buttons: false,timer: 2000});  
	$.ajax({
		url:"checkflow.php",
		type: "POST",
		dataType:"json",
		data : {
			"eventid":\'3\',
			"userid":Userid,
			"mbti":Mbti,
			"type":Type,
				},
		        success: function (data){  
		        }
			});
	setTimeout(function() {location.reload(); },2000);	
	}
</script>';
?>
 
