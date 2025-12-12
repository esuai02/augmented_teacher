-- ============================================================
-- Hybrid State Stabilization System - Database Schema
-- 하이브리드 상태 안정화 시스템 테이블
-- 
-- MySQL 5.7 호환
-- @version 1.0.0
-- @since 2025-12-06
-- ============================================================

-- 하이브리드 상태 저장 테이블
CREATE TABLE IF NOT EXISTS mdl_at_hybrid_state (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
    
    -- 핵심 상태 변수
    predicted_state FLOAT DEFAULT 0.5 COMMENT '예측된 집중도 (0~1)',
    uncertainty FLOAT DEFAULT 0.1 COMMENT '불확실성 (0~1, 낮을수록 좋음)',
    confidence FLOAT DEFAULT 1.0 COMMENT '확신도 (0~1, 높을수록 확실)',
    
    -- 상태 벡터 (JSON)
    state_vector TEXT COMMENT 'JSON: {focus, flow, struggle, lost}',
    
    -- 핑 히스토리 (최근 10개)
    ping_history TEXT COMMENT 'JSON: 최근 핑 히스토리',
    
    -- 마지막 이벤트 정보
    last_event_type VARCHAR(50) DEFAULT NULL COMMENT '마지막 이벤트 유형',
    last_event_value FLOAT DEFAULT NULL COMMENT '마지막 이벤트 측정값',
    last_kalman_gain FLOAT DEFAULT NULL COMMENT '마지막 Kalman Gain',
    
    -- 타임스탬프
    created_at INT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    updated_at INT(10) NOT NULL COMMENT '수정 시간 (Unix timestamp)',
    
    -- 인덱스
    UNIQUE KEY idx_user (user_id),
    KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='하이브리드 상태 안정화 시스템 - 사용자별 실시간 상태';

-- ============================================================

-- 이벤트 히스토리 테이블 (Kalman Correction 기록)
CREATE TABLE IF NOT EXISTS mdl_at_hybrid_event_log (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
    
    -- 이벤트 정보
    event_type VARCHAR(50) NOT NULL COMMENT '이벤트 유형',
    event_data TEXT COMMENT 'JSON: 이벤트 추가 데이터',
    
    -- 보정 전 상태
    state_before FLOAT NOT NULL COMMENT '보정 전 예측 상태',
    uncertainty_before FLOAT NOT NULL COMMENT '보정 전 불확실성',
    
    -- 측정값 및 Kalman
    measured_value FLOAT NOT NULL COMMENT '측정된 상태값',
    kalman_gain FLOAT NOT NULL COMMENT '적용된 Kalman Gain',
    
    -- 보정 후 상태
    state_after FLOAT NOT NULL COMMENT '보정 후 예측 상태',
    uncertainty_after FLOAT NOT NULL COMMENT '보정 후 불확실성',
    
    -- 타임스탬프
    created_at INT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
    
    -- 인덱스
    KEY idx_user_time (user_id, created_at),
    KEY idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='하이브리드 시스템 이벤트 로그 - Kalman 보정 기록';

-- ============================================================

-- Active Ping 로그 테이블
CREATE TABLE IF NOT EXISTS mdl_at_hybrid_ping_log (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
    ping_id VARCHAR(50) NOT NULL COMMENT '핑 고유 ID',
    
    -- 핑 정보
    ping_level TINYINT(1) NOT NULL DEFAULT 1 COMMENT '핑 레벨 (1=subtle, 2=nudge, 3=alert)',
    confidence_at_fire FLOAT NOT NULL COMMENT '핑 발사 시점 확신도',
    
    -- 반응 정보
    responded TINYINT(1) DEFAULT NULL COMMENT '반응 여부 (1=반응, 0=무반응, NULL=미결)',
    response_time FLOAT DEFAULT NULL COMMENT '반응 시간 (초)',
    response_content VARCHAR(255) DEFAULT NULL COMMENT '응답 내용 (Level 3)',
    
    -- 결과
    collapse_result VARCHAR(20) DEFAULT NULL COMMENT '상태 붕괴 결과 (focus/lost)',
    
    -- 타임스탬프
    fired_at INT(10) NOT NULL COMMENT '핑 발사 시간',
    responded_at INT(10) DEFAULT NULL COMMENT '반응 시간',
    
    -- 인덱스
    UNIQUE KEY idx_ping_id (ping_id),
    KEY idx_user_time (user_id, fired_at),
    KEY idx_level (ping_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Active Ping 로그 - 능동 관측 기록';

-- ============================================================

-- 센서 데이터 집계 테이블 (분당 집계)
CREATE TABLE IF NOT EXISTS mdl_at_hybrid_sensor_agg (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
    
    -- 집계 기간
    period_start INT(10) NOT NULL COMMENT '집계 시작 시간',
    period_end INT(10) NOT NULL COMMENT '집계 종료 시간',
    
    -- 센서 집계 데이터
    avg_mouse_velocity FLOAT DEFAULT 0 COMMENT '평균 마우스 속도',
    avg_scroll_rate FLOAT DEFAULT 0 COMMENT '평균 스크롤 속도',
    avg_keystroke_rate FLOAT DEFAULT 0 COMMENT '평균 키 입력 속도',
    total_pause_duration FLOAT DEFAULT 0 COMMENT '총 멈춤 시간',
    max_pause_duration FLOAT DEFAULT 0 COMMENT '최대 멈춤 시간',
    
    -- 상태 통계
    avg_predicted_state FLOAT DEFAULT 0.5 COMMENT '평균 예측 상태',
    min_confidence FLOAT DEFAULT 1.0 COMMENT '최저 확신도',
    ping_count INT(10) DEFAULT 0 COMMENT '핑 발생 횟수',
    event_count INT(10) DEFAULT 0 COMMENT '이벤트 발생 횟수',
    
    -- 지배 상태
    dominant_state VARCHAR(20) DEFAULT 'focus' COMMENT '지배적 상태',
    
    -- 인덱스
    KEY idx_user_period (user_id, period_start),
    KEY idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='센서 데이터 분당 집계';

-- ============================================================
-- 초기 데이터 삽입 (테스트용)
-- ============================================================

-- 이벤트 신호 강도 참조 테이블 (선택적)
CREATE TABLE IF NOT EXISTS mdl_at_hybrid_event_signals (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT '이벤트 유형',
    signal_value FLOAT NOT NULL COMMENT '신호 강도 (0~1)',
    measurement_noise FLOAT NOT NULL DEFAULT 0.15 COMMENT '측정 노이즈',
    category VARCHAR(20) NOT NULL DEFAULT 'neutral' COMMENT '카테고리 (positive/neutral/negative)',
    description VARCHAR(255) DEFAULT NULL COMMENT '설명',
    
    UNIQUE KEY idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='이벤트 신호 강도 정의';

-- 기본 이벤트 신호 삽입
INSERT INTO mdl_at_hybrid_event_signals (event_type, signal_value, measurement_noise, category, description) VALUES
-- 긍정 신호
('correct_answer', 0.9, 0.05, 'positive', '정답 제출'),
('quick_response', 0.85, 0.05, 'positive', '빠른 응답'),
('scroll_active', 0.7, 0.15, 'positive', '활발한 스크롤'),
('mouse_movement', 0.6, 0.15, 'positive', '마우스 움직임'),
('click_problem', 0.75, 0.15, 'positive', '문제 클릭'),
-- 중립 신호
('page_view', 0.5, 0.20, 'neutral', '페이지 조회'),
('idle_short', 0.4, 0.20, 'neutral', '짧은 멈춤'),
-- 부정 신호
('hint_click', 0.2, 0.05, 'negative', '힌트 클릭'),
('wrong_answer', 0.3, 0.05, 'negative', '오답 제출'),
('skip_problem', 0.15, 0.05, 'negative', '문제 건너뛰기'),
('long_pause', 0.25, 0.10, 'negative', '긴 멈춤'),
('tab_switch', 0.1, 0.10, 'negative', '탭 전환'),
('idle_long', 0.1, 0.15, 'negative', '장시간 비활성')
ON DUPLICATE KEY UPDATE signal_value = VALUES(signal_value);

