-- mdl_alt42t_study_status 테이블에 study_level 컬럼 추가
-- 이 스크립트를 실행하여 누락된 컬럼을 추가하세요

-- 먼저 컬럼이 있는지 확인
SELECT COUNT(*) as column_exists
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'mathking' 
AND TABLE_NAME = 'mdl_alt42t_study_status' 
AND COLUMN_NAME = 'study_level';

-- 컬럼이 없다면 추가
ALTER TABLE mdl_alt42t_study_status 
ADD COLUMN study_level VARCHAR(20) DEFAULT NULL 
COMMENT 'Study level: concept, review, or practice' 
AFTER status;

-- 인덱스 추가
ALTER TABLE mdl_alt42t_study_status 
ADD INDEX idx_study_level (study_level);