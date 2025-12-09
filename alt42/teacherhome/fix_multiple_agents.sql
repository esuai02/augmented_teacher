-- 여러 개의 에이전트를 같은 탭에 추가할 수 있도록 수정
-- 기존의 unique constraint를 제거하고 card_index를 포함한 새로운 constraint 추가

-- 1. 기존 unique constraint 제거
ALTER TABLE mdl_alt42DB_card_plugin_settings 
DROP INDEX unique_user_card_plugin;

-- 2. card_index를 포함한 새로운 unique constraint 추가
-- 이제 같은 탭에 같은 플러그인 타입을 여러 개 추가할 수 있음
ALTER TABLE mdl_alt42DB_card_plugin_settings 
ADD UNIQUE KEY unique_user_card_plugin_index (user_id, category, card_title, card_index, plugin_id);

-- 3. 인덱스 추가 (성능 향상)
CREATE INDEX idx_card_index ON mdl_alt42DB_card_plugin_settings(card_index);

-- 확인 쿼리
SHOW INDEX FROM mdl_alt42DB_card_plugin_settings;