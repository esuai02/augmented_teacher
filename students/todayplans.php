<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$studentid = required_param('id', PARAM_INT);
 
$cid=$_GET["cid"]; 
$nch=$_GET["nch"]; 
$pid=$_GET["pid"]; 

$wgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid'   AND ( type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");

if($cid==NULL)$cid=$chapterlog->cid;
if($nch==NULL)$nch=$chapterlog  ->nch;  
if($pid==NULL)$pid=$goal->id;          
// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", array($USER->id)); // Added 'fieldid' condition
$role = isset($userrole->role) ? $userrole->role : '';

// 학생 이름 가져오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
$firstname = isset($username->firstname) ? $username->firstname : '';
$lastname = isset($username->lastname) ? $username->lastname : '';
$studentname = htmlspecialchars($firstname, ENT_QUOTES) . ' ' . htmlspecialchars($lastname, ENT_QUOTES);

// 데이터베이스에서 분기 목표 가져오기
$termplan = $DB->get_record_sql("SELECT id, deadline, memo, dreamchallenge, dreamtext, dreamurl FROM mdl_abessi_progress WHERE id = ? ORDER BY id DESC LIMIT 1", array($pid));

$termplan2= $DB->get_record_sql("SELECT  id FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated'  ORDER BY id DESC LIMIT 1  ");
    

if ($termplan) {
    $dreamdday = round(($termplan->deadline - $timecreated) / 86400 + 1, 0);
    $EGinputtime = date("m/d", $termplan->deadline);
    $termMission = htmlspecialchars($termplan->memo, ENT_QUOTES);
} else {
    $dreamdday = 0;
    $EGinputtime = '';
    $termMission = '';
}

// 주간 목표 가져오기
$todayGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid=? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1", array($studentid));

$todayGoalText = isset($todayGoal->text) ? htmlspecialchars($todayGoal->text, ENT_QUOTES) : '';

$goaldisplay = $EGinputtime.'까지 계획이 <b>' . $termMission . '</b> 이어서 이번 주는 <b>' . $todayGoalText . '</b>(을)를 목표로 정진 중입니다.</span>';


// progressid 필드 제거됨 - userid로만 최근 레코드 조회
$planinfo = $DB->get_record_sql("SELECT * FROM {abessi_todayplans} WHERE userid=? ORDER BY id DESC LIMIT 1", array($studentid));

$plans = array();
$times = array();
$urls = array();

$allTimesEmpty = true;
$statuses = array(); // status 배열 초기화
for ($i = 1; $i <= 16; $i++) {
    $planField = 'plan' . $i;
    $timeField = 'due' . $i;
    $urlField = 'url' . $i;
    $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...

    $plans[] = isset($planinfo->$planField) ? $planinfo->$planField : '';

    // due 값은 분 단위로 저장됨
    $rawTime = isset($planinfo->$timeField) ? $planinfo->$timeField : '';
    if ($rawTime === '' || $rawTime === null) {
        $minuteValue = '';
    } else if (is_numeric($rawTime)) {
        // 분 값 그대로 사용
        $minuteValue = intval($rawTime);
    } else {
        // 과거 형식 HH:MM인 경우 분으로 변환 (하위 호환성)
        if (preg_match('/^(\d{2}):(\d{2})$/', $rawTime, $m)) {
            $minuteValue = intval($m[1]) * 60 + intval($m[2]);
        } else {
            $minuteValue = 30;
        }
    }

    if (!empty($minuteValue)) {
        $allTimesEmpty = false;
    }
    $times[] = $minuteValue; // 분 값
    $urls[] = isset($planinfo->$urlField) ? $planinfo->$urlField : '';
    $statuses[] = isset($planinfo->$statusField) ? $planinfo->$statusField : ''; // status 값 로드
}

if ($allTimesEmpty) {
    // 모두 비었으면 기본 30분으로 채움
    for ($i = 0; $i < 16; $i++) {
        $times[$i] = 30;
    }
}

// JavaScript에서 사용할 변수들을 스크립트로 출력
// 초기에 6개 행 표시 (사용자 요구사항)
$initialRows = 6;

$totalWeeks = 16; // 최대 16주차까지
$plans_json = json_encode($plans);
$times_json = json_encode($times);
$urls_json = json_encode($urls);
$statuses_json = json_encode($statuses); // status 배열 JSON 인코딩

// 마무리 시간 계산을 위한 시작 시간 (tbegin 또는 timecreated)
$tbegin = isset($planinfo->tbegin) ? intval($planinfo->tbegin) : (isset($planinfo->timecreated) ? intval($planinfo->timecreated) : time());
$tbegin_json = json_encode($tbegin);

