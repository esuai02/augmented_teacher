<?php
// 필요한 설정 및 로그인 확인
include_once("/home/moodle/public_html/moodle/config.php");
require_login();
global $DB, $USER;

// GET 파라미터 수집
$params = [
    'cid' => $_GET['cid'] ?? null,
    'nch' => $_GET['nch'] ?? null,
    'cmid' => $_GET['cmid'] ?? null,
    'domain' => $_GET['dmn'] ?? null,
    'nthispage' => $_GET['page'] ?? null,
    'pgtype' => $_GET['pgtype'] ?? null,
    'quizid' => $_GET['quizid'] ?? null,
    'studentid' => $_GET['studentid'] ?? $USER->id,
];

$timecreated = time();

// 사용자 정보 가져오기
$userrole = $DB->get_field_sql(
    "SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22 ORDER BY id DESC LIMIT 1",
    [$USER->id]
);
$role = $userrole ?? '';

// 학습 스타일 및 사용자 이름 가져오기
$learningstyle = $DB->get_field_sql(
    "SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 90 ORDER BY id DESC LIMIT 1",
    [$params['studentid']]
);

$userinfo = $DB->get_record('user', ['id' => $params['studentid']], 'firstname, lastname');
$username = $userinfo->firstname . $userinfo->lastname;

// 주간 목표 가져오기
$weeklyGoal = $DB->get_record_sql(
    "SELECT text FROM mdl_abessi_today WHERE userid = ? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1",
    [$params['studentid']]
);

// 탭 제목 설정
$tabtitle = ($role === 'student') ? 'G : ' . ($weeklyGoal->text ?? '') : $username . '의 수학노트';

// 현재 URL 정보
$mynoteurl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$mynoteurl_params = parse_url($mynoteurl, PHP_URL_QUERY);

// 콘텐츠 페이지 가져오기
$cntpages = $DB->get_records_sql(
    "SELECT * FROM mdl_icontent_pages WHERE cmid = ? ORDER BY pagenum ASC",
    [$params['cmid']]
);

$ntotalpages = count($cntpages);
$contentslist = $contentslist2 = $contentslist3 = '';
$width1 = 80;
$width2 = 20;

