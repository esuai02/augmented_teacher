<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$studentid = $_GET["studentid"];
$wboardid = $_GET["wboardid"];
$print = 1;

if($cnttype==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $eventid=1;
    $maintext=$cnttext->maintext;
    $ankicnt=$cnttext->reflections1;
    }
elseif($cnttype==2)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $guidetext=$cnttext->mathexpression;
    $maintext=$cnttext->ans1;
    $ankicnt=$cnttext->reflections1;
    $eventid=2; 
    }
  $ankicnt = htmlspecialchars_decode($ankicnt);
  $ankicnt = str_replace("<p>", "", $ankicnt);
  $ankicnt = str_replace("</p>", "", $ankicnt);
  $ankicnt = str_replace("&nbsp;", "", $ankicnt);

// 문제와 답을 분리하여 배열에 저장
$qtext = [];
$stext = [];

// 문제와 답을 줄바꿈 문자를 기준으로 분리
$lines = explode("<br>", $ankicnt);
foreach ($lines as $line) {
    // 문제와 답을 \tab으로 분리
    $parts = explode('\tab', $line);
    if (count($parts) == 2) {
        $qtext[] = trim($parts[0]);
        $stext[] = trim($parts[1]);
    }
}

// 배열을 랜덤으로 섞기
$indices = range(0, count($qtext) - 1);
shuffle($indices);

// HTML 출력을 위한 변수 초기화
$ankicard = '';

foreach ($indices as $index) {
    $question = htmlspecialchars($qtext[$index]);
    $answer = htmlspecialchars($stext[$index]);
    $ankicard .= '
    <div class="card hidden" id="question' . $index . '">
        <h3>' . $question . '</h3>
        <button style="font-size:16px;" onclick="showAnswer(' . $index . ')">정답 보기</button>
    </div>
    <div class="card hidden" id="answer' . $index . '">
        <h3>' . $question . '</h3>
        <h3>' . $answer . '</h3>
       <table align=center><tr><td><button  style="font-size:16px;" onclick="showNextRandomCard(' . $index . ')">다음</button></td><td>&nbsp;&nbsp;</td><td><button  style="font-size:16px;" onclick="completeCard(' . $index . ')">제거</button></td></tr></table>
    </div>';
}
 
echo ' 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
 

'; //<script> alert("과거 학습데이터를 토대로 최적의 보충학습을 찾고 있습니다");    </script>
include("../bestnext.php");

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$Hippocampustest='';
if($cnttext->reflections1!=NULL)$Hippocampustest='&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=1"><button>확인테스트</button></a>';

echo '<!DOCTYPE html><html><head><script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ANKI 스타일 퀴즈</title>
<link rel="stylesheet" href="styles.css">

<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<script>
    window.MathJax = {
        tex: {
            inlineMath: [["$", "$"], ["\\(", "\\)"]]
        },
        startup: {
            pageReady: () => {
                return MathJax.startup.defaultPageReady().then(() => {
                    document.querySelectorAll(".card").forEach(card => {
                        card.style.fontSize = "1em";
                    });
                    MathJax.typeset();
                });
            }
        }
    };
</script>

<title>WarmUp Quiz</title>

<style>
body {
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f0f0f0;
    margin: 0;
}

.quiz-container {
    text-align: center;
    background: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    padding: 30px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    max-width: 90%;
    height: 300px; /* 고정 높이 설정 */
}

.card {
    display: none;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-size: 1em; /* 폰트 크기 고정 */
}

.card h2, .card p {
    margin: 0 0 10px;
    font-family: Arial, sans-serif;
}

.card button {
    padding: 10px 20px;
    background-color: #fff;
    color: #000000;
    border: none;
    
    border-radius: 4px;
    cursor: pointer;
    align-self: center;
}

.card button:hover {
    background-color: #B5FE98;
}

 
.hidden {
    display: none;
}

.visible {
    display: flex;
}

