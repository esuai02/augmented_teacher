<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[interface.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

$cjnchat=$DB->get_records_sql("SELECT * FROM mdl_abessi_cjntalk where userid='$userid' AND timecreated>'$aweekago' ORDER BY id DESC LIMIT 10");  
$result = json_decode(json_encode($cjnchat), True);
unset($value);
foreach(array_reverse($result) as $value)
	{
  $talkid=$value['id'];
  $sender=$value['userid'];
  $user= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$sender' ");
  $username=$user->lastname;
 // $chathistory.='<tr><td width=2%></td><td width=10%>'. $username.'</td><td>'.$value['text'].'</td></tr>';
  $DB->execute("UPDATE {abessi_cjntalk} SET mark='1'  WHERE id LIKE '$talkid' ");
	}  
 
  //include("../copilots.php");
  // $currentAnswer='안녕하세요. 수학이 어려워요'; 
 

$answerShort=false; 
$count=2;
$rolea='';
$roleb='';
$talka1='';
$talkb1='AI tutor';
$tone1=$finetuning;
if($avartarimg==NULL)$avartarimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png';


if($type!=='dialogue')//새로운 프롬프트
  {
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
 
</script>
';
  }

if($type==='popup')
$html0='<body>
<table width=100%><tr><td width=50% valign=top>
<div id="typing-container"><div id="teacher-image"><table align=center valign=top><tr><td align=center valign=top><img src="'.$avartarimg.'" alt="Teacher Image" width=50></td></tr></table>
</div></div>
  <br>
<table width=90% align=center><tr><td align="center"><input height=50px type="text" style="display:none;" class="form-control input-square" id="input-text" name="squareInput"  placeholder="추천 받은 프롬프트 또는 원하는 요청을 입력해주세요" value="'.$preparegpt.'" > </td><td>&nbsp;&nbsp;</td><td align=center width=100%> 
<button class="submit-button3" id="startTalk" onclick="">GPT 진단결과 보기</button>
</td></tr>
</table>';
else $html0='<body>
<table width=100%><tr><td width=50% valign=top>
<div id="typing-container"><div><table align=center valign=top><tr><td valign=top><img src="'.$avartarimg.'"  width=100></td></tr></table>
</div></div><table width=100% style="font-size:20px;">'.$chathistory.'</table>
<table align=center width=90%><tr><td>
<div id="typing-box">
<div id="typing-text"></div>
<div id="typing-cursor"></div>
</div></td></tr></table> <br>
<table width=90% align=center><tr><td align="center"><input height=50px type="text" class="form-control input-square" id="input-text" name="squareInput"  placeholder="추천 받은 프롬프트 또는 원하는 요청을 입력해주세요" value="'.$preparegpt.'" > </td><td>&nbsp;&nbsp;</td><td width=10%> 
<button class="submit-button3" id="startTalk" onclick=""><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/sendicon.png width=30></button>
</td></tr>
</table>';

$html1='<table width=90% align=center><tr><td>  
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
`;
//-----------------------------------
const chatgpt = async (messages, streamListener) => {
   const fetchoption = {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${apikey}` },
      body: JSON.stringify({
         stream: true,
         model: "gpt-4o", //model: "gpt-4o-mini",
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

  currentAnswer = document.querySelector("#input-text").value;
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


echo '<!DOCTYPE html> <html lang="en">
<head><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>';

if($type==='popup')
  {
  echo  $html0.$html1.'</td></tr></table><table width=90% align=center><tr><td> 
  <div id="output-text"></div> </td></tr></table>';
  }
else 
  {
  echo $html0.$html1.'</td></tr></table> <table width=90% align=center><tr><td> 
<div id="output-text"></div> </td></tr></table>
'.$typenew.'</td><td width=50% valign=top>'.$pageintro.'
<div id="updateDiv">'.$showpage.'</div><table align=center>'.$buttons.'</table></td></tr></table>';
  }

echo '</body></html>';

include("style.php");

echo '
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
    font-size: 18px;
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