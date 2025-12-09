<?php
// Include Moodle configuration files to get access to the $DB object and other configurations
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$cid = $_GET["cid"];
$nch = $_GET["nch"];
$cmid = $_GET["cmid"];
$domain = $_GET["dmn"];
$nthispage = $_GET["page"];
$pgtype = $_GET["pgtype"];
$quizid = $_GET["quizid"];
$studentid = $_GET["studentid"];
$timecreated = time();

// Include gpttalk.php if necessary
// include("gpttalk.php");

if ($studentid == NULL) $studentid = $USER->id;
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 ");
$role = $userrole->data;
$lstyle = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='90'  ORDER BY id DESC LIMIT 1");
$learningstyle = $lstyle->data;

$userinfo = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$username = $userinfo->firstname . $userinfo->lastname;

$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
if ($role === 'student') $tabtitle = 'G : ' . $weeklyGoal->text;
else $tabtitle = $username . '의 수학노트';

$mynoteurl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$mynotecontextid = substr($mynoteurl, 0, strpos($mynoteurl, '?')); // 문자 이후 삭제
$mynoteurl = strstr($mynoteurl, '?');  //before
$mynoteurl = str_replace("?", "", $mynoteurl);

$cntpages = $DB->get_records_sql("SELECT * FROM mdl_icontent_pages where cmid='$cmid' ORDER BY pagenum ASC   ");  //AND  title NOT LIKE '%Approach%'
$result = json_decode(json_encode($cntpages), True);
$ntotalpages = count($cntpages); // 삭제
unset($value);
foreach ($result as $value) {
    $title = $value['title'];
    $npage = $value['pagenum'];
    if ($value['audiourl'] != NULL) $audioicon = ' 🎧';
    else $audioicon = '';
    if ($npage == 1) $contentsid0 = $value['id'];
    $contentsid = $value['id'];

    // 추후 삭제
    if ($npage == $ntotalpages && (strpos($title, '표유형') != false || strpos($title, 'heck') != false)) $DB->execute("UPDATE {icontent_pages} SET milestone='1' WHERE id='$contentsid' ORDER BY id DESC LIMIT 1");

    $srcid = 'jnrsorksqcrark' . $contentsid;
    $wboardid = 'jnrsorksqcrark' . $contentsid . '_user' . $studentid;
    $thisboard = $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY timemodified DESC LIMIT 1");
    $thiscnt = $DB->get_record_sql("SELECT milestone FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");
    $milestone = $thiscnt->milestone;
    if ($milestone == NULL) $milestone = 0;

    // 자동출제
    $lmode = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='90' "); //
    if ($thisboard->wboardid == NULL && $USER->id == $studentid || $thisboard->url == NULL) {
        $mynoteurl2 = 'cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&page=' . $npage . '&studentid=' . $studentid . '&quizid=' . $quizid;
        $DB->execute("INSERT INTO {abessi_messages} (userid, userto, userrole, talkid, nstep, turn, student_check, status, contentstype, wboardid, contentstitle, contentsid, url, timemodified, timecreated) VALUES('$studentid', '2', '$role', '2', '0', '$milestone', '0', 'begintopic', '1', '$wboardid', 'inspecttopic', '$contentsid', '$mynoteurl2', '$timecreated', '$timecreated')");
    }
    if ($npage == 1) {
        $headimg = '<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg1.png width=15>';
        $contentstitle = $title;
    } elseif (strpos($title, 'Check') != false) $headimg = '<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png width=15>';
    elseif (strpos($title, '유형') != false) $headimg = '<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg3.png width=15>';
    else $headimg = '<img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png width=15>';
    $cjnfblist = '';
    $attemptresult = '';
    $presetfunction = 'ConnectNeurons';
    $width1 = 80;

    $width2 = 20;

    if ($pgtype === 'quiz') {
        $showpage = 'https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizid;

        if ($learningstyle === '도제' && strpos($title, '대표') !== false) echo '';
        elseif (strpos($title, '유형') !== false) $contentslist2 .= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&page=' . $npage . '&studentid=' . $studentid . '">' . $headimg . ' ' . $title . '</a>' . $audioicon . $attemptresult . '</td></tr>';
        elseif (strpos($title, '복습') !== false) $contentslist3 .= '<tr><td><a href="https://mathking.kr/Contents/IMAGES/restore.png" width=15> ' . $title . '</a>' . $audioicon . ' <input type="checkbox"  onclick="changecheckbox(1,' . $studentid . ',' . $mid . ', this.checked)"/></td></tr>';
        else $contentslist .= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&page=' . $npage . '&studentid=' . $studentid . '">' . $headimg . ' ' . $title . '</a>' . $audioicon . $attemptresult . '</td></tr>';

        $nnextpage = $nthispage + 1;
        $nextpage = $DB->get_record_sql("SELECT id, title FROM mdl_icontent_pages where cmid='$cmid' AND pagenum='$nnextpage' ORDER BY id DESC LIMIT 1");


        if (strpos($nextpage->title, '유형') != false && $quizid != NULL) $nextlearningurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&page=' . $nnextpage . '&studentid=' . $studentid;
        elseif ($quizid != NULL) $nextlearningurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=' . $cid . '&nch=' . $nch . '&cntid=' . ($cmid + 1) . '&studentid=' . $studentid;


        $rule = '<a style="text-decoration:none;color:white;" href="' . $nextlearningurl . '"><button class="stylish-button">NEXT</button></a>';
    } elseif ($npage == $nthispage) {
        $topictitle = $value['title'];
        $audiocnt = '';
        $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");
        $maintext = $cnttext->maintext;
        $milestone = $cnttext->milestone;
        $thispageid = $contentsid;
        if ($npage == 1) $contentstitle = $title;
        else $contentstitle = $contentstitle . '-' . $cnttext->title;

        if ($cnttext->audiourl !== NULL) {
            $audiocnt = '<audio id="audioPlayer" controls style="width:300px;height:30px;">
                <source src="' . $cnttext->audiourl . '" type="audio/mpeg">
            </audio>
            <div style="margin-top: 10px;" id="speedControl">
                <div>
                    <input type="range" id="speedSlider" min="0.8" max="1.8" step="0.1" value="1.0" style="width: 300px; display: none;height: 30px;">
                    <table align=right><tr><td><label for="speedSlider">(' . $thisboard->nreview . ')....속도 : <span id="speedValue">1.0</span>x</label> </td></tr></table>
                </div>
            </div><script>
            document.getElementById("audioPlayer").addEventListener("ended", function() {
                this.currentTime = 0; // 재생 위치를 처음으로 설정
                this.play(); // 재생 시작
                swal("", "OK ! 한 번 더 들어보세요 ! (3번씩 추천!)", {buttons: false,timer: 3000});
                      var Wboardid= \'' . $wboardid . '\';
                      var Contentstitle= \'' . $contentstitle . '\';
                      $.ajax({
                        url:"check_status.php",
                        type: "POST",
                        dataType:"json",
                        data : {
                        "eventid":6,
                        "wboardid":Wboardid,
                        "contentstitle":Contentstitle,
                        },
                        success:function(data){
                        }
                      })


            });
                document.addEventListener("DOMContentLoaded", function() {
                    const audioPlayer = document.getElementById("audioPlayer");
                    const speedSlider = document.getElementById("speedSlider");
                    const speedValueLabel = document.getElementById("speedValue");
                    let isMouseOver = false;

                    // Check if audio URL is valid and can be played
                    audioPlayer.addEventListener("error", function() {
                        console.error("Error loading audio file. Please check the audio URL.");
                    });

                    // Update playback rate when slider value changes
                    speedSlider.addEventListener("input", function() {
                        const playbackRate = parseFloat(this.value);
                        audioPlayer.playbackRate = playbackRate;
                        speedValueLabel.textContent = playbackRate.toFixed(1);
                    });

                    // Show speed slider on mouse over speed control
                    speedSlider.parentElement.addEventListener("mouseover", function() {
                        speedSlider.style.display = "block";
                        isMouseOver = true;
                    });

                    // Track if mouse leaves the speed control area
                    speedSlider.parentElement.addEventListener("mouseout", function() {
                        isMouseOver = false;
                    });

                    // Hide speed slider on document click, if mouse is not over the speed control
                    document.addEventListener("click", function() {
                        if (!isMouseOver) {
                            speedSlider.style.display = "none";
                        }
                    });
                });
            </script>
            <hr>';
        }

        if ($cnttext->milestone == NULL) $milestone = 0;
        if (strpos($cnttext->reflections1, 'youtube') !== false) $contentslink = '&nbsp;&nbsp; <a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/movie.php?cntid=' . $contentsid . '&cnttype=1&studentid=' . $studentid . '&wboardid=' . $wboardid . '&print=0"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=20></a>';
        elseif (strpos($cnttext->reflections1, '\tab') !== false) $contentslink = '&nbsp;&nbsp; <a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?dmn=' . $domain . '&cntid=' . $contentsid . '&cnttype=1&studentid=' . $studentid . '&wboardid=' . $wboardid . '&print=0"target="_blank"><img src="https://ankiweb.net/logo.png" width=20></a>';
        if ($milestone == 1 || strpos($cnttext->reflections0, '지시사항') !== false) {
            $HippocampusCnt = '<tr style="background-color:green;color:white;"><td><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid=' . $contentsid . '&cnttype=1&studentid=' . $studentid . '&wboardid=' . $wboardid . '&print=0"target="_blank">💊 </a><span  type="button"  onClick="Bridgesteps()">징검다리</span>  ' . $contentslink . ' </td></tr>';
        } elseif (strpos($cnttext->reflections1, '\tab') !== false) {
            $HippocampusCnt = '<tr style="background-color:green;color:white;"><td> ANKI 퀴즈  ' . $contentslink . ' </td></tr>';
        }
        $thispage = $npage;
        $bessiboard = 'cjnNotepageid' . $contentsid . 'jnrsorksqcrark';
        $bessiboard2 = 'CognitiveHunt_' . $contentsid . '_topic';
        $thiswbid = $bessiboard . '_user' . $studentid;
        $thisstamp = $DB->get_record_sql("SELECT id FROM mdl_abessi_questionstamp where wboardid='$bessiboard' ORDER BY id DESC LIMIT 1");
        $showpage = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=' . $wboardid . '&contentsid=' . $contentsid . '&studentid=' . $studentid . '&quizid=' . $quizid . '&' . $mynotecurrenturl;
        $showpage2 = $showpage;


        if (strpos($topictitle, '이해') !== false || strpos($topictitle, '특강') !== false) {
            $showpage = 'https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=' . $bessiboard . '&srcid=' . $wboardid . '&contentsid=' . $contentsid . '&contentstype=1&studentid=' . $studentid;
        }

        $gpteventname = '개념노트';
        $contextid = 'mynote_cid' . $cid . 'nch' . $nch . 'cmid' . $cmid . 'page' . $npage;
        //include("gptrecord.php");

        if ($milestone == 1 && $USER->id == $studentid) $DB->execute("UPDATE {abessi_messages} SET turn='1', student_check='1', timemodified='$timecreated',timecreated='$timecreated', active='1', contentsid='$contentsid',url='$mynoteurl' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");

        if ($role !== 'student' && $USER->id != 5 && $USER->id != 1500) $imageupload = '<span style="background-color:lightgreen;" id="image_upload" type="button" class="" data-toggle="collapse" data-target="#demo">image+</span>';
        else $imageupload = '';

        if ($npage == 1) {
            $stepbystepcnt = '<tr><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=' . $bessiboard . '&srcid=' . $wboardid . '&contentsid=' . $contentsid . '&contentstype=1&studentid=' . $studentid . '"target="_blank">' . $viewcnticon . '</a></td></tr>';
            $nextlearningurl = '';
        } elseif (strpos($topictitle, '특강') != false || strpos($topictitle, '이해') != false) {
            $timestr = date("ym");
            $wboard_retrieval = 'retrievalNote_' . $timestr . 'question' . $contentsid . '_user' . $studentid;

            $nextlearningurl = '';
        } elseif (strpos($topictitle, '유형') != false || strpos($topictitle, 'Check') != false) {
            $timestr = date("ym");
            $wboard_retrieval = 'retrievalNote_' . $timestr . 'question' . $contentsid . '_user' . $studentid;

            $nextlearningurl = '';
        } else {
            $nextlearningurl = '';
        }
        $stepbystepcnt = '<tr><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board_alt.php?id=' . $bessiboard2 . '&srcid=' . $wboardid . '&contentsid=' . $contentsid . '&contentstype=1&studentid=' . $studentid . '"target="_blank">🚩인지허들</a></td></tr>';
        if (strpos($title, '유형') != false) $contentslist2 .= '<tr style="background-color:lightpink;"><td style="width: 100%;"><span  type="button"  onClick="' . $presetfunction . '(\'' . $contentsid . '\')">' . $headimg . '</span><b> ' . $title . $audioicon . '</b> ' . $attemptresult . '</td></tr>' . $HippocampusCnt;
        elseif (strpos($title, '복습') !== false) $contentslist3 .= '<tr><td style="width: 100%;"><span  type="button"  onClick="' . $presetfunction . '(\'' . $contentsid . '\')"><img src=https://mathking.kr/Contents/IMAGES/restore.png width=15></span> ' . $title . $audioicon . ' <input type="checkbox"  onclick="changecheckbox(1,' . $studentid . ',' . $mid . ', this.checked)"/></td></tr>';
        else $contentslist .= '<tr style="background-color:lightblue;"><td style="width: 100%;"><span  type="button"  onClick="' . $presetfunction . '(\'' . $contentsid . '\')">' . $headimg . '</span><b> ' . $title . $audioicon . '</b> ' . $attemptresult . '</td></tr>' . $HippocampusCnt;

        $nnextpage = $npage + 1;
        $nextpage = $DB->get_record_sql("SELECT id, title FROM mdl_icontent_pages where cmid='$cmid' AND pagenum='$nnextpage' ORDER BY id DESC LIMIT 1");

        if (strpos($nextpage->title, '유형') != false && strpos($title, '유형') != true && $quizid != NULL || (strpos($title, '유형') != true && $quizid != NULL && $nextpage->id == NULL)) {
            $nextlearningurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&pgtype=quiz&page=' . $npage . '&studentid=' . $studentid;
            $nquizpage = $npage;
        } elseif ($nextpage->id != NULL) $nextlearningurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&page=' . $nnextpage . '&studentid=' . $studentid;

        elseif ($quizid != NULL && strpos($title, '유형') != false && $pgtype !== 'quiz') $nextlearningurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=' . $cid . '&nch=' . $nch . '&cntid=' . ($cmid + 1) . '&studentid=' . $studentid;
        else $nextlearningurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=' . $cid . '&nch=' . $nch . '&cntid=' . ($cmid + 1) . '&studentid=' . $studentid;

        $rule = '<a style="text-decoration:none;color:white;" href="' . $nextlearningurl . '"><button class="stylish-button">NEXT</button></a>';
    } else {
        if ($learningstyle === '도제' && strpos($title, '대표') !== false) echo '';
        elseif (strpos($title, '유형') != false) $contentslist2 .= '<tr><td style="width: 100%;"><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&page=' . $npage . '&studentid=' . $studentid . '">' . $headimg . ' ' . $title . '</a>' . $audioicon . $attemptresult . '</td></tr>';
        elseif (strpos($title, '복습') !== false) $contentslist3 .= '<tr><td style="width: 100%;"><a href="https://mathking.kr/Contents/IMAGES/restore.png" width=15> ' . $title . '</a>' . $audioicon . ' <input type="checkbox"  onclick="changecheckbox(1,' . $studentid . ',' . $mid . ', this.checked)"/></td></tr>';
        else $contentslist .= '<tr><td style="width: 100%;"><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&quizid=' . $quizid . '&page=' . $npage . '&studentid=' . $studentid . '">' . $headimg . ' ' . $title . '</a>' . $audioicon . $attemptresult . '</td></tr>';
    }
}

