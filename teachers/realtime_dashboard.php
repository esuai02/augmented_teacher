<?php 
/////////////////////////////// realtime_dashboard.php ///////////////////////////////
// 파일: teachers/realtime_dashboard.php
// 설명: 담당반 학생들의 실시간 활동 상태를 모니터링하는 대시보드
// URL: https://mathking.kr/moodle/local/augmented_teacher/teachers/realtime_dashboard.php?userid=2

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 선생님 ID 받기
$teacherid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
if ($teacherid == 0) {
    die("Error [realtime_dashboard.php:12]: teacherid가 필요합니다.");
}

// 선생님 및 동료 정보 조회
$collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid'"); 
$teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$teacherid' AND fieldid='79'"); 
$tsymbol = $teacher->symbol;
$teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$collegues->mntr1' AND fieldid='79'"); 
$tsymbol1 = $teacher1->symbol;
$teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$collegues->mntr2' AND fieldid='79'"); 
$tsymbol2 = $teacher2->symbol;
$teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$collegues->mntr3' AND fieldid='79'"); 
$tsymbol3 = $teacher3->symbol;  

$timecreated = time();
$sixhoursago = $timecreated - 21600; // 6시간 = 21600초
$halfdayago = $timecreated - 43200; // 12시간 = 43200초
$todayStart = strtotime('today'); // 오늘 시작 시간

$assistantid1 = $collegues->mntr1;
$assistantid2 = $collegues->mntr2;
$assistantid3 = $collegues->mntr3; 

$teachername = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid'");

if ($tsymbol == NULL) $tsymbol = '##';
if ($tsymbol1 == NULL) $tsymbol1 = '##';
if ($tsymbol2 == NULL) $tsymbol2 = '##';
if ($tsymbol3 == NULL) $tsymbol3 = '##';

