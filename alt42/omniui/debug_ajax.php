<?php
// 디버깅 모드
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AJAX Debug Page</h1>";

// PHP 정보
echo "<h2>PHP 버전: " . phpversion() . "</h2>";

// Moodle 설정 포함 시도
echo "<h3>Moodle 설정 로드 시도...</h3>";
$config_file = "/home/moodle/public_html/moodle/config.php";
if (file_exists($config_file)) {
    echo "<p style='color:green;'>✓ Config 파일 존재</p>";
    
    try {
        include_once($config_file);
        echo "<p style='color:green;'>✓ Config 파일 로드 성공</p>";
        
        global $DB, $USER;
        require_login();
        echo "<p style='color:green;'>✓ 로그인 확인</p>";
        
        // 사용자 정보
        echo "<h3>현재 사용자</h3>";
        echo "<p>ID: {$USER->id}</p>";
        echo "<p>Username: {$USER->username}</p>";
        echo "<p>Name: {$USER->firstname} {$USER->lastname}</p>";
        
        // 교사 권한 확인
        $isTeacher = false;
        if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
            $isTeacher = true;
        }
        echo "<p>교사 권한: " . ($isTeacher ? "YES" : "NO") . "</p>";
        
        if (!$isTeacher) {
            die("<p style='color:red;'>교사 권한이 없습니다.</p>");
        }
        
        // 데이터베이스 테스트
        echo "<h3>데이터베이스 연결 테스트</h3>";
        try {
            $count = $DB->count_records_sql("SELECT COUNT(*) FROM mdl_user");
            echo "<p style='color:green;'>✓ 전체 사용자 수: $count</p>";
            
            // 학생 수 확인
            $student_count = $DB->count_records_sql("SELECT COUNT(*) FROM mdl_user u 
                                                     INNER JOIN mdl_user_info_data uid ON u.id = uid.userid 
                                                     WHERE uid.fieldid = 22 AND uid.data = 'student' 
                                                     AND u.deleted = 0 AND u.suspended = 0");
            echo "<p style='color:green;'>✓ 학생 수: $student_count</p>";
            
            // 처음 3명의 학생 가져오기
            echo "<h3>학생 데이터 샘플 (처음 3명)</h3>";
            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1 as phone
                    FROM mdl_user u
                    INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                    WHERE uid.fieldid = 22 AND uid.data = 'student'
                    AND u.deleted = 0 AND u.suspended = 0
                    ORDER BY u.firstname ASC
                    LIMIT 3";
            
            $students = $DB->get_records_sql($sql);
            
            if ($students) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>";
                foreach ($students as $student) {
                    echo "<tr>";
                    echo "<td>{$student->id}</td>";
                    echo "<td>{$student->firstname} {$student->lastname}</td>";
                    echo "<td>" . (isset($student->email) ? $student->email : 'N/A') . "</td>";
                    echo "<td>" . (isset($student->phone) ? $student->phone : 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // JSON 형식으로 변환 테스트
                echo "<h3>JSON 변환 테스트</h3>";
                $studentsData = array();
                foreach ($students as $student) {
                    $studentsData[] = array(
                        'id' => $student->id,
                        'name' => $student->firstname . ' ' . $student->lastname,
                        'email' => isset($student->email) ? $student->email : '',
                        'phone' => isset($student->phone) ? $student->phone : ''
                    );
                }
                
                $json_result = array(
                    'status' => 'success',
                    'count' => count($studentsData),
                    'data' => $studentsData
                );
                
                echo "<pre style='background: #f5f5f5; padding: 10px;'>";
                echo htmlspecialchars(json_encode($json_result, JSON_PRETTY_PRINT));
                echo "</pre>";
                
            } else {
                echo "<p style='color:orange;'>학생 데이터가 없습니다.</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>데이터베이스 오류: " . $e->getMessage() . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Config 파일을 찾을 수 없습니다: $config_file</p>";
}

// AJAX 엔드포인트 직접 링크
echo "<h3>AJAX 엔드포인트 테스트</h3>";
echo "<ul>";
echo "<li><a href='attendance_teacher.php?ajax=students' target='_blank'>학생 목록 (attendance_teacher.php)</a></li>";
echo "<li><a href='test_json.php' target='_blank'>학생 목록 (test_json.php)</a></li>";
echo "<li><a href='attendance_teacher.php?ajax=alerts' target='_blank'>알림 목록</a></li>";
echo "</ul>";

// JavaScript 테스트
echo '<h3>JavaScript Fetch 테스트</h3>';
echo '<button onclick="testFetch()">Fetch 테스트</button>';
echo '<div id="fetch-result" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; background: #f5f5f5; min-height: 100px;">결과가 여기 표시됩니다...</div>';
echo '
<script>
function testFetch() {
    const resultDiv = document.getElementById("fetch-result");
    resultDiv.innerHTML = "<p>Loading...</p>";
    
    console.log("Starting fetch...");
    
    fetch("attendance_teacher.php?ajax=students")
        .then(response => {
            console.log("Response received:", response);
            console.log("Status:", response.status);
            console.log("Status Text:", response.statusText);
            console.log("Headers:", response.headers.get("content-type"));
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            console.log("Raw text:", text);
            resultDiv.innerHTML = "<h4>Raw Response:</h4><pre>" + text.substring(0, 1000) + "</pre>";
            
            try {
                const data = JSON.parse(text);
                console.log("Parsed JSON:", data);
                resultDiv.innerHTML += "<h4>Parsed JSON:</h4><pre>" + JSON.stringify(data, null, 2) + "</pre>";
            } catch (e) {
                console.error("JSON parse error:", e);
                resultDiv.innerHTML += "<h4 style=\"color:red;\">JSON Parse Error:</h4><p>" + e.message + "</p>";
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            resultDiv.innerHTML = "<p style=\"color:red;\">Fetch Error: " + error.message + "</p>";
        });
}
</script>
';
?>