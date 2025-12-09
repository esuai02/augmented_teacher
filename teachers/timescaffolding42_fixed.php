<?php
/////////////////////////////// 전체 코드 (수정 완료) ///////////////////////////////

// 메모리 및 실행 시간 제한 설정
ini_set('memory_limit', '256M');
set_time_limit(120);

// 오류 보고 설정 (디버깅용 - 운영환경에서는 제거)
error_reporting(E_ERROR | E_PARSE);

try {
    // config.php 경로는 서버 환경에 맞게 정확히 확인해야 합니다.
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
    } else {
        die("Moodle configuration file not found.");
    }

    if (file_exists(dirname(__FILE__) . "/openai_config.php")) {
        include_once(dirname(__FILE__) . "/openai_config.php"); // OpenAI API 설정 포함
    } else {
        // 이 파일이 없으면 AI 관련 기능이 동작하지 않으므로, 에러 핸들링이 필요할 수 있습니다.
    }
} catch (Exception $e) {
    http_response_code(500);
    die("Configuration loading failed: Please contact administrator.");
}

global $DB, $USER;

// 기본 보안 검증
if (!$USER || !$DB) {
    http_response_code(500);
    die("System not properly initialized. Please try again.");
}

$studentid = isset($_GET["userid"]) ? intval($_GET["userid"]) : null;
$cntinput = isset($_GET["cntinput"]) ? $_GET["cntinput"] : null;
$mode = isset($_GET["mode"]) ? $_GET["mode"] : null;
if ($studentid == NULL) $studentid = $USER->id;
$timecreated = time();
$hoursago = $timecreated - 14400;
$halfdayago = $timecreated - 43200;
$aweekago = $timecreated - 604800;
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM {user} WHERE id=?", array($studentid));
$stdname = $thisuser ? $thisuser->lastname . $thisuser->firstname : 'Unknown User';

$userrole = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} where userid=? AND fieldid='22' ", array($USER->id));
$role = $userrole ? $userrole->role : 'student';

// ===== AJAX 핸들러 섹션 =====

// Synergetic API 연동 AJAX 핸들러
if (isset($_GET['action']) && $_GET['action'] === 'fetch_learning_activity') {
    header('Content-Type: application/json');

    $userid = intval($_GET['userid'] ?? $studentid);
    $timeBegin = intval($_GET['tb'] ?? ($timecreated - 43200)); // 기본값: 12시간 전
    $timeEnd = intval($_GET['te'] ?? $timecreated); // 기본값: 현재 시간

    try {
        if ($timeBegin == null) {
            $handwriting = $DB->get_records_sql("SELECT * FROM {abessi_messages} WHERE userid=? AND active='1' AND timemodified > ? ORDER BY timemodified DESC LIMIT 100", array($userid, $hoursago));
        } else {
            $handwriting = $DB->get_records_sql("SELECT * FROM {abessi_messages} WHERE userid=? AND active='1' AND timemodified > ? AND timemodified < ? ORDER BY timemodified DESC LIMIT 100", array($userid, $timeBegin, $timeEnd));
        }

        $activityData = array();
        $totalActivities = 0;
        $completionScore = 0;

        foreach ($handwriting as $activity) {
            $totalActivities++;

            // 활동 완성도 기본 평가
            $activityScore = 0;
            if ($activity->usedtime > 300) $activityScore += 30; // 5분 이상 사용
            if ($activity->nstroke > 50) $activityScore += 30; // 충분한 작성량
            if ($activity->feedback > 0) $activityScore += 40; // 피드백 받음

            $completionScore += $activityScore;

            $activityData[] = array(
                'id' => $activity->id,
                'wboardid' => $activity->wboardid,
                'contentstype' => $activity->contentstype,
                'contentsid' => $activity->contentsid,
                'contentstitle' => $activity->contentstitle,
                'instruction' => $activity->instruction,
                'usedtime' => round($activity->usedtime / 60, 1), // 분 단위
                'nstroke' => $activity->nstroke,
                'feedback' => $activity->feedback,
                'status' => $activity->status,
                'timemodified' => $activity->timemodified,
                'completion_score' => $activityScore
            );
        }

        $avgCompletionScore = $totalActivities > 0 ? round($completionScore / $totalActivities, 1) : 0;

        echo json_encode(array(
            'success' => true,
            'data' => array(
                'activities' => $activityData,
                'summary' => array(
                    'total_activities' => $totalActivities,
                    'avg_completion_score' => $avgCompletionScore,
                    'time_range' => array(
                        'begin' => $timeBegin,
                        'end' => $timeEnd
                    )
                )
            ),
            'timestamp' => $timecreated
        ));
    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Failed to fetch learning activity data: ' . $e->getMessage()
        ));
    }

    exit; // AJAX 응답 후 스크립트 종료
}

