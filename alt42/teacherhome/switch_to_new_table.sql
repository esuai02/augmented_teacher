-- 새로운 정규화된 테이블로 전환하는 스크립트
-- 주의: 데이터 마이그레이션이 완료되고 검증된 후에만 실행하세요!

-- 1. 테이블 이름 변경
RENAME TABLE mdl_alt42DB_card_plugin_settings TO mdl_alt42DB_card_plugin_settings_old;
RENAME TABLE mdl_alt42DB_card_plugin_settings_new TO mdl_alt42DB_card_plugin_settings;

-- 2. 기존 API와의 호환성을 위한 뷰 생성 (옵션)
-- 이 뷰는 plugin_config를 JSON으로 제공하여 기존 코드와의 호환성을 유지합니다
CREATE OR REPLACE VIEW mdl_alt42DB_card_plugin_settings_compat AS
SELECT 
    id,
    user_id,
    category,
    card_title,
    card_index,
    plugin_id,
    -- plugin_config를 JSON으로 재구성
    CASE 
        WHEN plugin_id = 'internal_link' THEN
            JSON_OBJECT(
                'plugin_name', plugin_name,
                'card_description', card_description,
                'internal_url', internal_url,
                'open_new_tab', open_new_tab
            )
        WHEN plugin_id = 'external_link' THEN
            JSON_OBJECT(
                'plugin_name', plugin_name,
                'card_description', card_description,
                'external_url', external_url,
                'open_new_tab', open_new_tab
            )
        WHEN plugin_id = 'send_message' THEN
            JSON_OBJECT(
                'plugin_name', plugin_name,
                'card_description', card_description,
                'message_content', message_content,
                'message_type', message_type
            )
        WHEN plugin_id = 'agent' THEN
            JSON_OBJECT(
                'plugin_name', plugin_name,
                'card_description', card_description,
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
            )
        ELSE
            JSON_OBJECT(
                'plugin_name', plugin_name,
                'card_description', card_description
            )
    END AS plugin_config,
    is_active,
    display_order,
    timecreated,
    timemodified
FROM mdl_alt42DB_card_plugin_settings;

-- 3. 검증 쿼리
-- 원본과 새 테이블의 레코드 수 비교
SELECT 
    (SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings_old) as old_count,
    (SELECT COUNT(*) FROM mdl_alt42DB_card_plugin_settings) as new_count;

-- 샘플 데이터 확인
SELECT 
    plugin_id,
    plugin_name,
    card_description,
    CASE 
        WHEN plugin_id = 'internal_link' THEN internal_url
        WHEN plugin_id = 'external_link' THEN external_url
        WHEN plugin_id = 'send_message' THEN CONCAT('메시지: ', LEFT(message_content, 50))
        WHEN plugin_id = 'agent' THEN CONCAT(agent_type, ': ', LEFT(agent_description, 50))
    END as main_content
FROM mdl_alt42DB_card_plugin_settings
LIMIT 10;

-- 4. 백업 테이블 삭제 (선택사항 - 충분한 테스트 후)
-- DROP TABLE mdl_alt42DB_card_plugin_settings_old;
-- DROP TABLE mdl_alt42DB_card_plugin_settings_backup;