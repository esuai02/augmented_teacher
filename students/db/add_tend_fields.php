<?php
/**
 * DB Migration: Add tend01-tend16 fields to mdl_abessi_todayplans
 * File: students/db/add_tend_fields.php
 * Purpose: 각 일기 항목의 완료 시점(unixtime)을 기록하기 위한 필드 추가
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

 

echo "<h2>mdl_abessi_todayplans 테이블 마이그레이션</h2>";
echo "<p>tend01 ~ tend16 필드 추가 작업 시작...</p>";

try {
    $dbman = $DB->get_manager();
    $table = new xmldb_table('abessi_todayplans');

    $fieldsAdded = 0;
    $fieldsExisted = 0;

    // tend01 ~ tend16 필드 추가
    for ($i = 1; $i <= 16; $i++) {
        $fieldName = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT); // tend01, tend02, ...

        $field = new xmldb_field(
            $fieldName,
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            null
        );

        // 필드가 존재하지 않으면 추가
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            echo "<p style='color: green;'>✓ {$fieldName} 필드 추가 완료</p>";
            $fieldsAdded++;
        } else {
            echo "<p style='color: orange;'>⚠ {$fieldName} 필드 이미 존재</p>";
            $fieldsExisted++;
        }
    }

    echo "<hr>";
    echo "<h3>마이그레이션 완료</h3>";
    echo "<p><strong>추가된 필드:</strong> {$fieldsAdded}개</p>";
    echo "<p><strong>기존 필드:</strong> {$fieldsExisted}개</p>";
    echo "<p><strong>총 필드:</strong> " . ($fieldsAdded + $fieldsExisted) . "개</p>";

    // 테이블 구조 확인
    echo "<hr>";
    echo "<h3>테이블 구조 확인</h3>";

    $columns = $DB->get_columns('abessi_todayplans');
    $tendFields = array();

    foreach ($columns as $column) {
        if (strpos($column->name, 'tend') === 0) {
            $tendFields[] = $column->name . ' (' . $column->meta_type . ')';
        }
    }

    echo "<p><strong>tend 관련 필드 목록:</strong></p>";
    echo "<ul>";
    foreach ($tendFields as $field) {
        echo "<li>{$field}</li>";
    }
    echo "</ul>";

    // 샘플 데이터 확인
    echo "<hr>";
    echo "<h3>샘플 데이터 확인 (최근 5건)</h3>";

    $sampleData = $DB->get_records_sql(
        "SELECT id, userid, tbegin, tend01, tend02, tend03, timecreated, timemodified
         FROM {abessi_todayplans}
         ORDER BY id DESC
         LIMIT 5"
    );

    if ($sampleData) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>ID</th><th>UserID</th><th>tbegin</th><th>tend01</th><th>tend02</th><th>tend03</th>";
        echo "<th>Created</th><th>Modified</th>";
        echo "</tr>";

        foreach ($sampleData as $row) {
            echo "<tr>";
            echo "<td>{$row->id}</td>";
            echo "<td>{$row->userid}</td>";
            echo "<td>" . ($row->tbegin ? date('Y-m-d H:i:s', $row->tbegin) : '-') . "</td>";
            echo "<td>" . ($row->tend01 ?? '-') . "</td>";
            echo "<td>" . ($row->tend02 ?? '-') . "</td>";
            echo "<td>" . ($row->tend03 ?? '-') . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $row->timecreated) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $row->timemodified) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>샘플 데이터 없음</p>";
    }

    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✓ 마이그레이션 성공</p>";
    echo "<p><a href='../goals42.php?id={$USER->id}'>목표관리 페이지로 이동</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
    echo "<p>File: " . __FILE__ . ", Line: " . __LINE__ . "</p>";
    error_log("DB Migration Error: " . $e->getMessage() . " - File: " . __FILE__ . ", Line: " . __LINE__);
}
?>
