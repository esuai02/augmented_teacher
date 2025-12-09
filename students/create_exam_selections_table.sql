-- mdl_abessi_exam_selections 테이블 생성 쿼리
-- 내신대비 체크박스 선택 상태를 저장하는 테이블

CREATE TABLE IF NOT EXISTS `mdl_abessi_exam_selections` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL COMMENT '학생 ID',
  `cid` varchar(100) NOT NULL COMMENT '과목 ID (curriculum ID)',
  `selections` longtext NOT NULL COMMENT 'JSON 형식의 선택된 문제 정보',
  `timecreated` bigint(10) NOT NULL COMMENT '생성 시간',
  `timemodified` bigint(10) NOT NULL COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_abes_examsel_usecid_uix` (`userid`, `cid`),
  KEY `mdl_abes_examsel_use_ix` (`userid`),
  KEY `mdl_abes_examsel_cid_ix` (`cid`),
  KEY `mdl_abes_examsel_tim_ix` (`timemodified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='내신대비 체크박스 선택 상태 저장';

-- 테이블 설명
-- id: 자동 증가 기본키
-- userid: 학생의 Moodle 사용자 ID
-- cid: 과목 ID (mdl_abessi_curriculum 테이블의 id)
-- selections: JSON 형식으로 저장된 선택 정보
--   예시: {"midterm":{"basic":{"midterm-basic-0":{"originalIndex":"0"}},"standard":{}},"final":{}}
-- timecreated: 레코드 생성 시간 (Unix timestamp)
-- timemodified: 레코드 수정 시간 (Unix timestamp)

-- 인덱스 설명
-- UNIQUE KEY: userid와 cid 조합은 유일해야 함 (한 학생이 한 과목에 대해 하나의 선택 정보만 가짐)
-- KEY mdl_abes_examsel_use_ix: userid로 빠른 검색을 위한 인덱스
-- KEY mdl_abes_examsel_cid_ix: cid로 빠른 검색을 위한 인덱스
-- KEY mdl_abes_examsel_tim_ix: 수정 시간으로 정렬/검색을 위한 인덱스