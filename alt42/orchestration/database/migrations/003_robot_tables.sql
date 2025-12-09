-- 파일: database/migrations/003_robot_tables.sql (Line 1)
-- 로봇-스마트폰 통신 시스템 데이터베이스 스키마
-- MySQL 5.7 compatible
-- Created: 2025-01-27

-- ============================================================
-- 1. 로봇 등록 테이블
-- 로봇-스마트폰 쌍의 등록 정보 저장
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_robot_registration (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 고유 ID',
    device_id VARCHAR(100) NOT NULL COMMENT '스마트폰 기기 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user 참조)',
    device_info TEXT DEFAULT NULL COMMENT '기기 정보 (JSON)',
    location_info TEXT DEFAULT NULL COMMENT '위치 정보 (JSON)',
    capabilities TEXT DEFAULT NULL COMMENT '기능 정보 (JSON)',
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active' COMMENT '로봇 상태',
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '등록 시각',
    last_sync_at DATETIME DEFAULT NULL COMMENT '마지막 동기화 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_robot_id (robot_id),
    UNIQUE KEY unique_device_id (device_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_last_sync (last_sync_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 등록 정보';

-- ============================================================
-- 2. 센서 데이터 테이블
-- 스마트폰 센서 데이터 저장
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_robot_sensor_data (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user 참조)',
    sensor_data TEXT NOT NULL COMMENT '센서 데이터 (JSON)',
    processed_metrics TEXT DEFAULT NULL COMMENT '처리된 메트릭 (JSON)',
    session_id VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    timestamp DATETIME NOT NULL COMMENT '측정 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_robot_student (robot_id, student_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_session (session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 센서 데이터';

-- ============================================================
-- 3. 로봇 개입 실행 테이블
-- 로봇을 통한 개입 메시지 실행 기록
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_robot_intervention_execution (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    intervention_id VARCHAR(100) NOT NULL COMMENT '개입 ID',
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user 참조)',
    intervention_type VARCHAR(50) NOT NULL COMMENT '개입 유형 (micro_break, encouragement, etc.)',
    message_data TEXT NOT NULL COMMENT '메시지 데이터 (JSON)',
    robot_actions TEXT DEFAULT NULL COMMENT '로봇 동작 (JSON)',
    status ENUM('pending', 'sent', 'delivered', 'executing', 'completed', 'failed', 'expired') NOT NULL DEFAULT 'pending' COMMENT '실행 상태',
    sent_at DATETIME DEFAULT NULL COMMENT '전송 시각',
    delivered_at DATETIME DEFAULT NULL COMMENT '수신 시각',
    executed_at DATETIME DEFAULT NULL COMMENT '실행 시각',
    completed_at DATETIME DEFAULT NULL COMMENT '완료 시각',
    execution_result TEXT DEFAULT NULL COMMENT '실행 결과 (JSON)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_intervention_id (intervention_id),
    INDEX idx_robot_student (robot_id, student_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 개입 실행';

-- ============================================================
-- 4. 로봇 상태 로그 테이블
-- 로봇 상태 변경 이력 추적
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_robot_status_log (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 ID',
    status ENUM('active', 'inactive', 'maintenance', 'error') NOT NULL COMMENT '상태',
    battery_level INT(3) DEFAULT NULL COMMENT '배터리 레벨 (0-100)',
    connection_status ENUM('online', 'offline', 'unstable') DEFAULT NULL COMMENT '연결 상태',
    metadata TEXT DEFAULT NULL COMMENT '추가 메타데이터 (JSON)',
    timestamp DATETIME NOT NULL COMMENT '상태 변경 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_robot (robot_id),
    INDEX idx_status (status),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 상태 로그';

-- ============================================================
-- 5. 로봇-학생 매핑 테이블 (선택)
-- 로봇과 학생의 매핑 관계 관리
-- ============================================================
CREATE TABLE IF NOT EXISTS mdl_robot_student_mapping (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user 참조)',
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '할당 시각',
    unassigned_at DATETIME DEFAULT NULL COMMENT '해제 시각',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 여부',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_robot_active (robot_id, is_active),
    INDEX idx_student (student_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇-학생 매핑';

-- ============================================================
-- 데이터 보관 정책 설정
-- ============================================================

-- 센서 데이터는 90일 후 자동 삭제 (이벤트 스케줄러 필요)
-- 로봇 상태 로그는 30일 후 자동 삭제
-- 개입 실행 기록은 1년 보관

-- ============================================================
-- 샘플 데이터 (테스트용)
-- ============================================================

-- 테스트 로봇 등록
-- INSERT INTO mdl_robot_registration (robot_id, device_id, student_id, device_info, capabilities, status) VALUES
-- ('robot-001', 'android-abc123', 123, '{"device_model": "Samsung Galaxy S23", "os_version": "Android 14"}', '{"camera": true, "microphone": true, "tts": true}', 'active');

-- ============================================================
-- 검증 쿼리
-- ============================================================

-- 테이블 생성 확인
-- SELECT table_name FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_name LIKE 'mdl_robot_%';

-- 테이블별 행 수 확인
-- SELECT 'robot_registration' as table_name, COUNT(*) as row_count FROM mdl_robot_registration
-- UNION ALL
-- SELECT 'robot_sensor_data', COUNT(*) FROM mdl_robot_sensor_data
-- UNION ALL
-- SELECT 'robot_intervention_execution', COUNT(*) FROM mdl_robot_intervention_execution
-- UNION ALL
-- SELECT 'robot_status_log', COUNT(*) FROM mdl_robot_status_log
-- UNION ALL
-- SELECT 'robot_student_mapping', COUNT(*) FROM mdl_robot_student_mapping;

