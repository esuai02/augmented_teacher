-- 에이전트 플러그인 타입 추가 SQL
-- 작성일: 2025-01-18
-- 설명: mdl_alt42DB_plugin_types 테이블에 에이전트 플러그인 타입을 추가합니다.

-- 에이전트 플러그인이 이미 존재하는지 확인하고 없으면 추가
INSERT IGNORE INTO mdl_alt42DB_plugin_types (
    plugin_id, 
    plugin_title, 
    plugin_icon, 
    plugin_description, 
    plugin_type,
    is_active, 
    timecreated, 
    timemodified
) VALUES (
    'agent',
    '에이전트',
    '🤖',
    'URL 또는 PHP 코드를 실행하는 에이전트',
    'agent',
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 현재 등록된 모든 플러그인 타입 확인
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