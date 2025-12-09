-- 사용자의 최근 방문 강좌를 저장하는 테이블
CREATE TABLE IF NOT EXISTS mdl_user_recent_courses (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    course_name VARCHAR(255) NOT NULL, -- 강좌명 (예: 초등수학 4-1)
    course_type VARCHAR(50) NOT NULL, -- 강좌 유형 (elementary, middle, high)
    visit_count INT DEFAULT 1, -- 방문 횟수
    last_visited BIGINT(10) NOT NULL, -- 마지막 방문 시간
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY userid_idx (userid),
    KEY last_visited_idx (last_visited),
    UNIQUE KEY unique_user_course (userid, course_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;