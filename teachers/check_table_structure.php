<?php
/**
 * mdl_abessi_today 테이블 구조 확인 스크립트
 * 파일: /teachers/check_table_structure.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

try {
    // 테이블 구조 확인
    $dbman = $DB->get_manager();

    // 테이블 존재 확인
    $tableName = 'abessi_today';

    echo "<h2>mdl_abessi_today 테이블 구조 확인</h2>";

    // 샘플 데이터 조회
    $records = $DB->get_records_sql("SELECT * FROM {abessi_today} ORDER BY id DESC LIMIT 5");

    if ($records) {
        echo "<h3>샘플 데이터 (최근 5개):</h3>";
        echo "<pre>";
        foreach ($records as $record) {
            print_r($record);
            echo "\n---\n";
        }
        echo "</pre>";

        // 첫 번째 레코드로부터 필드 정보 추출
        $firstRecord = reset($records);
        echo "<h3>테이블 필드 목록:</h3>";
        echo "<ul>";
        foreach ($firstRecord as $field => $value) {
            $type = gettype($value);
            echo "<li><strong>$field</strong>: $type";
            if ($value !== null && $value !== '') {
                echo " (예시: " . htmlspecialchars(substr($value, 0, 50)) . ")";
            }
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>테이블에 데이터가 없습니다.</p>";

        // DESCRIBE 쿼리로 테이블 구조 확인
        echo "<h3>DESCRIBE 결과:</h3>";
        try {
            $structure = $DB->get_records_sql("DESCRIBE {abessi_today}");
            echo "<pre>";
            print_r($structure);
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>위치: " . __FILE__ . ":" . __LINE__ . "</p>";
        }
    }

} catch (Exception $e) {
    echo "<h3>에러 발생:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>파일: " . __FILE__ . "</p>";
    echo "<p>라인: " . $e->getLine() . "</p>";
}
?>
