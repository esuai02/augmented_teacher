<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");
$thisyear = date("Y",time());
$timecreated=time();
$newdream = $_GET["newdream"];
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");
$np=1;
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by id DESC LIMIT 3");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$missionid=$value['id'];
	$plantype=$value['plantype'];
	$text0=$value['memo'];		
  if($np==1)$text.='<span style="color:black">'.iconv_substr($text0, 0, 70, "utf-8").' </span><br>'; 	
  else $text.='<span style="color:lightgrey">'.iconv_substr($text0, 0, 70, "utf-8").' </span><br>'; 		
	$deadline= $value['deadline']; 
	$dday=round(($deadline-time())/86400)+1;   
	$dateString = date("Y-m-d",$deadline);
	$checkbox='';
	$tmissioncreated=$value['timecreated'];
  $np++;
	/*
	if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
	elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
	elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
	else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';
		*/
	if($plantype==='분기목표')$plantype='<b style="color:purple;">분기목표</b>';
	elseif($plantype==='방향설정')$plantype='<b style="color:red;">방향설정</b>'; 
	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck(150,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck2(200,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	} 


if($role!=='student')
	{
	$create1='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=3"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create2='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=4"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create3='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=5"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create4='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=6"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create5='<a href="http://twinery.org/2/#!/stories"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	}

include("../books/gpttalk.php");
$gpteventname='Golden Plan';
$contextid='goldenplan'.$studentid;
include("../books/gptrecord.php");
if($gptquestion==NULL)$scriptontopic='<table width=100%><tr><td>My golden story goes like this ...</td><td width=2% align=right><span onclick="GPTTalk(\''.$gpteventname.'\',\'질문\',\''.$gptquestion.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt2.png width=18></span></td></tr></table>';
else $scriptontopic='<table width=100%><tr><td>'.$gptquestion.'</td><td><span onclick="GPTTalk(\''.$gpteventname.'\',\'질문\',\''.$gptquestion.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt2.png width=18></span></td></tr><tr><td> '.$gpttalk.'</td><td width=2%><span onclick="GPTTalk(\''.$gpteventname.'\',\'답변\',\''.$gpttalk.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png width=18></span></td></tr></table>';
 
$plantypes='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic" class="form-control"  ><h3><option value="분기목표">분기목표</option></h3></select> </div>';

$Aweekago=time()-604800;
$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
		LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid WHERE mdl_question.name LIKE '%MX%' AND mdl_question_attempt_steps.userid='$studentid' AND  state NOT LIKE 'todo' AND  state NOT LIKE 'complete' AND  mdl_question_attempt_steps.timecreated > '$Aweekago'   ");
$nattempts=count($questionattempts);
$DB->execute("UPDATE {abessi_indicators} SET nattempts='$nattempts' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  


$imgWgrade0='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1623817278001.png" height=15>';
$imgWgrade1='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15>';
$imgWgrade2='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15>';
$imgWgrade3='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15>';
$imgWgrade4='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15>';
$imgWgrade5='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15>';

$nnn=1;
$amonthsago=time()-604800*4;
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' AND timecreated>'$amonthsago' ORDER BY id ASC ");
 
$result2 = json_decode(json_encode($goals), True);
unset($value);
 
foreach($result2 as $value)
	{
	$date_pre=$date;
	$att=gmdate("m월 d일 ", $value['timecreated']+32400);
	$date=gmdate("d", $value['timecreated']+32400);	 
	$goaltype='<b style="color:#bf04e0;">주간목표</b>';
	$notetype='weekly';	 
	$daterecord=date('Y_m_d', $value['timecreated']);  	 
	$tend=$value['timecreated'];
	 
 	$imgthisweek='imgWgrade'.$value['planscore'];
	$imgresult=$$imgthisweek;
	$goalhistory.= '<tr><td width=10%>&nbsp;&nbsp;</td><td>📅</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
	<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,80).'</a></td><td>'.$imgresult.'</td> </tr>';
	}
$goalhistory.='<tr><td width=10%>&nbsp;&nbsp;</td><td>🎯</td> <td> '.$plantype.'</td><td>&nbsp;&nbsp;&nbsp; </td> <td style="font-size:12pt" >'.$dateString.' (D-'.$dday.'일)</td><td style="font-size:14pt;" ><b>'.$text.'</b></td> <td width=5%></td> <td width=5%> '.$checkhide.'</td>  </tr>';
//<a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/bigplan.html"target="_blank"><img style="padding-bottom:0px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641245056.png width=30></a>
$weekplanhistory='<table width=100%><tr><td width=70%>   <table width=100% >'.$goalhistory.'</table>    </td><td></td><td width=30%>   </td></tr></table>';

$stateColor1='primary';
$stateColor2='primary'; 
$stateColor3='primary'; 
if($username->state==1)$stateColor1='Default'; 
if($username->state==2)$stateColor2='Default'; 
if($username->state==0)$stateColor3='Default'; 
if($role!=='student')$teacherScore='<button class="btn btn-success"  type="button"  style = "font-size:16;background-color:lightblue;color:black;border:0;height:40px;outline:0;"  onclick="WeeklyGrade()">분기목표 원활도 평가</button>';
 
if($USER->id==2)$exceptionButton=' <button   type="button"   id="alert_exception"  class="btn btn-'.$stateColor3.'" style = "font-size:16;background-color:lightblue;color:white;border:0;height:40px;outline:0;" >예외설정</button>';
$analysistext='<table width=1%><tr><td>'.$username->lesson.'</td></tr></table>';
 
$randomDreamList = [
    "인공지능 개발자",
    "환경 보호 전문가",
    "가상현실 게임 디자이너",
    "우주 탐사자",
    "유전공학 연구원",
    "스마트팜 기술자",
    "해양 생물학자",
    "신재생 에너지 엔지니어",
    "드론 파일럿",
    "사이버 보안 전문가",
    "데이터 과학자",
    "로봇공학 기술자",
    "콘텐츠 크리에이터",
    "의료 기술 혁신가",
    "지속 가능한 패션 디자이너",
    "가상 교육자",
    "우주 식민지 설계자",
    "인공장기 개발자",
    "디지털 마케터",
    "바이오인포매틱스 전문가",
    "청정 에너지 컨설턴트",
    "증강 현실 경험 디자이너",
    "암호화폐 분석가",
    "미래학 연구원",
    "나노기술 엔지니어",
    "스마트 도시 계획가",
    "인간-기계 인터페이스 디자이너",
    "디지털 윤리학자",
    "양자 컴퓨터 개발자",
    "자율 주행 차량 엔지니어",
    "생명공학 연구원",
    "모바일 앱 개발자",
    "인공지능 법률 고문",
    "스페이스 호텔 매니저",
    "디지털 복원 전문가",
    "신경과학자",
    "미생물 에너지 생산자",
    "스마트 웨어러블 디자이너",
    "3D 프린팅 전문가",
    "무인 항공 교통 관리자",
    "가상 현실 치료사",
    "블록체인 개발자",
    "음성 인식 기술 개발자",
    "클라우드 컴퓨팅 전문가",
    "인터넷 오브 싱스(IoT) 개발자",
    "게임 이론 분석가",
    "스마트 홈 시스템 디자이너",
    "텔레프레즌스 로봇 조종사",
    "웨어러블 헬스 기기 개발자",
    "식품 과학자",
    "디지털 아트 큐레이터",
    "생태계 복원 전문가",
    "미래 도시 건축가",
    "인공지능 음악 작곡가",
    "크립토 아트 작가",
    "전염병 예방 전문가",
    "심우주 통신 엔지니어",
    "지속 가능한 관광 개발자",
    "양자 암호화 전문가",
    "빅 데이터 분석가",
    "첨단 농업 기술자",
    "가상 현실 아키텍트",
    "뇌-컴퓨터 인터페이스 연구원",
    "홀로그램 콘텐츠 제작자",
    "인간 행동 연구원",
    "테라포밍 엔지니어",
    "초지능 시스템 디자이너",
    "멸종 위기 동물 보호 전문가",
    "스포츠 과학자",
    "스마트 교통 시스템 개발자",
    "도시 농업 전문가",
    "신경 조직 공학자",
    "모바일 헬스케어 서비스 개발자",
    "핵융합 에너지 연구원",
    "글로벌 웜링 해결 전략가",
    "인터스텔라 메시지 디자이너",
    "디지털 명상 지도자",
    "우주 광물학자",
    "스마트 그리드 기술자",
    "환경 데이터 과학자",
    "미래 학교 교육가",
    "디지털 디톡스 전문가",
    "가상 동물원 설계자",
    "스마트 패션 기술자",
    "항노화 연구원",
    "비디오 게임 스토리텔러",
    "지능형 건축 재료 개발자",
    "마이크로바이옴 연구원",
    "어반 에어 모빌리티 디자이너",
    "소셜 미디어 심리학자",
    "디지털 노마드 컨설턴트",
    "인공지능 윤리위원",
    "소리 치유사",
    "우주 날씨 예보자",
    "생체 모방 기술 개발자",
    "디지털 인문학자",
    "챗봇 스크립트 작가",
    "스마트 재난 대응 시스템 개발자",
    "가상 박물관 디자이너",
    "우주 법률 전문가",
    "스마트 재활 기기 개발자",
    "언더워터 호텔 디자이너",
    "증강 현실 교육 콘텐츠 제작자",
    "마이크로그래비티 요리사",
    "우주 쓰레기 관리 전문가",
    "바이오센서 개발자",
    "디지털 정신 건강 치료사",
    "가상 현실 스포츠 코치",
    "자율주행 자동차 디자이너",
    "심해 탐사 장비 엔지니어",
    "지능형 비즈니스 분석가",
    "클라우드 베이스드 교육 플랫폼 개발자",
    "소셜 임팩트 투자자",
    "3D 생체 인쇄 전문가",
    "스마트 패브릭 디자이너",
    "어반 푸드 시스템 혁신가",
    "디지털 저작권 관리자",
    "글로벌 로지스틱스 최적화 전문가",
    "공중 부양 교통 시스템 개발자",
    "식물 기반 식품 과학자",
    "지속 가능한 도시 농업 설계자",
    "인간 확장 기술 연구원",
    "사이버범죄 수사관",
    "스마트 재난 경보 시스템 개발자",
    "가상 현실 여행 에이전트",
    "인공지능 조교",
    "디지털 포렌식 전문가",
    "스마트 에너지 저장 솔루션 개발자",
    "초현실적 예술가",
    "바이러스 억제 연구원",
    "가상 인간 상호작용 디자이너",
    "나노메디슨 연구원",
    "생태계 기능 디자이너",
    "양자 통신 전문가",
    "디지털 아카이브 전문가",
    "인터랙티브 도서관 컨설턴트",
    "친환경 건축 자재 개발자",
    "모바일 결제 시스템 혁신가",
    "인공지능 기반 교육 컨텐츠 개발자",
    "미래 의학 연구원",
    "심리적 건강 모바일 앱 개발자",
    "공기 정화 기술 개발자",
    "디지털 농업 컨설턴트",
    "스마트 헬멧 개발자",
    "공간 데이터 분석가",
    "의료용 로봇 기술자",
    "가상 현실 치료 기기 개발자",
    "자연어 처리 연구원",
    "인공 지능 스타일리스트",
    "우주 관광 가이드",
    "퍼스널 데이터 프라이버시 어드바이저",
    "스마트 컨트랙트 개발자",
    "가상 아이돌 제작자",
    "지속 가능한 수자원 관리 전문가",
    "인공지능 기반 퍼스널 쇼퍼",
    "로우코드 애플리케이션 개발자",
    "지능형 교통 시스템 분석가",
    "미세먼지 저감 기술 연구원",
    "디지털 콘텐츠 권리 관리 전문가",
    "가상 현실 영화 제작자",
    "인공지능 화상 회의 퍼실리테이터",
    "신경망 칩 설계자",
    "언어학습 앱 개발자",
    "에코 컨셔스 패션 브랜드 창립자",
    "디지털 복원 기술자",
    "소셜 미디어 인플루언서 전략가",
    "양자 컴퓨팅 애플리케이션 개발자",
    "스마트 물류 시스템 설계자",
    "공중보건 위기 대응 전문가",
    "에코테크 스타트업 창업가",
    "디지털 이벤트 플래너",
    "가상 스포츠 리그 관리자",
    "인공지능 법률 분석가",
    "심해 연구 및 탐사 전문가",
    "우주 농업 연구원",
    "공간정보 시스템 개발자",
    "첨단 의료 이미징 기술자",
    "자동화 테스트 엔지니어",
    "스마트 시티 보안 전문가",
    "가상 교실 교육 기획자",
    "디지털 장례 서비스 제공자",
    "우주 환경 엔지니어",
    "스타트업 인큐베이터 멘토",
    "가상 현실 기반 심리 치료사",
    "에너지 효율성 컨설턴트",
    "스마트 센서 네트워크 개발자",
    "게이미피케이션 전략가",
    "빛 오염 해결 전문가",
    "디지털 노마드 커뮤니티 매니저",
    "지속 가능한 에너지 솔루션 디자이너",
    "인공지능 기반 식물 성장 모니터",
    "무인 배송 시스템 운영자",
    "디지털 감정 표현 연구원",
    "핀테크 솔루션 개발자",
    "스마트 건축물 에너지 관리자",
    "가상 현실 컨텐츠 큐레이터",
    "생체모방 로봇 디자이너",
    "디지털 건강 모니터링 시스템 개발자",
    "우주 관측 데이터 분석가",
    "바이오디지털 콘텐츠 크리에이터",
    "스마트 의복 제작자",
    "가상 현실 테마파크 디자이너",
    "디지털 웰빙 코치",
    "지속 가능한 에코빌리지 개발자",
    "식용 곤충 농장 운영자",
    "해저 도시 건축가",
    "인공지능 재난 대응 조정자",
    "스페이스 데브리 클리너",
    "스마트 도로 시스템 설계자",
    "바이오필릭 디자인 컨설턴트",
    "디지털 유산 컨설턴트",
    "사이버펑크 소설가",
    "미래식 식단 개발자",
    "가상 패션 쇼 오거나이저",
    "스마트 공기질 모니터",
    "우주 식량 생산자",
    "생체 적응형 게임 개발자",
    "디지털 통화 디자이너",
    "마이크로리빙 공간 디자이너",
    "가상 현실 교육 컨텐츠 개발자",
    "빛 기반 통신 기술자",
    "디지털 유물 보존 전문가",
    "인공지능 기반 작곡가",
    "바이오메트릭 데이터 분석가",
    "3D 프린트 의류 디자이너",
    "윤리적 AI 개발자",
    "스마트 약물 전달 시스템 디자이너",
    "재생 가능 에너지 벤처 캐피털리스트",
    "초연결 사회 분석가",
    "스팀(STEM) 교육 콘텐츠 크리에이터",
    "가상 현실 심리 치료 연구원",
    "환경 데이터 비주얼라이제이션 전문가",
    "나노봇 연구 개발자",
    "스마트 교통 체계 해커",
    "지속 가능한 관광 기획자",
    "어린이를 위한 프로그래밍 교육가",
    "증강 현실 쇼핑 어드바이저",
    "인터랙티브 디지털 아트워크 크리에이터",
    "모바일 건강 진단 개발자",
    "디지털 콘텐츠 저작권 관리자",
    "로봇 윤리 컨설턴트", 
    "스마트 시티 데이터 분석가",
    "퍼소널 브랜딩 전문가",
    "가상 현실 피트니스 트레이너",
    "홀로그래픽 데이터 시각화 전문가",
    "사이버 안전 교육가",
    "디지털 음악 배포자",
    "클라우드 기반 팀워크 플랫폼 개발자",
    "인공지능 패션 컨설턴트",
    "미래 도시 생활 컨설턴트",
    "디지털 인권 변호사",
    "가상 실감 콘텐츠 프로듀서",
    "친환경 건축 기술자",
    "인공지능 기반 도시 계획가",
    "식물 기반 식품 혁신가",
    "스마트 장난감 개발자",
    "지속 가능한 생활 스타일 코치",
    "소셜 미디어 데이터 분석가",
    "초소형 위성 개발자",
    "디지털 북 큐레이터",
    "가상 현실 미술관 큐레이터",
    "스마트 환경 모니터링 시스템 개발자",
    "바이오피드백 테라피스트",
    "우주 여행 가이드",
    "심해 탐사 기술 개발자",
    "디지털 윤리 컨설턴트",
    "가상 멘토링 서비스 개발자",
    "스마트 시티 생활 실험가",
    "에너지 하베스팅 기술 연구원",
    "사이버펑크 게임 디자이너",
    "가상 현실 치료 연구 개발자",
    "인공지능 기반 개인 건강 조언가",
    "지속 가능한 패션 블로거",
    "디지털 보안 컨설턴트",
    "3D 바이오 프린팅 연구원",
    "자율주행 도시 버스 시스템 디자이너",
    "가상 현실 역사 교육가",
    "인터넷 사물(IoT) 장난감 디자이너",
    "스마트 농업 컨설턴트",
    "로봇 공학 교육 전문가",
    "디지털 인문학 연구자",
    "가상 현실 스포츠 분석가",
    "스마트 워터 관리 시스템 엔지니어",
    "인공지능 기반 아트 테라피스트",
    "지구 외 생명체 연구원",
    "디지털 정체성 보호 전문가",
    "자연 언어 처리 기술 개발자",
    "가상 현실 여행 기획자",
    "바이오리듬 분석가",
    "스마트 교육 플랫폼 개발자",
    "디지털 푸드 디자이너",
    "가상 현실 콘서트 기획자",
    "실시간 데이터 분석가",
    "스마트 건강 진단 키트 개발자",
    "인공지능 기반 재난 경보 시스템 개발자",
    "디지털 커뮤니티 매니저",
    "친환경 도시 디자인 전문가",
    "가상 현실 교통 시스템 설계자",
    "디지털 자산 관리자",
    "스마트 홈 인테리어 디자이너"
];
$randomDreamUrlList = [
  "https://gamma.app/docs/-5dvdwrou2385tda",
  "https://gamma.app/docs/-57oe1106fexvovx",
  "https://gamma.app/docs/-w060d7y8nzrq6z1",
  "https://gamma.app/docs/-xl03qnlzbhw0l3d",
  "https://gamma.app/docs/Untitled-ekp8hywee87lsw8",
  "https://gamma.app/docs/-ggn6grxhpvp0tdj",
  "https://gamma.app/docs/-xieocbvr1u6hyd0",
  "https://gamma.app/docs/-lp6kn8pqg1aqmec",
  "https://gamma.app/docs/-fsuhnwucw8546bj",
  "https://gamma.app/docs/-t55yu127yjsi9fo",
  "https://gamma.app/docs/-8sln8zzhe487myk",
  "https://gamma.app/docs/-62mq1zcgmekj0xw",
  "https://gamma.app/docs/-80707aa8tnf1d8u",
  "https://gamma.app/docs/-kep6ua7le4tcsup",
  "https://gamma.app/docs/-xhdx8mkbak325bj",
  "https://gamma.app/docs/-x9nfq80il9glyiz",
  "https://gamma.app/docs/-020t0h8i64qt3ji",
  "https://gamma.app/docs/-m3j16vvgfw4c2c3",
  "https://gamma.app/docs/-o6e5u148e9n3hy0",
  "https://gamma.app/docs/-vf3my60eukzau3p",
  "https://gamma.app/docs/-s7945kxk45fptap",
  "https://gamma.app/docs/-eatbhq1xto25lmc",
  "https://gamma.app/docs/-ar1ok42v4guq3gr",
  "https://gamma.app/docs/-vmhpuzstpj6z9iv",
  "https://gamma.app/docs/-0vp4rijjzmxr5lb",
  "https://gamma.app/docs/-xp3lp0v1pldkxke",
  "https://gamma.app/docs/-irf6r12mpq21jxw",
  "https://gamma.app/docs/-7lcr5rezdf6k9br",
  "https://gamma.app/docs/-8u0i6dikdcq7r8q",
  "https://gamma.app/docs/-8gfvga11by9e2so",
  "https://gamma.app/docs/-bjb3fkradx5emgg",
  "https://gamma.app/docs/-786otp42dq41g6i",
  "https://gamma.app/docs/-s8ls52dgg1afk60",
  "https://gamma.app/docs/-l1sbevclt9fnm2g",
  "https://gamma.app/docs/-ojj0fz3q639r666",
  "https://gamma.app/docs/-2i5ufv5j73nw010",
  "https://gamma.app/docs/-y89z5ysjvw5292q",
  "https://gamma.app/docs/-yuie5rba52v21os",
  "https://gamma.app/docs/3D--ogt66n18dhu18ug",
  "https://gamma.app/docs/-85vj1hcg4t3gk5a",
  "https://gamma.app/docs/-gaycqrijcv024kp",
  "https://gamma.app/docs/-d9c1i0e27m95mgi",
  "https://gamma.app/docs/-fues7156ylaywrl",
  "https://gamma.app/docs/-lt5ywf8tlrtqy96",
  "https://gamma.app/docs/IoT--k5eard364ar18s2",
  "https://gamma.app/docs/-jpm4pqw09kavgmn",
  "https://gamma.app/docs/-aglumil3f2fhsyr",
  "https://gamma.app/docs/-kxaz0e1sdoa7v3o",
  "https://gamma.app/docs/-woyqxqy2jslwpn5",
  "https://gamma.app/docs/-76e8minqsvpg0cy",
  "https://gamma.app/docs/-0ieun0b7ocwfbne",
  "https://gamma.app/docs/-1f6svi6cdmz504q",
  "https://gamma.app/docs/-vqfbi2u1hoji2el",
  "https://gamma.app/docs/-im8xxfov6cnhihy",
  "https://gamma.app/docs/-mibiqp8hcuu7awc",
  "https://gamma.app/docs/-bmarhtojhahq1j1",
  "https://gamma.app/docs/-p2hfkaafbsm16hl",
  "https://gamma.app/docs/-y8kdy750rryglya",
  "https://gamma.app/docs/-7xlekxf04ouvn0d",
  "https://gamma.app/docs/-gy5salsqbe1aclw",
  "https://gamma.app/docs/-yn0m1sxume2atmu",
  "https://gamma.app/docs/-l9o8mxlxbxnd857",
  "https://gamma.app/docs/-pfvjxxck7buzkb3",
  "https://gamma.app/docs/-9ys3rl17dte5han",
  "https://gamma.app/docs/-va3ahhi49o4zt1y",
  "https://gamma.app/docs/-yjt635pommyqnjw",
  "https://gamma.app/docs/-smo5bdqm2kiim3i",
  "https://gamma.app/docs/-0ogmzeyq5nzsgmx",
  "https://gamma.app/docs/-23cqvaztlrgmhet",
  "https://gamma.app/docs/-c8yqn0opzp4sf1i",
  "https://gamma.app/docs/-irvfx6onndwlzsf",
  "https://gamma.app/docs/-gdu3cpvjsatdjui",
  "https://gamma.app/docs/-ji0vwzrqkbikrmn",
  "https://gamma.app/docs/-qa8mndk27l5aomo",
  "https://gamma.app/docs/-bur9fxba6i1x8d1",
  "https://gamma.app/docs/-hzvnowwvabccbwq",
  "https://gamma.app/docs/-r1o4o6i2epbkqca",
  "https://gamma.app/docs/-3sztxs20giuz113",
  "https://gamma.app/docs/-dw9yjujsfyxc6nf",
  "https://gamma.app/docs/-arxf1nb6oc3cd90",
  "https://gamma.app/docs/-0xdhc2gct6w50ex",
  "https://gamma.app/docs/-sdxz58fnmthdzne",
  "https://gamma.app/docs/-ow67c0m0cc2hz9w",
  "https://gamma.app/docs/-s9yyaztanyp8jmm",
  "https://gamma.app/docs/-m1di07ecxkzaci9",
  "https://gamma.app/docs/-9wjv8fwtckqlslo",
  "https://gamma.app/docs/-qzw0tepi62lt9mw",
  "https://gamma.app/docs/-ek53gbeha0ddxpt",
  "https://gamma.app/docs/-pd2cmjyv0g1zgdn",
  "https://gamma.app/docs/-jdk8ofesnbubh3x",
  "https://gamma.app/docs/-5z90lqmihqelfee",
  "https://gamma.app/docs/-z09uxt4wt06t0yj",
  "https://gamma.app/docs/-hpudiex8evcard0",
  "https://gamma.app/docs/-35w0x4e4sh6e1kj",
  "https://gamma.app/docs/-99kuwwh41xp7ekb",
  "https://gamma.app/docs/-n5m1rxp195f7i2g",
  "https://gamma.app/docs/-sazybl9byoh1fyg",
  "https://gamma.app/docs/-974u0unjy1rqelq",
  "https://gamma.app/docs/-jvjeu9uwc0ftmkh",
  "https://gamma.app/docs/-hpp1f3azv2r349x",
  "https://gamma.app/docs/-4aqckebehpskl59",
  "https://gamma.app/docs/-zrml04adt5wey73",
  "https://gamma.app/docs/-kl1sb32tn0sxewh",
  "https://gamma.app/docs/-zfwln3s9ugm0evt",
  "https://gamma.app/docs/-uwgll8wuguxfbmw",
  "https://gamma.app/docs/-5alqycnuvc19f6r",
  "https://gamma.app/docs/-ok9kxdjxygn3rvc",
  "https://gamma.app/docs/-gsrtc9l54d0pqnr",
  "https://gamma.app/docs/-qi1vcxkpezgvke7",
  "https://gamma.app/docs/-ov1qo1vsw4x8uui",
  "https://gamma.app/docs/-zngnj3lpxotv04u",
  "https://gamma.app/docs/-nwcwn0b225b7bca",
  "https://gamma.app/docs/-furx4dgvbi4xf51",
  "https://gamma.app/docs/3D--ean6ri9hgok5n95",
  "https://gamma.app/docs/-ehs98d8rlqy8pmg",
  "https://gamma.app/docs/-thv0e2qqiie28s9",
  "https://gamma.app/docs/-sk1ylzw8j4l9l39",
  "https://gamma.app/docs/-euslasa7gfuxrku",
  "https://gamma.app/docs/-s4wtoj4o6rqnopc",
  "https://gamma.app/docs/-780pgeei0qx25h8",
  "https://gamma.app/docs/-44wyuyxioo7277f",
  "https://gamma.app/docs/-w0e2gg0nvmecf0r",
  "https://gamma.app/docs/-n0ecytk4ir2l3q0",
  "https://gamma.app/docs/-tl4ev3qjscvno36",
  "https://gamma.app/docs/-9o6p0jm95ma09rc",
  "https://gamma.app/docs/-xr2qnk3sp6vajso",
  "https://gamma.app/docs/-v5814mccretdisl",
  "https://gamma.app/docs/-zm5sxdwve0dfy1w",
  "https://gamma.app/docs/-tej2n6x0lrcn6jh",
  "https://gamma.app/docs/-a9rti7t9r8ftoz8",
  "https://gamma.app/docs/-g1fcwyjgqurig5p",
  "https://gamma.app/docs/-cerh1y5s7ahqhb8",
  "https://gamma.app/docs/-vigfsykbazobo0f",
  "https://gamma.app/docs/-fbw4ghwx9ykckrs",
  "https://gamma.app/docs/-y1np44iewv8dc3i",
  "https://gamma.app/docs/-rbasvcsnn7ubb0n",
  "https://gamma.app/docs/-eqk70dczaysywqm",
  "https://gamma.app/docs/-zfq2iycdrlgi8ei",
  "https://gamma.app/docs/AI--70up9dn6u4w2qif",
  "https://gamma.app/docs/-av83z8lubexyvau",
  "https://gamma.app/docs/-n3vbdyrqcwfgmr4",
  "https://gamma.app/docs/-0pyfsqapoinpe5e",
  "https://gamma.app/docs/-rcret9petbw6j4u",
  "https://gamma.app/docs/-88y7o3m0tegcyaf",
  "https://gamma.app/docs/-0dz3tdtve83hj9e",
  "https://gamma.app/docs/-ar3wpbiecpqwt7t",
  "https://gamma.app/docs/-1llco26yb7574s9",
  "https://gamma.app/docs/-3jpj0s3zrbge35w",
  "https://gamma.app/docs/-fo7aqkpv03my2h1",
  "https://gamma.app/docs/-48o1dsqqg2tfzke",
  "https://gamma.app/docs/-smrrs3k0xbb4f8c",
  "https://gamma.app/docs/-40oys8w4o3iomcg",
  "https://gamma.app/docs/-u42vb63744f7tbf",
  "https://gamma.app/docs/-ayupuc51t4mqk8g",
  "https://gamma.app/docs/-bwm6i1s2w4zoqy6",
  "https://gamma.app/docs/-l8w49otlnl6op6m",
  "https://gamma.app/docs/-wq5duc8l59bc3m4",
  "https://gamma.app/docs/-no3473h2otca72v",
  "https://gamma.app/docs/-tk01witpmfknxcs",
  "https://gamma.app/docs/-zh0dqtrvekx5dgw",
  "https://gamma.app/docs/-c0o5fptdmgb6qui",
  "https://gamma.app/docs/-5wxo1qeix524i00",
  "https://gamma.app/docs/-hgz318oy0i3z5py",
  "https://gamma.app/docs/-5a7holiv5a8kots",
  "https://gamma.app/docs/-s6by5uwvo4md71m",
  "https://gamma.app/docs/-nfacws7qmo90whm",
  "https://gamma.app/docs/-8yisry5lvbwa276",
  "https://gamma.app/docs/-m0mvyrbgsp6i0id",
  "https://gamma.app/docs/-zsqtr12lzs915bx",
  "https://gamma.app/docs/-ddwaym785qvf7jz",
  "https://gamma.app/docs/-1mz9nx0x3y5u71t",
  "https://gamma.app/docs/-u8ofrmkyde0ywvg",
  "https://gamma.app/docs/-ld75wf9wiurtivi",
  "https://gamma.app/docs/-tht5mo8sebz6qoq",
  "https://gamma.app/docs/-ku4hffhzfauxnr4",
  "https://gamma.app/docs/-vrjfv2r6nhczroi",
  "https://gamma.app/docs/-6oj9cd457ci3bbp",
  "https://gamma.app/docs/-2dipwswg7b1ialm",
  "https://gamma.app/docs/-l9wmjx25ra15uve",
  "https://gamma.app/docs/-v1njqusg5df74iq",
  "https://gamma.app/docs/-1p339xhawye47sk",
  "https://gamma.app/docs/-0tn3lev1b2j53q0",
  "https://gamma.app/docs/-wnaqow3l2w184y9",
  "https://gamma.app/docs/-sirxql7pzrtjn0y",
  "https://gamma.app/docs/-mqzjq5h0g6b0s4h",
  "https://gamma.app/docs/-5o516w25he0czvm",
  "https://gamma.app/docs/-x6dk4b3omffsu6s",
  "https://gamma.app/docs/-j3442t7fphfkzes",
  "https://gamma.app/docs/-2nbehuf6v0klncz",
  "https://gamma.app/docs/-ukdtlnqska8shc6",
  "https://gamma.app/docs/-7jtolc4vsruchqd",
  "https://gamma.app/docs/-a0eahumuaiob698",
  "https://gamma.app/docs/-f73jlwiaus04tw8",
  "https://gamma.app/docs/-coably0qug18ude",
  "https://gamma.app/docs/-hci0vqp1xpelbe2",
  "https://gamma.app/docs/-otq04ruv3f5a05i",
  "https://gamma.app/docs/-z7aggnyryk7x3tu",
  "https://gamma.app/docs/-6f4p9sm2n9ztwiu",
  "https://gamma.app/docs/-w4puioqbeub828a",
  "https://gamma.app/docs/-2lcpwhk99phlw7g",
  "https://gamma.app/docs/-f9z76ssyqhlizrj",
  "https://gamma.app/docs/-0bsoaujm17p6dal",
  "https://gamma.app/docs/-0prfrtnuwl0s9e0",
  "https://gamma.app/docs/-8bee923pa12g5mj",
  "https://gamma.app/docs/-3dw8qbzqww3zc0k",
  "https://gamma.app/docs/-k5rcd050v4nta1h",
  "https://gamma.app/docs/-t4j0ezy2u4dnhqr",
  "https://gamma.app/docs/-soi31stkix1f7y3",
  "https://gamma.app/docs/-o9wxhxm1nw9sma5",
  "https://gamma.app/docs/-5z14zciln3u2b8h",
  "https://gamma.app/docs/-5u8cv8qubldmoan",
  "https://gamma.app/docs/-odj6m1jh5p76bah",
  "https://gamma.app/docs/-ujm1q396y91mih8",
  "https://gamma.app/docs/-jfgosssv4y92wg2",
  "https://gamma.app/docs/-dtm0jtyflgnybmf",
  "https://gamma.app/docs/-g8djh80xbasd2kq",
  "https://gamma.app/docs/-mlse7lpwkmt1aga",
  "https://gamma.app/docs/-drffurx6tt3sjtd",
  "https://gamma.app/docs/-pmmly3etukq8eyy",
  "https://gamma.app/docs/-sb73aoic39wpdev",
  "https://gamma.app/docs/-37lc8t3ajx09xyq",
  "https://gamma.app/docs/-0f6alctotc8kdg8",
  "https://gamma.app/docs/-xtiil4tuhmynq73",
  "https://gamma.app/docs/-z9s4904gru83euq",
  "https://gamma.app/docs/3D--b78zrohehtx1soq",
  "https://gamma.app/docs/AI--l0xk4jegi5zelfd",
  "https://gamma.app/docs/-mcyeio63ohaaxc8",
  "https://gamma.app/docs/-2bxqnz8sr2y6k7q",
  "https://gamma.app/docs/-hk1d6usnb86kmur",
  "https://gamma.app/docs/STEM--t0o671d8jcl7hh6",
  "https://gamma.app/docs/-lq0e6hji6y0hf11",
  "https://gamma.app/docs/-o59uc1nem7kdapn",
  "https://gamma.app/docs/-fcplxerug5qktrb",
  "https://gamma.app/docs/-vqz9xycvlct18hi",
  "https://gamma.app/docs/-i1uhs4bhr3m8w52",
  "https://gamma.app/docs/-dzjbyjipbck9xkd",
  "https://gamma.app/docs/-t9pwhbcgef0ay0b",
  "https://gamma.app/docs/-xz1jb3ndll6nwwm",
  "https://gamma.app/docs/-klo0zim3gda2bkg",
  "https://gamma.app/docs/-6zu5oowhwcjqyta",
  "https://gamma.app/docs/-7r5wooqdp1lup83",
  "https://gamma.app/docs/-r8fe3krrcirtbr2",
  "https://gamma.app/docs/-5w47hzlhmksor8x",
  "https://gamma.app/docs/-hea7rkt8c75xsz9",
  "https://gamma.app/docs/-bidqj1suf8wjxxg",
  "https://gamma.app/docs/-ea8qnwtiqzxkycd",
  "https://gamma.app/docs/-c7z4xxrfk8nfsaa",
  "https://gamma.app/docs/-fr84pplewqmq5y4",
  "https://gamma.app/docs/-poan9q9ti03458y",
  "https://gamma.app/docs/-jopl4d6mcjp96ng",
  "https://gamma.app/docs/-czm4xyvwa8crhrt",
  "https://gamma.app/docs/-ihfgzhbcarh10q0",
  "https://gamma.app/docs/-ibaakdxo12f4u2b",
  "https://gamma.app/docs/-yr9ubk8zgqxem7q",
  "https://gamma.app/docs/-l9tovcnqzjaej07",
  "https://gamma.app/docs/-y842ux8dzrdg3id",
  "https://gamma.app/docs/-uit55beir3cz4p9",
  "https://gamma.app/docs/-kub6tvvn0oerko2",
  "https://gamma.app/docs/-p41y9kcg0yrq7wu",
  "https://gamma.app/docs/-qthgzsigpuvryzb",
  "https://gamma.app/docs/-piceqvzb261cii2",
  "https://gamma.app/docs/-3hjhkq9r58mjfv5",
  "https://gamma.app/docs/-k1yb8827tf7qmy2",
  "https://gamma.app/docs/-n9zsk22ad7hts5j",
  "https://gamma.app/docs/-0oo8x9wyg4vfchh",
  "https://gamma.app/docs/-edvqzvsoyty1h0o",
  "https://gamma.app/docs/-6iz6f0iix4psp9e",
  "https://gamma.app/docs/-lo1n49f498u6sbm",
  "https://gamma.app/docs/-im201p4ih10xfo2",
  "https://gamma.app/docs/-taqkek9v5260m6d",
  "https://gamma.app/docs/-m7eqz1zlf2sjo1r",
  "https://gamma.app/docs/-rof8j779av6x6bg",
  "https://gamma.app/docs/-4qnc6omk3d9k0au",
  "https://gamma.app/docs/-5lemyq26jesegle",
  "https://gamma.app/docs/3D--vredyazv3l3ixca",
  "https://gamma.app/docs/-qgf9tnshsruhxtp",
  "https://gamma.app/docs/-9w47pwn4dxdbkkb",
  "https://gamma.app/docs/IoT--u8rh591u9o3oawd",
  "https://gamma.app/docs/-ldsxn2i7r3z5koi",
  "https://gamma.app/docs/-qij7be7s7fk0wgw",
  "https://gamma.app/docs/-t6ihe80b0il2s2i",
  "https://gamma.app/docs/-6gd9bw5reyff55x",
  "https://gamma.app/docs/-pkovx47mw4di70k",
  "https://gamma.app/docs/-rsq3538k9a3ke54",
  "https://gamma.app/docs/-fwie4lhsndundlh",
  "https://gamma.app/docs/-0quvqevx9znbthk",
  "https://gamma.app/docs/-hks23k6es0smskr",
  "https://gamma.app/docs/-pdyuwwav7huqhlr",
  "https://gamma.app/docs/-oq70dh1r7uemiig",
  "https://gamma.app/docs/-eq5k6uhrw786li0",
  "https://gamma.app/docs/-iwj56vtf9h11ixg",
  "https://gamma.app/docs/-7tsttez08fxgdpx",
  "https://gamma.app/docs/-fym0tsusvwsnb42",
  "https://gamma.app/docs/-3kuckp7o9dcgoxt",
  "https://gamma.app/docs/-w2pmd490v9p8fq1",
  "https://gamma.app/docs/-oyve39k43dddtkx",
  "https://gamma.app/docs/-18uti4ah6wddwha",
  "https://gamma.app/docs/-ksvto4dpib2ka5l",
  "https://gamma.app/docs/-5pgvq8vxgdy7tmf",
  "https://gamma.app/docs/-ea7j989edc17xrk"
];

if($termplan->dreamchallenge!==NULL)$randomDream=$termplan->dreamchallenge; 
else $randomDream = $randomDreamList[array_rand($randomDreamList)];

$index = array_rand($randomDreamList);
$newdream = $randomDreamList[$index];
$newdreamurl = $randomDreamUrlList[$index]; 

echo '내꿈은 '.$randomDream;

$currentAnswer= '🎯 분기목표 : '.$weeklyGoal->text.' 🌟 랜덤꿈 챌린지 : '.$randomDream.'입니다. (🏳️ 꿈 유효기간 :D-'.$dreamdday.'일. 종료 전에 업데이트 하지 않으면 당신의 랜덤꿈은 자동 소멸됩니다.)';
$rolea='💎 분기목표 내용';
$roleb='💎 랜덤꿈 챌린지';
$talka1='';
$talkb1='';
$talka2='';
$talkb2='';
$tone1='';
$tone2='';
if($tmissioncreated>time()-10)$showreflection='<table><tr><td><img src="https://mathking.kr/Contents/IMAGES/randomdream2.jpg" width=100%><iframe  class="foo"  style="border: 0px none; z-index:2; width:100%; height:30vh;margin-left:0px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/visionmapping.php?userid='.$studentid.'&answerShort=true&count=5&currentAnswer='.$currentAnswer.'&rolea='.$rolea.'&roleb='.$roleb.'&talka1='.$talka1.'&talkb1='.$talkb1.'&talka2='.$talka2.'&talkb2='.$talkb2.'&tone1='.$tone1.'&tone2='.$tone2.'" ></iframe><img src="https://mathking.kr/Contents/IMAGES/randomdream3.jpg" width=100%></td></tr></table>';
else $showreflection='<table><tr><td><p align=center><img src="https://mathking.kr/Contents/IMAGES/randomdream1.jpg" width=100%></p></td></tr></table>'; 
echo ' 									
	<table width=80% align=center class="table"><thead><tr><th scope="col" style="width: 5%; font-size:12pt" align=right><a href="https://moreleap.clickn.co.kr/pages/visionbook"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/visionbook.png width=40></a></th><th scope="col" style="width: 10%; font-size:15pt" >'.$plantypes.'</th><th  style="width:15%; font-size:18pt"><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="데드라인"></th><th scope="col" style="width: 35%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="선생님과 상의하여 다음 분기까지의 목표를 입력해 주세요"></th><th scope="col" width=5% align=center>
	<span  onclick="inputgoalstep(8,'.$studentid.',$(\'#basic1\').val(),$(\'#datepicker\').val(),$(\'#squareInput\').val(),\''.$newdream.'\',\''.$newdreamurl.'\') "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th> 	</tr></thead></table><div class="row"><div class="col-md-12"><div class="card"><div class="card-header"><div class="card-title"><table align=center width=90% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$showreflection.'</table>'.$weekplanhistory.'</div></div><div class="card-body"> ';
  
echo '
				<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
								</div>
							</div>
						</div>
					 </div>
				</div>
			</div>
			
		</div><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
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

	<script>
		function inputgoalstep(Eventid,Userid,Plantype,Deadline,Inputtext,RandomDream,RandomDreamUrl){ 
				swal({
					title: \'현재의 꿈을 유지하시겠습니까 ?\',
					text: "현재 꿈 : " + "'.$randomDream.'",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'예\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'아니요\',
							className: \'btn btn-danger\'
						}      			

					}
				}).then((willDelete) => {
					if (willDelete) {
						swal("네, 그럼 다음 설명으로 넘어가겠습니다.",
							 {
							icon: "success",
							buttons : {
								confirm : {
									className: \'btn btn-success\'
									}
								}
						     });
          RandomDream="stay";
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
          "randomdream":RandomDream,	
          "randomdreamurl":RandomDreamUrl,	
                     },
                  success:function(data){ 
                  
                   }
              })


                 setTimeout(function() {window.open("'.$termplan->dreamurl.'", "_blank");location.reload();},100);
					} else {
						swal("좀 더 자세하게 설명해 드리겠습니다 !", {
							buttons : {
								confirm : {
									className: \'btn btn-success\'
								}
							}
						});

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
            "randomdream":RandomDream,	
            "randomdreamurl":RandomDreamUrl,	
                      },
                  success:function(data){ 
                  
                    }
            })
            setTimeout(function() {window.open(RandomDreamUrl, "_blank");location.reload();},100);
					}
				});
	 
      

 


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
		 swal("새로고침하면 목록에서 사라집니다.", {buttons: false,timer: 1000});  
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




