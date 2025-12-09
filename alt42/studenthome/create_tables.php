<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

// 관리자 권한 확인
require_login();
require_capability('moodle/site:config', context_system::instance());

echo "<h2>AI 페르소나 매칭 시스템 데이터베이스 테이블 생성</h2>";
echo "<pre>";

try {
    // 1. mdl_persona_modes 테이블 생성
    echo "1. mdl_persona_modes 테이블 생성 중...\n";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS mdl_persona_modes (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        teacher_id BIGINT(10) NOT NULL COMMENT '선생님 사용자 ID',
        student_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID', 
        teacher_mode VARCHAR(50) NOT NULL COMMENT '선생님 교육 모드',
        student_mode VARCHAR(50) NOT NULL COMMENT '학생 학습 모드',
        created_at BIGINT(10) NOT NULL COMMENT '생성 시간 (timestamp)',
        updated_at BIGINT(10) NOT NULL COMMENT '수정 시간 (timestamp)',
        UNIQUE KEY unique_teacher_student (teacher_id, student_id),
        INDEX idx_teacher_id (teacher_id),
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 페르소나 매칭 모드 저장'";
    
    $DB->execute($sql1);
    echo "✓ mdl_persona_modes 테이블 생성 완료\n\n";
    
    // 2. mdl_message_transformations 테이블 생성
    echo "2. mdl_message_transformations 테이블 생성 중...\n";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS mdl_message_transformations (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        teacher_id BIGINT(10) NOT NULL COMMENT '선생님 사용자 ID',
        student_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        original_message TEXT NOT NULL COMMENT '원본 메시지',
        transformed_message TEXT NOT NULL COMMENT '변환된 메시지',
        teacher_mode VARCHAR(50) NOT NULL COMMENT '선생님 모드',
        student_mode VARCHAR(50) NOT NULL COMMENT '학생 모드',
        transformation_time BIGINT(10) NOT NULL COMMENT '변환 시간 (timestamp)',
        INDEX idx_teacher_student (teacher_id, student_id),
        INDEX idx_transformation_time (transformation_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='메시지 변환 이력'";
    
    $DB->execute($sql2);
    echo "✓ mdl_message_transformations 테이블 생성 완료\n\n";
    
    // 3. mdl_chat_messages 테이블 생성
    echo "3. mdl_chat_messages 테이블 생성 중...\n";
    
    $sql3 = "CREATE TABLE IF NOT EXISTS mdl_chat_messages (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(100) NOT NULL COMMENT '채팅방 ID (teacher_id_student_id)',
        sender_id BIGINT(10) NOT NULL COMMENT '발신자 ID',
        receiver_id BIGINT(10) NOT NULL COMMENT '수신자 ID',
        message_type ENUM('original', 'transformed') DEFAULT 'original' COMMENT '메시지 타입',
        message_content TEXT NOT NULL COMMENT '메시지 내용',
        sent_at BIGINT(10) NOT NULL COMMENT '전송 시간 (timestamp)',
        read_at BIGINT(10) DEFAULT NULL COMMENT '읽은 시간 (timestamp)',
        INDEX idx_room_id (room_id),
        INDEX idx_sent_at (sent_at),
        INDEX idx_sender_receiver (sender_id, receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실시간 채팅 메시지'";
    
    $DB->execute($sql3);
    echo "✓ mdl_chat_messages 테이블 생성 완료\n\n";
    
    echo "<strong style='color: green;'>모든 테이블이 성공적으로 생성되었습니다!</strong>\n\n";
    
    // 테이블 확인
    echo "생성된 테이블 확인:\n";
    
    // mdl_persona_modes 테이블 구조 확인
    $table_check1 = $DB->get_record_sql("SHOW CREATE TABLE mdl_persona_modes");
    if ($table_check1) {
        echo "✓ mdl_persona_modes 테이블 존재함\n";
    }
    
    // mdl_message_transformations 테이블 구조 확인
    $table_check2 = $DB->get_record_sql("SHOW CREATE TABLE mdl_message_transformations");
    if ($table_check2) {
        echo "✓ mdl_message_transformations 테이블 존재함\n";
    }
    
    // mdl_chat_messages 테이블 구조 확인
    $table_check3 = $DB->get_record_sql("SHOW CREATE TABLE mdl_chat_messages");
    if ($table_check3) {
        echo "✓ mdl_chat_messages 테이블 존재함\n";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>오류 발생: " . $e->getMessage() . "</strong>\n";
    echo "상세 오류:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

// 테이블 삭제가 필요한 경우를 위한 DROP 명령어 제공
echo "<hr>";
echo "<h3>테이블 삭제가 필요한 경우 (주의: 모든 데이터가 삭제됩니다)</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo "DROP TABLE IF EXISTS mdl_chat_messages;\n";
echo "DROP TABLE IF EXISTS mdl_message_transformations;\n";
echo "DROP TABLE IF EXISTS mdl_persona_modes;\n";
echo "</pre>";
?>