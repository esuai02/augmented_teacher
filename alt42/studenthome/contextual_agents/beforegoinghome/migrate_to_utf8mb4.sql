-- ============================================================
-- 귀가검사 리포트 테이블 UTF-8mb4 마이그레이션
-- 파일: migrate_to_utf8mb4.sql
-- 목적: report_html 컬럼을 utf8mb4로 변환하여 이모지 저장 가능하도록 수정
-- 날짜: 2025-11-13
-- ============================================================

-- 1. 현재 테이블 상태 확인
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'mdl_alt42_goinghome_reports'
  AND COLUMN_NAME IN ('report_html', 'report_data');

-- 2. 백업 권장 (실행 전 수동으로 백업)
-- mysqldump -u [username] -p [database_name] mdl_alt42_goinghome_reports > backup_goinghome_reports_20251113.sql

-- 3. report_html 컬럼을 utf8mb4로 변환
ALTER TABLE mdl_alt42_goinghome_reports
MODIFY COLUMN report_html LONGTEXT
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
COMMENT '리포트 HTML (이모지 포함 가능)';

-- 4. report_data 컬럼도 utf8mb4로 변환 (일관성 유지)
ALTER TABLE mdl_alt42_goinghome_reports
MODIFY COLUMN report_data LONGTEXT
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
COMMENT '리포트 JSON 데이터 (이모지 포함 가능)';

-- 5. 변환 결과 확인
SELECT
    COLUMN_NAME,
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    DATA_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'mdl_alt42_goinghome_reports'
  AND COLUMN_NAME IN ('report_html', 'report_data');

-- 6. 테스트 데이터 삽입 (이모지 포함)
-- 이 부분은 migrate_to_utf8mb4.php에서 수행
