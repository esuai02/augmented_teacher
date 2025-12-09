-- ktm_teaching_interactions 테이블에 WebVTT 필드 추가
ALTER TABLE mdl_ktm_teaching_interactions 
ADD COLUMN webvtt_data LONGTEXT DEFAULT NULL COMMENT 'WebVTT 형식의 타이밍 데이터' AFTER narration_text;

-- 인덱스 추가 (WebVTT 데이터 존재 여부 빠른 확인)
ALTER TABLE mdl_ktm_teaching_interactions 
ADD INDEX idx_has_webvtt ((CASE WHEN webvtt_data IS NOT NULL THEN 1 ELSE 0 END));

-- 필드 추가 확인
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mdl_ktm_teaching_interactions'
AND COLUMN_NAME = 'webvtt_data';