// OpenAI API 테스트 핸들러
if (isset($_GET['action']) && $_GET['action'] === 'test_openai_connection') {
    header('Content-Type: application/json');
    
    if (!function_exists('testOpenAIConnection')) {
        echo json_encode(array(
            'success' => false,
            'error' => 'OpenAI configuration not loaded'
        ));
        exit;
    }
    
    $result = testOpenAIConnection();
    echo json_encode($result);
    exit;
}

// OpenAI 분석 핸들러
if (isset($_GET['action']) && $_GET['action'] === 'analyze_with_openai') {
    header('Content-Type: application/json');
    
    $userid = intval($_GET['userid'] ?? $studentid);
    $timeRange = intval($_GET['timeRange'] ?? 1);
    
    if (!function_exists('generateAnalysisWithCache')) {
        echo json_encode(array(
            'success' => false,
            'error' => 'OpenAI analysis functions not available'
        ));
        exit;
    }
    
    // 학습 데이터 수집
    $startTime = $timecreated - ($timeRange * 86400);
    $learningData = $DB->get_records_sql(
        "SELECT * FROM {abessi_messages} 
         WHERE userid = ? AND active = '1' AND timemodified > ? 
         ORDER BY timemodified DESC LIMIT 100",
        array($userid, $startTime)
    );
    
    // 컨텍스트 정보 수집
    $contextInfo = array(
        'user_id' => $userid,
        'user_name' => $stdname,
        'time_range' => $timeRange,
        'current_goal' => $checkgoal ? $checkgoal->text : '',
        'weekly_goal' => $wgoal ? $wgoal->text : ''
    );
    
    // 캐시를 활용한 분석 생성
    $analysis = generateAnalysisWithCache($userid, $learningData, $contextInfo, $timeRange);
    
    echo json_encode($analysis);
    exit;
}

// 피드백 생성 핸들러
if (isset($_GET['action']) && $_GET['action'] === 'generate_feedback') {
    header('Content-Type: application/json');
    
    $userid = intval($_GET['userid'] ?? $studentid);
    $analysisData = json_decode($_GET['analysisData'] ?? '{}', true);
    
    if (!function_exists('generateFeedbackWithCache')) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Feedback generation functions not available'
        ));
        exit;
    }
    
    $feedback = generateFeedbackWithCache($userid, $analysisData);
    
    echo json_encode($feedback);
    exit;
}

// 캐시 관리 핸들러
if (isset($_GET['action']) && $_GET['action'] === 'manage_cache') {
    header('Content-Type: application/json');
    
    $operation = $_GET['operation'] ?? 'stats';
    $userid = intval($_GET['userid'] ?? $studentid);
    
    if (!function_exists('getCacheStatistics')) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Cache management functions not available'
        ));
        exit;
    }
    
    switch ($operation) {
        case 'stats':
            $result = getCacheStatistics($userid);
            break;
        case 'clear':
            $result = clearUserCache($userid);
            break;
        case 'clear_expired':
            $result = clearExpiredCache();
            break;
        default:
            $result = array('success' => false, 'error' => 'Invalid operation');
    }
    
    echo json_encode($result);
    exit;
}

// ===== 페이지 렌더링 시작 =====

