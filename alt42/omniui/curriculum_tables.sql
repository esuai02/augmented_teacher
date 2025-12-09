-- 커리큘럼 오케스트레이터 테이블 생성 SQL
-- phpMyAdmin 또는 MySQL 클라이언트에서 직접 실행

USE mathking;

-- 1. 설정 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_config` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `config_key` VARCHAR(100) NOT NULL,
    `config_value` TEXT,
    `courseid` BIGINT(10) DEFAULT NULL,
    `description` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_key_course` (`config_key`, `courseid`),
    KEY `idx_courseid` (`courseid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 설정';

-- 2. 커리큘럼 계획 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_plan` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL,
    `courseid` BIGINT(10) NOT NULL,
    `exam_date` DATE NOT NULL,
    `exam_type` VARCHAR(50) DEFAULT NULL,
    `exam_name` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT(10) NOT NULL,
    `ratio_lead` INT(3) NOT NULL DEFAULT 70,
    `ratio_review` INT(3) NOT NULL DEFAULT 30,
    `daily_minutes` INT(5) NOT NULL DEFAULT 120,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` INT(5) NOT NULL,
    `metadata` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_userid` (`userid`),
    KEY `idx_courseid` (`courseid`),
    KEY `idx_status` (`status`),
    KEY `idx_exam_date` (`exam_date`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 계획';

-- 3. 커리큘럼 항목 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_items` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `planid` BIGINT(10) NOT NULL,
    `day_index` INT(5) NOT NULL,
    `sequence` INT(5) NOT NULL DEFAULT 1,
    `item_type` VARCHAR(20) NOT NULL,
    `ref_type` VARCHAR(20) NOT NULL,
    `ref_id` BIGINT(10) NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `est_minutes` INT(5) NOT NULL DEFAULT 20,
    `actual_minutes` INT(5) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `due_date` DATE NOT NULL,
    `completed_date` DATETIME DEFAULT NULL,
    `difficulty` INT(1) DEFAULT 2,
    `priority` INT(1) DEFAULT 5,
    `tags` TEXT,
    `metadata` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_planid` (`planid`),
    KEY `idx_day_index` (`day_index`),
    KEY `idx_item_type` (`item_type`),
    KEY `idx_status` (`status`),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_ref` (`ref_type`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 항목';

-- 4. 진행 상황 추적 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_progress` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `planid` BIGINT(10) NOT NULL,
    `userid` BIGINT(10) NOT NULL,
    `day_index` INT(5) NOT NULL,
    `date` DATE NOT NULL,
    `planned_items` INT(5) NOT NULL DEFAULT 0,
    `completed_items` INT(5) NOT NULL DEFAULT 0,
    `planned_minutes` INT(5) NOT NULL DEFAULT 0,
    `actual_minutes` INT(5) NOT NULL DEFAULT 0,
    `completion_rate` DECIMAL(5,2) DEFAULT 0,
    `lead_completed` INT(5) NOT NULL DEFAULT 0,
    `review_completed` INT(5) NOT NULL DEFAULT 0,
    `notes` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_plan_user_day` (`planid`, `userid`, `day_index`),
    KEY `idx_userid` (`userid`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 진행상황';

-- 5. KPI 메트릭 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_kpi` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `planid` BIGINT(10) NOT NULL,
    `metric_date` DATE NOT NULL,
    `completion_rate` DECIMAL(5,2) DEFAULT 0,
    `review_resolution_rate` DECIMAL(5,2) DEFAULT 0,
    `daily_achievement_rate` DECIMAL(5,2) DEFAULT 0,
    `estimated_completion_date` DATE DEFAULT NULL,
    `days_ahead_behind` INT(5) DEFAULT 0,
    `total_study_minutes` INT(10) DEFAULT 0,
    `average_daily_minutes` DECIMAL(7,2) DEFAULT 0,
    `streak_days` INT(5) DEFAULT 0,
    `metadata` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_plan_date` (`planid`, `metric_date`),
    KEY `idx_metric_date` (`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 KPI';

-- 6. 오답노트 연동 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_review_pool` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL,
    `courseid` BIGINT(10) NOT NULL,
    `question_id` BIGINT(10) NOT NULL,
    `question_type` VARCHAR(50) DEFAULT NULL,
    `chapter_id` BIGINT(10) DEFAULT NULL,
    `error_count` INT(5) NOT NULL DEFAULT 1,
    `last_error_date` DATETIME NOT NULL,
    `resolution_status` VARCHAR(20) NOT NULL DEFAULT 'unresolved',
    `difficulty` INT(1) DEFAULT 2,
    `priority_score` DECIMAL(5,2) DEFAULT 50.00,
    `tags` TEXT,
    `notes` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_question` (`userid`, `question_id`),
    KEY `idx_courseid` (`courseid`),
    KEY `idx_status` (`resolution_status`),
    KEY `idx_priority` (`priority_score`),
    KEY `idx_last_error` (`last_error_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='오답노트 풀';

-- 7. 개념 맵 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_concept_map` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `courseid` BIGINT(10) NOT NULL,
    `concept_id` BIGINT(10) NOT NULL,
    `concept_name` VARCHAR(255) NOT NULL,
    `chapter_id` BIGINT(10) DEFAULT NULL,
    `prereq_of` TEXT COMMENT 'JSON array of concept_ids',
    `depends_on` TEXT COMMENT 'JSON array of concept_ids',
    `difficulty` INT(1) DEFAULT 2,
    `importance` INT(1) DEFAULT 5,
    `est_minutes` INT(5) DEFAULT 30,
    `tags` TEXT,
    `metadata` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_course_concept` (`courseid`, `concept_id`),
    KEY `idx_chapter` (`chapter_id`),
    KEY `idx_difficulty` (`difficulty`),
    KEY `idx_importance` (`importance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개념 맵';

-- 8. 활동 로그 테이블
CREATE TABLE IF NOT EXISTS `mdl_curriculum_log` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `planid` BIGINT(10) DEFAULT NULL,
    `userid` BIGINT(10) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` BIGINT(10) DEFAULT NULL,
    `old_value` TEXT,
    `new_value` TEXT,
    `metadata` TEXT,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT,
    `timecreated` BIGINT(10) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_planid` (`planid`),
    KEY `idx_userid` (`userid`),
    KEY `idx_action` (`action`),
    KEY `idx_timecreated` (`timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='활동 로그';

-- 기본 설정 데이터 삽입
INSERT IGNORE INTO mdl_curriculum_config 
(config_key, config_value, courseid, description, timecreated, timemodified) 
VALUES 
('default_ratio_lead', '70', NULL, '기본 선행학습 비율', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('default_ratio_review', '30', NULL, '기본 복습 비율', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('default_daily_minutes', '120', NULL, '기본 일일 학습 시간(분)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('min_item_minutes', '10', NULL, '최소 학습 단위(분)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('max_item_minutes', '30', NULL, '최대 학습 단위(분)', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('auto_redistribute', '1', NULL, '미이행 항목 자동 재분배', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('enable_notifications', '1', NULL, '알림 활성화', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('notification_time', '08:00:00', NULL, '일일 알림 시간', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());