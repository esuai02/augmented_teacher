<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

try {
    require_login();
    
    // Get student ID from GET parameter
    $studentid = isset($_GET["userid"]) ? $_GET["userid"] : null;
    
    // Get user role
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=?", 
                                   array($USER->id, 22));
    
    $role = $userrole ? $userrole->data : 'Unknown';
    
    // Prepare response data
    $response = array(
        'success' => true,
        'data' => array(
            'user_id' => $USER->id,
            'student_id' => $studentid,
            'user_role' => $role,
            'username' => $USER->username,
            'email' => $USER->email
        )
    );
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Handle errors
    $response = array(
        'success' => false,
        'error' => $e->getMessage()
    );
    
    echo json_encode($response);
}
?>