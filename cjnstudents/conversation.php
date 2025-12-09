<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[conversation.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;
 
// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘.. 
$userid=$_GET["userid"]; 
$cnttype=$_GET["cnttype"]; 
$cntid=$_GET["cntid"]; 
$mode=$_GET["mode"]; 
$type='conversation';//$_GET["type"];  
$gid= 71280;//$_GET["gid"];  7128 은 전체 해설을 의미함
$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
$wboardid =$thisboard->wboardid;
  
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800; 
$timecreated=time();

if($cnttype==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $eventid=1;
    $maintext=$cnttext->maintext;
    $description=$cnttext->reflections0;
   // $description=$cnttext->reflections1;

    $contentstext=$maintext;
    }
elseif($cnttype==2)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $guidetext=$cnttext->mathexpression;
    $maintext=$cnttext->ans1;
    $description=$cnttext->reflections0;
    //$description=$cnttext->reflections1;
    $eventid=2; 

    $contentstext=$guidetext.$maintext;
    } 
   
$cnttext=$contentstext;
$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE '$type' AND contentsid LIKE '$cntid' AND contentstype LIKE '$cnttype' AND gid LIKE '$gid'  ORDER BY id DESC LIMIT 1 ");
  

if($thiscnt->id!=NULL && $mode!=='restart')
    { 
    header("Location: https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid=" . $cntid . "&ctype=" . $cnttype);
    exit();
    }
else 
    {   
   $preparegpt=$cnttext.'내용을 세밀하게 검토해줘. 이 내용을 공부하는 과정에서 학생들이 겪을 수 있는 혼란과 불안요소들을 상세하게 기술해줘. 표현을 학생의 구어체 어투로 해줘. 결과 생성시 수식을 절대 사용하지말고 발음 대로 반드시 한글로 읽어줘.전체 설명을 의미단위로 몇 개 단계로 나눠서 진행해줘.';
   $newtalk='선택하신 부분에 대한 상세하고 입체적인 이해를 도와드립니다. 발송 아이콘을 눌러서 결과 저장하기를 클릭하시면 보충설명을 보실 수 있습니다 ';
    $typenew= '<script>
    var text = "'.$newtalk.'";
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

    </script>';
    } 
$answerShort=false; 
$count=2;
$rolea='';
$roleb='';
$tone1='학생은 선생님의 설명을 듣고 질문을 합니다.   설명이 마무리 될때까지 멈추지 말아줘. 마무리는 학생의 감사 인사로 해줘. 
'; 
$tone2=' 
Role:
act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.

Context:
- The expert needs to convert any mathematical content into a script that sounds natural when spoken in Korean, maintaining the sequence and coherence of the original content.
- The narrator is tasked with making the content understandable and engaging, using explanations, examples, and analogies, especially clarifying any potentially confusing parts.

Input Values:
- Mathematical text containing numbers, symbols, etc.

Instructions:
- Convert all numbers into their spoken Korean equivalents (e.g., 1 as 일, 2 as 이, etc.) . 최종 결과물에는 한글만 존재해야하며 다른 기호나 숫자는 존재하지 않아야 합니다.
-  Ensure all symbols, mathematical expressions, and alphabets are converted into their phonetic Korean readings.
- Maintain the logical sequence and coherence of the original mathematical content while transforming it into a narration script.
- Add explanatory notes, examples, or analogies to aid understanding, particularly clarifying any complex or confusing parts.
- Summarize each topic unit clearly, ensuring the script is engaging and understandable for a broad audience.
- Prepare the script for professional voice-over recording, ensuring it is suitable for educational video content.
- 학생이 나레이션의 도움을 통하여 혼자 스스로 공부할 수 있도록 적절한 예시와 세밀한 표현을 통하여 학습을 유도해 주세요.
- 항상 선생님과 학생의 대화형식으로 구성해줘. 특히, 학생은 헷갈리는 부분을 질문하며 다른 학생들이 대화를 들었을 때 도움이 되도록 해줘. 학생들이 컨텐츠를 보며 대화를 듣도록 제공된 내용에 대해 순서대로 읽으며 진행해줘
 

Guidelines:
- The script should be detailed enough for a professional voice actor to understand and perform without needing additional context.
- The language should be clear, professional, and accessible, suitable for a mathematics educator.
- Where necessary, include cues for intonation or emphasis to guide the voice-over artist.
- 생성결과에 아무리 간단한 경우라고 해도 반드시 숫자, 기호 대신 한글만 사용되어야 해. 
- 마지막에는 학생 간단하게 내용을 요약하고 점검하는 멘트를 추가하고 후속학습을 추천해줘.
Output format:
- Plain text suitable for script reading.

