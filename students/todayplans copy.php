<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$studentid = required_param('id', PARAM_INT);
$cid = required_param('cid', PARAM_INT);
$nch = required_param('nch', PARAM_INT);
$pid = required_param('pid', PARAM_INT); // Added to get 'pid' parameter
$timecreated = time();

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

 
$planinfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_todayplans WHERE userid=? AND progressid=? ORDER BY id DESC LIMIT 1", array($studentid, $pid));

$plans = array();
$times = array();

$allTimesEmpty = true;
for ($i = 1; $i <= 16; $i++) {
    $planField = 'plan' . $i;
    $timeField = 'due' . $i;

    $plans[] = isset($planinfo->$planField) ? $planinfo->$planField : '';
    $timeValue = isset($planinfo->$timeField) ? date('H:i', $planinfo->$timeField) : '';

    if (!empty($timeValue) && $timeValue != '00:00') {
        $allTimesEmpty = false;
    }
    $times[] = $timeValue;
}

if ($allTimesEmpty) {
    // 모든 시간 값이 비어있는 경우, 기본 값으로 초기화
    for ($i = 0; $i < 16; $i++) {
        $times[$i] = '00:00';
    }
}

// JavaScript에서 사용할 변수들을 스크립트로 출력
$initialRows = max(3, count(array_filter($plans)));

$totalWeeks = 16; // 최대 16주차까지
$plans_json = json_encode($plans);
$times_json = json_encode($times);

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