if ($role !== 'student') $cntlink = ' <a href="https://mathking.kr/moodle/mod/icontent/view.php?id=' . $cmid . '"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/cntlink.png width=15></a>';
$cntlink .= '&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editonetimeusecontents.php?cntid=' . $thispageid . '&cnttype=1"target="_blank">📰맞춤공부</a>';
$singleref = ' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/connectmemories.php?domain=8&contentstype=2"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/learningpath.png width=15></a>';

if ($quizid != NULL) {
    $cnttext2 = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid0'  ORDER BY id DESC LIMIT 1");
    if (strpos($cnttext2->reflections1, '지시사항') !== false) $HippocampusCnt = '<tr style="background-color:green;color:white;"><td><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid=' . $contentsid0 . '&cnttype=1&studentid=' . $studentid . '&wboardid=' . $wboardid . '&print=1"target="_blank">💊 준비학습 </a></td></tr>';
    if ($pgtype === 'quiz') $attemptquiz = '<tr><td style="background-color:lightblue;"><span  type="button"  onClick="' . $presetfunction . '(\'' . $contentsid0 . '\')">' . $headimg . '</span>  개념체크 퀴즈  ' . $attemptresult . ' <a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizid . '"target="_blank">(<b style="color:#E4167D;">시도</b>)</a> </td></tr>' . $HippocampusCnt;
    else $attemptquiz = '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote_full.php?dmn=' . $domain . '&cid=' . $cid . '&nch=' . $nch . '&cmid=' . $cmid . '&pgtype=quiz&quizid=' . $quizid . '&page=' . $npage . '&studentid=' . $studentid . '">' . $headimg . ' 개념체크 퀴즈</a>  <a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $quizid . '"target="_blank">(<b style="color:#E4167D;">시도</b>)</a>' . $attemptresult . '</td></tr>';
}

