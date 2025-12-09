<?php
// DB 쓰기 권한 테스트

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>DB 쓰기 권한 및 테이블 구조 테스트</h2>";

// 1. 테이블 존재 여부 확인
echo "<h3>1. 테이블 존재 여부</h3>";
$tables = ['alt42t_users', 'alt42t_exams', 'alt42t_exam_dates', 'alt42t_exam_resources', 'alt42t_study_status'];
foreach ($tables as $table) {
    try {
        $count = $DB->count_records($table);
        echo "✅ mdl_$table - 존재 (레코드 수: $count)<br>";
    } catch (Exception $e) {
        echo "❌ mdl_$table - " . $e->getMessage() . "<br>";
    }
}

// 2. alt42t_users 테이블 구조 확인
echo "<h3>2. mdl_alt42t_users 테이블 구조</h3>";
try {
    $columns = $DB->get_columns('alt42t_users');
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>크기</th><th>NULL 허용</th><th>기본값</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col->name}</td>";
        echo "<td>{$col->type}</td>";
        echo "<td>{$col->max_length}</td>";
        echo "<td>" . ($col->not_null ? 'NO' : 'YES') . "</td>";
        echo "<td>{$col->default_value}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// 3. 쓰기 테스트
echo "<h3>3. 쓰기 권한 테스트</h3>";

// 테스트용 데이터
$test_user = new stdClass();
$test_user->userid = 99999; // 테스트용 ID
$test_user->name = 'TEST USER';
$test_user->school_name = 'TEST SCHOOL';
$test_user->grade = 1;
$test_user->timecreated = time();
$test_user->timemodified = time();

try {
    // INSERT 테스트
    $test_id = $DB->insert_record('alt42t_users', $test_user);
    echo "✅ INSERT 성공 - ID: $test_id<br>";
    
    // UPDATE 테스트 (직접 SQL)
    $sql = "UPDATE {alt42t_users} SET name = :name WHERE id = :id";
    $params = array('name' => 'TEST USER UPDATED', 'id' => $test_id);
    $DB->execute($sql, $params);
    echo "✅ UPDATE 성공<br>";
    
    // DELETE 테스트
    $DB->delete_records('alt42t_users', array('id' => $test_id));
    echo "✅ DELETE 성공<br>";
    
} catch (Exception $e) {
    echo "❌ 오류: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 4. 실제 사용자 데이터 업데이트 테스트
echo "<h3>4. 실제 사용자 데이터 업데이트 테스트 (userid: $USER->id)</h3>";
$existing = $DB->get_record('alt42t_users', array('userid' => $USER->id));

if ($existing) {
    echo "기존 데이터 발견:<br>";
    echo "<pre>" . print_r($existing, true) . "</pre>";
    
    try {
        // UPDATE 시도
        $sql = "UPDATE {alt42t_users} 
                SET school_name = :school_name, 
                    timemodified = :timemodified
                WHERE userid = :userid";
        
        $params = array(
            'school_name' => $existing->school_name . ' (테스트)',
            'timemodified' => time(),
            'userid' => $USER->id
        );
        
        $DB->execute($sql, $params);
        echo "✅ UPDATE 성공<br>";
        
        // 원래대로 복구
        $sql = "UPDATE {alt42t_users} 
                SET school_name = :school_name
                WHERE userid = :userid";
        
        $params = array(
            'school_name' => str_replace(' (테스트)', '', $existing->school_name),
            'userid' => $USER->id
        );
        
        $DB->execute($sql, $params);
        echo "✅ 복구 성공<br>";
        
    } catch (Exception $e) {
        echo "❌ UPDATE 오류: " . $e->getMessage() . "<br>";
    }
} else {
    echo "기존 데이터 없음<br>";
}

// 5. 트랜잭션 테스트
echo "<h3>5. 트랜잭션 테스트</h3>";
try {
    $transaction = $DB->start_delegated_transaction();
    
    // 테스트 데이터 삽입
    $test_data = new stdClass();
    $test_data->userid = 88888;
    $test_data->name = 'TRANSACTION TEST';
    $test_data->school_name = 'TRANSACTION SCHOOL';
    $test_data->grade = 2;
    $test_data->timecreated = time();
    $test_data->timemodified = time();
    
    $id = $DB->insert_record('alt42t_users', $test_data);
    echo "트랜잭션 내 INSERT 성공 - ID: $id<br>";
    
    // 롤백
    $transaction->rollback(new Exception('Test rollback'));
    
} catch (Exception $e) {
    echo "트랜잭션 롤백됨: " . $e->getMessage() . "<br>";
}

// 롤백 확인
$check = $DB->get_record('alt42t_users', array('userid' => 88888));
if (!$check) {
    echo "✅ 트랜잭션 롤백 확인됨<br>";
} else {
    echo "❌ 트랜잭션 롤백 실패<br>";
}

echo "<br><a href='exam_preparation_system.php'>돌아가기</a>";
?>