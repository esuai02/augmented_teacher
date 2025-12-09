<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;

// API 키 로드
require_once(dirname(dirname(dirname(__DIR__))) . '/config/api_keys.php');
$openai_api_key = get_openai_api_key();

// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘..
$userid=$_GET["userid"];
$cnttype=$_GET["cnttype"];
$cntid=$_GET["cntid"];
$type='pedagogy';
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


if($thiscnt->id!=NULL)
    {
    $newtalk=$thiscnt->outputtext;
    $newtalk = str_replace("\\", "\\\\", $newtalk);
   // if($mode==='modify')
      {
      //$preparegpt=$cnttext.'중에서 '.$prompt.' 부분을 전체 본문에서의 위치를 식별해줘. 이후 해당 부분을 문맥을 고려하여 작은 단계로 나누어 설명해줘. 다른 부분은 이해가 되니 이 부분만 집중해서 진행해줘. 확인질문 5개 생성';
      $preparegpt=$cnttext.'내용을 세밀하게 검토해줘. ';
      $firstdescription ='';
      }
      $typenew='
      <script>
      document.addEventListener("DOMContentLoaded", function() {
        MathJax.startup.promise.then(function () {
            var steps = ['.$newtalk.'];
            var container = document.getElementById("animated-text");

            steps.forEach(function(text, index) {
                var div = document.createElement("div");
                div.innerHTML = text;
                container.appendChild(div);

                setTimeout(function() {
                    div.style.opacity = 1;
                    div.style.transform = "translateY(0)";
                    MathJax.typesetPromise([div]).then(function() {
                        console.log("MathJax 렌더링 완료");
                        // 스크롤을 페이지의 맨 아래로 이동
                        window.scrollTo(0, document.body.scrollHeight);
                    }).catch(function(err) {
                        console.error("MathJax 렌더링 오류:", err);
                    });
                }, 100 * index);
            });
        });
    });

        </script>

        <style>
        #animated-text div {
          font-size: 16px;
          opacity: 0; /* 초기 상태에서는 텍스트가 보이지 않도록 설정 */
          transform: translateY(-20px); /* 약간 위에서 시작하도록 설정 */
          transition: all 0.5s ease-in-out; /* 부드러운 효과를 위한 전환 설정 */
        }
        </style>';
      }
else
    {
    $preparegpt=$cnttext.'내용을 세밀하게 검토해줘. ';
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


$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro= '<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';
$avartarimg='https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chatgpt.png';
$showpage= '<table width=100% align=center><tr><td>Welcome 페이지 홈</td></tr><tr><td>공부를 시작할 때 효과적인 플러그인들을 추가하여 학습의 흐름을 원활하게 할 수 있습니다.</td></tr><tr><td>학습을 촉진시킬 감정엔진을 만들어보세요</td></tr>
<tr><td>플러그인을 추가해 주세요 (+)</td></tr></table>'; // 기본 컨텐츠
$pagewelcome='Welcome ! Mathking에 오신 것을 환영합니다.';

$answerShort=false;
$count=2;
$rolea='';
$roleb='';
$talka1='학생이 질문합니다';
$talkb1='멘토가 답합니다';
$tone1='아주중요해1 -  각 내용은 따옴표로 감싸고 반드시 컴마로 분리해, 도입부 및 마무리 등의 내용 제거하고 예시의 형식 외의 다른 내용은 아예 표시하지 말아줘. 안 그러면 크게 혼난다. 예시 : "1. 사전지식 : 필요한 기본지식 나열", "2. 고유지식 : 이부분에만 등장하는 특별한 세부 지식", "3. 작업기억 연결 확인질문 3가지 <br>  - 내용<br> - 내용<br> - 내용<br> ", "4. 관심연결 - 실생활 응용 <br>- 자연현상 <br>- 주변관찰 <br>- 게임개발  "';
$tone2='아주중요해2 - latex 사용. 수식은 항상 다른 기호로 입력이 이루어진 경우 $ 로 시작 $ 로 끝나도록 생성.  안 그러면 크게 혼난다.';

$part1='<table width=100%><tr><td width=50% valign=top>
<div id="typing-container"><div><table align=left valign=top><tr><td valign=top><a href="'.$changeurl.'"><img src="'.$avartarimg.'"  width=100></a> </td><td valign=bottom></td></tr></table>
</div></div><table width=100% style="font-size:16px;">'.$chathistory.'</table>
<table align=center width=90%><tr><td>
<div id="typing-box">
<div id="typing-text"></div><div id="animated-text"></div>
<div id="typing-cursor"></div>
</div></td></tr></table> <br>';

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
         temperature: 1,
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
<div><table align=center width=90%><tr><td width=3%><button class="submit-button3" id="startTalk" onclick=""><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/sendicon.png width=30></button></td><td align="center"><input height=50px type="text" class="form-control input-square" id="input-text" name="squareInput"  placeholder="선택부분 상세 설명보기 클릭" value="'.$preparegpt.'" ></td><td width=5%></td><td> <button style = "z-index:2;background-color:#C4C4C4;" onclick="saveResult();">저장</button>  <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&cntid='.$thiscnt->id.'&cnttype=8&gid='.$gid.'"target="_blank"><img src=https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png width=20></a></td></tr></table><table width=90% align=center><tr><td>
<div id="output-text"></div> </td></tr></table></div></body></html>';
include("style.php");

echo '
<script>
function saveResult()
  {
    var Contentsid= \''.$cntid.'\';
    var Contentstype= \''.$cnttype.'\';
    var Wboardid= \''.$wboardid.'\';
    var Gid= \''.$gid.'\';
    var Outputtext =document.getElementById("output-text").textContent;
    $.ajax({
      url:"check_status.php",
      type: "POST",
      dataType:"json",
      data : {
      "eventid":\'2\',
      "contentsid":Contentsid,
      "contentstype":Contentstype,
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
        setTimeout(function(){location.reload();window.close("","_parent","");},1000);

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
    background-color: #white; /* Dark green */
  }

</style>
';
?>
<style>
	        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: block;
            background-color: #4CAF50;
            background-image: url('https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chatgpt.png');
            background-size: cover;
            background-position: center;
        }
        #input-text {
            width: 100%;
            padding: 15px;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        #startTalk {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        #startTalk:hover {
            background-color: #45a049;
        }
        #audio-player {
            margin-top: 20px;
            width: 100%;
        }
        #audio-control {
            width: 100%;
        }
        #progress-container {
    width: 100%;
    background-color: #f0f0f0;
    border-radius: 5px;
    margin-top: 20px;
    display: none;
}
#progress-bar {
    width: 0;
    height: 20px;
    background-color: #4CAF50;
    border-radius: 5px;
    transition: width 0.3s;
}
</style>
