<?php
// user_info_field와 user_info_data 확인
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>User Info Fields 확인</h2>";

// 1. user_info_field 테이블 확인
echo "<h3>1. mdl_user_info_field 테이블</h3>";
$fields = $DB->get_records('user_info_field', array(), 'id ASC');

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Short Name</th><th>Name</th><th>Data Type</th><th>Description</th></tr>";
foreach ($fields as $field) {
    echo "<tr>";
    echo "<td>{$field->id}</td>";
    echo "<td>{$field->shortname}</td>";
    echo "<td>{$field->name}</td>";
    echo "<td>{$field->datatype}</td>";
    echo "<td>" . substr($field->description, 0, 50) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// 2. 현재 사용자의 user_info_data 확인
echo "<h3>2. 현재 사용자 ({$USER->id})의 user_info_data</h3>";
$userdata = $DB->get_records_sql("
    SELECT uid.*, uif.name, uif.shortname 
    FROM {user_info_data} uid
    JOIN {user_info_field} uif ON uid.fieldid = uif.id
    WHERE uid.userid = ?
", array($USER->id));

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field ID</th><th>Field Name</th><th>Short Name</th><th>Data</th></tr>";
foreach ($userdata as $data) {
    echo "<tr>";
    echo "<td>{$data->fieldid}</td>";
    echo "<td>{$data->name}</td>";
    echo "<td>{$data->shortname}</td>";
    echo "<td>{$data->data}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. 학교와 출생년도 관련 필드 찾기
echo "<h3>3. 학교/출생년도 관련 필드 검색</h3>";
$school_fields = $DB->get_records_sql("
    SELECT * FROM {user_info_field} 
    WHERE name LIKE '%학교%' 
       OR name LIKE '%school%' 
       OR name LIKE '%institute%'
       OR shortname LIKE '%school%'
       OR shortname LIKE '%institute%'
");

echo "<h4>학교 관련 필드:</h4>";
if ($school_fields) {
    foreach ($school_fields as $field) {
        echo "ID: {$field->id}, Name: {$field->name}, Short: {$field->shortname}<br>";
    }
} else {
    echo "학교 관련 필드를 찾을 수 없습니다.<br>";
}

$birth_fields = $DB->get_records_sql("
    SELECT * FROM {user_info_field} 
    WHERE name LIKE '%생년%' 
       OR name LIKE '%birth%' 
       OR name LIKE '%년도%'
       OR shortname LIKE '%birth%'
       OR shortname LIKE '%year%'
");

echo "<h4>출생년도 관련 필드:</h4>";
if ($birth_fields) {
    foreach ($birth_fields as $field) {
        echo "ID: {$field->id}, Name: {$field->name}, Short: {$field->shortname}<br>";
    }
} else {
    echo "출생년도 관련 필드를 찾을 수 없습니다.<br>";
}

// 4. 특정 fieldid 확인 (88, 89)
echo "<h3>4. Field ID 88, 89 확인</h3>";
$field88 = $DB->get_record('user_info_field', array('id' => 88));
$field89 = $DB->get_record('user_info_field', array('id' => 89));

if ($field88) {
    echo "Field 88: {$field88->name} ({$field88->shortname})<br>";
} else {
    echo "Field 88: 존재하지 않음<br>";
}

if ($field89) {
    echo "Field 89: {$field89->name} ({$field89->shortname})<br>";
} else {
    echo "Field 89: 존재하지 않음<br>";
}

// 5. 샘플 사용자 데이터 (최근 5명)
echo "<h3>5. 샘플 사용자 데이터</h3>";
$sample_users = $DB->get_records_sql("
    SELECT DISTINCT userid FROM {user_info_data} 
    ORDER BY id DESC 
    LIMIT 5
");

foreach ($sample_users as $sample) {
    $user = $DB->get_record('user', array('id' => $sample->userid));
    if ($user) {
        echo "<h4>User: {$user->firstname} {$user->lastname} (ID: {$user->id})</h4>";
        
        // 이 사용자의 모든 custom field 데이터
        $userfields = $DB->get_records_sql("
            SELECT uid.*, uif.name 
            FROM {user_info_data} uid
            JOIN {user_info_field} uif ON uid.fieldid = uif.id
            WHERE uid.userid = ?
        ", array($user->id));
        
        foreach ($userfields as $uf) {
            echo "- {$uf->name}: {$uf->data}<br>";
        }
    }
}

echo "<br><a href='exam_preparation_system.php'>돌아가기</a>";
?>