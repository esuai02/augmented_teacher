-- plugin_type 컬럼 추가 SQL
-- 작성일: 2025-01-18
-- 설명: mdl_alt42DB_plugin_types 테이블에 plugin_type 컬럼이 없는 경우 추가

-- 1. plugin_type 컬럼이 없으면 추가
ALTER TABLE mdl_alt42DB_plugin_types 
ADD COLUMN IF NOT EXISTS plugin_type VARCHAR(50) DEFAULT NULL COMMENT '플러그인 유형' AFTER plugin_description;

-- 2. 기존 데이터에 plugin_type 값 설정
UPDATE mdl_alt42DB_plugin_types 
SET plugin_type = plugin_id 
WHERE plugin_type IS NULL;

-- 3. 에이전트 플러그인 추가 (plugin_type 컬럼 없이)
INSERT IGNORE INTO mdl_alt42DB_plugin_types (
    plugin_id, 
    plugin_title, 
    plugin_icon, 
    plugin_description, 
    is_active, 
    timecreated, 
    timemodified
) VALUES (
    'agent',
    '에이전트',
    '🤖',
    'URL 또는 PHP 코드를 실행하는 에이전트',
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 4. 결과 확인
SELECT 
    plugin_id,
    plugin_title,
    plugin_icon,
    plugin_description,
    is_active
FROM mdl_alt42DB_plugin_types
WHERE is_active = 1
ORDER BY plugin_id;