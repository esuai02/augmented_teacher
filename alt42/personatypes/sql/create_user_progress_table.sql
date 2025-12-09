-- Create user pattern progress table
-- This table tracks individual user progress for each math learning pattern

-- User Pattern Progress Table
CREATE TABLE IF NOT EXISTS mdl_alt42i_user_pattern_progress (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    pattern_id INT(10) UNSIGNED NOT NULL,
    is_collected TINYINT(1) DEFAULT 0,
    mastery_level INT(3) DEFAULT 0,
    practice_count INT(10) DEFAULT 0,
    last_practice_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_pattern (user_id, pattern_id),
    KEY idx_pattern (pattern_id),
    CONSTRAINT fk_progress_pattern FOREIGN KEY (pattern_id) 
        REFERENCES mdl_alt42i_math_patterns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_pattern (user_id, pattern_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pattern Practice Logs Table (optional)
CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_practice_logs (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    pattern_id INT(10) UNSIGNED NOT NULL,
    practice_type VARCHAR(50) DEFAULT 'self',
    duration_seconds INT(10) DEFAULT 0,
    feedback TEXT DEFAULT NULL,
    is_completed TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_pattern_date (user_id, pattern_id, created_at),
    CONSTRAINT fk_log_pattern FOREIGN KEY (pattern_id) 
        REFERENCES mdl_alt42i_math_patterns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audio Play Logs Table (optional)
CREATE TABLE IF NOT EXISTS mdl_alt42i_audio_play_logs (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    pattern_id INT(10) UNSIGNED NOT NULL,
    played_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_pattern_play (user_id, pattern_id, played_at),
    CONSTRAINT fk_audio_pattern FOREIGN KEY (pattern_id) 
        REFERENCES mdl_alt42i_math_patterns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;