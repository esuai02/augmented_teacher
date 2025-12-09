<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

echo "<h2>주간목표 날짜 초기화</h2>";
echo "<p>모든 주간목표의 날짜를 월요일 기준 일주일 단위로 재설정합니다.</p>";

// 모든 주간목표 데이터 가져오기
$weeklyplans = $DB->get_records('abessi_weeklyplans');

if (empty($weeklyplans)) {
    echo "<p style='color:red;'>주간목표 데이터가 없습니다.</p>";
    exit;
}

$updated_count = 0;
$timecreated = time();

foreach ($weeklyplans as $plan) {
    echo "<hr>";
    echo "<p><strong>사용자 ID: {$plan->userid}, Progress ID: {$plan->progressid}</strong></p>";

    // 오늘 기준 이번 주 월요일 계산
    $dayOfWeek = date('N', $timecreated); // 1 (월요일) ~ 7 (일요일)
    $daysToSubtract = $dayOfWeek - 1; // 월요일까지 빼야 할 일수
    $mondayTimestamp = $timecreated - ($daysToSubtract * 86400); // 이번 주 월요일

    echo "<p>기준 월요일: " . date('Y-m-d', $mondayTimestamp) . "</p>";

    // 업데이트 데이터 준비
    $update_data = new stdClass();
    $update_data->id = $plan->id;

    echo "<ul>";
    // 16주간의 날짜를 월요일 기준으로 재설정
    for ($i = 1; $i <= 16; $i++) {
        $dateField = 'date' . $i;
        $newDate = date('Y-m-d', $mondayTimestamp + (($i - 1) * 7 * 86400));
        $update_data->$dateField = $newDate;

        $oldDate = isset($plan->$dateField) ? $plan->$dateField : '(비어있음)';
        echo "<li>Week {$i}: {$oldDate} → <strong>{$newDate}</strong></li>";
    }
    echo "</ul>";

    $update_data->timemodified = $timecreated;

    // 레코드 업데이트
    try {
        $DB->update_record('abessi_weeklyplans', $update_data);
        echo "<p style='color:green;'>✓ 업데이트 성공</p>";
        $updated_count++;
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ 업데이트 실패: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h3 style='color:blue;'>완료: {$updated_count}개의 레코드가 업데이트되었습니다.</h3>";
echo "<p><a href='javascript:history.back()'>뒤로 가기</a></p>";
?>
