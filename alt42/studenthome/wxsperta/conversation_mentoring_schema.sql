-- Conversation-based Mentoring Schema (Standalone UI friendly)
-- MySQL 5.7 compatible (no JSON column types; use TEXT)

-- 0) Conversation contexts (agent_key 기반)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_conversation_contexts (
  id INT(10) NOT NULL AUTO_INCREMENT,
  conversation_id VARCHAR(64) DEFAULT NULL,
  session_id VARCHAR(255) NOT NULL,
  user_id INT(10) NOT NULL,
  agent_key VARCHAR(64) NOT NULL,
  context_summary TEXT,
  emotion_state VARCHAR(50) DEFAULT 'neutral',

  conversation_phase VARCHAR(50) DEFAULT 'exploration',
  mentoring_year INT(1) DEFAULT 1,
  self_clarity_score INT(3) DEFAULT 0,
  direction_confidence INT(3) DEFAULT 0,
  exploration_breadth INT(5) DEFAULT 0,

  ai_era_competencies TEXT,
  quantum_state TEXT,
  core_philosophy TEXT,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uniq_conversation (conversation_id),
  INDEX idx_user_agent (user_id, agent_key),
  INDEX idx_last_updated (last_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1) Conversation messages (agent_key 기반)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_conversation_messages (
  id INT(10) NOT NULL AUTO_INCREMENT,
  conversation_id VARCHAR(64) DEFAULT NULL,
  session_id VARCHAR(255) NOT NULL,
  user_id INT(10) NOT NULL,
  agent_key VARCHAR(64) NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_session (session_id),
  INDEX idx_conv (conversation_id),
  INDEX idx_user_agent_time (user_id, agent_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Conversation -> WXSPERTA layer extraction (agent_key 기반)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_conversation_layers (
  id INT(10) NOT NULL AUTO_INCREMENT,
  conversation_id VARCHAR(64) DEFAULT NULL,
  session_id VARCHAR(255) NOT NULL,
  user_id INT(10) NOT NULL,
  agent_key VARCHAR(64) NOT NULL,
  message_id INT(10) DEFAULT NULL,
  layer ENUM('worldView', 'context', 'structure', 'process', 'execution', 'reflection', 'transfer', 'abstraction') NOT NULL,
  layer_content TEXT,
  extracted_from TEXT,
  confidence_score DECIMAL(3,2) DEFAULT 0.50,
  is_approved TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_session_layer (session_id, layer),
  INDEX idx_conv_layer (conversation_id, layer),
  INDEX idx_user_agent (user_id, agent_key),
  INDEX idx_message (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1) Conversation threads (long-term)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_conversations (
  id INT(10) NOT NULL AUTO_INCREMENT,
  conversation_id VARCHAR(64) NOT NULL,
  user_id INT(10) NOT NULL,
  agent_key VARCHAR(64) NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_conversation_id (conversation_id),
  INDEX idx_user_agent_last (user_id, agent_key, last_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2) Layer approvals (selective approval for worldView/abstraction)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_layer_approvals (
  id INT(10) NOT NULL AUTO_INCREMENT,
  conversation_id VARCHAR(64) NOT NULL,
  session_id VARCHAR(255) DEFAULT NULL,
  user_id INT(10) NOT NULL,
  agent_key VARCHAR(64) NOT NULL,
  message_id INT(10) DEFAULT NULL,
  layer ENUM('worldView', 'abstraction') NOT NULL,
  proposed_text TEXT,
  status ENUM('pending','approved','rejected','skipped') DEFAULT 'pending',
  approved_text TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  INDEX idx_user_status (user_id, status),
  INDEX idx_conv_layer (conversation_id, layer),
  INDEX idx_agent_layer (user_id, agent_key, layer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Journey tracking (6 phases)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_journey_tracking (
  id INT(10) NOT NULL AUTO_INCREMENT,
  user_id INT(10) NOT NULL,
  phase ENUM('self_awareness', 'world_exploration', 'intersection', 'experimentation', 'capacity_building', 'self_direction') NOT NULL,
  phase_start_date DATE NOT NULL,
  phase_completion_date DATE DEFAULT NULL,
  phase_progress INT(3) DEFAULT 0,
  key_insights TEXT,
  challenges_faced TEXT,
  breakthroughs TEXT,
  next_steps TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_user_phase (user_id, phase),
  INDEX idx_user_date (user_id, phase_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) AI-era competency tracking (4 competencies)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_competency_tracking (
  id INT(10) NOT NULL AUTO_INCREMENT,
  user_id INT(10) NOT NULL,
  competency_type ENUM('discovery','creation','connection','adaptation') NOT NULL,
  competency_score INT(3) DEFAULT 0,
  evidence TEXT,
  measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_user_comp (user_id, competency_type),
  INDEX idx_user_time (user_id, measured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Quantum states tracking
CREATE TABLE IF NOT EXISTS mdl_wxsperta_quantum_states (
  id INT(10) NOT NULL AUTO_INCREMENT,
  user_id INT(10) NOT NULL,
  state_type ENUM('superposition','collapse','observation','entanglement','tunneling') NOT NULL,
  state_description TEXT,
  related_interests TEXT,
  breakthrough_moment TEXT,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_user_state (user_id, state_type),
  INDEX idx_user_time (user_id, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


