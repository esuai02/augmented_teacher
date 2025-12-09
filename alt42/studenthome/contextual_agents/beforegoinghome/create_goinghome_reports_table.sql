-- mdl_alt42_goinghome_reports 테이블 생성
-- 리포트 페이지를 그대로 저장하고 표시하기 위한 테이블
CREATE TABLE IF NOT EXISTS `mdl_alt42_goinghome_reports` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL COMMENT '학생 ID',
  `report_id` varchar(100) NOT NULL COMMENT '리포트 고유 ID',
  `report_html` longtext NOT NULL COMMENT '리포트 HTML 전체 내용',
  `report_data` longtext DEFAULT NULL COMMENT '리포트 데이터 (JSON) - 응답, 통계 등',
  `report_date` varchar(50) DEFAULT NULL COMMENT '리포트 날짜 (예: 2024년 1월 15일)',
  `timecreated` bigint(20) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
  `timemodified` bigint(20) DEFAULT NULL COMMENT '수정 시간 (Unix timestamp)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_alt42_goinghome_reports_reportid_uk` (`report_id`),
  KEY `mdl_alt42_goinghome_reports_userid_idx` (`userid`),
  KEY `mdl_alt42_goinghome_reports_timecreated_idx` (`timecreated`),
  KEY `mdl_alt42_goinghome_reports_userid_timecreated_idx` (`userid`, `timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='귀가검사 리포트 저장 테이블';

