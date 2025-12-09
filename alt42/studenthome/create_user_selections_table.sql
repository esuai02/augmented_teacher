-- 사용자 학습 선택 정보를 저장하는 테이블
CREATE TABLE IF NOT EXISTS mdl_user_learning_selections (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    page_type VARCHAR(50) NOT NULL, -- 'index1', 'index2', 'index3', 'index4'
    selection_data LONGTEXT, -- JSON 형태로 선택 정보 저장
    last_path VARCHAR(255), -- 마지막 경로 정보
    last_unit VARCHAR(255), -- 마지막 선택한 단원
    last_topic VARCHAR(255), -- 마지막 선택한 주제
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY userid_page_idx (userid, page_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기존 데이터가 있는 경우를 위한 인덱스 추가
ALTER TABLE mdl_user_learning_selections 
ADD UNIQUE KEY unique_user_page (userid, page_type);