// 녹음 동의 여부 확인
$recordingConsent = $DB->get_record_sql("SELECT * FROM {abessi_mathtalk} WHERE userid=? AND type='agreement' ORDER BY timecreated DESC LIMIT 1", array($studentid));
$hasRecordingConsent = ($recordingConsent && $recordingConsent->hide == 0) ? true : false;

if ($role === 'student') echo '<title>📒수학일기</title>';
else echo '<title>' . $stdname . '📒</title>';

$context = $DB->get_record_sql("SELECT * FROM {abessi_tracking} WHERE userid=? AND type LIKE 'context' ORDER BY id DESC LIMIT 1", array($studentid));
$contextinfo = $context ? $context->text : '';

if ($studentid == 2 && $USER->id != 2) {
    exit();
}

$wgoal = $DB->get_record_sql("SELECT * FROM {abessi_today} WHERE userid=? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1", array($studentid));
$checkgoal = $DB->get_record_sql("SELECT * FROM {abessi_today} WHERE userid=? AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1", array($studentid));
$chapterlog = $DB->get_record_sql("SELECT * FROM {abessi_chapterlog} WHERE userid=? ORDER BY id DESC LIMIT 1", array($studentid));
$termplan2 = $DB->get_record_sql("SELECT id FROM {abessi_progress} WHERE userid LIKE ? AND plantype ='분기목표' AND hide=0 AND deadline > ? ORDER BY id DESC LIMIT 1", array($studentid, $timecreated));

$inspectToday = $checkgoal ? $checkgoal->inspect : 0;
$date = $checkgoal ? gmdate("h:i A", $checkgoal->timecreated + 32400) : '';

$status4 = '';
$status5 = '';
if ($inspectToday == 2) $status4 = 'checked';
elseif ($inspectToday == 3) $status5 = 'checked';

$lastbreak = $DB->get_record_sql("SELECT id,timecreated FROM {abessi_missionlog} WHERE userid=? AND timecreated>? AND eventid='7128' ORDER BY id DESC LIMIT 1", array($studentid, $halfdayago));
$beforebreak = 60;
if ($lastbreak && $lastbreak->id != NULL) {
    $beforebreak = -1;
} elseif ($lastbreak) {
    $beforebreak = 60 - (($timecreated - $lastbreak->timecreated) / 60);
}

$todolist = '상황별 조치방법 (학생 데이터를 토대로 아래 활동 중에서 필요한 활동을 선택하도록 해주세요)
1. 개념복습 : 개념을 직접 찾아보고 설명을 요청하거나 관련된 예제퀴즈나 대표유형을 10분정도 지시하는 것은 학생의 능동활동을 증가시키고 활력을 줄 수 있습니다.
2. 오답노트 검사 : 오답노트 방식을 관찰하여 능동적인 상태인지를 체크하고 학생에게 피드백을 줄 수 있습니다.
3. ANKI 퀴즈활동 : 기초 개념들을 숙달하지 못해 문제 해석이나 선생님의 설명을 흡수하는데 어려움을 겪거나 지연되는 경우 효과적입니다.
4. 질문준비 루틴 : 학생이 할 수 있는 부분을 능동적으로 수행한 후 질의응답이 이루어질 때 가장 효과적입니다. 이를 위해 유형별로 질문 방식을 알려주고 실행하도록 합니다. 충분한 공지가 이루어진 이후에는 질문을 시작할 때 준비 상태를 체크하고 필요한 경우 준비활동 후 다시 질문하도록 요청하는 방식으로 학생이 좀 더 능동적으로 공부하도록 유도할 수 있습니다.
5. 분기목표 입력 : 방학기간 또는 시험기간 등 분기별 최종목표를 입력하여 반복적으로 각인되도록 합니다. 총 6개의 분기로 이루어져 있음. 겨울방학, 1학기 중간고사, 1학기 기말고사, 여름방학, 2학기 중간고사, 2학기 기말고사.
6. 주간목표 입력 : 분기목표를 토대로 주간목표를 설정합니다.
7. 오늘목표 입력 : 주가목표를 토대로 오늘의 목표를 설정합니다.
8. 활동추적 및 자가진단 평가하기 : 오늘목표를 염두해 두고 작은 단위의 활동과 예상 시간을 입력하게 합니다. 학생이 활동을 진행하면서 자신의 상태를 체크하고 평가할 수 있도록 도와줍니다.
9. 지면평가 : 활동 중 특정 부분을 준비하여 선생님에게 직접 설명하며 피드백을 받는 활동입니다. 학생의 능동적인 학습태도를 고취시킬 수 있습니다. 해당 구간에서 부족한 부분을 드러내게 하고 피드백을 통하여 돌파하도록 돕습니다.
10. 질의응답 : 능동적인 질의응답의 몰입을 돕고 동기를 유지하는 최고의 방법입니다.';

