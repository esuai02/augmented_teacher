-- =====================================================
-- AI Tutor 상호작용 시스템 데이터베이스 스키마
-- MySQL 5.7 호환
-- 테이블 접두사: mdl_alt42i_ (i = interaction)
-- 
-- URL 파라미터 기반: contentsid, contentstype, studentid
-- 온톨로지/룰 확장 가능한 설계
-- =====================================================

-- =====================================================
-- 1. 학습 세션 테이블 (메인)
-- 학생의 학습 세션 관리
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_sessions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 고유 ID (SESSION_timestamp_random)',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user.id)',
    `contents_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID (URL 파라미터)',
    `contents_type` VARCHAR(50) NOT NULL COMMENT '컨텐츠 타입 (topic, question, unit 등)',
    `whiteboard_id` VARCHAR(200) DEFAULT NULL COMMENT '화이트보드 ID',
    `analysis_id` VARCHAR(100) DEFAULT NULL COMMENT '관련 분석 ID (mdl_alt42_analysis_results)',
    `persona_id` VARCHAR(50) DEFAULT NULL COMMENT '현재 적용된 페르소나 ID',
    `current_step` INT(2) UNSIGNED DEFAULT 1 COMMENT '현재 풀이 단계 (1-5)',
    `step_source` ENUM('auto', 'manual') DEFAULT 'auto' COMMENT '단계 결정 소스',
    `emotion_type` VARCHAR(20) DEFAULT 'neutral' COMMENT '현재 감정 상태',
    `emotion_source` ENUM('auto', 'manual') DEFAULT 'auto' COMMENT '감정 결정 소스',
    `session_status` ENUM('active', 'paused', 'completed', 'abandoned') DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended_at` DATETIME DEFAULT NULL,
    `duration_seconds` INT(10) UNSIGNED DEFAULT 0 COMMENT '총 학습 시간(초)',
    `metadata` LONGTEXT COMMENT 'JSON: 추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_contents` (`contents_id`, `contents_type`),
    KEY `idx_status` (`session_status`),
    KEY `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학습 세션 관리';


-- =====================================================
-- 2. 상호작용 로그 테이블
-- 모든 상호작용 이벤트 기록 (시계열 데이터)
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_interaction_logs` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    `contents_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `contents_type` VARCHAR(50) NOT NULL COMMENT '컨텐츠 타입',
    `event_type` ENUM(
        'step_change',       -- 단계 변경
        'emotion_change',    -- 감정 변경
        'gesture_input',     -- 제스처 입력
        'persona_change',    -- 페르소나 변경
        'feedback_shown',    -- 피드백 표시
        'memory_activity',   -- 장기기억 활동
        'whiteboard_action', -- 화이트보드 액션
        'rule_triggered',    -- 룰 트리거
        'ontology_update',   -- 온톨로지 업데이트
        'ai_response',       -- AI 응답
        'user_input',        -- 사용자 입력
        'system_event'       -- 시스템 이벤트
    ) NOT NULL COMMENT '이벤트 타입',
    `event_data` LONGTEXT NOT NULL COMMENT 'JSON: 이벤트 상세 데이터',
    `previous_state` LONGTEXT COMMENT 'JSON: 이전 상태',
    `current_state` LONGTEXT COMMENT 'JSON: 현재 상태',
    `triggered_rules` TEXT COMMENT 'JSON: 트리거된 룰 ID 목록',
    `triggered_interventions` TEXT COMMENT 'JSON: 트리거된 개입 활동 ID 목록',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL COMMENT '밀리초 타임스탬프',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_timestamp` (`timestamp_ms`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상호작용 이벤트 로그';


-- =====================================================
-- 3. 단계별 진행 상태 테이블
-- 각 단계의 상세 진행 상태 추적
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_step_progress` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `step_number` INT(2) UNSIGNED NOT NULL COMMENT '단계 번호 (1-5)',
    `step_label` VARCHAR(50) NOT NULL COMMENT '단계 라벨',
    `step_status` ENUM('pending', 'current', 'completed', 'skipped') DEFAULT 'pending',
    `entered_at` DATETIME DEFAULT NULL COMMENT '단계 진입 시각',
    `completed_at` DATETIME DEFAULT NULL COMMENT '단계 완료 시각',
    `duration_seconds` INT(10) UNSIGNED DEFAULT 0 COMMENT '단계 소요 시간',
    `attempt_count` INT(5) UNSIGNED DEFAULT 0 COMMENT '시도 횟수',
    `error_count` INT(5) UNSIGNED DEFAULT 0 COMMENT '오류 횟수',
    `hint_count` INT(5) UNSIGNED DEFAULT 0 COMMENT '힌트 요청 횟수',
    `gesture_summary` TEXT COMMENT 'JSON: 제스처 요약',
    `emotion_history` TEXT COMMENT 'JSON: 감정 변화 이력',
    `notes` TEXT COMMENT '추가 노트',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_step` (`session_id`, `step_number`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_step_status` (`step_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='단계별 진행 상태';


-- =====================================================
-- 4. 감정 상태 히스토리 테이블
-- 감정 변화 추적
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_emotion_history` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `emotion_type` VARCHAR(20) NOT NULL COMMENT 'confident, neutral, confused, stuck, anxious',
    `emotion_source` ENUM('auto', 'manual') NOT NULL DEFAULT 'auto',
    `confidence_score` DECIMAL(5,4) DEFAULT NULL COMMENT 'AI 감지 신뢰도 (0-1)',
    `trigger_event` VARCHAR(100) DEFAULT NULL COMMENT '감정 변화 트리거 이벤트',
    `current_step` INT(2) UNSIGNED DEFAULT NULL COMMENT '감정 변화 시 단계',
    `context_data` TEXT COMMENT 'JSON: 맥락 데이터',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_emotion_type` (`emotion_type`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='감정 상태 히스토리';


-- =====================================================
-- 5. 제스처 인식 기록 테이블
-- 펜 제스처 입력 및 인식 결과
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_gestures` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `gesture_type` VARCHAR(30) NOT NULL COMMENT 'check, x, question, circle, arrow, unknown',
    `gesture_symbol` VARCHAR(10) DEFAULT NULL COMMENT '✓, ✗, ?, ○, →',
    `gesture_meaning` VARCHAR(50) DEFAULT NULL COMMENT '이해했어, 아니야, 모르겠어 등',
    `recognition_confidence` DECIMAL(5,4) DEFAULT NULL COMMENT '인식 신뢰도',
    `path_data` TEXT COMMENT 'JSON: 제스처 경로 좌표',
    `action_taken` VARCHAR(100) DEFAULT NULL COMMENT '제스처로 인한 액션',
    `current_step` INT(2) UNSIGNED DEFAULT NULL,
    `feedback_shown` TEXT DEFAULT NULL COMMENT '표시된 피드백',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_gesture_type` (`gesture_type`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='제스처 인식 기록';


-- =====================================================
-- 6. 페르소나 변화 기록 테이블
-- 페르소나 선택/전환 이력
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_persona_history` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `previous_persona_id` VARCHAR(50) DEFAULT NULL,
    `new_persona_id` VARCHAR(50) NOT NULL COMMENT '새로운 페르소나 ID',
    `persona_name` VARCHAR(100) DEFAULT NULL COMMENT '페르소나 이름',
    `persona_icon` VARCHAR(20) DEFAULT NULL COMMENT '페르소나 아이콘',
    `selection_source` ENUM('ai_diagnosis', 'student_manual', 'system_switch') NOT NULL,
    `positive_persona_id` VARCHAR(50) DEFAULT NULL COMMENT '긍정 전환 페르소나',
    `guidance_message` TEXT DEFAULT NULL COMMENT '유도 문구',
    `switch_reason` TEXT COMMENT '전환 이유',
    `trigger_signals` TEXT COMMENT 'JSON: 트리거 신호들',
    `current_step` INT(2) UNSIGNED DEFAULT NULL,
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_persona` (`new_persona_id`),
    KEY `idx_source` (`selection_source`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='페르소나 변화 기록';


-- =====================================================
-- 7. 피드백 기록 테이블
-- AI 피드백 표시 이력
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_feedbacks` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `feedback_type` ENUM(
        'step_guidance',     -- 단계 안내
        'emotion_response',  -- 감정 반응
        'gesture_response',  -- 제스처 반응
        'persona_guidance',  -- 페르소나 유도
        'error_correction',  -- 오류 교정
        'encouragement',     -- 격려
        'hint',              -- 힌트
        'explanation',       -- 설명
        'memory_complete',   -- 장기기억 완료
        'system'             -- 시스템 메시지
    ) NOT NULL,
    `feedback_text` TEXT NOT NULL COMMENT '피드백 텍스트',
    `emotion_icon` VARCHAR(10) DEFAULT NULL COMMENT '감정 아이콘 (글머리)',
    `intervention_id` VARCHAR(50) DEFAULT NULL COMMENT '관련 개입 활동 ID',
    `rule_id` VARCHAR(100) DEFAULT NULL COMMENT '트리거된 룰 ID',
    `current_step` INT(2) UNSIGNED DEFAULT NULL,
    `display_duration_ms` INT(10) UNSIGNED DEFAULT 3500 COMMENT '표시 시간(ms)',
    `user_reaction` VARCHAR(50) DEFAULT NULL COMMENT '사용자 반응 (있는 경우)',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_feedback_type` (`feedback_type`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='피드백 기록';


-- =====================================================
-- 8. 장기기억 활동 기록 테이블
-- 5단계 장기기억 활동 추적
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_memory_activities` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `activity_type` ENUM('counter', 'timer', 'repetition') NOT NULL,
    `target_count` INT(5) UNSIGNED DEFAULT NULL COMMENT '목표 횟수',
    `current_count` INT(5) UNSIGNED DEFAULT 0 COMMENT '현재 횟수',
    `target_seconds` INT(10) UNSIGNED DEFAULT NULL COMMENT '목표 시간(초)',
    `elapsed_seconds` INT(10) UNSIGNED DEFAULT 0 COMMENT '경과 시간(초)',
    `is_completed` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
    `completed_at` DATETIME DEFAULT NULL COMMENT '완료 시각',
    `persona_id` VARCHAR(50) DEFAULT NULL COMMENT '관련 페르소나',
    `activity_data` TEXT COMMENT 'JSON: 활동 상세 데이터',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_activity_type` (`activity_type`),
    KEY `idx_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='장기기억 활동 기록';


-- =====================================================
-- 9. 동적 온톨로지 테이블 (확장용)
-- 상호작용 중 생성/확장되는 온톨로지
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_ontology_nodes` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `node_id` VARCHAR(100) NOT NULL COMMENT '노드 고유 ID',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID (NULL이면 글로벌)',
    `student_id` BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '학생 ID (NULL이면 공통)',
    `contents_id` VARCHAR(100) DEFAULT NULL COMMENT '컨텐츠 ID',
    `contents_type` VARCHAR(50) DEFAULT NULL COMMENT '컨텐츠 타입',
    `node_type` VARCHAR(50) NOT NULL COMMENT 'concept, relation, property, instance 등',
    `node_label` VARCHAR(200) NOT NULL COMMENT '노드 라벨/이름',
    `parent_node_id` VARCHAR(100) DEFAULT NULL COMMENT '부모 노드 ID',
    `namespace` VARCHAR(100) DEFAULT 'default' COMMENT '온톨로지 네임스페이스',
    `layer` ENUM('agent_core', 'task_core', 'task_module', 'session', 'dynamic') DEFAULT 'dynamic',
    `properties` LONGTEXT COMMENT 'JSON: 노드 속성들',
    `relations` LONGTEXT COMMENT 'JSON: 관계 정의',
    `semantic_embedding` TEXT COMMENT 'JSON: 의미 임베딩 벡터 (선택적)',
    `confidence_score` DECIMAL(5,4) DEFAULT 1.0 COMMENT '신뢰도 점수',
    `usage_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '사용 횟수',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성 상태',
    `source` ENUM('system', 'ai_generated', 'user_defined', 'inferred') DEFAULT 'system',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_node_id` (`node_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_node_type` (`node_type`),
    KEY `idx_namespace` (`namespace`),
    KEY `idx_layer` (`layer`),
    KEY `idx_parent` (`parent_node_id`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='동적 온톨로지 노드';


-- =====================================================
-- 10. 온톨로지 관계 테이블
-- 노드 간 관계 정의
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_ontology_relations` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `relation_id` VARCHAR(100) NOT NULL COMMENT '관계 고유 ID',
    `source_node_id` VARCHAR(100) NOT NULL COMMENT '소스 노드 ID',
    `target_node_id` VARCHAR(100) NOT NULL COMMENT '타겟 노드 ID',
    `relation_type` VARCHAR(50) NOT NULL COMMENT 'is_a, has_part, requires, leads_to 등',
    `relation_label` VARCHAR(200) DEFAULT NULL COMMENT '관계 라벨',
    `namespace` VARCHAR(100) DEFAULT 'default',
    `weight` DECIMAL(5,4) DEFAULT 1.0 COMMENT '관계 강도 (0-1)',
    `direction` ENUM('unidirectional', 'bidirectional') DEFAULT 'unidirectional',
    `properties` TEXT COMMENT 'JSON: 관계 속성',
    `context_conditions` TEXT COMMENT 'JSON: 관계 적용 조건',
    `is_active` TINYINT(1) DEFAULT 1,
    `source` ENUM('system', 'ai_generated', 'user_defined', 'inferred') DEFAULT 'system',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_relation_id` (`relation_id`),
    KEY `idx_source_node` (`source_node_id`),
    KEY `idx_target_node` (`target_node_id`),
    KEY `idx_relation_type` (`relation_type`),
    KEY `idx_namespace` (`namespace`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='온톨로지 관계';


-- =====================================================
-- 11. 동적 룰 테이블 (확장용)
-- 상호작용 중 생성/학습되는 룰
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_dynamic_rules` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_id` VARCHAR(100) NOT NULL COMMENT '룰 고유 ID',
    `session_id` VARCHAR(100) DEFAULT NULL COMMENT '세션 ID (NULL이면 글로벌)',
    `student_id` BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '학생 ID (NULL이면 공통)',
    `contents_id` VARCHAR(100) DEFAULT NULL,
    `contents_type` VARCHAR(50) DEFAULT NULL,
    `rule_name` VARCHAR(200) NOT NULL COMMENT '룰 이름',
    `rule_category` VARCHAR(50) NOT NULL COMMENT 'U0, U1, U2, U3, U4 또는 커스텀',
    `priority` INT(5) DEFAULT 50 COMMENT '우선순위 (높을수록 우선)',
    `conditions` LONGTEXT NOT NULL COMMENT 'JSON: 룰 조건들',
    `actions` LONGTEXT NOT NULL COMMENT 'JSON: 룰 액션들',
    `else_actions` TEXT COMMENT 'JSON: else 액션들',
    `trigger_signals` TEXT COMMENT 'JSON: 트리거 신호 정의',
    `persona_ids` TEXT COMMENT 'JSON: 적용 페르소나 ID 목록',
    `ontology_refs` TEXT COMMENT 'JSON: 참조 온톨로지 노드들',
    `execution_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '실행 횟수',
    `success_count` INT(10) UNSIGNED DEFAULT 0 COMMENT '성공 횟수',
    `effectiveness_score` DECIMAL(5,4) DEFAULT NULL COMMENT '효과성 점수',
    `is_active` TINYINT(1) DEFAULT 1,
    `source` ENUM('system', 'ai_generated', 'user_defined', 'learned') DEFAULT 'system',
    `parent_rule_id` VARCHAR(100) DEFAULT NULL COMMENT '부모 룰 ID (파생 룰인 경우)',
    `version` INT(5) DEFAULT 1 COMMENT '버전 번호',
    `valid_from` DATETIME DEFAULT NULL COMMENT '유효 시작일',
    `valid_until` DATETIME DEFAULT NULL COMMENT '유효 종료일',
    `metadata` TEXT COMMENT 'JSON: 추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_rule_id_version` (`rule_id`, `version`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_category` (`rule_category`),
    KEY `idx_priority` (`priority`),
    KEY `idx_active` (`is_active`),
    KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='동적 룰';


-- =====================================================
-- 12. 룰 실행 로그 테이블
-- 룰 실행 이력 및 결과 추적
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_rule_executions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `rule_id` VARCHAR(100) NOT NULL COMMENT '실행된 룰 ID',
    `rule_name` VARCHAR(200) DEFAULT NULL,
    `trigger_event` VARCHAR(100) DEFAULT NULL COMMENT '트리거 이벤트',
    `input_context` LONGTEXT COMMENT 'JSON: 입력 컨텍스트',
    `condition_results` TEXT COMMENT 'JSON: 조건 평가 결과',
    `executed_actions` TEXT COMMENT 'JSON: 실행된 액션들',
    `execution_result` ENUM('success', 'partial', 'failed', 'skipped') NOT NULL,
    `result_data` TEXT COMMENT 'JSON: 실행 결과 데이터',
    `effect_on_student` TEXT COMMENT 'JSON: 학생에게 미친 영향',
    `execution_time_ms` INT(10) UNSIGNED DEFAULT NULL COMMENT '실행 시간(ms)',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_rule_id` (`rule_id`),
    KEY `idx_result` (`execution_result`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='룰 실행 로그';


-- =====================================================
-- 13. 컨텍스트 상태 테이블
-- 현재 학습 맥락 상태 저장 (스냅샷)
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_context_states` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `state_type` ENUM('snapshot', 'checkpoint', 'final') DEFAULT 'snapshot',
    `current_step` INT(2) UNSIGNED DEFAULT NULL,
    `emotion_state` VARCHAR(20) DEFAULT NULL,
    `persona_state` VARCHAR(50) DEFAULT NULL,
    `cognitive_load` DECIMAL(5,4) DEFAULT NULL COMMENT '인지 부하 추정치 (0-1)',
    `engagement_level` DECIMAL(5,4) DEFAULT NULL COMMENT '참여도 (0-1)',
    `understanding_level` DECIMAL(5,4) DEFAULT NULL COMMENT '이해도 추정치 (0-1)',
    `step_progress` TEXT COMMENT 'JSON: 단계별 진행 상태',
    `active_rules` TEXT COMMENT 'JSON: 활성 룰 목록',
    `active_ontology_nodes` TEXT COMMENT 'JSON: 활성 온톨로지 노드',
    `pending_interventions` TEXT COMMENT 'JSON: 대기 중인 개입',
    `memory_activity_state` TEXT COMMENT 'JSON: 장기기억 활동 상태',
    `whiteboard_state` TEXT COMMENT 'JSON: 화이트보드 상태',
    `full_context` LONGTEXT COMMENT 'JSON: 전체 컨텍스트 덤프',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_state_type` (`state_type`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='컨텍스트 상태 스냅샷';


-- =====================================================
-- 14. 학습 성과 요약 테이블
-- 세션별/컨텐츠별 성과 집계
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_learning_outcomes` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `total_duration_seconds` INT(10) UNSIGNED DEFAULT 0,
    `steps_completed` INT(2) UNSIGNED DEFAULT 0,
    `total_gestures` INT(10) UNSIGNED DEFAULT 0,
    `positive_gestures` INT(10) UNSIGNED DEFAULT 0 COMMENT 'check, arrow 등',
    `negative_gestures` INT(10) UNSIGNED DEFAULT 0 COMMENT 'x, question 등',
    `emotion_changes` INT(10) UNSIGNED DEFAULT 0,
    `dominant_emotion` VARCHAR(20) DEFAULT NULL,
    `persona_switches` INT(5) UNSIGNED DEFAULT 0,
    `final_persona` VARCHAR(50) DEFAULT NULL,
    `feedbacks_shown` INT(10) UNSIGNED DEFAULT 0,
    `hints_used` INT(10) UNSIGNED DEFAULT 0,
    `errors_made` INT(10) UNSIGNED DEFAULT 0,
    `memory_activity_completed` TINYINT(1) DEFAULT 0,
    `memory_activity_count` INT(5) UNSIGNED DEFAULT 0,
    `rules_triggered` INT(10) UNSIGNED DEFAULT 0,
    `interventions_applied` INT(10) UNSIGNED DEFAULT 0,
    `understanding_score` DECIMAL(5,4) DEFAULT NULL COMMENT '이해도 점수 (0-1)',
    `engagement_score` DECIMAL(5,4) DEFAULT NULL COMMENT '참여도 점수 (0-1)',
    `completion_status` ENUM('not_started', 'in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    `outcome_summary` TEXT COMMENT 'JSON: 성과 요약',
    `ai_assessment` TEXT COMMENT 'JSON: AI 평가',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_completion` (`completion_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학습 성과 요약';


-- =====================================================
-- 15. 화이트보드 상호작용 테이블
-- 화이트보드 관련 상호작용 기록
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_alt42i_whiteboard_actions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `student_id` BIGINT(10) UNSIGNED NOT NULL,
    `contents_id` VARCHAR(100) NOT NULL,
    `contents_type` VARCHAR(50) NOT NULL,
    `whiteboard_id` VARCHAR(200) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL COMMENT 'draw, erase, undo, redo, scroll, zoom 등',
    `action_data` TEXT COMMENT 'JSON: 액션 상세 데이터',
    `stroke_count` INT(10) UNSIGNED DEFAULT NULL COMMENT '획 수',
    `pause_duration_ms` INT(10) UNSIGNED DEFAULT NULL COMMENT '이전 액션 후 멈춤 시간',
    `current_step` INT(2) UNSIGNED DEFAULT NULL,
    `inferred_state` VARCHAR(50) DEFAULT NULL COMMENT '추론된 상태 (thinking, writing, erasing 등)',
    `timestamp_ms` BIGINT(13) UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_student_contents` (`student_id`, `contents_id`, `contents_type`),
    KEY `idx_whiteboard_id` (`whiteboard_id`),
    KEY `idx_action_type` (`action_type`),
    KEY `idx_timestamp` (`timestamp_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='화이트보드 상호작용';


-- =====================================================
-- 인덱스 추가 (성능 최적화)
-- =====================================================

-- 복합 인덱스: 학생별 컨텐츠 조회 최적화
CREATE INDEX IF NOT EXISTS `idx_alt42i_sessions_lookup` 
ON `mdl_alt42i_sessions` (`student_id`, `contents_id`, `contents_type`, `session_status`);

-- 시계열 데이터 조회 최적화
CREATE INDEX IF NOT EXISTS `idx_alt42i_logs_timeline` 
ON `mdl_alt42i_interaction_logs` (`session_id`, `timestamp_ms`);

-- 룰 조회 최적화
CREATE INDEX IF NOT EXISTS `idx_alt42i_rules_lookup` 
ON `mdl_alt42i_dynamic_rules` (`is_active`, `rule_category`, `priority` DESC);


-- =====================================================
-- 뷰: 세션 요약
-- =====================================================
CREATE OR REPLACE VIEW `mdl_alt42i_session_summary` AS
SELECT 
    s.session_id,
    s.student_id,
    s.contents_id,
    s.contents_type,
    s.persona_id,
    s.current_step,
    s.emotion_type,
    s.session_status,
    s.duration_seconds,
    o.steps_completed,
    o.total_gestures,
    o.feedbacks_shown,
    o.understanding_score,
    o.engagement_score,
    o.completion_status,
    s.started_at,
    s.ended_at
FROM `mdl_alt42i_sessions` s
LEFT JOIN `mdl_alt42i_learning_outcomes` o ON s.session_id = o.session_id;


-- =====================================================
-- 초기 데이터: 기본 온톨로지 노드
-- =====================================================
INSERT INTO `mdl_alt42i_ontology_nodes` 
    (`node_id`, `node_type`, `node_label`, `namespace`, `layer`, `properties`, `source`) 
VALUES
    ('OIW_WILL', 'concept', '의지(Will)', 'oiw', 'agent_core', '{"level": 1, "description": "학습 의도와 목표"}', 'system'),
    ('OIW_INTENT', 'concept', '의도(Intent)', 'oiw', 'agent_core', '{"level": 2, "description": "구체적 학습 의도"}', 'system'),
    ('OIW_CONTEXT', 'concept', '맥락(Context)', 'oiw', 'agent_core', '{"level": 3, "description": "학습 상황 맥락"}', 'system'),
    ('OIW_INTERPRETATION', 'concept', '해석(Interpretation)', 'oiw', 'agent_core', '{"level": 4, "description": "상황 해석"}', 'system'),
    ('OIW_DECISION', 'concept', '결정(Decision)', 'oiw', 'agent_core', '{"level": 5, "description": "교수 결정"}', 'system'),
    ('OIW_EXECUTION', 'concept', '실행(Execution)', 'oiw', 'agent_core', '{"level": 6, "description": "개입 실행"}', 'system'),
    ('EMOTION_CONFIDENT', 'instance', '자신있음', 'emotion', 'task_core', '{"icon": "😊", "valence": "positive"}', 'system'),
    ('EMOTION_NEUTRAL', 'instance', '보통', 'emotion', 'task_core', '{"icon": "😐", "valence": "neutral"}', 'system'),
    ('EMOTION_CONFUSED', 'instance', '헷갈림', 'emotion', 'task_core', '{"icon": "🤔", "valence": "negative"}', 'system'),
    ('EMOTION_STUCK', 'instance', '막힘', 'emotion', 'task_core', '{"icon": "😵", "valence": "negative"}', 'system'),
    ('EMOTION_ANXIOUS', 'instance', '불안', 'emotion', 'task_core', '{"icon": "😰", "valence": "negative"}', 'system'),
    ('STEP_UNDERSTAND', 'instance', '문제 파악', 'step', 'task_core', '{"number": 1}', 'system'),
    ('STEP_FORMULATE', 'instance', '식 세우기', 'step', 'task_core', '{"number": 2}', 'system'),
    ('STEP_SOLVE', 'instance', '풀이', 'step', 'task_core', '{"number": 3}', 'system'),
    ('STEP_VERIFY', 'instance', '검산', 'step', 'task_core', '{"number": 4}', 'system'),
    ('STEP_MEMORY', 'instance', '장기기억 활동', 'step', 'task_core', '{"number": 5}', 'system')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;


-- =====================================================
-- 초기 데이터: OIW 관계
-- =====================================================
INSERT INTO `mdl_alt42i_ontology_relations` 
    (`relation_id`, `source_node_id`, `target_node_id`, `relation_type`, `relation_label`, `namespace`, `source`) 
VALUES
    ('REL_WILL_INTENT', 'OIW_WILL', 'OIW_INTENT', 'leads_to', '의지에서 의도로', 'oiw', 'system'),
    ('REL_INTENT_CONTEXT', 'OIW_INTENT', 'OIW_CONTEXT', 'leads_to', '의도에서 맥락으로', 'oiw', 'system'),
    ('REL_CONTEXT_INTERPRETATION', 'OIW_CONTEXT', 'OIW_INTERPRETATION', 'leads_to', '맥락에서 해석으로', 'oiw', 'system'),
    ('REL_INTERPRETATION_DECISION', 'OIW_INTERPRETATION', 'OIW_DECISION', 'leads_to', '해석에서 결정으로', 'oiw', 'system'),
    ('REL_DECISION_EXECUTION', 'OIW_DECISION', 'OIW_EXECUTION', 'leads_to', '결정에서 실행으로', 'oiw', 'system'),
    ('REL_STEP_1_2', 'STEP_UNDERSTAND', 'STEP_FORMULATE', 'next', '다음 단계', 'step', 'system'),
    ('REL_STEP_2_3', 'STEP_FORMULATE', 'STEP_SOLVE', 'next', '다음 단계', 'step', 'system'),
    ('REL_STEP_3_4', 'STEP_SOLVE', 'STEP_VERIFY', 'next', '다음 단계', 'step', 'system'),
    ('REL_STEP_4_5', 'STEP_VERIFY', 'STEP_MEMORY', 'next', '다음 단계', 'step', 'system')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

