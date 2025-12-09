-- ========================================
-- Migration: 006_create_heartbeat_views.sql
-- Description: Heartbeat scheduler를 위한 학생 상태 뷰 생성
-- Created: 2025-01-27
-- Error Location: db/migrations/006_create_heartbeat_views.sql
-- ========================================

-- ========================================
-- 1. Student State View (Heartbeat용)
-- Heartbeat scheduler에서 사용하는 학생 상태 뷰
-- heartbeat.php의 getStudentState() 메서드에서 사용
-- ========================================
CREATE OR REPLACE VIEW `mdl_alt42_v_student_state` AS
SELECT 
    COALESCE(s.student_id, u.id) AS student_id,
    
    -- 기본 정보
    s.mbti,
    s.grade,
    s.class,
    
    -- 감정 및 몰입 상태
    COALESCE(sp.emotion_state, 'neutral') AS emotion_state,
    COALESCE(sp.immersion_level, 5.0) AS immersion_level,
    
    -- 생체 신호
    COALESCE(sb.stress_level, 0.0) AS stress_level,
    COALESCE(sb.concentration_level, 5.0) AS concentration_level,
    
    -- 학습 지표
    COALESCE(sp.engagement_score, 0.0) AS engagement_score,
    COALESCE(sp.math_confidence, 5.0) AS math_confidence,
    
    -- 타임스탬프
    GREATEST(
        COALESCE(sp.updated_at, '1970-01-01 00:00:00'),
        COALESCE(sb.updated_at, '1970-01-01 00:00:00'),
        COALESCE(s.updated_at, '1970-01-01 00:00:00')
    ) AS updated_at
    
FROM mdl_user u
LEFT JOIN mdl_alt42_students s ON u.id = s.student_id
LEFT JOIN mdl_alt42_student_profiles sp ON COALESCE(s.student_id, u.id) = COALESCE(sp.student_id, sp.user_id)
LEFT JOIN mdl_alt42_student_biometrics sb ON COALESCE(s.student_id, u.id) = sb.student_id
WHERE u.deleted = 0;

-- ========================================
-- 2. Student Activity Table (Fallback용)
-- heartbeat.php의 getActiveStudents()에서 fallback으로 사용
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_student_activity` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL COMMENT '학생 ID',
    `activity_type` VARCHAR(50) COMMENT '활동 유형',
    `activity_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '활동 날짜',
    `activity_data` JSON COMMENT '활동 데이터',
    
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_activity_date` (`activity_date`),
    INDEX `idx_student_activity_date` (`student_id`, `activity_date`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='학생 활동 로그 (Heartbeat fallback용)';

-- ========================================
-- 3. Learning Sessions Table 수정 (session_end 컬럼 추가)
-- heartbeat.php의 getActiveStudents()에서 사용
-- ========================================
-- session_end 컬럼이 없으면 추가 (TIMESTAMP 타입)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_learning_sessions'
    AND COLUMN_NAME = 'session_end'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `mdl_alt42_learning_sessions` ADD COLUMN `session_end` TIMESTAMP NULL COMMENT ''세션 종료 시간'' AFTER `end_time`',
    'SELECT ''Column session_end already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- session_end에 인덱스 추가 (없으면)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mdl_alt42_learning_sessions'
    AND INDEX_NAME = 'idx_session_end'
);

SET @sql_idx = IF(@idx_exists = 0,
    'ALTER TABLE `mdl_alt42_learning_sessions` ADD INDEX `idx_session_end` (`session_end`)',
    'SELECT ''Index idx_session_end already exists'' AS message'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

