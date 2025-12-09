-- ========================================
-- Migration: 005_create_heartbeat_and_state_change_tables.sql
-- Description: Heartbeat scheduler 및 State Change Detector를 위한 테이블 생성
-- Created: 2025-01-27
-- Error Location: db/migrations/005_create_heartbeat_and_state_change_tables.sql
-- ========================================

-- ========================================
-- 1. Heartbeat Log Table
-- Heartbeat 스케줄러 실행 로그 저장
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_heartbeat_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `execution_time` TIMESTAMP NOT NULL COMMENT '실행 시간',
    `students_processed` INT UNSIGNED DEFAULT 0 COMMENT '처리된 학생 수',
    `errors` INT UNSIGNED DEFAULT 0 COMMENT '에러 발생 수',
    `duration_ms` DECIMAL(10,2) COMMENT '실행 시간 (밀리초)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    
    INDEX `idx_execution_time` (`execution_time`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='Heartbeat 스케줄러 실행 로그';

-- ========================================
-- 2. State Change Log Table
-- 학생 상태 변화 감지 로그 저장
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_state_change_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL COMMENT '학생 ID',
    `changed_fields` JSON COMMENT '변화된 필드 정보',
    `relevant_rules` JSON COMMENT '관련 룰 정보',
    `triggered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '트리거 발생 시간',
    
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_triggered_at` (`triggered_at`),
    INDEX `idx_student_triggered` (`student_id`, `triggered_at`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='학생 상태 변화 감지 로그';

-- ========================================
-- 3. Event Processing Log Table (확장)
-- 이벤트 처리 로그 저장 (기존 테이블이 있다면 확장, 없으면 생성)
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_event_processing_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(36) NOT NULL COMMENT '이벤트 ID (UUID)',
    `event_type` VARCHAR(100) NOT NULL COMMENT '이벤트 타입',
    `student_id` VARCHAR(20) COMMENT '학생 ID',
    `scenarios_evaluated` INT UNSIGNED DEFAULT 0 COMMENT '평가된 시나리오 수',
    `scenario_results` JSON COMMENT '시나리오 평가 결과',
    `routing_result` JSON COMMENT '라우팅 결과',
    `priority` TINYINT UNSIGNED DEFAULT 5 COMMENT '우선순위 (1-10)',
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending' COMMENT '처리 상태',
    `processed_at` TIMESTAMP NULL COMMENT '처리 완료 시간',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    
    INDEX `idx_event_id` (`event_id`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_event_type_status` (`event_type`, `status`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='이벤트 처리 로그';

-- ========================================
-- 4. Student State Cache Table (선택사항)
-- 학생 상태 캐시 저장 (State Change Detector용)
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_student_state_cache` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL COMMENT '학생 ID',
    `state_data` JSON COMMENT '상태 데이터',
    `cached_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '캐시 시간',
    `expires_at` TIMESTAMP NULL COMMENT '만료 시간',
    
    UNIQUE KEY `unique_student_id` (`student_id`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_cached_at` (`cached_at`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='학생 상태 캐시 (State Change Detector용)';

-- ========================================
-- 5. Scenario Evaluation Log Table
-- 시나리오 평가 로그 저장
-- ========================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_scenario_evaluation_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(36) COMMENT '이벤트 ID',
    `scenario_id` VARCHAR(10) NOT NULL COMMENT '시나리오 ID (S0, S1, etc.)',
    `student_id` VARCHAR(20) COMMENT '학생 ID',
    `rules_count` INT UNSIGNED DEFAULT 0 COMMENT '평가된 룰 수',
    `evaluation_mode` VARCHAR(20) DEFAULT 'priority_first' COMMENT '평가 모드',
    `evaluation_result` JSON COMMENT '평가 결과',
    `evaluated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '평가 시간',
    
    INDEX `idx_event_id` (`event_id`),
    INDEX `idx_scenario_id` (`scenario_id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_evaluated_at` (`evaluated_at`),
    INDEX `idx_scenario_student` (`scenario_id`, `student_id`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='시나리오 평가 로그';

