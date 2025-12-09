-- daily.js에서 누락된 hasLink 항목들을 플러그인 DB에 삽입
-- 작성일: 2025-01-16
-- 설명: 하드코딩된 메뉴 데이터를 DB로 마이그레이션

-- 변수 설정 (실제 사용자 ID로 변경 필요)
SET @user_id = 1;
SET @current_time = UNIX_TIMESTAMP();

-- daily.js에서 누락된 hasLink: true 항목들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 학습도구 탭
(@user_id, 'daily', '교과서 단원별 해설', 8, 'external_link',
'{"url": "https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php", "target": "_blank", "description": "교과서 단원별 상세한 해설을 제공합니다.", "details": ["단원별 핵심 개념", "예제 문제 해설", "심화 학습 자료", "단원 평가 대비"], "hasLink": true}',
8, @current_time, @current_time),

(@user_id, 'daily', '교과서 단원별 핵심 내용 정리', 9, 'external_link',
'{"url": "https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php", "target": "_blank", "description": "각 단원의 핵심 내용을 일목요연하게 정리합니다.", "details": ["핵심 개념 요약", "공식 정리", "암기 사항", "단원 연결 관계"], "hasLink": true}',
9, @current_time, @current_time),

(@user_id, 'daily', '외부 링크', 10, 'external_link',
'{"url": "#", "target": "_blank", "description": "유용한 외부 학습 자료를 연결합니다.", "details": ["추천 학습 사이트", "온라인 강의", "참고 자료", "학습 커뮤니티"], "hasLink": true}',
10, @current_time, @current_time),

(@user_id, 'daily', '포모도로', 11, 'external_link',
'{"url": "#", "target": "_self", "description": "집중력 향상을 위한 포모도로 타이머를 제공합니다.", "details": ["25분 집중 타이머", "5분 휴식 알림", "일일 세션 기록", "집중도 통계"], "hasLink": true}',
11, @current_time, @current_time),

-- 동기부여 탭
(@user_id, 'daily', '목표 달성 보상', 8, 'external_link',
'{"url": "#", "target": "_self", "description": "목표 달성 시 제공되는 보상 시스템입니다.", "details": ["포인트 적립", "배지 획득", "레벨업 시스템", "보상 교환"], "hasLink": true}',
8, @current_time, @current_time),

(@user_id, 'daily', '학습 게임화', 9, 'external_link',
'{"url": "#", "target": "_self", "description": "학습을 게임처럼 재미있게 만듭니다.", "details": ["퀘스트 시스템", "경험치 획득", "랭킹 시스템", "도전 과제"], "hasLink": true}',
9, @current_time, @current_time),

(@user_id, 'daily', '성취 기록', 10, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 성취를 기록하고 관리합니다.", "details": ["일일 성취 기록", "주간 성과 분석", "월간 리포트", "성장 그래프"], "hasLink": true}',
10, @current_time, @current_time),

(@user_id, 'daily', '동료 학습자 소통', 11, 'external_link',
'{"url": "#", "target": "_self", "description": "다른 학습자들과 소통하고 경쟁합니다.", "details": ["학습 그룹", "스터디 매칭", "경쟁 시스템", "협력 프로젝트"], "hasLink": true}',
11, @current_time, @current_time),

-- 지원도구 탭
(@user_id, 'daily', '학습 알림 설정', 8, 'external_link',
'{"url": "#", "target": "_self", "description": "중요한 학습 일정을 알려줍니다.", "details": ["일정 알림", "복습 알림", "과제 마감 알림", "시험 일정 알림"], "hasLink": true}',
8, @current_time, @current_time),

(@user_id, 'daily', '자료 다운로드', 9, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 자료를 다운로드할 수 있습니다.", "details": ["PDF 자료", "연습 문제", "해설집", "참고 자료"], "hasLink": true}',
9, @current_time, @current_time),

(@user_id, 'daily', '기술 지원', 10, 'external_link',
'{"url": "#", "target": "_self", "description": "기술적 문제 해결을 지원합니다.", "details": ["사용법 안내", "오류 해결", "FAQ", "1:1 문의"], "hasLink": true}',
10, @current_time, @current_time),

(@user_id, 'daily', '학습 환경 설정', 11, 'external_link',
'{"url": "#", "target": "_self", "description": "개인에게 맞는 학습 환경을 설정합니다.", "details": ["화면 설정", "알림 설정", "언어 설정", "접근성 옵션"], "hasLink": true}',
11, @current_time, @current_time);

-- 삽입 결과 확인
SELECT COUNT(*) as total_missing_cards FROM mdl_alt42DB_card_plugin_settings 
WHERE user_id = @user_id 
AND card_title IN (
    '교과서 단원별 해설', 
    '교과서 단원별 핵심 내용 정리', 
    '외부 링크', 
    '포모도로',
    '목표 달성 보상',
    '학습 게임화',
    '성취 기록',
    '동료 학습자 소통',
    '학습 알림 설정',
    '자료 다운로드',
    '기술 지원',
    '학습 환경 설정'
);