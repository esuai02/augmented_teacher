<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get POST data
$eventid = isset($_POST['eventid']) ? intval($_POST['eventid']) : 2;
$userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;
$type = isset($_POST['type']) ? $_POST['type'] : '';
$inputtext = isset($_POST['inputtext']) ? $_POST['inputtext'] : '';
$deadline = isset($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d');

// Validate user permission
if ($USER->id != $userid) {
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
    if ($userrole && $userrole->data === 'student') {
        echo json_encode(['status' => 'error', 'message' => '권한이 없습니다.']);
        exit;
    }
}

$timecreated = time();

try {
    // Process based on goal type
    if ($type === '오늘목표' || $type === '주간목표' || $type === '시험목표') {
        // Insert into mdl_abessi_today table
        $record = new stdClass();
        $record->userid = $userid;
        $record->type = $type;
        $record->text = $inputtext;
        $record->timecreated = $timecreated;
        
        $result = $DB->insert_record('abessi_today', $record);
        
        if ($result) {
            // Log the activity
            $DB->execute("INSERT INTO {abessi_missionlog} (userid, page, timecreated) VALUES('$userid', 'goal_$type', '$timecreated')");
            
            echo json_encode([
                'status' => 'success',
                'message' => '목표가 성공적으로 저장되었습니다.',
                'data' => [
                    'id' => $result,
                    'type' => $type,
                    'timestamp' => $timecreated
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => '저장에 실패했습니다.']);
        }
    } else if ($type === '분기목표' || $type === '시험목표') {
        // Insert into mdl_abessi_progress table for quarterly goals
        $record = new stdClass();
        $record->userid = $userid;
        $record->plantype = $type;
        $record->memo = $inputtext;
        $record->deadline = strtotime($deadline);
        $record->hide = 0;
        $record->timecreated = $timecreated;
        
        $result = $DB->insert_record('abessi_progress', $record);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => '분기목표가 성공적으로 저장되었습니다.',
                'data' => [
                    'id' => $result,
                    'type' => $type,
                    'deadline' => $deadline
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => '저장에 실패했습니다.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '잘못된 목표 유형입니다.']);
    }
} catch (Exception $e) {
    error_log('Goal save error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>