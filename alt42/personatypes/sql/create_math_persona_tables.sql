-- 수학 학습 패턴 데이터베이스 스키마
-- 60personas.txt 내용을 저장하기 위한 테이블 구조
-- 모든 테이블은 mdl_alt42i_ 접두사 사용

-- 1. 패턴 카테고리 마스터 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(100) NOT NULL COMMENT '카테고리명 (예: 인지 과부하)',
    `category_code` VARCHAR(50) NOT NULL COMMENT '카테고리 코드',
    `display_order` INT(11) DEFAULT 0 COMMENT '표시 순서',
    `description` TEXT COMMENT '카테고리 설명',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_category_code` (`category_code`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='수학 학습 패턴 카테고리';

-- 2. 수학 학습 패턴 메인 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_math_patterns` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `pattern_id` INT(11) NOT NULL COMMENT '패턴 번호 (1-60)',
    `pattern_name` VARCHAR(100) NOT NULL COMMENT '패턴명 (예: 계산 실수 반복)',
    `pattern_desc` TEXT NOT NULL COMMENT '패턴 설명',
    `category_id` INT(11) NOT NULL COMMENT '카테고리 ID',
    `icon` VARCHAR(10) DEFAULT '📊' COMMENT '아이콘',
    `priority` ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '우선순위',
    `audio_time` VARCHAR(20) DEFAULT '3분' COMMENT '음성 가이드 시간',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pattern_id` (`pattern_id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_priority` (`priority`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_pattern_category` FOREIGN KEY (`category_id`) 
        REFERENCES `mdl_alt42i_pattern_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='수학 학습 패턴 정보';

-- 3. 패턴 해결책 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_solutions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
    `action` TEXT NOT NULL COMMENT '실천 방법',
    `check_method` TEXT NOT NULL COMMENT '확인 방법',
    `audio_script` TEXT COMMENT '음성 대본',
    `teacher_dialog` TEXT COMMENT '교사 대화 템플릿',
    `example_problem` TEXT COMMENT '예시 문제',
    `practice_guide` TEXT COMMENT '연습 가이드',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pattern_solution` (`pattern_id`),
    CONSTRAINT `fk_solution_pattern` FOREIGN KEY (`pattern_id`) 
        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='패턴별 해결책 정보';

-- 4. 사용자 패턴 진행 상황 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_user_pattern_progress` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '사용자 ID',
    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
    `is_collected` TINYINT(1) DEFAULT 0 COMMENT '수집 여부',
    `mastery_level` INT(11) DEFAULT 0 COMMENT '숙달도 (0-100)',
    `practice_count` INT(11) DEFAULT 0 COMMENT '연습 횟수',
    `last_practice_at` DATETIME DEFAULT NULL COMMENT '마지막 연습 시간',
    `improvement_score` DECIMAL(5,2) DEFAULT 0 COMMENT '개선 점수',
    `notes` TEXT COMMENT '메모',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_pattern` (`user_id`, `pattern_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_pattern` (`pattern_id`),
    KEY `idx_collected` (`is_collected`),
    KEY `idx_mastery` (`mastery_level`),
    CONSTRAINT `fk_progress_pattern` FOREIGN KEY (`pattern_id`) 
        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자별 패턴 진행 상황';

-- 5. 패턴 연습 기록 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_practice_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '사용자 ID',
    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
    `practice_type` ENUM('self', 'guided', 'test') DEFAULT 'self' COMMENT '연습 유형',
    `duration_seconds` INT(11) DEFAULT 0 COMMENT '연습 시간(초)',
    `score` INT(11) DEFAULT NULL COMMENT '점수',
    `feedback` TEXT COMMENT '피드백 내용',
    `problem_data` JSON COMMENT '문제 데이터 (JSON)',
    `answer_data` JSON COMMENT '답변 데이터 (JSON)',
    `is_completed` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_pattern` (`user_id`, `pattern_id`),
    KEY `idx_practice_type` (`practice_type`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_log_pattern` FOREIGN KEY (`pattern_id`) 
        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='패턴 연습 기록';

-- 6. 음성 파일 관리 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_audio_files` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
    `audio_type` ENUM('guide', 'example', 'feedback') DEFAULT 'guide' COMMENT '음성 유형',
    `file_path` VARCHAR(500) NOT NULL COMMENT '파일 경로',
    `file_name` VARCHAR(255) NOT NULL COMMENT '파일명',
    `duration_seconds` INT(11) DEFAULT 0 COMMENT '재생 시간(초)',
    `language` VARCHAR(10) DEFAULT 'ko' COMMENT '언어 코드',
    `transcript` TEXT COMMENT '음성 대본',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pattern_audio` (`pattern_id`, `audio_type`),
    KEY `idx_language` (`language`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_audio_pattern` FOREIGN KEY (`pattern_id`) 
        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='패턴 음성 파일 정보';

-- 7. 주간 통계 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_weekly_stats` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '사용자 ID',
    `week_start_date` DATE NOT NULL COMMENT '주 시작일',
    `patterns_collected` INT(11) DEFAULT 0 COMMENT '수집한 패턴 수',
    `total_practice_time` INT(11) DEFAULT 0 COMMENT '총 연습 시간(초)',
    `average_score` DECIMAL(5,2) DEFAULT 0 COMMENT '평균 점수',
    `most_practiced_pattern` INT(11) DEFAULT NULL COMMENT '가장 많이 연습한 패턴',
    `improvement_rate` DECIMAL(5,2) DEFAULT 0 COMMENT '개선율(%)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_week` (`user_id`, `week_start_date`),
    KEY `idx_week` (`week_start_date`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주간 학습 통계';

-- 인덱스 추가
CREATE INDEX idx_pattern_name ON mdl_alt42i_math_patterns(pattern_name);
CREATE INDEX idx_pattern_priority_active ON mdl_alt42i_math_patterns(priority, is_active);
CREATE INDEX idx_progress_user_collected ON mdl_alt42i_user_pattern_progress(user_id, is_collected);
CREATE INDEX idx_log_user_created ON mdl_alt42i_pattern_practice_logs(user_id, created_at);

-- 기본 카테고리 데이터 삽입
INSERT INTO `mdl_alt42i_pattern_categories` (`category_name`, `category_code`, `display_order`, `description`) VALUES
('인지 과부하', 'cognitive_overload', 1, '정보 처리 용량 초과로 인한 학습 장애'),
('자신감 왜곡', 'confidence_distortion', 2, '자신감 수준과 실제 능력 간의 불일치'),
('실수 패턴', 'mistake_patterns', 3, '반복적으로 나타나는 실수 유형'),
('접근 전략 오류', 'approach_errors', 4, '문제 해결 전략의 부적절한 선택'),
('학습 습관', 'study_habits', 5, '비효율적인 학습 방법과 습관'),
('시간/압박 관리', 'time_pressure', 6, '시간 관리 및 압박 대처 문제'),
('검증/확인 부재', 'verification_absence', 7, '답안 검토 및 확인 과정 부족'),
('기타 장애', 'other_obstacles', 8, '기타 학습 장애 요인')
ON DUPLICATE KEY UPDATE 
    `description` = VALUES(`description`),
    `display_order` = VALUES(`display_order`);

-- 테이블 생성 완료 메시지
SELECT 'Database tables created successfully with mdl_alt42i_ prefix' AS status;