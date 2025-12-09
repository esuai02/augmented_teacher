<?php
// MathKing DB fieldid 88, 89 테스트
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);

echo "<h2>MathKing DB Field 88, 89 테스트 (User ID: $userid)</h2>";

// 1. 사용자 기본 정보
$user = $DB->get_record('user', array('id' => $userid));
echo "<h3>1. 사용자 정보</h3>";
echo "이름: {$user->firstname} {$user->lastname}<br>";
echo "Username: {$user->username}<br><br>";

// 2. Field 88 (학교) 확인
echo "<h3>2. Field 88 (학교)</h3>";
$school_data = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => 88));
if ($school_data) {
    echo "학교: {$school_data->data}<br>";
} else {
    echo "학교 정보 없음<br>";
}

// 3. Field 89 (출생년도) 확인
echo "<h3>3. Field 89 (출생년도)</h3>";
$birth_data = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => 89));
if ($birth_data) {
    echo "저장된 데이터: {$birth_data->data}<br>";
    
    // 출생년도 파싱
    $birthYear = 0;
    if (preg_match('/^(\d{4})-/', $birth_data->data, $matches)) {
        $birthYear = intval($matches[1]);
        echo "파싱된 출생년도 (YYYY-MM-DD 형식): $birthYear<br>";
    } else if (preg_match('/^(\d{4})년/', $birth_data->data, $matches)) {
        $birthYear = intval($matches[1]);
        echo "파싱된 출생년도 (YYYY년 형식): $birthYear<br>";
    } else if (preg_match('/^(\d{4})$/', $birth_data->data, $matches)) {
        $birthYear = intval($matches[1]);
        echo "파싱된 출생년도 (YYYY 형식): $birthYear<br>";
    } else {
        echo "출생년도 파싱 실패<br>";
    }
    
    if ($birthYear > 0) {
        // 학년 계산
        $currentYear = 2025;
        $age = $currentYear - $birthYear;
        
        $gradeMap = array(
            18 => '고등학교 3학년',
            17 => '고등학교 2학년',
            16 => '고등학교 1학년',
            15 => '중학교 3학년',
            14 => '중학교 2학년',
            13 => '중학교 1학년'
        );
        
        $grade = isset($gradeMap[$age]) ? $gradeMap[$age] : "나이: $age 세";
        echo "계산된 학년: $grade<br>";
    }
} else {
    echo "출생년도 정보 없음<br>";
}

// 4. Field 88, 89가 정확한지 확인
echo "<h3>4. Field 정보 확인</h3>";
$field88 = $DB->get_record('user_info_field', array('id' => 88));
$field89 = $DB->get_record('user_info_field', array('id' => 89));

if ($field88) {
    echo "Field 88: {$field88->name} (shortname: {$field88->shortname})<br>";
} else {
    echo "Field 88 존재하지 않음<br>";
}

if ($field89) {
    echo "Field 89: {$field89->name} (shortname: {$field89->shortname})<br>";
} else {
    echo "Field 89 존재하지 않음<br>";
}

// 5. exam_preparation_system.php에서 사용하는 쿼리 테스트
echo "<h3>5. exam_preparation_system.php 쿼리 테스트</h3>";
$userinfo = $DB->get_record_sql("
    SELECT u.id, u.firstname, u.lastname, 
           (SELECT data FROM {user_info_data} WHERE userid = u.id AND fieldid = 89 LIMIT 1) as birthdate,
           (SELECT data FROM {user_info_data} WHERE userid = u.id AND fieldid = 88 LIMIT 1) as institute
    FROM {user} u 
    WHERE u.id = ?", array($userid));

echo "쿼리 결과:<br>";
echo "- 이름: {$userinfo->firstname} {$userinfo->lastname}<br>";
echo "- 학교: " . ($userinfo->institute ?: '없음') . "<br>";
echo "- 출생년도 데이터: " . ($userinfo->birthdate ?: '없음') . "<br>";

echo "<br><br>";
echo "<a href='exam_preparation_system.php?userid=$userid'>시험 대비 시스템으로 이동</a> | ";
echo "<a href='check_user_fields.php'>모든 User Fields 확인</a>";
?>