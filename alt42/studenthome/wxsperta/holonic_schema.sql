-- Holonic WXSPERTA 데이터베이스 스키마
-- 무한 재귀 방지 및 안정적 관리를 위한 설계

-- 1. 기존 agents 테이블 업그레이드 (Holonic 구조 추가)
ALTER TABLE mdl_wxsperta_agents 
ADD COLUMN parent_agent_id INT(10) DEFAULT NULL,
ADD COLUMN depth_level INT(3) DEFAULT 0,
ADD COLUMN max_depth INT(3) DEFAULT 5,  -- 무한 재귀 방지
ADD COLUMN is_active BOOLEAN DEFAULT true,
ADD COLUMN created_by_agent_id INT(10) DEFAULT NULL,
ADD INDEX idx_parent (parent_agent_id),
ADD INDEX idx_depth (depth_level),
ADD CONSTRAINT fk_parent_agent FOREIGN KEY (parent_agent_id) 
    REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE;

-- 2. 프로젝트 테이블 (Holonic 구조)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_projects (
    id INT(10) NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    agent_owner_id INT(10) NOT NULL,
    parent_project_id INT(10) DEFAULT NULL,
    depth_level INT(3) DEFAULT 0,
    status ENUM('pending', 'active', 'completed', 'failed', 'suspended') DEFAULT 'pending',
    priority INT(3) DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_owner (agent_owner_id),
    INDEX idx_parent_project (parent_project_id),
    INDEX idx_status (status),
    CONSTRAINT fk_project_owner FOREIGN KEY (agent_owner_id) 
        REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE,
    CONSTRAINT fk_parent_project FOREIGN KEY (parent_project_id) 
        REFERENCES mdl_wxsperta_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 프로젝트별 WXSPERTA 속성
CREATE TABLE IF NOT EXISTS mdl_wxsperta_project_props (
    id INT(10) NOT NULL AUTO_INCREMENT,
    project_id INT(10) NOT NULL,
    layer ENUM('worldView', 'context', 'structure', 'process', 
               'execution', 'reflection', 'transfer', 'abstraction') NOT NULL,
    content TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_project_layer (project_id, layer),
    CONSTRAINT fk_project_props FOREIGN KEY (project_id) 
        REFERENCES mdl_wxsperta_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 이벤트 버스 테이블 (확장)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_event_bus (
    id INT(10) NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    emitter_type ENUM('agent', 'project', 'user', 'system') NOT NULL,
    emitter_id INT(10) NOT NULL,
    target_type ENUM('agent', 'project', 'user', 'broadcast') DEFAULT 'broadcast',
    target_id INT(10) DEFAULT NULL,
    payload TEXT,  -- JSON 데이터
    priority INT(3) DEFAULT 50,
    status ENUM('pending', 'processing', 'processed', 'failed') DEFAULT 'pending',
    retry_count INT(3) DEFAULT 0,
    max_retries INT(3) DEFAULT 3,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status_priority (status, priority DESC),
    INDEX idx_emitter (emitter_type, emitter_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 메트릭스 테이블 (KPI 추적)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_metrics (
    id INT(10) NOT NULL AUTO_INCREMENT,
    entity_type ENUM('agent', 'project') NOT NULL,
    entity_id INT(10) NOT NULL,
    kpi_name VARCHAR(100) NOT NULL,
    kpi_value DECIMAL(10,2),
    kpi_unit VARCHAR(50),
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_kpi_time (kpi_name, measured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 재귀 방지 및 루프 감지 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_recursion_guard (
    id INT(10) NOT NULL AUTO_INCREMENT,
    call_chain TEXT,  -- JSON array of agent/project IDs
    depth INT(3) NOT NULL,
    max_allowed_depth INT(3) DEFAULT 10,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT false,
    PRIMARY KEY (id),
    INDEX idx_depth (depth),
    INDEX idx_resolved (resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 학생 승인 요청 테이블
CREATE TABLE IF NOT EXISTS mdl_wxsperta_approval_requests (
    id INT(10) NOT NULL AUTO_INCREMENT,
    user_id INT(10) NOT NULL,
    request_type ENUM('agent_update', 'project_create', 'prop_change', 'system_action') NOT NULL,
    entity_type ENUM('agent', 'project') NOT NULL,
    entity_id INT(10) NOT NULL,
    change_description TEXT,
    old_value TEXT,
    new_value TEXT,
    status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    requested_by_agent_id INT(10) DEFAULT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
    PRIMARY KEY (id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires (expires_at),
    CONSTRAINT fk_approval_agent FOREIGN KEY (requested_by_agent_id) 
        REFERENCES mdl_wxsperta_agents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 대화 컨텍스트 추적 (채팅 세션 관리)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_chat_contexts (
    id INT(10) NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    user_id INT(10) NOT NULL,
    agent_id INT(10) NOT NULL,
    context_summary TEXT,
    emotion_state VARCHAR(50),
    learning_progress TEXT,  -- JSON
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_session (session_id),
    INDEX idx_user_agent (user_id, agent_id),
    CONSTRAINT fk_context_agent FOREIGN KEY (agent_id) 
        REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. 스케줄 작업 큐
CREATE TABLE IF NOT EXISTS mdl_wxsperta_scheduled_tasks (
    id INT(10) NOT NULL AUTO_INCREMENT,
    task_name VARCHAR(100) NOT NULL,
    task_type ENUM('realtime', 'hourly', 'daily', 'weekly') NOT NULL,
    agent_id INT(10) DEFAULT NULL,
    project_id INT(10) DEFAULT NULL,
    payload TEXT,
    cron_expression VARCHAR(100),
    next_run_at TIMESTAMP NOT NULL,
    last_run_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    PRIMARY KEY (id),
    INDEX idx_next_run (next_run_at),
    INDEX idx_task_type (task_type),
    CONSTRAINT fk_task_agent FOREIGN KEY (agent_id) 
        REFERENCES mdl_wxsperta_agents(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_project FOREIGN KEY (project_id) 
        REFERENCES mdl_wxsperta_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. 벡터 스토어 참조 (임베딩 관리)
CREATE TABLE IF NOT EXISTS mdl_wxsperta_embeddings (
    id INT(10) NOT NULL AUTO_INCREMENT,
    entity_type ENUM('agent', 'project', 'interaction', 'document') NOT NULL,
    entity_id INT(10) NOT NULL,
    embedding_key VARCHAR(255) NOT NULL,  -- Vector DB의 키
    content_hash VARCHAR(64),  -- 중복 방지용
    metadata TEXT,  -- JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_key (embedding_key),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_hash (content_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 재귀 쿼리를 위한 뷰 생성
CREATE OR REPLACE VIEW v_agent_hierarchy AS
WITH RECURSIVE agent_tree AS (
    -- Base case: top-level agents
    SELECT 
        id, name, parent_agent_id, depth_level, 
        CAST(id AS CHAR(1000)) AS path,
        0 as calculated_depth
    FROM mdl_wxsperta_agents
    WHERE parent_agent_id IS NULL
    
    UNION ALL
    
    -- Recursive case
    SELECT 
        a.id, a.name, a.parent_agent_id, a.depth_level,
        CONCAT(at.path, '/', a.id) AS path,
        at.calculated_depth + 1 as calculated_depth
    FROM mdl_wxsperta_agents a
    INNER JOIN agent_tree at ON a.parent_agent_id = at.id
    WHERE at.calculated_depth < 10  -- 재귀 깊이 제한
)
SELECT * FROM agent_tree;

-- 프로젝트 계층 뷰
CREATE OR REPLACE VIEW v_project_hierarchy AS
WITH RECURSIVE project_tree AS (
    SELECT 
        id, title, parent_project_id, agent_owner_id, depth_level,
        CAST(id AS CHAR(1000)) AS path,
        0 as calculated_depth
    FROM mdl_wxsperta_projects
    WHERE parent_project_id IS NULL
    
    UNION ALL
    
    SELECT 
        p.id, p.title, p.parent_project_id, p.agent_owner_id, p.depth_level,
        CONCAT(pt.path, '/', p.id) AS path,
        pt.calculated_depth + 1 as calculated_depth
    FROM mdl_wxsperta_projects p
    INNER JOIN project_tree pt ON p.parent_project_id = pt.id
    WHERE pt.calculated_depth < 10
)
SELECT * FROM project_tree;

-- 트리거: 깊이 레벨 자동 계산
DELIMITER //

CREATE TRIGGER before_agent_insert
BEFORE INSERT ON mdl_wxsperta_agents
FOR EACH ROW
BEGIN
    IF NEW.parent_agent_id IS NOT NULL THEN
        SELECT depth_level + 1 INTO NEW.depth_level
        FROM mdl_wxsperta_agents
        WHERE id = NEW.parent_agent_id;
        
        -- 최대 깊이 체크
        IF NEW.depth_level > NEW.max_depth THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Maximum agent depth exceeded';
        END IF;
    END IF;
END//

CREATE TRIGGER before_project_insert
BEFORE INSERT ON mdl_wxsperta_projects
FOR EACH ROW
BEGIN
    IF NEW.parent_project_id IS NOT NULL THEN
        SELECT depth_level + 1 INTO NEW.depth_level
        FROM mdl_wxsperta_projects
        WHERE id = NEW.parent_project_id;
        
        -- 최대 깊이 체크 (프로젝트는 10레벨까지)
        IF NEW.depth_level > 10 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Maximum project depth exceeded';
        END IF;
    END IF;
END//

DELIMITER ;

-- 초기 스케줄 작업 삽입
INSERT INTO mdl_wxsperta_scheduled_tasks (task_name, task_type, cron_expression, next_run_at) VALUES
('CEO Weekly Report', 'weekly', '0 9 * * 1', DATE_ADD(NOW(), INTERVAL 1 WEEK)),
('Daily Command Center', 'daily', '0 8 * * *', DATE_ADD(NOW(), INTERVAL 1 DAY)),
('Execution Pipeline Check', 'hourly', '0 * * * *', DATE_ADD(NOW(), INTERVAL 1 HOUR)),
('Realtime Response', 'realtime', '*/5 * * * *', DATE_ADD(NOW(), INTERVAL 5 MINUTE));