-- ========================================
-- Migration: 008_create_learning_sessions_and_verify_view.sql
-- Description: learning_sessions 테이블 생성 및 VIEW 검증
-- Created: 2025-11-13
-- Error Location: db/migrations/008_create_learning_sessions_and_verify_view.sql
-- ========================================

-- ========================================
-- 1. Learning Sessions Table
-- 학습 세션 테이블 생성
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_learning_sessions` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(32) NOT NULL UNIQUE COMMENT '세션 ID',
    `student_id` VARCHAR(32) NOT NULL COMMENT '학생 ID',
    `start_time` BIGINT(10) NOT NULL COMMENT '시작 시간 (Unix timestamp)',
    `end_time` BIGINT(10) DEFAULT NULL COMMENT '종료 시간 (Unix timestamp)',
    `session_end` TIMESTAMP NULL DEFAULT NULL COMMENT '세션 종료 시간 (VIEW용)',
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
    KEY `idx_end_time` (`end_time`),
    KEY `idx_session_end` (`session_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 learning session records';

-- ========================================
-- 2. VIEW 재생성 (확인용)
-- mdl_alt42_v_student_state VIEW가 존재하는지 확인하고 재생성
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

