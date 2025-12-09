-- AI 에이전트 프로젝트 시스템 데이터베이스 스키마

-- 사용자 테이블
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- AI 에이전트 카드 테이블
CREATE TABLE IF NOT EXISTS agent_cards (
    id VARCHAR(50) PRIMARY KEY,
    card_number INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 프로젝트 테이블
CREATE TABLE IF NOT EXISTS projects (
    id VARCHAR(50) PRIMARY KEY,
    card_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES agent_cards(id) ON DELETE CASCADE
);

-- 하위 프로젝트 테이블
CREATE TABLE IF NOT EXISTS subprojects (
    id VARCHAR(50) PRIMARY KEY,
    project_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 사용자 진행 상황 테이블
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_item_id VARCHAR(50) NOT NULL,
    item_type ENUM('project', 'subproject') NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, project_item_id, item_type)
);

-- 카드 간 연결 테이블
CREATE TABLE IF NOT EXISTS card_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_card_id VARCHAR(50) NOT NULL,
    target_card_id VARCHAR(50) NOT NULL,
    connection_type VARCHAR(50) DEFAULT 'related',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_card_id) REFERENCES agent_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (target_card_id) REFERENCES agent_cards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_connection (source_card_id, target_card_id)
);

-- 사용자 노트 테이블
CREATE TABLE IF NOT EXISTS user_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(50),
    project_id VARCHAR(50),
    subproject_id VARCHAR(50),
    note_content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES agent_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (subproject_id) REFERENCES subprojects(id) ON DELETE CASCADE
);

-- 사용자 설정 테이블
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme VARCHAR(50) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'ko',
    email_notifications BOOLEAN DEFAULT TRUE,
    progress_visibility ENUM('public', 'private') DEFAULT 'private',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 성과 지표 테이블
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(50) NOT NULL,
    metric_name VARCHAR(255) NOT NULL,
    metric_value DECIMAL(10, 2),
    metric_unit VARCHAR(50),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES agent_cards(id) ON DELETE CASCADE,
    INDEX idx_user_card_time (user_id, card_id, recorded_at)
);

-- 활동 로그 테이블
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(50),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, created_at)
);

-- 카드별 도구 테이블
CREATE TABLE IF NOT EXISTS card_tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(50) NOT NULL,
    tool_name VARCHAR(255) NOT NULL,
    tool_path VARCHAR(255) NOT NULL,
    tool_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES agent_cards(id) ON DELETE CASCADE
);

-- 미래 일기 테이블 (시간 수정체 전용)
CREATE TABLE IF NOT EXISTS future_diaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    future_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    morning_routine TEXT,
    work_life TEXT,
    relationships TEXT,
    achievements TEXT,
    advice_to_present TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 인덱스 생성
CREATE INDEX idx_projects_card ON projects(card_id);
CREATE INDEX idx_subprojects_project ON subprojects(project_id);
CREATE INDEX idx_progress_user ON user_progress(user_id);
CREATE INDEX idx_notes_user ON user_notes(user_id);