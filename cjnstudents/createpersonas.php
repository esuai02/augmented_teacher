<?php
// API 키 환경변수에서 로드
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    error_log('[createpersonas.php:' . __LINE__ . '] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.');
}

// Moodle 환경 설정 파일
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러출력 억제 (가능하다면 개발환경에서는 켜두고, 운영환경에서 끄세요)
error_reporting(0);
ini_set('display_errors', 0);

// GET 파라미터
$userid  = $_GET["userid"];
$cnttype = $_GET["cnttype"];
$cntid   = $_GET["cntid"];
$type    = 'drilling';

// 최근 메시지 (mdl_abessi_messages) 예시
$thisboard = $DB->get_record_sql("
  SELECT * FROM mdl_abessi_messages
  WHERE userid = :userid
  ORDER BY timemodified DESC
  LIMIT 1
", ['userid' => $userid]);

$wboardid = $thisboard ? $thisboard->wboardid : 0;

// 컨텐츠 본문 획득
if($cnttype == 1) {
  $row = $DB->get_record_sql("
    SELECT * FROM mdl_icontent_pages
    WHERE id = :cntid
    ORDER BY id DESC LIMIT 1
  ", ['cntid' => $cntid]);
  $contentstext = $row ? $row->maintext : "데이터 없음";
}
elseif($cnttype == 2) {
  $row = $DB->get_record_sql("
    SELECT * FROM mdl_question
    WHERE id = :cntid
    ORDER BY id DESC LIMIT 1
  ", ['cntid' => $cntid]);
  $contentstext = $row ? ($row->mathexpression . $row->ans1) : "데이터 없음";
}
else {
  $contentstext = "해당 컨텐츠 타입($cnttype)은 예시가 없습니다.";
}

// GPT 요청 프롬프트 (JSON 구조 지시) - **수정된 부분**
$promptPersona = <<<EOT

{$contentstext}를 어떤 학생이 공부하려고 읽고 있어. 이 내용에 대해서 일반적으로 학생들이 느끼는 두려움, 호기심, 자신감, 좌절 등
'수학 학습 맥락에서의 심리상태와 행동 경향'에 초점을 맞춰 6개의 부정적 페르소나와 6개의 긍정적 페르소나를 생성해줘. 반드시 제시된 내용과 관련된 구체적인 학습상황에서 발현되는 페르소나를 제시해줘. 
(예시, 
# 부정적 페르소나  
그래프혼돈러 - 지수 함수의 그래프를 보며 방향과 형태를 헷갈려 혼란스러워하는 학생.  
기호포기자 - 수식 속 기호가 많아질수록 이해를 포기하는 학생.  
1고정집착러 - 지수 함수에서 밑이 1이 되면 의미가 없다는 사실에 집착하여 문제의 본질을 놓치는 학생.  
선택지탈출러 - 보기 문제에서 선택지를 모두 읽기도 전에 답이 없을 것 같다고 결론짓고 문제를 넘겨버리는 학생.  
부등호공포증 - 부등식이 나오면 방향을 헷갈려 하며 풀이 과정을 두려워하는 학생.  
함수의심러 - 지수 함수 개념을 의심하며 모든 그래프를 직접 확인하지 않으면 믿지 않는 학생.  

# 긍정적 페르소나  
그래프분석가 - 지수 함수의 그래프를 직관적으로 분석하며 특성을 파악하는 학생.  
기호활용자 - 수식 속 기호를 자유자재로 다루며 문제를 쉽게 풀이하는 학생.  
핵심파악러 - 문제의 조건을 빠르게 이해하고 핵심을 짚어내는 학생.  
선택지탐험가 - 각 선택지를 꼼꼼히 분석하며 답을 찾아내는 학생.  
부등호마스터 - 부등식의 성질을 정확히 이해하고 활용하는 학생.  
함수이해러 - 지수 함수의 특징을 신뢰하며 개념을 자유롭게 적용하는 학생.  )

그리고 부정적 페르소나가 긍정적 페르소나로 전환하도록 이끄는
시(enepoem)를 한 편 작성해줘.

출력 형식은 반드시 JSON으로, 다음과 같은 구조로 해줘:
{
  "negative_persona": [
    {
      "id": "npersona1",
      "name": "부정적 페르소나명",
      "description": "해당 페르소나 특성"
    },
    ... (총 6개)
  ],
  "positive_persona": [
    {
      "id": "ppersona1",
      "name": "긍정적 페르소나명",
      "description": "해당 페르소나 특성"
    },
    ... (총 6개)
  ],
  "enepoem": "부정에서 긍정으로 전환을 유도하는 시"
}

다른 문자열은 절대 넣지 말고, 꼭 위 JSON 형식만 지켜줘.
 

EOT;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>페르소나 생성 & DB 저장</title>
<!-- MathJax -->
<script>
MathJax = {
  tex: { inlineMath:[["$","$"],["$$","$$"],["\\(","\\)"]] },
  startup: {
    ready: function () {
      MathJax.startup.defaultReady();
      MathJax.startup.promise.then(function () {});
    }
  }
};
</script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" 
  async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

<!-- jQuery & SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<h3>GPT로부터 페르소나 JSON 생성</h3>
<div>
  <textarea id="input-text" style="width:80%;height:120px;"><?php echo $promptPersona; ?></textarea><br>
  <button onclick="askGPT()">페르소나 생성 요청</button>
</div>
<br>

<div id="gpt-response" style="border:1px solid #ccc; padding:10px; width:80%;">
  <p>GPT 응답이 표시됩니다.</p>
</div>

<div style="margin-top:10px;">
  <button onclick="savePersonas()">DB 저장 (부정∙긍정 페르소나 + enepoem)</button>
</div>

<script>
// 실제 키로 대체
const OPENAI_API_KEY = "<?php echo $openai_api_key; ?>"; // from env

// 전역변수: GPT 응답 JSON
let personaData = null;

// GPT에 요청
function askGPT() {
  const promptText = document.getElementById("input-text").value;
  const responseDiv = document.getElementById("gpt-response");
  responseDiv.innerHTML = "<p>GPT 응답 대기중...</p>";

  const requestBody = {
    model: "gpt-3.5-turbo",
    messages: [
      { "role": "user", "content": promptText }
    ],
    temperature: 0.7
  };

  fetch("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${OPENAI_API_KEY}`
    },
    body: JSON.stringify(requestBody)
  })
  .then(res => res.json())
  .then(data => {
    console.log("GPT raw data:", data);
    if(data.choices && data.choices[0]) {
      const content = data.choices[0].message.content.trim();

      // GPT 응답 표시
      responseDiv.textContent = content;
      // 수식 렌더링
      MathJax.typeset();

      // JSON 파싱 시도
      try {
        personaData = JSON.parse(content);
        console.log("Parsed persona data:", personaData);
      } catch(e) {
        console.error("JSON 파싱 오류:", e);
        Swal.fire("에러", "GPT 응답이 JSON 형식이 아님", "error");
        personaData = null;
      }
    } else {
      responseDiv.textContent = "GPT 응답이 없습니다.";
      personaData = null;
    }
  })
  .catch(err => {
    console.error("오류:", err);
    responseDiv.textContent = "API 호출 오류: " + err;
    personaData = null;
  });
}

// DB 저장
function savePersonas() {
  if(!personaData) {
    Swal.fire("에러", "GPT 응답(JSON)이 없습니다.", "error");
    return;
  }

  // JSON 구조: { negative_persona: [...], positive_persona: [...], enepoem: "..." }
  const negativeJSON = JSON.stringify(personaData.negative_persona || []);
  const positiveJSON = JSON.stringify(personaData.positive_persona || []);
  const poemText     = personaData.enepoem || "";

  const Contentsid   = "<?php echo $cntid; ?>";
  const Contentstype = "<?php echo $cnttype; ?>";
  const Wboardid     = "<?php echo $wboardid; ?>";

  $.ajax({
    url: "savepersonas.php",
    type: "POST",
    dataType: "json",  // 응답이 순수 JSON 형식이어야 함
    data: {
      eventid: "1",
      contentsid: Contentsid,
      contentstype: Contentstype,
      wboardid: Wboardid,

      // 페르소나 & 시 데이터
      negative_persona: negativeJSON,
      positive_persona: positiveJSON,
      enepoem: poemText
    },
    success: function(res) {
      if(res.status == "success") {
        Swal.fire("완료", "페르소나 정보가 저장되었습니다.", "success");
      } else {
        Swal.fire("에러", "DB 저장 실패: " + (res.msg || ""), "error");
      }
    },
    error: function(err) {
      console.error("통신 중 오류:", err);
      Swal.fire("에러", "통신 중 오류 발생", "error");
    }
  });
}
</script>

</body>
</html>