$activities = '';
if ($role !== 'student') $generateontology = '<a href="https://chatgpt.com/g/g-8hqsdK0XP-jujebyeol-hagseubjeonryag-ontolroji-gucug-jeonmunga" target="_blank"><img src=https://mathking.kr/Contents/IMAGES/ontologylogo.png width=20></a>';

// 우측 메뉴 자동 숨김 기능을 위한 HTML 구조 변경
echo '
<head>
  <title>' . $tabtitle . '</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container">
    <!-- Menu Toggle Button -->
    <button id="menu-toggle" class="menu-toggle">&#9776;</button>
     <button id="next-button" class="menu-toggle2" onclick=window.location.href="'.$nextlearningurl.'">➡️</button>
    <!-- Left Column: iframe -->
    <div id="left-column" class="left-column">
        <div class="iframe-container">
           <iframe id="whiteboard-iframe" loading="lazy" src="' . $showpage . '"></iframe>

        </div>
    </div>
    <!-- Right Menu -->
    <div id="right-menu" class="right-menu">
        <div class="menu-content">
            <!-- 우측 메뉴 내용 -->
            <div>
                <table style="width: 100%;">' . $contentslist . $attemptquiz . '<tr><td><br></td></tr>' . $contentslist2 . $contentslist3 . '</table><br>
                <table>
                    <tr>
                        <td style="width: 100%;"><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=' . $cid . '&nch=' . $nch . '&cntid=' . ($cmid + 1) . '&studentid=' . $studentid . '"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621944121001.png width=20> 목차</a>' . $singleref . $cntlink . '</td>
                    </tr>
                    <tr>
                        <td align=left style="color:#347aeb; word-wrap: break-word;"><br>' . $rule . ' <br><br> </td>
                    </tr>
                    <tr>
                        <td align=center><img loading="lazy" src=http://ojsfile.ohmynews.com/STD_IMG_FILE/2015/0307/IE001806909_STD.jpg width=200> <br><br> ' . $generateontology . ' 기억방으로  ' . $imageupload . '</td>
                    </tr>
                </table>
                <hr>
                <table><tr><td>' . $audiocnt . ' </td></tr>' . $stepbystepcnt . '</table>
                <table><tr><td><br>' . $activities . '</td></tr><tr><td><hr></td></tr></table>
            </div>
        </div>
    </div>
