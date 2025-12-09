-- 🌌 개인화 내러티브 시스템 데이터베이스 스키마
-- Shining Stars - 편향 감지 및 우주적 서사 시스템

-- 1. 사용자 채팅 메시지 테이블
CREATE TABLE IF NOT EXISTS ss_chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    message_text TEXT NOT NULL,
    message_type ENUM('user_input', 'ai_response', 'system_message') NOT NULL,
    emotional_tone VARCHAR(50),
    bias_indicators JSON,
    context_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    INDEX idx_emotional_tone (emotional_tone)
);

-- 2. 사용자 상호작용 로그 테이블
CREATE TABLE IF NOT EXISTS ss_user_interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    event_type VARCHAR(100) NOT NULL,
    event_data JSON,
    bias_detected JSON,
    intervention_triggered BOOLEAN DEFAULT FALSE,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_event_type (event_type),
    INDEX idx_timestamp (timestamp)
);

-- 3. 편향 감지 기록 테이블
CREATE TABLE IF NOT EXISTS ss_bias_detections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    bias_type VARCHAR(100) NOT NULL,
    confidence_score DECIMAL(3,2) NOT NULL,
    evidence TEXT,
    context_phase VARCHAR(50),
    intervention_applied BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_bias_type (bias_type),
    INDEX idx_confidence_score (confidence_score),
    INDEX idx_created_at (created_at)
);

-- 4. 사용자 편향 프로필 테이블
CREATE TABLE IF NOT EXISTS ss_user_bias_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    profile_data JSON NOT NULL,
    dominant_biases JSON,
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    last_analysis_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_risk_level (risk_level),
    INDEX idx_updated_at (updated_at)
);

-- 5. 우주적 원형 할당 테이블
CREATE TABLE IF NOT EXISTS ss_cosmic_archetypes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    archetype_type VARCHAR(100) NOT NULL,
    archetype_data JSON NOT NULL,
    mentor_type VARCHAR(100),
    personality_traits JSON,
    journey_stage VARCHAR(50) DEFAULT 'beginning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_archetype_type (archetype_type),
    INDEX idx_journey_stage (journey_stage)
);

-- 6. 내러티브 생성 히스토리 테이블
CREATE TABLE IF NOT EXISTS ss_narrative_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    narrative_phase VARCHAR(100) NOT NULL,
    narrative_content TEXT NOT NULL,
    context_data JSON,
    user_response TEXT,
    effectiveness_score DECIMAL(3,2),
    generation_metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_narrative_phase (narrative_phase),
    INDEX idx_created_at (created_at),
    INDEX idx_effectiveness_score (effectiveness_score)
);

-- 7. 사용자 분석 결과 테이블
CREATE TABLE IF NOT EXISTS ss_user_analysis_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    analysis_data JSON NOT NULL,
    personality_type VARCHAR(100),
    learning_style JSON,
    confidence_level DECIMAL(3,2),
    analysis_version VARCHAR(20) DEFAULT '1.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_personality_type (personality_type),
    INDEX idx_updated_at (updated_at)
);

-- 8. 사용자 세션 정보 테이블
CREATE TABLE IF NOT EXISTS ss_user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    node_id INT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    time_spent INT DEFAULT 0, -- seconds
    completed BOOLEAN DEFAULT FALSE,
    answer_text TEXT,
    answer_quality ENUM('excellent', 'good', 'basic', 'incomplete') DEFAULT 'incomplete',
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_node_id (node_id),
    INDEX idx_completed (completed),
    INDEX idx_created_at (created_at)
);

-- 9. 사용자 진행 상황 테이블
CREATE TABLE IF NOT EXISTS ss_user_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    completed_nodes JSON,
    unlocked_nodes JSON,
    current_node INT,
    total_time_spent INT DEFAULT 0, -- seconds
    achievement_count INT DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_current_node (current_node),
    INDEX idx_last_activity (last_activity)
);

-- 10. 감정 추적 테이블
CREATE TABLE IF NOT EXISTS ss_emotional_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    emotion_state VARCHAR(50) NOT NULL,
    intensity_level DECIMAL(3,2) NOT NULL,
    confidence_level DECIMAL(3,2),
    context_trigger VARCHAR(200),
    detected_method ENUM('text_analysis', 'behavior_pattern', 'user_input') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_emotion_state (emotion_state),
    INDEX idx_created_at (created_at)
);

