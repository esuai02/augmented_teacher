-- Shining Stars 데이터베이스 스키마
-- 수학 학습 AI 에이전트 시스템

-- 학생 프로필 테이블
CREATE TABLE IF NOT EXISTS ss_student_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,                    -- Moodle user ID
    learning_style VARCHAR(50),              -- 학습 스타일 (visual, auditory, kinesthetic)
    math_confidence_level INT DEFAULT 50,    -- 수학 자신감 수준 (0-100)
    dopamine_baseline INT DEFAULT 50,        -- 기본 도파민 수준 추정치
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 여정 진행 상태 테이블
CREATE TABLE IF NOT EXISTS ss_journey_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    node_id INT NOT NULL,                    -- 여정 맵의 노드 ID
    status ENUM('locked', 'unlocked', 'completed') DEFAULT 'locked',
    unlocked_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_node (user_id, node_id),
    INDEX idx_user_status (user_id, status),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 성찰 기록 테이블
CREATE TABLE IF NOT EXISTS ss_reflections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    node_id INT NOT NULL,
    question_text TEXT NOT NULL,             -- 질문 내용
    answer_text TEXT NOT NULL,               -- 학생의 답변
    emotion_detected VARCHAR(50),            -- 감지된 감정 (joy, anxiety, frustration, etc.)
    confidence_score DECIMAL(3,2),           -- AI가 분석한 자신감 점수 (0.00-1.00)
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_node (user_id, node_id),
    INDEX idx_submitted (submitted_at),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI 피드백 테이블
CREATE TABLE IF NOT EXISTS ss_ai_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reflection_id INT NOT NULL,
    feedback_type ENUM('encouragement', 'insight', 'guidance', 'celebration') NOT NULL,
    feedback_text TEXT NOT NULL,
    ai_model VARCHAR(50),                    -- 사용된 AI 모델
    tokens_used INT,                         -- 토큰 사용량
    response_time DECIMAL(5,3),              -- 응답 시간 (초)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reflection (reflection_id),
    INDEX idx_type (feedback_type),
    FOREIGN KEY (reflection_id) REFERENCES ss_reflections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 도파민 이벤트 추적 테이블
CREATE TABLE IF NOT EXISTS ss_dopamine_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_type ENUM('achievement', 'progress', 'insight', 'streak', 'challenge_complete') NOT NULL,
    intensity ENUM('low', 'medium', 'high') NOT NULL,
    trigger_description TEXT,
    node_id INT,
    occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_event (user_id, event_type),
    INDEX idx_occurred (occurred_at),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 학습 세션 테이블
CREATE TABLE IF NOT EXISTS ss_learning_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_end TIMESTAMP NULL,
    nodes_visited INT DEFAULT 0,
    reflections_submitted INT DEFAULT 0,
    mood_start VARCHAR(50),                  -- 시작 시 기분
    mood_end VARCHAR(50),                    -- 종료 시 기분
    INDEX idx_user_session (user_id, session_start),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI 프롬프트 템플릿 테이블
CREATE TABLE IF NOT EXISTS ss_prompt_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_type ENUM('system', 'user', 'assistant') NOT NULL,
    template_text TEXT NOT NULL,
    variables JSON,                          -- 사용 가능한 변수 목록
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (template_name),
    INDEX idx_type_active (template_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 교사 인사이트 테이블
CREATE TABLE IF NOT EXISTS ss_teacher_insights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,                 -- Moodle teacher ID
    student_id INT NOT NULL,
    insight_type ENUM('progress', 'emotion', 'engagement', 'intervention') NOT NULL,
    insight_data JSON,                       -- 구조화된 인사이트 데이터
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher_priority (teacher_id, priority, is_read),
    INDEX idx_student (student_id),
    FOREIGN KEY (student_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 시스템 로그 테이블
CREATE TABLE IF NOT EXISTS ss_system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_level ENUM('debug', 'info', 'warning', 'error') NOT NULL,
    log_category VARCHAR(50),                -- api, ai, auth, etc.
    message TEXT NOT NULL,
    context JSON,                            -- 추가 컨텍스트 정보
    user_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level_created (log_level, created_at),
    INDEX idx_category (log_category),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 성과 배지 테이블
CREATE TABLE IF NOT EXISTS ss_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,   -- first_reflection, week_streak, all_nodes, etc.
    achievement_data JSON,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_id, achievement_type),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 데이터베이스 관련 정보
-- 테이블 접두사: ss_ (Shining Stars)
-- 문자셋: utf8mb4 (이모지 지원)
-- 엔진: InnoDB (트랜잭션 지원)
-- 
-- 주요 인덱스:
-- - user_id: 사용자별 빠른 조회
-- - timestamp 필드: 시간 기반 정렬 및 필터링
-- - status 필드: 상태별 필터링
--
-- 외래 키:
-- - 모든 user_id는 ss_student_profiles 참조
-- - CASCADE DELETE로 사용자 삭제 시 관련 데이터 자동 삭제