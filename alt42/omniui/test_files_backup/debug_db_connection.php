<?php
// DB 연결 및 데이터 확인 디버깅 스크립트

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>DB 연결 및 데이터 디버깅</h2>";

// 현재 사용자 정보
echo "<h3>1. 현재 사용자 정보</h3>";
echo "User ID: " . $USER->id . "<br>";
echo "Username: " . $USER->username . "<br>";
echo "Name: " . $USER->firstname . " " . $USER->lastname . "<br><br>";

// mathking DB에서 사용자 정보 조회
echo "<h3>2. MathKing DB 사용자 정보 (mdl_user_info_data)</h3>";
try {
    // 출생년도 (fieldid = 5)
    $birthdate = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => 5));
    echo "출생년도 (fieldid=5): " . ($birthdate ?: "없음") . "<br>";
    
    // 학교 (fieldid = 4)
    $institute = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => 4));
    echo "학교 (fieldid=4): " . ($institute ?: "없음") . "<br>";
    
    // fieldid 88, 89도 확인 (save_exam_data_alt42t.php에서 사용)
    $school_88 = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => 88));
    echo "학교 (fieldid=88): " . ($school_88 ?: "없음") . "<br>";
    
    $birth_89 = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => 89));
    echo "출생년도 (fieldid=89): " . ($birth_89 ?: "없음") . "<br>";
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// alt42t 테이블에서 데이터 조회
echo "<br><h3>3. Alt42t 테이블 데이터</h3>";

// mdl_alt42t_users
echo "<h4>mdl_alt42t_users</h4>";
try {
    $alt42t_user = $DB->get_record('alt42t_users', array('userid' => $USER->id));
    if ($alt42t_user) {
        echo "<pre>";
        print_r($alt42t_user);
        echo "</pre>";
    } else {
        echo "데이터 없음<br>";
    }
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// mdl_alt42t_exams
echo "<h4>mdl_alt42t_exams</h4>";
try {
    if ($alt42t_user) {
        $exams = $DB->get_records('alt42t_exams', array(
            'school_name' => $alt42t_user->school_name,
            'grade' => $alt42t_user->grade
        ));
        if ($exams) {
            echo "<pre>";
            print_r($exams);
            echo "</pre>";
        } else {
            echo "데이터 없음<br>";
        }
    }
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// 데이터 저장 테스트
echo "<br><h3>4. 데이터 저장 테스트</h3>";
echo '<form method="post" action="save_exam_data_alt42t.php">';
echo '<input type="hidden" name="userid" value="' . $USER->id . '">';
echo '<input type="hidden" name="section" value="0">';
echo '<p>학교: <input type="text" name="school" value="테스트고등학교"></p>';
echo '<p>학년: <input type="text" name="grade" value="2"></p>';
echo '<p>시험종류: <input type="text" name="examType" value="1학기 중간고사"></p>';
echo '<p><button type="submit">저장 테스트</button></p>';
echo '</form>';

// AJAX 테스트
echo '<br><h3>5. AJAX 저장 테스트</h3>';
echo '<button onclick="testSave()">AJAX 저장 테스트</button>';
echo '<div id="result"></div>';

echo '<script>
function testSave() {
    const data = {
        userid: ' . $USER->id . ',
        section: 0,
        school: "AJAX테스트고등학교",
        grade: "고등학교 2학년",
        examType: "1학기 중간고사"
    };
    
    fetch("save_exam_data_alt42t.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(data)
    })
    .then(response => response.text())
    .then(text => {
        console.log("Raw response:", text);
        return JSON.parse(text);
    })
    .then(data => {
        document.getElementById("result").innerHTML = "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
    })
    .catch(error => {
        document.getElementById("result").innerHTML = "Error: " + error;
        console.error("Error:", error);
    });
}
</script>';

// 최근 저장된 데이터 확인
echo '<br><h3>6. 최근 저장된 데이터 (최근 5개)</h3>';
try {
    $recent_users = $DB->get_records_sql("
        SELECT * FROM {alt42t_users} 
        ORDER BY timecreated DESC 
        LIMIT 5
    ");
    
    if ($recent_users) {
        echo "<h4>최근 사용자:</h4><pre>";
        print_r($recent_users);
        echo "</pre>";
    }
    
    $recent_exams = $DB->get_records_sql("
        SELECT * FROM {alt42t_exams} 
        ORDER BY timecreated DESC 
        LIMIT 5
    ");
    
    if ($recent_exams) {
        echo "<h4>최근 시험:</h4><pre>";
        print_r($recent_exams);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// 세션 정보
echo '<br><h3>7. 세션 정보</h3>';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>