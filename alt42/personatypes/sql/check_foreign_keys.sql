-- 외래 키 제약 조건 확인 SQL

-- ========================================
-- 1. 현재 데이터베이스의 모든 외래 키 확인
-- ========================================
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE 'mdl_alt42i_%'
ORDER BY
    TABLE_NAME,
    CONSTRAINT_NAME;

-- ========================================
-- 2. mdl_alt42i_math_patterns 테이블을 참조하는 외래 키 확인
-- ========================================
SELECT 
    TABLE_NAME AS '참조하는 테이블',
    COLUMN_NAME AS '참조하는 컬럼',
    CONSTRAINT_NAME AS '제약조건명'
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME = 'mdl_alt42i_math_patterns';

-- ========================================
-- 3. mdl_alt42i_pattern_solutions 테이블을 참조하는 외래 키 확인
-- ========================================
SELECT 
    TABLE_NAME AS '참조하는 테이블',
    COLUMN_NAME AS '참조하는 컬럼',
    CONSTRAINT_NAME AS '제약조건명'
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME = 'mdl_alt42i_pattern_solutions';

-- ========================================
-- 4. 특정 외래 키 제약 조건을 삭제하는 방법
-- ========================================
-- 예시: ALTER TABLE 테이블명 DROP FOREIGN KEY 제약조건명;
-- 
-- 모든 외래 키를 임시로 비활성화:
-- SET FOREIGN_KEY_CHECKS = 0;
-- 
-- 작업 후 다시 활성화:
-- SET FOREIGN_KEY_CHECKS = 1;