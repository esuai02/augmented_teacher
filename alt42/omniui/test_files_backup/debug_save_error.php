<?php
// 저장 오류 디버깅
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>저장 오류 디버깅 (User ID: $USER->id)</h2>";

// 1. alt42t_users 테이블 확인
echo "<h3>1. alt42t_users 테이블 상태</h3>";
$existing_user = $DB->get_record('alt42t_users', array('userid' => $USER->id));
if ($existing_user) {
    echo "✅ 기존 사용자 레코드 발견<br>";
    echo "<pre>";
    print_r($existing_user);
    echo "</pre>";
    $user_id = $existing_user->id;
} else {
    echo "❌ 사용자 레코드 없음<br>";
    $user_id = null;
}

// 2. 수동으로 업데이트 테스트
if ($existing_user) {
    echo "<h3>2. 직접 UPDATE 테스트</h3>";
    
    try {
        // alt42t_users 업데이트
        $sql = "UPDATE {alt42t_users} 
                SET school_name = :school_name,
                    grade = :grade,
                    timemodified = :timemodified
                WHERE userid = :userid";
        
        $params = array(
            'school_name' => '테스트고등학교',
            'grade' => 2,
            'timemodified' => time(),
            'userid' => $USER->id
        );
        
        echo "SQL: $sql<br>";
        echo "Params: <pre>" . print_r($params, true) . "</pre>";
        
        $DB->execute($sql, $params);
        echo "✅ alt42t_users UPDATE 성공<br><br>";
        
    } catch (Exception $e) {
        echo "❌ alt42t_users UPDATE 실패: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // 3. alt42t_exams 테스트
    echo "<h3>3. alt42t_exams 테스트</h3>";
    
    // 기존 exam 조회
    $existing_exam = $DB->get_record('alt42t_exams', array(
        'school_name' => '테스트고등학교',
        'grade' => 2,
        'userid' => $USER->id
    ));
    
    if ($existing_exam) {
        echo "✅ 기존 시험 레코드 발견<br>";
        echo "<pre>";
        print_r($existing_exam);
        echo "</pre>";
        
        try {
            $sql = "UPDATE {alt42t_exams} 
                    SET exam_type = :exam_type,
                        timemodified = :timemodified
                    WHERE exam_id = :exam_id";
            
            $params = array(
                'exam_type' => '1학기 중간고사',
                'timemodified' => time(),
                'exam_id' => $existing_exam->exam_id
            );
            
            $DB->execute($sql, $params);
            echo "✅ alt42t_exams UPDATE 성공<br>";
            
        } catch (Exception $e) {
            echo "❌ alt42t_exams UPDATE 실패: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "시험 레코드 없음 - 새로 생성 시도<br>";
        
        try {
            $exam_data = new stdClass();
            $exam_data->school_name = '테스트고등학교';
            $exam_data->grade = 2;
            $exam_data->exam_type = '1학기 중간고사';
            $exam_data->userid = $USER->id;
            $exam_data->timecreated = time();
            $exam_data->timemodified = time();
            
            $exam_id = $DB->insert_record('alt42t_exams', $exam_data);
            echo "✅ 새 exam 레코드 생성 성공 - ID: $exam_id<br>";
        } catch (Exception $e) {
            echo "❌ exam 레코드 생성 실패: " . $e->getMessage() . "<br>";
        }
    }
}

// 4. 테이블 구조 확인
echo "<h3>4. 테이블 구조 확인</h3>";
$tables = ['alt42t_users', 'alt42t_exams'];
foreach ($tables as $table) {
    echo "<h4>mdl_$table</h4>";
    try {
        $columns = $DB->get_columns($table);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>컬럼명</th><th>타입</th><th>NULL 허용</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col->name}</td>";
            echo "<td>{$col->type}</td>";
            echo "<td>" . ($col->not_null ? 'NO' : 'YES') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } catch (Exception $e) {
        echo "테이블 구조 조회 실패: " . $e->getMessage() . "<br>";
    }
}

// 5. 전체 저장 프로세스 시뮬레이션
echo "<h3>5. 전체 저장 프로세스 시뮬레이션</h3>";
echo '<button onclick="testFullSave()">전체 저장 테스트</button>';
echo '<div id="saveResult"></div>';

?>

<script>
function testFullSave() {
    const data = {
        userid: <?php echo $USER->id; ?>,
        section: 0,
        school: "시뮬레이션고등학교",
        grade: "고등학교 2학년",
        examType: "1mid"
    };
    
    fetch('save_exam_data_alt42t.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        document.getElementById('saveResult').innerHTML = '<pre>' + text + '</pre>';
        
        try {
            const json = JSON.parse(text);
            if (!json.success) {
                alert('오류: ' + json.message);
            }
        } catch(e) {
            console.error('JSON parse error:', e);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('saveResult').innerHTML = 'Error: ' + error;
    });
}
</script>

<br><br>
<a href="exam_preparation_system.php">돌아가기</a>