-- AI 페르소나 매칭 시스템을 위한 데이터베이스 테이블 생성

-- 페르소나 모드 저장 테이블
CREATE TABLE IF NOT EXISTS mdl_persona_modes (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT(10) NOT NULL COMMENT '선생님 사용자 ID',
    student_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID', 
    teacher_mode VARCHAR(50) NOT NULL COMMENT '선생님 교육 모드 (curriculum, exam, custom, mission, reflection, selfled)',
    student_mode VARCHAR(50) NOT NULL COMMENT '학생 학습 모드 (curriculum, exam, custom, mission, reflection, selfled)',
    created_at BIGINT(10) NOT NULL COMMENT '생성 시간 (timestamp)',
    updated_at BIGINT(10) NOT NULL COMMENT '수정 시간 (timestamp)',
    UNIQUE KEY unique_teacher_student (teacher_id, student_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 페르소나 매칭 모드 저장';

-- 메시지 변환 이력 저장 테이블 (선택적)
CREATE TABLE IF NOT EXISTS mdl_message_transformations (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT(10) NOT NULL COMMENT '선생님 사용자 ID',
    student_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
    original_message TEXT NOT NULL COMMENT '원본 메시지',
    transformed_message TEXT NOT NULL COMMENT '변환된 메시지',
    teacher_mode VARCHAR(50) NOT NULL COMMENT '선생님 모드',
    student_mode VARCHAR(50) NOT NULL COMMENT '학생 모드',
    transformation_time BIGINT(10) NOT NULL COMMENT '변환 시간 (timestamp)',
    INDEX idx_teacher_student (teacher_id, student_id),
    INDEX idx_transformation_time (transformation_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='메시지 변환 이력'; 

-- 실시간 채팅 메시지 저장 테이블 (미래 확장용)
CREATE TABLE IF NOT EXISTS mdl_alt42_chat_messages (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(100) NOT NULL COMMENT '채팅방 ID (teacher_id_student_id)',
    sender_id BIGINT(10) NOT NULL COMMENT '발신자 ID',
    receiver_id BIGINT(10) NOT NULL COMMENT '수신자 ID',
    message_type ENUM('original', 'transformed') DEFAULT 'original' COMMENT '메시지 타입',
    message_content TEXT NOT NULL COMMENT '메시지 내용',
    sent_at BIGINT(10) NOT NULL COMMENT '전송 시간 (timestamp)',
    read_at BIGINT(10) DEFAULT NULL COMMENT '읽은 시간 (timestamp)',
    INDEX idx_room_id (room_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_sender_receiver (sender_id, receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실시간 채팅 메시지';