<?php
/**
 * 플러그인 설정 데이터 마이그레이션 스크립트
 * plugin_config JSON 필드를 개별 컬럼으로 분리
 */

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 데이터베이스 설정 포함
require_once __DIR__ . '/plugin_db_config.php';

try {
    // 데이터베이스 연결
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "데이터베이스 연결 성공\n";
    echo "===================\n\n";
    
    // 0. 새 테이블 존재 확인
    $checkNewTable = $pdo->query("SHOW TABLES LIKE 'mdl_alt42DB_card_plugin_settings_new'")->fetch();
    if (!$checkNewTable) {
        echo "새 테이블이 없습니다. 먼저 테이블을 생성해야 합니다.\n";
        echo "create_new_card_plugin_settings_table.sql 파일을 실행하세요.\n";
        exit(1);
    }
    
    // 1. 기존 데이터 조회
    $sql = "SELECT * FROM mdl_alt42DB_card_plugin_settings ORDER BY id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $oldRecords = $stmt->fetchAll();
    
    echo "총 " . count($oldRecords) . "개의 레코드를 마이그레이션합니다.\n\n";
    
    // 2. 새 테이블에 데이터 삽입
    $insertSql = "INSERT INTO mdl_alt42DB_card_plugin_settings_new (
        id, user_id, category, card_title, card_index, plugin_id,
        plugin_name, card_description,
        internal_url, external_url, open_new_tab,
        message_content, message_type,
        agent_type, agent_code, agent_url, agent_prompt, agent_parameters, agent_description,
        agent_config_title, agent_config_description, agent_config_details, agent_config_action,
        extra_config,
        is_active, display_order, timecreated, timemodified
    ) VALUES (
        :id, :user_id, :category, :card_title, :card_index, :plugin_id,
        :plugin_name, :card_description,
        :internal_url, :external_url, :open_new_tab,
        :message_content, :message_type,
        :agent_type, :agent_code, :agent_url, :agent_prompt, :agent_parameters, :agent_description,
        :agent_config_title, :agent_config_description, :agent_config_details, :agent_config_action,
        :extra_config,
        :is_active, :display_order, :timecreated, :timemodified
    )";
    
    $insertStmt = $pdo->prepare($insertSql);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($oldRecords as $record) {
        try {
            // plugin_config JSON 파싱
            $config = json_decode($record['plugin_config'], true) ?: [];
            
            // 에이전트 설정 추출
            $agentConfig = $config['agent_config'] ?? [];
            
            // 파라미터 준비
            $params = [
                ':id' => $record['id'],
                ':user_id' => $record['user_id'],
                ':category' => $record['category'],
                ':card_title' => $record['card_title'],
                ':card_index' => $record['card_index'],
                ':plugin_id' => $record['plugin_id'],
                
                // 공통 필드
                ':plugin_name' => $config['plugin_name'] ?? null,
                ':card_description' => $config['card_description'] ?? $config['description'] ?? null,
                
                // internal_link
                ':internal_url' => $config['internal_url'] ?? null,
                
                // external_link
                ':external_url' => $config['external_url'] ?? null,
                
                // link 공통
                ':open_new_tab' => isset($config['open_new_tab']) ? (int)$config['open_new_tab'] : 0,
                
                // send_message
                ':message_content' => $config['message_content'] ?? null,
                ':message_type' => $config['message_type'] ?? null,
                
                // agent
                ':agent_type' => $config['agent_type'] ?? null,
                ':agent_code' => $config['agent_code'] ?? null,
                ':agent_url' => $config['agent_url'] ?? null,
                ':agent_prompt' => $config['agent_prompt'] ?? null,
                ':agent_parameters' => isset($config['agent_parameters']) ? 
                    (is_string($config['agent_parameters']) ? $config['agent_parameters'] : json_encode($config['agent_parameters'])) : null,
                ':agent_description' => $config['agent_description'] ?? null,
                
                // agent_config 필드들
                ':agent_config_title' => $agentConfig['title'] ?? null,
                ':agent_config_description' => $agentConfig['description'] ?? null,
                ':agent_config_details' => isset($agentConfig['details']) ? 
                    (is_string($agentConfig['details']) ? $agentConfig['details'] : json_encode($agentConfig['details'])) : null,
                ':agent_config_action' => $agentConfig['action'] ?? null,
                
                // 나머지 설정
                ':extra_config' => null, // 필요시 나머지 설정 저장
                
                // 시스템 필드
                ':is_active' => $record['is_active'],
                ':display_order' => $record['display_order'],
                ':timecreated' => $record['timecreated'],
                ':timemodified' => $record['timemodified']
            ];
            
            $insertStmt->execute($params);
            $successCount++;
            
            echo "✓ ID {$record['id']} - {$record['card_title']} ({$record['plugin_id']}) 마이그레이션 완료\n";
            
        } catch (Exception $e) {
            $errorCount++;
            echo "✗ ID {$record['id']} 마이그레이션 실패: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n===================\n";
    echo "마이그레이션 완료\n";
    echo "성공: {$successCount}개\n";
    echo "실패: {$errorCount}개\n";
    
    // 3. 데이터 검증
    echo "\n데이터 검증 중...\n";
    
    // 원본 레코드 수
    $originalCount = $pdo->query("SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings")->fetchColumn();
    $newCount = $pdo->query("SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings_new")->fetchColumn();
    
    echo "원본 레코드 수: {$originalCount}\n";
    echo "마이그레이션된 레코드 수: {$newCount}\n";
    
    if ($originalCount == $newCount) {
        echo "✓ 레코드 수가 일치합니다.\n";
    } else {
        echo "✗ 레코드 수가 일치하지 않습니다!\n";
    }
    
    // 4. 샘플 데이터 비교
    echo "\n샘플 데이터 확인:\n";
    $sampleSql = "SELECT 
        n.id,
        n.plugin_id,
        n.plugin_name,
        n.card_description,
        CASE 
            WHEN n.plugin_id = 'internal_link' THEN n.internal_url
            WHEN n.plugin_id = 'external_link' THEN n.external_url
            WHEN n.plugin_id = 'send_message' THEN n.message_content
            WHEN n.plugin_id = 'agent' THEN CONCAT(n.agent_type, ': ', LEFT(n.agent_description, 50))
        END as main_content
    FROM mdl_alt42DB_card_plugin_settings_new n
    LIMIT 5";
    
    $sampleStmt = $pdo->query($sampleSql);
    while ($row = $sampleStmt->fetch()) {
        echo "- [{$row['plugin_id']}] {$row['plugin_name']}: {$row['main_content']}\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n완료!\n";
echo "다음 단계: 데이터 검증 후 테이블 교체 실행\n";
echo "RENAME TABLE mdl_alt42DB_card_plugin_settings TO mdl_alt42DB_card_plugin_settings_old;\n";
echo "RENAME TABLE mdl_alt42DB_card_plugin_settings_new TO mdl_alt42DB_card_plugin_settings;\n";
?>