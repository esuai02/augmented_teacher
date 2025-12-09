-- 파일: mvp_system/database/migrations/002_agent_orchestration.sql
-- Mathking Agentic MVP System - Agent Orchestration Schema Extension
-- MySQL 5.7 compatible
-- Created: 2025-01-03
-- Purpose: 22개 에이전트 오케스트레이션 지원을 위한 스키마 확장

-- ============================================================
-- 1. Rule Changes 테이블 (룰 변경 이력 추적)
-- YAML 룰 파일 수정 이력을 데이터베이스에 기록
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_rule_changes (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(50) NOT NULL COMMENT '에이전트 식별자 (agent01_orchestrator ~ agent22_rl_optimizer)',
    changed_by BIGINT(10) UNSIGNED NOT NULL COMMENT '수정한 사용자 ID (mdl_user 참조)',
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
-- 각 에이전트의 활성화 상태 및 실행 통계 추적
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_agent_status (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(50) NOT NULL UNIQUE COMMENT '에이전트 식별자',
    agent_name VARCHAR(100) NOT NULL COMMENT '에이전트 이름',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부 (1=활성, 0=비활성)',
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
-- 3. Decision Log 테이블 확장 (agent_id 컬럼 추가)
-- 기존 decision_log에 에이전트 추적 기능 추가
-- ============================================================
ALTER TABLE mdl_mvp_decision_log
    ADD COLUMN agent_id VARCHAR(50) DEFAULT NULL COMMENT '실행한 에이전트 ID' AFTER rule_id;

ALTER TABLE mdl_mvp_decision_log
    ADD INDEX idx_agent_id (agent_id);

-- ============================================================
-- 4. 초기 데이터 시드 (22개 에이전트 상태 레코드)
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

-- ============================================================
-- Verification Queries (주석 처리됨 - 수동 검증용)
-- ============================================================
-- SELECT table_name FROM information_schema.tables
-- WHERE table_schema = DATABASE()
-- AND table_name IN ('mdl_mvp_rule_changes', 'mdl_mvp_agent_status');

-- DESCRIBE mdl_mvp_decision_log;

-- SHOW INDEX FROM mdl_mvp_decision_log WHERE Key_name = 'idx_agent_id';

-- SELECT agent_id, agent_name, is_active FROM mdl_mvp_agent_status ORDER BY agent_id;
