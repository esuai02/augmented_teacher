<?php
// 테이블 제약 조건 확인
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;
require_login();

echo "<h2>테이블 제약 조건 및 인덱스 확인</h2>";

// 데이터베이스 타입 확인
echo "<h3>데이터베이스 정보</h3>";
echo "DB Type: " . $CFG->dbtype . "<br>";
echo "DB Name: " . $CFG->dbname . "<br><br>";

// MySQL/MariaDB의 경우 SHOW CREATE TABLE 사용
if ($CFG->dbtype === 'mysqli' || $CFG->dbtype === 'mariadb') {
    $tables = ['mdl_alt42t_users', 'mdl_alt42t_exams', 'mdl_alt42t_exam_dates', 'mdl_alt42t_study_status'];
    
    foreach ($tables as $table) {
        echo "<h3>$table 구조</h3>";
        
        try {
            // SHOW CREATE TABLE
            $sql = "SHOW CREATE TABLE $table";
            $result = $DB->get_record_sql($sql);
            
            if ($result) {
                $create_sql = $result->{'create table'} ?? $result->{'Create Table'} ?? '';
                echo "<pre>" . htmlspecialchars($create_sql) . "</pre>";
            }
            
            // 인덱스 확인
            echo "<h4>인덱스</h4>";
            $sql = "SHOW INDEX FROM $table";
            $indexes = $DB->get_records_sql($sql);
            
            if ($indexes) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>";
                foreach ($indexes as $idx) {
                    echo "<tr>";
                    echo "<td>" . ($idx->key_name ?? $idx->Key_name ?? '') . "</td>";
                    echo "<td>" . ($idx->column_name ?? $idx->Column_name ?? '') . "</td>";
                    echo "<td>" . (($idx->non_unique ?? $idx->Non_unique ?? 1) ? 'NO' : 'YES') . "</td>";
                    echo "<td>" . ($idx->index_type ?? $idx->Index_type ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (Exception $e) {
            echo "오류: " . $e->getMessage() . "<br>";
        }
        
        echo "<br>";
    }
}

// 실제 데이터 샘플 확인
echo "<h3>데이터 샘플</h3>";

// alt42t_users 샘플
echo "<h4>mdl_alt42t_users (최근 5개)</h4>";
$users = $DB->get_records_sql("
    SELECT * FROM {alt42t_users} 
    ORDER BY timemodified DESC 
    LIMIT 5
");

if ($users) {
    echo "<table border='1' cellpadding='5'>";
    $first = true;
    foreach ($users as $user) {
        if ($first) {
            echo "<tr>";
            foreach ($user as $key => $value) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($user as $value) {
            echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// 중복 확인
echo "<h3>중복 데이터 확인</h3>";

// userid 중복 확인
$duplicates = $DB->get_records_sql("
    SELECT userid, COUNT(*) as cnt 
    FROM {alt42t_users} 
    GROUP BY userid 
    HAVING COUNT(*) > 1
");

if ($duplicates) {
    echo "⚠️ userid 중복 발견:<br>";
    foreach ($duplicates as $dup) {
        echo "User ID: {$dup->userid} - {$dup->cnt}개<br>";
    }
} else {
    echo "✅ userid 중복 없음<br>";
}

echo "<br><a href='exam_preparation_system.php'>돌아가기</a>";
?>