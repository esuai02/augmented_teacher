-- 상호작용 읽음 상태 추적 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_interaction_read_status (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    interaction_id BIGINT(10) NOT NULL COMMENT 'ktm_teaching_interactions 테이블 참조',
    student_id BIGINT(10) NOT NULL COMMENT '학생 ID',
    is_read TINYINT(1) DEFAULT 0 COMMENT '읽음 여부 (0:안읽음, 1:읽음)',
    timeread BIGINT(10) DEFAULT NULL COMMENT '읽은 시간',
    PRIMARY KEY (id),
    UNIQUE KEY unique_interaction_student (interaction_id, student_id),
    INDEX idx_student_id (student_id),
    INDEX idx_interaction_id (interaction_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상호작용 읽음 상태 테이블';