<script>

function WeeklyGrade() 
{
  let wrap = document.createElement('div');
  wrap.setAttribute('class', 'text-muted');
  wrap.innerHTML = '<button onclick="reply(\'level1\',\'1\')" type="button" value="level1" class="btn feel">이탈 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009610001.png" width=30 height=30></button><button onclick="reply(\'level2\',\'2\')" type="button" value="level2" class="btn feel">시작 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009642001.png" width=30 height=30></button><button onclick="reply(\'level3\',\'3\')" type="button" value="level3" class="btn feel">연결 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009715001.png" width=30 height=30></button><button onclick="reply(\'level4\',\'4\')" type="button" value="level4" class="btn feel">루틴 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009756001.png" width=30 height=30></button><button onclick="reply(\'level5\',\'5\')" type="button" value="level5" class="btn feel">안정 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621009790001.png" width=30 height=30></button><hr>' ;
swal({
    title: "분기목표 안정도",
    text: "분기목표-중간목표-주간목표-오늘목표-활동결과의 연결상태를 표시",
    closeOnClickOutside: false,
    content: {
      element: wrap
    },
    buttons: {
      confirm: {
        text:"취소",
        visible: true,
        className: "btn btn-default",
        closeModal: true,
      }
    },
  }).then((value) => {
    if (value === 'level1') {
      swal("Booster step 시작단계입니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level2') {
      swal("Booster step 실행단계입니다.", {
        icon: "success",
        buttons: false
      });
    } else if (value === 'level3') {
      swal("Booster step 숙달단계입니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level4') {
      swal("Booster step 체화단계입니다.", {
        icon: "success",
        buttons: false
      });
   } else if (value === 'level5') {
      swal("Booster step 마스터 클레스입니다.", {
        icon: "success",
        buttons: false
      });
    }
  });
}

function reply(feel,resultValue){
	var Userid= "<?php echo $studentid;?>";
	var Tutorid= "<?php echo $USER->id;?>";
	var Eventid="100";

swal.setActionValue(feel);
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":Eventid,
	"userid":Userid,
	"tutorid":Tutorid,
 	"value":resultValue,
	},
	success:function(data){
	 }
	 })
swal("상위 단계 목표와의 연결상태가 업데이트 되었습니다.", {buttons: false, timer: 2000, });
}
 
</script>
