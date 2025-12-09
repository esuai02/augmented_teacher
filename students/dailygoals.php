<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$studentid = required_param('id', PARAM_INT);

$cid=$_GET["cid"]; 
$pid=$_GET["pid"]; 
$timecreated = time();

// CHANGED: mdl_abessi_today → mdl_abessi_weeklyplans
// Get latest progress ID first
$latestProgress = $DB->get_record_sql("SELECT id FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER BY id DESC LIMIT 1");
$progressid_temp = $latestProgress ? $latestProgress->id : 0;

// Get goals from weeklyplans
$wgoal = null;
$goal = null;

if($progressid_temp > 0) {
    $weeklyPlansData = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid='$studentid' AND progressid='$progressid_temp' ORDER BY id DESC LIMIT 1");

    if($weeklyPlansData) {
        // 주간목표: plan8-16 중 첫 번째 비어있지 않은 값
        $weeklyGoalText = '';
        for($i = 8; $i <= 16; $i++) {
            $field = 'plan' . $i;
            if(!empty($weeklyPlansData->$field)) {
                $weeklyGoalText = $weeklyPlansData->$field;
                break;
            }
        }
        $wgoal = new stdClass();
        $wgoal->id = $weeklyPlansData->id;
        $wgoal->text = $weeklyGoalText;

        // 오늘목표: plan1-7 중 마지막 비어있지 않은 값 (가장 최근)
        $todayGoalText = '';
        for($i = 7; $i >= 1; $i--) {
            $field = 'plan' . $i;
            if(!empty($weeklyPlansData->$field)) {
                $todayGoalText = $weeklyPlansData->$field;
                break;
            }
        }
        $goal = new stdClass();
        $goal->text = $todayGoalText;
    }
}

$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
if($cid==NULL)$cid=$chapterlog->cid;
if($nch==NULL)$nch=$chapterlog->nch;
if($pid==NULL)$pid=$wgoal ? $wgoal->id : $progressid_temp;     

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ?", array($USER->id));
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

// 주간 목표 가져오기 (weeklyplans에서 이미 위에서 가져왔음)
// $wgoal 변수에 이미 저장되어 있음
$weeklyGoalText = isset($wgoal->text) ? htmlspecialchars($wgoal->text, ENT_QUOTES) : '';

$goaldisplay = $EGinputtime.'까지 계획이 <b>' . $termMission . '</b> 이어서 이번 주는 <b>' . $weeklyGoalText . '</b>(을)를 목표로 정진 중입니다.</span>';

// 데이터베이스에서 일별 목표를 가져옵니다.
$planinfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid=? AND progressid=? ORDER BY id DESC LIMIT 1", array($studentid, $pid));

$plans = array();
$dates = array();

$allDatesEmpty = true;
for ($i = 1; $i <= 7; $i++) {
    $planField = 'plan' . $i;
    $dateField = 'date' . $i;

    $plans[] = isset($planinfo->$planField) ? $planinfo->$planField : '';
    $dateValue = isset($planinfo->$dateField) ? $planinfo->$dateField : '';

    if (!empty($dateValue)) {
        $allDatesEmpty = false;
    }
    $dates[] = $dateValue;
}

if ($allDatesEmpty) {
    // 모든 날짜 값이 비어있는 경우, 오늘이 포함된 주의 월요일부터 일요일까지 생성
    $dayOfWeek = date('N', $timecreated); // 1 (월요일) ~ 7 (일요일)
    $daysToSubtract = $dayOfWeek - 1; // 월요일까지 빼야 할 일수
    $mondayTimestamp = $timecreated - ($daysToSubtract * 86400); // 이번 주 월요일

    for ($i = 0; $i < 7; $i++) {
        $dates[$i] = date('Y-m-d', $mondayTimestamp + ($i * 86400));
    }
} else {
    // 저장된 날짜 값이 일부라도 있는 경우, 비어있는 날짜는 다음 날짜로 설정
    for ($i = 0; $i < 7; $i++) {
        if (empty($dates[$i])) {
            $lastDate = !empty($dates[$i - 1]) ? $dates[$i - 1] : date('Y-m-d');
            $lastTimestamp = strtotime($lastDate);
            $dates[$i] = date('Y-m-d', $lastTimestamp + 86400); // 1일 후
        }
    }
}

// JavaScript에서 사용할 변수들을 스크립트로 출력
$initialRows = 7; // 처음부터 7개의 행을 표시

