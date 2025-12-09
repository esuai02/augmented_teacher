<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$promptText = $_POST['promptText'];
$timecreated = time();

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole->data;

if($role === 'student') {
    echo json_encode([
        'success' => false,
        'error' => '사용 권한이 없습니다.'
    ]);
    exit();
}

if(empty($promptText)) {
    echo json_encode([
        'success' => false,
        'error' => '프롬프트 내용을 입력하세요.'
    ]);
    exit();
}

try {
    // 기존 프롬프트 확인
    $existing = $DB->get_record_sql("SELECT * FROM mdl_gptprompts 
        WHERE userid='$USER->id' AND type='pmemory' 
        ORDER BY id DESC LIMIT 1");
    
    if($existing) {
        // 업데이트
        $DB->execute("UPDATE mdl_gptprompts 
            SET prompttext=?, timemodified=? 
            WHERE id=?", 
            [$promptText, $timecreated, $existing->id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'message' => '프롬프트가 업데이트되었습니다.'
        ]);
    } else {
        // 신규 삽입
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->type = 'pmemory';
        $record->prompttext = $promptText;
        $record->timecreated = $timecreated;
        $record->timemodified = $timecreated;
        
        $DB->insert_record('gptprompts', $record);
        
        echo json_encode([
            'success' => true,
            'action' => 'created',
            'message' => '프롬프트가 저장되었습니다.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'DB 오류: ' . $e->getMessage()
    ]);
}
?>


