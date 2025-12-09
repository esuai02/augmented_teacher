-- wxsperta AI 에이전트 시스템 테이블 생성 스크립트

-- 1. AI 에이전트 정의 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_agents (
    id INT(10) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(100) NOT NULL,
    color VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    layer_id VARCHAR(50) NOT NULL,
    description TEXT,
    short_desc VARCHAR(255),
    world_view TEXT,
    context TEXT,
    structure TEXT,
    process TEXT,
    execution TEXT,
    reflection TEXT,
    transfer TEXT,
    abstraction TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_category (category),
    INDEX idx_layer (layer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 사용자별 에이전트 우선순위 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_agent_priorities (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    agent_id INT(10) NOT NULL,
    priority INT(3) NOT NULL DEFAULT 50,
    motivator VARCHAR(50) DEFAULT 'autonomy',
    last_interaction TIMESTAMP NULL,
    interaction_count INT(10) DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_agent (user_id, agent_id),
    INDEX idx_user_priority (user_id, priority DESC),
    FOREIGN KEY (agent_id) REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 대화 질문 템플릿 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_agent_questions (
    id INT(10) NOT NULL AUTO_INCREMENT,
    agent_id INT(10) NOT NULL,
    question TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL, -- 'ask', 'success_cue', 'fail_cue'
    language VARCHAR(10) DEFAULT 'ko',
    is_active TINYINT(1) DEFAULT 1,
    usage_count INT(10) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_agent_type (agent_id, question_type),
    FOREIGN KEY (agent_id) REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 사용자 상호작용 로그 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_interactions (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    agent_id INT(10) NOT NULL,
    interaction_type VARCHAR(50) NOT NULL, -- 'question', 'answer', 'event', 'feedback'
    question_id INT(10) NULL,
    user_input TEXT,
    agent_response TEXT,
    success_score DECIMAL(3,2) DEFAULT NULL, -- 0.00 ~ 1.00
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_user_agent_date (user_id, agent_id, created_at),
    FOREIGN KEY (agent_id) REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES mdl_wxsperta_agent_questions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 이벤트 트리거 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_events (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    event_type VARCHAR(50) NOT NULL, -- 'exam', 'project', 'motivation_low', 'achievement'
    event_data TEXT,
    triggered_agents TEXT, -- 영향받은 에이전트 ID 목록
    event_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_date (user_id, event_date),
    INDEX idx_type_date (event_type, event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 사용자 프로필 확장 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_user_profiles (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    learning_style VARCHAR(50),
    interests TEXT,
    goals TEXT,
    mbti_type VARCHAR(4),
    preferred_motivator VARCHAR(50) DEFAULT 'autonomy',
    daily_active_time VARCHAR(20), -- 예: '09:00-12:00'
    streak_days INT(10) DEFAULT 0,
    total_interactions INT(10) DEFAULT 0,
    last_active DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 에이전트 레이어 정의 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_layers (
    id INT(10) NOT NULL AUTO_INCREMENT,
    layer_key VARCHAR(50) NOT NULL,
    layer_name VARCHAR(255) NOT NULL,
    layer_order INT(2) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY unique_key (layer_key),
    INDEX idx_order (layer_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 일일 미션/챌린지 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_daily_missions (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    agent_id INT(10) NOT NULL,
    mission_date DATE NOT NULL,
    mission_text TEXT NOT NULL,
    mission_type VARCHAR(50) NOT NULL, -- 'daily', 'weekly', 'special'
    target_value INT(10),
    current_value INT(10) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'in_progress', 'completed', 'failed'
    reward_points INT(10) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_date_agent (user_id, mission_date, agent_id),
    INDEX idx_user_date_status (user_id, mission_date, status),
    FOREIGN KEY (agent_id) REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. 성과/보상 추적 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_achievements (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,
    achievement_name VARCHAR(255) NOT NULL,
    achievement_data TEXT,
    points_earned INT(10) DEFAULT 0,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_type (user_id, achievement_type),
    INDEX idx_user_date (user_id, unlocked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. 시스템 설정/메타데이터 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_settings (
    id INT(10) NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 레이어 데이터 삽입
INSERT INTO mdl_wxsperta_layers (layer_key, layer_name, layer_order, description) VALUES
('worldView', '세계관', 1, '미션의 기본 철학과 이상적 성과를 정의합니다.'),
('context', '문맥', 2, '미션이 운영되는 환경과 조건을 인식합니다.'),
('structure', '구조', 3, '미션 수행을 위한 구조적 설계를 담당합니다.'),
('process', '절차', 4, '미션 실행의 단계별 프로세스를 정의합니다.'),
('execution', '실행', 5, '미션 달성을 위한 구체적 실행 방식을 설계합니다.'),
('reflection', '성찰', 6, '미션 성과 평가와 개선 전략을 관리합니다.'),
('transfer', '전파', 7, '미션 수행의 경험과 학습을 전파합니다.'),
('abstraction', '추상화', 8, '미션의 핵심 목표와 가치를 추상화합니다.');

-- 기본 시스템 설정 삽입
INSERT INTO mdl_wxsperta_settings (setting_key, setting_value, setting_type, description) VALUES
('system_version', '1.0.0', 'string', '시스템 버전'),
('default_priority', '50', 'integer', '에이전트 기본 우선순위'),
('max_daily_missions', '3', 'integer', '일일 최대 미션 수'),
('streak_bonus_days', '7', 'integer', '연속 달성 보너스 기준일'),
('ai_temperature', '0.7', 'float', 'AI 응답 창의성 수준');