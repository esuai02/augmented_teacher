-- 테이블을 안전하게 재생성하는 SQL (외래 키 제약 조건 처리 포함)

-- ========================================
-- 0. 외래 키 체크 비활성화
-- ========================================
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- 1. 기존 백업 테이블 삭제
-- ========================================
DROP TABLE IF EXISTS mdl_alt42i_math_patterns_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_solutions_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_categories_old;
DROP TABLE IF EXISTS mdl_alt42i_user_pattern_progress_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_practice_logs_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_audio_files_old;

-- ========================================
-- 2. 기존 테이블 백업 (존재하는 경우만)
-- ========================================
-- 외래 키가 있는 자식 테이블부터 백업
CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_audio_files_old AS SELECT * FROM mdl_alt42i_pattern_audio_files;
DROP TABLE IF EXISTS mdl_alt42i_pattern_audio_files;

CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_practice_logs_old AS SELECT * FROM mdl_alt42i_pattern_practice_logs;
DROP TABLE IF EXISTS mdl_alt42i_pattern_practice_logs;

CREATE TABLE IF NOT EXISTS mdl_alt42i_user_pattern_progress_old AS SELECT * FROM mdl_alt42i_user_pattern_progress;
DROP TABLE IF EXISTS mdl_alt42i_user_pattern_progress;

CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_solutions_old AS SELECT * FROM mdl_alt42i_pattern_solutions;
DROP TABLE IF EXISTS mdl_alt42i_pattern_solutions;

CREATE TABLE IF NOT EXISTS mdl_alt42i_math_patterns_old AS SELECT * FROM mdl_alt42i_math_patterns;
DROP TABLE IF EXISTS mdl_alt42i_math_patterns;

CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_categories_old AS SELECT * FROM mdl_alt42i_pattern_categories;
DROP TABLE IF EXISTS mdl_alt42i_pattern_categories;

-- ========================================
-- 3. 카테고리 테이블 생성
-- ========================================
CREATE TABLE mdl_alt42i_pattern_categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(50) NOT NULL,
    display_order INT(11) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_category_code (category_code),
    KEY idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 카테고리 데이터 삽입
INSERT INTO mdl_alt42i_pattern_categories (id, category_name, category_code, display_order, description) VALUES
(1, '인지 과부하', 'cognitive_overload', 1, '정보 처리 용량 초과로 인한 학습 장애'),
(2, '자신감 왜곡', 'confidence_distortion', 2, '자신감 수준과 실제 능력 간의 불일치'),
(3, '실수 패턴', 'mistake_patterns', 3, '반복적으로 나타나는 실수 유형'),
(4, '접근 전략 오류', 'approach_errors', 4, '문제 해결 전략의 부적절한 선택'),
(5, '학습 습관', 'study_habits', 5, '비효율적인 학습 방법과 습관'),
(6, '시간/압박 관리', 'time_pressure', 6, '시간 관리 및 압박 대처 문제'),
(7, '검증/확인 부재', 'verification_absence', 7, '답안 검토 및 확인 과정 부족'),
(8, '기타 장애', 'other_obstacles', 8, '기타 학습 장애 요인');

-- ========================================
-- 4. 새 테이블 생성 (INSERT SQL과 일치하는 구조)
-- ========================================
CREATE TABLE mdl_alt42i_math_patterns (
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
    KEY idx_priority (priority),
    CONSTRAINT fk_pattern_category FOREIGN KEY (category_id) 
        REFERENCES mdl_alt42i_pattern_categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mdl_alt42i_pattern_solutions (
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
-- 5. 외래 키 체크 다시 활성화
-- ========================================
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 6. 구조 확인
-- ========================================
SHOW COLUMNS FROM mdl_alt42i_math_patterns;
SHOW COLUMNS FROM mdl_alt42i_pattern_solutions;

-- ========================================
-- 7. 테스트 데이터로 구조 검증
-- ========================================
-- 테스트 삽입
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) 
VALUES (999, '테스트 패턴', '테스트 설명', 1, '🧪', 'low', '1:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) 
VALUES (999, '테스트 액션', '테스트 체크', '테스트 스크립트', '테스트 대화', NOW(), NOW());

-- 테스트 데이터 삭제
DELETE FROM mdl_alt42i_pattern_solutions WHERE pattern_id = 999;
DELETE FROM mdl_alt42i_math_patterns WHERE id = 999;

-- ========================================
-- 8. 성공 메시지
-- ========================================
SELECT '테이블이 성공적으로 재생성되었습니다. 이제 insert_personas SQL 파일들을 실행할 수 있습니다.' AS '결과';

-- ========================================
-- 9. 이전 데이터 복구가 필요한 경우
-- ========================================
-- 아래 쿼리들은 주석 처리되어 있습니다. 필요시 주석을 해제하여 사용하세요.
-- 
-- INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at)
-- SELECT pattern_id, pattern_name, pattern_desc, category_id, icon, priority, audio_time, created_at, updated_at
-- FROM mdl_alt42i_math_patterns_old;
-- 
-- INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at)
-- SELECT pattern_id, action, 
--        IFNULL(check_method, ''), -- check_method가 없는 경우 빈 문자열
--        audio_script, teacher_dialog, created_at, updated_at
-- FROM mdl_alt42i_pattern_solutions_old;