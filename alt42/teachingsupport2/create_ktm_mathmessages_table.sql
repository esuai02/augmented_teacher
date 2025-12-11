-- 하이튜터링 전용 메시지 테이블 생성
-- 기존 mdl_ktm_teaching_interactions, mdl_ktm_teaching_events와 연동

CREATE TABLE IF NOT EXISTS mdl_ktm_mathmessages (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT(10) NOT NULL COMMENT '선생님 ID (user 테이블 참조)',
    student_id BIGINT(10) NOT NULL COMMENT '학생 ID (user 테이블 참조)',
    interaction_id BIGINT(10) DEFAULT NULL COMMENT 'ktm_teaching_interactions 테이블 참조',
    subject VARCHAR(255) NOT NULL DEFAULT '하이튜터링 문제 해설' COMMENT '메시지 제목',
    message_content LONGTEXT NOT NULL COMMENT '메시지 내용',
    solution_text LONGTEXT DEFAULT NULL COMMENT '풀이 내용',
    audio_url VARCHAR(500) DEFAULT NULL COMMENT '음성 파일 URL',
    explanation_url VARCHAR(500) DEFAULT NULL COMMENT '상세 설명 페이지 URL',
    is_read TINYINT(1) DEFAULT 0 COMMENT '읽음 여부 (0:안읽음, 1:읽음)',
    timecreated BIGINT(10) NOT NULL COMMENT '생성 시간 (timestamp)',
    timeread BIGINT(10) DEFAULT NULL COMMENT '읽은 시간 (timestamp)',
    PRIMARY KEY (id),
    INDEX idx_student_id (student_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_interaction_id (interaction_id),
    INDEX idx_timecreated (timecreated),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='하이튜터링 메시지 테이블';

-- 기존 테이블들이 없는 경우를 대비한 생성 구문 (참고용)
/*
-- ktm_teaching_interactions 테이블 (이미 존재한다고 가정)
CREATE TABLE IF NOT EXISTS mdl_ktm_teaching_interactions (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    teacherid BIGINT(10) DEFAULT NULL,
    problem_type VARCHAR(50) DEFAULT NULL,
    problem_content TEXT DEFAULT NULL,
    solution_content TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_teacherid (teacherid),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='하이튜터링 상호작용 테이블';

-- ktm_teaching_events 테이블 (이미 존재한다고 가정)
CREATE TABLE IF NOT EXISTS mdl_ktm_teaching_events (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    interactionid BIGINT(10) DEFAULT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_description VARCHAR(255) DEFAULT NULL,
    metadata TEXT DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_interactionid (interactionid),
    INDEX idx_event_type (event_type),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='하이튜터링 이벤트 로그 테이블';
*/

-- 테이블 생성 확인 쿼리
SELECT 
    TABLE_NAME,
    TABLE_COMMENT,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('mdl_ktm_mathmessages', 'mdl_ktm_teaching_interactions', 'mdl_ktm_teaching_events')
ORDER BY TABLE_NAME;