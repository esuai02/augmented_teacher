<?php
// MathKing DB 정보 표시 페이지
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);

echo "<h2>MathKing DB 정보 확인 (User ID: $userid)</h2>";

// 1. 기본 사용자 정보
$user = $DB->get_record('user', array('id' => $userid));
echo "<h3>1. 기본 사용자 정보</h3>";
echo "이름: {$user->firstname} {$user->lastname}<br>";
echo "Username: {$user->username}<br>";
echo "Email: {$user->email}<br><br>";

// 2. user_info_field 확인
echo "<h3>2. User Info Fields</h3>";
$fields = $DB->get_records_sql("
    SELECT uif.*, uid.data 
    FROM {user_info_field} uif
    LEFT JOIN {user_info_data} uid ON uif.id = uid.fieldid AND uid.userid = ?
    ORDER BY uif.id
", array($userid));

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Short Name</th><th>User Data</th></tr>";
foreach ($fields as $field) {
    $data = $field->data ?? '(없음)';
    echo "<tr>";
    echo "<td>{$field->id}</td>";
    echo "<td>{$field->name}</td>";
    echo "<td>{$field->shortname}</td>";
    echo "<td>$data</td>";
    echo "</tr>";
}
echo "</table>";

// 3. 학년 계산
function calculateGrade($birthYear) {
    $currentYear = 2025;
    $age = $currentYear - $birthYear;
    
    $gradeMap = array(
        18 => '고등학교 3학년',
        17 => '고등학교 2학년',
        16 => '고등학교 1학년',
        15 => '중학교 3학년',
        14 => '중학교 2학년',
        13 => '중학교 1학년',
        12 => '초등학교 6학년',
        11 => '초등학교 5학년',
        10 => '초등학교 4학년',
        9 => '초등학교 3학년'
    );
    
    return isset($gradeMap[$age]) ? $gradeMap[$age] : "나이: $age 세";
}

// 4. 출생년도와 학년 계산
echo "<h3>3. 출생년도와 학년 계산</h3>";
$birth_data = null;
foreach ($fields as $field) {
    if (stripos($field->name, 'birth') !== false || stripos($field->name, '생년') !== false || stripos($field->name, '출생') !== false) {
        if (!empty($field->data)) {
            echo "출생 관련 필드: {$field->name} (ID: {$field->id})<br>";
            echo "데이터: {$field->data}<br>";
            
            // 출생년도 파싱
            $birthYear = 0;
            if (preg_match('/^(\d{4})-/', $field->data, $matches)) {
                $birthYear = intval($matches[1]);
            } else if (preg_match('/^(\d{4})년/', $field->data, $matches)) {
                $birthYear = intval($matches[1]);
            } else if (preg_match('/^(\d{4})$/', $field->data, $matches)) {
                $birthYear = intval($matches[1]);
            }
            
            if ($birthYear > 0) {
                echo "파싱된 출생년도: $birthYear<br>";
                echo "계산된 학년: " . calculateGrade($birthYear) . "<br>";
            }
        }
    }
}

// 5. 학교 정보
echo "<h3>4. 학교 정보</h3>";
foreach ($fields as $field) {
    if (stripos($field->name, 'school') !== false || stripos($field->name, '학교') !== false || 
        stripos($field->name, 'institute') !== false || stripos($field->name, '소속') !== false) {
        if (!empty($field->data)) {
            echo "학교 관련 필드: {$field->name} (ID: {$field->id})<br>";
            echo "데이터: {$field->data}<br>";
        }
    }
}

// 6. exam_preparation_system.php에서 사용하는 쿼리 테스트
echo "<h3>5. exam_preparation_system.php 쿼리 테스트</h3>";

// 학교 필드 찾기
$school_field = $DB->get_record_sql("
    SELECT id FROM {user_info_field} 
    WHERE shortname LIKE '%school%' OR shortname LIKE '%institute%' 
    OR name LIKE '%학교%' OR name LIKE '%소속%'
    LIMIT 1
");
echo "학교 필드 ID: " . ($school_field ? $school_field->id : '찾을 수 없음') . "<br>";

// 출생 필드 찾기
$birth_field = $DB->get_record_sql("
    SELECT id FROM {user_info_field} 
    WHERE shortname LIKE '%birth%' OR shortname LIKE '%year%' 
    OR name LIKE '%생년%' OR name LIKE '%출생%'
    LIMIT 1
");
echo "출생 필드 ID: " . ($birth_field ? $birth_field->id : '찾을 수 없음') . "<br>";

// fieldid 4, 5, 88, 89 확인
echo "<h3>6. 특정 fieldid 확인</h3>";
$check_fields = [4, 5, 88, 89];
foreach ($check_fields as $fid) {
    $field = $DB->get_record('user_info_field', array('id' => $fid));
    if ($field) {
        $data = $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => $fid));
        echo "Field $fid: {$field->name} = " . ($data ?: '(데이터 없음)') . "<br>";
    } else {
        echo "Field $fid: 존재하지 않음<br>";
    }
}

echo "<br><br>";
echo "<a href='exam_preparation_system.php?userid=$userid'>시험 대비 시스템으로 이동</a> | ";
echo "<a href='check_user_fields.php'>User Fields 확인</a>";
?>