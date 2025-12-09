-- 새로운 카드 플러그인 설정 테이블 생성
-- 기존 plugin_config JSON 필드를 개별 컬럼으로 분리

-- 1. 기존 테이블 백업
CREATE TABLE IF NOT EXISTS mdl_alt42DB_card_plugin_settings_backup AS 
SELECT * FROM mdl_alt42DB_card_plugin_settings;

-- 2. 새로운 테이블 생성
DROP TABLE IF EXISTS mdl_alt42DB_card_plugin_settings_new;

CREATE TABLE mdl_alt42DB_card_plugin_settings_new (
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
    message_type VARCHAR(50) DEFAULT NULL COMMENT '메시지 타입 (success, info, warning, error)',
    
    -- agent 전용 필드
    agent_type VARCHAR(50) DEFAULT NULL COMMENT '에이전트 타입 (php, url, onboarding_item)',
    agent_code TEXT DEFAULT NULL COMMENT 'PHP 코드 또는 에이전트 로직',
    agent_url VARCHAR(500) DEFAULT NULL COMMENT '에이전트 URL',
    agent_prompt TEXT DEFAULT NULL COMMENT '에이전트 프롬프트',
    agent_parameters TEXT DEFAULT NULL COMMENT '에이전트 파라미터 (JSON)',
    agent_description TEXT DEFAULT NULL COMMENT '에이전트 설명',
    
    -- 에이전트 설정 (agent_config 내부 필드들)
    agent_config_title VARCHAR(255) DEFAULT NULL COMMENT '에이전트 설정 제목',
    agent_config_description TEXT DEFAULT NULL COMMENT '에이전트 설정 설명',
    agent_config_details TEXT DEFAULT NULL COMMENT '에이전트 설정 상세 (JSON 배열)',
    agent_config_action VARCHAR(100) DEFAULT NULL COMMENT '에이전트 액션',
    
    -- 추가 메타데이터
    extra_config TEXT DEFAULT NULL COMMENT '추가 설정 (JSON)',
    
    -- 시스템 필드
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_alt42DB_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_card_title (card_title),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_plugin_name (plugin_name),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_user_card_plugin (user_id, category, card_title, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카드별 플러그인 설정 (정규화된 버전)';

-- 3. 데이터 마이그레이션 스크립트 (별도 PHP 파일로 실행)
-- migrate_plugin_config_data.php 참조

-- 4. 뷰 생성 (호환성을 위한 가상 plugin_config 필드 제공)
CREATE OR REPLACE VIEW mdl_alt42DB_card_plugin_settings_view AS
SELECT 
    id,
    user_id,
    category,
    card_title,
    card_index,
    plugin_id,
    -- plugin_config를 JSON으로 재구성
    JSON_OBJECT(
        'plugin_name', plugin_name,
        'card_description', card_description,
        'internal_url', internal_url,
        'external_url', external_url,
        'open_new_tab', open_new_tab,
        'message_content', message_content,
        'message_type', message_type,
        'agent_type', agent_type,
        'agent_code', agent_code,
        'agent_url', agent_url,
        'agent_prompt', agent_prompt,
        'agent_parameters', agent_parameters,
        'agent_description', agent_description,
        'agent_config', IF(
            agent_config_title IS NOT NULL OR 
            agent_config_description IS NOT NULL OR 
            agent_config_details IS NOT NULL OR 
            agent_config_action IS NOT NULL,
            JSON_OBJECT(
                'title', agent_config_title,
                'description', agent_config_description,
                'details', agent_config_details,
                'action', agent_config_action
            ),
            NULL
        )
    ) AS plugin_config,
    is_active,
    display_order,
    timecreated,
    timemodified
FROM mdl_alt42DB_card_plugin_settings_new;

-- 5. 테이블 스위치를 위한 준비
-- 마이그레이션 완료 후 다음 명령 실행:
-- RENAME TABLE mdl_alt42DB_card_plugin_settings TO mdl_alt42DB_card_plugin_settings_old;
-- RENAME TABLE mdl_alt42DB_card_plugin_settings_new TO mdl_alt42DB_card_plugin_settings;