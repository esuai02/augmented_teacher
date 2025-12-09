<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘.. 
$userid=$_GET["userid"]; 
$teacherid=$_GET["teacherid"]; 
$type=$_GET["type"]; 
$initialtalk=$_GET["initialtalk"];
$finetuning=$_GET["finetuning"]; 
$mode=$_GET["mode"];  
$step=$_GET["step"];  
if($teacherid==NULL)$teacherid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;
 
$timecreated=time();
$minutesago=$timecreated-600;
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
// 페이지정보, feedback, button 정보 가지고 오기
if($type==='firstvisit')include("./pages/firstvisit.php"); 
elseif($type==='welcome')include("./pages/welcome.php"); 
elseif($type==='prepare')include("./pages/prepare.php"); 
elseif($type==='todaygoal')include("./pages/todaygoal.php"); 
elseif($type==='todaymc')include("./pages/todaymc.php"); 

elseif($type==='mycourses')include("./pages/mycourses.php"); 
//elseif($type==='chapter')include("./pages/chapter.php"); 
//elseif($type==='mynote')include("./pages/mynote.php"); 

elseif($type==='changecourse')include("./pages/changecourse.php"); 
elseif($type==='missionhome')include("./pages/missionhome.php"); 
elseif($type==='cognitivetutor')include("./cognitivetutor.php"); 
elseif($type==='dashboard')include("./pages/dashboard.php"); 
elseif($type==='schedule')include("./pages/schedule.php"); 
elseif($type==='editschedule')include("./pages/editschedule.php"); 
elseif($type==='submittoday')include("./pages/submittoday.php"); 
elseif($type==='goodbye')include("./pages/goodbye.php"); 
elseif($type==='popup' && $initialtalk!=NULL)$newtalk=$initialtalk; //$newtalk=$pagewelcome;
// prepare gpt가 각가의 페이지 파일들에 있음.

// 가지고 온 정보 처리하기
include("./pages/process/firstaccess.php");
//include("./pages/process/newevents.php"); 
//include("./pages/process/selectnext.php"); 

include("interface.php"); // gpt chat + chatbot + chathistory

// 페이지 렌더링
for($nbtn=1;$nbtn<=3;$nbtn++)
  {
  echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script>
		$(document).ready(function() {
			$("#updateButton'.$nbtn.'").click(function() {
                var Eventid= \''.$nbtn.'\';
                var Userid= \''.$userid.'\';
                var Pagetype= \''.$type.'\';
               
                $.ajax({
                    url: "generatehtml.php",
                    type: "POST",
                    dataType: "json",
                    data : {
                            "eventid":Eventid, 
                            "userid":Userid, 
                            "pagetype":Pagetype,
                          },
                    success:function(data){
                            $("#updateDiv").html(data.html); 
                            // 버튼 클릭에 의해 재구성된 page 컨텐츠 정보를 표시.
                            }
                    });
			});
		});
  </script>';
  }
// 이부분에서 자동 체크 알고리즘 적용

// 현재 pagetype에 맞는 우측 메뉴구성. displaycnt.php에서 연결되는 다양한 page유형들 있음.
// 아래는 플러그인 적용을 위한 php 모듈 파일들
/*

include("contexticon.php"); // context에 맞는 설정버튼 아이콘 (선생님별 개인 preset 관리)
include("button.php"); // 설정된 버튼 표시. 선생님 preset history 적용. pagetype정보와 연동.

include("displaycomment.php"); // 사용자 아이콘 + text history

*/ 
// 1. 시작버튼 클릭으로 시작
// 2. 지난학습 review.. 우측은 최근 공부한 내용들입니다. 오늘 활동에 대한 간단한 성찰을 해 보시기 바랍니다. 버튼 : GPT 추천질문 버튼. NEXT 버튼. 
// 3. 분기, 주간, 오늘 목표 점검 및 학습상태 클리어링 확인하기. 버튼 : NEXT (상태진단 후 알림 후 바로가기)
// 4. 클리어된 상태.. 현재 진행중인 강좌는 ... 입니다. 버튼 : 시작 / 변경 / 자율학습. -자율학습 환경으로 이동합니다. 지난 활동들을 점검하며 필요한 보충학습을 진행해주세요.
// 개념공부/심화공부/내신대비/수능공부 ... 중 설정된 환경으로 이동... 
// 우측은 현재 진행 중인 강좌들입니다. 공부를 시작하실 수 있습니다. 


// 1. 오답노트 등 todolist 체크 10초단위로.. 대화창에 결과 표시. 시간표 변경 등 요청사항 포함 ( 페이지 우측 화면용으로 최적화)
// 2. 퀴즈 시작화면에서 자동으로 되돌아가기
// 3. 퀴즈는 adaptable theme 하단에 챗봇 아이콘 배치
// 4. 현재 진행중이 활동에 대한 맞춤형 dashboard 제공. 오늘 활동 중 오늘 해당 부분위주로 표시. 버튼. 전체현황보기. 진단버튼.
// 5. 지난 활동 중 마무리 안된 부분. 준비학습 등 오늘 활동 부분에 대한 내용 표시
// 6. 수고하셨습니다. 다음 시간 목표를 입력하시면 귀가검사가 제출됩니다. 



// google dialogue flow로 gpt 환경 제공 (챗봇 우측하단)
// 오늘 공부에 보다 쉽게 몰입하기 위하여 ... 메타인지 배우기를 추천드립니다.
// ... 을 선택하였습니다. 우측에 표시된 컨텐츠를 보면 메타인지에 대한 이해도를 높여보시기 바랍니다.
// 페이지 통합 : MBTI, 메타인지, 메타인지로그.



// 발신자 아이콘은 사용자에 맞게 

// 이전, 이후 버튼
// 신규생인 경우... 별도 링크로 접속.. Welcome 인사와 함께 아이디 비번 생성 .. 선생님에게 전달.. 승인.. 로그인 .. 신규생 모드로 시작..
// 시험기간인 경우 (분기목표에 중간, 기말 키워드) 모드 선택 후 맞춤형 피드백 환경으로 전환
// 메뉴체크
// 코멘트 체크
// 메세지 및 todolist 등 정기적으로 상태 점검하는 query ...

?>