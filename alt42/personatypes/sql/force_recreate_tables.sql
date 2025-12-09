-- 강제로 테이블을 재생성하는 SQL (모든 제약 조건 해제)

-- ========================================
-- 1. 외래 키 제약 조건 완전 비활성화
-- ========================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- ========================================
-- 2. 모든 관련 테이블 강제 삭제 (순서 무관)
-- ========================================
DROP TABLE IF EXISTS mdl_alt42i_pattern_audio_files_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_practice_logs_old;
DROP TABLE IF EXISTS mdl_alt42i_user_pattern_progress_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_solutions_old;
DROP TABLE IF EXISTS mdl_alt42i_math_patterns_old;
DROP TABLE IF EXISTS mdl_alt42i_pattern_categories_old;

DROP TABLE IF EXISTS mdl_alt42i_pattern_audio_files;
DROP TABLE IF EXISTS mdl_alt42i_pattern_practice_logs;
DROP TABLE IF EXISTS mdl_alt42i_user_pattern_progress;
DROP TABLE IF EXISTS mdl_alt42i_pattern_solutions;
DROP TABLE IF EXISTS mdl_alt42i_math_patterns;
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
-- 4. 메인 테이블 생성 (INSERT SQL과 일치하는 구조)
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
SET SQL_SAFE_UPDATES = 1;

-- ========================================
-- 6. 구조 확인
-- ========================================
SHOW COLUMNS FROM mdl_alt42i_math_patterns;
SHOW COLUMNS FROM mdl_alt42i_pattern_solutions;

-- ========================================
-- 7. 완료 메시지
-- ========================================
SELECT '테이블이 성공적으로 재생성되었습니다!' AS '상태',
       '이제 insert_personas_1_to_10.sql과 insert_personas_11_to_60.sql을 실행하세요.' AS '다음 단계';