<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

echo "<h2>오늘목표 날짜 초기화</h2>";
echo "<p>모든 오늘목표의 날짜를 이번 주 월요일~일요일로 재설정합니다.</p>";

// 모든 주간목표 데이터 가져오기 (일별 목표는 같은 테이블 사용)
$weeklyplans = $DB->get_records('abessi_weeklyplans');

if (empty($weeklyplans)) {
    echo "<p style='color:red;'>데이터가 없습니다.</p>";
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
    // 7일간의 날짜를 월요일부터 일요일까지 재설정 (일별 목표용)
    for ($i = 1; $i <= 7; $i++) {
        $dateField = 'date' . $i;
        $newDate = date('Y-m-d', $mondayTimestamp + (($i - 1) * 86400));
        $update_data->$dateField = $newDate;

        $oldDate = isset($plan->$dateField) ? $plan->$dateField : '(비어있음)';
        $dayNames = ['월', '화', '수', '목', '금', '토', '일'];
        echo "<li>Day {$i} ({$dayNames[$i-1]}): {$oldDate} → <strong>{$newDate}</strong></li>";
    }

    // 8~16번 날짜는 주간목표용이므로 그대로 유지
    for ($i = 8; $i <= 16; $i++) {
        $dateField = 'date' . $i;
        if (isset($plan->$dateField)) {
            $update_data->$dateField = $plan->$dateField;
        }
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
echo "<p>일별 목표(date1~date7)는 이번 주 월~일로, 주간 목표(date8~date16)는 유지되었습니다.</p>";
echo "<p><a href='javascript:history.back()'>뒤로 가기</a></p>";
?>
