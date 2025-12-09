<?php
// config.php 찾기
$config_paths = array(
    __DIR__ . '/../config.php',
    __DIR__ . '/../../config.php',
    '/home/moodle/public_html/moodle/config.php'
);

echo "<h3>Config.php 경로 확인</h3>";
foreach ($config_paths as $path) {
    echo "경로: $path - ";
    if (file_exists($path)) {
        echo "<span style='color:green'>존재함</span><br>";
        require_once($path);
        break;
    } else {
        echo "<span style='color:red'>없음</span><br>";
    }
}

echo "<h3>데이터베이스 연결 확인</h3>";
if (isset($DB)) {
    echo "<span style='color:green'>DB 연결됨</span><br>";
    
    // 테이블 존재 확인
    try {
        $table_exists = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_abessi_stickynotes'");
        if ($table_exists) {
            echo "<span style='color:green'>mdl_abessi_stickynotes 테이블 존재</span><br>";
            
            // 테이블 구조 확인
            $columns = $DB->get_columns('abessi_stickynotes');
            echo "<h4>테이블 컬럼:</h4><ul>";
            foreach ($columns as $column) {
                echo "<li>{$column->name} ({$column->type})</li>";
            }
            echo "</ul>";
            
            // 샘플 데이터 조회
            $sample = $DB->get_records_sql("SELECT * FROM mdl_abessi_stickynotes LIMIT 5");
            echo "<h4>샘플 데이터 (" . count($sample) . "개):</h4>";
            echo "<pre>";
            print_r($sample);
            echo "</pre>";
        } else {
            echo "<span style='color:red'>mdl_abessi_stickynotes 테이블 없음</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>오류: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span style='color:red'>DB 연결 안됨</span><br>";
}

echo "<h3>사용자 정보</h3>";
if (isset($USER)) {
    echo "사용자 ID: " . $USER->id . "<br>";
    echo "사용자명: " . $USER->username . "<br>";
} else {
    echo "<span style='color:red'>사용자 정보 없음</span><br>";
}
?> 