-- 11. 개입 이력 테이블
CREATE TABLE IF NOT EXISTS ss_intervention_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    intervention_type ENUM('urgent', 'preventive', 'gentle_guidance') NOT NULL,
    trigger_bias VARCHAR(100),
    intervention_content TEXT NOT NULL,
    user_response TEXT,
    effectiveness_rating INT, -- 1-5 scale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_intervention_type (intervention_type),
    INDEX idx_trigger_bias (trigger_bias),
    INDEX idx_created_at (created_at)
);

-- 12. 교사 관찰 메모 테이블
CREATE TABLE IF NOT EXISTS ss_teacher_observations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    observed_biases JSON,
    emotional_state VARCHAR(50),
    detailed_observation TEXT,
    lesson_context JSON,
    urgency_level ENUM('normal', 'high', 'critical') DEFAULT 'normal',
    follow_up_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_student_id (student_id),
    INDEX idx_urgency_level (urgency_level),
    INDEX idx_created_at (created_at)
);

-- 13. AI 응답 생성 로그 테이블
CREATE TABLE IF NOT EXISTS ss_ai_response_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    input_text TEXT NOT NULL,
    generated_response TEXT NOT NULL,
    response_type VARCHAR(100),
    personalization_factors JSON,
    tokens_used INT,
    generation_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_response_type (response_type),
    INDEX idx_created_at (created_at)
);

-- 14. 시스템 성능 메트릭스 테이블
CREATE TABLE IF NOT EXISTS ss_system_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    user_id INT,
    session_id VARCHAR(255),
    additional_data JSON,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_metric_type (metric_type),
    INDEX idx_user_id (user_id),
    INDEX idx_recorded_at (recorded_at)
);

-- 15. 프롬프트 템플릿 테이블 (기존 확장)
CREATE TABLE IF NOT EXISTS ss_prompt_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(200) NOT NULL,
    template_category VARCHAR(100) NOT NULL,
    template_text TEXT NOT NULL,
    personalization_variables JSON,
    archetype_specific VARCHAR(100),
    bias_specific VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    effectiveness_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_template_name (template_name),
    INDEX idx_template_category (template_category),
    INDEX idx_archetype_specific (archetype_specific),
    INDEX idx_bias_specific (bias_specific),
    INDEX idx_is_active (is_active)
);

-- 초기 데이터 삽입

-- 기본 프롬프트 템플릿 데이터
INSERT INTO ss_prompt_templates (template_name, template_category, template_text, personalization_variables, archetype_specific) VALUES
('problem_opening_reluctant_explorer', 'opening', '{name}님, 새로운 수학 행성이 눈앞에 나타났어요. 마음속에선 "할 수 있을까?" 하는 작은 목소리가 들리네요. 🌱 괜찮아요, 천천히 가도 돼요.', '["name"]', 'reluctant_explorer'),
('problem_opening_curious_wanderer', 'opening', '오! 흥미로운 수학 현상이 {name}의 망원경에 포착되었어요! 이 신비로운 패턴의 정체는 무엇일까요? 🔭', '["name"]', 'curious_wanderer'),
('bias_intervention_confirmation_bias', 'intervention', '{name}님, 확증편향의 중력장이 감지되었어요! 🕳️ 지금 하나의 별만 보고 계시는군요. 하지만 우주에는 무수한 별자리가 있어요!', '["name"]', NULL),
('bias_intervention_catastrophizing', 'intervention', '⚠️ {name}님, 재앙화사고 소행성이 접근 중이에요! 작은 운석을 행성 충돌로 보고 계시는군요. 실제로는 아름다운 유성우일 수도 있어요! 💫', '["name"]', NULL);

-- 기본 우주적 원형 데이터
INSERT INTO ss_cosmic_archetypes (user_id, archetype_type, archetype_data, mentor_type, personality_traits, journey_stage) VALUES
(0, 'template_reluctant_explorer', '{"name": "망설이는 탐험가", "traits": ["자기의심", "신중함", "성장잠재력"], "cosmic_symbol": "🌱", "journey_arc": "두려움 → 용기 → 마스터리"}', 'nurturing_mother', '{"openness": 0.4, "conscientiousness": 0.7, "confidence": 0.3}', 'beginning'),
(0, 'template_curious_wanderer', '{"name": "호기심 많은 방랑자", "traits": ["탐구심", "개방성", "산만함"], "cosmic_symbol": "🔭", "journey_arc": "산만함 → 집중 → 통찰"}', 'playful_trickster', '{"openness": 0.8, "conscientiousness": 0.4, "confidence": 0.6}', 'beginning');

