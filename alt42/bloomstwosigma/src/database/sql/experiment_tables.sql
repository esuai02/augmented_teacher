-- ALT42 실험 관련 테이블들
-- 모든 시간은 unix time 사용

-- 1. 실험 기본 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_name VARCHAR(255) NOT NULL COMMENT '실험명',
    description TEXT DEFAULT NULL COMMENT '실험 설명',
    start_date INT(10) NOT NULL COMMENT '시작일 (unixtime)',
    duration_weeks INT DEFAULT 8 COMMENT '실험 기간 (주)',
    status ENUM('planned', 'active', 'completed', 'cancelled') DEFAULT 'planned' COMMENT '실험 상태',
    created_by INT NOT NULL COMMENT '생성자 ID (moodle user id)',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_created_by (created_by),
    INDEX idx_status (status),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실험 기본 정보';

-- 2. 개입 방법 (피드백 방법) 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_intervention_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    method_type ENUM('metacognitive', 'learning', 'combined', 'control') NOT NULL COMMENT '개입 방법 유형',
    method_name VARCHAR(255) NOT NULL COMMENT '방법명',
    description TEXT DEFAULT NULL COMMENT '방법 설명',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_method_type (method_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개입 방법 정보';

-- 3. 측정 지표 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_tracking_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    config_name VARCHAR(255) NOT NULL COMMENT '측정 지표명',
    description TEXT DEFAULT NULL COMMENT '측정 지표 설명',
    tracking_type ENUM('performance', 'behavior', 'engagement', 'feedback') DEFAULT 'performance' COMMENT '추적 유형',
    data_source VARCHAR(255) DEFAULT NULL COMMENT '데이터 소스',
    collection_frequency ENUM('daily', 'weekly', 'monthly', 'event') DEFAULT 'weekly' COMMENT '수집 빈도',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_tracking_type (tracking_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='측정 지표 설정';

-- 4. 그룹 배정 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_group_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    user_id INT NOT NULL COMMENT '사용자 ID (moodle user id)',
    group_type ENUM('control', 'experiment') NOT NULL COMMENT '그룹 유형',
    intervention_method_id INT DEFAULT NULL COMMENT '개입 방법 ID',
    teacher_id INT DEFAULT NULL COMMENT '담당 교사 ID',
    assigned_by INT NOT NULL COMMENT '배정한 사용자 ID',
    timecreated INT(10) NOT NULL COMMENT '배정 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    FOREIGN KEY (intervention_method_id) REFERENCES mdl_alt42_intervention_methods(id) ON DELETE SET NULL,
    UNIQUE KEY unique_experiment_user (experiment_id, user_id),
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_group_type (group_type),
    INDEX idx_teacher_id (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='그룹 배정 정보';

-- 5. 데이터베이스 연결 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_database_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    table_name VARCHAR(255) NOT NULL COMMENT '연결된 테이블명',
    database_name VARCHAR(255) DEFAULT 'mathking' COMMENT '데이터베이스명',
    connection_purpose TEXT DEFAULT NULL COMMENT '연결 목적',
    query_conditions TEXT DEFAULT NULL COMMENT '쿼리 조건 (JSON)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_table_name (table_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='데이터베이스 연결 정보';

-- 6. 실험 결과 기록 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_experiment_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    result_type ENUM('survey', 'analysis', 'observation', 'measurement') NOT NULL COMMENT '결과 유형',
    result_title VARCHAR(255) NOT NULL COMMENT '결과 제목',
    result_content TEXT DEFAULT NULL COMMENT '결과 내용',
    result_data TEXT DEFAULT NULL COMMENT '결과 데이터 (JSON)',
    author_id INT NOT NULL COMMENT '작성자 ID',
    collection_date INT(10) DEFAULT NULL COMMENT '수집 일자',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_result_type (result_type),
    INDEX idx_author_id (author_id),
    INDEX idx_collection_date (collection_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실험 결과 기록';

-- 7. 가설 기록 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_hypotheses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    hypothesis_text TEXT NOT NULL COMMENT '가설 내용',
    hypothesis_type ENUM('primary', 'secondary', 'exploratory') DEFAULT 'primary' COMMENT '가설 유형',
    status ENUM('proposed', 'tested', 'confirmed', 'rejected') DEFAULT 'proposed' COMMENT '가설 상태',
    evidence TEXT DEFAULT NULL COMMENT '증거/근거',
    author_id INT NOT NULL COMMENT '작성자 ID',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_hypothesis_type (hypothesis_type),
    INDEX idx_status (status),
    INDEX idx_author_id (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='가설 기록';

-- 8. 설문 조사 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    survey_title VARCHAR(255) NOT NULL COMMENT '설문 제목',
    survey_description TEXT DEFAULT NULL COMMENT '설문 설명',
    survey_type ENUM('pre', 'post', 'during', 'followup') NOT NULL COMMENT '설문 유형',
    target_group ENUM('all', 'control', 'experiment', 'teachers') DEFAULT 'all' COMMENT '대상 그룹',
    questions TEXT DEFAULT NULL COMMENT '설문 질문 (JSON)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    start_date INT(10) DEFAULT NULL COMMENT '시작 일자',
    end_date INT(10) DEFAULT NULL COMMENT '종료 일자',
    created_by INT NOT NULL COMMENT '생성자 ID',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_survey_type (survey_type),
    INDEX idx_target_group (target_group),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='설문 조사 정보';

-- 9. 설문 응답 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL COMMENT '설문 ID',
    respondent_id INT NOT NULL COMMENT '응답자 ID',
    question_id VARCHAR(50) NOT NULL COMMENT '질문 ID',
    response_value TEXT DEFAULT NULL COMMENT '응답 값',
    response_text TEXT DEFAULT NULL COMMENT '응답 텍스트',
    response_score DECIMAL(5,2) DEFAULT NULL COMMENT '응답 점수',
    timecreated INT(10) NOT NULL COMMENT '응답 시간',
    
    FOREIGN KEY (survey_id) REFERENCES mdl_alt42_surveys(id) ON DELETE CASCADE,
    UNIQUE KEY unique_survey_respondent_question (survey_id, respondent_id, question_id),
    INDEX idx_survey_id (survey_id),
    INDEX idx_respondent_id (respondent_id),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='설문 응답 데이터';

-- 10. 실험 진행 로그 테이블
CREATE TABLE IF NOT EXISTS mdl_alt42_experiment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL COMMENT '실험 ID',
    log_type ENUM('start', 'pause', 'resume', 'complete', 'modify', 'error') NOT NULL COMMENT '로그 유형',
    log_message TEXT NOT NULL COMMENT '로그 메시지',
    log_data TEXT DEFAULT NULL COMMENT '로그 데이터 (JSON)',
    user_id INT NOT NULL COMMENT '사용자 ID',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (experiment_id) REFERENCES mdl_alt42_experiments(id) ON DELETE CASCADE,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_log_type (log_type),
    INDEX idx_user_id (user_id),
    INDEX idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실험 진행 로그';