foreach ($cntpages as $page) {
    $title = $page->title;
    $npage = $page->pagenum;
    $contentsid = $page->id;

    if ($npage == 1) {
        $contentsid0 = $contentsid;
    }

    // 마일스톤 업데이트
    if (
        $npage == $ntotalpages &&
        (strpos($title, '표유형') !== false || strpos($title, 'heck') !== false)
    ) {
        $DB->execute(
            "UPDATE {icontent_pages} SET milestone = 1 WHERE id = ?",
            [$contentsid]
        );
    }

    // 화이트보드 및 메시지 처리
    $wboardid = 'jnrsorksqcrark' . $contentsid . '_user' . $params['studentid'];
    $thisboard = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_messages WHERE wboardid = ? ORDER BY timemodified DESC LIMIT 1",
        [$wboardid]
    );
    $milestone = $DB->get_field_sql(
        "SELECT milestone FROM mdl_icontent_pages WHERE id = ? ORDER BY id DESC LIMIT 1",
        [$contentsid]
    ) ?? 0;

    if (
        (empty($thisboard->wboardid) && $USER->id == $params['studentid']) ||
        empty($thisboard->url)
    ) {
        $mynoteurl2 = http_build_query([
            'cid' => $params['cid'],
            'nch' => $params['nch'],
            'cmid' => $params['cmid'],
            'page' => $npage,
            'studentid' => $params['studentid'],
            'quizid' => $params['quizid'],
        ]);

        $DB->execute(
            "INSERT INTO {abessi_messages} 
            (userid, userto, userrole, talkid, nstep, turn, student_check, status, contentstype, wboardid, contentstitle, contentsid, url, timemodified, timecreated)
            VALUES (?, 2, ?, 2, 0, ?, 0, 'begintopic', 1, ?, 'inspecttopic', ?, ?, ?, ?)",
            [
                $params['studentid'],
                $role,
                $milestone,
                $wboardid,
                $contentsid,
                $mynoteurl2,
                $timecreated,
                $timecreated,
            ]
        );
    }

    // 헤더 이미지 설정
    if ($npage == 1) {
        $headimg = '<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg1.png" width="15">';
    } elseif (strpos($title, 'Check') !== false) {
        $headimg = '<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png" width="15">';
    } elseif (strpos($title, '유형') !== false) {
        $headimg = '<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg3.png" width="15">';
    } else {
        $headimg = '<img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/himg2.png" width="15">';
    }

    $presetfunction = 'ConnectNeurons';

    if ($params['pgtype'] === 'quiz') {
        // 퀴즈 페이지 처리
        $showpage = 'https://mathking.kr/moodle/mod/quiz/view.php?id=' . $params['quizid'];

        if ($learningstyle === '도제' && strpos($title, '대표') !== false) {
            continue;
        } elseif (strpos($title, '유형') !== false) {
            $contentslist2 .= '<tr><td><a href="mynote.php?' . http_build_query(array_merge($params, ['page' => $npage])) . '">' . $headimg . ' ' . $title . '</a></td></tr>';
        } elseif (strpos($title, '복습') !== false) {
            $contentslist3 .= '<tr><td><a href="mynote.php?' . http_build_query(array_merge($params, ['page' => $npage])) . '"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width="15"> ' . $title . '</a></td></tr>';
        } else {
            $contentslist .= '<tr><td><a href="mynote.php?' . http_build_query(array_merge($params, ['page' => $npage])) . '">' . $headimg . ' ' . $title . '</a></td></tr>';
        }

        $nnextpage = $params['nthispage'] + 1;
        $nextpage = $DB->get_record_sql(
            "SELECT id, title FROM mdl_icontent_pages WHERE cmid = ? AND pagenum = ? ORDER BY id DESC LIMIT 1",
            [$params['cmid'], $nnextpage]
        );

        if (strpos($nextpage->title ?? '', '유형') !== false && $params['quizid']) {
            $nextlearningurl = 'mynote.php?' . http_build_query(array_merge($params, ['page' => $nnextpage]));
        } elseif ($params['quizid']) {
            $nextlearningurl = 'chapter.php?' . http_build_query([
                'cid' => $params['cid'],
                'nch' => $params['nch'],
                'cntid' => $params['cmid'] + 1,
                'studentid' => $params['studentid'],
            ]);
        }

        $rule = '<a style="text-decoration:none;color:white;" href="' . $nextlearningurl . '"><button class="stylish-button">NEXT</button></a>';
    } elseif ($npage == $params['nthispage']) {
        // 현재 페이지 처리
        $topictitle = $title;
        $cnttext = $DB->get_record('icontent_pages', ['id' => $contentsid]);
        $maintext = $cnttext->maintext;
        $milestone = $cnttext->milestone ?? 0;
        $thispageid = $contentsid;

        $audiocnt = '';
        if ($cnttext->audiourl) {
            $audiocnt = '<audio controls style="width:250px;height: 50px;"><source src="' . $cnttext->audiourl . '" type="audio/mpeg"></audio><hr>';
        }

        $contentslink = '';
        if (strpos($cnttext->reflections1, 'youtube') !== false) {
            $contentslink = ' <a style="color:white;" href="movie.php?' . http_build_query(['cntid' => $contentsid, 'cnttype' => 1, 'studentid' => $params['studentid'], 'wboardid' => $wboardid, 'print' => 0]) . '" target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width="20"></a>';
        } elseif (strpos($cnttext->reflections1, '\tab') !== false) {
            $contentslink = ' <a style="color:white;" href="anki.php?' . http_build_query(['dmn' => $params['domain'], 'cntid' => $contentsid, 'cnttype' => 1, 'studentid' => $params['studentid'], 'wboardid' => $wboardid, 'print' => 0]) . '" target="_blank"><img src="https://ankiweb.net/logo.png" width="20"></a>';
        }

        // 히포캠퍼스 콘텐츠 설정
        $HippocampusCnt = '';
        if ($milestone == 1 || strpos($cnttext->reflections0, '지시사항') !== false) {
            $HippocampusCnt = '<tr style="background-color:green;color:white;"><td><a style="color:white;" href="print_papertest.php?' . http_build_query(['cntid' => $contentsid, 'cnttype' => 1, 'studentid' => $params['studentid'], 'wboardid' => $wboardid, 'print' => 0]) . '" target="_blank">💊 </a><span type="button" onClick="Bridgesteps()">징검다리</span> ' . $contentslink . '</td></tr>';
        } elseif (strpos($cnttext->reflections1, '\tab') !== false) {
            $HippocampusCnt = '<tr style="background-color:green;color:white;"><td> ANKI 퀴즈 ' . $contentslink . '</td></tr>';
        }

        $thispage = $npage;
        $bessiboard = 'cjnNotepageid' . $contentsid . 'jnrsorksqcrark';
        $bessiboard2 = 'CognitiveHunt_' . $contentsid . '_topic';
        $thiswbid = $bessiboard . '_user' . $params['studentid'];

        $showpage = 'board_topic.php?' . http_build_query([
            'id' => $wboardid,
            'contentsid' => $contentsid,
            'studentid' => $params['studentid'],
            'quizid' => $params['quizid'],
        ]);

        if (strpos($topictitle, '이해') !== false || strpos($topictitle, '특강') !== false) {
            $showpage = 'replay.php?' . http_build_query([
                'id' => $bessiboard,
                'srcid' => $wboardid,
                'contentsid' => $contentsid,
                'contentstype' => 1,
                'studentid' => $params['studentid'],
            ]);
        }

        // 메시지 업데이트
        if ($milestone == 1 && $USER->id == $params['studentid']) {
            $DB->execute(
                "UPDATE {abessi_messages} SET turn = 1, student_check = 1, timemodified = ?, timecreated = ?, active = 1, contentsid = ?, url = ? WHERE wboardid = ? ORDER BY id DESC LIMIT 1",
                [$timecreated, $timecreated, $contentsid, $mynoteurl_params, $wboardid]
            );
        }

        // 이미지 업로드 버튼 설정
        $imageupload = '';
        if ($role !== 'student' && !in_array($USER->id, [5, 1500])) {
            $imageupload = '<span style="background-color:lightgreen;" id="image_upload" type="button">image+</span>';
        }

        // 다음 학습 URL 설정
        $nnextpage = $npage + 1;
        $nextpage = $DB->get_record_sql(
            "SELECT id, title FROM mdl_icontent_pages WHERE cmid = ? AND pagenum = ? ORDER BY id DESC LIMIT 1",
            [$params['cmid'], $nnextpage]
        );

        $nextlearningurl = '';
        if (
            strpos($nextpage->title ?? '', '유형') !== false &&
            strpos($title, '유형') === false &&
            $params['quizid']
        ) {
            $nextlearningurl = 'mynote.php?' . http_build_query(array_merge($params, ['page' => $npage, 'pgtype' => 'quiz']));
        } elseif ($nextpage) {
            $nextlearningurl = 'mynote.php?' . http_build_query(array_merge($params, ['page' => $nnextpage]));
        } elseif ($params['quizid'] && strpos($title, '유형') !== false && $params['pgtype'] !== 'quiz') {
            $nextlearningurl = 'chapter.php?' . http_build_query([
                'cid' => $params['cid'],
                'nch' => $params['nch'],
                'cntid' => $params['cmid'] + 1,
                'studentid' => $params['studentid'],
            ]);
        } else {
            $nextlearningurl = 'chapter.php?' . http_build_query([
                'cid' => $params['cid'],
                'nch' => $params['nch'],
                'cntid' => $params['cmid'] + 1,
                'studentid' => $params['studentid'],
            ]);
        }

        $rule = '<a style="text-decoration:none;color:white;" href="' . $nextlearningurl . '"><button class="stylish-button">NEXT</button></a>';

        // 콘텐츠 리스트 업데이트
        if (strpos($title, '유형') !== false) {
            $contentslist2 .= '<tr style="background-color:lightpink;"><td><span type="button" onClick="' . $presetfunction . '(\'' . $contentsid . '\')">' . $headimg . '</span><b> ' . $title . '</b></td></tr>' . $HippocampusCnt;
        } elseif (strpos($title, '복습') !== false) {
            $contentslist3 .= '<tr><td><span type="button" onClick="' . $presetfunction . '(\'' . $contentsid . '\')"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width="15"></span> ' . $title . '</td></tr>';
        } else {
            $contentslist .= '<tr style="background-color:lightblue;"><td><span type="button" onClick="' . $presetfunction . '(\'' . $contentsid . '\')">' . $headimg . '</span><b> ' . $title . '</b></td></tr>' . $HippocampusCnt;
        }
    } else {
        // 기타 페이지 처리
        if ($learningstyle === '도제' && strpos($title, '대표') !== false) {
            continue;
        } elseif (strpos($title, '유형') !== false) {
            $contentslist2 .= '<tr><td><a href="mynote.php?' . http_build_query(array_merge($params, ['page' => $npage])) . '">' . $headimg . ' ' . $title . '</a></td></tr>';
        } elseif (strpos($title, '복습') !== false) {
            $contentslist3 .= '<tr><td><a href="mynote.php?' . http_build_query(array_merge($params, ['page' => $npage])) . '"><img src="https://mathking.kr/Contents/IMAGES/restore.png" width="15"> ' . $title . '</a></td></tr>';
        } else {
            $contentslist .= '<tr><td><a href="mynote.php?' . http_build_query(array_merge($params, ['page' => $npage])) . '">' . $headimg . ' ' . $title . '</a></td></tr>';
        }
    }
}