$instructions = $DB->get_records_sql("SELECT * FROM {abessi_tracking} WHERE userid=? AND duration > ? AND hide=0 ORDER BY id DESC LIMIT 100", array($studentid, $aweekago));
if ($USER->id == 2) $usercontext = '<SPAN ONCLICK="addContext(\'' . $studentid . '\');">➕</SPAN>';

$result = json_decode(json_encode($instructions), True);

$np = 0;
$pmresult = 0;
$directionlist0 = '';
$directionlist1 = '';
$directionlist2 = '';
$tend_prev = 0;
$totalduration = 0;
$prev_time = '';
$currenttrackingid = 0;

// 그래프용 데이터 배열 생성
$graphData = array();
$alertmessage = ''; // 변수 초기화
$goalid = ''; // goalid 변수 초기화
$lefttime = 0; // lefttime 변수 초기화
$copyContent = ''; // copyContent 변수 초기화

foreach ($result as $value) {
    if ($prev_time !== '' && $prev_time !== date("m_d", $value['timecreated'])) {
        $directionlist2 .= '<tr><td colspan="8"><hr></td></tr>';
    }

    $statustext = $value['status'];
    $trackingtext = $value['text'];
    $trackingid = $value['id'];
    $tresult = $value['timefinished'] - $value['timecreated'];
    $tamount = $value['duration'] - $value['timecreated'];
    if ($tresult < 0) $tresult = 0;
    $headingtext = '';

    if ($statustext === 'waiting') $headingtext = '🔒 대기 | ';
    elseif (strpos($trackingtext, '개념') !== false) $headingtext = '🌱 준비 | ';
    elseif (strpos($trackingtext, '유형') !== false || strpos($trackingtext, '단원') !== false || strpos($trackingtext, '도약') !== false) $headingtext = '🚀 응시 | ';
    elseif (strpos($trackingtext, '오답') !== false) $headingtext = '📝 오답 | ';
    elseif (strpos($trackingtext, '과제') !== false) $headingtext = '📚 과제 | ';
    elseif (strpos($trackingtext, '시험') !== false) $headingtext = '🏬 시험 | ';
    else $headingtext = '🌈 기타 | ';

    $finalMinutes = round(($value['timefinished'] - $value['timecreated']) / 60, 0);
    if ($finalMinutes < 0) $finalMinutes = 0;
    if ($finalMinutes > 60) $finalMinutes = 60;

    if ($tresult > $tamount)
        $tresult_disp = '<div style="display: inline;color:#fcddd9;">' . round(($tresult) / 60, 0) . '분</div>';
    else
        $tresult_disp = '<div style="display: inline;color:green;">' . round(($tresult) / 60, 0) . '분</div>';
    $tamount_disp = '<div style="display: inline;">' . round(($tamount) / 60, 0) . '분</div>';

    $tinterval = $tend_prev > 0 ? $tend_prev - $value['duration'] : 0;
    $statuscolor = '';
    $rowheight = '20px';
    $comeon = '';
    $realtimecomment = '';
    $duetime = '';

    if ($statustext === 'begin') {
        $currenttrackingid = $value['id'];
        $lefttime = round(($value['duration'] - $timecreated) / 60, 0);
        $statustext = '<button id="completebtn" style="background-color: #4CAF50; border: none; color: white; padding:2px 5px; text-align: center; font-size: 16px; cursor: pointer; border-radius: 10px;" onmouseover="this.style.backgroundColor=\'#45a049\';" onmouseout="this.style.backgroundColor=\'#4CAF50\';" ONCLICK="evaluateResult(\'' . $studentid . '\', \'' . $trackingid . '\');">완료</button> <img ONCLICK="addTime(\'' . $studentid . '\');" style="margin-bottom:5px;" src="https://mathking.kr/Contents/IMAGES/addtime.png" width="20">';
        $duetime = '<div style="float: right; white-space: nowrap;" id="second">(' . $lefttime . '분 남음)</div>';
        $statuscolor = '#e0e0e0';
        $rowheight = '50px';
    } elseif ($statustext === 'complete') {
        $totalduration += $value['duration'] - $value['timecreated'];
        $np++;
        $pmresult = $pmresult + $value['result'];
        if ($value['timefinished'] > $value['timecreated']) {
            $graphData[] = array(
                'time' => date("m-d H:i", $value['timecreated']),
                'final' => $finalMinutes,
                'wbtimeave' => min(round($value['wbtimeave'], 0), 30)
            );
        }
        if ($np == 1) {
            $realtimecomment = '<span style="background: skyblue; border-radius: 0.4em; display: inline-block; margin-top:15px;font-size: 16px;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"> ' . mb_substr($value['feedback'], 0, 20, "utf-8") . '...</span>';
            $alertmessage = '다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !';
        }
    }
    // 기타 statustext 조건들...
    
    $warningtext = '';
    if ($tinterval > 600 && $tinterval < 3600 * 6 && ($value['status'] === 'begin' || $value['status'] === 'complete')) {
        $warningtext = '<SPAN style="color:red;"> | 이탈 (' . round($tinterval / 60, 0) . ')</SPAN> ';
    }
    $tend_prev = $value['timecreated'];
    
    if ($value['result'] == 3) $statustext_disp = '<span style="color:green;">매우 만족</span> (' . $value['ndisengagement'] . ')';
    elseif ($value['result'] == 2) $statustext_disp = '<span style="color:grey;">만족</span> (' . $value['ndisengagement'] . ')';
    elseif ($value['result'] == 1) $statustext_disp = '<span style="color:orange;">불만족</span> (' . $value['ndisengagement'] . ')';
    else $statustext_disp = $statustext;
    
    // 방향 리스트에 HTML 추가
    if ($np <= 2) {
        $directionlist0 .= '<tr style="background-color:' . $statuscolor . ';" height=' . $rowheight . 'px>
            <td align="left" width=80%>' . $headingtext . htmlspecialchars($trackingtext) . $warningtext . $duetime . '</td>
            <td width="60%">' . $realtimecomment . '</td>
            <td></td>
            <td align="center">' . $tamount_disp . '</td>
            <td align="center">' . $tresult_disp . '</td>
            <td align="center">' . $statustext_disp . '</td>
            <td></td>
        </tr>';
    } else {
        $directionlist2 .= '<tr style="background-color:white;" height=' . $rowheight . 'px>
            <td align="left" width=80%>' . $headingtext . htmlspecialchars($trackingtext) . $warningtext . $duetime . '</td>
            <td width="60%">' . $realtimecomment . '</td>
            <td></td>
            <td align="center">' . $tamount_disp . '</td>
            <td align="center">' . $tresult_disp . '</td>
            <td align="center">' . $statustext_disp . '</td>
            <td></td>
        </tr>';
    }
    
    $prev_time = date("m_d", $value['timecreated']);
}

