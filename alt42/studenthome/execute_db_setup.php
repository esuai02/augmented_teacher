<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

// 실행할 SQL 파일들
$sql_files = array(
    'create_user_selections_table.sql',
    'create_recent_courses_table.sql'
);

foreach ($sql_files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file<br>\n";
        continue;
    }
    
    echo "<h3>Executing: $file</h3>\n";
    
    // SQL 파일 읽기
    $sql = file_get_contents($file);
    
    // SQL 문을 개별적으로 실행
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $DB->execute($query);
                echo "✓ Query executed successfully: " . substr($query, 0, 50) . "...<br>\n";
            } catch (Exception $e) {
                echo "✗ Error executing query: " . $e->getMessage() . "<br>\n";
            }
        }
    }
    echo "<br>\n";
}

echo "<br><strong>Database setup completed!</strong>";
?>