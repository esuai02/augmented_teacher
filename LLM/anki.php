<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$ankiquizid = $_GET["qid"];
$sbjt = $_GET["sbjt"];
$domain = $_GET["dmn"];
$index = $_GET["index"];
$cnttype = $_GET["cnttype"];
$studentid = $_GET["studentid"];
$wboardid = $_GET["wboardid"];
$print = 1;
$dpmode = $_GET["dpmode"];
$timecreated=time();
if($ankiquizid==NULL && $cntid==NULL)
    {
    echo '<br><br><br><br><br><table align=center><tr><td>출제된 활동이 없습니다.</td></tr></table>';
    exit;    
    }
require_login();
if($cnttype==NULL)$cnttype=1;
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

$cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
$eventid=1;
$icontentid=$cnttext->icontentid;
$maintext=$cnttext->maintext;
$ankicnt=$cnttext->reflections1;
if($cntid==NULL)$cntid = $cnttext->id; 
    
$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
$contextid=substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$currenturl=strstr($url, '?');  //before
$currenturl=str_replace("?","",$currenturl);

if($dpmode==='grade')$gradingmode=$contextid.'?'.str_replace('&dpmode=grade','',$currenturl);
else $gradingmode=$contextid.'?'.$currenturl.'&dpmode=grade';

if($ankiquizid!=NULL) //보충컨텐츠
    {
    $cmplanki=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz WHERE id='$ankiquizid' ");
    $ankicnt=$cmplanki->text;
    $maintext=$cmplanki->helpcnt;
    $cntid = $cmplanki->contentsid;
    $cnttype = $cmplanki->contentstype;
    $ankiquizid;

    if($ankicnt==NULL) 
        {
        $cnttext2=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
        $maintext=$cnttext2->maintext;
        $ankicnt=$cnttext2->reflections1;
        }
    }

$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_ankiquiz WHERE contentstype='$cnttype' AND contentsid='$cntid' ORDER BY id DESC LIMIT 1");
$ankiquizidforlog=$exist->id;
if($exist->id==NULL)
    {
    $gettopictitle=$DB->get_record_sql("SELECT * FROM mdl_icontent where id='$icontentid'  ORDER BY id DESC LIMIT 1");
    $ankitype='original';
    $DB->execute("INSERT INTO {abessi_ankiquiz} (type,userid,subject,chapter,ntopic,npage,topictitle,title,contentsid,contentstype,timemodified,timecreated) VALUES('$ankitype','$USER->id','$cnttext->subject','$cnttext->chapter','$cnttext->ntopic','$cnttext->pagenum','$gettopictitle->name','$cnttext->title','$cntid','$cnttype','$timecreated','$timecreated')");
    }
else 
    { 
    $exist2=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquizlog WHERE quizid='$exist->id' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
    echo 'id='.$exist->id.'studentid='.$studentid.'nretry='.$exist2->nretry;    
  

    if($exist2->id==NULL)$nretry=1;
    elseif($exist2->timecreated<$timecreated-600) $nretry=$exist2->nretry+1;
    else $nretry=$exist2->nretry;

    $DB->execute("INSERT INTO {abessi_ankiquizlog} (quizid,userid,nretry,timecreated) VALUES('$exist->id','$studentid','$nretry','$timecreated')");
    }  

if($role!=='student')
    {  
    if($ankiquizid==NULL)echo '<span style="background-color:lightgreen;position:fixed;  top: 0%;right: 0%;z-index:5;"><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$cntid.'&cnttype='.$cnttype.'">수정하기</a> </span>';
    else echo '<span style="background-color:lightgreen;position:fixed;  top: 0%;right: 0%;z-index:5;"><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/fixanki.php?dmn='.$domain.'&sbjt='.$sbjt.'&qid='.$ankiquizid.'">수정하기</a> </span>';
    }

echo '<span style="background-color:lightgreen;position:fixed;  top: 0%;left: 0%;z-index:5;"><a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjt.'&studentid='.$studentid.'">HOME</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/ankihelp.php?qid='.$ankiquizid.'&cntid='.$cntid.'&studentid='.$studentid.'"target="_blank">help</a> | <a href="'.$gradingmode.'"target="_blank">응시</a></span>';   

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

$ankicntsrc=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where contentstype='$cnttype' AND contentsid='$cntid'  ORDER BY id DESC LIMIT 1");  


// 퀴즈에 이미지 넣는 경우 이부분을 잘 조절하면 됨.



if($ankicntsrc->imgurl!==NULL)$quizimg='<img src="'.$ankicntsrc->imgurl.'" height=300>';


$finishimg='https://mathking.kr/Contents/IMAGES/quiz%20completed.png';

if($index!=NULL)
    {

        $question = htmlspecialchars($qtext[$index]);
        $answer = htmlspecialchars($stext[$index]);
        $ankicard .= '
 
        <div class="card hidden question" id="answer' . $index . '">
            <h3>' . $question . '</h3><br>
            <h3>' . $answer . '</h3> 
            <table align=center><tr><td>'.$quizimg.'</td></tr></table><br><br><br>            
        </div>';        
    }
