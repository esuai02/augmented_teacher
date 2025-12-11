
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
    `stage_names` TEXT COMMENT 'JSON: 단계 이름 배열',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_content_id` (`content_id`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. 개념 정의 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_concepts` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `concept_id` VARCHAR(100) NOT NULL COMMENT '개념 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `name` VARCHAR(200) NOT NULL COMMENT '개념 이름',
    `icon` VARCHAR(10) DEFAULT NULL COMMENT '아이콘',
    `color` VARCHAR(20) DEFAULT NULL COMMENT '색상 코드',
    `order_index` INT(5) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_concept_content` (`concept_id`, `content_id`),
    KEY `idx_content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. 노드 정의 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_nodes` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `node_id` VARCHAR(100) NOT NULL COMMENT '노드 ID',
    `content_id` VARCHAR(100) NOT NULL COMMENT '컨텐츠 ID',
    `label` VARCHAR(200) NOT NULL COMMENT '노드 라벨',
    `type` VARCHAR(50) NOT NULL COMMENT '노드 타입',
    `stage` INT(2) NOT NULL COMMENT '단계 번호',
    `x` INT(5) NOT NULL COMMENT 'X 좌표',
    `y` INT(5) NOT NULL COMMENT 'Y 좌표',
    `description` TEXT COMMENT '노드 설명',
    `order_index` INT(5) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_node_content` (`node_id`, `content_id`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. 노드-개념 연결 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_node_concepts` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `node_id` VARCHAR(100) NOT NULL,
    `concept_id` VARCHAR(100) NOT NULL,
    `content_id` VARCHAR(100) NOT NULL,
    `order_index` INT(5) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_node_concept` (`node_id`, `concept_id`, `content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. 엣지 정의 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_edges` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_node_id` VARCHAR(100) NOT NULL,
    `target_node_id` VARCHAR(100) NOT NULL,
    `content_id` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_edge_unique` (`source_node_id`, `target_node_id`, `content_id`),
    KEY `idx_content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. 사용자 세션 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_user_sessions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL,
    `user_id` BIGINT(10) UNSIGNED NOT NULL,
    `content_id` VARCHAR(100) NOT NULL,
    `current_stage` INT(2) DEFAULT 0,
    `current_node_id` VARCHAR(100) DEFAULT NULL,
    `is_complete` TINYINT(1) DEFAULT 0,
    `selected_path` TEXT NOT NULL,
    `activated_concepts` TEXT,
    `quantum_state` TEXT,
    `history_snapshot` LONGTEXT,
    `final_result` VARCHAR(50) DEFAULT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_id` (`session_id`),
    KEY `idx_user_content` (`user_id`, `content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. AI 요청 기록 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_ai_requests` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` VARCHAR(100) NOT NULL,
    `user_id` BIGINT(10) UNSIGNED NOT NULL,
    `content_id` VARCHAR(100) NOT NULL,
    `request_type` ENUM('new_solution', 'misconception', 'custom_input') NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `user_input` TEXT,
    `context_snapshot` LONGTEXT,
    `existing_nodes_snapshot` LONGTEXT,
    `openai_request` LONGTEXT,
    `openai_response` LONGTEXT,
    `openai_model` VARCHAR(50) DEFAULT 'gpt-4o',
    `openai_tokens_used` INT(10) DEFAULT 0,
    `error_message` TEXT,
    `processing_time_ms` INT(10) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_request_id` (`request_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. AI 제안 마스터 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_ai_suggestions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `suggestion_id` VARCHAR(100) NOT NULL,
    `request_id` VARCHAR(100) NOT NULL,
    `content_id` VARCHAR(100) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `suggestion_type` ENUM('new_path', 'misconception_path', 'modification') NOT NULL,
    `confidence_score` DECIMAL(3,2) DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'applied') DEFAULT 'pending',
    `reviewed_by` BIGINT(10) UNSIGNED DEFAULT NULL,
    `review_comment` TEXT,
    `applied_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_suggestion_id` (`suggestion_id`),
    KEY `idx_request_id` (`request_id`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. AI 제안 노드 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_ai_suggestion_nodes` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `suggestion_id` VARCHAR(100) NOT NULL,
    `proposed_node_id` VARCHAR(100) NOT NULL,
    `label` VARCHAR(200) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `stage` INT(2) NOT NULL,
    `x` INT(5) NOT NULL,
    `y` INT(5) NOT NULL,
    `description` TEXT,
    `ai_reasoning` TEXT,
    `is_new` TINYINT(1) DEFAULT 1,
    `original_node_id` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_suggestion_id` (`suggestion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. AI 제안 엣지 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_ai_suggestion_edges` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `suggestion_id` VARCHAR(100) NOT NULL,
    `source_node_id` VARCHAR(100) NOT NULL,
    `target_node_id` VARCHAR(100) NOT NULL,
    `is_new` TINYINT(1) DEFAULT 1,
    `ai_reasoning` TEXT,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_suggestion_id` (`suggestion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. 버전 관리 테이블
-- =====================================================
CREATE TABLE IF NOT EXISTS `mdl_at_quantum_map_versions` (
    `id` BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_id` VARCHAR(100) NOT NULL,
    `content_id` VARCHAR(100) NOT NULL,
    `version_number` INT(5) NOT NULL,
    `change_type` ENUM('initial', 'ai_suggestion', 'manual_edit', 'rollback') NOT NULL,
    `suggestion_id` VARCHAR(100) DEFAULT NULL,
    `changed_by` BIGINT(10) UNSIGNED NOT NULL,
    `change_summary` TEXT,
    `nodes_snapshot` LONGTEXT NOT NULL,
    `edges_snapshot` LONGTEXT NOT NULL,
    `concepts_snapshot` LONGTEXT NOT NULL,
    `is_current` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_version_id` (`version_id`),
    UNIQUE KEY `idx_content_version` (`content_id`, `version_number`),
    KEY `idx_is_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

