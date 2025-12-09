-- 출결 기록 테이블
CREATE TABLE IF NOT EXISTS `mdl_abessi_attendance_record` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `teacherid` bigint(10) NOT NULL,
  `type` varchar(50) NOT NULL, -- absence, makeup_complete, add_absence
  `reason` varchar(255) DEFAULT NULL,
  `hours` decimal(5,2) NOT NULL DEFAULT 0,
  `date` date NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `teacherid` (`teacherid`),
  KEY `date` (`date`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 출결 로그 테이블
CREATE TABLE IF NOT EXISTS `mdl_abessi_attendance_log` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `teacherid` bigint(10) NOT NULL,
  `action` varchar(50) NOT NULL,
  `data` text,
  `timecreated` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `teacherid` (`teacherid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 알림 로그 테이블
CREATE TABLE IF NOT EXISTS `mdl_abessi_alert_log` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `alertid` varchar(50) NOT NULL,
  `teacherid` bigint(10) NOT NULL,
  `action` varchar(50) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alertid` (`alertid`),
  KEY `teacherid` (`teacherid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 스케줄 데이터 저장용 수정 (필요시)
ALTER TABLE `mdl_abessi_schedule` 
ADD COLUMN IF NOT EXISTS `schedule_data` TEXT DEFAULT NULL COMMENT 'JSON 형식의 스케줄 데이터';

-- 사용자 정의 필드 추가 (출결 통계용)
INSERT INTO `mdl_user_info_field` (`shortname`, `name`, `datatype`, `description`, `categoryid`, `required`, `locked`, `visible`, `forceunique`, `signup`, `defaultdata`, `param1`, `param2`, `param3`, `param4`, `param5`) 
VALUES 
('attendance_stats', '출결 통계', 'text', '학생의 출결 통계 정보 (JSON)', 1, 0, 0, 0, 0, 0, '{}', NULL, NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 인덱스 추가
CREATE INDEX IF NOT EXISTS `idx_attendance_date_userid` ON `mdl_abessi_attendance_record` (`date`, `userid`);
CREATE INDEX IF NOT EXISTS `idx_missionlog_date` ON `mdl_abessi_missionlog` (`timecreated`);