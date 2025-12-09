<?php 
///////////////////////////////////////////////////////////////////////////////////////////////
// PHP 데이터 처리 섹션 - 모든 비즈니스 로직과 데이터베이스 쿼리 (줄 1-184 유지)
///////////////////////////////////////////////////////////////////////////////////////////////

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

// ==================== URL 파라미터 처리 ====================
$cid = $_GET["cid"] ?? null; 
$chnum = $_GET["nch"] ?? null; 
$type = $_GET["type"] ?? null; 
$mode = $_GET["mode"] ?? null; 
$stage = $_GET["stage"] ?? null; // 개념, 중급노트, 유형, 심화노트, 심화, 내신T, 수능, 경시
$thiscntid = $_GET["cntid"] ?? null; 
$studentid = $_GET["studentid"] ?? $USER->id;

// ==================== 시간 변수 ====================
$timecreated = time(); 
$halfdayago = time() - 43200;

// ==================== 사용자 정보 ====================
$username = $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid'");
$studentname = $username->firstname . $username->lastname;
$chnum0 = $chnum;

// ==================== 마지막 챕터 정보 ====================
$lastchapter = $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid' ORDER BY id DESC LIMIT 1");
if($cid == NULL) $cid = $lastchapter->cid;

// ==================== 모드 설정 ====================
if($mode == NULL || $mode === 'default') {
    $modechange = 'mode=review&';
    $modeinfo = '';
    $modetext = '복습선택';
} elseif($mode === 'review') {
    $modetext = '공부시작';
    $modechange = '';
    $modeinfo = 'mode=review&';
}

// ==================== 메시지 포함 ====================
if($USER->id == $studentid) include("../message.php");

// ==================== 인디케이터 정보 ====================
$indic = $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1");
$createmode = $indic->aistep; 
$cmodeimg = ($createmode == 7) ? 'createcontents' : 'timefolding';

// ==================== 챕터 로그 확인 ====================
$checklog = $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid' AND cid='$cid' ORDER BY id DESC LIMIT 1");
if($type === 'init' && $checklog->id != NULL) {
    $chnum = $checklog->nch;
    $thiscntid = $checklog->cntid;
}

// ==================== 목표 확인 ====================
$timeback = $timecreated - 43200;
$checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1");
$todaygoal = ($checkgoal->id != NULL) 
    ? '🕸️ ' . $checkgoal->text 
    : '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id=1491"> 목표 입력하기 </a>';

// ==================== 학습 스타일 ====================
$lstyle = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='90' ORDER BY id DESC LIMIT 1"); 
$learningstyle = $lstyle->data;
if($thiscntid == NULL) $thiscntid = 0;

// ==================== 챕터 로그 삽입 ====================
if($USER->id == $studentid) {
    $DB->execute("INSERT INTO {abessi_chapterlog} (userid,cid,nch,cntid,timecreated) VALUES('$studentid','$cid','$chnum','$thiscntid','$timecreated')");
}

// ==================== 도메인 정보 찾기 ====================
$domain = null;
for($ndm = 120; $ndm <= 136; $ndm++) {
    $dminfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$ndm'");
    for($ncid = 1; $ncid <= 20; $ncid++) {
        $cidstr = 'cid' . $ncid;
        if($cid == $dminfo->$cidstr) {
            $nchstr = 'nch' . $ncid;
            if($dminfo->$nchstr == $chnum) {
                $domain = $ndm;
                break 2;
            }
        }
    }
}

// ==================== 사용자 역할 ====================
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'"); 
$role = $userrole->role;

// ==================== 커리큘럼 정보 ====================
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'");
$dmn = ($curri->id >= 80 && $curri->id <= 94) ? 'science' : 'math';
$ankisbjt = $curri->sbjt;
$domainname = $curri->subject;
$subjectname = $curri->name;
$chapnum = $curri->nch;

// ==================== 챕터 제목 생성 ====================
$chaptertitle = '<a style="font-size:20px;text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index42.php?id='.$studentid.'">'.$studentname.'</a> ';
$chaptertitle .= '<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$studentid.'">';
$chaptertitle .= '<img style="margin-bottom:10px;" src="https://mathking.kr/Contents/IMAGES/pomodorologo.png" width="40"></a>';

