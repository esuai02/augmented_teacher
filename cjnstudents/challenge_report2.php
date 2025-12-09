<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[challenge_report2.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;

// (1) 스크롤 감지 및 코드 표시 + 마무리하기 버튼 노출을 위한 스크립트
// -------------------------------------------------------------
?>
<script>
// 스크롤이 끝까지 내려가면 코드 블록과 "마무리하기" 버튼 보이도록 처리
window.addEventListener('scroll', function() {
    var bottomReached = (window.innerHeight + window.scrollY) >= document.body.offsetHeight;
    if (bottomReached) {
        document.getElementById('hiddenCodeBlock').style.display = 'block';
        document.getElementById('finalButton').style.display = 'inline-block';
    }
});

// "마무리하기" 클릭시 saveResult() 함수를 호출 → 체크리스트 생성
function finalizeAction(){
    // 아래에서 정의된 saveResult() 함수를 호출함
    saveResult();
}
</script>
<?php
// -------------------------------------------------------------

$userid = $_GET["userid"];
if($userid == NULL) $userid = $USER->id;
$trackingid = $_GET["tid"];

$halfdayago = $timecreated - 43200;
$aweekago   = $timecreated - 604800;
$timecreated = time();

$thislog   = $DB->get_record_sql("SELECT * FROM mdl_abessi_reflections WHERE trackingid ='$trackingid' ");
$context   = $DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE id='$trackingid'");

$checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
                                  WHERE userid='$userid' 
                                    AND (type LIKE '오늘목표' OR type LIKE '검사요청') 
                                    AND timecreated > '$halfdayago' 
                                  ORDER BY id DESC LIMIT 1");

$rate = 0;
if($checkgoal->id == NULL) {
    echo '<br><br><br><br><br><br><table align=center><tr><td>
          먼저 오늘목표를 설정해 주세요. 
          <hr> 
          <a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'">
            목표 입력하기
          </a>
          </td></tr></table>';
    exit();
}

if($context->feedback != NULL) $feedback = ' # 선생님 코멘트 : '.$context->feedback;
$sessiontext = '# 오늘목표 : '.$checkgoal->text.'  # 현재 세션 내용 : '.$context->text.$feedback;
$wboardid    = 'reflection'.$context->id;

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data 
                                WHERE userid='$USER->id' 
                                  AND fieldid='22' ");
$role = $userrole->data;

if($role === 'student') $resetbtn = '';
else $resetbtn = '<button onclick="resetChecklist();">초기화</button>';

$exist = $DB->get_record_sql("SELECT * FROM mdl_abessi_bhtracking 
                             WHERE trackingid LIKE '$trackingid' 
                             ORDER BY id DESC LIMIT 1");

echo '
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
';


// ===== 이미 체크리스트가 존재하는 경우( $thislog->id != NULL ) → 바로 체크리스트 표시 =====
if($thislog->id != NULL)
{ 
    $resulttext = $thislog->resulttext;

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
      $.ajax({
          url: "check_status.php",
          type: "POST",
          dataType: "json",
          data: {
              "eventid":"3", 
              "thisrowid": Thisrowid
          },
          success: function(data) {
            location.reload();  
          }
      });
    } 
    </script>

    <script>
        const initialInputText = `'.$resulttext.'`;
        var Trackingid = \''.$trackingid.'\';

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
                "9": \''.$exist->check9.'\'
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
                    if(sectionIndex==0) checkbox.id = `${itemIndex+1}`;
                    else checkbox.id = `${itemIndex+7}`;

                    checkbox.checked = false;
                    if (checkStatuses[checkbox.id]==1) {
                        checkbox.checked = true;
                    }

                    const label = document.createElement("label");
                    label.htmlFor = checkbox.id;
                    label.textContent = item;
                    
                    // 체크박스 변경 이벤트
                    checkbox.addEventListener("change", function() {
                        const isChecked = this.checked; 
                        const checkboxId = this.id;     
                        const itemText  = label.textContent; 
                        swal("","저장되었습니다.", {buttons: false,timer:50});

                        // AJAX로 체크 상태 전송
                        var checkimsi = 0;
                        if(isChecked == true){
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
                                console.log("체크 상태가 저장되었습니다.");
                            },
                            error: function(xhr, status, error) {
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
            const lines = inputText.split("\\n");
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
                } else if (line.match(/^\\d+\\./)) {
                    currentSection = {
                        title: line.replace(/^\\d+\\.\\s*/, ""),
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

        window.onload = function() {
            document.getElementById("inputText").value = initialInputText;
        };
        setTimeout(function(){processInputAndCreateChecklist();},300);
    </script>

    <table>
    <tr>
        <td style="font-size:30px;"></td>
        <td>
            <button id="completebtn" style="background-color: #4CAF50; border: none; color: white; padding:2px 5px; text-align: center; font-size: 16px; cursor: pointer; height:45px; width:100px; border-radius: 10px;" onmouseover="this.style.backgroundColor=#45a049;" onmouseout="this.style.backgroundColor=#4CAF50;" ONCLICK="evaluateResult();">
                완료하기
            </button>  
            '.$resetbtn.'
        </td>
    </tr>
    </table>
    </body>
    </html>';

    // 만족도 평가 후 다음 페이지로 이동시키는 로직
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> 
    <style>
    .swal-footer {
      text-align: center !important;
    }
    .swal-footer .sa-button-container {
      display: inline-block !important; 
      margin: 0 5px !important;
    }
    .btn-sat1 {
      background-color: #b828d1; 
      color: #fff;
    }
    .btn-sat2 {
      background-color: #1fb4ff; 
      color: #fff;
    }
    .btn-sat3 {
      background-color: #f44336; 
      color: #fff;
    }
    </style>

    <script>
    function evaluateResult()
    {		 
      var Studentid= \''.$userid.'\';
      var CurrentTrackingId= \''.$trackingid.'\';
      var text1="매우 만족";
      var text2="만족";
      var text3="불만족";

      swal("구간 만족도",  "", {
        buttons: {
          catch1: {
            text: text1,
            value: "catch1",
            className : "btn-sat1"
          },
          catch2: {
            text: text2,
            value: "catch2",
            className : "btn-sat2"
          },
          catch3: {
            text: text3,
            value: "catch3",
            className : "btn-sat3"
          },
          cancel: {
            text: "취소",
            visible: false,
            className: "btn btn-alert"
          }, 
        },
      })
      .then((value) => {
        switch (value) {
          case "defeat":
            swal("취소되었습니다.", {buttons: false,timer: 500});
            break;

          case "catch1":
            swal("", text1 + "을 선택하였습니다.", {buttons: false,timer: 500});
            $.ajax({
              url: "../teachers/check.php",
              type: "POST",
              dataType: "json",
              data : {
                "eventid": "26",
                "userid": Studentid,
                "result": "3"
              },
              success:function(data){
                window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Studentid;
              }
            });
            break;
        
          case "catch2":
            swal("", text2 + "을 선택하였습니다.", {buttons: false,timer: 500});
            $.ajax({
              url: "../teachers/check.php",
              type: "POST",
              dataType: "json",
              data : {
                "eventid": "26",
                "userid": Studentid,
                "result": "2"
              },
              success:function(data){
                window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Studentid;
              }
            });
            break;
        
          case "catch3":
            swal("", text3 + "을 선택하였습니다.", {buttons: false,timer: 500});
            $.ajax({
              url: "../teachers/check.php",
              type: "POST",
              dataType: "json",
              data : {
                "eventid": "26",
                "userid": Studentid,
                "result": "1"
              },
              success:function(data){
                window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=" + Studentid;
              }
            });
            break;
        }
      });
    }
    </script>
    ';
    exit();
}
// ===== 체크리스트가 아직 없는 경우( $thislog->id == NULL ) → GPT 대화 후 체크리스트 생성 =====
else
{
    $tbegin = $context->timecreated;
    $tend   = $context->duration;
    $handwriting = $DB->get_records_sql("SELECT * FROM mdl_abessi_messages 
                                        WHERE userid='$userid' 
                                          AND active='1' 
                                          AND timemodified > '$tbegin' 
                                          AND timemodified < '$tend'   
                                        ORDER BY timecreated DESC LIMIT 100");
    $result = json_decode(json_encode($handwriting), True);
    unset($value); 
    $nwb = 0;
    $cntprompt = ''; // 초기화

    foreach(array_reverse($result) as $value) {
        $nwb++;
        $contentstype = $value['contentstype'];
        $contentsid   = $value['contentsid'];
        if($contentstype == 1) {
            $cnttext = $DB->get_record_sql("SELECT maintext FROM mdl_icontent_pages WHERE id ='$contentsid' ");
            $cntprompt .= $cnttext->maintext;
        } 
        elseif($contentstype == 2) {
            $cnttext = $DB->get_record_sql("SELECT reflections1 FROM mdl_question WHERE id='$contentsid' ");
            $cntprompt .= $cnttext->mathexpression;
        }
    }
    $DB->execute("UPDATE {abessi_tracking} SET nwboard='$nwb'  
                  WHERE id='$trackingid'   
                  ORDER BY id DESC LIMIT 1");

    $preparegpt   = $sessiontext.' # 구체적인 학습내용들 : '.$cntprompt.' |  이해도 평가 체크리스트 생성';
    $initialtalk  = '수고하셨습니다. 이번 공부는 어떠셨나요 ? 소감한줄과 함께 간단한 체크포인트를 확인해 보시기 바랍니다.';
    $typenew = '
    <script>
    var text = "'.$initialtalk.'";
    var lines = text.split("\\n");
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

    // 챗봇 관련 변수
    $answerShort = false; 
    $count       = 2;
    $rolea       = '';
    $roleb       = '';
    $talka1      = '너는 1시간 이내의 단기 집중 세션에 대해 평가하는 수학공부 피드백 전문가야';
    $talkb1      = '';
    $tone1       = '체크리스트 형식을 생성해서 체크하도록 해줘. 예, 아니오로 대답 가능한 목록 제시해줘. 공부한 내용과 관련된 구체적인 이해 점검 3개 항목. 수식은 최소화해줘. 주관식 답변 필요한 항목 배제해줘.

예시형식)
해당 내용을 체크해 주세요

1. 공부내용 점검
   - [ ] 내용1
   - [ ] 내용2
   - [ ] 내용3
';

    $part1 = '
    <table width=80% align=center>
    <tr>
        <td width=50% valign=top>
            <div id="typing-container"></div>
            <table align=center width=80% style="font-size:16px;">
                '.$chathistory.'
            </table>
            <table align=center width=90%>
            <tr>
                <td>
                <div id="typing-box">
                    <div id="typing-text"></div>
                    <div id="animated-text"></div>
                    <div id="typing-cursor"></div>
                </div>
                </td>
            </tr>
            </table>
            <br>
        </td>
    </tr>
    </table>';

    $part2 = '
    <table width=80% align=center><tr><td>  
    <style> 
      div {
        border: 1px solid white;
        padding: 5px;
      }
      pre {
        white-space: pre-wrap;
      }
    </style> 
    <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
    <script>
    //-----------------------------------
    const apikey = "<?php echo $openai_api_key; ?>"; // API-KEY from env
    const answerShort = \''.$answerShort.'\'; 
    const count = \''.$count.'\'; 
    const roleName = role => role ? \''.$rolea.'\' : \''.$roleb.'\'; 
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
             model: "gpt-4o-mini",
             messages,
             temperature: 0
          })
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
             let dataList = data.trim().split("\\n");
             for (let line of dataList) {
                line = line.trim();
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
      nameTag.style.overflow = "hidden";
      nameTag.innerText = currentParticipant.name;
      conversationLine.appendChild(nameTag);
      const opinionBox = document.createElement("div");
      const outputText = document.querySelector("#output-text");
      outputText.appendChild(conversationLine);
      conversationLine.classList.add("conversation-output");
      return { nameTag, conversationLine, opinionBox }
    }

    window.addEventListener("load", e => {
      document.querySelector("#startTalk").addEventListener("click", async e => {
        currentAnswer = "'.$firstdescription.'" + document.querySelector("#input-text").value;
        const outputText = document.querySelector("#output-text");
        outputText.innerHTML = "";
        clearOutput();
        let _currentAnswer = currentAnswer;
        const participations = [];
        [true, false].forEach(role => {
           const position = {
              role: "system",
              content: instruction(role)
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
             const outputText = document.querySelector("#output-text");
             const opNode = document.createTextNode(token);
             outputText.appendChild(opNode);
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
        // 대화 끝나면 체크리스트 보기(saveResult) 자동 호출
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

// 최종 화면 출력
echo '<!DOCTYPE html>
<html>
<head>
<!-- MathJax 3 start -->
<script>
MathJax = {
  tex: {
    inlineMath:[["$","$"],["$$","$$"],["\\(","\\)"]]
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
<body>
'.$part1.$part2.'
</td></tr></table>
'.$typenew.'
<table width=90% align=center><tr></table>

<div>
  <table align=center width=90%>
  <tr>
    <td width=3%>
      <button style="display:none;" class="submit-button3" id="startTalk" onclick="">
        <img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/sendicon.png" width=30>
      </button>
    </td>
    <td align="center">
      <input height=250px type="text" class="form-control input-square" id="input-text" name="squareInput"  
             placeholder="선택부분 상세 설명보기 클릭" value="'.$preparegpt.'">
    </td>
    <td width=1%></td>
    <td>
      <button style="display:none;" id="savebtn" style="z-index:2;background-color:#C4C4C4;" onclick="saveResult();">
        체크리스트 보기
      </button>
    </td>
  </tr>
  </table>
  <table width=90% align=center>
  <tr><td>
    <div id="output-text"></div>
  </td></tr>
  </table>
</div>

<script>
// 페이지 로드 시 자동 대화 시작
window.onload = function() {
   setTimeout(function(){document.getElementById("startTalk").click();},1000);  
};

function saveResult()
{  
  var Userid      = \''.$userid.'\'; 
  var Prompt      = "활동결과 성찰하기";  
  var Trackingid  = \''.$trackingid.'\';
  var Rate        = \''.$rate.'\';  

  var Resulttext  = document.getElementById("output-text").textContent; 
  swal("","체크리스트가 생성됩니다.", {buttons: false,timer:1500});

  $.ajax({
    url:"check_status.php",
    type: "POST", 
    dataType:"json",
    data : {
      "eventid": "11", 
      "userid": Userid,
      "prompt": Prompt,
      "trackingid": Trackingid,
      "resulttext": Resulttext,
      "rate": Rate
    },
    success:function(data){    
      var thisuserid = data.thisuserid;       
      Swal.fire({
        position:"top-end",
        icon: "success",
        title: "저장되었습니다.",
        showConfirmButton: false,
        timer: 1500
      });
    }
  });
  // 2초 후 페이지 리로드 → 체크리스트 표시
  setTimeout(function(){location.reload();},2000);
}
</script>

<style>
#typing-text {
  font-size: 16px;
  line-height: 1.5;
  margin-bottom: 10px;
}
@media (max-width: 767px) {
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
    background-color: #4CAF50;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    display: inline-block;
    font-size: 16px;
    border-radius: 20px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.submit-button:hover {
    background-color: #3e8e41;
}
.submit-button2 {
    background-color:#0080ff;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    display: inline-block;
    font-size: 16px;
    border-radius: 20px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.submit-button2:hover {
    background-color: white;
}
.submit-button3 {
    background-color:white;
    border: none;
    color: black;
    padding: 5px 22px;
    text-align: center;
    display: inline-block;
    font-size: 16px;
    border-radius: 5px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 0px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.submit-button3:hover {
    background-color: #e6f2ff;
}
</style>
</body>
</html>
';

} // end else($thislog->id == NULL)

// (2) 여기서부터 코드 전체를 <pre>로 감싸서 스크롤 끝에 표시
// -------------------------------------------------------------
?>
<pre id="hiddenCodeBlock" style="display:none; margin:20px; padding:10px; border:1px solid #ccc; background:#f9f9f9;">
<?php
// 원본 코드(현재 이 파일) 전체를 그대로 보여주고 싶다면 아래처럼 echo할 수도 있음.
// echo htmlentities(file_get_contents(__FILE__)); 
// 여기서는 예시상 "현재 코드"라는 안내만 표기
echo "이 곳에는 위에서 사용된 전체 코드를 표시합니다.\n\n(실제 구현 시 file_get_contents(__FILE__) 등을 사용해서 소스 전체를 노출 가능)";
?>
</pre>

<!-- (3) 마무리하기 버튼 노출 -->
<button id="finalButton" style="display:none; margin-bottom:30px;" onclick="finalizeAction()">마무리하기</button>

<script>
function finalizeAction(){
    // 스크롤 완료 후 나타나는 "마무리하기" 버튼을 클릭하면 saveResult() 호출
    // (이미 코드 내부에 정의된 saveResult()가 체크리스트를 생성합니다.)
    saveResult();
}
</script>
