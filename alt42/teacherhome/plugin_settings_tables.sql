-- KTM 코파일럿 플러그인 설정 테이블들
-- 작성일: 2024-12-31
-- 설명: teacherhome/index.html에서 사용하는 플러그인 세부설정을 저장하기 위한 테이블

-- 1. 플러그인 기본 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_plugin_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_id VARCHAR(50) NOT NULL UNIQUE COMMENT '플러그인 ID (internal_link, external_link, send_message)',
    plugin_title VARCHAR(255) NOT NULL COMMENT '플러그인 제목',
    plugin_icon VARCHAR(10) NOT NULL COMMENT '플러그인 아이콘',
    plugin_description TEXT NOT NULL COMMENT '플러그인 설명',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='플러그인 기본 정보';

-- 2. 사용자별 플러그인 설정 테이블 (전역 설정)
CREATE TABLE IF NOT EXISTS mdl_ktm_user_plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID (moodle user id)',
    plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
    setting_name VARCHAR(255) NOT NULL COMMENT '설정명',
    setting_value TEXT DEFAULT NULL COMMENT '설정값 (JSON 형태)',
    category VARCHAR(50) DEFAULT NULL COMMENT '카테고리 (quarterly, weekly, daily, etc.)',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_ktm_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_category (category),
    INDEX idx_is_enabled (is_enabled),
    UNIQUE KEY unique_user_plugin_setting (user_id, plugin_id, setting_name, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자별 플러그인 설정';

-- 3. 카드별 플러그인 설정 테이블 (카드 특정 설정)
CREATE TABLE IF NOT EXISTS mdl_ktm_card_plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID (moodle user id)',
    category VARCHAR(50) NOT NULL COMMENT '카테고리 (quarterly, weekly, daily, etc.)',
    card_title VARCHAR(255) NOT NULL COMMENT '카드 제목',
    card_index INT DEFAULT 0 COMMENT '카드 인덱스',
    plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
    plugin_config TEXT DEFAULT NULL COMMENT '플러그인 설정 (JSON 형태)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_ktm_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_card_title (card_title),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_user_card_plugin (user_id, category, card_title, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카드별 플러그인 설정';

-- 4. 플러그인 설정 히스토리 테이블 (변경 이력 추적)
CREATE TABLE IF NOT EXISTS mdl_ktm_plugin_settings_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID (moodle user id)',
    plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
    setting_type ENUM('user_setting', 'card_setting') NOT NULL COMMENT '설정 유형',
    reference_id INT NOT NULL COMMENT '참조 ID (user_plugin_settings 또는 card_plugin_settings의 ID)',
    old_value TEXT DEFAULT NULL COMMENT '이전 값 (JSON 형태)',
    new_value TEXT DEFAULT NULL COMMENT '새 값 (JSON 형태)',
    change_reason VARCHAR(255) DEFAULT NULL COMMENT '변경 사유',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_ktm_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_setting_type (setting_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='플러그인 설정 변경 히스토리';

-- 초기 플러그인 데이터 삽입
INSERT INTO mdl_ktm_plugin_types (plugin_id, plugin_title, plugin_icon, plugin_description, timecreated, timemodified) VALUES
('internal_link', '내부링크 열기', '🔗', '플랫폼 내 다른 페이지로 이동', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('external_link', '외부링크 열기', '🌐', '외부 사이트나 도구 연결', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('send_message', '메시지 발송', '📨', '사용자에게 자동 메시지 전송', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()); 