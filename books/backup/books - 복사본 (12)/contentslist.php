<?php
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$cid = $_GET["cid"];

// 기존 코드 유지
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'");
if ($curri->id >= 80 && $curri->id <= 94) $dmn = 'science';
else $dmn = 'math';
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
    $qid = $curri->$qid;
    if ($title == NULL) continue;
    $moduleid = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id='$qid'");
    $attemptlog = $DB->get_record_sql("SELECT id, quiz, sumgrades, attempt, timefinish FROM mdl_quiz_attempts WHERE quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
    $timefinish = date("m/d | H:i", $attemptlog->timefinish);
    $quiz = $DB->get_record_sql("SELECT id, sumgrades FROM mdl_quiz WHERE id='$moduleid->instance'");
    $quizgrade = round($attemptlog->sumgrades / $quiz->sumgrades * 100, 0);
    $quizresult = '';
    if ($quizgrade != NULL) $quizresult = '<span style="color:lightgrey;">' . $quizgrade . '점 (' . $attemptlog->attempt . '회)</span>';

    // 텍스트 복사 버튼 추가
    $copyButton = '<button class="copy-button" data-clipboard-text="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">Copy</button>';

    if ($nch == $chnum) {
        $thischtitle = $curri->$chname;
        $cntstr = 'cnt' . $nch;
        $checklistid = $curri->$cntstr;

        $ankilink = '<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=' . $dmn . '&sbjt=' . $ankisbjt . '&studentid=' . $studentid . '&nch=' . $nch . '"><img src="https://ankiweb.net/logo.png" width="20"></a>';
        $gptstr = 'gpt' . $nch;
        $gpturl = $curri->$gptstr;

        $chapterlist .= '<tr><td>' . $nch . '</td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id=' . $checklistid . '" target="_blank"><b>' . $title . '</b></a>' . $copyButton . ' ' . $quizresult . ' ' . $ankilink . '</td></tr>';
        $wboardid = 'obsnote' . $cid . '_ch' . $chnum . '_user' . $studentid;
    } else {
        $chapterlist .= '<tr><td>' . $nch . '</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?' . $modeinfo . '&cid=' . $cid . '&nch=' . $nch . '&studentid=' . $studentid . '">' . $title . '</a>' . $copyButton . ' ' . $quizresult . '</td></tr>';
    }
}

// 스타일링 및 테이블 출력
echo '
<style>
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
    /* 버튼 스타일 */
    .copy-button {
        background-color: #4CAF50;
        border: none;
        color: white;
        padding: 5px 10px;
        text-align: center;
        font-size: 12px;
        cursor: pointer;
        border-radius: 3px;
        margin-left: 5px;
    }
    .copy-button:hover {
        background-color: #45a049;
    }
</style>

<table class="chapter-table">
    <thead>
        <tr>
            <th>챕터 번호</th>
            <th>챕터 제목</th>
        </tr>
    </thead>
    <tbody>
        ' . $chapterlist . '
    </tbody>
</table>

<!-- 클립보드 복사 스크립트 -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const buttons = document.querySelectorAll(".copy-button");
    buttons.forEach(function(button) {
        button.addEventListener("click", function() {
            const textToCopy = button.getAttribute("data-clipboard-text");
            navigator.clipboard.writeText(textToCopy).then(function() {
                alert("텍스트가 복사되었습니다: " + textToCopy);
            }, function(err) {
                console.error("텍스트 복사 실패", err);
            });
        });
    });
});
</script>';
?>
