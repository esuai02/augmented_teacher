-- 데이터베이스 테이블 구조를 insert SQL 파일들과 일치하도록 조정
-- 실행 전 반드시 백업을 먼저 수행하세요!

-- ========================================
-- 1. 백업 테이블 생성 (안전을 위해)
-- ========================================
CREATE TABLE IF NOT EXISTS mdl_alt42i_math_patterns_backup AS 
SELECT * FROM mdl_alt42i_math_patterns;

CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_solutions_backup AS 
SELECT * FROM mdl_alt42i_pattern_solutions;

-- ========================================
-- 2. mdl_alt42i_math_patterns 테이블 수정
-- ========================================

-- 기존 데이터가 있다면 pattern_id를 id로 매핑
UPDATE mdl_alt42i_math_patterns 
SET id = pattern_id 
WHERE pattern_id IS NOT NULL AND id != pattern_id;

-- pattern_name을 name으로 변경
ALTER TABLE mdl_alt42i_math_patterns 
CHANGE COLUMN pattern_name name VARCHAR(100) NOT NULL;

-- pattern_desc를 description으로 변경
ALTER TABLE mdl_alt42i_math_patterns 
CHANGE COLUMN pattern_desc description TEXT NOT NULL;

-- unique key 제거 (존재하는 경우)
ALTER TABLE mdl_alt42i_math_patterns 
DROP INDEX IF EXISTS uk_pattern_id;

-- pattern_id 컬럼 제거 (id를 직접 사용)
ALTER TABLE mdl_alt42i_math_patterns 
DROP COLUMN IF EXISTS pattern_id;

-- is_active 컬럼 제거 (insert 파일에서 사용하지 않음)
ALTER TABLE mdl_alt42i_math_patterns 
DROP COLUMN IF EXISTS is_active;

-- ========================================
-- 3. mdl_alt42i_pattern_solutions 테이블 수정
-- ========================================

-- check_method 필드가 없으면 추가
ALTER TABLE mdl_alt42i_pattern_solutions 
ADD COLUMN IF NOT EXISTS check_method TEXT NOT NULL AFTER action;

-- 불필요한 필드 제거 (insert 파일에서 사용하지 않음)
ALTER TABLE mdl_alt42i_pattern_solutions 
DROP COLUMN IF EXISTS example_problem;

ALTER TABLE mdl_alt42i_pattern_solutions 
DROP COLUMN IF EXISTS practice_guide;

-- ========================================
-- 4. 최종 테이블 구조 확인
-- ========================================
-- mdl_alt42i_math_patterns 테이블은 다음 필드를 가져야 합니다:
-- id, name, description, category_id, icon, priority, audio_time, created_at, updated_at

-- mdl_alt42i_pattern_solutions 테이블은 다음 필드를 가져야 합니다:
-- id, pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at

-- ========================================
-- 5. 테이블 구조 확인 쿼리
-- ========================================
DESCRIBE mdl_alt42i_math_patterns;
DESCRIBE mdl_alt42i_pattern_solutions;

-- ========================================
-- 6. 롤백이 필요한 경우
-- ========================================
-- DROP TABLE mdl_alt42i_math_patterns;
-- DROP TABLE mdl_alt42i_pattern_solutions;
-- RENAME TABLE mdl_alt42i_math_patterns_backup TO mdl_alt42i_math_patterns;
-- RENAME TABLE mdl_alt42i_pattern_solutions_backup TO mdl_alt42i_pattern_solutions;