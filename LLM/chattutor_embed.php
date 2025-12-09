<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[chattutor_embed.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

$headdesign='<table width=80%><tr><td></td><td><span id="nexttalk" align=center>NEXT</span></td></tr></table><hr>';
echo  $headdesign.' 
<script>
.subtitle {
   position: fixed;
   bottom: 20px;
   right: 20px;
   z-index: 9999;
   background-color: rgba(0, 0, 0, 0.5);
   color: white;
   font-size: 24px;
   padding: 10px;
   border-radius: 5px;
   display: none;
 }
</script>
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
- ${role ? \''.$talka1.'\' : \''.$talkb1.'\'}
- ${role ? \''.$talka2.'\' : \''.$talkb2.'\'} 
- ${role ? \''.$talka3.'\' : \''.$talkb3.'\'} 
- \''.$tone1.'\'
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
 // memory 
   async askFollowUpQuestion(streamListener) {
      const lastAssistantResponse = this.memory[this.memory.length - 1].content;
      const question = this.extractQuestion(lastAssistantResponse);
      if (question) {
         return await this.listenAndAnswer(question, streamListener);
      } else {
         throw new Error("No follow-up question found in the last assistant response.");
      }
   }

   extractQuestion(text) {
      const match = text.match(/[\?!\.](?:\s+|^)([A-Za-z][^\?]+?\?)/);
      return match ? match[1] : null;
   }
}

(async () => {
   const aiRobot = new AIRobot("user");
   const streamListener = (token) => console.log(token);
   const response1 = await aiRobot.listenAndAnswer("Tell me about GPT-4.", streamListener);
   console.log("Response 1:", response1);
   const response2 = await aiRobot.askFollowUpQuestion(streamListener);
   console.log("Response 2:", response2);
})();


const makeLine = (currentParticipant, currentParticipantIndex) => {
   const conversationLine = document.createElement("div");

   conversationLine.style.display = "flex";
   conversationLine.style.padding = "0px";
   conversationLine.style.position = "fixed"; // Added line
   conversationLine.style.bottom = "50px"; // Added line
   conversationLine.style.left = "10%"; // Modified line
   conversationLine.style.width = "80%"; // Added line
   conversationLine.style.zIndex = "9999"; // Added line
   conversationLine.style.backgroundColor = "transparent"; // Added line
   conversationLine.style.color = "#000"; // Added line
   conversationLine.style.fontSize = "20px"; // Added line
   const nameTag = document.createElement("div");
   nameTag.style.minWidth = "60px";
   nameTag.style.maxWidth = "60px";
   nameTag.style.overflow = "hidden";
   nameTag.style.color = "#000"; // Added line
   nameTag.innerText = currentParticipant.name;
   conversationLine.appendChild(nameTag);
   const opinionBox = document.createElement("div");
   opinionBox.style.border = "none";
   conversationLine.appendChild(opinionBox);
   const body = document.querySelector("body");
   body.appendChild(conversationLine);
   if (currentParticipantIndex === 0) {
      conversationLine.style.backgroundColor = "transparent"; // Modified line
      nameTag.style.backgroundColor = "#000"; // Modified line
   } else {
      conversationLine.style.backgroundColor = "transparent"; // Modified line
      nameTag.style.backgroundColor = "#000"; // Modified line
   }
   return { nameTag, conversationLine, opinionBox }
}

window.addEventListener("load", e => {

 //  document.querySelector("#summary").innerText = `${roleName(true)}:${(instruction(true))}\n${roleName(false)}:${(instruction(false))}`
   document.querySelector("#startTalk").addEventListener("click", async e => {
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
         //opinionBox.innerText = _currentAnswer;   
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
            return new Promise(resolve => setTimeout(resolve, 1));
         }
         
         let req = answerShort ? ". 짧게 대답하세요." : "";
         _currentAnswer = await currentParticipant.listenAndAnswer(_currentAnswer + req, streamListener);
         isPlayerTurn = !isPlayerTurn;
         _count--;
         await sleep(60);
         if (_count === 0) break;
      }
   });

});
 
function sleep(seconds) {
  const delay = seconds * 1000; // milliseconds로 변환
  
  const sleepPromise = new Promise(resolve => {
    setTimeout(resolve, delay);
  });

 const enterPromise = new Promise(resolve => {
  document.getElementById("nexttalk").addEventListener("click", function() {
    resolve();
  });
});
  
  return Promise.race([sleepPromise, enterPromise]);
}

const clearOutput = () => {
   const body = document.querySelector("body");
   const conversationOutputs = body.querySelectorAll(".conversation-output");
   conversationOutputs.forEach(child => {
      body.removeChild(child);
   });
};
</script>';

?>