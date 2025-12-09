<?php
// 가장 기본적인 PHP 테스트
echo "<h1>Teacher Check Page</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Moodle config 포함 시도
$config_file = "/home/moodle/public_html/moodle/config.php";
if (file_exists($config_file)) {
    echo "<p>Config file exists</p>";
    
    // config 파일 포함
    require_once($config_file);
    
    // 로그인 확인
    require_login();
    
    echo "<h2>User Information</h2>";
    echo "<p>User ID: " . $USER->id . "</p>";
    echo "<p>Username: " . $USER->username . "</p>";
    echo "<p>First Name: " . $USER->firstname . "</p>";
    echo "<p>Last Name: " . $USER->lastname . "</p>";
    echo "<p>Full Name: " . $USER->firstname . " " . $USER->lastname . "</p>";
    
    // 교사 권한 체크
    echo "<h2>Teacher Check</h2>";
    $lastname = $USER->lastname;
    echo "<p>Checking lastname: '" . $lastname . "'</p>";
    
    if (strpos($lastname, 'T') !== false || $lastname === 'T') {
        echo "<p style='color:green;'><strong>✓ TEACHER CONFIRMED (lastname contains T)</strong></p>";
    } else {
        echo "<p style='color:red;'>✗ Not a teacher (lastname does not contain T)</p>";
    }
    
} else {
    echo "<p style='color:red;'>Config file not found at: " . $config_file . "</p>";
}
?>