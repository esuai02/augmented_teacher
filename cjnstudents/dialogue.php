<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[dialogue.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

 /*
$answerShort = false; // 짧게 대답할지
$count = 7; // 대화의 횟수 
$currentAnswer = "질문있어요"; // 첫 마디
$rolea='학생';
$roleb='선생님';
$talk1a='당신은 초등학생이다.';
$talk1b='당신은 수학선생님이다.';
$talk2a='당신은 소인수분해에 대해 질문한다.';
$talk2b='당신은 초등학교 수준으로 쉽게 설명해준다.';
$talk3a='어려워 비유해서 설명하는 것을 요청한다.';
$talk3b='친절하고 재미있게 설명해준다.';
$tone1='재미있게 대화해';
$tone2='친군한 표현으로 해';
  

$answerShort = false; // 짧게 대답할지
$count = 10; // 대화의 횟수 
$currentAnswer = "우리 어떻게 태훈이의 미래를 밝게 도와줄까 ?"; // 첫 마디
$rolea='메타인지 코파일럿';
$roleb='수학 선생님';
$talk1a='당신은 메타인지를 돕는 선생님이다.';
$talk1b='당신은 수학공부를 돕는 선생님이다.';
$talk2a='당신은 메타인지를 돕는 방식으로 어떻게 수학공부를 도울지에 대해 상의하고 싶어한다..';
$talk2b='당신은 수학교사로서 태훈이의 수학공부가 메타인지적 도움이 필요하다고 느낀다.';
$talk3a='어려워 비유해서 설명하는 것을 요청한다.';
$talk3b='친절하고 재미있게 설명해준다.';
$tone1='태훈이에 대한 직접적인 애정과 관심을 토대로 재미있게 대화해. 반말로.';
$tone2='태훈이는 대화에 참가하지 않고 두 선생님이 친근한 표현으로 태훈에애 대해 이야기';

 $answerShort = false; // 짧게 대답할지
$count = 10; // 대화의 횟수 
$currentAnswer = "우리 어떻게 태훈이의 미래를 밝게 도와줄까 ?"; // 첫 마디
$rolea='비전코칭 선생님';
$roleb='수학 선생님';
$talk1a='당신은 진로와 비전발견을 돕는 선생님이다.';
$talk1b='당신은 수학공부를 돕는 선생님이다.';
$talk2a='당신은 비전코칭을 하는 방식으로 어떻게 수학공부를 도울지에 대해 상의하고 싶어한다..';
$talk2b='당신은 수학교사로서 태훈이가 미래에 대한 비전을 발견한다면 수학공부에 도움이 된다고 느낀다.';
$talk3a='어려워 비유해서 설명하는 것을 요청한다.';
$talk3b='친절하고 재미있게 설명해준다.';
$tone1='태훈이에 대한 애정과 관심을 토대로 재미있게 대화해. 반말로.';
$tone2='태훈이는 대화에 참가하지 않고 두 선생님이 친근한 표현으로 태훈에애 대해 이야기';
 

$answerShort = false; // 짧게 대답할지
$count = 6; // 대화의 횟수 
$currentAnswer = "태훈이의 오늘목표는 7단원 단원별 테스트를 통과하는 것입니다. 당신은 결과를 어떻게 예측하시나요 ?"; // 첫 마디
$rolea='GPT_A';
$roleb='GPT_B';
$talk1a='당신은 태훈이가 오늘 공부를 실패할 거라 주장합니다.';
$talk1b='당신은 태훈이가 오늘 공부를 성공할 거라 주장합니다.';
$talk2a='당신의 태훈이의 함수에 대한 이전 학습이력을 토대로 성공할거라 다양한 의견을 제시합니다.';
$talk2b='당신의 태훈이의 계산실수 이력을 근거로 실패할거라 주장합니다.';
$talk3a='태훈이가 시험기간에 집중력이 향상되는 경향성을 토대로 성공에대한 전망을 얘기합니다.';
$talk3b='태훈이가 현실적인 어려움을 겪을 것을 내용을 구성하여 제시합니다.';
$tone1='대화의 마무리를 두 인공지능이 협력하여 태훈이를 돕기로 의기투합하는 모습';
$tone2='긍정요인 : 잠재력, 함수에 대한 지난 학습이력, 적극적인 질문, 중간점검, 적절한 휴식. 반말로 얘기. 20자.';
$tone3='부정요인 : 피로도, 판단실수, 질문에 대한 부담감, 시간에 대한 압박, 컨디션에 대한 인식 부족. 반말로 얘기. 20자.';
$talk4a='태훈이의 내재된 잠재력이 어떻게 위기극복을 할지 전망을 얘기합니다.';
$talk4b='이제 우리의 의견을 태훈이한테 전달할 준비가 된거 같네요 !';
//<hr>인사이드 아웃은 머릿속 감정 컨트롤 본부에서 일하는 기쁨, 슬픔, 버럭, 까칠, 소심 다섯 감정이 낯선 환경에서 힘든 시간을 보내고 있는 라일리에게 행복을 되찾아주기 위해 벌이는 모험을 그린다.<hr>

*/


$answerShort = false; // 짧게 대답할지
$count = 6; // 대화의 횟수 
$currentAnswer = "나 너 좋아해! 사귀자!"; // 첫 마디
$rolea='남자';
$roleb='여자';
$talk1a='당신은 태훈이가 오늘 공부를 실패할 거라 주장합니다.';
$talk1b='당신은 태훈이가 오늘 공부를 성공할 거라 주장합니다.';
$talk2a='당신의 태훈이의 함수에 대한 이전 학습이력을 토대로 성공할거라 다양한 의견을 제시합니다.';
$talk2b='당신의 태훈이의 계산실수 이력을 근거로 실패할거라 주장합니다.';
$talk3a='태훈이가 시험기간에 집중력이 향상되는 경향성을 토대로 성공에대한 전망을 얘기합니다.';
$talk3b='태훈이가 현실적인 어려움을 겪을 것을 내용을 구성하여 제시합니다.';
$tone1='대화의 마무리를 두 인공지능이 협력하여 태훈이를 돕기로 의기투합하는 모습';
$tone2='긍정요인 : 잠재력, 함수에 대한 지난 학습이력, 적극적인 질문, 중간점검, 적절한 휴식. 반말로 얘기. 20자.';
$tone3='부정요인 : 피로도, 판단실수, 질문에 대한 부담감, 시간에 대한 압박, 컨디션에 대한 인식 부족. 반말로 얘기. 20자.';
$talk4a='태훈이의 내재된 잠재력이 어떻게 위기극복을 할지 전망을 얘기합니다.';
$talk4b='이제 우리의 의견을 태훈이한테 전달할 준비가 된거 같네요 !';
//<hr>인사이드 아웃은 머릿속 감정 컨트롤 본부에서 일하는 기쁨, 슬픔, 버럭, 까칠, 소심 다섯 감정이 낯선 환경에서 힘든 시간을 보내고 있는 라일리에게 행복을 되찾아주기 위해 벌이는 모험을 그린다.<hr>
/*
   본 소스코드는 GPT API를 활용하여 두 AI끼리 대화를 하게 해보는 코드입니다.
   소스코드 상에서 "API-KEY" 부분에 API를 발급받아서 넣어서 GPT API에 요청할 수 있습니다
   API의 발급은 다음 웹페이지에서 가능합니다
   https://platform.openai.com/account/api-keys
   API에 요청에 따른 비용이 과금될 수 있는점과 API키가 노출되지 않도록 유의해주세요.
   특히 gpt-4의 가격차이는 gpt-3.5-turbo와 상당히 크다는 점도 참고바랍니다.
   자세한 가격정보는 https://openai.com/pricing 참고해주세요.
   프론트엔드 코드는 노출될 수 있으므로 API키를 프론트엔드 코드에 넣지 않도록 유의해주세요.
   본 소스코드는 학습을 위한 예시이므로 실제 서비스 개발시에는 이 예시에서처럼 프론트엔드 코드에 API키를 포함하지 않는것이 좋습니다.
   본 코드는 AI 코딩 어시스턴트 익스텐션인 https://aicodehelper.dev/ 의 도움을 받아 제작되었습니다.
 
   INSTRUCTIONS:
   - ${role ? '당신은 남자다.' : '당신은 여자다.'}
   - 친구와 대화해.
   - 반말로 해.
   - ${role ? '당신은 용기내어 고백한다.' : '당신은 고백에 놀랐고 고백받아서 부끄럽지만 기쁘다.'}
   - ${role ? '당신은 고백하고싶었으나 평소에 용기가 없었다.' : '당신은 평소에 호감이 있었다.'}
   `;
*/
echo '
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <h3 align=center>내 머릿 속의 인공지능 (실제상황) ㄷㄷㄷ<h3><hr>
  <table align=center><tr><td><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/insideout.png width=300></td></tr></table><br>
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

- ${role ? \'당신은 남자다.\' : \'당신은 여자다.\'}
- ${role ?  \''.$talk4a.'\' : \''.$talk4b.'\'}
- 친구와 대화해.
- 반말로 해.
- ${role ?  \'당신은 용기내어 고백한다.\' : \'당신은 고백에 놀랐고 고백받아서 부끄럽지만 기쁘다\'}
- ${role ?  \'당신은 고백하고싶었으나 평소에 용기가 없었다.\' : \'당신은 평소에 호감이 있었다.\'}
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