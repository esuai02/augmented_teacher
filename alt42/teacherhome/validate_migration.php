<?php
/**
 * 마이그레이션 검증 스크립트
 * 기존 테이블과 새 테이블의 데이터 일치성 확인
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/plugin_db_config.php';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "=== 마이그레이션 검증 시작 ===\n\n";
    
    // 1. 레코드 수 비교
    $oldCount = $pdo->query("SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings")->fetchColumn();
    $newCount = $pdo->query("SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings_new")->fetchColumn();
    
    echo "1. 레코드 수 검증\n";
    echo "   - 기존 테이블: {$oldCount}개\n";
    echo "   - 새 테이블: {$newCount}개\n";
    echo "   - 결과: " . ($oldCount == $newCount ? "✓ 일치" : "✗ 불일치") . "\n\n";
    
    // 2. 데이터 무결성 검증
    echo "2. 데이터 무결성 검증\n";
    
    $sql = "SELECT 
            o.id,
            o.user_id,
            o.category,
            o.card_title,
            o.plugin_id,
            o.plugin_config as old_config,
            n.plugin_name,
            n.card_description,
            n.internal_url,
            n.external_url,
            n.open_new_tab,
            n.message_content,
            n.message_type,
            n.agent_type,
            n.agent_code,
            n.agent_url,
            n.agent_prompt,
            n.agent_parameters,
            n.agent_description,
            n.agent_config_title,
            n.agent_config_description,
            n.agent_config_details,
            n.agent_config_action
        FROM mdl_alt42DB_card_plugin_settings o
        LEFT JOIN mdl_alt42DB_card_plugin_settings_new n ON o.id = n.id
        LIMIT 10";
    
    $stmt = $pdo->query($sql);
    $mismatchCount = 0;
    $sampleCount = 0;
    
    while ($row = $stmt->fetch()) {
        $sampleCount++;
        $oldConfig = json_decode($row['old_config'], true) ?: [];
        $hasError = false;
        $errors = [];
        
        // 플러그인별 검증
        switch ($row['plugin_id']) {
            case 'internal_link':
                if ($oldConfig['plugin_name'] != $row['plugin_name']) {
                    $errors[] = "plugin_name 불일치";
                    $hasError = true;
                }
                if ($oldConfig['internal_url'] != $row['internal_url']) {
                    $errors[] = "internal_url 불일치";
                    $hasError = true;
                }
                break;
                
            case 'external_link':
                if ($oldConfig['external_url'] != $row['external_url']) {
                    $errors[] = "external_url 불일치";
                    $hasError = true;
                }
                break;
                
            case 'send_message':
                if ($oldConfig['message_content'] != $row['message_content']) {
                    $errors[] = "message_content 불일치";
                    $hasError = true;
                }
                break;
                
            case 'agent':
                if ($oldConfig['agent_type'] != $row['agent_type']) {
                    $errors[] = "agent_type 불일치";
                    $hasError = true;
                }
                if (isset($oldConfig['agent_config'])) {
                    $oldAgentConfig = $oldConfig['agent_config'];
                    if (($oldAgentConfig['title'] ?? null) != $row['agent_config_title']) {
                        $errors[] = "agent_config.title 불일치";
                        $hasError = true;
                    }
                }
                break;
        }
        
        if ($hasError) {
            $mismatchCount++;
            echo "   ✗ ID {$row['id']}: " . implode(", ", $errors) . "\n";
        } else {
            echo "   ✓ ID {$row['id']}: 정상\n";
        }
    }
    
    echo "\n   검증 샘플: {$sampleCount}개 중 {$mismatchCount}개 불일치\n\n";
    
    // 3. NULL 값 체크
    echo "3. NULL 값 검증\n";
    
    $nullChecks = [
        'plugin_name' => "plugin_name IS NULL",
        'user_id' => "user_id IS NULL OR user_id = 0",
        'category' => "category IS NULL OR category = ''",
        'plugin_id' => "plugin_id IS NULL OR plugin_id = ''"
    ];
    
    foreach ($nullChecks as $field => $condition) {
        $count = $pdo->query("SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings_new WHERE {$condition}")->fetchColumn();
        echo "   - {$field} NULL/빈값: {$count}개\n";
    }
    
    echo "\n";
    
    // 4. 플러그인별 통계
    echo "4. 플러그인별 통계\n";
    
    $pluginStats = $pdo->query("
        SELECT plugin_id, COUNT(*) as cnt 
        FROM mdl_alt42DB_card_plugin_settings_new 
        GROUP BY plugin_id
    ")->fetchAll();
    
    foreach ($pluginStats as $stat) {
        echo "   - {$stat['plugin_id']}: {$stat['cnt']}개\n";
    }
    
    echo "\n=== 검증 완료 ===\n";
    
    if ($oldCount == $newCount && $mismatchCount == 0) {
        echo "\n✓ 마이그레이션이 성공적으로 완료되었습니다.\n";
        echo "테이블 전환을 진행해도 안전합니다.\n";
    } else {
        echo "\n✗ 마이그레이션에 문제가 있습니다.\n";
        echo "데이터를 확인하고 다시 시도하세요.\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>