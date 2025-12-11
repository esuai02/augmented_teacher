-- 문제 해설 상호작용 기록 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_teaching_interactions (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    teacherid BIGINT(10) DEFAULT NULL,
    problem_type VARCHAR(50) DEFAULT NULL,
    problem_image TEXT DEFAULT NULL,
    problem_text LONGTEXT DEFAULT NULL,
    solution_text LONGTEXT DEFAULT NULL,
    narration_text LONGTEXT DEFAULT NULL,
    audio_url TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    score INT(10) DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY mdl_ktm_teach_userid_idx (userid),
    KEY mdl_ktm_teach_teacherid_idx (teacherid),
    KEY mdl_ktm_teach_status_idx (status),
    KEY mdl_ktm_teach_timecreated_idx (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Teaching agent interaction history';

-- 학습 이벤트 로그 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_teaching_events (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    interactionid BIGINT(10) DEFAULT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_description TEXT DEFAULT NULL,
    metadata LONGTEXT DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY mdl_ktm_events_userid_idx (userid),
    KEY mdl_ktm_events_interaction_idx (interactionid),
    KEY mdl_ktm_events_type_idx (event_type),
    KEY mdl_ktm_events_time_idx (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Teaching interaction events log';