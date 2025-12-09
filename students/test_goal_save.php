<?php 
// Test script for goal saving functionality
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

echo "<h2>목표 저장 기능 테스트</h2>\n";

// Test parameters
$test_userid = $USER->id;
$test_eventid = 2;
$test_inputtext = "테스트 목표 " . date('Y-m-d H:i:s');
$test_type = "오늘목표";
$test_level = 2;
$test_deadline = date('Y-m-d');

echo "<p><strong>테스트 파라미터:</strong></p>\n";
echo "<ul>\n";
echo "<li>User ID: $test_userid</li>\n";
echo "<li>Event ID: $test_eventid</li>\n";
echo "<li>Input Text: $test_inputtext</li>\n";
echo "<li>Type: $test_type</li>\n";
echo "<li>Level: $test_level</li>\n";
echo "<li>Deadline: $test_deadline</li>\n";
echo "</ul>\n";

// Test database table existence
try {
    $table_check = $DB->get_record_sql("SELECT COUNT(*) as count FROM mdl_abessi_today LIMIT 1");
    echo "<p><strong>✅ 데이터베이스 테이블 접근:</strong> 성공</p>\n";
} catch (Exception $e) {
    echo "<p><strong>❌ 데이터베이스 테이블 접근:</strong> 실패 - " . $e->getMessage() . "</p>\n";
}

// Test parameter validation (simulate what database.php does)
$errors = [];

if(empty($test_inputtext)) {
    $errors[] = "입력 텍스트가 비어있습니다.";
}
if(empty($test_type)) {
    $errors[] = "목표 타입이 지정되지 않았습니다.";
}
if(empty($test_userid)) {
    $errors[] = "사용자 ID가 지정되지 않았습니다.";
}

if (!empty($errors)) {
    echo "<p><strong>❌ 파라미터 검증:</strong></p>\n";
    foreach ($errors as $error) {
        echo "<li>$error</li>\n";
    }
} else {
    echo "<p><strong>✅ 파라미터 검증:</strong> 통과</p>\n";
}

// Test existing goal retrieval
try {
    $checkgoal1 = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$test_userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1");
    $checkgoal2 = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$test_userid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
    
    echo "<p><strong>✅ 기존 목표 조회:</strong> 성공</p>\n";
    echo "<ul>\n";
    echo "<li>기존 오늘목표: " . ($checkgoal1 ? "있음 (ID: {$checkgoal1->id})" : "없음") . "</li>\n";
    echo "<li>기존 주간목표: " . ($checkgoal2 ? "있음 (ID: {$checkgoal2->id})" : "없음") . "</li>\n";
    echo "</ul>\n";
    
    // Test score calculation
    $score = ($checkgoal1 && $checkgoal1->score !== null) ? $checkgoal1->score : 0;
    echo "<p><strong>계산된 점수:</strong> $score</p>\n";
    
} catch (Exception $e) {
    echo "<p><strong>❌ 기존 목표 조회:</strong> 실패 - " . $e->getMessage() . "</p>\n";
}

// Test actual insertion (but don't execute to avoid duplicate data)
echo "<p><strong>테스트 결과:</strong> 모든 검증이 통과하면 실제 저장이 가능합니다.</p>\n";

// Show SQL that would be executed
$timecreated = time();
$deadline_timestamp = strtotime($test_deadline);
$mindset = '';

echo "<p><strong>실행될 SQL:</strong></p>\n";
echo "<pre>INSERT INTO mdl_abessi_today (text,userid,type,goallevel,score,complete,mindset,timemodified,timecreated,deadline) VALUES('$test_inputtext','$test_userid','$test_type','$test_level','0','0','$mindset','$timecreated','$timecreated','$deadline_timestamp')</pre>\n";

echo "<p><strong>JSON 응답 예시:</strong></p>\n";
echo "<pre>" . json_encode(array("passedeventid" => $test_eventid), JSON_UNESCAPED_UNICODE) . "</pre>\n";

?>