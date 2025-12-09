<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid = isset($_GET["userid"]) ? intval($_GET["userid"]) : $USER->id;
$tbegin = isset($_GET["tb"]) ? intval($_GET["tb"]) : NULL;
$tend = isset($_GET["te"]) ? intval($_GET["te"]) : NULL;
$display_limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

require_login();
$timecreated = time(); 
$hoursago = $timecreated - 14400;
$aweekago = $timecreated - 604800;

// 사용자 정보 가져오기
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
$stdname = $thisuser->firstname . $thisuser->lastname;

// 사용자 역할 가져오기
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = ?", array($USER->id, 22));
$role = $userrole->role;

// 챕터 로그 가져오기
$chapterlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog WHERE userid = ? ORDER BY id DESC LIMIT 1", array($studentid));

// 핸드라이팅 데이터 가져오기
$limitnum = $display_limit + 1; // 더보기 확인을 위해 +1

if ($tbegin == NULL) {
    $sql = "SELECT * FROM mdl_abessi_messages WHERE userid = ? AND active = 1 AND timemodified > ? ORDER BY timecreated DESC";
    $params = array($studentid, $hoursago);
    $handwriting = $DB->get_records_sql($sql, $params, 0, $limitnum);
} else {
    $sql = "SELECT * FROM mdl_abessi_messages WHERE userid = ? AND active = 1 AND timemodified BETWEEN ? AND ? ORDER BY timecreated DESC";
    $params = array($studentid, $tbegin, $tend);
    $handwriting = $DB->get_records_sql($sql, $params, 0, $limitnum);
}

$result = json_decode(json_encode($handwriting), True);

// 더보기 여부 확인
$has_more = false;
if (count($result) > $display_limit) {
    $has_more = true;
    $result = array_slice($result, 0, $display_limit);
}

$timelineData = array();
$currentstatus = '자유활동';
$tprev = $timecreated;
$quizstatus = 0;
$eventspaceanalysis = '<a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic_timeline.php?userid=' . $studentid . '">📊</a>';
$ForDeepLearning = '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid=' . $studentid . '"> <img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651023487.png" width=40></a>';

