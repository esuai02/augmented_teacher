-- 현재 테이블 구조 확인 및 수정 SQL

-- ========================================
-- 1. 현재 테이블 구조 확인
-- ========================================
SHOW COLUMNS FROM mdl_alt42i_math_patterns;
SHOW COLUMNS FROM mdl_alt42i_pattern_solutions;

-- ========================================
-- 2. 조건부 테이블 수정 (MySQL 5.7+)
-- ========================================

-- mdl_alt42i_math_patterns 테이블이 이미 올바른 구조를 가지고 있는 경우를 대비한 조건부 수정
-- 주의: 아래 명령어들은 각각 별도로 실행하고, 오류가 나면 무시하세요.

-- name 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS name VARCHAR(100) NOT NULL;

-- description 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS description TEXT NOT NULL;

-- category_id 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS category_id INT(11) NOT NULL;

-- icon 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS icon VARCHAR(10) DEFAULT '📊';

-- priority 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS priority ENUM('high', 'medium', 'low') DEFAULT 'medium';

-- audio_time 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS audio_time VARCHAR(20) DEFAULT '3분';

-- created_at 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- updated_at 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_math_patterns ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- mdl_alt42i_pattern_solutions 테이블 수정
-- check_method 컬럼이 없는 경우 추가
ALTER TABLE mdl_alt42i_pattern_solutions ADD COLUMN IF NOT EXISTS check_method TEXT NOT NULL AFTER action;

-- ========================================
-- 3. 테이블이 아예 없는 경우 생성
-- ========================================
CREATE TABLE IF NOT EXISTS mdl_alt42i_math_patterns (
    id INT(11) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category_id INT(11) NOT NULL,
    icon VARCHAR(10) DEFAULT '📊',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    audio_time VARCHAR(20) DEFAULT '3분',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_category (category_id),
    KEY idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_solutions (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pattern_id INT(11) NOT NULL,
    action TEXT NOT NULL,
    check_method TEXT NOT NULL,
    audio_script TEXT,
    teacher_dialog TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pattern_solution (pattern_id),
    CONSTRAINT fk_solution_pattern FOREIGN KEY (pattern_id) 
        REFERENCES mdl_alt42i_math_patterns (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. 최종 구조 확인
-- ========================================
SHOW COLUMNS FROM mdl_alt42i_math_patterns;
SHOW COLUMNS FROM mdl_alt42i_pattern_solutions;

-- ========================================
-- 5. 테스트 INSERT (구조 확인용)
-- ========================================
-- 아래 명령어가 오류 없이 실행되면 구조가 올바른 것입니다.
-- INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) 
-- VALUES (999, '테스트', '테스트 설명', 1, '🧪', 'low', '1:00', NOW(), NOW());
-- 
-- INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) 
-- VALUES (999, '테스트 액션', '테스트 체크', '테스트 스크립트', '테스트 대화', NOW(), NOW());
-- 
-- -- 테스트 데이터 삭제
-- DELETE FROM mdl_alt42i_pattern_solutions WHERE pattern_id = 999;
-- DELETE FROM mdl_alt42i_math_patterns WHERE id = 999;