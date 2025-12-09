-- Module Configuration Database Schema
-- This schema provides a unified structure for all module configurations
-- to replace hardcoded data in JavaScript modules

-- Ensure all categories exist
INSERT IGNORE INTO mdl_ktm_categories (category_key, title, description, agent_name, agent_role, agent_avatar, display_order, is_active, timecreated, timemodified)
VALUES 
('quarterly', '분기활동', '장기간에 걸친 학습 목표 설정과 성과 관리를 통해 체계적인 교육 계획을 수립합니다.', '분기 관리자', '장기 계획 및 목표 관리', '📅', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('daily', '오늘활동', '시험대비, 복습전략, 학습분석을 통한 일일 학습 관리', '일일 관리자', '오늘의 활동 및 목표 관리', '⏰', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('realtime', '실시간 관리', '학습 과정을 실시간으로 모니터링하고 즉각적인 개입과 지원을 제공합니다.', '실시간 관리자', '즉시 모니터링 및 대응', '📊', 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('interaction', '상호작용 관리', '학습자와의 효과적인 소통을 통해 개인화된 학습 경험을 제공합니다.', '상호작용 관리자', '소통 및 피드백 관리', '💬', 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('bias', '인지관성 개선', '학생들의 인지관성을 개선하고 연쇄상호작용을 통해 효과적인 학습 환경을 조성합니다.', '인지관성 개선 관리자', '수학 학습 인지관성 개선 및 연쇄상호작용 관리', '🧠', 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('development', '컨텐츠 및 앱개발', '교육용 컨텐츠와 애플리케이션을 개발하고 관리하여 효과적인 학습 도구를 제공합니다.', '개발 관리자', '컨텐츠 및 앱 개발', '🛠️', 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Create a stored procedure to insert tabs with proper error handling
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS insert_module_tabs()
BEGIN
    DECLARE category_id INT;
    
    -- Quarterly tabs
    SELECT id INTO category_id FROM mdl_ktm_categories WHERE category_key = 'quarterly';
    INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, timecreated, timemodified)
    VALUES 
    (category_id, 'planning', '계획관리', '장기 목표 설정 및 관리', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'counseling', '학부모상담', '학부모와의 소통 관리', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
    
    -- Daily tabs
    SELECT id INTO category_id FROM mdl_ktm_categories WHERE category_key = 'daily';
    INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, timecreated, timemodified)
    VALUES 
    (category_id, 'exam_prep', '시험대비', '학교 시험 분석 및 대비 전략', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'review_strategy', '복습전략', '효과적인 복습 계획 수립', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'learning_analysis', '학습분석', '학습 패턴 및 성과 분석', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
    
    -- Realtime tabs
    SELECT id INTO category_id FROM mdl_ktm_categories WHERE category_key = 'realtime';
    INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, timecreated, timemodified)
    VALUES 
    (category_id, 'monitoring', '모니터링', '실시간 상태 모니터링', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'intervention', '개입관리', '적시 개입 및 지원', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'adjustment', '조정관리', '실시간 계획 조정', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
    
    -- Interaction tabs
    SELECT id INTO category_id FROM mdl_ktm_categories WHERE category_key = 'interaction';
    INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, timecreated, timemodified)
    VALUES 
    (category_id, 'communication', '소통관리', '대화 및 의사소통', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'feedback', '피드백', '개인화된 피드백 제공', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'adaptation', '적응관리', '개인별 맞춤 적응', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
    
    -- Bias tabs
    SELECT id INTO category_id FROM mdl_ktm_categories WHERE category_key = 'bias';
    INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, timecreated, timemodified)
    VALUES 
    (category_id, 'concept_study', '개념공부', '인지관성 유형화를 통한 개념 학습', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'problem_solving', '문제풀이', '인지관성 유형화를 통한 문제 해결', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'learning_management', '학습관리', '인지관성 유형화를 통한 학습 관리', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'exam_preparation', '시험대비', '인지관성 유형화를 통한 시험 준비', 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'practical_training', '실전연습', '인지관성 유형화를 통한 실전 연습', 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'attendance', '출결관련', '인지관성 유형화를 통한 출결 관리', 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
    
    -- Development tabs
    SELECT id INTO category_id FROM mdl_ktm_categories WHERE category_key = 'development';
    INSERT IGNORE INTO mdl_ktm_tabs (category_id, tab_key, title, description, display_order, is_active, timecreated, timemodified)
    VALUES 
    (category_id, 'content', '컨텐츠 개발', '교육 컨텐츠 생성 및 관리', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'application', '앱 개발', '애플리케이션 개발', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (category_id, 'tools', '개발도구', '개발 도구 및 환경', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
END$$

DELIMITER ;

-- Execute the stored procedure
CALL insert_module_tabs();

-- Insert development module items (as an example of items with details)
INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'mobile_app',
    '모바일 앱',
    '스마트폰과 태블릿에서 사용할 수 있는 모바일 교육 앱을 개발합니다.',
    JSON_ARRAY('iOS 앱 개발', 'Android 앱 개발', '반응형 디자인', '모바일 UX 최적화'),
    1,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'application';

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'web_app',
    '웹 애플리케이션',
    '웹 브라우저에서 실행되는 교육용 웹 애플리케이션을 개발합니다.',
    JSON_ARRAY('프론트엔드 개발', '백엔드 시스템', '클라우드 배포', '웹 보안'),
    2,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'application';

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'desktop_app',
    '데스크톱 앱',
    'PC와 Mac에서 사용할 수 있는 데스크톱 교육 애플리케이션을 개발합니다.',
    JSON_ARRAY('Windows 앱 개발', 'macOS 앱 개발', '네이티브 성능', '시스템 통합'),
    3,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'application';

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'cross_platform',
    '크로스 플랫폼',
    '여러 플랫폼에서 동시에 작동하는 크로스 플랫폼 앱을 개발합니다.',
    JSON_ARRAY('통합 개발 환경', '코드 재사용', '플랫폼 최적화', '일관된 사용자 경험'),
    4,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'application';

-- Development tools items
INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'dev_framework',
    '개발 프레임워크',
    '효율적인 개발을 위한 프레임워크와 라이브러리를 구축합니다.',
    JSON_ARRAY('프레임워크 선택', '라이브러리 통합', '개발 템플릿', '코드 표준화'),
    1,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'tools';

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'test_tools',
    '테스트 도구',
    '품질 보증을 위한 테스트 도구와 자동화 시스템을 구축합니다.',
    JSON_ARRAY('자동화 테스트', '단위 테스트', '통합 테스트', '성능 테스트'),
    2,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'tools';

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'deploy_management',
    '배포 관리',
    '애플리케이션 배포와 운영을 위한 관리 시스템을 구축합니다.',
    JSON_ARRAY('CI/CD 파이프라인', '배포 자동화', '모니터링 시스템', '롤백 관리'),
    3,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'tools';

INSERT IGNORE INTO mdl_ktm_menu_items (tab_id, item_key, title, description, details, display_order, is_active, timecreated, timemodified)
SELECT 
    t.id,
    'version_control',
    '버전 관리',
    '코드와 컨텐츠의 버전을 체계적으로 관리합니다.',
    JSON_ARRAY('Git 관리', '브랜치 전략', '릴리스 관리', '협업 도구'),
    4,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM mdl_ktm_tabs t
JOIN mdl_ktm_categories c ON t.category_id = c.id
WHERE c.category_key = 'development' AND t.tab_key = 'tools';

-- Create view for module data retrieval
CREATE OR REPLACE VIEW v_ktm_module_data AS
SELECT 
    c.category_key,
    c.title as category_title,
    c.description as category_description,
    c.agent_name,
    c.agent_role,
    c.agent_avatar,
    t.id as tab_id,
    t.tab_key,
    t.title as tab_title,
    t.description as tab_description,
    t.display_order as tab_order,
    mi.id as item_id,
    mi.item_key,
    mi.title as item_title,
    mi.description as item_description,
    mi.details as item_details,
    mi.has_chain_interaction,
    mi.display_order as item_order
FROM mdl_ktm_categories c
LEFT JOIN mdl_ktm_tabs t ON c.id = t.category_id
LEFT JOIN mdl_ktm_menu_items mi ON t.id = mi.tab_id
WHERE c.is_active = 1
    AND t.is_active = 1
    AND (mi.is_active = 1 OR mi.id IS NULL)
ORDER BY c.display_order, t.display_order, mi.display_order;