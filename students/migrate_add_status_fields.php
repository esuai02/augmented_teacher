<?php
// DB 마이그레이션: mdl_abessi_todayplans 테이블에 status01~status16 필드 추가
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

echo "<h2>DB 마이그레이션: status 필드 추가</h2>";
echo "<p>파일 위치: " . __FILE__ . "</p>";

try {
    // 테이블 존재 확인
    $table_check = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_abessi_todayplans'");

    if (!$table_check) {
        echo "<p style='color: red;'>❌ ERROR: 테이블 mdl_abessi_todayplans이 존재하지 않습니다.</p>";
        exit;
    }

    echo "<p style='color: green;'>✅ 테이블 mdl_abessi_todayplans 존재 확인</p>";

    // 기존 컬럼 확인
    $columns = $DB->get_columns('abessi_todayplans');
    echo "<h3>기존 컬럼 확인</h3>";

    $existing_status_fields = [];
    foreach ($columns as $column) {
        if (preg_match('/^status(\d+)$/', $column->name)) {
            $existing_status_fields[] = $column->name;
        }
    }

    if (count($existing_status_fields) > 0) {
        echo "<p style='color: orange;'>⚠️ 이미 존재하는 status 필드: " . implode(', ', $existing_status_fields) . "</p>";
        echo "<p>기존 필드를 유지하고 누락된 필드만 추가합니다.</p>";
    }

    // status01 ~ status16 필드 추가
    $added_count = 0;
    $skipped_count = 0;

    for ($i = 1; $i <= 16; $i++) {
        $field_name = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...

        // 필드가 이미 존재하는지 확인
        if (in_array($field_name, $existing_status_fields)) {
            echo "<p>⏭️ 필드 <b>$field_name</b>은(는) 이미 존재합니다. 건너뜁니다.</p>";
            $skipped_count++;
            continue;
        }

        // 필드 추가 (VARCHAR(20), NULL 허용)
        $sql = "ALTER TABLE mdl_abessi_todayplans ADD COLUMN $field_name VARCHAR(20) DEFAULT NULL COMMENT '상태: 만족/매우만족/불만족'";

        try {
            $DB->execute($sql);
            echo "<p style='color: green;'>✅ 필드 <b>$field_name</b> 추가 완료</p>";
            $added_count++;
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ ERROR: 필드 <b>$field_name</b> 추가 실패 - " . $e->getMessage() . "</p>";
            echo "<p style='color: red;'>파일 위치: " . __FILE__ . ":49</p>";
        }
    }

    echo "<hr>";
    echo "<h3>마이그레이션 완료</h3>";
    echo "<p>추가된 필드: <b>$added_count</b>개</p>";
    echo "<p>건너뛴 필드: <b>$skipped_count</b>개</p>";

    // 최종 컬럼 목록 확인
    echo "<h3>최종 status 필드 목록</h3>";
    $final_columns = $DB->get_columns('abessi_todayplans');
    $final_status_fields = [];
    foreach ($final_columns as $column) {
        if (preg_match('/^status(\d+)$/', $column->name)) {
            $final_status_fields[] = $column->name;
        }
    }
    sort($final_status_fields);
    echo "<ul>";
    foreach ($final_status_fields as $field) {
        echo "<li>$field</li>";
    }
    echo "</ul>";

    echo "<p style='color: green; font-weight: bold;'>✅ 마이그레이션이 성공적으로 완료되었습니다!</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: 마이그레이션 실패 - " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>파일 위치: " . __FILE__ . ":73</p>";
}

echo "<hr>";
echo "<p><a href='todayplans.php'>← todayplans.php로 돌아가기</a></p>";
?>
