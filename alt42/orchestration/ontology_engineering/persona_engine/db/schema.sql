-- ==============================================
-- Persona Engine Database Schema
-- Version: 1.0
-- MySQL 5.7 Compatible
-- ==============================================

-- 에이전트 간 메시지 테이블
CREATE TABLE IF NOT EXISTS mdl_at_agent_messages (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    source_agent VARCHAR(20) NOT NULL COMMENT '발신 에이전트 ID',
    target_agent VARCHAR(20) NOT NULL COMMENT '수신 에이전트 ID',
    message_type VARCHAR(50) NOT NULL COMMENT '메시지 타입',
    payload LONGTEXT COMMENT '메시지 페이로드 (JSON)',
    priority TINYINT(3) DEFAULT 5 COMMENT '우선순위 (0-10)',
    status VARCHAR(20) DEFAULT 'pending' COMMENT '상태: pending, processed, failed, skipped',
    response LONGTEXT COMMENT '응답 데이터 (JSON)',
    created_at BIGINT(10) NOT NULL COMMENT '생성 시간',
    processed_at BIGINT(10) DEFAULT NULL COMMENT '처리 시간',
    expires_at BIGINT(10) DEFAULT NULL COMMENT '만료 시간',
    PRIMARY KEY (id),
    INDEX idx_target_status (target_agent, status),
    INDEX idx_source_agent (source_agent),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 페르소나 이벤트 테이블
CREATE TABLE IF NOT EXISTS mdl_at_persona_events (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(20) NOT NULL COMMENT '에이전트 ID',
    event_type VARCHAR(50) NOT NULL COMMENT '이벤트 타입',
    event_data LONGTEXT COMMENT '이벤트 데이터 (JSON)',
    user_id BIGINT(10) DEFAULT 0 COMMENT '관련 사용자 ID',
    persona_id VARCHAR(50) DEFAULT NULL COMMENT '관련 페르소나 ID',
    created_at BIGINT(10) NOT NULL COMMENT '생성 시간',
    expires_at BIGINT(10) DEFAULT NULL COMMENT '만료 시간',
    PRIMARY KEY (id),
    INDEX idx_user_event (user_id, event_type),
    INDEX idx_agent_id (agent_id),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 페르소나 세션 테이블
CREATE TABLE IF NOT EXISTS mdl_at_persona_session (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
    agent_id VARCHAR(20) DEFAULT NULL COMMENT '에이전트 ID',
    current_persona VARCHAR(50) DEFAULT NULL COMMENT '현재 페르소나 ID',
    previous_persona VARCHAR(50) DEFAULT NULL COMMENT '이전 페르소나 ID',
    current_situation VARCHAR(10) DEFAULT 'S1' COMMENT '현재 상황 코드',
    interaction_count INT(10) DEFAULT 0 COMMENT '상호작용 횟수',
    session_data LONGTEXT COMMENT '세션 데이터 (JSON)',
    created_at BIGINT(10) NOT NULL COMMENT '생성 시간',
    updated_at BIGINT(10) DEFAULT NULL COMMENT '수정 시간',
    PRIMARY KEY (id),
    UNIQUE INDEX idx_user_agent (user_id, agent_id),
    INDEX idx_user_id (user_id),
    INDEX idx_current_persona (current_persona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 페르소나 액션 로그 테이블
CREATE TABLE IF NOT EXISTS mdl_at_persona_action_log (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(20) NOT NULL COMMENT '에이전트 ID',
    action_type VARCHAR(50) NOT NULL COMMENT '액션 타입',
    action_data LONGTEXT COMMENT '액션 데이터 (JSON)',
    result LONGTEXT COMMENT '실행 결과 (JSON)',
    user_id BIGINT(10) DEFAULT 0 COMMENT '관련 사용자 ID',
    persona_id VARCHAR(50) DEFAULT NULL COMMENT '실행 페르소나 ID',
    success TINYINT(1) DEFAULT 1 COMMENT '성공 여부',
    created_at BIGINT(10) NOT NULL COMMENT '실행 시간',
    PRIMARY KEY (id),
    INDEX idx_user_action (user_id, action_type),
    INDEX idx_agent_id (agent_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 응답 템플릿 테이블
CREATE TABLE IF NOT EXISTS mdl_at_response_templates (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    template_id VARCHAR(50) NOT NULL COMMENT '템플릿 ID',
    agent_id VARCHAR(20) DEFAULT NULL COMMENT '에이전트 ID (NULL이면 공통)',
    persona_id VARCHAR(50) DEFAULT NULL COMMENT '페르소나 ID (NULL이면 공통)',
    template_text LONGTEXT NOT NULL COMMENT '템플릿 텍스트',
    tone VARCHAR(30) DEFAULT 'Professional' COMMENT '톤',
    intervention_type VARCHAR(50) DEFAULT NULL COMMENT '개입 유형',
    language VARCHAR(10) DEFAULT 'ko' COMMENT '언어 코드',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성 여부',
    created_at BIGINT(10) NOT NULL,
    updated_at BIGINT(10) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_template_persona (template_id, agent_id, persona_id),
    INDEX idx_agent_persona (agent_id, persona_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 에이전트 설정 테이블
CREATE TABLE IF NOT EXISTS mdl_at_agent_config (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(20) NOT NULL COMMENT '에이전트 ID',
    config_key VARCHAR(100) NOT NULL COMMENT '설정 키',
    config_value LONGTEXT COMMENT '설정 값',
    config_type VARCHAR(20) DEFAULT 'string' COMMENT '값 타입: string, int, bool, json',
    description VARCHAR(255) DEFAULT NULL COMMENT '설정 설명',
    created_at BIGINT(10) NOT NULL,
    updated_at BIGINT(10) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_agent_key (agent_id, config_key),
    INDEX idx_agent_id (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
