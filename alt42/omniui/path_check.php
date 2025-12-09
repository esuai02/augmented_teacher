<?php
// 현재 파일 경로 확인
echo "<h2>파일 경로 확인</h2>";
echo "<p>현재 스크립트 경로: " . __FILE__ . "</p>";
echo "<p>디렉토리: " . __DIR__ . "</p>";

// 관련 파일들 존재 여부 확인
$files = array(
    'exam_preparation_system.php',
    'save_exam_data_alt42t.php',
    'test_exam_system.php',
    'check_table_structure.php',
    'dashboard.php',
    'index.php'
);

echo "<h3>파일 존재 여부:</h3>";
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file - 존재함<br>";
    } else {
        echo "❌ $file - 없음<br>";
    }
}

// Moodle 경로 확인
if (file_exists("/home/moodle/public_html/moodle/config.php")) {
    echo "<br>✅ Moodle config.php 찾음<br>";
} else {
    echo "<br>❌ Moodle config.php를 찾을 수 없음<br>";
}

// 현재 URL 구조
echo "<br><h3>접속 URL 정보:</h3>";
echo "프로토콜: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "<br>";
echo "호스트: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "요청 URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "스크립트 이름: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// 정확한 접속 URL 제시
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);

echo "<br><h3>시스템 접속 URL:</h3>";
echo "<a href='$base_url$script_path/exam_preparation_system.php'>시험 대비 시스템</a><br>";
echo "<a href='$base_url$script_path/test_exam_system.php'>테스트 페이지</a><br>";
echo "<a href='$base_url$script_path/check_table_structure.php'>테이블 구조 확인</a><br>";
?>