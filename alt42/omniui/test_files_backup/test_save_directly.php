<?php
// 직접 저장 테스트
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>직접 저장 테스트 (User ID: $USER->id)</h2>";

// 테스트 데이터
$test_data = array(
    'userid' => $USER->id,
    'section' => 0,
    'school' => '테스트고등학교',
    'grade' => '고등학교 2학년',
    'examType' => '1mid'
);

echo "<h3>테스트 데이터:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// save_exam_data_alt42t.php에 POST 요청 보내기
$url = 'save_exam_data_alt42t.php';
$json_data = json_encode($test_data);

echo "<h3>전송할 JSON:</h3>";
echo "<pre>" . htmlspecialchars($json_data) . "</pre>";

// cURL 사용하여 요청
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_data)
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>HTTP Response Code: $http_code</h3>";
echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// JSON 파싱 시도
try {
    $result = json_decode($response, true);
    if ($result) {
        echo "<h3>Parsed Response:</h3>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "JSON 파싱 실패: " . $e->getMessage();
}

echo "<br><br>";
echo "<a href='exam_preparation_system.php'>시험 대비 시스템으로 이동</a> | ";
echo "<a href='check_exam_table.php'>테이블 구조 확인</a>";
?>