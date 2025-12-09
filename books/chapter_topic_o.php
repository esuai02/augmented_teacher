<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$cid = $_GET["cid"]; 
$chnum = $_GET["nch"]; 
$type = $_GET["type"]; 
$mode = $_GET["mode"]; 
$domain = $_GET["domain"]; 
$stage = $_GET["stage"]; // 개념, 중급노트, 유형, 심화노트, 심화, 내신T, 수능, 경시
$thiscntid = $_GET["cntid"]; 
$studentid = $_GET["studentid"]; 
if($studentid == NULL) $studentid = $USER->id;
$timecreated = time(); 
$username = $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname = $username->firstname.$username->lastname;
$chnum0 = $chnum;

// 최근 저장된 단원 불러오기
$lastchapter = $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
if($cid == NULL) $cid = $lastchapter->cid;

// 본인일 때만 메시지 출력
if($USER->id == $studentid) include("../message.php");

// ai 스텝 가져오기(특정 로직 사용)
$indic = $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
$createmode = $indic->aistep; 
if($createmode == 7) $cmodeimg = 'createcontents';
else $cmodeimg = 'timefolding';

// 기존 로그 확인
$checklog = $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog WHERE userid='$studentid' AND cid='$cid' ORDER BY id DESC LIMIT 1");
if($type === 'init' && $checklog->id != NULL) {
    $chnum = $checklog->nch;
    $thiscntid = $checklog->cntid;
}

// 최근 12시간 내 기록 
$timeback = $timecreated - 43200;
$checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
    WHERE userid='$studentid' 
    AND (type LIKE '오늘목표' OR type LIKE '검사요청') 
    AND timecreated>'$timeback' 
    ORDER BY id DESC LIMIT 1");
if($checkgoal->id != NULL) $todaygoal = '🕸️ '.$checkgoal->text;
else $todaygoal = '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=1491">목표 입력하기</a>';