// 복사 컨텐츠 생성
$pastActivities = strip_tags(str_replace(['<tr>', '</tr>', '<td>', '</td>'], ["\n", "", "", " | "], $directionlist2));
if (!empty($pastActivities)) {
    $copyContent .= "【지난 활동】\n" . $pastActivities . "\n\n";
}
if (!empty($checkgoal->text)) {
    $copyContent .= "【오늘 목표】\n" . $checkgoal->text . "\n\n";
}
if (!empty($wgoal->text)) {
    $copyContent .= "【주간 목표】\n" . $wgoal->text . "\n\n";
}
$copyContent .= "이상의 값들을 분석하여 학생의 지난 일주일간의 학습 여정을 추론해줘. 추론된 결과를 토대로 학생의 학습여정을 학생의 화법으로 학습일지 스토리텔링을 블로그 형식으로 작성해줘.";

// ===== HTML 출력 시작 =====
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/ready.min.css">
    <link rel="stylesheet" href="../assets/css/demo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        /* 여기에 필요한 CSS 스타일 추가 */
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: #f5f5f5;
        }
        .modern-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .tab-content-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* 추가 스타일... */
    </style>
</head>
<body>
<?php
if ($mode === 'parental') {
    // parental 모드 HTML 출력
    echo '<br><div class="top-menu"><table align="left"><tr>
        <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1" class="btn btn-sm btn-info">일정</a></td>
        <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800" class="btn btn-sm btn-info">계획</a></td>
        <td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$studentid.'" class="btn btn-sm btn-danger">일지</a></td>
        <td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200" class="btn btn-sm btn-info">오늘</a></td>
        <td><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20letter.php?userid='.$studentid.'" class="btn btn-sm btn-info">상담</a></td>
        </tr></table></div>
        <table align="left" width="80%">
        <tr><td> </td><td width="60%"> </td><td><td align="center">Plan</td><td align="center">Final</td><td align="center">상태</td><td></td></tr>
        '.$directionlist0.$directionlist1.'
        <!-- 두 그래프를 나란히 표시할 컨테이너 -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin: 3px auto; width: 90%;">
            <div style="flex: 1; margin-right: 10px;">
                <canvas id="chartCanvasFinal" style="width:100%; height:200px;"></canvas>
            </div>
            <div style="flex: 1; margin-left: 10px;">
                <canvas id="chartCanvasWbtimeave" style="width:100%; height:200px;"></canvas>
            </div>
        </div>'.$directionlist2.'
        </table>';
} else {
    // 기본 모드 HTML 출력 - 여기에 전체 HTML 구조 추가
    echo '<div class="modern-header">
        <h2>' . htmlspecialchars($stdname) . '의 수학일기</h2>
    </div>
    
    <div class="container">
        <div id="main-tabs">
            <ul>
                <li><a href="#tab-activity">📈 활동일지</a></li>
                <li><a href="#tab-notes">📝 메모장</a></li>
                <li><a href="#tab-analysis">🤖 학습분석</a></li>
            </ul>
            
            <div id="tab-activity" class="tab-content-panel">
                <table width="100%">
                    <tr>
                        <td>활동</td>
                        <td>Plan</td>
                        <td>Final</td>
                        <td>상태</td>
                    </tr>
                    ' . $directionlist0 . $directionlist1 . $directionlist2 . '
                </table>
                
                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <div style="flex: 1; margin-right: 10px;">
                        <canvas id="chartCanvasFinal"></canvas>
                    </div>
                    <div style="flex: 1; margin-left: 10px;">
                        <canvas id="chartCanvasWbtimeave"></canvas>
                    </div>
                </div>
            </div>
            
            <div id="tab-notes" class="tab-content-panel">
                <div id="notes-container">
                    <!-- 메모 내용 -->
                </div>
            </div>
            
            <div id="tab-analysis" class="tab-content-panel">
                <div id="analysis-container">
                    <button onclick="runAnalysis()">AI 분석 실행</button>
                    <div id="analysis-result"></div>
                </div>
            </div>
        </div>
    </div>';
}
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// jQuery UI 탭 초기화
$(document).ready(function() {
    $("#main-tabs").tabs();
});

