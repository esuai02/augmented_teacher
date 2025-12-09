<?php
/**
 * 새 테이블 직접 생성 스크립트
 * 한 번에 실행하는 간단한 버전
 */

require_once __DIR__ . '/plugin_db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>새 테이블 생성</h1>";
    echo "<pre>";
    
    // 백업 테이블 생성 (이미 있으면 무시)
    try {
        $sql1 = "CREATE TABLE IF NOT EXISTS mdl_alt42DB_card_plugin_settings_backup AS 
                SELECT * FROM mdl_alt42DB_card_plugin_settings";
        $pdo->exec($sql1);
        echo "✓ 백업 테이블 생성 완료\n";
    } catch (PDOException $e) {
        echo "- 백업 테이블 생성 건너뜀: " . $e->getMessage() . "\n";
    }
    
    // 기존 새 테이블 삭제
    try {
        $sql2 = "DROP TABLE IF EXISTS mdl_alt42DB_card_plugin_settings_new";
        $pdo->exec($sql2);
        echo "✓ 기존 새 테이블 삭제 완료\n";
    } catch (PDOException $e) {
        echo "- 테이블 삭제 오류: " . $e->getMessage() . "\n";
    }
    
    // 새 테이블 생성
    $sql3 = "CREATE TABLE mdl_alt42DB_card_plugin_settings_new (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL COMMENT '사용자 ID',
        category VARCHAR(50) NOT NULL COMMENT '카테고리',
        card_title VARCHAR(255) NOT NULL COMMENT '카드 제목',
        card_index INT DEFAULT 0 COMMENT '카드 인덱스',
        plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
        
        -- 공통 필드
        plugin_name VARCHAR(255) DEFAULT NULL COMMENT '플러그인 이름',
        card_description TEXT DEFAULT NULL COMMENT '카드 설명',
        
        -- internal_link 전용 필드
        internal_url VARCHAR(500) DEFAULT NULL COMMENT '내부 URL',
        
        -- external_link 전용 필드
        external_url VARCHAR(500) DEFAULT NULL COMMENT '외부 URL',
        
        -- link 공통 필드
        open_new_tab TINYINT(1) DEFAULT 0 COMMENT '새 탭에서 열기',
        
        -- send_message 전용 필드
        message_content TEXT DEFAULT NULL COMMENT '메시지 내용',
        message_type VARCHAR(50) DEFAULT NULL COMMENT '메시지 타입',
        
        -- agent 전용 필드
        agent_type VARCHAR(50) DEFAULT NULL COMMENT '에이전트 타입',
        agent_code TEXT DEFAULT NULL COMMENT 'PHP 코드',
        agent_url VARCHAR(500) DEFAULT NULL COMMENT '에이전트 URL',
        agent_prompt TEXT DEFAULT NULL COMMENT '에이전트 프롬프트',
        agent_parameters TEXT DEFAULT NULL COMMENT '에이전트 파라미터',
        agent_description TEXT DEFAULT NULL COMMENT '에이전트 설명',
        
        -- 에이전트 설정
        agent_config_title VARCHAR(255) DEFAULT NULL COMMENT '설정 제목',
        agent_config_description TEXT DEFAULT NULL COMMENT '설정 설명',
        agent_config_details TEXT DEFAULT NULL COMMENT '설정 상세',
        agent_config_action VARCHAR(100) DEFAULT NULL COMMENT '설정 액션',
        
        -- 추가 메타데이터
        extra_config TEXT DEFAULT NULL COMMENT '추가 설정',
        
        -- 시스템 필드
        is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
        display_order INT DEFAULT 0 COMMENT '표시 순서',
        timecreated INT UNSIGNED DEFAULT 0 COMMENT '생성 시간',
        timemodified INT UNSIGNED DEFAULT 0 COMMENT '수정 시간',
        
        KEY idx_user_id (user_id),
        KEY idx_category (category),
        KEY idx_plugin_id (plugin_id),
        UNIQUE KEY idx_unique_card (user_id, category, card_title, card_index)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카드 플러그인 설정 테이블 (정규화된 버전)'";
    
    $pdo->exec($sql3);
    echo "✓ 새 테이블 생성 완료\n";
    
    // 테이블 존재 확인
    $check = $pdo->query("SHOW TABLES LIKE 'mdl_alt42DB_card_plugin_settings_new'")->fetch();
    if ($check) {
        echo "\n✓ 테이블이 성공적으로 생성되었습니다!\n";
        
        // 컬럼 수 확인
        $columns = $pdo->query("DESCRIBE mdl_alt42DB_card_plugin_settings_new")->fetchAll();
        echo "✓ 총 " . count($columns) . "개의 컬럼이 생성되었습니다.\n";
        
        echo "\n<a href='migration_manager.php'>마이그레이션 관리자로 돌아가기</a>";
    } else {
        echo "\n✗ 테이블 생성에 실패했습니다.\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>오류 발생</h1>";
    echo "<pre>";
    echo $e->getMessage();
    echo "</pre>";
}
?>