-- =====================================================
-- Student Presentation (발표하기) 저장 스키마
-- MySQL 5.7 호환
-- =====================================================

-- 학생 발표 텍스트/분석 결과 저장 (음성 파일은 저장하지 않음)
CREATE TABLE IF NOT EXISTS `mdl_at_student_presentations` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `analysis_id` VARCHAR(120) DEFAULT NULL COMMENT '학습 세션/화이트보드 식별자(learning_interface의 id 파라미터 등)',
    `userid` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 user id (mdl_user.id)',
    `contentsid` BIGINT(10) UNSIGNED NOT NULL COMMENT '컨텐츠 id (문항 id 등)',
    `contentstype` VARCHAR(50) NOT NULL COMMENT '컨텐츠 타입',
    `nrepeat` INT(10) UNSIGNED NOT NULL COMMENT '동일 컨텐츠 반복 횟수 (1부터 증가)',
    `duration_seconds` INT(10) UNSIGNED DEFAULT 0 COMMENT '발표 총 길이(초)',
    `presentation_text` LONGTEXT COMMENT 'STT 결과 텍스트',
    `analysis_json` LONGTEXT COMMENT 'JSON: 페르소나 분석 결과 원문',
    `weak_personas_json` TEXT COMMENT 'JSON: 취약 페르소나 id 배열',
    `selected_persona_ids_json` TEXT COMMENT 'JSON: 학생이 선택한 페르소나 id 배열',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_content` (`userid`, `contentsid`, `contentstype`),
    KEY `idx_analysis_id` (`analysis_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