// 타이머 스크립트 (begin 상태일 때만 실행)
<?php if (isset($statustext) && $statustext === 'begin'): ?>
    var counter = <?php echo $lefttime; ?>;
    var Userid = '<?php echo $studentid; ?>';
    var Inputtext = '<?php echo isset($trackingtext) ? addslashes($trackingtext) : ''; ?>';

    if (counter > 3) document.title = "🟢수학일기(" + counter + "분) ";
    else if (counter <= 3 && counter >= 0) document.title = "🟡수학일기(" + counter + "분) ";
    else document.title = "🔴수학일기(" + counter + "분) ";

    var auto_refresh = setInterval(function () {
        var newcontent = counter + "분 남음";
        $("#second").html(newcontent);
        if (counter <= 0) {
            $("#completebtn").click();
            document.title = "🔴수학일기(" + counter + "분) ";
        } else if (counter <= 3 && counter % 3 === 0) {
            document.title = "🟡수학일기(" + counter + "분) ";
            // alertTime 함수 호출 (정의 필요)
        } else {
            document.title = "🟢수학일기(" + counter + "분) ";
        }
        counter = counter - 1;
    }, 60000);
<?php endif; ?>

// evaluateResult 함수 정의
function evaluateResult(Studentid, currentTrackingId) {
    Swal.fire({
        title: "수고하셨습니다",
        text: "마무리 점검 페이지로 이동합니다.",
        icon: "success",
        timer: 1000,
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/challenge_report.php?tid=' + currentTrackingId + '&userid=' + Studentid;
    });
}

