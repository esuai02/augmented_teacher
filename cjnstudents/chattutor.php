<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[chattutor.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

/*
 $answerShort = false; // 짧게 대답할지
 $count = 6; // 대화의 횟수 
 $currentAnswer = '근의 공식에 대해 알고 싶습니다.'; // 첫 마디
 $rolea='A';
 $roleb='B';
 $talk1a='근의 공식에 대해 알고 싶습니다.';
 $talk1b='';
 $tone1='직설적인 어투';
 $tone2='구체적인 대화, 예를 들어서 설명';
*/

$answerShort=$_GET['answerShort']; 
$count=$_GET['count'];
$currentAnswer=$_GET['currentAnswer']; 
$rolea=$_GET['rolea'];
$roleb=$_GET['roleb'];
$talk1a=$_GET['talk1a'];
$talk1b=$_GET['talk1b'];
$tone1=$_GET['tone1'];
$tone2=$_GET['tone2'];

 
echo '
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <h3 align=center>동작기억에 인공지능 탑재<h3><hr>
  <table align=center><tr><td><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/insideout.png width=100></td></tr></table><br>
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

 <span align=center id="startTalk"> &nbsp;&nbsp;&nbsp;&nbsp;GPT 이제 수학공부까지 ? 실제하는 학원 ㄷㄷㄷ</span> 

</body>
<pre id="summary"></pre>

</html>

<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script>
 
//-----------------------------------
const apikey = "'.$openai_api_key.'"; // API-KEY from env
const answerShort = \''.$answerShort.'\';  // 짧게 대답할지
const count = \''.$count.'\'; // 대화의 횟수 
const currentAnswer = \''.$currentAnswer.'\'; // 첫 마디
const roleName = role => role ? \''.$rolea.'\' : \''.$roleb.'\'; // 역할의 이름
const instruction = role => `
INSTRUCTIONS: 
- \''.$tone1.'\'
- \''.$tone2.'\'
- ${role ? \''.$talk1a.'\' : \''.$talk1b.'\'}
 
`;
//-----------------------------------
const chatgpt = async (messages, streamListener) => {
   const fetchoption = {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${apikey}` },
      body: JSON.stringify({
         stream: true,
         model: "gpt-3.5-turbo",
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

 //  document.querySelector("#summary").innerText = `${roleName(true)}:${(instruction(true))}\n${roleName(false)}:${(instruction(false))}`


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
         opinionBox.innerText = _currentAnswer;
      }
      let _count = count - 1;
      while (_count > 0) {
         let currentParticipantIndex = Number(!isPlayerTurn);
         const currentParticipant = participations[currentParticipantIndex];
         const { opinionBox, nameTag, conversationLine } = makeLine(currentParticipant, currentParticipantIndex);
         const streamListener = token => {
            const body = document.querySelector("body");
            const opNode = document.createTextNode(token);
            opinionBox.appendChild(opNode);
            window.scrollTo(0, body.scrollHeight)
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
});</script>';

?>