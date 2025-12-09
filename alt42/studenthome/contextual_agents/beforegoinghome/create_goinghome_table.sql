-- mdl_alt42_goinghome 테이블 생성
CREATE TABLE IF NOT EXISTS `mdl_alt42_goinghome` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `timecreated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mdl_alt42_goinghome_timecreated_idx` (`timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;