<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Simple Test</title></head><body>";
echo "<h1>Simple Database Test</h1>";

try {
    // 1. 사용자 정보 테스트
    echo "<h2>1. 사용자 정보 테스트</h2>";
    $user = $DB->get_record('user', array('id' => $USER->id));
    if ($user) {
        echo "✅ 사용자 정보 조회 성공: " . fullname($user) . "<br>";
    } else {
        echo "❌ 사용자 정보 조회 실패<br>";
    }
    
    // 2. 테이블 존재 확인
    echo "<h2>2. 테이블 존재 확인</h2>";
    $tables_to_check = ['message', 'messages', 'user', 'message_read'];
    foreach ($tables_to_check as $table) {
        try {
            $exists = $DB->get_manager()->table_exists($table);
            if ($exists) {
                echo "✅ 테이블 '{$table}' 존재<br>";
            } else {
                echo "❌ 테이블 '{$table}' 없음<br>";
            }
        } catch (Exception $e) {
            echo "⚠️ 테이블 '{$table}' 확인 중 오류: " . $e->getMessage() . "<br>";
        }
    }
    
    // 3. 메시지 테이블 데이터 확인
    echo "<h2>3. 메시지 테이블 데이터 확인</h2>";
    $message_table = null;
    
    if ($DB->get_manager()->table_exists('message')) {
        $message_table = 'message';
        echo "✅ 'message' 테이블 사용<br>";
    } elseif ($DB->get_manager()->table_exists('messages')) {
        $message_table = 'messages';
        echo "✅ 'messages' 테이블 사용<br>";
    } else {
        echo "❌ 메시지 테이블이 없습니다<br>";
    }
    
    if ($message_table) {
        try {
            $count = $DB->count_records($message_table);
            echo "📊 전체 메시지 수: {$count}개<br>";
            
            // 현재 사용자가 받은 메시지 수
            $user_messages = $DB->count_records($message_table, array('useridto' => $USER->id));
            echo "📊 현재 사용자가 받은 메시지 수: {$user_messages}개<br>";
            
            // 하이튜터링 관련 메시지 수
            $sql = "SELECT COUNT(*) FROM {" . $message_table . "} WHERE useridto = ? AND (subject LIKE '%문제 해설%' OR subject LIKE '%하이튜터링%')";
            $tutoring_messages = $DB->count_records_sql($sql, array($USER->id));
            echo "📊 하이튜터링 관련 메시지 수: {$tutoring_messages}개<br>";
            
        } catch (Exception $e) {
            echo "❌ 메시지 테이블 조회 중 오류: " . $e->getMessage() . "<br>";
        }
    }
    
    // 4. 간단한 메시지 조회 테스트
    echo "<h2>4. 간단한 메시지 조회 테스트</h2>";
    if ($message_table) {
        try {
            $sql = "SELECT m.id, m.subject, m.timecreated, u.firstname, u.lastname
                    FROM {" . $message_table . "} m
                    JOIN {user} u ON m.useridfrom = u.id
                    WHERE m.useridto = ?
                    ORDER BY m.timecreated DESC
                    LIMIT 5";
            
            $recent_messages = $DB->get_records_sql($sql, array($USER->id));
            
            if ($recent_messages) {
                echo "✅ 최근 메시지 조회 성공 (" . count($recent_messages) . "개)<br>";
                foreach ($recent_messages as $msg) {
                    echo "- " . $msg->subject . " (from: " . fullname($msg) . ")<br>";
                }
            } else {
                echo "📭 받은 메시지가 없습니다<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ 메시지 조회 중 오류: " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. get_student_messages.php API 테스트
    echo "<h2>5. API 테스트</h2>";
    echo "<button onclick=\"testAPI()\">메시지 API 테스트</button>";
    echo "<div id='apiResult'></div>";
    
} catch (Exception $e) {
    echo "❌ 전체 테스트 중 오류: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<script>
async function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<p>API 테스트 중...</p>';
    
    try {
        const response = await fetch('get_student_messages.php');
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = '<p>✅ API 테스트 성공</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p>❌ API 테스트 실패</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p>❌ 네트워크 오류: ' + error.message + '</p>';
    }
}
</script>";

echo "</body></html>";
?>