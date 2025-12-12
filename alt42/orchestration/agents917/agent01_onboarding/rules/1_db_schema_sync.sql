-- Agent 01 Onboarding 시스템 정상화를 위한 DB 스키마 동기화 스크립트
-- 작성일: 2025-11-19
-- 목적: rules.yaml의 요구사항을 충족하는 alt42o_onboarding 테이블 생성 및 컬럼 추가

-- 1. mdl_alt42o_onboarding 테이블 생성 (존재하지 않을 경우)
CREATE TABLE IF NOT EXISTS mdl_alt42o_onboarding (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    school VARCHAR(255) DEFAULT NULL COMMENT '학교명',
    birth_year INT(4) DEFAULT NULL COMMENT '출생년도',
    course_level VARCHAR(10) DEFAULT NULL COMMENT '과정: 초등/중등/고등',
    grade_detail VARCHAR(10) DEFAULT NULL COMMENT '학년: 1학년/2학년/3학년',
    
    -- 학습 진도
    concept_level VARCHAR(10) DEFAULT NULL,
    concept_progress INT(2) DEFAULT NULL,
    advanced_level VARCHAR(10) DEFAULT NULL,
    advanced_progress INT(2) DEFAULT NULL,
    learning_notes TEXT DEFAULT NULL,

    -- 학습 스타일
    problem_preference VARCHAR(50) DEFAULT NULL,
    exam_style VARCHAR(50) DEFAULT NULL,
    math_confidence INT(2) DEFAULT NULL,
    
    -- 학습 방식
    parent_style VARCHAR(50) DEFAULT NULL,
    stress_level VARCHAR(20) DEFAULT NULL,
    feedback_preference VARCHAR(50) DEFAULT NULL,

    -- 메타데이터
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY userid_unique (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 온보딩 메인 데이터';

-- 2. Rules.yaml 요구사항 반영을 위한 컬럼 추가 (존재하지 않을 경우를 가정하여 실행 필요)
-- 주의: 이미 컬럼이 존재하면 에러가 발생할 수 있으므로, PHP 스크립트나 프로시저로 제어 권장

-- 2.1 수학 학습 스타일 (S0_R1)
-- problem_preference와 유사하지만 rules.yaml 명세에 맞춤
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN math_learning_style VARCHAR(50) DEFAULT NULL COMMENT '수학 학습 스타일 (계산형/개념형/응용형)';

-- 2.2 학원 정보 (S0_R2)
-- 메인 테이블에서 바로 조회 가능하도록 추가
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN academy_name VARCHAR(255) DEFAULT NULL COMMENT '현재 학원명';
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN academy_grade VARCHAR(100) DEFAULT NULL COMMENT '현재 학원 등급/반';
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN academy_schedule VARCHAR(255) DEFAULT NULL COMMENT '학원 수업 일정';

-- 2.3 수학 성적/수준 정량화 (S0_R3)
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN math_recent_score VARCHAR(100) DEFAULT NULL COMMENT '최근 수학 점수 및 등수';
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN math_weak_units TEXT DEFAULT NULL COMMENT '취약 단원 목록';

-- 2.4 교재 정보 (S0_R4)
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN textbooks TEXT DEFAULT NULL COMMENT '사용 중인 교재 목록';

-- 2.5 단원별 마스터링 수준 (S0_R5)
ALTER TABLE mdl_alt42o_onboarding ADD COLUMN math_unit_mastery JSON DEFAULT NULL COMMENT '단원별 마스터링 수준 (완료/진행중/미완료)';

-- 3. 인덱스 추가
CREATE INDEX idx_math_style ON mdl_alt42o_onboarding(math_learning_style);
CREATE INDEX idx_academy ON mdl_alt42o_onboarding(academy_name);

