<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[brainalignment.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

$pagetype=$_GET['pagetype'];
$wboardid=$_GET['wboardid'];
$gid=$_GET['gid'];
$ntalk=$_GET['ntalk'];
$answerShort=$_GET['answerShort']; 
$count=$_GET['count'];
$currentAnswer=$_GET['currentAnswer']; 
$rolea=$_GET['rolea'];
$roleb=$_GET['roleb'];
$talka1=$_GET['talka1'];
$talkb1=$_GET['talkb1'];
$talka2=$_GET['talka2'];
$talkb2=$_GET['talkb2'];
$talka3=$_GET['talka3'];
$talkb3=$_GET['talkb3'];
$tone1=$_GET['tone1'];
$tone2=$_GET['tone2']; 

$headdesign='';
echo ' <!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <!-- 배경색을 검정색으로 지정하는 CSS 코드 -->
  <style>
    body {
      background-color: black;
      /* body 태그의 배경색을 검정색으로 지정 */
      color: white;
      /* 글씨 색을 흰색으로 지정 */
      font-family: "Noto Sans KR", sans-serif;
      /* 폰트를 Noto Sans KR로 지정 */
    }

    div {
      border: 0px solid gray;
      padding: 5px;
      /* div 태그의 보더를 회색으로 지정 */
    }

    pre {
      white-space: pre-wrap;
    }
  </style>
</head>

<body>
<button style="display:none;" id="startTalk">대화시작</button>
<pre id="summary"></pre>
</body>
</html>

<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script>  
//-----------------------------------
//-----------------------------------
const apiKey = "'.$openai_api_key.'"; // API-KEY from env

const answerShort = true; // 짧게 대답할지
const count =2; // 대화의 횟수 
const currentAnswer = \''.$currentAnswer.'\'; // 첫 마디
const roleName = role => role ? \''.$rolea.'\' : \''.$roleb.'\'; // 역할의 이름
const instruction = role => `
INSTRUCTIONS:
- ${role ? "당신은 학생이다" : "당신은 수학선생님이다."}
- ${role ? "당신은 계획에 대해 이야기한다." : "당신은 학생의 목표를 상세하게 풀어서 해설해 주고 공부의 흐름을 쉽게 떠올릴 수 있도록 도와준다.수학교과 내용을 토대로 안내해줘. 수식을 절대 사용하지마.  "}
- 300자 이내로 작성하라. 마지막은 명언 한줄로 마무리하라.
`;
   
const chatgpt = async (messages, streamListener) => {
   const fetchoption = {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${apiKey}` },
      body: JSON.stringify({
         stream: true,
         model: "gpt-4o-mini",
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
   nameTag.style.minWidth = "120px";
   nameTag.style.maxWidth = "120px";
   // nameTag.style.fontSize = "9px";
   nameTag.style.overflow = "hidden";
   nameTag.innerText = currentParticipant.name;
   conversationLine.appendChild(nameTag);
   const opinionBox = document.createElement("div");
   opinionBox.style.border = "none";
   conversationLine.appendChild(opinionBox);
   const body = document.querySelector("body");
   body.appendChild(conversationLine);
   if (currentParticipantIndex === 0) {
      conversationLine.style.backgroundColor = "#333";
      nameTag.style.backgroundColor = "#444"; // Modified line
   } else {
      conversationLine.style.backgroundColor = "#222";
      nameTag.style.backgroundColor = "#333"; // Modified line
   }
   return { nameTag, conversationLine, opinionBox }
}
window.addEventListener("load", e => {

//document.querySelector("#summary").innerText = `${roleName(true)}:${(instruction(true))}\n${roleName(false)}:${(instruction(false))}`
   document.querySelector("#startTalk").addEventListener("click", async e => {
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
         opinionBox.innerHTML = _currentAnswer.replace(/\n/g, "<br>");
      }
      let _count = count - 1;
      while (_count > 0) {
         let currentParticipantIndex = Number(!isPlayerTurn);
         const currentParticipant = participations[currentParticipantIndex];
         const { opinionBox, nameTag, conversationLine } = makeLine(currentParticipant, currentParticipantIndex);

         const streamListener = token => {
            const body = document.querySelector("body");
            const opNode = document.createElement("span");
            const formattedToken = token.replace(/\n/g, "<br>").replace(/\*\*(.*?)\*\*/g, "");
            opNode.innerHTML = formattedToken + " ";
            opinionBox.appendChild(opNode);
            window.scrollTo(0, body.scrollHeight);
            document.body.style.marginBottom = "0px";
         }

         let req = answerShort ? ". 짧게 대답하세요." : "";
         _currentAnswer = await currentParticipant.listenAndAnswer(_currentAnswer + req, streamListener);
         isPlayerTurn = !isPlayerTurn;
         _count--;
         if (_count === 0) break;
      }
   });
});
</script>'; 


echo '<script>
setTimeout(function() {
  document.querySelector("#startTalk").click();
}, 100);
</script>';
?>