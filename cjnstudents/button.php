<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘.. 
$userid=$_GET["userid]; 
$pagetype=$_GET["ptype"]; 
$prevpage=$_GET["ppage"]; 
$nextpage=$_GET["npage"]; 
$commentid=$_GET["cmt"]; 

include("contextimg.php"); // 추후 DB로 이동하여 사용자 커스터마이즈 가능하도록 변경
include("contexticon.php"); // 추후 DB로 이동하여 사용자 커스터마이즈 가능하도록 변경
include("button.php");
include("displaycnt.php");
include("comment.php");
// 발신자 아이콘은 사용자에 맞게 

// 이전, 이후 버튼
// 신규생인 경우... 별도 링크로 접속.. Welcome 인사와 함께 아이디 비번 생성 .. 선생님에게 전달.. 승인.. 로그인 .. 신규생 모드로 시작..
// 시험기간인 경우 (분기목표에 중간, 기말 키워드) 모드 선택 후 맞춤형 피드백 환경으로 전환
// 메뉴체크
// 코멘트 체크
// 메세지 및 todolist 등 정기적으로 상태 점검하는 query ...
// 지난학습 review.. 우측은 최근 공부한 내용들입니다. 오늘 활동에 대한 간단한 성찰을 해 보시기 바랍니다. 
// GPT 추천질문은 ... 입니다.
// 오늘 공부에 보다 쉽게 몰입하기 위하여 ... 메타인지 배우기를 추천드립니다.
// ... 을 선택하였습니다. 우측에 표시된 컨텐츠를 보면 메타인지에 대한 이해도를 높여보시기 바랍니다.
// 다음... 
// 분기, 주간, 오늘 목표 점검 및 학습상태 클리어링 확인하기
// 클리어된 상태.. 현재 진행중인 강좌는 ... 입니다. 시작 / 변경 / 자율학습
// 자율학습 환경으로 이동합니다. 지난 활동들을 점검하며 필요한 보충학습을 진행해주세요
// 개념공부/심화공부/내신대비/수능공부 ... 중 설정된 환경으로 이동... 
// 우측은 현재 진행 중인 강좌들입니다. 공부를 시작하실 수 있습니다. 
// 오답노트 등 todolist 체크 10초단위로.. 대화창에 결과 표시. 
// 퀴즈 시작화면에서 자동으로 되돌아가기
// 퀴즈는 adaptable theme 하단에 챗봇 아이콘 배치
// google dialogue flow로 gpt 환경 제공 (챗봇 우측하단)
// 현재 진행중이 활동에 대한 맞춤형 dashboard 제공. 오늘 활동 중 오늘 해당 부분위주로 표시.
// 지난 활동 중 마무리 안된 부분. 준비학습 등 오늘 활동 부분에 대한 내용 표시
// 시간표 변경관련 페이지 우측 화면용으로 최적화
// 분기 목표 변경 관련 페이지 우측 화면용으로 최적화
// 강좌 추가 관련 페이지 우측 화면용으로 최적화
// 메타인지 관련 페이지 우측 화면 용으로 최적화
// 수고하셨습니다. 다음 시간 목표를 입력하시면 귀가검사가 제출됩니다. 
// 계획된 분기 목표가 ... 입니다. 적용 / 변경 

$gptlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_gptultratalk where id='$id' ");  

$gptquestion = $gptlog->question;
$gpttalk = $gptlog->gpttalk;
//$gpttalk =strip_tags($gpttalk);

echo '
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
#typing-text {
  font-size: 24px;
  line-height: 1.5;
  margin-bottom: 10px;
}

@media (max-width: 767px) {
  /* Set font size for screens smaller than 768px (smartphones) */
  #typing-text {
    font-size: 30px;
  }
}


#typing-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    padding: 20px;
  }
  
  #teacher-image {
    width: 20%;
    padding: 20px;
  }
  
  #teacher-image img {
    width: 60%;
    height: auto;
    display: block;
    border-radius: 10px;
  }
  
  #typing-box {
    width: 60%;
    padding: 20px;
    border-radius: 10px;
    background-color: #f5f5f5;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  
  #typing-text {
    font-size: 24px;
    line-height: 1.5;
    margin-bottom: 10px;
  }
  
  #typing-cursor {
    width: 5px;
    height: 24px;
    background-color: #000;
    animation: cursor-blink 1s infinite;
  }
  
  @keyframes cursor-blink {
    0% {
      opacity: 0;
    }
    50% {
      opacity: 1;
    }
    100% {
      opacity: 0;
    }
  }
</style>
</head>
 <div id="typing-container">
  <div id="teacher-image">
    <table align=center valign=bottom><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png" alt="Teacher Image"></td></tr></table>
  </div>
  <div id="typing-box">
    <div id="typing-text"></div>
    <div id="typing-cursor"></div>
  </div>
</div>


<script>
  var text = "'.$gpttalk.'";
  var lines = text.split("\n");
  var lineIndex = 0;
  var charIndex = 0;
  var speed = 50;
  var typingTimer;

  function typeLine() {
    var line = lines[lineIndex];
    if (charIndex < line.length) {
      document.getElementById("typing-text").innerHTML += line.charAt(charIndex);
      charIndex++;
      typingTimer = setTimeout(typeLine, speed);
    } else if (lineIndex < lines.length - 1) {
      document.getElementById("typing-text").innerHTML += "<br>";
      lineIndex++;
      charIndex = 0;
      typingTimer = setTimeout(typeLine, speed);
    }
  }

  typeLine();
</script>
 
</html>
';
// 이부분에서 자동 체크 알고리즘 적용

?>