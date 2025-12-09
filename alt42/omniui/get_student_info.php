<?php
header('Content-Type: application/json; charset=utf-8');

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get userid from parameter
$userid = optional_param('userid', 0, PARAM_INT);
if ($userid == 0) {
    $userid = $USER->id;
}

try {
    $response = array('success' => true);
    $timecreated = time();
    $hoursago = $timecreated - 14400;
    $halfdayago = $timecreated - 43200;
    $aweekago = $timecreated - 604800;
    
    // 1. 사용자 기본 정보
    $thisuser = $DB->get_record_sql("SELECT * FROM {user} WHERE id = ?", array($userid));
    $response['user_info'] = array(
        'name' => $thisuser->lastname . $thisuser->firstname,
        'username' => $thisuser->username,
        'email' => $thisuser->email
    );
    
    // 2. 사용자 역할
    $userrole = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ? AND fieldid = '22'", array($userid));
    $response['role'] = $userrole ? $userrole->role : 'student';
    
    // 3. 녹음 동의 여부
    $recordingConsent = $DB->get_record_sql(
        "SELECT * FROM {abessi_mathtalk} WHERE userid = ? AND type = 'agreement' ORDER BY timecreated DESC LIMIT 1",
        array($userid)
    );
    $response['recording_consent'] = ($recordingConsent && $recordingConsent->hide == 0);
    
    // 4. 주간 목표
    $wgoal = $DB->get_record_sql(
        "SELECT * FROM {abessi_today} WHERE userid = ? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1",
        array($userid)
    );
    $response['weekly_goal'] = $wgoal ? $wgoal->text : '';
    
    // 5. 오늘 목표
    $checkgoal = $DB->get_record_sql(
        "SELECT * FROM {abessi_today} WHERE userid = ? AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1",
        array($userid)
    );
    $response['today_goal'] = $checkgoal ? array(
        'text' => $checkgoal->text,
        'inspect' => $checkgoal->inspect ?? 0,
        'time' => $checkgoal->timecreated ? gmdate("h:i A", $checkgoal->timecreated + 32400) : ''
    ) : null;
    
    // 6. 분기 목표
    $termplan = $DB->get_record_sql(
        "SELECT * FROM {abessi_progress} WHERE userid = ? AND plantype = '분기목표' AND hide = 0 AND deadline > ? ORDER BY id DESC LIMIT 1",
        array($userid, $timecreated)
    );
    $response['term_goal'] = $termplan ? array(
        'id' => $termplan->id,
        'title' => $termplan->title,
        'content' => $termplan->content,
        'deadline' => $termplan->deadline
    ) : null;
    
    // 7. 최근 학습 기록
    $chapterlog = $DB->get_record_sql(
        "SELECT * FROM {abessi_chapterlog} WHERE userid = ? ORDER BY id DESC LIMIT 1",
        array($userid)
    );
    $response['last_chapter'] = $chapterlog ? array(
        'chapter' => $chapterlog->chapter ?? '',
        'progress' => $chapterlog->progress ?? 0,
        'time' => date('Y-m-d H:i', $chapterlog->timecreated ?? 0)
    ) : null;
    
    // 8. 휴식 시간 체크
    $lastbreak = $DB->get_record_sql(
        "SELECT id, timecreated FROM {abessi_missionlog} WHERE userid = ? AND timecreated > ? AND eventid = '7128' ORDER BY id DESC LIMIT 1",
        array($userid, $halfdayago)
    );
    $beforebreak = 60;
    if ($lastbreak && isset($lastbreak->timecreated)) {
        $beforebreak = 60 - ($timecreated - $lastbreak->timecreated) / 60;
        if ($lastbreak->id != NULL) $beforebreak = -1;
    }
    $response['break_info'] = array(
        'minutes_until_break' => round($beforebreak, 0),
        'last_break_time' => $lastbreak ? date('H:i', $lastbreak->timecreated) : null
    );
    
    // 9. 최근 활동 통계 (오늘)
    $todayActivities = $DB->get_records_sql(
        "SELECT * FROM {abessi_tracking} WHERE userid = ? AND timecreated > ? AND status = 'complete' ORDER BY id DESC",
        array($userid, $timecreated - 86400) // 24시간 이내
    );
    
    $totalDuration = 0;
    $completedCount = 0;
    $satisfactionSum = 0;
    
    foreach ($todayActivities as $activity) {
        if ($activity->duration > $activity->timecreated) {
            $totalDuration += $activity->duration - $activity->timecreated;
        }
        $completedCount++;
        if ($activity->result > 0) {
            $satisfactionSum += $activity->result;
        }
    }
    
    $response['today_stats'] = array(
        'total_duration_minutes' => round($totalDuration / 60, 0),
        'completed_activities' => $completedCount,
        'average_satisfaction' => $completedCount > 0 ? round($satisfactionSum / $completedCount, 1) : 0
    );
    
    // 10. 대기 중인 활동
    $waitingActivities = $DB->get_records_sql(
        "SELECT * FROM {abessi_tracking} WHERE userid = ? AND status = 'waiting' ORDER BY id DESC LIMIT 5",
        array($userid)
    );
    
    $waitingList = array();
    foreach ($waitingActivities as $activity) {
        $waitingList[] = array(
            'id' => $activity->id,
            'text' => $activity->text,
            'type' => $activity->type
        );
    }
    $response['waiting_activities'] = $waitingList;
    
    // 11. 학습 맥락 정보
    $context = $DB->get_record_sql(
        "SELECT * FROM {abessi_tracking} WHERE userid = ? AND type LIKE 'context' ORDER BY id DESC LIMIT 1",
        array($userid)
    );
    $response['learning_context'] = $context ? $context->text : '';
    
} catch (Exception $e) {
    $response = array(
        'success' => false,
        'error' => $e->getMessage()
    );
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>