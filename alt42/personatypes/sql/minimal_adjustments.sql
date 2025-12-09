-- 최소한의 조정으로 INSERT SQL 파일을 실행할 수 있도록 하는 SQL

-- ========================================
-- 1. 현재 구조 확인
-- ========================================
SHOW COLUMNS FROM mdl_alt42i_math_patterns;
SHOW COLUMNS FROM mdl_alt42i_pattern_solutions;

-- ========================================
-- 2. 필수 컬럼만 추가 (이미 있으면 오류 무시)
-- ========================================

-- mdl_alt42i_math_patterns에 필요한 컬럼 추가
-- 각 명령을 개별적으로 실행하고, 이미 존재하는 컬럼 오류는 무시하세요

ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN name VARCHAR(100) NOT NULL;
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN description TEXT NOT NULL;
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN category_id INT(11) NOT NULL;
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN icon VARCHAR(10) DEFAULT '📊';
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN priority ENUM('high', 'medium', 'low') DEFAULT 'medium';
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN audio_time VARCHAR(20) DEFAULT '3분';

-- mdl_alt42i_pattern_solutions에 필요한 컬럼 추가
ALTER TABLE mdl_alt42i_pattern_solutions ADD COLUMN check_method TEXT NOT NULL AFTER action;

-- ========================================
-- 3. 또는 INSERT 시 컬럼 매핑을 사용하는 방법
-- ========================================
-- 만약 위 방법이 어렵다면, 기존 테이블 구조에 맞춰 INSERT 문을 수정할 수 있습니다.
-- 예시:

-- 현재 테이블에 pattern_name, pattern_desc가 있다면:
-- INSERT INTO mdl_alt42i_math_patterns (id, pattern_name, pattern_desc, category_id, icon, priority, audio_time, created_at, updated_at) 
-- SELECT id, name, description, category_id, icon, priority, audio_time, created_at, updated_at
-- FROM (
--     VALUES (1, '아이디어 해방 자동발화형', '번쩍이는...', 1, '🧠', 'high', '2:15', NOW(), NOW())
-- ) AS tmp(id, name, description, category_id, icon, priority, audio_time, created_at, updated_at);

-- ========================================
-- 4. 데이터 삽입을 위한 임시 뷰 생성 (고급 방법)
-- ========================================
-- 뷰를 생성하여 컬럼명 차이를 해결할 수도 있습니다
CREATE OR REPLACE VIEW mdl_alt42i_math_patterns_insert AS
SELECT 
    id,
    COALESCE(name, pattern_name) as name,
    COALESCE(description, pattern_desc) as description,
    category_id,
    icon,
    priority,
    audio_time,
    created_at,
    updated_at
FROM mdl_alt42i_math_patterns;

-- ========================================
-- 5. 카테고리 데이터가 없다면 삽입
-- ========================================
INSERT IGNORE INTO mdl_alt42i_pattern_categories (id, category_name, category_code, display_order, description) VALUES
(1, '인지 과부하', 'cognitive_overload', 1, '정보 처리 용량 초과로 인한 학습 장애'),
(2, '자신감 왜곡', 'confidence_distortion', 2, '자신감 수준과 실제 능력 간의 불일치'),
(3, '실수 패턴', 'mistake_patterns', 3, '반복적으로 나타나는 실수 유형'),
(4, '접근 전략 오류', 'approach_errors', 4, '문제 해결 전략의 부적절한 선택'),
(5, '학습 습관', 'study_habits', 5, '비효율적인 학습 방법과 습관'),
(6, '시간/압박 관리', 'time_pressure', 6, '시간 관리 및 압박 대처 문제'),
(7, '검증/확인 부재', 'verification_absence', 7, '답안 검토 및 확인 과정 부족'),
(8, '기타 장애', 'other_obstacles', 8, '기타 학습 장애 요인');