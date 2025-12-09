-- KTM 코파일럿 완전한 데이터베이스 스키마
-- 작성일: 2025-01-27
-- 설명: teacherhome 인터페이스에 필요한 전체 DB 구조

-- ============================================
-- 1. 사용자 관리 테이블
-- ============================================

-- 1.1 사용자 기본 정보 (Moodle 연동)
CREATE TABLE IF NOT EXISTS mdl_ktm_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moodle_user_id INT NOT NULL UNIQUE COMMENT 'Moodle 사용자 ID',
    username VARCHAR(100) NOT NULL COMMENT '사용자명',
    email VARCHAR(255) NOT NULL COMMENT '이메일',
    role ENUM('teacher', 'student', 'parent', 'admin') NOT NULL DEFAULT 'teacher' COMMENT '사용자 역할',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    last_login INT(10) DEFAULT NULL COMMENT '마지막 로그인 시간',
    preferences TEXT DEFAULT NULL COMMENT '사용자 환경설정 (JSON)',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_moodle_user_id (moodle_user_id),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KTM 사용자 정보';

-- 1.2 사용자 세션 관리
CREATE TABLE IF NOT EXISTS mdl_ktm_user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'KTM 사용자 ID',
    session_token VARCHAR(255) NOT NULL UNIQUE COMMENT '세션 토큰',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
    user_agent TEXT DEFAULT NULL COMMENT '브라우저 정보',
    last_activity INT(10) NOT NULL COMMENT '마지막 활동 시간',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (user_id) REFERENCES mdl_ktm_users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 세션 정보';

-- ============================================
-- 2. 카테고리 및 메뉴 구조
-- ============================================

-- 2.1 카테고리 정보
CREATE TABLE IF NOT EXISTS mdl_ktm_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_key VARCHAR(50) NOT NULL UNIQUE COMMENT '카테고리 키',
    title VARCHAR(255) NOT NULL COMMENT '카테고리 제목',
    description TEXT NOT NULL COMMENT '카테고리 설명',
    agent_name VARCHAR(255) NOT NULL COMMENT '에이전트 이름',
    agent_role VARCHAR(255) NOT NULL COMMENT '에이전트 역할',
    agent_avatar VARCHAR(10) NOT NULL COMMENT '에이전트 아바타',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_category_key (category_key),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카테고리 정보';

