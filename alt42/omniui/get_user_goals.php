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
    // Get today's goal
    $todayGoal = $DB->get_record_sql(
        "SELECT * FROM {abessi_today} 
         WHERE userid = ? AND type LIKE '오늘목표' 
         ORDER BY id DESC LIMIT 1", 
        array($userid)
    );
    
    // Get weekly goal
    $weeklyGoal = $DB->get_record_sql(
        "SELECT * FROM {abessi_today} 
         WHERE userid = ? AND type LIKE '주간목표' 
         ORDER BY id DESC LIMIT 1", 
        array($userid)
    );
    
    // Get quarter goal (분기목표)
    $quarterGoal = $DB->get_record_sql(
        "SELECT * FROM {abessi_progress} 
         WHERE userid = ? AND (plantype = '분기목표' OR plantype = '방향설정') AND hide = 0 
         ORDER BY id DESC LIMIT 1", 
        array($userid)
    );
    
    // Prepare response
    $response = array(
        'success' => true,
        'goals' => array(
            'today' => $todayGoal ? $todayGoal->text : '',
            'weekly' => $weeklyGoal ? $weeklyGoal->text : '',
            'quarter' => $quarterGoal ? ($quarterGoal->title ?? $quarterGoal->memo ?? '') : '',
            'quarterDetails' => $quarterGoal ? array(
                'content' => $quarterGoal->content ?? $quarterGoal->memo ?? '',
                'deadline' => $quarterGoal->plandate ?? '',
                'dream' => $quarterGoal->dream ?? $quarterGoal->dreamchallenge ?? ''
            ) : null
        ),
        'debug' => array(
            'quarterGoal' => $quarterGoal ? true : false,
            'quarterData' => $quarterGoal ?? null
        )
    );
    
} catch (Exception $e) {
    $response = array(
        'success' => false,
        'error' => $e->getMessage()
    );
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>