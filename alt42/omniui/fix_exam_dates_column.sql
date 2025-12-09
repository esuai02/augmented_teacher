-- Fix for mdl_alt42t_exam_dates table
-- Add missing math_exam_date column

-- Check current table structure first
SHOW CREATE TABLE mdl_alt42t_exam_dates;

-- Add math_exam_date column if it doesn't exist
ALTER TABLE mdl_alt42t_exam_dates 
ADD COLUMN IF NOT EXISTS math_exam_date DATE DEFAULT NULL AFTER end_date;

-- If your MySQL version doesn't support IF NOT EXISTS, use this approach:
-- First check if column exists:
SELECT COUNT(*) 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'mathking' 
AND TABLE_NAME = 'mdl_alt42t_exam_dates' 
AND COLUMN_NAME = 'math_exam_date';

-- If the count is 0, then run:
-- ALTER TABLE mdl_alt42t_exam_dates 
-- ADD COLUMN math_exam_date DATE DEFAULT NULL AFTER end_date;

-- Also check if the column name is different (e.g., math_date instead of math_exam_date)
DESCRIBE mdl_alt42t_exam_dates;