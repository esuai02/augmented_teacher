-- 상담관리 모듈 데이터 삽입
-- consultation 카테고리 추가

-- 1. 카테고리 추가 (이미 있으면 무시)
INSERT IGNORE INTO mdl_ktm_categories (category_key, title, description, display_order, is_active, created_at)
VALUES ('consultation', '상담관리', '학생 및 학부모 상담을 체계적으로 관리하고 기록합니다', 9, 1, NOW());

-- 카테고리 ID 가져오기
SET @category_id = (SELECT id FROM mdl_ktm_categories WHERE category_key = 'consultation');

-- 2. 탭 추가
INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, created_at)
VALUES 
    (@category_id, 'new_student', '신규학생', '신규 학생 상담 및 레벨 테스트', 1, 1, NOW()),
    (@category_id, 'regular_consult', '정기상담', '재원생 정기 상담 및 학습 점검', 2, 1, NOW()),
    (@category_id, 'exam_consult', '시험관련', '시험 관련 특별 상담 및 관리', 3, 1, NOW()),
    (@category_id, 'situation_consult', '상황맞춤 상담', '특별한 상황에 맞춘 맞춤형 상담', 4, 1, NOW()),
    (@category_id, 'case_skillup', '사례청취 및 스킬업', '상담 사례 분석과 전문성 향상', 5, 1, NOW()),
    (@category_id, 'parent_persona', '학부모 페르소나', '학부모 유형별 맞춤 대응 전략', 6, 1, NOW()),
    (@category_id, 'student_persona', '학생 페르소나', '학생 유형별 맞춤 학습 전략', 7, 1, NOW());

-- 3. 신규학생 탭 아이템 추가
SET @new_student_tab_id = (SELECT id FROM mdl_ktm_tabs WHERE category_id = @category_id AND tab_key = 'new_student');

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, title, description, details, item_order, is_active, created_at)
VALUES 
    (@new_student_tab_id, '초등학생', '초등학생 신규 상담', '["학습 수준 파악", "학부모 상담", "학습 목표 설정", "수업 계획 수립"]', 1, 1, NOW()),
    (@new_student_tab_id, '중학생', '중학생 신규 상담', '["현재 성적 분석", "학습 습관 점검", "목표 설정", "커리큘럼 제안"]', 2, 1, NOW()),
    (@new_student_tab_id, '예비고', '예비 고등학생 상담', '["중학 내신 분석", "고등 과정 안내", "학습 전략 수립", "진로 상담"]', 3, 1, NOW()),
    (@new_student_tab_id, '고1', '고등학교 1학년 상담', '["내신 관리 전략", "수능 기초 안내", "학습 계획 수립", "진로 탐색"]', 4, 1, NOW()),
    (@new_student_tab_id, '고2', '고등학교 2학년 상담', '["문/이과 선택 상담", "내신 심화 전략", "수능 준비 계획", "대입 로드맵"]', 5, 1, NOW()),
    (@new_student_tab_id, '고3', '고등학교 3학년 상담', '["수능 집중 전략", "수시/정시 상담", "실전 대비 계획", "멘탈 관리"]', 6, 1, NOW());

-- 4. 정기상담 탭 아이템 추가
SET @regular_consult_tab_id = (SELECT id FROM mdl_ktm_tabs WHERE category_id = @category_id AND tab_key = 'regular_consult');

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, title, description, details, item_order, is_active, created_at)
VALUES 
    (@regular_consult_tab_id, '초등학생', '초등학생 정기 상담', '["학습 진도 점검", "학습 태도 개선", "학부모 피드백", "다음 목표 설정"]', 1, 1, NOW()),
    (@regular_consult_tab_id, '중학생', '중학생 정기 상담', '["성적 변화 분석", "학습 방법 개선", "시험 대비 전략", "진로 구체화"]', 2, 1, NOW()),
    (@regular_consult_tab_id, '예비고', '예비고 정기 상담', '["고등 준비 상황", "선행 학습 점검", "학습 습관 강화", "목표 재설정"]', 3, 1, NOW()),
    (@regular_consult_tab_id, '고1', '고1 정기 상담', '["내신 성적 분석", "학습 패턴 점검", "약점 보완 계획", "수능 기초 점검"]', 4, 1, NOW()),
    (@regular_consult_tab_id, '고2', '고2 정기 상담', '["내신 관리 점검", "수능 준비 상황", "모의고사 분석", "입시 전략 수립"]', 5, 1, NOW()),
    (@regular_consult_tab_id, '고3', '고3 정기 상담', '["수능 준비 점검", "실전 연습 분석", "입시 일정 관리", "컨디션 관리"]', 6, 1, NOW());

-- 5. 시험관련 탭 아이템 추가
SET @exam_consult_tab_id = (SELECT id FROM mdl_ktm_tabs WHERE category_id = @category_id AND tab_key = 'exam_consult');

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, title, description, details, item_order, is_active, created_at)
VALUES 
    (@exam_consult_tab_id, '시험대비 안내', '시험 전 준비사항 안내', '["시험 범위 확인", "학습 계획 점검", "취약 단원 집중", "시험 전략 안내"]', 1, 1, NOW()),
    (@exam_consult_tab_id, '시험 마무리 상담', '시험 직전 최종 점검', '["핵심 내용 정리", "실수 방지 전략", "시간 관리 팁", "멘탈 관리"]', 2, 1, NOW()),
    (@exam_consult_tab_id, '시험결과 상담', '시험 후 결과 분석', '["성적 분석", "오답 원인 파악", "개선점 도출", "다음 계획 수립"]', 3, 1, NOW());

-- 6. 상황맞춤 상담 탭 아이템 추가
SET @situation_consult_tab_id = (SELECT id FROM mdl_ktm_tabs WHERE category_id = @category_id AND tab_key = 'situation_consult');

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, title, description, details, item_order, is_active, created_at)
VALUES 
    (@situation_consult_tab_id, '입시상담', '대학 입시 전략 상담', '["수시/정시 전략", "대학별 전형 분석", "학생부 관리", "자소서 컨설팅"]', 1, 1, NOW()),
    (@situation_consult_tab_id, '스마트폰 관련', '스마트폰 사용 관련 상담', '["사용 시간 관리", "학습 앱 활용", "집중력 향상 방법", "디지털 디톡스"]', 2, 1, NOW());

-- 나머지 탭들은 현재 아이템이 없으므로 비워둠

-- 확인 쿼리
SELECT 
    c.title as category_title,
    t.title as tab_title,
    COUNT(mi.id) as item_count
FROM mdl_ktm_categories c
LEFT JOIN mdl_ktm_tabs t ON c.id = t.category_id
LEFT JOIN mdl_ktm_menu_items mi ON t.id = mi.tab_id
WHERE c.category_key = 'consultation'
GROUP BY c.id, t.id
ORDER BY t.display_order;