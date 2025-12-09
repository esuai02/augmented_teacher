<?php
// Moodle database connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

// Check if user is admin
if (!is_siteadmin()) {
    die("관리자 권한이 필요합니다.");
}

echo "<h2>Chatbot 테이블 생성 스크립트</h2>";
echo "<pre>";

// SQL 파일 읽기
$sql_file = __DIR__ . '/create_chatbot_table.sql';
if (!file_exists($sql_file)) {
    die("SQL 파일을 찾을 수 없습니다: $sql_file");
}

$sql_content = file_get_contents($sql_file);
echo "SQL 파일 내용:\n";
echo htmlspecialchars($sql_content) . "\n\n";

// SQL 문장 분리 및 실행
$sql_statements = array_filter(array_map('trim', explode(';', $sql_content)));

foreach ($sql_statements as $sql) {
    if (empty($sql) || strpos(strtoupper($sql), 'CREATE TABLE') === false) {
        continue;
    }
    
    echo "실행 중: " . substr($sql, 0, 50) . "...\n";
    
    try {
        $DB->execute($sql);
        echo "✅ 성공\n\n";
    } catch (Exception $e) {
        echo "❌ 실패: " . $e->getMessage() . "\n\n";
    }
}

// 테이블 확인
echo "\n=== 테이블 생성 확인 ===\n";
$tables = ['mdl_chatbot_messages', 'mdl_chatbot_preferences'];

foreach ($tables as $table) {
    $table_exists = $DB->get_record_sql("SHOW TABLES LIKE '$table'");
    if ($table_exists) {
        echo "✅ $table 테이블이 존재합니다.\n";
        
        // 테이블 구조 확인
        if ($table == 'mdl_chatbot_messages') {
            echo "  테이블 구조:\n";
            $columns = $DB->get_columns('chatbot_messages');
            foreach ($columns as $column) {
                echo "    - {$column->name} ({$column->type})\n";
            }
        }
    } else {
        echo "❌ $table 테이블이 존재하지 않습니다.\n";
    }
}

echo "\n=== 테스트 데이터 삽입 ===\n";
try {
    // 테스트 메시지 삽입
    $test_message = new stdClass();
    $test_message->student_id = 2;
    $test_message->learning_mode = 'curriculum';
    $test_message->message_type = 'bot';
    $test_message->message = '안녕하세요! 학습 도우미입니다. 무엇을 도와드릴까요?';
    $test_message->timestamp = time();
    
    $insert_id = $DB->insert_record('chatbot_messages', $test_message);
    echo "✅ 테스트 메시지 삽입 성공! ID: $insert_id\n";
    
    // 삽입된 데이터 확인
    $retrieved = $DB->get_record('chatbot_messages', array('id' => $insert_id));
    if ($retrieved) {
        echo "✅ 데이터 조회 성공!\n";
        echo "  - Student ID: {$retrieved->student_id}\n";
        echo "  - Learning Mode: {$retrieved->learning_mode}\n";
        echo "  - Message: {$retrieved->message}\n";
        
        // 테스트 데이터 삭제
        $DB->delete_records('chatbot_messages', array('id' => $insert_id));
        echo "✅ 테스트 데이터 삭제 완료\n";
    }
} catch (Exception $e) {
    echo "❌ 테스트 실패: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h3>완료!</h3>";
echo "<p>Chatbot 테이블이 성공적으로 생성되었습니다.</p>";
echo "<p><a href='index.php'>메인 페이지로 이동</a></p>";
?>