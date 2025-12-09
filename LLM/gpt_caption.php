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
$htmlContent = $cnttext->reflections0;
  
// DOMDocument와 DOMXPath를 사용하여 HTML을 파싱
$doc = new DOMDocument();
libxml_use_internal_errors(true); // HTML 파싱 오류 무시
$doc->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

$xpath = new DOMXPath($doc);
$paragraphs = $xpath->query('//p');

// 자막 배열 초기화 및 시간 설정
$subtitles = [];
$time = 0;
$timeIncrement = 5; // 5초 간격

$captionInput = ''; // 자막 문자열 초기화

foreach ($paragraphs as $para) {
    // 발언자 초기화
    $speaker = '';
    $text = '';

    // strong 태그를 가진 경우 발언자 추출
    if ($para->getElementsByTagName('strong')->length > 0) {
        $speakerNode = $para->getElementsByTagName('strong')->item(0);
        $speaker = trim($speakerNode->nodeValue);

        // strong 태그를 제거한 텍스트 추출
        $clonedPara = $para->cloneNode(true);
        $strongs = $clonedPara->getElementsByTagName('strong');
        foreach ($strongs as $strong) {
            $strong->parentNode->removeChild($strong);
        }
        $text = trim($clonedPara->textContent);

        // 발언자 뒤에 오는 ':'와 공백 제거
        $text = ltrim($text, ": \t\n\r\0\x0B");
    } else {
        // strong 태그가 없는 경우 전체 텍스트 사용
        $text = trim($para->textContent);
    }

    // 발언자와 텍스트 조합
    if ($speaker != '') {
        $fullText = "$speaker: $text";
    } else {
        $fullText = $text;
    }

    // HTML 엔티티 디코딩 (수학 표현식 및 특수 문자 처리)
    $fullText = html_entity_decode($fullText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // JavaScript에서 사용할 수 있도록 문자열 이스케이프 처리
    $escapedText = addcslashes($fullText, "\\\"\n\r");

    // 자막 문자열에 추가
    $captionInput .= "{ time: $time, text: \"$escapedText\" },\n";

    $time += $timeIncrement; // 시간 증가
}

// 마지막 콤마와 줄바꿈 제거
//$captionInput = rtrim($captionInput, ",\n");
// JavaScript 자막 배열 생성
//$captionInput = json_encode($subtitles, JSON_UNESCAPED_UNICODE);
$captionInput='['.$captionInput.']';
  
/*
    const subtitles = ['
        { time: 0, text: "선생님: 오늘은 원의 방정식의 일반형에 대해 배워보겠습니다." },
        { time: 5, text: "선생님: 원의 방정식의 일반형은 x² + y² + Ax + By + C = 0 입니다." },
        { time: 10, text: "학생: 음, 원을 나타낸다고요? 그냥 이차방정식처럼 보이는데, 어떻게 원을 나타내는 거죠?" },
        { time: 15, text: "선생님: 좋은 질문이에요! 이 방정식이 원을 나타내는 이유는 이차항인 x²와 y²가 있기 때문이에요." },
        { time: 20, text: "선생님: 이 방정식에서 원의 중심과 반지름을 구할 수 있어요." },
        { time: 25, text: "학생: 아, 중심과 반지름을 어떻게 구할 수 있나요?" },
        { time: 30, text: "선생님: 원의 중심은 (-A/2, -B/2)로 구할 수 있어요." },
        { time: 35, text: "선생님: 반지름은 √(A² + B² - 4C) / 2 로 구할 수 있습니다." }'
    ];
*/

echo '  <div id="whiteboard">
        <div id="subtitle"></div>
        <div id="progress-container">
            <div id="progress-bar"></div>
        </div>
    </div>
    <div id="controls">
        <div id="buttons">
            <button id="start-btn">자막 시작</button>
            <button id="pause-btn">일시정지</button>
            <button id="reset-btn">초기화</button>
            <button id="next-btn">다음 자막</button>
            <button id="tts-btn">TTS 켜기</button>
        </div>
        <div id="speed-control">
            <span>자막 속도: 느림</span>
            <input type="range" id="speed-slider" min="1" max="10" value="5">
            <span>빠름</span>
        </div>
    </div>
    
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const subtitles = '.$captionInput.'; 
            let currentSubtitleIndex = 0;
            let subtitleInterval;
            let isTTSEnabled = false;
            let isSpeaking = false;

            const subtitleElement = document.getElementById("subtitle");
            const progressBar = document.getElementById("progress-bar");
            const progressContainer = document.getElementById("progress-container");
            const speedSlider = document.getElementById("speed-slider");
            const startBtn = document.getElementById("start-btn");
            const pauseBtn = document.getElementById("pause-btn");
            const resetBtn = document.getElementById("reset-btn");
            const nextBtn = document.getElementById("next-btn");
            const ttsBtn = document.getElementById("tts-btn");

            let voices = [];

            function loadVoices() {
                voices = window.speechSynthesis.getVoices();
                console.log("Available voices:", voices);
            }

            if (speechSynthesis.onvoiceschanged !== undefined) {
                speechSynthesis.onvoiceschanged = loadVoices;
            }

            loadVoices();

            function getSubtitleInterval() {
                return 11000 - speedSlider.value * 1000; // 1초에서 10초 사이
            }

            function updateProgressBar() {
                const progress = (currentSubtitleIndex / (subtitles.length - 1)) * 100;
                progressBar.style.width = `${progress}%`;
            }

            function improveKoreanPronunciation(text) {
                // 숫자 읽기 개선
                text = text.replace(/(\d+)\/2/g, "$1분의 2");
                text = text.replace(/(\d+)²/g, "$1 제곱");
                text = text.replace(/(\d+)³/g, "$1 세제곱");
                
                // 수학 기호 읽기 개선
                text = text.replace(/=/g, "는");
                text = text.replace(/\+/g, "더하기");
                text = text.replace(/-/g, "빼기");
                text = text.replace(/x/g, "엑스");
                text = text.replace(/y/g, "와이");
                text = text.replace(/√/g, "루트");

                // 괄호 읽기 개선
                text = text.replace(/\(/g, "열고");
                text = text.replace(/\)/g, "닫고");

                return text;
            }

            function convertMathToText(text) {
                const speaker = text.startsWith("선생님:") ? "선생님" : "학생";
                text = text.replace(/^(선생님:|학생:)\s*/, "");
                text = improveKoreanPronunciation(text);
                return { speaker, text };
            }

            function showSubtitle(index) {
                if (index < subtitles.length) {
                    subtitleElement.textContent = subtitles[index].text;
                    updateProgressBar();
                    if (isTTSEnabled) {
                        speakText(subtitles[index].text);
                    }
                } else {
                    clearTimeout(subtitleInterval);
                    subtitleElement.textContent = "자막 종료";
                    progressBar.style.width = "100%";
                    setTimeout(() => {
                        resetSubtitles();
                    }, 2000);
                }
            }

            function startSubtitles() {
                clearTimeout(subtitleInterval);
                showSubtitle(currentSubtitleIndex);
                nextSubtitleWithDelay();
                setActiveButton(startBtn);
            }

            function nextSubtitleWithDelay() {
                const interval = getSubtitleInterval();
                subtitleInterval = setTimeout(() => {
                    if (!isSpeaking) {
                        currentSubtitleIndex++;
                        showSubtitle(currentSubtitleIndex);
                        nextSubtitleWithDelay();
                    } else {
                        nextSubtitleWithDelay(); // TTS가 끝나지 않았다면 다시 확인
                    }
                }, interval);
            }

            function pauseSubtitles() {
                clearTimeout(subtitleInterval);
                if (isTTSEnabled) {
                    window.speechSynthesis.pause();
                }
                setActiveButton(pauseBtn);
            }

            function resetSubtitles() {
                clearTimeout(subtitleInterval);
                currentSubtitleIndex = 0;
                subtitleElement.textContent = "";
                progressBar.style.width = "0%";
                if (isTTSEnabled) {
                    window.speechSynthesis.cancel();
                }
                setActiveButton(resetBtn);
            }

            function nextSubtitle() {
                pauseSubtitles();
                currentSubtitleIndex++;
                showSubtitle(currentSubtitleIndex);
                setActiveButton(nextBtn);
            }

            function setActiveButton(activeButton) {
                [startBtn, pauseBtn, resetBtn, nextBtn].forEach(btn => btn.classList.remove("active"));
                activeButton.classList.add("active");
            }

            function toggleTTS() {
                isTTSEnabled = !isTTSEnabled;
                ttsBtn.textContent = isTTSEnabled ? "TTS 끄기" : "TTS 켜기";
                if (isTTSEnabled) {
                    speakText(subtitleElement.textContent);
                } else {
                    window.speechSynthesis.cancel();
                }
            }

            function speakText(text) {
                if ("speechSynthesis" in window) {
                    window.speechSynthesis.cancel();
                    const { speaker, text: convertedText } = convertMathToText(text);
                    const utterance = new SpeechSynthesisUtterance(convertedText);
                    utterance.lang = "ko-KR";
                    
                    // 속도 조정
                    utterance.rate = 0.9; // 약간 느리게

                    // 화자에 따라 다른 음성 선택 및 피치 조정
                    if (speaker === "선생님") {
                        const teacherVoice = voices.find(voice => voice.lang === "ko-KR" && voice.name.includes("Male"));
                        utterance.voice = teacherVoice || voices.find(voice => voice.lang === "ko-KR") || voices[0];
                        utterance.pitch = 1; // 기본 피치
                    } else { // 학생
                        const studentVoice = voices.find(voice => voice.lang === "ko-KR" && voice.name.includes("Female"));
                        utterance.voice = studentVoice || voices.find(voice => voice.lang === "ko-KR" && !voice.name.includes("Male")) || voices[1];
                        utterance.pitch = 1.2; // 약간 높은 피치
                    }

                    isSpeaking = true;
                    utterance.onend = () => {
                        isSpeaking = false;
                    };

                    window.speechSynthesis.speak(utterance);
                }
            }

            startBtn.addEventListener("click", startSubtitles);
            pauseBtn.addEventListener("click", pauseSubtitles);
            resetBtn.addEventListener("click", resetSubtitles);
            nextBtn.addEventListener("click", nextSubtitle);
            ttsBtn.addEventListener("click", toggleTTS);

            speedSlider.addEventListener("input", function() {
                if (subtitleInterval) {
                    startSubtitles(); // 속도 변경 시 자막을 재시작하여 새로운 속도 적용
                }
            });

            progressContainer.addEventListener("click", function(e) {
                const rect = progressContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const clickedProgress = x / rect.width;
                currentSubtitleIndex = Math.round(clickedProgress * (subtitles.length - 1));
                showSubtitle(currentSubtitleIndex);
                if (subtitleInterval) {
                    startSubtitles(); // 클릭 후 자막 재생 시작
                } else {
                    pauseSubtitles(); // 클릭 후 일시정지 상태 유지
                }
            });

            resetSubtitles(); // 초기 상태 설정
        });
    </script>';


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
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        #whiteboard {
            width: 800px;
            height: 110px;
            border: 2px solid #333;
            position: relative;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        #subtitle {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px;
            font-size: 18px;
        }
        #progress-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10px;
            background-color: #ddd;
            cursor: pointer;
        }
        #progress-bar {
            width: 0%;
            height: 100%;
            background-color: #4CAF50;
            transition: width 0.1s linear;
        }
        #controls {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #buttons {
            margin-bottom: 15px;
        }
        button {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.1s;
        }
        button:hover {
            background-color: #45a049;
        }
        button.active {
            background-color: #357a38;
            transform: scale(0.98);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        #speed-control {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        #speed-slider {
            width: 200px;
            margin: 0 10px;
        }
    </style>

    <style>
        .quiz-container {
            
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0);
            padding: 20px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: absolute;
            bottom: 20%;
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
            padding: 10px 20px;
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
</head> ';
//echo  $captioninput;
//echo $caption;

echo '</html>';
?>