$totalDays = 7; // 7일간의 일별 목표
$plans_json = json_encode($plans);
$dates_json = json_encode($dates);

// 추가된 코드 시작 (챕터 목록 생성)
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id=?", array($cid));

if ($curri) {
    if ($curri->id >= 80 && $curri->id <= 94) {
        $dmn = 'science';
    } else {
        $dmn = 'math';
    }
    $ankisbjt = $curri->sbjt;
    $domainname = $curri->subject;
    $subjectname = $curri->name;
    $chapnum = $curri->nch;

    $chaptertitle = '<a style="font-size:20px;text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id=' . $studentid . '">' . $studentname . '</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id=' . $USER->id . '&userid=' . $studentid . '"><img style="margin-bottom:10px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width="40"></a>';

    $chapterlist = ''; // 챕터 리스트 초기화

    for ($nch = 1; $nch <= $chapnum; $nch++) {
        $chname = 'ch' . $nch;
        $title = $curri->$chname;
        $qid = 'qid' . $nch;
        $qidValue = $curri->$qid;
        if ($title == NULL) continue;
        $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id=?", array($qidValue));
        $attemptlog = $DB->get_record_sql("SELECT id, quiz, sumgrades, attempt, timefinish FROM mdl_quiz_attempts WHERE quiz=? AND userid=? ORDER BY id DESC LIMIT 1", array($moduleid->instance, $studentid));
        $timefinish = isset($attemptlog->timefinish) ? date("m/d | H:i", $attemptlog->timefinish) : '';
        $quiz = $DB->get_record_sql("SELECT id, sumgrades FROM mdl_quiz WHERE id=?", array($moduleid->instance));
        $quizgrade = ($attemptlog && $quiz && $quiz->sumgrades > 0) ? round($attemptlog->sumgrades / $quiz->sumgrades * 100, 0) : NULL;
        $quizresult = '';
        if ($quizgrade !== NULL) $quizresult = '<span style="color:lightgrey;">' . $quizgrade . '점 (' . $attemptlog->attempt . '회)</span>';

        // 텍스트 복사 버튼 추가
        $copyButton = '<span class="copy-button" data-clipboard-text="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">📋' . $title . '</span>';

        // 새로운 입력 버튼 추가
        $insertButton = '<button class="insert-button" data-title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">➕</button>';

        $chapterlist .= '<tr><td>' . $nch . '</td><td>' . $insertButton . ' ' . $copyButton . ' <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=' . $cid . '&nch=' . $nch . '&studentid=' . $studentid . '" target="_blank">🔗</a></td></tr>';
    }
    // 추가된 코드 끝
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>일별 목표 관리</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <!-- 추가적인 스타일이나 스크립트가 필요하면 여기에 포함 -->
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <!-- jQuery UI CSS 및 JS 추가 -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- jQuery UI 및 한국어 번역 -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- 한국어 번역 스크립트 -->
    <script src="https://code.jquery.com/ui/1.12.1/i18n/datepicker-ko.js"></script>
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
            width: 10%;
        }
        .day-column {
            width: 30px; /* 폭을 좁게 설정 */
            max-width: 30px;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
        }
       /* 날짜 입력 부분의 폭을 날짜 길이에 맞게 조절 */

       .date-column .form-control {
            width: 100%;
           
            padding-left: 5px;
            padding-right: 5px;
        }
        .apply-column {
            width: 10%;
        }
        .apply-btn {
            width: 100%;
            background-color: #b76dbf;
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
        var dates = <?php echo $dates_json; ?>;
        var totalDays = 7; // 7일간의 일별 목표
        var currentRows = <?php echo $initialRows; ?>;
    </script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6 left-column">
                <form id="dailyGoalsForm">
                 <table class="table table-bordered" id="goalsTable">
                        <thead>
                            <tr align="left">
                                <th style="width: 3%; background-color: #b76dbf; color:white;" class="day-column" align="left" height="45"></th>
                                <th style="width: 15%; background-color: #b76dbf; color:white;" class="date-column" height="45" align="left"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800"><?php echo $studentname; ?></a> </th>
                                <th style="width: 82%; background-color: #b76dbf;color:white;" class="goal-column" height="45"><?php echo $goaldisplay; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for ($day = 1; $day <= $initialRows; $day++) {
                                $planValue = isset($plans[$day - 1]) ? $plans[$day - 1] : '';
                                $dateValue = isset($dates[$day - 1]) ? $dates[$day - 1] : date('Y-m-d');
                                $dayOfWeek = date('w', strtotime($dateValue)); // 요일 구하기 (0: 일요일 ~ 6: 토요일)
                                $dayOfWeekKorean = ['일', '월', '화', '수', '목', '금', '토'];
                                $dayOfWeekDisplay = $dayOfWeekKorean[$dayOfWeek];
                                echo '<tr>
                                    <td class="day-column" style="width: 3%;">' . $dayOfWeekDisplay . '</td>
                                    <td class="date-column" style="width: 15%;"><input type="text" class="form-control datepicker" name="date' . $day . '" value="' . htmlspecialchars($dateValue, ENT_QUOTES) . '"></td>
                                    <td class="goal-column" style="width: 82%;"><input type="text" class="form-control" name="day' . $day . '" value="' . htmlspecialchars($planValue, ENT_QUOTES) . '"></td>
                                </tr>';
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- 초기화 버튼 -->
                    <div align="center" style="margin-top: 15px;">
                        <button type="button" onclick="resetDailyForm()" class="btn btn-warning">
                            <i class="fa fa-refresh"></i> 초기화
                        </button>
                    </div>
                </form>
            </div>
            <!-- Right Column -->
            <div class="col-md-6 right-column">
                <!-- 챕터 목록 시작 -->
                <?php if (!empty($chapterlist)): ?>

                    <table class="chapter-table">
                        <thead>
                            <tr>
                                <th style="background-color: #b76dbf; color:white;">챕터</th>
                                <th style="background-color: #b76dbf; color:white;"> <?php echo $subjectname; ?></th>
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
                                    $links = [
                                        ['cid' => 95, 'name' => '초등 3-1'],
                                        ['cid' => 96, 'name' => '초등 3-2'],
                                        ['cid' => 73, 'name' => '초등 4-1'],
                                        ['cid' => 74, 'name' => '초등 4-2'],
                                        ['cid' => 75, 'name' => '초등 5-1'],
                                        ['cid' => 76, 'name' => '초등 5-2'],
                                        ['cid' => 78, 'name' => '초등 6-1'],
                                        ['cid' => 79, 'name' => '초등 6-2'],
                                        ['cid' => 66, 'name' => '중 1-1'],
                                        ['cid' => 67, 'name' => '중 1-2'],
                                        ['cid' => 68, 'name' => '중 2-1'],
                                        ['cid' => 69, 'name' => '중 2-2'],
                                        ['cid' => 71, 'name' => '중 3-1'],
                                        ['cid' => 72, 'name' => '중 3-2'],
                                        ['cid' => 59, 'name' => '수 상'],
                                        ['cid' => 60, 'name' => '수 하'],
                                        ['cid' => 61, 'name' => '수 1'],
                                        ['cid' => 62, 'name' => '수 2'],
                                        ['cid' => 64, 'name' => '확통'],
                                        ['cid' => 63, 'name' => '미적'],
                                        ['cid' => 65, 'name' => '기하'],
                                    ];
                                    $linkStrings = [];
                                    foreach ($links as $link) {
                                        $href = 'https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id=' . $studentid . '&pid=' . $pid . '&cid=' . $link['cid'];
                                        $linkStrings[] = '<a href="' . $href . '">' . $link['name'] . '</a>';
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
        $(document).on('focus', 'input[name^="day"]', function () {
            lastFocusedInput = this;
        });

        // 'insert-button' 클릭 이벤트 처리기 수정
        $('.insert-button').on('click', function () {
            var chapterTitle = $(this).data('title');
            if (lastFocusedInput && $(lastFocusedInput).is('input[name^="day"]')) {
                // 마지막으로 포커스된 입력 필드에 내용 추가
                var $input = $(lastFocusedInput);
                var currentValue = $input.val();
                if (currentValue) {
                    $input.val(currentValue + ' + ' + chapterTitle);
                } else {
                    $input.val(chapterTitle);
                }
                // 입력 필드의 포커스 해제 및 lastFocusedInput 초기화
                $input.blur();
                lastFocusedInput = null;
            } else {
                var found = false;
                // 첫 번째 빈 plan 입력 필드를 찾습니다.
                $('input[name^="day"]').each(function () {
                    if (!found && $(this).val() == '') {
                        $(this).val(chapterTitle);
                        found = true;
                    }
                });
                if (!found) {
                    swal("빈 입력 필드가 없습니다.");
                }
            }
            // 내용 변경 시 자동 저장
            saveDailyGoals(studentid, pid);
        });

        // 입력 필드 변경 시 자동 저장 기능 추가
        $(document).on('change', 'input[name^="day"], input[name^="date"]', function () {
            saveDailyGoals(studentid, pid);
        });

        function saveDailyGoals(studentid, pid) {
            var formData = $("#dailyGoalsForm").serialize();
            $.ajax({
                url: "save_daily_goals.php",
                type: "POST",
                data: formData + "&studentid=" + encodeURIComponent(studentid) + "&pid=" + encodeURIComponent(pid),
                dataType: "json",
                success: function (response) {
                    if (response.status === 'success') {
                        // 저장 성공 메시지 표시 (부모 창 새로고침 제거 - 입력 모드 유지)
                        swal("", "저장되었습니다.", { buttons: false, timer: 500 });
                    } else {
                        swal("", "저장을 실패했습니다", { buttons: false, timer: 2000 });
                    }
                },
                error: function (xhr, status, error) {
                    swal("", "저장을 실패했습니다", { buttons: false, timer: 2000 });
                }
            });
        }

        // Datepicker 초기화 및 클립보드 복사 기능 추가
        $(function () {
            // 한국어로 설정
            $(".datepicker").datepicker({
                dateFormat: 'yy-mm-dd',
                firstDay: 1, // 주의 시작 요일을 월요일로 설정
                showMonthAfterYear: true,
                yearSuffix: '년',
                monthNames: ['1월', '2월', '3월', '4월', '5월', '6월',
                    '7월', '8월', '9월', '10월', '11월', '12월'],
                monthNamesShort: ['1월', '2월', '3월', '4월', '5월', '6월',
                    '7월', '8월', '9월', '10월', '11월', '12월'],
                dayNamesMin: ['일', '월', '화', '수', '목', '금', '토'],
                onSelect: function (dateText, inst) {
                    var dayOfWeek = getDayOfWeek(dateText);
                    $(this).closest('tr').find('.day-column').text(dayOfWeek);
                    // 날짜 변경 시 자동 저장
                    saveDailyGoals(studentid, pid);
                }
            });

            // 클립보드 복사 스크립트
            const buttons = document.querySelectorAll(".copy-button");
            buttons.forEach(function (button) {
                button.addEventListener("click", function () {
                    const textToCopy = button.getAttribute("data-clipboard-text");
                    navigator.clipboard.writeText(textToCopy).then(function () {
                        swal("텍스트가 복사되었습니다", { buttons: false, timer: 300 });
                    }, function (err) {
                        console.error("텍스트 복사 실패", err);
                    });
                });
            });
        });

        function getDayOfWeek(dateStr) {
            var date = new Date(dateStr);
            var dayOfWeek = date.getDay();
            var dayOfWeekKorean = ['일', '월', '화', '수', '목', '금', '토'];
            return dayOfWeekKorean[dayOfWeek];
        }

        // 초기화 함수
        function resetDailyForm() {
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
                    // 모든 목표 입력 필드 초기화
                    $("#dailyGoalsForm").find("input[name^='day']").val('');

                    // 날짜 필드는 현재 주의 날짜로 초기화
                    var today = new Date();
                    var dayOfWeek = today.getDay(); // 0 (일요일) ~ 6 (토요일)
                    var monday = new Date(today);
                    monday.setDate(today.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1)); // 이번 주 월요일

                    for (var i = 0; i < 7; i++) {
                        var currentDate = new Date(monday);
                        currentDate.setDate(monday.getDate() + i);
                        var dateStr = currentDate.toISOString().split('T')[0];
                        $("input[name='date" + (i + 1) + "']").val(dateStr);

                        // 요일 표시 업데이트
                        var dayOfWeekKorean = ['일', '월', '화', '수', '목', '금', '토'];
                        var koreanDay = dayOfWeekKorean[currentDate.getDay()];
                        $("input[name='date" + (i + 1) + "']").closest('tr').find('.day-column').text(koreanDay);
                    }

                    swal("초기화 완료", "모든 입력 내용이 초기화되었습니다.", "success");
                }
            });
        }
    </script>
</body>
</html>
