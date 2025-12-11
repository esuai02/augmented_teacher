 
-- =====================================================
-- 1. 컨텐츠 메타데이터 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_contents` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `contents_type` VARCHAR(50) DEFAULT NULL COMMENT '컨텐츠 타입',
    `title` VARCHAR(200) NOT NULL COMMENT '문제 제목',
    `answer` VARCHAR(200) DEFAULT NULL COMMENT '정답',
    `question_image_url` VARCHAR(500) DEFAULT NULL COMMENT '문제 이미지 URL',
    `solution_image_url` VARCHAR(500) DEFAULT NULL COMMENT '해설 이미지 URL',
    `stage_names` TEXT COMMENT 'JSON: 단계 이름 배열 ["시작", "문제해석", ...]',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_content_id` (`content_id`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 컨텐츠 메타데이터';

-- =====================================================
-- 2. 개념 정의 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_concepts` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `concept_id` VARCHAR(100) NOT NULL COMMENT '개념 ID (예: factor)',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `name` VARCHAR(200) NOT NULL COMMENT '개념 이름',
    `icon` VARCHAR(10) DEFAULT NULL COMMENT '아이콘',
    `color` VARCHAR(20) DEFAULT NULL COMMENT '색상 코드',
    `order_index` INT(5) DEFAULT 0 COMMENT '표시 순서',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_concept_content` (`concept_id`, `content_id`),
    KEY `idx_content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 개념 정의';

-- =====================================================
-- 3. 노드 정의 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_nodes` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `node_id` VARCHAR(100) NOT NULL COMMENT '노드 ID (예: s1_full)',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `label` VARCHAR(200) NOT NULL COMMENT '노드 라벨',
    `type` VARCHAR(50) NOT NULL COMMENT '노드 타입 (start, correct, partial, wrong, confused, success, fail)',
    `stage` INT(2) NOT NULL COMMENT '단계 번호 (0-7)',
    `x` INT(5) NOT NULL COMMENT 'X 좌표',
    `y` INT(5) NOT NULL COMMENT 'Y 좌표',
    `description` TEXT COMMENT '노드 설명',
    `order_index` INT(5) DEFAULT 0 COMMENT '같은 단계 내 순서',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_node_content` (`node_id`, `content_id`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 노드 정의';

-- =====================================================
-- 4. 노드-개념 연결 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_node_concepts` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `node_id` VARCHAR(100) NOT NULL COMMENT '노드 ID',
    `concept_id` VARCHAR(100) NOT NULL COMMENT '개념 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `order_index` INT(5) DEFAULT 0 COMMENT '개념 표시 순서',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_node_concept` (`node_id`, `concept_id`, `content_id`),
    KEY `idx_node_id` (`node_id`, `content_id`),
    KEY `idx_concept_id` (`concept_id`, `content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 노드-개념 연결';

-- =====================================================
-- 5. 엣지 정의 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_edges` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_node_id` VARCHAR(100) NOT NULL COMMENT '소스 노드 ID',
    `target_node_id` VARCHAR(100) NOT NULL COMMENT '타겟 노드 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_edge_unique` (`source_node_id`, `target_node_id`, `content_id`),
    KEY `idx_source_node` (`source_node_id`, `content_id`),
    KEY `idx_target_node` (`target_node_id`, `content_id`),
    KEY `idx_content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 엣지 정의';

-- =====================================================
-- 6. 사용자 세션 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_sessions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 고유 ID',
    `user_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '사용자 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `current_stage` INT(2) DEFAULT 0 COMMENT '현재 단계',
    `current_node_id` VARCHAR(100) DEFAULT NULL COMMENT '현재 노드 ID',
    `is_complete` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
    `selected_path` TEXT NOT NULL COMMENT 'JSON: 선택한 경로 배열 ["start", "s1_full", ...]',
    `activated_concepts` TEXT COMMENT 'JSON: 활성화된 개념 ID 배열',
    `quantum_state` TEXT COMMENT 'JSON: 양자 상태 {alpha, beta, gamma}',
    `history_snapshot` LONGTEXT COMMENT 'JSON: 전체 히스토리 배열 (세션 복원용)',
    `final_result` VARCHAR(50) DEFAULT NULL COMMENT '최종 결과 (success, partial, fail)',
    `started_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `completed_at` DATETIME(3) DEFAULT NULL,
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_id` (`session_id`),
    KEY `idx_user_content` (`user_id`, `content_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 사용자 세션';

-- =====================================================
-- 7. 사용자 경로 기록 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_paths` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `user_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '사용자 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `path_sequence` TEXT NOT NULL COMMENT 'JSON: 선택한 노드 ID 배열',
    `path_length` INT(5) DEFAULT 0 COMMENT '경로 길이',
    `final_result` VARCHAR(50) DEFAULT NULL COMMENT '최종 결과 (success, partial, fail)',
    `final_node_id` VARCHAR(100) DEFAULT NULL COMMENT '최종 노드 ID',
    `activated_concepts_count` INT(5) DEFAULT 0 COMMENT '활성화된 개념 수',
    `quantum_state_final` TEXT COMMENT 'JSON: 최종 양자 상태',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_user_content` (`user_id`, `content_id`),
    KEY `idx_final_result` (`final_result`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 사용자 경로 기록';

-- =====================================================
-- 8. 히스토리 스냅샷 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_history` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `user_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '사용자 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `snapshot_index` INT(5) NOT NULL COMMENT '히스토리 인덱스 (0부터 시작)',
    `path` TEXT NOT NULL COMMENT 'JSON: 경로 배열 ["start", "s1_full", ...]',
    `quantum_state` TEXT NOT NULL COMMENT 'JSON: 양자 상태 {alpha, beta, gamma}',
    `activated_concepts` TEXT NOT NULL COMMENT 'JSON: 활성화된 개념 ID 배열 (Set이 아닌 Array)',
    `current_stage` INT(2) NOT NULL COMMENT '현재 단계',
    `current_node_id` VARCHAR(100) DEFAULT NULL COMMENT '현재 노드 ID',
    `is_complete` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_snapshot` (`session_id`, `snapshot_index`),
    KEY `idx_user_content` (`user_id`, `content_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 사용자 히스토리 스냅샷';

-- =====================================================
-- 9. 양자 상태 기록 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_states` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `user_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '사용자 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `node_id` VARCHAR(100) NOT NULL COMMENT '노드 ID',
    `alpha` DECIMAL(5,4) NOT NULL COMMENT '정답 확률 (0-1)',
    `beta` DECIMAL(5,4) NOT NULL COMMENT '오개념 확률 (0-1)',
    `gamma` DECIMAL(5,4) NOT NULL COMMENT '혼란 확률 (0-1)',
    `state_sum` DECIMAL(6,4) GENERATED ALWAYS AS (alpha + beta + gamma) STORED COMMENT '합계 (검증용)',
    `current_stage` INT(2) NOT NULL COMMENT '현재 단계',
    `timestamp` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_user_content` (`user_id`, `content_id`),
    KEY `idx_node_id` (`node_id`, `content_id`),
    KEY `idx_timestamp` (`timestamp`),
    KEY `idx_current_stage` (`current_stage`),
    -- 양자 상태 합은 1.0이어야 함 (허용 오차: ±0.01)
    CONSTRAINT `chk_state_sum` CHECK (`state_sum` BETWEEN 0.99 AND 1.01)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 사용자 양자 상태 기록';

-- =====================================================
-- 10. 개념 활성화 기록 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_concepts` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `user_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '사용자 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `concept_id` VARCHAR(100) NOT NULL COMMENT '개념 ID',
    `node_id` VARCHAR(100) NOT NULL COMMENT '개념을 활성화한 노드 ID',
    `activated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_concept` (`session_id`, `concept_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_user_content` (`user_id`, `content_id`),
    KEY `idx_concept_id` (`concept_id`, `content_id`),
    KEY `idx_node_id` (`node_id`, `content_id`),
    KEY `idx_activated_at` (`activated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 사용자 개념 활성화 기록';

-- =====================================================
-- 11. 노드 이벤트 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_events` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT '세션 ID',
    `user_id` BIGINT(10) UNSIGNED NOT NULL COMMENT '사용자 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `event_type` ENUM('click', 'backtrack', 'reset') NOT NULL COMMENT '이벤트 타입',
    `node_id` VARCHAR(100) DEFAULT NULL COMMENT '관련 노드 ID (NULL일 수 있음)',
    `stage` INT(2) DEFAULT NULL COMMENT '단계 번호',
    `path_before` TEXT COMMENT 'JSON: 이벤트 전 경로',
    `path_after` TEXT COMMENT 'JSON: 이벤트 후 경로',
    `concepts_before` TEXT COMMENT 'JSON: 이벤트 전 활성화된 개념',
    `concepts_after` TEXT COMMENT 'JSON: 이벤트 후 활성화된 개념',
    `quantum_state_before` TEXT COMMENT 'JSON: 이벤트 전 양자 상태',
    `quantum_state_after` TEXT COMMENT 'JSON: 이벤트 후 양자 상태',
    `timestamp` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_user_content` (`user_id`, `content_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_node_id` (`node_id`, `content_id`),
    KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quantum Modeling 사용자 노드 이벤트 기록';

