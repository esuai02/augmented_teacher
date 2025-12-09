-- Brain Dump 테이블 생성 SQL
-- phpMyAdmin이나 MySQL 콘솔에서 실행하세요

CREATE TABLE IF NOT EXISTS `mdl_abessi_brain_dump` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `userid` BIGINT(10) NOT NULL,
    `tags` LONGTEXT,
    `timecreated` BIGINT(10) NOT NULL,
    `timemodified` BIGINT(10) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 