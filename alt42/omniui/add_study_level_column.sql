-- Add study_level column to mdl_alt42t_study_status table if it doesn't exist

-- For MySQL/MariaDB (Moodle default)
ALTER TABLE mdl_alt42t_study_status 
ADD COLUMN IF NOT EXISTS study_level VARCHAR(20) DEFAULT NULL 
COMMENT 'Study level: concept, review, or practice';

-- Update index to include new column
ALTER TABLE mdl_alt42t_study_status 
ADD INDEX IF NOT EXISTS idx_study_level (study_level);

-- If you need to run this manually and your MySQL version doesn't support IF NOT EXISTS, use:
-- First check if column exists:
-- SELECT COUNT(*) 
-- FROM information_schema.COLUMNS 
-- WHERE TABLE_SCHEMA = 'mathking' 
-- AND TABLE_NAME = 'mdl_alt42t_study_status' 
-- AND COLUMN_NAME = 'study_level';

-- If the count is 0, then run:
-- ALTER TABLE mdl_alt42t_study_status 
-- ADD COLUMN study_level VARCHAR(20) DEFAULT NULL 
-- COMMENT 'Study level: concept, review, or practice';