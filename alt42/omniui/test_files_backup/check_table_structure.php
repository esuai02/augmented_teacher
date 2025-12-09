<?php
// 테이블 구조 확인 스크립트

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB;

echo "<h2>alt42t 테이블 구조 확인</h2>";

// mdl_alt42t_exam_dates 테이블 구조 확인
echo "<h3>mdl_alt42t_exam_dates 테이블 컬럼</h3>";
try {
    $columns = $DB->get_records_sql("
        SELECT column_name, data_type, column_key, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'mdl_alt42t_exam_dates'
        ORDER BY ordinal_position
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>컬럼명</th><th>데이터 타입</th><th>키</th><th>NULL 허용</th><th>기본값</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col->column_name . "</td>";
        echo "<td>" . $col->data_type . "</td>";
        echo "<td>" . $col->column_key . "</td>";
        echo "<td>" . $col->is_nullable . "</td>";
        echo "<td>" . ($col->column_default ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // math_date 또는 math_exam_date 컬럼 확인
    $has_math_date = false;
    $has_math_exam_date = false;
    foreach ($columns as $col) {
        if ($col->column_name == 'math_date') $has_math_date = true;
        if ($col->column_name == 'math_exam_date') $has_math_exam_date = true;
    }
    
    echo "<br><br>";
    if ($has_math_date) {
        echo "✅ 'math_date' 컬럼이 존재합니다.<br>";
    }
    if ($has_math_exam_date) {
        echo "✅ 'math_exam_date' 컬럼이 존재합니다.<br>";
    }
    if (!$has_math_date && !$has_math_exam_date) {
        echo "❌ 'math_date' 또는 'math_exam_date' 컬럼이 없습니다.<br>";
        echo "다음 SQL을 실행하세요:<br>";
        echo "<pre>ALTER TABLE mdl_alt42t_exam_dates ADD COLUMN math_date DATE DEFAULT NULL AFTER end_date;</pre>";
    }
    
} catch (Exception $e) {
    echo "오류: " . $e->getMessage();
}

// 다른 테이블도 확인
echo "<br><h3>기타 alt42t 테이블 존재 여부</h3>";
$tables = array(
    'mdl_alt42t_users',
    'mdl_alt42t_exams',
    'mdl_alt42t_exam_resources',
    'mdl_alt42t_study_status'
);

foreach ($tables as $table) {
    try {
        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {" . str_replace('mdl_', '', $table) . "}");
        echo "✅ $table - 존재함 (레코드 수: $count)<br>";
    } catch (Exception $e) {
        echo "❌ $table - 테이블 없음<br>";
    }
}

// 샘플 데이터 조회
echo "<br><h3>mdl_alt42t_exam_dates 샘플 데이터 (최근 5개)</h3>";
try {
    $samples = $DB->get_records_sql("
        SELECT * FROM {alt42t_exam_dates} 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    if ($samples) {
        echo "<pre>";
        foreach ($samples as $sample) {
            print_r($sample);
        }
        echo "</pre>";
    } else {
        echo "데이터가 없습니다.";
    }
} catch (Exception $e) {
    echo "조회 오류: " . $e->getMessage();
}
?>