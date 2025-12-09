-- 백업 테이블 문제 해결 SQL

-- ========================================
-- 1. 백업 테이블들의 외래 키 제약 조건 확인
-- ========================================
SELECT 
    TABLE_NAME AS '테이블명',
    CONSTRAINT_NAME AS '제약조건명',
    REFERENCED_TABLE_NAME AS '참조 테이블'
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE 'mdl_alt42i_%_old'
ORDER BY
    TABLE_NAME;

-- ========================================
-- 2. 백업 테이블을 참조하는 외래 키 확인
-- ========================================
SELECT 
    TABLE_NAME AS '참조하는 테이블',
    CONSTRAINT_NAME AS '제약조건명',
    REFERENCED_TABLE_NAME AS '참조되는 백업 테이블'
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME LIKE 'mdl_alt42i_%_old'
ORDER BY
    REFERENCED_TABLE_NAME;

-- ========================================
-- 3. 백업 테이블명을 다르게 변경하여 회피
-- ========================================
SET FOREIGN_KEY_CHECKS = 0;

-- 기존 백업 테이블을 다른 이름으로 변경
RENAME TABLE mdl_alt42i_math_patterns_old TO mdl_alt42i_math_patterns_backup_temp;
RENAME TABLE mdl_alt42i_pattern_solutions_old TO mdl_alt42i_pattern_solutions_backup_temp;
RENAME TABLE mdl_alt42i_pattern_categories_old TO mdl_alt42i_pattern_categories_backup_temp;
RENAME TABLE mdl_alt42i_user_pattern_progress_old TO mdl_alt42i_user_pattern_progress_backup_temp;
RENAME TABLE mdl_alt42i_pattern_practice_logs_old TO mdl_alt42i_pattern_practice_logs_backup_temp;
RENAME TABLE mdl_alt42i_pattern_audio_files_old TO mdl_alt42i_pattern_audio_files_backup_temp;

-- 이제 _old 테이블들을 삭제 가능
DROP TABLE IF EXISTS mdl_alt42i_math_patterns_backup_temp;
DROP TABLE IF EXISTS mdl_alt42i_pattern_solutions_backup_temp;
DROP TABLE IF EXISTS mdl_alt42i_pattern_categories_backup_temp;
DROP TABLE IF EXISTS mdl_alt42i_user_pattern_progress_backup_temp;
DROP TABLE IF EXISTS mdl_alt42i_pattern_practice_logs_backup_temp;
DROP TABLE IF EXISTS mdl_alt42i_pattern_audio_files_backup_temp;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 4. 성공 메시지
-- ========================================
SELECT '백업 테이블이 정리되었습니다. 이제 force_recreate_tables.sql을 실행할 수 있습니다.' AS '결과';