if (!empty($result)) {
    foreach ($result as $value) {
        $event = array();

        $wboardid = $value['wboardid'];
        $contentstype = $value['contentstype'];
        $contentsid = $value['contentsid'];
        $contentstitle = $value['contentstitle'];
        $instruction = $value['instruction'];
        $nstroke = $value['nstroke'];
        $ncommit = $value['feedback'];
        $nretry = isset($value['nretry']) ? $value['nretry'] : 0;
        if ($ncommit != 0) $ncommit = '<b style="color:#FF0000;">' . $ncommit . '</b>';
        $usedtime = round($value['usedtime'] / 60, 1) . '분';
        $tinterval = round(($tprev - $value['timemodified']) / 60, 0) . '분';
        $tprev = $value['timemodified'];
        $status = $value['status'];
        if ($tinterval < 0) $tinterval = round(($timecreated - $value['timemodified']) / 60, 0) . '분';

        $timestamp_diff = $timecreated - $value['timemodified'];
        if ($timestamp_diff <= 60) $timestamp = $timestamp_diff . '초 전';
        elseif ($timestamp_diff <= 3600) $timestamp = round($timestamp_diff / 60, 0) . '분 전';
        elseif ($timestamp_diff <= 86400) $timestamp = round($timestamp_diff / 3600, 0) . '시간 전';
        elseif ($timestamp_diff <= 2592000) $timestamp = round($timestamp_diff / 86400, 0) . '일 전';
        else $timestamp = date('Y-m-d', $value['timemodified']);

        $instructionBtn = '';

        if ($value['student_check'] == 1) $checkstatus = 'Checked';
        else $checkstatus = '';

        if ($role !== 'student' || $timestamp_diff > 7200)
            $checkout = '<input type="checkbox" name="checkAccount"  ' . $checkstatus . '  onClick="ChangeCheckBox(213,\'' . $studentid . '\',\'' . $wboardid . '\', this.checked)"/>';
        else
            $checkout = '▶ ';

        // 이벤트 정보 초기화
        $event['timestamp'] = $timestamp;
        $event['nstroke'] = $nstroke;
        $event['ncommit'] = strip_tags($ncommit); // HTML 태그 제거
        $event['usedtime'] = $usedtime;
        $event['status'] = $status;
        $event['title'] = $contentstitle;
        $event['imgSrc'] = '';
        $event['details'] = array();
        $event['description'] = '';
        $event['wboardid'] = $wboardid;
        $event['contentsid'] = $contentsid;
        $event['contentstype'] = $contentstype;
        $event['checkout'] = $checkout;
        $event['result'] = isset($value['result']) ? $value['result'] : '';
        $event['nretry'] = $nretry;

        // 상태나 wboardid에 따라 처리
        if ($value['status'] === 'commitquiz') {
            // 퀴즈 처리
            $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id = ?", array($contentsid));
            $attemptlog = $DB->get_record_sql("SELECT id, quiz, attempt, sumgrades, timefinish FROM mdl_quiz_attempts WHERE quiz = ? AND userid = ? AND timemodified > ? ORDER BY id DESC LIMIT 1", array($moduleid->instance, $studentid, $aweekago));

            if ($attemptlog) {
                $timefinish = date("m/d | H:i", $attemptlog->timefinish);
                $quiz = $DB->get_record_sql("SELECT sumgrades FROM mdl_quiz WHERE id = ?", array($moduleid->instance));
                $quizgrade = round($attemptlog->sumgrades / $quiz->sumgrades * 100, 0);
                $event['title'] = $contentstitle . ' (최근점수:' . $quizgrade . '점, 최근시험:' . $timefinish . ')';
                $event['cnturl'] = 'https://mathking.kr/moodle/mod/quiz/review.php?attempt=' . $attemptlog->id . '&studentid=' . $studentid;
            } else {
                $event['cnturl'] = 'https://mathking.kr/moodle/mod/quiz/view.php?id=' . $contentsid;
            }

            $event['description'] = '<b><a style="color:#000000;text-decoration:none;" href="' . $event['cnturl'] . '" target="_blank">' . $event['title'] . '</a></b>';
            $event['type'] = 'quiz';
        } elseif (strpos($wboardid, 'jnrsorksqcrark') !== false) {
            // 노트 처리
            $noteurl = $value['url'];
            $getimg = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id = ?", array($contentsid));
            $ctext = $getimg->pageicontent;
            if (strpos($getimg->reflections, '지시사항') !== false)
                $instructionBtn = '<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid=' . $contentsid . '&cnttype=1&studentid=' . $studentid . '" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/instructions.png" width=20></a><br><br>';
            if ($getimg->reflections != NULL)
                $reflections = $getimg->reflections . '<hr>';

            $htmlDom = new DOMDocument;
            @$htmlDom->loadHTML($ctext);
            $imageTags = $htmlDom->getElementsByTagName('img');
            $imgSrc = '';
            foreach ($imageTags as $imageTag) {
                $imgSrc = $imageTag->getAttribute('src');
                $imgSrc = str_replace(' ', '%20', $imgSrc);
                if (strpos($imgSrc, 'MATRIX') !== false || strpos($imgSrc, 'MATH') !== false || strpos($imgSrc, 'imgur') !== false) break;
            }
            $event['imgSrc'] = $imgSrc;
            $event['details'][] = $reflections;
            $event['description'] = $reflections;
            $event['type'] = 'note';
        } else {
            // 기본 처리
            $qtext = $DB->get_record_sql("SELECT questiontext, reflections1 FROM mdl_question WHERE id = ?", array($contentsid));
            if (strpos($qtext->reflections1, '지시사항') !== false)
                $instructionBtn = '<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid=' . $contentsid . '&cnttype=2&studentid=' . $studentid . '" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/instructions.png" width=20></a><br><br>';

            $htmlDom = new DOMDocument;
            @$htmlDom->loadHTML($qtext->questiontext);
            $imageTags = $htmlDom->getElementsByTagName('img');
            $imgSrc = '';
            foreach ($imageTags as $imageTag) {
                $imgSrc = $imageTag->getAttribute('src');
                $imgSrc = str_replace(' ', '%20', $imgSrc);
                if (strpos($imgSrc, 'MATRIX/MATH') !== false || strpos($imgSrc, 'HintIMG') !== false) break;
            }
            $event['imgSrc'] = $imgSrc;
            $event['details'][] = $qtext->reflections1;
            $event['description'] = $qtext->reflections1;
            $event['type'] = 'default';
        }

        // 상태에 따른 색상 지정
        $event['color'] = 'bg-gray-100';
        if ($event['result'] === 'wrong' || $event['status'] === 'incorrect') {
            $event['color'] = 'bg-red-100';
        } elseif ($event['result'] === 'right' || $event['status'] === 'correct') {
            $event['color'] = 'bg-green-100';
        } elseif ($event['status'] === 'realtime') {
            $event['color'] = 'bg-blue-100';
        }

        // 이벤트를 타임라인 데이터에 추가
        $timelineData[] = $event;
    }
}