// 학습스타일 정보
$lstyle = $DB->get_record_sql("SELECT data FROM mdl_user_info_data 
    WHERE userid='$studentid' AND fieldid='90' ORDER BY id DESC LIMIT 1"); 
$learningstyle = $lstyle->data;
if($thiscntid == NULL) $thiscntid = 0;

// 로그 기록
if($USER->id == $studentid) {
    $DB->execute("INSERT INTO {abessi_chapterlog} (userid,cid,nch,cntid,timecreated) 
        VALUES('$studentid','$cid','$chnum','$thiscntid','$timecreated')");
}

// domain 탐색
for($ndm=120; $ndm<=136; $ndm++){
    $dminfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$ndm'");
    for($ncid=1; $ncid<=20; $ncid++){
        $cidstr = 'cid'.$ncid;
        if($cid == $dminfo->$cidstr){
            $nchstr = 'nch'.$ncid;
            if($dminfo->$nchstr == $chnum){
                $domain = $ndm;
                break 2;
            }
        } else {
            continue;
        }
    }
}

if($studentid == NULL) $studentid = $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data 
    WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->role;

// 헤더 타이틀
if($role === 'student') {
    echo ' <head><title>'.$studentname.' 개념노트</title></head><body>';    
} else {
    echo ' <head><title>개념노트</title></head><body>';
}

// 커리큘럼 정보
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'");
if($curri->id>=80 && $curri->id<=94) $dmn='science';
else $dmn='math';
$ankisbjt = $curri->sbjt;
$domainname = $curri->subject;
$subjectname = $curri->name;
$chapnum = $curri->nch;

// 단원 리스트
$chapterlist = '';
for($nch=1;$nch<=$chapnum;$nch++) {
    $chname = 'ch'.$nch;
    $title = $curri->$chname;
    $qid = 'qid'.$nch;
    $qid = $curri->$qid;
    if($title == NULL) continue;

    $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$qid'");
    $attemptlog = $DB->get_record_sql("SELECT id,quiz,sumgrades,attempt,timefinish 
        FROM mdl_quiz_attempts WHERE quiz='$moduleid->instance' AND userid='$studentid' 
        ORDER BY id DESC LIMIT 1");
    $timefinish = date("m/d | H:i",$attemptlog->timefinish);  
    $quiz = $DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz WHERE id='$moduleid->instance'");
    $quizgrade = round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
    $quizresult = '';
    if($quizgrade != NULL) {
        $quizresult = '<span style="color:lightgrey;">'.$quizgrade.'점 ('.$attemptlog->attempt.'회)</span>';
    }
    
    if($nch == $chnum) { 
        $thischtitle = $curri->$chname;
        $cntstr = 'cnt'.$nch;
        $checklistid = $curri->$cntstr;
        $chapterlist .= '<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'"target="_blank"><b>'.$title.'</b></a> '.$quizresult.'</td></tr>';
    } else {
        $chapterlist .= '<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?'.$modeinfo.'&cid='.$cid.'&nch='.$nch.'&studentid='.$studentid.'">'.$title.'</a>'.$quizresult.'</td></tr>';
    }
}

// checklist 아이템 불러오기
$chklist = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$checklistid' ORDER BY id DESC LIMIT 1");
$topics = $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$chklist->instance' ORDER BY position ASC");
$result = json_decode(json_encode($topics), true);

$ntopic = 1; 
$nchk = 0; 
$npassed = 0; 
$nstage = 0; 
$nanki = 0;
$topiclist = '';

// 토픽별 카드 구성
foreach($result as $value) {
    $chkitemid = $value['id']; 
    $checkstatus = '';
    $nview = 0;

    $chkitem = $DB->get_record_sql("SELECT usertimestamp FROM mdl_checklist_check 
        WHERE item='$chkitemid' AND userid='$studentid' 
        ORDER BY id DESC LIMIT 1");
    if($chkitem->usertimestamp > 1) {
        $checkstatus = "checked";
        $npassed++;
    }

    $ncolap = $value['position']; 
    $linkurl = $value['linkurl']; 
    $displaytext = $value['displaytext']; 
    $thismenutext = $displaytext;

    // 노트(가림)
    if(strpos($displaytext, '노트_') !== false) continue;

    $url_components = parse_url($linkurl);
    parse_str($url_components['query'], $params);

    $scriptontopic = $value['script'];
    // 변경점: collapse 상태 지정
    $collapseClass = 'collapse';

    $quizresult = '';
    $retrieval = '';
    $setgoal = '<span onclick="setGoal(\''.$thismenutext.'\');">➕</span>';

    // -------------------------------------------
    // 전자책(icontent)인 경우
    // -------------------------------------------
    if(strpos($linkurl, 'icontent') !== false) {
        $nchk++;
        $nview = 1;
        $cntid = $params['id']; 
        $quizid = $params['quizid'];

        // retrieval Note id
        $getimg = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cntid'");
        $getpagenum = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cntid' ORDER BY pagenum DESC LIMIT 1");  
        $pagenum = $getpagenum->pagenum;

        // 실제 저장된 페이지 내 첫 번째 이미지
        $defaulttalk = '<img loading="lazy" src="https://mathking.kr/Contents/IMAGES/createnote.png" width="800px"><br>학습 개요';
        if($getimg && $getimg->pageicontent) {
            $htmlDom = new DOMDocument;
            @$htmlDom->loadHTML($getimg->pageicontent);
            $imageTags = $htmlDom->getElementsByTagName('img');
            $foundImg = '';
            foreach($imageTags as $imageTag) {
                $imgSrc = $imageTag->getAttribute('src');
                $imgSrc = str_replace(' ', '%20', $imgSrc);
                $foundImg = $imgSrc; 
                break;
            }
            if($foundImg) {
                $defaulttalk = '<img loading="lazy" src="'.$foundImg.'" width="800px">';
            }
        }
        $scriptontopic = $defaulttalk;

        if(strpos($displaytext, '도약') !== false ) {
            if($checkstatus === 'checked') $nstage=0;  
            $todoitem = '<a href="https://mathking.kr/moodle/local/augmented_teacher/books/sessionnote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$chnum.'&cmid='.$cntid.'&page=1&studentid='.$studentid.'&quizid='.$quizid.'">
                <button class="stylish-button">NEXT</button></a>';
        }
        elseif(strpos($displaytext, '유형') !== false) {
            if($checkstatus === 'checked') $nstage=2;  
            $todoitem = '
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/KeyPatternsGame.php?&cid='.$cid.'&cntid='.$cntid.'&studentid='.$studentid.'">
                <img loading="lazy" style="margin-bottom:7px;" 
                    src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic.png" width="20">
            </a> 
            <a href="https://mathking.kr/moodle/local/augmented_teacher/books/sessionnote.php?dmn='.$domain.'&cid='.$cid.'&nch='.$chnum.'&cmid='.$cntid.'&page=1&studentid='.$studentid.'&quizid='.$quizid.'">
                <button class="stylish-button">NEXT</button>
            </a>';
        } else {
            $todoitem = '';
        }
        $ntopic++;
    }
    // -------------------------------------------
    // 퀴즈(유형정복/마무리 등)
    // -------------------------------------------
    elseif(strpos($displaytext, '정복') !== false && $learningstyle !== '도제') {
        $nchk++;
        $nview=1;
        $quizid = $params['id'];
        $defaulttalk = '<img loading="lazy" 
            src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/exercise.png" width="60%">
            <br><br>시작 전 대표유형 문항들을 여러 번 풀고 숙달하세요<br>';
        $scriptontopic = $defaulttalk;
        $displaytext = '<b>'.$displaytext.'</b>';

        $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$quizid'");
        $attemptlog = $DB->get_record_sql("SELECT id,quiz,attempt,sumgrades,timefinish 
            FROM mdl_quiz_attempts WHERE quiz='$moduleid->instance' AND userid='$studentid' 
            ORDER BY id DESC LIMIT 1");
        $timefinish = date("m/d | H:i", $attemptlog->timefinish);  
        $quiz = $DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz WHERE id='$moduleid->instance'");
        $quizgrade = round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
        $quizresult = $quizgrade.'점 ('.$timefinish.' | '.$attemptlog->attempt.'회)';
        $todoitem = '
        <img loading="lazy" style="margin-bottom:7px;" 
            src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic2.png" width="20"> 
        <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'" target="_blank">
            <button class="stylish-button">NEXT</button>
        </a> (랜덤 2문항 | 8분 | 3연속 100점 통과)';
    }
    elseif(strpos($displaytext, '마무리') !== false && $learningstyle !== '도제') {
        $nchk++;
        $nview=1;
        $quizid = $params['id'];
        $defaulttalk = '<img loading="lazy" 
            src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/ilovemath.png" width="60%">
            <br><br>시작 전 유형정복 문제들을 모두 통과하세요<br>';
        $scriptontopic = $defaulttalk;
        $displaytext = '<span style="font-size:18px;color:#1F55F7;"><b>단원 마무리 T : '.$thischtitle.'</b></span>';

        $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$quizid'");
        $attemptlog = $DB->get_record_sql("SELECT id,quiz,sumgrades,timefinish 
            FROM mdl_quiz_attempts WHERE quiz='$moduleid->instance' AND userid='$studentid' 
            ORDER BY id DESC LIMIT 1");
        $timefinish = date("m/d | H:i",$attemptlog->timefinish);  
        $quiz = $DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz WHERE id='$moduleid->instance'");
        $quizgrade = round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
        $quizresult = $quizgrade.'점 ('.$timefinish.' | '.$attemptlog->attempt.'회)';

        if(strpos($linkurl, 'checklist') !== false) {
            $finishingchapterquiz = '
            <a href="'.$linkurl.'" target="_blank">
                <button class="stylish-button">NEXT</button>
            </a>';
        } else {
            $finishingchapterquiz = '
            <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizid.'" target="_blank">
                <button class="stylish-button">NEXT</button>
            </a>';
        }
        $todoitem = '
        <img loading="lazy" style="margin-bottom:7px;" 
            src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/topic3.png" width="20"> 
        '.$finishingchapterquiz.' (90점 통과 | 50분)';
    } else {
        continue;
    }

    // -------------------------------------------
    // Wizard Card 구성
    // -------------------------------------------
    {
        $personaBtn = '';
        if(strpos($displaytext, '도약') !== false && isset($cntid)) {
            $personaBtn = '<button class="btn btn-warning btn-sm ml-2" onclick="openPersonaPopup(\''.$getimg->id.'\', \''.$studentid.'\');">🎭진단</button>';
        }
        $topiclist .= '
        <div class="wizard-step card" style="font-size:16;">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" name="checkAccount" '.$checkstatus.' 
                            data-toggle="collapse"
                            data-target="#collapse-'.$chkitemid.'"
                            aria-expanded="true"
                            aria-controls="collapse-'.$chkitemid.'"
                            onClick="CheckProgress(2,\''.$studentid.'\',\''.$chkitemid.'\', this.checked)"/> 
                        <span style="font-size:18;color:#4287f5;">'.$displaytext.'</span>
                        <span style="margin-left:15px;color:gray;font-size:14px;">'.$quizresult.'</span>
                    </div>
                    <div>
                        '.$personaBtn.'
                        '.$setgoal.'
                    </div>
                </div>
            </div>
            <div id="collapse-'.$chkitemid.'" class="'.$collapseClass.' card-body">
                '.$scriptontopic.'<br>
                '.$todoitem.'
            </div>
        </div>';
    }
}

$progressfilled = 0;
if($nchk > 0) {
    $progressfilled = round($npassed / $nchk * 100, 1);
}
if($progressfilled < 20) $bgtype = 'alert';
elseif($progressfilled < 40) $bgtype = 'info';
elseif($progressfilled < 60) $bgtype = 'primary';
elseif($progressfilled < 80) $bgtype = 'danger';
else $bgtype = 'success';

$timefolding = '<img style="margin-top:5px;" 
    onclick="ImmersiveSession(4,\''.$studentid.'\',\''.$curri->id.'\',\''.$domain.'\',\''.$chnum.'\',\''.$thiscntid.'\')" 
    src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/'.$cmodeimg.'.png" width=40>'; 

$dashbordtitle = $chnum.'단원. '.$thischtitle;
if($role !== 'student') {
    $alt42game = '
        <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/games/dashboard.php?
            cid='.$cid.'&studentid='.$studentid.'&title='.$dashbordtitle.'" target="_blank">
            <img style="margin-bottom:7px;" src="https://mathking.kr/Contents/IMAGES/joystick.png" width="35">
        </a>&nbsp;&nbsp;
        <a style="font-size:28px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?
            userid='.$studentid.'&mode=1" accesskey="w">
            🎙️
        </a>';
} else {
    $alt42game = '';
}

$progressbar = '
<div class="progress" style="background-color:#bdbdbd; height:15px;">
    <div class="progress-bar progress-bar-striped bg-'.$bgtype.'" role="progressbar" 
        style="width: '.$progressfilled.'%; height: 15px;" 
        aria-valuenow="'.$progressfilled.'" aria-valuemin="0" aria-valuemax="100">
    </div>
</div>
';

echo '
<!DOCTYPE html>
<html>
<head>
  <title>단원 학습 진행</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & jQuery -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- SweetAlert2 라이브러리 추가 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    /* Updated Visual Design */
    body {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: #fff;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 20px;
    }
    img {
      user-drag: none; 
      user-select: none; 
      -webkit-user-drag: none; 
      -webkit-user-select: none; 
      -moz-user-select: none; 
      -ms-user-select: none;
    }
    .stylish-button {
      background-color: #007bff;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .stylish-button:hover {
      background-color: #0056b3;
    }
    .stylish-button:active {
      transform: scale(0.98);
    }
    .stylish-button:focus {
      outline: none;
    }
    .wizard-step {
      margin-bottom: 15px;
      background-color: #ffffff;
      color: #333;
      border: none;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .wizard-step .card-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      font-size: 18px;
      font-weight: bold;
    }
    .wizard-step .card-body {
      background-color: #ffffff;
      font-size: 16px;
      padding: 15px;
    }
    .wizard-step input[type="checkbox"] {
      margin-right: 10px;
    }
    .progress {
      background-color: rgba(255,255,255,0.3);
      height: 15px;
      border-radius: 7px;
    }
    .progress .progress-bar {
      background-color: #28a745;
      border-radius: 7px;
      transition: width 0.5s ease;
    }
  </style>
</head>
<body>

<div class="container mt-3">
    <h3>'.$dashbordtitle.' 학습 단계</h3>
    <p>( 진행률 : '.$progressfilled.'% )</p>
    '.$progressbar.'
    <small>※ 제목을 보고 스스로 개념을 떠올려 본 뒤, 카드를 펼쳐 자세히 확인하세요.</small>
    <br><br>
    
    <!-- 단계별 카드 (Wizard Step) -->
    '.$topiclist.'
    

</div>

<script src="https://d3js.org/d3.v5.min.js"></script>

<script>
// ImmersiveSession
function ImmersiveSession(Eventid,Userid,Cid,Domainid,Chapterid,Topicid) {
    var Createmode = \''.$createmode.'\';
    if(Createmode == 7) {
        alert("독립세션 설계모드를 종료합니다.");
    } else {
        alert("독립세션 설계모드를 시작합니다.");
    }
    setTimeout(function(){ location.reload(); }, 1000);
}

// CheckProgress (체크 후 섹션 접힘 유지)
function CheckProgress(Eventid,Userid,Itemid,Checkvalue){
    var checkimsi = 0;
    if(Checkvalue == true) { checkimsi = 1; }
    $.ajax({
        url: "check_status.php",
        type: "POST",
        dataType: "json",
        data : {
            "userid": Userid,
            "cntid": Itemid,
            "checkimsi": checkimsi,
            "eventid": Eventid
        },
        success: function (data){}
    });
}

// 목표 설정
function setGoal(Inputtext){
    var Userid = \''.$studentid.'\';
    alert("학습 목표를 설정합니다.");
    window.open("https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=" 
        + Userid + "&cntinput=" + Inputtext );
}

function openPersonaPopup(cntid, studentid) {
    Swal.fire({
        html: `<iframe src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/selectpersona.php?cnttype=1&type=contents&cntid=${cntid}&userid=${studentid}" style="width:100%; height:100vh; border:0;" scrolling="no"></iframe>`,
        width: "100vw",
        height: "100vh",
        customClass: {
            popup: "swal-maximized"
        },
        showCloseButton: false,
        scrollbarPadding: false,
        confirmButtonText: "닫기"
    });
}

document.addEventListener("DOMContentLoaded", function(){
    var chaptersData = [
        ';
        $lines = explode("\n", $chapterlist);
        foreach($lines as $line) {
            if(preg_match('/<tr><td>(\d+)<\/td><td><a href="([^"]+)">([^<]+)<\/a>(.*?)<\/td><\/tr>/', $line, $m)){
                $nchJs = $m[1];
                $linkJs = $m[2];
                $titleJs= strip_tags($m[3]);
                echo '{id:"ch'.$nchJs.'", title:"'.$titleJs.'", link:"'.$linkJs.'"},';
            }
        }
        echo '
    ];
    var nodes = chaptersData.map(function(d){ return { id:d.id, title:d.title, link:d.link }; });
    var links = [];
    for(var i=0; i<nodes.length-1; i++){
        links.push({source:nodes[i].id, target:nodes[i+1].id});
    }
    var width = 450, height=400;
    var svg = d3.select("#topicMap")
                .append("svg")
                .attr("width", width)
                .attr("height", height);
    var simulation = d3.forceSimulation(nodes)
        .force("link", d3.forceLink(links).id(function(d){return d.id; }).distance(80))
        .force("charge", d3.forceManyBody().strength(-300))
        .force("center", d3.forceCenter(width/2, height/2));
    var link = svg.selectAll(".link")
        .data(links).enter().append("line")
        .attr("class", "link")
        .style("stroke", "#999")
        .style("stroke-opacity", 0.6)
        .style("stroke-width", "1.5px");
    var node = svg.selectAll(".node")
        .data(nodes).enter().append("g")
        .attr("class", "node")
        .call(d3.drag()
            .on("start", dragStarted)
            .on("drag", dragged)
            .on("end", dragEnded)
        );
    node.append("circle")
        .attr("r", 18)
        .style("fill", "#4287f5");
    node.append("text")
        .attr("dx", -15)
        .attr("dy", 4)
        .style("fill", "#fff")
        .text(function(d){ return d.id; });
    node.on("click", function(d){
        window.open(d.link, "_blank");
    });
    simulation.on("tick", function(){
        link
            .attr("x1", function(d){ return d.source.x; })
            .attr("y1", function(d){ return d.source.y; })
            .attr("x2", function(d){ return d.target.x; })
            .attr("y2", function(d){ return d.target.y; });
        node
            .attr("transform", function(d){
                return "translate(" + d.x + "," + d.y + ")";
            });
    });
    function dragStarted(d){
        if(!d3.event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x; d.fy = d.y;
    }
    function dragged(d){
        d.fx = d3.event.x; d.fy = d3.event.y;
    }
    function dragEnded(d){
        if(!d3.event.active) simulation.alphaTarget(0);
        d.fx = null; d.fy = null;
    }
});
</script>
<style>
.swal-maximized {
    height: 100vh;
    max-height: 100vh;
    margin: 0;
    padding: 0;
}
</style>
</body>
</html>
';
?>
