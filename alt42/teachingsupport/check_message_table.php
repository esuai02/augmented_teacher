<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

echo "<h2>메시지 테이블 구조 확인</h2>";

// 1. 메시지 관련 테이블들 확인
$tables_to_check = ['messages', 'message', 'message_read', 'message_metadata'];

echo "<h3>1. 테이블 존재 확인</h3>";
foreach ($tables_to_check as $table) {
    if ($DB->get_manager()->table_exists($table)) {
        echo "✅ 테이블 '$table' 존재<br>";
        
        // 테이블 구조 확인 (첫 번째 레코드로 필드 확인)
        try {
            $sample = $DB->get_records($table, array(), '', '*', 0, 1);
            if (!empty($sample)) {
                $first_record = reset($sample);
                echo "&nbsp;&nbsp;&nbsp;필드들: " . implode(', ', array_keys(get_object_vars($first_record))) . "<br>";
            } else {
                echo "&nbsp;&nbsp;&nbsp;테이블이 비어있음<br>";
            }
        } catch (Exception $e) {
            echo "&nbsp;&nbsp;&nbsp;구조 확인 오류: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ 테이블 '$table' 없음<br>";
    }
    echo "<br>";
}

// 2. 메시지 테이블의 샘플 데이터 확인
echo "<h3>2. 메시지 테이블 샘플 데이터 확인</h3>";
try {
    if ($DB->get_manager()->table_exists('messages')) {
        $sample_messages = $DB->get_records('messages', array(), 'id DESC', '*', 0, 3);
        if (!empty($sample_messages)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            $first = true;
            foreach ($sample_messages as $msg) {
                if ($first) {
                    // 헤더 출력
                    echo "<tr>";
                    foreach (get_object_vars($msg) as $field => $value) {
                        echo "<th>$field</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                
                // 데이터 출력
                echo "<tr>";
                foreach (get_object_vars($msg) as $field => $value) {
                    echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "메시지 테이블이 비어있습니다.<br>";
        }
    }
} catch (Exception $e) {
    echo "샘플 데이터 확인 오류: " . $e->getMessage() . "<br>";
}

// 3. 특정 사용자 ID로 메시지 검색 시도
$test_userid = $_GET['userid'] ?? 2;
echo "<h3>3. 사용자 ID $test_userid 관련 메시지 검색</h3>";

$possible_fields = ['useridto', 'userid', 'userto', 'recipient', 'recipientid'];

foreach ($possible_fields as $field) {
    try {
        $count = $DB->count_records('messages', array($field => $test_userid));
        echo "✅ 필드 '$field'로 검색: $count 개 메시지<br>";
    } catch (Exception $e) {
        echo "❌ 필드 '$field' 없음 또는 오류<br>";
    }
}

// 4. Moodle 버전 확인
echo "<h3>4. Moodle 정보</h3>";
echo "Moodle 버전: " . $CFG->version . "<br>";
echo "Release: " . $CFG->release . "<br>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h3 { color: #2c3e50; margin-top: 30px; }
table { margin: 10px 0; }
th { background: #f8f9fa; padding: 5px; }
td { padding: 5px; }
</style>