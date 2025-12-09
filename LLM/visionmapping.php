<?php
require_once(dirname(__DIR__) . '/config/api_keys.php');
$openai_api_key = get_openai_api_key();

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
- ${role ? "당신은 학생이다" : "당신은 비전코칭 멘토다."}
- ${role ? "당신은 계획에 대해 이야기한다." : "당신은 학생에게 인공지능 시대 랜덤꿈을 제시한다."}
- ${role ? "공감하고 제시대로 개선하는 번뜩이는 아이디어를 말한다." : "위험을 체크해주고 격려하며 대화를 마무리한다."}
- 친절하고 다정한 대화. 입력된 내용에 따라 오늘 목표는 오늘에 집중하는 방식으로 피드백. 주간목표는 참고만하고 피드백은 하지마.
- 500자 이내로. 학생은 질문하지 않는다. 불확실한 정보는 추측으로 피드백 하지말고 학생 스스로 성찰하도록 열린질문을 던져줘.
- 이모티콘 듬뿍 쓰고 친근하고 재미있게 반말로. 친한친구처럼.
`;

const chatgpt = async (messages, streamListener) => {
   const fetchoption = {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${apiKey}` },
      body: JSON.stringify({
         stream: true,
         model: "gpt-4o",
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