</div>

</body>
';

// JavaScript 코드 추가
echo '
<script>
window.addEventListener("message", function(event) {
    if (event.data === "hideMenu") {
        rightMenu.classList.remove("show");
    }
}, false);

var iframe = document.getElementById("whiteboard-iframe");
iframe.addEventListener("load", function() {
    try {
        iframe.contentWindow.document.addEventListener("mousedown", function() {
            rightMenu.classList.remove("show");
        });
    } catch (e) {
        // 크로스 도메인인 경우 메시지 통신 사용
    }
});

function Bridgesteps()
{
Swal.fire({
backdrop: false,position:"bottom",showCloseButton: true,width: 800,
customClass: {
  popup: "custom-sweetalert"
},
  html:
    \'<iframe style="border: 1px none; z-index:2; height:20vh;  margin-left: -3px;margin-right: -3px;margin-top: 0px; margin-bottom: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki_next.php?cntid=' . $contentsid . '&cnttype=1&studentid=' . $studentid . '&wboardid=' . $wboardid . '&print=0" ></iframe>\',showConfirmButton: false,})
}
document.getElementById("image_upload").onclick = function ()
{
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";  // 이미지 파일만 선택할 수 있도록 변경
    var object = null;
    var Contentsid = \'' . $thispageid . '\';
    alert("현재 페이지의 컨텐츠 이미지가 교체됩니다. 계속하시겠습니까 ?");
    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
        var formData = new FormData();
        formData.append("image", file);  //
        formData.append("contentsid", Contentsid);

        $.ajax({
            url: "uploadimage.php",  // 이미지를 처리할 서버의 URL
            type: "POST",
            cache: false,
            contentType: false,
            processData: false,
            data: formData,
            success: function (data, status, xhr)
            {
                var parsed_data = JSON.parse(data);
                // 이 부분에 이미지 객체를 생성하고 처리하는 로직을 추가합니다.
                object = parsed_data; // 필요에 따라 수정
                if (object)
                {
                    // 이미지 객체 처리 로직
                }
            }
        })
    }
    input.click();
}


function ConnectNeurons(Contentsid)
    {
        var Userid= \'' . $studentid . '\';

        Swal.fire({
        backdrop:false,position:"top-end",showCloseButton: true,width:1200,
           showClass: {
             popup: "animate__animated animate__fadeInDown"
          },
          hideClass: {
           popup: "animate__animated animate__fadeOutUp"
          },
          html:
            \'<iframe loading="lazy"   class="foo" style="border: 0px none; z-index:2; width:1180; height:90vh;margin-left: -20px;margin-bottom: -10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid=\'+Contentsid+\'&cnttype=1&studentid=\'+Userid+\'" ></iframe>\',
          showConfirmButton: true,
          })
    }
  function InputAnswers()
    {
        Swal.fire({
        backdrop:false,position:"top",showCloseButton: true,width:500,
           showClass: {
             popup: "animate__animated animate__fadeInDown"
          },
          hideClass: {
           popup: "animate__animated animate__fadeOutUp"
          },
          html:
            \'<iframe loading="lazy"   class="foo" style="border: 0px none; z-index:2; width:470; height:30vh;margin-left: -20px;margin-bottom: -10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/inputanswers.php?srcid=' . $srcid . '" ></iframe>\',
          showConfirmButton: true,
                })
    }

// 우측 메뉴 자동 닫힘 기능
var rightMenu = document.getElementById("right-menu");
var menuToggle = document.getElementById("menu-toggle");
var menuTimer;

function showRightMenu() {
    rightMenu.classList.add("show");
    clearTimeout(menuTimer);
    menuTimer = setTimeout(function() {
        rightMenu.classList.remove("show");
    }, 5000); // 5초 후에 메뉴 숨김
}

// 페이지 로드 시 메뉴 표시
showRightMenu();

// 메뉴 토글 버튼 클릭 시 메뉴 열기
menuToggle.addEventListener("click", function() {
    showRightMenu();
});

// 사용자가 메뉴를 마우스로 호버하면 자동 닫힘 취소
rightMenu.addEventListener("mouseover", function() {
    clearTimeout(menuTimer);
});

// 마우스가 메뉴를 벗어나면 다시 자동 닫힘 설정
rightMenu.addEventListener("mouseout", function() {
    menuTimer = setTimeout(function() {
        rightMenu.classList.remove("show");
    }, 5000);
});

// 메뉴 내부의 링크를 클릭하면 메뉴를 즉시 닫음
rightMenu.addEventListener("click", function(event) {
    if (event.target.tagName === "A") {
        rightMenu.classList.remove("show");
    }
});
// 글씨 쓰기 이벤트 감지
function onWrite() {
    // 부모 창으로 메시지 전송
    window.parent.postMessage("hideMenu", "*");
}

// 글씨 쓰기 이벤트에 onWrite 함수 연결
canvas.addEventListener("mousedown", onWrite);

// 화이트보드에서 메시지 수신
window.addEventListener("message", function(event) {
    if (event.data === "hideMenu") {
        rightMenu.classList.remove("show");
    }
}, false);

</script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';

echo '<style>
.menu-toggle2 {
    position: absolute;
    bottom: 20px;
    right: 70px;
    z-index: 1100;
    background-color: #76c7c0;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu-toggle2:hover {
    background-color: #5aa5a0;
}

/* Reset CSS */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
/* Basic styling */
html, body {
    height: 100%;
    overflow: hidden; /* Prevent body scrollbars */
}
body {
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
}
.container {
    display: flex;
    flex-direction: row;
    height: 100%;
    width: 100%;
    margin: 0;
    padding: 0;
    position: relative;
}
.left-column {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    display: flex;
    flex-direction: column;
    position: relative;
}
.right-menu {
    position: absolute;
    top: 0;
    right: 0;
    width: 300px; /* 메뉴 너비 */
    height: 100%;
    background-color: #ffffff;
    border-left: 1px solid #ccc;
    overflow-y: auto;
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
    z-index: 1000;
}
.right-menu.show {
    transform: translateX(0);
}
.right-menu .menu-content {
    padding: 10px;
}
/* Iframe styling */
.iframe-container {
    flex-grow: 1;
    position: relative;
}
.iframe-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}
/* Menu Toggle Button */
.menu-toggle {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1100;
    background-color: #76c7c0;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.menu-toggle:hover {
    background-color: #5aa5a0;
}
/* Custom styles */
img {
  user-drag: none; /* for WebKit browsers including Chrome */
  user-select: none; /* for standard-compliant browsers */
  -webkit-user-drag: none; /* for Safari and Chrome */
  -webkit-user-select: none; /* for Safari */
  -moz-user-select: none; /* for Firefox */
  -ms-user-select: none; /* for Internet Explorer/Edge */
}
.custom-sweetalert {
  border: 3px solid black !important;
}
a {
  user-drag: none; /* for WebKit browsers including Chrome */
  user-select: none; /* for standard-compliant browsers */
  -webkit-user-drag: none; /* for Safari and Chrome */
  -webkit-user-select: none; /* for Safari */
  -moz-user-select: none; /* for Firefox */
  -ms-user-select: none; /* for Internet Explorer/Edge */
}
.stylish-button {
  background-color: #FF69B4; /* 네온 핑크 색상 */
  color: white;
  padding: 5px 5px;
  width:6vw;
  border: none;
  cursor: pointer;
  font-family: "Arial Rounded MT Bold", sans-serif;
  font-size: 16px;
  transition: background-color 0.3s ease;
}

.stylish-button:hover {
  background-color: #FF1493; /* 색상을 조금 더 진하게 */
}

.stylish-button:active {
  transform: translateY(2px);
}

.stylish-button:focus {
  outline: none;
}

.icon {
  padding-left: 5px;
}
#typing-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    padding: 0px;
  }
   
  #typing-box {
    width: 90%;
    padding:0px;
    border-radius: 10px;
    background-color: #f5f5f5;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  #typing-cursor {
    width: 5px;
    height: 20px;
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
  #typing-text {
    font-size: 20px;
    line-height: 1.5;
    margin-left:0px;
    margin-top: 5px;
  }
  
  @media (max-width: 767px) {
    /* Set font size for screens smaller than 768px (smartphones) */
    #typing-text {
      font-size: 20px;
    }
  }
  
</style>

<script>
var text = "<?php echo $gpttalk; ?>";
var lines = text.split("\n");
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
?>