// 6시간 이내 접속한 담당반 학생 조회
$students = $DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended='0' AND lastaccess > '$sixhoursago' AND (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%' OR firstname LIKE '%$tsymbol2%' OR firstname LIKE '%$tsymbol3%') ORDER BY id DESC");  

$result = json_decode(json_encode($students), true);

// 학생별 활동 정보 수집
$activeStudents = [];
foreach ($result as $value) {
    $studentid = $value['id'];
    $userlastaccess = $value['lastaccess'];
    
    // 6시간 이내 접속 여부 확인
    if ($userlastaccess < $sixhoursago) {
        continue;
    }
    
    // Engagement 데이터 조회
    $engagement1 = $DB->get_record_sql("SELECT id, url, timecreated FROM mdl_abessi_missionlog WHERE userid='$studentid' AND eventid=17 ORDER BY id DESC LIMIT 1");
    $engagement2 = $DB->get_record_sql("SELECT id, timecreated FROM mdl_logstore_standard_log WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
    $engagement3 = $DB->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
    
    $teng1 = $engagement1 ? (int)((time() - $engagement1->timecreated) / 60) : 9999;
    $teng2 = $engagement2 ? (int)((time() - $engagement2->timecreated) / 60) : 9999;
    $teng3 = $engagement3 ? (int)((time() - $engagement3->tlaststroke) / 60) : 9999;
    
    $lastaccess = min($teng1, $teng2, $teng3);
    
    // 1시간(60분) 이상 비활동시 생략
    if ($lastaccess >= 60) {
        continue;
    }
    
    // 가장 최근 활동 유형 판별 및 URL/상태 결정
    $iframeUrl = '';
    $statusText = '';
    $studentName = $value['firstname'] . $value['lastname'];
    $lastTimestamp = 0;
    
    if ($teng3 <= $teng1 && $teng3 <= $teng2) {
        // $teng3가 가장 최근 - 화이트보드 활동
        $iframeUrl = "https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid={$studentid}&mode=1";
        $statusText = "{$lastaccess}분 전";
        $activityType = 'whiteboard';
        $lastTimestamp = (int)$engagement3->tlaststroke;
    } elseif ($teng2 <= $teng1 && $teng2 <= $teng3) {
        // $teng2가 가장 최근 - 로그 활동
        $logDetail = $DB->get_record_sql("SELECT * FROM mdl_logstore_standard_log WHERE id='{$engagement2->id}'");
        if ($logDetail && $logDetail->component == 'mod_quiz') {
            $statusText = "{$lastaccess}분 전";
            $iframeUrl = "https://mathking.kr/moodle/mod/quiz/attempt.php?attempt={$logDetail->objectid}";
        } else {
            $statusText = "{$lastaccess}분 전";
            $iframeUrl = "https://mathking.kr/moodle/user/profile.php?id={$studentid}";
        }
        $activityType = 'quiz';
        $lastTimestamp = (int)$engagement2->timecreated;
    } else {
        // $teng1이 가장 최근 - 미션로그 활동
        $iframeUrl = $engagement1->url;
        $statusText = "{$lastaccess}분 전";
        $activityType = 'mission';
        $lastTimestamp = (int)$engagement1->timecreated;
    }
    
    if (!empty($iframeUrl)) {
        // 학생 추가 정보 조회
        // 1. 오늘목표 조회 (navbar.php 방식)
        $checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1");
        
        $todayGoalText = $checkgoal ? $checkgoal->text : '목표 미설정';
        $goalTimecreated = $checkgoal ? (int)$checkgoal->timecreated : 0;
        $goalElapsed = $goalTimecreated > 0 ? (int)((time() - $goalTimecreated) / 60) : 0; // 분 단위 경과 시간
        $calmnessScore = $checkgoal ? (int)$checkgoal->score : 0;
        
        // 2. 침착도 등급 계산 (navbar.php 방식)
        $calmnessGrade = 'F';
        $calmnessColor = '#999';
        if ($calmnessScore < 70) {
            $calmnessGrade = 'D';
            $calmnessColor = '#e74c3c';
        } elseif ($calmnessScore < 75) {
            $calmnessGrade = 'C';
            $calmnessColor = '#e67e22';
        } elseif ($calmnessScore < 80) {
            $calmnessGrade = 'C+';
            $calmnessColor = '#f39c12';
        } elseif ($calmnessScore < 85) {
            $calmnessGrade = 'B';
            $calmnessColor = '#3498db';
        } elseif ($calmnessScore < 90) {
            $calmnessGrade = 'B+';
            $calmnessColor = '#2980b9';
        } elseif ($calmnessScore < 95) {
            $calmnessGrade = 'A';
            $calmnessColor = '#27ae60';
        } else {
            $calmnessGrade = 'A+';
            $calmnessColor = '#16a085';
        }
        
        // 3. 오늘 포모도로(트래킹) 조회 - timescaffolding.php의 Final 로직 사용
        // mdl_abessi_tracking에서 완료된(complete) 또는 진행중(begin) 항목의 실제 소요 시간 조회
        $trackingRecords = $DB->get_records_sql("SELECT id, timecreated, timefinished, status FROM mdl_abessi_tracking WHERE userid = ? AND timecreated >= ? AND hide = 0 AND (status = 'complete' OR status = 'begin') ORDER BY id ASC", [$studentid, $todayStart]);
        $pomodoroCount = count($trackingRecords);
        
        // 4. Final 값들 (timefinished - timecreated)을 분 단위로 개별 표시
        $durationList = '';
        $durationHtml = '';
        if ($pomodoroCount > 0) {
            $durations = [];
            $durationItems = [];
            foreach ($trackingRecords as $record) {
                // timescaffolding.php의 Final 로직: timefinished - timecreated
                $tresult = $record->timefinished - $record->timecreated;
                if ($tresult < 0) $tresult = 0;
                $durationMin = round($tresult / 60); // 초 → 분
                $durations[] = $durationMin;
                // 30분 기준 색상: 30분 초과 파란색, 30분 이하 녹색
                $color = $durationMin > 30 ? '#3b82f6' : '#10b981';
                $durationItems[] = '<span style="background:'.$color.';color:white;padding:1px 4px;border-radius:3px;margin:0 1px;">'.$durationMin.'</span>';
            }
            $durationList = implode(',', $durations);
            $durationHtml = implode('', $durationItems);
        }
        
        $activeStudents[] = [
            'studentid' => $studentid,
            'name' => $studentName,
            'url' => $iframeUrl,
            'status' => $statusText,
            'lastaccess' => $lastaccess,
            'activityType' => $activityType,
            'lastTimestamp' => $lastTimestamp,
            'todayGoal' => $todayGoalText,
            'goalElapsed' => $goalElapsed,
            'pomodoroCount' => $pomodoroCount,
            'calmnessGrade' => $calmnessGrade,
            'calmnessColor' => $calmnessColor,
            'calmnessScore' => $calmnessScore,
            'durationList' => $durationList,
            'durationHtml' => $durationHtml
        ];
    }
}

// 학생 수에 따른 그리드 열 수 결정 (최대 2행 유지)
$studentCount = count($activeStudents);
// 2행으로 제한: 열 수 = 학생 수 / 2 (올림)
$gridColumns = max(2, ceil($studentCount / 2));
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>실시간 대시보드 - <?php echo $teachername->firstname . $teachername->lastname; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Malgun Gothic', sans-serif;
            background: #1a1a2e;
            color: #eee;
            padding: 10px;
            padding-bottom: 50px;
            margin: 0;
            height: 100vh;
            overflow: hidden;
            box-sizing: border-box;
        }
        .header {
            background: linear-gradient(135deg, #16213e, #0f3460);
            padding: 5px 10px;
            border-radius: 0;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 999;
        }
        .header h1 {
            font-size: 0.85em;
            color: #e94560;
        }
        .header .info {
            color: #94a3b8;
            font-size: 0.7em;
        }
        .student-count {
            background: #e94560;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 0.7em;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(<?php echo $gridColumns; ?>, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 10px;
            height: calc(100vh - 60px);
            overflow: hidden;
        }
        .student-card {
            background: #16213e;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #0f3460;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .card-header {
            padding: 6px 10px;
            background: #0f3460;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .card-header .name {
            font-weight: bold;
            color: #00d9ff;
            cursor: pointer;
            text-decoration: none;
        }
        .card-header .name:hover {
            text-decoration: underline;
            color: #5ce1e6;
        }
        .card-header .status {
            font-size: 0.7em;
            color: #94a3b8;
            margin-left: auto;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            background: rgba(0, 217, 255, 0.1);
        }
        .card-header .status:hover {
            background: rgba(0, 217, 255, 0.3);
            color: #00d9ff;
        }
        .student-info {
            display: flex;
            gap: 6px;
            font-size: 0.65em;
            align-items: center;
        }
        .link-btn {
            background: rgba(139, 92, 246, 0.3);
            color: #c4b5fd;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65em;
            text-decoration: none;
            cursor: pointer;
        }
        .link-btn:hover {
            background: rgba(139, 92, 246, 0.5);
            color: #fff;
        }
        .link-btn.diary {
            background: rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }
        .link-btn.diary:hover {
            background: rgba(245, 158, 11, 0.5);
            color: #fff;
        }
        .info-tag {
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .info-tag.goal {
            background: rgba(233, 69, 96, 0.2);
            color: #ff8fa3;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .info-tag.pomodoro {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        .info-tag.calmness {
            padding: 2px 8px;
            font-weight: bold;
        }
        .info-tag.elapsed {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        .info-tag.avgtime {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
        }
        /* Tooltip 스타일 */
        .info-tag, .card-header .name, .card-header .status, .link-btn {
            position: relative;
        }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85em;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 100;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            max-width: 300px;
            white-space: normal;
            text-align: center;
        }
        [data-tooltip]::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 100;
            margin-bottom: -6px;
        }
        [data-tooltip]:hover::after,
        [data-tooltip]:hover::before {
            opacity: 1;
            visibility: visible;
        }
        .info-tag.goal::after {
            max-width: 280px;
            word-break: break-word;
        }
        .activity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7em;
            margin-left: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .activity-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .activity-badge.whiteboard { background: #10b981; color: white; }
        .activity-badge.quiz { background: #f59e0b; color: white; }
        .activity-badge.mission { background: #8b5cf6; color: white; }
        .iframe-container {
            width: 100%;
            height: calc(100% - 50px);
            min-height: 200px;
            position: relative;
            overflow: hidden;
        }
        .iframe-container iframe {
            width: 200%;
            height: 200%;
            border: none;
            transform: scale(0.5);
            transform-origin: top left;
        }
        .refresh-indicator {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.7);
            color: #00d9ff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.7em;
            display: none;
        }
        .no-students {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
            font-size: 1.2em;
        }
        .last-update {
            text-align: center;
            padding: 10px;
            color: #64748b;
            font-size: 0.8em;
        }
        .student-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .student-card:hover {
            box-shadow: 0 4px 20px rgba(0, 217, 255, 0.3);
        }
        /* 모달 스타일 */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            width: 90%;
            height: 98%;
            max-width: none;
            background: #16213e;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 50px rgba(0, 217, 255, 0.4);
        }
        .modal-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #0f3460, #16213e);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e94560;
        }
        .modal-header .modal-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #00d9ff;
        }
        .modal-header .modal-status {
            color: #94a3b8;
            font-size: 0.9em;
        }
        .modal-close {
            background: #e94560;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5em;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background 0.2s;
        }
        .modal-close:hover {
            background: #ff6b6b;
        }
        .modal-iframe-container {
            width: 100%;
            height: calc(100% - 70px);
        }
        .modal-iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 실시간 학생 활동 대시보드 <span class="student-count">활성 <?php echo count($activeStudents); ?>명</span></h1>
        <div class="info">
            <?php echo $teachername->firstname . $teachername->lastname; ?>
            <span style="margin-left:10px;color:#fff;font-size:0.65em;">🔄 iframe:10초 | 모달:10초 | 접속체크:5분</span><span id="lastUpdate" style="display:none;"></span>
        </div>
    </div>
    
    <?php if (empty($activeStudents)): ?>
        <div class="no-students">
            현재 1시간 이내 활동 중인 학생이 없습니다.
        </div>
    <?php else: ?>
        <div class="grid-container">
            <?php foreach ($activeStudents as $index => $student): ?>
                <div class="student-card" id="card-<?php echo $student['studentid']; ?>">
                    <div class="card-header">
                        <span class="name" onclick="event.stopPropagation(); openActivityModal('<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', <?php echo $student['studentid']; ?>)" data-tooltip="클릭: 활동결과 보기"><?php echo htmlspecialchars($student['name']); ?></span>
                        <span class="activity-badge <?php echo $student['activityType']; ?>" onclick="event.stopPropagation(); openModal('<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['status'], ENT_QUOTES); ?>', '<?php echo $student['activityType']; ?>', <?php echo $student['studentid']; ?>)">
                            <?php 
                                switch($student['activityType']) {
                                    case 'whiteboard': echo '화이트보드'; break;
                                    case 'quiz': echo '퀴즈'; break;
                                    case 'mission': echo '미션'; break;
                                }
                            ?>
                        </span>
                        <span class="link-btn diary" onclick="event.stopPropagation(); openDiaryModal('<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', <?php echo $student['studentid']; ?>)"><span style="filter: grayscale(1) brightness(10);">📔</span> <?php echo $student['durationHtml'] ?: '-'; ?></span>
                        <div class="student-info">
                            <span class="info-tag goal" data-tooltip="오늘목표: <?php echo htmlspecialchars($student['todayGoal']); ?>">🎯<?php echo mb_substr($student['todayGoal'], 0, 16); ?><?php echo mb_strlen($student['todayGoal']) > 16 ? '..' : ''; ?></span>
                            <span class="info-tag calmness" style="background: <?php echo $student['calmnessColor']; ?>33; color: <?php echo $student['calmnessColor']; ?>;"><?php echo $student['calmnessGrade']; ?></span>
                            <?php if ($student['goalElapsed'] > 0): ?>
                            <span class="info-tag elapsed" data-tooltip="수업 시작 후 경과 시간">⏱<?php echo $student['goalElapsed']; ?>분</span>
                            <?php endif; ?>
                        </div>
                        <span class="status" onclick="event.stopPropagation(); openModal('<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['status'], ENT_QUOTES); ?>', '<?php echo $student['activityType']; ?>', <?php echo $student['studentid']; ?>)" data-tooltip="클릭: 현재 활동 확대 보기"><?php echo htmlspecialchars($student['status']); ?></span>
                    </div>
                    <div class="iframe-container">
                        <iframe 
                            id="iframe-<?php echo $student['studentid']; ?>"
                            src="<?php echo htmlspecialchars($student['url']); ?>"
                            data-url="<?php echo htmlspecialchars($student['url']); ?>"
                            data-studentid="<?php echo $student['studentid']; ?>"
                        ></iframe>
                        <div class="refresh-indicator" id="refresh-<?php echo $student['studentid']; ?>">새로고침 중...</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    

    <!-- 확대 모달 -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="modal-title" id="modalTitle">학생 이름</span>
                    <span class="activity-badge" id="modalBadge"></span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="info-tag goal" id="modalGoal" style="font-size: 0.85em;" data-tooltip="오늘목표">🎯 목표</span>
                    <span class="info-tag pomodoro" id="modalPomodoro" style="font-size: 0.85em;" data-tooltip="오늘 작성한 수학일기 개수">📝 0회</span>
                    <span class="info-tag calmness" id="modalCalmness" style="font-size: 0.85em;" data-tooltip="침착도 등급">😊 -</span>
                    <span class="modal-status" id="modalStatus">상태</span>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
            </div>
            <div class="modal-iframe-container">
                <iframe id="modalIframe" src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <script>
        // 학생별 URL 및 타임스탬프 상태 저장
        const studentUrls = {};
        <?php foreach ($activeStudents as $student): ?>
        studentUrls[<?php echo $student['studentid']; ?>] = {
            url: "<?php echo addslashes($student['url']); ?>",
            activityType: "<?php echo $student['activityType']; ?>",
            lastTimestamp: <?php echo $student['lastTimestamp']; ?>,
            todayGoal: "<?php echo addslashes($student['todayGoal']); ?>",
            goalElapsed: <?php echo $student['goalElapsed']; ?>,
            pomodoroCount: <?php echo $student['pomodoroCount']; ?>,
            calmnessGrade: "<?php echo $student['calmnessGrade']; ?>",
            calmnessScore: <?php echo $student['calmnessScore']; ?>,
            calmnessColor: "<?php echo $student['calmnessColor']; ?>",
            durationList: "<?php echo $student['durationList']; ?>"
        };
        <?php endforeach; ?>

        // 타이머 관리
        let gridRefreshTimer = null;
        let modalRefreshTimer = null;
        let isModalOpen = false;
        let currentModalStudentId = null;

        // 그리드 iframe 새로고침 함수 (60초마다, 타임스탬프 비교)
        async function checkAndRefreshStudents() {
            if (isModalOpen) return; // 모달 열려있으면 중단
            
            try {
                const response = await fetch('realtime_dashboard_api.php?userid=<?php echo $teacherid; ?>&action=check');
                const data = await response.json();
                
                if (data.students) {
                    data.students.forEach(student => {
                        const currentData = studentUrls[student.studentid];
                        // URL 변경 또는 타임스탬프 변경 시 새로고침
                        if (currentData && (
                            currentData.url !== student.url || 
                            currentData.lastTimestamp !== student.lastTimestamp
                        )) {
                            const iframe = document.getElementById('iframe-' + student.studentid);
                            const indicator = document.getElementById('refresh-' + student.studentid);
                            
                            if (iframe && indicator) {
                                indicator.style.display = 'block';
                                iframe.src = student.url;
                                studentUrls[student.studentid].url = student.url;
                                studentUrls[student.studentid].activityType = student.activityType;
                                studentUrls[student.studentid].lastTimestamp = student.lastTimestamp;
                                
                                setTimeout(() => {
                                    indicator.style.display = 'none';
                                }, 2000);
                            }
                        }
                    });
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleString('ko-KR');
                }
            } catch (error) {
                console.error('Error [realtime_dashboard.php:JS]: 학생 상태 확인 실패 -', error);
            }
        }

        // 모달 iframe 새로고침 함수 (10초마다)
        async function refreshModalIframe() {
            if (!isModalOpen || !currentModalStudentId) return;
            
            try {
                const response = await fetch('realtime_dashboard_api.php?userid=<?php echo $teacherid; ?>&action=check');
                const data = await response.json();
                
                if (data.students) {
                    const student = data.students.find(s => s.studentid === currentModalStudentId);
                    if (student) {
                        const currentData = studentUrls[student.studentid];
                        // 타임스탬프 변경 시 모달 iframe 새로고침
                        if (currentData && currentData.lastTimestamp !== student.lastTimestamp) {
                            const modalIframe = document.getElementById('modalIframe');
                            modalIframe.src = student.url;
                            
                            // 상태 업데이트
                            studentUrls[student.studentid].url = student.url;
                            studentUrls[student.studentid].lastTimestamp = student.lastTimestamp;
                            
                            // 모달 상태 텍스트 업데이트
                            document.getElementById('modalStatus').textContent = student.status;
                        }
                    }
                }
            } catch (error) {
                console.error('Error [realtime_dashboard.php:JS]: 모달 새로고침 실패 -', error);
            }
        }

        // 그리드 새로고침 시작 (10초)
        function startGridRefresh() {
            if (gridRefreshTimer) clearInterval(gridRefreshTimer);
            gridRefreshTimer = setInterval(checkAndRefreshStudents, 10000);
        }

        // 그리드 새로고침 중단
        function stopGridRefresh() {
            if (gridRefreshTimer) {
                clearInterval(gridRefreshTimer);
                gridRefreshTimer = null;
            }
        }

        // 모달 새로고침 시작 (10초)
        function startModalRefresh() {
            if (modalRefreshTimer) clearInterval(modalRefreshTimer);
            modalRefreshTimer = setInterval(refreshModalIframe, 10000);
        }

        // 모달 새로고침 중단
        function stopModalRefresh() {
            if (modalRefreshTimer) {
                clearInterval(modalRefreshTimer);
                modalRefreshTimer = null;
            }
        }

        // 활동결과 페이지 모달 열기
        function openActivityModal(name, studentId) {
            const url = 'https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=' + studentId + '&tb=604800';
            const modal = document.getElementById('modalOverlay');
            const modalTitle = document.getElementById('modalTitle');
            const modalStatus = document.getElementById('modalStatus');
            const modalBadge = document.getElementById('modalBadge');
            const modalIframe = document.getElementById('modalIframe');
            
            modalTitle.textContent = name + ' - 활동결과';
            modalStatus.textContent = '최근 7일 활동';
            modalIframe.src = url;
            currentModalStudentId = studentId;
            
            // 배지 설정
            modalBadge.className = 'activity-badge mission';
            modalBadge.textContent = '활동결과';
            
            // 학생 정보 업데이트
            const studentData = studentUrls[studentId];
            if (studentData) {
                const modalGoal = document.getElementById('modalGoal');
                const modalPomodoro = document.getElementById('modalPomodoro');
                const modalCalmness = document.getElementById('modalCalmness');
                
                modalGoal.textContent = '🎯 ' + (studentData.todayGoal.length > 20 ? studentData.todayGoal.substring(0, 20) + '...' : studentData.todayGoal);
                modalGoal.setAttribute('data-tooltip', '오늘목표: ' + studentData.todayGoal);
                modalPomodoro.textContent = '📝 ' + studentData.pomodoroCount + '회';
                modalCalmness.textContent = '😊 ' + studentData.calmnessGrade;
                modalCalmness.style.background = studentData.calmnessColor + '33';
                modalCalmness.style.color = studentData.calmnessColor;
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            isModalOpen = true;
            stopGridRefresh();
            startModalRefresh();
        }

        // 수학일기 타임라인 모달 열기
        function openDiaryModal(name, studentId) {
            const url = 'https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=' + studentId;
            const modal = document.getElementById('modalOverlay');
            const modalTitle = document.getElementById('modalTitle');
            const modalStatus = document.getElementById('modalStatus');
            const modalBadge = document.getElementById('modalBadge');
            const modalIframe = document.getElementById('modalIframe');
            
            modalTitle.textContent = name + ' - 수학일기';
            modalStatus.textContent = '타임 스캐폴딩';
            modalIframe.src = url;
            currentModalStudentId = studentId;
            
            // 배지 설정
            modalBadge.className = 'activity-badge quiz';
            modalBadge.textContent = '수학일기';
            
            // 학생 정보 업데이트
            const studentData = studentUrls[studentId];
            if (studentData) {
                const modalGoal = document.getElementById('modalGoal');
                const modalPomodoro = document.getElementById('modalPomodoro');
                const modalCalmness = document.getElementById('modalCalmness');
                
                modalGoal.textContent = '🎯 ' + (studentData.todayGoal.length > 20 ? studentData.todayGoal.substring(0, 20) + '...' : studentData.todayGoal);
                modalGoal.setAttribute('data-tooltip', '오늘목표: ' + studentData.todayGoal);
                modalPomodoro.textContent = '📝 ' + studentData.pomodoroCount + '회';
                modalCalmness.textContent = '😊 ' + studentData.calmnessGrade;
                modalCalmness.style.background = studentData.calmnessColor + '33';
                modalCalmness.style.color = studentData.calmnessColor;
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            isModalOpen = true;
            stopGridRefresh();
            startModalRefresh();
        }

        // 화이트보드 모달 열기 함수
        function openModal(name, url, status, activityType, studentId) {
            const modal = document.getElementById('modalOverlay');
            const modalTitle = document.getElementById('modalTitle');
            const modalStatus = document.getElementById('modalStatus');
            const modalBadge = document.getElementById('modalBadge');
            const modalIframe = document.getElementById('modalIframe');
            const modalGoal = document.getElementById('modalGoal');
            const modalPomodoro = document.getElementById('modalPomodoro');
            const modalCalmness = document.getElementById('modalCalmness');
            
            modalTitle.textContent = name;
            modalStatus.textContent = status;
            modalIframe.src = url;
            currentModalStudentId = studentId;
            
            // 학생 추가 정보 표시
            const studentData = studentUrls[studentId];
            if (studentData) {
                modalGoal.textContent = '🎯 ' + (studentData.todayGoal.length > 20 ? studentData.todayGoal.substring(0, 20) + '...' : studentData.todayGoal);
                modalGoal.setAttribute('data-tooltip', '오늘목표: ' + studentData.todayGoal);
                modalPomodoro.textContent = '📝 ' + studentData.pomodoroCount + '회';
                modalPomodoro.setAttribute('data-tooltip', '오늘 작성한 수학일기(포모도로) 개수: ' + studentData.pomodoroCount + '회');
                modalCalmness.textContent = '😊 ' + studentData.calmnessGrade;
                modalCalmness.style.background = studentData.calmnessColor + '33';
                modalCalmness.style.color = studentData.calmnessColor;
                modalCalmness.setAttribute('data-tooltip', '침착도 등급: ' + studentData.calmnessGrade + ' (' + studentData.calmnessScore + '점)\n95+:A+ / 90+:A / 85+:B+ / 80+:B / 75+:C+ / 70+:C / 70-:D');
            }
            
            // 배지 설정
            modalBadge.className = 'activity-badge ' + activityType;
            switch(activityType) {
                case 'whiteboard': modalBadge.textContent = '화이트보드'; break;
                case 'quiz': modalBadge.textContent = '퀴즈'; break;
                case 'mission': modalBadge.textContent = '미션'; break;
                default: modalBadge.textContent = '';
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // 그리드 새로고침 중단, 모달 새로고침 시작
            isModalOpen = true;
            stopGridRefresh();
            startModalRefresh();
        }

        // 모달 닫기 함수
        function closeModal() {
            const modal = document.getElementById('modalOverlay');
            const modalIframe = document.getElementById('modalIframe');
            
            modal.classList.remove('active');
            modalIframe.src = 'about:blank';
            document.body.style.overflow = '';
            
            // 모달 새로고침 중단, 그리드 새로고침 재개
            isModalOpen = false;
            currentModalStudentId = null;
            stopModalRefresh();
            startGridRefresh();
        }

        // 모달 바깥 영역 클릭 시 닫기
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // 학생 카드 HTML 생성 함수
        function createStudentCardHtml(student) {
            const activityLabel = {
                'whiteboard': '화이트보드',
                'quiz': '퀴즈',
                'mission': '미션'
            };
            
            const goalShort = student.todayGoal.length > 16 
                ? student.todayGoal.substring(0, 16) + '..' 
                : student.todayGoal;
            
            return `
                <div class="student-card" id="card-${student.studentid}">
                    <div class="card-header">
                        <span class="name" onclick="event.stopPropagation(); openActivityModal('${escapeHtml(student.name)}', ${student.studentid})" data-tooltip="클릭: 활동결과 보기">${escapeHtml(student.name)}</span>
                        <span class="activity-badge ${student.activityType}" onclick="event.stopPropagation(); openModal('${escapeHtml(student.name)}', '${escapeHtml(student.url)}', '${escapeHtml(student.status)}', '${student.activityType}', ${student.studentid})">
                            ${activityLabel[student.activityType] || '활동'}
                        </span>
                        <span class="link-btn diary" onclick="event.stopPropagation(); openDiaryModal('${escapeHtml(student.name)}', ${student.studentid})"><span style="filter: grayscale(1) brightness(10);">📔</span> ${student.durationHtml || '-'}</span>
                        <div class="student-info">
                            <span class="info-tag goal" data-tooltip="오늘목표: ${escapeHtml(student.todayGoal)}">🎯${escapeHtml(goalShort)}</span>
                            <span class="info-tag calmness" style="background: ${student.calmnessColor}33; color: ${student.calmnessColor};">${student.calmnessGrade}</span>
                            ${student.goalElapsed > 0 ? `<span class="info-tag elapsed" data-tooltip="수업 시작 후 경과 시간">⏱${student.goalElapsed}분</span>` : ''}
                        </div>
                        <span class="status" onclick="event.stopPropagation(); openModal('${escapeHtml(student.name)}', '${escapeHtml(student.url)}', '${escapeHtml(student.status)}', '${student.activityType}', ${student.studentid})" data-tooltip="클릭: 현재 활동 확대 보기">${escapeHtml(student.status)}</span>
                    </div>
                    <div class="iframe-container">
                        <iframe 
                            id="iframe-${student.studentid}"
                            src="${escapeHtml(student.url)}"
                            data-url="${escapeHtml(student.url)}"
                            data-studentid="${student.studentid}"
                        ></iframe>
                        <div class="refresh-indicator" id="refresh-${student.studentid}">새로고침 중...</div>
                    </div>
                </div>
            `;
        }
        
        // HTML 이스케이프 함수
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        
        // 그리드 컬럼 수 업데이트
        function updateGridColumns(studentCount) {
            const gridContainer = document.querySelector('.grid-container');
            if (gridContainer) {
                const columns = Math.max(2, Math.ceil(studentCount / 2));
                gridContainer.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
            }
        }
        
        // 학생 수 표시 업데이트
        function updateStudentCount(count) {
            const countEl = document.querySelector('.student-count');
            if (countEl) {
                countEl.textContent = `활성 ${count}명`;
            }
        }
        
        // 5분마다 학생 목록 확인 및 동적 업데이트
        async function checkStudentListChanges() {
            if (isModalOpen) return; // 모달 열려있으면 중단
            
            try {
                const response = await fetch('realtime_dashboard_api.php?userid=<?php echo $teacherid; ?>&action=check');
                const data = await response.json();
                
                if (!data.success || !data.students) {
                    console.error('Error [realtime_dashboard.php:JS]: 학생 목록 조회 실패');
                    return;
                }
                
                const apiStudents = data.students;
                const apiStudentIds = new Set(apiStudents.map(s => s.studentid));
                const currentStudentIds = new Set(Object.keys(studentUrls).map(id => parseInt(id)));
                
                // 새로 추가된 학생 찾기
                const addedStudents = apiStudents.filter(s => !currentStudentIds.has(s.studentid));
                
                // 나간 학생 찾기
                const removedStudentIds = [...currentStudentIds].filter(id => !apiStudentIds.has(id));
                
                // 나간 학생 카드 제거
                removedStudentIds.forEach(studentId => {
                    const card = document.getElementById('card-' + studentId);
                    if (card) {
                        card.style.transition = 'opacity 0.5s, transform 0.5s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            card.remove();
                        }, 500);
                    }
                    delete studentUrls[studentId];
                });
                
                // 새 학생 카드 추가
                const gridContainer = document.querySelector('.grid-container');
                if (gridContainer && addedStudents.length > 0) {
                    addedStudents.forEach(student => {
                        // studentUrls에 등록
                        studentUrls[student.studentid] = {
                            url: student.url,
                            activityType: student.activityType,
                            lastTimestamp: student.lastTimestamp,
                            todayGoal: student.todayGoal,
                            goalElapsed: student.goalElapsed || 0,
                            pomodoroCount: student.pomodoroCount,
                            calmnessGrade: student.calmnessGrade,
                            calmnessScore: student.calmnessScore,
                            calmnessColor: student.calmnessColor,
                            durationHtml: student.durationHtml || ''
                        };
                        
                        // 카드 HTML 생성 및 추가
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = createStudentCardHtml(student);
                        const newCard = tempDiv.firstElementChild;
                        newCard.style.opacity = '0';
                        newCard.style.transform = 'scale(0.8)';
                        gridContainer.appendChild(newCard);
                        
                        // 애니메이션으로 나타나기
                        setTimeout(() => {
                            newCard.style.transition = 'opacity 0.5s, transform 0.5s';
                            newCard.style.opacity = '1';
                            newCard.style.transform = 'scale(1)';
                        }, 50);
                    });
                }
                
                // 학생이 0명이면 빈 메시지 표시
                const noStudentsDiv = document.querySelector('.no-students');
                const currentCount = Object.keys(studentUrls).length;
                
                if (currentCount === 0 && !noStudentsDiv) {
                    if (gridContainer) {
                        gridContainer.innerHTML = '';
                        gridContainer.outerHTML = '<div class="no-students">현재 1시간 이내 활동 중인 학생이 없습니다.</div>';
                    }
                } else if (currentCount > 0 && noStudentsDiv) {
                    // 학생이 다시 생기면 그리드 컨테이너 재생성
                    const columns = Math.max(2, Math.ceil(currentCount / 2));
                    noStudentsDiv.outerHTML = `<div class="grid-container" style="grid-template-columns: repeat(${columns}, 1fr);"></div>`;
                    const newGrid = document.querySelector('.grid-container');
                    apiStudents.forEach(student => {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = createStudentCardHtml(student);
                        newGrid.appendChild(tempDiv.firstElementChild);
                    });
                }
                
                // 그리드 컬럼 및 학생 수 업데이트
                updateGridColumns(currentCount);
                updateStudentCount(currentCount);
                
                // 기존 학생 정보 업데이트 (URL, 상태 등)
                apiStudents.forEach(student => {
                    if (currentStudentIds.has(student.studentid) && !addedStudents.includes(student)) {
                        const currentData = studentUrls[student.studentid];
                        if (currentData) {
                            // URL 또는 타임스탬프 변경 시 iframe 새로고침
                            if (currentData.url !== student.url || currentData.lastTimestamp !== student.lastTimestamp) {
                                const iframe = document.getElementById('iframe-' + student.studentid);
                                const indicator = document.getElementById('refresh-' + student.studentid);
                                
                                if (iframe && indicator) {
                                    indicator.style.display = 'block';
                                    iframe.src = student.url;
                                    setTimeout(() => {
                                        indicator.style.display = 'none';
                                    }, 2000);
                                }
                            }
                            
                            // studentUrls 업데이트
                            studentUrls[student.studentid] = {
                                url: student.url,
                                activityType: student.activityType,
                                lastTimestamp: student.lastTimestamp,
                                todayGoal: student.todayGoal,
                                goalElapsed: student.goalElapsed || 0,
                                pomodoroCount: student.pomodoroCount,
                                calmnessGrade: student.calmnessGrade,
                                calmnessScore: student.calmnessScore,
                                calmnessColor: student.calmnessColor,
                                durationHtml: student.durationHtml || ''
                            };
                            
                            // 카드 UI 요소 업데이트
                            const card = document.getElementById('card-' + student.studentid);
                            if (card) {
                                // 상태 텍스트 업데이트
                                const statusEl = card.querySelector('.status');
                                if (statusEl) statusEl.textContent = student.status;
                                
                                // 활동 배지 업데이트
                                const badge = card.querySelector('.activity-badge');
                                if (badge) {
                                    badge.className = 'activity-badge ' + student.activityType;
                                    const activityLabel = {'whiteboard': '화이트보드', 'quiz': '퀴즈', 'mission': '미션'};
                                    badge.textContent = activityLabel[student.activityType] || '활동';
                                }
                                
                                // 경과 시간 업데이트
                                const elapsedEl = card.querySelector('.info-tag.elapsed');
                                if (student.goalElapsed > 0) {
                                    if (elapsedEl) {
                                        elapsedEl.textContent = '⏱' + student.goalElapsed + '분';
                                    } else {
                                        // 경과 시간 태그가 없으면 새로 추가
                                        const calmnessEl = card.querySelector('.info-tag.calmness');
                                        if (calmnessEl) {
                                            const newElapsed = document.createElement('span');
                                            newElapsed.className = 'info-tag elapsed';
                                            newElapsed.setAttribute('data-tooltip', '수업 시작 후 경과 시간');
                                            newElapsed.textContent = '⏱' + student.goalElapsed + '분';
                                            calmnessEl.parentNode.insertBefore(newElapsed, calmnessEl.nextSibling);
                                        }
                                    }
                                } else if (elapsedEl) {
                                    elapsedEl.remove();
                                }
                            }
                        }
                    }
                });
                
                console.log(`[realtime_dashboard.php] 학생 목록 업데이트: 추가 ${addedStudents.length}명, 제거 ${removedStudentIds.length}명, 현재 ${currentCount}명`);
                document.getElementById('lastUpdate').textContent = new Date().toLocaleString('ko-KR');
                
            } catch (error) {
                console.error('Error [realtime_dashboard.php:JS]: 학생 목록 확인 실패 -', error);
            }
        }
        
        // 5분마다 학생 목록 변경 확인 (전체 새로고침 대신)
        let studentListTimer = setInterval(checkStudentListChanges, 300000);

        // 초기화: 그리드 새로고침 시작
        startGridRefresh();
    </script>
</body>
</html>

