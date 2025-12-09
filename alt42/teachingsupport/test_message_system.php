<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? 0;
$teacherid = $_GET['teacherid'] ?? $USER->id;

if (!$studentid) {
    die('studentid 파라미터가 필요합니다.');
}

echo "<h2>메시지 시스템 진단 도구</h2>";
echo "<p>학생 ID: $studentid</p>";
echo "<p>선생님 ID: $teacherid</p>";
echo "<hr>";

// 1. 사용자 정보 확인
echo "<h3>1. 사용자 정보 확인</h3>";
$student = $DB->get_record('user', array('id' => $studentid));
$teacher = $DB->get_record('user', array('id' => $teacherid));

if ($student) {
    echo "✅ 학생 정보: " . fullname($student) . " (" . $student->email . ")<br>";
} else {
    echo "❌ 학생 정보를 찾을 수 없습니다.<br>";
}

if ($teacher) {
    echo "✅ 선생님 정보: " . fullname($teacher) . " (" . $teacher->email . ")<br>";
} else {
    echo "❌ 선생님 정보를 찾을 수 없습니다.<br>";
}

// 2. 메시지 테이블 존재 확인
echo "<h3>2. 데이터베이스 테이블 확인</h3>";
$tables_to_check = ['messages', 'message', 'message_read'];
foreach ($tables_to_check as $table) {
    if ($DB->get_manager()->table_exists($table)) {
        echo "✅ 테이블 '$table' 존재<br>";
    } else {
        echo "❌ 테이블 '$table' 없음<br>";
    }
}

// 3. 메시지 전송 함수 존재 확인
echo "<h3>3. Moodle 메시지 시스템 확인</h3>";
if (function_exists('message_send')) {
    echo "✅ message_send 함수 사용 가능<br>";
} else {
    echo "❌ message_send 함수 없음<br>";
}

// 4. 기존 메시지 확인
echo "<h3>4. 기존 메시지 확인</h3>";
try {
    // messages 테이블 직접 확인
    $total_messages = $DB->count_records('messages', array('useridto' => $studentid));
    echo "✅ 학생에게 보낸 전체 메시지 수: $total_messages<br>";
    
    // 하이튜터링 관련 메시지 확인 (like 조건 단순화)
    $teaching_sql = "SELECT COUNT(*) FROM {messages} WHERE useridto = ? AND subject LIKE ?";
    $teaching_messages = $DB->count_records_sql($teaching_sql, array($studentid, '%하이튜터링%'));
    echo "✅ 하이튜터링 관련 메시지 수: $teaching_messages<br>";
    
    // 최근 메시지 몇 개 보기 (LIMIT 제거, Moodle 방식 사용)
    $recent_messages = $DB->get_records('messages', array('useridto' => $studentid), 'timecreated DESC', '*', 0, 5);
    
    echo "<h4>최근 메시지 목록:</h4>";
    if (empty($recent_messages)) {
        echo "❌ 메시지가 없습니다.<br>";
    } else {
        foreach ($recent_messages as $msg) {
            echo "- " . date('Y-m-d H:i:s', $msg->timecreated) . ": " . $msg->subject . "<br>";
        }
    }
    
    // 모든 메시지 제목 확인 (디버깅용)
    $all_messages = $DB->get_records('messages', array('useridto' => $studentid), 'timecreated DESC', 'subject, timecreated', 0, 10);
    echo "<h4>전체 메시지 제목 (최근 10개):</h4>";
    foreach ($all_messages as $msg) {
        echo "- " . $msg->subject . " (" . date('Y-m-d H:i:s', $msg->timecreated) . ")<br>";
    }
    
} catch (Exception $e) {
    echo "❌ 메시지 조회 오류: " . $e->getMessage() . "<br>";
    echo "상세 오류: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

// 5. 테스트 메시지 전송 버튼
if ($student && $teacher) {
    echo "<h3>5. 테스트 메시지 전송</h3>";
    echo '<button onclick="sendTestMessage()">복잡한 테스트 메시지 전송</button>';
    echo '<button onclick="sendSimpleMessage()" style="margin-left: 10px;">간단한 테스트 메시지 전송</button>';
    echo '<div id="result"></div>';
}

?>

<script>
async function sendTestMessage() {
    const studentId = <?php echo $studentid; ?>;
    const teacherId = <?php echo $teacherid; ?>;
    
    const testMessage = `테스트 메시지입니다.
    
📚 전송 시간: ${new Date().toLocaleString()}
🔍 메시지 전달 테스트
    
이 메시지가 보이면 메시지 시스템이 정상 작동하고 있습니다.`;

    try {
        // 현재 페이지와 같은 디렉토리의 send_message.php 사용
        const response = await fetch('./send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                studentId: studentId,
                teacherId: teacherId,
                interactionId: 999999, // 테스트용 ID
                message: testMessage,
                solutionText: '테스트 풀이',
                audioUrl: ''
            })
        });

        const data = await response.json();
        const resultDiv = document.getElementById('result');
        
        if (data.success) {
            resultDiv.innerHTML = '<p style="color: green;">✅ 테스트 메시지 전송 성공!</p><p>메시지 ID: ' + data.message_id + '</p>';
        } else {
            resultDiv.innerHTML = '<p style="color: red;">❌ 전송 실패: ' + data.error + '</p>';
        }
    } catch (error) {
        document.getElementById('result').innerHTML = '<p style="color: red;">❌ 오류: ' + error.message + '</p>';
    }
}

async function sendSimpleMessage() {
    const studentId = <?php echo $studentid; ?>;
    const teacherId = <?php echo $teacherid; ?>;
    
    try {
        const response = await fetch('./simple_message_test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `studentid=${studentId}&teacherid=${teacherId}`
        });

        const data = await response.json();
        const resultDiv = document.getElementById('result');
        
        if (data.success) {
            resultDiv.innerHTML = '<p style="color: green;">✅ 간단한 테스트 메시지 전송 성공!</p><p>메시지 ID: ' + data.message_id + '</p><p>상세 정보: ' + JSON.stringify(data.debug_info, null, 2) + '</p>';
        } else {
            resultDiv.innerHTML = '<p style="color: red;">❌ 전송 실패: ' + data.error + '</p><p>디버그 정보: ' + JSON.stringify(data.debug_info, null, 2) + '</p>';
        }
    } catch (error) {
        document.getElementById('result').innerHTML = '<p style="color: red;">❌ 오류: ' + error.message + '</p>';
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h3 { color: #2c3e50; margin-top: 30px; }
button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #2980b9; }
#result { margin-top: 10px; padding: 10px; border-radius: 5px; }
</style>