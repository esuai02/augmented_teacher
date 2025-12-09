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
$timecreated = time();
 
require_login();

if ($cnttype == NULL) {
    $cnttype = 1;
}

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role = $userrole->data;


$cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
$icontentid = $cnttext->icontentid;
$maintext = $cnttext->maintext;
$ankicnt = $cnttext->reflections0;

if ($cntid == NULL) {
    $cntid = $cnttext->id; 
}

$url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
$contextid = substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$currenturl = strstr($url, '?');  //before
$currenturl = str_replace("?", "", $currenturl);

if ($dpmode === 'grade') {
    $gradingmode = $contextid.'?'.str_replace('&dpmode=grade', '', $currenturl);
} else {
    $gradingmode = $contextid.'?'.$currenturl.'&dpmode=grade';
}
 
echo '<span style="background-color:lightgreen;position:fixed;  top: 0%;left: 0%;z-index:5;"> </span>';   

// Prepare the card content
$ankicnt = htmlspecialchars_decode($ankicnt);
$ankicnt = str_replace("<p>", "", $ankicnt);
$ankicnt = str_replace("</p>", "", $ankicnt);
$ankicnt = str_replace("&nbsp;", "", $ankicnt);
$ankicnt = str_replace("<br>", "", $ankicnt);  // 이 부분이 태그를 제거합니다.

// 지시사항 이후의 텍스트 추출
$start = strpos($ankicnt, '지시사항');
 
$instructions = substr($ankicnt, $start);

// 지시사항을 숫자와 점(.)으로 시작하는 부분으로 분할
$parts = preg_split('/(?=\d+\.)/', $instructions, -1, PREG_SPLIT_NO_EMPTY);
 

// 첫 번째 요소가 '지시사항' 타이틀인지 확인하고 제거
if (strpos($parts[0], '지시사항') !== false) {
    array_shift($parts);
} else {
    echo "<br><br><br><br><br>첫 번째 요소는 지시사항 타이틀이 아닙니다: " . $parts[0];
    exit;
}

$qtext = [];
foreach ($parts as $part) {
    $qtext[] =$cleanedText = preg_replace('/^\d+\.\s*/', '',trim($part)); 
}
 
// Modify the last element
if (!empty($qtext)) {
    $qtext[count($qtext) - 1] = "완료되었습니다."; // Replace the content of the last card
}

// Initialize variables for HTML output
$ankicard = '';
$finishimg = 'https://mathking.kr/Contents/IMAGES/quiz%20completed.png';

// Display cards sequentially
foreach ($qtext as $index => $question) {
    $ankicard .= '
    <div class="card question" id="question' . $index . '">
    <br>
        <h4>' . htmlspecialchars($question) . '</h4>
        <br>
        <button onclick="showNextCard(' . $index . ')">OK</button>
    </div>';
}

echo '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
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


    <style>
        .quiz-container {
            
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0);
            padding: 10px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: absolute;
            bottom: 0%;
            left: 50%;
  
            transform: translateX(-50%);
            width: auto;
            min-width: 90%;
            max-width: 90%;
        }
        .card {
            display: none;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 1em;
        }
        .card h3 {
            margin: 0 0 10px;
        }
        .card button {
            padding: 10px 10px;
            background-color: #fff;
            color: #000;
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
    </style>
</head>
<body>
<div class="quiz-container">
    '.$ankicard.'
<div class="hidden" id="completionMessage">
    <br><br>
    <table align=center>
        <tr><td align=center style="font-size:25px;">
        
        </td></tr>
    </table>
    <br><br>
</div>

    <br><br>
    <div class="progress-container"> 
        <div class="progress-bar" id="progressBar"></div>
    </div> 
</div>
<script>
    let currentIndex = 0;
    const totalCards = '.count($qtext).';
    let completedCards = new Set();

 function showNextCard() {
    if (currentIndex < totalCards) {
        hideAllCards();
        document.getElementById("question" + currentIndex).classList.remove("hidden");
        currentIndex++;
        updateProgressBar();
    } else {
        document.getElementById("completionMessage").classList.remove("hidden"); // 완료 이미지를 표시
        updateProgressBar();
    }
}

function hideAllCards() {
    let allCards = document.querySelectorAll(".card");
    allCards.forEach(card => {
        card.classList.add("hidden");
    });
}

function updateProgressBar() {
    const progressBar = document.getElementById("progressBar");
    const progress = (currentIndex / totalCards) * 100;
    progressBar.style.width = progress + "%";
}


    // Initialize first card
    document.addEventListener("DOMContentLoaded", function() {
        showNextCard();
    });
</script>
</body>
</html>';
?>
