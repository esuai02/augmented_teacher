<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$studentid = required_param('id', PARAM_INT);
$pid = required_param('pid', PARAM_INT);
$cid = required_param('cid', PARAM_INT);
$timecreated = time();

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
$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid=? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1", array($studentid));

$weeklyGoalText = isset($weeklyGoal->text) ? htmlspecialchars($weeklyGoal->text, ENT_QUOTES) : '';

$goaldisplay = '<span style="color:black;"> <a style="text-decoration:none;color:black;" href="weeklyplans.php?id=' . $studentid . '&pid=' . (isset($termplan->id) ? $termplan->id : '') . '" target="_blank">' . htmlspecialchars($EGinputtime, ENT_QUOTES) . '까지</a> 계획이</span> <b>' . $termMission . '</b><span style="color:black;"> 이어서 이번 주는</span> <b>' . $weeklyGoalText . '</b><span style="color:black;">(을)를 목표로 정진 중입니다.</span>';

// 데이터베이스에서 주간 목표를 가져옵니다.
$planinfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid=? AND progressid=? ORDER BY id DESC LIMIT 1", array($studentid, $pid));

$plans = array();
$dates = array();

$allDatesEmpty = true;
for ($i = 1; $i <= 16; $i++) {
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
    // 모든 날짜 값이 비어있는 경우, 오늘 기준 첫 일요일부터 시작하여 날짜 생성
    $dayOfWeek = date('w', $timecreated); // 0 (일요일)부터 6 (토요일)
    if ($dayOfWeek == 0) { // 오늘이 일요일인 경우
        $startDate = date('Y-m-d', $timecreated);
    } else {
        $startDate = date('Y-m-d', strtotime('next Sunday', $timecreated));
    }
    for ($i = 0; $i < 16; $i++) {
        $dates[$i] = date('Y-m-d', strtotime($startDate . ' + ' . ($i * 7) . ' days'));
    }
} else {
    // 저장된 날짜 값이 일부라도 있는 경우, 비어있는 날짜는 오늘 날짜로 설정
    for ($i = 0; $i < 16; $i++) {
        if (empty($dates[$i])) {
            $dates[$i] = date('Y-m-d');
        }
    }
}

// JavaScript에서 사용할 변수들을 스크립트로 출력
$initialRows = max(3, count(array_filter($plans)));

