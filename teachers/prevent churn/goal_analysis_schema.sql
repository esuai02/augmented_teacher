-- 목표설정 구체성 분석 결과 저장 테이블
-- 파일: teachers/prevent churn/goal_analysis_schema.sql

CREATE TABLE IF NOT EXISTS mdl_abessi_goal_analysis (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT '학생 ID',
    week_start_date DATE NOT NULL COMMENT '주 시작일 (일요일)',
    week_end_date DATE NOT NULL COMMENT '주 종료일 (토요일)',
    
    -- 분기목표 분석
    term_goal_text TEXT COMMENT '분기목표 내용',
    term_goal_score TINYINT(1) DEFAULT NULL COMMENT '분기목표 구체성 점수 (0-5)',
    term_goal_deadline BIGINT(20) DEFAULT NULL COMMENT '분기목표 마감일',
    
    -- 주간목표 분석
    weekly_goal_text TEXT COMMENT '주간목표 내용',
    weekly_goal_score TINYINT(1) DEFAULT NULL COMMENT '주간목표 구체성 점수 (0-5)',
    weekly_goal_created BIGINT(20) DEFAULT NULL COMMENT '주간목표 생성일',
    
    -- 오늘목표 분석
    today_goal_text TEXT COMMENT '오늘목표 내용',
    today_goal_score TINYINT(1) DEFAULT NULL COMMENT '오늘목표 구체성 점수 (0-5)',
    today_goal_created BIGINT(20) DEFAULT NULL COMMENT '오늘목표 생성일',
    
    -- 상관관계 분석
    correlation_score TINYINT(1) DEFAULT NULL COMMENT '분기->주간->오늘 상관관계 점수 (0-5)',
    correlation_analysis TEXT COMMENT '상관관계 분석 설명',
    
    -- 메타데이터
    analysis_status VARCHAR(20) DEFAULT 'completed' COMMENT '분석 상태 (completed, pending, error)',
    error_message TEXT COMMENT '에러 메시지',
    timecreated BIGINT(20) NOT NULL COMMENT '생성 시간',
    timemodified BIGINT(20) NOT NULL COMMENT '수정 시간',
    
    PRIMARY KEY (id),
    INDEX idx_userid_week (userid, week_start_date),
    INDEX idx_week_start (week_start_date),
    INDEX idx_week_end (week_end_date),
    INDEX idx_timecreated (timecreated),
    UNIQUE KEY unique_user_week (userid, week_start_date, week_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='목표설정 구체성 분석 결과 (주 단위)';

-- 학생별 분석 요약 테이블 (선택사항 - 빠른 조회용)
CREATE TABLE IF NOT EXISTS mdl_abessi_goal_analysis_summary (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL COMMENT '학생 ID',
    week_start_date DATE NOT NULL COMMENT '주 시작일',
    
    -- 평균 점수
    avg_term_score DECIMAL(3,2) DEFAULT NULL COMMENT '평균 분기목표 점수',
    avg_weekly_score DECIMAL(3,2) DEFAULT NULL COMMENT '평균 주간목표 점수',
    avg_today_score DECIMAL(3,2) DEFAULT NULL COMMENT '평균 오늘목표 점수',
    avg_correlation_score DECIMAL(3,2) DEFAULT NULL COMMENT '평균 상관관계 점수',
    
    -- 최근 분석 결과 ID (참조용)
    latest_analysis_id BIGINT(10) DEFAULT NULL COMMENT '최근 분석 결과 ID',
    
    timecreated BIGINT(20) NOT NULL,
    timemodified BIGINT(20) NOT NULL,
    
    PRIMARY KEY (id),
    INDEX idx_userid_week (userid, week_start_date),
    UNIQUE KEY unique_user_week (userid, week_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생별 목표설정 분석 요약';

