-- =====================================================
-- AI Tutor 시스템 데이터베이스 스키마
-- MySQL 5.7 호환
-- 테이블 접두사: mdl_alt42_
-- =====================================================

-- =====================================================
-- 1. 분석 결과 테이블
-- 컨텐츠 분석 결과 저장 (메인 테이블)
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_analysis_results` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `analysis_id` VARCHAR(100) NOT NULL COMMENT '분석 고유 ID (ANALYSIS_timestamp_random)',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user.id)',
    `created_by` BIGINT(10) UNSIGNED NOT NULL COMMENT '생성자 ID',
    `text_content` LONGTEXT COMMENT '분석된 텍스트 내용',
    `image_data` LONGTEXT COMMENT 'Base64 인코딩된 이미지 데이터',
    `dialogue_analysis` LONGTEXT COMMENT 'JSON: 대화 분석 결과',
    `comprehensive_questions` LONGTEXT COMMENT 'JSON: 포괄적 질문 목록',
    `detailed_questions` LONGTEXT COMMENT 'JSON: 세부 질문 목록',
    `teaching_rules` LONGTEXT COMMENT 'JSON: 교수법 룰 목록',
    `ontology` LONGTEXT COMMENT 'JSON: 온톨로지 데이터',
    `rule_contents` LONGTEXT COMMENT 'JSON: 룰 컨텐츠',
    `metadata` LONGTEXT COMMENT 'JSON: 추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_analysis_id` (`analysis_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 분석 결과';


-- =====================================================
-- 2. 상호작용 히스토리 테이블
-- 학생-튜터 간 상호작용 기록
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_interactions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `interaction_id` VARCHAR(100) NOT NULL COMMENT '상호작용 고유 ID',
    `analysis_id` VARCHAR(100) DEFAULT NULL COMMENT '관련 분석 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    `user_input` TEXT NOT NULL COMMENT '학생 입력',
    `response_text` TEXT COMMENT 'AI 응답 텍스트',
    `response_data` LONGTEXT COMMENT 'JSON: 전체 응답 데이터',
    `matched_rules` TEXT COMMENT 'JSON: 매칭된 룰 목록',
    `persona_id` VARCHAR(50) DEFAULT NULL COMMENT '적용된 페르소나 ID',
    `intervention_id` VARCHAR(50) DEFAULT NULL COMMENT '적용된 개입 활동 ID',
    `context_data` LONGTEXT COMMENT 'JSON: 컨텍스트 데이터',
    `understanding_level` ENUM('very_low', 'low', 'medium', 'high', 'very_high') DEFAULT 'medium' COMMENT '이해도 레벨',
    `confidence` DECIMAL(3,2) DEFAULT 0.50 COMMENT '신뢰도 (0.00-1.00)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_interaction_id` (`interaction_id`),
    KEY `idx_analysis_id` (`analysis_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_persona_id` (`persona_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 상호작용 히스토리';


-- =====================================================
-- 3. 생성된 룰 테이블
-- 분석에서 생성된 교수법 룰
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_generated_rules` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_id` VARCHAR(100) NOT NULL COMMENT '룰 고유 ID',
    `analysis_id` VARCHAR(100) DEFAULT NULL COMMENT '생성된 분석 ID',
    `priority` INT(3) NOT NULL DEFAULT 50 COMMENT '우선순위 (1-100)',
    `description` TEXT NOT NULL COMMENT '룰 설명',
    `conditions` LONGTEXT COMMENT 'JSON: 조건 목록',
    `actions` LONGTEXT COMMENT 'JSON: 액션 목록',
    `confidence` DECIMAL(3,2) DEFAULT 0.80 COMMENT '신뢰도 (0.00-1.00)',
    `rationale` TEXT COMMENT '룰 근거/이유',
    `category` VARCHAR(50) DEFAULT NULL COMMENT '룰 카테고리 (U0-U4)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `execution_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '실행 횟수',
    `success_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '성공 횟수',
    `metadata` LONGTEXT COMMENT 'JSON: 추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_rule_id` (`rule_id`),
    KEY `idx_analysis_id` (`analysis_id`),
    KEY `idx_priority` (`priority`),
    KEY `idx_category` (`category`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 생성된 룰';


-- =====================================================
-- 4. 룰 컨텐츠 테이블
-- 룰 검증/시나리오/테스트 케이스 컨텐츠
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_rule_contents` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_id` VARCHAR(100) NOT NULL COMMENT '관련 룰 ID',
    `content_type` ENUM('verification', 'scenario', 'test_case', 'example') NOT NULL COMMENT '컨텐츠 유형',
    `title` VARCHAR(255) NOT NULL COMMENT '컨텐츠 제목',
    `content` LONGTEXT NOT NULL COMMENT 'JSON: 컨텐츠 데이터',
    `metadata` LONGTEXT COMMENT 'JSON: 추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rule_id` (`rule_id`),
    KEY `idx_content_type` (`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 룰 컨텐츠';


-- =====================================================
-- 5. 온톨로지 데이터 테이블
-- OIW 모델 기반 온톨로지 노드
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_ontology_data` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `node_id` VARCHAR(100) NOT NULL COMMENT '온톨로지 노드 ID',
    `analysis_id` VARCHAR(100) DEFAULT NULL COMMENT '관련 분석 ID',
    `node_class` VARCHAR(100) NOT NULL COMMENT '노드 클래스 (mk:UnitLearningContext 등)',
    `stage` ENUM('Will', 'Intent', 'Context', 'Interpretation', 'Decision', 'Execution') NOT NULL COMMENT 'OIW 단계',
    `parent_id` VARCHAR(100) DEFAULT NULL COMMENT '부모 노드 ID',
    `properties` LONGTEXT COMMENT 'JSON: 노드 속성',
    `metadata` LONGTEXT COMMENT 'JSON: 메타데이터 (intent, identity, purpose, context)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_node_id` (`node_id`),
    KEY `idx_analysis_id` (`analysis_id`),
    KEY `idx_node_class` (`node_class`),
    KEY `idx_stage` (`stage`),
    KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 온톨로지 데이터';


-- =====================================================
-- 6. 학생 컨텍스트 테이블
-- 학생별 학습 상태 및 컨텍스트
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_student_contexts` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `current_unit` VARCHAR(100) DEFAULT NULL COMMENT '현재 학습 단원',
    `current_concept` VARCHAR(100) DEFAULT NULL COMMENT '현재 학습 개념',
    `understanding_level` ENUM('very_low', 'low', 'medium', 'high', 'very_high') DEFAULT 'medium' COMMENT '이해도 레벨',
    `concepts_learned` LONGTEXT COMMENT 'JSON: 학습한 개념 목록',
    `concepts_struggling` LONGTEXT COMMENT 'JSON: 어려워하는 개념 목록',
    `learning_style` VARCHAR(50) DEFAULT NULL COMMENT '학습 스타일',
    `preferred_explanation` VARCHAR(50) DEFAULT NULL COMMENT '선호하는 설명 방식',
    `context_data` LONGTEXT COMMENT 'JSON: 추가 컨텍스트 데이터',
    `session_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '세션 횟수',
    `total_interactions` INT(10) UNSIGNED DEFAULT 0 COMMENT '총 상호작용 횟수',
    `last_activity_at` DATETIME DEFAULT NULL COMMENT '마지막 활동 시간',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_student_id` (`student_id`),
    KEY `idx_current_unit` (`current_unit`),
    KEY `idx_understanding_level` (`understanding_level`),
    KEY `idx_last_activity_at` (`last_activity_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 학생 컨텍스트';


-- =====================================================
-- 7. 페르소나 테이블
-- 12가지 학습자 페르소나 정의
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_personas` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `persona_id` VARCHAR(50) NOT NULL COMMENT '페르소나 ID (P001-P012)',
    `name` VARCHAR(100) NOT NULL COMMENT '페르소나 이름 (예: 막힘-회피형)',
    `name_en` VARCHAR(100) DEFAULT NULL COMMENT '영문 이름 (예: Avoider)',
    `description` TEXT NOT NULL COMMENT '페르소나 설명',
    `situation` TEXT COMMENT '상황 설명',
    `behavior` TEXT COMMENT '행동 패턴',
    `hidden_cause` TEXT COMMENT '숨은 원인',
    `intervention_strategy` LONGTEXT COMMENT 'JSON: 개입 전략',
    `trigger_patterns` LONGTEXT COMMENT 'JSON: 트리거 패턴',
    `recommended_interventions` LONGTEXT COMMENT 'JSON: 추천 개입 활동 ID 목록',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `display_order` INT(3) DEFAULT 0 COMMENT '표시 순서',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_persona_id` (`persona_id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 학습자 페르소나';


-- =====================================================
-- 8. 학생-페르소나 매칭 테이블
-- 학생에게 할당된 페르소나
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_student_personas` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `persona_id` VARCHAR(50) NOT NULL COMMENT '페르소나 ID',
    `match_score` DECIMAL(5,4) DEFAULT 0.0000 COMMENT '매칭 점수 (0.0000-1.0000)',
    `confidence` DECIMAL(3,2) DEFAULT 0.50 COMMENT '신뢰도',
    `interaction_patterns` LONGTEXT COMMENT 'JSON: 상호작용 패턴 데이터',
    `is_current` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '현재 적용 중 여부',
    `matched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '매칭 시간',
    `expires_at` DATETIME DEFAULT NULL COMMENT '만료 시간',
    PRIMARY KEY (`id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_persona_id` (`persona_id`),
    KEY `idx_is_current` (`is_current`),
    KEY `idx_matched_at` (`matched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 학생-페르소나 매칭';


-- =====================================================
-- 9. 페르소나 스위칭 기록 테이블
-- 페르소나 변경 이력
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_persona_switches` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `from_persona_id` VARCHAR(50) DEFAULT NULL COMMENT '이전 페르소나 ID',
    `to_persona_id` VARCHAR(50) NOT NULL COMMENT '새 페르소나 ID',
    `switch_reason` TEXT COMMENT '스위칭 이유',
    `trigger_interaction_id` VARCHAR(100) DEFAULT NULL COMMENT '트리거된 상호작용 ID',
    `confidence_change` DECIMAL(4,3) DEFAULT NULL COMMENT '신뢰도 변화량',
    `context_snapshot` LONGTEXT COMMENT 'JSON: 스위칭 시점 컨텍스트',
    `switched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_from_persona_id` (`from_persona_id`),
    KEY `idx_to_persona_id` (`to_persona_id`),
    KEY `idx_switched_at` (`switched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 페르소나 스위칭 기록';


-- =====================================================
-- 10. 개입 활동 테이블
-- AlphaTutor42 개입 시스템 (42가지 활동)
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_intervention_activities` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_id` VARCHAR(50) NOT NULL COMMENT '개입 활동 ID (INT_1_1 ~ INT_7_6)',
    `category` ENUM('pause_wait', 'repeat_rephrase', 'alternative_explanation', 'emphasis_alerting', 'questioning_probing', 'immediate_intervention', 'emotional_regulation') NOT NULL COMMENT '카테고리',
    `category_order` INT(2) NOT NULL COMMENT '카테고리 순서 (1-7)',
    `activity_order` INT(2) NOT NULL COMMENT '카테고리 내 순서',
    `name` VARCHAR(100) NOT NULL COMMENT '활동명',
    `description` TEXT NOT NULL COMMENT '활동 설명',
    `trigger_signals` LONGTEXT COMMENT 'JSON: 트리거 신호 목록',
    `persona_mapping` LONGTEXT COMMENT 'JSON: 매핑된 페르소나 ID 목록',
    `priority` INT(2) DEFAULT 2 COMMENT '우선순위 (1=높음, 3=낮음)',
    `duration` VARCHAR(50) DEFAULT NULL COMMENT '예상 소요 시간',
    `method` VARCHAR(50) DEFAULT NULL COMMENT '실행 방식',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `execution_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '실행 횟수',
    `success_rate` DECIMAL(5,4) DEFAULT NULL COMMENT '성공률',
    `metadata` LONGTEXT COMMENT 'JSON: 추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_activity_id` (`activity_id`),
    KEY `idx_category` (`category`),
    KEY `idx_category_order` (`category_order`, `activity_order`),
    KEY `idx_priority` (`priority`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 개입 활동 (42가지)';


-- =====================================================
-- 11. 개입 활동 실행 기록 테이블
-- 개입 활동 실행 이력
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_intervention_executions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_id` VARCHAR(50) NOT NULL COMMENT '개입 활동 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `interaction_id` VARCHAR(100) DEFAULT NULL COMMENT '관련 상호작용 ID',
    `persona_id` VARCHAR(50) DEFAULT NULL COMMENT '적용된 페르소나 ID',
    `trigger_signal` VARCHAR(255) DEFAULT NULL COMMENT '트리거 신호',
    `context_snapshot` LONGTEXT COMMENT 'JSON: 실행 시점 컨텍스트',
    `response_type` ENUM('positive', 'neutral', 'negative', 'no_response') DEFAULT 'neutral' COMMENT '학생 반응 유형',
    `effectiveness` DECIMAL(3,2) DEFAULT NULL COMMENT '효과성 점수 (0.00-1.00)',
    `notes` TEXT COMMENT '메모/노트',
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_id` (`activity_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_interaction_id` (`interaction_id`),
    KEY `idx_persona_id` (`persona_id`),
    KEY `idx_response_type` (`response_type`),
    KEY `idx_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 개입 활동 실행 기록';


-- =====================================================
-- 12. 필기 패턴 테이블
-- 필기 기반 AI 튜터용 패턴 데이터
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_writing_patterns` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `pattern_id` VARCHAR(50) NOT NULL COMMENT '패턴 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    `pattern_type` ENUM('pause', 'erase', 'overwrite', 'progress', 'error', 'gesture') NOT NULL COMMENT '패턴 유형',
    `duration` DECIMAL(10,3) DEFAULT NULL COMMENT '지속 시간 (초)',
    `count` INT(5) DEFAULT 1 COMMENT '발생 횟수',
    `confidence` DECIMAL(3,2) DEFAULT 0.50 COMMENT '패턴 인식 신뢰도',
    `inferred_state` VARCHAR(100) DEFAULT NULL COMMENT '유추된 인지 상태',
    `stroke_data` LONGTEXT COMMENT 'JSON: 스트로크 데이터',
    `position_data` LONGTEXT COMMENT 'JSON: 위치 데이터',
    `intervention_triggered` VARCHAR(50) DEFAULT NULL COMMENT '트리거된 개입 활동 ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pattern_id` (`pattern_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_pattern_type` (`pattern_type`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 필기 패턴';


-- =====================================================
-- 13. 비침습적 질문 테이블
-- 비침습적 질문 기록
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_non_intrusive_questions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id` VARCHAR(100) NOT NULL COMMENT '질문 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    `question_type` ENUM('margin_whisper', 'breathing_bar', 'corner_emoji', 'inline_question', 'gesture_response') NOT NULL COMMENT '질문 방식',
    `content` TEXT NOT NULL COMMENT '질문 내용',
    `inferred_state` VARCHAR(100) DEFAULT NULL COMMENT '유추된 인지 상태',
    `response_type` ENUM('check', 'cross', 'question', 'arrow', 'emoji', 'tap', 'no_response') DEFAULT 'no_response' COMMENT '학생 응답 유형',
    `response_value` VARCHAR(100) DEFAULT NULL COMMENT '응답 값',
    `response_time` INT(10) DEFAULT NULL COMMENT '응답까지 걸린 시간 (ms)',
    `was_escalated` TINYINT(1) DEFAULT 0 COMMENT '강화되었는지 여부',
    `escalation_level` INT(2) DEFAULT 0 COMMENT '강화 레벨',
    `displayed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `responded_at` DATETIME DEFAULT NULL COMMENT '응답 시간',
    `hidden_at` DATETIME DEFAULT NULL COMMENT '숨김 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_question_id` (`question_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_question_type` (`question_type`),
    KEY `idx_response_type` (`response_type`),
    KEY `idx_displayed_at` (`displayed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 비침습적 질문';


-- =====================================================
-- 14. 세션 테이블
-- 학습 세션 관리
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_sessions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `content_id` BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '컨텐츠 ID',
    `analysis_id` VARCHAR(100) DEFAULT NULL COMMENT '관련 분석 ID',
    `unit_code` VARCHAR(50) DEFAULT NULL COMMENT '단원 코드',
    `current_step` INT(10) UNSIGNED DEFAULT 1 COMMENT '현재 단계',
    `progress_percent` INT(10) UNSIGNED DEFAULT 0 COMMENT '진행률',
    `detected_persona` VARCHAR(50) DEFAULT NULL COMMENT '감지된 페르소나 ID',
    `start_persona_id` VARCHAR(50) DEFAULT NULL COMMENT '시작 시 페르소나 ID',
    `end_persona_id` VARCHAR(50) DEFAULT NULL COMMENT '종료 시 페르소나 ID',
    `interaction_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '상호작용 횟수',
    `intervention_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '개입 횟수',
    `avg_understanding` DECIMAL(3,2) DEFAULT NULL COMMENT '평균 이해도',
    `duration_seconds` INT(10) UNSIGNED DEFAULT NULL COMMENT '세션 지속 시간 (초)',
    `status` ENUM('active', 'paused', 'completed', 'abandoned') DEFAULT 'active' COMMENT '세션 상태',
    `context_data` LONGTEXT COMMENT 'JSON: 세션 컨텍스트',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended_at` DATETIME DEFAULT NULL COMMENT '종료 시간',
    `created_at` INT(10) UNSIGNED DEFAULT NULL COMMENT '생성 시간 (Unix timestamp)',
    `updated_at` INT(10) UNSIGNED DEFAULT NULL COMMENT '수정 시간 (Unix timestamp)',
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_analysis_id` (`analysis_id`),
    KEY `idx_status` (`status`),
    KEY `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 학습 세션';


-- =====================================================
-- 15. 상호작용 로그 테이블
-- 모든 상호작용 이벤트의 시계열 기록
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_interaction_logs` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '세션 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `event_type` VARCHAR(50) NOT NULL COMMENT '이벤트 타입',
    `event_data` LONGTEXT COMMENT 'JSON: 이벤트 데이터',
    `timestamp` INT(10) UNSIGNED NOT NULL COMMENT '시간 (Unix timestamp)',
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 튜터 상호작용 로그';


-- =====================================================
-- 기본 데이터 삽입: 12가지 페르소나
-- =====================================================
INSERT INTO `mdl_alt42_personas` (`persona_id`, `name`, `name_en`, `description`, `situation`, `behavior`, `hidden_cause`, `display_order`) VALUES
('P001', '막힘-회피형', 'Avoider', '문제를 읽다 막히면 바로 포기하는 유형', '문제 읽다 막히면 바로 연필 내려놓음', '"몰라요…" / 문제 안 읽고 다음으로 넘김', '실패 불안 + 작업기억 과부하', 1),
('P002', '확인요구형', 'Checker', '계속 확인을 요청하는 유형', '맞는지 계속 물어봄', '"이렇게 하면 되죠?" 반복', '낮은 학습 효능감', 2),
('P003', '감정출렁형', 'Emotion-driven', '감정 변화가 큰 유형', '문제 한 개 틀리면 전체 기분 하락', '표정 다운·속도 느려짐', '정서 조절력 부족', 3),
('P004', '빠른데 허술형', 'Speed-but-Miss', '빠르지만 실수가 많은 유형', '빨리 끝냈는데 실수 많음', '계산 실수·단위 누락', '과도한 자동화 + 검증 회로 부재', 4),
('P005', '집중 튐형', 'Attention Hopper', '집중력이 자주 흐트러지는 유형', '문제 읽다가 다른 줄로 눈이 튐', '시선 불안정, 방향성 없는 질문', '주의 지속시간 짧음', 5),
('P006', '패턴추론형', 'Pattern Seeker', '전체 구조를 먼저 파악하려는 유형', '전체 구조 먼저 찾으려 함', '"여기서 의도는…" / 원리 탐색 선호', '고차원적 처리 선호', 6),
('P007', '최대한 쉬운길 찾기형', 'Efficiency Maximizer', '효율적인 방법을 선호하는 유형', '최소 노력으로 최대 결과 원함', '지름길, 공략, 노하우 질문', '합리적 학습자', 7),
('P008', '불안과몰입형', 'Over-focusing Worrier', '쉬운 문제에도 오래 붙잡는 유형', '쉬운 문제에도 오래 붙잡힘', '확인·재확인 반복', '실수에 대한 과도한 민감성', 8),
('P009', '추상-언어 약함형', 'Concrete Learner', '예시를 통해 학습하는 유형', '설명은 이해 못하지만 예시는 잘 따라옴', '"예시 하나만 더요"', '추상처리능력 낮음', 9),
('P010', '상호작용 의존형', 'Interactive Dependent', '혼자 풀면 멈추는 유형', '혼자 풀면 갑자기 정지', '옆에서 질문해주면 폭발적으로 진행', '외부 자극 필요', 10),
('P011', '무기력·저동기형', 'Low Drive', '에너지가 없는 유형', '시작부터 에너지 없음', '앉아 있지만 진도 안 나감', '정서적 소진 / 성공경험 부족', 11),
('P012', '메타인지 고수형', 'Meta-high', '자기 조절력이 높은 유형', '스스로 오류검출·전략수립', '"이건 구조가 이래서…"', '높은 자기조절력', 12);


-- =====================================================
-- 기본 데이터 삽입: 42가지 개입 활동
-- =====================================================

-- 1. 멈춤/대기 (Pause & Wait) — 5개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`, `duration`) VALUES
('INT_1_1', 'pause_wait', 1, 1, '인지 부하 대기', '설명을 멈추고 3~5초 침묵, 처리 시간 확보', '["눈 깜빡임 증가", "시선 고정", "멍한 표정"]', '["P001", "P005", "P009"]', 1, '3-5초'),
('INT_1_2', 'pause_wait', 1, 2, '필기 동기화 대기', '학생이 적을 때까지 말을 멈추고 기다림', '["고개 숙임", "펜 움직임", "화면/종이 응시"]', '["P002", "P008"]', 2, '필기 완료까지'),
('INT_1_3', 'pause_wait', 1, 3, '사고 여백 제공', '"한번 생각해봐" 후 10초 이상 기다림', '["질문 직후", "어려운 개념 제시 직후"]', '["P001", "P006", "P012"]', 1, '10초 이상'),
('INT_1_4', 'pause_wait', 1, 4, '감정 진정 대기', '좌절/혼란 시 다그치지 않고 잠시 쉼', '["한숨", "펜 내려놓음", "고개 떨굼"]', '["P003", "P011"]', 1, '5-10초'),
('INT_1_5', 'pause_wait', 1, 5, '자기 수정 대기', '학생이 스스로 오류 인식할 시간 제공', '["말하다 멈춤", "아 잠깐...", "표정 변화"]', '["P004", "P012"]', 2, '5-10초');

-- 2. 재설명 (Repeat & Rephrase) — 6개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`) VALUES
('INT_2_1', 'repeat_rephrase', 2, 1, '동일 반복', '같은 내용을 천천히, 또박또박 다시', '["네?", "다시요?", "되묻기"]', '["P002", "P010"]', 2),
('INT_2_2', 'repeat_rephrase', 2, 2, '강조점 이동 반복', '같은 문장에서 강조 위치를 바꿔 반복', '["부분적 이해 표현", "앞부분은 알겠는데..."]', '["P005", "P009"]', 2),
('INT_2_3', 'repeat_rephrase', 2, 3, '단계 분해', '한 덩어리를 2~3개 미니 스텝으로 쪼갬', '["복합 과정에서 중간에 막힘"]', '["P001", "P005", "P009"]', 1),
('INT_2_4', 'repeat_rephrase', 2, 4, '역순 재구성', '결론 → 중간 → 시작 순으로 거꾸로 설명', '["왜 이렇게 되는지 모르겠어요"]', '["P006", "P012"]', 3),
('INT_2_5', 'repeat_rephrase', 2, 5, '연결고리 명시', '"A이기 때문에 B, B이기 때문에 C" 인과 강조', '["단계는 따라오나 연결을 못 느낌"]', '["P006", "P007"]', 2),
('INT_2_6', 'repeat_rephrase', 2, 6, '요약 압축', '긴 설명을 한 문장으로 핵심만 재진술', '["정보 과다로 혼란", "그래서 뭐가 중요한 거예요?"]', '["P004", "P007"]', 2);

-- 3. 전환 설명 (Alternative Explanation) — 7개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`) VALUES
('INT_3_1', 'alternative_explanation', 3, 1, '일상 비유', '추상 개념을 일상 경험에 빗대어 설명', '["수학 용어에서 막힘", "개념 자체 이해 불가"]', '["P009", "P011"]', 1),
('INT_3_2', 'alternative_explanation', 3, 2, '시각화 전환', '말 → 그림/도표/그래프로 표현 방식 변경', '["언어적 설명에 반응 없음", "청각 처리 한계"]', '["P005", "P009"]', 1),
('INT_3_3', 'alternative_explanation', 3, 3, '구체적 수 대입', '문자식을 특정 숫자로 바꿔 계산 흐름 시연', '["변수/문자에 대한 두려움", "x가 뭔데요"]', '["P001", "P009"]', 1),
('INT_3_4', 'alternative_explanation', 3, 4, '극단적 예시', '0, 1, 무한대 등 극단값으로 직관 형성', '["일반적 설명으로 감 못 잡음"]', '["P006", "P012"]', 3),
('INT_3_5', 'alternative_explanation', 3, 5, '반례 제시', '"만약 이렇게 하면 왜 안 되는지 볼까?"', '["잘못된 방법을 확신함", "오개념 고착"]', '["P004", "P008"]', 2),
('INT_3_6', 'alternative_explanation', 3, 6, '학생 언어 번역', '학생이 쓰는 표현/용어로 재설명', '["교과서 용어에 거부감", "자기 말로 표현 시도"]', '["P009", "P011"]', 1),
('INT_3_7', 'alternative_explanation', 3, 7, '신체/동작 비유', '손동작, 움직임으로 개념 체화', '["정적 설명에 집중 못함", "운동감각형 학습자"]', '["P005", "P010"]', 2);

-- 4. 강조/주의환기 (Emphasis & Alerting) — 5개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`) VALUES
('INT_4_1', 'emphasis_alerting', 4, 1, '핵심 반복 강조', '"이게 제일 중요해" 동일 포인트 2~3회', '["핵심을 지나치고 지엽적인 것에 집중"]', '["P004", "P005"]', 2),
('INT_4_2', 'emphasis_alerting', 4, 2, '대비 강조', '"A가 아니라 B야" 오개념과 정개념 병렬', '["흔한 오류 패턴 감지", "헷갈리는 개념"]', '["P004", "P008"]', 2),
('INT_4_3', 'emphasis_alerting', 4, 3, '톤/속도 변화', '갑자기 천천히, 또는 높은 톤으로 전환', '["주의력 저하", "멍한 상태", "습관적 고개 끄덕임"]', '["P005", "P011"]', 1),
('INT_4_4', 'emphasis_alerting', 4, 4, '시각적 마킹', '밑줄, 동그라미, 색깔로 주의 집중 유도', '["시각 자료에서 핵심 못 찾음"]', '["P005", "P009"]', 2),
('INT_4_5', 'emphasis_alerting', 4, 5, '예고 신호', '"지금부터 말하는 거 시험에 나와" 경고', '["전반적 이완 상태", "중요도 인식 부족"]', '["P007", "P011"]', 3);

-- 5. 질문/탐색 (Questioning & Probing) — 7개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`) VALUES
('INT_5_1', 'questioning_probing', 5, 1, '확인 질문', '"여기까지 이해됐어?" 단순 예/아니오', '["설명 구간 완료 시점", "표정 불확실"]', '["P002", "P010"]', 2),
('INT_5_2', 'questioning_probing', 5, 2, '예측 질문', '"다음엔 뭘 해야 할 것 같아?"', '["수동적 청취 지속", "능동 사고 유도 필요"]', '["P010", "P011"]', 2),
('INT_5_3', 'questioning_probing', 5, 3, '역질문', '"왜 그렇게 생각했어?" 사고과정 탐색', '["답은 맞으나 과정 불명확", "찍기 의심"]', '["P004", "P012"]', 2),
('INT_5_4', 'questioning_probing', 5, 4, '선택지 질문', '"A일까 B일까?" 이지선다로 부담 경감', '["열린 질문에 대답 못함", "막막해함"]', '["P001", "P002", "P011"]', 1),
('INT_5_5', 'questioning_probing', 5, 5, '힌트 질문', '"만약 여기가 0이면?" 방향 유도', '["시작점을 못 잡음", "백지 상태"]', '["P001", "P011"]', 1),
('INT_5_6', 'questioning_probing', 5, 6, '연결 질문', '"이거 저번에 한 거랑 뭐가 비슷해?"', '["새 개념에 고립감", "기존 지식 활성화 필요"]', '["P006", "P009"]', 2),
('INT_5_7', 'questioning_probing', 5, 7, '메타인지 질문', '"지금 어디가 헷갈려?" 자기 상태 인식 유도', '["막연한 모르겠어요", "구체화 필요"]', '["P001", "P011", "P012"]', 1);

-- 6. 즉시 개입 (Immediate Intervention) — 6개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`) VALUES
('INT_6_1', 'immediate_intervention', 6, 1, '즉시 교정', '오류 순간 "잠깐!" 바로 멈추고 수정', '["계산 실수", "부호 오류", "공식 오적용"]', '["P004", "P008"]', 1),
('INT_6_2', 'immediate_intervention', 6, 2, '부분 인정 확장', '"거기까진 맞아, 근데..." 긍정 후 보완', '["방향은 맞으나 불완전한 답변"]', '["P002", "P003"]', 2),
('INT_6_3', 'immediate_intervention', 6, 3, '함께 완성', '막힌 부분부터 같이 써가며 이끌기', '["말/글이 중간에 끊김", "다음 진행 불가"]', '["P001", "P010"]', 1),
('INT_6_4', 'immediate_intervention', 6, 4, '되물어 확인', '"네 말은 ~라는 거지?" 재구성 확인', '["답변이 모호하거나 문장이 불완전"]', '["P002", "P009"]', 2),
('INT_6_5', 'immediate_intervention', 6, 5, '오개념 즉시 분리', '"그건 다른 거야" 혼동 요소 명확 분리', '["두 개념 혼합 사용", "용어 혼란"]', '["P004", "P008"]', 1),
('INT_6_6', 'immediate_intervention', 6, 6, '실시간 시범', '학생 시도 옆에서 바로 올바른 과정 시연', '["같은 실수 반복", "말로 교정 안 됨"]', '["P004", "P010"]', 1);

-- 7. 정서 조절 (Emotional Regulation) — 6개
INSERT INTO `mdl_alt42_intervention_activities` (`activity_id`, `category`, `category_order`, `activity_order`, `name`, `description`, `trigger_signals`, `persona_mapping`, `priority`) VALUES
('INT_7_1', 'emotional_regulation', 7, 1, '노력 인정', '"열심히 생각했네" 과정 자체 칭찬', '["오답이지만 시도함", "좌절 직전"]', '["P003", "P011"]', 1),
('INT_7_2', 'emotional_regulation', 7, 2, '정상화', '"이거 다 어려워해" 혼자가 아님 전달', '["자책", "나만 못해요 표현"]', '["P003", "P011"]', 1),
('INT_7_3', 'emotional_regulation', 7, 3, '난이도 조정 예고', '"이건 어려운 거야, 천천히 가자"', '["불안 상승", "조급함", "빨리 끝내려 함"]', '["P003", "P008"]', 1),
('INT_7_4', 'emotional_regulation', 7, 4, '작은 성공 만들기', '일부러 쉬운 질문으로 성취감 제공', '["연속 오답", "자신감 저하"]', '["P003", "P011"]', 1),
('INT_7_5', 'emotional_regulation', 7, 5, '유머/가벼운 전환', '잠깐 긴장 풀어주는 가벼운 말', '["과도한 긴장", "어깨 경직", "호흡 얕음"]', '["P003", "P008"]', 2),
('INT_7_6', 'emotional_regulation', 7, 6, '선택권 부여', '"이거 먼저 할까, 저거 먼저 할까?"', '["통제감 상실", "무기력 신호"]', '["P010", "P011"]', 1);


-- =====================================================
-- 15. 문항별 페르소나 테이블 (신규)
-- 문항 이미지 분석 및 맞춤형 페르소나 저장
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_question_personas` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id` BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '문제 ID (mdl_question.id)',
    `wboard_id` VARCHAR(255) DEFAULT NULL COMMENT '화이트보드 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `question_analysis` LONGTEXT COMMENT 'JSON: 문항 분석 결과 (topic, problems, cognitive_load)',
    `persona_data` LONGTEXT COMMENT 'JSON: 문항별 페르소나 매칭 데이터',
    `mastery_recommendations` LONGTEXT COMMENT 'JSON: 장기기억 집중숙련 추천 (3가지)',
    `mastery_progress` LONGTEXT COMMENT 'JSON: 집중숙련 진행 상태',
    `total_attempts` INT(5) UNSIGNED DEFAULT 0 COMMENT '총 시도 횟수',
    `long_term_reached` TINYINT(1) DEFAULT 0 COMMENT '장기기억 도달 여부',
    `long_term_reached_at` DATETIME DEFAULT NULL COMMENT '장기기억 도달 시점',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_question_id` (`question_id`),
    KEY `idx_wboard_id` (`wboard_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_long_term` (`long_term_reached`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='문항별 맞춤 페르소나 및 집중숙련 데이터';


-- =====================================================
-- 16. 집중숙련 기록 테이블 (신규)
-- 반복필기 활동 기록
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42_mastery_records` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_persona_id` BIGINT(10) UNSIGNED NOT NULL COMMENT 'question_personas.id',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `recommendation_id` INT(3) UNSIGNED NOT NULL COMMENT '추천 ID (1-3)',
    `concept` VARCHAR(255) NOT NULL COMMENT '집중숙련 개념',
    `practice_content` TEXT COMMENT '반복필기 내용',
    `repetition_target` INT(3) UNSIGNED DEFAULT 3 COMMENT '목표 반복 횟수',
    `repetition_completed` INT(3) UNSIGNED DEFAULT 0 COMMENT '완료 반복 횟수',
    `is_completed` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
    `completed_at` DATETIME DEFAULT NULL COMMENT '완료 시점',
    `stroke_data` LONGTEXT COMMENT 'JSON: 필기 스트로크 데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_qp_id` (`question_persona_id`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_is_completed` (`is_completed`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='집중숙련 반복필기 기록';
