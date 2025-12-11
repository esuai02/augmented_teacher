<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    print_error('관리자 권한이 필요합니다.');
}

echo "<h2>하이튜터링 메시지 시스템 설정</h2>";

// 1. 기존 테이블 존재 확인
$tables_to_check = [
    'ktm_teaching_interactions' => '하이튜터링 상호작용 테이블',
    'ktm_teaching_events' => '하이튜터링 이벤트 로그 테이블',
    'ktm_mathmessages' => '하이튜터링 메시지 테이블'
];

echo "<h3>1. 테이블 존재 확인</h3>";
foreach ($tables_to_check as $table => $description) {
    if ($DB->get_manager()->table_exists($table)) {
        echo "✅ $table ($description) - 존재<br>";
        
        // 레코드 수 확인
        try {
            $count = $DB->count_records($table);
            echo "&nbsp;&nbsp;&nbsp;📊 레코드 수: $count<br>";
        } catch (Exception $e) {
            echo "&nbsp;&nbsp;&nbsp;⚠️ 레코드 조회 오류: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $table ($description) - 없음<br>";
    }
    echo "<br>";
}

// 2. ktm_mathmessages 테이블 생성
echo "<h3>2. ktm_mathmessages 테이블 생성</h3>";

if (!$DB->get_manager()->table_exists('ktm_mathmessages')) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS {ktm_mathmessages} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            teacher_id BIGINT(10) NOT NULL COMMENT '선생님 ID',
            student_id BIGINT(10) NOT NULL COMMENT '학생 ID',
            interaction_id BIGINT(10) DEFAULT NULL COMMENT '상호작용 ID',
            subject VARCHAR(255) NOT NULL DEFAULT '하이튜터링 문제 해설' COMMENT '메시지 제목',
            message_content TEXT NOT NULL COMMENT '메시지 내용',
            solution_text TEXT DEFAULT NULL COMMENT '풀이 내용',
            audio_url VARCHAR(500) DEFAULT NULL COMMENT '음성 파일 URL',
            explanation_url VARCHAR(500) DEFAULT NULL COMMENT '상세 설명 URL',
            is_read TINYINT(1) DEFAULT 0 COMMENT '읽음 여부',
            timecreated BIGINT(10) NOT NULL COMMENT '생성 시간',
            timeread BIGINT(10) DEFAULT NULL COMMENT '읽은 시간',
            PRIMARY KEY (id),
            INDEX idx_student_id (student_id),
            INDEX idx_teacher_id (teacher_id),
            INDEX idx_interaction_id (interaction_id),
            INDEX idx_timecreated (timecreated),
            INDEX idx_is_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='하이튜터링 메시지 테이블'";
        
        $DB->execute($sql);
        echo "✅ ktm_mathmessages 테이블 생성 완료<br>";
        
    } catch (Exception $e) {
        echo "❌ ktm_mathmessages 테이블 생성 실패: " . $e->getMessage() . "<br>";
    }
} else {
    echo "ℹ️ ktm_mathmessages 테이블이 이미 존재합니다.<br>";
}

// 3. 테이블 구조 확인
echo "<h3>3. ktm_mathmessages 테이블 구조 확인</h3>";
if ($DB->get_manager()->table_exists('ktm_mathmessages')) {
    try {
        // 샘플 데이터 삽입 테스트
        $test_data = new stdClass();
        $test_data->teacher_id = 1;
        $test_data->student_id = 2;
        $test_data->interaction_id = null;
        $test_data->subject = '테스트 메시지';
        $test_data->message_content = '테스트 메시지 내용입니다.';
        $test_data->solution_text = null;
        $test_data->audio_url = null;
        $test_data->explanation_url = null;
        $test_data->is_read = 0;
        $test_data->timecreated = time();
        $test_data->timeread = null;
        
        $test_id = $DB->insert_record('ktm_mathmessages', $test_data);
        
        if ($test_id) {
            echo "✅ 테스트 데이터 삽입 성공 (ID: $test_id)<br>";
            
            // 테스트 데이터 삭제
            $DB->delete_records('ktm_mathmessages', array('id' => $test_id));
            echo "✅ 테스트 데이터 삭제 완료<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ 테이블 테스트 실패: " . $e->getMessage() . "<br>";
    }
}

// 4. 시스템 통합 확인
echo "<h3>4. 시스템 통합 확인</h3>";

$integration_tests = [
    'send_message.php' => '메시지 전송 API',
    'get_student_messages.php' => '메시지 조회 API',
    'mark_message_read.php' => '읽음 처리 API',
    'student_inbox.php' => '학생 메시지함'
];

foreach ($integration_tests as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file ($description) - 파일 존재<br>";
    } else {
        echo "❌ $file ($description) - 파일 없음<br>";
    }
}

echo "<h3>5. 설정 완료</h3>";
echo "<p>✅ 하이튜터링 메시지 시스템 설정이 완료되었습니다!</p>";

echo "<h4>테스트 방법:</h4>";
echo "<ol>";
echo "<li><strong>메시지 전송 테스트:</strong><br>";
echo "&nbsp;&nbsp;<code>simple_message_test.php?studentid=827&teacherid=2</code></li>";
echo "<li><strong>학생 메시지함 확인:</strong><br>";
echo "&nbsp;&nbsp;<code>student_inbox.php?studentid=827</code></li>";
echo "<li><strong>하이튜터링 실행:</strong><br>";
echo "&nbsp;&nbsp;<code>teachingagent.php?userid=2&studentid=827</code></li>";
echo "</ol>";

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
}
h2, h3 { 
    color: #2c3e50; 
    border-bottom: 2px solid #3498db;
    padding-bottom: 5px;
}
h4 {
    color: #e74c3c;
    margin-top: 20px;
}
code {
    background: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
ol li {
    margin-bottom: 10px;
}
</style>