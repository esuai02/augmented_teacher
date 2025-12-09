-- ========================================
-- Migration: 004_create_base_tables.sql
-- Description: Heartbeat VIEW를 위한 기본 테이블 생성
-- Created: 2025-01-27
-- Error Location: db/migrations/004_create_base_tables.sql
-- ========================================

-- ========================================
-- 1. Students Table (기본 학생 정보)
-- VIEW에서 사용: student_id, mbti, grade, class, updated_at
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_students` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `student_id` VARCHAR(32) NOT NULL UNIQUE COMMENT '학생 ID',
    `userid` BIGINT(10) DEFAULT NULL COMMENT 'Moodle user ID reference',
    `grade` INT(2) DEFAULT NULL COMMENT '학년',
    `class` VARCHAR(10) DEFAULT NULL COMMENT '반',
    `mbti` VARCHAR(4) DEFAULT NULL COMMENT 'MBTI 유형',
    `profile_info` LONGTEXT DEFAULT NULL COMMENT '프로필 정보 (JSON)',
    `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간 (Unix timestamp)',
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간 (VIEW용)',
    PRIMARY KEY (`id`),
    KEY `mdl_alt42stud_stuid_ix` (`student_id`),
    KEY `mdl_alt42stud_userid_ix` (`userid`),
    KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 student basic information';

-- ========================================
-- 2. Student Biometrics Table (생체 신호)
-- VIEW에서 사용: student_id, stress_level, concentration_level, updated_at
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_student_biometrics` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `student_id` VARCHAR(32) NOT NULL COMMENT '학생 ID',
    `stress_level` DECIMAL(5,2) DEFAULT 0.0 COMMENT '스트레스 레벨 (0-10)',
    `concentration_level` DECIMAL(5,2) DEFAULT 5.0 COMMENT '집중도 레벨 (0-10)',
    `heart_rate` INT(11) DEFAULT NULL COMMENT '심박수',
    `bio_data` LONGTEXT DEFAULT NULL COMMENT '생체 데이터 (JSON)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간',
    PRIMARY KEY (`id`),
    KEY `mdl_alt42bio_stuid_ix` (`student_id`),
    KEY `idx_stress_level` (`stress_level`),
    KEY `idx_concentration_level` (`concentration_level`),
    KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 student biometric data';

-- ========================================
-- 3. Learning Sessions Table (학습 세션)
-- ALTER TABLE에서 사용: end_time 컬럼 필요
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_learning_sessions` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(32) NOT NULL UNIQUE COMMENT '세션 ID',
    `student_id` VARCHAR(32) NOT NULL COMMENT '학생 ID',
    `start_time` BIGINT(10) NOT NULL COMMENT '시작 시간 (Unix timestamp)',
    `end_time` BIGINT(10) DEFAULT NULL COMMENT '종료 시간 (Unix timestamp)',
    `activity_type` VARCHAR(50) DEFAULT NULL COMMENT '활동 유형',
    `completion_rate` DECIMAL(5,2) DEFAULT NULL COMMENT '완료율 (%)',
    `engagement_time` INT(11) DEFAULT NULL COMMENT '참여 시간 (초)',
    `focus_time` INT(11) DEFAULT NULL COMMENT '집중 시간 (초)',
    `performance` DECIMAL(5,2) DEFAULT NULL COMMENT '성과 점수',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간',
    PRIMARY KEY (`id`),
    KEY `mdl_alt42sess_sid_ix` (`session_id`),
    KEY `mdl_alt42sess_stuid_ix` (`student_id`),
    KEY `mdl_alt42sess_start_ix` (`start_time`),
    KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 learning session records';

-- ========================================
-- 4. Student Profiles Table 업데이트 (VIEW 호환성)
-- VIEW에서 사용: emotion_state, immersion_level, engagement_score, math_confidence, updated_at
-- ========================================
-- 테이블이 이미 존재하는 경우 컬럼 추가
SET @col_exists_emotion = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'emotion_state'
);

SET @sql_emotion = IF(@col_exists_emotion = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `emotion_state` VARCHAR(20) DEFAULT ''neutral'' COMMENT ''감정 상태'' AFTER `learning_style`',
    'SELECT ''Column emotion_state already exists'' AS message'
);

PREPARE stmt_emotion FROM @sql_emotion;
EXECUTE stmt_emotion;
DEALLOCATE PREPARE stmt_emotion;

SET @col_exists_immersion = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'immersion_level'
);

SET @sql_immersion = IF(@col_exists_immersion = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `immersion_level` DECIMAL(5,2) DEFAULT 5.0 COMMENT ''몰입도 레벨 (0-10)'' AFTER `emotion_state`',
    'SELECT ''Column immersion_level already exists'' AS message'
);

PREPARE stmt_immersion FROM @sql_immersion;
EXECUTE stmt_immersion;
DEALLOCATE PREPARE stmt_immersion;

SET @col_exists_engagement = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'engagement_score'
);

SET @sql_engagement = IF(@col_exists_engagement = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `engagement_score` DECIMAL(5,2) DEFAULT 0.0 COMMENT ''참여도 점수 (0-10)'' AFTER `immersion_level`',
    'SELECT ''Column engagement_score already exists'' AS message'
);

PREPARE stmt_engagement FROM @sql_engagement;
EXECUTE stmt_engagement;
DEALLOCATE PREPARE stmt_engagement;

SET @col_exists_math_confidence = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'math_confidence'
);

SET @sql_math_confidence = IF(@col_exists_math_confidence = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `math_confidence` DECIMAL(5,2) DEFAULT 5.0 COMMENT ''수학 자신감 (0-10)'' AFTER `engagement_score`',
    'SELECT ''Column math_confidence already exists'' AS message'
);

PREPARE stmt_math_confidence FROM @sql_math_confidence;
EXECUTE stmt_math_confidence;
DEALLOCATE PREPARE stmt_math_confidence;

-- updated_at 컬럼이 TIMESTAMP인지 확인하고 없으면 추가
SET @col_exists_updated_at = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'updated_at'
);

SET @col_type_updated_at = (
    SELECT DATA_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'updated_at'
);

SET @sql_updated_at = IF(@col_exists_updated_at = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT ''업데이트 시간 (VIEW용)''',
    IF(@col_type_updated_at = 'bigint',
       'ALTER TABLE `mdl_alt42_student_profiles` MODIFY COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT ''업데이트 시간 (VIEW용)''',
       'SELECT ''Column updated_at already exists with correct type'' AS message'
    )
);

PREPARE stmt_updated_at FROM @sql_updated_at;
EXECUTE stmt_updated_at;
DEALLOCATE PREPARE stmt_updated_at;