Output fields:
- Detailed narration script including numbers, explanations, examples, and any additional notes for clarity
Output examples:
선생님: 이번 시간에는 ... 에 대해 알아보는 시간을 가지겠습니다.
학생 : 아. ..인 거군요. 기대됩니다 ! 
선생님: 원과 직선의 위치 관계를 이해하기 위해서는 원의 중심과 직선 사이의 거리 $d$와 원의 반지름 $r$을 비교해야 해. 이때 세 가지 경우가 있어. 첫 번째 경우는 $d > r$일 때 이 경우 원과 직선은 만나지 않아. 두 번째 경우는 $d = r$일 때 이 경우 원과 직선은 접해. 마지막으로 $d < r$일 때 원과 직선은 서로 교차해. 이 세 가지 경우를 잘 기억해 두면 좋겠어.
학생: 선생님 $d > r$일 때 원과 직선이 만나지 않는다고 하셨는데, 이 경우를 좀 더 자세히 설명해 주실 수 있나요?,
선생님: 물론이지! $d > r$라는 것은 원의 중심에서 직선까지의 거리가 원의 반지름보다 크다는 의미야. 즉, 원의 가장자리가 직선에 도달하지 못하고, 따라서 두 도형은 서로 만나지 않게 되는 거지. 예를 들어, 원의 중심이 $(0, 0)$이고 반지름이 $r = 2$인 원을 생각해보면, 직선이 $y = 3$일 때, 원의 중심과 직선 사이의 거리는 $d = 3$이야. 이 경우 $d > r$이므로 원과 직선은 만나지 않아.,
학생: ....
 
# 이것은 중요해 ! 
- 어떤 생성결과도 한글만 사용해줘, 특수문자나 숫자, 기호 등은 절대로 사용하지 말아줘
- 분수읽을 때 오류 발생 주의 $\frac{3}{4} 는 사분의 삼이야. 그런데 종종 삼 사분의 삼이라고 잘못읽는 경우가 있어 조심해.
- : (콜론)은 학생과 선생님 뒤에만 나타나게해. 다른 상황에서 콜론을 사용하는 일은 절대 금지
- 결과 생성은 반드시 대화형식으로 자연스럽게 이어줘. 절대로 목록화(예시. - 목록1, - 목록2, - 목록3)를 하지마. 

# 이것은 매우 중요해
- 숫자를 표현할 때 반드시 아라비아숫자 읽기 (일, 이, 삼, 사, .... ,이십, 이십일..)를 사용해줘
- 하나, 둘, 셋, 넷, 다섯, 여섯, 일곱, 여덟, 아홉, 열, 열하나 ... 스물 등과 같은 표현은 사용하지말아줘.
- 소숫점을 잘 식별해서 읽어줘 0.35 (영점삼오)
- 수식을 사용하지말고 한글 발음 대로 한글만 사용해서 결과를 생성해주

프롬프트 시작 부분에 추가할 필수 문장 : 
작은 의미단위로  완결성있게 설명 후 요약. 그리고 준비하는 시간을 가지고 준비가 되면 다음 소주제로 넘어 가는 방식으로 잘게 잘게 쪼개서 진행해줘. 작은 예시들을 통하여 확실히 확인하는 방식으로 진행해줘.

'; 
$talka1='선생님이 설명합니다.';  $talkb1='학생질문하고 참여합니다.';
 

$part1='<table width=100%><tr><td width=50% valign=top>
<div id="typing-container"><div><table align=left valign=top><tr><td valign=top><a href="https://chatgpt.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/personatalk.jpg"  width=500></a> </td><td valign=bottom></td></tr></table>
</div></div><table width=100% style="font-size:16px;">'.$chathistory.'</table>
<table align=center width=90%><tr><td>
<div id="typing-box">
<div id="typing-text"></div><div id="animated-text"></div>
<div id="typing-cursor"></div>
</div></td></tr></table> <br>
';

$part2='<table width=90% align=center><tr><td>  
  <style> 

    div {
      border: 1px solid white;
      padding: 5px;
      /* div 태그의 보더를 회색으로 지정 */
    }

    pre {
      white-space: pre-wrap;
    }
  </style> 
