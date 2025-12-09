-- 학습 진도 테이블 생성
-- MathKing 데이터베이스에서 실행

CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_progress` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
    `math_level` VARCHAR(50) DEFAULT NULL COMMENT '현재 수학 실력 수준',

    -- 개념 진도
    `concept_level` VARCHAR(100) DEFAULT NULL COMMENT '개념 학습 레벨',
    `concept_progress` INT(3) DEFAULT 0 COMMENT '개념 진도율 (0-100)',
    `concept_details` TEXT DEFAULT NULL COMMENT '개념 학습 상세 내용',

    -- 심화 진도
    `advanced_level` VARCHAR(100) DEFAULT NULL COMMENT '심화 학습 레벨',
    `advanced_progress` INT(3) DEFAULT 0 COMMENT '심화 진도율 (0-100)',
    `advanced_details` TEXT DEFAULT NULL COMMENT '심화 학습 상세 내용',

    -- 추가 정보
    `notes` TEXT DEFAULT NULL COMMENT '비고 및 특이사항',
    `weekly_hours` DECIMAL(5,2) DEFAULT NULL COMMENT '주당 학습 시간',
    `academy_experience` TEXT DEFAULT NULL COMMENT '학원 경험',

    -- 타임스탬프
    `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
    `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',

    PRIMARY KEY (`id`),
    KEY `mdl_alt42g_leapro_use_ix` (`userid`),
    KEY `mdl_alt42g_leapro_tim_ix` (`timemodified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 진도 정보';

-- 학습 스타일 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_style` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',

    -- 학습 선호도
    `problem_preference` VARCHAR(50) DEFAULT NULL COMMENT '문제 선호도 (easy/balanced/challenge)',
    `exam_style` VARCHAR(50) DEFAULT NULL COMMENT '시험 대비 스타일 (concept/types/intensive)',
    `math_confidence` INT(2) DEFAULT 5 COMMENT '수학 자신감 (1-10)',

    -- 학습 환경
    `parent_style` VARCHAR(50) DEFAULT NULL COMMENT '부모님 관여도 (direct/indirect/independent)',
    `stress_level` VARCHAR(50) DEFAULT NULL COMMENT '스트레스 수준 (low/medium/high)',
    `feedback_preference` VARCHAR(50) DEFAULT NULL COMMENT '피드백 선호도 (immediate/summary/minimal)',

    -- 타임스탬프
    `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
    `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',

    PRIMARY KEY (`id`),
    UNIQUE KEY `mdl_alt42g_leasty_use_uix` (`userid`),
    KEY `mdl_alt42g_leasty_tim_ix` (`timemodified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 스타일 정보';

-- 학습 목표 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_goals` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',

    -- 목표 설정
    `short_term_goal` TEXT DEFAULT NULL COMMENT '단기 목표 (1-3개월)',
    `mid_term_goal` TEXT DEFAULT NULL COMMENT '중기 목표 (6개월)',
    `long_term_goal` TEXT DEFAULT NULL COMMENT '장기 목표 (1년)',
    `goal_note` TEXT DEFAULT NULL COMMENT '목표 관련 메모',

    -- 타임스탬프
    `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
    `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',

    PRIMARY KEY (`id`),
    UNIQUE KEY `mdl_alt42g_leagoa_use_uix` (`userid`),
    KEY `mdl_alt42g_leagoa_tim_ix` (`timemodified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 목표 정보';

-- 온보딩 완료 상태 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42g_onboarding_status` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
    `basic_info_completed` TINYINT(1) DEFAULT 0 COMMENT '기본정보 완료',
    `learning_progress_completed` TINYINT(1) DEFAULT 0 COMMENT '학습진도 완료',
    `learning_style_completed` TINYINT(1) DEFAULT 0 COMMENT '학습스타일 완료',
    `learning_goals_completed` TINYINT(1) DEFAULT 0 COMMENT '학습목표 완료',
    `data_consent` TINYINT(1) DEFAULT 0 COMMENT '개인정보 동의',
    `overall_completed` TINYINT(1) DEFAULT 0 COMMENT '전체 완료',
    `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
    `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',

    PRIMARY KEY (`id`),
    UNIQUE KEY `mdl_alt42g_onbsta_use_uix` (`userid`),
    KEY `mdl_alt42g_onbsta_tim_ix` (`timemodified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='온보딩 완료 상태';