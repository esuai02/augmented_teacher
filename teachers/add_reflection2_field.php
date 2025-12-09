<?php
/**
 * mdl_abessi_today 테이블에 reflection2 필드 추가 스크립트
 * 파일: /teachers/add_reflection2_field.php
 *
 * 이 스크립트는 mdl_abessi_today 테이블에 reflection2 필드를 추가합니다.
 * reflection2 필드는 1~5 값을 저장합니다:
 * 1 = 길을 잃음
 * 2 = 산만함
 * 3 = 성실함
 * 4 = 매우 성실
 * 5 = 열정적
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_login();

echo "<h2>mdl_abessi_today 테이블에 reflection2 필드 추가</h2>";

try {
    // 1. 먼저 테이블 구조 확인
    echo "<h3>1. 현재 테이블 구조 확인...</h3>";
    $columns = $DB->get_columns('abessi_today');

    $hasReflection2 = false;
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column->name . " (" . $column->type . ")</li>";
        if ($column->name === 'reflection2') {
            $hasReflection2 = true;
        }
    }
    echo "</ul>";

    // 2. reflection2 필드가 없으면 추가
    if (!$hasReflection2) {
        echo "<h3>2. reflection2 필드 추가 중...</h3>";

        $sql = "ALTER TABLE {abessi_today} ADD COLUMN reflection2 TINYINT(1) DEFAULT NULL COMMENT '학습태도평가 (1=길을잃음, 2=산만함, 3=성실함, 4=매우성실, 5=열정적)'";

        try {
            $DB->execute($sql);
            echo "<p style='color: green;'>✓ reflection2 필드가 성공적으로 추가되었습니다.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ 필드 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>파일: " . __FILE__ . ", 라인: " . $e->getLine() . "</p>";
        }
    } else {
        echo "<h3>2. reflection2 필드 확인</h3>";
        echo "<p style='color: blue;'>ℹ reflection2 필드가 이미 존재합니다. 추가 작업이 필요하지 않습니다.</p>";
    }

    // 3. 변경 후 테이블 구조 재확인
    echo "<h3>3. 변경 후 테이블 구조 확인...</h3>";
    $columnsAfter = $DB->get_columns('abessi_today');

    echo "<ul>";
    foreach ($columnsAfter as $column) {
        $highlight = ($column->name === 'reflection2') ? " style='color: green; font-weight: bold;'" : "";
        echo "<li$highlight>" . $column->name . " (" . $column->type . ")";
        if ($column->name === 'reflection2') {
            echo " ← 새로 추가된 필드";
        }
        echo "</li>";
    }
    echo "</ul>";

    // 4. 샘플 데이터 확인
    echo "<h3>4. 샘플 데이터 확인 (최근 5개)</h3>";
    $samples = $DB->get_records_sql("SELECT * FROM {abessi_today} ORDER BY id DESC LIMIT 5");

    if ($samples) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>userid</th><th>type</th><th>text</th><th>reflection2</th><th>timecreated</th>";
        echo "</tr>";

        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . $sample->id . "</td>";
            echo "<td>" . $sample->userid . "</td>";
            echo "<td>" . htmlspecialchars($sample->type) . "</td>";
            echo "<td>" . htmlspecialchars(substr($sample->text ?? '', 0, 30)) . "...</td>";
            echo "<td style='font-weight: bold; color: " . ($sample->reflection2 ? "green" : "gray") . ";'>";
            echo ($sample->reflection2 ?? 'NULL');
            echo "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $sample->timecreated ?? 0) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>샘플 데이터가 없습니다.</p>";
    }

    echo "<hr>";
    echo "<h3>작업 완료!</h3>";
    echo "<p><a href='timescaffolding.php'>← timescaffolding.php로 돌아가기</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>에러 발생!</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>파일: " . __FILE__ . "</p>";
    echo "<p>라인: " . $e->getLine() . "</p>";
}
?>
