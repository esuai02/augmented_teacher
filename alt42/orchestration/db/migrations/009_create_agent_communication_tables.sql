-- ============================================================================
-- Migration 009: Agent Communication Tables
-- 에이전트 간 통신 시스템을 위한 테이블 생성
--
-- @package     AugmentedTeacher
-- @subpackage  EngineCore
-- @version     1.0.0
-- @created     2025-12-03
--
-- 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/009_create_agent_communication_tables.sql
-- ============================================================================

-- ============================================================================
-- 1. 에이전트 메시지 큐 테이블
-- 에이전트 간 비동기 통신을 위한 메시지 저장
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_messages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` VARCHAR(64) NOT NULL COMMENT 'UUID v4 형식의 고유 메시지 ID',
    `from_agent` TINYINT UNSIGNED NOT NULL COMMENT '발신 에이전트 번호 (1-21)',
    `to_agent` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '수신 에이전트 번호 (1-21, 0=브로드캐스트)',
    `message_type` VARCHAR(50) NOT NULL COMMENT '메시지 유형 (emotion_detected, dropout_risk 등)',
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '우선순위 (1=최고, 10=최저)',
    `payload` JSON COMMENT '메시지 페이로드 (JSON)',
    `status` ENUM('pending', 'processing', 'processed', 'failed', 'expired') NOT NULL DEFAULT 'pending' COMMENT '메시지 상태',
    `checksum` VARCHAR(64) COMMENT 'SHA256 페이로드 체크섬',
    `retry_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '재시도 횟수',
    `expires_at` DATETIME NOT NULL COMMENT '메시지 만료 시간',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    `timeprocessed` INT UNSIGNED DEFAULT NULL COMMENT '처리 완료 시간 (Unix timestamp)',

    UNIQUE KEY `uk_message_id` (`message_id`),
    INDEX `idx_to_agent_status` (`to_agent`, `status`),
    INDEX `idx_from_agent` (`from_agent`),
    INDEX `idx_priority_created` (`priority`, `timecreated`),
    INDEX `idx_message_type` (`message_type`),
    INDEX `idx_status_expires` (`status`, `expires_at`),
    INDEX `idx_created` (`timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='에이전트 간 메시지 큐';

-- ============================================================================
-- 2. 에이전트 페르소나 상태 테이블
-- 사용자별 에이전트 페르소나 상태 저장
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_persona_state` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Moodle 사용자 ID',
    `nagent` TINYINT UNSIGNED NOT NULL COMMENT '에이전트 번호 (1-21)',
    `persona_code` VARCHAR(20) NOT NULL COMMENT '현재 페르소나 코드',
    `confidence` DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT '페르소나 결정 신뢰도 (0.00-1.00)',
    `context_data` JSON COMMENT '컨텍스트 데이터 (JSON)',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    `timemodified` INT UNSIGNED NOT NULL COMMENT '수정 시간 (Unix timestamp)',

    UNIQUE KEY `uk_user_agent` (`user_id`, `nagent`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_nagent` (`nagent`),
    INDEX `idx_persona_code` (`persona_code`),
    INDEX `idx_modified` (`timemodified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='에이전트별 사용자 페르소나 상태';

-- ============================================================================
-- 3. 페르소나 전환 이력 테이블
-- 페르소나 전환 히스토리 추적
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_transitions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Moodle 사용자 ID',
    `nagent` TINYINT UNSIGNED NOT NULL COMMENT '에이전트 번호 (1-21)',
    `from_persona` VARCHAR(20) NOT NULL COMMENT '이전 페르소나 코드',
    `to_persona` VARCHAR(20) NOT NULL COMMENT '새 페르소나 코드',
    `trigger_type` VARCHAR(50) NOT NULL COMMENT '전환 트리거 유형',
    `confidence` DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT '전환 신뢰도 (0.00-1.00)',
    `context_snapshot` JSON COMMENT '전환 시점 컨텍스트 스냅샷',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '전환 시간 (Unix timestamp)',

    INDEX `idx_user_agent` (`user_id`, `nagent`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_nagent` (`nagent`),
    INDEX `idx_trigger_type` (`trigger_type`),
    INDEX `idx_created` (`timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='페르소나 전환 이력';

-- ============================================================================
-- 4. 에이전트 하트비트 테이블
-- 에이전트 가용성 모니터링
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_heartbeat` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nagent` TINYINT UNSIGNED NOT NULL COMMENT '에이전트 번호 (1-21)',
    `status` ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active' COMMENT '에이전트 상태',
    `last_activity` INT UNSIGNED NOT NULL COMMENT '마지막 활동 시간 (Unix timestamp)',
    `messages_processed` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '처리된 메시지 수',
    `errors_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '에러 발생 수',
    `metadata` JSON COMMENT '추가 메타데이터',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    `timemodified` INT UNSIGNED NOT NULL COMMENT '수정 시간 (Unix timestamp)',

    UNIQUE KEY `uk_nagent` (`nagent`),
    INDEX `idx_status` (`status`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='에이전트 하트비트 및 가용성';

-- ============================================================================
-- 5. 에이전트 구독 테이블
-- 메시지 유형별 에이전트 구독 관리
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_subscriptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nagent` TINYINT UNSIGNED NOT NULL COMMENT '구독 에이전트 번호 (1-21)',
    `message_type` VARCHAR(50) NOT NULL COMMENT '구독할 메시지 유형',
    `handler_class` VARCHAR(255) COMMENT '핸들러 클래스명 (선택적)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '생성 시간 (Unix timestamp)',

    UNIQUE KEY `uk_agent_type` (`nagent`, `message_type`),
    INDEX `idx_message_type` (`message_type`),
    INDEX `idx_nagent` (`nagent`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='에이전트 메시지 구독 관리';

-- ============================================================================
-- 6. 요청-응답 매핑 테이블
-- 동기식 요청-응답 패턴 지원
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_request_response` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_id` VARCHAR(64) NOT NULL COMMENT '요청 ID (UUID)',
    `request_message_id` VARCHAR(64) NOT NULL COMMENT '요청 메시지 ID',
    `response_message_id` VARCHAR(64) DEFAULT NULL COMMENT '응답 메시지 ID',
    `from_agent` TINYINT UNSIGNED NOT NULL COMMENT '요청 에이전트',
    `to_agent` TINYINT UNSIGNED NOT NULL COMMENT '응답 에이전트',
    `status` ENUM('waiting', 'responded', 'timeout', 'error') NOT NULL DEFAULT 'waiting' COMMENT '상태',
    `timeout_at` INT UNSIGNED NOT NULL COMMENT '타임아웃 시간 (Unix timestamp)',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '요청 시간',
    `timeresponded` INT UNSIGNED DEFAULT NULL COMMENT '응답 시간',

    UNIQUE KEY `uk_request_id` (`request_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_from_agent` (`from_agent`),
    INDEX `idx_to_agent` (`to_agent`),
    INDEX `idx_timeout` (`timeout_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='요청-응답 패턴 매핑';

-- ============================================================================
-- 7. 통신 로그 테이블
-- 디버깅 및 모니터링을 위한 통신 로그
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_communication_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` VARCHAR(64) NOT NULL COMMENT '메시지 ID',
    `action` VARCHAR(30) NOT NULL COMMENT '액션 (send, receive, acknowledge 등)',
    `from_agent` TINYINT UNSIGNED NOT NULL COMMENT '발신 에이전트',
    `to_agent` TINYINT UNSIGNED NOT NULL COMMENT '수신 에이전트',
    `status` VARCHAR(20) NOT NULL COMMENT '상태',
    `details` JSON COMMENT '상세 정보',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '로그 시간 (Unix timestamp)',

    INDEX `idx_message_id` (`message_id`),
    INDEX `idx_from_agent` (`from_agent`),
    INDEX `idx_to_agent` (`to_agent`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='에이전트 통신 로그';

-- ============================================================================
-- 8. 에이전트 협력 관계 테이블
-- 에이전트 간 허용된 협력 관계 정의
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mdl_at_agent_collaboration` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `from_agent` TINYINT UNSIGNED NOT NULL COMMENT '발신 에이전트',
    `to_agent` TINYINT UNSIGNED NOT NULL COMMENT '수신 에이전트',
    `collaboration_type` VARCHAR(50) NOT NULL COMMENT '협력 유형',
    `is_bidirectional` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '양방향 여부',
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '기본 우선순위',
    `description` TEXT COMMENT '협력 관계 설명',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `timecreated` INT UNSIGNED NOT NULL COMMENT '생성 시간',

    UNIQUE KEY `uk_collaboration` (`from_agent`, `to_agent`, `collaboration_type`),
    INDEX `idx_from_agent` (`from_agent`),
    INDEX `idx_to_agent` (`to_agent`),
    INDEX `idx_type` (`collaboration_type`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='에이전트 협력 관계 정의';

-- ============================================================================
-- 초기 데이터: 에이전트 하트비트 초기화 (21개 에이전트)
-- ============================================================================
INSERT IGNORE INTO `mdl_at_agent_heartbeat` (`nagent`, `status`, `last_activity`, `messages_processed`, `errors_count`, `timecreated`, `timemodified`)
VALUES
    (1, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (2, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (3, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (4, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (5, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (6, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (7, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (8, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (9, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (10, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (11, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (12, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (13, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (14, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (15, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (16, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (17, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (18, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (19, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (20, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (21, 'active', UNIX_TIMESTAMP(), 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 초기 데이터: 에이전트 협력 관계 (계획서 기준)
-- Agent05 → Agent08: 좌절 감지 시 평온화 모드
-- Agent09 → Broadcast: 이탈 위험 감지 시 전체 알림
-- Agent03 → Agent09: 진단 완료 후 학습 계획 생성
-- Agent20 → Agent21: 개입 준비 완료 후 실행
-- ============================================================================
INSERT IGNORE INTO `mdl_at_agent_collaboration` (`from_agent`, `to_agent`, `collaboration_type`, `is_bidirectional`, `priority`, `description`, `is_active`, `timecreated`)
VALUES
    (5, 8, 'frustration_to_calmness', 0, 2, 'Agent05(학습감정)가 좌절 감지 시 Agent08(평온도)에 평온화 모드 활성화 요청', 1, UNIX_TIMESTAMP()),
    (9, 0, 'dropout_broadcast', 0, 1, 'Agent09(학습관리)가 이탈 위험 감지 시 전체 에이전트에 브로드캐스트', 1, UNIX_TIMESTAMP()),
    (3, 9, 'diagnosis_to_planning', 0, 3, 'Agent03(목표분석)이 진단 완료 후 Agent09(학습관리)에 학습 계획 생성 요청', 1, UNIX_TIMESTAMP()),
    (20, 21, 'preparation_to_execution', 0, 2, 'Agent20(개입준비)가 준비 완료 후 Agent21(개입실행)에 실행 요청', 1, UNIX_TIMESTAMP()),
    (13, 0, 'dropout_risk_broadcast', 0, 1, 'Agent13(학습이탈)이 이탈 위험 감지 시 전체 알림', 1, UNIX_TIMESTAMP()),
    (5, 13, 'emotion_to_dropout', 0, 3, 'Agent05(학습감정)이 부정적 감정 지속 시 Agent13(학습이탈)에 알림', 1, UNIX_TIMESTAMP()),
    (8, 5, 'calmness_feedback', 0, 4, 'Agent08(평온도)이 평온화 완료 후 Agent05(학습감정)에 피드백', 1, UNIX_TIMESTAMP());

-- ============================================================================
-- 완료 메시지
-- ============================================================================
-- Migration 009 completed successfully
-- Tables created:
--   1. mdl_at_agent_messages - 메시지 큐
--   2. mdl_at_agent_persona_state - 페르소나 상태
--   3. mdl_at_agent_transitions - 전환 이력
--   4. mdl_at_agent_heartbeat - 에이전트 하트비트
--   5. mdl_at_agent_subscriptions - 구독 관리
--   6. mdl_at_agent_request_response - 요청-응답 매핑
--   7. mdl_at_agent_communication_log - 통신 로그
--   8. mdl_at_agent_collaboration - 협력 관계
-- ============================================================================
