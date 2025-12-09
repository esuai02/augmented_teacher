<?php
/**
 * SQL 파일 실행 스크립트
 * 사용법: php execute_sql_file.php [sql파일명]
 */

require_once __DIR__ . '/plugin_db_config.php';

// 명령줄 인자 확인
$sqlFile = $argv[1] ?? 'create_new_card_plugin_settings_table.sql';

if (!file_exists($sqlFile)) {
    echo "오류: SQL 파일을 찾을 수 없습니다: $sqlFile\n";
    exit(1);
}

try {
    $pdo = getDBConnection();
    
    echo "SQL 파일 실행: $sqlFile\n";
    echo "=======================\n\n";
    
    // SQL 파일 읽기
    $sql = file_get_contents($sqlFile);
    
    // 주석 제거 및 쿼리 분리
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // 세미콜론으로 쿼리 분리
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            echo "실행: " . substr($query, 0, 50) . "...\n";
            $pdo->exec($query);
            $successCount++;
            echo "✓ 성공\n\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ 실패: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "=======================\n";
    echo "실행 완료\n";
    echo "성공: $successCount 개\n";
    echo "실패: $errorCount 개\n";
    
    // 테이블 확인
    if (strpos($sqlFile, 'create_new_card_plugin_settings_table') !== false) {
        echo "\n테이블 생성 확인:\n";
        $checkTable = $pdo->query("SHOW TABLES LIKE 'mdl_alt42DB_card_plugin_settings_new'")->fetch();
        if ($checkTable) {
            echo "✓ mdl_alt42DB_card_plugin_settings_new 테이블이 생성되었습니다.\n";
            
            // 컬럼 수 확인
            $columns = $pdo->query("DESCRIBE mdl_alt42DB_card_plugin_settings_new")->fetchAll();
            echo "✓ 총 " . count($columns) . "개의 컬럼이 생성되었습니다.\n";
        } else {
            echo "✗ 테이블이 생성되지 않았습니다.\n";
        }
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}
?>