// 오늘 요일의 계획된 공부시간 가져오기 (schedule 테이블)
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0); // 0=일요일, 1=월요일, ..., 6=토요일
$schedule = $DB->get_record_sql("SELECT * FROM {abessi_schedule} WHERE userid=? AND pinned=1 ORDER BY id DESC LIMIT 1", array($studentid));

$todayduration = 0; // 계획 시간 (시간 단위)
if ($schedule) {
    if($nday==1) $todayduration = $schedule->duration1;
    elseif($nday==2) $todayduration = $schedule->duration2;
    elseif($nday==3) $todayduration = $schedule->duration3;
    elseif($nday==4) $todayduration = $schedule->duration4;
    elseif($nday==5) $todayduration = $schedule->duration5;
    elseif($nday==6) $todayduration = $schedule->duration6;
    elseif($nday==0) $todayduration = $schedule->duration7;
}
$todayduration_json = json_encode($todayduration);
error_log("[todayplans.php:129] 오늘 요일: $nday, 계획 시간: $todayduration 시간");

$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id=?", array($cid));

$subjectname = $curri->name;
$cntstr = 'cnt' . $nch;

$chname = 'ch' . $nch;
$thischtitle = $curri->$chname;
$checklistid = $curri->$cntstr;

// 체크리스트의 인스턴스를 가져옵니다.
$chklist = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id=? ORDER BY id DESC LIMIT 1", array($checklistid));
$topics = $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist=? ORDER BY position ASC", array($chklist->instance));

$chapterlist = ''; // 챕터 리스트 초기화

$chapter_num = 1; // 챕터 번호 초기화

// 표시할 문구와 아이콘 매핑
$phrases = array(
    '개념도약' => '🟢',
    '유형정복' => '🟦',
    '단원 마무리' => '☑️',
    '대표유형' => '✳️',
    '심화수업' => '🏆',
);

foreach ($topics as $topic) {
    $chkitemid = $topic->id;
    $displaytext = $topic->displaytext;
    $linkurl = $topic->linkurl;
    $position = $topic->position;

    // 표시할 문구 포함 여부 확인
    $include_topic = false;
    $icon = '';
    foreach ($phrases as $phrase => $icon_symbol) {
        if (strpos($displaytext, $phrase) !== false) {
            $include_topic = true;
            $icon = $icon_symbol;
            break; // 첫 번째로 매칭되는 문구만 사용
        }
    }
    if (!$include_topic) {
        continue; // 포함되지 않으면 다음 항목으로
    }

    $url_components = parse_url($linkurl);
    $params = array();
    if (isset($url_components['query'])) {
        parse_str($url_components['query'], $params);
    }
    $cntid = isset($params['id']) ? $params['id'] : '';
    $quizid = isset($params['quizid']) ? $params['quizid'] : '';

    // $displaytext 수정 (필요한 경우)
    if (strpos($displaytext, '마무리') !== false) {
        $displaytext = '단원 마무리 T: ' . $thischtitle;
    }
    // Update $linkurl based on conditions
    if (strpos($displaytext, '도약') !== false) {
        $linkurl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?cmid=' . $cntid . '&page=1&studentid=' . $studentid . '&quizid=' . $cntid;
    } elseif (strpos($displaytext, '유형') !== false) {
        $linkurl = 'https://mathking.kr/moodle/mod/quiz/view.php?id=' . $cntid;
    } elseif (strpos($displaytext, '정복') !== false && $learningstyle !== '도제') {
        $linkurl = 'https://mathking.kr/moodle/mod/quiz/view.php?id=' . $cntid;
    } elseif (strpos($displaytext, '마무리') !== false && $learningstyle !== '도제') {
        $linkurl = 'https://mathking.kr/moodle/mod/quiz/view.php?id=' . $cntid;
    } elseif (strpos($displaytext, '심유') !== false && strpos($linkurl, 'checklist') !== false) {
        $linkurl = 'https://mathking.kr/moodle/mod/checklist/view.php?id=' . $cntid . '&studentid=' . $studentid;
    } elseif (strpos($displaytext, '화수업') !== false && strpos($linkurl, 'checklist') !== false) {
        $linkurl = 'https://mathking.kr/moodle/mod/checklist/view.php?id=' . $cntid . '&studentid=' . $studentid;
    }

    // 🔗 아이콘에 $linkurl 적용
    $copyButton = '<span class="copy-button" data-clipboard-text="' . htmlspecialchars($displaytext, ENT_QUOTES, 'UTF-8') . '">' . $icon . ' ' . $displaytext . '</span>';
    $insertButton = '<button class="insert-button" data-title="' . htmlspecialchars($displaytext, ENT_QUOTES, 'UTF-8') . '" data-linkurl="' . htmlspecialchars($linkurl, ENT_QUOTES, 'UTF-8') . '">➕</button>';
    $linkIcon = '<a href="' . $linkurl . '" target="_blank">🔗</a>';
    $chapterlist .= '<tr><td>' . $chapter_num . '</td><td>' . $insertButton . ' ' . $copyButton . ' ' . $linkIcon . '</td></tr>';
    $chapter_num++;
}

