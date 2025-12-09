-- 파일: mvp_system/database/migrations/001_create_tables.sql
-- Mathking Agentic MVP System - Database Schema
-- MySQL 5.7 compatible
-- Created: 2025-11-02

-- ============================================================
-- 1. Snapshot Metrics 테이블
-- 학생의 실시간 학습 상태 메트릭 저장
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_snapshot_metrics (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user 참조)',
    calm_score DECIMAL(5,2) NOT NULL COMMENT '침착도 지표 (0-100)',
    focus_score DECIMAL(5,2) DEFAULT NULL COMMENT '집중도 지표 (0-100)',
    flow_score DECIMAL(5,2) DEFAULT NULL COMMENT '몰입도 지표 (0-100)',
    goal_alignment DECIMAL(3,2) DEFAULT NULL COMMENT '목표 정렬도 (0-1)',
    raw_data TEXT DEFAULT NULL COMMENT '원시 로그 데이터 (JSON)',
    recommendation VARCHAR(500) DEFAULT NULL COMMENT 'agent08 기반 추천',
    timestamp DATETIME NOT NULL COMMENT '측정 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_student_time (student_id, timestamp),
    INDEX idx_calm_score (calm_score),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 상태 스냅샷';

-- ============================================================
-- 2. Decision Log 테이블
-- 룰 엔진의 의사결정 결과 저장
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_decision_log (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '대상 학생 ID',
    action VARCHAR(50) NOT NULL COMMENT '결정된 행동 (micro_break, ask_teacher, none)',
    params TEXT DEFAULT NULL COMMENT '행동 파라미터 (JSON)',
    confidence DECIMAL(3,2) NOT NULL COMMENT '신뢰도 (0-1)',
    rationale TEXT NOT NULL COMMENT '결정 근거 (Explainability)',
    rule_id VARCHAR(100) DEFAULT NULL COMMENT '매칭된 룰 ID',
    trace_data TEXT DEFAULT NULL COMMENT '추적 정보 (JSON)',
    timestamp DATETIME NOT NULL COMMENT '결정 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_student (student_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp),
    INDEX idx_rule_id (rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의사결정 로그';

-- ============================================================
-- 3. Teacher Feedback 테이블
-- 교사의 개입 승인/거부 피드백 저장
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_teacher_feedback (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT(10) UNSIGNED NOT NULL COMMENT '교사 ID (mdl_user 참조)',
    decision_id BIGINT(10) UNSIGNED NOT NULL COMMENT '대상 결정 ID',
    response ENUM('approve', 'reject', 'defer') NOT NULL COMMENT '교사 응답',
    comment TEXT DEFAULT NULL COMMENT '교사 의견',
    structured_feedback TEXT DEFAULT NULL COMMENT '구조화된 피드백 (JSON)',
    timestamp DATETIME NOT NULL COMMENT '피드백 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_decision (decision_id),
    INDEX idx_response (response),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (decision_id) REFERENCES mdl_mvp_decision_log(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='교사 피드백';

-- ============================================================
-- 4. Intervention Execution 테이블
-- 개입 실행 결과 및 LMS 응답 저장
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_intervention_execution (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    intervention_id VARCHAR(100) NOT NULL COMMENT '개입 고유 ID',
    decision_id BIGINT(10) UNSIGNED NOT NULL COMMENT '원본 결정 ID',
    type VARCHAR(50) NOT NULL COMMENT '개입 유형 (micro_break, notification)',
    target_student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '대상 학생 ID',
    message TEXT NOT NULL COMMENT '전달 메시지',
    scheduled_at DATETIME DEFAULT NULL COMMENT '예정 시각',
    executed_at DATETIME DEFAULT NULL COMMENT '실제 실행 시각',
    status ENUM('pending', 'sent', 'delivered', 'completed', 'failed') NOT NULL DEFAULT 'pending' COMMENT '실행 상태',
    lms_response TEXT DEFAULT NULL COMMENT 'LMS 응답 (JSON)',
    metadata TEXT DEFAULT NULL COMMENT '메타데이터 (JSON)',
    retry_count INT(3) DEFAULT 0 COMMENT '재시도 횟수',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_intervention_id (intervention_id),
    INDEX idx_decision (decision_id),
    INDEX idx_student (target_student_id),
    INDEX idx_status (status),
    INDEX idx_executed_at (executed_at),
    FOREIGN KEY (decision_id) REFERENCES mdl_mvp_decision_log(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개입 실행 로그';

-- ============================================================
-- 5. System Metrics 테이블 (SLA 모니터링용)
-- 시스템 성능 및 SLA 추적
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_mvp_system_metrics (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    metric_name VARCHAR(100) NOT NULL COMMENT '메트릭 이름',
    metric_value DECIMAL(10,2) NOT NULL COMMENT '메트릭 값',
    unit VARCHAR(20) DEFAULT NULL COMMENT '단위 (ms, percent, count)',
    context TEXT DEFAULT NULL COMMENT '컨텍스트 정보 (JSON)',
    timestamp DATETIME NOT NULL COMMENT '측정 시각',

    PRIMARY KEY (id),
    INDEX idx_metric_name (metric_name),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 성능 메트릭';

-- ============================================================
-- Sample Data Seeds (Optional - for testing)
-- ============================================================

-- Test student metrics
INSERT INTO mdl_mvp_snapshot_metrics (student_id, calm_score, focus_score, timestamp, recommendation) VALUES
(123, 88.5, 92.0, '2025-11-02 10:30:00', '안정, 가벼운 복습 권장'),
(123, 70.0, 65.0, '2025-11-02 11:00:00', '낮음, 3~5분 휴식 후 재시작');

-- Test decision log
INSERT INTO mdl_mvp_decision_log (student_id, action, confidence, rationale, rule_id, timestamp) VALUES
(123, 'micro_break', 0.90, 'Rule calm_break_rule matched: calm=70 < threshold 75', 'calm_break_rule', '2025-11-02 11:00:05');

-- ============================================================
-- Verification Queries
-- ============================================================

-- Check tables created
-- SELECT table_name FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_name LIKE 'mdl_mvp_%';

-- Check row counts
-- SELECT 'snapshot_metrics' as table_name, COUNT(*) as row_count FROM mdl_mvp_snapshot_metrics
-- UNION ALL
-- SELECT 'decision_log', COUNT(*) FROM mdl_mvp_decision_log
-- UNION ALL
-- SELECT 'teacher_feedback', COUNT(*) FROM mdl_mvp_teacher_feedback
-- UNION ALL
-- SELECT 'intervention_execution', COUNT(*) FROM mdl_mvp_intervention_execution
-- UNION ALL
-- SELECT 'system_metrics', COUNT(*) FROM mdl_mvp_system_metrics;