// 과목 네비게이션 생성
$subjectnav = '<div id="tableContainer" style="background-color:#F0F1F4;">
    <table width=100%>
        <tr>
            <td><img style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/createtimefolding.png" width=40></td>
            <td style="color:black">&nbsp; 
                <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=95&nch=1&studentid=' . $studentid . '&type=init">초등3-1</a> |
                <!-- 다른 과목 링크 -->
            </td>
        </tr>
    </table>
</div>';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>타임라인 탐색기</title>
    <!-- Tailwind CSS 추가 -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- 추가적인 스타일 -->
    <style>
        /* 추가적인 CSS */
        .details-toggle {
            cursor: pointer;
        }
        .details {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
        }
        .details.open {
            max-height: 1000px;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="max-w-4xl mx-auto relative bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between mb-4">
            <h2 class="text-2xl font-bold text-gray-800">타임라인 탐색기</h2>
            <!-- 필요한 경우 상단 버튼 추가 -->
        </div>

        <!-- 과목 네비게이션 출력 -->
        <?php echo $subjectnav; ?>

        <div class="mt-6">
            <?php foreach ($timelineData as $index => $event): ?>
                <div class="timeline-event <?php echo $event['color']; ?> group relative flex flex-col gap-2 items-start mb-4 p-4 rounded-lg transition-all duration-300 ease-in-out">
                    <div class="flex items-center w-full">
                        <div class="flex-none w-24 text-sm font-medium text-gray-600">
                            <?php echo $event['timestamp']; ?>
                        </div>
                        <div class="flex-1 text-gray-800 font-semibold">
                            <?php echo $event['title']; ?>
                        </div>
                        <div class="flex-none text-sm text-gray-500">
                            <?php echo $event['nstroke']; ?>획 / <?php echo $event['usedtime']; ?> / <?php echo $event['ncommit']; ?>번
                        </div>
                    </div>

                    <div class="w-full">
                        <p class="text-gray-600 mb-2">
                            <?php echo $event['description']; ?>
                        </p>
                    </div>

                    <?php if (!empty($event['imgSrc']) || !empty($event['details'])): ?>
                        <div class="details-toggle text-blue-500 cursor-pointer" onclick="toggleDetails(<?php echo $index; ?>)">
                            상세보기
                        </div>
                        <div id="details-<?php echo $index; ?>" class="details">
                            <div class="bg-white rounded-lg p-4 shadow-md mt-2">
                                <?php if (!empty($event['imgSrc'])): ?>
                                    <img src="<?php echo $event['imgSrc']; ?>" alt="" class="max-w-full h-auto mb-4">
                                <?php endif; ?>
                                <?php foreach ($event['details'] as $detail): ?>
                                    <p><?php echo $detail; ?></p>
                                <?php endforeach; ?>
                                <!-- 기타 링크 및 버튼 -->
                                <?php echo $instructionBtn; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- 더보기 버튼 -->
            <?php if ($has_more): ?>
                <?php
                $next_limit = $display_limit + 20;
                $load_more_url = "?userid=$studentid&limit=$next_limit";
                ?>
                <div class="text-center mt-6">
                    <a href="<?php echo $load_more_url; ?>" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        더보기
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 필요한 스크립트 추가 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleDetails(index) {
            var details = document.getElementById('details-' + index);
            if (details.classList.contains('open')) {
                details.classList.remove('open');
            } else {
                details.classList.add('open');
            }
        }

        // 체크박스 변경 함수
        function ChangeCheckBox(Eventid, Userid, Wboardid, Checkvalue) {
            var checkimsi = 0;
            if (Checkvalue == true) {
                checkimsi = 1;
            }
            alert("적용되었습니다.");
            $.ajax({
                url: "../students/check.php",
                type: "POST",
                dataType: "json",
                data: {
                    "userid": Userid,
                    "wboardid": Wboardid,
                    "checkimsi": checkimsi,
                    "eventid": Eventid,
                },
                success: function(data) {}
            });
            setTimeout(function() {
                location.reload();
            }, 200);
        }
    </script>
</body>
</html>
