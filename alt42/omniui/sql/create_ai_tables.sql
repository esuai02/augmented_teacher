-- MathKing 자동개입 시스템 AI 분석 테이블
-- 실행 전 데이터베이스 백업을 권장합니다

USE mathking;

-- 1. AI 에이전트 분석 결과 테이블
CREATE TABLE IF NOT EXISTS mdl_abessi_ai_analysis (
    id bigint(10) NOT NULL AUTO_INCREMENT,
    userid bigint(10) NOT NULL COMMENT '학생 ID',
    agent_type varchar(50) NOT NULL COMMENT '에이전트 타입 (onboarding, exam_schedule, activity_adjustment 등)',
    agent_level int NOT NULL COMMENT '에이전트 레벨 (1-21)',
    analysis_data text COMMENT 'JSON 형식의 분석 데이터',
    confidence_score decimal(5,2) DEFAULT NULL COMMENT '신뢰도 점수 (0-100)',
    recommendations text COMMENT '추천사항 JSON',
    created_date date NOT NULL COMMENT '분석 날짜',
    timecreated bigint(10) NOT NULL,
    timemodified bigint(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_userid (userid),
    KEY idx_agent_type (agent_type),
    KEY idx_created_date (created_date),
    KEY idx_userid_date (userid, created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='21단계 AI 에이전트 분석 결과';

-- 2. 학습 루틴 추적 테이블
CREATE TABLE IF NOT EXISTS mdl_abessi_learning_routine (
    id bigint(10) NOT NULL AUTO_INCREMENT,
    userid bigint(10) NOT NULL COMMENT '학생 ID',
    routine_name varchar(100) NOT NULL COMMENT '루틴 이름 (예: 30초 사고 마스터)',
    routine_type varchar(50) NOT NULL COMMENT '루틴 타입 (thinking, problem_solving, review 등)',
    step_data text COMMENT 'JSON 형식의 단계별 데이터',
    completion_rate decimal(5,2) DEFAULT 0 COMMENT '완료율 (0-100)',
    effectiveness_score decimal(5,2) DEFAULT NULL COMMENT '효과성 점수 (0-100)',
    daily_goal int DEFAULT 5 COMMENT '일일 목표',
    daily_completed int DEFAULT 0 COMMENT '일일 완료',
    streak_days int DEFAULT 0 COMMENT '연속 실행 일수',
    total_practices int DEFAULT 0 COMMENT '총 연습 횟수',
    timecreated bigint(10) NOT NULL,
    timemodified bigint(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_userid (userid),
    KEY idx_routine_type (routine_type),
    KEY idx_userid_type (userid, routine_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='학습 루틴 추적 데이터';

-- 3. AI 개입 기록 테이블
CREATE TABLE IF NOT EXISTS mdl_abessi_intervention_log (
    id bigint(10) NOT NULL AUTO_INCREMENT,
    userid bigint(10) NOT NULL COMMENT '학생 ID',
    intervention_type varchar(50) NOT NULL COMMENT '개입 유형',
    trigger_condition text COMMENT '개입 트리거 조건',
    intervention_content text COMMENT '개입 내용',
    student_response varchar(50) DEFAULT NULL COMMENT '학생 반응 (accepted, rejected, ignored)',
    effectiveness_score decimal(5,2) DEFAULT NULL COMMENT '개입 효과성',
    ai_model_used varchar(50) DEFAULT NULL COMMENT '사용된 AI 모델',
    timecreated bigint(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_userid (userid),
    KEY idx_intervention_type (intervention_type),
    KEY idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 개입 기록';

-- 4. 시그니처 루틴 템플릿 테이블
CREATE TABLE IF NOT EXISTS mdl_abessi_signature_routines (
    id bigint(10) NOT NULL AUTO_INCREMENT,
    routine_code varchar(50) NOT NULL COMMENT '루틴 코드',
    routine_name varchar(100) NOT NULL COMMENT '루틴 이름',
    target_problem varchar(200) NOT NULL COMMENT '목표 문제 해결',
    routine_steps text NOT NULL COMMENT 'JSON 형식의 루틴 단계',
    expected_outcome text COMMENT '기대 효과',
    difficulty_level int DEFAULT 1 COMMENT '난이도 (1-5)',
    recommended_for text COMMENT '추천 대상 조건',
    success_rate decimal(5,2) DEFAULT NULL COMMENT '평균 성공률',
    usage_count int DEFAULT 0 COMMENT '사용 횟수',
    is_active tinyint(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated bigint(10) NOT NULL,
    timemodified bigint(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_routine_code (routine_code),
    KEY idx_difficulty_level (difficulty_level),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='시그니처 루틴 템플릿';

-- 5. 학습 인사이트 캐시 테이블
CREATE TABLE IF NOT EXISTS mdl_abessi_learning_insights (
    id bigint(10) NOT NULL AUTO_INCREMENT,
    userid bigint(10) NOT NULL COMMENT '학생 ID',
    insight_type varchar(50) NOT NULL COMMENT '인사이트 타입',
    insight_data text NOT NULL COMMENT 'JSON 형식의 인사이트 데이터',
    generated_by varchar(50) DEFAULT 'ai' COMMENT '생성 주체 (ai, teacher, system)',
    validity_period int DEFAULT 7 COMMENT '유효기간 (일)',
    is_active tinyint(1) DEFAULT 1 COMMENT '활성화 여부',
    view_count int DEFAULT 0 COMMENT '조회 횟수',
    usefulness_rating decimal(3,2) DEFAULT NULL COMMENT '유용성 평가 (0-5)',
    timecreated bigint(10) NOT NULL,
    timeexpired bigint(10) NOT NULL COMMENT '만료 시간',
    PRIMARY KEY (id),
    KEY idx_userid (userid),
    KEY idx_insight_type (insight_type),
    KEY idx_userid_type (userid, insight_type),
    KEY idx_timeexpired (timeexpired)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='학습 인사이트 캐시';

-- 6. 기본 시그니처 루틴 데이터 삽입
INSERT INTO mdl_abessi_signature_routines
(routine_code, routine_name, target_problem, routine_steps, expected_outcome, difficulty_level, recommended_for, timecreated, timemodified)
VALUES
('30SEC_THINKING', '30초 사고 마스터', '성급한 문제 풀이로 인한 실수',
 '[{"step":1,"name":"문제 읽기","duration":10,"description":"천천히 핵심 단어 파악"},{"step":2,"name":"멈추고 생각하기","duration":30,"description":"관련 개념 3개 떠올리기"},{"step":3,"name":"전략 선택","duration":10,"description":"풀이 방법 결정"},{"step":4,"name":"실행","duration":60,"description":"선택한 전략으로 풀이"}]',
 '문제 접근 시 신중함 증가, 실수 감소, 정답률 15% 향상',
 2, '{"grade":"중학생","problem_type":"조급함","target_score_improvement":15}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

('CONCEPT_MAPPING', '개념 연결 마스터', '개념 간 연결 부족',
 '[{"step":1,"name":"핵심 개념 추출","duration":20,"description":"문제에서 핵심 개념 찾기"},{"step":2,"name":"관련 개념 매핑","duration":30,"description":"연관 개념 그리기"},{"step":3,"name":"연결고리 찾기","duration":20,"description":"개념 간 관계 파악"},{"step":4,"name":"통합 적용","duration":40,"description":"연결된 개념으로 문제 해결"}]',
 '개념 이해도 향상, 응용력 증가',
 3, '{"grade":"중학생","problem_type":"개념이해","study_level":"review"}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

('ERROR_PATTERN', '오답 패턴 분석가', '반복되는 실수 패턴',
 '[{"step":1,"name":"오답 수집","duration":15,"description":"틀린 문제 모으기"},{"step":2,"name":"패턴 분석","duration":25,"description":"공통된 실수 찾기"},{"step":3,"name":"원인 파악","duration":20,"description":"실수 원인 분석"},{"step":4,"name":"대책 수립","duration":20,"description":"예방 전략 만들기"}]',
 '반복 실수 70% 감소, 메타인지 능력 향상',
 3, '{"grade":"중학생","problem_type":"반복실수","min_errors":5}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 인덱스 추가 (성능 최적화)
ALTER TABLE mdl_abessi_ai_analysis ADD INDEX idx_userid_agent_date (userid, agent_type, created_date);
ALTER TABLE mdl_abessi_learning_routine ADD INDEX idx_userid_routine (userid, routine_name);
ALTER TABLE mdl_abessi_intervention_log ADD INDEX idx_userid_time (userid, timecreated);

-- 권한 부여 (필요시)
-- GRANT ALL PRIVILEGES ON mathking.mdl_abessi_ai_analysis TO 'moodle'@'%';
-- GRANT ALL PRIVILEGES ON mathking.mdl_abessi_learning_routine TO 'moodle'@'%';
-- GRANT ALL PRIVILEGES ON mathking.mdl_abessi_intervention_log TO 'moodle'@'%';
-- GRANT ALL PRIVILEGES ON mathking.mdl_abessi_signature_routines TO 'moodle'@'%';
-- GRANT ALL PRIVILEGES ON mathking.mdl_abessi_learning_insights TO 'moodle'@'%';
-- FLUSH PRIVILEGES;