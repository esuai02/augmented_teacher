-- 필드 설명 테이블 생성
CREATE TABLE IF NOT EXISTS mdl_alt42_field_descriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(255) NOT NULL COMMENT '테이블명',
    field_name VARCHAR(255) NOT NULL COMMENT '필드명',
    description TEXT DEFAULT NULL COMMENT '필드 설명',
    type VARCHAR(100) DEFAULT NULL COMMENT '데이터 타입 (선택사항)',
    timecreated INT(10) NOT NULL COMMENT '생성 시간 (unixtime)',
    timemodified INT(10) NOT NULL COMMENT '수정 시간 (unixtime)',
    
    -- 테이블명과 필드명 조합으로 유니크 제약조건
    UNIQUE KEY unique_table_field (table_name, field_name),
    
    -- 인덱스 생성
    INDEX idx_table_name (table_name),
    INDEX idx_field_name (field_name),
    INDEX idx_timemodified (timemodified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='테이블 필드 설명 정보';