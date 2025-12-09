-- 파일: mvp_system/database/migrations/002_agent_orchestration_safe.sql
-- Mathking Agentic MVP System - Agent Orchestration Schema Extension (SAFE VERSION)
-- MySQL 5.7 compatible with idempotent execution
-- Created: 2025-01-03
-- Purpose: 22개 에이전트 오케스트레이션 지원을 위한 스키마 확장 (재실행 가능)

-- ============================================================
-- 1. Rule Changes 테이블 (룰 변경 이력 추적)
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_rule_changes (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(50) NOT NULL COMMENT '에이전트 식별자',
    changed_by BIGINT(10) UNSIGNED NOT NULL COMMENT '수정한 사용자 ID',
    change_type ENUM('create', 'update', 'delete', 'rollback') NOT NULL COMMENT '변경 유형',
    old_content LONGTEXT DEFAULT NULL COMMENT '변경 전 YAML 내용',
    new_content LONGTEXT DEFAULT NULL COMMENT '변경 후 YAML 내용',
    change_summary VARCHAR(500) DEFAULT NULL COMMENT '변경 요약',
    timestamp DATETIME NOT NULL COMMENT '변경 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_agent_id (agent_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_change_type (change_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='룰 변경 이력';

-- ============================================================
-- 2. Agent Status 테이블 (에이전트 상태 관리)
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_agent_status (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(50) NOT NULL UNIQUE COMMENT '에이전트 식별자',
    agent_name VARCHAR(100) NOT NULL COMMENT '에이전트 이름',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    execution_count INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '총 실행 횟수',
    success_count INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '성공 실행 횟수',
    error_count INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '에러 발생 횟수',
    avg_execution_time DECIMAL(10,2) DEFAULT NULL COMMENT '평균 실행 시간 (ms)',
    last_execution_at DATETIME DEFAULT NULL COMMENT '마지막 실행 시각',
    last_error_at DATETIME DEFAULT NULL COMMENT '마지막 에러 시각',
    last_error_msg TEXT DEFAULT NULL COMMENT '마지막 에러 메시지',
    config_data TEXT DEFAULT NULL COMMENT '에이전트 설정 (JSON)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_agent_id (agent_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_execution (last_execution_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='에이전트 상태 및 통계';

-- ============================================================
-- 3. 초기 데이터 시드 (22개 에이전트 상태 레코드)
-- ============================================================
INSERT INTO mdl_mvp_agent_status (agent_id, agent_name, is_active) VALUES
('agent01_onboarding', 'Onboarding', 1),
('agent02_exam_schedule', 'Exam Schedule', 1),
('agent03_goals_analysis', 'Goals Analysis', 1),
('agent04_problem_activity', 'Problem Activity', 1),
('agent05_learning_emotion', 'Learning Emotion', 1),
('agent06_teacher_feedback', 'Teacher Feedback', 1),
('agent07_interaction_targeting', 'Interaction Targeting', 1),
('agent08_calmness', 'Calmness', 1),
('agent09_learning_management', 'Learning Management', 1),
('agent10_concept_notes', 'Concept Notes', 1),
('agent11_problem_notes', 'Problem Notes', 1),
('agent12_rest_routine', 'Rest Routine', 1),
('agent13_learning_dropout', 'Learning Dropout', 1),
('agent14_current_position', 'Current Position', 1),
('agent15_problem_redefinition', 'Problem Redefinition', 1),
('agent16_interaction_preparation', 'Interaction Preparation', 1),
('agent17_remaining_activities', 'Remaining Activities', 1),
('agent18_signature_routine', 'Signature Routine', 1),
('agent19_interaction_content', 'Interaction Content', 1),
('agent20_intervention_preparation', 'Intervention Preparation', 1),
('agent21_intervention_execution', 'Intervention Execution', 1),
('agent22_module_improvement', 'Module Improvement', 1)
ON DUPLICATE KEY UPDATE agent_name = VALUES(agent_name);
