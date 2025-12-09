-- JSON 필드를 TEXT로 변경하는 SQL 스크립트
-- Moodle은 MySQL의 JSON 타입을 지원하지 않으므로 TEXT로 변경

-- mdl_wxsperta_events 테이블의 JSON 필드 변경
ALTER TABLE mdl_wxsperta_events 
MODIFY COLUMN event_data TEXT,
MODIFY COLUMN triggered_agents TEXT;

-- mdl_wxsperta_user_profiles 테이블의 JSON 필드 변경
ALTER TABLE mdl_wxsperta_user_profiles 
MODIFY COLUMN interests TEXT,
MODIFY COLUMN goals TEXT;

-- mdl_wxsperta_achievements 테이블의 JSON 필드 변경
ALTER TABLE mdl_wxsperta_achievements 
MODIFY COLUMN achievement_data TEXT;