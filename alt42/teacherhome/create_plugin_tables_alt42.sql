-- ALT42 플러그인 시스템 테이블 생성
-- 작성일: 2025-01-18
-- 설명: mdl_alt42DB 접두사를 사용하는 플러그인 테이블 생성

-- 1. 플러그인 타입 테이블 생성 (이미 있으면 무시)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 기본 플러그인 타입 데이터 삽입
INSERT IGNORE INTO mdl_alt42DB_plugin_types (plugin_id, plugin_title, plugin_icon, plugin_description, plugin_type, is_active, timecreated, timemodified) VALUES
('internal_link', '내부링크 열기', '🔗', '플랫폼 내 다른 페이지로 이동', 'internal_link', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('external_link', '외부링크 열기', '🌐', '외부 사이트나 도구 연결', 'external_link', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('send_message', '메시지 발송', '📨', '사용자에게 자동 메시지 전송', 'send_message', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('agent', '에이전트', '🤖', 'URL 또는 PHP 코드를 실행하는 에이전트', 'agent', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3. 플러그인 타입 확인
SELECT 
    plugin_id,
    plugin_title,
    plugin_icon,
    plugin_description,
    plugin_type,
    is_active
FROM mdl_alt42DB_plugin_types
WHERE is_active = 1
ORDER BY plugin_id;