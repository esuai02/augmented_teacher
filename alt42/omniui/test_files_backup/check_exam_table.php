<?php
// alt42t_exams 테이블 구조 확인
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

echo "<h2>alt42t_exams 테이블 구조 확인</h2>";

// 1. 테이블 구조 확인
echo "<h3>1. 테이블 컬럼 정보</h3>";
try {
    $columns = $DB->get_columns('alt42t_exams');
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL 허용</th><th>키</th><th>기본값</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col->name}</td>";
        echo "<td>{$col->type}</td>";
        echo "<td>" . ($col->not_null ? 'NO' : 'YES') . "</td>";
        echo "<td>" . ($col->primary_key ? 'PRIMARY' : '') . "</td>";
        echo "<td>{$col->default_value}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// 2. PRIMARY KEY 확인
echo "<h3>2. PRIMARY KEY 정보</h3>";
try {
    $sql = "SHOW KEYS FROM {alt42t_exams} WHERE Key_name = 'PRIMARY'";
    $keys = $DB->get_records_sql($sql);
    if ($keys) {
        foreach ($keys as $key) {
            echo "Primary Key Column: " . ($key->column_name ?? $key->Column_name ?? 'unknown') . "<br>";
        }
    } else {
        echo "PRIMARY KEY를 찾을 수 없습니다.<br>";
    }
} catch (Exception $e) {
    echo "키 조회 오류: " . $e->getMessage() . "<br>";
}

// 3. 샘플 데이터
echo "<h3>3. 샘플 데이터 (최근 5개)</h3>";
try {
    $samples = $DB->get_records_sql("
        SELECT * FROM {alt42t_exams} 
        ORDER BY timemodified DESC 
        LIMIT 5
    ");
    
    if ($samples) {
        echo "<table border='1' cellpadding='5'>";
        $first = true;
        foreach ($samples as $sample) {
            if ($first) {
                echo "<tr>";
                foreach ($sample as $key => $value) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($sample as $value) {
                echo "<td>" . htmlspecialchars(substr($value ?? '', 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "데이터 없음<br>";
    }
} catch (Exception $e) {
    echo "데이터 조회 오류: " . $e->getMessage() . "<br>";
}

// 4. UPDATE 테스트
echo "<h3>4. UPDATE 쿼리 테스트</h3>";
$test_exam = $DB->get_record('alt42t_exams', array(), '*', IGNORE_MULTIPLE);
if ($test_exam) {
    echo "테스트할 레코드 찾음:<br>";
    echo "exam_id: " . ($test_exam->exam_id ?? 'NULL') . "<br>";
    echo "id: " . ($test_exam->id ?? 'NULL') . "<br>";
    
    // exam_id로 UPDATE 시도
    if (isset($test_exam->exam_id)) {
        try {
            $sql = "UPDATE {alt42t_exams} 
                    SET timemodified = :timemodified
                    WHERE exam_id = :exam_id";
            $params = array(
                'timemodified' => time(),
                'exam_id' => $test_exam->exam_id
            );
            $DB->execute($sql, $params);
            echo "✅ exam_id 기반 UPDATE 성공<br>";
        } catch (Exception $e) {
            echo "❌ exam_id 기반 UPDATE 실패: " . $e->getMessage() . "<br>";
        }
    }
    
    // id로 UPDATE 시도
    if (isset($test_exam->id)) {
        try {
            $sql = "UPDATE {alt42t_exams} 
                    SET timemodified = :timemodified
                    WHERE id = :id";
            $params = array(
                'timemodified' => time(),
                'id' => $test_exam->id
            );
            $DB->execute($sql, $params);
            echo "✅ id 기반 UPDATE 성공<br>";
        } catch (Exception $e) {
            echo "❌ id 기반 UPDATE 실패: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "테스트할 레코드가 없습니다.<br>";
}

echo "<br><a href='exam_preparation_system.php'>돌아가기</a>";
?>