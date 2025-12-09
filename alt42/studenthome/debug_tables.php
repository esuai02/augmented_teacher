<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 로그인 확인
require_login();

echo "<h2>데이터베이스 테이블 구조 디버깅</h2>";

// 1. 테이블 존재 여부 및 구조 확인
$tables = ['mdl_persona_modes', 'mdl_chat_messages', 'mdl_message_transformations'];

foreach ($tables as $table) {
    echo "<h3>테이블: {$table}</h3>";
    
    try {
        // 테이블 구조 확인
        $columns = $DB->get_columns($table);
        
        if (empty($columns)) {
            echo "<p style='color: red;'>❌ 테이블이 존재하지 않거나 필드가 없습니다.</p>";
            continue;
        }
        
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>필드명</th><th>타입</th><th>Null 허용</th><th>기본값</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td style='padding: 5px;'>{$column->name}</td>";
            echo "<td style='padding: 5px;'>{$column->type}</td>";
            echo "<td style='padding: 5px;'>" . ($column->not_null ? 'NO' : 'YES') . "</td>";
            echo "<td style='padding: 5px;'>" . ($column->default_value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 레코드 수 확인
        $count = $DB->count_records($table);
        echo "<p>현재 레코드 수: <strong>{$count}</strong></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 테이블 '{$table}' 오류: " . $e->getMessage() . "</p>";
    }
}

// 2. 실제 테이블명 확인 (mdl_ 접두사 포함)
echo "<h3>실제 데이터베이스 테이블 확인</h3>";
try {
    $sql = "SHOW TABLES LIKE 'mdl_%modes' OR SHOW TABLES LIKE 'mdl_%messages' OR SHOW TABLES LIKE 'mdl_%transformations'";
    $tables_result = $DB->get_records_sql("SHOW TABLES LIKE 'mdl_%'");
    
    echo "<p>데이터베이스의 모든 mdl_ 테이블:</p>";
    echo "<ul>";
    foreach ($tables_result as $table) {
        $table_name = array_values((array)$table)[0];
        if (strpos($table_name, 'persona') !== false || 
            strpos($table_name, 'chat') !== false || 
            strpos($table_name, 'message') !== false) {
            echo "<li style='color: green;'><strong>{$table_name}</strong></li>";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>테이블 목록 조회 오류: " . $e->getMessage() . "</p>";
}

// 3. 테이블 생성 시도
echo "<h3>테이블 생성 시도</h3>";
try {
    // chat_messages 테이블 생성
    $sql_chat = "CREATE TABLE IF NOT EXISTS {chat_messages} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(100) NOT NULL,
        sender_id BIGINT(10) NOT NULL,
        receiver_id BIGINT(10) NOT NULL,
        message_type ENUM('original', 'transformed') DEFAULT 'original',
        message_content TEXT NOT NULL,
        sent_at BIGINT(10) NOT NULL,
        read_at BIGINT(10) DEFAULT NULL,
        INDEX idx_room_id (room_id),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql_chat);
    echo "<p style='color: green;'>✅ chat_messages 테이블 생성/확인 완료</p>";
    
    // message_transformations 테이블 생성
    $sql_trans = "CREATE TABLE IF NOT EXISTS {message_transformations} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        teacher_id BIGINT(10) NOT NULL,
        student_id BIGINT(10) NOT NULL,
        original_message TEXT NOT NULL,
        transformed_message TEXT NOT NULL,
        teacher_mode VARCHAR(50) NOT NULL,
        student_mode VARCHAR(50) NOT NULL,
        transformation_time BIGINT(10) NOT NULL,
        INDEX idx_teacher_student (teacher_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql_trans);
    echo "<p style='color: green;'>✅ message_transformations 테이블 생성/확인 완료</p>";
    
    // persona_modes 테이블 생성
    $sql_persona = "CREATE TABLE IF NOT EXISTS {persona_modes} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        teacher_id BIGINT(10) NOT NULL,
        student_id BIGINT(10) NOT NULL,
        teacher_mode VARCHAR(50) NOT NULL,
        student_mode VARCHAR(50) NOT NULL,
        created_at BIGINT(10) NOT NULL,
        updated_at BIGINT(10) NOT NULL,
        UNIQUE KEY unique_teacher_student (teacher_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql_persona);
    echo "<p style='color: green;'>✅ persona_modes 테이블 생성/확인 완료</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 테이블 생성 오류: " . $e->getMessage() . "</p>";
}

// 4. 간단한 삽입 테스트
echo "<h3>데이터 삽입 테스트</h3>";
try {
    // 테스트 데이터 준비
    $test_data = new stdClass();
    $test_data->room_id = 'test_room';
    $test_data->sender_id = $USER->id;
    $test_data->receiver_id = 999;
    $test_data->message_type = 'original';
    $test_data->message_content = '테스트 메시지';
    $test_data->sent_at = time();
    
    echo "<p>삽입할 데이터:</p>";
    echo "<pre>" . print_r($test_data, true) . "</pre>";
    
    // 기존 테스트 데이터 삭제
    $DB->delete_records('mdl_chat_messages', array('room_id' => 'test_room'));
    
    // 삽입 시도
    $insert_id = $DB->insert_record('mdl_chat_messages', $test_data);
    echo "<p style='color: green;'>✅ 데이터 삽입 성공! ID: {$insert_id}</p>";
    
    // 조회 테스트
    $retrieved = $DB->get_record('mdl_chat_messages', array('id' => $insert_id));
    if ($retrieved) {
        echo "<p style='color: green;'>✅ 데이터 조회 성공!</p>";
        echo "<pre>" . print_r($retrieved, true) . "</pre>";
    }
    
    // 테스트 데이터 정리
    $DB->delete_records('mdl_chat_messages', array('id' => $insert_id));
    echo "<p>🗑️ 테스트 데이터 정리 완료</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 데이터 삽입 테스트 실패: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>