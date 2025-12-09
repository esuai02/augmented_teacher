-- PLP 시스템 테이블 생성 SQL
-- MathKing 데이터베이스에서 실행
-- Database: mathking (58.180.27.46)

-- 1. 학습 기록 테이블
CREATE TABLE IF NOT EXISTS `mdl_plp_learning_records` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `date` date NOT NULL,
  `summary` text DEFAULT NULL,
  `advance_mins` int(11) DEFAULT 0,
  `review_mins` int(11) DEFAULT 0,
  `summary_count` int(11) DEFAULT 0,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_plp_lr_user_date_uix` (`userid`, `date`),
  KEY `mdl_plp_lr_user_ix` (`userid`),
  KEY `mdl_plp_lr_date_ix` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 오답 태그 테이블
CREATE TABLE IF NOT EXISTS `mdl_plp_error_tags` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `problem_id` varchar(50) NOT NULL,
  `tags` text DEFAULT NULL,
  `difficulty` tinyint(1) DEFAULT 1,
  `timecreated` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mdl_plp_et_user_ix` (`userid`),
  KEY `mdl_plp_et_problem_ix` (`problem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 연속 통과 추적 테이블
CREATE TABLE IF NOT EXISTS `mdl_plp_streak_tracker` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `current_streak` int(11) DEFAULT 0,
  `best_streak` int(11) DEFAULT 0,
  `last_pass_date` date DEFAULT NULL,
  `timemodified` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_plp_st_user_uix` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 문제 체크 테이블
CREATE TABLE IF NOT EXISTS `mdl_plp_practice_checks` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `date` date NOT NULL,
  `problem_ids` text DEFAULT NULL,
  `checked_count` int(11) DEFAULT 0,
  `timecreated` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mdl_plp_pc_user_date_ix` (`userid`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 샘플 데이터 삽입 (테스트용)
-- 이현선 학생 샘플 데이터 (userid는 실제 사용자 ID로 변경 필요)
INSERT INTO `mdl_plp_learning_records` (`userid`, `date`, `summary`, `advance_mins`, `review_mins`, `summary_count`, `timecreated`, `timemodified`) 
VALUES 
(2, CURDATE(), '오늘은 미적분학 극한 개념을 학습하고 연습문제 5개를 풀었습니다.', 42, 18, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '삼각함수 미분법을 복습하고 오답노트를 정리했습니다.', 35, 25, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `mdl_plp_streak_tracker` (`userid`, `current_streak`, `best_streak`, `last_pass_date`, `timemodified`)
VALUES 
(2, 2, 5, CURDATE(), UNIX_TIMESTAMP());

INSERT INTO `mdl_plp_error_tags` (`userid`, `problem_id`, `tags`, `difficulty`, `timecreated`)
VALUES 
(2, 'calc_001', '극한,연속성,계산실수', 3, UNIX_TIMESTAMP()),
(2, 'calc_002', '미분,연쇄법칙', 2, UNIX_TIMESTAMP());

INSERT INTO `mdl_plp_practice_checks` (`userid`, `date`, `problem_ids`, `checked_count`, `timecreated`)
VALUES 
(2, CURDATE(), 'calc_001,calc_002,calc_003', 3, UNIX_TIMESTAMP());