<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h2>테스트 메시지 생성</h2>";

$studentid = $_GET['studentid'] ?? $USER->id;

// ktm_teaching_interactions에서 완료된 항목 확인
if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
    $interactions = $DB->get_records('ktm_teaching_interactions', array(
        'userid' => $studentid,
        'status' => 'completed'
    ), 'timecreated DESC');
    
    if ($interactions) {
        echo "<p>ktm_teaching_interactions에서 " . count($interactions) . "개의 완료된 항목을 찾았습니다.</p>";
        
        // ktm_mathmessages 테이블이 없으면 생성
        if (!$DB->get_manager()->table_exists('ktm_mathmessages')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_mathmessages (
                id BIGINT(10) NOT NULL AUTO_INCREMENT,
                teacher_id BIGINT(10) NOT NULL,
                student_id BIGINT(10) NOT NULL,
                interaction_id BIGINT(10) DEFAULT NULL,
                subject VARCHAR(255) NOT NULL DEFAULT '하이튜터링 문제 해설',
                message_content LONGTEXT NOT NULL,
                solution_text LONGTEXT DEFAULT NULL,
                audio_url VARCHAR(500) DEFAULT NULL,
                explanation_url VARCHAR(500) DEFAULT NULL,
                is_read TINYINT(1) DEFAULT 0,
                timecreated BIGINT(10) NOT NULL,
                timeread BIGINT(10) DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX idx_student_id (student_id),
                INDEX idx_teacher_id (teacher_id),
                INDEX idx_interaction_id (interaction_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $DB->execute($sql);
            echo "<p style='color: green;'>ktm_mathmessages 테이블을 생성했습니다.</p>";
        }
        
        // 각 interaction에 대해 메시지 생성
        $created = 0;
        foreach ($interactions as $interaction) {
            // 이미 메시지가 있는지 확인
            $existing = $DB->get_record('ktm_mathmessages', array(
                'interaction_id' => $interaction->id
            ));
            
            if (!$existing) {
                $message = new stdClass();
                $message->teacher_id = $interaction->teacherid ?: $USER->id;
                $message->student_id = $interaction->userid;
                $message->interaction_id = $interaction->id;
                $message->subject = '하이튜터링 문제 해설';
                $message->message_content = '문제 해설이 준비되었습니다. 클릭하여 확인하세요.';
                $message->solution_text = $interaction->solution_text;
                $message->audio_url = $interaction->audio_url;
                $message->explanation_url = null;
                $message->is_read = 0;
                $message->timecreated = $interaction->timecreated;
                $message->timeread = null;
                
                $DB->insert_record('ktm_mathmessages', $message);
                $created++;
            }
        }
        
        echo "<p style='color: green;'>$created 개의 메시지를 생성했습니다.</p>";
    } else {
        echo "<p>완료된 상호작용이 없습니다.</p>";
    }
}

// 결과 확인
$count = $DB->count_records('ktm_mathmessages', array('student_id' => $studentid));
echo "<p>현재 학생($studentid)의 메시지 수: $count</p>";

echo "<p><a href='student_inbox.php?studentid=$studentid'>학생 메시지함으로 이동</a></p>";
?>