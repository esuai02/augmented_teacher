-- 전체 메뉴 데이터 DB 마이그레이션
-- 작성일: 2025-01-16
-- 설명: 모든 하드코딩된 메뉴 데이터를 플러그인 DB로 통합 마이그레이션

-- 기존 default cards 테이블의 데이터를 플러그인 테이블로 마이그레이션
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
SELECT 
    1 as user_id,
    category,
    title as card_title,
    position as card_index,
    'external_link' as plugin_id,
    JSON_OBJECT(
        'url', COALESCE(url, '#'),
        'target', '_blank',
        'description', COALESCE(description, ''),
        'details', JSON_ARRAY(
            CONCAT(COALESCE(description, ''), ' - 기능 1'),
            CONCAT(COALESCE(description, ''), ' - 기능 2'),
            CONCAT(COALESCE(description, ''), ' - 기능 3'),
            CONCAT(COALESCE(description, ''), ' - 기능 4')
        )
    ) as plugin_config,
    position as display_order,
    UNIX_TIMESTAMP() as timecreated,
    UNIX_TIMESTAMP() as timemodified
FROM mdl_alt42DB_default_cards
WHERE NOT EXISTS (
    SELECT 1 FROM mdl_alt42DB_card_plugin_settings ps
    WHERE ps.category = mdl_alt42DB_default_cards.category
    AND ps.card_title = mdl_alt42DB_default_cards.title
    AND ps.user_id = 1
);

-- 기존 데이터 확인
SELECT 
    category,
    COUNT(*) as card_count
FROM mdl_alt42DB_card_plugin_settings
WHERE user_id = 1
GROUP BY category
ORDER BY category;