// 코스 이름을 가져옵니다.
$course = $DB->get_record('course', array('id' => $cid));
$subjectname = isset($course->fullname) ? $course->fullname : '학습목록';

// 진행 상황 바를 위한 메뉴 수 계산
$menu_count = $chapter_num - 1; // 생성된 메뉴 수
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>공부일기</title>
    <!-- Tailwind CSS 추가 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 기존 스타일 및 스크립트 -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <!-- 추가적인 스타일이나 스크립트가 필요하면 여기에 포함 -->
     
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <!-- 시간 입력 필드를 위한 추가적인 스크립트는 필요하지 않습니다 -->

    <!-- jQuery 및 Bootstrap JS 추가 -->
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <!-- jQuery UI 추가 -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- 시간 피커는 사용하지 않음 (분 선택 드롭다운으로 대체) -->

    <!-- 진행 상황 바 스타일 추가 -->
    <style>
        /* 진행 상황 바 스타일 */
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .progress-segment {
            flex-grow: 1;
            height: 4px;
            margin: 0 2px;
            background-color: #e5e7eb; /* Gray-200 */
            position: relative;
        }
        .progress-segment.active {
            background-color: #3b82f6; /* Blue-500 */
        }
    </style>

    <style>
        /* 추가적인 스타일을 여기에 정의 */
        html, body {
            height: 100%;
        }
        .container-fluid, .row {
            height: 100%;
        }
        .container {
            margin-top: 20px;
        }
        .left-column {
            padding: 15px;
            border-right: 1px solid #ddd;
            height: 100%;
            overflow-y: auto;
        }
        .right-column {
            padding: 15px;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
        }
        .table th, .table td {
            vertical-align: middle !important;
            text-align: center;
        }
        /* 목표 칼럼을 넓히고 데드라인 칼럼 폭을 줄임 */
        .goal-column {
            width: 50%;
        }
        .deadline-column {
            width: 20%;
        }
        .link-column {
            width: 15%;
        }
        .apply-column {
            width: 15%;
        }
        .apply-btn {
            width: 100%;
        }
        /* 플러스 버튼 스타일 */
        .add-more-btn {
            width: 100%;
            margin-top: 10px;
        }
        /* 추가된 스타일 */
        .chapter-table {
            width: 100%;
            border-collapse: collapse;
        }
        .chapter-table th, .chapter-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .chapter-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .chapter-table tr:hover {
            background-color: #f1f1f1;
        }
        .chapter-table th {
            padding-top: 12px;
            padding-bottom: 12px;
            background-color: #4CAF50;
            color: white;
            text-align: left;
        }
        /* 챕터 번호 열의 폭을 줄임 */
        .chapter-table th:nth-child(1),
        .chapter-table td:nth-child(1) {
            width: 10%;
        }
        /* 버튼 스타일 */
        .copy-button {
            background-color: white;
            border: none;
            color: black;
            padding: 5px 10px;
            text-align: center;
            font-size: 12px;
            cursor: pointer;
            border-radius: 3px;
            margin-left: 5px;
        }
        .copy-button:hover {
            background-color: grey;
        }
        /* 새로운 입력 버튼 스타일 */
        .insert-button {
            background-color: white;
            border: none;
            color: black;
            padding: 5px 10px;
            text-align: center;
            font-size: 12px;
            cursor: pointer;
            border-radius: 3px;
            margin-left: 5px;
        }
        .insert-button:hover {
            background-color: grey;
        }
        /* 시간 입력 화살표 버튼 스타일 */
        .time-input-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .time-btn {
            padding: 5px 10px;
            border: 1px solid #ccc;
            background: #f8f9fa;
            cursor: pointer;
            border-radius: 3px;
            transition: all 0.2s ease;
            font-size: 14px;
            user-select: none;
        }
        .time-btn:hover {
            background: #e9ecef;
            border-color: #999;
        }
        .time-btn:active {
            background: #dee2e6;
            transform: scale(0.95);
        }
        .time-input {
            width: 70px !important;
            text-align: center;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            font-weight: 500;
        }
        .time-unit {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        /* 마우스 오버 시 표시되는 컨텐츠 스타일 */
        #hoverContainer {
            position: relative;
            margin-top: 20px;
        }
        #hoverContent {
            display: none;
            position: absolute;
            top: 20px;
            left: 0;
            background-color: #F0F1F4;
            padding: 10px;
            border: 1px solid #ccc;
            z-index: 100;
            width: 100%;
        }
        #hoverContainer:hover #hoverContent {
            display: block;
        }
    </style>
    <script>
        var studentid = <?php echo $studentid; ?>;
        var pid = <?php echo $pid; ?>;
        var plans = <?php echo $plans_json; ?>;
        var times = <?php echo $times_json; ?>; // 분 값 배열
        var urls = <?php echo $urls_json; ?>;
        var statuses = <?php echo $statuses_json; ?>; // status 배열 (만족/매우만족/불만족)
        var totalWeeks = 16;
        var currentRows = <?php echo $initialRows; ?>;
        var defaultDuration = 30;

        var unsavedChanges = false; // 변경 사항 추적 변수
        var recordCreatedTime = <?php echo $tbegin_json; ?>; // 학습 시작 시간 (tbegin)
        var scheduledDuration = <?php echo $todayduration_json; ?>; // 계획 시간 (시간 단위)

    </script>
