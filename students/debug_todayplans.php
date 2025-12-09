<?php
// 디버깅 스크립트 - todayplans 데이터 확인
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

echo "<h2>Todayplans 디버깅 정보</h2>";

// URL 파라미터에서 studentid 가져오기
$studentid = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$studentid) {
    echo "<p style='color: red;'>ERROR: studentid가 제공되지 않았습니다. URL에 ?id=학생번호 를 추가하세요.</p>";
    exit;
}

echo "<h3>1. 기본 정보</h3>";
echo "<p>Student ID: <b>$studentid</b></p>";

// 학생 이름 확인
try {
    $username = $DB->get_record_sql("SELECT lastname, firstname FROM {user} WHERE id = ?", array($studentid));
    if ($username) {
        echo "<p>학생 이름: <b>" . htmlspecialchars($username->firstname) . " " . htmlspecialchars($username->lastname) . "</b></p>";
    } else {
        echo "<p style='color: orange;'>WARNING: 학생 정보를 찾을 수 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR 학생 정보 조회: " . $e->getMessage() . "</p>";
}

// 2. 테이블 존재 확인
echo "<h3>2. abessi_todayplans 테이블 확인</h3>";
try {
    $table_check = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_abessi_todayplans'");
    if ($table_check) {
        echo "<p style='color: green;'>✅ 테이블 mdl_abessi_todayplans 존재함</p>";
    } else {
        echo "<p style='color: red;'>❌ 테이블 mdl_abessi_todayplans 존재하지 않음</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR 테이블 확인: " . $e->getMessage() . "</p>";
}

// 3. 전체 레코드 수 확인
echo "<h3>3. 전체 데이터 확인</h3>";
try {
    $total_records = $DB->count_records('abessi_todayplans');
    echo "<p>전체 레코드 수: <b>$total_records</b></p>";

    if ($total_records > 0) {
        // 최근 5개 레코드 표시
        $recent_records = $DB->get_records_sql("SELECT id, userid, timecreated, timemodified FROM {abessi_todayplans} ORDER BY timecreated DESC LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>생성시간</th><th>수정시간</th></tr>";
        foreach ($recent_records as $record) {
            $created = date('Y-m-d H:i:s', $record->timecreated);
            $modified = date('Y-m-d H:i:s', $record->timemodified);
            echo "<tr><td>$record->id</td><td>$record->userid</td><td>$created</td><td>$modified</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR 전체 레코드 조회: " . $e->getMessage() . "</p>";
}

// 4. 해당 학생의 레코드 확인
echo "<h3>4. 학생 ID $studentid 의 레코드 확인</h3>";
try {
    $student_records = $DB->get_records_sql("SELECT id, userid, timecreated, timemodified FROM {abessi_todayplans} WHERE userid = ? ORDER BY timecreated DESC", array($studentid));

    if ($student_records && count($student_records) > 0) {
        echo "<p style='color: green;'>✅ 레코드 <b>" . count($student_records) . "개</b> 발견</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>생성시간</th><th>수정시간</th><th>12시간 이내?</th></tr>";

        $twelveHoursAgo = time() - 43200;

        foreach ($student_records as $record) {
            $created = date('Y-m-d H:i:s', $record->timecreated);
            $modified = date('Y-m-d H:i:s', $record->timemodified);
            $within12hours = ($record->timecreated >= $twelveHoursAgo) ? "✅ YES" : "❌ NO";
            echo "<tr><td>$record->id</td><td>$created</td><td>$modified</td><td>$within12hours</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 학생 ID $studentid 의 레코드가 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR 학생 레코드 조회: " . $e->getMessage() . "</p>";
}

// 5. todayplans.php와 동일한 쿼리 테스트
echo "<h3>5. todayplans.php 쿼리 테스트</h3>";
try {
    $planinfo = $DB->get_record_sql("SELECT * FROM {abessi_todayplans} WHERE userid=? ORDER BY id DESC LIMIT 1", array($studentid));

    if ($planinfo) {
        echo "<p style='color: green;'>✅ 쿼리 성공 - 레코드 ID: <b>$planinfo->id</b></p>";
        echo "<p>생성시간: <b>" . date('Y-m-d H:i:s', $planinfo->timecreated) . "</b></p>";
        echo "<p>수정시간: <b>" . date('Y-m-d H:i:s', $planinfo->timemodified) . "</b></p>";

        // plan1, due1 확인
        echo "<h4>계획 데이터 샘플 (plan1~plan3)</h4>";
        echo "<ul>";
        for ($i = 1; $i <= 3; $i++) {
            $planField = 'plan' . $i;
            $dueField = 'due' . $i;
            $urlField = 'url' . $i;

            $planValue = isset($planinfo->$planField) ? $planinfo->$planField : '(empty)';
            $dueValue = isset($planinfo->$dueField) ? $planinfo->$dueField : '(empty)';
            $urlValue = isset($planinfo->$urlField) ? $planinfo->$urlField : '(empty)';

            echo "<li><b>$planField:</b> $planValue | <b>$dueField:</b> $dueValue | <b>$urlField:</b> $urlValue</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ 쿼리 결과 없음 - 학생 ID $studentid 의 데이터가 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR 쿼리 테스트: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='todayplans.php?id=$studentid'>← todayplans.php로 돌아가기</a></p>";
?>