// AI 분석 실행 함수
function runAnalysis() {
    $.ajax({
        url: window.location.pathname,
        type: 'GET',
        data: {
            action: 'analyze_with_openai',
            userid: '<?php echo $studentid; ?>',
            timeRange: 1
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#analysis-result').html('<pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
            } else {
                Swal.fire('오류', response.error, 'error');
            }
        },
        error: function() {
            Swal.fire('오류', '서버 연결 실패', 'error');
        }
    });
}

// Chart.js 그래프 그리기
document.addEventListener("DOMContentLoaded", function() {
    var graphData = <?php echo json_encode(array_reverse($graphData)); ?>;

    if (graphData.length > 0) {
        var labels = graphData.map(item => item.time);
        var finalData = graphData.map(item => item.final);
        var wbtimeaveData = graphData.map(item => item.wbtimeave);

        var ctxFinal = document.getElementById('chartCanvasFinal');
        if (ctxFinal) {
            var chartFinal = new Chart(ctxFinal.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '세션별 소요시간',
                        data: finalData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        var ctxWbtimeave = document.getElementById('chartCanvasWbtimeave');
        if (ctxWbtimeave) {
            var chartWbtimeave = new Chart(ctxWbtimeave.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '노트 작성시간',
                        data: wbtimeaveData,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // 복사 버튼 이벤트 리스너
    var copyContent = <?php echo json_encode($copyContent); ?>;
    var copyButton = document.getElementById("copyButton");
    if (copyButton) {
        copyButton.addEventListener("click", function() {
            navigator.clipboard.writeText(copyContent).then(function() {
                Swal.fire({
                    icon: "success",
                    title: "복사 완료",
                    text: "클립보드에 복사되었습니다.",
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(function(err) {
                Swal.fire({
                    icon: "error",
                    title: "복사 실패",
                    text: "클립보드 복사에 실패했습니다.",
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        });
    }
    
    // 알림 메시지 표시
    <?php if (!empty($alertmessage)) : ?>
        // ShowMessage 함수가 정의되어 있다면 사용, 없으면 Swal 사용
        if (typeof ShowMessage === 'function') {
            ShowMessage('<?php echo addslashes($alertmessage); ?>');
        } else {
            Swal.fire({
                title: '알림',
                text: '<?php echo addslashes($alertmessage); ?>',
                icon: 'info',
                timer: 3000,
                showConfirmButton: false
            });
        }
    <?php endif; ?>
});
</script>
</body>
</html>