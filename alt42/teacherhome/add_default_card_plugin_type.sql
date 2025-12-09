-- default_card 플러그인 타입 추가
INSERT INTO mdl_alt42DB_plugin_types (plugin_id, name, description, icon, is_active, created_at) 
VALUES ('default_card', '기본 카드', '미리 정의된 기능 카드', '📋', 1, NOW())
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    description = VALUES(description),
    icon = VALUES(icon),
    is_active = VALUES(is_active);

-- 플러그인 타입이 제대로 추가되었는지 확인
SELECT * FROM mdl_alt42DB_plugin_types WHERE plugin_id = 'default_card';