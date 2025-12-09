-- ============================================================
-- ALT42 Agent Links System Database Schema - MVP Version
-- ============================================================
-- Created: 2025-10-17
-- Purpose: Minimal Viable Product for inter-agent communication
-- MySQL 5.7 Compatible
--
-- DESIGN PHILOSOPHY:
-- - Simplicity over features (70% complexity reduction)
-- - Stability over flexibility (zero circular reference risk)
-- - Fast implementation (1-2 weeks)
-- - Progressive enhancement (easy to extend)
--
-- MVP SCOPE:
-- - Basic artifact creation and storage
-- - Simple link creation (source → target)
-- - Single-version prompts (no versioning)
-- - Autodiscovery inbox/outbox
-- - Event logging for audit
--
-- EXCLUDED FROM MVP (Phase 2+):
-- - Prompt/output version management
-- - Soft delete pattern
-- - External blob storage
-- - Complex visibility controls
-- - Circular reference validation
-- ============================================================

-- ============================================================
-- 1. Agent Registry Table
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_alt42_agent_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id TINYINT UNSIGNED NOT NULL UNIQUE COMMENT '1-22 agent IDs',
    name VARCHAR(50) NOT NULL COMMENT 'Agent system name',
    title_ko VARCHAR(100) NOT NULL COMMENT 'Korean display title',
    capabilities TEXT NULL COMMENT 'JSON array of agent capabilities',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_agent_id (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registry of 22 agents (MVP - simplified)';

-- ============================================================
-- 2. Analysis Artifacts Table
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_alt42_artifacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artifact_id VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique artifact identifier',
    agent_id TINYINT UNSIGNED NOT NULL COMMENT 'Source agent that created this',
    student_id INT NOT NULL COMMENT 'Student this artifact belongs to',
    task_id VARCHAR(50) NULL COMMENT 'Optional task ID for context',
    summary_text TEXT NOT NULL COMMENT 'Human-readable summary (required)',
    full_data MEDIUMTEXT NULL COMMENT 'Full JSON data (max 16MB)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_artifact_id (artifact_id),
    INDEX idx_student_agent (student_id, agent_id, created_at DESC) COMMENT 'Student artifact list by agent',
    INDEX idx_task_id (task_id) COMMENT 'Task-scoped queries',

    FOREIGN KEY (agent_id) REFERENCES mdl_alt42_agent_registry(agent_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Analysis artifacts from agents (MVP - simplified)';

-- ============================================================
-- 3. Agent Links Table (Core Communication)
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_alt42_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique link identifier',
    source_agent_id TINYINT UNSIGNED NOT NULL COMMENT 'Sender agent ID',
    target_agent_id TINYINT UNSIGNED NOT NULL COMMENT 'Receiver agent ID (1-21)',
    artifact_id VARCHAR(50) NOT NULL COMMENT 'Source artifact being sent',
    student_id INT NOT NULL COMMENT 'Student context',
    task_id VARCHAR(50) NULL COMMENT 'Optional task context',

    -- MVP: Single-version prompt and output (no versioning)
    prompt_text TEXT NULL COMMENT 'Preparation prompt for target agent',
    output_data MEDIUMTEXT NULL COMMENT 'Prepared output data (JSON)',
    render_hint VARCHAR(20) DEFAULT 'text' COMMENT 'Render type: table|cards|timeline|raw|text',

    -- MVP: Simple status (draft/published only)
    status ENUM('draft', 'published') DEFAULT 'draft' COMMENT 'Link publication status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_link_id (link_id),
    INDEX idx_student_target (student_id, target_agent_id, created_at DESC) COMMENT 'Target inbox query',
    INDEX idx_artifact (artifact_id) COMMENT 'Find links by artifact',
    INDEX idx_task_id (task_id) COMMENT 'Task-scoped link queries',

    FOREIGN KEY (source_agent_id) REFERENCES mdl_alt42_agent_registry(agent_id) ON DELETE CASCADE,
    FOREIGN KEY (target_agent_id) REFERENCES mdl_alt42_agent_registry(agent_id) ON DELETE CASCADE,
    FOREIGN KEY (artifact_id) REFERENCES mdl_alt42_artifacts(artifact_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Links between agents (MVP - simplified)';

-- ============================================================
-- 4. Event Log Table
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_alt42_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique event identifier',
    event_type VARCHAR(50) NOT NULL COMMENT 'Event type (link.created, link.published, etc)',
    link_id VARCHAR(50) NULL COMMENT 'Related link ID',
    student_id INT NOT NULL COMMENT 'Student context for filtering',
    payload TEXT NULL COMMENT 'JSON event data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event_id (event_id),
    INDEX idx_link_id (link_id) COMMENT 'Find events for link',
    INDEX idx_student_created (student_id, created_at DESC) COMMENT 'Student event timeline',

    FOREIGN KEY (link_id) REFERENCES mdl_alt42_links(link_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Event log for audit trail (MVP - simplified)';

-- ============================================================
-- 5. Audit Log Table
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_alt42_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL COMMENT 'Table name (artifacts, links, etc)',
    entity_id VARCHAR(50) NOT NULL COMMENT 'Record ID in that table',
    action VARCHAR(50) NOT NULL COMMENT 'Action performed (create, update, delete)',
    changed_by VARCHAR(50) NOT NULL COMMENT 'User or system that made change',
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_entity (entity_type, entity_id) COMMENT 'Find changes for entity',
    INDEX idx_changed_at (changed_at DESC) COMMENT 'Recent changes timeline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit trail (MVP - minimal)';

-- ============================================================
-- 6. Initialize Agent Registry with 22 Agents
-- ============================================================
INSERT INTO mdl_alt42_agent_registry (agent_id, name, title_ko, capabilities) VALUES
(1, 'agent01_onboarding', '온보딩', '["profile", "history", "initialization"]'),
(2, 'agent02_exam_schedule', '시험일정 식별', '["schedule", "context", "urgency"]'),
(3, 'agent03_goals_analysis', '목표 및 계획 분석', '["goals", "planning", "alignment"]'),
(4, 'agent04_problem_activity', '문제활동 식별', '["activity", "problem-solving", "patterns"]'),
(5, 'agent05_learning_emotion', '학습감정 분석', '["emotion", "sentiment", "motivation"]'),
(6, 'agent06_teacher_feedback', '선생님 피드백', '["feedback", "guidance", "instruction"]'),
(7, 'agent07_interaction_targeting', '상호작용 타게팅', '["targeting", "prioritization", "intervention"]'),
(8, 'agent08_calmness', '침착도 분석', '["biometric", "stress", "calmness"]'),
(9, 'agent09_learning_management', '학습관리 분석', '["attendance", "goals", "pomodoro", "notes", "tests"]'),
(10, 'agent10_concept_notes', '개념노트 분석', '["concept", "notes", "understanding"]'),
(11, 'agent11_problem_notes', '문제노트 분석', '["errors", "patterns", "correction"]'),
(12, 'agent12_rest_routine', '휴식루틴 분석', '["rest", "energy", "recovery"]'),
(13, 'agent13_learning_dropout', '학습이탈 분석', '["dropout", "prevention", "engagement"]'),
(14, 'agent14_current_position', '현재위치 평가', '["progress", "risk", "status"]'),
(15, 'agent15_problem_redefinition', '문제 재정의 & 개선방안', '["redefine", "solutions", "strategy"]'),
(16, 'agent16_interaction_preparation', '상호작용 준비', '["mode", "strategy", "preparation"]'),
(17, 'agent17_remaining_activities', '잔여활동 조정', '["adjustment", "planning", "booster"]'),
(18, 'agent18_signature_routine', '시그너처 루틴 찾기', '["routine", "optimization", "signature"]'),
(19, 'agent19_interaction_content', '상호작용 컨텐츠 생성', '["content", "personalization", "delivery"]'),
(20, 'agent20_intervention_preparation', '개입준비', '["planning", "preparation", "setup"]'),
(21, 'agent21_intervention_execution', '개입실행', '["execution", "intervention", "delivery"]'),
(22, 'agent22_module_improvement', '모듈성능 개선 제안', '["improvement", "optimization", "feedback"]')
ON DUPLICATE KEY UPDATE
    name=VALUES(name),
    title_ko=VALUES(title_ko),
    capabilities=VALUES(capabilities);

-- ============================================================
-- 7. Schema Validation Checks
-- ============================================================

-- Verify all tables created
SELECT
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'mdl_alt42_%'
ORDER BY TABLE_NAME;

-- Verify all foreign keys
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'mdl_alt42_%'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- Verify all indexes
SELECT
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns,
    NON_UNIQUE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'mdl_alt42_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

-- ============================================================
-- End of MVP schema
-- ============================================================
--
-- MVP SUMMARY:
-- - Tables: 5 (vs 8 in V2 Full) - 37% reduction
-- - Foreign Keys: 4 (vs 12 in V2 Full) - 67% reduction
-- - Indexes: 13 (vs 43 in V2 Full) - 70% reduction
-- - Installation: Single-step (vs 2-step in V2 Full)
-- - Complexity: Low (no circular refs, no soft delete, no versioning)
--
-- NEXT STEPS:
-- 1. Run test_integrity_mvp.sql for validation
-- 2. Implement basic API endpoints:
--    - POST /api/artifacts
--    - POST /api/links
--    - GET /api/inbox/:agent_id/:student_id
-- 3. Implement basic UI:
--    - Agent popup with summary/target/prompt/output
--    - Inbox list for target agents
--
-- FUTURE ENHANCEMENTS (Phase 2+):
-- - Add link_history table for prompt versions
-- - Add soft delete to links table
-- - Add blob storage support to artifacts
-- - Add prep_prompts/outputs tables for full versioning
--
-- ERROR MESSAGE FORMAT (for PHP):
-- All exceptions must include file and line:
-- throw new Exception("Error message - File: " . __FILE__ . ", Line: " . __LINE__);
-- ============================================================
