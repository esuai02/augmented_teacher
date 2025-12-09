-- =========================================================
-- A/B Testing Framework Database Schema
-- Phase 11.1: Database Integration
-- MySQL 5.7 Compatible
-- =========================================================
--
-- Usage: Execute this SQL directly on the server or via
--        db_install.php script
--
-- Tables:
--   1. mdl_quantum_ab_tests - Group assignments
--   2. mdl_quantum_ab_test_outcomes - Learning metrics
--   3. mdl_quantum_ab_test_state_changes - 8D StateVector changes
--   4. mdl_quantum_ab_test_reports - Cached analysis reports
--   5. mdl_quantum_ab_test_config - Test configuration
--
-- Created: 2025-12-09
-- Version: 1.0
-- =========================================================

-- Drop existing tables if needed (uncomment for fresh install)
-- DROP TABLE IF EXISTS mdl_quantum_ab_test_config;
-- DROP TABLE IF EXISTS mdl_quantum_ab_test_reports;
-- DROP TABLE IF EXISTS mdl_quantum_ab_test_state_changes;
-- DROP TABLE IF EXISTS mdl_quantum_ab_test_outcomes;
-- DROP TABLE IF EXISTS mdl_quantum_ab_tests;

-- =========================================================
-- Table 1: A/B Test Group Assignments
-- =========================================================
CREATE TABLE IF NOT EXISTS mdl_quantum_ab_tests (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    test_id VARCHAR(255) NOT NULL COMMENT 'Unique identifier for the A/B test',
    student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
    group_name VARCHAR(50) NOT NULL COMMENT 'control or treatment',
    treatment_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.50 COMMENT 'Ratio of treatment group (0.00-1.00)',
    seed INT(10) DEFAULT 42 COMMENT 'Random seed for reproducibility',
    hash_value DECIMAL(10,8) DEFAULT NULL COMMENT 'Calculated hash value for assignment',
    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of creation',
    timemodified BIGINT(10) NOT NULL COMMENT 'Unix timestamp of last modification',
    PRIMARY KEY (id),
    UNIQUE KEY idx_test_student (test_id, student_id),
    KEY idx_test_group (test_id, group_name),
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores A/B test group assignments for students';

-- =========================================================
-- Table 2: A/B Test Outcomes (Learning Metrics)
-- =========================================================
CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_outcomes (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
    student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
    metric_name VARCHAR(100) NOT NULL COMMENT 'Name of the metric (learning_gain, engagement_rate, effectiveness_score)',
    metric_value DECIMAL(12,4) NOT NULL COMMENT 'Numeric value of the metric',
    session_id VARCHAR(100) DEFAULT NULL COMMENT 'Optional session identifier',
    context_data TEXT DEFAULT NULL COMMENT 'JSON encoded additional context',
    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of recording',
    PRIMARY KEY (id),
    KEY idx_test_metric (test_id, metric_name),
    KEY idx_student_test (student_id, test_id),
    KEY idx_metric_time (metric_name, timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Records learning outcomes for A/B test analysis';

-- =========================================================
-- Table 3: A/B Test State Changes (8D StateVector tracking)
-- =========================================================
CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_state_changes (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
    student_id BIGINT(10) NOT NULL COMMENT 'Moodle user ID',
    dimension_name VARCHAR(100) NOT NULL COMMENT '8D dimension name (cognitive_clarity, emotional_stability, etc.)',
    before_value DECIMAL(10,4) DEFAULT NULL COMMENT 'State value before intervention',
    after_value DECIMAL(10,4) DEFAULT NULL COMMENT 'State value after intervention',
    change_value DECIMAL(10,4) DEFAULT NULL COMMENT 'Calculated change (after - before)',
    intervention_type VARCHAR(50) DEFAULT NULL COMMENT 'Type of intervention applied',
    agent_id INT(10) DEFAULT NULL COMMENT 'Agent that caused the change',
    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of recording',
    PRIMARY KEY (id),
    KEY idx_test_dimension (test_id, dimension_name),
    KEY idx_student_test (student_id, test_id),
    KEY idx_time_dimension (timecreated, dimension_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks 8D StateVector changes during A/B tests';

-- =========================================================
-- Table 4: A/B Test Reports (Cached Analysis Results)
-- =========================================================
CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_reports (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    test_id VARCHAR(255) NOT NULL COMMENT 'Reference to the A/B test',
    report_type VARCHAR(50) NOT NULL COMMENT 'Type of report (overview, metrics, full)',
    report_data LONGTEXT NOT NULL COMMENT 'JSON encoded report data',
    control_size INT(10) DEFAULT NULL COMMENT 'Number of control group participants',
    treatment_size INT(10) DEFAULT NULL COMMENT 'Number of treatment group participants',
    recommendation VARCHAR(20) DEFAULT NULL COMMENT 'ADOPT, CONTINUE, or REJECT',
    confidence VARCHAR(20) DEFAULT NULL COMMENT 'high, medium, low',
    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of report generation',
    valid_until BIGINT(10) DEFAULT NULL COMMENT 'Cache expiration timestamp',
    PRIMARY KEY (id),
    KEY idx_test_type (test_id, report_type),
    KEY idx_recommendation (recommendation),
    KEY idx_valid (valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores cached statistical analysis reports';

-- =========================================================
-- Table 5: A/B Test Configuration
-- =========================================================
CREATE TABLE IF NOT EXISTS mdl_quantum_ab_test_config (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    test_id VARCHAR(255) NOT NULL COMMENT 'Unique test identifier',
    test_name VARCHAR(255) NOT NULL COMMENT 'Human readable test name',
    description TEXT DEFAULT NULL COMMENT 'Test description and hypothesis',
    status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, paused, completed, archived',
    treatment_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.50 COMMENT 'Default treatment ratio',
    min_sample_size INT(10) DEFAULT 100 COMMENT 'Minimum sample size for significance',
    target_metrics TEXT DEFAULT NULL COMMENT 'JSON array of target metric names',
    created_by BIGINT(10) NOT NULL COMMENT 'User ID who created the test',
    timecreated BIGINT(10) NOT NULL COMMENT 'Test creation timestamp',
    timemodified BIGINT(10) NOT NULL COMMENT 'Last modification timestamp',
    timestarted BIGINT(10) DEFAULT NULL COMMENT 'Test start timestamp',
    timeended BIGINT(10) DEFAULT NULL COMMENT 'Test end timestamp',
    PRIMARY KEY (id),
    UNIQUE KEY idx_test_id (test_id),
    KEY idx_status (status),
    KEY idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores A/B test configuration and metadata';

-- =========================================================
-- Insert Default Test Configuration (quantum_v1)
-- =========================================================
INSERT INTO mdl_quantum_ab_test_config
    (test_id, test_name, description, status, treatment_ratio, min_sample_size, target_metrics, created_by, timecreated, timemodified, timestarted)
VALUES
    ('quantum_v1',
     'Quantum Orchestrator A/B Test',
     'Comparing Quantum Orchestrator model (treatment) vs traditional model (control) for learning effectiveness',
     'active',
     0.50,
     100,
     '["learning_gain", "engagement_rate", "effectiveness_score"]',
     2,
     UNIX_TIMESTAMP(),
     UNIX_TIMESTAMP(),
     UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE timemodified = UNIX_TIMESTAMP();

-- =========================================================
-- Verification Queries
-- =========================================================
-- Uncomment to verify table creation:
-- SHOW TABLES LIKE 'mdl_quantum_ab%';
-- DESCRIBE mdl_quantum_ab_tests;
-- DESCRIBE mdl_quantum_ab_test_outcomes;
-- DESCRIBE mdl_quantum_ab_test_state_changes;
-- DESCRIBE mdl_quantum_ab_test_reports;
-- DESCRIBE mdl_quantum_ab_test_config;
-- SELECT * FROM mdl_quantum_ab_test_config;

-- =========================================================
-- 8D StateVector Dimensions Reference
-- =========================================================
-- The 8 dimensions tracked in mdl_quantum_ab_test_state_changes:
--   1. cognitive_clarity    - 인지적 명확성
--   2. emotional_stability  - 정서적 안정성
--   3. attention_level      - 주의력 수준
--   4. motivation_strength  - 동기 강도
--   5. energy_level         - 에너지 수준
--   6. social_connection    - 사회적 연결성
--   7. creative_flow        - 창의적 흐름
--   8. learning_momentum    - 학습 모멘텀

-- =========================================================
-- Related Tables (DB Schema Reference)
-- =========================================================
-- mdl_user - Moodle user table (student_id references this)
-- mdl_user_info_data - Custom user fields (fieldid=22 for role)
