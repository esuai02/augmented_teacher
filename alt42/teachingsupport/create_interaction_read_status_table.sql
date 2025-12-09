-- ktm_interaction_read_status 테이블 생성
CREATE TABLE IF NOT EXISTS mdl_ktm_interaction_read_status (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    interaction_id BIGINT(10) NOT NULL,
    student_id BIGINT(10) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    timeread BIGINT(10) DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY interaction_student_unique (interaction_id, student_id),
    KEY interaction_idx (interaction_id),
    KEY student_idx (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;