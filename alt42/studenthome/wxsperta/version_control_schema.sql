-- WXsperta 버전 관리 시스템 스키마
-- 21개 에이전트 카드의 프로젝트 및 속성 버전 관리

-- 1. 현재 프로젝트 구조 (최신 상태만 보관)
CREATE TABLE IF NOT EXISTS `wxsperta_projects_current` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `project_json` LONGTEXT NOT NULL COMMENT '전체 프로젝트 구조 JSON',
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_updated_by` INT NOT NULL,
    CHECK (id = 1) -- 단일 행만 허용
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 프로젝트 버전 히스토리 (전체 스냅샷)
CREATE TABLE IF NOT EXISTS `wxsperta_projects_versions` (
    `version_id` VARCHAR(40) PRIMARY KEY COMMENT 'UUID 또는 SHA-1 해시',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `author_id` INT NOT NULL,
    `author_name` VARCHAR(100),
    `commit_msg` VARCHAR(500) COMMENT '커밋 메시지',
    `project_json` LONGTEXT NOT NULL COMMENT '해당 시점의 전체 프로젝트 구조',
    `parent_version_id` VARCHAR(40) COMMENT '이전 버전 참조',
    `is_milestone` BOOLEAN DEFAULT FALSE COMMENT '주요 마일스톤 여부',
    INDEX idx_created_at (created_at DESC),
    INDEX idx_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 에이전트 카드 속성 현재 상태
CREATE TABLE IF NOT EXISTS `wxsperta_agent_texts_current` (
    `card_id` INT PRIMARY KEY COMMENT '에이전트 카드 ID (1-21)',
    `properties_json` JSON NOT NULL COMMENT '8-layer 속성 JSON',
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_updated_by` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 에이전트 카드 속성 버전 히스토리
CREATE TABLE IF NOT EXISTS `wxsperta_agent_texts_versions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `version_id` VARCHAR(40) NOT NULL COMMENT '프로젝트 버전과 동일',
    `card_id` INT NOT NULL COMMENT '에이전트 카드 ID',
    `properties_json` JSON NOT NULL COMMENT '해당 시점의 속성',
    UNIQUE KEY `uniq_version_card` (`version_id`, `card_id`),
    INDEX idx_version (version_id),
    INDEX idx_card (card_id),
    FOREIGN KEY (version_id) REFERENCES wxsperta_projects_versions(version_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 버전 간 차이점 저장 (선택적, 성능 최적화용)
CREATE TABLE IF NOT EXISTS `wxsperta_version_diffs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `from_version_id` VARCHAR(40) NOT NULL,
    `to_version_id` VARCHAR(40) NOT NULL,
    `diff_json` JSON NOT NULL COMMENT 'JSON 패치 형식의 차이점',
    `diff_summary` VARCHAR(500) COMMENT '변경 사항 요약',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_from_to` (`from_version_id`, `to_version_id`),
    INDEX idx_versions (from_version_id, to_version_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 사용자 메모/코멘트 (학생, 교사)
CREATE TABLE IF NOT EXISTS `wxsperta_user_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `version_id` VARCHAR(40) NOT NULL,
    `card_id` INT,
    `user_id` INT NOT NULL,
    `user_role` ENUM('student', 'teacher') NOT NULL,
    `note_text` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_version_card (version_id, card_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 버전 태그 (시맨틱 버저닝)
CREATE TABLE IF NOT EXISTS `wxsperta_version_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `version_id` VARCHAR(40) NOT NULL,
    `tag_name` VARCHAR(50) NOT NULL COMMENT 'v2.4.0-growth',
    `tag_type` ENUM('major', 'minor', 'patch', 'custom') DEFAULT 'custom',
    `description` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT NOT NULL,
    UNIQUE KEY `uniq_tag` (`tag_name`),
    INDEX idx_version (version_id),
    FOREIGN KEY (version_id) REFERENCES wxsperta_projects_versions(version_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 롤백 이력 추적
CREATE TABLE IF NOT EXISTS `wxsperta_rollback_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `from_version_id` VARCHAR(40) NOT NULL,
    `to_version_id` VARCHAR(40) NOT NULL,
    `rollback_reason` VARCHAR(500),
    `performed_by` INT NOT NULL,
    `performed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `pre_rollback_backup_version_id` VARCHAR(40) COMMENT '롤백 전 자동 백업된 버전',
    INDEX idx_versions (from_version_id, to_version_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 초기 데이터 삽입 (현재 프로젝트 구조)
INSERT INTO wxsperta_projects_current (id, project_json, last_updated_by) 
VALUES (1, '{}', 1) 
ON DUPLICATE KEY UPDATE id=id;

-- 에이전트 카드 초기 데이터 (1-21)
INSERT INTO wxsperta_agent_texts_current (card_id, properties_json, last_updated_by) 
VALUES 
(1, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(2, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(3, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(4, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(5, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(6, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(7, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(8, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(9, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(10, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(11, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(12, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(13, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(14, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(15, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(16, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(17, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(18, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(19, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(20, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1),
(21, '{"worldView":"","context":"","structure":"","process":"","execution":"","reflection":"","transfer":"","abstraction":""}', 1)
ON DUPLICATE KEY UPDATE card_id=card_id;