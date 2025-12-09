<?php
// UPDATE 문제 해결 테스트
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>UPDATE 쿼리 수정 테스트</h2>";

// 현재 사용자의 alt42t_users 레코드 확인
$existing_user = $DB->get_record('alt42t_users', array('userid' => $USER->id));

if ($existing_user) {
    echo "<h3>기존 레코드 발견</h3>";
    echo "<pre>";
    print_r($existing_user);
    echo "</pre>";
    
    // UPDATE 테스트 - userid를 WHERE 조건으로 사용
    try {
        $sql = "UPDATE {alt42t_users} 
                SET school_name = :school_name,
                    timemodified = :timemodified
                WHERE userid = :userid";
        
        $params = array(
            'school_name' => $existing_user->school_name . ' (수정테스트)',
            'timemodified' => time(),
            'userid' => $USER->id
        );
        
        $DB->execute($sql, $params);
        echo "✅ UPDATE 성공 (userid 조건 사용)<br>";
        
        // 결과 확인
        $updated = $DB->get_record('alt42t_users', array('userid' => $USER->id));
        echo "<h3>업데이트된 레코드</h3>";
        echo "<pre>";
        print_r($updated);
        echo "</pre>";
        
        // 원래대로 복구
        $sql = "UPDATE {alt42t_users} 
                SET school_name = :school_name
                WHERE userid = :userid";
        
        $params = array(
            'school_name' => str_replace(' (수정테스트)', '', $updated->school_name),
            'userid' => $USER->id
        );
        
        $DB->execute($sql, $params);
        echo "✅ 복구 완료<br>";
        
    } catch (Exception $e) {
        echo "❌ UPDATE 오류: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "기존 레코드 없음 - 새로 생성 테스트<br>";
    
    try {
        $user_data = new stdClass();
        $user_data->userid = $USER->id;
        $user_data->name = $USER->firstname . ' ' . $USER->lastname;
        $user_data->school_name = '테스트 학교';
        $user_data->grade = 2;
        $user_data->timecreated = time();
        $user_data->timemodified = time();
        
        $id = $DB->insert_record('alt42t_users', $user_data);
        echo "✅ INSERT 성공 - ID: $id<br>";
        
        // 생성된 레코드 확인
        $created = $DB->get_record('alt42t_users', array('userid' => $USER->id));
        echo "<pre>";
        print_r($created);
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "❌ INSERT 오류: " . $e->getMessage() . "<br>";
    }
}

echo "<br><a href='exam_preparation_system.php'>시험 대비 시스템으로 돌아가기</a>";
echo " | <a href='check_saved_data.php'>저장된 데이터 확인</a>";
?>