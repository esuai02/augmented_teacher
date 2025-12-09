<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[gpt.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

if($type==='dialogue')
  {
    $answerShort = false; // 짧게 대답할지
    $count = 4; // 대화의 횟수 
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
 
    echo '
    <!DOCTYPE html>
    <html lang="en">
    
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Document</title>
      <h3 align=center>CHATBOT<h3><hr>
      <table align=center><tr><td><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/insideout.png width=200></td></tr></table><br>
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
    
     <span align=center id="startTalk"> &nbsp;&nbsp;&nbsp;&nbsp;GPT ChatBot</span> 
    
    </body>
    <pre id="summary"></pre>
    
    </html>
    
    <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
    <script>
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
     
    
    */
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
    - ${role ?  \''.$talk2a.'\' : \''.$talk2b.'\'}
    - ${role ?  \''.$talk3a.'\' : \''.$talk3b.'\'}
    - ${role ?  \''.$talk4a.'\' : \''.$talk4b.'\'}
    - ${role ?  \''.$talk5a.'\' : \''.$talk5b.'\'}
    `;
    //-----------------------------------
    const chatgpt = async (messages, streamListener) => {
       const fetchoption = {
          method: "POST",
          headers: { "Content-Type": "application/json", Authorization: `Bearer ${apikey}` },
          body: JSON.stringify({
             stream: true,
             model: "gpt-4o-mini",
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
  }
else
  {
  echo '
  <!DOCTYPE html>
  <html>
  
  <head>
      <title>GPT 요청</title>
      <style>
          body {
              background-color: black;
              color: white;
              font-size: 20px;
          }

          #output-text {
              margin-top: 20px;
              padding: 10px;
              border: 1px solid white;
          }
      </style>
  </head>

  <body> 
      <script>
          const chatGPT = async (messages, parameters = {}) => {
              const apikey = "'.$openai_api_key.'";
              if (messages[0].constructor === String) return await chatGPT([[\'user\', messages[0]]]);
              messages = messages.map(line => ({ role: line[0], content: line[1].trim() }))
              console.log(1)
              const response = await fetch(\'https://api.openai.com/v1/chat/completions\', {
                method: \'POST\',
                headers: { \'Content-Type\': \'application/json\', Authorization: `Bearer ${apikey}` },
                body: JSON.stringify({ model: \'gpt-3.5-turbo\', messages, ...parameters }),
              });
              const data = await response.json();
              if (data?.error?.message) throw new Error(data.error.message);
              return data.choices[0].message.content.trim();
          };
          
              
          async function showOutput(Finetuning) {
              var outputText = document.getElementById("output-text");
              outputText.innerHTML="응답을 생성 중입니다";
              var inputText = Finetuning + " : " + document.getElementById("input-text").value.trim();
              if (!inputText) {
                alert(\'Please describe\');
                return;
              }
              inputText = `DESC::${inputText}`;
              let response;
              try {
                response = await chatGPT([
                  [\'system\', `The assistant\'s job is to describe given topic. reasonForRecommendation to be in korean. Remove pre-text and post-text.`],
                  [\'user\', inputText],
                  ], { temperature: 0.8 });
                } catch (e) {
                  console.log(e.message);
                  return;
                }

                outputText.innerHTML = response;
              }
              
        </script>
    
    </body>

    </html>'; 
  }
/* 어울리는 생상 추천
  <script>
         const chatGPT = async (messages, parameters = {}) => {
            const apikey = "'.$openai_api_key.'";
            if (messages[0].constructor === String) return await chatGPT([[\'user\', messages[0]]]);
            messages = messages.map(line => ({ role: line[0], content: line[1].trim() }))
            console.log(1)
            const response = await fetch(\'https://api.openai.com/v1/chat/completions\', {
               method: \'POST\',
               headers: { \'Content-Type\': \'application/json\', Authorization: `Bearer ${apikey}` },
               body: JSON.stringify({ model: \'gpt-3.5-turbo\', messages, ...parameters }),
            });
            const data = await response.json();
            if (data?.error?.message) throw new Error(data.error.message);
            return data.choices[0].message.content.trim();
         };
        async function showOutput() {
            var inputText = "수학에 대한 것을 먼저 고려. 진지하지 않은 톤으로 답해줘."+ document.getElementById("input-text").value.trim();
            if(!inputText)alert(\'묘사해주세요\')
            inputText = `DESC::${inputText}`
            let response
            try{
                response = await chatGPT([
                    [\'system\', `The assistant\'s job is to recommend color codes that match what user\'s describing. Response JSONArray like ["","",...]. reasonForRecommendation to be in korean. Return only JSON Array. Remove pre-text and post-text.`],
                    [\'user\', \'DESC::맛있는 딸기\'],
                    [\'assistant\', \'{"reasonForRecommendation":"..","colorlist":["#000000","#000000","#000000","#000000","#000000"]}\'],
                    [\'user\', \'DESC::우거진 숲속의 소나무\'],
                    [\'assistant\', \'{"reasonForRecommendation":"...","colorlist":["#000000","#000000","#000000","#000000","#000000"]}\'],
                    [\'user\', \'DESC::드넓은 사막의 모래\'],
                    [\'assistant\', \'{"reasonForRecommendation":"....","colorlist":["#000000","#000000","#000000","#000000","#000000"]}\'],
                    [\'user\', inputText],
                ], { temperature: 0.8 })
            }catch(e){
               console.log(e.message)
               return;
            }
            var outputText = document.getElementById("output-text");
            console.log(response)
            const color = JSON.parse(response);
            for (let i = 0; i < color.colorlist.length; i++) {
                const divElem = document.createElement(\'div\');
                divElem.style.backgroundColor = color.colorlist[i];
                divElem.textContent = color.colorlist[i];
                outputText.appendChild(divElem);
            }
            const divElem = document.createElement(\'div\');
            divElem.textContent = color.reasonForRecommendation
            outputText.appendChild(divElem);
        }
    </script>
*/
?>