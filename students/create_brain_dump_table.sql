-- Brain Dump 테이블 생성
CREATE TABLE IF NOT EXISTS mdl_abessi_brain_dump (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    tags LONGTEXT,
    timecreated BIGINT(10) NOT NULL DEFAULT 0,
    timemodified BIGINT(10) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_userid (userid)
);

-- 사용자별 unique 인덱스 추가 (한 사용자당 하나의 brain dump 레코드)
ALTER TABLE mdl_abessi_brain_dump ADD UNIQUE KEY unique_userid (userid); 