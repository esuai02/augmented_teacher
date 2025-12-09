<?php
/**
 * 새 테이블 존재 여부 확인 스크립트
 */

require_once __DIR__ . '/plugin_db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "데이터베이스 연결 성공\n\n";
    
    // 테이블 존재 확인
    $tables = [
        'mdl_alt42DB_card_plugin_settings' => '기존 테이블',
        'mdl_alt42DB_card_plugin_settings_new' => '새 테이블',
        'mdl_alt42DB_card_plugin_settings_old' => '백업 테이블',
        'mdl_alt42DB_card_plugin_settings_backup' => '백업 테이블2'
    ];
    
    echo "=== 테이블 존재 확인 ===\n";
    foreach ($tables as $table => $desc) {
        $sql = "SHOW TABLES LIKE '$table'";
        $result = $pdo->query($sql)->fetch();
        echo "- $desc ($table): " . ($result ? "✓ 존재" : "✗ 없음") . "\n";
    }
    
    // 새 테이블이 없으면 생성
    $checkNewTable = $pdo->query("SHOW TABLES LIKE 'mdl_alt42DB_card_plugin_settings_new'")->fetch();
    if (!$checkNewTable) {
        echo "\n새 테이블이 없습니다. 생성을 시작합니다...\n";
        
        // SQL 파일 실행
        $sqlFile = file_get_contents(__DIR__ . '/create_new_card_plugin_settings_table.sql');
        $pdo->exec($sqlFile);
        
        echo "✓ 새 테이블이 생성되었습니다.\n";
    } else {
        echo "\n✓ 새 테이블이 이미 존재합니다.\n";
        
        // 테이블 구조 확인
        echo "\n=== 새 테이블 구조 ===\n";
        $columns = $pdo->query("DESCRIBE mdl_alt42DB_card_plugin_settings_new")->fetchAll();
        
        echo "컬럼 수: " . count($columns) . "개\n";
        echo "\n주요 컬럼:\n";
        $importantColumns = [
            'plugin_name', 'card_description', 'internal_url', 'external_url',
            'message_content', 'agent_type', 'agent_config_title'
        ];
        
        foreach ($columns as $col) {
            if (in_array($col['Field'], $importantColumns)) {
                echo "- {$col['Field']} ({$col['Type']})\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>