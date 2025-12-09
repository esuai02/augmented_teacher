-- 하이튜터링 전용 메시지 시스템 테이블

-- 1. 메시지 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_messages (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT(10) NOT NULL,
    student_id BIGINT(10) NOT NULL,
    interaction_id BIGINT(10) DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    message_content TEXT NOT NULL,
    solution_text TEXT DEFAULT NULL,
    audio_url VARCHAR(500) DEFAULT NULL,
    explanation_url VARCHAR(500) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    timecreated BIGINT(10) NOT NULL,
    timeread BIGINT(10) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_student_id (student_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_interaction_id (interaction_id),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='하이튜터링 메시지 테이블';

-- 2. 메시지 이벤트 로그 (기존 ktm_teaching_events에 통합 가능)
-- 별도 테이블이 필요하면 생성
CREATE TABLE IF NOT EXISTS mdl_ktm_message_events (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    message_id BIGINT(10) NOT NULL,
    student_id BIGINT(10) NOT NULL,
    teacher_id BIGINT(10) NOT NULL,
    event_type VARCHAR(50) NOT NULL, -- 'sent', 'read', 'resent'
    event_description VARCHAR(255) DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_message_id (message_id),
    INDEX idx_student_id (student_id),
    INDEX idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='메시지 이벤트 로그';