-- 2.2 탭 정보
CREATE TABLE IF NOT EXISTS mdl_ktm_tabs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL COMMENT '카테고리 ID',
    tab_key VARCHAR(50) NOT NULL COMMENT '탭 키',
    title VARCHAR(255) NOT NULL COMMENT '탭 제목',
    description TEXT NOT NULL COMMENT '탭 설명',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (category_id) REFERENCES mdl_ktm_categories(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_tab_key (tab_key),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_category_tab (category_id, tab_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='탭 정보';

-- 2.3 메뉴 아이템 (온보딩 카드 포함)
CREATE TABLE IF NOT EXISTS mdl_ktm_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tab_id INT NOT NULL COMMENT '탭 ID',
    item_key VARCHAR(50) NOT NULL COMMENT '아이템 키',
    title VARCHAR(255) NOT NULL COMMENT '아이템 제목',
    description TEXT NOT NULL COMMENT '아이템 설명',
    details JSON DEFAULT NULL COMMENT '세부 작업 목록',
    has_chain_interaction TINYINT(1) DEFAULT 0 COMMENT '연쇄상호작용 여부',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (tab_id) REFERENCES mdl_ktm_tabs(id) ON DELETE CASCADE,
    INDEX idx_tab_id (tab_id),
    INDEX idx_item_key (item_key),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_tab_item (tab_id, item_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='메뉴 아이템 정보';

-- ============================================
-- 3. 플러그인 시스템
-- ============================================

-- 3.1 플러그인 타입
CREATE TABLE IF NOT EXISTS mdl_alt42DB_plugin_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_id VARCHAR(50) NOT NULL UNIQUE COMMENT '플러그인 ID',
    plugin_title VARCHAR(255) NOT NULL COMMENT '플러그인 제목',
    plugin_icon VARCHAR(10) NOT NULL COMMENT '플러그인 아이콘',
    plugin_description TEXT NOT NULL COMMENT '플러그인 설명',
    plugin_type VARCHAR(50) DEFAULT NULL COMMENT '플러그인 유형',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='플러그인 타입';

-- 3.2 사용자별 플러그인 설정
CREATE TABLE IF NOT EXISTS mdl_alt42DB_user_plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID (moodle user id)',
    plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
    setting_name VARCHAR(255) NOT NULL COMMENT '설정명',
    setting_value TEXT DEFAULT NULL COMMENT '설정값 (JSON)',
    category VARCHAR(50) DEFAULT NULL COMMENT '카테고리',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_alt42DB_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_category (category),
    UNIQUE KEY unique_user_plugin_setting (user_id, plugin_id, setting_name, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자별 플러그인 설정';

-- 3.3 카드별 플러그인 설정
CREATE TABLE IF NOT EXISTS mdl_alt42DB_card_plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    category VARCHAR(50) NOT NULL COMMENT '카테고리',
    card_title VARCHAR(255) NOT NULL COMMENT '카드 제목',
    card_index INT DEFAULT 0 COMMENT '카드 인덱스',
    plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
    plugin_config TEXT DEFAULT NULL COMMENT '플러그인 설정 (JSON)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_alt42DB_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_card_title (card_title),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_user_card_plugin (user_id, category, card_title, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카드별 플러그인 설정';

-- ============================================
-- 4. 채팅 및 상호작용
-- ============================================

-- 4.1 채팅 세션
CREATE TABLE IF NOT EXISTS mdl_ktm_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    category VARCHAR(50) NOT NULL COMMENT '카테고리',
    session_type ENUM('onboarding', 'menu', 'chat', 'agent') NOT NULL COMMENT '세션 유형',
    context JSON DEFAULT NULL COMMENT '세션 컨텍스트 (현재 탭, 아이템 등)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성 세션 여부',
    started_at INT(10) NOT NULL COMMENT '시작 시간',
    ended_at INT(10) DEFAULT NULL COMMENT '종료 시간',
    
    FOREIGN KEY (user_id) REFERENCES mdl_ktm_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_session_type (session_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='채팅 세션';

-- 4.2 채팅 메시지
CREATE TABLE IF NOT EXISTS mdl_ktm_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL COMMENT '세션 ID',
    sender_type ENUM('user', 'ai', 'system') NOT NULL COMMENT '발신자 유형',
    message TEXT NOT NULL COMMENT '메시지 내용',
    metadata JSON DEFAULT NULL COMMENT '메타데이터 (버튼, 카드 등)',
    is_read TINYINT(1) DEFAULT 0 COMMENT '읽음 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (session_id) REFERENCES mdl_ktm_chat_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_sender_type (sender_type),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='채팅 메시지';

-- 4.3 연쇄상호작용 추적
CREATE TABLE IF NOT EXISTS mdl_ktm_chain_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    category VARCHAR(50) NOT NULL COMMENT '카테고리',
    item_key VARCHAR(50) NOT NULL COMMENT '아이템 키',
    interaction_type VARCHAR(50) NOT NULL COMMENT '상호작용 유형',
    interaction_data JSON DEFAULT NULL COMMENT '상호작용 데이터',
    parent_interaction_id INT DEFAULT NULL COMMENT '부모 상호작용 ID',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (user_id) REFERENCES mdl_ktm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_interaction_id) REFERENCES mdl_ktm_chain_interactions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_item_key (item_key),
    INDEX idx_parent_interaction_id (parent_interaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='연쇄상호작용 추적';

-- ============================================
-- 5. 에이전트 시스템
-- ============================================

-- 5.1 에이전트 정의
CREATE TABLE IF NOT EXISTS mdl_ktm_agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_key VARCHAR(50) NOT NULL UNIQUE COMMENT '에이전트 키',
    agent_name VARCHAR(255) NOT NULL COMMENT '에이전트 이름',
    agent_type ENUM('file', 'code', 'api', 'onboarding_item') NOT NULL COMMENT '에이전트 유형',
    agent_config JSON NOT NULL COMMENT '에이전트 설정',
    system_prompt TEXT DEFAULT NULL COMMENT '시스템 프롬프트',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_agent_key (agent_key),
    INDEX idx_agent_type (agent_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='에이전트 정의';

-- 5.2 에이전트 실행 로그
CREATE TABLE IF NOT EXISTS mdl_ktm_agent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    agent_id INT NOT NULL COMMENT '에이전트 ID',
    session_id INT DEFAULT NULL COMMENT '채팅 세션 ID',
    input_data JSON DEFAULT NULL COMMENT '입력 데이터',
    output_data JSON DEFAULT NULL COMMENT '출력 데이터',
    execution_time INT DEFAULT NULL COMMENT '실행 시간(ms)',
    status ENUM('success', 'error', 'timeout') NOT NULL COMMENT '실행 상태',
    error_message TEXT DEFAULT NULL COMMENT '오류 메시지',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (user_id) REFERENCES mdl_ktm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES mdl_ktm_agents(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES mdl_ktm_chat_sessions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_agent_id (agent_id),
    INDEX idx_status (status),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='에이전트 실행 로그';

-- ============================================
-- 6. 사용 통계 및 분석
-- ============================================

-- 6.1 사용자 활동 통계
CREATE TABLE IF NOT EXISTS mdl_ktm_user_activity_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    activity_date DATE NOT NULL COMMENT '활동 날짜',
    category_usage JSON DEFAULT NULL COMMENT '카테고리별 사용 통계',
    plugin_usage JSON DEFAULT NULL COMMENT '플러그인별 사용 통계',
    chat_message_count INT DEFAULT 0 COMMENT '채팅 메시지 수',
    agent_execution_count INT DEFAULT 0 COMMENT '에이전트 실행 수',
    total_session_time INT DEFAULT 0 COMMENT '총 세션 시간(초)',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (user_id) REFERENCES mdl_ktm_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_date (activity_date),
    UNIQUE KEY unique_user_date (user_id, activity_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 활동 통계';

-- 6.2 플러그인 사용 통계
CREATE TABLE IF NOT EXISTS mdl_alt42DB_plugin_usage_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    plugin_id VARCHAR(50) NOT NULL COMMENT '플러그인 ID',
    category VARCHAR(50) DEFAULT NULL COMMENT '카테고리',
    card_title VARCHAR(255) DEFAULT NULL COMMENT '카드 제목',
    execution_count INT DEFAULT 0 COMMENT '실행 횟수',
    last_execution INT(10) DEFAULT NULL COMMENT '마지막 실행 시간',
    execution_data TEXT DEFAULT NULL COMMENT '실행 데이터 (JSON)',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (plugin_id) REFERENCES mdl_alt42DB_plugin_types(plugin_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_category (category),
    INDEX idx_last_execution (last_execution),
    UNIQUE KEY unique_user_plugin_stats (user_id, plugin_id, category, card_title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='플러그인 사용 통계';

-- ============================================
-- 7. 시스템 설정 및 로그
-- ============================================

-- 7.1 시스템 설정
CREATE TABLE IF NOT EXISTS mdl_ktm_system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '설정 키',
    config_value TEXT DEFAULT NULL COMMENT '설정 값',
    config_type ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string' COMMENT '설정 유형',
    description TEXT DEFAULT NULL COMMENT '설정 설명',
    is_editable TINYINT(1) DEFAULT 1 COMMENT '편집 가능 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정';

-- 7.2 시스템 로그
CREATE TABLE IF NOT EXISTS mdl_ktm_system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT '사용자 ID',
    log_level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL COMMENT '로그 레벨',
    log_category VARCHAR(50) NOT NULL COMMENT '로그 카테고리',
    log_message TEXT NOT NULL COMMENT '로그 메시지',
    log_data JSON DEFAULT NULL COMMENT '추가 데이터',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소',
    user_agent TEXT DEFAULT NULL COMMENT '브라우저 정보',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    INDEX idx_user_id (user_id),
    INDEX idx_log_level (log_level),
    INDEX idx_log_category (log_category),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 로그';

-- ============================================
-- 8. 초기 데이터 삽입
-- ============================================

-- 8.1 플러그인 타입 초기 데이터
INSERT INTO mdl_alt42DB_plugin_types (plugin_id, plugin_title, plugin_icon, plugin_description, plugin_type, timecreated, timemodified) VALUES
('agent', '에이전트', '🤖', '팝업창에서 멀티턴 작업 실행', 'agent', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE timemodified = UNIX_TIMESTAMP();

-- 8.2 카테고리 초기 데이터
INSERT INTO mdl_ktm_categories (category_key, title, description, agent_name, agent_role, agent_avatar, display_order, timecreated, timemodified) VALUES
('quarterly', '분기 관리', '장기 목표 및 계획 수립', '분기 관리자', '장기 계획 및 목표 관리', '📅', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('weekly', '주간 관리', '주간 활동 계획 및 관리', '주간 관리자', '주간 활동 및 진도 관리', '📝', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('daily', '일일 관리', '일일 활동 및 과제 관리', '일일 관리자', '오늘의 활동 및 목표 관리', '⏰', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('realtime', '실시간 모니터링', '실시간 학습 모니터링', '실시간 관리자', '즉시 모니터링 및 대응', '📊', 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('interaction', '상호작용 관리', '학생-교사 상호작용 관리', '상호작용 관리자', '소통 및 피드백 관리', '💬', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('bias', '인지관성 개선', '학습 인지관성 개선', '인지관성 개선 관리자', '수학 학습 인지관성 개선 및 연쇄상호작용 관리', '🧠', 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('development', '개발 관리', '컨텐츠 및 도구 개발', '개발 관리자', '컨텐츠 및 앱 개발', '🛠️', 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('viral', '바이럴 마케팅', '바이럴 콘텐츠 제작', '바이럴 마케팅 매니저', '바이럴 콘텐츠 제작 및 소셜미디어 마케팅', '💰', 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('consultation', '상담 관리', '학생/학부모 상담', '상담 관리자', '학생 상담 및 학부모 소통 관리', '🤝', 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE timemodified = UNIX_TIMESTAMP();

-- 8.3 시스템 설정 초기 데이터
INSERT INTO mdl_ktm_system_config (config_key, config_value, config_type, description, timecreated, timemodified) VALUES
('system_version', '1.0.0', 'string', '시스템 버전', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('default_language', 'ko', 'string', '기본 언어', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('session_timeout', '3600', 'number', '세션 타임아웃 (초)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('enable_chat_history', 'true', 'boolean', '채팅 기록 저장 여부', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('max_agent_execution_time', '300', 'number', '에이전트 최대 실행 시간 (초)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE timemodified = UNIX_TIMESTAMP();

-- ============================================
-- 9. 인덱스 및 제약 조건 추가
-- ============================================

-- 성능 최적화를 위한 추가 인덱스
CREATE INDEX idx_chat_messages_session_time ON mdl_ktm_chat_messages(session_id, timecreated);
CREATE INDEX idx_agent_logs_user_time ON mdl_ktm_agent_logs(user_id, timecreated);
CREATE INDEX idx_activity_stats_user_date ON mdl_ktm_user_activity_stats(user_id, activity_date);

-- ============================================
-- 10. 뷰 생성 (자주 사용되는 조회)
-- ============================================

-- 10.1 사용자별 최근 활동 뷰
CREATE OR REPLACE VIEW v_ktm_user_recent_activity AS
SELECT 
    u.id as user_id,
    u.username,
    u.role,
    COUNT(DISTINCT cs.id) as total_sessions,
    COUNT(DISTINCT cm.id) as total_messages,
    MAX(cs.started_at) as last_activity
FROM mdl_ktm_users u
LEFT JOIN mdl_ktm_chat_sessions cs ON u.id = cs.user_id
LEFT JOIN mdl_ktm_chat_messages cm ON cs.id = cm.session_id
WHERE u.is_active = 1
GROUP BY u.id, u.username, u.role;

-- 10.2 플러그인 사용 현황 뷰
CREATE OR REPLACE VIEW v_ktm_plugin_usage_summary AS
SELECT 
    pt.plugin_id,
    pt.plugin_title,
    pt.plugin_icon,
    COUNT(DISTINCT cps.user_id) as total_users,
    COUNT(cps.id) as total_installations,
    SUM(CASE WHEN cps.is_active = 1 THEN 1 ELSE 0 END) as active_installations
FROM mdl_alt42DB_plugin_types pt
LEFT JOIN mdl_alt42DB_card_plugin_settings cps ON pt.plugin_id = cps.plugin_id
WHERE pt.is_active = 1
GROUP BY pt.plugin_id, pt.plugin_title, pt.plugin_icon;

-- ============================================
-- 완료 메시지
-- ============================================
SELECT 'KTM 코파일럿 데이터베이스 스키마 생성 완료!' as message;

-- 생성된 테이블 확인
SHOW TABLES LIKE 'mdl_ktm_%';
SHOW TABLES LIKE 'mdl_alt42DB_%';