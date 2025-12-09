<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get parameters
$studentid = isset($_GET['id']) ? intval($_GET['id']) : $USER->id;
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Validate user permission
if ($USER->id != $studentid) {
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
    if ($userrole && $userrole->data === 'student') {
        echo json_encode(['status' => 'error', 'message' => '권한이 없습니다.']);
        exit;
    }
}

$timecreated = time();
$aweekago = $timecreated - 604800;
$adayago = $timecreated - 86400;

$response = [
    'status' => 'success',
    'data' => [
        'daily' => null,
        'weekly' => null,
        'quarterly' => null
    ]
];

try {
    // Get today's goal
    if ($type === 'all' || $type === 'daily') {
        $todayGoal = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_today 
             WHERE userid='$studentid' 
             AND type LIKE '오늘목표' 
             AND timecreated > '$adayago'
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($todayGoal) {
            $response['data']['daily'] = [
                'id' => $todayGoal->id,
                'text' => $todayGoal->text,
                'timecreated' => $todayGoal->timecreated,
                'completed' => false
            ];
        }
    }
    
    // Get weekly goal
    if ($type === 'all' || $type === 'weekly') {
        $weeklyGoal = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_today 
             WHERE userid='$studentid' 
             AND type LIKE '주간목표' 
             AND timecreated > '$aweekago'
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($weeklyGoal) {
            $response['data']['weekly'] = [
                'id' => $weeklyGoal->id,
                'text' => $weeklyGoal->text,
                'timecreated' => $weeklyGoal->timecreated,
                'completed' => false
            ];
        }
    }
    
    // Get quarterly goal (분기목표)
    if ($type === 'all' || $type === 'quarterly') {
        $quarterlyGoal = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_progress 
             WHERE userid='$studentid' 
             AND (plantype LIKE '분기목표' OR plantype LIKE '시험목표')
             AND hide=0
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($quarterlyGoal) {
            $response['data']['quarterly'] = [
                'id' => $quarterlyGoal->id,
                'text' => $quarterlyGoal->memo,
                'deadline' => date('Y-m-d', $quarterlyGoal->deadline),
                'timecreated' => $quarterlyGoal->timecreated,
                'completed' => false
            ];
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '데이터 조회 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>