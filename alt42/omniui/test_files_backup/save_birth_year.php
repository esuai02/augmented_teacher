<?php
// 출생년도 저장 테스트
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$birthyear = optional_param('birthyear', '', PARAM_TEXT);

echo "<h2>출생년도 저장 (User ID: $userid)</h2>";

// 현재 저장된 데이터 확인
$current_data = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => 89));

echo "<h3>현재 저장된 데이터</h3>";
if ($current_data) {
    echo "데이터: {$current_data->data}<br>";
    echo "형식: " . gettype($current_data->data) . "<br>";
    echo "길이: " . strlen($current_data->data) . "<br>";
} else {
    echo "저장된 데이터 없음<br>";
}

// 저장 액션
if ($action == 'save' && $birthyear) {
    echo "<h3>저장 시도</h3>";
    
    if ($current_data) {
        // 업데이트
        $current_data->data = $birthyear;
        $DB->update_record('user_info_data', $current_data);
        echo "✅ 업데이트 완료: $birthyear<br>";
    } else {
        // 신규 삽입
        $record = new stdClass();
        $record->userid = $userid;
        $record->fieldid = 89;
        $record->data = $birthyear;
        $record->dataformat = 0;
        $DB->insert_record('user_info_data', $record);
        echo "✅ 신규 저장 완료: $birthyear<br>";
    }
    
    // 다시 읽어서 확인
    $new_data = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => 89));
    echo "저장된 값: {$new_data->data}<br>";
}

// 학년 계산 테스트
echo "<h3>학년 계산 테스트</h3>";
function calculateGrade($birthYear) {
    $gradeMap = array(
        2007 => '고등학교 3학년',
        2008 => '고등학교 2학년',
        2009 => '고등학교 1학년',
        2010 => '중학교 3학년',
        2011 => '중학교 2학년',
        2012 => '중학교 1학년'
    );
    
    return isset($gradeMap[$birthYear]) ? $gradeMap[$birthYear] : '';
}

$test_years = [2007, 2008, 2009, 2010, 2011, 2012];
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>출생년도</th><th>학년</th></tr>";
foreach ($test_years as $year) {
    $grade = calculateGrade($year);
    echo "<tr><td>{$year}년생</td><td>$grade</td></tr>";
}
echo "</table>";

// 저장 폼
echo "<h3>출생년도 저장하기</h3>";
echo "<form method='get'>";
echo "<input type='hidden' name='userid' value='$userid'>";
echo "<input type='hidden' name='action' value='save'>";
echo "<select name='birthyear'>";
echo "<option value=''>선택하세요</option>";
for ($year = 2007; $year <= 2012; $year++) {
    $grade = calculateGrade($year);
    echo "<option value='$year'>$year ($grade)</option>";
}
echo "</select>";
echo " <button type='submit'>저장</button>";
echo "</form>";

echo "<br><br>";
echo "<a href='exam_preparation_system.php?userid=$userid'>시험 대비 시스템으로 이동</a> | ";
echo "<a href='test_mathking_fields.php?userid=$userid'>Field 테스트</a>";
?>