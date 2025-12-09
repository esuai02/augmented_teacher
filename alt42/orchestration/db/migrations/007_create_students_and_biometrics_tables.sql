-- ========================================
-- Migration: 007_create_students_and_biometrics_tables.sql
-- Description: Heartbeat VIEW 생성을 위한 학생 및 생체신호 테이블 생성
-- Created: 2025-11-13
-- Error Location: db/migrations/007_create_students_and_biometrics_tables.sql
-- ========================================

-- ========================================
-- 1. Students Table
-- 학생 기본 정보 테이블
-- VIEW에서 사용: student_id, mbti, grade, class, updated_at
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_students` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `student_id` VARCHAR(32) NOT NULL UNIQUE COMMENT '학생 ID (mdl_user.id와 매핑)',
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
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='ALT42 student basic information';

-- ========================================
-- 2. Student Biometrics Table
-- 학생 생체신호 데이터 테이블
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
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='ALT42 student biometric data';

-- ========================================
-- 3. Student Profiles Table - 컬럼 추가
-- VIEW에서 사용하는 컬럼 추가
-- ========================================
-- student_id 컬럼 추가 (없으면) - VIEW JOIN을 위해 필요
SET @col_exists_student_id = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'student_id'
);

SET @sql_student_id = IF(@col_exists_student_id = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `student_id` VARCHAR(32) COMMENT ''학생 ID (VIEW JOIN용)'' AFTER `user_id`',
    'SELECT ''Column student_id already exists'' AS message'
);

PREPARE stmt_student_id FROM @sql_student_id;
EXECUTE stmt_student_id;
DEALLOCATE PREPARE stmt_student_id;

-- emotion_state 컬럼 추가 (없으면)
SET @col_exists_emotion = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'emotion_state'
);

SET @sql_emotion = IF(@col_exists_emotion = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `emotion_state` VARCHAR(20) DEFAULT ''neutral'' COMMENT ''감정 상태'' AFTER `mbti_type`',
    'SELECT ''Column emotion_state already exists'' AS message'
);

PREPARE stmt_emotion FROM @sql_emotion;
EXECUTE stmt_emotion;
DEALLOCATE PREPARE stmt_emotion;

-- immersion_level 컬럼 추가 (없으면)
SET @col_exists_immersion = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'immersion_level'
);

SET @sql_immersion = IF(@col_exists_immersion = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `immersion_level` DECIMAL(5,2) DEFAULT 5.0 COMMENT ''몰입도 수준 (0-10)'' AFTER `emotion_state`',
    'SELECT ''Column immersion_level already exists'' AS message'
);

PREPARE stmt_immersion FROM @sql_immersion;
EXECUTE stmt_immersion;
DEALLOCATE PREPARE stmt_immersion;

-- engagement_score 컬럼 추가 (없으면)
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

-- math_confidence 컬럼 추가 (없으면)
SET @col_exists_confidence = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_student_profiles'
    AND COLUMN_NAME = 'math_confidence'
);

SET @sql_confidence = IF(@col_exists_confidence = 0,
    'ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `math_confidence` DECIMAL(5,2) DEFAULT 5.0 COMMENT ''수학 자신감 (0-10)'' AFTER `engagement_score`',
    'SELECT ''Column math_confidence already exists'' AS message'
);

PREPARE stmt_confidence FROM @sql_confidence;
EXECUTE stmt_confidence;
DEALLOCATE PREPARE stmt_confidence;

