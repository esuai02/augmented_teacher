-- ALT42 플러그인 타입 초기 데이터 삽입
-- 작성일: 2025-01-07
-- 설명: 기본 플러그인 타입들을 mdl_alt42DB_plugin_types 테이블에 삽입

-- 기존 데이터와 충돌하지 않도록 INSERT IGNORE 사용
INSERT IGNORE INTO mdl_alt42DB_plugin_types 
(plugin_id, plugin_title, plugin_icon, plugin_description, is_active, timecreated, timemodified) 
VALUES 
('default_card', '기본 카드', '📋', '미리 정의된 기능 카드', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('internal_link', '내부링크 열기', '🔗', '플랫폼 내 다른 페이지로 이동', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('external_link', '외부링크 열기', '🌐', '외부 사이트나 도구 연결', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('send_message', '메시지 발송', '📨', '사용자에게 자동 메시지 전송', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('agent', '에이전트', '🤖', '팝업창에서 멀티턴 작업 실행', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 삽입된 데이터 확인
SELECT * FROM mdl_alt42DB_plugin_types ORDER BY plugin_id;