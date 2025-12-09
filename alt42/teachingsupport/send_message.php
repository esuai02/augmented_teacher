<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$studentId = $input['studentId'] ?? 0;
$teacherId = $input['teacherId'] ?? $USER->id;
$interactionId = $input['interactionId'] ?? 0;
$message = $input['message'] ?? '';
$solutionText = $input['solutionText'] ?? '';
$audioUrl = $input['audioUrl'] ?? '';

try {
    $time = time();
    
    // 디버깅 로그 추가
    error_log("send_message.php - Input data: " . json_encode($input));
    error_log("send_message.php - StudentId: $studentId, TeacherId: $teacherId, InteractionId: $interactionId");
    
    // 학생 정보 가져오기
    $student = $DB->get_record('user', array('id' => $studentId));
    if (!$student) {
        error_log("send_message.php - Student not found: $studentId");
        throw new Exception('학생 정보를 찾을 수 없습니다.');
    }
    
    error_log("send_message.php - Student found: " . fullname($student));
    
    // 선생님 정보 가져오기
    $teacher = $DB->get_record('user', array('id' => $teacherId));
    if (!$teacher) {
        throw new Exception('선생님 정보를 찾을 수 없습니다.');
    }
    
    // ktm_mathmessages 테이블 사용 중단 - ktm_teaching_interactions 직접 사용
    // 메시지 전송은 성공으로 처리 (이미 ktm_teaching_interactions에 데이터가 있음)
    echo json_encode([
        'success' => true,
        'message' => '해설이 완료되었습니다. 학생이 메시지함에서 확인할 수 있습니다.',
        'student_name' => fullname($student),
        'teacher_name' => fullname($teacher),
        'interaction_id' => $interactionId
    ]);
    return;
    
    // 아래 코드는 실행되지 않음 (향후 제거 예정)
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('ktm_mathmessages')) {
        error_log("send_message.php - ktm_mathmessages table does not exist, creating it");
        
        // 테이블 생성
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
        
        try {
            $DB->execute($sql);
            error_log("send_message.php - ktm_mathmessages table created successfully");
        } catch (Exception $e) {
            error_log("send_message.php - Failed to create ktm_mathmessages table: " . $e->getMessage());
            throw new Exception('메시지 테이블 생성 실패: ' . $e->getMessage());
        }
    }
    
    // 메시지에 학생 메시지함 링크 추가
    $inbox_url = $CFG->wwwroot . '/local/augmented_teacher/alt42/teachingsupport/student_inbox.php?studentid=' . $studentId;
    
    $enhanced_message = $message . "\n\n📬 나의 풀이 메시지함에서 확인하기: " . $inbox_url;
    
    // 자체 메시지 테이블에 저장
    $messagedata = new stdClass();
    $messagedata->teacher_id = $teacherId;
    $messagedata->student_id = $studentId;
    $messagedata->interaction_id = $interactionId;
    $messagedata->subject = '📚 하이튜터링 문제 해설 완료';
    $messagedata->message_content = $enhanced_message;
    $messagedata->solution_text = $solutionText;
    $messagedata->audio_url = $audioUrl;
    $messagedata->explanation_url = '';
    $messagedata->is_read = 0;
    $messagedata->timecreated = $time;
    $messagedata->timeread = null;
    
    // 필드 길이 검증 (UTF-8 멀티바이트 고려)
    if (mb_strlen($messagedata->message_content, 'UTF-8') > 65535) {
        $messagedata->message_content = mb_substr($messagedata->message_content, 0, 65535, 'UTF-8');
        error_log("send_message.php - Message content truncated due to length");
    }
    if (mb_strlen($messagedata->solution_text, 'UTF-8') > 65535) {
        $messagedata->solution_text = mb_substr($messagedata->solution_text, 0, 65535, 'UTF-8');
        error_log("send_message.php - Solution text truncated due to length");
    }
    if (mb_strlen($messagedata->audio_url, 'UTF-8') > 500) {
        error_log("send_message.php - Audio URL too long: " . $messagedata->audio_url);
        $messagedata->audio_url = '';
    }
    
    // NULL 값 처리
    if (empty($messagedata->solution_text)) {
        $messagedata->solution_text = '';
    }
    if (empty($messagedata->audio_url)) {
        $messagedata->audio_url = '';
    }
    if (empty($messagedata->explanation_url)) {
        $messagedata->explanation_url = '';
    }
    if (!isset($messagedata->interaction_id) || empty($messagedata->interaction_id)) {
        $messagedata->interaction_id = null;
    }
    
    error_log("send_message.php - Inserting message to ktm_mathmessages table: " . json_encode($messagedata));
    
    try {
        $message_id = $DB->insert_record('ktm_mathmessages', $messagedata);
        error_log("send_message.php - Message insert result: $message_id");
    } catch (dml_exception $e) {
        error_log("send_message.php - Database error: " . $e->getMessage());
        error_log("send_message.php - Error details: " . $e->debuginfo);
        error_log("send_message.php - Data being inserted: " . json_encode($messagedata));
        
        // 더 자세한 오류 메시지 제공
        $errorMsg = '데이터베이스 쓰기 오류';
        if (strpos($e->getMessage(), 'Incorrect integer value') !== false) {
            $errorMsg = '잘못된 ID 값';
        } else if (strpos($e->getMessage(), 'Data too long') !== false) {
            $errorMsg = '데이터가 너무 깁니다';
        } else if (strpos($e->getMessage(), 'cannot be null') !== false) {
            $errorMsg = '필수 필드가 비어있습니다';
        }
        
        throw new Exception($errorMsg . ': ' . $e->getMessage());
    }
    
    if ($message_id) {
        // 이벤트 로그 추가 (기존 ktm_teaching_events 테이블 사용)
        if ($DB->get_manager()->table_exists('ktm_teaching_events')) {
            $event = new stdClass();
            $event->userid = $studentId;
            $event->interactionid = $interactionId;
            $event->event_type = 'message_sent';
            $event->event_description = '선생님이 문제 해설을 전송했습니다.';
            $event->metadata = json_encode([
                'teacher_id' => $teacherId,
                'message_id' => $message_id,
                'audio_url' => $audioUrl,
                'inbox_url' => $inbox_url
            ]);
            $event->timecreated = $time;
            
            $DB->insert_record('ktm_teaching_events', $event);
        }
        
        echo json_encode([
            'success' => true,
            'message_id' => $message_id,
            'student_name' => fullname($student),
            'teacher_name' => fullname($teacher),
            'inbox_url' => $inbox_url
        ]);
    } else {
        throw new Exception('메시지 저장에 실패했습니다.');
    }
    
} catch (Exception $e) {
    error_log("send_message.php - Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>