.mathjax {
    font-size: 1em; /* MathJax 수식의 폰트 크기 고정 */
}
.progress-container {
  width: 100%;
  background-color: #e0e0e0;
  border-radius: 5px;
  margin-bottom: 0px;
}

.progress-bar {
  width: 0%;
  height: 2px;
  background-color: #5BAAFF;
  border-radius: 5px;
}


    .header {
        text-align: left;
        margin-bottom: 20px;
    }
    .problem-statement {
        background-color: #fff;
        padding: 20px;
        text-align: left; font-size: 1.2em;
        border-left: 5px solid #ffffff;
        margin-top:-20px; margin-bottom:-20px;
        box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    .instructions {
        background-color: #fff;
        text-align: left;
        padding: 40px;
        font-size: 1em;
        margin-top:-50px;  margin-bottom: 0px;
       // box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    .instruction {
        margin-bottom: 10px;margin-top:-50px; 
        text-align: left;
    }
    .footer {
        text-align: center;
        margin-top: 0px;
        font-size: 0.85em;
        color: #666;
    }
    @media only screen and (max-width: 600px) {

        .problem-statement, .instructions {
            border-left: none;
        }
    }
</style>
<style>
    .collapsible {
      cursor: pointer;
      padding: 10px;
      width: 100%;
      text-align: left;
      border: none;
      outline: none;
      transition: 0.4s;
    }
    
    .active, .collapsible:hover {
      background-color: #f1f1f1;
    }
    
    .content {
      padding: 0 18px;
      display: none;
      overflow: hidden;
      transition: max-height 0.2s ease-out;
    }
</style>

</head> 
<div class="quiz-container">
'.$ankicard.'<br>
<div class="progress-container">
<div class="progress-bar" id="progressBar"></div>
</div>
<div class="hidden" id="completionMessage"><br>
<table align=center><tr><td><img src=https://icon-library.com/images/completed-icon/completed-icon-6.jpg width=70></td></tr></table><br>
<h3>수고하셨습니다 !</h3>
</div>
</div> 

 
<script>
let currentIndex = 0;
const totalCards = '.count($qtext).';
let completedCards = new Set();

function showAnswer(index) {
    document.getElementById("question" + index).classList.add("hidden");
    document.getElementById("answer" + index).classList.remove("hidden");
}

function showQuestion(index) {
    document.getElementById("answer" + index).classList.add("hidden");
    document.getElementById("question" + index).classList.remove("hidden");
}

function showNextRandomCard(currentIndex) {
    document.getElementById("answer" + currentIndex).classList.add("completed");
    showNextCard();
}

function completeCard(index) {
    completedCards.add(index);
    document.getElementById("question" + index).classList.add("completed");
    document.getElementById("answer" + index).classList.add("completed");
    showNextCard();
}

function showNextCard() {
    let cards = document.querySelectorAll(".card:not(.completed)");
    if (cards.length > 0) {
        hideAllCards(); // 모든 카드를 숨깁니다
        showRandomCard(cards); // 무작위로 카드 하나를 표시합니다
        updateProgressBar();
    } else {
        hideAllCards(); // 모든 카드를 숨깁니다
        updateProgressBar();
        document.getElementById("completionMessage").classList.remove("hidden");
    }
}
function showRandomCard(cards) {
    let randomIndex = Math.floor(Math.random() * cards.length);
    cards[randomIndex].classList.remove("hidden");
    cards[randomIndex].classList.add("visible");
}
function hideAllCards() {
    let allCards = document.querySelectorAll(".card");
    allCards.forEach(card => {
        card.classList.remove("visible");
        card.classList.add("hidden");
    });
}
function updateProgressBar() {
    const progressBar = document.getElementById("progressBar");
    const progress = (completedCards.size / totalCards) * 100;
    progressBar.style.width = progress + "%";
}

// 초기 상태 설정
document.addEventListener("DOMContentLoaded", function() {
    showNextCard();
});
</script>
</html> 


<script type="text/x-mathjax-config">
MathJax.Hub.Config({
  tex2jax: {
    inlineMath:[["$","$"],["$$","$$"],["\(","\)"]],
    //displayMath: [ ["$","$"], ["\\[","\\]"],["\(","\)"]]
  }
});
</script>

<script>
    tinymce.init({
      selector: "textarea",
      plugins: "anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount ",
      toolbar: "undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat",
      tinycomments_mode: "embedded",
      tinycomments_author: "Author name",
      mergetags_list: [
        { value: "First.Name", title: "First Name" },
        { value: "Email", title: "Email" },
      ]
    });
  </script> ';

echo ' 
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script> 
async function convertToImageAndUpload(studentid, contentsid, print) {
  try {
      const canvas = await html2canvas(document.getElementById("content-to-capture"), {
          ignoreElements: function (element) {
              return element.tagName === "DETAILS" || element.closest("details");
              }
          });
  
      canvas.toBlob(async blob => {
          const formData = new FormData();
          formData.append("image", blob, "image.png");
          formData.append("studentid", studentid); // studentid 값을 FormData에 추가합니다.
          formData.append("contentsid", contentsid); // contentsid 값을 FormData에 추가합니다.
          formData.append("print", print); // contentsid 값을 FormData에 추가합니다.

          try {
              const response = await fetch("uploadimage.php", { // 업로드 스크립트 경로를 지정합니다.
                  method: "POST",
                  body: formData,
              });
 
           
              const result = await response.json();
              if (result.success) {
                  // 업로드 성공 시, 필요한 후속 조치를 수행합니다.
                  window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/create_SPECWB.php?wboardid='.$wboardid.'&studentid='.$studentid.'&contentsid='.$cntid.'&contentstype=1&print='.$print.'&imageurl=${result.url}`;
              } else {
                window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/create_SPECWB.php?wboardid='.$wboardid.'&studentid='.$studentid.'&contentsid='.$cntid.'&contentstype=1&print='.$print.'&imageurl=${result.url}`;
              }
          } catch (error) {
              console.error("업로드 실패:", error);
              alert("이미지 업로드에 실패했습니다.");
          }
      }, "image/png");
  } catch (error) {
      console.error("이미지 생성 실패:", error);
      alert("이미지 생성에 실패했습니다.");
  }
}

</script>
';
echo '<script>   
function saveContent(Eventid,Cntid)
  {
    var editor = tinymce.get("mytextarea");   
    var htmlContent = editor.getContent();
    var NewHtml = htmlContent;    
   // NewHtml = NewHtml.replace(/(<([^>]+)>)/ig,"");

    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
    var NewHtml2 = htmlContent2;    

    var editor3 = tinymce.get("mytextarea3");   
    var htmlContent3 = editor3.getContent();
    var NewHtml3 = htmlContent3;    
   // NewHtml2 = NewHtml2.replace(/(<([^>]+)>)/ig,"");

        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "cntid":Cntid,		 
              "inputtext":NewHtml, 
              "inputtext2":NewHtml2, 
              "inputtext3":NewHtml3, 
            },
            success:function(data){
                    var Cntid2=data.cntid;
                    swal("OK !", "저장되었습니다.", {buttons: false,timer: 100});
                    setTimeout(function(){location.reload();} , 100); 
                    }
             })
}
 
</script>

<style>a[href]:after { content: none !important; }
@media print {
  .no-print {
      display: none;
  }
 
}
</style>
';




if ($role === 'student') {
  echo '<script>
  swal("보충학습을 위한 화이트보드가 생성 중입니다.", {buttons: false,timer: 2000});
  window.addEventListener("load", (event) => {
    setTimeout(function() {
      document.getElementById("createwhiteboard").click();
      }, 2000); 
  });
  </script>';
}
?>
