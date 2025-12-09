-- Shining Stars 데이터베이스 스키마 v2
-- MySQL 5.2.1 호환 버전

-- 기존 테이블 삭제 (개발 환경에서만)
-- DROP TABLE IF EXISTS ss_achievements;
-- DROP TABLE IF EXISTS ss_system_logs;
-- DROP TABLE IF EXISTS ss_teacher_insights;
-- DROP TABLE IF EXISTS ss_learning_sessions;
-- DROP TABLE IF EXISTS ss_dopamine_events;
-- DROP TABLE IF EXISTS ss_ai_feedback;
-- DROP TABLE IF EXISTS ss_reflections;
-- DROP TABLE IF EXISTS ss_journey_progress;
-- DROP TABLE IF EXISTS ss_student_profiles;
-- DROP TABLE IF EXISTS ss_prompt_templates;

-- 학생 프로필 테이블
CREATE TABLE IF NOT EXISTS ss_student_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    learning_style VARCHAR(50),
    math_confidence_level INT DEFAULT 50,
    dopamine_baseline INT DEFAULT 50,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 여정 진행 상태 테이블
CREATE TABLE IF NOT EXISTS ss_journey_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    node_id INT NOT NULL,
    status ENUM('locked', 'unlocked', 'completed') DEFAULT 'locked',
    unlocked_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
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
    question_text TEXT NOT NULL,
    answer_text TEXT NOT NULL,
    emotion_detected VARCHAR(50),
    confidence_score DECIMAL(3,2),
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
    ai_model VARCHAR(50),
    tokens_used INT,
    response_time DECIMAL(5,3),
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
    session_end TIMESTAMP NULL DEFAULT NULL,
    nodes_visited INT DEFAULT 0,
    reflections_submitted INT DEFAULT 0,
    mood_start VARCHAR(50),
    mood_end VARCHAR(50),
    INDEX idx_user_session (user_id, session_start),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI 프롬프트 템플릿 테이블 (JSON을 TEXT로 저장)
CREATE TABLE IF NOT EXISTS ss_prompt_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_type ENUM('system', 'user', 'assistant') NOT NULL,
    template_text TEXT NOT NULL,
    variables TEXT,  -- JSON 문자열로 저장
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (template_name),
    INDEX idx_type_active (template_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 교사 인사이트 테이블
CREATE TABLE IF NOT EXISTS ss_teacher_insights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    insight_type ENUM('progress', 'emotion', 'engagement', 'intervention') NOT NULL,
    insight_data TEXT,  -- JSON 문자열로 저장
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher_priority (teacher_id, priority, is_read),
    INDEX idx_student (student_id),
    FOREIGN KEY (student_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 시스템 로그 테이블
CREATE TABLE IF NOT EXISTS ss_system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_level ENUM('debug', 'info', 'warning', 'error') NOT NULL,
    log_category VARCHAR(50),
    message TEXT NOT NULL,
    context TEXT,  -- JSON 문자열로 저장
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
    achievement_type VARCHAR(50) NOT NULL,
    achievement_data TEXT,  -- JSON 문자열로 저장
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_id, achievement_type),
    FOREIGN KEY (user_id) REFERENCES ss_student_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 노드 질문 데이터
CREATE TABLE IF NOT EXISTS ss_node_questions (
    node_id INT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL,
    follow_up_prompts TEXT  -- JSON 문자열로 저장
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 사용자 역할 테이블 (Moodle과 별도로 관리하는 경우)
CREATE TABLE IF NOT EXISTS ss_user_roles (
    user_id INT PRIMARY KEY,
    role VARCHAR(20) NOT NULL,
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 세션 관리 테이블 (필요시)
CREATE TABLE IF NOT EXISTS ss_user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_key VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_key (session_key),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;