$totalWeeks = 16; // 최대 16주차까지
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
        $copyButton = '<span class="copy-button" data-clipboard-text="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">📋'.$title.'</span>';

        $chapterlist .= '<tr><td>' . $nch . '</td><td>' . $copyButton . '<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$cid.'&nch='.$nch.'&studentid='.$studentid.'"target="_blank">🔗</a>  </td></tr>';
    }
}
// 추가된 코드 끝
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>주간 목표 관리</title>
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
        var totalWeeks = <?php echo $totalWeeks; ?>;
        var currentRows = <?php echo $initialRows; ?>;
		
    </script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8 left-column">
                <form id="weeklyGoalsForm">
                    <table class="table table-bordered" id="goalsTable">
                        <thead>
                            <tr>
                                <th class="deadline-column">데드라인</th>
                                <th class="goal-column"><?php echo $goaldisplay; ?></th>
                                <th class="apply-column">적용</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for ($week = 1; $week <= $initialRows; $week++) {
                                $planValue = isset($plans[$week - 1]) ? $plans[$week - 1] : '';
                                $dateValue = isset($dates[$week - 1]) ? $dates[$week - 1] : date('Y-m-d');
                                echo '<tr>
                                    <td><input type="text" class="form-control datepicker" name="date' . $week . '" value="' . htmlspecialchars($dateValue, ENT_QUOTES) . '"></td>
                                    <td><input type="text" class="form-control" name="week' . $week . '" value="' . htmlspecialchars($planValue, ENT_QUOTES) . '"></td>
                                    <td><button type="button" class="btn btn-success apply-btn" onclick="applyWeek(' . $week . ', ' . $studentid . ')">적용</button></td>
                                </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-default add-more-btn" onclick="addMoreRows()">+ 추가</button>
                    <div align="center">
                        <button type="button" onclick="saveWeeklyGoals(<?php echo $studentid; ?>, <?php echo $pid; ?>)" class="btn btn-primary">저장하기</button>
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
                                <th> <?php echo $subjectname; ?></th>
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
                                        $href = 'https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id=' . $studentid . '&pid=' . $pid . '&cid=' . $link['cid'];
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
        function saveWeeklyGoals(studentid, pid) {
            var formData = $("#weeklyGoalsForm").serialize();
            $.ajax({
                url: "save_weekly_goals.php",
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
            var url = "https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=" + studentid + "&cntinput=" + encodeURIComponent(planValue) + "&gtype=%EC%A3%BC%EA%B0%84%EB%AA%A9%ED%91%9C";
            window.open(url);
        }

        function addMoreRows() {
            var tbody = $("#goalsTable tbody");
            for (var i = 0; i < 3; i++) {
                currentRows++;
                if (currentRows > totalWeeks) {
                    swal("더 이상 추가할 행이 없습니다.");
                    break;
                }
                var planValue = plans[currentRows - 1] || '';
                var dateValue = dates[currentRows - 1] || getNextDate();

                var newRow = '<tr>' +
                    '<td><input type="text" class="form-control datepicker" name="date' + currentRows + '" value="' + dateValue + '"></td>' +
                    '<td><input type="text" class="form-control" name="week' + currentRows + '" value="' + planValue + '"></td>' +
                    '<td><button type="button" class="btn btn-success apply-btn" onclick="applyWeek(' + currentRows + ', ' + studentid + ')">적용</button></td>' +
                    '</tr>';
                tbody.append(newRow);
            }
            // 새로 추가된 날짜 입력 필드에 datepicker 적용
            $(".datepicker").datepicker({
                dateFormat: 'yy-mm-dd',
                firstDay: 1,
                showMonthAfterYear: true,
                yearSuffix: '년',
                monthNames: ['1월', '2월', '3월', '4월', '5월', '6월',
                    '7월', '8월', '9월', '10월', '11월', '12월'],
                monthNamesShort: ['1월', '2월', '3월', '4월', '5월', '6월',
                    '7월', '8월', '9월', '10월', '11월', '12월'],
                dayNamesMin: ['일', '월', '화', '수', '목', '금', '토']
            });
        }

        function getNextDate() {
            // 현재 행의 인덱스를 사용하여 다음 날짜 계산
            var index = currentRows - 1;
            if (dates[index]) {
                return dates[index];
            } else {
                // 이전 날짜에 7일을 더함
                var prevDate = dates[index - 1] || getTodayDate();
                var nextDate = new Date(prevDate);
                nextDate.setDate(nextDate.getDate() + 7);
                var yyyy = nextDate.getFullYear();
                var mm = String(nextDate.getMonth() + 1).padStart(2, '0');
                var dd = String(nextDate.getDate()).padStart(2, '0');
                var formattedDate = yyyy + '-' + mm + '-' + dd;
                dates[index] = formattedDate;
                return formattedDate;
            }
        }

        function getTodayDate() {
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); // 월은 0부터 시작하므로 +1
            var yyyy = today.getFullYear();

            return yyyy + '-' + mm + '-' + dd;
        }

        // Datepicker 초기화 및 클립보드 복사 기능 추가
        $(function() {
            // 한국어로 설정
            $(".datepicker").datepicker({
                dateFormat: 'yy-mm-dd',
                firstDay: 0, // 주의 시작 요일을 일요일로 설정
                showMonthAfterYear: true,
                yearSuffix: '년',
                monthNames: ['1월', '2월', '3월', '4월', '5월', '6월',
                    '7월', '8월', '9월', '10월', '11월', '12월'],
                monthNamesShort: ['1월', '2월', '3월', '4월', '5월', '6월',
                    '7월', '8월', '9월', '10월', '11월', '12월'],
                dayNamesMin: ['일', '월', '화', '수', '목', '금', '토']
            });

            // 클립보드 복사 스크립트
            const buttons = document.querySelectorAll(".copy-button");
            buttons.forEach(function(button) {
                button.addEventListener("click", function() {
                    const textToCopy = button.getAttribute("data-clipboard-text");
                    navigator.clipboard.writeText(textToCopy).then(function() {
						swal("텍스트가 복사되었습니다", {buttons: false,timer: 300});
                     }, function(err) {
                        console.error("텍스트 복사 실패", err);
                    });
                });
            });
        });
    </script>
</body>
</html>
