<?php 
/////////////////////////////// realtime_dashboard_api.php ///////////////////////////////
// 파일: teachers/realtime_dashboard_api.php
// 설명: 실시간 대시보드의 학생 상태 확인 API
// URL: https://mathking.kr/moodle/local/augmented_teacher/teachers/realtime_dashboard_api.php?userid=2&action=check

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

// 선생님 ID 받기
$teacherid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($teacherid == 0) {
    echo json_encode(['error' => 'Error [realtime_dashboard_api.php:15]: teacherid가 필요합니다.']);
    exit;
}

if ($action !== 'check') {
    echo json_encode(['error' => 'Error [realtime_dashboard_api.php:20]: 유효하지 않은 action입니다.']);
    exit;
}

// 선생님 및 동료 정보 조회
$collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid'"); 
$teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$teacherid' AND fieldid='79'"); 
$tsymbol = $teacher ? $teacher->symbol : null;
$teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$collegues->mntr1' AND fieldid='79'"); 
$tsymbol1 = $teacher1 ? $teacher1->symbol : null;
$teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$collegues->mntr2' AND fieldid='79'"); 
$tsymbol2 = $teacher2 ? $teacher2->symbol : null;
$teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid='$collegues->mntr3' AND fieldid='79'"); 
$tsymbol3 = $teacher3 ? $teacher3->symbol : null;

$timecreated = time();
$sixhoursago = $timecreated - 21600;
$halfdayago = $timecreated - 43200; // 12시간 = 43200초
$todayStart = strtotime('today'); // 오늘 시작 시간

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
    
    // 가장 최근 활동 유형 판별 및 URL 결정
    $iframeUrl = '';
    $activityType = '';
    $statusText = '';
    $lastTimestamp = 0;
    
    if ($teng3 <= $teng1 && $teng3 <= $teng2) {
        $iframeUrl = "https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid={$studentid}&mode=1";
        $statusText = "화이트보드 활동 ({$lastaccess}분 전)";
        $activityType = 'whiteboard';
        $lastTimestamp = (int)$engagement3->tlaststroke;
    } elseif ($teng2 <= $teng1 && $teng2 <= $teng3) {
        $logDetail = $DB->get_record_sql("SELECT * FROM mdl_logstore_standard_log WHERE id='{$engagement2->id}'");
        if ($logDetail && $logDetail->component == 'mod_quiz') {
            $statusText = "현재 상태 : {$logDetail->action} ({$lastaccess}분 전)";
            $iframeUrl = "https://mathking.kr/moodle/mod/quiz/attempt.php?attempt={$logDetail->objectid}";
        } else {
            $statusText = "시스템 활동 ({$lastaccess}분 전)";
            $iframeUrl = "https://mathking.kr/moodle/user/profile.php?id={$studentid}";
        }
        $activityType = 'quiz';
        $lastTimestamp = (int)$engagement2->timecreated;
    } else {
        $iframeUrl = $engagement1->url;
        $statusText = "미션 활동 ({$lastaccess}분 전)";
        $activityType = 'mission';
        $lastTimestamp = (int)$engagement1->timecreated;
    }
    
    if (!empty($iframeUrl)) {
        // 학생 추가 정보 조회
        // 1. 오늘목표 조회
        $checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1");
        
        $todayGoalText = $checkgoal ? $checkgoal->text : '목표 미설정';
        $goalTimecreated = $checkgoal ? (int)$checkgoal->timecreated : 0;
        $goalElapsed = $goalTimecreated > 0 ? (int)((time() - $goalTimecreated) / 60) : 0; // 분 단위 경과 시간
        $calmnessScore = $checkgoal ? (int)$checkgoal->score : 0;
        
        // 2. 침착도 등급 계산
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
        
        // 3. 오늘 포모도로(트래킹) 조회
        $trackingRecords = $DB->get_records_sql("SELECT id, timecreated, timefinished, status FROM mdl_abessi_tracking WHERE userid = ? AND timecreated >= ? AND hide = 0 AND (status = 'complete' OR status = 'begin') ORDER BY id ASC", [$studentid, $todayStart]);
        $pomodoroCount = count($trackingRecords);
        
        // 4. Final 값들 (timefinished - timecreated)을 분 단위로 개별 표시
        $durationHtml = '';
        if ($pomodoroCount > 0) {
            $durationItems = [];
            foreach ($trackingRecords as $record) {
                $tresult = $record->timefinished - $record->timecreated;
                if ($tresult < 0) $tresult = 0;
                $durationMin = round($tresult / 60);
                $color = $durationMin > 30 ? '#3b82f6' : '#10b981';
                $durationItems[] = '<span style="background:'.$color.';color:white;padding:1px 4px;border-radius:3px;margin:0 1px;">'.$durationMin.'</span>';
            }
            $durationHtml = implode('', $durationItems);
        }
        
        $activeStudents[] = [
            'studentid' => $studentid,
            'name' => $value['firstname'] . $value['lastname'],
            'url' => $iframeUrl,
            'status' => "{$lastaccess}분 전",
            'lastaccess' => $lastaccess,
            'activityType' => $activityType,
            'lastTimestamp' => $lastTimestamp,
            'todayGoal' => $todayGoalText,
            'goalElapsed' => $goalElapsed,
            'pomodoroCount' => $pomodoroCount,
            'calmnessGrade' => $calmnessGrade,
            'calmnessColor' => $calmnessColor,
            'calmnessScore' => $calmnessScore,
            'durationHtml' => $durationHtml
        ];
    }
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'students' => $activeStudents
]);