// 추가 링크 설정
$cntlink = '';
if ($role !== 'student') {
    $cntlink = ' <a href="https://mathking.kr/moodle/mod/icontent/view.php?id=' . $params['cmid'] . '" target="_blank"><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/cntlink.png" width="15"></a>';
}
$cntlink .= ' <a href="editonetimeusecontents.php?cntid=' . ($thispageid ?? '') . '&cnttype=1" target="_blank">📰맞춤공부</a>';

$singleref = ' <a href="connectmemories.php?domain=8&contentstype=2" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/learningpath.png" width="15"></a>';

// 퀴즈 시도 설정
$attemptquiz = '';
if ($params['quizid']) {
    $cnttext2 = $DB->get_record('icontent_pages', ['id' => $contentsid0]);
    if (strpos($cnttext2->reflections1, '지시사항') !== false) {
        $HippocampusCnt = '<tr style="background-color:green;color:white;"><td><a style="color:white;" href="print_papertest.php?' . http_build_query(['cntid' => $contentsid0, 'cnttype' => 1, 'studentid' => $params['studentid'], 'wboardid' => $wboardid, 'print' => 1]) . '" target="_blank">💊 준비학습 </a></td></tr>';
    }
    if ($params['pgtype'] === 'quiz') {
        $attemptquiz = '<tr><td style="background-color:lightblue;"><span type="button" onClick="' . $presetfunction . '(\'' . $contentsid0 . '\')">' . $headimg . '</span> 개념체크 퀴즈 <a href="https://mathking.kr/moodle/mod/quiz/view.php?id=' . $params[
