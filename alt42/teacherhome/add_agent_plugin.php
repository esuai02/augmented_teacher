<?php
/**
 * 에이전트 플러그인 타입 추가 스크립트
 * 작성일: 2025-01-18
 * 설명: mdl_alt42DB_plugin_types 테이블에 에이전트 플러그인 타입을 추가합니다.
 */

// 데이터베이스 설정
require_once(__DIR__ . '/plugin_db_config.php');

try {
    // 데이터베이스 연결
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO($dsn, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "데이터베이스 연결 성공\n";
    
    // 이미 에이전트 플러그인이 있는지 확인
    $checkSql = "SELECT COUNT(*) FROM mdl_alt42DB_plugin_types WHERE plugin_id = 'agent'";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    
    if ($count > 0) {
        echo "에이전트 플러그인 타입이 이미 존재합니다.\n";
    } else {
        // 에이전트 플러그인 추가
        $insertSql = "INSERT INTO mdl_alt42DB_plugin_types 
                      (plugin_id, plugin_title, plugin_icon, plugin_description, plugin_type, is_active, timecreated, timemodified) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($insertSql);
        $current_time = time();
        
        $result = $stmt->execute([
            'agent',                                        // plugin_id
            '에이전트',                                     // plugin_title
            '🤖',                                          // plugin_icon
            'URL 또는 PHP 코드를 실행하는 에이전트',        // plugin_description
            'agent',                                        // plugin_type
            1,                                              // is_active
            $current_time,                                  // timecreated
            $current_time                                   // timemodified
        ]);
        
        if ($result) {
            echo "에이전트 플러그인 타입이 성공적으로 추가되었습니다.\n";
        } else {
            echo "에이전트 플러그인 타입 추가 실패\n";
        }
    }
    
    // 모든 플러그인 타입 확인
    echo "\n현재 등록된 플러그인 타입:\n";
    $listSql = "SELECT plugin_id, plugin_title, plugin_icon, plugin_description FROM mdl_alt42DB_plugin_types WHERE is_active = 1 ORDER BY plugin_id";
    $listStmt = $db->prepare($listSql);
    $listStmt->execute();
    
    while ($row = $listStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['plugin_icon']} {$row['plugin_title']} ({$row['plugin_id']}): {$row['plugin_description']}\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}

echo "\n스크립트 실행 완료\n";
?>