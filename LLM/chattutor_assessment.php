<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[chattutor_assessment.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
$contentstype=$_GET['contentstype'];
$contentsid=$_GET['contentsid'];

if($contentstype==1)
   {
   $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
   $pagetype='assessment';
   $cnttext1=$cnttext->maintext;  
   $description=$cnttext->description;  
   $currentAnswer='(1. 문제내용 :'.$cnttext1.' 2. 교과 단원 내용 : '.$description.'.   다음에 무엇을 해야할 지 모르겠어요. 힌트 하나만 주세요. 수식은 변환된 latex양식을 사용해 주세요. 30자 이내 ';
   }
elseif($contentstype==2)
   {
   $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
   $pagetype='assessment';
   $cnttext1=$cnttext->mathexpression;  
   $cnttext2=$cnttext->ans1;   
   $cnttext3=$cnttext->description;  
   $currentAnswer='(1. 문제내용 :'.$cnttext1.' 2. 해설지 : '.$cnttext2.'. 3. 단원내용 : '.$cnttext3.'. 문제가 잘 안 풀립니다. 힌트 좀 주세요. 수식은 변환된 latex양식을 사용해 주세요. 30자 이내 ';
   $currentAnswer='(1. 문제내용 :'.$cnttext1;
   }

$answerShort='false'; 
$count=4;
  
$rolea='학생';
$roleb='선생님';
$talka1='답변'; 
$talkb1='답변';
$tone1='답변';
$headdesign='<table width=80%><tr><td><h3 id="startTalk" align=center>대화시작<h3></td><td><span id="nexttalk" align=center>NEXT</span></td></tr></table><hr>';
echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML"></script>
  <title>GPT Tutor</title>
  '.$headdesign.'
   
</head>

<body> 
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
<pre id="summary"></pre>

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
  const nameTag = document.createElement("div");
  nameTag.style.minWidth = "60px";
  nameTag.style.maxWidth = "60px";
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
    nameTag.style.backgroundColor = "#444";
  } else {
    conversationLine.style.backgroundColor = "#222";
    nameTag.style.backgroundColor = "#333";
  }
  conversationLine.classList.add("conversation-output");
  return { nameTag, conversationLine, opinionBox };
};

window.addEventListener("load", () => {
  document.querySelector("#startTalk").addEventListener("click", async () => {
    clearOutput();
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
    });
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
      const streamListener = async token => {
        const opNode = document.createTextNode(token);
        opinionBox.appendChild(opNode);
        window.scrollTo(0, document.body.scrollHeight);
        document.body.style.marginBottom = "0px";
        await sleep(1);
      };

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
  const delay = seconds * 1000;
  const sleepPromise = new Promise(resolve => {
    setTimeout(resolve, delay);
  });

  const enterPromise = new Promise(resolve => {
    document.getElementById("nexttalk").addEventListener("click", function () {
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