</head>

<body>
    <!-- 진행 상황 바 추가 -->
    <div class="progress-bar">
        <?php for ($i = 1; $i <= $menu_count; $i++): ?>
            <div class="progress-segment <?php echo ($i <= $nch) ? 'active' : ''; ?>"></div>
        <?php endfor; ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6 left-column">
             <form id="todayGoalsForm">
                    <table class="table table-bordered" id="goalsTable">
                        <thead>
                            <tr align="left">
                                <th style="background-color: #4CAF50;color:white;" class="deadline-column" align="left" height="45"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=<?php echo $studentid; ?>">🧑🏻</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800"><?php echo $studentname; ?></a> 수학일기</th>
                                <th style="background-color: #4CAF50;color:white;" class="goal-column" height="45"><?php echo $goaldisplay; ?></th>
                                <th  style="background-color: #4CAF50;color:white;width: 3%;" class="link-column" height="45">링크</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for ($week = 1; $week <= $initialRows; $week++) {
                                $planValue = isset($plans[$week - 1]) ? $plans[$week - 1] : '';
                                $timeValue = isset($times[$week - 1]) ? $times[$week - 1] : '';
                                $urlValue = isset($urls[$week - 1]) ? $urls[$week - 1] : '';
                                $statusValue = isset($statuses[$week - 1]) ? $statuses[$week - 1] : ''; // status 값
                                // 기본값 30분 설정
                                $selectedMinutes = (!empty($timeValue) && is_numeric($timeValue)) ? intval($timeValue) : 30;

                                echo '<tr>
                                    <td>
                                        <div class="time-input-wrapper" style="display: flex; align-items: center; gap: 5px;">
                                            <div style="display: flex; gap: 2px;">
                                                <button type="button" class="time-btn time-decrease" onclick="adjustTime(this, -5)" style="padding: 5px 8px; border: 1px solid #ccc; background: #f8f9fa; cursor: pointer; border-radius: 3px 0 0 3px;">◀</button>
                                                <button type="button" class="time-btn time-increase" onclick="adjustTime(this, 5)" style="padding: 5px 8px; border: 1px solid #ccc; background: #f8f9fa; cursor: pointer; border-radius: 0 3px 3px 0;">▶</button>
                                            </div>
                                            <input type="number" class="form-control time-input" name="time' . $week . '" value="' . $selectedMinutes . '" min="5" max="240" step="5" style="width: 70px; text-align: center; padding: 5px;" readonly>
                                            <span class="time-unit" style="font-size: 14px;">분</span>
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control" name="week' . $week . '" value="' . htmlspecialchars($planValue, ENT_QUOTES) . '">
                                    <input type="hidden" name="url' . $week . '" value="' . htmlspecialchars($urlValue, ENT_QUOTES) . '"></td>';
                                // 컨텐츠 링크를 새로운 <td></td>로 이동
                                echo '<td style="width: 3%;">';
                                if (!empty($urlValue)) {
                                    $urlsArray = explode(',', $urlValue);
                                    foreach ($urlsArray as $url) {
                                        $url = trim($url);
                                        if (!empty($url)) {
                                            echo '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank">🔗</a> ';
                                        }
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 15px; background-color: #f0f8f0; font-weight: bold; border-top: 2px solid #4CAF50; position: relative;">
                                    <button type="button" class="btn btn-sm btn-default" onclick="addMoreRows()" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); padding: 5px 15px;">
                                        <i class="fa fa-plus"></i> 추가
                                    </button>
                                    <span id="finishTimeDisplay" style="color: #4CAF50; font-size: 16px;">
                                        마무리 예상: --:--
                                    </span>
                                    <button type="button" id="addRestBtn" class="btn btn-sm btn-info" style="position: absolute; right: 100px; top: 50%; transform: translateY(-50%); padding: 5px 15px;">
                                        <i class="fa fa-coffee"></i> 휴식
                                    </button>
                                    <button type="button" onclick="resetDiaryForm()" class="btn btn-sm btn-warning" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); padding: 5px 15px;">
                                        <i class="fa fa-refresh"></i> 초기화
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            </div>
            <!-- Right Column -->
            <div class="col-md-6 right-column">
                <!-- 챕터 목록 시작 -->
                <?php if (!empty($chapterlist)): ?>
                    <table class="chapter-table">
                        <thead>
                            <tr>
                                <th>챕터</th>
                                <th><?php echo $thischtitle; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $chapterlist; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>챕터 정보가 없습니다.</p>
                <?php endif; ?>
                <!-- 챕터 목록 끝 -->
                <!-- 새로운 내용 시작 -->
                <div id="hoverContainer">
                    <span id="hoverTrigger" style="cursor: pointer; color: grey; ">과목변경</span>
                    <div id="hoverContent">
                        <table width="100%">
                            <tr>
                                <td>
                                    <img style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/createtimefolding.png" width="40">&nbsp;&nbsp;
                                </td>
                                <td style="color:black">
                                <?php
                                // 해당 과목의 다른 단원(챕터)으로 이동하는 링크를 생성합니다.

                                // 커리큘럼 정보를 가져옵니다.
                                $curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id=?", array($cid));

                                // 챕터 수를 가져옵니다.
                                $chapnum = isset($curri->nch) ? $curri->nch : 0;

                                $linkStrings = [];
                                for ($nch_loop = 1; $nch_loop <= $chapnum; $nch_loop++) {
                                    $chname = 'ch' . $nch_loop;
                                    $thischtitle = isset($curri->$chname) ? $curri->$chname : '챕터 ' . $nch_loop;
                                    $title = isset($thischtitle) ? $thischtitle : '챕터 ' . $nch_loop;
                                    $href = 'https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id=' . $studentid . '&pid=' . $pid . '&cid=' . $cid . '&nch=' . $nch_loop;
                                    $linkStrings[] = '<a href="' . $href . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a>';
                                }
                                echo implode(' | ', $linkStrings);
                                ?>
                            </td>

                            </tr>
                        </table>
                    </div>
                </div>
                <!-- 새로운 내용 끝 -->
            </div>
        </div>
    </div>

    <!-- 스크립트 부분 -->
    <script>
        var lastFocusedInput = null;
        $(document).on('focus', 'input[name^="week"]', function() {
            lastFocusedInput = this;
        });

        // 시간 증감 함수
        function adjustTime(button, change) {
            var input = $(button).closest('.time-input-wrapper').find('.time-input')[0];
            var currentValue = parseInt(input.value) || 30;
            var newValue = currentValue + change;

            // 5분 ~ 240분 범위 제한
            if (newValue >= 5 && newValue <= 240) {
                input.value = newValue;
                // 마무리 시간 업데이트
                updateFinishTime();
                // 변경 사항 즉시 저장
                saveTodayPlans(studentid, pid);
                unsavedChanges = false;
            }
        }

        // 마무리 시간 계산 및 표시
        function updateFinishTime() {
            // 시작 시간 (tbegin 그대로 사용)
            var startDate = new Date(recordCreatedTime * 1000);

            // 내용이 입력된 항목만 소요시간 합산
            var totalMinutes = 0;
            $('#goalsTable tbody tr').each(function() {
                var planInput = $(this).find('input[name^="week"]');
                var timeInput = $(this).find('input.time-input');

                // plan에 내용이 있는 경우만 시간 합산
                if (planInput.length > 0 && planInput.val().trim() !== '') {
                    var minutes = parseInt(timeInput.val()) || 0;
                    if (minutes > 0) {
                        totalMinutes += minutes;
                    }
                }
            });

            // 마무리 시간 계산
            var finishDate = new Date(startDate.getTime() + (totalMinutes * 60 * 1000));

            // 시간 포맷팅 (HH:MM)
            var hours = String(finishDate.getHours()).padStart(2, '0');
            var minutes = String(finishDate.getMinutes()).padStart(2, '0');
            var finishTimeStr = hours + ':' + minutes;

            // 계획 시간과 실제 시간 비교
            var scheduledMinutes = scheduledDuration * 60; // 시간을 분으로 변환
            var actualHours = (totalMinutes / 60).toFixed(1); // 실제 시간 (소수점 1자리)
            var timeDifference = scheduledMinutes - totalMinutes; // 계획 - 실제 (분)

            var comparison = totalMinutes >= scheduledMinutes ? '✅' : '⚠️';
            var textColor = '#4CAF50'; // 기본 초록색

            // 계획보다 실제가 3분 이상 적으면 파란색
            if (timeDifference >= 3) {
                textColor = '#2196F3'; // 파란색
            }

            // 화면에 표시 (마무리 시간 + 계획/실제 비교)
            $('#finishTimeDisplay').html(
                '<span style="color: ' + textColor + ';">마무리 예상: <strong>' + finishTimeStr + '</strong> ' +
                comparison + ' (계획: ' + scheduledDuration + '시간, 실제: ' + actualHours + '시간)</span>'
            );
        }

        $(document).ready(function() {
            // 드롭다운 재생성 제거 - 이미 PHP에서 화살표 버튼 형태로 생성됨
            // 페이지 로드 시 초기 마무리 시간 계산
            updateFinishTime();

            // 휴식 버튼 클릭 이벤트 핸들러
            $('#addRestBtn').on('click', function() {
                // 미입력된 첫 번째 행 찾기
                var emptyRow = null;
                $('#goalsTable tbody tr').each(function() {
                    var planInput = $(this).find('input[name^="week"]');
                    if (planInput.length > 0 && planInput.val().trim() === '') {
                        emptyRow = $(this);
                        return false; // break
                    }
                });

                if (emptyRow) {
                    // '휴식시간' 텍스트 입력
                    emptyRow.find('input[name^="week"]').val('휴식시간');
                    // 시간 10분으로 설정
                    emptyRow.find('input.time-input').val(10);
                    // 마무리 시간 업데이트
                    updateFinishTime();
                    // 저장
                    saveTodayPlans(studentid, pid);

                    swal({
                        title: "휴식 추가",
                        text: "휴식시간(10분)이 추가되었습니다.",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    swal({
                        title: "알림",
                        text: "미입력된 행이 없습니다. '+ 추가' 버튼을 눌러 행을 추가하세요.",
                        type: "info",
                        confirmButtonText: "확인"
                    });
                }
            });

            // 상태 체크박스 클릭 이벤트 (동적 행에도 적용)
            $(document).on('click', '.status-checkbox', function() {
                var $checkbox = $(this);
                var week = $checkbox.data('week');
                var statusField = 'status' + String(week).padStart(2, '0'); // status01, status02, ...

                // SweetAlert로 만족도 선택
                swal({
                    title: "만족도를 선택하세요",
                    text: "학습 결과에 대한 만족도를 선택해 주세요.",
                    buttons: {
                        satisfied: {
                            text: "만족",
                            value: "만족",
                            className: "btn-success"
                        },
                        verySatisfied: {
                            text: "매우만족",
                            value: "매우만족",
                            className: "btn-primary"
                        },
                        dissatisfied: {
                            text: "불만족",
                            value: "불만족",
                            className: "btn-danger"
                        }
                    }
                }).then(function(value) {
                    if (value) {
                        // 선택한 값을 status 배열에 저장
                        statuses[week - 1] = value;

                        // AJAX로 status 저장
                        var formData = new FormData();
                        formData.append('studentid', studentid);
                        formData.append(statusField, value);

                        // 다른 필드들도 함께 전송 (전체 저장)
                        for (var i = 1; i <= 16; i++) {
                            var planValue = $('input[name="week' + i + '"]').val() || '';
                            var timeValue = $('input[name="time' + i + '"]').val() || '';
                            var urlValue = $('input[name="url' + i + '"]').val() || '';
                            var statusIdx = i - 1;
                            var statusVal = statuses[statusIdx] || '';

                            formData.append('week' + i, planValue);
                            formData.append('time' + i, timeValue);
                            formData.append('url' + i, urlValue);
                            formData.append('status' + String(i).padStart(2, '0'), statusVal);
                        }

                        $.ajax({
                            url: 'save_todayplan.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log('[todayplans.php] Status 저장 성공:', response);

                                // 체크박스를 텍스트로 변경
                                var $statusCell = $checkbox.closest('.status-cell');
                                $statusCell.html('<span class="status-text">' + value + '</span>');

                                swal({
                                    title: "저장 완료",
                                    text: "만족도가 저장되었습니다: " + value,
                                    type: "success",
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            },
                            error: function(xhr, status, error) {
                                console.error('[todayplans.php] Status 저장 실패:', error);
                                swal({
                                    title: "오류",
                                    text: "만족도 저장 중 오류가 발생했습니다.",
                                    type: "error"
                                });
                            }
                        });
                    }
                });
            });
        });

        // 디바운싱용 타이머 변수
        var saveTimeout;
        var SAVE_DELAY = 500; // 500ms 디바운스

        // 디바운싱 적용 저장 함수
        function debouncedSave(studentid, pid) {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                saveTodayPlans(studentid, pid);
                unsavedChanges = false;
            }, SAVE_DELAY);
        }

        // 텍스트 입력 필드(week1-16)에서 blur 시 디바운싱 자동 저장
        $(document).on('blur', 'input[name^="week"]', function() {
            updateFinishTime(); // 마무리 시간 업데이트
            debouncedSave(studentid, pid);
        });

        // 변경 사항 추적을 위한 이벤트 핸들러 추가
        $(document).on('change', 'input, select', function() {
            unsavedChanges = true;
        });

        // plan 입력 필드 변경 시 마무리 시간 업데이트
        $(document).on('input', 'input[name^="week"]', function() {
            updateFinishTime();
        });

        // 시간 입력 필드 변경 시 마무리 시간 업데이트
        $(document).on('change', 'input.time-input', function() {
            updateFinishTime();
        });

        // 빈 공간 클릭 시 저장 (백업용)
        $(document).on('click', function(event) {
            if (unsavedChanges && !$(event.target).is('input, textarea, .btn, .modal, .modal *')) {
                saveTodayPlans(studentid, pid);
                unsavedChanges = false;
            }
        });

        // 'insert-button' 클릭 이벤트 처리기 수정
        $(document).on('click', '.insert-button', function() {
            var chapterTitle = $(this).data('title');
            var linkurl = $(this).data('linkurl');
            if (lastFocusedInput && $(lastFocusedInput).is('input[name^="week"]')) {
                var $input = $(lastFocusedInput);
                var currentValue = $input.val();
                if (currentValue) {
                    $input.val(currentValue + ' + ' + chapterTitle);
                } else {
                    $input.val(chapterTitle);
                }
                // url 값도 추가
                var inputName = $input.attr('name');
                var weekNumber = inputName.match(/\d+/)[0];
                var $urlInput = $('input[name="url' + weekNumber + '"]');
                var currentUrlValue = $urlInput.val();
                if (currentUrlValue) {
                    $urlInput.val(currentUrlValue + ',' + linkurl);
                } else {
                    $urlInput.val(linkurl);
                }
                $input.blur();
                lastFocusedInput = null;
            } else {
                var found = false;
                $('input[name^="week"]').each(function() {
                    if (!found && $(this).val() == '') {
                        $(this).val(chapterTitle);
                        var inputName = $(this).attr('name');
                        var weekNumber = inputName.match(/\d+/)[0];
                        var $urlInput = $('input[name="url' + weekNumber + '"]');
                        $urlInput.val(linkurl);
                        found = true;
                    }
                });
                if (!found) {
                    addMoreRows();
                    $('input[name^="week"]').each(function() {
                        if (!found && $(this).val() == '') {
                            $(this).val(chapterTitle);
                            var inputName = $(this).attr('name');
                            var weekNumber = inputName.match(/\d+/)[0];
                            var $urlInput = $('input[name="url' + weekNumber + '"]');
                            $urlInput.val(linkurl);
                            found = true;
                        }
                    });
                }
            }
            // 플러스 버튼 클릭 시 전체 저장
            saveTodayPlans(studentid, pid);
            unsavedChanges = false; 
        });

        function saveTodayPlans(studentid, pid) {
            var formData = $("#todayGoalsForm").serializeArray();

            // 전송 데이터 로깅
            console.log('=== Saving Today Plans ===');
            console.log('studentid:', studentid);
            console.log('pid:', pid);
            console.log('formData:', formData);

            $.ajax({
                url: "save_todayplan.php",
                type: "POST",
                data: $.param(formData) + "&studentid=" + encodeURIComponent(studentid) + "&pid=" + encodeURIComponent(pid),
                dataType: "json",
                success: function(response) {
                    // 응답 데이터 로깅
                    console.log('=== AJAX Success ===');
                    console.log('Response:', response);
                    console.log('Status:', response.status);

                    if (response.status === 'success') {
                        swal("", response.message, {buttons: false, timer: 2000});
                        // 자동저장이므로 부모 창 새로고침 제거 (입력 모드 유지)
                        // 사용자가 직접 보기 모드로 전환할 때만 업데이트된 데이터 표시
                    } else {
                        console.error('Save failed - Response:', response);
                        swal("", "저장 실패: " + (response.message || '알 수 없는 오류'), {buttons: false, timer: 3000});
                    }
                },
                error: function(xhr, status, error) {
                    // 상세 에러 정보 로깅
                    console.error('=== AJAX Error ===');
                    console.error('HTTP Status:', xhr.status);
                    console.error('Status Text:', xhr.statusText);
                    console.error('Response Text:', xhr.responseText);
                    console.error('Error:', error);
                    console.error('AJAX Status:', status);

                    // 사용자 친화적 에러 메시지
                    var msg = "저장 실패";
                    if (xhr.status === 0) {
                        msg = "서버 연결 실패 - 네트워크를 확인하세요";
                    } else if (xhr.status === 500) {
                        msg = "서버 오류 (HTTP 500) - 관리자에게 문의하세요";
                    } else if (xhr.status === 404) {
                        msg = "저장 파일을 찾을 수 없습니다 (HTTP 404)";
                    } else {
                        msg = "저장 실패 (HTTP " + xhr.status + "): " + error;
                    }

                    swal("", msg, {buttons: false, timer: 3000});
                }
            });
        }

        function resetDiaryForm() {
            // 확인 메시지 표시
            swal({
                title: "초기화 확인",
                text: "모든 입력 내용이 삭제됩니다. 계속하시겠습니까?",
                icon: "warning",
                buttons: {
                    cancel: "취소",
                    confirm: "초기화"
                },
                dangerMode: true,
            }).then((willReset) => {
                if (willReset) {
                    // 모든 입력 필드 초기화
                    $("#todayGoalsForm").find("input[type='text']").val('');
                    $("#todayGoalsForm").find("input[type='hidden']").val('');

                    // 시간 입력 필드를 기본값(30분)으로 초기화
                    $("#todayGoalsForm").find("input[type='number'].time-input").val(30);

                    // 마무리 시간 업데이트
                    updateFinishTime();

                    // 변경 사항 플래그 초기화
                    unsavedChanges = false;

                    // DB에 초기화된 상태 저장
                    saveTodayPlans(studentid, pid);

                    swal("초기화 완료", "모든 입력 내용이 초기화되었습니다.", "success");
                }
            });
        }

        function addMoreRows() {
            var tbody = $("#goalsTable tbody");
            for (var i = 0; i < 3; i++) {
                currentRows++;
                if (currentRows > totalWeeks) {
                    swal("", "더 이상 추가할 행이 없습니다.", {buttons: false, timer: 2000});
                    break;
                }
                var planValue = plans[currentRows - 1] || '';
                var urlValue = urls[currentRows - 1] || '';
                var statusValue = statuses[currentRows - 1] || ''; // status 값

                var newRow = '<tr>' +
                    '<td>' +
                    '<div class="time-input-wrapper" style="display: flex; align-items: center; gap: 5px;">' +
                    '<div style="display: flex; gap: 2px;">' +
                    '<button type="button" class="time-btn time-decrease" onclick="adjustTime(this, -5)" style="padding: 5px 8px; border: 1px solid #ccc; background: #f8f9fa; cursor: pointer; border-radius: 3px 0 0 3px;">◀</button>' +
                    '<button type="button" class="time-btn time-increase" onclick="adjustTime(this, 5)" style="padding: 5px 8px; border: 1px solid #ccc; background: #f8f9fa; cursor: pointer; border-radius: 0 3px 3px 0;">▶</button>' +
                    '</div>' +
                    '<input type="number" class="form-control time-input" name="time' + currentRows + '" value="30" min="5" max="240" step="5" style="width: 70px; text-align: center; padding: 5px;" readonly>' +
                    '<span class="time-unit" style="font-size: 14px;">분</span>' +
                    '</div>' +
                    '</td>' +
                    '<td><input type="text" class="form-control" name="week' + currentRows + '" value="' + planValue + '">' +
                    '<input type="hidden" name="url' + currentRows + '" value="' + urlValue + '"></td>';
                // 컨텐츠 링크 추가
                newRow += '<td>';
                if (urlValue) {
                    var urlsArray = urlValue.split(',');
                    for (var j = 0; j < urlsArray.length; j++) {
                        var url = urlsArray[j].trim();
                        if (url) {
                            newRow += '<a href="' + url + '" target="_blank">🔗</a> ';
                        }
                    }
                }
                newRow += '</td>';
                newRow += '</tr>';
                tbody.append(newRow);
            }
            // 마무리 시간 업데이트
            updateFinishTime();
            // 플러스 버튼 클릭 시 전체 저장
            saveTodayPlans(studentid, pid);
            unsavedChanges = false;
        }

        // 클립보드 복사 기능 추가
        $(function() {
            // 클립보드 복사 스크립트
            $(document).on('click', '.copy-button', function() {
                const textToCopy = $(this).attr("data-clipboard-text");
                navigator.clipboard.writeText(textToCopy).then(function() {
                    swal("", "텍스트가 복사되었습니다", {buttons: false, timer: 300});
                 }, function(err) {
                    console.error("텍스트 복사 실패", err);
                });
            });
        });
    </script>
</body>
</html>
