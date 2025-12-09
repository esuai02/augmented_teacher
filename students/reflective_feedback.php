<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[reflective_feedback.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;

// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘..
$userid=$_GET["userid"];
if($userid==NULL)$userid=$USER->id;
$trackingid=$_GET["tid"];  // 제거
$attemptid=$_GET["attemptid"]; 
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800; 
$timecreated=time();

$thislog=$DB->get_record_sql("SELECT * FROM mdl_abessi_reflections WHERE attemptid ='$attemptid' ");  
$wboardid = 'quizattempt'.$attemptid;


$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

if($role==='student')$resetbtn='';
else $resetbtn='<button onclick="resetChecklist();">초기화</button>';


$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_bhtracking WHERE trackingid LIKE '$trackingid' ORDER BY id DESC LIMIT 1 ");

if($trackingid==NULL)$trackingid=$thislog->trackingid;   

echo '
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
';



if($thislog->id!=NULL)
    { 
    $resulttext=$thislog->resulttext;
    //echo $resulttext; 
    echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>동적 체크리스트</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        .checklist {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        h2 {
            color: #3498db;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        li {
            margin-bottom: 10px;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }
        label {
            cursor: pointer;
        }
        label:hover {
            color: #3498db;
        }
        textarea {
            width: 100%;
            height: 200px;
            margin-bottom: 10px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body> 
<textarea id="inputText" style="display:none;" placeholder="여기에 체크리스트 내용을 입력하세요..."></textarea>
<div id="checklistContainer"></div>
<script>
function resetChecklist() 
{
  var Thisrowid= "'.$thislog->id.'";
  //alert(Thisrowid);
  $.ajax({
      url: "../cjnstudents/check_status.php",
      type: "POST",
      dataType: "json",
      data: {
          "eventid":"3", 
          "thisrowid": Thisrowid,
      },
      success: function(data) {
        location.reload();  
      }
  });
} 
</script>

<script> 

    const initialInputText =`'.$resulttext.'`;
    var Trackingid= \''.$trackingid.'\';

    function createChecklist(data) {
        const container = document.getElementById("checklistContainer");
        container.innerHTML = "";

        const checkStatuses = {
            "1": \''.$exist->check1.'\',
            "2": \''.$exist->check2.'\',
            "3": \''.$exist->check3.'\',
            "4": \''.$exist->check4.'\',
            "5": \''.$exist->check5.'\',
            "6": \''.$exist->check6.'\',
            "7": \''.$exist->check7.'\',
            "8": \''.$exist->check8.'\',
            "9": \''.$exist->check9.'\',
        };

        const mainTitle = document.createElement("h1");
        mainTitle.textContent = data.title;
        container.appendChild(mainTitle);

        data.sections.forEach((section, sectionIndex) => {
            const sectionDiv = document.createElement("div");
            sectionDiv.className = "checklist";
            
            const sectionTitle = document.createElement("h2");
            sectionTitle.textContent = `${sectionIndex + 1}. ${section.title}`;
            sectionDiv.appendChild(sectionTitle);
            
            const ul = document.createElement("ul");
            section.items.forEach((item, itemIndex) => {
                const li = document.createElement("li");
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                if(sectionIndex==0)checkbox.id = `${itemIndex+1}`;
                else checkbox.id = `${itemIndex+7}`;

                checkbox.checked = false;
                if (checkStatuses[checkbox.id]==1) {
                    checkbox.checked = true;
                }
                


                const label = document.createElement("label");
                label.htmlFor = checkbox.id;
                label.textContent = item;
                
                // 이벤트 리스너 추가
                checkbox.addEventListener("change", function() {
                    const isChecked = this.checked; // 체크 여부
                    const checkboxId = this.id;     // 체크박스 아이디
                    const itemText = label.textContent; // 항목 텍스트
                    swal("","저장되었습니다.", {buttons: false,timer:50});

                    // AJAX로 체크 상태 전송
                    var checkimsi = 0;
                    if(isChecked==true){
                        checkimsi = 1;
                    }
                    $.ajax({
                        url: "check_status.php",
                        type: "POST",
                        dataType: "json",
                        data: {
                            "eventid": "4", 
                            "trackingid": Trackingid,
                            "checkboxid": checkboxId,
                            "checkimsi": checkimsi,
                            "itemtext": itemText
                        },
                        success: function(data) {
                            // 성공 시 처리
                            console.log("체크 상태가 저장되었습니다.");
                            // 필요 시 페이지 새로고침
                            // location.reload();  
                        },
                        error: function(xhr, status, error) {
                            // 에러 처리
                            console.error("체크 상태 저장 중 오류 발생:", error);
                        }
                    });
                });
                
                li.appendChild(checkbox);
                li.appendChild(label);
                ul.appendChild(li);
            });
            
            sectionDiv.appendChild(ul);
            container.appendChild(sectionDiv);
        });

        if (data.footer) {
            const footer = document.createElement("p");
            footer.textContent = data.footer;
            container.appendChild(footer);
        }
    }



        function parseInput(inputText) {
            const lines = inputText.split("\n");
            let data = {
                title: "",
                sections: [],
                footer: ""
            };
            let currentSection = null;

            lines.forEach((line, index) => {
                line = line.trim();
                if (index === 0) {
                    data.title = line.replace("체크리스트: ", "");
                } else if (line.match(/^\d+\./)) {
                    currentSection = {
                        title: line.replace(/^\d+\.\s*/, ""),
                        items: []
                    };
                    data.sections.push(currentSection);
                } else if (line.startsWith("- [ ]") && currentSection) {
                    currentSection.items.push(line.replace("- [ ] ", ""));
                } else if (line && index === lines.length - 1) {
                    data.footer = line;
                }
            });

            return data;
        }

        function processInputAndCreateChecklist() {
            const inputText = document.getElementById("inputText").value;
            const checklistData = parseInput(inputText);
            createChecklist(checklistData);
        }
       

    
        // 페이지 로드 시 초기 데이터 설정
        window.onload = function() {
            document.getElementById("inputText").value = initialInputText;
        };

         setTimeout(function(){processInputAndCreateChecklist();},300);  	
         
    </script>

<table><tr><td style="font-size:30px;"></td><td><button><a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$userid.'"><b style="font-size:16px;color:white;">✏️제출하기</b></a></button> '.$resetbtn.'</td></tr></table>
</body>
</html>';

echo '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> 
';

 exit();
    }
else 
    { 
    $tbegin=$context->timecreated;
    $tend=$context->duration;
	  
	  $handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE attemptid LIKE '$attemptid' AND contentstitle LIKE 'incorrect' AND active='1' ");
      //$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$userid' AND active='1' AND timemodified > '$tbegin' AND timemodified < '$tend'   ORDER BY timecreated DESC LIMIT 100");
      $result = json_decode(json_encode($handwriting), True);
      unset($value); 
      $nfix=0;
      foreach(array_reverse($result) as $value) 
        {
        $nfix++;
        $contentstype=$value['contentstype'];
        $contentsid=$value['contentsid'];

        $cnttext = $DB->get_record_sql("SELECT mathexpression FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1 ");
        $cntprompt.='오답'.$nfix.' : '.$cnttext->mathexpression.'<br>';         
        }

      if($nfix==0)
        {
        echo '<div style="display: flex; justify-content: center; align-items: center; height: 100vh; font-size: 20px;">분석할 오답이 없습니다.</div>';
        exit();
        }
    $preparegpt=' # 오답문항들 정보 : '.$cntprompt.' |  오답문항들 정보를 토대로 보충해야할 개념목록을 생성해주세요.';
    $initialtalk='수고하셨습니다. 이번 공부는 어떠셨나요 ? 소감한줄과 함께 간단한 체크포인트를 확인해 보시기 바랍니다.';
    $typenew= '<script>
    var text = "'.$initialtalk.'";
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
$talka1='너는 수학 테스트 오답문항들에 대한 정보들을 토대로 풀이과정에 있는 구체적인 공식과 개념에 대한 복습목록을 구체적으로 추천해주는 전문가야.';
$talkb1='';
$tone1='복습추천 주제 6개 항목, 추가활동 추천 3개 항목. 체크리스트 형식을 생성해서 체크하도록 해줘.. 

예시형식)
오답분석 후 활동 추천

1. 풀이과정 점검 및 약점분석
   - [ ] 내용1
   - [ ] 내용2
   - [ ] 내용3 
   - [ ] 내용4
   - [ ] 내용5
   - [ ] 내용6

2. 보충활동
   - [ ] 내용1
   - [ ] 내용2
   - [ ] 내용3 
';

$part1='<table width=80% align=center><tr><td width=50% valign=top>
<div id="typing-container"><div><table align=center valign=top><tr><td valign=top><a href="'.$changeurl.'"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chatgpt.png"  width=150></a> </td><td valign=bottom></td></tr></table>
</div></div><table align=center width=80% style="font-size:16px;">'.$chathistory.'</table>
<table align=center width=90%><tr><td>
<div id="typing-box">
<div id="typing-text"></div><div id="animated-text"></div>
<div id="typing-cursor"></div>
</div></td></tr></table> <br>
';

$part2='<table width=80% align=center><tr><td>  
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
const apikey = "<?php echo $openai_api_key; ?>"; // API-KEY from env
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
<div><table align=center width=90%><tr><td width=3%><button  class="submit-button3" id="startTalk" onclick=""><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/sendicon.png width=30></button></td><td align="center"><input height=250px type="text" class="form-control input-square" id="input-text" name="squareInput"  placeholder="선택부분 상세 설명보기 클릭" value="'.$preparegpt.'" ></td><td width=1%></td><td><button  id="savebtn" style = "z-index:2;background-color:#C4C4C4;" onclick="saveResult();">체크리스트 보기</button>  </td></tr></table><table width=90% align=center><tr><td> 
<div id="output-text"></div> </td></tr></table></div></body></html>';

    
echo ' 
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

echo '
<script>
    
// 페이지 로드 시 초기 데이터 설정
window.onload = function() {
setTimeout(function(){document.getElementById("startTalk").click();},1000);  
};
function saveResult()
  {   
    alert("aaa");
    var Userid= \''.$userid.'\'; 
    var Prompt= "오답문항 분석";  
    var Attemptid= \''.$attemptid.'\';
    var Trackingid= \''.$trackingid.'\';

   
    var Resulttext =document.getElementById("output-text").textContent; 
    swal("","체크리스트가 생성됩니다.", {buttons: false,timer:1500});
    $.ajax({
      url:"../cjnstudents/check_status.php",
      type: "POST", 
      dataType:"json",
      data : {
      "eventid":\'12\', 
      "userid":Userid,
      "prompt":Prompt,
      "attemptid":Attemptid,
      "resulttext":Resulttext,
    
      },
      success:function(data){		 
        var Trackingid2=data.thisTrackingid;
        var Url="https://mathking.kr/moodle/local/augmented_teacher/students/reflective_feedback.php?userid="+Userid+"&attemptid="+Attemptid+"&trackingid="+Trackingid2;
        setTimeout(function(){window.open(Url, "_self");},500);
        }
    })   
    
  }    
</script>';
 
?>