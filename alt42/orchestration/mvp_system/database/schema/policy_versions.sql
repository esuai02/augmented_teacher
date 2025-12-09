-- File: database/schema/policy_versions.sql
-- MVP Policy Versions Table Schema
-- Pure SQL - No Moodle dependencies

CREATE TABLE mdl_mvp_policy_versions (
  id BIGINT(10) NOT NULL AUTO_INCREMENT,
  policy_source VARCHAR(50) NOT NULL COMMENT 'Agent identifier (e.g., agent_01)',
  file_path VARCHAR(255) NOT NULL COMMENT 'Path to policy markdown file',
  version_hash VARCHAR(64) NOT NULL COMMENT 'MD5 hash of policy content',
  parsed_rules LONGTEXT NOT NULL COMMENT 'JSON-encoded policy rules',
  is_active TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Active status (0=inactive, 1=active)',
  activated_at BIGINT(10) DEFAULT NULL COMMENT 'Unix timestamp of activation',
  deactivated_at BIGINT(10) DEFAULT NULL COMMENT 'Unix timestamp of deactivation',
  author VARCHAR(100) DEFAULT NULL COMMENT 'Policy author username',
  created_at BIGINT(10) NOT NULL COMMENT 'Unix timestamp of creation',

  PRIMARY KEY (id),
  INDEX idx_active (is_active, policy_source),
  INDEX idx_hash (version_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Policy versioning for MVP system';
