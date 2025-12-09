<?php
// Moodle 환경 설정 파일
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 출력 억제 (개발환경에서는 켜두고, 운영환경에서는 끄는 것을 권장)
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

// 컨텐츠 본문 획득 (예시용, 필요하면 수정하세요)
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
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>JSON 페르소나 입력 & DB 저장</title>
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
<h3>JSON 페르소나 입력 & DB 저장</h3>

<!-- 컨텐츠 정보 예시 출력 -->
<div style="border:1px solid #ccc; padding:10px; width:80%;">
  <p><strong>컨텐츠 텍스트:</strong> <?php echo nl2br($contentstext); ?></p>
  <p><strong>Userid:</strong> <?php echo $userid; ?> /
     <strong>Cnttype:</strong> <?php echo $cnttype; ?> /
     <strong>Cntid:</strong> <?php echo $cntid; ?> /
     <strong>Wboardid:</strong> <?php echo $wboardid; ?></p>
</div>

<br>

<!-- JSON 입력 텍스트박스 -->
<textarea id="json-input" style="width:80%; height:250px;">
{
  "negative_persona": [
    {
      "id": "npersona1",
      "name": "기계적 암기자",
      "description": "공식을 무작정 외우지만, 컬레근이 왜 성립하는지 이해하지 못한다. 응용 문제에서 쉽게 막힌다."
    },
    {
      "id": "npersona2",
      "name": "혼동하는 계산자",
      "description": "유리수와 무리수, 실수와 허수를 혼동하며 틀린 답을 도출한다. 숫자의 성질을 제대로 구분하지 못한다."
    }
  ],
  "positive_persona": [
    {
      "id": "ppersona1",
      "name": "논리적 탐구자",
      "description": "컬레근의 성질을 근본적으로 이해하고 논리적으로 설명할 수 있다.",
      "enepoem": "숫자의 흐름 속에서 논리를 짓는다.\n근이 보이면 그 반쪽도 찾으리.\n무리수 속에도, 허수 속에도,\n대칭을 따라 해답을 찾는다."
    }
  ]
}
</textarea>
<br>

<button onclick="savePersonas()">DB 저장하기</button>

<script>
// 전역변수: 파싱된 JSON 객체
let personaData = null;

// DB 저장
function savePersonas() {
  const jsonInput = document.getElementById("json-input").value.trim();

  // JSON 파싱 시도
  try {
    personaData = JSON.parse(jsonInput);
    console.log("Parsed personaData:", personaData);
  } catch(e) {
    console.error("JSON 파싱 오류:", e);
    Swal.fire("에러", "유효한 JSON 형식이 아닙니다.", "error");
    return;
  }

  // 구조 확인 & 값 할당
  const negativeJSON = JSON.stringify(personaData.negative_persona || []);
  const positiveJSON = JSON.stringify(personaData.positive_persona || []);
  // 상위 레벨 enepoem이 없을 수 있으므로 처리
  const poemText     = personaData.enepoem || "";

  const Contentsid   = "<?php echo $cntid; ?>";
  const Contentstype = "<?php echo $cnttype; ?>";
  const Wboardid     = "<?php echo $wboardid; ?>";

  $.ajax({
    url: "savepersonas.php",
    type: "POST",
    dataType: "json",  // 응답이 JSON 형식이어야 함
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
