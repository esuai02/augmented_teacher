-- =========================================================
-- Rule-Quantum Bridge Database Schema
-- Migration 010: Create Rule Quantum State Table
-- MySQL 5.7 Compatible
-- =========================================================
--
-- Purpose: Store 4-layer probability calculations and wave function
--          parameters for the Rule-Quantum Bridge system
--
-- Part of Phase 1: Agent04-centric expansion
--
-- Created: 2025-12-09
-- Version: 1.0
-- =========================================================

-- Drop existing table if needed (uncomment for fresh install)
-- DROP TABLE IF EXISTS mdl_at_rule_quantum_state;

-- =========================================================
-- Table: mdl_at_rule_quantum_state
-- =========================================================
-- Stores the quantum state calculations for each rule evaluation
-- including 4-layer probability values and wave function parameters
-- =========================================================

CREATE TABLE IF NOT EXISTS mdl_at_rule_quantum_state (
    -- Primary key
    id BIGINT(10) NOT NULL AUTO_INCREMENT,

    -- Context identifiers
    studentid BIGINT(10) NOT NULL COMMENT 'Moodle user ID (mdl_user.id)',
    sessionid VARCHAR(50) NOT NULL COMMENT 'Learning session identifier',
    agentid INT(3) NOT NULL COMMENT 'Agent number (1-22)',
    ruleid VARCHAR(100) NOT NULL COMMENT 'Unique rule identifier from rules.yaml',

    -- 4-Layer Probability Values
    layer1_rule_conf DECIMAL(6,5) DEFAULT 0.00000 COMMENT 'Layer 1: Rule Confidence = confidence × (priority/100) × condition_match',
    layer2_wave_prob DECIMAL(6,5) DEFAULT 0.00000 COMMENT 'Layer 2: Wave Function Probability = |⟨ψ_agent|ψ_target⟩|²',
    layer3_corr_inf DECIMAL(6,5) DEFAULT 0.00000 COMMENT 'Layer 3: Correlation Influence = Σ(C_ij × P_j) / 21',
    layer4_final DECIMAL(6,5) DEFAULT 0.00000 COMMENT 'Layer 4: Final HYBRID probability = sigmoid(weighted_sum)',

    -- Wave Function Parameters (JSON)
    wave_params TEXT DEFAULT NULL COMMENT 'JSON: 13 wave function parameters (amplitude, phase, coupling, etc.)',

    -- StateVector snapshot (JSON)
    state_vector TEXT DEFAULT NULL COMMENT 'JSON: 8D StateVector at evaluation time',

    -- Intervention tracking
    intervention_type VARCHAR(50) DEFAULT NULL COMMENT 'IMMEDIATE_INTERVENTION, PROBABILISTIC_GATING, WEIGHT_ADJUSTMENT, OBSERVE_ONLY',
    intervention_executed TINYINT(1) DEFAULT 0 COMMENT '0=not executed, 1=executed',
    intervention_result TEXT DEFAULT NULL COMMENT 'JSON: Result of intervention if executed',

    -- Rule metadata snapshot
    rule_priority INT(3) DEFAULT 0 COMMENT 'Rule priority at evaluation time (0-100)',
    rule_confidence DECIMAL(4,3) DEFAULT 0.000 COMMENT 'Rule confidence at evaluation time (0-1)',
    conditions_matched INT(3) DEFAULT 0 COMMENT 'Number of conditions that matched',
    conditions_total INT(3) DEFAULT 0 COMMENT 'Total number of conditions in rule',

    -- Timestamps
    timecreated BIGINT(10) NOT NULL COMMENT 'Unix timestamp of creation',
    timemodified BIGINT(10) NOT NULL COMMENT 'Unix timestamp of last modification',

    PRIMARY KEY (id),

    -- Indexes for common queries
    KEY idx_student_session (studentid, sessionid) COMMENT 'Fast lookup by student and session',
    KEY idx_agent_rule (agentid, ruleid) COMMENT 'Fast lookup by agent and rule',
    KEY idx_session_time (sessionid, timecreated) COMMENT 'Time-series by session',
    KEY idx_intervention (intervention_type, intervention_executed) COMMENT 'Filter by intervention status',
    KEY idx_layer4_final (layer4_final) COMMENT 'Sort by final probability',
    KEY idx_timecreated (timecreated) COMMENT 'Time-based queries'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores 4-layer quantum probability calculations for Rule-Quantum Bridge';

-- =========================================================
-- Verification Queries
-- =========================================================
-- SHOW TABLES LIKE 'mdl_at_rule_quantum%';
-- DESCRIBE mdl_at_rule_quantum_state;
-- SELECT COUNT(*) FROM mdl_at_rule_quantum_state;

-- =========================================================
-- 4-Layer Probability Reference
-- =========================================================
-- Layer 1: P_rule = confidence × (priority/100) × condition_match
-- Layer 2: P_wave = |⟨ψ_agent|ψ_target⟩|²
-- Layer 3: P_corr = Σ(C_ij × P_j) / 21
-- Layer 4: P_final = sigmoid(0.25×P_rule + 0.35×P_wave + 0.25×P_corr + bias)
--
-- Decision Thresholds:
--   P ≥ 0.9  → IMMEDIATE_INTERVENTION (100% execution)
--   0.7 ≤ P → PROBABILISTIC_GATING (P probability execution)
--   0.5 ≤ P → WEIGHT_ADJUSTMENT (adjust and wait)
--   P < 0.5 → OBSERVE_ONLY
-- =========================================================

-- =========================================================
-- 13 Wave Functions Reference (stored in wave_params JSON)
-- =========================================================
-- ψ_core      - Core learning state (amplitude)
-- ψ_align     - Alignment with goals (phase, coherence)
-- ψ_fluct     - Quantum fluctuations (volatility, randomness)
-- ψ_tunnel    - Tunneling through barriers (barrier_height, tunnel_prob)
-- ψ_WM        - Working memory (capacity, decay_rate)
-- ψ_affect    - Affective/emotional state (valence, arousal, dominance)
-- ψ_routine   - Routine patterns (strength, flexibility)
-- ψ_engage    - Engagement level (depth, persistence)
-- ψ_concept   - Concept understanding (clarity, connections)
-- ψ_cascade   - Cascade effects (amplitude, reach)
-- ψ_meta      - Metacognition (awareness, control)
-- ψ_context   - Context sensitivity (weights per dimension)
-- ψ_predict   - Prediction/anticipation (horizon, confidence)
-- =========================================================

-- =========================================================
-- 8D StateVector Reference (stored in state_vector JSON)
-- =========================================================
-- cognitive_clarity     - 인지적 명확성 (0-1)
-- emotional_stability   - 정서적 안정성 (0-1)
-- attention_level       - 주의력 수준 (0-1)
-- motivation_strength   - 동기 강도 (0-1)
-- energy_level          - 에너지 수준 (0-1)
-- social_connection     - 사회적 연결성 (0-1)
-- creative_flow         - 창의적 흐름 (0-1)
-- learning_momentum     - 학습 모멘텀 (0-1)
-- =========================================================