else 
    {

        foreach ($indices as $index) {
            $question = htmlspecialchars_decode($qtext[$index]);  // HTML 엔티티 디코딩
            $answer = htmlspecialchars($stext[$index]);
            
            if($ankicntsrc->imgurl == NULL) {
                list($question, $thisimgurl, $quizimg) = extractAndResizeImage($question);
            }
            
            $ankicard .= '
            <div class="card hidden question" id="question' . $index . '">
                <h3>' . htmlspecialchars($question) . '</h3>
                <br><br><br>
                <table align=center><tr><td>' . $quizimg . '</td></tr></table><br><br><br>
                <button onclick="showAnswer(' . $studentid . ',' . $ankiquizidforlog . ',' . $index . ')">정답 보기</button>
            </div>
            <div class="card hidden answer" id="answer' . $index . '">
                <h3>' . htmlspecialchars($question) . '</h3><br>
                <h3>' . $answer . '</h3>
                <table align=center><tr><td>' . $quizimg . '</td></tr></table><br><br><br>
                <table align=center><tr><td><button style="font-size:16px;" onclick="showNextRandomCard(' . $index . ')">다음</button></td><td>&nbsp;&nbsp;</td><td><button style="font-size:16px;" onclick="completeCard(' . $index . ')">제거</button></td></tr></table>
            </div>';
        }
    }

    function extractAndResizeImage($question) {
        // 정규식을 사용하여 이미지 태그를 추출
        $pattern = '/<img src="data:image\/[^>]+">/';
        preg_match($pattern, $question, $matches);
        
        // 이미지 태그를 $thisimgurl에 저장하고 폭을 600px로 설정
        $thisimgurl = "";
        $quizimg = "";
        if (!empty($matches)) {
            $thisimgurl = $matches[0];
            $quizimg = str_replace('<img ', '<img height="300px" ', $thisimgurl);
        }
        
        // 이미지 태그를 $question에서 제거
        $newQuestion = preg_replace($pattern, '', $question);
        
        return [$newQuestion, $thisimgurl, $quizimg];
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
';  

 
 
if($dpmode==='grade')
    {
    $wbstr='anki'.$cntid.'q.'.$ankiquizid;
    include("../ankiboard/gradeanki.php");
    $showankiquiz='<table width=100%><tr><td width=80%>
    <div class="quiz-container">  
    '.$ankicard.'
    <div class="hidden" id="completionMessage">
    <br><br><div class="img-container">
    <table align=center><tr><td align=center style="font-size:25px;"><img class="responsive-img" alt="Image" src="'.$finishimg.'"></td></tr></table><br><br> 
    </div>
    </div><br><br>
    <div class="progress-container"> 
    <div class="progress-bar" id="progressBar"></div>
    </div> 
    </div>  
    </td><td width=20%>'.$ankigrade_iframe.'</td></tr></table> 
    '; 
    $quizcontainerstyle='.quiz-container {
        text-align: center;
        background: #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        padding: 30px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: absolute; /* 절대 위치 지정 */
        bottom: 20%; /* 하단 경계 고정 */
        left: 40%; /* 가로 가운데 정렬 */ 
        transform: translateX(-50%); /* 가로 가운데 정렬 보정 */
        width: auto; /* 너비 자동 조절 */
        min-width: 50%; /* 최소 너비 설정 (선택 사항) */
        max-width: 90%; /* 최대 너비 설정 */
        }';
    }
else 
    {
    $quizcontainerstyle='.quiz-container {
        text-align: center;
        background: #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        padding: 30px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: absolute; /* 절대 위치 지정 */
        bottom: 20%; /* 하단 경계 고정 */
        left: 50%; /* 가로 가운데 정렬 */
        transform: translateX(-50%); /* 가로 가운데 정렬 보정 */
        width: auto; /* 너비 자동 조절 */
        min-width: 50%; /* 최소 너비 설정 (선택 사항) */
        max-width: 90%; /* 최대 너비 설정 */
        }';
    $showankiquiz='
    <div class="quiz-container">
    '.$ankicard.'
    <div class="hidden" id="completionMessage"><br><br><table align=center><tr><td align=center style="font-size:25px;"><img class="responsive-img" alt="Image" src="'.$finishimg.'"></td></tr></table><br><br> 
    </div><br><br>
    <div class="progress-container"> 
    <div class="progress-bar" id="progressBar"></div>
    </div> 
    </div> ';
    }


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
.responsive-img {
    width: 100%;
    height: auto;
    max-width: 100%;
}
.img-container {
    width: 100%;
    margin: auto;
}
body {
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f0f0f0;
    margin: 0;
}
'.$quizcontainerstyle.'


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
    background-color: #A0DCFF;
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
  height: 5px;
  background-color:#21F332;
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
<body>
'.$showankiquiz.'
</body>
<script>
let currentIndex = 0;
const totalCards = '.count($qtext).';
let completedCards = new Set();

function showAnswer(Studentid,Ankiquizid,index) 
    {
    document.getElementById("question" + index).classList.add("hidden");
    document.getElementById("answer" + index).classList.remove("hidden");
    var Eventid=15;
    index=index+1;
    $.ajax({
        url: "check_status.php", 
        type: "POST",
        dataType: "json",
        data : {
                "eventid":Eventid,
                "studentid":Studentid, 
                "quizid":Ankiquizid, 
                "index":index, 
                },
        success: function (data){  
        }
        });
        
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
    let cards = document.querySelectorAll(".card.question:not(.completed)");
    //let cards = document.querySelectorAll(".card:not(.completed)");
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
 '; 
 
?>