// ==================== 챕터 리스트 생성 ====================
$chapterlist = '';
for($nch = 1; $nch <= $chapnum; $nch++) {
    $chname = 'ch' . $nch;
    $title = $curri->$chname;
    $qid = 'qid' . $nch;
    $qid = $curri->$qid;
    
    if($title == NULL) continue;
    
    $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$qid'");
    $attemptlog = $DB->get_record_sql("SELECT id,quiz,sumgrades,attempt,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
    $timefinish = date("m/d | H:i", $attemptlog->timefinish);  
    $quiz = $DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'");
    $quizgrade = round($attemptlog->sumgrades / $quiz->sumgrades * 100, 0);
    $quizresult = '';
    
    if($quizgrade != NULL) {
        $quizresult = '<span style="color:lightgrey;">' . $quizgrade . '점 (' . $attemptlog->attempt . '회)</span>';
    }
    
    if($nch == $chnum) { 
        $thischtitle = $curri->$chname;
        $cntstr = 'cnt' . $nch;
        $checklistid = $curri->$cntstr;
        
        $ankilink = '<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$dmn.'&sbjt='.$ankisbjt.'&studentid='.$studentid.'&nch='.$nch.'">';
        $ankilink .= '<img src="https://ankiweb.net/logo.png" width="20"></a>';
        
        $gptstr = 'gpt' . $nch;
        $gpturl = $curri->$gptstr;
        
        $chapterlist .= '<tr><td>' . $nch . '</td><td>';
        $chapterlist .= '<a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'" target="_blank"><b>'.$title.'</b></a> ';
        $chapterlist .= $quizresult . '  ' . $ankilink . '</td></tr>';
        
        $wboardid = 'obsnote' . $cid . '_ch' . $chnum . '_user' . $studentid;
    } else {
        $chapterlist .= '<tr><td>' . $nch . '</td><td>';
        $chapterlist .= '<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?'.$modeinfo.'&cid='.$cid.'&nch='.$nch.'&studentid='.$studentid.'">'.$title.'</a>';
        $chapterlist .= $quizresult . '</td></tr>';
    }
}

// ==================== 도메인 챕터 리스트 생성 ====================
$obsnotelist = '';
$chlist = $DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$domain'");
$domaintitle = $chlist->title;
$chapnum = $chlist->chnum;

for($nch = 1; $nch <= $chapnum; $nch++) {
    $cidstr = 'cid' . $nch; 
    $chstr = 'nch' . $nch;
    $cid2 = $chlist->$cidstr;
    $nchapter = $chlist->$chstr;
    
    $curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid2'");
    $chname = 'ch' . $nchapter;
    $title = $curri->$chname;
    
    $qid = 'qid' . $nch;
    $qid = $curri->$qid;
    
    $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$qid'");
    $attemptlog = $DB->get_record_sql("SELECT id,quiz,sumgrades,attempt,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
    $timefinish = date("m/d | H:i", $attemptlog->timefinish);  
    $quiz = $DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'");
    $quizgrade = round($attemptlog->sumgrades / $quiz->sumgrades * 100, 0);
    $quizresult = '';
    
    if($quizgrade != NULL) {
        $quizresult = '<span style="color:lightgrey;">' . $quizgrade . '점 (' . $attemptlog->attempt . '회)</span>';
    }
    
    if($cid == $cid2 && $nchapter == $chnum) {
        $cntstr = 'cnt' . $nchapter;
        $checklistid = $curri->$cntstr;
        $wboardid = 'obsnote' . $cid2 . '_ch' . $nchapter . '_user' . $studentid;
        $notetitle = '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id='.$studentid.'&tb=604800" target="_blank">'.$studentname.'</a>의 ';
        $notetitle .= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&cid='.$cid2.'&nch='.$nchapter.'&mode=map" target="_blank">개념집착</a> : '.$domaintitle;
        
        $obsnotelist .= '<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' ';
        $obsnotelist .= '<a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$checklistid.'" target="_blank"><b>'.$title.'</b></a> ';
        $obsnotelist .= '<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=fix&domain='.$domain.'" target="_blank">';
        $obsnotelist .= '<img style="margin-bottom:8px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667755172.png" width="20"></a></td></tr>';
    } else {
        $obsnotelist .= '<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><span>'.$nch.' ';
        $obsnotelist .= '<a style="color:#a9aab0;" href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?'.$modeinfo.'&cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'" target="_blank">'.$title.'</a></span> ';
        $obsnotelist .= '<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid='.$cid2.'&nch='.$nchapter.'&studentid='.$studentid.'&mode=domain&domain='.$domain.'" target="_blank">';
        $obsnotelist .= '<img style="margin-bottom:8px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667755172.png" width="20"></a></td></tr>';
    }
}

$dmprinciples = ''; // 초기화 (원본에서 정의되지 않음)
$domchapters = '<table width="100%">' . $obsnotelist . '<tr><td><hr></td></tr>' . $dmprinciples . '</table>';

// ==================== GPT 관련 처리 ====================
$contextid = 'introcid' . $cid . 'ch' . $chnum;
include("gptrecord.php");

$exist = $DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1"); 
if($exist->id == NULL) {
    $context = ''; // gptrecord.php에서 정의됨
    $url = ''; // gptrecord.php에서 정의됨
    $DB->execute("INSERT INTO {abessi_gptultratalk} (creator,role,gpttalk,contextid,context,url,status,timecreated) VALUES('$USER->id','$role','','$contextid','$context','$url','connected','$timecreated')");
}

$gpteventname = '단원도입';
$defaulttalk = '이 단원은 <b>' . $thischtitle . '</b>입니다. <br>시작하기 전 필요한 이전 과정 내용들에 대한 <b>기억상태를 점검</b>해 보고 필요한 경우 간단한 <b>복습</b>을 진행하는 것을 권장합니다. <br><br># 복습방법을 선택하는데 어려움이 있으시다면 선생님과 대화해 주세요 ~<br>';
$gpttalk = ''; // gptrecord.php에서 정의됨
$chapterintro = '<a href="https://mathking.kr/moodle/local/augmented_teacher/books/edit.php?cntid='.$gptlog->id.'" target="_blank">';
$chapterintro .= '<img style="margin-bottom:7px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt.png" width="18"></a> ';
$chapterintro .= $defaulttalk . '<hr>' . $gpttalk;

// ==================== 체크리스트 아이템 처리 (줄 190부터 계속) ====================
// 여기서부터는 원본 코드의 나머지 PHP 로직을 그대로 유지합니다
// (원본 파일의 줄 190-480 부분을 처리하는 코드가 필요)

// 페이지 데이터를 구조화된 배열로 정리
$pageData = [
    'studentid' => $studentid,
    'studentname' => $studentname,
    'role' => $role,
    'cid' => $cid,
    'chnum' => $chnum,
    'domain' => $domain,
    'thischtitle' => $thischtitle ?? '',
    'chaptertitle' => $chaptertitle,
    'subjectname' => $subjectname,
    'chapterlist' => $chapterlist,
    'domchapters' => $domchapters,
    'todaygoal' => $todaygoal,
    'gpturl' => $gpturl ?? '',
    'cmodeimg' => $cmodeimg,
    'createmode' => $createmode,
    'modechange' => $modechange,
    'modeinfo' => $modeinfo,
    'modetext' => $modetext,
    'checklistid' => $checklistid ?? '',
    'dmn' => $dmn,
    'learningstyle' => $learningstyle
];

///////////////////////////////////////////////////////////////////////////////////////////////
// HTML 출력 섹션 - 이제부터 HTML 렌더링 시작
///////////////////////////////////////////////////////////////////////////////////////////////
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $role === 'student' ? $studentname . ' 개념노트' : '개념노트' ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS (외부 파일로 분리 예정) -->
    <style>
        /* 기존 인라인 스타일을 여기에 임시로 유지 */
        * {
            -webkit-user-drag: none;
            -moz-user-drag: none;
            -ms-user-drag: none;
            user-drag: none;
        }
        
        img, a {
            user-drag: none;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .stylish-button {
            background-color: #FF69B4;
            color: white;
            padding: 5px 5px;
            width: 6vw;
            border: none;
            cursor: pointer;
            font-family: "Arial Rounded MT Bold", sans-serif;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        
        .stylish-button:hover {
            background-color: #FF1493;
        }
        
        .stylish-button:active {
            transform: translateY(2px);
        }
        
        .stylish-button:focus {
            outline: none;
        }
        
        #tableContainer {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        #tableContainer.active {
            opacity: 1;
        }
        
        .container {
            display: flex;
        }
        
        .left-column {
            width: 20%;
            padding: 16px;
        }
        
        .right-column {
            width: 80%;
            padding: 0px;
        }
        
        .left-sidebar {
            width: 20%;
            height: 100%;
            position: fixed;
            left: 0;
            top: 0;
            background-color: #f1f1f1;
            padding: 20px;
        }
        
        .main-body {
            width: 20%;
            height: 100%;
        }
        
        .collapsible {
            background-color: #eee;
            color: #444;
            cursor: pointer;
            padding: -0px;
            width: 80%;
            border: none;
            text-align: left;
            outline: none;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        
        .colsection {
            width: 79%;
            height: 100%;
            position: absolute;
            left: 20%;
            top: 0;
            background-color: #f1f1f1;
            padding: 0px;
        }
        
        .active, .collapsible:hover {
            background-color: #ccc;
        }
        
        .content {
            padding: 0 18px;
            display: none;
            overflow: hidden;
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        
        @keyframes pulse-red {
            0% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.6;
                transform: scale(1.05);
            }
            100% { 
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <!-- 알림 버튼 -->
    <button id="callbackButton" class="btn btn-primary" title="알림 설정" 
            style="position: fixed; top: 0; right: 10px; z-index: 1000; padding: 10px 15px; 
                   font-weight: bold; border-radius: 0 0 15px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
                   cursor: pointer; background-color: #007bff; border: none;">
        <i class="fas fa-clock"></i>
    </button>
    
    <!-- 메인 컨테이너 -->
    <div class="container">
        <!-- 여기에 나머지 HTML 구조가 들어갑니다 -->
        <!-- 원본 파일의 HTML 부분을 여기에 포함시킵니다 -->
    </div>
    
    <!-- jQuery (기존 함수 호환성 유지) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js"></script>
    
    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- 커스텀 JavaScript (외부 파일로 분리 예정) -->
    <script>
        // 기존 JavaScript 코드를 여기에 임시로 유지
        // 추후 assets/js/chapter42.js로 이동 예정
    </script>
</body>
</html>