<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script>
//-----------------------------------
const apikey = "'.$openai_api_key.'"; // API-KEY from env
const answerShort = \''.$answerShort.'\';  // 짧게 대답할지
const count = \''.$count.'\'; // 대화의 횟수 
//const currentAnswer = \''.$currentAnswer.'\'; // 첫 마디
const roleName = role => role ? \''.$rolea.'\' : \''.$roleb.'\'; // 역할의 이름
const instruction = role => `
INSTRUCTIONS: 
- ${role ? \''.$talka1.'\' : \''.$talkb1.'\'} 
- \''.$tone1.'\'
- \''.$tone2.'\' 
`;
//-----------------------------------
const chatgpt = async (messages, streamListener) => {
   const fetchoption = {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${apikey}` },
      body: JSON.stringify({
         stream: true,
         model: "gpt-4o-mini", //model: "gpt-3.5-turbo",
         messages,
         temperature: 0,
      }),
   };
   let resolver;
   const awaiter = new Promise(resolve => resolver = resolve)
   fetch("https://api.openai.com/v1/chat/completions", fetchoption).then(response => {
      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let role;
      const message = [];
      reader.read().then(function processResult(result) {
         let data = decoder.decode(result.value, { stream: true });
         let errMsg;
         try { errMsg = JSON.parse(data).error.message } catch { }
         if (errMsg) throw new Error(errMsg)
         let dataList = data.trim().split("\n");
         for (let line of dataList) {
            line = line.trim()
            if (!line) continue;
            if (line.startsWith("data: ")) line = line.slice(6);
            if ("[DONE]" === line) continue;
            const { delta, finish_reason } = JSON.parse(line).choices[0];
            if (!role && delta.role) { role = delta.role; continue }
            if (!delta.hasOwnProperty("content")) continue;
            const token = ({ ...delta, role }).content
            streamListener(token)
            message.push(token)
         }
         if (result.done) { resolver(message.join("")); return }
         else reader.read().then(processResult);
      });
   });
   return await awaiter
}
class AIRobot {
   constructor(position) {
      this.memory = [position];
   }

   async listenAndAnswer(answer, streamListener) {
      const message = {
         role: "user",
         content: answer
      };
      this.memory.push(message);
      const response = await chatgpt(this.memory, streamListener);
      const aiMessage = {
         role: "assistant",
         content: response
      };
      this.memory.push(aiMessage);
      return response;
   }
}
const makeLine = (currentParticipant, currentParticipantIndex) => {
  const conversationLine = document.createElement("div");
  conversationLine.style.display = "flex";
  conversationLine.style.padding = "0px";
  const nameTag = document.createElement("div");
  nameTag.style.minWidth = "0px";
  nameTag.style.maxWidth = "0px";
  // nameTag.style.fontSize = "9px";
  nameTag.style.overflow = "hidden";
  nameTag.innerText = currentParticipant.name;
  conversationLine.appendChild(nameTag);
  const opinionBox = document.createElement("div");
  //opinionBox.style.border = "none";
  //opinionBox.style.backgroundColor = currentParticipantIndex === 0 ? "#444" : "#333";
  //opinionBox.style.color = "#fff";
  //opinionBox.style.padding = "5px";
  //conversationLine.appendChild(opinionBox);
  // 아래 두 줄을 추가합니다.
  const outputText = document.querySelector("#output-text");
  outputText.appendChild(conversationLine);

  conversationLine.classList.add("conversation-output"); // Add this line
  return { nameTag, conversationLine, opinionBox }
}

window.addEventListener("load", e => {
 
 document.querySelector("#startTalk").addEventListener("click", async e => {

  currentAnswer = "'.$firstdescription.'" + document.querySelector("#input-text").value;
  const outputText = document.querySelector("#output-text");
  outputText.innerHTML = ""; // output-text div 내용 초기화
      clearOutput(); // Add this line to clear previous output
      let _currentAnswer = currentAnswer;
      const participations = [];
      [true, false].forEach(role => {
         const position = {
            role: "system",
            content: instruction(role),
         };
         const discussionAIRobot = new AIRobot(position);
         discussionAIRobot.name = roleName(role);
         participations.push(discussionAIRobot);
      })
      let isPlayerTurn = false;
      let systemStarts = false;
      if (!systemStarts) {
         participations[Number(!!isPlayerTurn)].memory.push({
            role: "user",
            content: _currentAnswer
         });
         const { opinionBox } = makeLine(participations[Number(!!isPlayerTurn)], Number(!!isPlayerTurn));
         opinionBox.innerText = _currentAnswer;
      }
      let _count = count - 1;
      while (_count > 0) {
         let currentParticipantIndex = Number(!isPlayerTurn);
         const currentParticipant = participations[currentParticipantIndex];
         const { opinionBox, nameTag, conversationLine } = makeLine(currentParticipant, currentParticipantIndex);
         const streamListener = token => {
          const outputText = document.querySelector("#output-text"); // output-text div 요소를 가져옴
          const opNode = document.createTextNode(token);
          outputText.appendChild(opNode); // output-text div에 메시지를 추가함
          window.scrollTo(0, document.body.scrollHeight)
          document.body.style.marginBottom = "0px";
            return new Promise(resolve => setTimeout(resolve, 1000));
         } 
        
         
         let req = answerShort ? ". 짧게 대답하세요." : "";
         _currentAnswer = await currentParticipant.listenAndAnswer(_currentAnswer + req, streamListener);
         isPlayerTurn = !isPlayerTurn;
         _count--;
         if (_count === 0) break;
      }
          document.getElementById("savebtn").click();
   });

});

const clearOutput = () => {
   const body = document.querySelector("body");
   const conversationOutputs = body.querySelectorAll(".conversation-output");
   conversationOutputs.forEach(child => {
      body.removeChild(child);
   });
};

</script>';
//화면출력


echo '<!DOCTYPE html><html><head>
<!-- MathJax 3 start -->
<script>
MathJax = {
  tex: {
    inlineMath:[["$","$"],["$$","$$"],["\(","\)"]],
    //displayMath: [ ["$","$"],["$$","$$"], ["\\[","\\]"],["\(","\)"]]
  },
  startup: {
    ready: function () {
      MathJax.startup.defaultReady();
      MathJax.startup.promise.then(function () {
        // 모든 수식이 렌더링된 후 실행할 코드
      });
    }
  }
};
</script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<!-- MathJax 3 end -->
</head>

<body>'.$part1.$part2.'</td></tr></table>'.$typenew.'<table width=90% align=center><tr></table>
<div><table align=center width=90%><tr><td width=3%><button class="submit-button3" id="startTalk" onclick=""><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/sendicon.png width=30></button></td><td align="center"><input height=50px type="text" class="form-control input-square" id="input-text" name="squareInput"  placeholder="선택부분 상세 설명보기 클릭" value="'.$preparegpt.'"  rows="4" ></td><td></td><td><button style = "z-index:2;background-color:#C4C4C4;" id="savebtn" onclick="saveResult();">저장</button>  <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&cntid='.$thiscnt->id.'&cnttype=8&gid='.$gid.'"target="_blank"><img src=https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png width=20></a></td></tr></table><table width=90% align=center><tr><td> 
<div id="output-text"></div> </td></tr></table></div></body></html>';
include("style.php");

echo '
<script>
function saveResult()
  {
    var Contentsid= \''.$cntid.'\'; 
    var Contentstype= \''.$cnttype.'\';
    var Type= \''.$type.'\';    
    var Wboardid= \''.$wboardid.'\';  
    var Gid= \''.$gid.'\';  
    var Outputtext =document.getElementById("output-text").textContent; 
    $.ajax({
      url:"check_status.php",
      type: "POST", 
      dataType:"json",
      data : {
      "eventid":\'1\', 
      "contentsid":Contentsid,
      "contentstype":Contentstype,
      "type":Type,
      "wboardid":Wboardid,
      "gid":Gid,
      "text":Outputtext,
      },
      success:function(data){		
      var outputgid = data.gid;			 
        Swal.fire({
          position:"top-end",
          icon: "success",
          title: "저장되었습니다.",
          showConfirmButton: false,
          timer: 1500
        });

        setTimeout(function(){
            window.parent.location.reload(); 
          //  window.close("", "_parent", "");
        }, 1000);
      }
    })   
    
  }
</script>
<style>
#typing-text {
  font-size: 16px;
  line-height: 1.5;
  margin-bottom: 10px;
}

@media (max-width: 767px) {
  /* Set font size for screens smaller than 768px (smartphones) */
  #typing-text {
    font-size: 16px;
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
    width: 40%;
    padding: 0px;
  }
  
  #teacher-image img {
    width: 30%;
    height: auto;
    display: block;
    border-radius: 10px;
  }
  
  #typing-box {
    width: 100%;
    padding: 20px;
    border-radius: 10px;
    background-color: #f5f5f5;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
 
  
  #typing-text {
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 10px;
  }
    
 
  
  #typing-cursor {
    width: 5px;
    height: 18px;
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
  .submit-button {
    background-color: #4CAF50; /* Green */
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    border-radius: 20px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .submit-button:hover {
    background-color: #3e8e41; /* Dark green */
  }
  .submit-button2 {
    background-color:#0080ff; /* bright blue */
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    border-radius: 20px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .submit-button2:hover {
    background-color: white; /* Dark green */
  }
  .submit-button3 {
    background-color:white; /* bright blue */
    border: none;
    color: black;
    padding: 5px 22px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    border-radius: 5px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 0px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .submit-button3:hover {
    background-color: #e6f2ff; /* Dark green */
  }
</style>
';
?>