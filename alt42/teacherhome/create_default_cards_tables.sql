-- KTM 코파일럿 기본 카드 정보 테이블
-- 작성일: 2025-01-16
-- 설명: 기본 카드(탭, 아이템) 정보를 DB에서 관리하기 위한 테이블

-- 1. 카테고리 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_key VARCHAR(50) NOT NULL UNIQUE COMMENT '카테고리 키 (quarterly, weekly, daily, realtime, interaction, bias, development, viral, consultation)',
    title VARCHAR(255) NOT NULL COMMENT '카테고리 제목',
    description TEXT NOT NULL COMMENT '카테고리 설명',
    agent_name VARCHAR(255) NOT NULL COMMENT '에이전트 이름',
    agent_role VARCHAR(255) NOT NULL COMMENT '에이전트 역할',
    agent_avatar VARCHAR(10) NOT NULL COMMENT '에이전트 아바타',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    INDEX idx_category_key (category_key),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카테고리 기본 정보';

-- 2. 탭 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_tabs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL COMMENT '카테고리 ID',
    tab_key VARCHAR(50) NOT NULL COMMENT '탭 키',
    title VARCHAR(255) NOT NULL COMMENT '탭 제목',
    description TEXT NOT NULL COMMENT '탭 설명',
    explanation TEXT DEFAULT NULL COMMENT '탭 상세 설명',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (category_id) REFERENCES mdl_ktm_categories(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_tab_key (tab_key),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order),
    UNIQUE KEY unique_category_tab (category_id, tab_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='탭 정보';

-- 3. 아이템 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tab_id INT NOT NULL COMMENT '탭 ID',
    title VARCHAR(255) NOT NULL COMMENT '아이템 제목',
    description TEXT NOT NULL COMMENT '아이템 설명',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    has_chain_interaction TINYINT(1) DEFAULT 0 COMMENT '연쇄상호작용 가능 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (tab_id) REFERENCES mdl_ktm_tabs(id) ON DELETE CASCADE,
    INDEX idx_tab_id (tab_id),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='아이템 정보';

-- 4. 아이템 상세 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_item_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL COMMENT '아이템 ID',
    detail_text VARCHAR(500) NOT NULL COMMENT '상세 내용',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (item_id) REFERENCES mdl_ktm_items(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='아이템 상세 정보';

-- 5. 사용자별 카드 커스터마이즈 테이블 (새로 추가된 카드 정보)
CREATE TABLE IF NOT EXISTS mdl_ktm_user_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '사용자 ID',
    category_id INT NOT NULL COMMENT '카테고리 ID',
    tab_id INT NOT NULL COMMENT '탭 ID',
    title VARCHAR(255) NOT NULL COMMENT '카드 제목',
    description TEXT NOT NULL COMMENT '카드 설명',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    timemodified INT(10) NOT NULL COMMENT '수정 시간',
    
    FOREIGN KEY (category_id) REFERENCES mdl_ktm_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (tab_id) REFERENCES mdl_ktm_tabs(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_tab_id (tab_id),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자별 커스텀 카드';

-- 6. 사용자별 카드 상세 정보 테이블
CREATE TABLE IF NOT EXISTS mdl_ktm_user_card_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL COMMENT '사용자 카드 ID',
    detail_text VARCHAR(500) NOT NULL COMMENT '상세 내용',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    timecreated INT(10) NOT NULL COMMENT '생성 시간',
    
    FOREIGN KEY (card_id) REFERENCES mdl_ktm_user_cards(id) ON DELETE CASCADE,
    INDEX idx_card_id (card_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자별 카드 상세 정보';