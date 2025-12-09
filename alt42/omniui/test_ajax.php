<?php
// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 교사 권한 확인
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다.</h2>");
}

// AJAX 테스트
if (isset($_GET['test'])) {
    $test = $_GET['test'];
    
    if ($test === 'students') {
        // 학생 목록 테스트
        $sql = "SELECT u.id, u.firstname, u.lastname 
                FROM mdl_user u
                INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
                WHERE uid.fieldid = 22 AND uid.data = 'student'
                AND u.deleted = 0 
                AND u.suspended = 0
                LIMIT 10";
        
        try {
            $students = $DB->get_records_sql($sql);
            
            $result = array();
            if ($students) {
                foreach ($students as $student) {
                    $result[] = array(
                        'id' => $student->id,
                        'name' => $student->firstname . ' ' . $student->lastname
                    );
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode(array(
                'status' => 'success',
                'count' => count($result),
                'data' => $result
            ));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'status' => 'error',
                'message' => $e->getMessage()
            ));
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AJAX Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .result { background: #f5f5f5; padding: 10px; margin-top: 10px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>AJAX Endpoint Test</h1>
    
    <div class="test-section">
        <h2>학생 목록 테스트</h2>
        <button onclick="testStudents()">Test Students API</button>
        <div id="students-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>attendance_teacher.php AJAX 테스트</h2>
        <button onclick="testMainStudents()">Test Main Students API</button>
        <div id="main-students-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>알림 테스트</h2>
        <button onclick="testAlerts()">Test Alerts API</button>
        <div id="alerts-result" class="result"></div>
    </div>

    <script>
        function testStudents() {
            const resultDiv = document.getElementById('students-result');
            resultDiv.innerHTML = 'Loading...';
            
            fetch('test_ajax.php?test=students')
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = 'Error: ' + error;
                });
        }
        
        function testMainStudents() {
            const resultDiv = document.getElementById('main-students-result');
            resultDiv.innerHTML = 'Loading...';
            
            fetch('attendance_teacher.php?ajax=students')
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = 'Error: ' + error;
                });
        }
        
        function testAlerts() {
            const resultDiv = document.getElementById('alerts-result');
            resultDiv.innerHTML = 'Loading...';
            
            fetch('attendance_teacher.php?ajax=alerts')
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = 'Error: ' + error;
                });
        }
    </script>
</body>
</html>