-- 인덱스 최적화를 위한 추가 인덱스
CREATE INDEX idx_chat_messages_user_created ON ss_chat_messages(user_id, created_at);
CREATE INDEX idx_interactions_user_timestamp ON ss_user_interactions(user_id, timestamp);
CREATE INDEX idx_bias_detections_user_type ON ss_bias_detections(user_id, bias_type);
CREATE INDEX idx_narrative_user_phase ON ss_narrative_history(user_id, narrative_phase);

-- 뷰 생성: 사용자 대시보드용
CREATE VIEW v_user_dashboard AS
SELECT 
    u.id as user_id,
    u.firstname,
    u.lastname,
    up.completed_nodes,
    up.unlocked_nodes,
    up.current_node,
    up.total_time_spent,
    up.achievement_count,
    up.last_activity,
    ubp.dominant_biases,
    ubp.risk_level,
    ca.archetype_type,
    ca.journey_stage
FROM mdl_user u
LEFT JOIN ss_user_progress up ON u.id = up.user_id
LEFT JOIN ss_user_bias_profiles ubp ON u.id = ubp.user_id
LEFT JOIN ss_cosmic_archetypes ca ON u.id = ca.user_id;

-- 뷰 생성: 교사 모니터링용
CREATE VIEW v_teacher_monitoring AS
SELECT 
    s.id as student_id,
    s.firstname,
    s.lastname,
    ubp.risk_level,
    ubp.dominant_biases,
    COUNT(bd.id) as recent_bias_detections,
    MAX(bd.created_at) as last_bias_detection,
    COUNT(ih.id) as intervention_count,
    AVG(ih.effectiveness_rating) as avg_intervention_effectiveness,
    up.current_node,
    up.last_activity
FROM mdl_user s
LEFT JOIN ss_user_bias_profiles ubp ON s.id = ubp.user_id
LEFT JOIN ss_bias_detections bd ON s.id = bd.user_id 
    AND bd.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
LEFT JOIN ss_intervention_history ih ON s.id = ih.user_id
    AND ih.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
LEFT JOIN ss_user_progress up ON s.id = up.user_id
GROUP BY s.id;

-- 성능 모니터링을 위한 프로시저
DELIMITER //
CREATE PROCEDURE sp_update_user_analysis(IN p_user_id INT)
BEGIN
    DECLARE v_dominant_biases JSON;
    DECLARE v_risk_level VARCHAR(20);
    
    -- 최근 편향 감지 데이터 기반으로 분석
    SELECT 
        JSON_ARRAYAGG(JSON_OBJECT('bias', bias_type, 'frequency', bias_count))
    INTO v_dominant_biases
    FROM (
        SELECT bias_type, COUNT(*) as bias_count
        FROM ss_bias_detections 
        WHERE user_id = p_user_id 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY bias_type
        ORDER BY bias_count DESC
        LIMIT 5
    ) bias_summary;
    
    -- 위험도 계산
    SELECT CASE 
        WHEN AVG(confidence_score) >= 0.8 THEN 'critical'
        WHEN AVG(confidence_score) >= 0.6 THEN 'high'
        WHEN AVG(confidence_score) >= 0.4 THEN 'medium'
        ELSE 'low'
    END INTO v_risk_level
    FROM ss_bias_detections 
    WHERE user_id = p_user_id 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- 프로필 업데이트
    INSERT INTO ss_user_bias_profiles (user_id, profile_data, dominant_biases, risk_level)
    VALUES (p_user_id, '{}', v_dominant_biases, COALESCE(v_risk_level, 'low'))
    ON DUPLICATE KEY UPDATE 
        dominant_biases = v_dominant_biases,
        risk_level = COALESCE(v_risk_level, 'low'),
        updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- 자동 백업을 위한 이벤트 (선택사항)
-- CREATE EVENT ev_daily_metrics_backup
-- ON SCHEDULE EVERY 1 DAY
-- DO
-- INSERT INTO ss_system_metrics (metric_type, metric_value, additional_data)
-- SELECT 'daily_active_users', COUNT(DISTINCT user_id), JSON_OBJECT('date', CURDATE())
-- FROM ss_user_interactions 
-- WHERE DATE(timestamp) = CURDATE();