foreach ($topics as $topic) {
    $chkitemid = $topic->id;
    $displaytext = $topic->displaytext;
    $linkurl = $topic->linkurl;
    $position = $topic->position;

    $url_components = parse_url($linkurl);
    $params = array();
    if (isset($url_components['query'])) {
        parse_str($url_components['query'], $params);
    }
    $cntid = isset($params['id']) ? $params['id'] : '';
    $quizid = isset($params['quizid']) ? $params['quizid'] : '';

    // $displaytext 수정 (필요한 경우)
    if (strpos($displaytext, '마무리') !== false) {
        // $thischtitle은 필요한 경우에 맞게 정의해야 합니다.
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
    $copyButton = '<span class="copy-button" data-clipboard-text="' . htmlspecialchars($displaytext, ENT_QUOTES, 'UTF-8') . '">📋' . $displaytext . '</span>';
    $insertButton = '<button class="insert-button" data-title="' . htmlspecialchars($displaytext, ENT_QUOTES, 'UTF-8') . '">➕</button>';
    $linkIcon = '<a href="' . $linkurl . '" target="_blank">🔗</a>';
    $chapterlist .= '<tr><td>' . $chapter_num . '</td><td>' . $insertButton . ' ' . $copyButton . ' ' . $linkIcon . '</td></tr>';
    $chapter_num++;
}

// 코스 이름을 가져옵니다.
$course = $DB->get_record('course', array('id' => $cid));
$subjectname = isset($course->fullname) ? $course->fullname : '학습목록';
// 추가된 코드 끝
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>공부일기</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <!-- 추가적인 스타일이나 스크립트가 필요하면 여기에 포함 -->
     
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <!-- 시간 입력 필드를 위한 추가적인 스크립트는 필요하지 않습니다 -->



        <!-- jQuery 및 jQuery Timepicker Plugin 추가 -->
        <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <!-- jQuery UI 추가 -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- jQuery Timepicker Addon 추가 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css">



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
            width: 70%;
        }
        .deadline-column {
            width: 20%;
        }
        .apply-column {
            width: 10%;
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
        var times = <?php echo $times_json; ?>;
        var totalWeeks = 16;
        var currentRows = <?php echo $initialRows; ?>;
    </script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8 left-column">
             <form id="todayGoalsForm">
                    <table class="table table-bordered" id="goalsTable">
                        <thead>
                            <tr align="left">
                                <th style="background-color: #3ba365;color:white;" class="deadline-column" align="left" height="45">시간</th>
                                <th style="background-color: #3ba365;color:white;" class="goal-column" height="45"><?php echo $goaldisplay; ?></th>
                                <th style="background-color: white;" class="apply-column" height="45"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for ($week = 1; $week <= $initialRows; $week++) {
                                $planValue = isset($plans[$week - 1]) ? $plans[$week - 1] : '';
                                $timeValue = isset($times[$week - 1]) ? $times[$week - 1] : '00:00';
                                echo '<tr>
                                    <td><input type="time" class="form-control time-input" name="time' . $week . '" value="' . htmlspecialchars($timeValue, ENT_QUOTES) . '" step="300"></td>
                                    <td><input type="text" class="form-control" name="week' . $week . '" value="' . htmlspecialchars($planValue, ENT_QUOTES) . '"></td>
                                    <td><button type="button" class="btn btn-success apply-btn" onclick="applyWeek(' . $week . ', ' . $studentid . ')">적용</button></td>
                                </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-default add-more-btn" onclick="addMoreRows()">+ 추가</button>
                    <div align="center">
                        <button type="button" onclick="saveTodayPlans(<?php echo $studentid; ?>, <?php echo $pid; ?>)" class="btn btn-primary">저장하기</button>
                    </div>
                </form>
            </div>
            <!-- Right Column -->
            <div class="col-md-4 right-column">
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
                                    $href = 'https://mathking.kr/moodle/local/augmented_teacher/students/dailyplans.php?id=' . $studentid . '&pid=' . $pid . '&cid=' . $cid . '&nch=' . $nch_loop;
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

        $(document).ready(function() {
            $(document).on('focus', 'input[type="time"]', function() {
                $(this)[0].showPicker();
            });
        });

        // 'insert-button' 클릭 이벤트 처리기 수정
        $(document).on('click', '.insert-button', function() {
            var chapterTitle = $(this).data('title');
            if (lastFocusedInput && $(lastFocusedInput).is('input[name^="week"]')) {
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
                $('input[name^="week"]').each(function() {
                    if (!found && $(this).val() == '') {
                        $(this).val(chapterTitle);
                        found = true;
                    }
                });
                if (!found) {
                    // 빈 입력 필드가 없으면 행을 추가하고 입력합니다.
                    addMoreRows();
                    $('input[name^="week"]').each(function() {
                        if (!found && $(this).val() == '') {
                            $(this).val(chapterTitle);
                            found = true;
                        }
                    });
                }
            }
        });

        function saveTodayPlans(studentid, pid) {
            var formData = $("#todayGoalsForm").serialize();
            $.ajax({
                url: "save_todayplan.php",
                type: "POST",
                data: formData + "&studentid=" + encodeURIComponent(studentid) + "&pid=" + encodeURIComponent(pid),
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        swal("", response.message, {buttons: false, timer: 2000}); 
                    } else {
                        swal("", "저장을 실패했습니다", {buttons: false, timer: 2000});
                    }
                },
                error: function(xhr, status, error) {
                    swal("", "저장을 실패했습니다", {buttons: false, timer: 2000});
                }
            });
        }

        function applyWeek(week, studentid) {
            var planValue = $('input[name="week' + week + '"]').val();
            var timeValue = $('input[name="time' + week + '"]').val();
            var url = "https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=" + studentid + "&cntinput=" + encodeURIComponent(planValue) + "&gtype=%EC%A3%BC%EA%B0%84%EB%AA%A9%ED%91%9C&time=" + encodeURIComponent(timeValue);
            window.open(url);
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
                var timeValue = times[currentRows - 1] || '00:00';

                var newRow = '<tr>' +
                    '<td><input type="time" class="form-control" name="time' + currentRows + '" value="' + timeValue + '"></td>' +
                    '<td><input type="text" class="form-control" name="week' + currentRows + '" value="' + planValue + '"></td>' +
                    '<td><button type="button" class="btn btn-success apply-btn" onclick="applyWeek(' + currentRows + ', ' + studentid + ')">적용</button></td>' +
                    '</tr>';
                tbody.append(newRow);
            }
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