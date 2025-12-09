<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 보고 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>abessi_stickynotes 테이블 구조 확인</h2>";

try {
    // 테이블 존재 여부 확인
    $table_exists = $DB->get_manager()->table_exists('abessi_stickynotes');
    echo "<p>테이블 존재 여부: " . ($table_exists ? '존재함' : '존재하지 않음') . "</p>";
    
    if ($table_exists) {
        // 테이블의 첫 번째 레코드 확인 (구조 파악용)
        $sample_record = $DB->get_record_sql("SELECT * FROM {abessi_stickynotes} LIMIT 1");
        
        if ($sample_record) {
            echo "<h3>샘플 레코드 구조:</h3>";
            echo "<pre>";
            print_r($sample_record);
            echo "</pre>";
        } else {
            echo "<p>테이블에 데이터가 없습니다.</p>";
        }
        
        // MySQL의 경우 DESCRIBE 명령어로 테이블 구조 확인
        try {
            $table_info = $DB->get_records_sql("DESCRIBE mdl_abessi_stickynotes");
            if ($table_info) {
                echo "<h3>테이블 구조 (DESCRIBE):</h3>";
                echo "<table border='1'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                foreach ($table_info as $field) {
                    echo "<tr>";
                    echo "<td>" . $field->field . "</td>";
                    echo "<td>" . $field->type . "</td>";
                    echo "<td>" . $field->null . "</td>";
                    echo "<td>" . $field->key . "</td>";
                    echo "<td>" . $field->default . "</td>";
                    echo "<td>" . $field->extra . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p>DESCRIBE 실행 실패: " . $e->getMessage() . "</p>";
        }
        
        // 테이블의 레코드 수 확인
        $count = $DB->count_records('abessi_stickynotes');
        echo "<p>총 레코드 수: " . $count . "</p>";
        
        // 최근 레코드 몇 개 확인
        $recent_records = $DB->get_records_sql("SELECT * FROM {abessi_stickynotes} ORDER BY id DESC LIMIT 5");
        if ($recent_records) {
            echo "<h3>최근 5개 레코드:</h3>";
            echo "<pre>";
            print_r($recent_records);
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류 발생: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// POST 테스트 (실제 삽입은 하지 않음)
if (isset($_POST['test'])) {
    echo "<h3>POST 데이터 테스트:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>테이블 구조 디버깅</title>
</head>
<body>
    <form method="POST">
        <h3>POST 데이터 테스트</h3>
        <input type="hidden" name="test" value="1">
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="userid" value="123">
        <input type="hidden" name="type" value="edittoday">
        <input type="hidden" name="content" value="테스트 메모">
        <input type="hidden" name="created_at" value="<?php echo time(); ?>">
        <button type="submit">POST 데이터 테스트</button>
    </form>
</body>
</html> 